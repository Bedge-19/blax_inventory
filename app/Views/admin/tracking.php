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
            <div class="absolute bottom-6 right-6 bg-surface/90 backdrop-blur px-sm py-xs rounded-full text-xs text-on-surface-variant border border-outline-variant">No active deliveries in transit</div>
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
    </section>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function(){
    const pins = <?= json_encode($pins ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
    const el = document.getElementById('fleet-map');
    if(!el || typeof L==='undefined') return;
    const POLO_CENTER=[6.2136,125.0661];
    const POLO_BOUNDS=[[6.10,124.95],[6.32,125.18]];
    const map = L.map(el, {maxBounds: POLO_BOUNDS, minZoom: 11}).setView(POLO_CENTER,12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap', maxZoom:18}).addTo(map);
    L.rectangle(POLO_BOUNDS,{color:'#2563eb', weight:1, fillOpacity:0.03, dashArray:'6 6'}).addTo(map).bindTooltip('Polomolok boundary', {permanent:false});
    const colors={ready_for_pickup:'#f59e0b', shipped:'#2563eb', in_transit:'#7c3aed', delivered:'#10b981'};
    const markers=[];
    (pins||[]).forEach(p=>{
        const lat=parseFloat(p.current_lat), lng=parseFloat(p.current_lng);
        if(isNaN(lat)||isNaN(lng)) return;
        // enforce Polomolok bounds
        if(lat < 6.10 || lat > 6.32 || lng < 124.95 || lng > 125.18) return;
        const color=colors[p.status]||'#64748b';
        const icon=L.divIcon({className:'fleet-pin', html:'<div style="width:14px;height:14px;border-radius:50%;background:'+color+';border:3px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)"></div>', iconSize:[14,14], iconAnchor:[7,7]});
        const m=L.marker([lat,lng],{icon}).addTo(map);
        const shop=p.shop_name||'Shop';
        m.bindPopup('<strong>#'+(p.tracking_id||'')+'</strong><br>'+shop+'<br>'+(p.destination_address||'Polomolok')+'<br>'+(p.status||'').replace(/_/g,' '));
        markers.push(m);
    });
    if(markers.length){ map.fitBounds(L.featureGroup(markers).getBounds().pad(0.3)); } else { map.setView(POLO_CENTER,12); }
})();
</script>
<?= $this->endSection() ?>
