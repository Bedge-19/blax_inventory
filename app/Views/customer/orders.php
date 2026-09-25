<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<div class="flex flex-1 flex-col md:flex-row w-full min-h-[calc(100vh-72px)] bg-slate-50/50">

    <?= view('components/profile_sidebar', ['activeNav' => 'orders']) ?>

    <!-- Main Content Area -->
    <main class="flex-1 p-4 md:p-8 lg:p-10 overflow-y-auto">

        <div class="max-w-6xl mx-auto space-y-6">

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

                                        <?php $isPickupCard = (($order['fulfillment_method'] ?? 'delivery') === 'pickup'); ?>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full border <?= $isPickupCard ? 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60' : 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800/60' ?>">
                                            <span class="material-symbols-outlined text-[13px]"><?= $isPickupCard ? 'storefront' : 'local_shipping' ?></span>
                                            <span><?= $isPickupCard ? 'Store Pick-up' : 'Doorstep Delivery' ?></span>
                                        </span>

                                        <span class="text-on-surface-variant text-label-sm">Ordered on <?= esc($orderedOn) ?></span>

                                    </div>

                                </div>

                                <span class="text-title-lg font-title-lg text-primary font-bold">₱<?= number_format((float) $order['total_amount'], 2) ?></span>

                            </div>

                            <!-- Order Tracking Timeline -->
                            <?php
                                $isCancelled = ($orderStatus === 'cancelled');
                                $isPickup = (($order['fulfillment_method'] ?? 'delivery') === 'pickup');
                                $currentStep = match($orderStatus) {
                                    'pending'                                  => 1,
                                    'processing'                               => 2,
                                    'shipped', 'in_transit', 'ready_for_pickup' => 3,
                                    'delivered', 'completed'                   => 4,
                                    default                                    => 1,
                                };
                                $steps = [
                                    1 => ['label' => 'Placed'],
                                    2 => ['label' => 'Processing'],
                                    3 => ['label' => $isPickup ? 'Ready' : 'Shipped'],
                                    4 => ['label' => 'Completed'],
                                ];
                            ?>
                            <?php if ($isCancelled): ?>
                                <div class="my-sm py-xs px-sm bg-error-container/20 border border-error-container/50 rounded-xl flex items-center gap-xs text-error text-xs font-semibold">
                                    <span class="material-symbols-outlined text-[16px]">cancel</span>
                                    <span>This order has been cancelled.</span>
                                </div>
                            <?php else: ?>
                                <div class="my-md py-sm">
                                    <div class="flex items-center w-full">
                                        <?php foreach ($steps as $stepNum => $stepData):
                                            $isCompleted = ($stepNum < $currentStep) || ($orderStatus === 'delivered' || $orderStatus === 'completed');
                                            $isCurrent   = ($stepNum === $currentStep) && !($orderStatus === 'delivered' || $orderStatus === 'completed');
                                        ?>
                                            <?php if ($stepNum > 1): ?>
                                                <!-- Connector line -->
                                                <div class="flex-1 h-[3px] mx-0.5 rounded-full transition-all duration-500 <?= ($isCompleted || ($isCurrent && $stepNum <= $currentStep)) ? 'bg-blue-600' : 'bg-outline-variant/30' ?>"></div>
                                            <?php endif; ?>

                                            <!-- Step node -->
                                            <div class="flex flex-col items-center gap-1 shrink-0">
                                                <?php if ($isCompleted): ?>
                                                    <!-- Completed step -->
                                                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-sm ring-2 ring-blue-600/20">
                                                        <span class="material-symbols-outlined text-[16px]">check</span>
                                                    </div>
                                                <?php elseif ($isCurrent): ?>
                                                    <!-- Active step -->
                                                    <div class="relative w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-sm">
                                                        <span class="absolute w-full h-full rounded-full bg-blue-500 animate-ping opacity-30"></span>
                                                        <span class="w-3 h-3 rounded-full bg-white relative z-10"></span>
                                                    </div>
                                                <?php else: ?>
                                                    <!-- Pending step -->
                                                    <div class="w-8 h-8 rounded-full bg-surface-container-high border-2 border-outline-variant/50 flex items-center justify-center">
                                                        <span class="w-2 h-2 rounded-full bg-outline-variant/60"></span>
                                                    </div>
                                                <?php endif; ?>
                                                <span class="text-[10px] md:text-[11px] whitespace-nowrap font-bold <?= $isCompleted ? 'text-blue-700' : ($isCurrent ? 'text-blue-600' : 'text-on-surface-variant/60') ?>"><?= $stepData['label'] ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="space-y-xs mb-md bg-surface-container-low/50 p-sm rounded-xl border border-outline-variant/20">
                                <?php foreach (array_slice($order['items'] ?? [], 0, 2) as $item): ?>
                                    <?php
                                        $itemImg = product_image_url($item['image_url'] ?? '', 'thumbnail');
                                        $pName = !empty($item['product_name']) ? $item['product_name'] : 'Product Item';
                                    ?>
                                    <div class="flex items-center gap-sm">
                                        <div class="relative w-12 h-12 rounded-lg overflow-hidden border border-outline-variant/30 bg-surface-container-low flex items-center justify-center shrink-0">
                                            <?php if (!empty($itemImg)): ?>
                                                <img class="w-full h-full object-cover" src="<?= esc($itemImg) ?>" alt="<?= esc($pName) ?>">
                                            <?php else: ?>
                                                <span class="material-symbols-outlined text-outline text-lg">inventory_2</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-body-sm font-semibold text-on-surface truncate">
                                                <?= esc($pName) ?>
                                                <?php if (!empty($item['variant_label'])): ?>
                                                    <span class="text-[11px] font-medium text-primary bg-primary/10 px-1.5 py-0.5 rounded ml-1"><?= esc($item['variant_label']) ?></span>
                                                <?php endif; ?>
                                            </p>
                                            <p class="text-label-sm text-on-surface-variant">Qty: <?= (int) ($item['quantity'] ?? 1) ?> · ₱<?= number_format((float) ($item['unit_price'] ?? 0), 2) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($extraItems > 0): ?>
                                    <p class="text-xs text-on-surface-variant font-medium pt-1 border-t border-outline-variant/15">+<?= $extraItems ?> more item(s) in this order</p>
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

                        <div class="flex md:flex-col justify-end gap-sm md:w-52 shrink-0">

                            <?php if ($orderStatus !== 'cancelled'): ?>

                                <?php if ($orderStatus === 'delivered' || $orderStatus === 'completed'): ?>

                                    <button type="button" 
                                            class="rate-order-btn flex-1 md:flex-none bg-amber-500 hover:bg-amber-600 text-white py-sm px-md rounded-lg font-button text-button transition-colors flex items-center justify-center gap-xs shadow-sm"
                                            data-order='<?= esc(json_encode($order), 'attr') ?>'>
                                        <span class="material-symbols-outlined text-[16px]">star</span>
                                        <span>Rate Product & Shop</span>
                                    </button>

                                    <a href="<?= base_url('shop/' . url_title($order['shop_name'] ?? 'rhk', '-', true)) ?>" class="flex-1 md:flex-none text-center bg-surface-container-highest text-on-surface py-sm px-md rounded-lg font-button text-button hover:bg-outline-variant transition-colors">Buy Again</a>

                                <?php elseif (($order['fulfillment_method'] ?? 'delivery') === 'pickup'): ?>

                                    <?php 
                                        $shopLoc = trim(($order['shop_address'] ?? '') . ' ' . ($order['shop_city'] ?? ''));
                                        if ($shopLoc === '') $shopLoc = 'Poblacion, Polomolok';
                                    ?>
                                    <?php if ($orderStatus === 'ready_for_pickup'): ?>
                                        <button type="button" 
                                                class="order-qr-btn flex-1 md:flex-none bg-primary text-on-primary py-sm px-md rounded-lg font-button text-button hover:bg-primary-container transition-all flex items-center justify-center gap-xs shadow-sm active:scale-95"
                                                data-number="<?= esc($order['order_number'] ?? ('ORD-' . $order['id'])) ?>"
                                                data-shop="<?= esc($order['shop_name'] ?? 'Storefront') ?>"
                                                data-location="<?= esc($shopLoc) ?>"
                                                data-status="<?= esc(humanize_status($orderStatus)) ?>"
                                                data-date="<?= esc($orderedOn) ?>"
                                                data-paid="<?= ($order['payment_status'] ?? '') === 'paid' ? 'PAID' : 'UNPAID' ?>">
                                            <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                                            <span>View Pick-up QR Code</span>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-xs text-on-surface-variant/70 font-medium text-center py-sm px-md bg-surface-container/70 border border-outline-variant/30 rounded-lg flex items-center justify-center gap-1">
                                            <span class="material-symbols-outlined text-[15px] text-outline">schedule</span>
                                            <span>QR available when ready</span>
                                        </span>
                                    <?php endif; ?>

                                <?php else: ?>

                                    <?php if (in_array($orderStatus, ['shipped', 'in_transit'], true)): ?>
                                        <a href="<?= base_url('customer/orders/track/' . esc($order['order_number'] ?? $order['id'])) ?>" 
                                           class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition gap-1.5 flex-1 md:flex-none">
                                            <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                                            <span>Track Order</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-on-surface-variant/70 font-medium text-center py-sm px-md bg-surface-container/70 border border-outline-variant/30 rounded-lg flex items-center justify-center gap-1">
                                            <span class="material-symbols-outlined text-[15px] text-outline">schedule</span>
                                            <span>Tracking available once shipped</span>
                                        </span>
                                    <?php endif; ?>

                                <?php endif; ?>

                            <?php endif; ?>

                            <!-- Cancel Order Button: only visible strictly if status is pending or processing -->
                            <?php if (in_array($orderStatus, ['pending', 'processing'], true)): ?>
                                <button type="button" 
                                        class="cancel-order-btn flex-1 md:flex-none border border-error/50 text-error hover:bg-error-container/20 py-sm px-md rounded-lg font-button text-button transition-colors flex items-center justify-center gap-xs"
                                        data-id="<?= (int) $order['id'] ?>"
                                        data-number="<?= esc($order['order_number'] ?? ('ORD-' . $order['id'])) ?>">
                                    <span class="material-symbols-outlined text-[16px]">close</span>
                                    <span>Cancel Order</span>
                                </button>
                            <?php endif; ?>

                            <button type="button" class="order-details-btn flex-1 md:flex-none border border-outline-variant text-on-surface py-sm px-md rounded-lg font-button text-button hover:bg-surface-container-high transition-colors" data-order='<?= esc(json_encode($order), 'attr') ?>'>Order Details</button>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="glass-card rounded-2xl p-xxl text-center max-w-md mx-auto my-xl border border-outline-variant/30 flex flex-col items-center">
                <div class="w-20 h-20 rounded-full bg-primary/10 text-primary flex items-center justify-center mb-md">
                    <span class="material-symbols-outlined text-4xl">inventory_2</span>
                </div>
                <h2 class="text-title-lg font-bold text-on-surface mb-xs">No orders yet</h2>
                <p class="text-body-md text-on-surface-variant max-w-md mb-lg">When you place orders for products or printing services, you will be able to track their progress and pick-up QR codes right here.</p>
                <a href="<?= base_url('/') ?>" class="inline-flex items-center gap-xs bg-primary text-on-primary px-lg py-md rounded-xl font-button hover:bg-primary-container transition-all shadow-sm">
                    <span class="material-symbols-outlined text-[20px]">storefront</span>
                    <span>Explore Marketplace</span>
                </a>
            </div>

        <?php endif; ?>

        </div>

    </main>

