<?php

namespace App\Controllers;

use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\ProductModel;
use App\Models\ProductImageModel;
use App\Models\ShopModel;
use App\Models\ShippingAddressModel;
use App\Models\DeliveryModel;

class CustomerOrderController extends BaseController
{
    /**
     * Dedicated live tracking page for Doorstep Delivery orders.
     * Route: /customer/orders/track/{order_id}
     *
     * @param string|int|null $orderRef
     * @return \CodeIgniter\HTTP\ResponseInterface|string
     */
    public function trackOrder($orderRef = null)
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        if (empty($orderRef)) {
            return redirect()->to('/customer/orders')->with('error', 'Please specify an order to track.');
        }

        $orderModel = new OrderModel();
        $order = null;

        if (is_numeric($orderRef)) {
            $order = $orderModel->where('id', (int) $orderRef)->first();
        }

        if (!$order) {
            $order = $orderModel->where('order_number', (string) $orderRef)->first();
        }

        if (!$order && is_numeric($orderRef)) {
            $order = $orderModel->where('order_number', 'ORD-' . $orderRef)->first();
        }

        // Ownership verification: Customer must own this order
        if (!$order || (int) $order['customer_id'] !== (int) $userId) {
            return redirect()->to('/customer/orders')->with('error', 'Order not found or access denied.');
        }

        // Fulfillment type check: Must be Doorstep Delivery
        $fulfillment = strtolower(trim((string) ($order['fulfillment_method'] ?? 'delivery')));
        if ($fulfillment === 'pickup' || $fulfillment === 'store pick-up') {
            return redirect()->to('/customer/orders')->with('error', 'This order is for Store Pick-up. Please view your Store Pick-up QR Pass.');
        }

        // Fetch Shop info
        $shopModel = new ShopModel();
        $shop = $shopModel->find($order['shop_id']) ?? [];

        // Fetch Shipping Address
        $shippingAddressModel = new ShippingAddressModel();
        $shippingAddress = !empty($order['shipping_address_id'])
            ? $shippingAddressModel->find($order['shipping_address_id'])
            : null;

        $destAddressText = '';
        if ($shippingAddress) {
            $parts = array_filter([
                $shippingAddress['address_line1'] ?? '',
                $shippingAddress['address_line2'] ?? '',
                $shippingAddress['city'] ?? 'Polomolok',
                $shippingAddress['province'] ?? 'South Cotabato',
                $shippingAddress['postal_code'] ?? '',
            ]);
            $destAddressText = implode(', ', $parts);
        }

        // Fetch Order Items with product images and names
        $orderItemModel = new OrderItemModel();
        $items = $orderItemModel->where('order_id', $order['id'])->findAll();

        $productIds = [];
        foreach ($items as $it) {
            if (!empty($it['product_id'])) {
                $productIds[(int) $it['product_id']] = true;
            }
        }

        $productNames   = [];
        $imageByProduct = [];
        if (!empty($productIds)) {
            $productModel = new ProductModel();
            $prodRows = $productModel->builder()
                ->select('id, name')
                ->whereIn('id', array_keys($productIds))
                ->get()->getResultArray();
            foreach ($prodRows as $pr) {
                $productNames[(int) $pr['id']] = $pr['name'];
            }

            $productImageModel = new ProductImageModel();
            $imgRows = $productImageModel->builder()
                ->whereIn('product_id', array_keys($productIds))
                ->orderBy('is_primary', 'DESC')
                ->orderBy('sort_order', 'ASC')
                ->get()->getResultArray();
            foreach ($imgRows as $img) {
                $pid = (int) $img['product_id'];
                if (!isset($imageByProduct[$pid])) {
                    $imageByProduct[$pid] = $img['image_url'];
                }
            }
        }

        foreach ($items as &$item) {
            $pid = (int) ($item['product_id'] ?? 0);
            $img = $imageByProduct[$pid] ?? ($item['image_url'] ?? '');
            if (!empty($img) && !str_starts_with($img, 'http://') && !str_starts_with($img, 'https://')) {
                $img = base_url($img);
            }
            $item['image_url'] = $img;
            if (empty($item['product_name']) && isset($productNames[$pid])) {
                $item['product_name'] = $productNames[$pid];
            }
            if (empty($item['product_name'])) {
                $item['product_name'] = 'Product Item';
            }
        }
        unset($item);

