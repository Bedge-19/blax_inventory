<?= $this->extend('layouts/tenant') ?>
<?= $this->section('content') ?>

<div class="flex-1 space-y-5 pb-8">

    <!-- Flash Alerts -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-3.5 px-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs sm:text-sm font-semibold flex items-center justify-between shadow-xs animate-fadeIn">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-emerald-600 text-[20px]">check_circle</span>
                <span><?= session()->getFlashdata('success') ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 p-1">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-3.5 px-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs sm:text-sm font-semibold flex items-center justify-between shadow-xs animate-fadeIn">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-rose-600 text-[20px]">error</span>
                <span><?= session()->getFlashdata('error') ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-700 hover:text-rose-900 p-1">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl sm:text-3xl font-black text-on-surface tracking-tight">Orders Management</h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-primary/10 text-primary">
                    Live
                </span>
            </div>
            <p class="text-xs sm:text-sm text-on-surface-variant font-medium mt-0.5">
                Monitor incoming customer orders, manage packing queues, and track fulfillment status
            </p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="<?= base_url('tenant/pos') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-secondary text-on-secondary rounded-xl text-xs sm:text-sm font-bold hover:bg-secondary/90 shadow-xs hover:shadow-sm active:scale-95 transition-all">
                <span class="material-symbols-outlined text-[18px]">point_of_sale</span>
                <span>Open POS</span>
            </a>
            <a href="<?= base_url('tenant/orders') ?>" class="p-2.5 bg-surface-container-lowest hover:bg-surface-container-high border border-outline-variant/40 rounded-xl text-on-surface-variant hover:text-on-surface transition-colors shadow-2xs" title="Refresh Orders List">
                <span class="material-symbols-outlined text-[18px]">refresh</span>
            </a>
        </div>
    </div>

    <!-- Modernized Interactive Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- Card 1: Total Orders -->
        <a href="<?= base_url('tenant/orders') ?>" class="group bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/30 shadow-xs hover:shadow-md hover:border-primary/40 transition-all flex items-center justify-between cursor-pointer">
            <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <span class="text-xs text-on-surface-variant font-semibold uppercase tracking-wider">Total Orders</span>
                    <span class="opacity-0 group-hover:opacity-100 text-primary text-[10px] font-bold transition-opacity">All</span>
                </div>
                <p class="text-2xl sm:text-3xl font-bold font-mono text-on-surface mt-1 group-hover:text-primary transition-colors">
                    <?= number_format((int) $summary['total_orders']) ?>
                </p>
                <span class="text-[11px] text-outline mt-0.5 block truncate">Store lifetime active orders</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0 shadow-2xs group-hover:scale-105 group-hover:bg-primary group-hover:text-on-primary transition-all">
                <span class="material-symbols-outlined text-[24px]">shopping_cart</span>
            </div>
        </a>

        <!-- Card 2: Pending Shipments -->
        <a href="<?= base_url('tenant/orders?status=shipped') ?>" class="group bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/30 shadow-xs hover:shadow-md hover:border-blue-400 transition-all flex items-center justify-between cursor-pointer">
            <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <span class="text-xs text-on-surface-variant font-semibold uppercase tracking-wider">In Transit / Deliveries</span>
                    <span class="opacity-0 group-hover:opacity-100 text-blue-600 text-[10px] font-bold transition-opacity">Filter</span>
                </div>
                <p class="text-2xl sm:text-3xl font-bold font-mono text-blue-700 mt-1">
                    <?= number_format((int) $summary['pending_shipments']) ?>
                </p>
                <span class="text-[11px] text-outline mt-0.5 block truncate">Currently dispatched for delivery</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-700 flex items-center justify-center shrink-0 shadow-2xs group-hover:scale-105 group-hover:bg-blue-600 group-hover:text-white transition-all">
                <span class="material-symbols-outlined text-[24px]">local_shipping</span>
            </div>
        </a>

        <!-- Card 3: Ready for Pickup -->
        <a href="<?= base_url('tenant/orders?status=ready_for_pickup') ?>" class="group bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/30 shadow-xs hover:shadow-md hover:border-amber-400 transition-all flex items-center justify-between cursor-pointer">
            <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <span class="text-xs text-on-surface-variant font-semibold uppercase tracking-wider">Ready for Pick-up</span>
                    <span class="opacity-0 group-hover:opacity-100 text-amber-600 text-[10px] font-bold transition-opacity">Filter</span>
                </div>
                <p class="text-2xl sm:text-3xl font-bold font-mono text-amber-700 mt-1">
                    <?= number_format((int) $summary['ready_for_pickup']) ?>
                </p>
                <span class="text-[11px] text-outline mt-0.5 block truncate">Waiting for customer handover</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-700 flex items-center justify-center shrink-0 shadow-2xs group-hover:scale-105 group-hover:bg-amber-600 group-hover:text-white transition-all">
                <span class="material-symbols-outlined text-[24px]">storefront</span>
            </div>
        </a>

        <!-- Card 4: Revenue Today -->
        <div class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/30 shadow-xs hover:shadow-md transition-all flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-xs text-on-surface-variant font-semibold uppercase tracking-wider block">Revenue Today</span>
                <p class="text-2xl sm:text-3xl font-bold font-mono text-emerald-700 mt-1">
                    ₱<?= number_format((float) $summary['revenue_today'], 2) ?>
                </p>
                <span class="text-[11px] text-outline mt-0.5 block truncate">Completed orders &amp; pickups</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0 shadow-2xs">
                <span class="material-symbols-outlined text-[24px]">payments</span>
            </div>
        </div>
    </div>

    <!-- Active Processing & Packing Queue Container -->
    <?php
    $procCount = count($processingOrders ?? []);
    $pickupProcCount = 0;
    $deliveryProcCount = 0;
    if (!empty($processingOrders)) {
        foreach ($processingOrders as $po) {
            if (($po['fulfillment_method'] ?? 'delivery') === 'pickup') {
                $pickupProcCount++;
            } else {
                $deliveryProcCount++;
            }
        }
    }
    ?>
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
        
        <!-- Queue Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-outline-variant/20 pb-3.5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-800 flex items-center justify-center shrink-0 shadow-2xs">
                    <span class="material-symbols-outlined text-[22px]">inventory_2</span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base sm:text-lg font-bold text-on-surface">Active Processing &amp; Packing Queue</h2>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold font-mono <?= $procCount > 0 ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-surface-container text-outline' ?>">
                            <?= $procCount ?> <?= $procCount === 1 ? 'order' : 'orders' ?> in prep
                        </span>
                    </div>
                    <p class="text-xs text-on-surface-variant font-medium">Orders accepted by your shop. Pack items and click the button when ready.</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Sub-filters -->
                <?php if ($procCount > 0): ?>
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 custom-scrollbar text-xs">
                        <button type="button" onclick="filterProcessingQueue('all')" id="proc-tab-all" class="proc-filter-tab px-3 py-1.5 rounded-full font-bold bg-primary text-on-primary shadow-2xs transition-all cursor-pointer">
                            All (<?= $procCount ?>)
                        </button>
                        <button type="button" onclick="filterProcessingQueue('pickup')" id="proc-tab-pickup" class="proc-filter-tab px-3 py-1.5 rounded-full font-medium bg-surface-container-low text-on-surface-variant hover:bg-surface-container border border-outline-variant/30 transition-all cursor-pointer">
                            Store Pick-up (<?= $pickupProcCount ?>)
                        </button>
                        <button type="button" onclick="filterProcessingQueue('delivery')" id="proc-tab-delivery" class="proc-filter-tab px-3 py-1.5 rounded-full font-medium bg-surface-container-low text-on-surface-variant hover:bg-surface-container border border-outline-variant/30 transition-all cursor-pointer">
                            Doorstep Delivery (<?= $deliveryProcCount ?>)
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Toggle Hide/Show Button -->
                <button type="button" id="toggleQueueBtn" onclick="toggleProcessingQueue()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-container-low hover:bg-surface-container-high border border-outline-variant/30 text-on-surface-variant hover:text-on-surface text-xs font-semibold transition-all shadow-2xs active:scale-95 shrink-0 cursor-pointer" title="Hide/Show Queue">
                    <span class="material-symbols-outlined text-[18px] transition-transform duration-300" id="toggleQueueIcon">expand_less</span>
                    <span id="toggleQueueLabel" class="hidden sm:inline">Hide</span>
                </button>
            </div>
        </div>

        <!-- Collapsible Queue Content -->
        <div id="queueContent" class="transition-all duration-300 overflow-hidden">
        <?php if (!empty($processingOrders)): ?>
            <div id="processingQueueGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5">
                <?php foreach ($processingOrders as $pOrd): ?>
                    <?php
                    $isPickup = ($pOrd['fulfillment_method'] ?? 'delivery') === 'pickup';
                    $custName = trim(($pOrd['first_name'] ?? '') . ' ' . ($pOrd['last_name'] ?? ''));
                    $custInitials = $custName !== '' ? mb_strtoupper(mb_substr($custName, 0, 2)) : 'GU';
                    $custProfileImg = trim((string) ($pOrd['profile_image_url'] ?? ''));
                    $custPhone = esc($pOrd['customer_phone'] ?? 'N/A');
                    $deliveryAddr = trim(($pOrd['address_line1'] ?? '') . ', ' . ($pOrd['city'] ?? '')) ?: 'Polomolok, South Cotabato';
                    $orderItemsList = $pOrd['items'] ?? [];
                    $totalUnits = array_sum(array_column($orderItemsList, 'quantity'));
                    $isPaid = ($pOrd['payment_status'] ?? '') === 'paid';
                    $shopNameStr = esc($shop['shop_name'] ?? 'Blax Storefront');
                    ?>
                    <div class="processing-order-card bg-surface-container-low/40 hover:bg-surface-container-low border border-outline-variant/30 rounded-2xl p-4 flex flex-col justify-between gap-3 shadow-2xs hover:shadow-sm transition-all" data-fulfillment="<?= $isPickup ? 'pickup' : 'delivery' ?>">
                        
                        <!-- Card Top: Order No, Elapsed Time & Fulfillment Pill -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-sm font-bold text-primary">#<?= esc($pOrd['order_number']) ?></span>
                                <?php if ($isPickup): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/15 text-amber-900 border border-amber-500/30 shadow-2xs">
                                        <span class="material-symbols-outlined text-[14px]">storefront</span>
                                        <span>Store Pick-up</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-500/15 text-blue-900 border border-blue-500/30 shadow-2xs">
                                        <span class="material-symbols-outlined text-[14px]">local_shipping</span>
                                        <span>Doorstep Delivery</span>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center justify-between text-[11px] text-outline">
                                <span>Placed: <?= esc(date('M d, h:i A', strtotime($pOrd['placed_at'] ?? 'now'))) ?></span>
                                <?php if ($pOrd['status'] === 'pending'): ?>
                                    <span class="font-mono font-semibold text-amber-800 bg-amber-100 px-1.5 py-0.5 rounded">New Pending</span>
                                <?php else: ?>
                                    <span class="font-mono font-semibold text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded">In Prep</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Customer & Destination Info -->
                        <div class="p-3 bg-surface-container-lowest rounded-xl border border-outline-variant/20 space-y-2 text-xs">
                            <div class="flex items-center justify-between gap-2.5">
                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                    <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-800 flex items-center justify-center text-xs font-bold shrink-0 shadow-2xs relative overflow-hidden">
                                        <span><?= esc($custInitials) ?></span>
                                        <?php if ($custProfileImg !== ''): ?>
                                            <img class="absolute inset-0 w-full h-full object-cover rounded-full" src="<?= esc(base_url($custProfileImg)) ?>" alt="<?= esc($custName) ?> avatar" loading="lazy" onerror="this.remove();">
                                        <?php endif; ?>
                                    </div>
                                    <span class="font-bold text-sm text-on-surface truncate"><?= esc($custName !== '' ? $custName : 'Customer') ?></span>
                                </div>
                                <?php if ($custPhone !== 'N/A' && $custPhone !== ''): ?>
                                    <a href="tel:<?= $custPhone ?>" class="text-xs font-mono font-semibold text-blue-600 hover:text-blue-700 hover:underline flex items-center gap-1 shrink-0">
                                        <span class="material-symbols-outlined text-[15px] text-blue-600">call</span>
                                        <span><?= $custPhone ?></span>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center gap-1.5 text-xs text-on-surface-variant pt-2 border-t border-outline-variant/15">
                                <span class="material-symbols-outlined text-[16px] text-outline shrink-0">
                                    <?= $isPickup ? 'storefront' : 'local_shipping' ?>
                                </span>
                                <span class="truncate leading-tight font-medium">
                                    <?= $isPickup ? 'Store Counter Pick-up' : esc($deliveryAddr) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Items to Pack Checklist -->
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-[11px] font-bold text-on-surface-variant px-0.5">
                                <span class="uppercase tracking-wider">Items to Pack</span>
                                <span class="font-mono text-outline"><?= (int) $totalUnits ?> units</span>
                            </div>
                            <div class="bg-surface-container-lowest rounded-xl p-2 border border-outline-variant/20 space-y-1.5 max-h-36 overflow-y-auto custom-scrollbar">
                                <?php if (!empty($orderItemsList)): ?>
                                    <?php foreach ($orderItemsList as $it): ?>
                                        <div class="flex items-center justify-between gap-2 text-xs">
                                             <div class="flex items-center gap-2 min-w-0 flex-1">
                                                <?php if (!empty($it['product_image'])): ?>
                                                    <img src="<?= esc(base_url($it['product_image'])) ?>" class="w-7 h-7 rounded-lg object-cover border border-outline-variant/20 shrink-0" alt="">
                                                <?php else: ?>
                                                    <span class="w-7 h-7 rounded-lg bg-surface-container flex items-center justify-center shrink-0 text-outline text-[14px] material-symbols-outlined">inventory_2</span>
                                                <?php endif; ?>
                                                <div class="min-w-0 flex-1">
                                                    <p class="font-semibold text-on-surface truncate leading-tight"><?= esc($it['product_name']) ?></p>
                                                    <p class="text-[10px] text-outline font-mono">₱<?= number_format((float) $it['unit_price'], 2) ?></p>
                                                </div>
                                            </div>
                                            <span class="font-mono font-bold text-xs bg-surface-container px-2 py-0.5 rounded text-on-surface shrink-0">
                                                <?= (int) $it['quantity'] ?>x
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-[11px] text-outline text-center py-2">Item details loaded from order.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Financial Summary Pill -->
                        <div class="flex items-center justify-between pt-1 text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="text-on-surface-variant font-medium">Total:</span>
                                <span class="font-mono font-bold text-sm text-on-surface">₱<?= number_format((float) $pOrd['total_amount'], 2) ?></span>
                            </div>
                            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold <?= $isPaid ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' ?>">
                                <span class="material-symbols-outlined text-[12px]"><?= $isPaid ? 'verified' : 'pending' ?></span>
                                <?= $isPaid ? 'Paid Online' : ($isPickup ? 'Pay on Pick-up' : 'Cash on Delivery') ?>
                            </span>
                        </div>

                        <!-- Action Buttons: Context-Aware Button + Utilities -->
                        <div class="pt-2 border-t border-outline-variant/20 flex items-center gap-1.5">
                            <?php if ($pOrd['status'] === 'pending'): ?>
                                <!-- Pending Order: Button is 'Accept & Start Prep' -->
                                <form action="<?= base_url('tenant/orders/update-status') ?>" method="POST" class="flex-1">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="order_id" value="<?= (int) $pOrd['id'] ?>">
                                    <input type="hidden" name="status" value="processing">
                                    <button type="submit" class="w-full min-h-[42px] py-2 px-3 bg-primary hover:bg-primary/90 active:scale-95 text-on-primary rounded-xl text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-1.5 cursor-pointer" title="Accept order and start packaging">
                                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                        <span>Accept &amp; Pack</span>
                                    </button>
                                </form>
                            <?php elseif ($isPickup): ?>
                                <!-- Store Pick-up: Button is 'Ready for Pick-up' -->
                                <form action="<?= base_url('tenant/orders/update-status') ?>" method="POST" class="flex-1">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="order_id" value="<?= (int) $pOrd['id'] ?>">
                                    <input type="hidden" name="status" value="ready_for_pickup">
                                    <button type="submit" class="w-full min-h-[42px] py-2 px-3 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-1.5 cursor-pointer" title="Mark packed and ready for customer pick-up">
                                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                        <span>Ready for Pick-up</span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <!-- Doorstep Delivery: Button is 'In Transit' -->
                                <form action="<?= base_url('tenant/orders/update-status') ?>" method="POST" class="flex-1" onsubmit="return confirm('Dispatch Order #<?= esc($pOrd['order_number']) ?> as In Transit for delivery?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="order_id" value="<?= (int) $pOrd['id'] ?>">
                                    <input type="hidden" name="status" value="shipped">
                                    <button type="submit" class="w-full min-h-[42px] py-2 px-3 bg-primary hover:bg-primary/90 active:scale-95 text-on-primary rounded-xl text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-1.5 cursor-pointer" title="Dispatch order as In Transit and hand over to delivery">
                                        <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                                        <span>In Transit</span>
                                    </button>
                                </form>
                            <?php endif; ?>

                            <!-- Secondary Utility Buttons -->
                            <button type="button"
                                    onclick="openOrderQrModal(
                                        '<?= esc($pOrd['order_number']) ?>',
                                        '<?= $shopNameStr ?>',
                                        '<?= esc($custName !== '' ? $custName : 'Customer') ?>',
                                        '<?= esc($deliveryAddr) ?>',
                                        '<?= esc($pOrd['address_label'] ?? 'Home') ?>',
                                        '<?= esc($custPhone) ?>',
                                        '<?= esc(date('M d, Y h:i A', strtotime($pOrd['placed_at']))) ?>',
                                        '<?= esc(base_url('order/' . $pOrd['order_number'])) ?>'
                                    )"
                                    class="w-9 h-[42px] bg-surface-container-lowest hover:bg-surface-container-high border border-outline-variant/30 rounded-xl text-primary flex items-center justify-center shrink-0 transition-colors shadow-2xs cursor-pointer"
                                    title="Generate & Download QR Code">
                                <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                            </button>

                            <a href="<?= base_url('tenant/orders/receipt/' . (int) $pOrd['id']) ?>" target="_blank" class="w-9 h-[42px] bg-surface-container-lowest hover:bg-surface-container-high border border-outline-variant/30 rounded-xl text-on-surface-variant flex items-center justify-center shrink-0 transition-colors shadow-2xs" title="Print Packing Slip / Receipt">
                                <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                            </a>

                            <button type="button" onclick="openOrderDetails(<?= (int) $pOrd['id'] ?>)" class="w-9 h-[42px] bg-surface-container-lowest hover:bg-surface-container-high border border-outline-variant/30 rounded-xl text-on-surface-variant flex items-center justify-center shrink-0 transition-colors shadow-2xs cursor-pointer" title="View Details">
                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                            </button>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Empty State when no processing orders -->
            <div class="py-8 text-center flex flex-col items-center justify-center gap-2 border border-dashed border-outline-variant/40 rounded-2xl bg-surface-container-low/20">
                <span class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center text-outline text-2xl material-symbols-outlined">task_alt</span>
                <div>
                    <h4 class="text-sm font-bold text-on-surface">No Orders Currently in Preparation</h4>
                    <p class="text-xs text-outline max-w-sm mt-0.5">Orders that have been accepted and set to processing will appear here for packaging and fulfillment.</p>
                </div>
            </div>
        <?php endif; ?>
        </div>

    </div>

    <!-- All Orders Main Section -->
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl overflow-hidden shadow-xs space-y-0">
        
        <!-- Section Header -->
        <div class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-outline-variant/20">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 shadow-2xs">
                    <span class="material-symbols-outlined text-[22px]">list_alt</span>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-on-surface">All Orders Directory</h3>
                    <p class="text-xs text-on-surface-variant">View order logs, track statuses, inspect items, and handle fulfillment</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <?php
                $hasFilters = !empty($filters['q']) || !empty($filters['status']) || !empty($filters['fulfillment']) || !empty($filters['from']) || !empty($filters['to']);
                ?>
                <?php if ($hasFilters): ?>
                    <a href="<?= base_url('tenant/orders') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition-all">
                        <span class="material-symbols-outlined text-[16px]">filter_alt_off</span>
                        <span>Clear Filters</span>
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('tenant/pos') ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-secondary text-on-secondary rounded-xl text-xs font-bold hover:bg-secondary/90 shadow-2xs transition-all">
                    <span class="material-symbols-outlined text-[16px]">point_of_sale</span>
                    <span>New POS Sale</span>
                </a>
            </div>
        </div>

        <!-- Status Filter Tabs (Horizontal Pill Nav) -->
        <?php
        $currStatus = $filters['status'] ?? '';
        $counts = $summary['status_counts'] ?? [];
        $statusTabs = [
            ''                 => ['label' => 'All Orders',        'count' => $counts['all'] ?? null,              'icon' => 'apps'],
            'pending'          => ['label' => 'Pending',           'count' => $counts['pending'] ?? null,          'icon' => 'schedule'],
            'processing'       => ['label' => 'In Prep',           'count' => $counts['processing'] ?? null,       'icon' => 'inventory_2'],
            'ready_for_pickup' => ['label' => 'Ready for Pickup',  'count' => $counts['ready_for_pickup'] ?? null, 'icon' => 'storefront'],
            'shipped'          => ['label' => 'In Transit',        'count' => $counts['in_transit'] ?? null,       'icon' => 'local_shipping'],
            'completed'        => ['label' => 'Completed',         'count' => $counts['completed'] ?? null,        'icon' => 'check_circle'],
            'cancelled'        => ['label' => 'Cancelled',         'count' => $counts['cancelled'] ?? null,        'icon' => 'cancel'],
        ];
        ?>
        <div class="px-4 sm:px-5 py-2.5 bg-surface-container-low/30 border-b border-outline-variant/20 overflow-x-auto custom-scrollbar">
            <div class="flex items-center gap-2 min-w-max">
                <?php foreach ($statusTabs as $tabKey => $tab): ?>
                    <?php
                    $isActive = $currStatus === $tabKey;
                    $urlParams = $filters;
                    $urlParams['status'] = $tabKey;
                    if ($tabKey === '') unset($urlParams['status']);
                    $tabUrl = base_url('tenant/orders?' . http_build_query(array_filter($urlParams)));
                    ?>
                    <a href="<?= esc($tabUrl) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all <?= $isActive ? 'bg-primary text-on-primary shadow-xs font-bold' : 'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container hover:text-on-surface border border-outline-variant/30' ?>">
                        <span class="material-symbols-outlined text-[15px]"><?= $tab['icon'] ?></span>
                        <span><?= esc($tab['label']) ?></span>
                        <?php if (isset($tab['count']) && $tab['count'] !== null): ?>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono <?= $isActive ? 'bg-on-primary/20 text-on-primary font-bold' : 'bg-surface-container-high text-on-surface-variant' ?>">
                                <?= (int) $tab['count'] ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Secondary Filter Toolbar (Search, Fulfillment, Dates, Per-page) -->
        <div class="p-3 sm:px-5 bg-surface-container-low/50 border-b border-outline-variant/20">
            <form method="get" action="<?= base_url('tenant/orders') ?>" class="flex flex-wrap items-center gap-2.5 text-xs">
                <?php if (!empty($filters['status'])): ?>
                    <input type="hidden" name="status" value="<?= esc($filters['status']) ?>">
                <?php endif; ?>

                <!-- Search Input -->
                <div class="relative flex-1 min-w-[220px]">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                    <input type="text" name="q" value="<?= esc($filters['q']) ?>" placeholder="Search order #, customer, SKU, product..." class="w-full pl-9 pr-8 py-2 bg-surface-container-lowest border border-outline-variant/40 rounded-xl text-xs text-on-surface placeholder:text-outline focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                    <?php if (!empty($filters['q'])): ?>
                        <a href="<?= base_url('tenant/orders?' . http_build_query(array_merge($filters, ['q' => '']))) ?>" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface p-0.5" title="Clear search">
                            <span class="material-symbols-outlined text-[16px]">close</span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Fulfillment Method Filter -->
                <select name="fulfillment" class="bg-surface-container-lowest border border-outline-variant/40 rounded-xl px-3 py-2 text-xs text-on-surface font-semibold focus:ring-2 focus:ring-primary transition-all cursor-pointer">
                    <option value="">All Fulfillment</option>
                    <option value="delivery" <?= ($filters['fulfillment'] ?? '') === 'delivery' ? 'selected' : '' ?>>Doorstep Delivery</option>
                    <option value="pickup" <?= ($filters['fulfillment'] ?? '') === 'pickup' ? 'selected' : '' ?>>Store Pick-up</option>
                </select>

                <!-- Date Range -->
                <div class="flex items-center gap-1.5 bg-surface-container-lowest border border-outline-variant/40 rounded-xl px-2.5 py-1 text-xs">
                    <span class="text-outline font-medium text-[11px]">From:</span>
                    <input name="from" value="<?= esc($filters['from']) ?>" type="date" class="bg-transparent text-xs text-on-surface font-medium border-0 p-1 focus:ring-0">
                    <span class="text-outline font-medium text-[11px]">To:</span>
                    <input name="to" value="<?= esc($filters['to']) ?>" type="date" class="bg-transparent text-xs text-on-surface font-medium border-0 p-1 focus:ring-0">
                </div>

                <!-- Per-Page Selector -->
                <div class="flex items-center gap-1.5 text-xs text-on-surface-variant">
                    <label for="orders_per_page" class="font-medium text-outline">Show:</label>
                    <select name="per_page" id="orders_per_page" onchange="this.form.submit()" class="bg-surface-container-lowest border border-outline-variant/40 rounded-xl px-2.5 py-2 text-xs font-semibold text-on-surface focus:ring-2 focus:ring-primary transition-all cursor-pointer">
                        <option value="5" <?= ($per_page ?? 10) == 5 ? 'selected' : '' ?>>5</option>
                        <option value="10" <?= ($per_page ?? 10) == 10 ? 'selected' : '' ?>>10</option>
                        <option value="15" <?= ($per_page ?? 10) == 15 ? 'selected' : '' ?>>15</option>
                        <option value="20" <?= ($per_page ?? 10) == 20 ? 'selected' : '' ?>>20</option>
                    </select>
                </div>

                <button type="submit" class="bg-primary hover:bg-primary/90 text-on-primary px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-2xs active:scale-95 cursor-pointer">
                    Apply Filter
                </button>
            </form>
        </div>

        <!-- DESKTOP ORDERS TABLE (Hidden on small screens) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low/70 border-b border-outline-variant/30">
                    <tr>
                        <th class="px-4 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Order ID</th>
                        <th class="px-4 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Customer</th>
                        <th class="px-4 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Items Summary</th>
                        <th class="px-4 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-wider text-center">Fulfillment &amp; Pay</th>
                        <th class="px-4 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Placed Date</th>
                        <th class="px-4 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-wider text-right">Amount</th>
                        <th class="px-4 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-wider text-center">Status</th>
                        <th class="px-4 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/15">
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $o): ?>
                            <?php
                            $fullName = trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? ''));
                            $initials = $fullName !== '' ? mb_strtoupper(mb_substr($fullName, 0, 2)) : 'GU';
                            $profileImage = trim((string) ($o['profile_image_url'] ?? ''));
                            $isPickup = ($o['fulfillment_method'] ?? 'delivery') === 'pickup';
                            $isPaid = ($o['payment_status'] ?? '') === 'paid';
                            $custPhone = esc($o['customer_phone'] ?? 'N/A');
                            $itemsSummary = $o['items_summary'] ?? '';
                            $totalUnits = (int) ($o['total_units'] ?? 0);
                            $shopNameStr = esc($shop['shop_name'] ?? 'Blax Storefront');
                            $locationStr = trim(($o['address_line1'] ?? '') . ', ' . ($o['city'] ?? '')) ?: 'Polomolok, South Cotabato';
                            $labelStr    = esc($o['address_label'] ?? 'Home');
                            ?>
                            <tr class="hover:bg-surface-container-low/40 transition-colors group">
                                <!-- Order ID with Quick Copy -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" onclick="openOrderDetails(<?= (int) $o['id'] ?>)" class="font-mono text-xs font-bold text-primary hover:underline hover:text-primary/80 cursor-pointer">
                                            #<?= esc($o['order_number']) ?>
                                        </button>
                                        <button type="button" onclick="copyOrderNumber('<?= esc($o['order_number']) ?>', this)" class="p-1 rounded-lg text-outline hover:text-primary hover:bg-primary/10 transition-colors cursor-pointer" title="Copy Order #">
                                            <span class="material-symbols-outlined text-[14px]">content_copy</span>
                                        </button>
                                    </div>
                                </td>

                                <!-- Customer Info -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-bold shrink-0 relative overflow-hidden shadow-2xs">
                                            <span><?= esc($initials) ?></span>
                                            <?php if ($profileImage !== ''): ?>
                                                <img class="absolute inset-0 w-full h-full object-cover rounded-full" src="<?= esc(base_url($profileImage)) ?>" alt="<?= esc($fullName) ?> avatar" loading="lazy" onerror="this.remove();">
                                            <?php endif; ?>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold text-on-surface truncate leading-tight"><?= esc($fullName !== '' ? $fullName : 'Customer') ?></p>
                                            <?php if ($custPhone !== 'N/A' && $custPhone !== ''): ?>
                                                <a href="tel:<?= $custPhone ?>" class="text-[11px] font-mono text-outline hover:text-primary flex items-center gap-0.5 mt-0.5">
                                                    <span class="material-symbols-outlined text-[12px]">call</span>
                                                    <span><?= $custPhone ?></span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- Items Preview -->
                                <td class="px-4 py-3.5 max-w-[220px]">
                                    <div class="text-xs space-y-0.5">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-mono text-[10px] font-bold px-1.5 py-0.2 rounded bg-surface-container text-on-surface-variant">
                                                <?= $totalUnits > 0 ? $totalUnits . ' unit' . ($totalUnits === 1 ? '' : 's') : '1 unit' ?>
                                            </span>
                                        </div>
                                        <p class="text-xs text-on-surface-variant truncate font-medium" title="<?= esc($itemsSummary) ?>">
                                            <?= esc($itemsSummary !== '' ? $itemsSummary : 'Standard Order Items') ?>
                                        </p>
                                    </div>
                                </td>

                                <!-- Fulfillment & Payment Status -->
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <div class="flex flex-col items-center gap-1">
                                        <?php if ($isPickup): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-800 border border-amber-500/20 shadow-2xs">
                                                <span class="material-symbols-outlined text-[13px]">storefront</span>
                                                <span>Store Pick-up</span>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-800 border border-blue-500/20 shadow-2xs">
                                                <span class="material-symbols-outlined text-[13px]">local_shipping</span>
                                                <span>Doorstep Delivery</span>
                                            </span>
                                        <?php endif; ?>

                                        <!-- Payment Tag -->
                                        <div class="flex items-center gap-1 text-[10px]">
                                            <?php if ($isPaid): ?>
                                                <span class="font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded flex items-center gap-0.5">
                                                    <span class="material-symbols-outlined text-[11px]">verified</span> Paid
                                                </span>
                                            <?php else: ?>
                                                <span class="font-bold text-amber-700 bg-amber-50 px-1.5 py-0.2 rounded">
                                                    <?= $isPickup ? 'Pay on Pick-up' : 'COD' ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- Date Placed -->
                                <td class="px-4 py-3.5 text-xs text-on-surface-variant whitespace-nowrap">
                                    <p class="font-semibold text-on-surface"><?= esc(date('M d, Y', strtotime($o['placed_at']))) ?></p>
                                    <p class="text-[10px] text-outline"><?= esc(date('h:i A', strtotime($o['placed_at']))) ?></p>
                                </td>

                                <!-- Total Amount -->
                                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                    <span class="font-mono text-sm font-bold text-on-surface">₱<?= number_format((float) $o['total_amount'], 2) ?></span>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <?= status_badge($o['status']) ?>
                                    <?php if ($isPaid && !in_array($o['status'], ['delivered', 'completed', 'cancelled'], true)): ?>
                                        <span class="block text-[9px] font-bold text-amber-700 mt-0.5 tracking-tight">Escrow Holding</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                    <div class="flex justify-end items-center gap-1.5">
                                        
                                        <!-- Contextual Primary Workflow Button -->
                                        <?php if ($o['status'] === 'pending'): ?>
                                            <form action="<?= base_url('tenant/orders/update-status') ?>" method="POST" class="inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                                                <input type="hidden" name="status" value="processing">
                                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-primary hover:bg-primary/90 text-on-primary text-xs font-bold transition-all shadow-2xs active:scale-95 cursor-pointer" title="Accept order &amp; start packaging">
                                                    <span class="material-symbols-outlined text-[15px]">check_circle</span>
                                                    <span>Accept</span>
                                                </button>
                                            </form>
                                        <?php elseif (!$isPickup && $o['status'] === 'processing'): ?>
                                            <form action="<?= base_url('tenant/orders/update-status') ?>" method="POST" class="inline" onsubmit="return confirm('Dispatch Order #<?= esc($o['order_number']) ?> as In Transit for delivery?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                                                <input type="hidden" name="status" value="shipped">
                                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition-all shadow-2xs active:scale-95 cursor-pointer" title="Handover to delivery">
                                                    <span class="material-symbols-outlined text-[15px]">local_shipping</span>
                                                    <span>In Transit</span>
                                                </button>
                                            </form>
                                        <?php elseif ($isPickup && !in_array($o['status'], ['pending', 'completed', 'delivered', 'cancelled'], true)): ?>
                                            <a href="<?= base_url('tenant/pos?order_id=' . $o['id']) ?>" 
                                               class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-secondary-container/50 hover:bg-secondary-container text-secondary border border-secondary/30 text-xs font-bold transition-all shadow-2xs active:scale-95" 
                                               title="Add to POS for Store Pick-up Handover">
                                                <span class="material-symbols-outlined text-[15px]">point_of_sale</span>
                                                <span><?= $o['status'] === 'ready_for_pickup' ? 'Add POS' : 'POS' ?></span>
                                            </a>
                                        <?php endif; ?>

                                        <!-- View Details Button -->
                                        <button type="button" onclick="openOrderDetails(<?= (int) $o['id'] ?>)" class="p-1.5 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface border border-outline-variant/30 transition-all shadow-2xs hover:shadow-xs active:scale-95 cursor-pointer" title="View Full Order Details">
                                            <span class="material-symbols-outlined text-[17px] text-primary">visibility</span>
                                        </button>

                                        <!-- QR Code Button (if processing) -->
                                        <?php if ($o['status'] === 'processing'): ?>
                                            <button type="button"
                                                    onclick="openOrderQrModal(
                                                        '<?= esc($o['order_number']) ?>',
                                                        '<?= $shopNameStr ?>',
                                                        '<?= esc($fullName !== '' ? $fullName : 'Customer') ?>',
                                                        '<?= esc($locationStr) ?>',
                                                        '<?= $labelStr ?>',
                                                        '<?= esc($custPhone) ?>',
                                                        '<?= esc(date('M d, Y h:i A', strtotime($o['placed_at']))) ?>',
                                                        '<?= esc(base_url('order/' . $o['order_number'])) ?>'
                                                    )"
                                                    class="p-1.5 rounded-xl bg-primary/10 hover:bg-primary/20 text-primary border border-primary/20 transition-all shadow-2xs active:scale-95 cursor-pointer"
                                                    title="Generate & Download QR Code">
                                                <span class="material-symbols-outlined text-[17px]">qr_code_2</span>
                                            </button>
                                        <?php endif; ?>

                                        <!-- Print Slip Button -->
                                        <a href="<?= base_url('tenant/orders/receipt/' . (int) $o['id']) ?>" target="_blank" class="p-1.5 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface-variant border border-outline-variant/30 transition-all shadow-2xs" title="Print Official Receipt">
                                            <span class="material-symbols-outlined text-[17px]">receipt_long</span>
                                        </a>

                                        <!-- More Actions Dropdown -->
                                        <div class="relative">
                                            <button type="button" data-row="<?= (int) $o['id'] ?>" onclick="toggleDropdown(this)" class="more-toggle p-1.5 hover:bg-surface-container-high rounded-xl text-on-surface-variant border border-transparent hover:border-outline-variant/30 transition-colors cursor-pointer" title="More Options" aria-haspopup="true" aria-expanded="false">
                                                <span class="material-symbols-outlined text-[18px]">more_vert</span>
                                            </button>
                                            <div id="more-menu-<?= (int) $o['id'] ?>" class="hidden more-menu z-50 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl shadow-xl p-2 min-w-[220px]" role="menu">
                                                <p class="text-[10px] font-bold uppercase tracking-wider text-outline px-2.5 py-1">Update Status</p>
                                                <form action="<?= base_url('tenant/orders/update-status') ?>" method="POST" class="space-y-1.5 px-1 pb-1">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                                                    <?php
                                                    $rowStatusOptions = $isPickup
                                                        ? [
                                                            'pending'          => 'Pending',
                                                            'processing'       => 'Processing',
                                                            'ready_for_pickup' => 'Ready for Pickup',
                                                            'cancelled'        => 'Cancelled',
                                                        ]
                                                        : [
                                                            'pending'          => 'Pending',
                                                            'processing'       => 'Processing',
                                                            'shipped'          => 'In Transit',
                                                            'cancelled'        => 'Cancelled',
                                                        ];
                                                    if (!isset($rowStatusOptions[$o['status']])) {
                                                        $rowStatusOptions[$o['status']] = humanize_status($o['status']);
                                                    }
                                                    ?>
                                                    <select name="status" class="w-full p-2 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs font-semibold">
                                                        <?php foreach ($rowStatusOptions as $val => $label): ?>
                                                            <option value="<?= esc($val) ?>" <?= $o['status'] === $val ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="w-full py-1.5 bg-primary text-on-primary rounded-xl text-xs font-bold hover:bg-primary/90 transition-all cursor-pointer">Apply Status</button>
                                                </form>
                                                
                                                <div class="border-t border-outline-variant/15 my-1"></div>
                                                
                                                <button type="button" onclick="openReportCustomerModal(<?= (int) ($o['customer_id'] ?? 0) ?>, '<?= esc($fullName !== '' ? $fullName : 'Customer', 'js') ?>', '<?= esc($o['order_number'], 'js') ?>')" class="w-full text-left px-2.5 py-2 rounded-xl text-error text-xs font-bold hover:bg-rose-50 flex items-center gap-1.5 transition-colors cursor-pointer">
                                                    <span class="material-symbols-outlined text-[16px]">flag</span>
                                                    <span>Report Customer</span>
                                                </button>
                                            </div>
                                        </div>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="py-12 text-center text-on-surface-variant">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="w-12 h-12 rounded-2xl bg-surface-container flex items-center justify-center text-outline text-2xl material-symbols-outlined">search_off</div>
                                    <h4 class="text-sm font-bold text-on-surface">No Orders Found</h4>
                                    <p class="text-xs text-outline max-w-sm">No customer orders matched your active search or filters. Try adjusting your search query or status tab.</p>
                                    <?php if ($hasFilters): ?>
                                        <a href="<?= base_url('tenant/orders') ?>" class="mt-2 px-3 py-1.5 rounded-xl bg-primary text-on-primary text-xs font-bold hover:bg-primary/90">
                                            Clear All Filters
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- MOBILE ORDERS CARDS (Visible on screens < 768px) -->
        <div class="md:hidden divide-y divide-outline-variant/20">
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $o): ?>
                    <?php
                    $fullName = trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? ''));
                    $initials = $fullName !== '' ? mb_strtoupper(mb_substr($fullName, 0, 2)) : 'GU';
                    $profileImage = trim((string) ($o['profile_image_url'] ?? ''));
                    $isPickup = ($o['fulfillment_method'] ?? 'delivery') === 'pickup';
                    $isPaid = ($o['payment_status'] ?? '') === 'paid';
                    $custPhone = esc($o['customer_phone'] ?? 'N/A');
                    $itemsSummary = $o['items_summary'] ?? '';
                    $totalUnits = (int) ($o['total_units'] ?? 0);
                    $shopNameStr = esc($shop['shop_name'] ?? 'Blax Storefront');
                    $locationStr = trim(($o['address_line1'] ?? '') . ', ' . ($o['city'] ?? '')) ?: 'Polomolok, South Cotabato';
                    $labelStr    = esc($o['address_label'] ?? 'Home');
                    ?>
                    <div class="p-4 space-y-3 bg-surface-container-lowest hover:bg-surface-container-low/30 transition-colors">
                        <!-- Top Header: Order #, Date, Status -->
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <button type="button" onclick="openOrderDetails(<?= (int) $o['id'] ?>)" class="font-mono text-sm font-bold text-primary hover:underline flex items-center gap-1 cursor-pointer">
                                    #<?= esc($o['order_number']) ?>
                                </button>
                                <span class="text-[11px] text-outline block mt-0.5">
                                    <?= esc(date('M d, Y h:i A', strtotime($o['placed_at']))) ?>
                                </span>
                            </div>
                            <div class="text-right flex flex-col items-end gap-1">
                                <?= status_badge($o['status']) ?>
                                <span class="font-mono text-xs font-bold text-on-surface">₱<?= number_format((float) $o['total_amount'], 2) ?></span>
                            </div>
                        </div>

                        <!-- Customer Info Row -->
                        <div class="flex items-center justify-between p-2.5 bg-surface-container-low/40 rounded-xl border border-outline-variant/20 text-xs">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-bold shrink-0 relative overflow-hidden">
                                    <span><?= esc($initials) ?></span>
                                    <?php if ($profileImage !== ''): ?>
                                        <img class="absolute inset-0 w-full h-full object-cover rounded-full" src="<?= esc(base_url($profileImage)) ?>" alt="<?= esc($fullName) ?> avatar" loading="lazy" onerror="this.remove();">
                                    <?php endif; ?>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-on-surface truncate"><?= esc($fullName !== '' ? $fullName : 'Customer') ?></p>
                                    <p class="text-[11px] text-outline truncate"><?= $isPickup ? 'Store Pick-up' : esc($locationStr) ?></p>
                                </div>
                            </div>
                            <?php if ($custPhone !== 'N/A' && $custPhone !== ''): ?>
                                <a href="tel:<?= $custPhone ?>" class="p-1.5 rounded-lg bg-blue-50 text-blue-600 font-mono text-xs flex items-center gap-0.5">
                                    <span class="material-symbols-outlined text-[15px]">call</span>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Items Summary & Badges -->
                        <div class="flex items-center justify-between gap-2 text-xs">
                            <div class="truncate text-on-surface-variant text-[11px] flex-1">
                                <span class="font-mono font-bold bg-surface-container px-1.5 py-0.2 rounded mr-1"><?= $totalUnits ?>x</span>
                                <?= esc($itemsSummary !== '' ? $itemsSummary : 'Order items') ?>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <?php if ($isPickup): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">Pick-up</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">Delivery</span>
                                <?php endif; ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $isPaid ? 'bg-emerald-100 text-emerald-800' : 'bg-surface-container text-outline' ?>">
                                    <?= $isPaid ? 'Paid' : ($isPickup ? 'Unpaid' : 'COD') ?>
                                </span>
                            </div>
                        </div>

                        <!-- Actions Bar -->
                        <div class="flex items-center gap-1.5 pt-1 border-t border-outline-variant/15">
                            <?php if ($o['status'] === 'pending'): ?>
                                <form action="<?= base_url('tenant/orders/update-status') ?>" method="POST" class="flex-1">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                                    <input type="hidden" name="status" value="processing">
                                    <button type="submit" class="w-full py-2 bg-primary text-on-primary rounded-xl text-xs font-bold flex items-center justify-center gap-1 cursor-pointer">
                                        <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                        <span>Accept</span>
                                    </button>
                                </form>
                            <?php elseif (!$isPickup && $o['status'] === 'processing'): ?>
                                <form action="<?= base_url('tenant/orders/update-status') ?>" method="POST" class="flex-1" onsubmit="return confirm('Dispatch Order #<?= esc($o['order_number']) ?> as In Transit?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                                    <input type="hidden" name="status" value="shipped">
                                    <button type="submit" class="w-full py-2 bg-blue-600 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1 cursor-pointer">
                                        <span class="material-symbols-outlined text-[16px]">local_shipping</span>
                                        <span>In Transit</span>
                                    </button>
                                </form>
                            <?php elseif ($isPickup && !in_array($o['status'], ['pending', 'completed', 'delivered', 'cancelled'], true)): ?>
                                <a href="<?= base_url('tenant/pos?order_id=' . $o['id']) ?>" class="flex-1 py-2 bg-secondary text-on-secondary rounded-xl text-xs font-bold flex items-center justify-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">point_of_sale</span>
                                    <span>POS</span>
                                </a>
                            <?php endif; ?>

                            <button type="button" onclick="openOrderDetails(<?= (int) $o['id'] ?>)" class="flex-1 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface rounded-xl text-xs font-bold border border-outline-variant/30 flex items-center justify-center gap-1 cursor-pointer">
                                <span class="material-symbols-outlined text-[16px] text-primary">visibility</span>
                                <span>Details</span>
                            </button>

                            <a href="<?= base_url('tenant/orders/receipt/' . (int) $o['id']) ?>" target="_blank" class="p-2 bg-surface-container rounded-xl text-on-surface-variant border border-outline-variant/30 flex items-center justify-center" title="Receipt">
                                <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="py-10 text-center text-on-surface-variant">
                    <p class="text-xs font-bold">No orders found.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Modern Pagination Bar -->
        <?php
        $oTot     = (int) $pager->getTotal('orders');
        $oCur     = (int) $pager->getCurrentPage('orders');
        $oPag     = (int) $pager->getPageCount('orders');
        $oPerPage = (int) ($per_page ?? ($pager ? $pager->getPerPage('orders') : 10));
        $oStart   = $oTot === 0 ? 0 : ($oCur - 1) * $oPerPage + 1;
        $oEnd     = min($oCur * $oPerPage, $oTot);
        ?>
        <?php if ($oPag > 1 || $oTot > 0): ?>
            <div class="px-4 sm:px-5 py-3.5 bg-surface-container-low/40 flex justify-between items-center border-t border-outline-variant/20 flex-wrap gap-2 text-xs">
                <p class="text-on-surface-variant font-medium">
                    Showing <span class="font-bold text-on-surface"><?= number_format($oStart) ?></span> to <span class="font-bold text-on-surface"><?= number_format($oEnd) ?></span> of <span class="font-bold text-on-surface"><?= number_format($oTot) ?></span> orders
                </p>
                <?php if ($oPag > 1): ?>
                    <div class="flex items-center gap-1">
                        <a class="p-1.5 rounded-xl border border-outline-variant/30 hover:bg-surface-container-high transition-colors <?= $oCur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('orders') ?>" title="Previous page">
                            <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                        </a>
                        <?php
                        $w = [];
                        for ($i = 1; $i <= $oPag; $i++) {
                            if ($i === 1 || $i === $oPag || abs($i - $oCur) <= 2) { $w[] = $i; }
                        }
                        $pv = 0;
                        foreach ($w as $n):
                            if ($n - $pv > 1): ?><span class="px-1 text-outline">...</span><?php endif; ?>
                            <a class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-semibold transition-all <?= $oCur === $n ? 'bg-primary text-on-primary font-bold shadow-2xs' : 'bg-surface-container-lowest border border-outline-variant/30 hover:bg-surface-container-high text-on-surface' ?>" href="<?= $pager->getPageURI($n, 'orders') ?>"><?= $n ?></a>
                        <?php $pv = $n; endforeach; ?>
                        <a class="p-1.5 rounded-xl border border-outline-variant/30 hover:bg-surface-container-high transition-colors <?= $oCur >= $oPag ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('orders') ?>" title="Next page">
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

