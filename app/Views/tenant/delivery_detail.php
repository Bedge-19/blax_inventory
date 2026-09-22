<?= $this->extend('layouts/tenant') ?>
<?= $this->section('content') ?>

<div class="space-y-lg max-w-7xl mx-auto pb-xl">

    <!-- Top Navigation & Action Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-md">
        <div class="flex items-center gap-sm">
            <a href="<?= base_url('tenant/delivery') ?>" class="p-2.5 rounded-xl border border-outline-variant/40 bg-surface-container-low hover:bg-surface-container text-on-surface-variant transition-colors flex items-center justify-center shadow-2xs">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <div>
                <div class="flex items-center gap-xs text-xs text-on-surface-variant">
                    <a href="<?= base_url('tenant/delivery') ?>" class="hover:underline">Deliveries</a>
                    <span>/</span>
                    <span class="font-mono text-primary font-bold">#<?= esc($delivery['tracking_id']) ?></span>
                </div>
                <h1 class="text-title-lg sm:text-headline-sm font-extrabold text-on-surface tracking-tight mt-0.5 flex items-center gap-sm flex-wrap">
                    <span>Delivery Route &amp; Mapping</span>
                    <?= status_badge($delivery['status']) ?>
                </h1>
            </div>
        </div>

        <div class="flex items-center gap-sm flex-wrap">
            <span class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-surface-container-low border border-outline-variant/30 text-on-surface-variant flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px] text-primary">local_shipping</span>
                <span>Type: <?= esc(ucfirst(str_replace('_', ' ', $delivery['deliverable_type'] ?? 'Order'))) ?></span>
            </span>
            <span class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-surface-container-low border border-outline-variant/30 text-on-surface-variant flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px] text-outline">schedule</span>
                <span>Created: <?= date('M d, Y h:i A', strtotime($delivery['created_at'])) ?></span>
            </span>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-md rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 text-sm font-semibold flex items-center gap-sm">
            <span class="material-symbols-outlined text-[20px]">check_circle</span>
            <span><?= session()->getFlashdata('success') ?></span>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-md rounded-xl bg-error-container/30 border border-error/30 text-on-error-container text-sm font-semibold flex items-center gap-sm">
            <span class="material-symbols-outlined text-[20px]">error</span>
            <span><?= session()->getFlashdata('error') ?></span>
        </div>
    <?php endif; ?>

    <!-- Main Grid: Left 7 cols Map, Right 5 cols Info & Status -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-lg items-start">

        <!-- Map Column (7 cols) -->
        <div class="lg:col-span-7 space-y-md">
            <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 overflow-hidden shadow-sm">
                <div class="p-md border-b border-outline-variant/20 flex items-center justify-between bg-surface-container-low/40">
                    <div class="flex items-center gap-sm">
                        <span class="p-2 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                            <span class="material-symbols-outlined text-[18px]">near_me</span>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-on-surface">Live Route &amp; GPS Tracking</h3>
                            <p class="text-[11px] text-on-surface-variant">Polomolok, South Cotabato &bull; Direct Waypoint Route</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-xs">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold border <?= in_array($delivery['status'], ['shipped', 'in_transit']) ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-surface-container text-on-surface-variant border-outline-variant/30' ?>">
                            <span class="w-2 h-2 rounded-full <?= in_array($delivery['status'], ['shipped', 'in_transit']) ? 'bg-emerald-500 animate-ping' : 'bg-gray-400' ?>"></span>
                            <span><?= in_array($delivery['status'], ['shipped', 'in_transit']) ? 'GPS Broadcasting Active' : 'Passive Tracking' ?></span>
                        </span>
                    </div>
                </div>

                <!-- Honest Live GPS Broadcasting Indicator Bar -->
                <div id="liveTrackingBar" class="p-2.5 px-4 bg-surface-container-low/70 border-b border-outline-variant/20 flex items-center justify-between gap-sm text-xs flex-wrap">
                    <div class="flex items-center gap-2">
                        <span id="gpsIndicatorDot" class="w-2 h-2 rounded-full <?= in_array($delivery['status'], ['shipped', 'in_transit']) ? 'bg-emerald-500 animate-ping' : 'bg-gray-400' ?>"></span>
                        <span class="font-bold text-on-surface">GPS Status:</span>
                        <span id="gpsStatusText" class="text-on-surface-variant font-medium">
                            <?php if (in_array($delivery['status'], ['shipped', 'in_transit'])): ?>
                                Connecting to device GPS...
                            <?php elseif ($delivery['status'] === 'delivered'): ?>
                                Delivery completed
                            <?php elseif ($delivery['status'] === 'cancelled'): ?>
                                Delivery cancelled
                            <?php else: ?>
                                Awaiting dispatch
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="gpsLastUpdated" class="font-mono text-outline text-[11px]"></span>
                    </div>
                </div>

                <!-- Google Maps Canvas -->
                <div id="singleDeliveryMap" class="w-full h-[480px] sm:h-[520px] bg-surface-container z-0" style="min-height:480px;"></div>

                <!-- Map Legend Banner -->
                <div class="p-sm bg-surface-container-low/60 border-t border-outline-variant/20 flex items-center justify-between text-xs text-on-surface-variant flex-wrap gap-sm">
                    <div class="flex items-center gap-md">
                        <span class="flex items-center gap-1">
                            <span class="w-3 h-3 rounded-full bg-primary inline-block"></span>
                            <strong class="text-on-surface">Shop Origin</strong> (<?= esc($shop['shop_name'] ?? 'Your Store') ?>)
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-3 h-3 rounded-full bg-emerald-600 inline-block"></span>
                            <strong class="text-on-surface">Destination</strong> (Customer)
                        </span>
                    </div>
                    <span class="text-[11px] text-outline">Google Maps &bull; Realtime Bounds</span>
                </div>
            </div>

            <!-- Destination Address Card -->
            <div class="bg-surface-container-lowest rounded-2xl p-md border border-outline-variant/30 flex items-start gap-md shadow-2xs">
                <span class="p-2.5 bg-emerald-500/10 text-emerald-700 rounded-xl material-symbols-outlined shrink-0 text-xl">home_pin</span>
                <div class="min-w-0 flex-1">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Delivery Destination Address</h4>
                    <p class="text-body-md font-bold text-on-surface mt-0.5"><?= esc($delivery['destination_address'] ?: 'Customer Delivery Address, Polomolok') ?></p>
                    <p class="text-xs font-mono text-outline mt-1">Coordinates: <?= number_format($destLat, 6) ?>, <?= number_format($destLng, 6) ?></p>
                </div>
            </div>
        </div>

        <!-- Details Column (5 cols) -->
        <div class="lg:col-span-5 space-y-md">

            <!-- Status Updater & Timeline Card -->
            <div class="bg-surface-container-lowest rounded-2xl p-lg border border-outline-variant/30 shadow-sm space-y-md">
                <div class="flex items-center justify-between border-b border-outline-variant/20 pb-sm">
                    <h3 class="text-title-md font-bold text-on-surface">Delivery Progress</h3>
                    <span class="font-mono text-xs text-primary font-bold">#<?= esc($delivery['tracking_id']) ?></span>
                </div>

                <!-- Visual Step Indicator -->
                <?php
                    $curStatus = $delivery['status'] ?? 'ready_for_pickup';
                    $steps = [
                        'ready_for_pickup' => ['label' => 'Ready', 'icon' => 'inventory_2'],
                        'shipped'          => ['label' => 'Dispatched', 'icon' => 'local_shipping'],
                        'in_transit'       => ['label' => 'In Transit', 'icon' => 'two_wheeler'],
                        'delivered'        => ['label' => 'Delivered', 'icon' => 'check_circle'],
                    ];
                    $stepKeys = array_keys($steps);
                    $curIdx = array_search($curStatus, $stepKeys, true);
                    if ($curIdx === false) {
                        $curIdx = ($curStatus === 'cancelled' || $curStatus === 'returned') ? -1 : 0;
                    }
                ?>
                <div class="grid grid-cols-4 gap-1 relative pt-2">
                    <?php foreach ($stepKeys as $idx => $k): ?>
                        <?php 
                            $isPassed = ($curIdx >= $idx && $curIdx !== -1);
                            $isCurrent = ($curIdx === $idx);
                        ?>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all <?= $isCurrent ? 'bg-primary text-on-primary ring-4 ring-primary/20 scale-110 shadow-sm' : ($isPassed ? 'bg-emerald-600 text-white' : 'bg-surface-container text-outline') ?>">
                                <span class="material-symbols-outlined text-[16px]"><?= $steps[$k]['icon'] ?></span>
                            </div>
                            <span class="text-[10px] mt-1 font-semibold <?= $isCurrent ? 'text-primary font-bold' : ($isPassed ? 'text-on-surface' : 'text-outline') ?>">
                                <?= $steps[$k]['label'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Status Update Form -->
                <form action="<?= base_url('tenant/deliveries/update-status') ?>" method="POST" class="pt-sm border-t border-outline-variant/20 space-y-sm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="delivery_id" value="<?= (int) $delivery['id'] ?>">

                    <label class="text-xs font-bold text-on-surface-variant uppercase block">Update Delivery Status</label>
                    <div class="flex items-center gap-xs">
                        <select name="delivery_status" class="flex-1 p-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold focus:ring-2 focus:ring-primary">
                            <option value="ready_for_pickup" <?= $curStatus === 'ready_for_pickup' ? 'selected' : '' ?>>Ready for Pickup</option>
                            <option value="shipped" <?= $curStatus === 'shipped' ? 'selected' : '' ?>>Shipped (Dispatched)</option>
                            <option value="in_transit" <?= $curStatus === 'in_transit' ? 'selected' : '' ?>>In Transit</option>
                            <option value="delivered" <?= $curStatus === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="returned" <?= $curStatus === 'returned' ? 'selected' : '' ?>>Returned</option>
                            <option value="cancelled" <?= $curStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="px-md py-2.5 bg-primary text-on-primary font-bold text-xs rounded-xl hover:bg-primary/90 transition-all shadow-sm shrink-0">
                            Apply
                        </button>
                    </div>
                </form>
            </div>

            <!-- Customer Card -->
            <div class="bg-surface-container-lowest rounded-2xl p-lg border border-outline-variant/30 shadow-sm space-y-sm">
                <div class="flex items-center justify-between border-b border-outline-variant/20 pb-xs">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Customer Information</h4>
                    <?php if ($customer): ?>
                        <span class="text-[11px] text-outline font-mono">ID: #<?= (int)$customer['id'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-sm pt-xs">
                    <?php if (!empty($customer['profile_image_url'])): ?>
                        <img src="<?= base_url($customer['profile_image_url']) ?>" class="w-12 h-12 rounded-full object-cover border border-outline-variant/40" alt="Customer">
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-body-lg">
                            <?= strtoupper(substr($customer['first_name'] ?? 'C', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-on-surface text-body-md truncate"><?= esc(trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? 'Customer'))) ?></p>
                        <p class="text-xs text-on-surface-variant flex items-center gap-1 mt-0.5">
                            <span class="material-symbols-outlined text-[14px] text-primary">phone</span>
                            <span><?= esc($customer['phone'] ?? 'No phone provided') ?></span>
                        </p>
                        <?php if (!empty($customer['email'])): ?>
                            <p class="text-[11px] text-outline truncate"><?= esc($customer['email']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Deliverable Details (Order or Printing Job) -->
            <div class="bg-surface-container-lowest rounded-2xl p-lg border border-outline-variant/30 shadow-sm space-y-sm">
                <div class="flex items-center justify-between border-b border-outline-variant/20 pb-xs">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                        <?= $order ? 'Order Items' : 'Printing Job Specs' ?>
                    </h4>
                    <?php if ($order): ?>
                        <a href="<?= base_url('tenant/orders') ?>" class="text-xs font-bold text-primary hover:underline font-mono">#<?= esc($order['order_number']) ?></a>
                    <?php elseif ($printingRequest): ?>
                        <span class="text-xs font-bold text-primary font-mono">#<?= esc($printingRequest['request_number'] ?? 'PR-'.$printingRequest['id']) ?></span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($orderItems)): ?>
                    <div class="divide-y divide-outline-variant/10 max-h-64 overflow-y-auto pr-1">
                        <?php foreach ($orderItems as $it): ?>
                            <?php
                                $img = !empty($it['gallery_image']) ? $it['gallery_image'] : (!empty($it['product_image']) ? $it['product_image'] : '');
                                if ($img !== '' && !str_starts_with($img, 'http://') && !str_starts_with($img, 'https://')) {
                                    $img = base_url($img);
                                }
                            ?>
                            <div class="py-sm flex items-center justify-between gap-sm">
                                <div class="flex items-center gap-sm min-w-0">
                                    <?php if ($img): ?>
                                        <img src="<?= esc($img) ?>" class="w-10 h-10 rounded-lg object-cover bg-surface-container border border-outline-variant/30 shrink-0" alt="<?= esc($it['product_name']) ?>">
                                    <?php else: ?>
                                        <div class="w-10 h-10 rounded-lg bg-surface-container flex items-center justify-center text-outline shrink-0">
                                            <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-on-surface truncate"><?= esc($it['product_name']) ?></p>
                                        <?php if (!empty($it['variant_label'])): ?>
                                            <span class="text-[10px] text-primary font-semibold"><?= esc($it['variant_label']) ?></span>
                                        <?php endif; ?>
                                        <p class="text-[11px] text-on-surface-variant">Qty: <?= (int)$it['quantity'] ?> &times; ₱<?= number_format((float)$it['unit_price'], 2) ?></p>
                                    </div>
                                </div>
                                <span class="font-mono font-bold text-xs text-on-surface shrink-0">₱<?= number_format((float)$it['line_total'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($order): ?>
                        <div class="border-t border-outline-variant/20 pt-sm space-y-1 text-xs">
                            <div class="flex justify-between text-on-surface-variant"><span>Subtotal:</span><span class="font-mono">₱<?= number_format((float)$order['subtotal'], 2) ?></span></div>
                            <div class="flex justify-between text-on-surface-variant"><span>Shipping Fee:</span><span class="font-mono">₱<?= number_format((float)$order['shipping_fee'], 2) ?></span></div>
                            <div class="flex justify-between text-body-md font-bold text-on-surface pt-1 border-t border-outline-variant/10"><span>Total:</span><span class="font-mono text-primary">₱<?= number_format((float)$order['total_amount'], 2) ?></span></div>
                        </div>
                    <?php endif; ?>

                <?php elseif ($printingRequest): ?>
                    <div class="space-y-sm text-xs py-xs">
                        <div class="flex justify-between"><span class="text-on-surface-variant">File:</span><span class="font-bold text-on-surface truncate max-w-[200px]"><?= esc($printingRequest['file_name']) ?></span></div>
                        <div class="flex justify-between"><span class="text-on-surface-variant">Pages & Copies:</span><span class="font-bold text-on-surface"><?= (int)$printingRequest['page_count'] ?> pages &times; <?= (int)$printingRequest['copies'] ?> copies</span></div>
                        <div class="flex justify-between"><span class="text-on-surface-variant">Paper & Color:</span><span class="font-bold text-on-surface"><?= esc($printingRequest['paper_size']) ?> (<?= esc($printingRequest['color_mode']) ?>)</span></div>
                        <div class="flex justify-between"><span class="text-on-surface-variant">Binding:</span><span class="font-bold text-on-surface"><?= esc($printingRequest['binding_option'] ?: 'None') ?></span></div>
                        <div class="flex justify-between pt-sm border-t border-outline-variant/20 font-bold"><span class="text-on-surface">Total Price:</span><span class="font-mono text-primary text-sm">₱<?= number_format((float)$printingRequest['total_price'], 2) ?></span></div>
                    </div>
                <?php else: ?>
                    <p class="text-xs text-on-surface-variant py-sm">No linked items record found.</p>
                <?php endif; ?>
            </div>

        </div>

    </div>

</div>

<script>
    let mapInstance     = null;
    let shopMarker      = null;
    let destMarker      = null;
    let riderMarker     = null;
    let routePolyline   = null;
    let routePathPoints = [];
    let watchId         = null;
    let lastSentTime    = 0;
    let lastSentCoords  = null;

    // Handle Google Maps API authentication / activation failure
    window.gm_authFailure = function() {
        const container = document.getElementById('singleDeliveryMap');
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

    const shopLatLng = { lat: parseFloat(<?= json_encode($shopLat) ?>), lng: parseFloat(<?= json_encode($shopLng) ?>) };
    const destLatLng = { lat: parseFloat(<?= json_encode($destLat) ?>), lng: parseFloat(<?= json_encode($destLng) ?>) };
    const initialRiderLat = parseFloat(<?= json_encode($delivery['current_lat'] ?? null) ?>) || shopLatLng.lat;
    const initialRiderLng = parseFloat(<?= json_encode($delivery['current_lng'] ?? null) ?>) || shopLatLng.lng;
    let currentRiderLatLng = { lat: initialRiderLat, lng: initialRiderLng };
    const DELIVERY_ID = <?= (int) $delivery['id'] ?>;
    const IS_ACTIVE_DELIVERY = <?= json_encode(in_array($delivery['status'], ['shipped', 'in_transit'])) ?>;

    const POLOMOLOK_BOUNDS = {
        north: 6.32,
        south: 6.10,
        east: 125.18,
        west: 124.95
    };

    function createDetailMarkerElement(type) {
        const div = document.createElement('div');
        if (type === 'shop') {
            div.innerHTML = `
                <div style="background-color:#2563eb;color:white;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 10px rgba(0,0,0,0.3);border:2.5px solid white;cursor:pointer;">
                    <span class="material-symbols-outlined" style="font-size:18px;line-height:1;">storefront</span>
                </div>
            `;
        } else if (type === 'dest') {
            div.innerHTML = `
                <div style="background-color:#16a34a;color:white;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 10px rgba(0,0,0,0.3);border:2.5px solid white;cursor:pointer;">
                    <span class="material-symbols-outlined" style="font-size:18px;line-height:1;">home</span>
                </div>
            `;
        } else if (type === 'rider') {
            div.innerHTML = `
                <div style="background-color:#ea580c;color:white;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(234,88,12,0.5);border:2.5px solid white;cursor:pointer;">
                    <span class="material-symbols-outlined" style="font-size:18px;line-height:1;">two_wheeler</span>
                </div>
            `;
        }
        return div;
    }

    window.initDeliveryDetailMap = function() {
        const container = document.getElementById('singleDeliveryMap');
        if (!container || typeof google === 'undefined' || !google.maps) return;

        mapInstance = new google.maps.Map(container, {
            center: shopLatLng,
            zoom: 14,
            minZoom: 11,
            restriction: {
                latLngBounds: POLOMOLOK_BOUNDS,
                strictBounds: false
            },
            mapId: 'DEMO_MAP_ID',
            disableDefaultUI: false,
            zoomControl: true,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true
        });

        // 1. Shop Marker
        const shopElem = createDetailMarkerElement('shop');
        if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
            shopMarker = new google.maps.marker.AdvancedMarkerElement({
                map: mapInstance,
                position: shopLatLng,
                content: shopElem,
                title: <?= json_encode($shop['shop_name'] ?? 'Your Store') ?>
            });
        } else {
            shopMarker = new google.maps.Marker({
                map: mapInstance,
                position: shopLatLng,
                title: <?= json_encode($shop['shop_name'] ?? 'Your Store') ?>
            });
        }
        const shopInfoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding:4px;font-family:sans-serif;">
                    <strong style="color:#2563eb;font-size:13px;"><?= esc($shop['shop_name'] ?? 'Your Store') ?></strong>
                    <p style="font-size:11px;color:#64748b;margin:2px 0 0 0;">Shop Origin Dispatch Point</p>
                </div>
            `
        });
        shopMarker.addListener('click', () => {
            shopInfoWindow.open(mapInstance, shopMarker);
        });

        // 2. Destination Marker
        const destElem = createDetailMarkerElement('dest');
        if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
            destMarker = new google.maps.marker.AdvancedMarkerElement({
                map: mapInstance,
                position: destLatLng,
                content: destElem,
                title: 'Customer Destination'
            });
        } else {
            destMarker = new google.maps.Marker({
                map: mapInstance,
                position: destLatLng,
                title: 'Customer Destination'
            });
        }
        const destInfoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding:4px;font-family:sans-serif;">
                    <strong style="color:#16a34a;font-size:13px;"><?= esc(trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? 'Customer'))) ?></strong>
                    <p style="font-size:11px;color:#334155;margin:2px 0 0 0;"><?= esc($delivery['destination_address']) ?></p>
                </div>
            `
        });
        destMarker.addListener('click', () => {
            destInfoWindow.open(mapInstance, destMarker);
        });
        setTimeout(() => destInfoWindow.open(mapInstance, destMarker), 500);

        // 3. Rider Marker (positioned at current coordinates)
        const riderElem = createDetailMarkerElement('rider');
        if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
            riderMarker = new google.maps.marker.AdvancedMarkerElement({
                map: mapInstance,
                position: currentRiderLatLng,
                content: riderElem,
                title: 'Live Courier Position'
            });
        } else {
            riderMarker = new google.maps.Marker({
                map: mapInstance,
                position: currentRiderLatLng,
                title: 'Live Courier Position'
            });
        }
        const riderInfoWindow = new google.maps.InfoWindow({
            content: `<div style="padding:4px;font-family:sans-serif;min-width:140px;"><div style="font-size:11px;font-weight:700;color:#2563eb;text-transform:uppercase;letter-spacing:0.5px;">Live Courier Position</div><div style="font-size:13px;font-weight:700;color:#0f172a;margin-top:2px;">🏍️ <?= esc($shop['shop_name'] ?? 'Your Store') ?> Courier (You)</div><div style="font-size:11px;color:#64748b;margin-top:2px;">Live GPS location broadcast</div></div>`
        });
        riderMarker.addListener('click', () => {
            riderInfoWindow.open(mapInstance, riderMarker);
        });

        // 4. Fetch Route Polyline
        fetchDeliveryRoute(shopLatLng, destLatLng);

        // Fit map bounds once on load
        const bounds = new google.maps.LatLngBounds();
        bounds.extend(shopLatLng);
        bounds.extend(destLatLng);
        bounds.extend(currentRiderLatLng);
        mapInstance.fitBounds(bounds, 60);

        // 5. Start real GPS tracking if delivery is active
        if (IS_ACTIVE_DELIVERY) {
            startGpsBroadcasting();
        }
    };

    function fetchDeliveryRoute(origin, destination) {
        fetch('<?= base_url('api/route') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                origin: origin,
                destination: destination
            })
        })
        .then(res => res.json())
        .then(data => {
            let path = [];
            if (data && data.success && data.route) {
                if (data.route.points && data.route.points.length > 0) {
                    path = data.route.points.map(p => new google.maps.LatLng(parseFloat(p.lat), parseFloat(p.lng)));
                    routePathPoints = data.route.points;
                } else if (data.route.encodedPolyline && google.maps.geometry && google.maps.geometry.encoding) {
                    path = google.maps.geometry.encoding.decodePath(data.route.encodedPolyline);
                    routePathPoints = path.map(p => ({ lat: p.lat(), lng: p.lng() }));
                }
            }

            if (!path || path.length === 0) {
                path = [origin, destination];
                routePathPoints = [origin, destination];
            }

            if (routePolyline) routePolyline.setMap(null);
            routePolyline = new google.maps.Polyline({
                path: path,
                geodesic: true,
                strokeColor: '#2563eb',
                strokeOpacity: 0.85,
                strokeWeight: 4,
                map: mapInstance
            });
        })
        .catch(err => {
            console.warn('Could not fetch route from Routes API:', err);
            routePathPoints = [origin, destination];
            if (routePolyline) routePolyline.setMap(null);
            routePolyline = new google.maps.Polyline({
                path: [origin, destination],
                geodesic: true,
                strokeColor: '#2563eb',
                strokeOpacity: 0.85,
                strokeWeight: 4,
                map: mapInstance
            });
        });
    }

    function distanceMeters(lat1, lon1, lat2, lon2) {
        const R = 6371e3;
        const p1 = lat1 * Math.PI / 180;
        const p2 = lat2 * Math.PI / 180;
        const dp = (lat2 - lat1) * Math.PI / 180;
        const dl = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dp / 2) * Math.sin(dp / 2) +
                  Math.cos(p1) * Math.cos(p2) *
                  Math.sin(dl / 2) * Math.sin(dl / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    function startGpsBroadcasting() {
        if (!navigator.geolocation) {
            const statusElem = document.getElementById('gpsStatusText');
            if (statusElem) statusElem.textContent = 'Geolocation is not supported by your browser.';
            return;
        }

        watchId = navigator.geolocation.watchPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                const accuracy = Math.round(pos.coords.accuracy || 0);

                currentRiderLatLng = { lat, lng };

                // Update marker position on map
                if (riderMarker) {
                    if (riderMarker.position && typeof riderMarker.position.lat === 'function') {
                        riderMarker.setPosition(new google.maps.LatLng(lat, lng));
                    } else {
                        riderMarker.position = { lat, lng };
                    }
                }

                const now = Date.now();
                let shouldSend = false;
                if (now - lastSentTime >= 6000) {
                    shouldSend = true;
                } else if (lastSentCoords && distanceMeters(lat, lng, lastSentCoords.lat, lastSentCoords.lng) >= 5) {
                    if (now - lastSentTime >= 3000) {
                        shouldSend = true;
                    }
                }

                if (shouldSend) {
                    sendLocationUpdate(lat, lng, accuracy);
                }
            },
            (err) => {
                let msg = 'Unable to retrieve GPS location.';
                if (err.code === err.PERMISSION_DENIED) {
                    msg = 'Location permission denied. Please allow GPS access in your browser to broadcast live position.';
                } else if (err.code === err.POSITION_UNAVAILABLE) {
                    msg = 'GPS signal unavailable. Please check device location services.';
                } else if (err.code === err.TIMEOUT) {
                    msg = 'Location request timed out. Retrying...';
                }
                const statusElem = document.getElementById('gpsStatusText');
                if (statusElem) statusElem.textContent = msg;
                const dot = document.getElementById('gpsIndicatorDot');
                if (dot) dot.className = 'w-2.5 h-2.5 rounded-full bg-amber-500';
            },
            {
                enableHighAccuracy: true,
                maximumAge: 5000,
                timeout: 10000
            }
        );
    }

    function sendLocationUpdate(lat, lng, accuracy) {
        lastSentTime = Date.now();
        lastSentCoords = { lat, lng };

        const formData = new FormData();
        formData.append('delivery_id', DELIVERY_ID);
        formData.append('lat', lat);
        formData.append('lng', lng);
        const csrfToken = window.getCsrfToken ? window.getCsrfToken() : '';
        if (csrfToken) {
            formData.append('<?= csrf_token() ?>', csrfToken);
        }

        fetch('<?= base_url('tenant/deliveries/update-location') ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.success) {
                const timeStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                const statusElem = document.getElementById('gpsStatusText');
                if (statusElem) statusElem.textContent = `Broadcasting your live location to customer (±${accuracy}m)`;
                const timeElem = document.getElementById('gpsLastUpdated');
                if (timeElem) timeElem.textContent = `Last sent: ${timeStr}`;
                const dot = document.getElementById('gpsIndicatorDot');
                if (dot) dot.className = 'w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping';
            } else if (data && data.error) {
                const statusElem = document.getElementById('gpsStatusText');
                if (statusElem) statusElem.textContent = data.error;
            }
        })
        .catch(err => {
            console.debug('Failed to broadcast GPS position:', err);
        });
    }

    function stopBroadcastingOnExit() {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
        if (DELIVERY_ID) {
            const formData = new FormData();
            formData.append('delivery_id', DELIVERY_ID);
            const stopUrl = '<?= base_url('tenant/deliveries/stop-broadcast') ?>';
            if (navigator.sendBeacon) {
                navigator.sendBeacon(stopUrl, formData);
            } else {
                fetch(stopUrl, {
                    method: 'POST',
                    body: formData,
                    keepalive: true
                }).catch(() => {});
            }
        }
    }

    window.addEventListener('beforeunload', stopBroadcastingOnExit);
    window.addEventListener('pagehide', stopBroadcastingOnExit);

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof google !== 'undefined' && google.maps && !mapInstance) {
            window.initDeliveryDetailMap();
        }
    });
</script>

<!-- Google Maps Platform JS API -->
<script src="https://maps.googleapis.com/maps/api/js?key=<?= esc(env('GOOGLE_MAPS_API_KEY')) ?>&libraries=marker,geometry&loading=async&callback=initDeliveryDetailMap" async defer></script>

<?= $this->endSection() ?>
