<?= $this->extend('layouts/tenant') ?>

<?= $this->section('content') ?>

<?php
$isStorePickup = !empty($order);
$customerFullName = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
$customerPhone = trim((string) ($order['customer_phone'] ?? ($order['phone'] ?? '')));
$customerEmail = trim((string) ($order['customer_email'] ?? ($order['email'] ?? '')));
$customerProfileImg = trim((string) ($order['profile_image_url'] ?? ''));
$customerInitials = $customerFullName !== '' ? mb_strtoupper(mb_substr($customerFullName, 0, 2)) : 'OC';
$isOnlinePaid = ($order['payment_status'] ?? '') === 'paid';
$originalTotal = (float) ($order['total_amount'] ?? 0);
$existingPosAdd = (float) ($order['pos_additional_amount'] ?? 0);
$baseOnlineAmount = max(0, $originalTotal - $existingPosAdd);
$pickupOrders = $pickupOrders ?? [];
$printingPickupRequests = $printingPickupRequests ?? [];
$printingRequest = $printingRequest ?? null;
$isPrintingPickup = !empty($printingRequest);
$isCombinedPickup = $isStorePickup && $isPrintingPickup;
$printingOnlinePaid = (float) ($printingOnlinePaid ?? 0);
$printingRemainingBalance = (float) ($printingRemainingBalance ?? 0);
$categories = $categories ?? [];
$orderItems = $orderItems ?? [];
$customerHasPrintingMap = $customerHasPrintingMap ?? [];
$customerHasOrderMap = $customerHasOrderMap ?? [];
?>