</div>

<!-- Elevated Order Details Modal -->
<div id="orderModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-3 sm:p-4 backdrop-blur-xs" onclick="if(event.target === this) this.classList.add('hidden')">
    <div class="bg-surface-container-lowest rounded-3xl p-5 sm:p-6 max-w-xl w-full border border-outline-variant/30 shadow-2xl max-h-[92vh] overflow-y-auto space-y-4 animate-scaleUp">
        
        <!-- Header -->
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-3">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-lg sm:text-xl font-black text-on-surface">Order <span id="omNumber" class="text-primary font-mono"></span></h3>
                    <span id="omStatusBadge" class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-surface-container text-on-surface-variant uppercase"></span>
                </div>
                <p id="omDate" class="text-xs text-outline mt-0.5"></p>
            </div>
            <button type="button" onclick="document.getElementById('orderModal').classList.add('hidden')" class="text-outline hover:text-on-surface p-1.5 rounded-full hover:bg-surface-container transition-colors cursor-pointer">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <!-- Customer & Destination Card -->
        <div class="bg-surface-container-low/60 p-4 rounded-2xl border border-outline-variant/25 space-y-2.5 text-xs">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-primary">person</span>
                    <span class="text-outline uppercase tracking-wider font-bold text-[10px]">Customer Information</span>
                </div>
                <button type="button" id="omReportCustomerBtn" class="text-[11px] font-bold text-rose-600 hover:text-rose-800 hover:underline flex items-center gap-0.5 cursor-pointer">
                    <span class="material-symbols-outlined text-[14px]">flag</span> Report Customer
                </button>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 border-t border-outline-variant/15">
                <div>
                    <span class="text-outline text-[10px] block uppercase font-medium">Name</span>
                    <span id="omCustomer" class="font-bold text-sm text-on-surface"></span>
                </div>
                <div>
                    <span class="text-outline text-[10px] block uppercase font-medium">Contact Phone</span>
                    <span id="omPhone" class="font-bold font-mono text-on-surface"></span>
                </div>
                <div class="sm:col-span-2">
                    <span class="text-outline text-[10px] block uppercase font-medium">Fulfillment &amp; Address</span>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span id="omFulfillmentBadge" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800"></span>
                        <span id="omAddress" class="text-on-surface font-medium truncate"></span>
                    </div>
                </div>
                <div>
                    <span class="text-outline text-[10px] block uppercase font-medium">Payment Method</span>
                    <span id="omPayment" class="font-bold text-on-surface uppercase font-mono"></span>
                </div>
            </div>
        </div>

        <!-- Products Breakdown Table -->
        <div class="space-y-1.5">
            <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Ordered Items</span>
            <div class="rounded-2xl border border-outline-variant/20 overflow-hidden bg-surface-container-lowest">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-surface-container-low/70 border-b border-outline-variant/20">
                        <tr>
                            <th class="px-3 py-2 text-[10px] font-bold text-outline uppercase tracking-wider">Product</th>
                            <th class="px-3 py-2 text-[10px] font-bold text-outline uppercase tracking-wider text-right">Qty</th>
                            <th class="px-3 py-2 text-[10px] font-bold text-outline uppercase tracking-wider text-right">Unit Price</th>
                            <th class="px-3 py-2 text-[10px] font-bold text-outline uppercase tracking-wider text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody id="omItems" class="divide-y divide-outline-variant/10"></tbody>
                </table>
            </div>
        </div>

        <!-- Financial Breakdown -->
        <div class="bg-surface-container-low/40 p-3.5 rounded-2xl border border-outline-variant/20 space-y-1.5 text-xs">
            <div class="flex justify-between text-on-surface-variant">
                <span>Subtotal</span>
                <span id="omSubtotal" class="font-mono font-medium text-on-surface">₱0.00</span>
            </div>
            <div class="flex justify-between text-on-surface-variant">
                <span>Delivery / Shipping Fee</span>
                <span id="omShipping" class="font-mono font-medium text-on-surface">₱0.00</span>
            </div>
            <div class="flex justify-between text-on-surface-variant">
                <span>Tax</span>
                <span id="omTax" class="font-mono font-medium text-on-surface">₱0.00</span>
            </div>
            <div class="flex justify-between text-sm font-bold pt-1.5 border-t border-outline-variant/20">
                <span class="text-on-surface">Grand Total</span>
                <span id="omTotal" class="font-mono text-base font-extrabold text-primary">₱0.00</span>
            </div>
        </div>

        <!-- Modal Action Buttons -->
        <div class="space-y-2 pt-1">
            <div id="omAcceptContainer" class="hidden">
                <button type="button" id="omAcceptBtn" class="w-full py-2.5 bg-primary text-on-primary rounded-xl font-bold hover:bg-primary/90 transition-all flex items-center justify-center gap-1.5 text-xs shadow-sm active:scale-95 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    <span>Accept Order &amp; Start Packaging</span>
                </button>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div id="omPosContainer" class="hidden col-span-2 sm:col-span-1">
                    <button type="button" id="omPosBtn" class="w-full py-2.5 bg-secondary text-on-secondary rounded-xl font-bold hover:bg-secondary/90 transition-all flex items-center justify-center gap-1.5 text-xs shadow-2xs cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">point_of_sale</span>
                        <span>Open in POS</span>
                    </button>
                </div>

                <div id="omQrContainer" class="hidden col-span-2 sm:col-span-1">
                    <button type="button" id="omQrBtn" class="w-full py-2.5 bg-primary/10 text-primary border border-primary/25 rounded-xl font-bold hover:bg-primary/20 transition-all flex items-center justify-center gap-1.5 text-xs cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                        <span>View QR Code</span>
                    </button>
                </div>

                <div id="omReceiptContainer" class="col-span-2 sm:col-span-1">
                    <button type="button" id="omReceiptBtn" class="w-full py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface border border-outline-variant/40 rounded-xl font-bold transition-all flex items-center justify-center gap-1.5 text-xs shadow-2xs cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                        <span>Print Receipt</span>
                    </button>
                </div>

                <button type="button" onclick="document.getElementById('orderModal').classList.add('hidden')" class="col-span-2 sm:col-span-1 py-2.5 bg-surface-container text-on-surface rounded-xl font-semibold hover:bg-surface-container-high transition-all text-xs cursor-pointer">
                    Close
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Order QR Code & Waybill Modal -->
<div id="orderQrModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-3 sm:p-4 backdrop-blur-xs">
    <div class="bg-surface-container-lowest rounded-3xl p-5 sm:p-6 max-w-lg w-full space-y-4 border border-outline-variant/30 shadow-2xl relative max-h-[92vh] overflow-y-auto">
        
        <button type="button" onclick="closeOrderQrModal()" class="absolute top-4 right-4 text-outline hover:text-on-surface p-1 rounded-full hover:bg-surface-container cursor-pointer">
            <span class="material-symbols-outlined text-[20px]">close</span>
        </button>

        <!-- Header Status & Order No -->
        <div class="flex items-center justify-between border-b border-outline-variant/20 pb-3">
            <div>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                    <span class="material-symbols-outlined text-[13px]">sync</span> Processing Order
                </span>
                <h3 id="qrModalOrderNumber" class="text-xl font-black text-on-surface mt-1 font-mono"></h3>
            </div>
            <div class="text-right">
                <span id="qrModalPlacedAt" class="text-xs text-outline font-medium"></span>
            </div>
        </div>

        <!-- 2-Column Table UI: QR on Left, Shop Name / Customer / Location on Right -->
        <div class="bg-surface-container-low/70 p-4 rounded-2xl border border-outline-variant/25">
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
                
                <!-- Left Side: QR Code -->
                <div class="sm:col-span-5 flex flex-col items-center justify-center">
                    <div class="bg-white p-2.5 rounded-2xl border border-outline-variant/30 shadow-sm flex items-center justify-center">
                        <img id="qrModalImg" src="" alt="Order QR Code" class="w-36 h-36 object-contain rounded-lg">
                    </div>
                    <span class="text-[10px] text-outline mt-1.5 text-center font-semibold">Scan to Verify Order</span>
                </div>

                <!-- Right Side: Shop Name, Customer Name & Location -->
                <div class="sm:col-span-7 space-y-3 border-t sm:border-t-0 sm:border-l border-outline-variant/20 pt-3 sm:pt-0 sm:pl-4 text-left">
                    
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-outline tracking-wider block">Shop Name</span>
                            <h4 id="qrModalShopName" class="text-base font-black text-primary leading-tight mt-0.5"></h4>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-[10px] uppercase font-bold text-outline tracking-wider block">Phone</span>
                            <span id="qrModalPhone" class="text-xs font-bold text-on-surface bg-surface-container-high px-2 py-0.5 rounded-lg font-mono"></span>
                        </div>
                    </div>

                    <div class="border-t border-outline-variant/15 pt-2.5 space-y-2">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-outline tracking-wider flex items-center gap-1">
                                <span class="material-symbols-outlined text-[13px] text-primary">person</span> Customer Name
                            </span>
                            <p id="qrModalCustName" class="text-sm font-bold text-on-surface truncate mt-0.5"></p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] uppercase font-bold text-outline tracking-wider flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[13px] text-primary">location_on</span> Location
                                </span>
                                <span id="qrModalAddressLabel" class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-secondary-container text-on-secondary-container uppercase"></span>
                            </div>
                            <p id="qrModalLocation" class="text-xs font-medium text-on-surface-variant mt-0.5 leading-snug"></p>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2 pt-1">
            <button type="button" onclick="triggerQrDownload()" class="flex-1 py-2.5 px-3 bg-primary text-on-primary rounded-xl font-bold hover:bg-primary/90 transition-all shadow-sm active:scale-95 flex items-center justify-center gap-1.5 text-xs cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">download</span>
                <span>Download QR</span>
            </button>
            <button type="button" onclick="printQrCode()" class="flex-1 py-2.5 px-3 bg-surface-container-high text-on-surface rounded-xl font-bold hover:bg-surface-container-highest transition-all active:scale-95 flex items-center justify-center gap-1.5 text-xs cursor-pointer" title="Print Waybill & QR Code">
                <span class="material-symbols-outlined text-[18px]">print</span>
                <span>Print Waybill</span>
            </button>
        </div>

    </div>
