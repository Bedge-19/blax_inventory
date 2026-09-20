<?php

namespace App\Controllers;

use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\ProductModel;
use App\Models\ProductImageModel;
use App\Models\ShopModel;
use App\Models\ShippingAddressModel;
use App\Models\DeliveryModel;
use App\Services\GoogleMapsService;

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

        // Live delivery tracking is only available once the order has been shipped
        $status = strtolower(trim((string) $order['status']));
        if (!in_array($status, ['shipped', 'in_transit', 'delivered', 'completed'], true)) {
            return redirect()->to('/customer/orders')->with('error', 'Live delivery tracking will be available once the store has marked your order as shipped.');
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
        $mapsService = new GoogleMapsService();
        $defaultCoords = $mapsService->getDefaultCoordinates();

        // 1. Store base coordinates
        if (!empty($shop['latitude']) && !empty($shop['longitude']) && (float) $shop['latitude'] != 0) {
            $storeLat = (float) $shop['latitude'];
            $storeLng = (float) $shop['longitude'];
        } else {
            $shopAddr = implode(', ', array_filter([$shop['street'] ?? '', $shop['barangay'] ?? '', $shop['address_line'] ?? '', 'Polomolok', 'South Cotabato']));
            $geoShop = $mapsService->geocodeAddress($shopAddr);
            if ($geoShop) {
                $storeLat = $geoShop['lat'];
                $storeLng = $geoShop['lng'];
                $shopModel->update($shop['id'], [
                    'latitude'    => $storeLat,
                    'longitude'   => $storeLng,
                    'geocoded_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                $storeLat = $defaultCoords['lat'];
                $storeLng = $defaultCoords['lng'];
            }
        }
        $storeCoords = [$storeLat, $storeLng];

        // 2. Destination coordinates
        if ($shippingAddress && !empty($shippingAddress['latitude']) && !empty($shippingAddress['longitude']) && (float) $shippingAddress['latitude'] != 0) {
            $destLat = (float) $shippingAddress['latitude'];
            $destLng = (float) $shippingAddress['longitude'];
        } else {
            $geoDest = !empty($destAddressText) ? $mapsService->geocodeAddress($destAddressText) : null;
            if ($geoDest) {
                $destLat = $geoDest['lat'];
                $destLng = $geoDest['lng'];
                if ($shippingAddress && !empty($shippingAddress['id'])) {
                    $shippingAddressModel->update($shippingAddress['id'], [
                        'latitude'    => $destLat,
                        'longitude'   => $destLng,
                        'place_id'    => $geoDest['place_id'] ?? null,
                        'geocoded_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            } else {
                $seed = (int) $order['id'];
                $destLat = round(6.2300 + ((($seed * 17) % 31) - 15) * 0.0016, 6);
                $destLng = round(125.0750 + ((($seed * 23) % 31) - 15) * 0.0016, 6);
            }
        }
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

    /**
     * AJAX proxy to compute road routes via Google Routes API.
     * Route: POST /api/route
     */
    public function computeRoute()
    {
        $json = $this->request->getJSON(true);
        if (!$json || empty($json['origin']) || empty($json['destination'])) {
            $origin = [
                'lat' => (float) $this->request->getPost('origin_lat'),
                'lng' => (float) $this->request->getPost('origin_lng'),
            ];
            $dest = [
                'lat' => (float) $this->request->getPost('dest_lat'),
                'lng' => (float) $this->request->getPost('dest_lng'),
            ];
        } else {
            $origin = [
                'lat' => (float) ($json['origin']['lat'] ?? $json['origin']['latitude'] ?? 0),
                'lng' => (float) ($json['origin']['lng'] ?? $json['origin']['longitude'] ?? 0),
            ];
            $dest = [
                'lat' => (float) ($json['destination']['lat'] ?? $json['destination']['latitude'] ?? 0),
                'lng' => (float) ($json['destination']['lng'] ?? $json['destination']['longitude'] ?? 0),
            ];
        }

        if ($origin['lat'] == 0 || $origin['lng'] == 0 || $dest['lat'] == 0 || $dest['lng'] == 0) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Invalid coordinates provided.',
            ]);
        }

        $mapsService = new GoogleMapsService();
        $route = $mapsService->computeRoute($origin, $dest);

        if (!$route) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Failed to compute route from Google Routes API.',
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'route'   => $route,
        ]);
    }

    /**
     * AJAX endpoint for customer live position polling.
     * Route: GET customer/orders/track/(:any)/position
     */
    public function getDeliveryPosition($orderRef = null)
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'error'   => 'Unauthorized',
            ]);
        }

        if (empty($orderRef)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => 'Missing order reference',
            ]);
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

        if (!$order || (int) $order['customer_id'] !== (int) $userId) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'Order not found or access denied.',
            ]);
        }

        $deliveryModel = new DeliveryModel();
        $delivery = $deliveryModel->where('deliverable_type', 'order')
            ->where('deliverable_id', (int) $order['id'])
            ->first();

        $status = strtolower(trim((string) $order['status']));
        if (!in_array($status, ['shipped', 'in_transit', 'delivered', 'completed'], true)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => 'Live tracking is not available until the order has been shipped.',
            ]);
        }

        $lat = null;
        $lng = null;

        if ($delivery && !empty($delivery['current_lat']) && !empty($delivery['current_lng']) && DeliveryModel::isPolomolokCoordinate((float) $delivery['current_lat'], (float) $delivery['current_lng'])) {
            $lat = (float) $delivery['current_lat'];
            $lng = (float) $delivery['current_lng'];
        } else {
            $shop = (new ShopModel())->find($order['shop_id']);
            if ($shop && !empty($shop['latitude']) && !empty($shop['longitude']) && DeliveryModel::isPolomolokCoordinate((float) $shop['latitude'], (float) $shop['longitude'])) {
                $lat = (float) $shop['latitude'];
                $lng = (float) $shop['longitude'];
            } else {
                $lat = DeliveryModel::POLOMOLOK_CENTER_LAT;
                $lng = DeliveryModel::POLOMOLOK_CENTER_LNG;
            }
        }

        return $this->response->setJSON([
            'success'         => true,
            'status'          => $status,
            'delivery_status' => $delivery['status'] ?? 'pending',
            'lat'             => $lat,
            'lng'             => $lng,
            'updated_at'      => $delivery['location_updated_at'] ?? $delivery['updated_at'] ?? null,
        ]);
    }

    /**
     * Dedicated live tracking page for Doorstep Delivery printing requests.
     * Route: /customer/printing/track/{ref}
     *
     * @param string|int|null $reqRef
     * @return \CodeIgniter\HTTP\ResponseInterface|string
     */
    public function trackPrintingRequest($reqRef = null)
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        if (empty($reqRef)) {
            return redirect()->to('/customer/printing')->with('error', 'Please specify a printing request to track.');
        }

        $prModel = new \App\Models\PrintingRequestModel();
        $req = null;

        if (is_numeric($reqRef)) {
            $req = $prModel->find((int) $reqRef);
        }
        if (!$req) {
            $req = $prModel->where('request_number', (string) $reqRef)->first();
        }
        if (!$req && is_numeric($reqRef)) {
            $req = $prModel->where('request_number', 'PR-' . $reqRef)->first();
        }

        if (!$req || (int) $req['customer_id'] !== (int) $userId) {
            return redirect()->to('/customer/printing')->with('error', 'Printing request not found or access denied.');
        }

        $fulfillment = strtolower(trim((string) ($req['fulfillment_method'] ?? 'pickup')));
        if ($fulfillment === 'pickup') {
            return redirect()->to('/customer/printing')->with('error', 'This printing request is for Store Pick-up. Please view your Store Pick-up QR Pass.');
        }

        $status = strtolower(trim((string) $req['status']));
        if (!in_array($status, ['ready_for_delivery', 'shipped', 'in_transit', 'delivered', 'completed'], true)) {
            return redirect()->to('/customer/printing')->with('error', 'Live delivery tracking will be available once the store has dispatched your printing request for delivery.');
        }

        // Fetch Shop
        $shopModel = new ShopModel();
        $shop = $shopModel->find($req['shop_id']) ?? [];

        // Customer user & address
        $userModel = new \App\Models\UserModel();
        $customer = $userModel->find($userId);

        $shippingAddressModel = new ShippingAddressModel();
        $shippingAddress = $shippingAddressModel->where('user_id', $userId)->where('is_default', 1)->first()
            ?? $shippingAddressModel->where('user_id', $userId)->first();

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

        // Fetch delivery record
        $deliveryModel = new DeliveryModel();
        $delivery = $deliveryModel->where('deliverable_type', 'printing_request')
            ->where('deliverable_id', (int) $req['id'])
            ->first();

        if (!$delivery) {
            $deliveryModel->syncMissingDeliveries((int) $req['shop_id']);
            $delivery = $deliveryModel->where('deliverable_type', 'printing_request')
                ->where('deliverable_id', (int) $req['id'])
                ->first();
        }

        if (empty($destAddressText) && !empty($delivery['destination_address']) && $delivery['destination_address'] !== 'Doorstep Delivery') {
            $destAddressText = $delivery['destination_address'];
        }
        if (empty($destAddressText)) {
            $destAddressText = 'Poblacion, Polomolok, South Cotabato';
        }

        // Coordinates calculations
        $mapsService = new GoogleMapsService();
        $defaultCoords = $mapsService->getDefaultCoordinates();

        // 1. Store coords
        if (!empty($shop['latitude']) && !empty($shop['longitude']) && (float) $shop['latitude'] != 0) {
            $storeLat = (float) $shop['latitude'];
            $storeLng = (float) $shop['longitude'];
        } else {
            $storeLat = $defaultCoords['lat'];
            $storeLng = $defaultCoords['lng'];
        }
        $storeCoords = [$storeLat, $storeLng];

        // 2. Destination coords
        if ($shippingAddress && !empty($shippingAddress['latitude']) && !empty($shippingAddress['longitude']) && (float) $shippingAddress['latitude'] != 0) {
            $destLat = (float) $shippingAddress['latitude'];
            $destLng = (float) $shippingAddress['longitude'];
        } else {
            $geoDest = !empty($destAddressText) ? $mapsService->geocodeAddress($destAddressText) : null;
            if ($geoDest) {
                $destLat = $geoDest['lat'];
                $destLng = $geoDest['lng'];
            } else {
                $seed = (int) $req['id'];
                $destLat = round(6.2300 + ((($seed * 17) % 31) - 15) * 0.0016, 6);
                $destLng = round(125.0750 + ((($seed * 23) % 31) - 15) * 0.0016, 6);
            }
        }
        $destCoords = [$destLat, $destLng];

        // 3. Courier coords
        if (in_array($status, ['delivered', 'completed'], true)) {
            $courierCoords = $destCoords;
        } elseif (in_array($status, ['shipped', 'in_transit', 'ready_for_delivery'], true)) {
            if ($delivery && !empty($delivery['current_lat']) && !empty($delivery['current_lng']) && DeliveryModel::isPolomolokCoordinate((float) $delivery['current_lat'], (float) $delivery['current_lng'])) {
                $courierCoords = [(float) $delivery['current_lat'], (float) $delivery['current_lng']];
            } else {
                $cLat = round($storeLat + 0.70 * ($destLat - $storeLat), 6);
                $cLng = round($storeLng + 0.70 * ($destLng - $storeLng), 6);
                $courierCoords = [$cLat, $cLng];
            }
        } else {
            $courierCoords = $storeCoords;
        }

        $courierName = !empty($delivery['courier_name']) && !in_array($delivery['courier_name'], ['Store Courier', 'Store Pick-up'], true)
            ? $delivery['courier_name']
            : 'Blax Express Rider';
        $courierPhone = $delivery['courier_phone'] ?? '0917-889-2144';
        $courierVehicle = 'Motorcycle • Honda Click 125i (Plate: 842-MCG)';

        // Estimated delivery timing & progress percentage
        $progressPercent = 75;
        $estimatedArrival = 'Estimated Delivery: Today, by 5:30 PM';
        $statusBadge = 'In Transit';
        $badgeClass = 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300';

        if ($status === 'completed' || $status === 'delivered') {
            $progressPercent = 100;
            $estimatedArrival = 'Delivered: ' . date('M d, Y h:i A', strtotime($req['completed_at'] ?? 'now'));
            $statusBadge = 'Delivered';
            $badgeClass = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
        }

        // Synthesize $order object for view
        $order = [
            'id'                  => $req['id'],
            'order_number'        => $req['request_number'] ?? ('PR-' . $req['id']),
            'customer_id'         => $req['customer_id'],
            'shop_id'             => $req['shop_id'],
            'status'              => in_array($status, ['ready_for_delivery', 'in_transit'], true) ? 'in_transit' : $status,
            'fulfillment_method'  => 'delivery',
            'payment_method'      => 'Online / COD',
            'payment_status'      => $status === 'completed' ? 'paid' : 'down_payment_paid',
            'subtotal'            => (float) ($req['total_price'] ?? 0),
            'shipping_fee'        => 50.00,
            'total_amount'        => (float) (($req['total_price'] ?? 0) + 50.00),
            'placed_at'           => $req['created_at'] ?? date('Y-m-d H:i:s'),
            'completed_at'        => $req['completed_at'] ?? null,
            'delivery_address'    => $destAddressText,
            'destination_address' => $destAddressText,
        ];

        // Synthesize single item representing the print document
        $specParts = array_filter([
            ($req['page_count'] ?? 1) . ' Page' . (($req['page_count'] ?? 1) !== 1 ? 's' : ''),
            ($req['copies'] ?? 1) . ' Cop' . (($req['copies'] ?? 1) !== 1 ? 'ies' : 'y'),
            strtoupper($req['paper_size'] ?? 'A4'),
            ucwords(str_replace('_', ' ', $req['color_mode'] ?? 'Color')),
            ucwords($req['binding_option'] ?? 'None') . ' Binding',
        ]);
        $items = [
            [
                'product_id'    => 0,
                'product_name'  => 'Print Document: ' . ($req['file_name'] ?? 'Document.pdf'),
                'variant_label' => implode(' • ', $specParts),
                'quantity'      => (int) ($req['copies'] ?? 1),
                'unit_price'    => (float) ($req['total_price'] ?? 0),
                'image_url'     => '',
            ]
        ];

        return view('customer/orders/track', [
            'order'            => $order,
            'items'            => $items,
            'shop'             => $shop,
            'delivery'         => $delivery,
            'shippingAddress'  => $shippingAddress,
            'destAddressText'  => $destAddressText,
            'storeCoords'      => $storeCoords,
            'destCoords'       => $destCoords,
            'courierCoords'    => $courierCoords,
            'courierName'      => $courierName,
            'courierPhone'     => $courierPhone,
            'courierVehicle'   => $courierVehicle,
            'progressPercent'  => $progressPercent,
            'estimatedArrival' => $estimatedArrival,
            'statusBadge'      => $statusBadge,
            'badgeClass'       => $badgeClass,
            'backUrl'          => base_url('customer/printing'),
            'backLabel'        => 'Back to Printing Requests',
            'pollPositionUrl'  => base_url('customer/printing/track/' . ($req['request_number'] ?? $req['id']) . '/position'),
        ]);
    }

    /**
     * AJAX endpoint for customer live position polling on printing requests.
     */
    public function getPrintingDeliveryPosition($reqRef = null)
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'error'   => 'Unauthorized',
            ]);
        }

        if (empty($reqRef)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => 'Missing printing request reference',
            ]);
        }

        $prModel = new \App\Models\PrintingRequestModel();
        $req = null;
        if (is_numeric($reqRef)) {
            $req = $prModel->find((int) $reqRef);
        }
        if (!$req) {
            $req = $prModel->where('request_number', (string) $reqRef)->first();
        }
        if (!$req && is_numeric($reqRef)) {
            $req = $prModel->where('request_number', 'PR-' . $reqRef)->first();
        }

        if (!$req || (int) $req['customer_id'] !== (int) $userId) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'Printing request not found or access denied.',
            ]);
        }

        $deliveryModel = new DeliveryModel();
        $delivery = $deliveryModel->where('deliverable_type', 'printing_request')
            ->where('deliverable_id', (int) $req['id'])
            ->first();

        $status = strtolower(trim((string) $req['status']));
        $lat = null;
        $lng = null;

        if ($delivery && !empty($delivery['current_lat']) && !empty($delivery['current_lng']) && DeliveryModel::isPolomolokCoordinate((float) $delivery['current_lat'], (float) $delivery['current_lng'])) {
            $lat = (float) $delivery['current_lat'];
            $lng = (float) $delivery['current_lng'];
        } else {
            $shop = (new ShopModel())->find($req['shop_id']);
            if ($shop && !empty($shop['latitude']) && !empty($shop['longitude']) && DeliveryModel::isPolomolokCoordinate((float) $shop['latitude'], (float) $shop['longitude'])) {
                $lat = (float) $shop['latitude'];
                $lng = (float) $shop['longitude'];
            } else {
                $lat = DeliveryModel::POLOMOLOK_CENTER_LAT;
                $lng = DeliveryModel::POLOMOLOK_CENTER_LNG;
            }
        }

        return $this->response->setJSON([
            'success'         => true,
            'status'          => $status,
            'delivery_status' => $delivery['status'] ?? 'pending',
            'lat'             => $lat,
            'lng'             => $lng,
            'updated_at'      => $delivery['location_updated_at'] ?? $delivery['updated_at'] ?? null,
        ]);
    }
}
