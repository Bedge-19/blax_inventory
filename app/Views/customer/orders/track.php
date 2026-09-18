<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<style>
    /* Pulsing radar effects for live tracking */
    @keyframes pulseRing {
        0% { transform: scale(0.95); opacity: 0.8; }
        50% { transform: scale(1.35); opacity: 0.2; }
        100% { transform: scale(1.6); opacity: 0; }
    }
    .pulse-ring {
        animation: pulseRing 2s cubic-bezier(0.24, 0, 0.38, 1) infinite;
    }
    @keyframes activeDotPulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.25); opacity: 0.7; }
    }
    .active-dot-pulse {
        animation: activeDotPulse 1.8s ease-in-out infinite;
    }
    /* Map Container Style */
    #trackMap {
        width: 100%;
        height: 100%;
        min-height: 480px;
        background-color: #f8fafc;
    }
    .gm-style-iw {
        font-family: inherit !important;
        border-radius: 12px !important;
        padding: 4px 8px !important;
    }
</style>

<div class="bg-surface/50 min-h-screen py-4 md:py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        
        <!-- Main 2-Column Responsive Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- ========================================================= -->
            <!-- LEFT COLUMN (5 Cols): Order & Progress Details            -->
            <!-- ========================================================= -->
            <div class="lg:col-span-5 flex flex-col gap-5">
                
                <!-- 1. Top Navigation & Back Button -->
                <div class="flex items-center justify-between">
                    <a href="<?= base_url('customer/orders') ?>" 
                       class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:text-primary/80 transition-colors group">
                        <span class="material-symbols-outlined text-[18px] transition-transform group-hover:-translate-x-1">arrow_back</span>
                        <span>Back to My Orders</span>
                    </a>

                    <!-- Status & Fulfillment Badges -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <!-- Fulfillment Method Badge -->
                        <?php 
                            $fulfillmentMethod = strtolower(trim((string) ($order['fulfillment_method'] ?? 'delivery')));
                            $isPickup = ($fulfillmentMethod === 'pickup' || $fulfillmentMethod === 'store pick-up');
                            $fulfillmentLabel = $isPickup ? 'Store Pick-up' : 'Doorstep Delivery';
                        ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-2xs <?= $isPickup ? 'bg-amber-50 text-amber-800 border-amber-300 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60' : 'bg-blue-50 text-blue-800 border-blue-300 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800/60' ?>">
                            <span class="material-symbols-outlined text-[15px]"><?= $isPickup ? 'storefront' : 'local_shipping' ?></span>
                            <span><?= esc($fulfillmentLabel) ?></span>
                        </span>

                        <!-- Status Badge -->
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold tracking-wide <?= esc($badgeClass) ?>">
                            <span class="w-2 h-2 rounded-full <?= ($statusBadge === 'Delivered') ? 'bg-emerald-500' : (($statusBadge === 'Cancelled') ? 'bg-red-500' : 'bg-blue-600 active-dot-pulse') ?>"></span>
                            <?= esc($statusBadge) ?>
                        </span>
                    </div>
                </div>

                <!-- Order Header Card with Prominent Product Image & Name -->
                <div class="bg-surface-container-lowest dark:bg-surface-container-low border border-outline-variant/30 rounded-2xl p-4 md:p-5 shadow-xs">
                    <div class="flex items-center justify-between pb-4 border-b border-outline-variant/20">
                        <div>
                            <span class="text-xs uppercase font-medium text-on-surface-variant/70 tracking-wider">Order Reference</span>
                            <h1 class="text-lg md:text-xl font-headline-md font-bold text-on-surface mt-0.5">
                                #<?= esc($order['order_number'] ?? ('ORD-' . $order['id'])) ?>
                            </h1>
                            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                <p class="text-xs text-on-surface-variant/80 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[15px] text-outline">storefront</span>
                                    <span>Sold by: <strong class="text-on-surface"><?= esc($shop['shop_name'] ?? 'Store Partner') ?></strong></span>
                                </p>

                                <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-md border <?= $isPickup ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-blue-50 text-blue-800 border-blue-200' ?>">
                                    <span class="material-symbols-outlined text-[13px]"><?= $isPickup ? 'storefront' : 'local_shipping' ?></span>
                                    <span><?= esc($fulfillmentLabel) ?></span>
                                </span>
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="text-xs text-on-surface-variant/70">Total Amount</span>
                            <div class="text-base md:text-lg font-bold text-primary font-mono">
                                ₱<?= number_format((float) ($order['total_amount'] ?? 0), 2) ?>
                            </div>
                            <span class="inline-block mt-0.5 text-[11px] px-2 py-0.5 rounded font-medium <?= ($order['payment_status'] ?? '') === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                                <?= strtoupper($order['payment_status'] ?? 'UNPAID') ?>
                            </span>
                        </div>
                    </div>

                    <!-- Prominently displayed Ordered Product(s) -->
                    <div class="pt-4 space-y-3">
                        <span class="text-xs uppercase font-semibold text-on-surface-variant/80 tracking-wider flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] text-primary">inventory_2</span>
                            <span>Ordered <?= count($items) > 1 ? 'Items (' . count($items) . ')' : 'Product' ?></span>
                        </span>

                        <?php foreach ($items as $item): ?>
                            <?php 
                                $itemImg = $item['image_url'] ?? '';
                                if (!empty($itemImg) && !str_starts_with($itemImg, 'http://') && !str_starts_with($itemImg, 'https://')) {
                                    $itemImg = base_url($itemImg);
                                }
                                $pName = !empty($item['product_name']) ? $item['product_name'] : 'Product Item';
                            ?>
                            <div class="flex items-center gap-3.5 p-3 rounded-xl bg-surface-container/50 dark:bg-surface-container/30 border border-outline-variant/25 transition-all hover:bg-surface-container">
                                <!-- Product Image -->
                                <div class="relative w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden bg-white dark:bg-surface-container border border-outline-variant/30 flex items-center justify-center shrink-0 shadow-2xs">
                                    <?php if (!empty($itemImg)): ?>
                                        <img src="<?= esc($itemImg) ?>" 
                                             alt="<?= esc($pName) ?>" 
                                             class="w-full h-full object-cover object-center" 
                                             onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');" />
                                        <div class="hidden w-full h-full flex items-center justify-center text-outline">
                                            <span class="material-symbols-outlined text-2xl">image_not_supported</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="w-full h-full flex flex-col items-center justify-center text-outline bg-surface-container-high/50">
                                            <span class="material-symbols-outlined text-2xl text-primary/70">inventory_2</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Product Name & Details -->
                                <div class="flex-1 min-w-0">
                                    <h2 class="text-sm sm:text-base font-bold text-on-surface line-clamp-2 leading-snug">
                                        <?= esc($pName) ?>
                                    </h2>
                                    <?php if (!empty($item['variant_label'])): ?>
                                        <div class="mt-1">
                                            <span class="inline-block text-[11px] font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded-md">
                                                <?= esc($item['variant_label']) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex items-center justify-between text-xs text-on-surface-variant mt-2 pt-1 border-t border-outline-variant/15">
                                        <span class="font-medium">Quantity: <strong class="text-on-surface font-bold text-xs sm:text-sm"><?= (int) ($item['quantity'] ?? 1) ?></strong></span>
                                        <span class="font-mono font-bold text-xs sm:text-sm text-primary">₱<?= number_format((float) ($item['unit_price'] ?? 0), 2) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2. Estimated Delivery Card with Dynamic Progress Bar -->
                <div class="bg-gradient-to-br from-blue-500/10 via-primary/5 to-transparent border border-blue-500/20 dark:border-blue-500/30 rounded-2xl p-5 shadow-xs relative overflow-hidden">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-1.5 text-blue-600 dark:text-blue-400 text-xs font-semibold uppercase tracking-wider">
                                <span class="material-symbols-outlined text-[17px]">schedule</span>
                                <span>Arrival Estimate</span>
                            </div>
                            <h2 class="text-base sm:text-lg font-bold text-on-surface mt-1">
                                <?= esc($estimatedArrival) ?>
                            </h2>
                            <p class="text-xs text-on-surface-variant/80 mt-0.5">
                                <?= strtolower($order['status'] ?? '') === 'delivered' ? 'Package successfully delivered' : 'Standard Doorstep Delivery via Local Fleet' ?>
                            </p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-sm shadow-blue-500/20">
                            <span class="material-symbols-outlined text-[22px]">local_shipping</span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-4 pt-1">
                        <div class="flex justify-between items-center text-xs font-medium text-on-surface-variant mb-1.5">
                            <span>Fulfillment Progress</span>
                            <span class="font-bold text-primary"><?= (int) $progressPercent ?>%</span>
                        </div>
                        <div class="w-full bg-surface-container-high rounded-full h-2.5 overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 h-2.5 rounded-full transition-all duration-700 ease-out" 
                                 style="width: <?= (int) $progressPercent ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Milestone Stepper -->
                <div class="bg-surface-container-lowest dark:bg-surface-container-low border border-outline-variant/30 rounded-2xl p-5 shadow-xs">
                    <h2 class="text-sm font-bold text-on-surface tracking-tight mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-primary">timeline</span>
                        <span>Delivery Timeline</span>
                    </h2>

                    <div class="relative pl-6 space-y-6 before:absolute before:left-[11px] before:top-2.5 before:bottom-2.5 before:w-0.5 before:bg-outline-variant/40">
                        <?php foreach ($milestones as $idx => $m): ?>
                            <?php 
                                $isDone = ($m['state'] === 'completed');
                                $isActive = ($m['state'] === 'active');
                            ?>
                            <div class="relative flex items-start group">
                                <!-- Stepper Node Dot / Icon -->
                                <div class="absolute -left-6 mt-0.5 flex items-center justify-center">
                                    <?php if ($isDone): ?>
                                        <div class="w-6 h-6 rounded-full bg-primary text-on-primary flex items-center justify-center ring-4 ring-primary/10 shadow-xs">
                                            <span class="material-symbols-outlined text-[14px]">check</span>
                                        </div>
                                    <?php elseif ($isActive): ?>
                                        <div class="relative w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-xs">
                                            <span class="absolute w-full h-full rounded-full bg-blue-500 pulse-ring"></span>
                                            <span class="w-2.5 h-2.5 rounded-full bg-white"></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="w-6 h-6 rounded-full bg-surface-container-high border-2 border-outline-variant text-outline flex items-center justify-center">
                                            <span class="w-1.5 h-1.5 rounded-full bg-outline"></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Milestone Content -->
                                <div class="ml-3 flex-1 min-w-0">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <h3 class="text-xs sm:text-sm font-bold <?= $isDone ? 'text-on-surface' : ($isActive ? 'text-blue-600 dark:text-blue-400' : 'text-on-surface-variant/70') ?>">
                                            <?= esc($m['title']) ?>
                                        </h3>
                                        <span class="text-[11px] font-medium text-on-surface-variant/60 whitespace-nowrap">
                                            <?= esc($m['timestamp']) ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-on-surface-variant/80 mt-0.5">
                                        <?= esc($m['description']) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 5. Shipping Address & Items Collapsible -->
                <div class="bg-surface-container-lowest dark:bg-surface-container-low border border-outline-variant/30 rounded-2xl p-5 shadow-xs" x-data="{ open: false }">
                    <div class="flex items-center justify-between pb-3 border-b border-outline-variant/20">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-primary">location_on</span>
                            <span class="text-xs uppercase font-bold text-on-surface tracking-wider">Destination Address</span>
                        </div>
                    </div>
                    <p class="text-xs sm:text-sm font-medium text-on-surface mt-2.5 leading-relaxed">
                        <?= esc($destAddressText) ?>
                    </p>
                    <?php if (!empty($shippingAddress['recipient_name'])): ?>
                        <p class="text-xs text-on-surface-variant/80 mt-1">
                            Recipient: <strong class="text-on-surface"><?= esc($shippingAddress['recipient_name']) ?></strong> 
                            <?= !empty($shippingAddress['phone']) ? '• ' . esc($shippingAddress['phone']) : '' ?>
                        </p>
                    <?php endif; ?>

                    <!-- Items Collapsible Toggle -->
                    <div class="mt-4 pt-3 border-t border-outline-variant/20">
                        <button type="button" 
                                onclick="toggleItemsCollapsible()" 
                                id="items-toggle-btn"
                                class="w-full flex items-center justify-between py-1 text-xs font-semibold text-primary hover:text-primary/80 transition-colors">
                            <span class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px]">shopping_bag</span>
                                <span>Order Items Summary (<?= count($items) ?> item<?= count($items) > 1 ? 's' : '' ?>)</span>
                            </span>
                            <span class="material-symbols-outlined text-[18px] transition-transform duration-200" id="items-toggle-chevron">expand_more</span>
                        </button>

                        <div id="items-collapsible-content" class="hidden mt-3 space-y-3 pt-1">
                            <?php foreach ($items as $item): ?>
                                <div class="flex items-center gap-3 p-2 rounded-xl bg-surface-container/50 border border-outline-variant/20">
                                    <?php if (!empty($item['image_url'])): ?>
                                        <img src="<?= esc($item['image_url']) ?>" 
                                             alt="<?= esc($item['product_name'] ?? 'Item') ?>" 
                                             class="w-12 h-12 rounded-lg object-cover bg-white border border-outline-variant/30 shrink-0" 
                                             onerror="this.src='https://placehold.co/100x100?text=Item'" />
                                    <?php else: ?>
                                        <div class="w-12 h-12 rounded-lg bg-surface-container-high flex items-center justify-center text-outline shrink-0">
                                            <span class="material-symbols-outlined text-[20px]">package_2</span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-xs font-bold text-on-surface truncate">
                                            <?= esc($item['product_name'] ?? ('Product #' . ($item['product_id'] ?? ''))) ?>
                                        </h4>
                                        <div class="flex items-center justify-between text-xs text-on-surface-variant/80 mt-0.5">
                                            <span>Qty: <strong class="text-on-surface"><?= (int) ($item['quantity'] ?? 1) ?></strong></span>
                                            <span class="font-mono font-semibold text-on-surface">₱<?= number_format((float) ($item['unit_price'] ?? 0), 2) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Summary Subtotals -->
                            <div class="p-3 bg-surface-container/30 rounded-xl space-y-1.5 text-xs text-on-surface-variant">
                                <div class="flex justify-between">
                                    <span>Subtotal</span>
                                    <span class="font-mono">₱<?= number_format((float) ($order['subtotal'] ?? 0), 2) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Delivery Fee</span>
                                    <span class="font-mono">₱<?= number_format((float) ($order['shipping_fee'] ?? 0), 2) ?></span>
                                </div>
                                <div class="flex justify-between font-bold text-on-surface pt-1.5 border-t border-outline-variant/20 text-xs sm:text-sm">
                                    <span>Total</span>
                                    <span class="font-mono text-primary">₱<?= number_format((float) ($order['total_amount'] ?? 0), 2) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ========================================================= -->
            <!-- RIGHT COLUMN (7 Cols): Live Tracking Map                   -->
            <!-- ========================================================= -->
            <div class="lg:col-span-7">
                <div class="sticky top-20">
                    
                    <!-- Map Card Container -->
                    <div class="relative w-full rounded-2xl overflow-hidden shadow-sm border border-outline-variant/30 bg-slate-100 min-h-[550px] h-[calc(100vh_-_140px)] flex flex-col">
                        
                        <!-- The Actual Google Maps Element -->
                        <div id="trackMap" class="w-full h-full flex-grow z-[1]" style="min-height:400px;"></div>

                        <!-- Floating Header Controls -->
                        <div class="absolute top-4 left-4 right-4 z-[400] flex items-center justify-between pointer-events-none">
                            
                            <!-- Pulsating "Live" Badge -->
                            <div class="pointer-events-auto flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/95 dark:bg-gray-900/90 backdrop-blur-md shadow-md border border-outline-variant/30">
                                <span class="relative flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                                </span>
                                <span class="text-xs font-bold text-gray-800 dark:text-gray-100 tracking-wide uppercase">Live Route Tracking</span>
                            </div>

                            <!-- "Center on Courier" Control Button -->
                            <button type="button" 
                                    id="btn-center-courier"
                                    onclick="centerOnCourier()"
                                    title="Center Map on Courier"
                                    class="pointer-events-auto inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/30 transition-all hover:scale-105 active:scale-95">
                                <span class="material-symbols-outlined text-[16px]">my_location</span>
                                <span>Center on Courier</span>
                            </button>
                        </div>

                        <!-- Floating Footer Map Legend & Route Details -->
                        <div class="absolute bottom-4 left-4 right-4 z-[400] bg-white/95 dark:bg-gray-900/90 backdrop-blur-md p-3 rounded-xl border border-outline-variant/30 shadow-md">
                            <div class="grid grid-cols-3 gap-2 text-center divide-x divide-outline-variant/30">
                                <div class="flex flex-col items-center">
                                    <div class="flex items-center gap-1 text-[11px] font-semibold text-gray-600 dark:text-gray-300">
                                        <span class="w-2.5 h-2.5 rounded-full bg-slate-900 dark:bg-slate-300 inline-block"></span>
                                        <span>Store Hub</span>
                                    </div>
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 truncate max-w-full font-medium">
                                        <?= esc($shop['shop_name'] ?? 'Merchant') ?>
                                    </span>
                                </div>

                                <div class="flex flex-col items-center">
                                    <div class="flex items-center gap-1 text-[11px] font-semibold text-blue-600 dark:text-blue-400">
                                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600 inline-block"></span>
                                        <span>Courier</span>
                                    </div>
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 truncate max-w-full font-medium">
                                        <?= esc($courierName) ?>
                                    </span>
                                </div>

                                <div class="flex flex-col items-center">
                                    <div class="flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                                        <span>Destination</span>
                                    </div>
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 truncate max-w-full font-medium">
                                        Customer Address
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
    // Map Coordinates Data passed from Controller
    const STORE_COORDS   = <?= json_encode($storeCoords) ?>;
    const DEST_COORDS    = <?= json_encode($destCoords) ?>;
    const COURIER_COORDS = <?= json_encode($courierCoords) ?>;
    const SHOP_NAME      = <?= json_encode($shop['shop_name'] ?? 'Store Hub') ?>;
    const DEST_ADDRESS   = <?= json_encode($destAddressText) ?>;
    const COURIER_NAME   = <?= json_encode($courierName) ?>;
    const ORDER_STATUS   = <?= json_encode($order['status'] ?? 'pending') ?>;

    let trackMap          = null;
    let courierMarker     = null;
    let storeMarker       = null;
    let destMarker        = null;
    let courierInfoWindow = null;
    let routePolyline     = null;

    // Handle Google Maps API authentication / activation failure
    window.gm_authFailure = function() {
        const container = document.getElementById('trackMap');
        if (container) {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full p-6 text-center bg-amber-50/80 dark:bg-amber-950/30 border-2 border-dashed border-amber-300 rounded-2xl">
                    <span class="material-symbols-outlined text-4xl text-amber-600 mb-2">warning</span>
                    <h3 class="font-bold text-amber-900 dark:text-amber-200 text-base">Google Maps API Activation Required</h3>
                    <p class="text-xs text-amber-700 dark:text-amber-300/80 max-w-md mt-1">
                        The Google Cloud project (<strong>300493013944</strong>) has not yet activated the <strong>Maps JavaScript API</strong> or the API Key needs permissions.
                    </p>
                    <a href="https://console.cloud.google.com/apis/library/maps-backend.googleapis.com" target="_blank" class="mt-3 inline-flex items-center gap-1 px-3 py-1.5 bg-amber-600 text-white rounded-lg text-xs font-semibold hover:bg-amber-700 transition-colors">
                        <span>Enable Maps JavaScript API</span>
                        <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                    </a>
                </div>
            `;
        }
    };

    function createMarkerContent(type) {
        const div = document.createElement('div');
        if (type === 'store') {
            div.innerHTML = `
                <div style="width:36px;height:36px;border-radius:50%;background:#0f172a;border:2.5px solid #ffffff;box-shadow:0 3px 8px rgba(0,0,0,0.35);display:flex;align-items:center;justify-content:center;color:#fff;cursor:pointer;">
                    <span class="material-symbols-outlined" style="font-size:18px;line-height:1;">storefront</span>
                </div>
            `;
        } else if (type === 'dest') {
            div.innerHTML = `
                <div style="width:36px;height:36px;border-radius:50%;background:#10b981;border:2.5px solid #ffffff;box-shadow:0 3px 8px rgba(16,185,129,0.35);display:flex;align-items:center;justify-content:center;color:#fff;cursor:pointer;">
                    <span class="material-symbols-outlined" style="font-size:18px;line-height:1;">home</span>
                </div>
            `;
        } else if (type === 'courier') {
            div.innerHTML = `
                <div style="position:relative;width:40px;height:40px;display:flex;align-items:center;justify-content:center;cursor:pointer;">
                    <div style="position:absolute;width:100%;height:100%;border-radius:50%;background:rgba(37,99,235,0.3);animation:pulseRing 1.8s infinite;"></div>
                    <div style="width:34px;height:34px;border-radius:50%;background:#2563eb;border:2.5px solid #ffffff;box-shadow:0 3px 10px rgba(37,99,235,0.5);display:flex;align-items:center;justify-content:center;color:#fff;z-index:2;">
                        <span class="material-symbols-outlined" style="font-size:18px;line-height:1;">two_wheeler</span>
                    </div>
                </div>
            `;
        }
        return div;
    }

    window.initGoogleTrackMap = function() {
        const container = document.getElementById('trackMap');
        if (!container || typeof google === 'undefined' || !google.maps) return;

        const storeLatLng   = { lat: parseFloat(STORE_COORDS[0]), lng: parseFloat(STORE_COORDS[1]) };
        const destLatLng    = { lat: parseFloat(DEST_COORDS[0]), lng: parseFloat(DEST_COORDS[1]) };
        const courierLatLng = { lat: parseFloat(COURIER_COORDS[0]), lng: parseFloat(COURIER_COORDS[1]) };

        trackMap = new google.maps.Map(container, {
            center: courierLatLng,
            zoom: 14,
            mapId: 'DEMO_MAP_ID',
            disableDefaultUI: false,
            zoomControl: true,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true
        });

        // 1. Store Marker
        const storeContent = createMarkerContent('store');
        if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
            storeMarker = new google.maps.marker.AdvancedMarkerElement({
                map: trackMap,
                position: storeLatLng,
                content: storeContent,
                title: SHOP_NAME
            });
        } else {
            storeMarker = new google.maps.Marker({
                map: trackMap,
                position: storeLatLng,
                title: SHOP_NAME
            });
        }
        const storeInfoWindow = new google.maps.InfoWindow({
            content: `
                <div style="font-family:inherit;padding:4px;">
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Store Dispatch Hub</div>
                    <div style="font-size:13px;font-weight:700;color:#0f172a;margin-top:2px;">${SHOP_NAME}</div>
                    <div style="font-size:11px;color:#475569;margin-top:2px;">Polomolok Hub</div>
                </div>
            `
        });
        storeMarker.addListener('click', () => {
            storeInfoWindow.open(trackMap, storeMarker);
        });

        // 2. Customer Destination Marker
        const destContent = createMarkerContent('dest');
        if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
            destMarker = new google.maps.marker.AdvancedMarkerElement({
                map: trackMap,
                position: destLatLng,
                content: destContent,
                title: 'Delivery Destination'
            });
        } else {
            destMarker = new google.maps.Marker({
                map: trackMap,
                position: destLatLng,
                title: 'Delivery Destination'
            });
        }
        const destInfoWindow = new google.maps.InfoWindow({
            content: `
                <div style="font-family:inherit;padding:4px;">
                    <div style="font-size:11px;font-weight:700;color:#059669;text-transform:uppercase;">Delivery Destination</div>
                    <div style="font-size:12px;font-weight:600;color:#0f172a;margin-top:2px;">${DEST_ADDRESS}</div>
                </div>
            `
        });
        destMarker.addListener('click', () => {
            destInfoWindow.open(trackMap, destMarker);
        });

        // 3. Courier Marker
        const courierContent = createMarkerContent('courier');
        if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
            courierMarker = new google.maps.marker.AdvancedMarkerElement({
                map: trackMap,
                position: courierLatLng,
                content: courierContent,
                title: COURIER_NAME
            });
        } else {
            courierMarker = new google.maps.Marker({
                map: trackMap,
                position: courierLatLng,
                title: COURIER_NAME
            });
        }
        courierInfoWindow = new google.maps.InfoWindow({
            content: `
                <div style="font-family:inherit;padding:4px;">
                    <div style="font-size:11px;font-weight:700;color:#2563eb;text-transform:uppercase;">Live Courier Position</div>
                    <div style="font-size:13px;font-weight:700;color:#0f172a;margin-top:2px;">🏍️ ${COURIER_NAME}</div>
                    <div style="font-size:11px;color:#475569;margin-top:2px;">Status: En Route</div>
                </div>
            `
        });
        courierMarker.addListener('click', () => {
            courierInfoWindow.open(trackMap, courierMarker);
        });

        // Open courier info window by default
        setTimeout(() => {
            courierInfoWindow.open(trackMap, courierMarker);
        }, 500);

        // 4. Fetch Route Polyline from backend proxy (/api/route)
        fetchRoutePolyline(storeLatLng, destLatLng, courierLatLng);

        // Fit map bounds to show all markers
        const bounds = new google.maps.LatLngBounds();
        bounds.extend(storeLatLng);
        bounds.extend(destLatLng);
        bounds.extend(courierLatLng);
        trackMap.fitBounds(bounds, 60);
    };

    function fetchRoutePolyline(storeLatLng, destLatLng, courierLatLng) {
        fetch('<?= base_url('api/route') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                origin: storeLatLng,
                destination: destLatLng
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.success && data.route && data.route.encodedPolyline && google.maps.geometry && google.maps.geometry.encoding) {
                const decodedPath = google.maps.geometry.encoding.decodePath(data.route.encodedPolyline);
                if (routePolyline) routePolyline.setMap(null);
                routePolyline = new google.maps.Polyline({
                    path: decodedPath,
                    geodesic: true,
                    strokeColor: '#2563eb',
                    strokeOpacity: 0.85,
                    strokeWeight: 4,
                    map: trackMap
                });
            } else {
                drawFallbackLine(storeLatLng, courierLatLng, destLatLng);
            }
        })
        .catch(err => {
            console.warn('Could not fetch route from Routes API, using direct line fallback:', err);
            drawFallbackLine(storeLatLng, courierLatLng, destLatLng);
        });
    }

    function drawFallbackLine(storeLatLng, courierLatLng, destLatLng) {
        if (routePolyline) routePolyline.setMap(null);
        routePolyline = new google.maps.Polyline({
            path: [storeLatLng, courierLatLng, destLatLng],
            geodesic: true,
            strokeColor: '#2563eb',
            strokeOpacity: 0.85,
            strokeWeight: 3,
            map: trackMap
        });
    }

    function centerOnCourier() {
        if (!trackMap || !COURIER_COORDS) return;
        const courierLatLng = { lat: parseFloat(COURIER_COORDS[0]), lng: parseFloat(COURIER_COORDS[1]) };
        trackMap.panTo(courierLatLng);
        trackMap.setZoom(16);
        if (courierInfoWindow && courierMarker) {
            courierInfoWindow.open(trackMap, courierMarker);
        }
    }

    function toggleItemsCollapsible() {
        const content = document.getElementById('items-collapsible-content');
        const chevron = document.getElementById('items-toggle-chevron');
        if (!content) return;

        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            if (chevron) chevron.style.transform = 'rotate(180deg)';
        } else {
            content.classList.add('hidden');
            if (chevron) chevron.style.transform = 'rotate(0deg)';
        }
    }

    // Fallback if callback didn't fire immediately
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof google !== 'undefined' && google.maps && !trackMap) {
            window.initGoogleTrackMap();
        }
    });
</script>

<!-- Google Maps JavaScript API (loaded after callback is defined) -->
<script src="https://maps.googleapis.com/maps/api/js?key=<?= esc(env('GOOGLE_MAPS_API_KEY')) ?>&libraries=marker,geometry&loading=async&callback=initGoogleTrackMap" async defer></script>

<?= $this->endSection() ?>