</div>

<!-- Modal: Report Customer -->
<div id="reportCustomerModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-3 sm:p-4">
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-outline-variant/20 pb-3">
            <div class="flex items-center gap-2.5">
                <span class="p-2 bg-rose-100 text-rose-700 rounded-xl material-symbols-outlined text-[20px]">flag</span>
                <div>
                    <h3 class="text-base font-bold text-on-surface">Report Customer</h3>
                    <p id="rcCustomerName" class="text-xs text-on-surface-variant font-semibold"></p>
                </div>
            </div>
            <button type="button" onclick="closeReportCustomerModal()" class="text-outline hover:text-on-surface p-1 rounded-full cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        <form action="<?= base_url('tenant/compliance/report-customer') ?>" method="POST" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="customer_id" id="rcCustomerId">

            <div>
                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Issue Type</label>
                <select name="issue_type" required class="w-full mt-1 p-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs font-medium focus:ring-2 focus:ring-primary">
                    <option value="Bogus Buyer / Refused Order">Bogus Buyer / Refused Order</option>
                    <option value="Unresponsive Customer">Unresponsive Customer</option>
                    <option value="Abusive Language / Harassment">Abusive Language / Harassment</option>
                    <option value="Payment Dispute">Payment Dispute</option>
                    <option value="Suspicious / Fraudulent Account">Suspicious / Fraudulent Account</option>
                    <option value="Other Policy Violation">Other Policy Violation</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Incident Description &amp; Evidence</label>
                <textarea name="description" rows="4" required class="w-full mt-1 p-3 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs focus:ring-2 focus:ring-primary" placeholder="Detail the incident, messages, dates, or order context..."></textarea>
            </div>

            <div class="flex gap-2 pt-2 border-t border-outline-variant/20">
                <button type="button" onclick="closeReportCustomerModal()" class="flex-1 py-2.5 rounded-xl text-xs font-bold border border-outline-variant/40 hover:bg-surface-container cursor-pointer">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl text-xs font-bold bg-rose-600 text-white hover:bg-rose-700 shadow-sm cursor-pointer">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Dynamic Feedback Toast Notification -->
