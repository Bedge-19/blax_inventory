<?= $this->extend('layouts/tenant') ?>
<?= $this->section('content') ?>

<?php
$ranges = ['7' => 'Last 7 days', '30' => 'Last 30 days', 'year' => 'This year'];
$donutColors = [
    'pending'         => '#737686',
    'processing'      => '#004ac6',
    'shipped'         => '#0074a6',
    'ready_for_pickup' => '#545f73',
    'delivered'       => '#0074a6',
    'completed'       => '#166534',
    'cancelled'       => '#ba1a1a',
];
$segs = [];
$acc  = 0;
foreach ($status_dist as $s) {
    $pct = (float) $s['pct'];
    if ($pct <= 0) {
        continue;
    }
    $col = $donutColors[$s['status']] ?? '#737686';
    $segs[] = $col . ' ' . $acc . '% ' . ($acc + $pct) . '%';
    $acc += $pct;
}
$conic = 'conic-gradient(' . implode(', ', $segs) . ')';

$catColors = ['bg-primary', 'bg-secondary', 'bg-tertiary', 'bg-outline', 'bg-error'];
$chartMax  = max((float) $chart_max, 0.01);
?>

<div class="flex-1 space-y-lg">

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-md">
        <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant/20 shadow-sm">
            <div class="flex items-center gap-sm mb-sm">
                <span class="p-xs bg-primary-container/10 rounded-lg">
                    <span class="material-symbols-outlined text-primary">payments</span>
                </span>
                <h3 class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">Total Revenue</h3>
            </div>
            <p class="text-headline-md font-bold text-on-surface">₱<?= number_format((float) $total_revenue, 2) ?></p>
            <p class="text-label-sm text-on-surface-variant mt-xs">From non-cancelled orders</p>
        </div>

        <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant/20 shadow-sm">
            <div class="flex items-center gap-sm mb-sm">
                <span class="p-xs bg-secondary-container/30 rounded-lg">
                    <span class="material-symbols-outlined text-secondary">shopping_cart</span>
                </span>
                <h3 class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">Avg. Order Value</h3>
            </div>
            <p class="text-headline-md font-bold text-on-surface">₱<?= number_format((float) $avg_order_value, 2) ?></p>
            <p class="text-label-sm text-on-surface-variant mt-xs">Across <?= number_format((int) $avg_order_base) ?> valid orders</p>
        </div>

        <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant/20 shadow-sm">
            <div class="flex items-center gap-sm mb-sm">
                <span class="p-xs bg-tertiary-container/10 rounded-lg">
                    <span class="material-symbols-outlined text-tertiary">category</span>
                </span>
                <h3 class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">Top Selling Category</h3>
            </div>
            <p class="text-headline-md font-bold text-on-surface"><?= esc($top_category) ?></p>
            <p class="text-label-sm text-on-surface-variant mt-xs">By revenue</p>
        </div>
    </div>

    <!-- Revenue Over Time -->
    <div class="glass-card rounded-2xl p-lg shadow-sm border border-outline-variant/30">
        <div class="flex flex-wrap justify-between items-center gap-md mb-md">
            <div>
                <h3 class="text-title-lg font-bold text-on-surface">Revenue Over Time</h3>
                <p class="text-body-md text-on-surface-variant">Gross revenue from delivered orders and completed print requests.</p>
            </div>
            <div class="flex items-center gap-xs bg-surface-container-low p-1 rounded-xl">
                <?php foreach ($ranges as $key => $label): ?>
                    <button type="button" data-range="<?= esc($key) ?>"
                            class="analytics-range-btn px-md py-sm rounded-lg text-label-sm font-semibold transition-all <?= $range === $key ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-high' ?>"><?= esc($label) ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Metric Badges -->
        <div class="flex flex-wrap items-center justify-between gap-md mb-md pb-md border-b border-outline-variant/20">
            <div class="flex items-center gap-lg">
                <div class="flex items-center gap-sm">
                    <span class="w-3.5 h-3.5 rounded-full bg-primary ring-4 ring-primary/20"></span>
                    <div>
                        <span class="text-[11px] text-on-surface-variant font-medium block">Current Period</span>
                        <span id="analytics-current-total" class="text-title-md font-extrabold text-on-surface">₱<?= number_format((float) $chart_total, 2) ?></span>
                    </div>
                </div>
                <div id="analytics-prev-container" class="flex items-center gap-sm <?= (float) $chart_previous_total > 0 ? '' : 'hidden' ?>">
                    <span class="w-3.5 h-3.5 rounded-full bg-outline-variant ring-4 ring-outline-variant/20"></span>
                    <div>
                        <span class="text-[11px] text-on-surface-variant font-medium block">Previous Period</span>
                        <span id="analytics-prev-total" class="text-title-md font-extrabold text-on-surface-variant">₱<?= number_format((float) $chart_previous_total, 2) ?></span>
                    </div>
                </div>
            </div>

            <div class="text-right">
                <?php
                $growthPct = 0;
                if ((float) $chart_previous_total > 0) {
                    $growthPct = round((($chart_total - $chart_previous_total) / $chart_previous_total) * 100, 1);
                }
                ?>
                <span id="analytics-growth-badge" class="inline-flex items-center gap-0.5 text-label-sm font-bold px-2.5 py-1 rounded-full <?= (float) $chart_previous_total > 0 ? ($growthPct >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') : 'hidden' ?>">
                    <span id="analytics-growth-icon" class="material-symbols-outlined text-[16px]"><?= $growthPct >= 0 ? 'trending_up' : 'trending_down' ?></span>
                    <span id="analytics-growth-text"><?= $growthPct >= 0 ? '+' : '' ?><?= $growthPct ?>% vs prev</span>
                </span>
            </div>
        </div>

        <!-- Inline error alert -->
        <div id="analytics-error" class="hidden mb-md p-sm rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center justify-between">
            <div class="flex items-center gap-xs">
                <span class="material-symbols-outlined text-[18px]">error</span>
                <span id="analytics-error-msg">Failed to refresh analytics data.</span>
            </div>
            <button type="button" id="analytics-retry-btn" class="font-bold underline ml-sm hover:text-rose-950">Retry</button>
        </div>

        <!-- Interactive Line Chart Container with Explicit Height -->
        <div id="chart-wrapper" class="relative w-full min-h-[300px] h-80 rounded-xl bg-surface-container-low/20 p-2 overflow-hidden" style="min-height: 300px; height: 320px;">
            <div id="analytics-loading" class="hidden absolute inset-0 rounded-xl bg-surface-container-lowest/60 backdrop-blur-[1px] flex items-center justify-center z-20 transition-opacity duration-150">
                <div class="flex items-center gap-xs px-md py-sm rounded-xl bg-surface-container-lowest shadow-md border border-outline-variant/30 text-primary text-xs font-semibold">
                    <span class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                    <span>Loading analytics data...</span>
                </div>
            </div>
            <canvas id="revenueLineChart" class="w-full h-full block transition-opacity duration-200"></canvas>
            <div id="revenueTooltip" class="hidden absolute z-30 pointer-events-none whitespace-nowrap text-xs font-medium px-3 py-2 rounded-xl shadow-xl bg-slate-900/95 text-slate-100 border border-slate-700/60 backdrop-blur-sm transition-[opacity,transform] duration-75"></div>
        </div>
    </div>

    <!-- Sales by Category & Order Status -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-md">
        <div class="glass-card rounded-xl p-lg shadow-sm">
            <h3 class="text-title-lg font-bold text-on-surface mb-lg">Sales by Category</h3>
            <?php if (!empty($category_sales)): ?>
                <div class="space-y-md">
                    <?php foreach ($category_sales as $i => $c): ?>
                        <div class="space-y-xs">
                            <div class="flex justify-between text-label-sm">
                                <span class="text-on-surface font-medium"><?= esc($c['category']) ?></span>
                                <span class="font-bold"><?= number_format((float) $c['pct'], 1) ?>% &middot; ₱<?= number_format((float) $c['revenue'], 2) ?></span>
                            </div>
                            <div class="h-2 bg-surface-container-highest rounded-full overflow-hidden">
                                <div class="h-full rounded-full <?= $catColors[$i % count($catColors)] ?>" style="width: <?= min(100, max(0, (float) $c['pct'])) ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-body-md text-on-surface-variant">Not enough data yet.</p>
            <?php endif; ?>
        </div>

        <div class="glass-card rounded-xl p-lg shadow-sm">
            <h3 class="text-title-lg font-bold text-on-surface mb-lg">Order Status Distribution</h3>
            <?php if ($status_total > 0): ?>
                <div class="flex items-center gap-xl h-full flex-wrap">
                    <div class="relative w-40 h-40">
                        <div class="w-40 h-40 rounded-full" style="background: <?= $conic ?>"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-28 h-28 rounded-full bg-surface-container-lowest flex flex-col items-center justify-center">
                                <span class="text-title-lg font-bold"><?= number_format((int) $status_total) ?></span>
                                <span class="text-[10px] text-on-surface-variant uppercase font-bold">Orders</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 space-y-md min-w-[200px]">
                        <?php foreach ($status_dist as $s): ?>
                            <?php $col = $donutColors[$s['status']] ?? '#737686'; ?>
                            <div class="flex items-center gap-md">
                                <span class="w-3 h-3 rounded-full" style="background: <?= $col ?>"></span>
                                <div>
                                    <p class="text-label-sm font-bold text-on-surface"><?= esc(humanize_status($s['status'])) ?></p>
                                    <p class="text-[10px] text-on-surface-variant"><?= number_format((int) $s['count']) ?> Orders (<?= number_format((float) $s['pct'], 1) ?>%)</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-body-md text-on-surface-variant">No orders yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Performing Products -->
    <div class="glass-card rounded-xl overflow-hidden shadow-sm">
        <div class="p-lg border-b border-outline-variant/30 flex items-center justify-between">
            <h3 class="text-title-lg font-bold text-on-surface">Top Performing Products</h3>
            <span class="text-label-sm text-on-surface-variant">By revenue, non-cancelled orders</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-60 uppercase tracking-wider">Product Name</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-60 uppercase tracking-wider text-right">Units Sold</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-60 uppercase tracking-wider text-right">Revenue Generated</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-60 uppercase tracking-wider">Stock Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php if (!empty($top_products)): ?>
                        <?php foreach ($top_products as $tp): ?>
                            <?php
                            $stockKey = null;
                            if (isset($tp['stock_quantity']) && isset($tp['low_stock_threshold']) && $tp['product_status'] !== null) {
                                $qty = (int) $tp['stock_quantity'];
                                $thr = (int) $tp['low_stock_threshold'];
                                $stockKey = $qty <= 0 ? 'out_of_stock' : ($qty <= $thr ? 'low_stock' : 'in_stock');
                            }
                            ?>
                            <tr class="hover:bg-surface-container-high/50 transition-colors">
                                <td class="px-lg py-md">
                                    <div class="flex items-center gap-md">
                                        <div class="w-10 h-10 rounded-lg bg-surface-container flex items-center justify-center text-primary">
                                            <span class="material-symbols-outlined">inventory_2</span>
                                        </div>
                                        <span class="text-body-md font-medium text-on-surface"><?= esc($tp['name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-lg py-md text-body-md text-on-surface text-right"><?= number_format((int) $tp['units']) ?></td>
                                <td class="px-lg py-md text-body-md font-bold text-on-surface text-right">₱<?= number_format((float) $tp['revenue'], 2) ?></td>
                                <td class="px-lg py-md">
                                    <?php if ($stockKey !== null): ?>
                                        <?= status_badge($stockKey) ?>
                                    <?php else: ?>
                                        <span class="text-label-sm text-on-surface-variant">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="py-lg text-center text-on-surface-variant">Not enough data yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Chart.js CDN with fail-safe fallback -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    // Initial datasets from server
    let labels = <?= json_encode($chart_labels ?? []) ?>;
    let values = <?= json_encode(array_map('floatval', $chart_values ?? [])) ?>;
    let previousValues = <?= json_encode(array_map('floatval', $chart_previous ?? [])) ?>;

    const endpoint = '<?= site_url('tenant/analytics/data') ?>';
    const canvas = document.getElementById('revenueLineChart');
    const tooltip = document.getElementById('revenueTooltip');
    const loadingEl = document.getElementById('analytics-loading');
    const errorContainer = document.getElementById('analytics-error');
    const errorMsg = document.getElementById('analytics-error-msg');
    const retryBtn = document.getElementById('analytics-retry-btn');
    const currentTotalEl = document.getElementById('analytics-current-total');
    const prevContainer = document.getElementById('analytics-prev-container');
    const prevTotalEl = document.getElementById('analytics-prev-total');
    const growthBadge = document.getElementById('analytics-growth-badge');
    const growthIcon = document.getElementById('analytics-growth-icon');
    const growthText = document.getElementById('analytics-growth-text');

    let activeBtn = document.querySelector('.analytics-range-btn.bg-primary') || document.querySelector('.analytics-range-btn');
    let chartInstance = null;
    let points = [];

    function formatCurrency(v) {
        return '₱' + Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function compactMoney(v) {
        if (v >= 1000) return '₱' + (v / 1000).toFixed(1) + 'k';
        return '₱' + Math.round(v);
    }

    // --- High-performance Native Canvas Renderer (Zero-CDN Resilience) ---
    function renderNativeCanvas() {
        if (!canvas) return;
        const rect = canvas.getBoundingClientRect();
        if (rect.width <= 0 || rect.height <= 0) return;

        const dpr = window.devicePixelRatio || 1;
        canvas.width = Math.round(rect.width * dpr);
        canvas.height = Math.round(rect.height * dpr);

        const ctx = canvas.getContext('2d');
        ctx.resetTransform();
        ctx.scale(dpr, dpr);

        const W = rect.width;
        const H = rect.height;
        ctx.clearRect(0, 0, W, H);

        const padLeft = 52;
        const padRight = 20;
        const padTop = 24;
        const padBottom = 34;
        const plotW = W - padLeft - padRight;
        const plotH = H - padTop - padBottom;

        const maxVal = Math.max.apply(null, values.concat(previousValues).concat([1]));
        const yTop = maxVal * 1.15;

        // Draw horizontal grid lines & Y labels
        ctx.font = '11px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        ctx.fillStyle = '#94a3b8';
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';

        const steps = 4;
        for (let i = 0; i <= steps; i++) {
            const y = padTop + (plotH / steps) * i;
            const val = yTop - (yTop / steps) * i;
            ctx.strokeStyle = 'rgba(226, 232, 240, 0.6)';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(padLeft, y);
            ctx.lineTo(W - padRight, y);
            ctx.stroke();
            ctx.fillText(compactMoney(val), padLeft - 8, y);
        }

        if (!values.length) return;

        // Compute point coordinates
        points = [];
        const n = values.length;
        for (let i = 0; i < n; i++) {
            const x = padLeft + (n > 1 ? (plotW / (n - 1)) * i : plotW / 2);
            const y = padTop + plotH - (values[i] / yTop) * plotH;
            points.push({ x: x, y: y, val: values[i], label: labels[i] || '', prevVal: previousValues[i] || 0 });
        }

        // Previous period line (dashed grey)
        if (previousValues && previousValues.length && previousValues.some(v => v > 0)) {
            ctx.save();
            ctx.strokeStyle = '#94a3b8';
            ctx.lineWidth = 2;
            ctx.setLineDash([4, 4]);
            ctx.beginPath();
            for (let i = 0; i < previousValues.length; i++) {
                const px = padLeft + (previousValues.length > 1 ? (plotW / (previousValues.length - 1)) * i : plotW / 2);
                const py = padTop + plotH - (previousValues[i] / yTop) * plotH;
                if (i === 0) ctx.moveTo(px, py); else ctx.lineTo(px, py);
            }
            ctx.stroke();
            ctx.restore();
        }

        // Gradient area under current curve
        const grad = ctx.createLinearGradient(0, padTop, 0, padTop + plotH);
        grad.addColorStop(0, 'rgba(37, 99, 235, 0.22)');
        grad.addColorStop(1, 'rgba(37, 99, 235, 0.00)');

        ctx.beginPath();
        ctx.moveTo(points[0].x, padTop + plotH);
        ctx.lineTo(points[0].x, points[0].y);
        for (let i = 1; i < points.length; i++) {
            const prev = points[i - 1];
            const curr = points[i];
            const cpx = (prev.x + curr.x) / 2;
            ctx.bezierCurveTo(cpx, prev.y, cpx, curr.y, curr.x, curr.y);
        }
        ctx.lineTo(points[points.length - 1].x, padTop + plotH);
        ctx.closePath();
        ctx.fillStyle = grad;
        ctx.fill();

        // Current revenue curve (solid blue)
        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        for (let i = 1; i < points.length; i++) {
            const prev = points[i - 1];
            const curr = points[i];
            const cpx = (prev.x + curr.x) / 2;
            ctx.bezierCurveTo(cpx, prev.y, cpx, curr.y, curr.x, curr.y);
        }
        ctx.strokeStyle = '#2563eb';
        ctx.lineWidth = 3;
        ctx.stroke();

        // Points
        const ptRadius = points.length > 15 ? 3 : 5;
        points.forEach(pt => {
            ctx.beginPath();
            ctx.arc(pt.x, pt.y, ptRadius, 0, Math.PI * 2);
            ctx.fillStyle = '#ffffff';
            ctx.fill();
            ctx.lineWidth = 2;
            ctx.strokeStyle = '#2563eb';
            ctx.stroke();
        });

        // X-axis date labels
        ctx.fillStyle = '#64748b';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        const step = Math.max(1, Math.ceil(points.length / 8));
        for (let i = 0; i < points.length; i += step) {
            ctx.fillText(points[i].label, points[i].x, padTop + plotH + 8);
        }
    }

    // Chart.js initialization when library is present
    function buildChartJsDatasets(curVals, prevVals) {
        const chartCtx = canvas.getContext('2d');
        const primaryGradient = chartCtx.createLinearGradient(0, 0, 0, 260);
        primaryGradient.addColorStop(0, 'rgba(37, 99, 235, 0.25)');
        primaryGradient.addColorStop(1, 'rgba(37, 99, 235, 0.00)');

        const ds = [{
            label: 'Current Revenue',
            data: curVals,
            borderColor: '#2563eb',
            backgroundColor: primaryGradient,
            fill: true,
            tension: 0.38,
            borderWidth: 3,
            pointRadius: curVals.length > 15 ? 3 : 5,
            pointHoverRadius: 7,
            pointBackgroundColor: '#2563eb',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
        }];

        if (prevVals && prevVals.length > 0 && prevVals.some(v => v > 0)) {
            ds.push({
                label: 'Previous Period',
                data: prevVals,
                borderColor: '#94a3b8',
                backgroundColor: 'transparent',
                borderDash: [5, 5],
                tension: 0.38,
                borderWidth: 2,
                pointRadius: prevVals.length > 15 ? 2 : 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#94a3b8',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 1.5,
            });
        }
        return ds;
    }

    function initOrUpdateChart() {
        if (typeof Chart !== 'undefined') {
            try {
                if (chartInstance) {
                    chartInstance.data.labels = labels;
                    chartInstance.data.datasets = buildChartJsDatasets(values, previousValues);
                    chartInstance.update();
                    return;
                }
                chartInstance = new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: buildChartJsDatasets(values, previousValues)
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleColor: '#f8fafc',
                                bodyColor: '#f8fafc',
                                padding: 12,
                                cornerRadius: 10,
                                callbacks: {
                                    label: ctx => ' ' + ctx.dataset.label + ': ' + formatCurrency(ctx.parsed.y)
                                }
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 11 }, autoSkip: true, maxTicksLimit: 10 } },
                            y: {
                                beginAtZero: true,
                                grace: '12%',
                                grid: { color: 'rgba(226, 232, 240, 0.6)' },
                                ticks: {
                                    color: '#64748b',
                                    font: { size: 11 },
                                    callback: v => compactMoney(v)
                                }
                            }
                        }
                    }
                });
                return;
            } catch (e) {
                console.warn('Chart.js init fallback to Canvas:', e);
            }
        }
        renderNativeCanvas();
    }

    // Hover tooltip for native canvas
    if (canvas && tooltip) {
        canvas.addEventListener('mousemove', function (e) {
            if (chartInstance || !points.length) return;
            const rect = canvas.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            let closest = null;
            let minDist = Infinity;
            for (let i = 0; i < points.length; i++) {
                const dist = Math.abs(points[i].x - mouseX);
                if (dist < minDist) {
                    minDist = dist;
                    closest = points[i];
                }
            }

            if (closest && minDist < 35) {
                tooltip.innerHTML = `
                    <div class="font-bold text-[11px] text-slate-300 mb-0.5">${closest.label}</div>
                    <div class="text-blue-400 font-extrabold text-sm">${formatCurrency(closest.val)}</div>
                    ${closest.prevVal > 0 ? `<div class="text-[10px] text-slate-400 mt-0.5">Prev: ${formatCurrency(closest.prevVal)}</div>` : ''}
                `;
                tooltip.classList.remove('hidden');
                const tw = tooltip.offsetWidth || 130;
                let tx = closest.x - tw / 2;
                if (tx < 10) tx = 10;
                if (tx + tw > rect.width - 10) tx = rect.width - tw - 10;
                let ty = closest.y - 56;
                if (ty < 8) ty = closest.y + 12;
                tooltip.style.transform = `translate(${tx}px, ${ty}px)`;
            } else {
                tooltip.classList.add('hidden');
            }
        });

        canvas.addEventListener('mouseleave', function () {
            if (!chartInstance) tooltip.classList.add('hidden');
        });
    }

    // Interactive AJAX Range Switcher (Always attached immediately)
    async function switchAnalyticsRange(btn) {
        if (!btn) return;
        activeBtn = btn;
        const rangeVal = btn.dataset.range || '30';

        document.querySelectorAll('.analytics-range-btn').forEach(function (b) {
            b.classList.remove('bg-primary', 'text-on-primary', 'shadow-sm');
            b.classList.add('text-on-surface-variant');
        });
        btn.classList.add('bg-primary', 'text-on-primary', 'shadow-sm');
        btn.classList.remove('text-on-surface-variant');

        if (errorContainer) errorContainer.classList.add('hidden');
        if (loadingEl) loadingEl.classList.remove('hidden');
        if (canvas) canvas.classList.add('opacity-40', 'pointer-events-none');

        try {
            const sep = endpoint.indexOf('?') >= 0 ? '&' : '?';
            const url = `${endpoint}${sep}range=${encodeURIComponent(rangeVal)}&period=${encodeURIComponent(rangeVal)}&_t=${Date.now()}`;
            const res = await fetch(url, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();

            if (!data || !data.success) {
                throw new Error((data && data.error) ? data.error : 'Invalid response');
            }

            labels = data.labels || [];
            values = (data.values || []).map(Number);
            previousValues = (data.previous_values || []).map(Number);

            // Update badge totals
            if (currentTotalEl) {
                currentTotalEl.textContent = formatCurrency(data.total);
            }

            const prevTotal = Number(data.previous_total) || 0;
            if (prevTotal > 0) {
                if (prevTotalEl) prevTotalEl.textContent = formatCurrency(prevTotal);
                if (prevContainer) prevContainer.classList.remove('hidden');

                const growth = Number(data.growth_pct) || 0;
                if (growthBadge) {
                    growthBadge.className = 'inline-flex items-center gap-0.5 text-label-sm font-bold px-2.5 py-1 rounded-full ' + (growth >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800');
                    if (growthIcon) growthIcon.textContent = growth >= 0 ? 'trending_up' : 'trending_down';
                    if (growthText) growthText.textContent = (growth >= 0 ? '+' : '') + growth + '% vs prev';
                    growthBadge.classList.remove('hidden');
                }
            } else {
                if (prevContainer) prevContainer.classList.add('hidden');
                if (growthBadge) growthBadge.classList.add('hidden');
            }

            // Redraw chart
            initOrUpdateChart();

        } catch (err) {
            console.error('Failed to switch analytics range:', err);
            if (errorContainer) {
                if (errorMsg) errorMsg.textContent = 'Unable to refresh analytics chart (' + (err.message || 'network error') + ').';
                errorContainer.classList.remove('hidden');
            }
        } finally {
            if (loadingEl) loadingEl.classList.add('hidden');
            if (canvas) canvas.classList.remove('opacity-40', 'pointer-events-none');
        }
    }

    // Connect button click handlers
    document.querySelectorAll('.analytics-range-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            switchAnalyticsRange(btn);
        });
    });

    if (retryBtn) {
        retryBtn.addEventListener('click', function () {
            if (activeBtn) switchAnalyticsRange(activeBtn);
        });
    }

    window.addEventListener('resize', function () {
        if (!chartInstance) renderNativeCanvas();
    });

    // Auto-update when sale is made in another window
    window.addEventListener('storage', function (e) {
        if (e.key === 'blax_last_sale' && e.newValue) {
            if (activeBtn) switchAnalyticsRange(activeBtn);
        }
    });

    // Initial render
    setTimeout(initOrUpdateChart, 50);
})();
</script>
<?= $this->endSection() ?>