<?php

namespace App\Controllers;

use App\Models\ProductImageModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\ShippingAddressModel;
use App\Models\ShopModel;
use App\Traits\CheckoutProcessorTrait;

class Checkout extends BaseController
{
    use CheckoutProcessorTrait;

    /**
     * Direct Buy Now page: Renders dedicated single-item checkout without touching cart_items.
     */
    public function direct()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $productId = (int) $this->request->getGet('product_id');
        $quantity  = max(1, (int) ($this->request->getGet('quantity') ?? 1));
        $variantId = (int) ($this->request->getGet('variant_id') ?? 0);

        if ($productId <= 0) {
            return redirect()->to('/')->with('error', 'Invalid product requested.');
        }

        $productModel = new ProductModel();
        $product      = $productModel->find($productId);

        if (!$product || ($product['status'] ?? 'active') !== 'active') {
            return redirect()->to('/')->with('error', 'Product is no longer available.');
        }

        // Validate variants if product has them
        $variantModel = new ProductVariantModel();
        $variants     = $variantModel->where('product_id', $productId)->findAll();

        $selectedVariant = null;
        $variantLabel    = null;
        $unitPrice       = (float) $product['price'];

        if (!empty($variants)) {
            if ($variantId <= 0) {
                return redirect()->to(base_url('product/' . $productId))->with('error', 'Please select a product option before checking out.');
            }

            foreach ($variants as $v) {
                if ((int) $v['id'] === $variantId) {
                    $selectedVariant = $v;
                    break;
                }
            }

            if (!$selectedVariant) {
                return redirect()->to(base_url('product/' . $productId))->with('error', 'The selected product option does not exist.');
            }

            if ((int) $selectedVariant['stock_quantity'] <= 0) {
                return redirect()->to(base_url('product/' . $productId))->with('error', 'The selected product option is currently out of stock.');
            }

            $unitPrice    = !empty($selectedVariant['price_override']) ? (float) $selectedVariant['price_override'] : $unitPrice;
            $variantLabel = ($selectedVariant['name'] ?? 'Option') . ': ' . ($selectedVariant['value'] ?? '');
            $quantity     = min($quantity, (int) $selectedVariant['stock_quantity']);
        } else {
            if ((int) $product['stock_quantity'] <= 0) {
                return redirect()->to(base_url('product/' . $productId))->with('error', 'This product is currently out of stock.');
            }
            $quantity = min($quantity, (int) $product['stock_quantity']);
        }

        // Resolve product thumbnail
        $imageUrl = null;
        if ($selectedVariant && !empty($selectedVariant['image_url'])) {
            $imageUrl = $selectedVariant['image_url'];
        } else {
            $img = (new ProductImageModel())
                ->where('product_id', $productId)
                ->orderBy('is_primary', 'DESC')
                ->first();
            if ($img && !empty($img['image_url'])) {
                $imageUrl = $img['image_url'];
            }
        }

        // Load shop and addresses
        $shop      = (new ShopModel())->find($product['shop_id']);
        $addresses = (new ShippingAddressModel())
            ->where('user_id', $userId)
            ->orderBy('is_default', 'DESC')
            ->findAll();

        $subtotal = $unitPrice * $quantity;
        $shipping = 50.00;

        return view('customer/buy_now', [
            'product'         => $product,
            'shop'            => $shop,
            'selectedVariant' => $selectedVariant,
            'variantLabel'    => $variantLabel,
            'quantity'        => $quantity,
            'unitPrice'       => $unitPrice,
            'subtotal'        => $subtotal,
            'shipping'        => $shipping,
            'addresses'       => $addresses,
            'imageUrl'        => $imageUrl,
            'csrfName'        => csrf_token(),
            'csrfHash'        => csrf_hash(),
        ]);
    }

    /**
     * Place order from Direct Buy Now page.
     * Re-derives all pricing and stock server-side and uses CheckoutProcessorTrait.
     */
    public function placeOrder()
    {
        $session = session();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $productId = (int) $this->request->getPost('product_id');
        $quantity  = max(1, (int) ($this->request->getPost('quantity') ?? 1));
        $variantId = (int) ($this->request->getPost('variant_id') ?? 0);

        if ($productId <= 0) {
            return redirect()->to('/')->with('error', 'Invalid product.');
        }

        $productModel = new ProductModel();
        $product      = $productModel->find($productId);

        if (!$product || ($product['status'] ?? 'active') !== 'active') {
            return redirect()->to('/')->with('error', 'This product is no longer available.');
        }

        $variantModel    = new ProductVariantModel();
        $variants        = $variantModel->where('product_id', $productId)->findAll();
        $selectedVariant = null;
        $variantLabel    = null;
        $unitPrice       = (float) $product['price'];

        if (!empty($variants)) {
            if ($variantId <= 0) {
                return redirect()->to(base_url('product/' . $productId))->with('error', 'Please select a product option to continue.');
            }

            foreach ($variants as $v) {
                if ((int) $v['id'] === $variantId) {
                    $selectedVariant = $v;
                    break;
                }
            }

            if (!$selectedVariant || (int) $selectedVariant['stock_quantity'] <= 0) {
                return redirect()->to(base_url('product/' . $productId))->with('error', 'The selected option is out of stock.');
            }

            $unitPrice    = !empty($selectedVariant['price_override']) ? (float) $selectedVariant['price_override'] : $unitPrice;
            $variantLabel = ($selectedVariant['name'] ?? 'Option') . ': ' . ($selectedVariant['value'] ?? '');
            $quantity     = min($quantity, (int) $selectedVariant['stock_quantity']);
        } else {
            if ((int) $product['stock_quantity'] <= 0) {
                return redirect()->to(base_url('product/' . $productId))->with('error', 'Product is out of stock.');
            }
            $quantity = min($quantity, (int) $product['stock_quantity']);
        }

        $paymentMethod     = (string) ($this->request->getPost('payment_method') ?? 'gcash');
        $fulfillmentMethod = (string) ($this->request->getPost('fulfillment_method') ?? ($paymentMethod === 'pickup' ? 'pickup' : 'delivery'));
        $addressId         = $this->request->getPost('shipping_address_id') ? (int) $this->request->getPost('shipping_address_id') : null;

        $itemsByShop = [
            $product['shop_id'] => [[
                'product_id'    => $product['id'],
                'variant_id'    => $selectedVariant['id'] ?? null,
                'variant_label' => $variantLabel,
                'product_name'  => $product['name'],
                'quantity'      => $quantity,
                'unit_price'    => $unitPrice,
            ]],
        ];

        $buyNowUrl = base_url("buy-now?product_id={$productId}&quantity={$quantity}" . ($variantId > 0 ? "&variant_id={$variantId}" : ''));

        return $this->processItemsByShop(
            $itemsByShop,
            $userId,
            $paymentMethod,
            $fulfillmentMethod,
            $addressId,
            [], // Empty cart items list — Direct Buy never touches cart_items!
            $buyNowUrl,
            $buyNowUrl
        );
    }
}
