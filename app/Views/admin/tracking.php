<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="space-y-xl">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-headline-md font-bold text-on-surface">Live Fleet Tracking</h1>
            <p class="text-body-md text-on-surface-variant">Real-time bird's-eye oversight of all shop courier deliveries across Polomolok.</p>
        </div>
        <div class="flex items-center gap-2 bg-surface-container-high px-4 py-2 rounded-xl border border-outline-variant/40 shadow-xs">
            <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-ping"></span>
            <span class="text-xs font-bold text-on-surface">Live Fleet Feed</span>
            <span class="text-outline text-xs">&bull;</span>
            <span id="lastSyncLabel" class="text-xs text-on-surface-variant font-mono">Syncing...</span>
        </div>
    </div>

    <!-- Operational KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-md">
        <div class="bg-surface-container-lowest p-md rounded-2xl border border-outline-variant/30 shadow-2xs">
            <div class="flex justify-between items-start mb-sm">
                <div class="p-2 bg-primary/10 text-primary rounded-xl">
                    <span class="material-symbols-outlined text-[20px]">two_wheeler</span>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-primary bg-primary/10 px-2 py-0.5 rounded-full">Fleet</span>
            </div>
            <h3 class="text-xs text-on-surface-variant font-medium">Active Couriers</h3>
            <p id="kpi-total-active" class="text-headline-md font-extrabold text-on-surface mt-xs font-mono"><?= number_format((int) ($kpis['total_active'] ?? count($pins ?? []))) ?></p>
        </div>

        <div class="bg-surface-container-lowest p-md rounded-2xl border border-outline-variant/30 shadow-2xs">
            <div class="flex justify-between items-start mb-sm">
                <div class="p-2 bg-purple-500/10 text-purple-600 rounded-xl">
                    <span class="material-symbols-outlined text-[20px]">navigation</span>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 bg-purple-500/10 px-2 py-0.5 rounded-full">Moving</span>
            </div>
            <h3 class="text-xs text-on-surface-variant font-medium">In Transit</h3>
            <p id="kpi-in-transit" class="text-headline-md font-extrabold text-purple-700 mt-xs font-mono"><?= number_format((int) ($kpis['in_transit'] ?? 0)) ?></p>
        </div>

        <div class="bg-surface-container-lowest p-md rounded-2xl border border-outline-variant/30 shadow-2xs">
            <div class="flex justify-between items-start mb-sm">
                <div class="p-2 bg-blue-500/10 text-blue-600 rounded-xl">
                    <span class="material-symbols-outlined text-[20px]">local_shipping</span>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700 bg-blue-500/10 px-2 py-0.5 rounded-full">Dispatched</span>
            </div>
            <h3 class="text-xs text-on-surface-variant font-medium">Shipped</h3>
            <p id="kpi-shipped" class="text-headline-md font-extrabold text-blue-700 mt-xs font-mono"><?= number_format((int) ($kpis['shipped'] ?? 0)) ?></p>
        </div>

        <div class="bg-surface-container-lowest p-md rounded-2xl border border-outline-variant/30 shadow-2xs">
            <div class="flex justify-between items-start mb-sm">
                <div class="p-2 bg-amber-500/10 text-amber-600 rounded-xl">
                    <span class="material-symbols-outlined text-[20px]">storefront</span>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 bg-amber-500/10 px-2 py-0.5 rounded-full">Hubs</span>
            </div>
            <h3 class="text-xs text-on-surface-variant font-medium">Active Stores</h3>
            <p id="kpi-active-shops" class="text-headline-md font-extrabold text-amber-700 mt-xs font-mono"><?= number_format((int) ($kpis['active_shops'] ?? count($groupedShops ?? []))) ?></p>
        </div>
    </div>

    <!-- Live Fleet Map Container -->
    <div class="relative rounded-2xl overflow-hidden border border-outline-variant/30 bg-surface-container shadow-sm" style="min-height: 540px;">
        <div id="fleet-map" class="w-full h-full" style="min-height: 540px;"></div>

        <!-- Legend Overlay (Bottom Left) -->
        <div class="absolute bottom-6 left-6 p-4 bg-surface/90 dark:bg-surface-container-high/90 backdrop-blur-md rounded-2xl border border-outline-variant/30 shadow-lg max-w-xs pointer-events-none">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                <h4 class="text-xs font-bold text-on-surface uppercase tracking-wider">Live Fleet Legend</h4>
            </div>
            <div class="space-y-1.5 text-xs text-on-surface">
                <div class="flex items-center justify-between gap-4">
                    <span class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded-full bg-blue-600 flex items-center justify-center text-white text-[9px] shadow-xs">🏍️</span>
                        <span>Shipped / Dispatched</span>
                    </span>
                    <span class="text-[10px] font-mono text-outline font-bold">#2563eb</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded-full bg-purple-600 flex items-center justify-center text-white text-[9px] shadow-xs">🏍️</span>
                        <span>In Transit / En Route</span>
                    </span>
                    <span class="text-[10px] font-mono text-outline font-bold">#7c3aed</span>
                </div>
            </div>
            <p class="mt-3 pt-2 border-t border-outline-variant/20 text-[10px] text-on-surface-variant">
                Polomolok, South Cotabato &bull; Auto-refreshing every 10s
            </p>
        </div>

        <?php if (ENVIRONMENT === 'development' && empty($pins)): ?>
            <!-- Development Mode Notice (Only shown if dev and zero real deliveries) -->
            <div id="demoPinNotice" class="absolute bottom-6 right-6 bg-surface/90 dark:bg-surface-container-high/90 backdrop-blur px-3 py-1.5 rounded-full text-xs text-on-surface-variant border border-outline-variant font-medium flex items-center gap-1.5 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                <span>Showing Polomolok Demo Pins (Dev Only)</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Active Deliveries Grouped by Storefront -->
    <div class="space-y-md">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-title-lg font-bold text-on-surface">Active Deliveries by Storefront</h2>
                <p class="text-xs text-on-surface-variant">Store owners deliver packages directly to customers within Polomolok.</p>
            </div>
        </div>

        <div id="grouped-shops-container">
            <?php if (!empty($groupedShops)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php foreach ($groupedShops as $shop): ?>
                        <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl shadow-xs overflow-hidden flex flex-col transition-all hover:border-primary/30">
                            <!-- Shop Card Header -->
                            <div class="p-md border-b border-outline-variant/20 bg-surface-container-low/40 flex items-center justify-between gap-sm">
                                <div class="flex items-center gap-sm min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-surface-container-high border border-outline-variant/30 overflow-hidden flex items-center justify-center shrink-0 shadow-2xs">
                                        <?php if (!empty($shop['shop_logo'])): ?>
                                            <img src="<?= esc(base_url($shop['shop_logo'])) ?>" alt="<?= esc($shop['shop_name']) ?>" class="w-full h-full object-cover" />
                                        <?php else: ?>
                                            <span class="material-symbols-outlined text-primary text-xl">storefront</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-bold text-on-surface truncate"><?= esc($shop['shop_name']) ?></h3>
                                        <p class="text-[11px] text-on-surface-variant">Polomolok Storefront</p>
                                    </div>
                                </div>
                                <span class="shrink-0 px-2.5 py-1 bg-primary/10 text-primary border border-primary/20 text-xs font-bold rounded-full">
                                    <?= count($shop['deliveries']) ?> Active
                                </span>
                            </div>

                            <!-- Deliveries List -->
                            <div class="divide-y divide-outline-variant/10 flex-1">
                                <?php foreach ($shop['deliveries'] as $d): ?>
                                    <div class="p-md space-y-2 hover:bg-surface-container-low/30 transition-colors">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-mono font-bold text-xs text-primary">#<?= esc($d['tracking_id'] ?? ('TRK-' . $d['id'])) ?></span>
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full <?= $d['status'] === 'in_transit' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-blue-50 text-blue-700 border border-blue-200' ?>">
                                                <span class="w-1.5 h-1.5 rounded-full <?= $d['status'] === 'in_transit' ? 'bg-purple-600' : 'bg-blue-600' ?>"></span>
                                                <span><?= $d['status'] === 'in_transit' ? 'In Transit' : 'Shipped' ?></span>
                                            </span>
                                        </div>

                                        <div class="text-xs text-on-surface">
                                            <p class="font-semibold text-on-surface">
                                                <?= esc(trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? 'Customer Recipient'))) ?>
                                            </p>
                                            <p class="text-[11px] text-on-surface-variant truncate max-w-full">
                                                <?= esc($d['destination_address'] ?? 'Polomolok') ?>
                                            </p>
                                        </div>

                                        <div class="flex items-center justify-between pt-1 text-[11px] text-outline">
                                            <span>
                                                Updated: <?= !empty($d['location_updated_at']) ? date('g:i A', strtotime($d['location_updated_at'])) : 'Recently' ?>
                                            </span>
                                            <button type="button" 
                                                    onclick="focusDeliveryPin(<?= (int)$d['id'] ?>)" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-surface-container border border-outline-variant/40 hover:bg-primary hover:text-white hover:border-primary transition-all">
                                                <span class="material-symbols-outlined text-[13px]">my_location</span>
                                                <span>Locate</span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Honest Empty State -->
                <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 p-12 text-center shadow-xs">
                    <div class="w-16 h-16 rounded-full bg-surface-container-high mx-auto flex items-center justify-center text-outline mb-3">
                        <span class="material-symbols-outlined text-3xl text-primary/70">two_wheeler</span>
                    </div>
                    <h3 class="text-base font-bold text-on-surface">No Active Deliveries on the Road</h3>
                    <p class="text-xs text-on-surface-variant max-w-md mx-auto mt-1">
                        There are currently no active deliveries in transit across Polomolok. Deliveries will appear here automatically when shop owners mark orders as shipped.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function(){
    // Handle Google Maps API authentication / activation failure
    window.gm_authFailure = function() {
        const container = document.getElementById('fleet-map');
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

    const isDev = <?= json_encode(ENVIRONMENT === 'development') ?>;
    let initialPins = <?= json_encode($pins ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
    
    // Only use demo pins in development mode when real pins are empty
    const demoPins = isDev ? [
        { id: 9991, tracking_id: 'TRK-TEST-POLO1', shop_name: 'Blax Printing Hub', destination_address: 'Purok 4, Brgy. Cannery Site, Polomolok', status: 'shipped', current_lat: 6.2305, current_lng: 125.0740, is_demo: true },
        { id: 9992, tracking_id: 'TRK-TEST-POLO2', shop_name: 'Blax Printing Hub', destination_address: 'Crossing Rubber, Brgy. Rubber, Polomolok', status: 'in_transit', current_lat: 6.1950, current_lng: 125.0920, is_demo: true },
        { id: 9993, tracking_id: 'TRK-TEST-POLO3', shop_name: 'Apex Prints', destination_address: 'Purok Pag-asa, Brgy. Glamang, Polomolok', status: 'in_transit', current_lat: 6.1823, current_lng: 125.0456, is_demo: true }
    ] : [];

    let currentPins = (initialPins && initialPins.length > 0) ? initialPins : demoPins;

    const POLO_CENTER = { lat: 6.2136, lng: 125.0661 };
    const POLO_BOUNDS = {
        north: 6.32,
        south: 6.10,
        east: 125.18,
        west: 124.95
    };

    let mapInstance = null;
    let activeMarkers = new Map(); // id -> { marker, infoWindow, pinData }

    function createMotorcycleMarkerElement(status) {
        const isTransit = status === 'in_transit';
        const bgColor = isTransit ? '#7c3aed' : '#2563eb';
        const shadowColor = isTransit ? 'rgba(124, 58, 237, 0.4)' : 'rgba(37, 99, 235, 0.4)';

        const div = document.createElement('div');
        div.style.position = 'relative';
        div.style.width = '36px';
        div.style.height = '36px';
        div.style.display = 'flex';
        div.style.alignItems = 'center';
        div.style.justifyContent = 'center';
        div.style.cursor = 'pointer';

        div.innerHTML = `
            <div style="position:absolute;width:100%;height:100%;border-radius:50%;background:${bgColor};opacity:0.25;animation:pulse 2s infinite;"></div>
            <div style="width:32px;height:32px;border-radius:50%;background:${bgColor};border:2px solid #ffffff;box-shadow:0 3px 10px ${shadowColor};display:flex;align-items:center;justify-content:center;color:#ffffff;z-index:2;">
                <span class="material-symbols-outlined" style="font-size:17px;line-height:1;">two_wheeler</span>
            </div>
        `;
        return div;
    }

    function createInfoWindowContent(p) {
        const isTransit = p.status === 'in_transit';
        const statusLabel = isTransit ? 'In Transit' : 'Shipped';
        const statusBg = isTransit ? '#f3e8ff' : '#eff6ff';
        const statusText = isTransit ? '#7c3aed' : '#1d4ed8';
        const demoTag = p.is_demo ? '<span style="font-size:9px;background:#fef3c7;color:#92400e;padding:1px 5px;border-radius:4px;font-weight:bold;margin-left:4px;">DEMO</span>' : '';
        const custName = [p.first_name, p.last_name].filter(Boolean).join(' ') || 'Customer';

        return `
            <div style="font-family:inherit;min-width:180px;padding:4px;">
                <div style="font-weight:800;color:#0f172a;font-size:13px;display:flex;align-items:center;">
                    #${p.tracking_id || ''} ${demoTag}
                </div>
                <div style="font-size:11px;color:#2563eb;font-weight:700;margin-top:2px;">
                    🏪 ${p.shop_name || 'Shop Partner'}
                </div>
                <div style="font-size:11px;color:#334155;margin-top:2px;">
                    👤 ${custName}
                </div>
                <div style="font-size:11px;color:#64748b;margin-top:1px;">
                    📍 ${p.destination_address || 'Polomolok'}
                </div>
                <div style="margin-top:6px;display:flex;align-items:center;justify-content:between;">
                    <span style="font-size:10px;font-weight:700;text-transform:uppercase;padding:2px 8px;border-radius:999px;background:${statusBg};color:${statusText};">
                        ${statusLabel}
                    </span>
                </div>
            </div>
        `;
    }

    function updatePinsOnMap(pins) {
        if (!mapInstance) return;

        const bounds = new google.maps.LatLngBounds();
        const currentIds = new Set();

        (pins || []).forEach(p => {
            const lat = parseFloat(p.current_lat);
            const lng = parseFloat(p.current_lng);
            if (isNaN(lat) || isNaN(lng)) return;
            if (lat < 6.10 || lat > 6.32 || lng < 124.95 || lng > 125.18) return;

            const pinId = String(p.id || p.tracking_id);
            currentIds.add(pinId);
            const pos = { lat, lng };
            bounds.extend(pos);

            if (activeMarkers.has(pinId)) {
                // Update existing marker position smoothly
                const entry = activeMarkers.get(pinId);
                entry.pinData = p;
                if (entry.marker.position && typeof entry.marker.position.lat === 'function') {
                    entry.marker.setPosition(new google.maps.LatLng(lat, lng));
                } else {
                    entry.marker.position = pos;
                }
                entry.infoWindow.setContent(createInfoWindowContent(p));
            } else {
                // Create new marker
                const pinElem = createMotorcycleMarkerElement(p.status);
                let marker;
                if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
                    marker = new google.maps.marker.AdvancedMarkerElement({
                        map: mapInstance,
                        position: pos,
                        content: pinElem,
                        title: `#${p.tracking_id || ''} - ${p.shop_name || 'Shop'}`
                    });
                } else {
                    marker = new google.maps.Marker({
                        map: mapInstance,
                        position: pos,
                        title: `#${p.tracking_id || ''} - ${p.shop_name || 'Shop'}`
                    });
                }

                const infoWindow = new google.maps.InfoWindow({
                    content: createInfoWindowContent(p)
                });

                marker.addListener('click', () => {
                    infoWindow.open(mapInstance, marker);
                });

                activeMarkers.set(pinId, { marker, infoWindow, pinData: p });
            }
        });

        // Remove markers that are no longer active
        for (const [id, entry] of activeMarkers.entries()) {
            if (!currentIds.has(id)) {
                if (entry.marker.map) entry.marker.map = null;
                if (typeof entry.marker.setMap === 'function') entry.marker.setMap(null);
                activeMarkers.delete(id);
            }
        }
    }

    window.initAdminMap = function() {
        const el = document.getElementById('fleet-map');
        if (!el || typeof google === 'undefined' || !google.maps) return;

        mapInstance = new google.maps.Map(el, {
            center: POLO_CENTER,
            zoom: 12,
            minZoom: 11,
            restriction: {
                latLngBounds: POLO_BOUNDS,
                strictBounds: false
            },
            mapId: 'DEMO_MAP_ID',
            disableDefaultUI: false,
            zoomControl: true,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true
        });

        updatePinsOnMap(currentPins);

        if (activeMarkers.size > 0) {
            const bounds = new google.maps.LatLngBounds();
            activeMarkers.forEach(entry => {
                const p = entry.pinData;
                bounds.extend({ lat: parseFloat(p.current_lat), lng: parseFloat(p.current_lng) });
            });
            mapInstance.fitBounds(bounds, 50);
        }

        // Set initial sync time
        const syncLabel = document.getElementById('lastSyncLabel');
        if (syncLabel) {
            syncLabel.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }

        // Start 10-second auto-refresh polling
        setInterval(refreshFleetPins, 10000);
    };

    function refreshFleetPins() {
        fetch('<?= base_url('admin/tracking/pins') ?>', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.success && Array.isArray(data.pins)) {
                currentPins = (data.pins.length > 0) ? data.pins : (isDev ? demoPins : []);
                updatePinsOnMap(currentPins);

                // Update KPIs
                if (data.kpis) {
                    const totalEl = document.getElementById('kpi-total-active');
                    const transitEl = document.getElementById('kpi-in-transit');
                    const shippedEl = document.getElementById('kpi-shipped');
                    const shopsEl = document.getElementById('kpi-active-shops');
                    if (totalEl) totalEl.textContent = data.kpis.total_active ?? currentPins.length;
                    if (transitEl) transitEl.textContent = data.kpis.in_transit ?? 0;
                    if (shippedEl) shippedEl.textContent = data.kpis.shipped ?? 0;
                    if (shopsEl) shopsEl.textContent = data.kpis.active_shops ?? 0;
                }

                // Update sync time
                const syncLabel = document.getElementById('lastSyncLabel');
                if (syncLabel) {
                    syncLabel.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                }
            }
        })
        .catch(err => {
            console.debug('Fleet auto-refresh skipped:', err);
        });
    }

    // Locate button clicked on shop delivery card
    window.focusDeliveryPin = function(deliveryId) {
        const idStr = String(deliveryId);
        let targetEntry = null;

        for (const [id, entry] of activeMarkers.entries()) {
            if (id === idStr || String(entry.pinData.id) === idStr) {
                targetEntry = entry;
                break;
            }
        }

        if (targetEntry && mapInstance) {
            const lat = parseFloat(targetEntry.pinData.current_lat);
            const lng = parseFloat(targetEntry.pinData.current_lng);
            const pos = { lat, lng };

            mapInstance.panTo(pos);
            mapInstance.setZoom(15);
            targetEntry.infoWindow.open(mapInstance, targetEntry.marker);

            // Scroll up to map if not visible
            const mapEl = document.getElementById('fleet-map');
            if (mapEl) {
                mapEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof google !== 'undefined' && google.maps && !mapInstance) {
            window.initAdminMap();
        }
    });
})();
</script>

<!-- Google Maps Platform JS API -->
<script src="https://maps.googleapis.com/maps/api/js?key=<?= esc(env('GOOGLE_MAPS_API_KEY')) ?>&libraries=marker,geometry&loading=async&callback=initAdminMap" async defer></script>

<?= $this->endSection() ?>
