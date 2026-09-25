<?= $this->extend('layouts/tenant') ?>

<?php
$renderDelta = function (array $d): string {
    if (empty($d['has_prior'])) {
        return '<p class="text-xs text-on-surface-variant flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">trending_flat</span>No prior period data</p>';
    }
    if ($d['direction'] === 'neutral') {
        return '<p class="text-xs text-on-surface-variant flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">trending_flat</span>No change vs prior 30 days</p>';
    }
    $up   = $d['direction'] === 'up';
    $cls  = $up ? 'text-green-600' : 'text-red-600';
    $icon = $up ? 'trending_up' : 'trending_down';
    return '<p class="text-xs font-semibold flex items-center gap-1 ' . $cls . '"><span class="material-symbols-outlined text-[14px]">' . $icon . '</span>' . esc((string) $d['pct']) . '% vs prior 30 days</p>';
};

$compactMoney = function (float $v): string {
    if ($v >= 1000) {
        return '₱' . number_format($v / 1000, 1) . 'k';
    }
    if ($v > 0) {
        return '₱' . number_format($v, 0);
    }
    return '';
};
?>

<?= $this->section('content') ?>

<div class="flex-1 space-y-gutter">

    <?php if (!empty($active_warning)): ?>
        <!-- Formal Compliance Warning Banner -->
        <div class="rounded-2xl p-md lg:p-lg bg-red-500/10 border-2 border-red-500/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-md shadow-sm">
            <div class="flex items-center gap-md">
                <span class="w-10 h-10 rounded-xl bg-red-500/20 text-red-700 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">error</span>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="font-bold text-red-800 dark:text-red-300 text-body-md sm:text-body-lg"><?= esc($active_warning['title'] ?? 'Formal Compliance Warning') ?></h4>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-red-200 text-red-900 uppercase">Attention Required</span>
                    </div>
                    <p class="text-xs text-on-surface-variant mt-1"><?= esc($active_warning['message'] ?? 'Your shop has been flagged for a policy review. Please ensure all store activities comply with marketplace standards.') ?></p>
                </div>
            </div>
            <a href="<?= base_url('tenant/settings') ?>" class="inline-flex items-center gap-xs px-md py-2 bg-red-600 text-white text-xs font-bold rounded-xl hover:bg-red-700 transition-colors shadow-sm whitespace-nowrap">
                <span>View Settings & Policies</span>
                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        </div>
    <?php endif; ?>

    <?php if (!empty($low_stock_count) && $low_stock_count > 0): ?>
        <!-- Low Stock / Out of Stock Alert Banner -->
        <div class="rounded-2xl p-md lg:p-lg bg-amber-500/10 border border-amber-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-md shadow-sm">
            <div class="flex items-center gap-md">
                <span class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-700 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">warning</span>
                </span>
                <div>
                    <h4 class="font-bold text-on-surface text-body-md sm:text-body-lg">Low Stock Attention Needed</h4>
                    <p class="text-xs text-on-surface-variant">You have <span class="font-bold text-amber-800"><?= (int)$low_stock_count ?></span> item<?= (int)$low_stock_count === 1 ? '' : 's' ?> at or below the threshold. Restock now to prevent missed customer orders.</p>
                </div>
            </div>
            <a href="<?= base_url('tenant/inventory?stock=low') ?>" class="inline-flex items-center gap-xs px-md py-2 bg-amber-600 text-white text-xs font-bold rounded-xl hover:bg-amber-700 transition-colors shadow-sm whitespace-nowrap">
                <span>View Low Stock Products</span>
                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        </div>
    <?php endif; ?>

    <?php if (!empty($seasonal_spotlight) && !empty($seasonal_spotlight['top_items'])): ?>
        <!-- AI Seasonal Intelligence & Restock Forecast Banner -->
        <div class="rounded-3xl p-5 lg:p-6 bg-slate-900 bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-950 text-white shadow-xl border border-white/10 relative overflow-hidden group" style="background-color: #0f172a;">
            <!-- Ambient Glow -->
            <div class="absolute -top-16 -right-16 w-64 h-64 bg-primary/30 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-16 -left-16 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                <div class="max-w-xl">
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-white/15 text-sky-200 border border-white/20 backdrop-blur-xs">
                            <span class="material-symbols-outlined text-[15px]"><?= esc($seasonal_spotlight['icon']) ?></span>
                            <span>Active Season: <?= esc($seasonal_spotlight['short_name']) ?></span>
                        </span>
                        <?php if (!empty($seasonal_spotlight['cohere_powered'])): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-400/20 text-sky-200 border border-sky-400/30">
                                <span class="material-symbols-outlined text-[13px]">smart_toy</span>
                                <span>Cohere AI</span>
                            </span>
                        <?php endif; ?>
                        <?php if ($seasonal_spotlight['critical_count'] > 0): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-rose-500 text-white shadow-xs animate-pulse">
                                <span><?= (int) $seasonal_spotlight['critical_count'] ?> Critical Stockouts</span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-lg sm:text-xl font-extrabold tracking-tight text-white">
                        AI Seasonal Restock Recommendations
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-300 mt-1 leading-relaxed">
                        <?php if (!empty($seasonal_spotlight['cohere_summary'])): ?>
                            <?= esc($seasonal_spotlight['cohere_summary']) ?>
                        <?php else: ?>
                            Demand is accelerating in Polomolok for <?= esc($seasonal_spotlight['short_name']) ?>. Replenish high-velocity items to capture an estimated <strong class="text-amber-300 font-extrabold">₱<?= number_format($seasonal_spotlight['est_opp_revenue'], 2) ?></strong> in seasonal sales.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <a href="<?= base_url('tenant/analytics') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-slate-900 text-xs font-extrabold hover:bg-slate-100 transition-all shadow-md active:scale-95">
                        <span>Explore Full AI Forecast</span>
                        <span class="material-symbols-outlined text-[18px]">insights</span>
                    </a>
                </div>
            </div>

            <!-- Spotlight Top Restock Items -->
            <div class="relative z-10 mt-5 pt-4 border-t border-white/10 grid grid-cols-1 md:grid-cols-3 gap-3">
                <?php foreach ($seasonal_spotlight['top_items'] as $item): ?>
                    <div class="bg-slate-800/80 hover:bg-slate-800 rounded-2xl p-3.5 border border-white/15 backdrop-blur-xs transition-all flex flex-col justify-between" style="background-color: rgba(30, 41, 59, 0.85);">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-300 truncate block"><?= esc($item['category']) ?></span>
                                <span class="text-[10px] font-black px-2 py-0.5 rounded-full uppercase <?= $item['urgency'] === 'critical' ? 'bg-rose-500 text-white' : 'bg-amber-400 text-slate-900' ?>">
                                    <?= $item['urgency'] === 'critical' ? 'Urgent' : 'Low Buffer' ?>
                                </span>
                            </div>
                            <h4 class="text-xs font-bold text-white truncate mt-1" title="<?= esc($item['name']) ?>">
                                <?= esc($item['name']) ?>
                            </h4>
                            <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-300">
                                <span>Stock: <strong class="text-white font-mono"><?= $item['current_stock'] ?></strong> / <?= $item['target_buffer'] ?> pcs</span>
                                <span class="font-mono text-emerald-300 font-bold">+<?= $item['restock_units'] ?> needed</span>
                            </div>
                            <!-- Mini Progress Bar -->
                            <div class="w-full bg-slate-700 h-1.5 rounded-full mt-1.5 overflow-hidden">
                                <div class="h-full rounded-full <?= $item['urgency'] === 'critical' ? 'bg-rose-400' : 'bg-amber-400' ?>" style="width: <?= $item['stock_pct'] ?>%"></div>
                            </div>
                        </div>

                        <div class="mt-3 pt-2.5 border-t border-white/10 flex items-center justify-between">
                            <span class="text-[10px] text-slate-300">Est. ₱<?= number_format($item['est_revenue_uplift'], 2) ?></span>
                            <a href="<?= base_url('tenant/inventory?search=' . urlencode($item['name'])) ?>" class="inline-flex items-center gap-1 text-[11px] font-bold text-sky-300 hover:text-white transition-colors">
                                <span>Restock</span>
                                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-gutter">

        <div class="glass-card rounded-2xl p-lg flex flex-col gap-sm soft-shadow metric-card-hover">
            <div class="flex items-center justify-between">
                <p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Total Revenue</p>
                <span class="w-10 h-10 rounded-xl bg-primary/10 text-primary border border-primary/20 flex items-center justify-center">
                    <span class="material-symbols-outlined fill-icon">payments</span>
                </span>
            </div>
            <h3 id="kpi-total-revenue" data-raw="<?= (float)$total_revenue ?>" class="text-headline-md font-bold text-primary transition-all duration-300">₱<?= number_format($total_revenue, 2) ?></h3>
            <?= $renderDelta($revenue_delta) ?>
        </div>

        <div class="glass-card rounded-2xl p-lg flex flex-col gap-sm soft-shadow metric-card-hover">
            <div class="flex items-center justify-between">
                <p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Total Sales</p>
                <span class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 flex items-center justify-center">
                    <span class="material-symbols-outlined fill-icon">shopping_bag</span>
                </span>
            </div>
            <h3 id="kpi-total-sales" data-raw="<?= (int)$total_sales ?>" class="text-headline-md font-bold text-on-surface transition-all duration-300"><?= number_format((int) $total_sales) ?></h3>
            <?= $renderDelta($sales_delta) ?>
        </div>

        <div class="glass-card rounded-2xl p-lg flex flex-col gap-sm soft-shadow metric-card-hover">
            <div class="flex items-center justify-between">
                <p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Pending Orders</p>
                <span class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 flex items-center justify-center">
                    <span class="material-symbols-outlined fill-icon">pending_actions</span>
                </span>
            </div>
            <h3 id="kpi-pending-orders" data-raw="<?= (int)$pending_orders ?>" class="text-headline-md font-bold text-on-surface transition-all duration-300"><?= number_format((int) $pending_orders) ?></h3>
            <?= $renderDelta($pending_delta) ?>
        </div>

        <div class="glass-card rounded-2xl p-lg flex flex-col gap-sm soft-shadow metric-card-hover">
            <div class="flex items-center justify-between">
                <p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Printing Requests</p>
                <span class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20 flex items-center justify-center">
                    <span class="material-symbols-outlined fill-icon">print</span>
                </span>
            </div>
            <h3 id="kpi-printing-count" data-raw="<?= (int)$printing_count ?>" class="text-headline-md font-bold text-tertiary transition-all duration-300"><?= number_format((int) $printing_count) ?></h3>
            <?= $renderDelta($printing_delta) ?>
        </div>

    </div>

    <!-- Sales Overview -->
    <div class="glass-card rounded-2xl p-lg">

        <div class="flex flex-wrap items-start justify-between gap-md mb-lg">

            <div>

                <h3 class="text-title-lg font-bold text-on-surface">Sales Overview</h3>

                <p class="text-label-sm text-on-surface-variant">Daily revenue from store orders & completed printing requests</p>

            </div>

            <div class="flex items-center gap-xs bg-surface-container-low rounded-xl p-xs">

                <button type="button" data-sales-range="7" class="sales-range-btn px-md py-sm rounded-lg text-label-sm font-semibold bg-primary text-on-primary">Last 7 Days</button>

                <button type="button" data-sales-range="30" class="sales-range-btn px-md py-sm rounded-lg text-label-sm font-semibold text-on-surface-variant hover:bg-surface-container-high">Last 30 Days</button>

                <button type="button" data-sales-range="year" class="sales-range-btn px-md py-sm rounded-lg text-label-sm font-semibold text-on-surface-variant hover:bg-surface-container-high">This Year</button>

            </div>

        </div>

        <div class="rounded-xl bg-surface-container-lowest border border-outline-variant/30 p-md">

            <p class="text-label-sm text-on-surface-variant">Total Revenue <span id="sales-range-label">(Last 7 Days)</span></p>

            <h3 id="sales-total" class="text-headline-md font-bold text-primary mb-md">₱<?= number_format($chart_total, 2) ?></h3>

            <div id="sales-error" class="hidden mb-md p-sm rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center justify-between">
                <div class="flex items-center gap-xs">
                    <span class="material-symbols-outlined text-[18px]">error</span>
                    <span id="sales-error-msg">Failed to refresh sales data.</span>
                </div>
                <button type="button" id="sales-retry-btn" class="font-bold underline ml-sm hover:text-rose-950">Retry</button>
            </div>

            <div id="sales-chart" class="flex items-end gap-xs sm:gap-md h-40 sm:h-48 transition-opacity duration-200">
                <?php foreach ($chart_labels as $i => $label): ?>
                    <?php $val = (float) $chart_values[$i]; ?>
                    <?php $pct = $chart_max > 0 ? max(4, (int) round($val / $chart_max * 100)) : 4; ?>
                    <div class="flex-1 flex flex-col items-center justify-end gap-1 h-full min-w-0">
                        <span class="text-[10px] text-on-surface-variant whitespace-nowrap"><?= $val > 0 ? $compactMoney($val) : '' ?></span>
                        <div class="w-full max-w-[32px] rounded-t-md <?= $chart_max > 0 && $val === (float) $chart_max ? 'bg-tertiary' : ($val > 0 ? 'bg-primary' : 'bg-surface-container-high/60') ?>" style="height: <?= $pct ?>%" title="<?= esc($label) ?>: ₱<?= number_format($val, 2) ?>"></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="sales-labels" class="flex gap-xs sm:gap-md mt-xs">
                <?php foreach ($chart_labels as $label): ?>
                    <div class="flex-1 text-center min-w-0">
                        <span class="text-[10px] sm:text-[11px] text-on-surface-variant truncate"><?= esc($label) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>

    </div>

    <!-- Low Stock Alert -->
    <div class="glass-card rounded-2xl p-lg">

        <div class="flex items-center justify-between mb-md">

            <h3 class="text-title-lg font-bold text-on-surface">Low Stock Alert</h3>

            <a href="<?= base_url('tenant/inventory') ?>" class="text-label-sm font-semibold text-primary hover:underline">View All</a>

        </div>

        <?php if (!empty($low_stock_items)): ?>

            <div class="table-responsive">

                <table class="w-full text-left border-collapse min-w-[560px]">

                    <thead>

                        <tr class="border-b border-outline-variant/30 text-label-sm text-on-surface-variant uppercase">

                            <th class="py-sm px-md">Product</th>
                            <th class="py-sm px-md">SKU</th>
                            <th class="py-sm px-md">Stock</th>
                            <th class="py-sm px-md">Status</th>
                            <th class="py-sm px-md"></th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach (array_slice($low_stock_items, 0, 8) as $p): ?>

                            <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low">

                                <td class="py-md px-md">

                                    <div class="flex items-center gap-md min-w-0">

                                        <?php if (!empty($p['image_url'])): ?>

                                            <img src="<?= esc(product_image_url($p['image_url'], 'thumbnail')) ?>" alt="<?= esc($p['name']) ?>" class="w-10 h-10 rounded-lg object-cover shrink-0">

                                        <?php else: ?>

                                            <div class="w-10 h-10 rounded-lg bg-surface-container-high flex items-center justify-center shrink-0">

                                                <span class="material-symbols-outlined text-on-surface-variant">inventory_2</span>

                                            </div>

                                        <?php endif; ?>

                                        <span class="font-semibold truncate"><?= esc($p['name']) ?></span>

                                    </div>

                                </td>

                                <td class="py-md px-md text-label-sm text-on-surface-variant whitespace-nowrap"><?= esc($p['sku']) ?></td>

                                <td class="py-md px-md font-semibold"><?= (int) $p['stock_quantity'] ?></td>

                                <td class="py-md px-md"><?= ((int) $p['stock_quantity'] === 0) ? status_badge('out_of_stock') : status_badge('low_stock') ?></td>

                                <td class="py-md px-md"><a href="<?= base_url('tenant/inventory') ?>" class="text-label-sm font-semibold text-primary hover:underline whitespace-nowrap">Restock</a></td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="text-center py-10 text-on-surface-variant flex flex-col items-center justify-center gap-2">

                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">check_circle</span>
                </div>

                <p class="text-xs font-bold text-on-surface">Healthy Inventory</p>
                <p class="text-xs text-on-surface-variant">All items are sufficiently stocked.</p>

            </div>

        <?php endif; ?>

    </div>

    <!-- Recent Orders + Recent Printing Requests -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-gutter">

        <div class="glass-card rounded-2xl p-lg">

            <div class="flex items-center justify-between mb-md">

                <h3 class="text-title-lg font-bold text-on-surface">Recent Orders</h3>