<div class="space-y-md">
    <!-- TOP: Sleek POS Command Bar -->
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 sm:px-4 sm:py-3 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl <?= $isCombinedPickup ? 'bg-purple-100 text-purple-700' : ($isPrintingPickup ? 'bg-primary-container text-on-primary-container' : ($isStorePickup ? 'bg-secondary-container text-on-secondary-container' : 'bg-primary-container text-on-primary-container')) ?> flex items-center justify-center shrink-0 shadow-sm">
                <span class="material-symbols-outlined text-xl"><?= $isCombinedPickup ? 'shopping_cart_checkout' : ($isPrintingPickup ? 'print' : ($isStorePickup ? 'storefront' : 'point_of_sale')) ?></span>
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-title-md font-extrabold text-on-surface">
                        Point of Sale (POS)
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold <?= $isCombinedPickup ? 'bg-purple-100 text-purple-800' : ($isPrintingPickup ? 'bg-primary/15 text-primary' : ($isStorePickup ? 'bg-secondary/15 text-secondary' : 'bg-primary/15 text-primary')) ?>">
                        <?= $isCombinedPickup ? 'Combined Pick-up POS' : ($isPrintingPickup ? 'Printing Pick-up POS' : ($isStorePickup ? 'Store Pick-up POS' : 'Walk-in Counter POS')) ?>
                    </span>
                </div>
                <p class="text-[11px] text-on-surface-variant font-medium hidden sm:block">
                    <?= $isCombinedPickup
                        ? 'Combined fulfillment: process product order and printing request in a single checkout without double-charging.'
                        : ($isPrintingPickup
                            ? 'Fulfill customer printing request, verify pick-up, collect counter balance, and record additions.'
                            : ($isStorePickup 
                                ? 'Fulfill online pick-up order, add in-store purchases, and record counter payment without double-charging.' 
                                : 'Quick counter retail billing and instant real-time inventory deduction for walk-in customers.')) ?>
                </p>
            </div>
        </div>

        <!-- Mode Buttons: Primary 3-Action Touch Group + Secondary Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 w-full xl:w-auto">
            <!-- Segmented Primary Action Group: min-h-[48px], 22-24px icons -->
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-1.5 p-1 bg-surface-container-low/80 border border-outline-variant/30 rounded-2xl shadow-inner">
                <!-- 1. Select Ready for Pick-up -->
                <button type="button" 
                        onclick="openPickupSelectorModal()" 
                        class="min-h-[48px] px-3.5 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all flex-1 sm:flex-initial <?= $isStorePickup ? 'bg-secondary text-on-secondary shadow-md ring-2 ring-secondary/30' : 'bg-surface-container-lowest hover:bg-surface-container text-on-surface border border-outline-variant/20 shadow-sm' ?>"
                        title="Select ready-for-pickup product order">
                    <span class="material-symbols-outlined text-[22px] sm:text-[24px] <?= $isStorePickup ? '' : 'text-secondary' ?>">shopping_bag</span>
                    <span>Ready Pick-up</span>
                    <?php if (!empty($pickupOrders)): ?>
                        <span class="px-2 py-0.5 rounded-full text-xs font-extrabold <?= $isStorePickup ? 'bg-on-secondary/20 text-on-secondary' : 'bg-secondary/15 text-secondary' ?>">
                            <?= count($pickupOrders) ?>
                        </span>
                    <?php endif; ?>
                </button>

                <!-- 2. Printing Ready for Pick-up -->
                <button type="button" 
                        onclick="openPrintingSelectorModal()" 
                        class="min-h-[48px] px-3.5 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all flex-1 sm:flex-initial <?= $isPrintingPickup ? 'bg-primary text-on-primary shadow-md ring-2 ring-primary/30' : 'bg-surface-container-lowest hover:bg-surface-container text-on-surface border border-outline-variant/20 shadow-sm' ?>"
                        title="Select ready-for-pickup printing request">
                    <span class="material-symbols-outlined text-[22px] sm:text-[24px] <?= $isPrintingPickup ? '' : 'text-primary' ?>">print</span>
                    <span>Ready Printing</span>
                    <?php if (!empty($printingPickupRequests)): ?>
                        <span class="px-2 py-0.5 rounded-full text-xs font-extrabold <?= $isPrintingPickup ? 'bg-on-primary/20 text-on-primary' : 'bg-primary/15 text-primary' ?>">
                            <?= count($printingPickupRequests) ?>
                        </span>
                    <?php endif; ?>
                </button>

                <!-- 3. Scan Customer QR -->
                <button type="button" 
                        onclick="openQrScannerModal()" 
                        class="min-h-[48px] px-3.5 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all flex-1 sm:flex-initial bg-surface-container-lowest hover:bg-secondary-container/40 text-secondary border border-secondary/30 shadow-sm"
                        title="Scan customer pickup QR code">
                    <span class="material-symbols-outlined text-[22px] sm:text-[24px]">qr_code_scanner</span>
                    <span>Scan QR</span>
                </button>
            </div>

            <!-- Secondary Links: New Walk-in & Back to Orders -->
            <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                <a href="<?= base_url('tenant/pos') ?>" 
                   class="h-10 px-3.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all <?= (!$isStorePickup && !$isPrintingPickup) ? 'bg-primary/15 text-primary border border-primary/30 shadow-sm' : 'bg-surface-container-low hover:bg-surface-container text-on-surface-variant' ?>">
                    <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span>
                    <span>New Walk-in</span>
                </a>

                <a href="<?= base_url('tenant/orders') ?>" 
                   class="h-10 px-3 rounded-xl border border-outline-variant/30 hover:bg-surface-container text-xs font-semibold text-on-surface-variant flex items-center gap-1 transition-all">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    <span>Orders</span>
                </a>
            </div>
        </div>
    </div>

    <?php if ($isCombinedPickup): ?>
        <!-- Combined Pick-up Master Banner -->
        <div class="p-md rounded-2xl bg-gradient-to-r from-purple-500/10 via-primary/10 to-secondary/10 border border-purple-500/30 flex flex-col sm:flex-row sm:items-center justify-between gap-md shadow-sm">
            <div class="flex items-center gap-md">
                <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <span class="material-symbols-outlined text-xl">join_inner</span>
                </div>
                <div>
                    <div class="flex items-center gap-xs flex-wrap">
                        <h3 class="font-extrabold text-sm sm:text-base text-on-surface">Combined Store Pick-up Transaction</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">Order + Printing</span>
                    </div>
                    <p class="text-xs text-on-surface-variant">
                        Fulfilling <strong>Order #<?= esc($order['order_number']) ?></strong> and <strong>Printing Request #<?= esc($printingRequest['request_number']) ?></strong> together in one unified checkout.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-xs shrink-0 self-end sm:self-center">
                <a href="<?= base_url('tenant/pos?order_id=' . $order['id']) ?>" class="text-[11px] font-semibold text-secondary hover:underline px-2.5 py-1 bg-surface-container rounded-lg border border-outline-variant/20">Order Only</a>
                <a href="<?= base_url('tenant/pos?printing_id=' . $printingRequest['id']) ?>" class="text-[11px] font-semibold text-primary hover:underline px-2.5 py-1 bg-surface-container rounded-lg border border-outline-variant/20">Print Only</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($isStorePickup): ?>
        <?php
        $poCustId = (int) ($order['customer_id'] ?? 0);
        $matchingPr = (!$isCombinedPickup && !empty($customerHasPrintingMap[$poCustId])) ? $customerHasPrintingMap[$poCustId][0] : null;
        ?>
        <!-- Modern Pick-up Fulfillment Card -->
        <div class="bg-surface-container-lowest border border-secondary/35 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
            <?php if ($matchingPr): ?>
                <!-- Cross-reference Alert: Customer also has printing request ready -->
                <div class="p-3 rounded-xl bg-purple-50/90 border border-purple-200 text-purple-950 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-purple-200/80 text-purple-800 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[18px]">print</span>
                        </span>
                        <div class="text-xs">
                            <strong class="font-bold text-purple-900">This customer also has a Ready for Pick-up Printing Request!</strong>
                            <span class="block sm:inline sm:ml-1 text-[11px] text-purple-800 font-mono">#<?= esc($matchingPr['request_number']) ?> (<?= esc($matchingPr['file_name'] ?? 'Document.pdf') ?>)</span>
                        </div>
                    </div>
                    <a href="<?= base_url('tenant/pos?order_id=' . $order['id'] . '&printing_id=' . $matchingPr['id']) ?>" 
                       class="px-3 py-1.5 bg-purple-700 hover:bg-purple-800 text-white rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1 shadow-xs shrink-0 active:scale-95 cursor-pointer">
                        <span class="material-symbols-outlined text-[15px]">join_inner</span>
                        <span>Combine Pick-up</span>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Top Header: Reference, Placement Date, Payment Condition & Quick Actions -->
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3.5 border-b border-outline-variant/20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-secondary/15 text-secondary flex items-center justify-center shrink-0 shadow-2xs">
                        <span class="material-symbols-outlined text-[22px]">storefront</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-mono text-base sm:text-lg font-extrabold text-on-surface leading-none">#<?= esc($order['order_number']) ?></h3>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-secondary/15 text-secondary border border-secondary/25">
                                <span class="material-symbols-outlined text-[13px]">check_circle</span>
                                <span>Ready for Pick-up</span>
                            </span>
                        </div>
                        <p class="text-[11px] text-outline font-medium mt-1">Placed on <?= esc(date('M d, Y h:i A', strtotime($order['placed_at']))) ?></p>
                    </div>
                </div>

                <!-- Payment Status Pill & Actions -->
                <div class="flex items-center gap-2 flex-wrap">
                    <?php if ($isOnlinePaid): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-green-500/10 text-green-800 border border-green-500/25">
                            <span class="material-symbols-outlined text-[16px] text-green-700">verified</span>
                            <span>PAID ONLINE (<?= esc(strtoupper($order['payment_method'] ?: 'ONLINE')) ?>)</span>
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500/15 text-amber-900 border border-amber-500/30">
                            <span class="material-symbols-outlined text-[16px] text-amber-700">payments</span>
                            <span>UNPAID • Collect ₱<?= number_format($baseOnlineAmount, 2) ?> at Counter</span>
                        </span>
                    <?php endif; ?>

                    <button type="button" onclick="openPickupSelectorModal()" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-primary hover:bg-primary/10 rounded-xl border border-primary/25 transition-colors cursor-pointer" title="Select another pick-up order">
                        <span class="material-symbols-outlined text-[16px]">swap_horiz</span>
                        <span>Change Order</span>
                    </button>
                    <a href="<?= base_url('tenant/pos') ?>" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-error hover:bg-error/10 rounded-xl border border-error/25 transition-colors cursor-pointer" title="Exit pick-up mode to regular walk-in">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                        <span>Exit</span>
                    </a>
                </div>
            </div>

            <!-- 2-Column Content Grid: Customer Profile & Guidance (Left) + Itemized Handover Checklist (Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                
                <!-- Left: Customer Profile & Cashier Guidance (5 cols) -->
                <div class="lg:col-span-5 space-y-3">
                    <!-- Customer Card -->
                    <div class="p-3.5 bg-surface-container-low rounded-xl border border-outline-variant/25 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center text-sm font-bold shrink-0 shadow-2xs <?= $customerProfileImg !== '' ? 'relative overflow-hidden' : '' ?>">
                            <span><?= esc($customerInitials) ?></span>
                            <?php if ($customerProfileImg !== ''): ?>
                                <img class="absolute inset-0 w-full h-full object-cover" src="<?= esc(base_url($customerProfileImg)) ?>" alt="<?= esc($customerFullName) ?> avatar" loading="lazy" onerror="this.remove();">
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="text-[10px] text-outline font-bold uppercase tracking-wider block">Customer</span>
                            <h4 class="font-extrabold text-sm sm:text-base text-on-surface truncate leading-tight"><?= esc($customerFullName ?: 'Online Customer') ?></h4>
                            <div class="flex items-center gap-2 mt-1 text-xs text-on-surface-variant flex-wrap">
                                <?php if ($customerPhone !== ''): ?>
                                    <a href="tel:<?= esc($customerPhone) ?>" class="inline-flex items-center gap-0.5 hover:text-primary transition-colors font-mono font-medium">
                                        <span class="material-symbols-outlined text-[14px]">phone</span>
                                        <span><?= esc($customerPhone) ?></span>
                                    </a>
                                <?php else: ?>
                                    <span class="text-outline text-[11px]">No phone on file</span>
                                <?php endif; ?>
                                <span class="text-outline">•</span>
                                <span class="text-[11px] font-semibold text-secondary">Store Pick-up</span>
                            </div>
                        </div>
                    </div>

                    <!-- Cashier Guidance Alert Box -->
                    <div class="p-3.5 rounded-xl text-xs <?= $isOnlinePaid ? 'bg-green-50/90 text-green-950 border border-green-300/70' : 'bg-amber-50/90 text-amber-950 border border-amber-300/70' ?>">
                        <div class="flex items-start gap-2.5">
                            <span class="material-symbols-outlined text-[20px] shrink-0 mt-0.5 <?= $isOnlinePaid ? 'text-green-700' : 'text-amber-700' ?>">
                                <?= $isOnlinePaid ? 'check_circle' : 'info' ?>
                            </span>
                            <div class="space-y-0.5">
                                <p class="font-bold text-xs sm:text-sm <?= $isOnlinePaid ? 'text-green-900' : 'text-amber-900' ?>">
                                    <?= $isOnlinePaid ? 'Order Paid Online • Hand Over Products' : 'Payment Required Upon Pick-up' ?>
                                </p>
                                <p class="text-[11px] leading-relaxed text-on-surface-variant">
                                    <?php if ($isOnlinePaid): ?>
                                        Customer already paid <strong>₱<?= number_format($baseOnlineAmount, 2) ?></strong> via <?= esc(strtoupper($order['payment_method'] ?: 'ONLINE')) ?>. Hand over items and <strong>only collect payment for additional walk-in items</strong> added to cart.
                                    <?php else: ?>
                                        Original order is unpaid. <strong>Collect ₱<?= number_format($baseOnlineAmount, 2) ?></strong> at register, plus any additional items selected below.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Itemized Handover Checklist (7 cols) -->
                <div class="lg:col-span-7 bg-surface-container-low rounded-xl border border-outline-variant/25 p-3.5 space-y-2.5">
                    <div class="flex items-center justify-between border-b border-outline-variant/20 pb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] text-secondary">inventory_2</span>
                            <span>Items to Hand Over (<?= count($orderItems) ?> item<?= count($orderItems) === 1 ? '' : 's' ?>)</span>
                        </span>
                        <span class="font-mono text-xs font-bold text-on-surface">
                            Order Subtotal: ₱<?= number_format($baseOnlineAmount, 2) ?>
                        </span>
                    </div>

                    <!-- Items List -->
                    <div class="space-y-2 max-h-[220px] overflow-y-auto pr-1">
                        <?php if (!empty($orderItems)): ?>
                            <?php foreach ($orderItems as $oit): ?>
                                <?php if (empty($oit['is_pos_addition'])): ?>
                                    <div class="flex items-center justify-between gap-3 p-2.5 bg-surface-container-lowest rounded-xl border border-outline-variant/20 shadow-2xs hover:border-secondary/30 transition-colors">
                                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                            <?php $itemImg = trim((string) ($oit['gallery_image'] ?? '')); ?>
                                            <?php if ($itemImg !== ''): ?>
                                                <img src="<?= esc(base_url($itemImg)) ?>" alt="<?= esc($oit['product_name']) ?>" class="w-11 h-11 object-cover rounded-lg border border-outline-variant/25 shrink-0 bg-surface-container" loading="lazy" onerror="this.remove();">
                                            <?php else: ?>
                                                <div class="w-11 h-11 rounded-lg bg-surface-container flex items-center justify-center text-outline shrink-0">
                                                    <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="min-w-0 flex-1">
                                                <h5 class="font-extrabold text-xs sm:text-sm text-on-surface truncate leading-tight"><?= esc($oit['product_name']) ?></h5>
                                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                                    <?php if (!empty($oit['variant_label'])): ?>
                                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-primary/10 text-primary border border-primary/20">
                                                            <?= esc($oit['variant_label']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="text-[11px] text-outline font-mono">₱<?= number_format((float) $oit['unit_price'], 2) ?> each</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="inline-flex items-center justify-center px-2 py-0.5 bg-secondary-container/50 text-secondary text-xs font-extrabold rounded-md font-mono">
                                                <?= (int) $oit['quantity'] ?>×
                                            </span>
                                            <p class="font-mono text-xs sm:text-sm font-bold text-on-surface mt-0.5">
                                                ₱<?= number_format((float) $oit['line_total'], 2) ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-xs text-outline text-center py-4">No items listed for this order.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    <?php endif; ?>

    <?php if ($isPrintingPickup): ?>
        <?php
        $prCustId = (int) ($printingRequest['customer_id'] ?? 0);
        $matchingOrder = (!$isCombinedPickup && !empty($customerHasOrderMap[$prCustId])) ? $customerHasOrderMap[$prCustId][0] : null;
        $prCustomerName = trim(($printingRequest['first_name'] ?? '') . ' ' . ($printingRequest['last_name'] ?? ''));
        $prTotal = (float) ($printingRequest['total_price'] ?? 0);
        $prPaid = (float) $printingOnlinePaid;
        $prDue = (float) $printingRemainingBalance;
        $prSpecs = [
            ((int) ($printingRequest['page_count'] ?? 1)) . ' pages',
            ((int) ($printingRequest['copies'] ?? 1)) . ' cop' . (((int) ($printingRequest['copies'] ?? 1)) === 1 ? 'y' : 'ies'),
            strtoupper((string) ($printingRequest['color_mode'] ?? 'BW')),
            esc($printingRequest['paper_size'] ?? 'Letter'),
        ];
        if (!empty($printingRequest['binding_option']) && $printingRequest['binding_option'] !== 'none') {
            $prSpecs[] = ucfirst($printingRequest['binding_option']) . ' Binding';
        }
        ?>
        <!-- Online Printing Request Card (Pickup Mode) -->
        <div class="bg-surface-container-lowest border border-primary/30 rounded-2xl p-lg shadow-sm">
            <?php if ($matchingOrder): ?>
                <!-- Cross-reference Alert: Customer also has product order ready -->
                <div class="mb-md p-md rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-900 flex flex-col sm:flex-row sm:items-center justify-between gap-sm">
                    <div class="flex items-center gap-sm">
                        <span class="material-symbols-outlined text-indigo-700 text-2xl">shopping_bag</span>
                        <div class="text-xs sm:text-sm">
                            <strong>This customer also has a Ready for Pick-up Product Order!</strong>
                            <span class="block sm:inline sm:ml-1 text-xs text-indigo-800 font-mono">#<?= esc($matchingOrder['order_number']) ?></span>
                        </div>
                    </div>
                    <a href="<?= base_url('tenant/pos?order_id=' . $matchingOrder['id'] . '&printing_id=' . $printingRequest['id']) ?>" 
                       class="px-3 py-1.5 bg-indigo-700 hover:bg-indigo-800 text-white rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1 shadow-sm shrink-0">
                        <span class="material-symbols-outlined text-[16px]">join_inner</span>
                        <span>Combine Pick-up</span>
                    </a>
                </div>
            <?php endif; ?>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-md border-b border-outline-variant/20 pb-md mb-md">
                <div class="flex items-center gap-sm">
                    <div class="p-2 bg-primary-container/40 text-primary rounded-xl">
                        <span class="material-symbols-outlined text-xl">print</span>
                    </div>
                    <div>
                        <span class="text-xs text-primary font-bold uppercase tracking-wider"><?= esc($prCustomerName ?: 'Online Customer') ?></span>
                        <h2 class="text-title-md sm:text-title-lg font-extrabold text-on-surface leading-tight">
                            <?= esc($printingRequest['file_name'] ?? 'Document.pdf') ?>
                        </h2>
                        <div class="text-xs text-on-surface-variant font-mono mt-0.5">
                            Printing Request Reference #<?= esc($printingRequest['request_number']) ?>
                            <span class="text-outline mx-1">•</span>
                            <span class="text-xs text-on-surface-variant"><?= implode(' • ', $prSpecs) ?></span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-sm">
                    <?php if ($prDue <= 0 && $prTotal > 0): ?>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">verified</span>
                            <span>FULLY PAID ONLINE (₱<?= number_format($prPaid, 2) ?>)</span>
                        </span>
                    <?php elseif ($prPaid > 0): ?>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">verified</span>
                            <span>DOWN PAYMENT PAID (₱<?= number_format($prPaid, 2) ?>)</span>
                        </span>
                    <?php else: ?>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">warning</span>
                            <span>UNPAID ONLINE (Collect ₱<?= number_format($prTotal, 2) ?> at counter)</span>
                        </span>
                    <?php endif; ?>
                    <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-surface-container text-on-surface-variant">
                        Placed <?= esc(date('M d, Y h:i A', strtotime($printingRequest['created_at']))) ?>
                    </span>
                    <button type="button" onclick="openPrintingSelectorModal()" class="text-xs font-bold text-primary hover:underline ml-xs">
                        Change Request
                    </button>
                </div>
            </div>

            <!-- Notice Banner -->
            <div class="mb-md p-md rounded-xl text-body-md font-medium flex items-center gap-sm <?= $prDue <= 0 ? 'bg-green-50 text-green-900 border border-green-200' : 'bg-amber-50 text-amber-900 border border-amber-200' ?>">
                <span class="material-symbols-outlined shrink-0 text-xl <?= $prDue <= 0 ? 'text-green-600' : 'text-amber-600' ?>"><?= $prDue <= 0 ? 'check_circle' : 'info' ?></span>
                <div class="text-xs sm:text-sm">
                    <?php if ($prDue <= 0 && $prTotal > 0): ?>
                        <strong>ORIGINAL PRINTING ORDER: ₱<?= number_format($prTotal, 2) ?> FULLY PAID ONLINE.</strong> Customer already paid 100% online. <strong>NEVER charge this original ₱<?= number_format($prTotal, 2) ?> again.</strong> Only collect payment for any additional in-store items selected below.
                    <?php elseif ($prPaid > 0): ?>
                        <strong>ONLINE DOWN PAYMENT: ₱<?= number_format($prPaid, 2) ?> PAID.</strong> Customer already paid down payment online. <strong>NEVER charge the down payment again.</strong> Collect remaining balance of <strong>₱<?= number_format($prDue, 2) ?></strong> plus any additional in-store items at pickup.
                    <?php else: ?>
                        <strong>PRINTING ORDER IS UNPAID:</strong> Collect ₱<?= number_format($prTotal, 2) ?> plus any additional in-store items at pickup.
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-md text-body-md">
                <div class="p-sm bg-surface-container-low rounded-xl">
                    <span class="text-xs text-on-surface-variant block font-medium">Customer</span>
                    <span class="font-bold text-on-surface"><?= esc($prCustomerName ?: 'Online Customer') ?></span>
                    <?php if (!empty($printingRequest['phone'])): ?>
                        <span class="text-xs text-outline block mt-0.5"><?= esc($printingRequest['phone']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="p-sm bg-surface-container-low rounded-xl">
                    <span class="text-xs text-on-surface-variant block font-medium">Total Print Price</span>
                    <span class="font-bold font-mono text-on-surface">₱<?= number_format($prTotal, 2) ?></span>
                    <span class="text-xs text-outline block mt-0.5"><?= esc(humanize_status($printingRequest['status'])) ?></span>
                </div>

                <div class="p-sm bg-surface-container-low rounded-xl">
                    <span class="text-xs text-on-surface-variant block font-medium">Online Paid Credit</span>
                    <span class="font-bold font-mono text-green-700">₱<?= number_format($prPaid, 2) ?></span>
                    <span class="text-xs <?= $prPaid > 0 ? 'text-green-700 font-bold' : 'text-outline' ?> block mt-0.5">
                        <?= $prPaid > 0 ? '✓ Paid Online' : 'None' ?>
                    </span>
                </div>

                <div class="p-sm bg-surface-container-low rounded-xl">
                    <span class="text-xs text-on-surface-variant block font-medium">Remaining Print Balance Due</span>
                    <span class="font-bold font-mono <?= $prDue > 0 ? 'text-amber-800 font-extrabold' : 'text-green-700' ?>">₱<?= number_format($prDue, 2) ?></span>
                    <span class="text-xs <?= $prDue > 0 ? 'text-amber-800 font-semibold' : 'text-green-700' ?> block mt-0.5">
                        <?= $prDue > 0 ? 'Collect at counter' : 'Fully Settled' ?>
                    </span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- POS Main Interactive Area -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 sm:gap-4 items-start">
        
        <!-- Left Side: Product Search, Category Chips & Product Cards Grid (7 cols on lg, 8 cols on xl) -->
        <div class="lg:col-span-7 xl:col-span-8 space-y-3">
            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3.5 sm:p-4 shadow-xs space-y-3">
                
                <!-- Search Header & Shortcuts Bar -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-primary text-[18px]">manage_search</span>
                        <h2 class="text-xs sm:text-sm font-bold text-on-surface">Find Products</h2>
                    </div>
                    <span class="text-[11px] text-outline hidden sm:inline-block">Search by name, brand, or SKU</span>
                </div>

                <div class="relative">
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined absolute left-3.5 text-outline text-[20px] pointer-events-none">search</span>
                        <input type="text" 
                               id="posProductSearch" 
                               placeholder="Search products by name, brand, or SKU... (Press '/' to search)" 
                               autocomplete="off"
                               class="w-full pl-11 pr-20 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl text-body-md text-on-surface placeholder:text-outline/70 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                        <div class="absolute right-3 flex items-center gap-1">
                            <kbd class="hidden sm:inline-block px-1.5 py-0.5 text-[10px] font-mono font-bold text-outline bg-surface-container border border-outline-variant/40 rounded shadow-2xs">/</kbd>
                            <button type="button" id="posClearSearch" class="hidden text-outline hover:text-on-surface p-1 rounded-lg hover:bg-surface-container">
                                <span class="material-symbols-outlined text-[18px]">close</span>
                            </button>
                        </div>
                    </div>

                    <!-- Live Results Dropdown -->
                    <div id="posSearchResults" class="hidden absolute left-0 right-0 top-full mt-1 bg-surface-container-lowest border border-outline-variant/40 rounded-xl shadow-xl z-40 max-h-80 overflow-y-auto divide-y divide-outline-variant/20 custom-scrollbar">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <!-- Category Filter Chips -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 pt-0.5 custom-scrollbar text-xs">
                    <button type="button" 
                            class="pos-cat-chip px-3 py-1.5 rounded-full font-bold transition-all shrink-0 bg-primary text-on-primary shadow-xs" 
                            data-category="">
                        All Categories
                    </button>
                    <?php foreach ($categories as $cat): ?>
                        <button type="button" 
                                class="pos-cat-chip px-3 py-1.5 rounded-full font-medium transition-all shrink-0 bg-surface-container-low text-on-surface-variant hover:bg-surface-container border border-outline-variant/30" 
                                data-category="<?= (int) $cat['id'] ?>">
                            <?= esc($cat['name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Product Catalog Header & Quick Grid -->
                <div class="pt-2 border-t border-outline-variant/20">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-primary">grid_view</span>
                            <h3 class="text-label-sm font-bold text-on-surface uppercase tracking-wider">Product Catalog</h3>
                            <span id="posActiveCategoryText" class="text-xs text-outline font-medium hidden sm:inline-block">• All Products</span>
                        </div>
                        <span id="posCatalogHint" class="text-xs text-outline">Click card to add</span>
                    </div>

                    <!-- Modern Product Cards Grid -->
                    <div id="posQuickCatalog" class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2.5 max-h-[calc(100vh-270px)] min-h-[420px] overflow-y-auto pr-1 custom-scrollbar">
                        <!-- Populated dynamically via JS product cards -->
                        <div class="col-span-full py-xl text-center text-on-surface-variant/70 flex flex-col items-center gap-xs">
                            <span class="material-symbols-outlined text-3xl animate-spin text-primary">progress_activity</span>
                            <span class="text-xs font-medium">Loading store products...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Sticky Cashier Register Panel (5 cols on lg, 4 cols on xl) -->
        <div class="lg:col-span-5 xl:col-span-4 space-y-3 lg:sticky lg:top-4">
            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3.5 sm:p-4 shadow-sm space-y-3">
                
                <!-- Register Header -->
                <div class="flex items-center justify-between border-b border-outline-variant/20 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]">receipt_long</span>
                        <h2 class="text-title-md font-bold text-on-surface">
                            <?= $isCombinedPickup ? 'Combined Pick-up Summary' : ($isPrintingPickup ? 'Printing Pick-up Summary' : ($isStorePickup ? 'Sale & Pick-up Summary' : 'Counter Cart')) ?>
                        </h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="posCartCountBadge" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-surface-container text-on-surface-variant font-mono">0 items</span>
                        <button type="button" onclick="posClearAllCart()" class="text-xs font-semibold text-outline hover:text-error transition-colors px-1.5 py-0.5 rounded hover:bg-error-container/20" title="Clear all items in cart">
                            Clear
                        </button>
                    </div>
                </div>

                <?php if ($isCombinedPickup): ?>
                    <!-- Section: COMBINED PICK-UP (ORDER + PRINTING) -->
                    <!-- 1. Original Order Box -->
                    <div class="border border-secondary/30 rounded-xl p-3 bg-secondary-container/10 space-y-2">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-secondary flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">shopping_bag</span>
                                Order #<?= esc($order['order_number']) ?>
                            </h4>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold <?= $isOnlinePaid ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' ?>">
                                <?= $isOnlinePaid ? 'PAID ONLINE' : 'UNPAID' ?>
                            </span>
                        </div>
                        <div class="border border-outline-variant/20 rounded-lg overflow-hidden bg-surface-container-lowest divide-y divide-outline-variant/10 text-xs">
                            <?php if (!empty($orderItems)): ?>
                                <?php foreach ($orderItems as $oit): ?>
                                    <?php if (empty($oit['is_pos_addition'])): ?>
                                        <div class="p-1.5 px-2.5 flex items-center justify-between">
                                            <div class="min-w-0 flex-1 pr-2">
                                                <p class="font-semibold text-on-surface truncate"><?= esc($oit['product_name']) ?></p>
                                                <p class="text-[10px] text-outline font-mono">Qty: <?= (int) $oit['quantity'] ?> × ₱<?= number_format((float) $oit['unit_price'], 2) ?></p>
                                            </div>
                                            <span class="font-mono font-bold text-on-surface">₱<?= number_format((float) $oit['line_total'], 2) ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="flex justify-between items-center text-xs font-bold pt-0.5">
                            <span class="text-on-surface-variant">Order Total:</span>
                            <span class="font-mono <?= $isOnlinePaid ? 'text-green-700' : 'text-on-surface' ?>">
                                ₱<?= number_format($baseOnlineAmount, 2) ?> <?= $isOnlinePaid ? '(PAID)' : '' ?>
                            </span>
                        </div>
                    </div>

                    <!-- 2. Original Printing Box -->
                    <div class="border border-primary/30 rounded-xl p-3 bg-primary-container/10 space-y-2">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-primary flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">print</span>
                                Printing #<?= esc($printingRequest['request_number']) ?>
                            </h4>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold <?= $printingRemainingBalance <= 0 ? 'bg-green-100 text-green-800' : ($printingOnlinePaid > 0 ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') ?>">
                                <?= $printingRemainingBalance <= 0 ? 'PAID ONLINE' : ($printingOnlinePaid > 0 ? 'DOWN PAYMENT PAID' : 'UNPAID') ?>
                            </span>
                        </div>
                        <div class="border border-outline-variant/20 rounded-lg p-2 bg-surface-container-lowest text-xs space-y-1">
                            <div class="flex justify-between items-start">
                                <p class="font-semibold text-on-surface truncate max-w-[200px]"><?= esc($printingRequest['file_name'] ?? 'Document') ?></p>
                                <span class="font-mono font-bold text-on-surface">₱<?= number_format((float) ($printingRequest['total_price'] ?? 0), 2) ?></span>
                            </div>
                            <p class="text-[10px] text-outline font-mono">
                                <?= (int) ($printingRequest['page_count'] ?? 1) ?> pgs • <?= (int) ($printingRequest['copies'] ?? 1) ?> copies • <?= strtoupper((string) ($printingRequest['color_mode'] ?? 'BW')) ?>
                            </p>
                        </div>
                        <div class="flex justify-between items-center text-xs font-bold pt-0.5">
                            <span class="text-on-surface-variant">Print Balance Due:</span>
                            <span class="font-mono <?= $printingRemainingBalance > 0 ? 'text-amber-800 font-extrabold' : 'text-green-700' ?>">
                                ₱<?= number_format($printingRemainingBalance, 2) ?> <?= $printingRemainingBalance <= 0 ? '(PAID)' : '' ?>
                            </span>
                        </div>
                    </div>

                <?php elseif ($isPrintingPickup): ?>
                    <!-- Section: ORIGINAL PRINTING REQUEST -->
                    <div class="border border-outline-variant/30 rounded-xl p-3 bg-surface-container-low/40 space-y-2">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px] text-primary">print</span>
                                Original Printing Request
                            </h4>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold <?= $printingRemainingBalance <= 0 ? 'bg-green-100 text-green-800' : ($printingOnlinePaid > 0 ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') ?>">
                                <?= $printingRemainingBalance <= 0 ? 'PAID ONLINE' : ($printingOnlinePaid > 0 ? 'DOWN PAYMENT PAID' : 'UNPAID') ?>
                            </span>
                        </div>

                        <!-- Printing Details Box -->
                        <div class="border border-outline-variant/20 rounded-lg p-2 bg-surface-container-lowest text-xs space-y-1">
                            <div class="flex justify-between items-start">
                                <p class="font-semibold text-on-surface truncate max-w-[200px]"><?= esc($printingRequest['file_name'] ?? 'Document') ?></p>
                                <span class="font-mono font-bold text-on-surface">₱<?= number_format((float) ($printingRequest['total_price'] ?? 0), 2) ?></span>
                            </div>
                            <p class="text-[10px] text-outline font-mono">
                                #<?= esc($printingRequest['request_number']) ?> • <?= (int) ($printingRequest['page_count'] ?? 1) ?> pgs • <?= (int) ($printingRequest['copies'] ?? 1) ?> copies • <?= strtoupper((string) ($printingRequest['color_mode'] ?? 'BW')) ?>
                            </p>
                        </div>

                        <div class="flex justify-between items-center text-xs pt-0.5">
                            <span class="text-on-surface-variant font-medium">Online Paid Credit:</span>
                            <span class="font-mono text-green-700 font-bold">
                                -₱<?= number_format($printingOnlinePaid, 2) ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-xs font-bold">
                            <span class="text-on-surface-variant">Remaining Print Balance:</span>
                            <span class="font-mono <?= $printingRemainingBalance > 0 ? 'text-amber-800 font-extrabold' : 'text-green-700' ?>">
                                ₱<?= number_format($printingRemainingBalance, 2) ?>
                            </span>
                        </div>
                    </div>
                <?php elseif ($isStorePickup): ?>
                    <!-- Section: ORIGINAL ONLINE ORDER -->
                    <div class="border border-outline-variant/30 rounded-xl p-3 bg-surface-container-low/40 space-y-2">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px] text-primary">shopping_bag</span>
                                Original Online Order
                            </h4>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold <?= $isOnlinePaid ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' ?>">
                                <?= $isOnlinePaid ? 'PAID ONLINE' : 'UNPAID' ?>
                            </span>
                        </div>

                        <!-- Original Online Items List -->
                        <div class="border border-outline-variant/20 rounded-lg overflow-hidden bg-surface-container-lowest divide-y divide-outline-variant/10 text-xs">
                            <?php if (!empty($orderItems)): ?>
                                <?php foreach ($orderItems as $oit): ?>
                                    <?php if (empty($oit['is_pos_addition'])): ?>
                                        <div class="p-1.5 px-2.5 flex items-center justify-between">
                                            <div class="min-w-0 flex-1 pr-2">
                                                <p class="font-semibold text-on-surface truncate"><?= esc($oit['product_name']) ?></p>
                                                <p class="text-[10px] text-outline font-mono">Qty: <?= (int) $oit['quantity'] ?> × ₱<?= number_format((float) $oit['unit_price'], 2) ?></p>
                                            </div>
                                            <span class="font-mono font-bold text-on-surface">₱<?= number_format((float) $oit['line_total'], 2) ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-2 text-center text-outline">Items details loaded from online order.</div>
                            <?php endif; ?>
                        </div>

                        <div class="flex justify-between items-center text-xs font-bold pt-0.5">
                            <span class="text-on-surface-variant">Original Online Total:</span>
                            <span class="font-mono <?= $isOnlinePaid ? 'text-green-700' : 'text-on-surface' ?>">
                                ₱<?= number_format($baseOnlineAmount, 2) ?> <?= $isOnlinePaid ? '(PAID)' : '' ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Section: ADDITIONAL IN-STORE ITEMS (or Walk-in Items) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px] text-secondary">add_shopping_cart</span>
                            <?= ($isStorePickup || $isPrintingPickup) ? 'Additional POS Items' : 'Sale Items' ?>
                        </h4>
                        <span class="text-[10px] text-outline" id="posItemsHintText"><?= ($isStorePickup || $isPrintingPickup) ? 'Add items at counter' : 'Selected items' ?></span>
                    </div>

                    <!-- Cart Items List with Quantity Controls and Remove -->
                    <div class="space-y-1.5 min-h-[110px] max-h-[220px] overflow-y-auto custom-scrollbar pr-0.5" id="posCartList">
                        <div id="posEmptyCartNotice" class="py-6 text-center text-on-surface-variant/70 flex flex-col items-center gap-1 border border-dashed border-outline-variant/40 rounded-xl">
                            <span class="material-symbols-outlined text-2xl text-outline-variant">remove_shopping_cart</span>
                            <span class="text-xs font-medium px-3 text-outline">
                                <?= ($isStorePickup || $isPrintingPickup)
                                    ? 'No additional in-store items. (You can complete pick-up without adding any extra items)' 
                                    : 'No items in sale yet. Click products from the catalog to add.' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Walk-in Customer Name / Note Field -->
                <?php if (!$isStorePickup && !$isPrintingPickup): ?>
                    <div class="pt-0.5">
                        <label for="posCustomerName" class="text-[11px] font-bold text-on-surface uppercase tracking-wider block mb-1">
                            Customer Name / Note (Optional)
                        </label>
                        <input type="text" id="posCustomerName" placeholder="e.g. Walk-in Student, Cash Customer" class="w-full px-3 py-1.5 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs font-medium focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                    </div>
                <?php endif; ?>

                <!-- Settlement Breakdown & Amount Due Card -->
                <div class="pt-2 border-t border-outline-variant/20 space-y-2">
                    <?php if ($isCombinedPickup): ?>
                        <?php 
                        $orderCounterDue = $isOnlinePaid ? 0.0 : $baseOnlineAmount;
                        $combinedBaseDue = $orderCounterDue + $printingRemainingBalance;
                        ?>
                        <div class="space-y-1 text-xs text-on-surface-variant">
                            <div class="flex justify-between items-center">
                                <span>Product Order Due:</span>
                                <span class="font-mono font-bold <?= $orderCounterDue > 0 ? 'text-amber-800' : 'text-green-700' ?>">
                                    ₱<?= number_format($orderCounterDue, 2) ?> <?= $isOnlinePaid ? '(Paid Online)' : '' ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Printing Balance Due:</span>
                                <span class="font-mono font-bold <?= $printingRemainingBalance > 0 ? 'text-amber-800' : 'text-green-700' ?>">
                                    ₱<?= number_format($printingRemainingBalance, 2) ?> <?= $printingRemainingBalance <= 0 ? '(Paid Online)' : '' ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Additional POS Purchase:</span>
                                <span class="font-mono font-bold text-secondary" id="posAdditionalSubtotal">₱0.00</span>
                            </div>
                        </div>
                        <!-- Combined Amount to Collect Now -->
                        <div class="p-3 rounded-xl bg-purple-50 border border-purple-200">
                            <span class="text-xs font-bold text-purple-900 block mb-1">Combined Amount to Collect:</span>
                            <div class="flex items-baseline justify-between">
                                <span class="text-xs text-purple-700 font-medium">Total Collectible</span>
                                <span class="font-mono text-2xl font-black text-purple-900" id="posAmountDueNow">
                                    ₱<?= number_format($combinedBaseDue, 2) ?>
                                </span>
                            </div>
                        </div>
                    <?php elseif ($isPrintingPickup): ?>
                        <div class="space-y-1 text-xs text-on-surface-variant">
                            <div class="flex justify-between items-center">
                                <span>Print Request Total:</span>
                                <span class="font-mono font-medium">₱<?= number_format((float) ($printingRequest['total_price'] ?? 0), 2) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Online Paid Credit:</span>
                                <span class="font-mono font-bold text-green-700">-₱<?= number_format($printingOnlinePaid, 2) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Remaining Print Balance:</span>
                                <span class="font-mono font-bold text-on-surface" id="posPrintingBalance">₱<?= number_format($printingRemainingBalance, 2) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Additional POS Purchase:</span>
                                <span class="font-mono font-bold text-secondary" id="posAdditionalSubtotal">₱0.00</span>
                            </div>
                        </div>
                        <!-- Amount to collect now: remaining balance + in-store additions -->
                        <div class="p-3 rounded-xl bg-primary-container/30 border border-primary/30">
                            <span class="text-xs font-bold text-on-surface block mb-1">Amount to collect now:</span>
                            <div class="flex items-baseline justify-between">
                                <span class="text-xs text-on-surface-variant font-medium">Total Collectible</span>
                                <span class="font-mono text-2xl font-black text-primary" id="posAmountDueNow">
                                    ₱<?= number_format($printingRemainingBalance, 2) ?>
                                </span>
                            </div>
                        </div>
                    <?php elseif ($isStorePickup): ?>
                        <div class="space-y-1 text-xs text-on-surface-variant">
                            <div class="flex justify-between items-center">
                                <span>Original Online Order:</span>
                                <span class="font-mono font-medium">₱<?= number_format($baseOnlineAmount, 2) ?> <?= $isOnlinePaid ? '<span class="text-green-700 font-bold">(PAID)</span>' : '' ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Additional POS Purchase:</span>
                                <span class="font-mono font-bold text-secondary" id="posAdditionalSubtotal">₱0.00</span>
                            </div>
                            <div class="flex justify-between items-center pt-1 border-t border-outline-variant/10">
                                <span>Combined Order Total:</span>
                                <span class="font-mono font-bold text-on-surface" id="posUpdatedGrandTotal">₱<?= number_format($originalTotal, 2) ?></span>
                            </div>
                        </div>
                        <!-- Amount to collect now: MUST NEVER CHARGE ONLINE PAID AMOUNT AGAIN -->
                        <div class="p-3 rounded-xl bg-secondary-container/30 border border-secondary/30">
                            <span class="text-xs font-bold text-on-surface block mb-1">Amount to collect now:</span>
                            <div class="flex items-baseline justify-between">
                                <span class="text-xs text-on-surface-variant font-medium">Total Collectible</span>
                                <span class="font-mono text-2xl font-black text-secondary" id="posAmountDueNow">
                                    ₱<?= number_format($isOnlinePaid ? 0.00 : $originalTotal, 2) ?>
                                </span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex justify-between items-center text-xs text-on-surface-variant">
                            <span>Subtotal:</span>
                            <span class="font-mono font-medium" id="posWalkinSubtotal">₱0.00</span>
                        </div>
                        <div class="p-3 rounded-xl bg-primary/10 border border-primary/20">
                            <span class="text-xs font-bold text-primary block mb-1">Total Amount Due:</span>
                            <div class="flex items-baseline justify-between">
                                <span class="text-xs text-on-surface-variant font-medium">Due Now</span>
                                <span class="font-mono text-2xl font-black text-primary" id="posAmountDueNow">₱0.00</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Payment Method Selector -->
                <div class="pt-2 border-t border-outline-variant/20 space-y-2">
                    <label class="text-[11px] font-bold text-on-surface uppercase tracking-wider block">
                        Payment Method
                    </label>
                    <div class="grid grid-cols-3 gap-1.5">
                        <label class="cursor-pointer">
                            <input type="radio" name="pos_payment_method" value="cash" class="peer sr-only" checked>
                            <span class="flex items-center justify-center gap-1 py-2 px-2 rounded-xl border border-outline-variant/40 bg-surface-container-low text-xs font-bold text-on-surface transition-all peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary shadow-xs">
                                <span class="material-symbols-outlined text-[16px]">payments</span>
                                Cash
                            </span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="pos_payment_method" value="gcash" class="peer sr-only">
                            <span class="flex items-center justify-center gap-1 py-2 px-2 rounded-xl border border-outline-variant/40 bg-surface-container-low text-xs font-bold text-on-surface transition-all peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary shadow-xs">
                                <span class="material-symbols-outlined text-[16px]">account_balance_wallet</span>
                                GCash
                            </span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="pos_payment_method" value="card" class="peer sr-only">
                            <span class="flex items-center justify-center gap-1 py-2 px-2 rounded-xl border border-outline-variant/40 bg-surface-container-low text-xs font-bold text-on-surface transition-all peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary shadow-xs">
                                <span class="material-symbols-outlined text-[16px]">credit_card</span>
                                Card
                            </span>
                        </label>
                    </div>

                    <!-- Cash Calculation Container -->
                    <div id="posCashCalculationContainer" class="p-3 bg-surface-container-low border border-outline-variant/30 rounded-xl space-y-2 transition-all">
                        <div class="flex items-center justify-between gap-2">
                            <label for="posCashTendered" class="text-xs font-bold text-on-surface shrink-0">Cash Tendered (₱):</label>
                            <input type="number" id="posCashTendered" step="any" min="0" placeholder="0.00" class="w-28 sm:w-32 py-1.5 px-2 text-right font-mono font-bold text-sm bg-surface-container-lowest border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary">
                        </div>

                        <!-- Quick Cash Denominations -->
                        <div class="flex flex-wrap items-center gap-1 justify-end">
                            <button type="button" class="pos-denom-btn px-2 py-1 text-[11px] font-semibold bg-surface-container-lowest border border-outline-variant/40 rounded-md hover:bg-surface-container active:scale-95 transition-all" data-val="exact">Exact</button>
                            <button type="button" class="pos-denom-btn px-2 py-1 text-[11px] font-semibold bg-surface-container-lowest border border-outline-variant/40 rounded-md hover:bg-surface-container active:scale-95 transition-all" data-val="20">+20</button>
                            <button type="button" class="pos-denom-btn px-2 py-1 text-[11px] font-semibold bg-surface-container-lowest border border-outline-variant/40 rounded-md hover:bg-surface-container active:scale-95 transition-all" data-val="50">+50</button>
                            <button type="button" class="pos-denom-btn px-2 py-1 text-[11px] font-semibold bg-surface-container-lowest border border-outline-variant/40 rounded-md hover:bg-surface-container active:scale-95 transition-all" data-val="100">+100</button>
                            <button type="button" class="pos-denom-btn px-2 py-1 text-[11px] font-semibold bg-surface-container-lowest border border-outline-variant/40 rounded-md hover:bg-surface-container active:scale-95 transition-all" data-val="500">+500</button>
                            <button type="button" class="pos-denom-btn px-2 py-1 text-[11px] font-semibold bg-surface-container-lowest border border-outline-variant/40 rounded-md hover:bg-surface-container active:scale-95 transition-all" data-val="1000">+1000</button>
                        </div>

                        <div class="flex justify-between items-center text-xs font-bold border-t border-outline-variant/20 pt-1.5">
                            <span class="text-on-surface-variant">Change Due:</span>
                            <span id="posChangeDueText" class="font-mono text-sm font-bold text-green-700">₱0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Error Notice Container -->
                <div id="posErrorMessage" class="hidden p-2.5 bg-error-container/30 border border-error/20 text-on-error-container rounded-xl text-xs font-medium"></div>

                <!-- Complete Button -->
                <button type="button" id="posSubmitBtn" class="w-full min-h-[48px] py-3 bg-primary text-on-primary rounded-xl font-button text-sm font-bold shadow-md hover:shadow-lg hover:bg-primary/90 active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">check_circle</span>
                    <span id="posSubmitBtnLabel"><?= $isPrintingPickup ? 'Complete Printing Pick-up' : ($isStorePickup ? 'Complete Store Pick-up' : 'Complete Walk-in Sale') ?></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Persistent Mobile / Tablet POS Running Total Floating Bar -->
<div id="posMobileRunningBar" class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-surface-container-lowest/95 backdrop-blur border-t border-outline-variant/40 px-md py-sm shadow-2xl flex items-center justify-between">
    <div class="flex items-center gap-sm">
        <span class="w-9 h-9 rounded-xl bg-primary text-on-primary flex items-center justify-center shadow-sm">
            <span class="material-symbols-outlined text-[20px]">receipt_long</span>
        </span>
        <div>
            <span class="text-[10px] text-on-surface-variant font-medium uppercase tracking-wider block">Due Now</span>
            <span id="posMobileTotalText" class="font-mono text-title-md font-extrabold text-primary">₱0.00</span>
        </div>
    </div>
    <button type="button" onclick="document.getElementById('posSubmitBtn').scrollIntoView({behavior: 'smooth'})" class="px-md py-2 bg-primary text-on-primary rounded-xl text-xs font-bold hover:bg-primary/90 flex items-center gap-xs shadow-md active:scale-95 transition-all">
        <span>Proceed to Pay</span>
        <span class="material-symbols-outlined text-[16px]">arrow_downward</span>
    </button>
</div>

<!-- Modal: Store Pick-up Order Selector -->
<div id="posPickupSelectorModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl max-w-2xl w-full p-lg shadow-2xl space-y-md max-h-[90vh] flex flex-col">
        
        <div class="flex items-center justify-between border-b border-outline-variant/20 pb-sm shrink-0">
            <div class="flex items-center gap-sm">
                <div class="p-2 bg-secondary-container/40 text-secondary rounded-xl">
                    <span class="material-symbols-outlined text-xl">storefront</span>
                </div>
                <div>
                    <h3 class="text-title-lg font-bold text-on-surface">Select Ready for Pick-up</h3>
                    <p class="text-xs text-on-surface-variant">Select an active ready-for-pickup product order to fulfill at counter</p>
                </div>
            </div>
            <button type="button" onclick="closePickupSelectorModal()" class="text-outline hover:text-on-surface p-1 rounded-full hover:bg-surface-container">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Filter Input inside modal -->
        <div class="relative shrink-0">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
            <input type="text" 
                   id="posPickupFilterInput" 
                   placeholder="Filter by order number, customer name, or product..." 
                   oninput="filterPickupOrders(this.value)"
                   class="w-full pl-10 pr-md py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:ring-2 focus:ring-primary focus:border-primary">
        </div>

        <!-- Pickup Orders List -->
        <div class="overflow-y-auto space-y-sm flex-1 pr-1 custom-scrollbar" id="posPickupOrdersList">
            <?php if (!empty($pickupOrders)): ?>
                <?php foreach ($pickupOrders as $po): ?>
                    <?php 
                    $poName = trim(($po['first_name'] ?? '') . ' ' . ($po['last_name'] ?? ''));
                    $isPoPaid = ($po['payment_status'] ?? '') === 'paid';
                    $itemsSummary = trim((string) ($po['items_summary'] ?? ''));
                    $poCustId = (int) ($po['customer_id'] ?? 0);
                    $matchingPr = !empty($customerHasPrintingMap[$poCustId]) ? $customerHasPrintingMap[$poCustId][0] : null;
                    ?>
                    <div class="pos-pickup-row p-md bg-surface-container-low hover:bg-surface-container border border-outline-variant/20 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-sm transition-all"
                         data-search="<?= strtolower(esc($po['order_number'] . ' ' . $poName . ' ' . $itemsSummary)) ?>">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-xs flex-wrap">
                                <h4 class="font-bold text-sm text-on-surface truncate max-w-[320px]">
                                    <?= esc($itemsSummary !== '' ? $itemsSummary : 'Pick-up Items') ?>
                                </h4>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $isPoPaid ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' ?>">
                                    <?= $isPoPaid ? 'PAID ONLINE' : 'UNPAID' ?>
                                </span>
                            </div>
                            <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                                Customer: <strong class="text-on-surface"><?= esc($poName ?: 'Online Customer') ?></strong>
                                <span class="font-mono text-outline ml-1.5">#<?= esc($po['order_number']) ?></span>
                                <span class="text-xs text-outline ml-1">• <?= esc(date('M d, Y h:i A', strtotime($po['placed_at']))) ?></span>
                            </p>
                            <?php if ($matchingPr): ?>
                                <div class="flex items-center gap-1.5 text-[11px] text-purple-700 font-bold bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-lg mt-1 w-fit">
                                    <span class="material-symbols-outlined text-[14px]">print</span>
                                    <span>+ Also has Ready Printing (#<?= esc($matchingPr['request_number']) ?>)</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center justify-between sm:justify-end gap-md shrink-0 pt-xs sm:pt-0 border-t sm:border-t-0 border-outline-variant/10">
                            <span class="font-mono font-bold text-sm text-primary">₱<?= number_format((float) $po['total_amount'], 2) ?></span>
                            <?php if ($matchingPr): ?>
                                <div class="flex items-center gap-xs">
                                    <a href="<?= base_url('tenant/pos?order_id=' . $po['id'] . '&printing_id=' . $matchingPr['id']) ?>" 
                                        class="px-2.5 py-1.5 bg-purple-700 hover:bg-purple-800 text-white rounded-lg text-xs font-bold transition-all flex items-center gap-xs shadow-sm"
                                        title="Fulfill order and printing request together in one transaction">
                                        <span class="material-symbols-outlined text-[15px]">join_inner</span>
                                        Pick Up Combined
                                    </a>
                                    <a href="<?= base_url('tenant/pos?order_id=' . $po['id']) ?>" 
                                        class="px-2.5 py-1.5 border border-outline-variant hover:bg-surface-container rounded-lg text-xs font-semibold text-on-surface transition-all">
                                        Order Only
                                    </a>
                                </div>
                            <?php else: ?>
                                <a href="<?= base_url('tenant/pos?order_id=' . $po['id']) ?>" 
                                    class="px-md py-1.5 bg-secondary text-on-secondary hover:bg-secondary/90 rounded-lg text-xs font-bold transition-all flex items-center gap-xs shadow-sm">
                                    <span class="material-symbols-outlined text-[16px]">point_of_sale</span>
                                    Open in POS
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="py-xl text-center text-outline text-xs flex flex-col items-center gap-xs">
                    <span class="material-symbols-outlined text-3xl">inbox</span>
                    <span>No Ready for Pick-up orders found for your shop.</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-end border-t border-outline-variant/20 pt-sm shrink-0">
            <button type="button" onclick="closePickupSelectorModal()" class="px-md py-sm rounded-lg border border-outline-variant text-xs font-semibold hover:bg-surface-container">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Modal: Printing Ready for Pick-up Selector -->
<div id="posPrintingSelectorModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl max-w-2xl w-full p-lg shadow-2xl space-y-md max-h-[90vh] flex flex-col">
        
        <div class="flex items-center justify-between border-b border-outline-variant/20 pb-sm shrink-0">
            <div class="flex items-center gap-sm">
                <div class="p-2 bg-primary/10 text-primary rounded-xl">
                    <span class="material-symbols-outlined text-xl">print</span>
                </div>
                <div>
                    <h3 class="text-title-lg font-bold text-on-surface">Printing Ready for Pick-up</h3>
                    <p class="text-xs text-on-surface-variant">Select a ready-for-pickup printing request to fulfill and collect at counter</p>
                </div>
            </div>
            <button type="button" onclick="closePrintingSelectorModal()" class="text-outline hover:text-on-surface p-1 rounded-full hover:bg-surface-container">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Filter Input inside modal -->
        <div class="relative shrink-0">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
            <input type="text" 
                   id="posPrintingFilterInput" 
                   placeholder="Filter by request number, customer name, or file name..." 
                   oninput="filterPrintingRequests(this.value)"
                   class="w-full pl-10 pr-md py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:ring-2 focus:ring-primary focus:border-primary">
        </div>

        <!-- Printing Requests List -->
        <div class="overflow-y-auto space-y-sm flex-1 pr-1 custom-scrollbar" id="posPrintingRequestsList">
            <?php if (!empty($printingPickupRequests)): ?>
                <?php foreach ($printingPickupRequests as $pr): ?>
                    <?php 
                    $prCustName = trim(($pr['first_name'] ?? '') . ' ' . ($pr['last_name'] ?? ''));
                    $prDownPayment = (float) ($pr['down_payment'] ?? 0);
                    $prTotalPrice = (float) ($pr['total_price'] ?? 0);
                    $prIsFullyPaid = ($prTotalPrice > 0 && $prDownPayment >= $prTotalPrice);
                    $prSpecs = (int)($pr['page_count'] ?? 1) . ' pgs • ' . (int)($pr['copies'] ?? 1) . ' copies • ' . strtoupper((string)($pr['color_mode'] ?? 'BW')) . ' • ' . esc($pr['paper_size'] ?? 'Letter');
                    $prCustId = (int) ($pr['customer_id'] ?? 0);
                    $matchingPo = !empty($customerHasOrderMap[$prCustId]) ? $customerHasOrderMap[$prCustId][0] : null;
                    ?>
                    <div class="pos-printing-row p-md bg-surface-container-low hover:bg-surface-container border border-outline-variant/20 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-sm transition-all"
                         data-search="<?= strtolower(esc(($pr['request_number'] ?? '') . ' ' . $prCustName . ' ' . ($pr['first_name'] ?? '') . ' ' . ($pr['last_name'] ?? '') . ' ' . ($pr['file_name'] ?? ''))) ?>">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-xs flex-wrap">
                                <h4 class="font-bold text-sm text-on-surface truncate max-w-[320px]">
                                    <?= esc($pr['file_name'] ?? 'Document.pdf') ?>
                                </h4>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800">
                                    Ready for Pick-up
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-secondary-container text-on-secondary-container">
                                    Store Pick-up
                                </span>
                                <?php if ($prIsFullyPaid): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        PAID ONLINE
                                    </span>
                                <?php elseif ($prDownPayment > 0): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                        DOWN PAYMENT PAID (₱<?= number_format($prDownPayment, 2) ?>)
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                                Customer: <strong class="text-on-surface"><?= esc($prCustName ?: 'Online Customer') ?></strong>
                                <span class="font-mono text-outline ml-1.5">#<?= esc($pr['request_number']) ?></span>
                                <span class="text-xs text-outline ml-1">• <?= esc($prSpecs) ?></span>
                            </p>
                            <?php if ($matchingPo): ?>
                                <div class="flex items-center gap-1.5 text-[11px] text-indigo-700 font-bold bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-lg mt-1 w-fit">
                                    <span class="material-symbols-outlined text-[14px]">shopping_bag</span>
                                    <span>+ Also has Ready Product Order (#<?= esc($matchingPo['order_number']) ?>)</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center justify-between sm:justify-end gap-md shrink-0 pt-xs sm:pt-0 border-t sm:border-t-0 border-outline-variant/10">
                            <div class="text-right">
                                <span class="font-mono font-bold text-sm text-primary block">₱<?= number_format($prTotalPrice, 2) ?></span>
                                <?php if (!$prIsFullyPaid && $prTotalPrice > $prDownPayment): ?>
                                    <span class="text-[10px] text-amber-800 font-medium">Bal: ₱<?= number_format($prTotalPrice - $prDownPayment, 2) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($matchingPo): ?>
                                <div class="flex items-center gap-xs">
                                    <a href="<?= base_url('tenant/pos?order_id=' . $matchingPo['id'] . '&printing_id=' . $pr['id']) ?>" 
                                        class="px-2.5 py-1.5 bg-indigo-700 hover:bg-indigo-800 text-white rounded-lg text-xs font-bold transition-all flex items-center gap-xs shadow-sm"
                                        title="Fulfill printing request and product order together in one transaction">
                                        <span class="material-symbols-outlined text-[15px]">join_inner</span>
                                        Pick Up Combined
                                    </a>
                                    <a href="<?= base_url('tenant/pos?printing_id=' . $pr['id']) ?>" 
                                        class="px-2.5 py-1.5 border border-outline-variant hover:bg-surface-container rounded-lg text-xs font-semibold text-on-surface transition-all">
                                        Print Only
                                    </a>
                                </div>
                            <?php else: ?>
                                <a href="<?= base_url('tenant/pos?printing_id=' . $pr['id']) ?>" 
                                    class="px-md py-1.5 bg-primary text-on-primary hover:bg-primary/90 rounded-lg text-xs font-bold transition-all flex items-center gap-xs shadow-sm">
                                    <span class="material-symbols-outlined text-[16px]">point_of_sale</span>
                                    Open in POS
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="py-xl text-center text-outline text-xs flex flex-col items-center gap-xs">
                    <span class="material-symbols-outlined text-3xl">inbox</span>
                    <span>No Ready for Pick-up printing requests found for your shop.</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-end border-t border-outline-variant/20 pt-sm shrink-0">
            <button type="button" onclick="closePrintingSelectorModal()" class="px-md py-sm rounded-lg border border-outline-variant text-xs font-semibold hover:bg-surface-container">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Receipt / Success Modal -->
<div id="posSuccessModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl max-w-md w-full p-lg shadow-2xl space-y-md">
        <div class="text-center space-y-xs">
            <div class="w-14 h-14 bg-green-100 text-green-700 rounded-full mx-auto flex items-center justify-center">
                <span class="material-symbols-outlined text-3xl">done_all</span>
            </div>
            <h3 class="text-headline-md font-bold text-on-surface">Sale Completed!</h3>
            <p id="posSuccessModalMessage" class="text-xs text-on-surface-variant">Transaction processed successfully.</p>
        </div>

        <div id="posReceiptSummary" class="bg-surface-container-low p-md rounded-xl space-y-xs text-xs">
            <!-- Receipt lines populated dynamically -->
        </div>

        <div class="flex items-center gap-sm pt-xs">
            <button type="button" id="posPrintReceiptBtn" class="flex-1 py-2.5 border border-outline-variant hover:bg-surface-container text-on-surface rounded-xl text-xs font-bold flex items-center justify-center gap-xs">
                <span class="material-symbols-outlined text-[18px]">print</span>
                Print Receipt
            </button>
            <button type="button" id="posNewSaleBtn" class="flex-1 py-2.5 bg-primary hover:bg-primary/90 text-on-primary rounded-xl text-xs font-bold flex items-center justify-center gap-xs">
                <span class="material-symbols-outlined text-[18px]">autorenew</span>
                Next Sale
            </button>
        </div>
    </div>
</div>

<!-- Customer QR Scanner Modal -->
<div id="posQrScannerModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-md">
    <div class="glass-card bg-surface-container-lowest rounded-2xl p-lg max-w-md w-full border border-outline-variant/30 shadow-2xl space-y-md">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">
            <div class="flex items-center gap-xs text-secondary font-bold">
                <span class="material-symbols-outlined text-2xl">qr_code_scanner</span>
                <div>
                    <h3 class="text-title-md text-on-surface">Scan Pick-up &amp; Order QR</h3>
                    <p class="text-[11px] text-on-surface-variant font-normal">Live camera, scan from photo, or enter code</p>
                </div>
            </div>
            <button type="button" onclick="closeQrScannerModal()" class="text-on-surface-variant hover:text-on-surface p-1 rounded-full hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div id="posScanFeedback" class="hidden text-xs rounded-xl p-sm font-medium"></div>

        <!-- Scan Mode Tabs -->
        <div class="flex items-center gap-xs p-1 bg-surface-container-low rounded-xl border border-outline-variant/20">
            <button type="button" id="posTabCameraBtn" onclick="switchPosScanMode('camera')" class="flex-1 py-1.5 px-sm rounded-lg text-xs font-bold transition-all bg-surface-container-lowest text-primary shadow-sm flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-[16px]">photo_camera</span>
                <span>Live Camera</span>
            </button>
            <button type="button" id="posTabFileBtn" onclick="switchPosScanMode('file')" class="flex-1 py-1.5 px-sm rounded-lg text-xs font-bold transition-all text-on-surface-variant hover:text-on-surface flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-[16px]">add_photo_alternate</span>
                <span>Scan from Photo</span>
            </button>
        </div>

        <!-- Mode 1: Live Camera (Unobstructed, No Dark Box) -->
        <div id="posCameraContainer" class="space-y-sm">
            <div class="rounded-2xl overflow-hidden bg-black aspect-square relative flex items-center justify-center border border-outline-variant/30 shadow-inner">
                <div id="posQrReader" class="w-full h-full"></div>
                <!-- Clean, non-blocking transparent corner reticle -->
                <div class="qr-viewfinder-overlay pointer-events-none absolute inset-4 border border-white/20 rounded-xl">
                    <div class="absolute top-0 left-0 w-6 h-6 border-t-4 border-l-4 border-secondary rounded-tl-lg"></div>
                    <div class="absolute top-0 right-0 w-6 h-6 border-t-4 border-r-4 border-secondary rounded-tr-lg"></div>
                    <div class="absolute bottom-0 left-0 w-6 h-6 border-b-4 border-l-4 border-secondary rounded-bl-lg"></div>
                    <div class="absolute bottom-0 right-0 w-6 h-6 border-b-4 border-r-4 border-secondary rounded-br-lg"></div>
                    <div class="pos-qr-laser-line"></div>
                </div>
                <div id="posQrCameraPlaceholder" class="absolute inset-0 flex flex-col items-center justify-center text-white/70 p-md text-center bg-black/80">
                    <span class="material-symbols-outlined text-4xl mb-1 text-secondary">photo_camera</span>
                    <span class="text-xs font-semibold">Starting camera...</span>
                    <span class="text-[10px] opacity-70 mt-1">Please allow camera permissions if prompted</span>
                </div>
            </div>
            <p class="text-xs text-center text-on-surface-variant font-medium">Point camera directly at customer phone or ticket QR</p>
        </div>

        <!-- Mode 2: Scan from Photo / Image File -->
        <div id="posFileContainer" class="hidden space-y-sm">
            <label for="posPhotoUpload" class="flex flex-col items-center justify-center p-xl border-2 border-dashed border-secondary/40 hover:border-secondary rounded-2xl bg-secondary/5 hover:bg-secondary/10 transition-all cursor-pointer text-center group">
                <span class="p-3 bg-secondary/10 text-secondary rounded-2xl material-symbols-outlined text-3xl group-hover:scale-110 transition-transform mb-2">image_search</span>
                <span class="text-xs font-bold text-on-surface">Click to Select QR Photo / Image</span>
                <span class="text-[11px] text-on-surface-variant mt-1">Upload picture of completed order or ticket</span>
                <input id="posPhotoUpload" type="file" accept="image/*" class="hidden" onchange="handlePosPhotoUpload(this)">
            </label>
            <p id="posPhotoScanStatus" class="text-xs text-center text-on-surface-variant font-medium hidden"></p>
        </div>

        <!-- Manual Lookup Fallback -->
        <div class="space-y-xs pt-xs border-t border-outline-variant/20">
            <label for="posManualOrderCode" class="text-xs font-bold text-on-surface uppercase tracking-wider block">
                Manual Order / Request Code or ID
            </label>
            <div class="flex gap-xs">
                <input type="text" id="posManualOrderCode" placeholder="e.g. ORD-88102, PR-190, or 190" class="flex-1 px-md py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono font-bold focus:ring-2 focus:ring-primary focus:border-primary">
                <button type="button" id="posManualLookupBtn" onclick="verifyManualOrderCode()" class="bg-primary hover:bg-primary/90 text-on-primary px-md py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-xs">
                    <span class="material-symbols-outlined text-[16px]">search</span>
                    <span>Verify</span>
                </button>
            </div>
        </div>

        <div class="flex items-center justify-end pt-xs">
            <button type="button" onclick="closeQrScannerModal()" class="px-md py-2 rounded-xl border border-outline-variant text-xs font-semibold text-on-surface hover:bg-surface-container">
                Cancel
            </button>
        </div>
    </div>
</div>

<style>
/* Remove Html5Qrcode blocking dark region box in POS */
#posQrReader #qr-shaded-region {
    display: none !important;
}
#posQrReader {
    border: none !important;
}
#posQrReader video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    border-radius: 0.875rem !important;
}
.pos-qr-laser-line {
    position: absolute;
    left: 10px;
    right: 10px;
    height: 2px;
    background: linear-gradient(90deg, transparent, #eab308, transparent);
    box-shadow: 0 0 10px #eab308;
    animation: posQrLaserScan 2s ease-in-out infinite alternate;
}
@keyframes posQrLaserScan {
    0% { top: 12px; }
    100% { top: calc(100% - 14px); }
}
</style>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function() {
    const isStorePickup = <?= $isStorePickup ? 'true' : 'false' ?>;
    const storePickupOrderId = <?= (int) ($order['id'] ?? 0) ?>;
    const onlineOrderBaseAmount = <?= (float) $baseOnlineAmount ?>;
    const isOnlinePaid = <?= $isOnlinePaid ? 'true' : 'false' ?>;

    const isPrintingPickup = <?= !empty($printingRequest) ? 'true' : 'false' ?>;
    const printingRequestId = <?= (int) ($printingRequest['id'] ?? 0) ?>;
    const printingRemainingBalance = <?= (float) ($printingRemainingBalance ?? 0) ?>;
    const printingOnlinePaid = <?= (float) ($printingOnlinePaid ?? 0) ?>;

    const isCombinedPickup = isStorePickup && isPrintingPickup;

    const orderItemsSummary = <?= !empty($orderItems) ? json_encode(array_values(array_map(static fn($oit) => [
        'name' => (string) ($oit['product_name'] ?? 'Product'),
        'qty' => (int) ($oit['quantity'] ?? 1),
        'variant' => (string) ($oit['variant_label'] ?? ''),
        'price' => (float) ($oit['unit_price'] ?? 0),
        'total' => (float) ($oit['line_total'] ?? 0),
    ], array_filter($orderItems, static fn($oit) => empty($oit['is_pos_addition']))))) : '[]' ?>;

    const printingItemSummary = <?= !empty($printingRequest) ? json_encode([
        'file_name' => (string) ($printingRequest['file_name'] ?? 'Document.pdf'),
        'request_number' => (string) ($printingRequest['request_number'] ?? ''),
        'specs' => trim(($printingRequest['paper_size'] ?? '') . ' • ' . ($printingRequest['color_mode'] ?? '') . (!empty($printingRequest['binding_option']) ? ' • ' . $printingRequest['binding_option'] : '')),
        'total' => (float) ($printingRequest['total_price'] ?? 0),
    ]) : 'null' ?>;

    const BASE_URL = '<?= rtrim(site_url(), '/') ?>';
    const CSRF_TOKEN_NAME = '<?= csrf_token() ?>';
    let CSRF_HASH_VAL = '<?= csrf_hash() ?>';

    let cart = []; // Array of { product_id, name, price, stock_quantity, quantity, sku, image_url }
    let currentCategoryId = '';
    let searchDebounceTimer = null;
    let catalogProducts = [];

    // DOM Elements
    const searchInput = document.getElementById('posProductSearch');
    const clearSearchBtn = document.getElementById('posClearSearch');
    const searchResultsBox = document.getElementById('posSearchResults');
    const quickCatalogBox = document.getElementById('posQuickCatalog');
    const cartListBox = document.getElementById('posCartList');
    const emptyCartNotice = document.getElementById('posEmptyCartNotice');
    const cartCountBadge = document.getElementById('posCartCountBadge');
    const amountDueNowEl = document.getElementById('posAmountDueNow');
    const additionalSubtotalEl = document.getElementById('posAdditionalSubtotal');
    const updatedGrandTotalEl = document.getElementById('posUpdatedGrandTotal');
    const walkinSubtotalEl = document.getElementById('posWalkinSubtotal');
    const cashInput = document.getElementById('posCashTendered');
    const changeDueText = document.getElementById('posChangeDueText');
    const submitBtn = document.getElementById('posSubmitBtn');
    const submitBtnLabel = document.getElementById('posSubmitBtnLabel');
    const errBox = document.getElementById('posErrorMessage');

    const successModal = document.getElementById('posSuccessModal');
    const successMessage = document.getElementById('posSuccessModalMessage');
    const receiptSummary = document.getElementById('posReceiptSummary');
    const printReceiptBtn = document.getElementById('posPrintReceiptBtn');
    const newSaleBtn = document.getElementById('posNewSaleBtn');

    // Currency Formatter
    function formatMoney(amount) {
        return '₱' + (parseFloat(amount) || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function esc(str) {
        return String(str || '').replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        })[m]);
    }

    // Load Catalog with optional category & query
    function loadCatalog(query = '', categoryId = '') {
        quickCatalogBox.innerHTML = `
            <div class="col-span-full py-xl text-center text-on-surface-variant/70 flex flex-col items-center gap-xs">
                <span class="material-symbols-outlined text-3xl animate-spin text-primary">progress_activity</span>
                <span class="text-xs font-medium">Loading catalog products...</span>
            </div>
        `;

        let url = `${BASE_URL}/tenant/pos/search-products?q=${encodeURIComponent(query)}`;
        if (categoryId) {
            url += `&category_id=${encodeURIComponent(categoryId)}`;
        }

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.products && data.products.length > 0) {
                catalogProducts = data.products;
                renderProductCards(data.products);
            } else {
                quickCatalogBox.innerHTML = '<div class="col-span-full py-lg text-center text-outline text-xs">No products found matching your selection.</div>';
            }
        })
        .catch(() => {
            quickCatalogBox.innerHTML = '<div class="col-span-full py-lg text-center text-error text-xs">Failed to load shop catalog.</div>';
        });
    }

    // Render modern product cards grid
    function renderProductCards(products) {
        quickCatalogBox.innerHTML = products.map(p => {
            const outOfStock = p.stock_quantity <= 0;
            const isLowStock = p.stock_quantity > 0 && p.stock_quantity <= 5;
            const cartItem = cart.find(x => x.product_id === p.id);
            const inCartQty = cartItem ? cartItem.quantity : 0;

            return `
                <div class="pos-product-card bg-surface-container-lowest hover:bg-surface-container-low border ${inCartQty > 0 ? 'border-primary ring-1 ring-primary/40' : 'border-outline-variant/30'} rounded-2xl p-2.5 sm:p-3 flex flex-col justify-between gap-2 transition-all hover:shadow-md group cursor-pointer relative" data-id="${p.id}" ${outOfStock ? 'style="opacity: 0.6; cursor: not-allowed;"' : ''}>
                    <!-- Product Image -->
                    <div class="w-full aspect-square rounded-xl bg-surface-container overflow-hidden flex items-center justify-center relative">
                        ${p.image_url 
                            ? `<img src="${p.image_url}" class="w-full h-full object-cover group-hover:scale-105 transition-transform" alt="${esc(p.name)}">` 
                            : `<div class="text-primary/40 flex flex-col items-center gap-1"><span class="material-symbols-outlined text-3xl sm:text-4xl">inventory_2</span><span class="text-[9px] uppercase font-bold text-outline">No Image</span></div>`}
                        
                        <!-- In-cart Badge -->
                        ${inCartQty > 0 ? `
                            <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-primary text-on-primary shadow-xs flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-[12px]">shopping_bag</span>
                                ${inCartQty} in cart
                            </span>
                        ` : ''}

                        <!-- Stock Badge Overlay -->
                        <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-[10px] font-bold shadow-xs ${
                            outOfStock ? 'bg-error text-on-error' : (isLowStock ? 'bg-amber-500 text-white' : 'bg-surface-container-lowest/90 text-on-surface')
                        }">
                            ${outOfStock ? 'Out of Stock' : (isLowStock ? `Low: ${p.stock_quantity}` : `Stock: ${p.stock_quantity}`)}
                        </span>
                    </div>

                    <!-- Product Details -->
                    <div class="space-y-0.5 min-w-0">
                        <p class="text-xs font-bold text-on-surface truncate" title="${esc(p.name)}">${esc(p.name)}</p>
                        <p class="text-[10px] text-outline truncate font-mono">SKU: ${esc(p.sku || 'N/A')}</p>
                        <p class="text-sm sm:text-body-md font-mono font-bold text-primary">₱${p.price.toFixed(2)}</p>
                    </div>

                    <!-- Add Button -->
                    <button type="button" 
                            class="pos-card-add-btn w-full py-1.5 rounded-xl text-xs font-bold flex items-center justify-center gap-1 transition-all shadow-2xs ${
                                outOfStock ? 'bg-outline/20 text-outline cursor-not-allowed' : (inCartQty > 0 ? 'bg-primary/10 text-primary border border-primary/30 hover:bg-primary hover:text-on-primary' : 'bg-primary text-on-primary hover:bg-primary/90 active:scale-95')
                            }" 
                            data-id="${p.id}" 
                            ${outOfStock ? 'disabled' : ''}>
                        <span class="material-symbols-outlined text-[16px]">${inCartQty > 0 ? 'add' : 'add_shopping_cart'}</span>
                        <span>${inCartQty > 0 ? 'Add More' : 'Add'}</span>
                    </button>
                </div>
            `;
        }).join('');

        // Attach click handlers to cards and add buttons
        quickCatalogBox.querySelectorAll('.pos-product-card').forEach(card => {
            card.addEventListener('click', function(e) {
                const id = parseInt(this.dataset.id);
                const prod = catalogProducts.find(x => x.id === id);
                if (prod && prod.stock_quantity > 0) {
                    addToCart(prod);
                }
            });
        });

        quickCatalogBox.querySelectorAll('.pos-card-add-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const id = parseInt(this.dataset.id);
                const prod = catalogProducts.find(x => x.id === id);
                if (prod && prod.stock_quantity > 0) {
                    addToCart(prod);
                }
            });
        });
    }

    function updateCatalogBadges() {
        if (!catalogProducts || catalogProducts.length === 0) return;
        const currentScroll = quickCatalogBox ? quickCatalogBox.scrollTop : 0;
        renderProductCards(catalogProducts);
        if (quickCatalogBox) quickCatalogBox.scrollTop = currentScroll;
    }

    // Category chips click handler
    document.querySelectorAll('.pos-cat-chip').forEach(chip => {
        chip.addEventListener('click', function() {
            document.querySelectorAll('.pos-cat-chip').forEach(c => {
                c.className = 'pos-cat-chip px-3 py-1.5 rounded-full font-medium transition-all shrink-0 bg-surface-container-low text-on-surface-variant hover:bg-surface-container border border-outline-variant/30';
            });
            this.className = 'pos-cat-chip px-3 py-1.5 rounded-full font-bold transition-all shrink-0 bg-primary text-on-primary shadow-xs';

            currentCategoryId = this.dataset.category || '';
            const activeCategoryText = document.getElementById('posActiveCategoryText');
            if (activeCategoryText) {
                activeCategoryText.textContent = this.dataset.category ? `• ${this.textContent.trim()}` : '• All Products';
            }

            const query = searchInput ? searchInput.value.trim() : '';
            loadCatalog(query, currentCategoryId);
        });
    });

    // Keyboard shortcut '/' to search
    document.addEventListener('keydown', (e) => {
        if (e.key === '/' && document.activeElement !== searchInput && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
    });

    // Payment method toggle (show/hide cash calculations)
    document.querySelectorAll('input[name="pos_payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const cashCalc = document.getElementById('posCashCalculationContainer');
            if (cashCalc) {
                if (this.value === 'cash') {
                    cashCalc.classList.remove('hidden');
                } else {
                    cashCalc.classList.add('hidden');
                }
            }
        });
    });

    // Search input handler
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchDebounceTimer);
            const query = e.target.value.trim();

            if (query.length > 0) {
                clearSearchBtn.classList.remove('hidden');
            } else {
                clearSearchBtn.classList.add('hidden');
                searchResultsBox.classList.add('hidden');
                searchResultsBox.innerHTML = '';
            }

            searchDebounceTimer = setTimeout(() => {
                loadCatalog(query, currentCategoryId);
            }, 250);
        });

        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            clearSearchBtn.classList.add('hidden');
            searchResultsBox.classList.add('hidden');
            searchResultsBox.innerHTML = '';
            loadCatalog('', currentCategoryId);
        });
    }

    // Add product to cart
    function addToCart(prod) {
        hideError();
        const existing = cart.find(x => x.product_id === prod.id);
        if (existing) {
            if (existing.quantity < prod.stock_quantity) {
                existing.quantity += 1;
            } else {
                showError(`Cannot add more than ${prod.stock_quantity} units available in inventory.`);
                return;
            }
        } else {
            cart.push({
                product_id: prod.id,
                name: prod.name,
                price: parseFloat(prod.price),
                stock_quantity: parseInt(prod.stock_quantity),
                sku: prod.sku || '',
                quantity: 1
            });
        }
        renderCart();
    }

    window.posUpdateQty = function(productId, delta) {
        hideError();
        const item = cart.find(x => x.product_id === productId);
        if (!item) return;

        const newQty = item.quantity + delta;
        if (newQty <= 0) {
            cart = cart.filter(x => x.product_id !== productId);
        } else if (newQty <= item.stock_quantity) {
            item.quantity = newQty;
        } else {
            showError(`Cannot exceed available inventory of ${item.stock_quantity} units.`);
        }
        renderCart();
    };

    window.posRemoveItem = function(productId) {
        hideError();
        cart = cart.filter(x => x.product_id !== productId);
        renderCart();
    };

    window.posClearAllCart = function() {
        if (cart.length === 0) return;
        cart = [];
        hideError();
        renderCart();
    };

    // Render cart items & totals
    function renderCart() {
        const totalItemsCount = cart.reduce((acc, it) => acc + it.quantity, 0);
        cartCountBadge.textContent = `${totalItemsCount} ${totalItemsCount === 1 ? 'item' : 'items'}`;

        if (cart.length === 0) {
            cartListBox.innerHTML = `
                <div id="posEmptyCartNotice" class="py-6 text-center text-on-surface-variant/70 flex flex-col items-center gap-1 border border-dashed border-outline-variant/40 rounded-xl">
                    <span class="material-symbols-outlined text-2xl text-outline-variant">remove_shopping_cart</span>
                    <span class="text-xs font-medium px-3 text-outline">
                        ${isStorePickup ? 'No additional in-store items. (You can complete store pickup without adding any extra items)' : 'No items in sale yet. Click products from the catalog to add.'}
                    </span>
                </div>
            `;
        } else {
            cartListBox.innerHTML = cart.map(it => {
                const lineTotal = it.price * it.quantity;
                return `
                    <div class="p-2 bg-surface-container-low rounded-xl flex items-center justify-between gap-2 border border-outline-variant/20">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-on-surface truncate">${esc(it.name)}</p>
                            <p class="text-[10px] text-outline font-mono">₱${it.price.toFixed(2)} ea • Line: <span class="font-bold text-on-surface">₱${lineTotal.toFixed(2)}</span></p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="inline-flex items-center border border-outline-variant/40 rounded-lg overflow-hidden bg-surface-container-lowest">
                                <button type="button" onclick="posUpdateQty(${it.product_id}, -1)" class="w-5 h-5 flex items-center justify-center font-bold text-xs hover:bg-surface-container">-</button>
                                <span class="px-1.5 font-mono font-bold text-xs">${it.quantity}</span>
                                <button type="button" onclick="posUpdateQty(${it.product_id}, 1)" class="w-5 h-5 flex items-center justify-center font-bold text-xs hover:bg-surface-container">+</button>
                            </div>
                            <button type="button" onclick="posRemoveItem(${it.product_id})" class="p-1 text-error hover:bg-error/10 rounded-lg transition-colors" title="Remove">
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        const additionalSubtotal = cart.reduce((acc, it) => acc + (it.price * it.quantity), 0);

        if (isCombinedPickup) {
            if (additionalSubtotalEl) additionalSubtotalEl.textContent = formatMoney(additionalSubtotal);
            const orderDue = isOnlinePaid ? 0 : onlineOrderBaseAmount;
            const combinedDue = orderDue + printingRemainingBalance + additionalSubtotal;
            if (amountDueNowEl) amountDueNowEl.textContent = formatMoney(combinedDue);
            calculateChange(combinedDue);
        } else if (isStorePickup) {
            if (additionalSubtotalEl) additionalSubtotalEl.textContent = formatMoney(additionalSubtotal);
            const updatedGrandTotal = onlineOrderBaseAmount + additionalSubtotal;
            if (updatedGrandTotalEl) updatedGrandTotalEl.textContent = formatMoney(updatedGrandTotal);
            const dueNow = isOnlinePaid ? additionalSubtotal : updatedGrandTotal;
            if (amountDueNowEl) amountDueNowEl.textContent = formatMoney(dueNow);
            calculateChange(dueNow);
        } else if (isPrintingPickup) {
            if (additionalSubtotalEl) additionalSubtotalEl.textContent = formatMoney(additionalSubtotal);
            const dueNow = printingRemainingBalance + additionalSubtotal;
            if (amountDueNowEl) amountDueNowEl.textContent = formatMoney(dueNow);
            calculateChange(dueNow);
        } else {
            if (walkinSubtotalEl) walkinSubtotalEl.textContent = formatMoney(additionalSubtotal);
            if (amountDueNowEl) amountDueNowEl.textContent = formatMoney(additionalSubtotal);
            calculateChange(additionalSubtotal);
        }
        const mobileTotal = document.getElementById('posMobileTotalText');
        if (mobileTotal && amountDueNowEl) mobileTotal.textContent = amountDueNowEl.textContent;

        updateCatalogBadges();
    }

    // Cash calculations
    function calculateChange(dueAmount) {
        const tendered = parseFloat(cashInput.value) || 0;
        const change = Math.max(0, tendered - dueAmount);
        changeDueText.textContent = formatMoney(change);
    }

    if (cashInput) {
        cashInput.addEventListener('input', () => {
            const due = getDueAmount();
            calculateChange(due);
        });
    }

    document.querySelectorAll('.pos-denom-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const val = this.dataset.val;
            const due = getDueAmount();
            if (val === 'exact') {
                cashInput.value = due.toFixed(2);
            } else {
                const addVal = parseFloat(val) || 0;
                const current = parseFloat(cashInput.value) || 0;
                cashInput.value = (current + addVal).toFixed(2);
            }
            calculateChange(due);
        });
    });

    function getDueAmount() {
        const additionalSubtotal = cart.reduce((acc, it) => acc + (it.price * it.quantity), 0);
        if (isCombinedPickup) {
            const orderDue = isOnlinePaid ? 0 : onlineOrderBaseAmount;
            return orderDue + printingRemainingBalance + additionalSubtotal;
        }
        if (isStorePickup) {
            return isOnlinePaid ? additionalSubtotal : (onlineOrderBaseAmount + additionalSubtotal);
        }
        if (isPrintingPickup) {
            return printingRemainingBalance + additionalSubtotal;
        }
        return additionalSubtotal;
    }

    function showError(msg) {
        errBox.textContent = msg;
        errBox.classList.remove('hidden');
    }

    function hideError() {
        errBox.classList.add('hidden');
        errBox.textContent = '';
    }

    // Submit handler
    if (submitBtn) {
        submitBtn.addEventListener('click', async function() {
            hideError();

            const due = getDueAmount();
            const selectedPaymentMethod = document.querySelector('input[name="pos_payment_method"]:checked')?.value || 'cash';

            if (!isStorePickup && !isPrintingPickup && cart.length === 0) {
                showError('Please add at least one product to the sale before completing.');
                return;
            }

            // If cash and customer owes an amount, verify tendered cash
            if (selectedPaymentMethod === 'cash' && due > 0) {
                const tendered = parseFloat(cashInput.value) || 0;
                if (tendered < due) {
                    showError(`Tendered cash (${formatMoney(tendered)}) is less than total due (${formatMoney(due)}).`);
                    return;
                }
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-symbols-outlined text-[20px] animate-spin">progress_activity</span><span>Processing...</span>';

            const payload = new FormData();
            payload.append(CSRF_TOKEN_NAME, CSRF_HASH_VAL);
            payload.append('counter_payment_method', selectedPaymentMethod);
            payload.append('items', JSON.stringify(cart.map(i => ({
                product_id: i.product_id,
                quantity: i.quantity
            }))));

            let endpoint = '';
            if (isCombinedPickup) {
                endpoint = `${BASE_URL}/tenant/pos/complete-pickup`;
                payload.append('order_id', storePickupOrderId);
                payload.append('printing_id', printingRequestId);
            } else if (isStorePickup) {
                endpoint = `${BASE_URL}/tenant/pos/complete-pickup`;
                payload.append('order_id', storePickupOrderId);
            } else if (isPrintingPickup) {
                endpoint = `${BASE_URL}/tenant/pos/complete-pickup`;
                payload.append('printing_id', printingRequestId);
            } else {
                endpoint = `${BASE_URL}/tenant/pos/complete-walkin`;
                const custName = document.getElementById('posCustomerName')?.value.trim() || '';
                payload.append('customer_name', custName);
            }

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: payload
                });

                const data = await response.json();

                if (!data.success) {
                    showError(data.error || 'Failed to complete transaction.');
                    resetSubmitButton();
                    return;
                }

                // Show Receipt Modal
                showSuccessReceipt(data, selectedPaymentMethod, due);

            } catch (err) {
                showError('Network or server error while completing transaction. Please try again.');
                resetSubmitButton();
            }
        });
    }

    function resetSubmitButton() {
        submitBtn.disabled = false;
        let label = 'Complete Walk-in Sale';
        if (isCombinedPickup) {
            label = 'Complete Combined Pick-up';
        } else if (isPrintingPickup) {
            label = 'Complete Printing Pick-up';
        } else if (isStorePickup) {
            label = 'Complete Store Pick-up';
        }
        submitBtn.innerHTML = `<span class="material-symbols-outlined text-[20px]">check_circle</span><span>${label}</span>`;
    }

    let lastCompletedOrderId = null;
    let lastCompletedPrintingId = null;
    let lastIsCombined = false;

    function showSuccessReceipt(data, paymentMethod, dueAmount) {
        lastCompletedOrderId = data.order_id || data.order_number;
        lastCompletedPrintingId = data.printing_id || null;
        lastIsCombined = isCombinedPickup || data.type === 'combined';

        try {
            localStorage.setItem('blax_last_sale', JSON.stringify({ time: Date.now(), shop_id: <?= (int) ($shop['id'] ?? 0) ?>, order_id: data.order_id }));
            window.dispatchEvent(new CustomEvent('blax:sale_completed', { detail: data }));
        } catch (e) {}

        successMessage.textContent = data.message || 'Sale processed successfully.';

        const tendered = parseFloat(cashInput.value) || dueAmount;
        const change = Math.max(0, tendered - dueAmount);

        let refHtml = `<span class="font-bold text-on-surface font-mono">${esc(data.order_number || ('#' + data.order_id))}</span>`;
        if (lastIsCombined && (data.printing_number || lastCompletedPrintingId)) {
            refHtml += `<div class="text-[11px] text-indigo-700 font-bold font-mono">Print Ref: #${esc(data.printing_number || lastCompletedPrintingId)}</div>`;
        }

        // Build itemized breakdown of products and printing
        let itemsBreakdownHtml = '';
        if (lastIsCombined || isCombinedPickup) {
            itemsBreakdownHtml += `
                <div class="py-2.5 border-b border-outline-variant/20 text-xs">
                    <span class="text-[11px] font-bold text-outline uppercase tracking-wider block mb-1.5">Fulfilled Items & Printing:</span>
                    <div class="space-y-1.5 max-h-32 overflow-y-auto pr-1">`;
            if (orderItemsSummary && orderItemsSummary.length > 0) {
                orderItemsSummary.forEach(it => {
                    const varLabel = it.variant ? ` (${esc(it.variant)})` : '';
                    itemsBreakdownHtml += `
                        <div class="flex justify-between items-center text-on-surface text-[11px]">
                            <span class="truncate flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px] text-secondary">inventory_2</span>
                                <strong class="font-semibold">${esc(it.name)}${varLabel}</strong> <span class="text-outline font-mono">&times;${it.qty}</span>
                            </span>
                            <span class="font-mono text-outline">${formatMoney(it.total)}</span>
                        </div>`;
                });
            }
            if (printingItemSummary) {
                itemsBreakdownHtml += `
                    <div class="flex justify-between items-center text-on-surface text-[11px]">
                        <span class="truncate flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px] text-primary">print</span>
                            <strong class="font-semibold text-primary">${esc(printingItemSummary.file_name)}</strong>
                            ${printingItemSummary.specs ? `<span class="text-[10px] text-outline">(${esc(printingItemSummary.specs)})</span>` : ''}
                        </span>
                        <span class="font-mono text-outline">${formatMoney(printingItemSummary.total)}</span>
                    </div>`;
            }
            if (cart && cart.length > 0) {
                cart.forEach(it => {
                    itemsBreakdownHtml += `
                        <div class="flex justify-between items-center text-on-surface text-[11px]">
                            <span class="truncate flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px] text-amber-600">add_shopping_cart</span>
                                <strong class="font-semibold">${esc(it.name)}</strong> <span class="text-outline font-mono">&times;${it.quantity} (Counter)</span>
                            </span>
                            <span class="font-mono text-outline">${formatMoney(it.price * it.quantity)}</span>
                        </div>`;
                });
            }
            itemsBreakdownHtml += `</div></div>`;
        } else if (isStorePickup && orderItemsSummary && orderItemsSummary.length > 0) {
            itemsBreakdownHtml += `
                <div class="py-2.5 border-b border-outline-variant/20 text-xs">
                    <span class="text-[11px] font-bold text-outline uppercase tracking-wider block mb-1.5">Fulfilled Order Items:</span>
                    <div class="space-y-1.5 max-h-32 overflow-y-auto pr-1">`;
            orderItemsSummary.forEach(it => {
                const varLabel = it.variant ? ` (${esc(it.variant)})` : '';
                itemsBreakdownHtml += `
                    <div class="flex justify-between items-center text-on-surface text-[11px]">
                        <span class="truncate flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px] text-secondary">inventory_2</span>
                            <strong class="font-semibold">${esc(it.name)}${varLabel}</strong> <span class="text-outline font-mono">&times;${it.qty}</span>
                        </span>
                        <span class="font-mono text-outline">${formatMoney(it.total)}</span>
                    </div>`;
            });
            if (cart && cart.length > 0) {
                cart.forEach(it => {
                    itemsBreakdownHtml += `
                        <div class="flex justify-between items-center text-on-surface text-[11px]">
                            <span class="truncate flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px] text-amber-600">add_shopping_cart</span>
                                <strong class="font-semibold">${esc(it.name)}</strong> <span class="text-outline font-mono">&times;${it.quantity} (Counter)</span>
                            </span>
                            <span class="font-mono text-outline">${formatMoney(it.price * it.quantity)}</span>
                        </div>`;
                });
            }
            itemsBreakdownHtml += `</div></div>`;
        } else if (isPrintingPickup && printingItemSummary) {
            itemsBreakdownHtml += `
                <div class="py-2.5 border-b border-outline-variant/20 text-xs">
                    <span class="text-[11px] font-bold text-outline uppercase tracking-wider block mb-1.5">Fulfilled Printing Job:</span>
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center text-on-surface text-[11px]">
                            <span class="truncate flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px] text-primary">print</span>
                                <strong class="font-semibold text-primary">${esc(printingItemSummary.file_name)}</strong>
                                ${printingItemSummary.specs ? `<span class="text-[10px] text-outline">(${esc(printingItemSummary.specs)})</span>` : ''}
                            </span>
                            <span class="font-mono text-outline">${formatMoney(printingItemSummary.total)}</span>
                        </div>`;
            if (cart && cart.length > 0) {
                cart.forEach(it => {
                    itemsBreakdownHtml += `
                        <div class="flex justify-between items-center text-on-surface text-[11px]">
                            <span class="truncate flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px] text-amber-600">add_shopping_cart</span>
                                <strong class="font-semibold">${esc(it.name)}</strong> <span class="text-outline font-mono">&times;${it.quantity} (Counter)</span>
                            </span>
                            <span class="font-mono text-outline">${formatMoney(it.price * it.quantity)}</span>
                        </div>`;
                });
            }
            itemsBreakdownHtml += `</div></div>`;
        } else if (cart && cart.length > 0) {
            itemsBreakdownHtml += `
                <div class="py-2.5 border-b border-outline-variant/20 text-xs">
                    <span class="text-[11px] font-bold text-outline uppercase tracking-wider block mb-1.5">Sale Items:</span>
                    <div class="space-y-1.5 max-h-32 overflow-y-auto pr-1">`;
            cart.forEach(it => {
                itemsBreakdownHtml += `
                    <div class="flex justify-between items-center text-on-surface text-[11px]">
                        <span class="truncate flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px] text-primary">shopping_bag</span>
                            <strong class="font-semibold">${esc(it.name)}</strong> <span class="text-outline font-mono">&times;${it.quantity}</span>
                        </span>
                        <span class="font-mono text-outline">${formatMoney(it.price * it.quantity)}</span>
                    </div>`;
            });
            itemsBreakdownHtml += `</div></div>`;
        }

        let linesHtml = `
            <div class="flex justify-between py-1 border-b border-outline-variant/20">
                <span class="text-outline">Reference:</span>
                <div class="text-right">${refHtml}</div>
            </div>
            ${itemsBreakdownHtml}
            <div class="flex justify-between py-1 border-b border-outline-variant/20">
                <span class="text-outline">Payment Method:</span>
                <span class="font-bold uppercase text-on-surface">${paymentMethod}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-outline-variant/20">
                <span class="text-outline">Total Collected:</span>
                <span class="font-bold font-mono text-primary">${formatMoney(dueAmount)}</span>
            </div>
        `;

        if (paymentMethod === 'cash' && dueAmount > 0) {
            linesHtml += `
                <div class="flex justify-between py-1 border-b border-outline-variant/20">
                    <span class="text-outline">Cash Tendered:</span>
                    <span class="font-bold font-mono">${formatMoney(tendered)}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-outline-variant/20">
                    <span class="text-outline">Change Returned:</span>
                    <span class="font-bold font-mono text-green-700">${formatMoney(change)}</span>
                </div>
            `;
        }

        receiptSummary.innerHTML = linesHtml;
        successModal.classList.remove('hidden');
    }

    if (printReceiptBtn) {
        printReceiptBtn.addEventListener('click', () => {
            if (lastIsCombined && lastCompletedOrderId && lastCompletedPrintingId) {
                const printUrl = `${BASE_URL}/tenant/orders/receipt/${encodeURIComponent(lastCompletedOrderId)}?printing_id=${encodeURIComponent(lastCompletedPrintingId)}`;
                window.open(printUrl, '_blank');
            } else if (lastCompletedOrderId) {
                const printUrl = isPrintingPickup
                    ? `${BASE_URL}/tenant/printing/receipt/${encodeURIComponent(lastCompletedOrderId)}`
                    : `${BASE_URL}/tenant/orders/receipt/${encodeURIComponent(lastCompletedOrderId)}`;
                window.open(printUrl, '_blank');
            } else {
                window.print();
            }
        });
    }

    if (newSaleBtn) {
        newSaleBtn.addEventListener('click', () => {
            if (isCombinedPickup) {
                window.location.href = `${BASE_URL}/tenant/pos`;
            } else if (isStorePickup) {
                window.location.href = `${BASE_URL}/tenant/orders`;
            } else if (isPrintingPickup) {
                window.location.href = `${BASE_URL}/tenant/printing`;
            } else {
                window.location.reload();
            }
        });
    }

    // Modal functions for Store Pick-up selector
    window.openPickupSelectorModal = function() {
        const modal = document.getElementById('posPickupSelectorModal');
        if (modal) modal.classList.remove('hidden');
    };

    window.closePickupSelectorModal = function() {
        const modal = document.getElementById('posPickupSelectorModal');
        if (modal) modal.classList.add('hidden');
    };

    window.filterPickupOrders = function(query) {
        const q = query.toLowerCase().trim();
        document.querySelectorAll('.pos-pickup-row').forEach(row => {
            const searchData = row.dataset.search || '';
            if (q === '' || searchData.includes(q)) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
    };

    // Modal functions for Printing Pick-up selector
    window.openPrintingSelectorModal = function() {
        const modal = document.getElementById('posPrintingSelectorModal');
        if (modal) modal.classList.remove('hidden');
    };

    window.closePrintingSelectorModal = function() {
        const modal = document.getElementById('posPrintingSelectorModal');
        if (modal) modal.classList.add('hidden');
    };

    window.filterPrintingRequests = function(query) {
        const q = query.toLowerCase().trim();
        document.querySelectorAll('.pos-printing-row').forEach(row => {
            const searchData = row.dataset.search || '';
            if (q === '' || searchData.includes(q)) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
    };

    // QR Scanner & Verification Handlers
    let html5QrCode = null;
    let currentPosScanMode = 'camera';

    window.switchPosScanMode = function(mode) {
        currentPosScanMode = mode;
        const camBtn = document.getElementById('posTabCameraBtn');
        const fileBtn = document.getElementById('posTabFileBtn');
        const camBox = document.getElementById('posCameraContainer');
        const fileBox = document.getElementById('posFileContainer');

        if (mode === 'camera') {
            camBtn.classList.remove('text-on-surface-variant');
            camBtn.classList.add('bg-surface-container-lowest', 'text-primary', 'shadow-sm');
            fileBtn.classList.remove('bg-surface-container-lowest', 'text-primary', 'shadow-sm');
            fileBtn.classList.add('text-on-surface-variant');
            camBox.classList.remove('hidden');
            fileBox.classList.add('hidden');
            startPosCamera();
        } else {
            fileBtn.classList.remove('text-on-surface-variant');
            fileBtn.classList.add('bg-surface-container-lowest', 'text-primary', 'shadow-sm');
            camBtn.classList.remove('bg-surface-container-lowest', 'text-primary', 'shadow-sm');
            camBtn.classList.add('text-on-surface-variant');
            fileBox.classList.remove('hidden');
            camBox.classList.add('hidden');
            stopQrScanner();
        }
    };

    function startPosCamera() {
        if (!window.Html5Qrcode) return;
        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("posQrReader");
        }

        const placeholder = document.getElementById('posQrCameraPlaceholder');
        if (placeholder) {
            placeholder.classList.remove('hidden');
            placeholder.innerHTML = `
                <span class="material-symbols-outlined text-4xl mb-1 text-secondary animate-pulse">photo_camera</span>
                <span class="text-xs font-semibold">Starting camera...</span>
                <span class="text-[10px] opacity-70 mt-1">Please allow camera permissions if prompted</span>
            `;
        }

        const showCameraError = () => {
            if (placeholder) {
                placeholder.classList.remove('hidden');
                placeholder.innerHTML = `
                    <span class="material-symbols-outlined text-3xl mb-1 text-amber-400">videocam_off</span>
                    <span class="text-xs font-semibold">Camera unavailable or blocked</span>
                    <span class="text-[10px] opacity-70 mt-1">Upload a photo or enter code manually below</span>
                `;
            }
        };

        if (window.isSecureContext === false && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
            showCameraError();
            if (placeholder) {
                const sub = placeholder.querySelector('span:last-child');
                if (sub) sub.textContent = 'Camera requires HTTPS or localhost. Upload photo or enter code manually.';
            }
            return;
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showCameraError();
            return;
        }

        const config = { fps: 12 };
        html5QrCode.start(
            { facingMode: "environment" },
            config,
            (decodedText) => {
                stopQrScanner();
                sendVerifyQrRequest(decodedText);
            },
            () => {}
        ).then(() => {
            if (placeholder) placeholder.classList.add('hidden');
        }).catch(() => {
            if (Html5Qrcode.getCameras) {
                Html5Qrcode.getCameras().then(cameras => {
                    if (cameras && cameras.length) {
                        html5QrCode.start(
                            { deviceId: { exact: cameras[0].id } },
                            config,
                            (decodedText) => {
                                stopQrScanner();
                                sendVerifyQrRequest(decodedText);
                            },
                            () => {}
                        ).then(() => {
                            if (placeholder) placeholder.classList.add('hidden');
                        }).catch(() => showCameraError());
                    } else {
                        showCameraError();
                    }
                }).catch(() => showCameraError());
            } else {
                showCameraError();
            }
        });
    }

    window.openQrScannerModal = function() {
        const modal = document.getElementById('posQrScannerModal');
        const feedback = document.getElementById('posScanFeedback');
        const manualInput = document.getElementById('posManualOrderCode');
        const photoStatus = document.getElementById('posPhotoScanStatus');

        if (feedback) {
            feedback.classList.add('hidden');
            feedback.textContent = '';
        }
        if (manualInput) {
            manualInput.value = '';
            manualInput.disabled = false;
        }
        if (photoStatus) {
            photoStatus.classList.add('hidden');
        }

        if (modal) modal.classList.remove('hidden');
        switchPosScanMode('camera');
    };

    window.stopQrScanner = function() {
        if (html5QrCode) {
            try {
                if (html5QrCode.isScanning) {
                    html5QrCode.stop().catch(e => console.error(e));
                }
            } catch (e) {
                console.error(e);
            }
        }
    };

    window.closeQrScannerModal = function() {
        stopQrScanner();
        const modal = document.getElementById('posQrScannerModal');
        if (modal) modal.classList.add('hidden');
    };

    window.handlePosPhotoUpload = function(input) {
        if (!input || !input.files || input.files.length === 0) return;
        const file = input.files[0];
        const statusEl = document.getElementById('posPhotoScanStatus');
        if (statusEl) {
            statusEl.className = 'text-xs text-center text-primary font-bold animate-pulse';
            statusEl.textContent = 'Scanning image for QR code...';
            statusEl.classList.remove('hidden');
        }

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("posQrReader");
        }

        html5QrCode.scanFile(file, true)
            .then(decodedText => {
                if (statusEl) {
                    statusEl.className = 'text-xs text-center text-green-700 font-bold';
                    statusEl.textContent = 'QR Code detected! Verifying...';
                }
                sendVerifyQrRequest(decodedText);
            })
            .catch(err => {
                if (statusEl) {
                    statusEl.className = 'text-xs text-center text-red-600 font-bold';
                    statusEl.textContent = 'Could not detect a clear QR code in this photo. Please ensure good lighting or enter the code manually.';
                    statusEl.classList.remove('hidden');
                }
            });
    };

    window.verifyManualOrderCode = function() {
        const input = document.getElementById('posManualOrderCode');
        const val = input ? input.value.trim() : '';
        if (!val) {
            const feedback = document.getElementById('posScanFeedback');
            if (feedback) {
                feedback.className = 'text-xs rounded-xl p-sm font-semibold bg-amber-100 text-amber-900 border border-amber-300';
                feedback.textContent = 'Please enter an order/request number or scan a QR code.';
                feedback.classList.remove('hidden');
            }
            return;
        }
        stopQrScanner();
        sendVerifyQrRequest(val);
    };

    // Allow Enter key in manual order code input
    const manualOrderInput = document.getElementById('posManualOrderCode');
    if (manualOrderInput) {
        manualOrderInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                verifyManualOrderCode();
            }
        });
    }

    function sendVerifyQrRequest(code) {
        const feedback = document.getElementById('posScanFeedback');
        const manualBtn = document.getElementById('posManualLookupBtn');
        const manualInput = document.getElementById('posManualOrderCode');

        if (feedback) {
            feedback.className = 'text-xs rounded-xl p-sm font-semibold bg-primary/10 text-primary border border-primary/20 flex items-center gap-xs';
            feedback.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span> Verifying Order / Request #' + code + '...';
            feedback.classList.remove('hidden');
        }
        if (manualBtn) manualBtn.disabled = true;
        if (manualInput) manualInput.disabled = true;

        const fd = new FormData();
        fd.append('qr_code', code);
        const currentCsrf = (window.getCsrfToken && window.getCsrfToken()) ? window.getCsrfToken() : CSRF_HASH_VAL;
        fd.append(CSRF_TOKEN_NAME, currentCsrf);

        fetch(`${BASE_URL}/tenant/pos/verify-qr`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
        .then(r => r.json().then(data => ({ status: r.status, data: data })))
        .then(({ status, data }) => {
            if (manualBtn) manualBtn.disabled = false;
            if (manualInput) manualInput.disabled = false;
            if (data.csrf_hash) {
                CSRF_HASH_VAL = data.csrf_hash;
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) meta.setAttribute('content', data.csrf_hash);
            }

            if (data.success) {
                if (feedback) {
                    feedback.className = 'text-xs rounded-xl p-sm font-semibold bg-green-100 text-green-900 border border-green-300';
                    feedback.textContent = `${data.message} Redirecting...`;
                }
                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 700);
            } else {
                if (feedback) {
                    feedback.className = 'text-xs rounded-xl p-sm font-semibold bg-red-100 text-red-900 border border-red-300';
                    feedback.textContent = data.error || 'Verification failed.';
                }
            }
        })
        .catch(() => {
            if (manualBtn) manualBtn.disabled = false;
            if (manualInput) manualInput.disabled = false;
            if (feedback) {
                feedback.className = 'text-xs rounded-xl p-sm font-semibold bg-red-100 text-red-900 border border-red-300';
                feedback.textContent = 'Server or network error while verifying code.';
            }
        });
    }

    // Initialize catalog
    loadCatalog('', '');
    renderCart();
})();
</script>
<?= $this->endSection() ?>
