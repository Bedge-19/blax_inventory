<?= $this->extend('layouts/tenant') ?>
<?= $this->section('content') ?>

<div class="flex-1 space-y-lg max-w-7xl mx-auto pb-xl">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-md rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 text-sm font-semibold flex items-center gap-sm">
            <span class="material-symbols-outlined text-[20px]">check_circle</span>
            <span><?= session()->getFlashdata('success') ?></span>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-md rounded-xl bg-error-container/40 border border-error/30 text-on-error-container text-sm font-semibold flex items-center gap-sm">
            <span class="material-symbols-outlined text-[20px]">error</span>
            <span><?= session()->getFlashdata('error') ?></span>
        </div>
    <?php endif; ?>

    <!-- Header + Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-md">
        <div>
            <h1 class="text-headline-sm font-extrabold text-on-surface tracking-tight">Delivery Management</h1>
            <p class="text-body-md text-on-surface-variant mt-0.5">Track, search, and manage your shipments across Polomolok.</p>
        </div>
        <div class="flex items-center gap-sm flex-wrap">
            <button type="button" id="btnOpenDeliveryScanner" onclick="openScanner()" class="inline-flex items-center gap-sm px-md py-sm bg-primary text-on-primary rounded-xl text-label-sm font-bold hover:bg-primary/90 transition-all shadow-sm active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">qr_code_scanner</span>
                <span>Scan QR Code</span>
            </button>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-md">
        <div class="bg-surface-container-lowest p-md rounded-2xl border border-outline-variant/30 shadow-2xs transition-all hover:border-primary/40">
            <div class="flex justify-between items-start mb-sm">
                <div class="p-2 bg-primary-container/20 text-primary rounded-xl">
                    <span class="material-symbols-outlined text-[20px]">local_shipping</span>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-primary bg-primary/10 px-2 py-0.5 rounded-full">Active</span>
            </div>
            <h3 class="text-xs text-on-surface-variant font-medium">Active Deliveries</h3>
            <p class="text-headline-md font-extrabold text-on-surface mt-xs font-mono"><?= number_format((int) ($kpis['active'] ?? 0)) ?></p>
        </div>

        <div class="bg-surface-container-lowest p-md rounded-2xl border border-outline-variant/30 shadow-2xs transition-all hover:border-blue-500/40">
            <div class="flex justify-between items-start mb-sm">
                <div class="p-2 bg-blue-500/10 text-blue-600 rounded-xl">
                    <span class="material-symbols-outlined text-[20px]">inventory</span>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700 bg-blue-500/10 px-2 py-0.5 rounded-full">Shipped</span>
            </div>
            <h3 class="text-xs text-on-surface-variant font-medium">Shipped Packages</h3>
            <p class="text-headline-md font-extrabold text-blue-700 mt-xs font-mono"><?= number_format((int) ($kpis['shipped'] ?? 0)) ?></p>
        </div>

        <div class="bg-surface-container-lowest p-md rounded-2xl border border-outline-variant/30 shadow-2xs transition-all hover:border-amber-500/40">
            <div class="flex justify-between items-start mb-sm">
                <div class="p-2 bg-amber-500/10 text-amber-600 rounded-xl">
                    <span class="material-symbols-outlined text-[20px]">navigation</span>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 bg-amber-500/10 px-2 py-0.5 rounded-full">In Transit</span>
            </div>
            <h3 class="text-xs text-on-surface-variant font-medium">In Transit</h3>
            <p class="text-headline-md font-extrabold text-amber-700 mt-xs font-mono"><?= number_format((int) ($kpis['in_transit'] ?? 0)) ?></p>
        </div>

        <div class="bg-surface-container-lowest p-md rounded-2xl border border-outline-variant/30 shadow-2xs transition-all hover:border-emerald-500/40">
            <div class="flex justify-between items-start mb-sm">
                <div class="p-2 bg-emerald-100 text-emerald-800 rounded-xl">
                    <span class="material-symbols-outlined text-[20px]">check_circle</span>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-full">Today</span>
            </div>
            <h3 class="text-xs text-on-surface-variant font-medium">Completed Today</h3>
            <p class="text-headline-md font-extrabold text-emerald-700 mt-xs font-mono"><?= number_format((int) ($kpis['completed_today'] ?? 0)) ?></p>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 p-md shadow-2xs space-y-md">
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-md">
            <!-- Live Search Input -->
            <div class="relative flex-1">
                <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                <input id="liveShipmentSearch" 
                       type="text" 
                       placeholder="Search by product name, order #, tracking ID, customer, or destination..." 
                       value="<?= esc($filters['q'] ?? '') ?>"
                       autocomplete="off"
                       class="w-full pl-11 pr-md py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
            </div>

            <!-- Status Dropdown Filter -->
            <div class="flex items-center gap-xs flex-wrap shrink-0">
                <select id="liveStatusFilter" 
                        onchange="applyStatusFilter(this.value)"
                        class="px-md py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs font-bold text-on-surface focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All Active Deliveries</option>
                    <?php foreach ($statusOptions as $val => $label): ?>
                        <option value="<?= esc($val) ?>" <?= ($filters['status'] ?? '') === $val ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="button" onclick="resetShipmentFilters()" class="px-md py-2.5 text-xs font-bold rounded-xl border border-outline-variant/40 hover:bg-surface-container transition-colors text-on-surface-variant">
                    Reset
                </button>
            </div>
        </div>

        <!-- Quick Status Pills -->
        <div class="flex items-center gap-xs overflow-x-auto pb-1 text-xs font-semibold custom-scrollbar">
            <?php
                $activeFilter = $filters['status'] ?? '';
                $pills = [
                    ''           => 'All Active Deliveries',
                    'shipped'    => 'Shipped',
                    'in_transit' => 'In Transit',
                ];
            ?>
            <?php foreach ($pills as $pkey => $plabel): ?>
                <button type="button" 
                        onclick="quickFilterStatus('<?= esc($pkey) ?>')" 
                        class="status-pill px-3 py-1.5 rounded-xl border transition-all whitespace-nowrap <?= $activeFilter === $pkey ? 'bg-primary text-on-primary border-primary font-bold shadow-xs' : 'bg-surface-container-low border-outline-variant/30 text-on-surface-variant hover:bg-surface-container' ?>"
                        data-status="<?= esc($pkey) ?>">
                    <?= esc($plabel) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Section Heading & Realtime Count -->
    <div class="flex items-center justify-between px-1">
        <div>
            <h2 class="text-title-md font-bold text-on-surface flex items-center gap-sm">
                <span>Shipment Packages</span>
                <span id="shipmentCountBadge" class="text-xs px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant font-mono font-semibold">
                    <?= count($deliveries ?? []) ?> package(s)
                </span>
            </h2>
            <p class="text-xs text-on-surface-variant mt-0.5">Click any shipment card to open its dedicated live route &amp; GPS mapping page.</p>
        </div>
    </div>

    <!-- Shipment Cards Grid (Replaces old table & side map) -->
    <div id="shipmentsGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-md lg:gap-lg">
        <?php if (!empty($deliveries)): ?>
            <?php foreach ($deliveries as $d): ?>
                <?php
                    $fullName = trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''));
                    $initials = $fullName !== '' ? mb_strtoupper(mb_substr($fullName, 0, 2)) : 'CU';
                    $profileImage = trim((string) ($d['profile_image_url'] ?? ''));
                    $trackingClean = esc($d['tracking_id']);
                    $refClean = esc($d['ref_number'] ?? ('ID #' . $d['id']));
                    $isPickup = ($d['fulfillment_method'] ?? '') === 'pickup';
                    $destAddress = esc($d['destination_address'] ?: ($isPickup ? 'Storefront Collection, Polomolok' : 'Customer Address, Polomolok'));
                    $productName = esc($d['product_name'] ?? 'Order Item');
                    $allProductsList = esc($d['all_products_list'] ?? $productName);
                    $extraItems = (int) ($d['extra_items_count'] ?? 0);
                    $totalQty = (int) ($d['total_qty'] ?? 1);
                    $variantLabel = esc($d['variant_label'] ?? '');
                    $searchIndex = strtolower($productName . ' ' . $allProductsList . ' ' . $trackingClean . ' ' . $refClean . ' ' . $fullName . ' ' . $destAddress . ' ' . $d['status']);
                ?>
                <div class="shipment-card bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-md sm:p-lg shadow-2xs hover:shadow-md hover:border-primary/50 transition-all flex flex-col justify-between group relative"
                     data-tracking="<?= $trackingClean ?>"
                     data-status="<?= esc($d['status']) ?>"
                     data-search="<?= esc($searchIndex) ?>">

                    <!-- Top Bar: Status Badge & Fulfillment Badge -->
                    <div>
                        <div class="flex items-center justify-between gap-sm mb-sm">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <?= status_badge($d['status']) ?>
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-primary bg-primary/10 border border-primary/20 px-2 py-0.5 rounded-full">
                                    <span class="material-symbols-outlined text-[13px]">local_shipping</span>
                                    <span>Doorstep Delivery</span>
                                </span>
                            </div>

                            <!-- More Menu / Quick Status Dropdown -->
                            <div class="relative">
                                <button type="button" 
                                        data-row="<?= (int) $d['id'] ?>" 
                                        onclick="toggleDropdown(this, event)" 
                                        class="more-toggle p-1 rounded-lg text-outline hover:text-on-surface hover:bg-surface-container transition-colors"
                                        title="Quick Actions">
                                    <span class="material-symbols-outlined text-[18px]">more_vert</span>
                                </button>
                                <div id="more-menu-<?= (int) $d['id'] ?>" class="hidden more-menu z-50 bg-surface-container-lowest border border-outline-variant/30 rounded-xl shadow-xl p-sm min-w-[200px] absolute right-0 top-full mt-1" role="menu">
                                    <p class="text-label-sm font-bold text-on-surface-variant px-sm pb-xs">Change Status</p>
                                    <form action="<?= base_url('tenant/deliveries/update-status') ?>" method="POST" class="space-y-xs px-sm pb-sm" onclick="event.stopPropagation();">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="delivery_id" value="<?= (int) $d['id'] ?>">
                                        <select name="delivery_status" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg text-xs font-semibold">
                                            <?php foreach ($statusOptions as $val => $label): ?>
                                                <option value="<?= esc($val) ?>" <?= $d['status'] === $val ? 'selected' : '' ?>><?= esc($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="w-full py-1.5 bg-primary text-on-primary rounded-lg text-xs font-bold hover:bg-primary/90 transition-colors mt-1">Apply</button>
                                    </form>
                                    <div class="border-t border-outline-variant/20 my-xs"></div>
                                    <a href="<?= base_url('tenant/deliveries/' . (int) $d['id']) ?>" class="w-full text-left px-sm py-1.5 rounded-lg text-primary text-xs font-bold hover:bg-primary/10 flex items-center gap-xs">
                                        <span class="material-symbols-outlined text-[16px]">near_me</span> View Full Map
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Product Name & Order ID Header -->
                        <div class="space-y-1.5 border-b border-outline-variant/20 pb-sm mb-sm">
                            <div class="flex items-start justify-between gap-sm">
                                <div class="min-w-0 flex-1">
                                    <!-- Primary Product Name Header -->
                                    <a href="<?= base_url('tenant/deliveries/' . (int) $d['id']) ?>" 
                                       class="text-title-sm font-extrabold text-on-surface hover:text-primary transition-colors flex items-center gap-1.5 leading-snug group/title" 
                                       title="<?= $allProductsList ?>">
                                        <span class="material-symbols-outlined text-[19px] text-primary shrink-0">inventory_2</span>
                                        <span class="truncate"><?= $productName ?></span>
                                    </a>

                                    <!-- Product Quantities / Extra Items Pill -->
                                    <div class="flex items-center gap-1.5 flex-wrap mt-0.5">
                                        <?php if ($extraItems > 0): ?>
                                            <span class="inline-flex items-center text-[10px] font-extrabold text-primary bg-primary/10 border border-primary/20 px-1.5 py-0.5 rounded-md" title="<?= $allProductsList ?>">
                                                +<?= $extraItems ?> more item<?= $extraItems > 1 ? 's' : '' ?> (<?= $totalQty ?> total pcs)
                                            </span>
                                        <?php elseif ($totalQty > 0): ?>
                                            <span class="text-[11px] font-medium text-on-surface-variant">
                                                Qty: <strong class="text-on-surface"><?= $totalQty ?></strong><?= $variantLabel !== '' ? ' &bull; ' . $variantLabel : '' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Order ID / Reference is retained here ("hindi total na mawawala ang order id nandyan parin") -->
                                <div class="flex flex-col items-end shrink-0">
                                    <span class="text-[11px] font-mono font-extrabold text-on-surface bg-surface-container border border-outline-variant/40 px-2 py-0.5 rounded-md" title="Order Reference Number">
                                        <?= $refClean ?>
                                    </span>
                                    <span class="text-[9px] uppercase tracking-wider text-outline font-bold mt-0.5">Order Ref</span>
                                </div>
                            </div>

                            <!-- Tracking ID and Deliverable Type Row -->
                            <div class="flex items-center justify-between gap-sm text-[11px] pt-1 text-on-surface-variant font-medium">
                                <a href="<?= base_url('tenant/deliveries/' . (int) $d['id']) ?>" class="font-mono font-bold text-primary hover:underline flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[15px]">local_shipping</span>
                                    <span>#<?= $trackingClean ?></span>
                                </a>
                                <span class="text-[11px] text-outline">
                                    <?= esc(ucfirst(str_replace('_', ' ', $d['deliverable_type'] ?? 'order'))) ?> &bull; <?= date('M d, Y h:i A', strtotime($d['created_at'])) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Customer & Destination Information -->
                        <div class="space-y-2 text-xs">
                            <!-- Customer Row -->
                            <div class="flex items-center gap-sm">
                                <?php if ($profileImage !== ''): ?>
                                    <img src="<?= base_url($profileImage) ?>" class="w-8 h-8 rounded-full object-cover border border-outline-variant/30 shrink-0" alt="Customer">
                                <?php else: ?>
                                    <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0">
                                        <?= esc($initials) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-on-surface truncate"><?= esc($fullName ?: 'Online Customer') ?></p>
                                    <?php if (!empty($d['customer_phone'])): ?>
                                        <p class="text-[11px] text-on-surface-variant flex items-center gap-0.5 font-mono">
                                            <span class="material-symbols-outlined text-[13px] text-primary">phone</span>
                                            <span><?= esc($d['customer_phone']) ?></span>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Destination Address Row -->
                            <div class="flex items-start gap-1.5 p-2 bg-surface-container-low/70 rounded-xl border border-outline-variant/20">
                                <span class="material-symbols-outlined text-[16px] text-emerald-600 shrink-0 mt-0.5">location_on</span>
                                <p class="text-[11px] text-on-surface leading-tight line-clamp-2" title="<?= $destAddress ?>">
                                    <?= $destAddress ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card Action Footer: Direct Navigation to Dedicated Live Route Map -->
                    <div class="mt-md pt-sm border-t border-outline-variant/20">
                        <a href="<?= base_url('tenant/deliveries/' . (int) $d['id']) ?>" 
                           class="w-full py-2.5 px-md rounded-xl bg-primary text-on-primary font-bold text-xs flex items-center justify-center gap-2 hover:bg-primary/90 shadow-sm transition-all group-hover:scale-[1.01] active:scale-95">
                            <span class="material-symbols-outlined text-[18px]">near_me</span>
                            <span>View Live Route &amp; Map</span>
                            <span class="material-symbols-outlined text-[16px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full py-2xl text-center bg-surface-container-lowest rounded-2xl border border-outline-variant/30 p-xl space-y-sm">
                <span class="w-16 h-16 rounded-full bg-surface-container text-outline flex items-center justify-center mx-auto text-3xl material-symbols-outlined">
                    local_shipping
                </span>
                <h3 class="text-title-md font-bold text-on-surface">No Deliveries Found</h3>
                <p class="text-xs text-on-surface-variant max-w-sm mx-auto">There are currently no active doorstep deliveries matching your filter. Orders dispatched for delivery will appear here.</p>
                <button type="button" onclick="resetShipmentFilters()" class="mt-2 px-md py-sm bg-primary text-on-primary text-xs font-bold rounded-xl hover:bg-primary/90">
                    Clear Filters
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- No Match Dynamic Message for Client Filtering -->
    <div id="noMatchMessage" class="hidden py-2xl text-center bg-surface-container-lowest rounded-2xl border border-outline-variant/30 p-xl space-y-sm">
        <span class="material-symbols-outlined text-3xl text-outline">search_off</span>
        <h3 class="text-title-md font-bold text-on-surface">No matching shipments</h3>
        <p class="text-xs text-on-surface-variant">Try searching for a different product, order reference, customer, or status.</p>
        <button type="button" onclick="resetShipmentFilters()" class="px-md py-sm bg-primary text-on-primary text-xs font-bold rounded-xl hover:bg-primary/90">
            Reset Filters
        </button>
    </div>

    <!-- Modern Styled Pagination Bar -->
    <?php if (isset($pager) && is_object($pager)): ?>
        <?php
            $dTot   = (int) $pager->getTotal('deliveries');
            $dCur   = (int) $pager->getCurrentPage('deliveries');
            $dPag   = (int) $pager->getPageCount('deliveries');
            $dStart = $dTot === 0 ? 0 : ($dCur - 1) * 12 + 1;
            $dEnd   = min($dCur * 12, $dTot);
        ?>
        <div class="mt-lg pt-md flex flex-col sm:flex-row items-center justify-between gap-md border-t border-outline-variant/30">
            <p class="text-xs text-on-surface-variant font-medium">
                Showing <span class="font-bold text-on-surface"><?= number_format($dStart) ?></span> to <span class="font-bold text-on-surface"><?= number_format($dEnd) ?></span> of <span class="font-bold text-on-surface"><?= number_format($dTot) ?></span> deliveries
            </p>
            <?php if ($dPag > 1): ?>
                <div class="flex items-center gap-xs">
                    <a class="w-8 h-8 rounded-xl border border-outline-variant/40 hover:bg-surface-container text-on-surface flex items-center justify-center transition-all <?= $dCur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('deliveries') ?>" title="Previous">
                        <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                    </a>
                    <?php
                    $w = [];
                    for ($i = 1; $i <= $dPag; $i++) {
                        if ($i === 1 || $i === $dPag || abs($i - $dCur) <= 2) { $w[] = $i; }
                    }
                    $pv = 0;
                    foreach ($w as $n):
                        if ($n - $pv > 1): ?><span class="px-xs text-outline font-bold">...</span><?php endif; ?>
                        <a class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold transition-all <?= $dCur === $n ? 'bg-primary text-on-primary shadow-xs' : 'hover:bg-surface-container text-on-surface border border-outline-variant/20' ?>" href="<?= $pager->getPageURI($n, 'deliveries') ?>"><?= $n ?></a>
                    <?php $pv = $n; endforeach; ?>
                    <a class="w-8 h-8 rounded-xl border border-outline-variant/40 hover:bg-surface-container text-on-surface flex items-center justify-center transition-all <?= $dCur >= $dPag ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('deliveries') ?>" title="Next">
                        <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif (isset($pager) && is_string($pager) && trim($pager) !== ''): ?>
        <div class="mt-lg pt-md">
            <?= $pager ?>
        </div>
    <?php endif; ?>

</div>

<!-- QR Code Scanner Modal -->
<div id="deliveryScannerModal" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-md backdrop-blur-sm">
    <div class="bg-surface-container-lowest rounded-2xl p-lg sm:p-xl max-w-md w-full border border-outline-variant/30 shadow-2xl space-y-md relative">
        <div class="flex items-center justify-between border-b border-outline-variant/20 pb-sm">
            <div class="flex items-center gap-sm">
                <span class="p-2 bg-primary/10 text-primary rounded-xl material-symbols-outlined text-[20px]">qr_code_scanner</span>
                <div>
                    <h3 class="text-title-md font-bold text-on-surface">Scan Delivery &amp; Order QR</h3>
                    <p class="text-xs text-on-surface-variant">Live camera or scan from saved photo</p>
                </div>
            </div>
            <button type="button" onclick="closeScanner()" class="p-1 text-outline hover:text-on-surface rounded-full hover:bg-surface-container">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Scan Mode Tabs: Live Camera vs Photo Upload -->
        <div class="flex items-center gap-xs p-1 bg-surface-container-low rounded-xl border border-outline-variant/20">
            <button type="button" id="deliveryTabCameraBtn" onclick="switchDeliveryScanMode('camera')" class="flex-1 py-2 px-sm rounded-lg text-xs font-bold transition-all bg-surface-container-lowest text-primary shadow-sm flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-[16px]">photo_camera</span>
                <span>Live Camera</span>
            </button>
            <button type="button" id="deliveryTabFileBtn" onclick="switchDeliveryScanMode('file')" class="flex-1 py-2 px-sm rounded-lg text-xs font-bold transition-all text-on-surface-variant hover:text-on-surface flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-[16px]">add_photo_alternate</span>
                <span>Scan from Photo</span>
            </button>
        </div>

        <!-- Mode 1: Live Camera Viewfinder (Unobstructed, No Dark Box) -->
        <div id="deliveryCameraContainer" class="space-y-sm">
            <div class="rounded-2xl overflow-hidden bg-black aspect-square relative flex items-center justify-center border border-outline-variant/30 shadow-inner">
                <div id="qr-reader" class="w-full h-full"></div>
                <!-- Clean, non-blocking transparent corner reticle -->
                <div class="qr-viewfinder-overlay pointer-events-none absolute inset-4 border border-white/20 rounded-xl">
                    <div class="absolute top-0 left-0 w-6 h-6 border-t-4 border-l-4 border-primary rounded-tl-lg"></div>
                    <div class="absolute top-0 right-0 w-6 h-6 border-t-4 border-r-4 border-primary rounded-tr-lg"></div>
                    <div class="absolute bottom-0 left-0 w-6 h-6 border-b-4 border-l-4 border-primary rounded-bl-lg"></div>
                    <div class="absolute bottom-0 right-0 w-6 h-6 border-b-4 border-r-4 border-primary rounded-br-lg"></div>
                    <div class="qr-laser-line"></div>
                </div>
            </div>
            <p id="scannerStatus" class="text-xs text-center text-on-surface-variant font-medium">Point camera directly at QR code or phone screen</p>
        </div>

        <!-- Mode 2: Scan from Photo / Image File -->
        <div id="deliveryFileContainer" class="hidden space-y-sm">
            <label for="deliveryPhotoUpload" class="flex flex-col items-center justify-center p-xl border-2 border-dashed border-primary/40 hover:border-primary rounded-2xl bg-primary/5 hover:bg-primary/10 transition-all cursor-pointer text-center group">
                <span class="p-3 bg-primary/10 text-primary rounded-2xl material-symbols-outlined text-3xl group-hover:scale-110 transition-transform mb-2">image_search</span>
                <span class="text-xs font-bold text-on-surface">Click to Select QR Photo / Take Photo</span>
                <span class="text-[11px] text-on-surface-variant mt-1">Upload the photo of the completed order/slip</span>
                <input id="deliveryPhotoUpload" type="file" accept="image/*" class="hidden" onchange="handleDeliveryPhotoUpload(this)">
            </label>
            <p id="photoScanStatus" class="text-xs text-center text-on-surface-variant font-medium hidden"></p>
        </div>

        <!-- Result Box with Direct Route Map Link -->
        <div id="scanResultBox" class="hidden p-md rounded-xl border space-y-sm transition-all">
            <div class="flex items-start gap-sm">
                <span id="scanResultIcon" class="material-symbols-outlined text-[22px] mt-0.5">check_circle</span>
                <div class="flex-1 min-w-0">
                    <p id="scanResultMessage" class="text-xs font-bold leading-relaxed"></p>
                    <p id="scanResultSub" class="text-[11px] text-on-surface-variant mt-0.5"></p>
                </div>
            </div>
            <div id="scanActionBtnContainer" class="pt-1"></div>
        </div>

        <!-- Manual Tracking ID Input -->
        <div class="pt-sm border-t border-outline-variant/20 space-y-xs">
            <label class="text-[11px] font-bold text-outline uppercase tracking-wider">Manual Code / Order Entry</label>
            <div class="flex items-center gap-xs">
                <input id="manualTrackingInput" 
                       type="text" 
                       placeholder="e.g. TRK-F7E82B04, ORD-88460, PR-190, or 190" 
                       class="flex-1 px-md py-2 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs font-mono font-bold focus:ring-2 focus:ring-primary focus:outline-none">
                <button type="button" onclick="submitManualTracking()" class="px-md py-2 bg-primary text-on-primary font-bold text-xs rounded-xl hover:bg-primary/90 transition-all">
                    Lookup
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Remove Html5Qrcode blocking dark region box */
#qr-reader #qr-shaded-region,
#posQrReader #qr-shaded-region {
    display: none !important;
}
#qr-reader, #posQrReader {
    border: none !important;
}
#qr-reader video, #posQrReader video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    border-radius: 0.875rem !important;
}
.qr-laser-line {
    position: absolute;
    left: 10px;
    right: 10px;
    height: 2px;
    background: linear-gradient(90deg, transparent, #3b82f6, transparent);
    box-shadow: 0 0 10px #3b82f6;
    animation: qrLaserScan 2s ease-in-out infinite alternate;
}
@keyframes qrLaserScan {
    0% { top: 12px; }
    100% { top: calc(100% - 14px); }
}
</style>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
    let activeMenu = null;
    let deliveryHtml5QrCode = null;

    function closeMenus() {
        if (activeMenu) {
            activeMenu.classList.add('hidden');
            activeMenu = null;
        }
    }

    function toggleDropdown(btn, e) {
        if (e) e.stopPropagation();
        const id   = btn.dataset.row;
        const menu = document.getElementById('more-menu-' + id);
        if (!menu) return;

        if (activeMenu === menu) {
            closeMenus();
            return;
        }
        closeMenus();
        menu.classList.remove('hidden');
        activeMenu = menu;
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.more-menu') && !e.target.closest('.more-toggle')) {
            closeMenus();
        }
    });

    // Realtime Client-Side Filtering
    const searchInput = document.getElementById('liveShipmentSearch');
    const statusSelect = document.getElementById('liveStatusFilter');
    const cards = document.querySelectorAll('.shipment-card');
    const noMatchMessage = document.getElementById('noMatchMessage');
    const countBadge = document.getElementById('shipmentCountBadge');

    function filterShipments() {
        const query = (searchInput.value || '').trim().toLowerCase();
        const status = (statusSelect.value || '').trim().toLowerCase();
        let visibleCount = 0;

        cards.forEach(card => {
            const cardSearch = (card.dataset.search || '').toLowerCase();
            const cardStatus = (card.dataset.status || '').toLowerCase();

            const matchQuery = query === '' || cardSearch.includes(query);
            const matchStatus = status === '' || cardStatus === status;

            if (matchQuery && matchStatus) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        if (countBadge) {
            countBadge.textContent = visibleCount + ' package(s)';
        }

        if (noMatchMessage) {
            if (visibleCount === 0 && cards.length > 0) {
                noMatchMessage.classList.remove('hidden');
            } else {
                noMatchMessage.classList.add('hidden');
            }
        }

        // Update pills
        document.querySelectorAll('.status-pill').forEach(pill => {
            if (pill.dataset.status === status) {
                pill.classList.remove('bg-surface-container-low', 'text-on-surface-variant');
                pill.classList.add('bg-primary', 'text-on-primary', 'font-bold', 'border-primary', 'shadow-xs');
            } else {
                pill.classList.remove('bg-primary', 'text-on-primary', 'font-bold', 'border-primary', 'shadow-xs');
                pill.classList.add('bg-surface-container-low', 'text-on-surface-variant');
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterShipments);
    }

    function applyStatusFilter(val) {
        statusSelect.value = val;
        filterShipments();
    }

    function quickFilterStatus(val) {
        statusSelect.value = val;
        filterShipments();
    }

    function resetShipmentFilters() {
        if (searchInput) searchInput.value = '';
        if (statusSelect) statusSelect.value = '';
        filterShipments();
    }

    // QR Code Scanner Handlers
    let currentDeliveryScanMode = 'camera';

    function switchDeliveryScanMode(mode) {
        currentDeliveryScanMode = mode;
        const camBtn = document.getElementById('deliveryTabCameraBtn');
        const fileBtn = document.getElementById('deliveryTabFileBtn');
        const camBox = document.getElementById('deliveryCameraContainer');
        const fileBox = document.getElementById('deliveryFileContainer');

        if (mode === 'camera') {
            camBtn.classList.remove('text-on-surface-variant');
            camBtn.classList.add('bg-surface-container-lowest', 'text-primary', 'shadow-sm');
            fileBtn.classList.remove('bg-surface-container-lowest', 'text-primary', 'shadow-sm');
            fileBtn.classList.add('text-on-surface-variant');
            camBox.classList.remove('hidden');
            fileBox.classList.add('hidden');
            startDeliveryCamera();
        } else {
            fileBtn.classList.remove('text-on-surface-variant');
            fileBtn.classList.add('bg-surface-container-lowest', 'text-primary', 'shadow-sm');
            camBtn.classList.remove('bg-surface-container-lowest', 'text-primary', 'shadow-sm');
            camBtn.classList.add('text-on-surface-variant');
            fileBox.classList.remove('hidden');
            camBox.classList.add('hidden');
            stopDeliveryCamera();
        }
    }

    function startDeliveryCamera() {
        if (typeof Html5Qrcode === 'undefined') return;

        if (window.isSecureContext === false && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
            const statusEl = document.getElementById('scannerStatus');
            if (statusEl) statusEl.textContent = 'Camera requires HTTPS or localhost. Please upload a photo or enter code manually.';
            return;
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            const statusEl = document.getElementById('scannerStatus');
            if (statusEl) statusEl.textContent = 'Camera is not supported on this browser context. Please upload a photo or enter code manually.';
            return;
        }

        if (!deliveryHtml5QrCode) {
            deliveryHtml5QrCode = new Html5Qrcode("qr-reader");
        }
        
        // Scan full viewfinder without dark restrictive box overlay
        const config = { fps: 12 };
        deliveryHtml5QrCode.start(
            { facingMode: "environment" },
            config,
            (decodedText) => {
                handleScannedCode(decodedText);
            },
            () => {}
        ).catch(err => {
            // If environment camera fails, try any available camera
            if (Html5Qrcode.getCameras) {
                Html5Qrcode.getCameras().then(cameras => {
                    if (cameras && cameras.length > 0) {
                        deliveryHtml5QrCode.start(
                            cameras[0].id,
                            config,
                            (decodedText) => handleScannedCode(decodedText),
                            () => {}
                        ).catch(() => {
                            document.getElementById('scannerStatus').textContent = 'Camera unavailable. Please upload a photo or enter code manually.';
                        });
                    } else {
                        document.getElementById('scannerStatus').textContent = 'No camera found. Please upload a photo or enter code manually.';
                    }
                }).catch(() => {
                    document.getElementById('scannerStatus').textContent = 'Camera permission denied or unavailable.';
                });
            } else {
                document.getElementById('scannerStatus').textContent = 'Camera unavailable. Please upload a photo or enter code manually.';
            }
        });
    }

    function stopDeliveryCamera() {
        if (deliveryHtml5QrCode) {
            try {
                if (deliveryHtml5QrCode.isScanning) {
                    deliveryHtml5QrCode.stop().catch(() => {});
                }
            } catch (e) {}
        }
    }

    function openScanner() {
        const modal = document.getElementById('deliveryScannerModal');
        modal.classList.remove('hidden');
        resetScanResult();
        switchDeliveryScanMode('camera');
    }

    function closeScanner() {
        const modal = document.getElementById('deliveryScannerModal');
        modal.classList.add('hidden');
        stopDeliveryCamera();
        deliveryHtml5QrCode = null;
    }

    function resetScanResult() {
        const box = document.getElementById('scanResultBox');
        box.classList.add('hidden');
        const sub = document.getElementById('scanResultSub');
        if (sub) sub.textContent = '';
        const photoStatus = document.getElementById('photoScanStatus');
        if (photoStatus) photoStatus.classList.add('hidden');
    }

    function handleScannedCode(code) {
        if (!code) return;
        if (deliveryHtml5QrCode) {
            try { deliveryHtml5QrCode.pause(); } catch (e) {}
        }
        lookupTracking(code);
    }

    function handleDeliveryPhotoUpload(input) {
        if (!input || !input.files || input.files.length === 0) return;
        const file = input.files[0];
        const statusEl = document.getElementById('photoScanStatus');
        if (statusEl) {
            statusEl.className = 'text-xs text-center text-primary font-bold animate-pulse';
            statusEl.textContent = 'Scanning image for QR code...';
            statusEl.classList.remove('hidden');
        }

        if (!deliveryHtml5QrCode) {
            deliveryHtml5QrCode = new Html5Qrcode("qr-reader");
        }

        deliveryHtml5QrCode.scanFile(file, true)
            .then(decodedText => {
                if (statusEl) {
                    statusEl.className = 'text-xs text-center text-emerald-600 font-bold';
                    statusEl.textContent = 'QR Code detected! Looking up details...';
                }
                handleScannedCode(decodedText);
            })
            .catch(err => {
                if (statusEl) {
                    statusEl.className = 'text-xs text-center text-red-600 font-bold';
                    statusEl.textContent = 'Could not detect a QR code in this photo. Please ensure good lighting or enter the code manually.';
                    statusEl.classList.remove('hidden');
                }
            });
    }

    function submitManualTracking() {
        const val = (document.getElementById('manualTrackingInput').value || '').trim();
        if (!val) return;
        lookupTracking(val);
    }

    function lookupTracking(trackingCode) {
        const resultBox = document.getElementById('scanResultBox');
        const msgEl = document.getElementById('scanResultMessage');
        const subEl = document.getElementById('scanResultSub');
        const iconEl = document.getElementById('scanResultIcon');
        const btnContainer = document.getElementById('scanActionBtnContainer');

        resultBox.classList.remove('hidden', 'bg-red-50', 'text-red-800', 'border-red-200', 'bg-emerald-50', 'text-emerald-800', 'border-emerald-200', 'bg-blue-50', 'text-blue-800', 'border-blue-200');
        resultBox.classList.add('bg-surface-container', 'text-on-surface', 'border-outline-variant');
        iconEl.textContent = 'progress_activity';
        iconEl.classList.add('animate-spin');
        msgEl.textContent = 'Looking up code: ' + trackingCode + '...';
        if (subEl) subEl.textContent = 'Checking orders, printing requests, and delivery records...';
        btnContainer.innerHTML = '';

        const fd = new FormData();
        fd.append('tracking_id', trackingCode);
        const csrfToken = '<?= csrf_token() ?>';
        const csrfHash  = (window.getCsrfToken && window.getCsrfToken()) ? window.getCsrfToken() : '<?= csrf_hash() ?>';
        fd.append(csrfToken, csrfHash);

        fetch('<?= base_url('tenant/deliveries/lookup') ?>', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            iconEl.classList.remove('animate-spin');
            if (data && data.csrf_hash) {
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) meta.setAttribute('content', data.csrf_hash);
            }
            if (data.success && data.delivery) {
                const isDelivered = data.action_type === 'delivered' || data.action_type === 'already_delivered' || data.delivery.status === 'delivered' || data.delivery.status === 'completed';
                resultBox.classList.remove('bg-surface-container', 'text-on-surface', 'border-outline-variant');
                
                if (isDelivered) {
                    resultBox.classList.add('bg-emerald-50', 'text-emerald-800', 'border-emerald-200');
                    iconEl.textContent = 'check_circle';
                } else {
                    resultBox.classList.add('bg-blue-50', 'text-blue-800', 'border-blue-200');
                    iconEl.textContent = 'local_shipping';
                }

                const pName = data.delivery.product_name ? ('"' + data.delivery.product_name + '" • ') : '';
                msgEl.textContent = data.message || ('Found: ' + pName + '#' + (data.delivery.tracking_id || trackingCode));
                
                if (subEl) {
                    const recipient = data.delivery.recipient_name ? ('Recipient: ' + data.delivery.recipient_name + ' • ') : '';
                    const fulfillment = data.delivery.fulfillment_method ? ('Fulfillment: ' + data.delivery.fulfillment_method.toUpperCase() + ' • ') : '';
                    subEl.textContent = `${recipient}${fulfillment}Status: ${(data.delivery.status || 'Active').toUpperCase()}`;
                }

                const targetUrl = '<?= base_url('tenant/deliveries') ?>/' + data.delivery.id;
                btnContainer.innerHTML = `
                    <div class="flex items-center gap-xs mt-1">
                        <a href="${targetUrl}" class="flex-1 py-2 bg-primary text-on-primary font-bold text-xs rounded-xl flex items-center justify-center gap-1 shadow-sm hover:bg-primary/90 transition-all">
                            <span class="material-symbols-outlined text-[16px]">map</span>
                            <span>Open Route &amp; Shipment →</span>
                        </a>
                        <button type="button" onclick="closeScanner(); location.reload();" class="px-md py-2 border border-outline-variant font-bold text-xs rounded-xl hover:bg-surface-container transition-all">
                            Done
                        </button>
                    </div>
                `;
            } else {
                resultBox.classList.remove('bg-surface-container', 'text-on-surface', 'border-outline-variant');
                resultBox.classList.add('bg-red-50', 'text-red-800', 'border-red-200');
                iconEl.textContent = 'error';
                msgEl.textContent = data.error || data.message || 'No matching order or request found.';
                if (subEl) subEl.textContent = 'Please check the order number or take a clearer picture.';
            }
        })
        .catch(() => {
            iconEl.classList.remove('animate-spin');
            resultBox.classList.remove('bg-surface-container', 'text-on-surface', 'border-outline-variant');
            resultBox.classList.add('bg-red-50', 'text-red-800', 'border-red-200');
            iconEl.textContent = 'error';
            msgEl.textContent = 'Server connection error during lookup.';
            if (subEl) subEl.textContent = 'Please try again or enter the code manually.';
        });
    }
</script>

<?= $this->endSection() ?>