<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<style>
    #checkout-sidebar {
        transition: all 0.3s ease-in-out;
        opacity: 0;
        pointer-events: none;
        transform: translateY(10px);
    }
    #checkout-sidebar.visible {
        opacity: 1;
        pointer-events: auto;
        transform: translateY(0);
    }
</style>

<main class="max-w-container-max mx-auto px-4 md:px-8 lg:px-10 py-8 md:py-xl flex-grow w-full">

    <?php
        $shipping = (float) ($shippingFee ?? 50.00);
        $csrfName = csrf_token();
        $csrfHash = csrf_hash();
    ?>

    <div class="mb-xl">

        <h1 class="text-headline-lg font-headline-lg text-on-surface">Cart &amp; Checkout</h1>
        <p class="text-body-md text-on-surface-variant mt-xs">Select items to proceed with your order.</p>

    </div>

    <?php if (session()->getFlashdata('error')): ?>

        <div class="p-md rounded-xl bg-error-container text-on-error-container text-sm font-medium mb-lg">

            <?= session()->getFlashdata('error') ?>

        </div>

    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>

        <div class="p-md rounded-xl bg-green-100 text-green-800 text-sm font-medium mb-lg">

            <?= session()->getFlashdata('success') ?>

        </div>

    <?php endif; ?>

    <?php if (empty($cartItems)): ?>

        <div class="glass-card rounded-2xl p-xxl text-center max-w-xl mx-auto my-xl border border-outline-variant/30">

            <span class="material-symbols-outlined text-6xl text-outline mb-md">shopping_cart</span>
            <h2 class="text-headline-md font-bold text-on-surface mb-xs">Your Cart is Empty</h2>
            <p class="text-on-surface-variant text-body-md mb-lg">Explore our marketplace to add your favorite products and printing services.</p>
            <a href="<?= base_url('/') ?>" class="inline-block bg-primary text-on-primary px-xl py-md rounded-xl font-button hover:bg-primary/90 transition-colors">Start Shopping</a>

        </div>

    <?php else: ?>

        <form action="<?= base_url('cart/checkout') ?>" method="POST" class="grid grid-cols-12 gap-gutter" id="cart-checkout-form">

            <?= csrf_field() ?>
            <input type="hidden" name="fulfillment_method" id="form-fulfillment-method" value="delivery">

            <!-- Left: Cart Items -->
            <div class="col-span-12 lg:col-span-8 space-y-md">

                <div class="flex items-center justify-between px-md py-sm">

                    <label class="flex items-center gap-md cursor-pointer group">

                        <input class="w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary" id="select-all" type="checkbox">
                        <span class="text-label-sm font-bold text-on-surface-variant group-hover:text-primary transition-colors">SELECT ALL (<?= count($cartItems) ?> ITEMS)</span>

                    </label>

                    <button type="button" id="delete-selected" class="text-label-sm font-bold text-error hover:underline transition-all duration-300 hover:scale-105">DELETE SELECTED</button>

                </div>

                <?php foreach ($cartItems as $item): ?>

                    <div class="glass-card rounded-xl p-lg flex gap-lg group transition-all hover:shadow-sm">

                        <div class="flex items-start pt-md">

                            <input class="cart-item-checkbox w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary" name="selected_items[]" type="checkbox" value="<?= (int) $item['id'] ?>" data-id="<?= (int) $item['id'] ?>" data-price="<?= (float) $item['price'] ?>" data-qty="<?= (int) $item['quantity'] ?>" data-shop-name="<?= esc($item['shop_name'] ?? 'RHK Store') ?>" data-offers-delivery="<?= (isset($item['offers_delivery']) && (int)$item['offers_delivery'] === 0) ? '0' : '1' ?>" data-offers-pickup="<?= (isset($item['offers_pickup']) && (int)$item['offers_pickup'] === 0) ? '0' : '1' ?>" <?= !empty($item['is_selected']) ? 'checked' : '' ?>>

                        </div>

                        <div class="w-24 h-24 md:w-32 md:h-32 bg-surface-container rounded-lg overflow-hidden shrink-0">

                            <img alt="<?= esc($item['product_name']) ?>" class="w-full h-full object-cover" src="<?= esc(product_image_url($item['image_url'] ?? null)) ?>">

                        </div>

                        <div class="flex flex-col flex-grow justify-between">

                            <div class="flex justify-between items-start">

                                <div>

                                    <h3 class="text-body-lg font-bold text-on-surface"><?= esc($item['product_name']) ?></h3>
                                    <?php if (!empty($item['variant_label'])): ?>
                                        <span class="inline-block my-0.5 px-2 py-0.5 bg-primary/10 text-primary text-xs font-semibold rounded-md border border-primary/20"><?= esc($item['variant_label']) ?></span>
                                    <?php endif; ?>
                                    <p class="text-label-sm text-on-surface-variant"><?= esc($item['shop_name'] ?? 'RHK Store') ?></p>

                                </div>

                                <p class="text-title-lg font-bold text-primary">₱<?= number_format($item['price'], 2) ?></p>

                            </div>

                            <div class="flex items-center justify-between mt-md">

                                <div class="flex items-center border border-outline-variant rounded-lg">

                                    <button type="button" class="qty-minus p-xs px-sm hover:bg-surface-container text-on-surface-variant" data-id="<?= (int) $item['id'] ?>" data-stock="<?= (int) ($item['stock_quantity'] ?? 99) ?>" aria-label="Decrease quantity">-</button>

                                    <span class="qty-value px-md py-xs text-body-md font-semibold border-x border-outline-variant"><?= (int) $item['quantity'] ?></span>

                                    <button type="button" class="qty-plus p-xs px-sm hover:bg-surface-container text-on-surface-variant" data-id="<?= (int) $item['id'] ?>" data-stock="<?= (int) ($item['stock_quantity'] ?? 99) ?>" aria-label="Increase quantity">+</button>

                                </div>

                                <a href="<?= base_url('cart/remove/' . $item['id']) ?>" class="text-error-container hover:text-error transition-colors p-sm rounded-full" aria-label="Remove item">

                                    <span class="material-symbols-outlined">delete</span>

                                </a>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

                <div class="pt-md">

                    <a class="inline-flex items-center gap-sm text-primary font-button text-button hover:underline" href="<?= base_url('/') ?>">

                        <span class="material-symbols-outlined">arrow_back</span>
                        Continue Shopping

                    </a>

                </div>

            </div>

            <!-- Right: Summary & Payment -->
            <div class="col-span-12 lg:col-span-4">

                <div class="space-y-lg sticky top-24" id="checkout-sidebar">

                    <!-- Order Summary -->
                    <div class="glass-card rounded-xl p-lg shadow-md">

                        <h2 class="text-headline-md font-headline-md text-on-surface mb-lg">Order Summary</h2>

                        <div class="space-y-md border-b border-outline-variant pb-lg mb-lg">

                            <div class="flex justify-between items-center text-body-md text-on-surface-variant">

                                <span>Subtotal</span>
                                <span class="font-semibold text-on-surface" id="subtotal-val">₱<?= number_format($total, 2) ?></span>

                            </div>

                            <div class="flex justify-between items-center text-body-md text-on-surface-variant">

                                <span>Shipping</span>
                                <span class="text-primary font-medium">₱<?= number_format($shipping, 2) ?></span>

                            </div>

                        </div>

                        <div class="flex justify-between items-center mb-lg">

                            <span class="text-title-lg font-title-lg text-on-surface">Order Total</span>
                            <span class="text-headline-md font-bold text-primary" id="total-val">₱<?= number_format($total + $shipping, 2) ?></span>

                        </div>

                        <!-- Payment Methods -->
                        <div class="space-y-sm mb-xl">

                            <label class="text-label-sm font-bold text-on-surface-variant uppercase tracking-widest block mb-xs">Payment Method</label>

                            <div class="grid grid-cols-1 gap-sm">

                                <label id="label-pay-gcash" class="flex items-center gap-md p-md bg-surface-container-low hover:bg-surface-container transition-all rounded-lg border border-outline-variant/30 cursor-pointer group">

                                    <input class="w-4 h-4 text-primary border-outline-variant focus:ring-primary" name="payment_method" type="radio" value="gcash" id="cart-pay-gcash">
                                    <span class="material-symbols-outlined text-[#007DFE] group-hover:scale-110 transition-transform">account_balance_wallet</span>
                                    <div class="flex flex-col">
                                        <span class="text-body-md font-bold">GCash</span>
                                        <span class="text-xs text-on-surface-variant">PayMongo instant checkout</span>
                                    </div>

                                </label>

                                <label id="label-pay-pickup" class="flex items-center gap-md p-md bg-surface-container-low hover:bg-surface-container transition-all rounded-lg border border-outline-variant/30 cursor-pointer group">

                                    <input class="w-4 h-4 text-primary border-outline-variant focus:ring-primary" name="payment_method" type="radio" value="pickup" id="cart-pay-pickup">
                                    <span class="material-symbols-outlined text-secondary group-hover:scale-110 transition-transform">storefront</span>
                                    <div class="flex flex-col">
                                        <span class="text-body-md font-bold">Store Pick-up</span>
                                        <span class="text-xs text-on-surface-variant" id="subtext-pay-pickup">Pick up at the shop branch</span>
                                    </div>

                                </label>

                                <label id="label-pay-cod" class="flex items-center gap-md p-md bg-surface-container-low hover:bg-surface-container transition-all rounded-lg border border-outline-variant/30 cursor-pointer group">

                                    <input class="w-4 h-4 text-primary border-outline-variant focus:ring-primary" name="payment_method" type="radio" value="cod" id="cart-pay-cod">
                                    <span class="material-symbols-outlined text-secondary group-hover:scale-110 transition-transform">local_shipping</span>
                                    <div class="flex flex-col">
                                        <span class="text-body-md font-bold">Cash on Delivery</span>
                                        <span class="text-xs text-on-surface-variant" id="subtext-pay-cod">Pay at doorstep</span>
                                    </div>

                                </label>

                            </div>

                            <!-- Cart Service Restriction Notice -->
                            <div id="cart-service-notice" class="hidden mt-2 p-2.5 rounded-lg bg-amber-500/10 border border-amber-500/25 text-amber-800 dark:text-amber-300 text-xs flex items-start gap-2">
                                <span class="material-symbols-outlined text-[16px] text-amber-600 shrink-0 mt-0.5">info</span>
                                <span id="cart-service-notice-text"></span>
                            </div>

                        </div>

                        <div id="checkout-inline-error" class="hidden mb-md p-sm rounded-lg bg-error-container/20 border border-error text-error text-xs font-semibold flex items-center gap-xs">
                            <span class="material-symbols-outlined text-[16px]">error</span>
                            <span id="checkout-error-text"></span>
                        </div>

                        <button type="submit" class="w-full py-md bg-primary text-on-primary rounded-lg font-button text-button disabled:opacity-50 disabled:grayscale disabled:cursor-not-allowed hover:shadow-lg hover:-translate-y-0.5 transition-all active:scale-95 flex items-center justify-center gap-sm duration-300 hover:scale-105" id="place-order-btn" disabled>

                            Confirm &amp; Pay
                            <span class="material-symbols-outlined text-[18px]">lock</span>

                        </button>

                    </div>

                    <!-- Shipping Address -->
                    <div class="p-lg glass-card rounded-xl">

                        <div class="flex items-center justify-between mb-md">

                            <span class="text-label-sm font-bold text-on-surface-variant uppercase tracking-widest">Shipping To</span>

                        </div>

                        <?php if (!empty($addresses)): ?>

                            <select name="shipping_address_id" class="w-full p-md bg-surface-container-lowest border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary" required>

                                <?php foreach ($addresses as $addr): ?>

                                    <option value="<?= $addr['id'] ?>"><?= esc($addr['recipient_name']) ?> - <?= esc($addr['address_line1']) ?>, <?= esc($addr['city']) ?></option>

                                <?php endforeach; ?>

                            </select>

                        <?php else: ?>

                            <p class="text-xs text-on-surface-variant mb-xs">No address saved. Please type address below:</p>
                            <input type="text" name="shipping_address" placeholder="Enter delivery address" class="w-full p-md bg-surface-container-lowest border border-outline-variant rounded-xl text-sm" required>

                        <?php endif; ?>

                    </div>

                </div>

                <!-- Empty Selection Message -->
                <div class="text-center p-xl bg-surface-container-low rounded-xl border-2 border-dashed border-outline-variant/30 mt-lg" id="empty-selection-msg" style="display: none;">

                    <span class="material-symbols-outlined text-4xl text-outline-variant mb-md">shopping_cart_checkout</span>
                    <p class="text-body-md text-on-surface-variant">Select items to view summary and proceed to checkout.</p>

                </div>

            </div>

        </form>

    <?php endif; ?>