</div>

<!-- Order Details Modal -->
<div id="order-details-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-md">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="order-details-overlay"></div>
    <div class="relative bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-2xl w-full max-w-lg p-lg md:p-xl flex flex-col gap-md z-10 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-md">
            <div>
                <span class="text-[10px] uppercase font-bold text-outline tracking-wider">Order Details</span>
                <h3 class="text-title-lg font-bold text-primary font-mono" id="od-number">#ORD-00000</h3>
            </div>
            <button type="button" id="od-close" class="p-1 rounded-full hover:bg-surface-container-high text-on-surface-variant transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="grid grid-cols-2 gap-sm text-sm bg-surface-container-low p-md rounded-2xl border border-outline-variant/20">
            <div>
                <span class="text-[10px] uppercase font-bold text-outline block">Shop</span>
                <span id="od-shop" class="font-semibold text-on-surface"></span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-outline block">Date Placed</span>
                <span id="od-date" class="text-on-surface"></span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-outline block">Fulfillment</span>
                <span id="od-fulfillment" class="text-on-surface capitalize"></span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-outline block">Payment</span>
                <span id="od-payment" class="font-semibold uppercase text-primary"></span>
            </div>
        </div>

        <div>
            <h4 class="text-xs uppercase font-bold text-outline tracking-wider mb-sm">Purchased Items</h4>
            <div id="od-items-list" class="space-y-sm divide-y divide-outline-variant/10"></div>
        </div>

        <div class="border-t border-outline-variant/20 pt-md space-y-xs text-sm">
            <div class="flex justify-between text-on-surface-variant">
                <span>Total Amount</span>
                <span id="od-total" class="font-bold text-primary text-title-md">₱0.00</span>
            </div>
        </div>

        <button type="button" id="od-done" class="w-full py-md bg-surface-container-high hover:bg-surface-container-highest text-on-surface rounded-xl text-button font-button transition-all">Close</button>
    </div>
