<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<div class="flex flex-1 flex-col md:flex-row w-full min-h-[calc(100vh-72px)] bg-slate-50/50">

    <?= view('components/profile_sidebar', ['activeNav' => 'orders']) ?>

    <!-- Main Content Area -->
    <main class="flex-1 p-3 sm:p-6 md:p-8 lg:p-10 overflow-y-auto">

        <div class="max-w-5xl mx-auto space-y-6">

            <!-- Page Title & Tabs -->
            <header class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-semibold text-outline mb-1">
                            <a href="<?= base_url('customer/profile') ?>" class="hover:text-primary transition-colors">Account</a>
                            <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                            <span class="text-on-surface">My Orders</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-on-surface tracking-tight flex items-center gap-2.5">
                            <span>My Orders</span>
                            <?php if (!empty($orders)): ?>
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20">
                                    <?= count($orders) ?> <?= count($orders) === 1 ? 'order' : 'orders' ?>
                                </span>
                            <?php endif; ?>
                        </h1>
                    </div>

                    <a href="<?= base_url('/') ?>" class="inline-flex items-center gap-1.5 text-xs font-bold text-primary hover:text-primary/80 transition-colors self-start sm:self-auto">
                        <span class="material-symbols-outlined text-[18px]">storefront</span>
                        <span>Browse Marketplace</span>
                    </a>
                </div>

                <?php if (!empty($orders)): ?>
                    <?php
                        $countAll = count($orders);
                        $countActive = 0;
                        $countCompleted = 0;
                        $countCancelled = 0;
                        foreach ($orders as $o) {
                            $st = strtolower((string) ($o['status'] ?? 'pending'));
                            if ($st === 'cancelled') {
                                $countCancelled++;
                            } elseif (in_array($st, ['delivered', 'completed'], true)) {
                                $countCompleted++;
                            } else {
                                $countActive++;
                            }
                        }
                    ?>
                    <!-- Filter Tabs with Count Pills -->
                    <div class="flex border-b border-outline-variant/30 gap-2 sm:gap-4 overflow-x-auto no-scrollbar pb-px">
                        <button type="button" class="px-3.5 py-2.5 font-bold text-xs sm:text-sm text-primary border-b-2 border-primary whitespace-nowrap flex items-center gap-1.5 transition-all" data-filter="all">
                            <span>All Orders</span>
                            <span class="px-1.5 py-0.2 bg-primary/10 text-primary rounded-full text-[10px]"><?= $countAll ?></span>
                        </button>
                        <button type="button" class="px-3.5 py-2.5 font-bold text-xs sm:text-sm text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap flex items-center gap-1.5" data-filter="active">
                            <span>Active</span>
                            <span class="px-1.5 py-0.2 bg-surface-container-high text-on-surface-variant rounded-full text-[10px]"><?= $countActive ?></span>
                        </button>
                        <button type="button" class="px-3.5 py-2.5 font-bold text-xs sm:text-sm text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap flex items-center gap-1.5" data-filter="completed">
                            <span>Completed</span>
                            <span class="px-1.5 py-0.2 bg-surface-container-high text-on-surface-variant rounded-full text-[10px]"><?= $countCompleted ?></span>
                        </button>
                        <button type="button" class="px-3.5 py-2.5 font-bold text-xs sm:text-sm text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap flex items-center gap-1.5" data-filter="cancelled">
                            <span>Cancelled</span>
                            <span class="px-1.5 py-0.2 bg-surface-container-high text-on-surface-variant rounded-full text-[10px]"><?= $countCancelled ?></span>
                        </button>
                    </div>
                <?php endif; ?>
            </header>

            <?php if (!empty($orders)): ?>
                <!-- Orders List -->
                <div class="flex flex-col gap-5">
                    <?php
                        $statusIcons = [
                            'pending'          => 'schedule',
                            'processing'       => 'pending',
                            'shipped'          => 'local_shipping',
                            'in_transit'       => 'local_shipping',
                            'ready_for_pickup' => 'storefront',
                            'delivered'        => 'task_alt',
                            'completed'        => 'task_alt',
                            'cancelled'        => 'cancel',
                        ];
                        $statusPills = [
                            'pending'          => 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/30',
                            'processing'       => 'bg-blue-500/10 text-blue-700 dark:text-blue-300 border-blue-500/30',
                            'shipped'          => 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-500/30',
                            'in_transit'       => 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-500/30',
                            'ready_for_pickup' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
                            'delivered'        => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
                            'completed'        => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
                            'cancelled'        => 'bg-error/10 text-error border-error/30',
                        ];
                        $orderGroup = [
                            'pending'          => 'active',
                            'processing'       => 'active',
                            'shipped'          => 'active',
                            'in_transit'       => 'active',
                            'ready_for_pickup' => 'active',
                            'delivered'        => 'completed',
                            'completed'        => 'completed',
                            'cancelled'        => 'cancelled',
                        ];
                    ?>

                    <?php foreach ($orders as $order): ?>
                        <?php
                            $orderStatus = strtolower((string) ($order['status'] ?? 'pending'));
                            $statusIcon  = $statusIcons[$orderStatus] ?? 'receipt_long';
                            $statusPill  = $statusPills[$orderStatus] ?? 'bg-surface-container-high text-on-surface-variant border-outline-variant/30';
                            $group       = $orderGroup[$orderStatus] ?? 'active';
                            $orderedOn   = date('M d, Y', strtotime($order['placed_at'] ?? $order['created_at'] ?? 'now'));
                            $isPickup    = (($order['fulfillment_method'] ?? 'delivery') === 'pickup');
                            $items       = $order['items'] ?? [];
                            $totalItems  = count($items);
                            $shopName    = !empty($order['shop_name']) ? $order['shop_name'] : 'Merchant Store';
                            $shopLogo    = !empty($order['shop_logo']) ? $order['shop_logo'] : null;
                        ?>

                        <!-- Store Order Card (One card per store order) -->
                        <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/30 overflow-hidden shadow-2xs hover:shadow-sm transition-all" data-group="<?= esc($group) ?>" data-status="<?= esc($orderStatus) ?>">
                            
                            <!-- 1. Store Header Bar -->
                            <div class="p-4 sm:p-5 bg-surface-container-low/70 border-b border-outline-variant/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                
                                <!-- Shop Branding & Order Ref -->
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-bold text-sm overflow-hidden border border-primary/20 shrink-0">
                                        <?php if (!empty($shopLogo)): ?>
                                            <img src="<?= esc(product_image_url($shopLogo, 'thumbnail')) ?>" alt="<?= esc($shopName) ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <span class="material-symbols-outlined text-[20px]">storefront</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <a href="<?= base_url('shop/' . url_title($shopName, '-', true)) ?>" class="text-xs sm:text-sm font-bold text-on-surface hover:text-primary transition-colors truncate flex items-center gap-1">
                                                <span><?= esc($shopName) ?></span>
                                                <span class="material-symbols-outlined text-[14px] text-outline">chevron_right</span>
                                            </a>
                                        </div>
                                        <p class="text-[11px] text-on-surface-variant flex items-center gap-1.5 mt-0.5">
                                            <span class="font-mono font-bold text-primary">#<?= esc($order['order_number'] ?? ('ORD-' . $order['id'])) ?></span>
                                            <span>•</span>
                                            <span>Placed <?= esc($orderedOn) ?></span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Status & Fulfillment Pills -->
                                <div class="flex items-center gap-2 flex-wrap shrink-0">
                                    <!-- Fulfillment Method -->
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full border <?= $isPickup ? 'bg-amber-500/10 text-amber-800 dark:text-amber-300 border-amber-500/30' : 'bg-blue-500/10 text-blue-800 dark:text-blue-300 border-blue-500/30' ?>">
                                        <span class="material-symbols-outlined text-[13px]"><?= $isPickup ? 'storefront' : 'local_shipping' ?></span>
                                        <span><?= $isPickup ? 'Store Pick-up' : 'Doorstep Delivery' ?></span>
                                    </span>

                                    <!-- Status Pill -->
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-extrabold border uppercase tracking-wider <?= esc($statusPill) ?>">
                                        <span class="material-symbols-outlined text-[14px]"><?= $statusIcon ?></span>
                                        <span><?= humanize_status($orderStatus) ?></span>
                                    </span>
                                </div>

                            </div>

                            <!-- 2. Order Body: Stepper & Products -->
                            <div class="p-4 sm:p-6 space-y-5">

                                <!-- Order Progress Stepper (Hidden on Cancelled) -->
                                <?php if ($orderStatus === 'cancelled'): ?>
                                    <div class="p-3 bg-error/10 border border-error/20 rounded-2xl flex items-center gap-2.5 text-error text-xs font-semibold">
                                        <span class="material-symbols-outlined text-lg shrink-0">cancel</span>
                                        <span>This order has been cancelled. If payment was made via GCash, please contact shop support.</span>
                                    </div>
                                <?php else: ?>
                                    <?php
                                        $currentStep = match($orderStatus) {
                                            'pending'                                   => 1,
                                            'processing'                                => 2,
                                            'shipped', 'in_transit', 'ready_for_pickup' => 3,
                                            'delivered', 'completed'                    => 4,
                                            default                                     => 1,
                                        };
                                        $steps = [
                                            1 => ['label' => 'Placed', 'icon' => 'receipt'],
                                            2 => ['label' => 'Processing', 'icon' => 'sync'],
                                            3 => ['label' => $isPickup ? 'Ready for Pickup' : 'Shipped', 'icon' => $isPickup ? 'store' : 'local_shipping'],
                                            4 => ['label' => 'Completed', 'icon' => 'task_alt'],
                                        ];
                                    ?>
                                    <div class="py-1 px-2 sm:px-4">
                                        <div class="flex items-center w-full">
                                            <?php foreach ($steps as $stepNum => $stepData):
                                                $isCompleted = ($stepNum < $currentStep) || ($orderStatus === 'delivered' || $orderStatus === 'completed');
                                                $isCurrent   = ($stepNum === $currentStep) && !($orderStatus === 'delivered' || $orderStatus === 'completed');
                                            ?>
                                                <?php if ($stepNum > 1): ?>
                                                    <!-- Connector Line -->
                                                    <div class="flex-1 h-[3px] mx-1 sm:mx-2 rounded-full transition-all duration-500 <?= ($isCompleted || ($isCurrent && $stepNum <= $currentStep)) ? 'bg-primary' : 'bg-outline-variant/30' ?>"></div>
                                                <?php endif; ?>

                                                <!-- Step Node -->
                                                <div class="flex flex-col items-center gap-1.5 shrink-0">
                                                    <?php if ($isCompleted): ?>
                                                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-primary text-white flex items-center justify-center shadow-xs ring-2 ring-primary/20">
                                                            <span class="material-symbols-outlined text-[15px] sm:text-[17px]">check</span>
                                                        </div>
                                                    <?php elseif ($isCurrent): ?>
                                                        <div class="relative w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-primary text-white flex items-center justify-center shadow-sm">
                                                            <span class="absolute w-full h-full rounded-full bg-primary animate-ping opacity-30"></span>
                                                            <span class="w-2.5 h-2.5 rounded-full bg-white relative z-10"></span>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-surface-container-high border border-outline-variant/50 flex items-center justify-center text-outline">
                                                            <span class="w-2 h-2 rounded-full bg-outline-variant/60"></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <span class="text-[10px] sm:text-[11px] font-bold <?= $isCompleted ? 'text-primary' : ($isCurrent ? 'text-primary' : 'text-outline') ?> whitespace-nowrap text-center">
                                                        <?= $stepData['label'] ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Products List Inside This Store Order -->
                                <div class="bg-surface-container-low/40 rounded-2xl border border-outline-variant/20 p-3 sm:p-4 divide-y divide-outline-variant/15">
                                    <?php foreach ($items as $item): ?>
                                        <?php
                                            $itemImg = product_image_url($item['image_url'] ?? '', 'thumbnail');
                                            $pName = !empty($item['product_name']) ? $item['product_name'] : 'Product Item';
                                            $unitPrice = (float) ($item['unit_price'] ?? 0);
                                            $qty = (int) ($item['quantity'] ?? 1);
                                            $lineTotal = (float) ($item['line_total'] ?? ($unitPrice * $qty));
                                        ?>
                                        <div class="py-2.5 first:pt-0 last:pb-0 flex items-center gap-3 sm:gap-4">
                                            <!-- Thumbnail -->
                                            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl bg-surface-container overflow-hidden border border-outline-variant/30 flex items-center justify-center shrink-0">
                                                <?php if (!empty($itemImg)): ?>
                                                    <img class="w-full h-full object-cover" src="<?= esc($itemImg) ?>" alt="<?= esc($pName) ?>" loading="lazy">
                                                <?php else: ?>
                                                    <span class="material-symbols-outlined text-outline text-2xl">inventory_2</span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Product Info -->
                                            <div class="flex-1 min-w-0">
                                                <h4 class="text-xs sm:text-sm font-bold text-on-surface truncate">
                                                    <?= esc($pName) ?>
                                                </h4>
                                                <div class="flex items-center gap-2 flex-wrap mt-0.5">
                                                    <?php if (!empty($item['variant_label'])): ?>
                                                        <span class="text-[10px] font-semibold text-primary bg-primary/10 px-1.5 py-0.2 rounded border border-primary/20">
                                                            <?= esc($item['variant_label']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="text-xs text-on-surface-variant">Qty: <strong class="text-on-surface font-semibold"><?= $qty ?></strong></span>
                                                    <span class="text-xs text-outline">•</span>
                                                    <span class="text-xs text-on-surface-variant">₱<?= number_format($unitPrice, 2) ?> each</span>
                                                </div>
                                            </div>

                                            <!-- Line Subtotal -->
                                            <div class="text-right shrink-0">
                                                <p class="text-xs sm:text-sm font-extrabold text-on-surface">₱<?= number_format($lineTotal, 2) ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                            </div>

                            <!-- 3. Order Footer: Pricing & Action Buttons -->
                            <div class="px-4 py-4 sm:px-6 sm:py-4 bg-surface-container-low/50 border-t border-outline-variant/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                
                                <!-- Total Amount -->
                                <div>
                                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block">
                                        Order Total (<?= $totalItems ?> <?= $totalItems === 1 ? 'item' : 'items' ?>)
                                    </span>
                                    <p class="text-lg sm:text-xl font-black text-primary">
                                        ₱<?= number_format((float) $order['total_amount'], 2) ?>
                                    </p>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-2 flex-wrap justify-end">

                                    <!-- Rate Button (Completed Orders) -->
                                    <?php if ($orderStatus === 'delivered' || $orderStatus === 'completed'): ?>
                                        <button type="button" 
                                                class="rate-order-btn inline-flex items-center gap-1 px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition-all shadow-2xs active:scale-95 cursor-pointer"
                                                data-order='<?= esc(json_encode($order), 'attr') ?>'>
                                            <span class="material-symbols-outlined text-[16px]">star</span>
                                            <span>Rate Order</span>
                                        </button>

                                        <a href="<?= base_url('shop/' . url_title($shopName, '-', true)) ?>" class="inline-flex items-center gap-1 px-3.5 py-2 bg-surface-container-high hover:bg-surface-container-highest text-on-surface rounded-xl text-xs font-bold transition-all active:scale-95">
                                            <span class="material-symbols-outlined text-[16px]">replay</span>
                                            <span>Buy Again</span>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Store Pick-up QR Code Action -->
                                    <?php if ($isPickup && $orderStatus !== 'cancelled'): ?>
                                        <?php 
                                            $shopLoc = trim(($order['shop_address'] ?? '') . ' ' . ($order['shop_city'] ?? ''));
                                            if ($shopLoc === '') $shopLoc = 'Poblacion, Polomolok';
                                        ?>
                                        <?php if ($orderStatus === 'ready_for_pickup'): ?>
                                            <button type="button" 
                                                    class="order-qr-btn inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-white hover:bg-primary/90 rounded-xl text-xs font-bold transition-all shadow-md active:scale-95 cursor-pointer"
                                                    data-number="<?= esc($order['order_number'] ?? ('ORD-' . $order['id'])) ?>"
                                                    data-shop="<?= esc($shopName) ?>"
                                                    data-location="<?= esc($shopLoc) ?>"
                                                    data-status="<?= esc(humanize_status($orderStatus)) ?>"
                                                    data-date="<?= esc($orderedOn) ?>"
                                                    data-paid="<?= ($order['payment_status'] ?? '') === 'paid' ? 'PAID' : 'UNPAID' ?>">
                                                <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                                                <span>View Pick-up QR</span>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-[11px] text-on-surface-variant font-medium py-1.5 px-2.5 bg-surface-container/70 border border-outline-variant/30 rounded-xl inline-flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px] text-outline">schedule</span>
                                                <span>QR available when ready</span>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <!-- Doorstep Delivery Live Tracking -->
                                    <?php if (!$isPickup && $orderStatus !== 'cancelled'): ?>
                                        <?php if (in_array($orderStatus, ['shipped', 'in_transit'], true)): ?>
                                            <a href="<?= base_url('customer/orders/track/' . esc($order['order_number'] ?? $order['id'])) ?>" 
                                               class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-md active:scale-95">
                                                <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                                                <span>Track Order</span>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-[11px] text-on-surface-variant font-medium py-1.5 px-2.5 bg-surface-container/70 border border-outline-variant/30 rounded-xl inline-flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px] text-outline">schedule</span>
                                                <span>Tracking available once shipped</span>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <!-- Cancel Order (Pending only) -->
                                    <?php if (in_array($orderStatus, ['pending', 'processing'], true)): ?>
                                        <button type="button" 
                                                class="cancel-order-btn inline-flex items-center gap-1 px-3 py-2 border border-error/40 text-error hover:bg-error/10 rounded-xl text-xs font-bold transition-all active:scale-95 cursor-pointer"
                                                data-id="<?= (int) $order['id'] ?>"
                                                data-number="<?= esc($order['order_number'] ?? ('ORD-' . $order['id'])) ?>">
                                            <span class="material-symbols-outlined text-[16px]">close</span>
                                            <span>Cancel</span>
                                        </button>
                                    <?php endif; ?>

                                    <!-- Details Button -->
                                    <button type="button" class="order-details-btn inline-flex items-center gap-1 px-3.5 py-2 border border-outline-variant/50 text-on-surface hover:bg-surface-container-high rounded-xl text-xs font-bold transition-all active:scale-95 cursor-pointer" data-order='<?= esc(json_encode($order), 'attr') ?>'>
                                        <span class="material-symbols-outlined text-[16px] text-outline">info</span>
                                        <span>Details</span>
                                    </button>

                                </div>

                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <!-- Empty Orders State -->
                <div class="bg-surface-container-lowest rounded-3xl p-8 sm:p-14 text-center max-w-md mx-auto my-8 border border-outline-variant/30 shadow-sm flex flex-col items-center">
                    <div class="w-20 h-20 rounded-full bg-primary/10 text-primary flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-4xl">inventory_2</span>
                    </div>
                    <h2 class="text-xl font-bold text-on-surface mb-1">No Orders Found</h2>
                    <p class="text-xs sm:text-sm text-on-surface-variant max-w-sm mb-6 leading-relaxed">You haven't placed any marketplace or printing orders yet. Discover our verified local merchant catalogs and print services today!</p>
                    <a href="<?= base_url('/') ?>" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-bold text-xs sm:text-sm shadow-md hover:bg-primary/90 transition-all active:scale-95">
                        <span class="material-symbols-outlined text-[18px]">storefront</span>
                        <span>Explore Marketplace</span>
                    </a>
                </div>
            <?php endif; ?>

        </div>

    </main>