        // Fetch delivery record
        $deliveryModel = new DeliveryModel();
        $delivery = $deliveryModel->where('deliverable_type', 'order')
            ->where('deliverable_id', (int) $order['id'])
            ->first();

        if (!$delivery) {
            $deliveryModel->syncMissingDeliveries((int) $order['shop_id']);
            $delivery = $deliveryModel->where('deliverable_type', 'order')
                ->where('deliverable_id', (int) $order['id'])
                ->first();
        }

        if (empty($destAddressText) && !empty($delivery['destination_address'])) {
            $destAddressText = $delivery['destination_address'];
        }
        if (empty($destAddressText)) {
            $destAddressText = 'Purok 4, Brgy. Cannery Site, Polomolok, South Cotabato';
        }

        // Coordinates calculations (Polomolok area)
        // Store base: Polomolok Poblacion
        $storeLat = 6.2217;
        $storeLng = 125.0667;
        $storeCoords = [$storeLat, $storeLng];

        // Deterministic destination coordinates in Polomolok area
        $seed = (int) $order['id'];
        $destLat = round(6.2300 + ((($seed * 17) % 31) - 15) * 0.0016, 6);
        $destLng = round(125.0750 + ((($seed * 23) % 31) - 15) * 0.0016, 6);
        $destCoords = [$destLat, $destLng];

        $status = strtolower(trim((string) $order['status']));

        // Courier position calculation based on fulfillment status
        if (in_array($status, ['delivered', 'completed'], true)) {
            $courierCoords = $destCoords;
        } elseif (in_array($status, ['shipped', 'in_transit'], true)) {
            if ($delivery && !empty($delivery['current_lat']) && !empty($delivery['current_lng']) && DeliveryModel::isPolomolokCoordinate((float) $delivery['current_lat'], (float) $delivery['current_lng'])) {
                $courierCoords = [(float) $delivery['current_lat'], (float) $delivery['current_lng']];
            } else {
                // Courier is ~70% along the path towards customer destination
                $cLat = round($storeLat + 0.70 * ($destLat - $storeLat), 6);
                $cLng = round($storeLng + 0.70 * ($destLng - $storeLng), 6);
                $courierCoords = [$cLat, $cLng];
            }
        } else {
            // Still preparing at store
            $courierCoords = $storeCoords;
        }

        // Courier info
        $courierName = 'Blax Express Rider';
        if ($delivery && !empty($delivery['courier_name']) && !in_array($delivery['courier_name'], ['Store Courier', 'Store Pick-up'], true)) {
            $courierName = $delivery['courier_name'];
        } else {
            $courierName = 'Arnel Bautista';
        }
        $courierPhone = $delivery['courier_phone'] ?? '0917-889-2144';
        $courierVehicle = 'Motorcycle • Honda Click 125i (Plate: 842-MCG)';

        // Estimated delivery timing & progress percentage
        $progressPercent = 15;
        $estimatedArrival = 'Estimated Delivery: Today, by 5:30 PM';
        $statusBadge = 'In Transit';
        $badgeClass = 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300';

        switch ($status) {
            case 'pending':
                $progressPercent = 20;
                $estimatedArrival = 'Estimated Delivery: Tomorrow, by 3:00 PM';
                $statusBadge = 'Order Placed';
                $badgeClass = 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
                break;
            case 'processing':
                $progressPercent = 45;
                $estimatedArrival = 'Estimated Delivery: Today, by 5:30 PM';
                $statusBadge = 'Packed & Ready';
                $badgeClass = 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300';
                break;
            case 'shipped':
            case 'in_transit':
                $progressPercent = 75;
                $estimatedArrival = 'Estimated Delivery: Today, by ' . date('g:i A', strtotime('+45 minutes'));
                $statusBadge = 'Out for Delivery';
                $badgeClass = 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300';
                break;
            case 'delivered':
            case 'completed':
                $progressPercent = 100;
                $compTime = !empty($order['completed_at']) ? strtotime($order['completed_at']) : time();
                $estimatedArrival = 'Delivered: ' . date('M d, Y h:i A', $compTime);
                $statusBadge = 'Delivered';
                $badgeClass = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300';
                break;
            case 'cancelled':
                $progressPercent = 0;
                $estimatedArrival = 'Order Cancelled';
                $statusBadge = 'Cancelled';
                $badgeClass = 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300';
                break;
            default:
                $progressPercent = 50;
                $statusBadge = ucfirst(str_replace('_', ' ', $status));
        }

