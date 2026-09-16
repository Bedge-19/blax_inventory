<?= $this->extend('layouts/tenant') ?>
<?= $this->section('content') ?>

<div class="flex-1 space-y-lg">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-md rounded-xl bg-green-100 text-green-800 text-sm font-medium mb-lg"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-md rounded-xl bg-error-container/40 text-on-error-container text-sm font-medium mb-lg"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <!-- Header + Scan QR -->
    <div class="flex flex-wrap justify-between items-center gap-md">
        <div>
            <h2 class="text-headline-md font-bold text-on-surface">Delivery Management</h2>
            <p class="text-body-md text-on-surface-variant">Monitor and manage your deliveries across Polomolok.</p>
        </div>
        <button type="button" id="btnOpenDeliveryScanner" onclick="openScanner()" class="inline-flex items-center gap-sm px-md py-sm bg-primary text-on-primary rounded-lg text-label-sm font-semibold hover:bg-primary/90 transition-colors cursor-pointer">
            <span class="material-symbols-outlined text-[18px]">qr_code_scanner</span>
            Scan QR Code
        </button>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-md">
        <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant/20 shadow-sm">
            <div class="flex justify-between items-start mb-sm">
                <div class="p-xs bg-primary-container/10 rounded-lg">
                    <span class="material-symbols-outlined text-primary">local_shipping</span>
                </div>
            </div>
            <h3 class="text-label-sm text-on-surface-variant font-medium">Active Deliveries</h3>
            <p class="text-headline-md font-bold mt-xs"><?= number_format((int) $kpis['active']) ?></p>
        </div>
        <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant/20 shadow-sm">
            <div class="flex justify-between items-start mb-sm">
                <div class="p-xs bg-secondary-container/30 rounded-lg">
                    <span class="material-symbols-outlined text-secondary">storefront</span>
                </div>
            </div>
            <h3 class="text-label-sm text-on-surface-variant font-medium">Ready for Pick-up</h3>
            <p class="text-headline-md font-bold mt-xs"><?= number_format((int) $kpis['ready_for_pickup']) ?></p>
        </div>
        <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant/20 shadow-sm">
            <div class="flex justify-between items-start mb-sm">
                <div class="p-xs bg-tertiary-container/10 rounded-lg">
                    <span class="material-symbols-outlined text-tertiary">flight_takeoff</span>
                </div>
            </div>
            <h3 class="text-label-sm text-on-surface-variant font-medium">Shipped</h3>
            <p class="text-headline-md font-bold mt-xs"><?= number_format((int) $kpis['shipped']) ?></p>
        </div>
        <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant/20 shadow-sm">
            <div class="flex justify-between items-start mb-sm">
                <div class="p-xs bg-[#dcfce7] rounded-lg">
                    <span class="material-symbols-outlined text-[#166534]">check_circle</span>
                </div>
            </div>
            <h3 class="text-label-sm text-on-surface-variant font-medium">Completed Today</h3>
            <p class="text-headline-md font-bold mt-xs"><?= number_format((int) $kpis['completed_today']) ?></p>
        </div>
    </div>

    <!-- Shipments + Fleet Map -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 w-full items-start">

        <!-- Recent Shipments -->
        <div class="xl:col-span-8 overflow-hidden rounded-xl border border-outline-variant/30 bg-surface-container-lowest shadow-sm">
            <div class="px-lg py-md flex flex-wrap justify-between items-center gap-md border-b border-outline-variant/30">
                <div>
                    <h3 class="text-title-lg font-bold text-on-surface">Recent Shipments</h3>
                    <p class="text-xs text-on-surface-variant">Type any reference or customer to filter in real time, or click to view live route</p>
                </div>
                <form method="get" action="<?= base_url('tenant/deliveries') ?>" class="flex flex-wrap items-center gap-sm" onsubmit="return false;">
                    <div class="relative min-w-[240px]">
                        <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                        <input id="liveShipmentSearch" name="q" value="<?= esc($filters['q']) ?>" placeholder="Instant search ref, customer, tracking..." class="w-full pl-xl pr-md py-sm bg-surface-container-low border border-outline-variant rounded-lg text-body-md focus:outline-none focus:ring-2 focus:ring-primary" type="text" autocomplete="off">
                    </div>
                    <select id="liveStatusFilter" name="status" class="bg-surface-container-low border border-outline-variant rounded-lg px-md py-sm text-label-sm font-label-sm text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">All Statuses</option>
                        <?php foreach ($statusOptions as $val => $label): ?>
                            <option value="<?= esc($val) ?>" <?= $filters['status'] === $val ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" onclick="resetLiveFilters()" class="px-md py-sm text-on-surface-variant hover:text-on-surface text-label-sm font-semibold">Reset</button>
                    <?php $exportUrl = base_url('tenant/deliveries/export') . (empty(array_filter($filters)) ? '' : '?' . http_build_query(array_filter($filters))); ?>
                    <a id="exportDeliveriesBtn" href="<?= $exportUrl ?>" onclick="handleExportClick(this, 'Exporting CSV...')" class="flex items-center gap-xs px-md py-sm border border-outline-variant rounded-lg text-label-sm font-bold hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-[18px]">download</span>
                        <span>Export</span>
                    </a>
                </form>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="min-w-full text-left border-collapse">
                    <thead class="bg-surface-container-low border-b border-outline-variant/30">
                        <tr>
                            <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Tracking ID</th>
                            <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Reference</th>
                            <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Customer</th>
                            <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Destination</th>
                            <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Status</th>
                            <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Updated</th>
                            <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="shipmentTableBody" class="divide-y divide-outline-variant/20">
                        <?php if (!empty($deliveries)): ?>
                            <?php foreach ($deliveries as $d): ?>
                                <?php
                                $fullName = trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''));
                                $initials = $fullName !== '' ? mb_strtoupper(mb_substr($fullName, 0, 2)) : 'CU';
                                $profileImage = trim((string) ($d['profile_image_url'] ?? ''));
                                $updatedAt = !empty($d['shipped_at']) ? $d['shipped_at'] : (!empty($d['delivered_at']) ? $d['delivered_at'] : $d['created_at']);
                                $trackingClean = esc($d['tracking_id']);
                                $refClean = esc($d['ref_number'] ?? '-');
                                ?>
                                <tr class="shipment-row hover:bg-surface-container-low/60 transition-colors cursor-pointer"
                                    data-tracking="<?= $trackingClean ?>"
                                    data-ref="<?= $refClean ?>"
                                    data-customer="<?= esc(strtolower($fullName)) ?>"
                                    data-dest="<?= esc(strtolower($d['destination_address'] ?? '')) ?>"
                                    data-status="<?= esc(strtolower($d['status'])) ?>"
                                    onclick="focusShipmentOnMap('<?= $trackingClean ?>', event)">
                                    <td class="px-lg py-md">
                                        <button type="button" onclick="focusShipmentOnMap('<?= $trackingClean ?>', event)" class="font-mono text-body-md font-bold text-primary hover:underline flex items-center gap-1 text-left">
                                            <span class="material-symbols-outlined text-[16px] text-primary/70">pin_drop</span>
                                            #<?= $trackingClean ?>
                                        </button>
                                    </td>
                                    <td class="px-lg py-md">
                                        <button type="button" onclick="focusShipmentOnMap('<?= $trackingClean ?>', event)" class="text-body-md font-bold text-on-surface hover:text-primary transition-colors flex items-center gap-1 text-left">
                                            <span class="material-symbols-outlined text-[16px] text-secondary">receipt_long</span>
                                            #<?= $refClean ?>
                                        </button>
                                        <p class="text-[11px] text-on-surface-variant"><?= esc(ucfirst(str_replace('_', ' ', $d['deliverable_type']))) ?></p>
                                    </td>
                                    <td class="px-lg py-md">
                                        <div class="flex items-center gap-sm">
                                            <div class="w-8 h-8 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center text-label-sm font-bold <?= $profileImage !== '' ? 'relative overflow-hidden' : '' ?>">
                                                <span><?= esc($initials) ?></span>
                                                <?php if ($profileImage !== ''): ?>
                                                    <img class="absolute inset-0 w-full h-full object-cover" src="<?= esc(base_url($profileImage)) ?>" alt="<?= esc($fullName) ?> avatar" loading="lazy" onerror="this.remove();">
                                                <?php endif; ?>
                                            </div>
                                            <span class="text-body-md text-on-surface font-medium"><?= esc($fullName !== '' ? $fullName : 'Customer') ?></span>
                                        </div>
                                    </td>
                                    <td class="px-lg py-md text-xs text-on-surface-variant max-w-[200px] truncate" title="<?= esc($d['destination_address'] ?? '') ?>">
                                        <div class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px] text-on-surface-variant">home</span>
                                            <span><?= esc($d['destination_address'] ?? 'N/A') ?></span>
                                        </div>
                                    </td>
                                    <td class="px-lg py-md"><?= status_badge($d['status']) ?></td>
                                    <td class="px-lg py-md text-xs text-on-surface-variant"><?= date('M d, Y h:i A', strtotime($updatedAt)) ?></td>
                                    <td class="px-lg py-md text-right" onclick="event.stopPropagation()">
                                        <div class="flex justify-end items-center gap-md">
                                            <button type="button"
                                                    onclick="focusShipmentOnMap('<?= $trackingClean ?>', event)"
                                                    class="p-xs hover:bg-surface-container-high rounded text-primary"
                                                    title="Track on Live Map">
                                                <span class="material-symbols-outlined text-[20px]">two_wheeler</span>
                                            </button>
                                            <button type="button"
                                                    onclick="openDeliveryLookup('<?= $trackingClean ?>')"
                                                    class="p-xs hover:bg-surface-container-high rounded text-on-surface-variant"
                                                    title="View Details">
                                                <span class="material-symbols-outlined">visibility</span>
                                            </button>
                                            <div class="relative">
                                                <button type="button" data-row="<?= (int) $d['id'] ?>" onclick="toggleDropdown(this)" class="more-toggle p-xs hover:bg-surface-container-high rounded text-on-surface-variant" title="More Actions" aria-haspopup="true" aria-expanded="false">
                                                    <span class="material-symbols-outlined">more_vert</span>
                                                </button>
                                                <div id="more-menu-<?= (int) $d['id'] ?>" class="hidden more-menu z-50 bg-surface-container-lowest border border-outline-variant/30 rounded-xl shadow-lg p-sm min-w-[220px]" role="menu">
                                                    <p class="text-label-sm font-bold text-on-surface-variant px-sm pb-xs">Update Status</p>
                                                    <form action="<?= base_url('tenant/deliveries/update-status') ?>" method="POST" class="space-y-xs px-sm pb-sm">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="delivery_id" value="<?= (int) $d['id'] ?>">
                                                        <select name="delivery_status" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-lg text-label-sm font-label-sm">
                                                            <?php foreach ($statusOptions as $val => $label): ?>
                                                                 <option value="<?= esc($val) ?>" <?= $d['status'] === $val ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="submit" class="w-full py-sm bg-primary text-on-primary rounded-lg text-label-sm font-semibold hover:bg-primary/90">Apply Status</button>
                                                    </form>
                                                    <div class="border-t border-outline-variant/20 my-xs"></div>
                                                    <button type="button" onclick="focusShipmentOnMap('<?= $trackingClean ?>', event)" class="w-full text-left px-sm py-sm rounded-lg text-primary text-label-sm font-semibold hover:bg-surface-container-high flex items-center gap-xs">
                                                        <span class="material-symbols-outlined text-[18px]">two_wheeler</span> Show Live Route
                                                    </button>
                                                    <button type="button" onclick="openDeliveryLookup('<?= $trackingClean ?>')" class="w-full text-left px-sm py-sm rounded-lg text-on-surface-variant text-label-sm font-semibold hover:bg-surface-container-high flex items-center gap-xs">
                                                        <span class="material-symbols-outlined text-[18px]">visibility</span> View Details
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr id="noShipmentsRow">
                                <td colspan="7" class="py-lg text-center text-on-surface-variant">No deliveries match your filters.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $dTot  = (int) $pager->getTotal('deliveries');
            $dCur  = (int) $pager->getCurrentPage('deliveries');
            $dPag  = (int) $pager->getPageCount('deliveries');
            $dStart = $dTot === 0 ? 0 : ($dCur - 1) * 12 + 1;
            $dEnd   = min($dCur * 12, $dTot);
            ?>
            <?php if ($dPag > 1): ?>
                <div class="px-lg py-md bg-surface-container-low flex justify-between items-center border-t border-outline-variant/30 flex-wrap gap-sm">
                    <p class="text-label-sm font-label-sm text-on-surface-variant">Showing <?= number_format($dStart) ?> to <?= number_format($dEnd) ?> of <?= number_format($dTot) ?> shipments</p>
                    <div class="flex items-center gap-xs">
                        <a class="p-sm rounded hover:bg-surface-container-high <?= $dCur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('deliveries') ?>">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </a>
                        <?php
                        $w = [];
                        for ($i = 1; $i <= $dPag; $i++) {
                            if ($i === 1 || $i === $dPag || abs($i - $dCur) <= 2) { $w[] = $i; }
                        }
                        $pv = 0;
                        foreach ($w as $n):
                            if ($n - $pv > 1): ?><span class="px-xs text-outline">...</span><?php endif; ?>
                            <a class="w-8 h-8 rounded flex items-center justify-center text-label-sm <?= $dCur === $n ? 'bg-primary text-on-primary font-semibold' : 'hover:bg-surface-container-high' ?>" href="<?= $pager->getPageURI($n, 'deliveries') ?>"><?= $n ?></a>
                        <?php $pv = $n; endforeach; ?>
                        <a class="p-sm rounded hover:bg-surface-container-high <?= $dCur >= $dPag ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('deliveries') ?>">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Live Fleet Tracking Map (4 cols) -->
        <div class="xl:col-span-4 rounded-xl border border-outline-variant/30 bg-surface-container-lowest p-4 shadow-sm flex flex-col relative overflow-hidden">
            <div class="px-2 py-1 border-b border-outline-variant/20 flex flex-wrap items-center justify-between gap-sm mb-3">
                <div>
                    <h3 class="text-title-md font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]">near_me</span>
                        <span>Live Fleet Tracking</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-primary/10 text-primary uppercase">Polomolok Map</span>
                    </h3>
                    <p id="mapActiveSub" class="text-[11px] text-on-surface-variant font-medium">Real-time GPS delivery routes across Polomolok</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <div id="testRouteControls" class="flex items-center gap-1.5 bg-surface-container-low border border-outline-variant/30 px-2 py-1 rounded-lg">
                        <span class="text-[10px] font-bold text-outline uppercase tracking-wider">Test:</span>
                        <button type="button" onclick="focusShipmentOnMap('TRK-TEST-POLO1')" class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-primary/10 text-primary hover:bg-primary hover:text-white transition-all shadow-xs" title="Test route to Cannery Site">Cannery</button>
                        <button type="button" onclick="focusShipmentOnMap('TRK-TEST-POLO3')" class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-primary/10 text-primary hover:bg-primary hover:text-white transition-all shadow-xs" title="Test route to Glamang">Glamang</button>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-ping"></span> Live
                    </span>
                    <button type="button" onclick="resetFleetMapView()" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-surface-container-low hover:bg-surface-container text-on-surface-variant border border-outline-variant/30 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">my_location</span> Reset Map
                    </button>
                </div>
            </div>

            <!-- Route Active Info Floating Bar -->
            <div id="routeInfoBar" class="hidden absolute top-16 left-4 right-4 z-[400] bg-surface-container-lowest/95 backdrop-blur border border-primary/30 p-2.5 rounded-xl shadow-lg flex items-center justify-between">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center flex-shrink-0 animate-pulse">
                        <span class="material-symbols-outlined text-[18px]">two_wheeler</span>
                    </span>
                    <div class="min-w-0">
                        <p id="routeTitle" class="text-xs font-bold text-on-surface truncate">Tracking Order</p>
                        <p id="routeSubtitle" class="text-[10px] text-on-surface-variant truncate">Rider on way to destination</p>
                    </div>
                </div>
                <span id="routeBadge" class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 flex-shrink-0">🛵 Active Route</span>
            </div>

            <div id="fleetMap" class="w-full h-[450px] min-h-[450px] rounded-lg overflow-hidden relative z-0" style="height: 450px; min-height: 450px;"></div>
        </div>
    </div>
