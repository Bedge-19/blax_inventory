<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<div class="flex flex-1 max-w-container-max mx-auto w-full">

    <?= view('components/profile_sidebar', ['activeNav' => 'orders']) ?>

    <!-- Main Content Area -->
    <main class="flex-1 p-md md:p-xl overflow-y-auto">

        <header class="mb-xl">

            <h1 class="text-headline-lg font-headline-lg mb-base">My Orders</h1>

            <?php if (!empty($orders)): ?>

                <!-- Tabs -->
                <div class="flex border-b border-outline-variant/30 gap-lg overflow-x-auto pb-px">

                    <button type="button" class="px-base py-md font-label-sm text-label-sm text-primary border-b-2 border-primary whitespace-nowrap" data-filter="all">All Orders</button>
                    <button type="button" class="px-base py-md font-label-sm text-label-sm text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap" data-filter="active">Active</button>
                    <button type="button" class="px-base py-md font-label-sm text-label-sm text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap" data-filter="completed">Completed</button>
                    <button type="button" class="px-base py-md font-label-sm text-label-sm text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap" data-filter="cancelled">Cancelled</button>

                </div>

            <?php endif; ?>

        </header>

        <?php if (!empty($orders)): ?>

            <!-- Order List -->
            <div class="flex flex-col gap-lg">

                <?php
                    $statusIcons = [
                        'pending'         => 'schedule',
                        'processing'      => 'schedule',
                        'shipped'         => 'local_shipping',
                        'ready_for_pickup'=> 'store',
                        'delivered'       => 'task_alt',
                        'completed'       => 'task_alt',
                        'cancelled'       => 'cancel',
                    ];
                    $orderGroup = [
                        'pending'         => 'active',
                        'processing'      => 'active',
                        'shipped'         => 'active',
                        'ready_for_pickup'=> 'active',
                        'delivered'       => 'completed',
                        'completed'       => 'completed',
                        'cancelled'       => 'cancelled',
                    ];
                ?>

                <?php foreach ($orders as $order): ?>

                    <?php
                        $orderStatus = strtolower((string) ($order['status'] ?? 'pending'));
                        $statusIcon  = $statusIcons[$orderStatus] ?? 'receipt_long';
                        $group       = $orderGroup[$orderStatus] ?? 'active';

                        $orderedOn = date('M d, Y', strtotime($order['placed_at'] ?? $order['created_at'] ?? 'now'));

                        $thumbnails = array_slice($order['items'] ?? [], 0, 2);
                        $extraItems = max(0, count($order['items'] ?? []) - 2);
                    ?>

                    <div class="glass-card rounded-xl p-md md:p-lg flex flex-col md:flex-row gap-lg hover:shadow-md transition-all group" data-group="<?= esc($group) ?>" data-status="<?= esc($orderStatus) ?>">

                        <div class="flex-1">

                            <div class="flex justify-between items-start mb-md">

                                <div>

                                    <h3 class="text-title-lg font-title-lg mb-xs">#<?= esc($order['order_number'] ?? ('ORD-' . $order['id'])) ?></h3>

                                    <div class="flex items-center gap-sm flex-wrap">

                                        <span class="flex items-center gap-xs bg-tertiary-container/10 text-tertiary-container px-sm py-xs rounded-full text-label-sm font-label-sm">

                                            <span class="material-symbols-outlined text-[14px]"><?= $statusIcon ?></span>
                                            <?= humanize_status($orderStatus) ?>

                                        </span>

                                        <span class="text-on-surface-variant text-label-sm">Ordered on <?= esc($orderedOn) ?></span>

                                    </div>

                                </div>

                                <span class="text-title-lg font-title-lg text-primary font-bold">₱<?= number_format((float) $order['total_amount'], 2) ?></span>

                            </div>

                            <div class="flex items-center gap-md mb-md">

                                <?php foreach ($thumbnails as $item): ?>

                                    <div class="relative w-16 h-16 rounded-lg overflow-hidden border border-outline-variant/30 bg-surface-container-low flex items-center justify-center">

                                        <?php if (!empty($item['image_url'])): ?>

                                            <?php
                                                $itemImg = $item['image_url'];
                                                if (!str_starts_with($itemImg, 'http://') && !str_starts_with($itemImg, 'https://')) {
                                                    $itemImg = base_url($itemImg);
                                                }
                                            ?>
                                            <img class="w-full h-full object-cover" src="<?= esc($itemImg) ?>" alt="<?= esc($item['product_name'] ?? 'Product') ?>">

                                        <?php else: ?>

                                            <span class="material-symbols-outlined text-outline">inventory_2</span>

                                        <?php endif; ?>

                                    </div>

                                <?php endforeach; ?>

                                <?php if ($extraItems > 0): ?>

                                    <span class="text-on-surface-variant text-label-sm font-label-sm bg-surface-container rounded-full w-8 h-8 flex items-center justify-center">+<?= $extraItems ?></span>

                                <?php endif; ?>

                            </div>

                            <?php if ($orderStatus === 'cancelled'): ?>

                                <div class="bg-surface-container-low rounded-lg p-md flex items-center gap-md">

                                    <span class="material-symbols-outlined text-error">cancel</span>

                                    <div>
                                        <p class="text-label-sm font-label-sm text-on-surface-variant">Cancelled on</p>
                                        <p class="text-body-md font-body-md font-semibold"><?= !empty($order['cancelled_at']) ? date('M d, Y • h:i A', strtotime($order['cancelled_at'])) : '—' ?></p>
                                    </div>

                                </div>

                            <?php elseif ($orderStatus === 'delivered' || $orderStatus === 'completed'): ?>

                                <div class="bg-surface-container-low rounded-lg p-md flex items-center gap-md">

                                    <span class="material-symbols-outlined text-on-surface-variant">check_circle</span>

                                    <div>
                                        <p class="text-label-sm font-label-sm text-on-surface-variant">Delivered on</p>
                                        <p class="text-body-md font-body-md font-semibold"><?= !empty($order['completed_at']) ? date('M d, Y • h:i A', strtotime($order['completed_at'])) : '—' ?></p>
                                    </div>

                                </div>

                            <?php elseif (($order['fulfillment_method'] ?? 'delivery') === 'pickup'): ?>

                                <div class="bg-surface-container-low rounded-lg p-md flex items-center gap-md">

                                    <span class="material-symbols-outlined text-primary">store</span>

                                    <div>
                                        <p class="text-label-sm font-label-sm text-on-surface-variant">Pickup at</p>
                                        <p class="text-body-md font-body-md font-semibold"><?= esc($order['shop_name'] ?? 'RHK Merchant') ?></p>
                                    </div>

                                </div>

                            <?php else: ?>

                                <div class="bg-surface-container-low rounded-lg p-md flex items-center gap-md">

                                    <span class="material-symbols-outlined text-primary">local_shipping</span>

                                    <div>
                                        <p class="text-label-sm font-label-sm text-on-surface-variant">Estimated Delivery</p>
                                        <p class="text-body-md font-body-md font-semibold">Standard Delivery</p>
                                    </div>

                                </div>

                            <?php endif; ?>

                            <p class="text-xs text-on-surface-variant opacity-80 mt-md">Shop: <?= esc($order['shop_name'] ?? 'RHK Merchant') ?></p>

                        </div>

                        <div class="flex md:flex-col justify-end gap-sm md:w-48">

                            <?php if ($orderStatus !== 'cancelled'): ?>

                                <?php if ($orderStatus === 'delivered' || $orderStatus === 'completed'): ?>

                                    <button type="button" class="flex-1 md:flex-none bg-surface-container-highest text-on-surface py-sm px-md rounded-lg font-button text-button hover:bg-outline-variant transition-colors">Buy Again</button>

                                <?php elseif (($order['fulfillment_method'] ?? 'delivery') === 'pickup'): ?>

                                    <?php if ($orderStatus !== 'pending'): ?>
                                        <button type="button" 
                                                class="order-qr-btn flex-1 md:flex-none bg-primary text-on-primary py-sm px-md rounded-lg font-button text-button hover:bg-primary-container transition-all flex items-center justify-center gap-xs shadow-sm active:scale-95"
                                                data-number="<?= esc($order['order_number'] ?? ('ORD-' . $order['id'])) ?>"
                                                data-shop="<?= esc($order['shop_name'] ?? 'Shop') ?>"
                                                data-status="<?= esc(humanize_status($orderStatus)) ?>"
                                                data-date="<?= esc($orderedOn) ?>"
                                                data-paid="<?= ($order['payment_status'] ?? '') === 'paid' ? 'PAID' : 'UNPAID' ?>">
                                            <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                                            <span>Pick-up QR</span>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-xs text-on-surface-variant font-medium text-center py-sm px-md bg-surface-container rounded-lg">Awaiting Confirmation</span>
                                    <?php endif; ?>

                                <?php else: ?>

                                    <button type="button" class="flex-1 md:flex-none bg-primary text-on-primary py-sm px-md rounded-lg font-button text-button hover:bg-primary-container transition-colors">Track Order</button>

                                <?php endif; ?>

                            <?php endif; ?>

                            <button type="button" class="flex-1 md:flex-none border border-outline-variant text-on-surface py-sm px-md rounded-lg font-button text-button hover:bg-surface-container-high transition-colors">Order Details</button>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="glass-card rounded-xl p-xl text-center text-on-surface-variant">

                <span class="material-symbols-outlined text-4xl text-outline mb-2">shopping_bag</span>

                <p>No orders found yet.</p>

            </div>

        <?php endif; ?>

    </main>