<!-- Checkout Confirmation Modal -->
<div id="checkout-confirm-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden p-4">
    <div class="glass-card bg-surface rounded-2xl p-xl max-w-md w-full shadow-2xl border border-outline-variant/30 space-y-lg">
        <div class="flex items-center gap-md">
            <div id="modal-icon-bg" class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[28px]" id="modal-icon">storefront</span>
            </div>
            <div>
                <h3 class="text-title-lg font-bold text-on-surface" id="modal-title">Confirm Order</h3>
                <p class="text-xs text-on-surface-variant" id="modal-subtitle">Please review your order details</p>
            </div>
        </div>

        <div class="space-y-md bg-surface-container-low p-md rounded-xl border border-outline-variant/20 text-body-md">
            <!-- GCash Fulfillment Options -->
            <div id="modal-gcash-fulfillment" class="space-y-xs hidden">
                <span class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider block mb-1">Select Fulfillment</span>
                <div class="grid grid-cols-1 gap-sm">
                    <label class="flex items-center gap-md p-sm rounded-lg border border-primary bg-primary/5 cursor-pointer" id="label-opt-delivery">
                        <input type="radio" name="modal_fulfillment_choice" value="delivery" checked class="w-4 h-4 text-primary focus:ring-primary" id="modal-fchoice-delivery">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-on-surface flex items-center gap-1">
                                <span class="material-symbols-outlined text-[18px]">local_shipping</span> Doorstep Delivery
                            </span>
                            <span class="text-[11px] text-on-surface-variant" id="modal-desc-delivery">Delivered to your address (₱50.00 fee)</span>
                        </div>
                    </label>
                    <label class="flex items-center gap-md p-sm rounded-lg border border-outline-variant hover:bg-surface-container cursor-pointer" id="label-opt-pickup">
                        <input type="radio" name="modal_fulfillment_choice" value="pickup" class="w-4 h-4 text-primary focus:ring-primary" id="modal-fchoice-pickup">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-on-surface flex items-center gap-1">
                                <span class="material-symbols-outlined text-[18px]">storefront</span> Store Pick-up
                            </span>
                            <span class="text-[11px] text-on-surface-variant" id="modal-desc-pickup">Pick up at shop branch (Free / ₱0.00)</span>
                        </div>
                    </label>
                </div>
            </div>

            <p class="text-on-surface font-medium" id="modal-description"></p>
            <div id="modal-shop-info" class="text-xs text-on-surface-variant border-t border-outline-variant/20 pt-xs hidden">
                <span class="font-bold text-on-surface">Pick-up Location / Shop:</span>
                <span id="modal-shop-name" class="font-medium text-primary block mt-0.5"></span>
            </div>
            <div class="flex justify-between items-center text-body-md font-bold text-on-surface border-t border-outline-variant/20 pt-xs">
                <span>Order Total:</span>
                <span class="text-primary text-title-lg" id="modal-order-total"></span>
            </div>
        </div>

        <div class="flex items-center justify-end gap-md">
            <button type="button" id="modal-cancel-btn" class="px-lg py-md rounded-xl font-button text-on-surface-variant hover:bg-surface-container transition-colors">
                Cancel
            </button>
            <button type="button" id="modal-confirm-btn" class="px-xl py-md bg-primary text-on-primary rounded-xl font-button hover:bg-primary/90 transition-all shadow-md active:scale-95 flex items-center gap-xs">
                <span id="modal-confirm-text">Confirm Order</span>
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </button>
        </div>
    </div>