</div>

<!-- Order Details Modal -->
<div id="order-details-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="order-details-overlay"></div>
    <div class="relative bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-2xl w-full max-w-lg p-5 sm:p-7 flex flex-col gap-4 z-10 max-h-[90vh] overflow-y-auto animate-scale-up">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-3">
            <div>
                <span class="text-[10px] uppercase font-bold text-outline tracking-wider block">Order Details</span>
                <h3 class="text-base sm:text-lg font-bold text-primary font-mono" id="od-number">#ORD-00000</h3>
            </div>
            <button type="button" id="od-close" class="p-1.5 rounded-full hover:bg-surface-container-high text-on-surface-variant transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <div class="grid grid-cols-2 gap-2.5 text-xs bg-surface-container-low p-3.5 rounded-2xl border border-outline-variant/20">
            <div>
                <span class="text-[10px] uppercase font-bold text-outline block">Merchant Store</span>
                <span id="od-shop" class="font-bold text-on-surface"></span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-outline block">Date Placed</span>
                <span id="od-date" class="text-on-surface font-medium"></span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-outline block">Fulfillment</span>
                <span id="od-fulfillment" class="text-on-surface font-medium capitalize"></span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-outline block">Payment</span>
                <span id="od-payment" class="font-bold uppercase text-primary"></span>
            </div>
        </div>

        <div>
            <h4 class="text-[11px] uppercase font-extrabold text-outline tracking-wider mb-2">Purchased Items</h4>
            <div id="od-items-list" class="space-y-2 divide-y divide-outline-variant/10"></div>
        </div>

        <div class="border-t border-outline-variant/20 pt-3 flex justify-between items-center text-sm">
            <span class="font-bold text-on-surface">Grand Total</span>
            <span id="od-total" class="font-black text-primary text-lg sm:text-xl">₱0.00</span>
        </div>

        <button type="button" id="od-done" class="w-full py-3 bg-surface-container-high hover:bg-surface-container-highest text-on-surface rounded-xl text-xs font-bold transition-all">Close</button>
    </div>
