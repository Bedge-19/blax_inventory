<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<style>
    #checkout-sidebar {
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .store-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
</style>

<main class="max-w-7xl mx-auto px-3 sm:px-6 md:px-8 lg:px-10 py-6 md:py-10 flex-grow w-full pb-28 lg:pb-12">

    <?php
        $shipping = (float) ($shippingFee ?? 50.00);
        $csrfName = csrf_token();
        $csrfHash = csrf_hash();
        $totalItemsCount = count($cartItems ?? []);
    ?>

    <!-- Page Header & Breadcrumb -->
    <div class="mb-6 md:mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-outline mb-1">
                <a href="<?= base_url('/') ?>" class="hover:text-primary transition-colors">Marketplace</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-on-surface">Shopping Cart</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-on-surface tracking-tight flex items-center gap-2.5">
                <span>Shopping Cart</span>
                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20">
                    <?= $totalItemsCount ?> <?= $totalItemsCount === 1 ? 'item' : 'items' ?>
                </span>
            </h1>
        </div>
        <div>
            <a class="inline-flex items-center gap-1.5 text-xs font-bold text-primary hover:text-primary/80 transition-colors" href="<?= base_url('/') ?>">
                <span class="material-symbols-outlined text-[18px]">shopping_bag</span>
                <span>Continue Shopping</span>
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-4 rounded-2xl bg-error-container/20 border border-error/30 text-error text-xs sm:text-sm font-semibold mb-6 flex items-center gap-2.5 shadow-2xs animate-fade-in">
            <span class="material-symbols-outlined text-lg shrink-0">error</span>
            <span><?= session()->getFlashdata('error') ?></span>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-4 rounded-2xl bg-green-500/10 border border-green-500/30 text-green-700 dark:text-green-300 text-xs sm:text-sm font-semibold mb-6 flex items-center gap-2.5 shadow-2xs animate-fade-in">
            <span class="material-symbols-outlined text-lg shrink-0">check_circle</span>
            <span><?= session()->getFlashdata('success') ?></span>
        </div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <!-- Empty Cart State -->
        <div class="bg-surface-container-lowest rounded-3xl p-8 sm:p-14 text-center max-w-lg mx-auto my-8 border border-outline-variant/30 shadow-sm">
            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-5">
                <span class="material-symbols-outlined text-4xl sm:text-5xl">remove_shopping_cart</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-on-surface mb-2">Your Shopping Cart is Empty</h2>
            <p class="text-on-surface-variant text-xs sm:text-sm mb-6 max-w-sm mx-auto leading-relaxed">Looks like you haven't added anything to your cart yet. Explore verified local shops and printing services today!</p>
            <a href="<?= base_url('/') ?>" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-bold text-xs sm:text-sm shadow-md hover:bg-primary/90 hover:shadow-lg transition-all active:scale-95">
                <span class="material-symbols-outlined text-[18px]">storefront</span>
                <span>Start Shopping Now</span>
            </a>
        </div>
    <?php else: ?>

        <form action="<?= base_url('cart/checkout') ?>" method="POST" class="grid grid-cols-12 gap-6 lg:gap-8" id="cart-checkout-form">
            <?= csrf_field() ?>
            <input type="hidden" name="fulfillment_method" id="form-fulfillment-method" value="delivery">

            <!-- Left: Store-Grouped Cart Items -->
            <div class="col-span-12 lg:col-span-8 space-y-4">

                <!-- Global Select All Toolbar -->
                <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 px-4 py-3.5 flex items-center justify-between shadow-2xs">
                    <label class="flex items-center gap-3 cursor-pointer select-none group">
                        <input class="w-5 h-5 rounded-md border-outline-variant text-primary focus:ring-primary/40 transition-all cursor-pointer" id="select-all" type="checkbox">
                        <span class="text-xs font-extrabold text-on-surface group-hover:text-primary transition-colors tracking-wide">
                            SELECT ALL (<?= $totalItemsCount ?> ITEMS)
                        </span>
                    </label>

                    <button type="button" id="delete-selected" class="inline-flex items-center gap-1.5 text-xs font-bold text-error hover:text-error/80 px-3 py-1.5 rounded-lg hover:bg-error/10 transition-all active:scale-95 opacity-50 pointer-events-none" disabled>
                        <span class="material-symbols-outlined text-[16px]">delete_sweep</span>
                        <span>Delete Selected</span>
                    </button>
                </div>

                <!-- Stores and Products List -->
                <?php
                    // Group items by shop if not already structured
                    $groupedByShop = $itemsByShop ?? [];
                    if (empty($groupedByShop)) {
                        foreach ($cartItems as $it) {
                            $sId = (int) ($it['shop_id'] ?? 0);
                            $groupedByShop[$sId]['shop_id']         = $sId;
                            $groupedByShop[$sId]['shop_name']       = $it['shop_name'] ?? 'Merchant Store';
                            $groupedByShop[$sId]['shop_slug']       = $it['shop_slug'] ?? '';
                            $groupedByShop[$sId]['offers_delivery'] = (int) ($it['offers_delivery'] ?? 1);
                            $groupedByShop[$sId]['offers_pickup']   = (int) ($it['offers_pickup'] ?? 1);
                            $groupedByShop[$sId]['items'][]         = $it;
                        }
                    }
                ?>

                <?php foreach ($groupedByShop as $shopId => $shopGroup): ?>
                    <div class="store-card bg-surface-container-lowest rounded-2xl border border-outline-variant/30 overflow-hidden shadow-2xs hover:shadow-sm" data-shop-card="<?= (int) $shopId ?>">
                        
                        <!-- Store Header -->
                        <div class="px-4 py-3 bg-surface-container-low border-b border-outline-variant/20 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <input type="checkbox" class="store-checkbox w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary/40 transition-all cursor-pointer shrink-0" data-shop-id="<?= (int) $shopId ?>">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="material-symbols-outlined text-[18px] text-primary shrink-0">storefront</span>
                                    <a href="<?= !empty($shopGroup['shop_slug']) ? base_url('shop/' . esc($shopGroup['shop_slug'])) : base_url('shop/' . (int) $shopId) ?>" class="text-xs sm:text-sm font-bold text-on-surface hover:text-primary transition-colors truncate flex items-center gap-1">
                                        <span><?= esc($shopGroup['shop_name']) ?></span>
                                        <span class="material-symbols-outlined text-[14px] text-outline">chevron_right</span>
                                    </a>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <?php if (!empty($shopGroup['offers_delivery'])): ?>
                                    <span class="hidden sm:inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-500/20" title="Doorstep Delivery Available">
                                        <span class="material-symbols-outlined text-[12px]">local_shipping</span> Delivery
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($shopGroup['offers_pickup'])): ?>
                                    <span class="hidden sm:inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20" title="Store Pick-up Available">
                                        <span class="material-symbols-outlined text-[12px]">store</span> Pick-up
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Product Items Inside This Store -->
                        <div class="divide-y divide-outline-variant/15">
                            <?php foreach ($shopGroup['items'] as $item): ?>
                                <?php
                                    $itemPrice = (float) ($item['price'] ?? $item['unit_price'] ?? 0);
                                    $itemQty   = (int) ($item['quantity'] ?? 1);
                                    $itemLineTotal = $itemPrice * $itemQty;
                                    $itemImage = product_image_url($item['image_url'] ?? null, 'thumbnail');
                                    $stockQty  = (int) ($item['stock_quantity'] ?? 99);
                                ?>
                                <div class="p-3.5 sm:p-4 flex gap-3 sm:gap-4 items-start group hover:bg-surface-container-lowest transition-colors" data-item-row="<?= (int) $item['id'] ?>">
                                    
                                    <!-- Item Checkbox -->
                                    <div class="pt-2 sm:pt-3">
                                        <input class="cart-item-checkbox w-4 h-4 sm:w-5 sm:h-5 rounded-md border-outline-variant text-primary focus:ring-primary/40 transition-all cursor-pointer"
                                               name="selected_items[]"
                                               type="checkbox"
                                               value="<?= (int) $item['id'] ?>"
                                               data-id="<?= (int) $item['id'] ?>"
                                               data-shop-id="<?= (int) $shopId ?>"
                                               data-price="<?= $itemPrice ?>"
                                               data-qty="<?= $itemQty ?>"
                                               data-shop-name="<?= esc($shopGroup['shop_name']) ?>"
                                               data-offers-delivery="<?= (int) ($shopGroup['offers_delivery'] ?? 1) ?>"
                                               data-offers-pickup="<?= (int) ($shopGroup['offers_pickup'] ?? 1) ?>"
                                               <?= !empty($item['is_selected']) ? 'checked' : '' ?>>
                                    </div>

                                    <!-- Product Image Thumbnail -->
                                    <div class="w-18 h-18 sm:w-24 sm:h-24 bg-surface-container rounded-xl overflow-hidden shrink-0 border border-outline-variant/30 relative">
                                        <?php if (!empty($itemImage)): ?>
                                            <img alt="<?= esc($item['product_name'] ?? $item['name'] ?? 'Product') ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" src="<?= esc($itemImage) ?>">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-outline">
                                                <span class="material-symbols-outlined text-3xl">inventory_2</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Product Details & Controls -->
                                    <div class="flex flex-col flex-grow justify-between min-w-0 self-stretch">
                                        <div class="flex justify-between items-start gap-2">
                                            <div class="min-w-0 flex-1">
                                                <h3 class="text-xs sm:text-sm font-bold text-on-surface line-clamp-2 leading-snug">
                                                    <?= esc($item['product_name'] ?? $item['name'] ?? 'Product') ?>
                                                </h3>
                                                <?php if (!empty($item['variant_label'])): ?>
                                                    <span class="inline-flex items-center gap-1 my-1 px-2 py-0.5 bg-primary/10 text-primary text-[10px] sm:text-xs font-semibold rounded-md border border-primary/20">
                                                        <span class="material-symbols-outlined text-[12px]">tune</span>
                                                        <span><?= esc($item['variant_label']) ?></span>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Unit Price / Total -->
                                            <div class="text-right shrink-0">
                                                <p class="text-xs sm:text-sm font-extrabold text-primary">₱<?= number_format($itemPrice, 2) ?></p>
                                                <?php if ($itemQty > 1): ?>
                                                    <p class="text-[10px] text-on-surface-variant">Subtotal: ₱<?= number_format($itemLineTotal, 2) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Quantity Controls & Delete -->
                                        <div class="flex items-center justify-between mt-3 pt-2 border-t border-outline-variant/10">
                                            <div class="inline-flex items-center border border-outline-variant/50 rounded-xl bg-surface-container-low shadow-2xs overflow-hidden">
                                                <button type="button" class="qty-minus px-2.5 py-1 text-on-surface-variant hover:bg-surface-container hover:text-on-surface font-bold text-sm transition-colors" data-id="<?= (int) $item['id'] ?>" data-stock="<?= $stockQty ?>" aria-label="Decrease quantity">−</button>
                                                <span class="qty-value px-3 py-1 text-xs sm:text-sm font-bold text-on-surface min-w-[28px] text-center border-x border-outline-variant/30"><?= $itemQty ?></span>
                                                <button type="button" class="qty-plus px-2.5 py-1 text-on-surface-variant hover:bg-surface-container hover:text-on-surface font-bold text-sm transition-colors" data-id="<?= (int) $item['id'] ?>" data-stock="<?= $stockQty ?>" aria-label="Increase quantity">+</button>
                                            </div>

                                            <a href="<?= base_url('cart/remove/' . $item['id']) ?>" class="inline-flex items-center gap-1 text-xs font-semibold text-error/80 hover:text-error p-1.5 rounded-lg hover:bg-error/10 transition-colors" aria-label="Remove item" title="Remove from cart">
                                                <span class="material-symbols-outlined text-[17px]">delete</span>
                                                <span class="hidden sm:inline">Remove</span>
                                            </a>
                                        </div>

                                    </div>

                                </div>
                            <?php endforeach; ?>
                        </div>

                    </div>
                <?php endforeach; ?>

            </div>

            <!-- Right: Order Summary & Checkout Sidebar (Desktop) -->
            <div class="col-span-12 lg:col-span-4">
                <div class="space-y-4 sticky top-24" id="checkout-sidebar">

                    <!-- Summary Card -->
                    <div class="bg-surface-container-lowest rounded-3xl p-5 sm:p-6 border border-outline-variant/30 shadow-sm space-y-5">
                        <h2 class="text-base sm:text-lg font-extrabold text-on-surface flex items-center justify-between border-b border-outline-variant/20 pb-3">
                            <span>Order Summary</span>
                            <span class="text-xs font-bold text-primary" id="selected-count-badge">0 items selected</span>
                        </h2>

                        <!-- Calculation breakdown -->
                        <div class="space-y-2.5 text-xs sm:text-sm text-on-surface-variant border-b border-outline-variant/20 pb-4">
                            <div class="flex justify-between items-center">
                                <span>Selected Items Subtotal</span>
                                <span class="font-bold text-on-surface" id="subtotal-val">₱0.00</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="flex items-center gap-1">
                                    <span>Estimated Delivery Fee</span>
                                    <span class="material-symbols-outlined text-[14px] text-outline" title="₱50 Standard Delivery within Polomolok (Free for Pick-up)">help</span>
                                </span>
                                <span class="text-primary font-bold" id="shipping-val">₱<?= number_format($shipping, 2) ?></span>
                            </div>
                        </div>

                        <!-- Grand Total -->
                        <div class="flex justify-between items-baseline pt-1">
                            <div>
                                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block">Estimated Total</span>
                                <span class="text-[11px] text-outline">VAT Inclusive</span>
                            </div>
                            <span class="text-xl sm:text-2xl font-black text-primary" id="total-val">₱0.00</span>
                        </div>

                        <!-- Payment Method Selector -->
                        <div class="space-y-2.5 pt-2 border-t border-outline-variant/20">
                            <label class="text-[11px] font-extrabold text-on-surface-variant uppercase tracking-wider block">
                                Select Payment Method
                            </label>

                            <div class="grid grid-cols-1 gap-2">
                                <!-- GCash -->
                                <label id="label-pay-gcash" class="flex items-center gap-3 p-3 bg-surface-container-low hover:bg-surface-container transition-all rounded-xl border border-outline-variant/40 cursor-pointer group">
                                    <input class="w-4 h-4 text-primary border-outline-variant focus:ring-primary" name="payment_method" type="radio" value="gcash" id="cart-pay-gcash" checked>
                                    <div class="w-8 h-8 rounded-lg bg-[#007DFE]/10 text-[#007DFE] flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs sm:text-sm font-bold text-on-surface">GCash (PayMongo)</span>
                                        <span class="text-[11px] text-on-surface-variant">Instant online payment</span>
                                    </div>
                                </label>

                                <!-- Store Pick-up -->
                                <label id="label-pay-pickup" class="flex items-center gap-3 p-3 bg-surface-container-low hover:bg-surface-container transition-all rounded-xl border border-outline-variant/40 cursor-pointer group">
                                    <input class="w-4 h-4 text-primary border-outline-variant focus:ring-primary" name="payment_method" type="radio" value="pickup" id="cart-pay-pickup">
                                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-[20px]">storefront</span>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs sm:text-sm font-bold text-on-surface">Store Pick-up</span>
                                        <span class="text-[11px] text-on-surface-variant" id="subtext-pay-pickup">Pick up &amp; pay at branch (₱0 delivery)</span>
                                    </div>
                                </label>

                                <!-- COD -->
                                <label id="label-pay-cod" class="flex items-center gap-3 p-3 bg-surface-container-low hover:bg-surface-container transition-all rounded-xl border border-outline-variant/40 cursor-pointer group">
                                    <input class="w-4 h-4 text-primary border-outline-variant focus:ring-primary" name="payment_method" type="radio" value="cod" id="cart-pay-cod">
                                    <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-600 flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-[20px]">local_shipping</span>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs sm:text-sm font-bold text-on-surface">Cash on Delivery</span>
                                        <span class="text-[11px] text-on-surface-variant" id="subtext-pay-cod">Pay upon doorstep arrival</span>
                                    </div>
                                </label>
                            </div>

                            <!-- Cart Service Restriction Alert -->
                            <div id="cart-service-notice" class="hidden mt-2 p-3 rounded-xl bg-amber-500/10 border border-amber-500/25 text-amber-800 dark:text-amber-300 text-xs flex items-start gap-2">
                                <span class="material-symbols-outlined text-[16px] text-amber-600 shrink-0 mt-0.5">info</span>
                                <span id="cart-service-notice-text"></span>
                            </div>
                        </div>

                        <!-- Inline Error Notice -->
                        <div id="checkout-inline-error" class="hidden p-3 rounded-xl bg-error-container/20 border border-error text-error text-xs font-semibold flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] shrink-0">error</span>
                            <span id="checkout-error-text"></span>
                        </div>

                        <!-- Desktop Submit Button -->
                        <button type="submit" class="w-full py-3.5 bg-primary text-white rounded-xl font-bold text-xs sm:text-sm shadow-md hover:bg-primary/90 hover:shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed" id="place-order-btn" disabled>
                            <span id="place-order-btn-text">Proceed to Checkout (0)</span>
                            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                        </button>
                    </div>

                    <!-- Shipping Address Card -->
                    <div class="bg-surface-container-lowest rounded-3xl p-5 border border-outline-variant/30 shadow-2xs">
                        <div class="flex items-center justify-between mb-2.5">
                            <span class="text-[11px] font-extrabold text-on-surface-variant uppercase tracking-wider flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-primary">location_on</span>
                                <span>Shipping Address</span>
                            </span>
                            <a href="<?= base_url('customer/addresses') ?>" class="text-[11px] font-bold text-primary hover:underline">Manage</a>
                        </div>

                        <?php if (!empty($addresses)): ?>
                            <select name="shipping_address_id" class="w-full p-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs sm:text-sm font-semibold focus:ring-2 focus:ring-primary" required>
                                <?php foreach ($addresses as $addr): ?>
                                    <option value="<?= $addr['id'] ?>"><?= esc($addr['recipient_name']) ?> — <?= esc($addr['address_line1']) ?>, <?= esc($addr['city']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <p class="text-xs text-on-surface-variant mb-2">No saved address found. Please enter delivery address:</p>
                            <input type="text" name="shipping_address" placeholder="Enter delivery address (Polomolok/Tupi)" class="w-full p-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs sm:text-sm font-semibold" required>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <!-- Mobile Sticky Bottom Checkout Drawer -->
            <div class="lg:hidden fixed bottom-14 left-0 right-0 z-40 bg-surface-container-lowest/95 backdrop-blur-md border-t border-outline-variant/30 p-3 px-4 shadow-[0_-4px_16px_rgba(0,0,0,0.08)] flex items-center justify-between gap-3">
                <div class="flex flex-col min-w-0">
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Amount</span>
                    <span class="text-lg font-black text-primary leading-tight" id="mobile-total-val">₱0.00</span>
                    <span class="text-[10px] text-outline" id="mobile-item-count">0 items selected</span>
                </div>
                <button type="submit" class="bg-primary text-white px-6 py-3 rounded-xl font-bold text-xs sm:text-sm shadow-md hover:bg-primary/90 active:scale-95 transition-all flex items-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed shrink-0" id="mobile-checkout-btn" disabled>
                    <span>Checkout</span>
                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </button>
            </div>

        </form>

    <?php endif; ?>

<!-- Checkout Confirmation Modal -->
<div id="checkout-confirm-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm hidden p-4">
    <div class="bg-surface-container-lowest rounded-3xl p-6 max-w-md w-full shadow-2xl border border-outline-variant/30 space-y-5 animate-scale-up">
        <div class="flex items-center gap-3">
            <div id="modal-icon-bg" class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[28px]" id="modal-icon">storefront</span>
            </div>
            <div>
                <h3 class="text-lg font-bold text-on-surface" id="modal-title">Confirm Order</h3>
                <p class="text-xs text-on-surface-variant" id="modal-subtitle">Review order fulfillment before proceeding</p>
            </div>
        </div>

        <div class="space-y-3 bg-surface-container-low p-4 rounded-2xl border border-outline-variant/20 text-xs sm:text-sm">
            <!-- GCash Fulfillment Choice -->
            <div id="modal-gcash-fulfillment" class="space-y-2 hidden">
                <span class="text-[11px] font-extrabold text-on-surface-variant uppercase tracking-wider block">Select Fulfillment Option</span>
                <div class="grid grid-cols-1 gap-2">
                    <label class="flex items-center gap-3 p-2.5 rounded-xl border border-primary bg-primary/5 cursor-pointer" id="label-opt-delivery">
                        <input type="radio" name="modal_fulfillment_choice" value="delivery" checked class="w-4 h-4 text-primary focus:ring-primary" id="modal-fchoice-delivery">
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-on-surface flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px] text-primary">local_shipping</span> Doorstep Delivery
                            </span>
                            <span class="text-[10px] text-on-surface-variant" id="modal-desc-delivery">Delivered to address (₱50.00 fee)</span>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-2.5 rounded-xl border border-outline-variant hover:bg-surface-container cursor-pointer" id="label-opt-pickup">
                        <input type="radio" name="modal_fulfillment_choice" value="pickup" class="w-4 h-4 text-primary focus:ring-primary" id="modal-fchoice-pickup">
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-on-surface flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px] text-amber-600">storefront</span> Store Pick-up
                            </span>
                            <span class="text-[10px] text-on-surface-variant" id="modal-desc-pickup">Pick up at shop branch (Free / ₱0.00)</span>
                        </div>
                    </label>
                </div>
            </div>

            <p class="text-on-surface font-semibold" id="modal-description"></p>
            <div id="modal-shop-info" class="text-xs text-on-surface-variant border-t border-outline-variant/20 pt-2 hidden">
                <span class="font-bold text-on-surface">Store(s) Involved:</span>
                <span id="modal-shop-name" class="font-semibold text-primary block mt-0.5"></span>
            </div>
            <div class="flex justify-between items-center text-sm font-bold text-on-surface border-t border-outline-variant/20 pt-2">
                <span>Total Payable:</span>
                <span class="text-primary text-base sm:text-lg font-black" id="modal-order-total">₱0.00</span>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <button type="button" id="modal-cancel-btn" class="px-4 py-2.5 rounded-xl font-bold text-xs text-on-surface-variant hover:bg-surface-container transition-colors">
                Cancel
            </button>
            <button type="button" id="modal-confirm-btn" class="px-5 py-2.5 bg-primary text-white rounded-xl font-bold text-xs hover:bg-primary/90 transition-all shadow-md active:scale-95 flex items-center gap-1.5">
                <span id="modal-confirm-text">Confirm Order</span>
                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </button>
        </div>
    </div>