<a href="<?= base_url('tenant/orders') ?>" class="text-label-sm font-semibold text-primary hover:underline">View All</a>

        </div>

        <div class="table-responsive">

            <table class="w-full text-left border-collapse min-w-[560px]">

                    <thead>

                        <tr class="border-b border-outline-variant/30 text-label-sm text-on-surface-variant uppercase">

                            <th class="py-sm px-md">Order ID</th>
                            <th class="py-sm px-md">Customer</th>
                            <th class="py-sm px-md">Total</th>
                            <th class="py-sm px-md">Status</th>
                            <th class="py-sm px-md">Date</th>

                        </tr>

                    </thead>

                    <tbody id="dashboard-recent-orders-tbody">

                        <?php if (!empty($recent_orders)): ?>

                            <?php foreach ($recent_orders as $o): ?>

                                <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low">

                                    <td class="py-md px-md font-semibold whitespace-nowrap">#<?= esc($o['order_number'] ?? ('ORD-' . $o['id'])) ?></td>

                                    <td class="py-md px-md"><?= esc(trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? ''))) ?: 'Customer' ?></td>

                                    <td class="py-md px-md font-bold text-primary whitespace-nowrap">₱<?= number_format((float) $o['total_amount'], 2) ?></td>

                                    <td class="py-md px-md"><?= status_badge($o['status']) ?></td>

                                    <td class="py-md px-md text-xs text-on-surface-variant whitespace-nowrap"><?= date('M d, Y', strtotime($o['placed_at'])) ?></td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr id="dashboard-no-orders-row"><td colspan="5" class="py-lg text-center text-on-surface-variant">No orders yet.</td></tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <div class="glass-card rounded-2xl p-lg">

            <div class="flex items-center justify-between mb-md">

                <h3 class="text-title-lg font-bold text-on-surface">Recent Printing Requests</h3>

                <a href="<?= base_url('tenant/printing') ?>" class="text-label-sm font-semibold text-primary hover:underline">View All</a>

            </div>

            <div class="table-responsive">

                <table class="w-full text-left border-collapse min-w-[560px]">

                    <thead>

                        <tr class="border-b border-outline-variant/30 text-label-sm text-on-surface-variant uppercase">

                            <th class="py-sm px-md">Request No.</th>
                            <th class="py-sm px-md">File</th>
                            <th class="py-sm px-md">Customer</th>
                            <th class="py-sm px-md">Total</th>
                            <th class="py-sm px-md">Status</th>

                        </tr>

                    </thead>

                    <tbody id="dashboard-recent-printing-tbody">

                        <?php if (!empty($recent_requests)): ?>

                            <?php foreach ($recent_requests as $r): ?>

                                <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low">

                                    <td class="py-md px-md font-semibold whitespace-nowrap"><?= esc($r['request_number'] ?? ('PR-' . $r['id'])) ?></td>

                                    <td class="py-md px-md text-label-sm text-on-surface-variant truncate max-w-[160px]"><?= esc($r['file_name']) ?></td>

                                    <td class="py-md px-md"><?= esc(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''))) ?: 'Customer' ?></td>

                                    <td class="py-md px-md font-bold text-tertiary whitespace-nowrap">₱<?= number_format((float) $r['total_price'], 2) ?></td>

                                    <td class="py-md px-md"><?= status_badge($r['status']) ?></td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr id="dashboard-no-printing-row"><td colspan="5" class="py-lg text-center text-on-surface-variant">No printing requests yet.</td></tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>
