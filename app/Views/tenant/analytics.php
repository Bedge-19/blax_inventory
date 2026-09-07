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
                    <a href="<?= base_url('tenant/analytics?range=' . $key) ?>"
                       class="px-md py-sm rounded-lg text-label-sm font-semibold transition-all <?= $range === $key ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-high' ?>"><?= esc($label) ?></a>
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
                        <span class="text-title-md font-extrabold text-on-surface">₱<?= number_format((float) $chart_total, 2) ?></span>
                    </div>
                </div>
                <?php if ((float) $chart_previous_total > 0): ?>
                    <div class="flex items-center gap-sm">
                        <span class="w-3.5 h-3.5 rounded-full bg-outline-variant ring-4 ring-outline-variant/20"></span>
                        <div>
                            <span class="text-[11px] text-on-surface-variant font-medium block">Previous Period</span>
                            <span class="text-title-md font-extrabold text-on-surface-variant">₱<?= number_format((float) $chart_previous_total, 2) ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="text-right">
                <?php
                $growthPct = 0;
                if ((float) $chart_previous_total > 0) {
                    $growthPct = round((($chart_total - $chart_previous_total) / $chart_previous_total) * 100, 1);
                }
                ?>
                <?php if ($growthPct !== 0 && (float) $chart_previous_total > 0): ?>
                    <span class="inline-flex items-center gap-0.5 text-label-sm font-bold px-2.5 py-1 rounded-full <?= $growthPct >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                        <span class="material-symbols-outlined text-[16px]"><?= $growthPct >= 0 ? 'trending_up' : 'trending_down' ?></span>
                        <?= $growthPct >= 0 ? '+' : '' ?><?= $growthPct ?>% vs prev
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Interactive Line Chart Canvas -->
        <div class="relative w-full h-64 sm:h-72">
            <canvas id="revenueLineChart"></canvas>
        </div>
    </div>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('revenueLineChart');
            if (!ctx) return;

            const labels = <?= json_encode($chart_labels) ?>;
            const currentValues = <?= json_encode(array_map('floatval', $chart_values)) ?>;
            const previousValues = <?= json_encode(array_map('floatval', $chart_previous)) ?>;

            const chartCtx = ctx.getContext('2d');
            const primaryGradient = chartCtx.createLinearGradient(0, 0, 0, 260);
            primaryGradient.addColorStop(0, 'rgba(37, 99, 235, 0.25)');
            primaryGradient.addColorStop(1, 'rgba(37, 99, 235, 0.00)');

            const datasets = [{
                label: 'Current Revenue',
                data: currentValues,
                borderColor: '#2563eb',
                backgroundColor: primaryGradient,
                fill: true,
                tension: 0.38,
                borderWidth: 3,
                pointRadius: currentValues.length > 15 ? 3 : 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#2563eb',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: '#ffffff',
                pointHoverBorderColor: '#2563eb',
                pointHoverBorderWidth: 3,
            }];

            if (previousValues && previousValues.length > 0 && previousValues.some(v => v > 0)) {
                datasets.push({
                    label: 'Previous Period',
                    data: previousValues,
                    borderColor: '#94a3b8',
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    tension: 0.38,
                    borderWidth: 2,
                    pointRadius: previousValues.length > 15 ? 2 : 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#94a3b8',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1.5,
                });
            }

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#f8fafc',
                            bodyColor: '#f8fafc',
                            titleFont: { size: 12, weight: 'bold' },
                            bodyFont: { size: 13 },
                            padding: 12,
                            cornerRadius: 10,
                            displayColors: true,
                            boxPadding: 4,
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.dataset.label + ': ₱' + Number(context.parsed.y).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                color: '#64748b',
                                font: { size: 11 },
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 10
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(226, 232, 240, 0.6)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#64748b',
                                font: { size: 11 },
                                callback: function(value) {
                                    return '₱' + (value >= 1000 ? (value / 1000).toFixed(1) + 'k' : value);
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>

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