</div>
<div id="deliveryModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-md backdrop-blur-sm">
    <div class="glass-card bg-surface-container-lowest rounded-2xl p-xl max-w-lg w-full border border-outline-variant/30 shadow-2xl max-h-[90vh] overflow-y-auto relative">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm mb-md">
            <div>
                <h3 class="text-title-lg font-bold">Delivery <span id="dmTracking" class="text-primary"></span></h3>
                <p id="dmActionBanner" class="hidden text-xs font-bold px-2 py-0.5 rounded-full mt-1 inline-block"></p>
            </div>
            <button onclick="closeDeliveryModal()" class="text-on-surface-variant hover:text-on-surface p-1 rounded-full hover:bg-surface-container"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div id="dmBody" class="space-y-sm mb-md">
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Reference</span><span id="dmReference" class="text-body-md font-semibold text-on-surface"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Type</span><span id="dmType" class="text-body-md text-on-surface"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Customer</span><span id="dmCustomer" class="text-body-md text-on-surface"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Destination</span><span id="dmDestination" class="text-body-md text-on-surface text-right max-w-[70%]"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Courier</span><span id="dmCourier" class="text-body-md text-on-surface"></span></div>
            <div class="flex justify-between items-center"><span class="text-label-sm text-on-surface-variant">Current Status</span><span id="dmStatus"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Created</span><span id="dmCreated" class="text-body-md text-on-surface"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Shipped</span><span id="dmShipped" class="text-body-md text-on-surface"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Delivered</span><span id="dmDelivered" class="text-body-md text-on-surface"></span></div>
        </div>
        <div class="pt-sm border-t border-outline-variant/20 flex gap-sm">
            <button type="button" onclick="closeDeliveryModal(true)" class="w-full py-sm bg-primary text-on-primary rounded-xl font-semibold hover:bg-primary/90 transition-all">Done / Refresh Page</button>
        </div>
    </div>