(function () {
    const salesEndpoint = '<?= site_url('tenant/dashboard/sales') ?>';
    const salesChart = document.getElementById('sales-chart');
    const salesLabels = document.getElementById('sales-labels');
    const salesTotal = document.getElementById('sales-total');
    const salesRangeLabel = document.getElementById('sales-range-label');
    const salesError = document.getElementById('sales-error');
    const salesErrorMsg = document.getElementById('sales-error-msg');
    const salesRetryBtn = document.getElementById('sales-retry-btn');
    let lastActiveBtn = document.querySelector('.sales-range-btn.bg-primary') || document.querySelector('.sales-range-btn');

    function compactMoney(v) {
        if (v >= 1000) return '₱' + (v / 1000).toFixed(1) + 'k';
        if (v > 0) return '₱' + Math.round(v).toLocaleString();
        return '';
    }

    let lastSaleHandled = localStorage.getItem('blax_last_sale');

    function renderSalesChart(data) {
        const total = Number(data.total) || 0;
        const validValues = (data.values && Array.isArray(data.values)) ? data.values.map(v => Number(v) || 0) : [];
        const max = validValues.length ? Math.max.apply(null, validValues) : 0;
        salesTotal.textContent = '₱' + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        salesRangeLabel.textContent = data.range === 'year' ? '(This Year)' : '(Last ' + data.range + ' Days)';

        if (!validValues.length) {
            salesChart.className = 'h-40 flex items-center justify-center rounded-lg bg-surface-container-low/40 text-label-sm text-on-surface-variant';
            salesChart.textContent = 'No sales data available for this period.';
            salesLabels.className = 'hidden';
            return;
        }

        salesChart.className = 'flex items-end gap-xs sm:gap-md h-40 sm:h-48 transition-opacity duration-200';
        salesLabels.className = 'flex gap-xs sm:gap-md mt-xs';

        let bars = '';
        let labs = '';
        const scaleMax = max > 0 ? max : 1;
        validValues.forEach(function (val, i) {
            const pct = val > 0 ? Math.max(8, Math.round(val / scaleMax * 100)) : 4;
            const cls = (max > 0 && val === max) ? 'bg-tertiary' : (val > 0 ? 'bg-primary' : 'bg-surface-container-high/60');
            const lbl = val > 0 ? compactMoney(val) : '';
            const labelStr = (data.labels && data.labels[i]) ? data.labels[i] : '';
            bars += '<div class="flex-1 flex flex-col items-center justify-end gap-1 h-full min-w-0">'
                + '<span class="text-[10px] text-on-surface-variant whitespace-nowrap">' + lbl + '</span>'
                + '<div class="w-full max-w-[32px] rounded-t-md transition-all duration-300 ' + cls + '" style="height:' + pct + '%" title="' + labelStr + ': ₱' + val.toFixed(2) + '"></div>'
                + '</div>';
            labs += '<div class="flex-1 text-center min-w-0"><span class="text-[10px] sm:text-[11px] text-on-surface-variant truncate">' + labelStr + '</span></div>';
        });
        salesChart.innerHTML = bars;
        salesLabels.innerHTML = labs;
    }

    async function loadSalesRange(btn) {
        if (!btn) btn = lastActiveBtn;
        lastActiveBtn = btn;
        document.querySelectorAll('.sales-range-btn').forEach(function (b) {
            b.classList.remove('bg-primary', 'text-on-primary');
            b.classList.add('text-on-surface-variant');
        });
        btn.classList.add('bg-primary', 'text-on-primary');
        btn.classList.remove('text-on-surface-variant');

        if (salesError) salesError.classList.add('hidden');
        if (salesChart) salesChart.classList.add('opacity-40', 'pointer-events-none');

        try {
            const rangeVal = btn.dataset.salesRange || '7';
            const sep = salesEndpoint.indexOf('?') >= 0 ? '&' : '?';
            const res = await fetch(salesEndpoint + sep + 'range=' + encodeURIComponent(rangeVal) + '&_t=' + Date.now(), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            if (data && data.success) {
                renderSalesChart(data);
                lastSaleHandled = localStorage.getItem('blax_last_sale');
            } else {
                throw new Error((data && data.error) ? data.error : 'Invalid response');
            }
        } catch (err) {
            if (salesError) {
                if (salesErrorMsg) salesErrorMsg.textContent = 'Unable to refresh sales chart (' + (err.message || 'network error') + ').';
                salesError.classList.remove('hidden');
            }
        } finally {
            if (salesChart) salesChart.classList.remove('opacity-40', 'pointer-events-none');
        }
    }

    document.querySelectorAll('.sales-range-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            loadSalesRange(btn);
        });
    });

    if (salesRetryBtn) {
        salesRetryBtn.addEventListener('click', function () {
            if (lastActiveBtn) loadSalesRange(lastActiveBtn);
        });
    }

    // Immediate Cache Invalidation & Refetch when sale is completed in POS or another window
    window.addEventListener('storage', function (e) {
        if (e.key === 'blax_last_sale' && e.newValue) {
            loadSalesRange(lastActiveBtn);
        }
    });

    window.addEventListener('blax:sale_completed', function () {
        loadSalesRange(lastActiveBtn);
    });

    // Real-time Event Listeners for Instant Dashboard Metrics & Table Updating
    window.addEventListener('blax:new-order', function (e) {
        const order = e.detail;
        if (!order) return;

        // 1. Live KPI Increment: Total Sales
        const salesEl = document.getElementById('kpi-total-sales');
        if (salesEl) {
            let currentSales = parseInt(salesEl.getAttribute('data-raw') || '0', 10) + 1;
            salesEl.setAttribute('data-raw', currentSales);
            salesEl.textContent = new Intl.NumberFormat().format(currentSales);
            salesEl.classList.add('scale-110', 'text-primary');
            setTimeout(() => salesEl.classList.remove('scale-110', 'text-primary'), 600);
        }

        // 2. Live KPI Increment: Total Revenue
        const revEl = document.getElementById('kpi-total-revenue');
        if (revEl && order.total_amount) {
            let currentRev = parseFloat(revEl.getAttribute('data-raw') || '0') + parseFloat(order.total_amount);
            revEl.setAttribute('data-raw', currentRev);
            revEl.textContent = '₱' + new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(currentRev);
            revEl.classList.add('scale-110', 'text-emerald-600');
            setTimeout(() => revEl.classList.remove('scale-110', 'text-emerald-600'), 600);
        }

        // 3. Live KPI Increment: Pending Orders
        const pendingEl = document.getElementById('kpi-pending-orders');
        if (pendingEl) {
            let currentPending = parseInt(pendingEl.getAttribute('data-raw') || '0', 10) + 1;
            pendingEl.setAttribute('data-raw', currentPending);
            pendingEl.textContent = new Intl.NumberFormat().format(currentPending);
            pendingEl.classList.add('scale-110', 'text-amber-600');
            setTimeout(() => pendingEl.classList.remove('scale-110', 'text-amber-600'), 600);
        }

        // 4. Prepend to Recent Orders Table
        const tbody = document.getElementById('dashboard-recent-orders-tbody');
        if (tbody) {
            const noOrdersRow = document.getElementById('dashboard-no-orders-row');
            if (noOrdersRow) noOrdersRow.remove();

            const tr = document.createElement('tr');
            tr.className = 'border-b border-outline-variant/10 bg-emerald-500/10 hover:bg-emerald-500/15 transition-all duration-500';
            tr.innerHTML = `
                <td class="py-md px-md font-semibold whitespace-nowrap text-primary">#${order.order_number} <span class="text-[9px] font-bold bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded ml-1 animate-pulse">⚡ Just Now</span></td>
                <td class="py-md px-md font-medium text-on-surface">${order.customer_name}</td>
                <td class="py-md px-md font-bold text-primary whitespace-nowrap">₱${order.total_amount_fmt}</td>
                <td class="py-md px-md"><span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-300">Pending</span></td>
                <td class="py-md px-md text-xs text-on-surface-variant whitespace-nowrap">Just now</td>
            `;
            tbody.insertBefore(tr, tbody.firstChild);
        }

        // 5. Trigger Sales Chart Refresh
        if (lastActiveBtn) {
            loadSalesRange(lastActiveBtn);
        }
    });

    window.addEventListener('blax:new-printing', function (e) {
        const pr = e.detail;
        if (!pr) return;

        // 1. Live KPI Increment: Printing Count
        const prCountEl = document.getElementById('kpi-printing-count');
        if (prCountEl) {
            let currentPr = parseInt(prCountEl.getAttribute('data-raw') || '0', 10) + 1;
            prCountEl.setAttribute('data-raw', currentPr);
            prCountEl.textContent = new Intl.NumberFormat().format(currentPr);
            prCountEl.classList.add('scale-110', 'text-purple-600');
            setTimeout(() => prCountEl.classList.remove('scale-110', 'text-purple-600'), 600);
        }

        // 2. Prepend to Recent Printing Table
        const tbody = document.getElementById('dashboard-recent-printing-tbody');
        if (tbody) {
            const noPrRow = document.getElementById('dashboard-no-printing-row');
            if (noPrRow) noPrRow.remove();

            const tr = document.createElement('tr');
            tr.className = 'border-b border-outline-variant/10 bg-purple-500/10 hover:bg-purple-500/15 transition-all duration-500';
            tr.innerHTML = `
                <td class="py-md px-md font-semibold whitespace-nowrap text-tertiary">${pr.request_number} <span class="text-[9px] font-bold bg-purple-100 text-purple-800 px-1.5 py-0.5 rounded ml-1 animate-pulse">⚡ New</span></td>
                <td class="py-md px-md text-label-sm text-on-surface-variant truncate max-w-[160px]">${pr.service_name}</td>
                <td class="py-md px-md font-medium text-on-surface">${pr.customer_name}</td>
                <td class="py-md px-md font-bold text-tertiary whitespace-nowrap">₱${pr.total_amount_fmt}</td>
                <td class="py-md px-md"><span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 text-purple-800 border border-purple-300">New</span></td>
            `;
            tbody.insertBefore(tr, tbody.firstChild);
        }
    });
})();
</script>

<?= $this->endSection() ?>
