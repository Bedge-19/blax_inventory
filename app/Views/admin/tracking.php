<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="space-y-xl">

    <!-- Header with active badge — prototype -->
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-headline-md font-bold text-on-surface">Live Delivery Tracking</h3>
            <p class="text-body-md text-on-surface-variant">Real-time oversight of all tenant deliveries across Polomolok. Shops handle their own deliveries.</p>
        </div>
        <div class="flex items-center gap-2 bg-surface-container-high px-4 py-2 rounded-lg border border-outline-variant">
            <span class="w-3 h-3 bg-primary rounded-full animate-pulse"></span>
            <span class="text-sm font-medium"><?= esc($active_count ?? count($pins ?? [])) ?> Active Deliveries</span>
        </div>
    </div>

    <!-- Map + Legend — prototype dots, Leaflet with Polomolok bounds -->
    <div class="relative rounded-2xl overflow-hidden border border-outline-variant bg-surface-container shadow-inner" style="min-height: 520px;">
        <div id="fleet-map" class="absolute inset-0 w-full h-full"></div>
        <!-- Legend overlay -->
        <div class="absolute bottom-6 left-6 p-4 bg-surface/90 backdrop-blur-md rounded-xl border border-outline-variant shadow-lg max-w-xs pointer-events-none">
            <h4 class="text-sm font-bold mb-2">Live Fleet Status</h4>
            <div class="space-y-1 text-xs">
                <div class="flex justify-between"><span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#f59e0b]"></span> Ready for Pickup</span></div>
                <div class="flex justify-between"><span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#2563eb]"></span> Shipped</span></div>
                <div class="flex justify-between"><span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#7c3aed]"></span> In Transit</span></div>
            </div>
            <p class="mt-3 pt-2 border-t border-outline-variant text-[10px] text-on-surface-variant italic">* Shops handle own deliveries. Polomolok only.</p>
        </div>
        <!-- Search/Filters overlay -->
        <div class="absolute top-6 left-6 flex gap-2">
            <form method="get" action="<?= base_url('admin/tracking') ?>" class="flex gap-2">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-sm">search</span>
                    <input name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Search delivery ID..." class="w-64 pl-10 pr-4 py-2 rounded-lg bg-surface/90 backdrop-blur-md border border-outline-variant shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm">
                </div>
                <select name="shop" class="px-4 py-2 rounded-lg bg-surface/90 backdrop-blur-md border border-outline-variant shadow-sm text-sm focus:outline-none">
                    <option value="">All Shops</option>
                    <?php foreach (($shops ?? []) as $s): ?>
                        <option value="<?= esc($s['shop_name']) ?>" <?= ($filters['shop']??'')===$s['shop_name']?'selected':'' ?>><?= esc($s['shop_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-4 py-2 rounded-lg bg-primary text-on-primary text-sm font-semibold">Filter</button>
            </form>
        </div>
        <?php if (empty($pins)): ?>
            <div class="absolute bottom-6 right-6 bg-surface/90 backdrop-blur px-3 py-1.5 rounded-full text-xs text-on-surface-variant border border-outline-variant font-medium flex items-center gap-1.5 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                <span>Showing Polomolok Demo Pins (Testing)</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recently Delivered — prototype table -->
    <section class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 soft-shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-outline-variant flex justify-between items-center">
            <h3 class="text-title-lg font-bold text-on-surface">Recently Delivered</h3>
            <p class="text-xs text-on-surface-variant">Polomolok deliveries · Shop-managed fleet</p>
        </div>
        <div class="responsive-table">
            <table class="w-full text-left border-collapse">
                <thead><tr class="bg-surface-container-low text-on-surface-variant text-label-sm">
                    <th class="px-6 py-3 font-semibold">Tracking ID</th><th class="px-6 py-3 font-semibold">Shop</th><th class="px-6 py-3 font-semibold">Destination</th><th class="px-6 py-3 font-semibold">Status</th><th class="px-6 py-3 font-semibold">Updated</th>
                </tr></thead>
                <tbody class="divide-y divide-outline-variant/10">
                    <?php if (!empty($live_deliveries)): foreach ($live_deliveries as $d): ?>
                        <tr class="hover:bg-surface-container/50">
                            <td class="px-6 py-4 text-sm font-mono text-primary">#<?= esc($d['tracking_id'] ?? 'TRK-'.$d['order_id']) ?></td>
                            <td class="px-6 py-4 text-sm"><?= esc($d['shop_name'] ?? 'Shop') ?></td>
                            <td class="px-6 py-4 text-sm text-on-surface-variant"><?= esc($d['shipping_address'] ?? $d['destination_address'] ?? 'Polomolok') ?></td>
                            <td class="px-6 py-4"><?= status_badge($d['delivery_status'] ?? $d['status'] ?? 'in_transit') ?></td>
                            <td class="px-6 py-4 text-sm text-on-surface-variant"><?= date('M d, H:i', strtotime($d['updated_at'] ?? $d['created_at'] ?? 'now')) ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="py-lg text-center text-on-surface-variant">No deliveries match filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (isset($pager)): ?>
            <?php
            $total   = (int) $pager->getTotal('tracking');
            $perPage = 20;
            $cur     = (int) $pager->getCurrentPage('tracking');
            $pages   = (int) $pager->getPageCount('tracking');
            $start   = $total === 0 ? 0 : ($cur - 1) * $perPage + 1;
            $end     = min($cur * $perPage, $total);
            ?>
            <div class="px-6 py-3 bg-surface-container-low/30 flex justify-between items-center border-t border-outline-variant/20 flex-wrap gap-sm">
                <p class="text-xs text-on-surface-variant">Showing <?= number_format($start) ?> to <?= number_format($end) ?> of <?= number_format($total) ?> deliveries</p>
                <?php if ($pages > 1): ?>
                    <div class="flex items-center gap-xs">
                        <a class="p-sm rounded hover:bg-surface-container-high <?= $cur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('tracking') ?>" title="Previous">
                            <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                        </a>
                        <?php
                        $window = [];
                        for ($i = 1; $i <= $pages; $i++) {
                            if ($i === 1 || $i === $pages || abs($i - $cur) <= 2) {
                                $window[] = $i;
                            }
                        }
                        $prev = 0;
                        foreach ($window as $num):
                            if ($num - $prev > 1): ?>
                                <span class="px-xs text-outline text-xs">...</span>
                            <?php endif; ?>
                            <a class="w-8 h-8 rounded flex items-center justify-center text-xs <?= $cur === $num ? 'bg-primary text-on-primary font-semibold' : 'hover:bg-surface-container-high text-on-surface' ?>" href="<?= $pager->getPageURI($num, 'tracking') ?>"><?= $num ?></a>
                        <?php $prev = $num; endforeach; ?>
                        <a class="p-sm rounded hover:bg-surface-container-high <?= $cur >= $pages ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('tracking') ?>" title="Next">
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
    const serverPins = <?= json_encode($pins ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
    const demoPins = [
        { tracking_id: 'TRK-TEST-POLO1', shop_name: 'Blax Printing Hub', destination_address: 'Purok 4, Brgy. Cannery Site, Polomolok', status: 'shipped', current_lat: 6.2305, current_lng: 125.0740, is_demo: true },
        { tracking_id: 'TRK-TEST-POLO2', shop_name: 'Blax Printing Hub', destination_address: 'Crossing Rubber, Brgy. Rubber, Polomolok', status: 'in_transit', current_lat: 6.1950, current_lng: 125.0920, is_demo: true },
        { tracking_id: 'TRK-TEST-POLO3', shop_name: 'Apex Prints', destination_address: 'Purok Pag-asa, Brgy. Glamang, Polomolok', status: 'ready_for_pickup', current_lat: 6.1823, current_lng: 125.0456, is_demo: true },
        { tracking_id: 'TRK-TEST-POLO4', shop_name: 'Blax Printing Hub', destination_address: 'Poblacion, Polomolok', status: 'delivered', current_lat: 6.2185, current_lng: 125.0645, is_demo: true }
    ];

    const pins = (serverPins && serverPins.length > 0) ? serverPins : demoPins;

    function initAdminMap() {
        const el = document.getElementById('fleet-map');
        if (!el || typeof L === 'undefined') return;

        const POLO_CENTER = [6.2136, 125.0661];
        const POLO_BOUNDS = [[6.10, 124.95], [6.32, 125.18]];
        const map = L.map(el, { maxBounds: POLO_BOUNDS, minZoom: 11 }).setView(POLO_CENTER, 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(map);

        L.rectangle(POLO_BOUNDS, {
            color: '#2563eb',
            weight: 1.5,
            fillOpacity: 0.03,
            dashArray: '6 6'
        }).addTo(map).bindTooltip('Polomolok Delivery Scope', { permanent: false });

        const colors = {
            ready_for_pickup: '#f59e0b',
            shipped: '#2563eb',
            in_transit: '#7c3aed',
            delivered: '#10b981'
        };

        const markers = [];
        (pins || []).forEach(p => {
            const lat = parseFloat(p.current_lat), lng = parseFloat(p.current_lng);
            if (isNaN(lat) || isNaN(lng)) return;
            if (lat < 6.10 || lat > 6.32 || lng < 124.95 || lng > 125.18) return;

            const color = colors[p.status] || '#64748b';
            const icon = L.divIcon({
                className: 'fleet-pin',
                html: `<div style="width:16px;height:16px;border-radius:50%;background:${color};border:2.5px solid #fff;box-shadow:0 2px 5px rgba(0,0,0,0.4)"></div>`,
                iconSize: [16, 16],
                iconAnchor: [8, 8]
            });

            const m = L.marker([lat, lng], { icon }).addTo(map);
            const shop = p.shop_name || 'Shop';
            const demoTag = p.is_demo ? '<span style="font-size:9px;background:#fef3c7;color:#92400e;padding:1px 5px;border-radius:4px;font-weight:bold;margin-left:4px;">DEMO PIN</span>' : '';

            m.bindPopup(`
                <div style="font-family:inherit;min-width:160px;">
                    <div style="font-weight:bold;color:#2563eb;font-size:13px;">#${p.tracking_id || ''} ${demoTag}</div>
                    <div style="font-size:11px;color:#0f172a;font-weight:600;margin-top:2px;">🏪 ${shop}</div>
                    <div style="font-size:11px;color:#475569;margin-top:2px;">📍 ${p.destination_address || 'Polomolok'}</div>
                    <div style="margin-top:4px;">
                        <span style="font-size:10px;font-weight:bold;text-transform:uppercase;padding:2px 6px;border-radius:999px;background:#e0f2fe;color:#0369a1;">
                            ${(p.status || '').replace(/_/g, ' ')}
                        </span>
                    </div>
                </div>
            `);
            markers.push(m);
        });

        if (markers.length) {
            map.fitBounds(L.featureGroup(markers).getBounds().pad(0.3));
        } else {
            map.setView(POLO_CENTER, 12);
        }

        setTimeout(() => { if (map) map.invalidateSize(); }, 300);
        setTimeout(() => { if (map) map.invalidateSize(); }, 800);
    }

    function ensureAdminLeafletLoaded() {
        if (typeof L !== 'undefined') {
            initAdminMap();
            return;
        }
        let attempts = 0;
        const interval = setInterval(() => {
            attempts++;
            if (typeof L !== 'undefined') {
                clearInterval(interval);
                initAdminMap();
            } else if (attempts > 60) {
                clearInterval(interval);
                console.warn('Leaflet map library timed out in admin tracking.');
            }
        }, 80);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureAdminLeafletLoaded);
    } else {
        ensureAdminLeafletLoaded();
    }
})();
</script>
<?= $this->endSection() ?>