</div>

<!-- Order Pick-up QR Modal -->
<div id="order-qr-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="order-qr-overlay"></div>
    <div class="relative bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-2xl w-full max-w-sm p-6 flex flex-col items-center gap-4 text-center z-10 animate-scale-up">
        <div class="flex justify-between items-center w-full border-b border-outline-variant/20 pb-3">
            <div class="flex items-center gap-1.5 text-primary font-bold text-sm">
                <span class="material-symbols-outlined text-xl">qr_code_2</span>
                <span>Store Pick-up Pass</span>
            </div>
            <button type="button" id="order-qr-close" class="p-1 rounded-full hover:bg-surface-container-high text-on-surface-variant transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>
        
        <div class="flex flex-col items-center justify-center bg-white p-5 rounded-2xl shadow-inner border border-slate-100 w-full max-w-[220px] aspect-square mx-auto">
            <div id="order-qr-canvas" class="flex justify-center items-center w-full h-full"></div>
        </div>
        
        <div class="flex flex-col items-center w-full space-y-1">
            <span class="text-[10px] uppercase tracking-widest text-outline font-bold">Order Identifier</span>
            <span id="order-qr-number" class="text-base font-mono font-black text-primary">#ORD-00000</span>
            
            <div class="flex items-center justify-center gap-1.5 mt-1">
                <span id="order-qr-shop" class="text-xs font-bold text-on-surface"></span>
                <span class="text-outline">•</span>
                <span id="order-qr-payment" class="text-[10px] font-bold px-2 py-0.5 rounded-full"></span>
            </div>

            <div id="order-qr-location-box" class="text-[11px] text-on-surface-variant mt-1 flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-[14px] text-primary">location_on</span>
                <span id="order-qr-location">Poblacion, Polomolok</span>
            </div>

            <p id="order-qr-instructions" class="text-[11px] text-on-surface-variant/80 mt-2 leading-relaxed italic px-2">
                Present this QR code to the cashier at <span id="order-qr-inst-shop" class="font-semibold text-on-surface not-italic">the store</span> to claim your order.
            </p>
        </div>

        <div class="grid grid-cols-2 gap-2 w-full pt-1">
            <button type="button" id="order-qr-download" class="w-full py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-[16px]">download</span>
                <span>Download</span>
            </button>
            <button type="button" id="order-qr-done" class="w-full py-2.5 bg-primary text-white rounded-xl text-xs font-bold hover:bg-primary/90 transition-all shadow-sm active:scale-95">Close</button>
        </div>
    </div>