</div>

</main>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var itemCheckboxes    = document.querySelectorAll('.cart-item-checkbox');
    var storeCheckboxes   = document.querySelectorAll('.store-checkbox');
    var selectAll         = document.getElementById('select-all');
    var paymentRadios     = document.querySelectorAll('input[name="payment_method"]');
    var placeOrderBtn     = document.getElementById('place-order-btn');
    var placeOrderBtnText = document.getElementById('place-order-btn-text');
    var mobileCheckoutBtn = document.getElementById('mobile-checkout-btn');
    var deleteSelectedBtn = document.getElementById('delete-selected');

    var shipping          = <?= json_encode($shipping) ?>;
    var csrfName          = <?= json_encode($csrfName) ?>;
    var csrfHash          = <?= json_encode($csrfHash) ?>;
    var updateUrl         = <?= json_encode(base_url('cart/update')) ?>;
    var removeSelectedUrl = <?= json_encode(base_url('cart/remove-selected')) ?>;

    function money(n) {
        return '\u20B1' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function postForm(url, data) {
        var fd = new FormData();
        fd.append(csrfName, csrfHash);
        Object.keys(data).forEach(function (key) {
            if (Array.isArray(data[key])) {
                data[key].forEach(function (v) { fd.append(key, v); });
            } else {
                fd.append(key, data[key]);
            }
        });
        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
    }

    function updateUI() {
        var selected = Array.prototype.filter.call(itemCheckboxes, function (cb) { return cb.checked; });
        var anySelected = selected.length > 0;
        var totalQty = 0;
        var subtotal = 0;

        selected.forEach(function (cb) {
            var p = parseFloat(cb.getAttribute('data-price')) || 0;
            var q = parseInt(cb.getAttribute('data-qty'), 10) || 1;
            subtotal += p * q;
            totalQty += q;
        });

        // Sync each store checkbox
        storeCheckboxes.forEach(function (scb) {
            var sId = scb.getAttribute('data-shop-id');
            var storeItems = document.querySelectorAll('.cart-item-checkbox[data-shop-id="' + sId + '"]');
            if (storeItems.length > 0) {
                var allStoreChecked = Array.prototype.every.call(storeItems, function (cb) { return cb.checked; });
                var someStoreChecked = Array.prototype.some.call(storeItems, function (cb) { return cb.checked; });
                scb.checked = allStoreChecked;
                scb.indeterminate = someStoreChecked && !allStoreChecked;
            }
        });

        // Sync global select all
        if (selectAll) {
            selectAll.checked = itemCheckboxes.length > 0 && Array.prototype.every.call(itemCheckboxes, function (cb) { return cb.checked; });
            selectAll.indeterminate = anySelected && !selectAll.checked;
        }

        // Enable/disable delete selected
        if (deleteSelectedBtn) {
            deleteSelectedBtn.disabled = !anySelected;
            if (anySelected) {
                deleteSelectedBtn.classList.remove('opacity-50', 'pointer-events-none');
            } else {
                deleteSelectedBtn.classList.add('opacity-50', 'pointer-events-none');
            }
        }

        var canDeliver = true;
        var canPickup  = true;
        selected.forEach(function (cb) {
            if (cb.getAttribute('data-offers-delivery') === '0') canDeliver = false;
            if (cb.getAttribute('data-offers-pickup') === '0') canPickup = false;
        });

        // Sync payment method elements
        var payPickup     = document.getElementById('cart-pay-pickup');
        var payCod        = document.getElementById('cart-pay-cod');
        var labelPickup   = document.getElementById('label-pay-pickup');
        var labelCod      = document.getElementById('label-pay-cod');
        var subtextPickup = document.getElementById('subtext-pay-pickup');
        var subtextCod    = document.getElementById('subtext-pay-cod');
        var noticeEl      = document.getElementById('cart-service-notice');
        var noticeText    = document.getElementById('cart-service-notice-text');

        if (payCod && labelCod) {
            if (anySelected && !canDeliver) {
                payCod.disabled = true;
                if (payCod.checked) payCod.checked = false;
                labelCod.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                if (subtextCod) subtextCod.textContent = 'Doorstep delivery unavailable';
            } else {
                payCod.disabled = false;
                labelCod.classList.remove('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                if (subtextCod) subtextCod.textContent = 'Pay upon doorstep arrival';
            }
        }

        if (payPickup && labelPickup) {
            if (anySelected && !canPickup) {
                payPickup.disabled = true;
                if (payPickup.checked) payPickup.checked = false;
                labelPickup.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                if (subtextPickup) subtextPickup.textContent = 'Store pick-up unavailable';
            } else {
                payPickup.disabled = false;
                labelPickup.classList.remove('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                if (subtextPickup) subtextPickup.textContent = 'Pick up & pay at branch (₱0 delivery)';
            }
        }

        if (noticeEl && noticeText) {
            if (anySelected && (!canDeliver || !canPickup)) {
                noticeEl.classList.remove('hidden');
                if (!canDeliver && !canPickup) {
                    noticeText.textContent = 'Notice: Neither delivery nor pick-up is offered by selected merchant.';
                } else if (!canDeliver) {
                    noticeText.textContent = 'Notice: Doorstep delivery is not offered for one or more selected items.';
                } else if (!canPickup) {
                    noticeText.textContent = 'Notice: Store pick-up is not offered for one or more selected items.';
                }
            } else {
                noticeEl.classList.add('hidden');
            }
        }

        var selectedPayment = Array.prototype.find.call(paymentRadios, function (r) { return r.checked && !r.disabled; });
        var currentShipping = (selectedPayment && selectedPayment.value === 'pickup') ? 0 : (anySelected ? shipping : 0);

        var subtotalEl = document.getElementById('subtotal-val');
        var shippingEl = document.getElementById('shipping-val');
        var totalEl    = document.getElementById('total-val');
        var badgeEl    = document.getElementById('selected-count-badge');
        var mobileTotalEl = document.getElementById('mobile-total-val');
        var mobileCountEl = document.getElementById('mobile-item-count');

        if (subtotalEl) subtotalEl.innerText = money(subtotal);
        if (shippingEl) shippingEl.innerText = currentShipping > 0 ? money(currentShipping) : 'Free / ₱0.00';
        if (totalEl) totalEl.innerText = money(subtotal + currentShipping);
        if (badgeEl) badgeEl.innerText = totalQty + (totalQty === 1 ? ' item selected' : ' items selected');

        if (mobileTotalEl) mobileTotalEl.innerText = money(subtotal + currentShipping);
        if (mobileCountEl) mobileCountEl.innerText = totalQty + ' items';

        if (placeOrderBtn) {
            placeOrderBtn.disabled = !anySelected;
            if (placeOrderBtnText) placeOrderBtnText.innerText = 'Proceed to Checkout (' + totalQty + ')';
        }
        if (mobileCheckoutBtn) {
            mobileCheckoutBtn.disabled = !anySelected;
        }
    }

    // Event Listeners for Checkboxes
    if (selectAll) {
        selectAll.addEventListener('change', function (e) {
            itemCheckboxes.forEach(function (cb) { cb.checked = e.target.checked; });
            updateUI();
        });
    }

    storeCheckboxes.forEach(function (scb) {
        scb.addEventListener('change', function (e) {
            var sId = this.getAttribute('data-shop-id');
            var storeItems = document.querySelectorAll('.cart-item-checkbox[data-shop-id="' + sId + '"]');
            storeItems.forEach(function (cb) { cb.checked = e.target.checked; });
            updateUI();
        });
    });

    itemCheckboxes.forEach(function (cb) {
        cb.addEventListener('change', updateUI);
    });

    paymentRadios.forEach(function (radio) {
        radio.addEventListener('change', updateUI);
    });

    // Form Submission & Confirmation Modal
    var checkoutForm     = document.getElementById('cart-checkout-form');
    var confirmModal     = document.getElementById('checkout-confirm-modal');
    var modalTitle       = document.getElementById('modal-title');
    var modalDesc        = document.getElementById('modal-description');
    var modalIcon        = document.getElementById('modal-icon');
    var modalShopInfo    = document.getElementById('modal-shop-info');
    var modalShopName    = document.getElementById('modal-shop-name');
    var modalTotal       = document.getElementById('modal-order-total');
    var modalCancelBtn   = document.getElementById('modal-cancel-btn');
    var modalConfirmBtn  = document.getElementById('modal-confirm-btn');
    var modalConfirmText = document.getElementById('modal-confirm-text');
    var modalGcashFulfillment = document.getElementById('modal-gcash-fulfillment');
    var formFulfillmentMethod = document.getElementById('form-fulfillment-method');
    var isConfirmedSubmit = false;

    function getSelectedShops() {
        var selectedShops = [];
        itemCheckboxes.forEach(function (cb) {
            if (cb.checked) {
                var sName = cb.getAttribute('data-shop-name');
                if (sName && selectedShops.indexOf(sName) === -1) {
                    selectedShops.push(sName);
                }
            }
        });
        return selectedShops;
    }

    // Modal Fulfillment Choice Event
    var modalFulfillmentRadios = document.querySelectorAll('input[name="modal_fulfillment_choice"]');
    modalFulfillmentRadios.forEach(function (r) {
        r.addEventListener('change', function () {
            var subtotal = 0;
            itemCheckboxes.forEach(function (cb) {
                if (cb.checked) {
                    var price = parseFloat(cb.getAttribute('data-price')) || 0;
                    var qty   = parseInt(cb.getAttribute('data-qty'), 10) || 1;
                    subtotal += price * qty;
                }
            });

            var labelDel  = document.getElementById('label-opt-delivery');
            var labelPick = document.getElementById('label-opt-pickup');

            if (this.value === 'pickup') {
                if (labelPick) { labelPick.className = 'flex items-center gap-3 p-2.5 rounded-xl border border-primary bg-primary/5 cursor-pointer'; }
                if (labelDel)  { labelDel.className  = 'flex items-center gap-3 p-2.5 rounded-xl border border-outline-variant hover:bg-surface-container cursor-pointer'; }
                if (modalTotal) modalTotal.textContent = money(subtotal);
                var shops = getSelectedShops();
                if (shops.length > 0 && modalShopName && modalShopInfo) {
                    modalShopName.textContent = shops.join(', ');
                    modalShopInfo.classList.remove('hidden');
                }
            } else {
                if (labelDel)  { labelDel.className  = 'flex items-center gap-3 p-2.5 rounded-xl border border-primary bg-primary/5 cursor-pointer'; }
                if (labelPick) { labelPick.className = 'flex items-center gap-3 p-2.5 rounded-xl border border-outline-variant hover:bg-surface-container cursor-pointer'; }
                if (modalTotal) modalTotal.textContent = money(subtotal + shipping);
                if (modalShopInfo) modalShopInfo.classList.add('hidden');
            }
        });
    });

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (e) {
            if (isConfirmedSubmit) return;

            var selected = Array.prototype.filter.call(itemCheckboxes, function (cb) { return cb.checked; });
            var inlineError = document.getElementById('checkout-inline-error');
            var inlineErrorText = document.getElementById('checkout-error-text');

            if (selected.length === 0) {
                e.preventDefault();
                if (inlineError && inlineErrorText) {
                    inlineErrorText.textContent = 'Please select at least one item to proceed with checkout.';
                    inlineError.classList.remove('hidden');
                }
                return;
            }

            var selectedPayment = Array.prototype.find.call(paymentRadios, function (r) { return r.checked && !r.disabled; });
            if (!selectedPayment) {
                e.preventDefault();
                if (inlineError && inlineErrorText) {
                    inlineErrorText.textContent = 'Please select a valid payment method.';
                    inlineError.classList.remove('hidden');
                }
                return;
            }

            var paymentVal = selectedPayment.value;
            var subtotal = 0;
            selected.forEach(function (cb) {
                var p = parseFloat(cb.getAttribute('data-price')) || 0;
                var q = parseInt(cb.getAttribute('data-qty'), 10) || 1;
                subtotal += p * q;
            });

            if (paymentVal === 'gcash') {
                e.preventDefault();
                modalTitle.textContent = 'GCash Fulfillment Selection';
                modalIcon.textContent = 'account_balance_wallet';
                modalDesc.textContent = 'Please choose how your order will be fulfilled before proceeding to GCash payment:';
                if (modalConfirmText) modalConfirmText.textContent = 'Proceed to PayMongo';
                if (modalGcashFulfillment) modalGcashFulfillment.classList.remove('hidden');

                var chosenFulfillment = document.querySelector('input[name="modal_fulfillment_choice"]:checked:not(:disabled)')
                    || document.querySelector('input[name="modal_fulfillment_choice"]:not(:disabled)');
                if (chosenFulfillment) chosenFulfillment.checked = true;

                var fVal = chosenFulfillment ? chosenFulfillment.value : 'delivery';
                if (fVal === 'pickup') {
                    if (modalTotal) modalTotal.textContent = money(subtotal);
                    var shops = getSelectedShops();
                    if (shops.length > 0 && modalShopName && modalShopInfo) {
                        modalShopName.textContent = shops.join(', ');
                        modalShopInfo.classList.remove('hidden');
                    }
                } else {
                    if (modalTotal) modalTotal.textContent = money(subtotal + shipping);
                    if (modalShopInfo) modalShopInfo.classList.add('hidden');
                }

                if (confirmModal) confirmModal.classList.remove('hidden');

            } else if (paymentVal === 'pickup') {
                e.preventDefault();
                if (formFulfillmentMethod) formFulfillmentMethod.value = 'pickup';
                modalTitle.textContent = 'Confirm Store Pick-up Order';
                modalIcon.textContent = 'storefront';
                modalDesc.textContent = 'Your order will be prepared for store pick-up at the shop branch.';
                if (modalConfirmText) modalConfirmText.textContent = 'Confirm Order';
                if (modalGcashFulfillment) modalGcashFulfillment.classList.add('hidden');

                var selectedShops = getSelectedShops();
                if (selectedShops.length > 0 && modalShopName && modalShopInfo) {
                    modalShopName.textContent = selectedShops.join(', ');
                    modalShopInfo.classList.remove('hidden');
                }
                if (modalTotal) modalTotal.textContent = money(subtotal);
                if (confirmModal) confirmModal.classList.remove('hidden');

            } else if (paymentVal === 'cod') {
                e.preventDefault();
                if (formFulfillmentMethod) formFulfillmentMethod.value = 'delivery';
                modalTitle.textContent = 'Confirm Cash on Delivery Order';
                modalIcon.textContent = 'local_shipping';
                modalDesc.textContent = 'Payment will be collected upon doorstep delivery.';
                if (modalConfirmText) modalConfirmText.textContent = 'Confirm Order';
                if (modalGcashFulfillment) modalGcashFulfillment.classList.add('hidden');
                if (modalShopInfo) modalShopInfo.classList.add('hidden');
                if (modalTotal) modalTotal.textContent = money(subtotal + shipping);
                if (confirmModal) confirmModal.classList.remove('hidden');
            }
        });
    }

    if (modalCancelBtn) {
        modalCancelBtn.addEventListener('click', function () {
            if (confirmModal) confirmModal.classList.add('hidden');
        });
    }

    if (confirmModal) {
        confirmModal.addEventListener('click', function (e) {
            if (e.target === confirmModal) confirmModal.classList.add('hidden');
        });
    }

    if (modalConfirmBtn) {
        modalConfirmBtn.addEventListener('click', function () {
            var selectedPayment = Array.prototype.find.call(paymentRadios, function (r) { return r.checked; });
            var paymentVal = selectedPayment ? selectedPayment.value : '';

            if (paymentVal === 'gcash') {
                var chosenFulfillment = document.querySelector('input[name="modal_fulfillment_choice"]:checked');
                var fVal = chosenFulfillment ? chosenFulfillment.value : 'delivery';
                if (formFulfillmentMethod) formFulfillmentMethod.value = fVal;
            }

            if (confirmModal) confirmModal.classList.add('hidden');
            isConfirmedSubmit = true;
            if (checkoutForm) checkoutForm.submit();
        });
    }

    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', function () {
            var ids = Array.prototype.filter.call(itemCheckboxes, function (cb) { return cb.checked; })
                .map(function (cb) { return cb.getAttribute('data-id'); });
            if (!ids.length) return;
            if (!confirm('Are you sure you want to remove ' + ids.length + ' item(s) from your cart?')) return;
            postForm(removeSelectedUrl, { ids: ids }).then(function () {
                window.location.reload();
            });
        });
    }

    // Quantity Plus/Minus buttons
    document.querySelectorAll('.qty-minus, .qty-plus').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id    = this.getAttribute('data-id');
            var stock = parseInt(this.getAttribute('data-stock'), 10) || 99;
            var valueEl = this.parentElement.querySelector('.qty-value');
            var qty  = parseInt(valueEl.textContent, 10) || 1;
            qty += this.classList.contains('qty-plus') ? 1 : -1;
            qty = Math.max(1, Math.min(stock, qty));
            postForm(updateUrl, { item_id: id, quantity: qty }).then(function () {
                window.location.reload();
            });
        });
    });

    // Initial state update
    updateUI();
})();
</script>
<?= $this->endSection() ?>