</div>

</main>

<!-- Floating Go Back -->
<button type="button" onclick="window.history.back()" aria-label="Go Back" class="hidden md:flex fixed bottom-8 left-8 w-16 h-16 bg-surface-container-lowest text-primary rounded-full shadow-lg items-center justify-center z-50 transition-transform border border-outline-variant duration-300 hover:scale-105 hover:shadow-xl">

    <span class="material-symbols-outlined text-[32px]">arrow_back</span>

</button>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>
(function () {
    var itemCheckboxes   = document.querySelectorAll('.cart-item-checkbox');
    var selectAll        = document.getElementById('select-all');
    var checkoutSidebar  = document.getElementById('checkout-sidebar');
    var emptyMsg         = document.getElementById('empty-selection-msg');
    var paymentRadios    = document.querySelectorAll('input[name="payment_method"]');
    var placeOrderBtn    = document.getElementById('place-order-btn');
    var deleteSelectedBtn = document.getElementById('delete-selected');

    var shipping         = <?= json_encode($shipping) ?>;
    var csrfName         = <?= json_encode($csrfName) ?>;
    var csrfHash         = <?= json_encode($csrfHash) ?>;
    var updateUrl        = <?= json_encode(base_url('cart/update')) ?>;
    var removeSelectedUrl = <?= json_encode(base_url('cart/remove-selected')) ?>;

    function money(n) {
        return '\u20B1' + n.toFixed(2);
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

        var noticeEl   = document.getElementById('cart-service-notice');
        var noticeText = document.getElementById('cart-service-notice-text');

        // Handle Delivery (COD)
        if (payCod && labelCod) {
            if (anySelected && !canDeliver) {
                payCod.disabled = true;
                if (payCod.checked) payCod.checked = false;
                labelCod.className = 'flex items-center gap-md p-md bg-surface-container-low opacity-40 cursor-not-allowed pointer-events-none rounded-lg border border-outline-variant/30';
                if (subtextCod) subtextCod.textContent = 'Kasalukuyang walang doorstep delivery';
            } else {
                payCod.disabled = false;
                labelCod.className = 'flex items-center gap-md p-md bg-surface-container-low hover:bg-surface-container transition-all rounded-lg border border-outline-variant/30 cursor-pointer group';
                if (subtextCod) subtextCod.textContent = 'Pay at doorstep';
            }
        }

        // Handle Store Pick-up
        if (payPickup && labelPickup) {
            if (anySelected && !canPickup) {
                payPickup.disabled = true;
                if (payPickup.checked) payPickup.checked = false;
                labelPickup.className = 'flex items-center gap-md p-md bg-surface-container-low opacity-40 cursor-not-allowed pointer-events-none rounded-lg border border-outline-variant/30';
                if (subtextPickup) subtextPickup.textContent = 'Kasalukuyang walang store pick-up';
            } else {
                payPickup.disabled = false;
                labelPickup.className = 'flex items-center gap-md p-md bg-surface-container-low hover:bg-surface-container transition-all rounded-lg border border-outline-variant/30 cursor-pointer group';
                if (subtextPickup) subtextPickup.textContent = 'Pick up at the shop branch';
            }
        }

        // Service Notice Banner
        if (noticeEl && noticeText) {
            if (anySelected && (!canDeliver || !canPickup)) {
                noticeEl.classList.remove('hidden');
                if (!canDeliver && !canPickup) {
                    noticeText.textContent = 'Paalala: Walang delivery at pick-up service na available sa tindahang ito ngayon.';
                } else if (!canDeliver) {
                    noticeText.textContent = 'Paalala: Walang Doorstep Delivery service ang tindahang ito. Store Pick-up lamang ang maaaring piliin.';
                } else if (!canPickup) {
                    noticeText.textContent = 'Paalala: Walang Store Pick-up service ang tindahang ito. Doorstep Delivery lamang ang maaaring piliin.';
                }
            } else {
                noticeEl.classList.add('hidden');
            }
        }

        // Modal GCash choices enable/disable
        var modalChoiceDelivery = document.getElementById('modal-fchoice-delivery');
        var modalChoicePickup   = document.getElementById('modal-fchoice-pickup');
        var labelModalDel       = document.getElementById('label-opt-delivery');
        var labelModalPick      = document.getElementById('label-opt-pickup');
        var descModalDel        = document.getElementById('modal-desc-delivery');
        var descModalPick       = document.getElementById('modal-desc-pickup');

        if (modalChoiceDelivery && labelModalDel) {
            if (anySelected && !canDeliver) {
                modalChoiceDelivery.disabled = true;
                labelModalDel.className = 'flex items-center gap-md p-sm rounded-lg border border-outline-variant/30 opacity-40 cursor-not-allowed pointer-events-none';
                if (descModalDel) descModalDel.textContent = 'Hindi available sa shop na ito';
            } else {
                modalChoiceDelivery.disabled = false;
                labelModalDel.className = 'flex items-center gap-md p-sm rounded-lg border border-primary bg-primary/5 cursor-pointer';
                if (descModalDel) descModalDel.textContent = 'Delivered to your address (₱50.00 fee)';
            }
        }

        if (modalChoicePickup && labelModalPick) {
            if (anySelected && !canPickup) {
                modalChoicePickup.disabled = true;
                labelModalPick.className = 'flex items-center gap-md p-sm rounded-lg border border-outline-variant/30 opacity-40 cursor-not-allowed pointer-events-none';
                if (descModalPick) descModalPick.textContent = 'Hindi available sa shop na ito';
            } else {
                modalChoicePickup.disabled = false;
                labelModalPick.className = 'flex items-center gap-md p-sm rounded-lg border border-outline-variant hover:bg-surface-container cursor-pointer';
                if (descModalPick) descModalPick.textContent = 'Pick up at shop branch (Free / ₱0.00)';
            }
        }

        if (anySelected) {
            checkoutSidebar.classList.add('visible');
            if (emptyMsg) emptyMsg.style.display = 'none';

            var subtotal = 0;
            selected.forEach(function (cb) {
                subtotal += (parseFloat(cb.getAttribute('data-price')) || 0) * (parseInt(cb.getAttribute('data-qty'), 10) || 1);
            });

            var selectedPayment = Array.prototype.find.call(paymentRadios, function (r) { return r.checked && !r.disabled; });
            var currentShipping = (selectedPayment && selectedPayment.value === 'pickup') ? 0 : shipping;

            document.getElementById('subtotal-val').innerText = money(subtotal);
            document.getElementById('total-val').innerText = money(subtotal + currentShipping);
        } else {
            checkoutSidebar.classList.remove('visible');
            if (emptyMsg) emptyMsg.style.display = 'block';
        }

        if (selectAll) {
            selectAll.checked = itemCheckboxes.length > 0 && Array.prototype.every.call(itemCheckboxes, function (cb) { return cb.checked; });
        }

        validateCheckout();
    }

    function validateCheckout() {
        var hasPayment = Array.prototype.some.call(paymentRadios, function (r) { return r.checked && !r.disabled; });
        var hasItems   = Array.prototype.some.call(itemCheckboxes, function (cb) { return cb.checked; });
        placeOrderBtn.disabled = !(hasPayment && hasItems);
    }

    if (selectAll) {
        selectAll.addEventListener('change', function (e) {
            itemCheckboxes.forEach(function (cb) { cb.checked = e.target.checked; });
            updateUI();
        });
    }

    itemCheckboxes.forEach(function (cb) {
        cb.addEventListener('change', updateUI);
    });

    paymentRadios.forEach(function (radio) {
        radio.addEventListener('change', updateUI);
    });

    var checkoutForm = placeOrderBtn ? placeOrderBtn.closest('form') : null;
    var confirmModal = document.getElementById('checkout-confirm-modal');
    var modalTitle = document.getElementById('modal-title');
    var modalDesc = document.getElementById('modal-description');
    var modalIcon = document.getElementById('modal-icon');
    var modalShopInfo = document.getElementById('modal-shop-info');
    var modalShopName = document.getElementById('modal-shop-name');
    var modalTotal = document.getElementById('modal-order-total');
    var modalCancelBtn = document.getElementById('modal-cancel-btn');
    var modalConfirmBtn = document.getElementById('modal-confirm-btn');
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

    var shippingFee      = shipping;
    function formatMoney(n) {
        return money(n);
    }

    // Modal fulfillment radio change listener
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

            var labelDel = document.getElementById('label-opt-delivery');
            var labelPick = document.getElementById('label-opt-pickup');

            if (this.value === 'pickup') {
                if (labelPick) { labelPick.className = 'flex items-center gap-md p-sm rounded-lg border border-primary bg-primary/5 cursor-pointer'; }
                if (labelDel) { labelDel.className = 'flex items-center gap-md p-sm rounded-lg border border-outline-variant hover:bg-surface-container cursor-pointer'; }
                if (modalTotal) modalTotal.textContent = money(subtotal);
                var shops = getSelectedShops();
                if (shops.length > 0 && modalShopName && modalShopInfo) {
                    modalShopName.textContent = shops.join(', ');
                    modalShopInfo.classList.remove('hidden');
                }
            } else {
                if (labelDel) { labelDel.className = 'flex items-center gap-md p-sm rounded-lg border border-primary bg-primary/5 cursor-pointer'; }
                if (labelPick) { labelPick.className = 'flex items-center gap-md p-sm rounded-lg border border-outline-variant hover:bg-surface-container cursor-pointer'; }
                if (modalTotal) modalTotal.textContent = money(subtotal + shipping);
                if (modalShopInfo) modalShopInfo.classList.add('hidden');
            }
        });
    });

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (e) {
            if (isConfirmedSubmit) return;

            var selectedPayment = Array.prototype.find.call(paymentRadios, function (r) { return r.checked; });
            var paymentVal = selectedPayment ? selectedPayment.value : '';

            var subtotal = 0;
            itemCheckboxes.forEach(function (cb) {
                if (cb.checked) {
                    var price = parseFloat(cb.getAttribute('data-price')) || 0;
                    var qty   = parseInt(cb.getAttribute('data-qty'), 10) || 1;
                    subtotal += price * qty;
                }
            });

            var inlineError = document.getElementById('checkout-inline-error');
            var inlineErrorText = document.getElementById('checkout-error-text');
            function showInlineError(msg) {
                if (inlineError && inlineErrorText) {
                    inlineErrorText.textContent = msg;
                    inlineError.classList.remove('hidden');
                }
            }
            if (inlineError) inlineError.classList.add('hidden');

            if (!paymentVal) {
                e.preventDefault();
                showInlineError('Please select a payment method before proceeding.');
                return;
            }

            if (paymentVal === 'gcash') {
                e.preventDefault();
                modalTitle.textContent = 'GCash Fulfillment Selection';
                modalIcon.textContent = 'account_balance_wallet';
                modalDesc.textContent = 'Please choose how your order will be fulfilled before proceeding to GCash payment:';
                if (modalConfirmText) modalConfirmText.textContent = 'Proceed to PayMongo';
                if (modalGcashFulfillment) modalGcashFulfillment.classList.remove('hidden');

                // Determine currently checked modal fulfillment choice (non-disabled)
                var chosenFulfillment = document.querySelector('input[name="modal_fulfillment_choice"]:checked:not(:disabled)') 
                    || document.querySelector('input[name="modal_fulfillment_choice"]:not(:disabled)');
                if (chosenFulfillment) {
                    chosenFulfillment.checked = true;
                }
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
                } else {
                    if (modalShopInfo) modalShopInfo.classList.add('hidden');
                }

                if (modalTotal) modalTotal.textContent = money(subtotal);
                if (confirmModal) confirmModal.classList.remove('hidden');

            } else if (paymentVal === 'cod') {
                e.preventDefault();
                if (formFulfillmentMethod) formFulfillmentMethod.value = 'delivery';
                modalTitle.textContent = 'Confirm Cash on Delivery Order';
                modalIcon.textContent = 'local_shipping';
                modalDesc.textContent = 'Payment will be collected upon delivery to your address.';
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
            if (e.target === confirmModal) {
                confirmModal.classList.add('hidden');
            }
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
            postForm(removeSelectedUrl, { ids: ids }).then(function () {
                window.location.reload();
            });
        });
    }

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

    updateUI();
})();
</script>

<?= $this->endSection() ?>