</div>

<button aria-label="Go Back" onclick="window.history.back()" class="fixed bottom-lg left-lg w-16 h-16 bg-surface-container-highest text-on-surface rounded-full shadow-xl flex items-center justify-center z-50 hover:scale-105 transition-transform duration-200 border border-outline-variant">

    <span class="material-symbols-outlined text-[32px]">arrow_back</span>

</button>

<!-- Order Pick-up QR Modal -->
<div id="order-qr-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-md">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="order-qr-overlay"></div>
    <div class="relative bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-2xl w-full max-w-sm p-xl flex flex-col items-center gap-md text-center z-10">
        <div class="flex justify-between items-center w-full border-b border-outline-variant/20 pb-md">
            <div class="flex items-center gap-xs text-primary font-bold">
                <span class="material-symbols-outlined text-2xl">qr_code_2</span>
                <span class="text-title-md">Store Pick-up QR</span>
            </div>
            <button type="button" id="order-qr-close" class="p-1 rounded-full hover:bg-surface-container-high text-on-surface-variant transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <div class="flex flex-col items-center justify-center bg-white p-6 rounded-2xl shadow-inner border border-slate-100">
            <div id="order-qr-canvas" class="flex justify-center items-center min-w-[200px] min-h-[200px]"></div>
        </div>
        
        <div class="flex flex-col items-center w-full">
            <span class="text-[10px] uppercase tracking-widest text-outline font-bold">Order Number</span>
            <span id="order-qr-number" class="text-headline-sm font-mono font-bold text-primary mt-0.5">#ORD-00000</span>
            <div class="flex items-center justify-center gap-2 mt-1">
                <span id="order-qr-shop" class="text-xs font-semibold text-on-surface"></span>
                <span class="text-on-surface-variant/40">•</span>
                <span id="order-qr-payment" class="text-[11px] font-bold px-2 py-0.5 rounded-full"></span>
            </div>
            <p class="text-xs text-on-surface-variant/80 mt-3 leading-relaxed">Present this QR code to the store attendant upon pick-up.</p>
        </div>

        <button type="button" id="order-qr-done" class="w-full py-md bg-primary text-on-primary rounded-xl text-button font-button hover:bg-primary-container transition-all active:scale-95 shadow-md">Close</button>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<?php if (!empty($orders)): ?>