</div>

<!-- Order Pick-up QR Modal -->
<div id="order-qr-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-md">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="order-qr-overlay"></div>
    <div class="relative bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-2xl w-full max-w-sm p-xl flex flex-col items-center gap-md text-center z-10">
        <div class="flex justify-between items-center w-full border-b border-outline-variant/20 pb-md">
            <div class="flex items-center gap-xs text-primary font-bold">
                <span class="material-symbols-outlined text-2xl">qr_code_2</span>
                <span class="text-title-md">Store Pick-up Pass</span>
            </div>
            <button type="button" id="order-qr-close" class="p-1 rounded-full hover:bg-surface-container-high text-on-surface-variant transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <div class="flex flex-col items-center justify-center bg-white p-6 rounded-2xl shadow-inner border border-slate-100 w-full max-w-[240px] aspect-square mx-auto">
            <div id="order-qr-canvas" class="flex justify-center items-center w-full h-full"></div>
        </div>
        
        <div class="flex flex-col items-center w-full space-y-1">
            <span class="text-[10px] uppercase tracking-widest text-outline font-bold">Order Identifier</span>
            <span id="order-qr-number" class="text-headline-sm font-mono font-bold text-primary">#ORD-00000</span>
            
            <div class="flex items-center justify-center gap-2 mt-1">
                <span id="order-qr-shop" class="text-xs font-semibold text-on-surface"></span>
                <span class="text-on-surface-variant/40">•</span>
                <span id="order-qr-payment" class="text-[11px] font-bold px-2 py-0.5 rounded-full"></span>
            </div>

            <div id="order-qr-location-box" class="text-[11px] text-on-surface-variant/90 mt-1 flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-[14px] text-primary">location_on</span>
                <span id="order-qr-location">Poblacion, Polomolok</span>
            </div>

            <p id="order-qr-instructions" class="text-xs text-on-surface-variant/80 mt-2.5 leading-relaxed italic px-2">
                Present this QR code to the cashier at <span id="order-qr-inst-shop" class="font-semibold text-on-surface not-italic">the store</span> to verify and collect your order.
            </p>

            <div class="mt-3 p-2 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-center gap-1.5 text-amber-800 text-[11px] font-medium text-left w-full">
                <span class="material-symbols-outlined text-[16px] text-amber-600 shrink-0">brightness_high</span>
                <span>Turn up screen brightness for fast mobile scanner reading</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 w-full">
            <button type="button" id="order-qr-download" class="w-full py-md bg-surface-container-highest hover:bg-outline-variant text-on-surface rounded-xl text-button font-button transition-all flex items-center justify-center gap-xs">
                <span class="material-symbols-outlined text-[18px]">download</span>
                <span>Download QR</span>
            </button>
            <button type="button" id="order-qr-done" class="w-full py-md bg-primary text-on-primary rounded-xl text-button font-button hover:bg-primary-container transition-all active:scale-95 shadow-md">Close</button>
        </div>
    </div>