</div>

<!-- QR Scanner Modal -->
<div id="scannerModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-md backdrop-blur-sm">
    <div class="glass-card bg-surface-container-lowest rounded-2xl p-xl max-w-md w-full border border-outline-variant/30 shadow-2xl">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm mb-md">
            <div>
                <h3 class="text-title-lg font-bold">Scan QR Code</h3>
                <p class="text-xs text-on-surface-variant">Auto-updates Shipped ➔ Delivered / Delivered ➔ Returned</p>
            </div>
            <button onclick="closeScanner()" class="text-on-surface-variant hover:text-on-surface p-1 rounded-full hover:bg-surface-container"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div id="scanStatus" class="text-label-sm text-on-surface-variant mb-sm">Point your camera at the delivery QR label.</div>
        <div class="relative rounded-xl overflow-hidden bg-black aspect-square flex items-center justify-center">
            <div id="deliveryQrReader" class="w-full h-full"></div>
            <div id="deliveryCameraPlaceholder" class="absolute inset-0 flex flex-col items-center justify-center text-white/70 p-md text-center bg-black/80 pointer-events-none">
                <span class="material-symbols-outlined text-4xl mb-1 text-primary">photo_camera</span>
                <span class="text-xs font-semibold">Starting camera...</span>
                <span class="text-[10px] opacity-70 mt-1">Please allow camera permissions if prompted</span>
            </div>
        </div>
        <div class="mt-md space-y-sm">
            <p class="text-label-sm text-on-surface-variant">Or enter Tracking ID / Order # manually:</p>
            <div class="flex gap-sm">
                <input id="manualTracking" type="text" placeholder="e.g. TRK-F7E82B04 or ORD-88460" class="flex-1 px-md py-sm bg-surface-container-low border border-outline-variant rounded-lg text-body-md focus:outline-none focus:ring-2 focus:ring-primary">
                <button type="button" onclick="lookupManual()" class="bg-primary text-on-primary px-md py-sm rounded-lg text-label-sm font-semibold hover:bg-primary/90">Scan / Look Up</button>
            </div>
            <div id="scanResult" class="hidden text-label-sm rounded-lg p-sm"></div>
        </div>
    </div>
