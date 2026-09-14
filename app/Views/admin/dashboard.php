<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="space-y-xl">

    <!-- Metric Bento Grid — prototype dashboard style, admin-only -->
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-gutter">
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow hover:-translate-y-1 transition-transform duration-200">
            <p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold mb-2">Admin Revenue</p>
            <h3 class="text-headline-md font-bold text-primary">₱<?= number_format($admin_revenue ?? 0, 2) ?></h3>
            <p class="text-xs text-on-surface-variant mt-xs">3% from GCash withdrawals</p>
            <div class="mt-sm h-1 bg-surface-container-high rounded-full overflow-hidden"><div class="h-full bg-primary" style="width: 75%"></div></div>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow hover:-translate-y-1 transition-transform duration-200">
            <p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold mb-2">Active Shops</p>
            <h3 class="text-headline-md font-bold text-on-surface"><?= esc($active_shops ?? $total_shops ?? 0) ?></h3>
            <p class="text-xs text-on-surface-variant mt-xs"><?= esc($pending_shops ?? 0) ?> pending verification</p>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow hover:-translate-y-1 transition-transform duration-200">
            <p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold mb-2">Total Customers</p>
            <h3 class="text-headline-md font-bold text-on-surface"><?= number_format($total_customers ?? 0) ?></h3>
            <p class="text-xs text-on-surface-variant mt-xs">Registered platform users</p>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow hover:-translate-y-1 transition-transform duration-200">
            <p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold mb-2">Pending GCash Requests</p>
            <h3 class="text-headline-md font-bold text-error"><?= esc($pending_payments ?? 0) ?></h3>
            <p class="text-xs text-on-surface-variant mt-xs">GCash withdrawals awaiting approval</p>
        </div>
    </section>

    <!-- Analytics & Alerts grid — prototype dashboard -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-gutter">
        <div class="lg:col-span-2 bg-surface-container-lowest rounded-xl border border-outline-variant/30 soft-shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-title-lg font-bold text-on-surface">Admin Revenue Overview</h3>
                    <p class="text-xs text-on-surface-variant">GCash admin fees (3%) over the last 30 days.</p>
                </div>
                <span class="text-xs font-semibold px-sm py-xs bg-primary-container text-on-primary-container rounded-full">3% Fee</span>
            </div>
            <div id="dashboardChartWrap" class="relative h-64 w-full bg-surface-container-low rounded-xl border border-outline-variant/20 p-4">
                <canvas id="dashboardChart" class="w-full h-full"></canvas>
                <div id="dashboardTooltip" class="hidden absolute z-30 pointer-events-none whitespace-nowrap text-xs font-medium px-3 py-1.5 rounded-lg shadow-xl bg-slate-900/95 text-slate-100 border border-slate-700/60 backdrop-blur-sm transition-[opacity,transform] duration-75"></div>
            </div>
            <div class="flex justify-between text-[10px] text-on-surface-variant mt-sm">
                <span><?= esc(($admin_revenue_chart_labels[0] ?? '') ) ?></span><span><?= esc(end($admin_revenue_chart_labels) ?? 'Today') ?></span>
            </div>
        </div>
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 soft-shadow p-6">
            <h3 class="text-title-lg font-bold text-on-surface mb-4">Platform Alerts</h3>
            <div class="space-y-md">
                <div class="flex gap-md p-sm rounded-lg bg-surface-container-low">
                    <span class="material-symbols-outlined text-error">report</span>
                    <div>
                        <p class="text-label-sm font-semibold">GCash Payout Queue</p>
                        <p class="text-xs text-on-surface-variant"><?= esc($pending_payments ?? 0) ?> pending — review in next 48h.</p>
                    </div>
                </div>
                <div class="flex gap-md p-sm rounded-lg bg-surface-container-low">
                    <span class="material-symbols-outlined text-tertiary">verified_user</span>
                    <div>
                        <p class="text-label-sm font-semibold">Polomolok Boundary Active</p>
                        <p class="text-xs text-on-surface-variant">Orders & printing restricted to Polomolok.</p>
                    </div>
                </div>
                <div class="flex gap-md p-sm rounded-lg bg-surface-container-low">
                    <span class="material-symbols-outlined text-primary">storefront</span>
                    <div>
                        <p class="text-label-sm font-semibold">Shops Handle Deliveries</p>
                        <p class="text-xs text-on-surface-variant">No third-party courier — tenant-managed fleet.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Shop Registrations — no financials -->
    <section class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 soft-shadow overflow-hidden">
        <div class="p-6 border-b border-outline-variant/20 flex justify-between items-center">
            <h3 class="text-title-lg font-bold text-on-surface">Active Tenants & Recent Registrations</h3>
            <a href="<?= base_url('admin/tenants') ?>" class="text-primary text-label-sm font-semibold hover:underline">View All Tenants</a>
        </div>
        <div class="responsive-table">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low/50 text-label-sm text-on-surface-variant/70 uppercase">
                        <th class="py-sm px-md">Merchant Name</th>
                        <th class="py-sm px-md">Primary Contact</th>
                        <th class="py-sm px-md">Plan</th>
                        <th class="py-sm px-md">Status</th>
                        <th class="py-sm px-md">Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recent_shops)): ?>
                        <?php foreach ($recent_shops as $s): ?>
                            <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low">
                                <td class="py-md px-md font-semibold flex items-center gap-sm">
                                    <span class="w-8 h-8 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center text-xs font-bold"><?= esc(mb_strtoupper(mb_substr($s['shop_name'] ?? 'S',0,2))) ?></span>
                                    <?= esc($s['shop_name']) ?>
                                </td>
                                <td class="py-md px-md text-sm"><?= esc(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')) ?><p class="text-xs text-on-surface-variant"><?= esc($s['email'] ?? '') ?></p></td>
                                <td class="py-md px-md text-xs uppercase"><?= esc($s['plan'] ?? 'standard') ?></td>
                                <td class="py-md px-md"><?= status_badge($s['status'] ?? 'active') ?></td>
                                <td class="py-md px-md text-xs text-on-surface-variant"><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="py-lg text-center text-on-surface-variant">No recent shops found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function(){
    const labels = <?= json_encode($admin_revenue_chart_labels ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
    const values = <?= json_encode($admin_revenue_chart_values ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
    const canvas = document.getElementById('dashboardChart');
    if (!canvas || !labels.length) return;
    const ctx = canvas.getContext('2d');
    const wrap = document.getElementById('dashboardChartWrap');
    const tooltip = document.getElementById('dashboardTooltip');
    let hoveredIdx = -1;

    function draw(activeIdx = -1){
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * dpr; canvas.height = rect.height * dpr;
        ctx.setTransform(dpr,0,0,dpr,0,0);
        const w = rect.width, h = rect.height;
        const pad = {t: 20, r: 18, b: 24, l: 44};
        const max = Math.max(...values, 1);
        const stepX = (w - pad.l - pad.r) / Math.max(labels.length - 1, 1);
        ctx.clearRect(0,0,w,h);

        // grid
        ctx.strokeStyle = 'rgba(115,118,134,0.12)'; ctx.lineWidth = 1;
        for(let i = 0; i <= 3; i++){
            const y = pad.t + (h - pad.t - pad.b) * i / 3;
            ctx.beginPath(); ctx.moveTo(pad.l, y); ctx.lineTo(w - pad.r, y); ctx.stroke();
        }

        // area gradient
        ctx.beginPath();
        values.forEach((v, i) => {
            const x = pad.l + i * stepX;
            const y = pad.t + (h - pad.t - pad.b) * (1 - v / max);
            if(i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
        });
        const lastX = pad.l + (values.length - 1) * stepX;
        const lastY = pad.t + (h - pad.t - pad.b) * (1 - values[values.length - 1] / max);
        ctx.lineTo(lastX, h - pad.b); ctx.lineTo(pad.l, h - pad.b); ctx.closePath();
        const grad = ctx.createLinearGradient(0, 0, 0, h);
        grad.addColorStop(0, 'rgba(37,99,235,0.18)');
        grad.addColorStop(1, 'rgba(37,99,235,0)');
        ctx.fillStyle = grad; ctx.fill();

        // line
        ctx.beginPath(); ctx.strokeStyle = '#2563eb'; ctx.lineWidth = 2.2; ctx.lineJoin = 'round';
        values.forEach((v, i) => {
            const x = pad.l + i * stepX;
            const y = pad.t + (h - pad.t - pad.b) * (1 - v / max);
            if(i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
        });
        ctx.stroke();

        // vertical guideline on hover
        if (activeIdx >= 0 && activeIdx < values.length) {
            const ax = pad.l + activeIdx * stepX;
            ctx.beginPath();
            ctx.strokeStyle = 'rgba(37,99,235,0.25)';
            ctx.lineWidth = 1.5;
            ctx.setLineDash([3, 3]);
            ctx.moveTo(ax, pad.t);
            ctx.lineTo(ax, h - pad.b);
            ctx.stroke();
            ctx.setLineDash([]);
        }

        // dots
        values.forEach((v, i) => {
            const x = pad.l + i * stepX;
            const y = pad.t + (h - pad.t - pad.b) * (1 - v / max);
            const isHovered = (i === activeIdx);

            if (isHovered) {
                ctx.beginPath();
                ctx.arc(x, y, 7, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(37, 99, 235, 0.22)';
                ctx.fill();
            }

            ctx.beginPath();
            ctx.arc(x, y, isHovered ? 4.5 : 3, 0, Math.PI * 2);
            ctx.fillStyle = '#2563eb';
            ctx.fill();
            ctx.fillStyle = '#fff';
            ctx.beginPath();
            ctx.arc(x, y, isHovered ? 2.2 : 1.5, 0, Math.PI * 2);
            ctx.fill();
        });

        // y labels
        ctx.fillStyle = '#434655'; ctx.font = '11px Inter, sans-serif'; ctx.textAlign = 'right';
        ctx.fillText('₱' + max.toLocaleString(undefined, {minimumFractionDigits: 0}), pad.l - 6, pad.t + 10);
        ctx.fillText('₱0', pad.l - 6, h - pad.b);

        // x labels
        ctx.textAlign = 'center'; ctx.fillStyle = 'rgba(67,70,85,0.7)';
        [0, Math.floor(labels.length / 2), labels.length - 1].forEach(i => {
            if (labels[i]) ctx.fillText(labels[i], pad.l + i * stepX, h - 6);
        });

        canvas._points = values.map((v, i) => ({
            x: pad.l + i * stepX,
            y: pad.t + (h - pad.t - pad.b) * (1 - v / max),
            v,
            label: labels[i],
            index: i
        }));
    }

    draw();
    window.addEventListener('resize', () => draw(hoveredIdx));

    canvas.addEventListener('mousemove', (e) => {
        if (!canvas._points || !canvas._points.length) return;
        const canvasRect = canvas.getBoundingClientRect();
        const mx = e.clientX - canvasRect.left;
        let best = null, bestDist = 999;
        canvas._points.forEach(p => {
            const d = Math.abs(p.x - mx);
            if (d < bestDist) { bestDist = d; best = p; }
        });

        if (best && bestDist < 28) {
            if (hoveredIdx !== best.index) {
                hoveredIdx = best.index;
                draw(hoveredIdx);
            }
            tooltip.innerHTML = '<span class="text-slate-400 font-normal">' + best.label + '</span> &middot; <span class="font-bold text-emerald-400">₱' + Number(best.v).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span>';
            tooltip.classList.remove('hidden');

            const wrapRect = wrap.getBoundingClientRect();
            const ptXInWrap = (canvasRect.left - wrapRect.left) + best.x;
            const ptYInWrap = (canvasRect.top - wrapRect.top) + best.y;

            const tipW = tooltip.offsetWidth || 130;
            const tipH = tooltip.offsetHeight || 28;

            // Center horizontally on point and clamp within wrap to prevent clipping
            let left = ptXInWrap - (tipW / 2);
            const minLeft = 8;
            const maxLeft = wrapRect.width - tipW - 8;
            left = Math.max(minLeft, Math.min(left, maxLeft));

            // Default position above point; flip below if too close to wrap top
            let top = ptYInWrap - tipH - 12;
            if (top < 8) {
                top = ptYInWrap + 14;
            }
            if (top + tipH > wrapRect.height - 8) {
                top = wrapRect.height - tipH - 8;
            }

            tooltip.style.left = left + 'px';
            tooltip.style.top = top + 'px';
        } else {
            if (hoveredIdx !== -1) {
                hoveredIdx = -1;
                draw(-1);
            }
            tooltip.classList.add('hidden');
        }
    });

    canvas.addEventListener('mouseleave', () => {
        if (hoveredIdx !== -1) {
            hoveredIdx = -1;
            draw(-1);
        }
        tooltip.classList.add('hidden');
    });
})();
</script>
<?= $this->endSection() ?>