</div>

<!-- Rate Order / Product & Shop Modal -->
<div id="rate-modal" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-md">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="rate-overlay"></div>
    <div class="relative bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-2xl w-full max-w-lg p-lg md:p-xl flex flex-col gap-md z-10 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-md">
            <div>
                <span class="text-[10px] uppercase font-bold text-outline tracking-wider">Leave Feedback</span>
                <h3 class="text-title-lg font-bold text-on-surface" id="rate-title">Rate Your Order</h3>
            </div>
            <button type="button" id="rate-close" class="p-1 rounded-full hover:bg-surface-container-high text-on-surface-variant transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="rate-form" class="space-y-lg">
            <input type="hidden" id="rate-shop-id" value="">
            <input type="hidden" id="rate-product-id" value="">
            <input type="hidden" id="rate-order-id" value="">

            <!-- Shop Rating -->
            <div class="bg-surface-container-low p-md rounded-2xl border border-outline-variant/20 space-y-xs">
                <div class="flex justify-between items-center">
                    <label class="text-xs font-bold text-on-surface uppercase tracking-wide">Shop Rating: <span id="rate-shop-name" class="text-primary font-semibold">Store</span></label>
                    <span id="rate-shop-val-text" class="text-xs font-bold text-amber-500">5 / 5</span>
                </div>
                <div class="flex items-center gap-1" id="shop-star-group">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="shop-star text-amber-400 hover:scale-110 transition-transform" data-val="<?= $i ?>">
                            <span class="material-symbols-outlined text-[28px]" style="font-variation-settings: 'FILL' 1;">star</span>
                        </button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" id="rate-shop-score" value="5">
                <textarea id="rate-shop-comment" rows="2" placeholder="How was the seller's service and order packaging?" class="w-full text-xs p-2.5 rounded-xl border border-outline-variant/40 bg-surface focus:border-primary focus:ring-1 focus:ring-primary"></textarea>
            </div>

            <!-- Product Rating -->
            <div class="bg-surface-container-low p-md rounded-2xl border border-outline-variant/20 space-y-xs" id="product-rating-section">
                <div class="flex justify-between items-center">
                    <label class="text-xs font-bold text-on-surface uppercase tracking-wide">Product Rating: <span id="rate-product-name" class="text-primary font-semibold">Product</span></label>
                    <span id="rate-prod-val-text" class="text-xs font-bold text-amber-500">5 / 5</span>
                </div>
                <div class="flex items-center gap-1" id="prod-star-group">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="prod-star text-amber-400 hover:scale-110 transition-transform" data-val="<?= $i ?>">
                            <span class="material-symbols-outlined text-[28px]" style="font-variation-settings: 'FILL' 1;">star</span>
                        </button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" id="rate-prod-score" value="5">
                <textarea id="rate-prod-comment" rows="2" placeholder="How was the quality of the item received?" class="w-full text-xs p-2.5 rounded-xl border border-outline-variant/40 bg-surface focus:border-primary focus:ring-1 focus:ring-primary"></textarea>
            </div>

            <div id="rate-error" class="hidden p-sm bg-error-container/20 border border-error-container/50 rounded-xl text-xs text-error font-semibold flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">error</span>
                <span id="rate-error-text">Failed to submit review.</span>
            </div>

            <div class="flex justify-end gap-sm pt-xs border-t border-outline-variant/20">
                <button type="button" id="rate-cancel" class="py-sm px-lg bg-surface-container-high hover:bg-surface-container-highest text-on-surface rounded-xl font-button text-button transition-all font-semibold">Cancel</button>
                <button type="submit" id="rate-submit-btn" class="py-sm px-xl bg-primary hover:bg-primary-container text-on-primary rounded-xl font-button text-button transition-all font-semibold flex items-center justify-center gap-xs shadow-md active:scale-95">
                    <span class="material-symbols-outlined text-[18px]">send</span>
                    <span>Submit Reviews</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Cancel Order Confirmation Modal -->
