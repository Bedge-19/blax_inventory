<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="space-y-xl">

    <!-- Summary Metrics — admin-only, no shop revenue -->
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-gutter">
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow hover:-translate-y-1 transition-transform duration-200">
            <p class="text-label-sm text-on-surface-variant uppercase tracking-wider mb-2">Admin Revenue</p>
            <h3 class="text-headline-md font-bold text-primary">₱<?= number_format($admin_revenue ?? 0, 2) ?></h3>
            <p class="text-xs text-on-surface-variant mt-xs">3% fee on completed GCash withdrawals</p>
            <span class="inline-flex items-center gap-xs text-emerald-600 text-xs mt-sm"><span class="material-symbols-outlined text-sm">trending_up</span> GCash only</span>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow hover:-translate-y-1 transition-transform duration-200">
            <p class="text-label-sm text-on-surface-variant uppercase tracking-wider mb-2">Completed Payouts</p>
            <h3 class="text-headline-md font-bold text-on-surface"><?= number_format($payout_count ?? 0) ?></h3>
            <p class="text-xs text-on-surface-variant mt-xs">GCash withdrawals approved</p>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow hover:-translate-y-1 transition-transform duration-200">
            <p class="text-label-sm text-on-surface-variant uppercase tracking-wider mb-2">Average Fee</p>
            <h3 class="text-headline-md font-bold text-tertiary">₱<?= number_format($avg_fee ?? 0, 2) ?></h3>
            <p class="text-xs text-on-surface-variant mt-xs">Per completed withdrawal</p>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow hover:-translate-y-1 transition-transform duration-200">
            <p class="text-label-sm text-on-surface-variant uppercase tracking-wider mb-2">Total Orders</p>
            <h3 class="text-headline-md font-bold text-on-surface"><?= number_format($total_orders ?? 0) ?></h3>
            <p class="text-xs text-on-surface-variant mt-xs"><?= esc($active_shops ?? 0) ?> active shops · <?= number_format($total_customers ?? 0) ?> customers</p>
        </div>
    </section>

    <!-- Charts -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-gutter">
        <div class="lg:col-span-2 bg-surface-container-lowest rounded-xl border border-outline-variant/30 soft-shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-title-lg font-bold text-on-surface">Admin Revenue Growth</h3>
                <div class="flex gap-sm">
                    <button data-range="7" class="rangeBtn px-sm py-xs rounded-full text-xs font-semibold border <?= ($range??'30')==='7'?'bg-primary text-on-primary border-primary':'border-outline-variant text-on-surface-variant' ?>">7 Days</button>
                    <button data-range="30" class="rangeBtn px-sm py-xs rounded-full text-xs font-semibold border <?= ($range??'30')==='30'?'bg-primary text-on-primary border-primary':'border-outline-variant text-on-surface-variant' ?>">30 Days</button>
                    <button data-range="year" class="rangeBtn px-sm py-xs rounded-full text-xs font-semibold border <?= ($range??'30')==='year'?'bg-primary text-on-primary border-primary':'border-outline-variant text-on-surface-variant' ?>">Year</button>
                </div>
            </div>
            <div id="analyticsChartWrap" class="relative h-80 w-full bg-surface-container-low rounded-xl border border-outline-variant/20 overflow-hidden p-4">
                <canvas id="analyticsChart" class="w-full h-full"></canvas>
                <div id="analyticsTooltip" class="hidden absolute bg-inverse-surface text-inverse-on-surface text-xs px-sm py-xs rounded-lg shadow-lg pointer-events-none whitespace-nowrap"></div>
            </div>
            <p class="text-[11px] text-on-surface-variant mt-sm">Hover any point to see ₱ value. Source: <span class="font-semibold">payout_requests.fee</span> where status=completed and destination_method=gcash.</p>
        </div>
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 soft-shadow p-6">
            <h3 class="text-title-lg font-bold text-on-surface mb-4">Marketplace Distribution</h3>
            <p class="text-xs text-on-surface-variant mb-md">Share by shop count (prototype placeholder — no financial distribution exposed).</p>
            <div class="space-y-4">
                <?php
                $totalShopsDist = max(1, (int)($active_shops ?? 1));
                $dist = [
                    ['label'=>'Polomolok Shops','pct'=> min(100, round($totalShopsDist/ $totalShopsDist*42)), 'color'=>'bg-primary'],
                    ['label'=>'Printing Partners','pct'=>28,'color'=>'bg-tertiary'],
                    ['label'=>'Standard Plan','pct'=>15,'color'=>'bg-secondary'],
                    ['label'=>'Enterprise','pct'=>10,'color'=>'bg-primary-fixed-dim'],
                    ['label'=>'Others','pct'=>5,'color'=>'bg-outline'],
                ];
                foreach ($dist as $d): ?>
                    <div><div class="flex justify-between text-sm mb-1"><span class="font-medium text-on-surface"><?= esc($d['label']) ?></span><span class="text-on-surface-variant"><?= esc($d['pct']) ?>%</span></div><div class="w-full bg-surface-container-high rounded-full h-2"><div class="<?= esc($d['color']) ?> h-2 rounded-full" style="width:<?= esc($d['pct']) ?>%"></div></div></div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Tables & Map — prototype style, confidential-safe -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-gutter">
        <div class="lg:col-span-2 bg-surface-container-lowest rounded-xl border border-outline-variant/30 soft-shadow overflow-hidden">
            <div class="p-6 border-b border-outline-variant/20 flex justify-between items-center">
                <h3 class="text-title-lg font-bold text-on-surface">Shops by Order Volume</h3>
                <span class="text-xs text-on-surface-variant">No revenue — volume only</span>
            </div>
            <div class="responsive-table">
                <table class="w-full text-left border-collapse">
                    <thead><tr class="bg-surface-container-low/50"><th class="p-4 text-label-sm text-on-surface-variant/70 uppercase">Shop Name</th><th class="p-4 text-label-sm text-on-surface-variant/70 uppercase">Orders</th><th class="p-4 text-label-sm text-on-surface-variant/70 uppercase">Plan</th></tr></thead>
                    <tbody class="text-sm">
                        <?php if (!empty($top_shops)): foreach ($top_shops as $s): ?>
                            <tr class="border-b border-outline-variant/10 hover:bg-surface-container-lowest"><td class="p-4 font-medium text-on-surface"><?= esc($s['shop_name']) ?></td><td class="p-4 font-bold text-primary"><?= esc($s['total_orders']) ?></td><td class="p-4 text-xs uppercase text-on-surface-variant"><?= esc($s['plan'] ?? 'standard') ?></td></tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="3" class="p-6 text-center text-on-surface-variant">No orders yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 soft-shadow p-6 flex flex-col">
            <h3 class="text-title-lg font-bold text-on-surface mb-4">Top Areas (Polomolok)</h3>
            <div class="flex-1 rounded-lg overflow-hidden relative min-h-[220px] border border-outline-variant/20 bg-surface-container-low flex items-center justify-center">
                <div class="absolute inset-0 opacity-20 pointer-events-none" style="background-image: radial-gradient(circle at 2px 2px, rgba(0,0,0,0.12) 1px, transparent 0); background-size: 28px 28px;"></div>
                <div class="relative text-center p-4">
                    <span class="material-symbols-outlined text-4xl text-primary mb-2">distance</span>
                    <p class="text-label-sm font-bold">Polomolok</p>
                    <p class="text-xs text-on-surface-variant">6.2136, 125.0661 · All deliveries restricted here</p>
                    <div class="mt-md text-left bg-surface-container-lowest/90 backdrop-blur p-sm rounded-lg border border-outline-variant/30 text-xs">
                        <div class="flex justify-between"><span class="font-semibold">Poblacion</span><span class="text-primary font-bold">High</span></div>
                        <div class="flex justify-between"><span class="font-semibold">Silway</span><span class="text-primary font-bold">Medium</span></div>
                        <div class="flex justify-between"><span class="font-semibold">Cannery Site</span><span class="text-primary font-bold">Medium</span></div>
                    </div>
                </div>
            </div>
            <p class="text-[10px] text-on-surface-variant italic mt-sm">* Prototype geographic placeholder — sourced from Polomolok system data.</p>
        </div>
    </section>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function(){
    let labels = <?= json_encode($chart_labels ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
    let values = <?= json_encode($chart_values ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
    const canvas=document.getElementById('analyticsChart'), wrap=document.getElementById('analyticsChartWrap'), tooltip=document.getElementById('analyticsTooltip');
    if(!canvas) return;
    const ctx=canvas.getContext('2d');
    function draw(){
        const dpr=window.devicePixelRatio||1;
        const rect=canvas.getBoundingClientRect();
        canvas.width=rect.width*dpr; canvas.height=rect.height*dpr; ctx.setTransform(dpr,0,0,dpr,0,0);
        const w=rect.width,h=rect.height, pad={t:16,r:16,b:28,l:48};
        const max=Math.max(...values,1);
        const stepX=(w-pad.l-pad.r)/Math.max(labels.length-1,1);
        ctx.clearRect(0,0,w,h);
        // grid
        ctx.strokeStyle='rgba(115,118,134,0.12)'; ctx.lineWidth=1;
        for(let i=0;i<=4;i++){ const y=pad.t+(h-pad.t-pad.b)*i/4; ctx.beginPath(); ctx.moveTo(pad.l,y); ctx.lineTo(w-pad.r,y); ctx.stroke(); }
        // area
        ctx.beginPath();
        values.forEach((v,i)=>{ const x=pad.l+i*stepX, y=pad.t + (h-pad.t-pad.b)*(1 - v/max); if(i===0) ctx.moveTo(x,y); else ctx.lineTo(x,y); });
        const lastX=pad.l+(values.length-1)*stepX, lastY=pad.t + (h-pad.t-pad.b)*(1 - (values[values.length-1]||0)/max);
        ctx.lineTo(lastX,h-pad.b); ctx.lineTo(pad.l,h-pad.b); ctx.closePath();
        const grad=ctx.createLinearGradient(0,0,0,h); grad.addColorStop(0,'rgba(37,99,235,0.18)'); grad.addColorStop(1,'rgba(37,99,235,0)'); ctx.fillStyle=grad; ctx.fill();
        // line
        ctx.beginPath(); ctx.strokeStyle='#2563eb'; ctx.lineWidth=2.5; ctx.lineJoin='round';
        values.forEach((v,i)=>{ const x=pad.l+i*stepX, y=pad.t + (h-pad.t-pad.b)*(1 - v/max); if(i===0) ctx.moveTo(x,y); else ctx.lineTo(x,y); }); ctx.stroke();
        // dots
        values.forEach((v,i)=>{ const x=pad.l+i*stepX, y=pad.t + (h-pad.t-pad.b)*(1 - v/max); ctx.beginPath(); ctx.arc(x,y,3.5,0,Math.PI*2); ctx.fillStyle='#2563eb'; ctx.fill(); ctx.beginPath(); ctx.arc(x,y,1.7,0,Math.PI*2); ctx.fillStyle='#fff'; ctx.fill(); });
        ctx.fillStyle='#434655'; ctx.font='11px Inter'; ctx.textAlign='right';
        ctx.fillText('₱'+max.toLocaleString(undefined,{minimumFractionDigits:0}), pad.l-8, pad.t+10);
        ctx.fillText('₱0', pad.l-8, h-pad.b);
        ctx.textAlign='center'; ctx.fillStyle='rgba(67,70,85,0.75)';
        const idxs=[0, Math.floor(labels.length/2), labels.length-1];
        idxs.forEach(i=>{ if(labels[i]) ctx.fillText(labels[i], pad.l+i*stepX, h-8); });
        canvas._pts = values.map((v,i)=>({x:pad.l+i*stepX, y:pad.t + (h-pad.t-pad.b)*(1 - v/max), v, label:labels[i]}));
    }
    draw(); window.addEventListener('resize', draw);
    canvas.addEventListener('mousemove', e=>{
        const rect=canvas.getBoundingClientRect(); const mx=e.clientX-rect.left;
        let best=null,bd=999; (canvas._pts||[]).forEach(p=>{const d=Math.abs(p.x-mx); if(d<bd){bd=d; best=p;}});
        if(best && bd < 24){ tooltip.classList.remove('hidden'); tooltip.textContent=best.label+': ₱'+Number(best.v).toLocaleString(undefined,{minimumFractionDigits:2}); tooltip.style.left=(best.x+10)+'px'; tooltip.style.top=(best.y-36)+'px'; } else tooltip.classList.add('hidden');
    });
    canvas.addEventListener('mouseleave', ()=> tooltip.classList.add('hidden'));
    // range switching via AJAX
    document.querySelectorAll('.rangeBtn').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            const range=btn.dataset.range;
            fetch('<?= base_url('admin/analytics/data') ?>?range='+range, {headers:{'X-Requested-With':'XMLHttpRequest'}})
                .then(r=>r.json()).then(data=>{
                    if(data.success){ labels=data.labels; values=data.values; draw();
                        document.querySelectorAll('.rangeBtn').forEach(b=>{ b.className='rangeBtn px-sm py-xs rounded-full text-xs font-semibold border '+(b.dataset.range===range?'bg-primary text-on-primary border-primary':'border-outline-variant text-on-surface-variant'); });
                    }
                });
        });
    });
})();
</script>
<?= $this->endSection() ?>