</div>

<!-- Rate Order / Product & Shop Modal -->
<div id="rate-modal" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="rate-overlay"></div>
    <div class="relative bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-2xl w-full max-w-lg p-5 sm:p-7 flex flex-col gap-4 z-10 max-h-[90vh] overflow-y-auto animate-scale-up">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-3">
            <div>
                <span class="text-[10px] uppercase font-bold text-outline tracking-wider block">Customer Feedback</span>
                <h3 class="text-base sm:text-lg font-bold text-on-surface" id="rate-title">Rate Your Order Experience</h3>
            </div>
            <button type="button" id="rate-close" class="p-1.5 rounded-full hover:bg-surface-container-high text-on-surface-variant transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form id="rate-form" class="space-y-4">
            <input type="hidden" id="rate-shop-id" value="">
            <input type="hidden" id="rate-product-id" value="">
            <input type="hidden" id="rate-order-id" value="">

            <!-- Shop Rating -->
            <div class="bg-surface-container-low p-3.5 rounded-2xl border border-outline-variant/20 space-y-2">
                <div class="flex justify-between items-center">
                    <label class="text-xs font-bold text-on-surface uppercase tracking-wide">Shop Service: <span id="rate-shop-name" class="text-primary font-bold">Store</span></label>
                    <span id="rate-shop-val-text" class="text-xs font-bold text-amber-500">5 / 5</span>
                </div>
                <div class="flex items-center gap-1" id="shop-star-group">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="shop-star text-amber-400 hover:scale-110 transition-transform" data-val="<?= $i ?>">
                            <span class="material-symbols-outlined text-[26px]" style="font-variation-settings: 'FILL' 1;">star</span>
                        </button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" id="rate-shop-score" value="5">
                <textarea id="rate-shop-comment" rows="2" placeholder="How was the shop's fulfillment speed and service?" class="w-full text-xs p-2.5 rounded-xl border border-outline-variant/40 bg-surface focus:border-primary focus:ring-1 focus:ring-primary"></textarea>
            </div>

            <!-- Product Rating -->
            <div class="bg-surface-container-low p-3.5 rounded-2xl border border-outline-variant/20 space-y-2" id="product-rating-section">
                <div class="flex justify-between items-center">
                    <label class="text-xs font-bold text-on-surface uppercase tracking-wide">Product Quality: <span id="rate-product-name" class="text-primary font-bold">Product</span></label>
                    <span id="rate-prod-val-text" class="text-xs font-bold text-amber-500">5 / 5</span>
                </div>
                <div class="flex items-center gap-1" id="prod-star-group">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="prod-star text-amber-400 hover:scale-110 transition-transform" data-val="<?= $i ?>">
                            <span class="material-symbols-outlined text-[26px]" style="font-variation-settings: 'FILL' 1;">star</span>
                        </button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" id="rate-prod-score" value="5">
                <textarea id="rate-prod-comment" rows="2" placeholder="How was the product quality and packaging?" class="w-full text-xs p-2.5 rounded-xl border border-outline-variant/40 bg-surface focus:border-primary focus:ring-1 focus:ring-primary"></textarea>
            </div>

            <div id="rate-error" class="hidden p-2.5 bg-error/10 border border-error/30 rounded-xl text-xs text-error font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px] shrink-0">error</span>
                <span id="rate-error-text">Failed to submit review.</span>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-outline-variant/20">
                <button type="button" id="rate-cancel" class="py-2.5 px-4 bg-surface-container-high hover:bg-surface-container-highest text-on-surface rounded-xl text-xs font-bold transition-all">Cancel</button>
                <button type="submit" id="rate-submit-btn" class="py-2.5 px-6 bg-primary hover:bg-primary/90 text-white rounded-xl text-xs font-bold transition-all shadow-md active:scale-95 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">send</span>
                    <span>Submit Reviews</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Cancel Order Confirmation Modal -->