<div id="copyToast" class="fixed bottom-5 right-5 z-50 transform transition-all duration-300 translate-y-16 opacity-0 pointer-events-none flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gray-900 text-white text-xs font-bold shadow-lg">
    <span class="material-symbols-outlined text-[16px] text-emerald-400">check_circle</span>
    <span id="copyToastMsg">Order # copied to clipboard!</span>
</div>

<script>
    let activeMenu = null;

    function closeMenus() {
        if (activeMenu) {
            activeMenu.classList.add('hidden');
            activeMenu = null;
        }
        document.querySelectorAll('.more-toggle[aria-expanded="true"]').forEach((b) => b.setAttribute('aria-expanded', 'false'));
    }

    function toggleDropdown(btn) {
        const id   = btn.dataset.row;
        const menu = document.getElementById('more-menu-' + id);
        if (!menu) return;

        if (activeMenu === menu) {
            closeMenus();
            return;
        }
        closeMenus();

        document.body.appendChild(menu);
        menu.classList.remove('hidden');
        menu.style.position = 'fixed';
        menu.style.left = '';
        menu.style.right = '';
        menu.style.top = '';
        menu.style.bottom = '';

        const rect       = btn.getBoundingClientRect();
        const gap        = 8;
        const menuWidth  = menu.offsetWidth;
        const menuHeight = menu.offsetHeight;

        const left = rect.left + menuWidth <= window.innerWidth - gap
            ? rect.left
            : Math.max(gap, window.innerWidth - menuWidth - gap);
        menu.style.left = left + 'px';

        if (rect.bottom + gap + menuHeight <= window.innerHeight - gap) {
            menu.style.top = (rect.bottom + gap) + 'px';
        } else {
            menu.style.bottom = (window.innerHeight - rect.top + gap) + 'px';
        }

        activeMenu = menu;
        btn.setAttribute('aria-expanded', 'true');
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.more-menu') && !e.target.closest('.more-toggle')) {
            closeMenus();
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeMenus();
    });
    window.addEventListener('scroll', closeMenus, true);
    window.addEventListener('resize', closeMenus);

    function money(v) {
        return '₱' + Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function copyOrderNumber(num, btn) {
        navigator.clipboard.writeText(num).then(() => {
            const toast = document.getElementById('copyToast');
            const msg = document.getElementById('copyToastMsg');
            if (msg) msg.textContent = '#' + num + ' copied!';
            if (toast) {
                toast.classList.remove('translate-y-16', 'opacity-0');
                setTimeout(() => {
                    toast.classList.add('translate-y-16', 'opacity-0');
                }, 2200);
            }
        });
    }

    function openOrderDetails(id) {
        closeMenus();
        const modal = document.getElementById('orderModal');
        if (!modal) return;

        modal.classList.remove('hidden');

        document.getElementById('omNumber').textContent = '#' + id;
        document.getElementById('omCustomer').textContent = 'Loading...';
        document.getElementById('omDate').textContent = '...';
        document.getElementById('omPhone').textContent = '...';
        document.getElementById('omAddress').textContent = '...';
        document.getElementById('omPayment').textContent = '...';
        document.getElementById('omStatusBadge').textContent = '...';
        document.getElementById('omFulfillmentBadge').textContent = '...';
        document.getElementById('omSubtotal').textContent = '₱0.00';
        document.getElementById('omShipping').textContent = '₱0.00';
        document.getElementById('omTax').textContent = '₱0.00';
        document.getElementById('omTotal').textContent = '₱0.00';

        const reportBtn = document.getElementById('omReportCustomerBtn');
        if (reportBtn) reportBtn.classList.add('hidden');

        const acceptContainer = document.getElementById('omAcceptContainer');
        if (acceptContainer) acceptContainer.classList.add('hidden');

        const qrContainer = document.getElementById('omQrContainer');
        if (qrContainer) qrContainer.classList.add('hidden');

        const posContainer = document.getElementById('omPosContainer');
        if (posContainer) posContainer.classList.add('hidden');

        document.getElementById('omItems').innerHTML = '<tr><td colspan="4" class="py-4 text-center text-on-surface-variant"><span class="inline-flex items-center gap-2 font-semibold text-xs"><span class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span> Loading items...</span></td></tr>';

        const url = "<?= base_url('tenant/orders/items') ?>/" + encodeURIComponent(id);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => {
                if (!r.ok) throw new Error('HTTP status ' + r.status);
                return r.json();
            })
            .then((data) => {
                if (!data.success) {
                    document.getElementById('omItems').innerHTML = '<tr><td colspan="4" class="py-4 text-center text-rose-600 font-bold">' + escapeHtml(data.error || 'Could not load order.') + '</td></tr>';
                    return;
                }
                const o = data.order;
                document.getElementById('omNumber').textContent = '#' + (o.order_number || '');
                document.getElementById('omCustomer').textContent = o.customer || 'Customer';
                document.getElementById('omPhone').textContent = o.phone || 'N/A';
                document.getElementById('omAddress').textContent = o.location || (o.address_label || 'Store Counter');
                document.getElementById('omDate').textContent = 'Placed on ' + (o.placed_at || '');
                document.getElementById('omPayment').textContent = (o.payment_method || 'N/A') + ((o.payment_status === 'paid') ? ' (PAID)' : ' (UNPAID)');
                document.getElementById('omStatusBadge').textContent = o.status || '';
                document.getElementById('omFulfillmentBadge').textContent = (o.fulfillment_method === 'pickup') ? 'Store Pick-up' : 'Doorstep Delivery';

                if (reportBtn) {
                    if (o.customer_id) {
                        reportBtn.classList.remove('hidden');
                        reportBtn.onclick = function() {
                            openReportCustomerModal(o.customer_id, o.customer, o.order_number);
                        };
                    } else {
                        reportBtn.classList.add('hidden');
                    }
                }

                document.getElementById('omSubtotal').textContent = money(o.subtotal);
                document.getElementById('omShipping').textContent = money(o.shipping_fee);
                document.getElementById('omTax').textContent = money(o.tax_amount);
                document.getElementById('omTotal').textContent = money(o.total_amount);

                const tbody = document.getElementById('omItems');
                tbody.innerHTML = '';
                (data.items || []).forEach((it) => {
                    const tr = document.createElement('tr');
                    
                    const prodTd = document.createElement('td');
                    prodTd.className = 'px-3 py-2.5 text-xs text-left font-medium text-on-surface';
                    
                    let imgTag = '';
                    if (it.image_url) {
                        imgTag = `<img src="${escapeHtml(it.image_url)}" class="w-9 h-9 object-cover rounded-lg border border-outline-variant/30 shrink-0 bg-surface-container" alt="">`;
                    } else {
                        imgTag = `<div class="w-9 h-9 rounded-lg bg-surface-container flex items-center justify-center text-outline shrink-0"><span class="material-symbols-outlined text-[16px]">inventory_2</span></div>`;
                    }

                    let nameHtml = `<div class="min-w-0"><p class="font-bold text-on-surface leading-tight truncate">${escapeHtml(it.product_name)}</p>`;
                    if (it.variant_label) {
                        nameHtml += `<span class="inline-block text-[10px] font-bold text-primary bg-primary/10 px-1.5 py-0.2 rounded mt-0.5">${escapeHtml(it.variant_label)}</span>`;
                    }
                    nameHtml += `</div>`;

                    prodTd.innerHTML = `<div class="flex items-center gap-2">${imgTag}${nameHtml}</div>`;
                    tr.appendChild(prodTd);

                    const qtyTd = document.createElement('td');
                    qtyTd.className = 'px-3 py-2.5 text-xs text-right font-mono font-bold text-on-surface';
                    qtyTd.textContent = it.quantity + 'x';
                    tr.appendChild(qtyTd);

                    const unitTd = document.createElement('td');
                    unitTd.className = 'px-3 py-2.5 text-xs text-right font-mono text-outline';
                    unitTd.textContent = money(it.unit_price);
                    tr.appendChild(unitTd);

                    const lineTd = document.createElement('td');
                    lineTd.className = 'px-3 py-2.5 text-xs text-right font-mono font-bold text-on-surface';
                    lineTd.textContent = money(it.line_total);
                    tr.appendChild(lineTd);

                    tbody.appendChild(tr);
                });
                if (!data.items || data.items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="py-4 text-center text-outline">No items recorded for this order.</td></tr>';
                }

                // Accept button for pending orders
                const acceptBtn = document.getElementById('omAcceptBtn');
                if (acceptContainer && acceptBtn) {
                    if (o.raw_status === 'pending') {
                        acceptContainer.classList.remove('hidden');
                        acceptBtn.onclick = function() {
                            acceptBtn.disabled = true;
                            acceptBtn.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span> Updating...';

                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = '<?= base_url('tenant/orders/update-status') ?>';
                            
                            const csrfInput = document.createElement('input');
                            csrfInput.type = 'hidden';
                            csrfInput.name = '<?= csrf_token() ?>';
                            csrfInput.value = document.querySelector('input[name="<?= csrf_token() ?>"]')?.value || '<?= csrf_hash() ?>';
                            form.appendChild(csrfInput);

                            const orderIdInput = document.createElement('input');
                            orderIdInput.type = 'hidden';
                            orderIdInput.name = 'order_id';
                            orderIdInput.value = id;
                            form.appendChild(orderIdInput);

                            const statusInput = document.createElement('input');
                            statusInput.type = 'hidden';
                            statusInput.name = 'status';
                            statusInput.value = 'processing';
                            form.appendChild(statusInput);

                            document.body.appendChild(form);
                            form.submit();
                        };
                    } else {
                        acceptContainer.classList.add('hidden');
                    }
                }

                const qrBtn = document.getElementById('omQrBtn');
                if (qrContainer && qrBtn) {
                    if (o.raw_status === 'processing') {
                        qrContainer.classList.remove('hidden');
                        qrBtn.onclick = function() {
                            openOrderQrModal(
                                o.order_number,
                                o.shop_name || '<?= esc($shop['shop_name'] ?? 'Blax Storefront') ?>',
                                o.customer,
                                o.location || 'Polomolok, South Cotabato',
                                o.address_label || 'Home',
                                o.phone || 'N/A',
                                o.placed_at,
                                '<?= base_url('order/') ?>' + encodeURIComponent(o.order_number)
                            );
                        };
                    } else {
                        qrContainer.classList.add('hidden');
                    }
                }

                const posBtn = document.getElementById('omPosBtn');
                if (posContainer && posBtn) {
                    if (o.raw_fulfillment_method === 'pickup' && !['pending', 'completed', 'delivered', 'cancelled'].includes(o.raw_status)) {
                        posContainer.classList.remove('hidden');
                        posBtn.onclick = function() {
                            window.location.href = '<?= base_url('tenant/pos') ?>?order_id=' + id;
                        };
                    } else {
                        posContainer.classList.add('hidden');
                    }
                }

                const receiptBtn = document.getElementById('omReceiptBtn');
                if (receiptBtn) {
                    receiptBtn.onclick = function() {
                        window.open('<?= base_url('tenant/orders/receipt') ?>/' + encodeURIComponent(id), '_blank');
                    };
                }
            })
            .catch((err) => {
                console.error('Error loading order details:', err);
                document.getElementById('omItems').innerHTML = '<tr><td colspan="4" class="py-4 text-center text-rose-600 font-bold">Could not load order details. Please try again.</td></tr>';
            });
    }

    let currentQrData = {
        orderNumber: '',
        shopName: '',
        customerName: '',
        location: '',
        addressLabel: '',
        phone: '',
        placedAt: '',
        qrUrl: '',
        filename: ''
    };

    function openOrderQrModal(orderNumber, shopName, customerName, location, addressLabel, phone, placedAt, verifyUrl) {
        closeMenus();
        const modal = document.getElementById('orderQrModal');
        if (!modal) return;

        document.getElementById('qrModalOrderNumber').textContent = '#' + orderNumber;
        document.getElementById('qrModalPlacedAt').textContent = placedAt || '';
        document.getElementById('qrModalShopName').textContent = shopName || 'Blax Storefront';
        document.getElementById('qrModalCustName').textContent = customerName || 'Customer';
        document.getElementById('qrModalLocation').textContent = location || 'Polomolok, South Cotabato';
        document.getElementById('qrModalAddressLabel').textContent = addressLabel || 'Home';
        document.getElementById('qrModalPhone').textContent = phone || 'N/A';

        const apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' + encodeURIComponent(orderNumber);
        document.getElementById('qrModalImg').src = apiUrl;

        currentQrData = {
            orderNumber: orderNumber,
            shopName: shopName || 'Blax Storefront',
            customerName: customerName || 'Customer',
            location: location || 'Polomolok, South Cotabato',
            addressLabel: addressLabel || 'Home',
            phone: phone || 'N/A',
            placedAt: placedAt || '',
            qrUrl: apiUrl,
            filename: 'QR-' + orderNumber + '.png'
        };

        modal.classList.remove('hidden');
    }

    function closeOrderQrModal() {
        const modal = document.getElementById('orderQrModal');
        if (modal) modal.classList.add('hidden');
    }

    function triggerQrDownload() {
        if (!currentQrData.qrUrl) return;

        fetch(currentQrData.qrUrl)
            .then(res => res.blob())
            .then(blob => {
                const blobUrl = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = blobUrl;
                a.download = currentQrData.filename || 'Order-QR.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(blobUrl);
            })
            .catch(() => {
                window.open(currentQrData.qrUrl, '_blank');
            });
    }

    function printQrCode() {
        if (!currentQrData.qrUrl) return;
        const printWin = window.open('', '_blank');
        printWin.document.write(`
            <!DOCTYPE html>
            <html>
                <head>
                    <title>Order Waybill - #${currentQrData.orderNumber}</title>
                    <style>
                        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; padding: 20px; color: #1e293b; background: #ffffff; margin: 0; }
                        .card { border: 2px solid #2563eb; border-radius: 16px; padding: 20px; max-width: 500px; margin: 0 auto; background: #ffffff; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
                        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed #cbd5e1; padding-bottom: 12px; margin-bottom: 16px; }
                        .order-no { font-size: 22px; font-weight: 900; color: #0f172a; margin: 0; font-family: monospace; }
                        .badge { background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 99px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
                        
                        .main-grid { display: flex; gap: 20px; align-items: center; margin: 16px 0; }
                        .left-qr { width: 170px; text-align: center; }
                        .qr-img { width: 150px; height: 150px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 6px; background: #fff; }
                        .right-info { flex: 1; border-left: 2px solid #e2e8f0; padding-left: 18px; text-align: left; }
                        
                        .shop-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
                        .shop-label { font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin: 0; }
                        .shop-name { font-size: 18px; font-weight: 900; color: #2563eb; margin: 2px 0 0 0; line-height: 1.2; }
                        .phone-badge { font-size: 12px; font-weight: 800; color: #0f172a; font-family: monospace; background: #f1f5f9; padding: 3px 8px; border-radius: 6px; margin-top: 2px; }
                        
                        .field-group { margin-bottom: 10px; }
                        .field-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
                        .field-value { font-size: 14px; font-weight: 800; color: #0f172a; margin-top: 2px; line-height: 1.3; }
                        .address-label { display: inline-block; font-size: 10px; background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 4px; font-weight: 800; text-transform: uppercase; margin-left: 6px; }

                        .footer-note { text-align: center; font-size: 11px; color: #64748b; border-top: 1px dashed #e2e8f0; padding-top: 12px; margin-top: 16px; }
                    </style>
                </head>
                <body>
                    <div class="card">
                        <div class="header">
                            <div>
                                <span class="badge">Processing Order</span>
                                <h1 class="order-no">#${currentQrData.orderNumber}</h1>
                            </div>
                            <div style="font-size: 11px; color: #64748b; text-align: right;">${currentQrData.placedAt}</div>
                        </div>

                        <div class="main-grid">
                            <div class="left-qr">
                                <img src="${currentQrData.qrUrl}" class="qr-img" onload="doPrint()" onerror="doPrint()" />
                                <p style="font-size: 10px; color: #64748b; margin-top: 4px; font-weight: 600;">Scan to Verify Order</p>
                            </div>

                            <div class="right-info">
                                <div class="shop-header">
                                    <div>
                                        <p class="shop-label">Shop Name</p>
                                        <h2 class="shop-name">${currentQrData.shopName}</h2>
                                    </div>
                                    <div style="text-align: right;">
                                        <p class="shop-label">Customer Phone</p>
                                        <div class="phone-badge">${currentQrData.phone}</div>
                                    </div>
                                </div>

                                <div class="field-group">
                                    <span class="field-label">Customer Name</span>
                                    <div class="field-value">${currentQrData.customerName}</div>
                                </div>

                                <div class="field-group">
                                    <span class="field-label">Location</span> <span class="address-label">${currentQrData.addressLabel}</span>
                                    <div class="field-value">${currentQrData.location}</div>
                                </div>
                            </div>
                        </div>

                        <div class="footer-note">
                            Blax Storefront Order Waybill &amp; Verification System
                        </div>
                    </div>
                    <script>
                        var printed = false;
                        function doPrint() {
                            if (printed) return;
                            printed = true;
                            window.print();
                            setTimeout(function() { window.close(); }, 500);
                        }
                        setTimeout(doPrint, 2500);
                    <\/script>
                </body>
            </html>
        `);
        printWin.document.close();
    }

    function openReportCustomerModal(customerId, customerName, orderNumber) {
        document.getElementById('rcCustomerId').value = customerId;
        document.getElementById('rcCustomerName').textContent = customerName + (orderNumber ? ' (Order #' + orderNumber + ')' : '');
        document.getElementById('reportCustomerModal').classList.remove('hidden');
    }
    function closeReportCustomerModal() {
        document.getElementById('reportCustomerModal').classList.add('hidden');
    }

    function filterProcessingQueue(type) {
        document.querySelectorAll('.proc-filter-tab').forEach(t => {
            t.className = 'proc-filter-tab px-3 py-1.5 rounded-full font-medium bg-surface-container-low text-on-surface-variant hover:bg-surface-container border border-outline-variant/30 transition-all cursor-pointer';
        });
        const activeTab = document.getElementById('proc-tab-' + type);
        if (activeTab) {
            activeTab.className = 'proc-filter-tab px-3 py-1.5 rounded-full font-bold bg-primary text-on-primary shadow-2xs transition-all cursor-pointer';
        }
        document.querySelectorAll('.processing-order-card').forEach(card => {
            if (type === 'all' || card.dataset.fulfillment === type) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function toggleProcessingQueue() {
        const content = document.getElementById('queueContent');
        const icon = document.getElementById('toggleQueueIcon');
        const label = document.getElementById('toggleQueueLabel');
        const btn = document.getElementById('toggleQueueBtn');
        if (!content) return;

        const isHidden = content.classList.contains('hidden');
        if (isHidden) {
            content.classList.remove('hidden');
            if (icon) icon.textContent = 'expand_less';
            if (label) label.textContent = 'Hide';
            if (btn) btn.setAttribute('title', 'Hide Queue');
            localStorage.setItem('tenant_processing_queue_hidden', 'false');
        } else {
            content.classList.add('hidden');
            if (icon) icon.textContent = 'expand_more';
            if (label) label.textContent = 'Show';
            if (btn) btn.setAttribute('title', 'Show Queue');
            localStorage.setItem('tenant_processing_queue_hidden', 'true');
        }
    }

    // Restore saved hide/show state
    document.addEventListener('DOMContentLoaded', function() {
        if (localStorage.getItem('tenant_processing_queue_hidden') === 'true') {
            const content = document.getElementById('queueContent');
            const icon = document.getElementById('toggleQueueIcon');
            const label = document.getElementById('toggleQueueLabel');
            const btn = document.getElementById('toggleQueueBtn');
            if (content) content.classList.add('hidden');
            if (icon) icon.textContent = 'expand_more';
            if (label) label.textContent = 'Show';
            if (btn) btn.setAttribute('title', 'Show Queue');
        }
    });
</script>

<?= $this->endSection() ?>