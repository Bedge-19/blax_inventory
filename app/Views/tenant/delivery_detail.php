<?= $this->extend('layouts/tenant') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

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
                        <button type="button" id="btnToggleRider" onclick="toggleRiderAnimation()" class="px-2.5 py-1 text-xs font-bold bg-primary/10 text-primary hover:bg-primary hover:text-white border border-primary/30 rounded-lg transition-all flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">two_wheeler</span>
                            <span id="btnRiderText">Simulate Rider</span>
                        </button>
                        <button type="button" onclick="recenterRoute()" class="px-2.5 py-1 text-xs font-semibold bg-surface-container border border-outline-variant/40 rounded-lg hover:bg-surface-container-high transition-all flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">my_location</span>
                            <span>Recenter</span>
                        </button>
                    </div>
                </div>

                <!-- Floating Live Tracking Indicator Bar -->
                <div id="liveTrackingBar" class="p-2.5 px-4 bg-surface-container-low/70 border-b border-outline-variant/20 flex items-center justify-between gap-sm text-xs flex-wrap">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                        <span class="font-bold text-on-surface">Live Status:</span>
                        <span id="riderStatusText" class="text-on-surface-variant font-medium">Tracking route between <?= esc($shop['shop_name'] ?? 'Storefront') ?> and Destination</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="routeProgressBadge" class="font-mono font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-full text-[11px]">0% en route</span>
                    </div>
                </div>

                <!-- Leaflet Map Canvas -->
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
                    <span class="text-[11px] text-outline">OpenStreetMap &bull; Realtime Bounds</span>
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
    let mapInstance = null;
    let shopLatLng = [<?= json_encode($shopLat) ?>, <?= json_encode($shopLng) ?>];
    let destLatLng = [<?= json_encode($destLat) ?>, <?= json_encode($destLng) ?>];

    function initDeliveryDetailMap() {
        const container = document.getElementById('singleDeliveryMap');
        if (!container || typeof L === 'undefined') return;

        // Custom Leaflet Markers with SVG icons
        const shopIcon = L.divIcon({
            className: 'custom-shop-pin',
            html: `<div style="background-color:#2563eb;color:white;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 10px rgba(0,0,0,0.3);border:2.5px solid white;">
                     <span class="material-symbols-outlined" style="font-size:18px;">storefront</span>
                   </div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 34],
            popupAnchor: [0, -34]
        });

        const destIcon = L.divIcon({
            className: 'custom-dest-pin',
            html: `<div style="background-color:#16a34a;color:white;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 10px rgba(0,0,0,0.3);border:2.5px solid white;">
                     <span class="material-symbols-outlined" style="font-size:18px;">home</span>
                   </div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 34],
            popupAnchor: [0, -34]
        });

        mapInstance = L.map(container, {
            center: shopLatLng,
            zoom: 14,
            scrollWheelZoom: true
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(mapInstance);

        // Shop Marker
        const shopMarker = L.marker(shopLatLng, { icon: shopIcon }).addTo(mapInstance);
        shopMarker.bindPopup(`
            <div style="padding:4px;font-family:sans-serif;">
                <strong style="color:#2563eb;font-size:13px;"><?= esc($shop['shop_name'] ?? 'Your Store') ?></strong>
                <p style="font-size:11px;color:#64748b;margin:2px 0 0 0;">Shop Origin Dispatch Point</p>
            </div>
        `);

        // Destination Marker
        const destMarker = L.marker(destLatLng, { icon: destIcon }).addTo(mapInstance);
        destMarker.bindPopup(`
            <div style="padding:4px;font-family:sans-serif;">
                <strong style="color:#16a34a;font-size:13px;"><?= esc(trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? 'Customer'))) ?></strong>
                <p style="font-size:11px;color:#334155;margin:2px 0 0 0;"><?= esc($delivery['destination_address']) ?></p>
            </div>
        `).openPopup();

        // Polyline connecting points
        const polyline = L.polyline([shopLatLng, destLatLng], {
            color: '#2563eb',
            weight: 4,
            opacity: 0.8,
            dashArray: '8, 8'
        }).addTo(mapInstance);

        // Fit bounds with padding
        const group = new L.featureGroup([shopMarker, destMarker, polyline]);
        mapInstance.fitBounds(group.getBounds().pad(0.2));

        // Initialize Rider Marker
        const riderIcon = L.divIcon({
            className: 'custom-rider-pin',
            html: `<div style="background-color:#ea580c;color:white;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(234,88,12,0.5);border:2.5px solid white;">
                     <span class="material-symbols-outlined" style="font-size:18px;">two_wheeler</span>
                   </div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 17],
            popupAnchor: [0, -17]
        });

        riderMarker = L.marker(shopLatLng, { icon: riderIcon, zIndexOffset: 1000 }).addTo(mapInstance);
        riderMarker.bindPopup(`<strong>Delivery Courier</strong><br>Dispatched along Polomolok route`);

        // Auto-start animation if in transit or shipped
        const currentDeliveryStatus = <?= json_encode($delivery['status'] ?? '') ?>;
        if (currentDeliveryStatus === 'in_transit' || currentDeliveryStatus === 'shipped') {
            setTimeout(() => {
                toggleRiderAnimation();
            }, 600);
        }

        // Invalidate size on resize
        setTimeout(() => mapInstance.invalidateSize(), 300);
    }

    let riderMarker = null;
    let riderTimer = null;
    let riderProgress = 0;
    let isRiderMoving = false;

    function toggleRiderAnimation() {
        if (!mapInstance) return;
        isRiderMoving = !isRiderMoving;

        const btnText = document.getElementById('btnRiderText');
        const statusText = document.getElementById('riderStatusText');

        if (isRiderMoving) {
            btnText.textContent = 'Pause GPS';
            statusText.textContent = 'Live GPS Rider moving along waypoint trajectory';
            startRiderMovement();
        } else {
            btnText.textContent = 'Resume GPS';
            statusText.textContent = 'Live GPS paused at current coordinates';
            clearInterval(riderTimer);
        }
    }

    function startRiderMovement() {
        clearInterval(riderTimer);
        if (!riderMarker) {
            const riderIcon = L.divIcon({
                className: 'custom-rider-pin',
                html: `<div style="background-color:#ea580c;color:white;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(234,88,12,0.5);border:2.5px solid white;">
                         <span class="material-symbols-outlined" style="font-size:18px;">two_wheeler</span>
                       </div>`,
                iconSize: [34, 34],
                iconAnchor: [17, 17],
                popupAnchor: [0, -17]
            });
            riderMarker = L.marker(shopLatLng, { icon: riderIcon, zIndexOffset: 1000 }).addTo(mapInstance);
        }

        riderTimer = setInterval(() => {
            riderProgress += 0.02;
            if (riderProgress >= 1) {
                riderProgress = 1;
                clearInterval(riderTimer);
                isRiderMoving = false;
                document.getElementById('btnRiderText').textContent = 'Replay Route';
                document.getElementById('riderStatusText').textContent = 'Courier has arrived at Customer Destination!';
                document.getElementById('routeProgressBadge').textContent = '100% arrived';
                riderProgress = 0;
                return;
            }

            const curLat = shopLatLng[0] + (destLatLng[0] - shopLatLng[0]) * riderProgress;
            const curLng = shopLatLng[1] + (destLatLng[1] - shopLatLng[1]) * riderProgress;
            riderMarker.setLatLng([curLat, curLng]);

            const pct = Math.round(riderProgress * 100);
            document.getElementById('routeProgressBadge').textContent = pct + '% en route';
        }, 300);
    }

    function recenterRoute() {
        if (!mapInstance) return;
        const bounds = L.latLngBounds([shopLatLng, destLatLng]);
        mapInstance.fitBounds(bounds.pad(0.2));
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof L !== 'undefined') {
            initDeliveryDetailMap();
        } else {
            let retries = 0;
            const timer = setInterval(() => {
                retries++;
                if (typeof L !== 'undefined') {
                    clearInterval(timer);
                    initDeliveryDetailMap();
                } else if (retries > 30) {
                    clearInterval(timer);
                }
            }, 100);
        }
    });
</script>

<?= $this->endSection() ?>