<script>
(function () {
    // Filter tabs
    var tabs  = document.querySelectorAll('[data-filter]');
    var cards = document.querySelectorAll('[data-group]');

    function applyFilter(filter) {
        cards.forEach(function (card) {
            var show = filter === 'all' || card.getAttribute('data-group') === filter;
            card.classList.toggle('hidden', !show);
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) {
                t.classList.remove('text-primary', 'border-b-2', 'border-primary');
                t.classList.add('text-on-surface-variant');
            });
            tab.classList.remove('text-on-surface-variant');
            tab.classList.add('text-primary', 'border-b-2', 'border-primary');
            applyFilter(tab.getAttribute('data-filter'));
        });
    });

    // QR Modal logic
    var qrModal   = document.getElementById('order-qr-modal');
    var qrOverlay = document.getElementById('order-qr-overlay');
    var qrClose   = document.getElementById('order-qr-close');
    var qrDone    = document.getElementById('order-qr-done');
    var qrCanvas  = document.getElementById('order-qr-canvas');
    var qrNumber  = document.getElementById('order-qr-number');
    var qrShop    = document.getElementById('order-qr-shop');
    var qrPayment = document.getElementById('order-qr-payment');

    function openQr(orderNum, shopName, isPaid) {
        if (!qrModal) return;
        qrNumber.textContent = '#' + orderNum;
        qrShop.textContent = shopName || 'Storefront';
        
        if (isPaid === 'PAID') {
            qrPayment.textContent = 'PAID ONLINE';
            qrPayment.className = 'text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800';
        } else {
            qrPayment.textContent = 'PAY AT COUNTER';
            qrPayment.className = 'text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-900';
        }

        qrCanvas.innerHTML = '';
        if (window.QRCode) {
            new QRCode(qrCanvas, {
                text: orderNum,
                width: 200,
                height: 200,
                colorDark: '#1e293b',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        } else {
            qrCanvas.innerHTML = '<p class="text-error text-xs font-mono">#' + orderNum + '</p>';
        }

        qrModal.classList.remove('hidden');
    }

    function closeQr() {
        if (qrModal) qrModal.classList.add('hidden');
    }

    document.querySelectorAll('.order-qr-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            openQr(btn.dataset.number, btn.dataset.shop, btn.dataset.paid);
        });
    });

    if (qrOverlay) qrOverlay.addEventListener('click', closeQr);
    if (qrClose) qrClose.addEventListener('click', closeQr);
    if (qrDone) qrDone.addEventListener('click', closeQr);
})();
</script>
<?php endif; ?>

<?= $this->endSection() ?>