</div>

<form id="deliveryCsrfForm" class="hidden"><?= csrf_field() ?></form>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
    let activeMenu = null;
    let fleetMap = null;
    let mapMarkers = {};
    let activeRouteLine = null;
    let activeRiderMarker = null;
    let riderAnimationTimer = null;
    let isProcessingScan = false;
    let shouldReloadOnClose = false;
    let deliveryHtml5QrCode = null;

    const STORE_COORDS = [6.2217, 125.0667]; // Shop Base in Polomolok Poblacion
    const pins = <?= json_encode($pins ?? [], JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>;

    // Temporary Polomolok sample test pins for testing map, routes, and rider movement
    const samplePolomolokPins = [
        {
            id: 991,
            tracking_id: 'TRK-TEST-POLO1',
            ref_number: 'ORD-TEST-8801',
            first_name: 'Maria',
            last_name: 'Santos',
            customer_phone: '09171234567',
            status: 'in_transit',
            destination_address: 'Purok 4, Brgy. Cannery Site, Polomolok',
            current_lat: 6.2418,
            current_lng: 125.0782,
            shop_name: '<?= esc($shop['shop_name'] ?? 'Storefront') ?>',
            is_test_pin: true
        },
        {
            id: 992,
            tracking_id: 'TRK-TEST-POLO2',
            ref_number: 'ORD-TEST-8802',
            first_name: 'Juan',
            last_name: 'Dela Cruz',
            customer_phone: '09189876543',
            status: 'shipped',
            destination_address: 'Crossing Rubber, Brgy. Rubber, Polomolok',
            current_lat: 6.1950,
            current_lng: 125.0920,
            shop_name: '<?= esc($shop['shop_name'] ?? 'Storefront') ?>',
            is_test_pin: true
        },
        {
            id: 993,
            tracking_id: 'TRK-TEST-POLO3',
            ref_number: 'ORD-TEST-8803',
            first_name: 'Analyn',
            last_name: 'Flores',
            customer_phone: '09205551234',
            status: 'in_transit',
            destination_address: 'Purok Pag-asa, Brgy. Glamang, Polomolok',
            current_lat: 6.1823,
            current_lng: 125.0456,
            shop_name: '<?= esc($shop['shop_name'] ?? 'Storefront') ?>',
            is_test_pin: true
        },
        {
            id: 994,
            tracking_id: 'TRK-TEST-POLO4',
            ref_number: 'PR-TEST-8804',
            first_name: 'Rico',
            last_name: 'Magbanua',
            customer_phone: '09224448888',
            status: 'ready_for_pickup',
            destination_address: 'Storefront Collection, Poblacion, Polomolok',
            current_lat: 6.2217,
            current_lng: 125.0667,
            shop_name: '<?= esc($shop['shop_name'] ?? 'Storefront') ?>',
            is_test_pin: true
        }
    ];

    // Combine pins with sample pins for testing & inspection
    let activePins = (pins && pins.length > 0) ? [...pins] : [...samplePolomolokPins];
    if (pins && pins.length > 0) {
        samplePolomolokPins.forEach(sp => {
            if (!activePins.some(ap => ap.tracking_id === sp.tracking_id)) {
                activePins.push(sp);
            }
        });
    }

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

    function csrfToken() {
        const el = document.querySelector('#deliveryCsrfForm [name="csrf_test_name"]');
        return el ? el.value : '';
    }

    // ==========================================
    // 🗺️ MAP & LIVE ROUTE WITH MOTORCYCLE ICON
    // ==========================================
    function createMotorcycleIcon(angle = 0) {
        return L.divIcon({
            className: 'motor-rider-pin',
            html: `
                <div style="position:relative;display:flex;align-items:center;justify-content:center;width:44px;height:44px;">
                    <div style="position:absolute;width:44px;height:44px;border-radius:50%;background:rgba(37,99,235,0.3);animation:ping 1.5s cubic-bezier(0,0,0.2,1) infinite;"></div>
                    <div style="position:absolute;width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg, #2563eb, #1d4ed8);border:2.5px solid #ffffff;box-shadow:0 3px 8px rgba(0,0,0,0.35);display:flex;align-items:center;justify-content:center;color:#fff;">
                        <span class="material-symbols-outlined" style="font-size:20px;line-height:1;">two_wheeler</span>
                    </div>
                </div>
            `,
            iconSize: [44, 44],
            iconAnchor: [22, 22],
        });
    }

    function createCustomerIcon(status) {
        const colors = {
            ready_for_pickup: '#f59e0b',
            shipped: '#2563eb',
            in_transit: '#7c3aed',
            delivered: '#10b981',
            returned: '#8b5cf6',
        };
        const color = colors[status] || '#2563eb';
        return L.divIcon({
            className: 'dest-customer-pin',
            html: `
                <div style="position:relative;display:flex;align-items:center;justify-content:center;width:32px;height:32px;">
                    <div style="width:28px;height:28px;border-radius:50%;background:${color};border:2px solid #ffffff;box-shadow:0 2px 6px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;color:#fff;">
                        <span class="material-symbols-outlined" style="font-size:16px;line-height:1;">home</span>
                    </div>
                </div>
            `,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
        });
    }

    function createStoreIcon() {
        return L.divIcon({
            className: 'store-pin',
            html: `
                <div style="width:32px;height:32px;border-radius:50%;background:#0f172a;border:2px solid #ffffff;box-shadow:0 2px 6px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;color:#fff;">
                    <span class="material-symbols-outlined" style="font-size:16px;line-height:1;">storefront</span>
                </div>
            `,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
        });
    }

    function initMap() {
        const el = document.getElementById('fleetMap') || document.getElementById('fleet-map');
        if (!el || typeof L === 'undefined') return;

        fleetMap = L.map(el, { zoomControl: true }).setView([6.2136, 125.0661], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 18,
        }).addTo(fleetMap);

        // Add Store Base Marker
        const storeMarker = L.marker(STORE_COORDS, { icon: createStoreIcon() }).addTo(fleetMap);
        storeMarker.bindPopup('<strong>🏪 Store Dispatch Hub</strong><br>Poblacion, Polomolok');

        mapMarkers = {};
        const groupList = [storeMarker];

        (activePins || []).forEach((p) => {
            const lat = parseFloat(p.current_lat);
            const lng = parseFloat(p.current_lng);
            if (isNaN(lat) || isNaN(lng)) return;

            const customer = ((p.first_name || '') + ' ' + (p.last_name || '')).trim() || 'Customer';
            const m = L.marker([lat, lng], { icon: createCustomerIcon(p.status) }).addTo(fleetMap);
            
            const isTestBadge = p.is_test_pin ? '<span style="font-size:9px;font-weight:bold;background:#fef3c7;color:#92400e;padding:1px 5px;border-radius:4px;margin-left:4px;">DEMO PIN</span>' : '';

            const popupContent = `
                <div style="min-width:180px;font-family:inherit;">
                    <div style="font-weight:bold;font-size:13px;color:#2563eb;margin-bottom:2px;">#${p.ref_number ? p.ref_number : p.tracking_id} ${isTestBadge}</div>
                    <div style="font-size:11px;color:#64748b;margin-bottom:4px;">Tracking: #${p.tracking_id}</div>
                    <div style="font-size:12px;font-weight:600;color:#0f172a;">👤 ${customer}</div>
                    <div style="font-size:11px;color:#475569;margin-top:2px;">📍 ${p.destination_address || 'Polomolok'}</div>
                    <div style="margin-top:6px;display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:10px;font-weight:bold;text-transform:uppercase;padding:2px 6px;border-radius:999px;background:#e0f2fe;color:#0369a1;">${p.status.replace(/_/g, ' ')}</span>
                        <button onclick="focusShipmentOnMap('${p.tracking_id}')" style="font-size:11px;color:#2563eb;font-weight:bold;background:none;border:none;cursor:pointer;">🛵 Route</button>
                    </div>
                </div>
            `;
            m.bindPopup(popupContent);
            mapMarkers[p.tracking_id] = { marker: m, data: p };
            groupList.push(m);
        });

        if (groupList.length > 1) {
            fleetMap.fitBounds(L.featureGroup(groupList).getBounds(), { padding: [40, 40] });
        }

        // Trigger map.invalidateSize() to prevent gray or blank tiles
        setTimeout(() => { if (fleetMap) fleetMap.invalidateSize(); }, 100);
        setTimeout(() => { if (fleetMap) fleetMap.invalidateSize(); }, 300);
        setTimeout(() => { if (fleetMap) fleetMap.invalidateSize(); }, 600);
        setTimeout(() => { if (fleetMap) fleetMap.invalidateSize(); }, 1200);

        window.addEventListener('resize', () => {
            if (fleetMap) fleetMap.invalidateSize();
        });
    }

    function resetFleetMapView() {
        if (activeRouteLine) {
            fleetMap.removeLayer(activeRouteLine);
            activeRouteLine = null;
        }
        if (activeRiderMarker) {
            fleetMap.removeLayer(activeRiderMarker);
            activeRiderMarker = null;
        }
        if (riderAnimationTimer) {
            clearInterval(riderAnimationTimer);
            riderAnimationTimer = null;
        }
        document.getElementById('routeInfoBar').classList.add('hidden');
        document.getElementById('mapActiveSub').textContent = 'Real-time GPS delivery routes across Polomolok';

        const allMarkers = Object.values(mapMarkers).map(o => o.marker);
        if (allMarkers.length > 0) {
            fleetMap.fitBounds(L.featureGroup(allMarkers).getBounds(), { padding: [40, 40] });
        } else {
            fleetMap.setView([6.2136, 125.0661], 13);
        }
    }

    let liveGpsWatchId = null;
    let isRiderMoving = false;
    let activeRouteCoordinates = [];
    let currentRiderIndex = 0;

    function fetchRealRoadRoute(start, end) {
        // OSRM Driving & Shortest Road Route API
        const url = `https://router.project-osrm.org/route/v1/driving/${start[1]},${start[0]};${end[1]},${end[0]}?overview=full&geometries=geojson`;
        return fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data && data.routes && data.routes.length > 0) {
                    const coords = data.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
                    const distanceKm = (data.routes[0].distance / 1000).toFixed(1);
                    const durationMins = Math.ceil(data.routes[0].duration / 60);
                    return { coords, distanceKm, durationMins, success: true };
                }
                throw new Error('No OSRM route');
            })
            .catch(() => {
                // Realistic Polomolok street shortcut waypoint fallback
                const midLat = (start[0] + end[0]) / 2 + (end[1] > start[1] ? 0.0012 : -0.0012);
                const midLng = (start[1] + end[1]) / 2;
                return {
                    coords: [start, [midLat, midLng], end],
                    distanceKm: '1.5',
                    durationMins: 5,
                    success: false
                };
            });
    }

    function toggleRiderMovement(trackingId) {
        if (isRiderMoving) {
            // Stop movement
            if (riderAnimationTimer) {
                clearInterval(riderAnimationTimer);
                riderAnimationTimer = null;
            }
            if (liveGpsWatchId) {
                navigator.geolocation.clearWatch(liveGpsWatchId);
                liveGpsWatchId = null;
            }
            isRiderMoving = false;
            const btn = document.getElementById('btnToggleMovement');
            if (btn) {
                btn.innerHTML = '<span class="material-symbols-outlined text-[15px]">play_arrow</span> Start Moving';
                btn.className = 'px-2.5 py-1 text-xs font-bold rounded-lg bg-primary text-on-primary hover:bg-primary/90 flex items-center gap-1';
            }
            const badge = document.getElementById('routeBadge');
            if (badge) badge.textContent = '🛵 Ready (Stationary)';
            return;
        }

        // Start movement along the real street shortcut
        if (!activeRouteCoordinates || activeRouteCoordinates.length < 2) return;
        isRiderMoving = true;
        const btn = document.getElementById('btnToggleMovement');
        if (btn) {
            btn.innerHTML = '<span class="material-symbols-outlined text-[15px]">pause</span> Pause Movement';
            btn.className = 'px-2.5 py-1 text-xs font-bold rounded-lg bg-amber-500 text-white hover:bg-amber-600 flex items-center gap-1';
        }
        const badge = document.getElementById('routeBadge');
        if (badge) badge.textContent = '🛵 Moving (Real-Time)';

        // If mobile GPS is available, listen to real GPS updates
        if ('geolocation' in navigator) {
            liveGpsWatchId = navigator.geolocation.watchPosition((pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                if (activeRiderMarker) {
                    activeRiderMarker.setLatLng([lat, lng]);
                }
            }, () => {}, { enableHighAccuracy: true });
        }

        // Advance along real street geometry
        if (riderAnimationTimer) clearInterval(riderAnimationTimer);
        riderAnimationTimer = setInterval(() => {
            if (currentRiderIndex < activeRouteCoordinates.length - 1) {
                currentRiderIndex++;
                const pt = activeRouteCoordinates[currentRiderIndex];
                if (activeRiderMarker) {
                    activeRiderMarker.setLatLng(pt);
                }
            } else {
                // Arrived at destination
                clearInterval(riderAnimationTimer);
                riderAnimationTimer = null;
                isRiderMoving = false;
                if (btn) {
                    btn.innerHTML = '<span class="material-symbols-outlined text-[15px]">check_circle</span> Arrived';
                    btn.className = 'px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-600 text-white flex items-center gap-1';
                }
                if (badge) badge.textContent = '✓ Arrived at Destination';
            }
        }, 1200);
    }

    function focusShipmentOnMap(trackingId, e) {
        if (e && e.stopPropagation) e.stopPropagation();

        const match = mapMarkers[trackingId];
        if (!match || !fleetMap) return;

        const p = match.data;
        const destCoords = [parseFloat(p.current_lat), parseFloat(p.current_lng)];
        const storeCoords = STORE_COORDS;

        // Reset previous route, timer, & movement state
        if (activeRouteLine) fleetMap.removeLayer(activeRouteLine);
        if (activeRiderMarker) fleetMap.removeLayer(activeRiderMarker);
        if (riderAnimationTimer) {
            clearInterval(riderAnimationTimer);
            riderAnimationTimer = null;
        }
        if (liveGpsWatchId) {
            navigator.geolocation.clearWatch(liveGpsWatchId);
            liveGpsWatchId = null;
        }
        isRiderMoving = false;
        currentRiderIndex = 0;

        // For Store Pick-up items, do NOT track motorcycle delivery mapping
        const isPickup = p.status === 'ready_for_pickup' || (p.destination_address && p.destination_address.toLowerCase().includes('pick-up'));
        if (isPickup) {
            fleetMap.setView(STORE_COORDS, 16);

            const infoBar = document.getElementById('routeInfoBar');
            infoBar.classList.remove('hidden');
            infoBar.innerHTML = `
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-[18px]">storefront</span>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-on-surface truncate">#${p.ref_number || p.tracking_id} · ${p.first_name ? p.first_name + ' ' + (p.last_name || '') : 'Customer'}</p>
                        <p class="text-[10px] text-on-surface-variant truncate">🏪 <strong>STORE PICK-UP</strong> · Customer goes to shop for collection (No motorcycle route mapping required)</p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">🏪 Store Pick-up</span>
                    <button type="button" onclick="resetFleetMapView()" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-surface-container-high text-on-surface hover:bg-surface-variant transition-all">Close</button>
                </div>
            `;
            return;
        }

        // Fetch real street road shortcut via OSRM for doorstep deliveries
        fetchRealRoadRoute(storeCoords, destCoords).then(routeData => {
            activeRouteCoordinates = routeData.coords;

            // Draw clean road-following route line
            activeRouteLine = L.polyline(activeRouteCoordinates, {
                color: '#2563eb',
                weight: 4.5,
                opacity: 0.9,
                lineCap: 'round',
                lineJoin: 'round',
            }).addTo(fleetMap);

            // Place stationary Motorcycle / Rider Icon at start of route (Store/Dispatch Hub)
            const initialRiderPos = activeRouteCoordinates[0];
            activeRiderMarker = L.marker(initialRiderPos, {
                icon: createMotorcycleIcon(),
                zIndexOffset: 1000
            }).addTo(fleetMap);

            activeRiderMarker.bindPopup(`
                <div style="text-align:center;padding:4px;font-family:inherit;">
                    <div style="font-weight:bold;color:#2563eb;font-size:13px;">🛵 Delivery Rider</div>
                    <div style="font-size:11px;color:#64748b;">Order #${p.ref_number || p.tracking_id}</div>
                    <div style="font-size:11px;color:#0f172a;margin-top:2px;">🛣️ Shortest Shortcut: ${routeData.distanceKm} km · ~${routeData.durationMins} mins</div>
                </div>
            `);

            // Update floating route bar with start movement control
            const infoBar = document.getElementById('routeInfoBar');
            infoBar.classList.remove('hidden');
            infoBar.innerHTML = `
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-[18px]">two_wheeler</span>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-on-surface truncate">#${p.ref_number || p.tracking_id} · ${p.first_name ? p.first_name + ' ' + (p.last_name || '') : 'Customer'}</p>
                        <p class="text-[10px] text-on-surface-variant truncate">📍 ${p.destination_address || 'Polomolok'} · <strong>${routeData.distanceKm} km (${routeData.durationMins} mins shortcut)</strong></p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <span id="routeBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">🛵 Ready</span>
                    <button id="btnToggleMovement" type="button" onclick="toggleRiderMovement('${trackingId}')" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-primary text-on-primary hover:bg-primary/90 flex items-center gap-1 transition-all">
                        <span class="material-symbols-outlined text-[15px]">play_arrow</span> Move
                    </button>
                </div>
            `;

            // Fit route in view
            fleetMap.fitBounds(activeRouteLine.getBounds(), { padding: [50, 50] });
            setTimeout(() => {
                match.marker.openPopup();
            }, 300);
        });

        // Highlight selected row in table
        document.querySelectorAll('.shipment-row').forEach(r => {
            if (r.dataset.tracking === trackingId) {
                r.classList.add('bg-primary/10', 'ring-1', 'ring-primary');
            } else {
                r.classList.remove('bg-primary/10', 'ring-1', 'ring-primary');
            }
        });
    }

    // ==========================================
    // ⚡ REAL-TIME INSTANT SEARCH & FILTER
    // ==========================================
    const searchInput = document.getElementById('liveShipmentSearch');
    const statusFilter = document.getElementById('liveStatusFilter');

    function applyLiveFiltering() {
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const status = (statusFilter ? statusFilter.value : '').toLowerCase().trim();
        const rows = document.querySelectorAll('.shipment-row');
        let matchCount = 0;
        let firstMatchTracking = null;

        rows.forEach((row) => {
            const tracking = (row.dataset.tracking || '').toLowerCase();
            const ref      = (row.dataset.ref || '').toLowerCase();
            const customer = (row.dataset.customer || '').toLowerCase();
            const dest     = (row.dataset.dest || '').toLowerCase();
            const rowStatus = (row.dataset.status || '').toLowerCase();

            const matchesQuery = query === '' || 
                tracking.includes(query) || 
                ref.includes(query) || 
                customer.includes(query) || 
                dest.includes(query);

            const matchesStatus = status === '' || rowStatus === status;

            if (matchesQuery && matchesStatus) {
                row.classList.remove('hidden');
                matchCount++;
                if (!firstMatchTracking) firstMatchTracking = row.dataset.tracking;
            } else {
                row.classList.add('hidden');
            }
        });

        // Show/hide empty row indicator
        let noRow = document.getElementById('noShipmentsRow');
        if (matchCount === 0) {
            if (!noRow) {
                const tbody = document.getElementById('shipmentTableBody');
                if (tbody) {
                    noRow = document.createElement('tr');
                    noRow.id = 'noShipmentsRow';
                    noRow.innerHTML = '<td colspan="7" class="py-lg text-center text-on-surface-variant font-medium">No shipments matching "' + query + '".</td>';
                    tbody.appendChild(noRow);
                }
            } else {
                noRow.classList.remove('hidden');
            }
        } else if (noRow) {
            noRow.classList.add('hidden');
        }

        // If typing a specific reference and single exact/close match is found, auto-focus map
        if (query.length >= 3 && firstMatchTracking && matchCount === 1) {
            focusShipmentOnMap(firstMatchTracking);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyLiveFiltering);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', applyLiveFiltering);
    }

    function resetLiveFilters() {
        if (searchInput) searchInput.value = '';
        if (statusFilter) statusFilter.value = '';
        applyLiveFiltering();
        resetFleetMapView();
    }

    // ==========================================
    // 📷 SCANNER & DETAILS MODAL
    // ==========================================
    function showScanResult(success, message, actionType) {
        const box = document.getElementById('scanResult');
        box.classList.remove('hidden');
        let bgClass = 'bg-green-100 text-green-800';
        if (!success) {
            bgClass = 'bg-error-container/40 text-on-error-container';
        } else if (actionType === 'returned') {
            bgClass = 'bg-purple-100 text-purple-900';
        }
        box.className = 'text-label-sm font-semibold rounded-lg p-sm ' + bgClass;
        box.textContent = message;
    }

    function lookupByTracking(trackingId) {
        const fd = new FormData();
        fd.append('tracking_id', trackingId);
        fd.append('csrf_test_name', csrfToken());

        return fetch("<?= site_url('tenant/deliveries/lookup') ?>", {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        }).then((r) => r.json());
    }

    function openDeliveryLookup(trackingId, autoUpdatedData) {
        const processData = (data) => {
            if (!data.success || !data.delivery) {
                document.getElementById('dmTracking').textContent = '';
                document.getElementById('dmBody').innerHTML = '<p class="text-body-md text-error">' + (data.error || 'Could not load delivery.') + '</p>';
                document.getElementById('deliveryModal').classList.remove('hidden');
                return;
            }
            const d = data.delivery;
            document.getElementById('dmTracking').textContent = '#' + d.tracking_id;
            document.getElementById('dmReference').textContent = '#' + (d.ref_number || '-');
            document.getElementById('dmType').textContent = d.deliverable_type.replace(/_/g, ' ');
            document.getElementById('dmCustomer').textContent = d.customer || 'Customer';
            document.getElementById('dmDestination').textContent = d.destination || 'N/A';
            document.getElementById('dmCourier').textContent = d.courier_name || 'N/A';

            const banner = document.getElementById('dmActionBanner');
            if (data.status_updated) {
                shouldReloadOnClose = true;
                banner.classList.remove('hidden');
                if (data.action_type === 'delivered') {
                    banner.className = 'text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 uppercase inline-block';
                    banner.textContent = '✓ AUTO-MARKED AS DELIVERED';
                } else if (data.action_type === 'returned') {
                    banner.className = 'text-xs font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 uppercase inline-block';
                    banner.textContent = '↺ AUTO-MARKED AS RETURNED';
                }
            } else {
                banner.classList.add('hidden');
            }

            let statusPill = '<span class="px-2 py-1 rounded-full text-xs font-bold uppercase ' + 
                (d.status_key === 'delivered' ? 'bg-emerald-100 text-emerald-800' : 
                (d.status_key === 'returned' ? 'bg-purple-100 text-purple-800' : 
                (d.status_key === 'shipped' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'))) + 
                '">' + (d.status || d.status_key) + '</span>';
            document.getElementById('dmStatus').innerHTML = statusPill;

            document.getElementById('dmCreated').textContent = d.created_at || '';
            document.getElementById('dmShipped').textContent = d.shipped_at || '—';
            document.getElementById('dmDelivered').textContent = d.delivered_at || '—';
            document.getElementById('deliveryModal').classList.remove('hidden');
        };

        if (autoUpdatedData) {
            processData(autoUpdatedData);
        } else {
            lookupByTracking(trackingId)
                .then(processData)
                .catch(() => {
                    document.getElementById('dmBody').innerHTML = '<p class="text-body-md text-error">Could not load delivery.</p>';
                    document.getElementById('deliveryModal').classList.remove('hidden');
                });
        }
    }

    function closeDeliveryModal(forceReload = false) {
        document.getElementById('deliveryModal').classList.add('hidden');
        if (shouldReloadOnClose || forceReload) {
            window.location.reload();
        }
    }

    function handleScanCode(value) {
        if (isProcessingScan) return;
        isProcessingScan = true;

        const status = document.getElementById('scanStatus');
        const result = document.getElementById('scanResult');
        result.classList.add('hidden');
        status.textContent = 'Processing scanned code "' + value + '"...';

        lookupByTracking(value)
            .then((data) => {
                if (data.success) {
                    closeScanner();
                    openDeliveryLookup(value, data);
                } else {
                    showScanResult(false, data.error || 'Tracking ID / Order not found.');
                    setTimeout(() => { isProcessingScan = false; }, 1500);
                }
            })
            .catch(() => {
                showScanResult(false, 'Could not reach the server.');
                setTimeout(() => { isProcessingScan = false; }, 1500);
            });
    }

    function openScanner() {
        isProcessingScan = false;
        const modal = document.getElementById('scannerModal');
        const status = document.getElementById('scanStatus');
        const result = document.getElementById('scanResult');
        const placeholder = document.getElementById('deliveryCameraPlaceholder');

        result.classList.add('hidden');
        if (placeholder) {
            placeholder.classList.remove('hidden');
            placeholder.innerHTML = `
                <span class="material-symbols-outlined text-4xl mb-1 text-primary">photo_camera</span>
                <span class="text-xs font-semibold">Starting camera...</span>
                <span class="text-[10px] opacity-70 mt-1">Please allow camera permissions if prompted</span>
            `;
        }
        status.textContent = 'Point your camera at the delivery QR label.';
        modal.classList.remove('hidden');

        if (typeof Html5Qrcode === 'undefined') {
            status.textContent = 'Scanner library loading or blocked. Enter tracking ID below.';
            if (placeholder) placeholder.classList.add('hidden');
            return;
        }

        if (!deliveryHtml5QrCode) {
            deliveryHtml5QrCode = new Html5Qrcode('deliveryQrReader');
        }

        const showCameraError = () => {
            if (placeholder) {
                placeholder.innerHTML = `
                    <span class="material-symbols-outlined text-4xl mb-1 text-amber-400">videocam_off</span>
                    <span class="text-xs font-semibold">Camera unavailable</span>
                    <span class="text-[10px] opacity-70 mt-1">Check permissions or enter the tracking ID manually below</span>
                `;
            }
            status.textContent = 'Camera unavailable. Please enter tracking ID below.';
        };

        const startScanner = (cameraConfig) => {
            const config = { fps: 10, qrbox: { width: 220, height: 220 } };
            return deliveryHtml5QrCode.start(
                cameraConfig,
                config,
                (decodedText) => {
                    stopScannerStream();
                    handleScanCode(decodedText);
                },
                () => {}
            );
        };

        startScanner({ facingMode: "environment" })
            .then(() => {
                if (placeholder) placeholder.classList.add('hidden');
            })
            .catch(() => {
                if (Html5Qrcode.getCameras) {
                    Html5Qrcode.getCameras().then(cameras => {
                        if (cameras && cameras.length) {
                            startScanner({ deviceId: { exact: cameras[0].id } })
                                .then(() => {
                                    if (placeholder) placeholder.classList.add('hidden');
                                })
                                .catch(() => showCameraError());
                        } else {
                            showCameraError();
                        }
                    }).catch(() => showCameraError());
                } else {
                    showCameraError();
                }
            });
    }

    function lookupManual() {
        const value = document.getElementById('manualTracking').value.trim();
        if (value === '') {
            showScanResult(false, 'Please enter a tracking ID or Order #.');
            return;
        }
        handleScanCode(value);
    }

    const manualTrackingInput = document.getElementById('manualTracking');
    if (manualTrackingInput) {
        manualTrackingInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                lookupManual();
            }
        });
    }

    function stopScannerStream() {
        if (deliveryHtml5QrCode) {
            try {
                if (deliveryHtml5QrCode.isScanning) {
                    deliveryHtml5QrCode.stop().catch(e => console.error(e));
                }
            } catch (e) {
                console.error(e);
            }
        }
    }

    function closeScanner() {
        stopScannerStream();
        document.getElementById('scannerModal').classList.add('hidden');
        document.getElementById('scanResult').classList.add('hidden');
        document.getElementById('scanStatus').textContent = 'Point your camera at the delivery QR label.';
    }

    window.openScanner = openScanner;
    window.closeScanner = closeScanner;
    window.lookupManual = lookupManual;

    const scanBtn = document.getElementById('btnOpenDeliveryScanner');
    if (scanBtn) {
        scanBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openScanner();
        });
    }

    function ensureLeafletLoaded() {
        if (typeof L !== 'undefined') {
            initMap();
            return;
        }
        let attempts = 0;
        const interval = setInterval(() => {
            attempts++;
            if (typeof L !== 'undefined') {
                clearInterval(interval);
                initMap();
            } else if (attempts > 60) {
                clearInterval(interval);
                console.warn('Leaflet map library timed out.');
            }
        }, 80);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureLeafletLoaded);
    } else {
        ensureLeafletLoaded();
    }

    function handleExportClick(btn, label) {
        const icon = btn.querySelector('.material-symbols-outlined');
        const textSpan = btn.querySelector('span:not(.material-symbols-outlined)');
        const origIcon = icon ? icon.textContent : 'download';
        const origText = textSpan ? textSpan.textContent : 'Export';
        if (icon) {
            icon.textContent = 'progress_activity';
            icon.classList.add('animate-spin');
        }
        if (textSpan) textSpan.textContent = label || 'Exporting...';
        btn.classList.add('opacity-75', 'pointer-events-none');
        setTimeout(() => {
            if (icon) {
                icon.textContent = origIcon;
                icon.classList.remove('animate-spin');
            }
            if (textSpan) textSpan.textContent = origText;
            btn.classList.remove('opacity-75', 'pointer-events-none');
        }, 3500);
    }
</script>

<?= $this->endSection() ?>