        // Timeline milestones
        $placedTs = !empty($order['placed_at']) ? strtotime($order['placed_at']) : (!empty($order['created_at']) ? strtotime($order['created_at']) : time() - 3600);
        $confirmedTs = $placedTs + (35 * 60);
        $shippedTs = $placedTs + (75 * 60);
        $deliveredTs = !empty($order['completed_at']) ? strtotime($order['completed_at']) : ($placedTs + (120 * 60));

        $milestones = [
            [
                'title'       => 'Order Placed',
                'description' => 'Your order has been received by the merchant.',
                'timestamp'   => date('M d, Y h:i A', $placedTs),
                'icon'        => 'receipt_long',
                'state'       => 'completed', // completed, active, pending
            ],
            [
                'title'       => 'Order Confirmed & Packed by Store',
                'description' => 'Shop has verified the inventory and securely packed your items.',
                'timestamp'   => in_array($status, ['processing', 'shipped', 'in_transit', 'delivered', 'completed'], true) ? date('M d, Y h:i A', $confirmedTs) : 'Pending preparation',
                'icon'        => 'inventory_2',
                'state'       => in_array($status, ['shipped', 'in_transit', 'delivered', 'completed'], true) ? 'completed' : ($status === 'processing' ? 'active' : 'pending'),
            ],
            [
                'title'       => 'Handed to Delivery Courier',
                'description' => 'Dispatched from the store hub to courier rider.',
                'timestamp'   => in_array($status, ['shipped', 'in_transit', 'delivered', 'completed'], true) ? (!empty($delivery['shipped_at']) ? date('M d, Y h:i A', strtotime($delivery['shipped_at'])) : date('M d, Y h:i A', $shippedTs)) : 'Awaiting dispatch',
                'icon'        => 'local_shipping',
                'state'       => in_array($status, ['shipped', 'in_transit', 'delivered', 'completed'], true) ? 'completed' : 'pending',
            ],
            [
                'title'       => 'Out for Delivery',
                'description' => 'Rider is en route to your shipping address.',
                'timestamp'   => in_array($status, ['delivered', 'completed'], true) ? 'Delivered' : (in_array($status, ['shipped', 'in_transit'], true) ? 'In progress • En route' : 'Pending courier arrival'),
                'icon'        => 'two_wheeler',
                'state'       => in_array($status, ['delivered', 'completed'], true) ? 'completed' : (in_array($status, ['shipped', 'in_transit'], true) ? 'active' : 'pending'),
            ],
            [
                'title'       => 'Delivered',
                'description' => 'Package safely handed over at your doorstep.',
                'timestamp'   => in_array($status, ['delivered', 'completed'], true) ? date('M d, Y h:i A', $deliveredTs) : 'Estimated upon arrival',
                'icon'        => 'check_circle',
                'state'       => in_array($status, ['delivered', 'completed'], true) ? 'completed' : 'pending',
            ],
        ];

        return view('customer/orders/track', [
            'title'            => 'Track Order #' . ($order['order_number'] ?? $order['id']),
            'order'            => $order,
            'items'            => $items,
            'shop'             => $shop,
            'shippingAddress'  => $shippingAddress,
            'delivery'         => $delivery,
            'courierName'      => $courierName,
            'courierPhone'     => $courierPhone,
            'courierVehicle'   => $courierVehicle,
            'progressPercent'  => $progressPercent,
            'estimatedArrival' => $estimatedArrival,
            'statusBadge'      => $statusBadge,
            'badgeClass'       => $badgeClass,
            'milestones'       => $milestones,
            'storeCoords'      => $storeCoords,
            'destCoords'       => $destCoords,
            'courierCoords'    => $courierCoords,
            'destAddressText'  => $destAddressText,
        ]);
    }
}