<div id="cancel-order-modal" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="cancel-order-overlay"></div>
    <div class="relative bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-2xl w-full max-w-md p-5 sm:p-6 flex flex-col gap-3.5 z-10 animate-scale-up">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-error/10 text-error flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">warning</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-on-surface" id="cancel-modal-title">Cancel Order?</h3>
                <p class="text-xs text-on-surface-variant font-medium">This action cannot be undone</p>
            </div>
        </div>

        <p class="text-xs sm:text-sm text-on-surface-variant leading-relaxed">
            Are you sure you want to cancel this order? Any reserved stocks will be restored to the merchant immediately.
        </p>

        <div id="cancel-modal-error" class="hidden p-2.5 bg-error/10 border border-error/30 rounded-xl text-xs text-error font-semibold flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px] shrink-0">error</span>
            <span id="cancel-error-text">Failed to cancel order.</span>
        </div>

        <div class="flex items-center gap-2 pt-2 border-t border-outline-variant/20">
            <button type="button" id="cancel-modal-keep" class="flex-1 py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface rounded-xl text-xs font-bold transition-all">Keep Order</button>
            <button type="button" id="cancel-modal-confirm" class="flex-1 py-2.5 bg-error hover:bg-error/90 text-white rounded-xl text-xs font-bold transition-all shadow-md active:scale-95 flex items-center justify-center gap-1">
                <span id="cancel-spinner" class="material-symbols-outlined text-[16px] animate-spin hidden">progress_activity</span>
                <span id="cancel-confirm-text">Yes, Cancel</span>
            </button>
        </div>
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

    // Store Pick-up QR Modal logic
    var qrModal    = document.getElementById('order-qr-modal');
    var qrOverlay  = document.getElementById('order-qr-overlay');
    var qrClose    = document.getElementById('order-qr-close');
    var qrDone     = document.getElementById('order-qr-done');
    var qrCanvas   = document.getElementById('order-qr-canvas');
    var qrNumber   = document.getElementById('order-qr-number');
    var qrShop     = document.getElementById('order-qr-shop');
    var qrPayment  = document.getElementById('order-qr-payment');
    var qrLocation = document.getElementById('order-qr-location');
    var qrInstShop = document.getElementById('order-qr-inst-shop');

    function openQr(orderNum, shopName, isPaid, locationText) {
        if (!qrModal) return;
        qrNumber.textContent = '#' + orderNum;
        qrShop.textContent = shopName || 'Storefront';
        if (qrInstShop) qrInstShop.textContent = shopName || 'the store attendant';
        if (qrLocation) qrLocation.textContent = locationText || 'Poblacion, Polomolok';
        
        if (isPaid === 'PAID') {
            qrPayment.textContent = 'PAID ONLINE';
            qrPayment.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30';
        } else {
            qrPayment.textContent = 'PAY AT COUNTER';
            qrPayment.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/30';
        }

        qrCanvas.innerHTML = '';
        if (window.QRCode) {
            new QRCode(qrCanvas, {
                text: orderNum,
                width: 190,
                height: 190,
                colorDark: '#0f172a',
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
            openQr(btn.dataset.number, btn.dataset.shop, btn.dataset.paid, btn.dataset.location);
        });
    });

    if (qrOverlay) qrOverlay.addEventListener('click', closeQr);
    if (qrClose) qrClose.addEventListener('click', closeQr);
    if (qrDone) qrDone.addEventListener('click', closeQr);

    var qrDownloadBtn = document.getElementById('order-qr-download');
    if (qrDownloadBtn) {
        qrDownloadBtn.addEventListener('click', function () {
            var canvas = qrCanvas.querySelector('canvas');
            var img = qrCanvas.querySelector('img');
            var dataUrl = null;
            if (canvas) {
                dataUrl = canvas.toDataURL('image/png');
            } else if (img && img.src) {
                dataUrl = img.src;
            }
            if (dataUrl) {
                var a = document.createElement('a');
                a.href = dataUrl;
                a.download = 'BLAX-Pickup-QR-' + (qrNumber.textContent.replace('#', '') || 'order') + '.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
        });
    }

    // Rate Order / Product Modal Logic
    var rateModal      = document.getElementById('rate-modal');
    var rateOverlay    = document.getElementById('rate-overlay');
    var rateClose      = document.getElementById('rate-close');
    var rateCancel     = document.getElementById('rate-cancel');
    var rateForm       = document.getElementById('rate-form');
    var rateTitle      = document.getElementById('rate-title');
    var rateShopName   = document.getElementById('rate-shop-name');
    var rateProdName   = document.getElementById('rate-product-name');
    var rateShopId     = document.getElementById('rate-shop-id');
    var rateProdId     = document.getElementById('rate-product-id');
    var rateOrderId    = document.getElementById('rate-order-id');
    var rateShopScore  = document.getElementById('rate-shop-score');
    var rateProdScore  = document.getElementById('rate-prod-score');
    var rateShopComment= document.getElementById('rate-shop-comment');
    var rateProdComment= document.getElementById('rate-prod-comment');
    var rateShopValText= document.getElementById('rate-shop-val-text');
    var rateProdValText= document.getElementById('rate-prod-val-text');
    var rateErr        = document.getElementById('rate-error');
    var rateErrText    = document.getElementById('rate-error-text');

    function closeRateModal() {
        if (rateModal) rateModal.classList.add('hidden');
    }

    if (rateOverlay) rateOverlay.addEventListener('click', closeRateModal);
    if (rateClose) rateClose.addEventListener('click', closeRateModal);
    if (rateCancel) rateCancel.addEventListener('click', closeRateModal);

    function updateStars(containerSelector, score, textEl) {
        var stars = document.querySelectorAll(containerSelector + ' .shop-star, ' + containerSelector + ' .prod-star');
        stars.forEach(function (s) {
            var val = parseInt(s.dataset.val, 10);
            var icon = s.querySelector('.material-symbols-outlined');
            if (icon) {
                if (val <= score) {
                    icon.style.fontVariationSettings = "'FILL' 1";
                    s.classList.remove('opacity-40');
                } else {
                    icon.style.fontVariationSettings = "'FILL' 0";
                    s.classList.add('opacity-40');
                }
            }
        });
        if (textEl) textEl.textContent = score + ' / 5';
    }

    document.querySelectorAll('#shop-star-group .shop-star').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var val = parseInt(this.dataset.val, 10) || 5;
            rateShopScore.value = val;
            updateStars('#shop-star-group', val, rateShopValText);
        });
    });

    document.querySelectorAll('#prod-star-group .prod-star').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var val = parseInt(this.dataset.val, 10) || 5;
            rateProdScore.value = val;
            updateStars('#prod-star-group', val, rateProdValText);
        });
    });

    document.querySelectorAll('.rate-order-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            try {
                var order = JSON.parse(this.getAttribute('data-order'));
                if (!order) return;
                rateOrderId.value = order.id || '';
                rateShopId.value = order.shop_id || '';
                rateShopName.textContent = order.shop_name || 'Store';
                
                var firstItem = (order.items && order.items.length > 0) ? order.items[0] : null;
                if (firstItem) {
                    rateProdId.value = firstItem.product_id || '';
                    rateProdName.textContent = firstItem.product_name || 'Product';
                    document.getElementById('product-rating-section').classList.remove('hidden');
                } else {
                    rateProdId.value = '';
                    document.getElementById('product-rating-section').classList.add('hidden');
                }

                rateShopScore.value = '5';
                rateProdScore.value = '5';
                rateShopComment.value = '';
                rateProdComment.value = '';
                updateStars('#shop-star-group', 5, rateShopValText);
                updateStars('#prod-star-group', 5, rateProdValText);
                if (rateErr) rateErr.classList.add('hidden');

                if (rateModal) rateModal.classList.remove('hidden');
            } catch (err) {
                console.error('Failed to open rate modal:', err);
            }
        });
    });

    if (rateForm) {
        rateForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var csrfToken = (typeof window.getCsrfToken === 'function') ? window.getCsrfToken() : '';
            var csrfHeader = (typeof window.getCsrfHeader === 'function') ? window.getCsrfHeader() : 'X-CSRF-TOKEN';
            var submitBtn = document.getElementById('rate-submit-btn');
            if (submitBtn) submitBtn.disabled = true;

            var promises = [];
            if (rateShopId.value) {
                var shopData = new URLSearchParams({
                    shop_id: rateShopId.value,
                    rating: rateShopScore.value,
                    review: rateShopComment.value,
                    order_id: rateOrderId.value
                });
                promises.push(fetch('<?= base_url('customer/reviews/shop') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', [csrfHeader]: csrfToken },
                    body: shopData
                }).then(function(res) { return res.json(); }));
            }

            if (rateProdId.value) {
                var prodData = new URLSearchParams({
                    product_id: rateProdId.value,
                    rating: rateProdScore.value,
                    review: rateProdComment.value,
                    order_id: rateOrderId.value
                });
                promises.push(fetch('<?= base_url('customer/reviews/product') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', [csrfHeader]: csrfToken },
                    body: prodData
                }).then(function(res) { return res.json(); }));
            }

            Promise.all(promises).then(function () {
                if (submitBtn) submitBtn.disabled = false;
                closeRateModal();
                if (typeof showToast === 'function') {
                    showToast('Thank you! Your review has been submitted.', 'success');
                } else {
                    alert('Thank you! Your review has been submitted.');
                }
            }).catch(function () {
                if (submitBtn) submitBtn.disabled = false;
                if (rateErr && rateErrText) {
                    rateErrText.textContent = 'Failed to submit reviews. Please try again.';
                    rateErr.classList.remove('hidden');
                }
            });
        });
    }

    // Cancel Order modal logic
    var cancelModal   = document.getElementById('cancel-order-modal');
    var cancelOverlay = document.getElementById('cancel-order-overlay');
    var cancelKeep    = document.getElementById('cancel-modal-keep');
    var cancelConfirm = document.getElementById('cancel-modal-confirm');
    var cancelTitle   = document.getElementById('cancel-modal-title');
    var cancelErr     = document.getElementById('cancel-modal-error');
    var cancelErrText = document.getElementById('cancel-error-text');
    var cancelSpinner = document.getElementById('cancel-spinner');
    var cancelText    = document.getElementById('cancel-confirm-text');

    var currentCancelId = null;

    function openCancelModal(orderId, orderNumber) {
        currentCancelId = orderId;
        if (cancelTitle) cancelTitle.textContent = 'Cancel Order #' + orderNumber + '?';
        if (cancelErr) cancelErr.classList.add('hidden');
        if (cancelModal) cancelModal.classList.remove('hidden');
    }

    function closeCancelModal() {
        if (cancelModal) cancelModal.classList.add('hidden');
        currentCancelId = null;
    }

    if (cancelOverlay) cancelOverlay.addEventListener('click', closeCancelModal);
    if (cancelKeep) cancelKeep.addEventListener('click', closeCancelModal);

    document.querySelectorAll('.cancel-order-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            openCancelModal(this.getAttribute('data-id'), this.getAttribute('data-number'));
        });
    });

    if (cancelConfirm) {
        cancelConfirm.addEventListener('click', function() {
            if (!currentCancelId) return;

            cancelSpinner.classList.remove('hidden');
            cancelText.textContent = 'Cancelling...';
            cancelConfirm.disabled = true;

            var csrfToken = (typeof window.getCsrfToken === 'function') ? window.getCsrfToken() : '';
            var csrfHeader = (typeof window.getCsrfHeader === 'function') ? window.getCsrfHeader() : 'X-CSRF-TOKEN';

            fetch('<?= base_url('customer/orders') ?>/' + currentCancelId + '/cancel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    [csrfHeader]: csrfToken
                },
                body: new URLSearchParams({
                    'order_id': currentCancelId
                })
            })
            .then(function(res) {
                return res.json().then(function(data) {
                    return { ok: res.ok, status: res.status, data: data };
                });
            })
            .then(function(result) {
                cancelSpinner.classList.add('hidden');
                cancelText.textContent = 'Yes, Cancel';
                cancelConfirm.disabled = false;

                if (result.ok && result.data && result.data.success) {
                    closeCancelModal();
                    if (typeof showToast === 'function') {
                        showToast(result.data.message || 'Order cancelled successfully.', 'success');
                    }
                    setTimeout(function() {
                        window.location.reload();
                    }, 400);
                } else {
                    var errorMsg = (result.data && result.data.error) ? result.data.error : 'Failed to cancel order.';
                    if (cancelErr && cancelErrText) {
                        cancelErrText.textContent = errorMsg;
                        cancelErr.classList.remove('hidden');
                    }
                }
            })
            .catch(function() {
                cancelSpinner.classList.add('hidden');
                cancelText.textContent = 'Yes, Cancel';
                cancelConfirm.disabled = false;
                if (cancelErr && cancelErrText) {
                    cancelErrText.textContent = 'Network error. Please try again.';
                    cancelErr.classList.remove('hidden');
                }
            });
        });
    }

    // Order Details Modal logic
    var odModal   = document.getElementById('order-details-modal');
    var odOverlay = document.getElementById('order-details-overlay');
    var odClose   = document.getElementById('od-close');
    var odDone    = document.getElementById('od-done');
    var odNumber  = document.getElementById('od-number');
    var odShop    = document.getElementById('od-shop');
    var odDate    = document.getElementById('od-date');
    var odFulfill = document.getElementById('od-fulfillment');
    var odPayment = document.getElementById('od-payment');
    var odItems   = document.getElementById('od-items-list');
    var odTotal   = document.getElementById('od-total');

    function closeOdModal() {
        if (odModal) odModal.classList.add('hidden');
    }

    function escapeHtml(str) {
        if (!str) return '';
        var p = document.createElement('p');
        p.textContent = str;
        return p.innerHTML;
    }

    if (odOverlay) odOverlay.addEventListener('click', closeOdModal);
    if (odClose) odClose.addEventListener('click', closeOdModal);
    if (odDone) odDone.addEventListener('click', closeOdModal);

    document.querySelectorAll('.order-details-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            try {
                var raw = this.getAttribute('data-order');
                if (!raw) return;
                var order = JSON.parse(raw);
                if (odNumber) odNumber.textContent = '#' + (order.order_number || ('ORD-' + order.id));
                if (odShop) odShop.textContent = order.shop_name || 'Merchant Store';
                if (odDate) odDate.textContent = order.placed_at || order.created_at || '—';
                if (odFulfill) odFulfill.textContent = order.fulfillment_method || 'Delivery';
                if (odPayment) odPayment.textContent = (order.payment_method || 'Cash') + ' (' + (order.payment_status || 'Pending') + ')';
                if (odTotal) odTotal.textContent = '₱' + parseFloat(order.total_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                if (odItems) {
                    odItems.innerHTML = '';
                    (order.items || []).forEach(function (it) {
                        var div = document.createElement('div');
                        div.className = 'pt-2 flex items-center justify-between gap-3 text-xs sm:text-sm';
                        var nameSpan = document.createElement('div');
                        nameSpan.className = 'flex-1 min-w-0';
                        var pName = it.product_name || 'Product Item';
                        var pHtml = '<p class="font-bold text-on-surface truncate">' + escapeHtml(pName) + '</p>';
                        if (it.variant_label) {
                            pHtml += '<span class="text-[10px] font-semibold text-primary bg-primary/10 px-1.5 py-0.2 rounded border border-primary/20">' + escapeHtml(it.variant_label) + '</span>';
                        }
                        nameSpan.innerHTML = pHtml;

                        var qtyPrice = document.createElement('div');
                        qtyPrice.className = 'text-right shrink-0';
                        var qty = parseInt(it.quantity, 10) || 1;
                        var uPrice = parseFloat(it.unit_price || 0);
                        var lineTot = parseFloat(it.line_total || (qty * uPrice));
                        qtyPrice.innerHTML = '<span class="text-[11px] text-on-surface-variant">' + qty + ' × ₱' + uPrice.toFixed(2) + '</span><p class="font-bold text-on-surface">₱' + lineTot.toFixed(2) + '</p>';

                        div.appendChild(nameSpan);
                        div.appendChild(qtyPrice);
                        odItems.appendChild(div);
                    });
                }

                if (odModal) odModal.classList.remove('hidden');
            } catch (err) {
                console.error('Failed to parse order details:', err);
            }
        });
    });
})();
</script>
<?php endif; ?>

<?= $this->endSection() ?>