<div id="cancel-order-modal" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-md">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="cancel-order-overlay"></div>
    <div class="relative bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-2xl w-full max-w-md p-lg md:p-xl flex flex-col gap-md z-10">
        <div class="flex items-center gap-sm">
            <div class="w-12 h-12 rounded-2xl bg-error-container/20 flex items-center justify-center shrink-0 text-error">
                <span class="material-symbols-outlined text-2xl">warning</span>
            </div>
            <div>
                <h3 class="text-title-lg font-bold text-on-surface" id="cancel-modal-title">Cancel Order?</h3>
                <p class="text-xs text-on-surface-variant font-medium">Permanent Action</p>
            </div>
        </div>

        <p class="text-body-md text-on-surface-variant leading-relaxed">
            Are you sure you want to cancel this order? This action cannot be undone.
        </p>

        <div id="cancel-modal-error" class="hidden p-sm bg-error-container/20 border border-error-container/50 rounded-xl text-xs text-error font-semibold flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">error</span>
            <span id="cancel-error-text">Failed to cancel order.</span>
        </div>

        <div class="flex items-center gap-sm pt-sm border-t border-outline-variant/20">
            <button type="button" id="cancel-modal-keep" class="flex-1 py-md bg-surface-container-high hover:bg-surface-container-highest text-on-surface rounded-xl font-button text-button transition-all font-semibold">Keep Order</button>
            <button type="button" id="cancel-modal-confirm" class="flex-1 py-md bg-error hover:bg-error/90 text-white rounded-xl font-button text-button transition-all font-semibold flex items-center justify-center gap-xs shadow-md active:scale-95">
                <span id="cancel-spinner" class="material-symbols-outlined text-[16px] animate-spin hidden">progress_activity</span>
                <span id="cancel-confirm-text">Yes, Cancel Order</span>
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
                    showToast('Thank you! Your reviews have been submitted.', 'success');
                } else {
                    alert('Thank you! Your reviews have been submitted.');
                }
            }).catch(function (err) {
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
        if (cancelTitle) {
            cancelTitle.textContent = 'Cancel Order #' + orderNumber + '?';
        }
        if (cancelErr) {
            cancelErr.classList.add('hidden');
        }
        if (cancelModal) {
            cancelModal.classList.remove('hidden');
        }
    }

    function closeCancelModal() {
        if (cancelModal) {
            cancelModal.classList.add('hidden');
        }
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
                cancelText.textContent = 'Yes, Cancel Order';
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
            .catch(function(err) {
                cancelSpinner.classList.add('hidden');
                cancelText.textContent = 'Yes, Cancel Order';
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
                if (odShop) odShop.textContent = order.shop_name || 'Blax Marketplace Merchant';
                if (odDate) odDate.textContent = order.placed_at || order.created_at || '—';
                if (odFulfill) odFulfill.textContent = order.fulfillment_method || 'Delivery';
                if (odPayment) odPayment.textContent = (order.payment_method || 'Cash') + ' (' + (order.payment_status || 'Pending') + ')';
                if (odTotal) odTotal.textContent = '₱' + parseFloat(order.total_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                if (odItems) {
                    odItems.innerHTML = '';
                    (order.items || []).forEach(function (it) {
                        var div = document.createElement('div');
                        div.className = 'pt-sm flex items-center justify-between gap-sm text-sm';
                        var nameSpan = document.createElement('div');
                        nameSpan.className = 'flex-1 min-w-0';
                        var pName = it.product_name || 'Product Item';
                        var pHtml = '<p class="font-semibold text-on-surface truncate">' + escapeHtml(pName) + '</p>';
                        if (it.variant_label) {
                            pHtml += '<span class="text-[10px] font-semibold text-primary bg-primary/10 px-1.5 py-0.5 rounded">' + escapeHtml(it.variant_label) + '</span>';
                        }
                        nameSpan.innerHTML = pHtml;

                        var qtyPrice = document.createElement('div');
                        qtyPrice.className = 'text-right shrink-0';
                        var qty = parseInt(it.quantity, 10) || 1;
                        var uPrice = parseFloat(it.unit_price || 0);
                        var lineTot = parseFloat(it.line_total || (qty * uPrice));
                        qtyPrice.innerHTML = '<span class="text-xs text-on-surface-variant font-medium">' + qty + ' × ₱' + uPrice.toFixed(2) + '</span><p class="font-bold text-on-surface">₱' + lineTot.toFixed(2) + '</p>';

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