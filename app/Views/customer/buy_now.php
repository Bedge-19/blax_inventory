<?= $this->extend('layouts/marketplace') ?>

<?= $this->section('content') ?>

<main class="max-w-6xl mx-auto px-md py-lg">

    <!-- Breadcrumb & Back Link -->
    <div class="flex items-center justify-between gap-md mb-lg">
        <a href="<?= base_url('product/' . $product['id']) ?>" class="inline-flex items-center gap-xs text-label-md font-semibold text-primary hover:underline transition-colors">
            <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            Back to Product
        </a>
        <span class="text-xs text-on-surface-variant font-medium">Instant Direct Checkout</span>
    </div>

    <!-- Flash Messages -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="mb-lg p-md rounded-xl bg-error-container text-on-error-container text-body-md flex items-center gap-sm border border-error/30">
            <span class="material-symbols-outlined text-error">error</span>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('warning')): ?>
        <div class="mb-lg p-md rounded-xl bg-amber-50 text-amber-800 text-body-md flex items-center gap-sm border border-amber-200">
            <span class="material-symbols-outlined text-amber-600">warning</span>
            <span><?= esc(session()->getFlashdata('warning')) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-xl items-start">

        <!-- Left Column: Single Product Summary -->
        <div class="lg:col-span-6 space-y-lg">

            <div class="glass-card rounded-2xl p-xl shadow-sm border border-outline-variant/30 space-y-lg">
                
                <div class="flex items-center justify-between border-b border-outline-variant/20 pb-md">
                    <div class="flex items-center gap-xs text-label-md font-bold text-on-surface">
                        <span class="material-symbols-outlined text-primary text-[22px]">storefront</span>
                        <span><?= esc($shop['shop_name'] ?? 'Partner Merchant') ?></span>
                    </div>
                    <span class="text-xs text-on-surface-variant px-2.5 py-1 bg-surface-container-high rounded-full font-medium">Direct Order</span>
                </div>

                <!-- Product Row -->
                <div class="flex gap-md sm:gap-lg">
                    <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-xl overflow-hidden bg-surface-container-low border border-outline-variant/30 shrink-0 flex items-center justify-center">
                        <?php if (!empty($imageUrl)): ?>
                            <img src="<?= esc($imageUrl) ?>" alt="<?= esc($product['name']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="material-symbols-outlined text-outline-variant text-[48px]">image</span>
                        <?php endif; ?>
                    </div>

                    <div class="flex-1 flex flex-col justify-between min-w-0">
                        <div>
                            <h1 class="text-title-md font-bold text-on-surface truncate"><?= esc($product['name']) ?></h1>
                            
                            <?php if (!empty($variantLabel)): ?>
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-secondary-container text-on-secondary-container text-xs font-semibold">
                                        <?= esc($variantLabel) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-baseline justify-between gap-xs mt-xs">
                            <span class="text-xs text-on-surface-variant">Unit Price</span>
                            <span class="text-body-md font-bold text-on-surface">₱<?= number_format($unitPrice, 2) ?></span>
                        </div>

                        <div class="flex items-center justify-between gap-xs border-t border-outline-variant/10 pt-xs">
                            <span class="text-xs text-on-surface-variant">Quantity</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-surface-container-highest text-xs font-bold text-on-surface">
                                <?= (int) $quantity ?> <?= $quantity === 1 ? 'unit' : 'units' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Item Line Total -->
                <div class="bg-surface-container-low/60 rounded-xl p-md flex items-center justify-between">
                    <span class="text-label-md font-medium text-on-surface-variant">Item Subtotal</span>
                    <span class="text-title-lg font-extrabold text-primary">₱<?= number_format($subtotal, 2) ?></span>
                </div>

                <p class="text-xs text-on-surface-variant text-center">
                    Want to adjust quantity or variant? 
                    <a href="<?= base_url('product/' . $product['id']) ?>" class="text-primary font-semibold hover:underline">Change on product page</a>
                </p>

            </div>

            <!-- Guarantee Badges -->
            <div class="grid grid-cols-2 gap-md text-xs text-on-surface-variant">
                <div class="flex items-center gap-xs p-md rounded-xl bg-surface-container-lowest border border-outline-variant/20">
                    <span class="material-symbols-outlined text-primary text-[20px]">local_shipping</span>
                    <span>Polomolok delivery service</span>
                </div>
                <div class="flex items-center gap-xs p-md rounded-xl bg-surface-container-lowest border border-outline-variant/20">
                    <span class="material-symbols-outlined text-primary text-[20px]">verified_user</span>
                    <span>Direct merchant order</span>
                </div>
            </div>

        </div>

        <!-- Right Column: Checkout Form -->
        <div class="lg:col-span-6">

            <form action="<?= base_url('buy-now/place') ?>" method="POST" id="buy-now-form" class="space-y-lg">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= esc($product['id']) ?>">
                <input type="hidden" name="variant_id" value="<?= esc($selectedVariant['id'] ?? '') ?>">
                <input type="hidden" name="quantity" value="<?= esc($quantity) ?>">
                <input type="hidden" name="fulfillment_method" id="form-fulfillment-method" value="delivery">

                <!-- Payment Method Section -->
                <?php
                    $shopOffersPickup   = !isset($shop['offers_pickup']) || (int)$shop['offers_pickup'] === 1;
                    $shopOffersDelivery = !isset($shop['offers_delivery']) || (int)$shop['offers_delivery'] === 1;
                ?>
                <div class="glass-card rounded-2xl p-xl shadow-sm border border-outline-variant/30 space-y-md">
                    <h2 class="text-title-sm font-bold text-on-surface uppercase tracking-wider text-xs">Payment &amp; Fulfillment</h2>

                    <div class="grid grid-cols-1 gap-sm">

                        <!-- GCash Option -->
                        <label class="flex items-center gap-md p-md bg-surface-container-low hover:bg-surface-container transition-all rounded-xl border border-outline-variant/30 cursor-pointer group">
                            <input class="w-4 h-4 text-primary border-outline-variant focus:ring-primary payment-radio" name="payment_method" type="radio" value="gcash" checked>
                            <span class="material-symbols-outlined text-[#007DFE] group-hover:scale-110 transition-transform">account_balance_wallet</span>
                            <div class="flex flex-col">
                                <span class="text-body-md font-bold">GCash</span>
                                <span class="text-xs text-on-surface-variant">Instant checkout via PayMongo</span>
                            </div>
                        </label>

                        <!-- Store Pick-up Option -->
                        <label class="flex items-center gap-md p-md bg-surface-container-low transition-all rounded-xl border border-outline-variant/30 <?= $shopOffersPickup ? 'hover:bg-surface-container cursor-pointer group' : 'opacity-40 cursor-not-allowed pointer-events-none' ?>">
                            <input class="w-4 h-4 text-primary border-outline-variant focus:ring-primary payment-radio" name="payment_method" type="radio" value="pickup" <?= !$shopOffersPickup ? 'disabled' : '' ?>>
                            <span class="material-symbols-outlined text-secondary <?= $shopOffersPickup ? 'group-hover:scale-110' : '' ?> transition-transform">storefront</span>
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2">
                                    <span class="text-body-md font-bold">Store Pick-up</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">FREE</span>
                                </div>
                                <span class="text-xs text-on-surface-variant">
                                    <?= $shopOffersPickup ? 'Pick up directly at ' . esc($shop['shop_name'] ?? 'shop branch') : 'Kasalukuyang hindi available ang store pick-up' ?>
                                </span>
                            </div>
                        </label>

                        <!-- Cash on Delivery Option -->
                        <label class="flex items-center gap-md p-md bg-surface-container-low transition-all rounded-xl border border-outline-variant/30 <?= $shopOffersDelivery ? 'hover:bg-surface-container cursor-pointer group' : 'opacity-40 cursor-not-allowed pointer-events-none' ?>">
                            <input class="w-4 h-4 text-primary border-outline-variant focus:ring-primary payment-radio" name="payment_method" type="radio" value="cod" <?= !$shopOffersDelivery ? 'disabled' : '' ?>>
                            <span class="material-symbols-outlined text-secondary <?= $shopOffersDelivery ? 'group-hover:scale-110' : '' ?> transition-transform">local_shipping</span>
                            <div class="flex flex-col">
                                <span class="text-body-md font-bold">Cash on Delivery</span>
                                <span class="text-xs text-on-surface-variant">
                                    <?= $shopOffersDelivery ? 'Pay when delivered to doorstep' : 'Kasalukuyang hindi available ang doorstep delivery' ?>
                                </span>
                            </div>
                        </label>

                    </div>

                    <?php if (!$shopOffersDelivery && $shopOffersPickup): ?>
                        <div class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-300 text-xs flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-amber-600 shrink-0">info</span>
                            <span><strong>Paalala:</strong> Walang doorstep delivery service ang shop na ito ngayon. Store Pick-up lamang ang available.</span>
                        </div>
                    <?php elseif (!$shopOffersPickup && $shopOffersDelivery): ?>
                        <div class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-300 text-xs flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-amber-600 shrink-0">info</span>
                            <span><strong>Paalala:</strong> Walang store pick-up service ang shop na ito ngayon. Doorstep Delivery lamang ang available.</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Shipping Address Section (Hidden if Store Pick-up) -->
                <div id="address-section" class="glass-card rounded-2xl p-xl shadow-sm border border-outline-variant/30 space-y-md">
                    <div class="flex items-center justify-between">
                        <h2 class="text-title-sm font-bold text-on-surface uppercase tracking-wider text-xs">Shipping Address</h2>
                        <a href="<?= base_url('customer/addresses') ?>" class="text-xs text-primary font-semibold hover:underline">Manage</a>
                    </div>

                    <?php if (!empty($addresses)): ?>
                        <select name="shipping_address_id" id="shipping-address-select" class="w-full p-md bg-surface-container-lowest border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary" required>
                            <?php foreach ($addresses as $addr): ?>
                                <option value="<?= $addr['id'] ?>" <?= !empty($addr['is_default']) ? 'selected' : '' ?>>
                                    <?= esc($addr['recipient_name']) ?> — <?= esc($addr['address_line1']) ?>, <?= esc($addr['city']) ?> (<?= esc($addr['phone_number'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-[11px] text-on-surface-variant italic">* Delivery is restricted to Polomolok, South Cotabato.</p>
                    <?php else: ?>
                        <p class="text-xs text-on-surface-variant mb-xs">No saved addresses. Please enter your address:</p>
                        <input type="text" name="shipping_address" placeholder="Enter delivery address (e.g. Poblacion, Polomolok)" class="w-full p-md bg-surface-container-lowest border border-outline-variant rounded-xl text-sm" required>
                    <?php endif; ?>
                </div>

                <!-- Order Cost Summary & Place Order -->
                <div class="glass-card rounded-2xl p-xl shadow-sm border border-outline-variant/30 space-y-md">
                    <h2 class="text-title-sm font-bold text-on-surface uppercase tracking-wider text-xs">Order Summary</h2>

                    <div class="space-y-sm text-sm">
                        <div class="flex justify-between text-on-surface-variant">
                            <span>Item Subtotal</span>
                            <span class="font-medium text-on-surface" id="subtotal-display">₱<?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="flex justify-between text-on-surface-variant">
                            <span>Shipping Fee</span>
                            <span class="font-medium text-on-surface" id="shipping-display">₱<?= number_format($shipping, 2) ?></span>
                        </div>
                        <div class="flex justify-between text-title-md font-bold text-on-surface border-t border-outline-variant/20 pt-sm">
                            <span>Total Amount</span>
                            <span class="text-primary text-title-lg font-extrabold" id="total-display">₱<?= number_format($subtotal + $shipping, 2) ?></span>
                        </div>
                    </div>

                    <button type="submit" id="place-order-btn" class="w-full py-lg bg-primary text-on-primary rounded-full font-headline-md text-headline-md hover:shadow-lg transition-all active:scale-[0.98] flex items-center justify-center gap-sm mt-md">
                        <span id="place-order-btn-text">Confirm &amp; Place Order</span>
                        <span class="material-symbols-outlined text-[20px]">lock</span>
                    </button>

                    <p class="text-[11px] text-center text-on-surface-variant">
                        By placing your order, you agree to Blax Marketplace terms and merchant fulfillment policy.
                    </p>
                </div>

            </form>

        </div>

    </div>

</main>

<!-- GCash Fulfillment Choice Modal -->
<div id="gcash-fulfillment-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden p-4">
    <div class="glass-card bg-surface-container-lowest border border-outline-variant/50 rounded-2xl p-6 w-full max-w-md shadow-2xl relative">
        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/30 mb-4">
            <div class="flex items-center gap-2 text-[#007DFE]">
                <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
                <h3 class="font-title-md font-bold text-on-surface">GCash Order Fulfillment</h3>
            </div>
            <button type="button" id="btn-close-gcash-modal" class="p-1 rounded-full text-outline hover:text-on-surface hover:bg-surface-container">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <p class="text-xs text-on-surface-variant mb-4">
            How would you like to receive your item from <strong><?= esc($shop['shop_name'] ?? 'the store') ?></strong>?
        </p>

        <div class="flex flex-col gap-3">
            <button type="button" id="btn-choose-delivery" <?= !$shopOffersDelivery ? 'disabled' : '' ?> class="p-4 rounded-xl border-2 <?= $shopOffersDelivery ? 'border-primary/40 hover:border-primary hover:bg-primary/5 cursor-pointer group' : 'border-outline-variant/30 opacity-40 cursor-not-allowed pointer-events-none' ?> transition-all text-left flex items-start gap-3">
                <span class="material-symbols-outlined text-primary text-2xl <?= $shopOffersDelivery ? 'group-hover:scale-110' : '' ?> transition-transform">local_shipping</span>
                <div class="flex-1">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-bold text-on-surface">Doorstep Delivery</span>
                        <span class="text-xs font-bold text-primary"><?= $shopOffersDelivery ? '₱' . number_format($shipping, 2) : 'Unavailable' ?></span>
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">
                        <?= $shopOffersDelivery ? 'Delivered straight to your address in Polomolok' : 'Kasalukuyang walang doorstep delivery ang shop na ito.' ?>
                    </p>
                </div>
            </button>

            <button type="button" id="btn-choose-pickup" <?= !$shopOffersPickup ? 'disabled' : '' ?> class="p-4 rounded-xl border-2 <?= $shopOffersPickup ? 'border-outline-variant hover:border-secondary hover:bg-secondary/5 cursor-pointer group' : 'border-outline-variant/30 opacity-40 cursor-not-allowed pointer-events-none' ?> transition-all text-left flex items-start gap-3">
                <span class="material-symbols-outlined text-secondary text-2xl <?= $shopOffersPickup ? 'group-hover:scale-110' : '' ?> transition-transform">storefront</span>
                <div class="flex-1">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-bold text-on-surface">Store Pick-up</span>
                        <span class="text-xs font-bold text-emerald-600"><?= $shopOffersPickup ? 'FREE' : 'Unavailable' ?></span>
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">
                        <?= $shopOffersPickup ? 'Pick up at store counter with your digital QR pass' : 'Kasalukuyang walang store pick-up ang shop na ito.' ?>
                    </p>
                </div>
            </button>
        </div>
    </div>
</div>

<!-- Floating Go Back Button -->
<a href="<?= base_url('product/' . $product['id']) ?>" aria-label="Go Back" class="fixed bottom-8 left-8 w-14 h-14 bg-surface-container-lowest text-primary rounded-full shadow-lg flex items-center justify-center z-40 transition-transform border border-outline-variant duration-300 hover:scale-105 hover:shadow-xl">
    <span class="material-symbols-outlined text-[28px]">arrow_back</span>
</a>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var subtotal = <?= json_encode((float) $subtotal) ?>;
    var baseShipping = <?= json_encode((float) $shipping) ?>;

    var form = document.getElementById('buy-now-form');
    var paymentRadios = document.querySelectorAll('.payment-radio');
    var fulfillmentInput = document.getElementById('form-fulfillment-method');
    var addressSection = document.getElementById('address-section');
    var addressSelect = document.getElementById('shipping-address-select');
    var shippingDisplay = document.getElementById('shipping-display');
    var totalDisplay = document.getElementById('total-display');
    var placeOrderBtnText = document.getElementById('place-order-btn-text');

    var gcashModal = document.getElementById('gcash-fulfillment-modal');
    var btnCloseGcash = document.getElementById('btn-close-gcash-modal');
    var btnChooseDelivery = document.getElementById('btn-choose-delivery');
    var btnChoosePickup = document.getElementById('btn-choose-pickup');

    var shopOffersDelivery = <?= json_encode($shopOffersDelivery) ?>;
    var shopOffersPickup   = <?= json_encode($shopOffersPickup) ?>;

    function formatMoney(amount) {
        return '₱' + Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function getSelectedPayment() {
        var selected = '';
        paymentRadios.forEach(function (r) {
            if (r.checked && !r.disabled) selected = r.value;
        });
        if (!selected) {
            for (var i = 0; i < paymentRadios.length; i++) {
                if (!paymentRadios[i].disabled) {
                    paymentRadios[i].checked = true;
                    selected = paymentRadios[i].value;
                    break;
                }
            }
        }
        return selected || 'gcash';
    }

    function updateFulfillmentAndTotals() {
        var selectedMethod = getSelectedPayment();
        var isPickup = (selectedMethod === 'pickup');
        var shippingFee = isPickup ? 0.00 : baseShipping;
        var total = subtotal + shippingFee;

        // Sync hidden fulfillment method
        if (fulfillmentInput) {
            fulfillmentInput.value = isPickup ? 'pickup' : 'delivery';
        }

        // Display updates
        if (shippingDisplay) {
            shippingDisplay.textContent = isPickup ? '₱0.00 (Free)' : formatMoney(shippingFee);
        }
        if (totalDisplay) {
            totalDisplay.textContent = formatMoney(total);
        }

        // Button label update
        if (placeOrderBtnText) {
            if (selectedMethod === 'gcash') {
                placeOrderBtnText.textContent = 'Pay via GCash';
            } else if (selectedMethod === 'pickup') {
                placeOrderBtnText.textContent = 'Confirm Store Pick-up';
            } else {
                placeOrderBtnText.textContent = 'Confirm Cash on Delivery';
            }
        }

        // Address section visibility and requirement
        if (addressSection) {
            if (isPickup) {
                addressSection.classList.add('opacity-40', 'pointer-events-none');
                if (addressSelect) addressSelect.removeAttribute('required');
            } else {
                addressSection.classList.remove('opacity-40', 'pointer-events-none');
                if (addressSelect) addressSelect.setAttribute('required', 'required');
            }
        }
    }

    paymentRadios.forEach(function (r) {
        r.addEventListener('change', updateFulfillmentAndTotals);
    });

    updateFulfillmentAndTotals();

    // GCash Modal Intercept
    var gcashFulfillmentChosen = false;

    if (form) {
        form.addEventListener('submit', function (e) {
            var method = getSelectedPayment();
            if (method === 'gcash' && !gcashFulfillmentChosen) {
                e.preventDefault();
                if (gcashModal) gcashModal.classList.remove('hidden');
            }
        });
    }

    if (btnCloseGcash) {
        btnCloseGcash.addEventListener('click', function () {
            if (gcashModal) gcashModal.classList.add('hidden');
        });
    }

    if (btnChooseDelivery) {
        btnChooseDelivery.addEventListener('click', function () {
            gcashFulfillmentChosen = true;
            if (fulfillmentInput) fulfillmentInput.value = 'delivery';
            if (gcashModal) gcashModal.classList.add('hidden');
            form.submit();
        });
    }

    if (btnChoosePickup) {
        btnChoosePickup.addEventListener('click', function () {
            gcashFulfillmentChosen = true;
            if (fulfillmentInput) fulfillmentInput.value = 'pickup';
            if (addressSelect) addressSelect.removeAttribute('required');
            if (gcashModal) gcashModal.classList.add('hidden');
            form.submit();
        });
    }
})();
</script>
<?= $this->endSection() ?>
