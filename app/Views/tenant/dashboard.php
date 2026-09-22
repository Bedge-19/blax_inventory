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

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-gutter">

        <div class="glass-card rounded-2xl p-lg flex flex-col gap-sm soft-shadow metric-card-hover">

            <div class="flex items-center justify-between">

                <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Total Revenue</p>

                <span class="w-10 h-10 rounded-xl bg-primary-container/20 text-primary flex items-center justify-center">

                    <span class="material-symbols-outlined fill-icon">payments</span>

                </span>

            </div>

            <h3 class="text-headline-md font-bold text-primary">₱<?= number_format($total_revenue, 2) ?></h3>

            <?= $renderDelta($revenue_delta) ?>

        </div>

        <div class="glass-card rounded-2xl p-lg flex flex-col gap-sm soft-shadow metric-card-hover">

            <div class="flex items-center justify-between">

                <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Total Sales</p>

                <span class="w-10 h-10 rounded-xl bg-surface-variant text-on-surface-variant flex items-center justify-center">

                    <span class="material-symbols-outlined fill-icon">shopping_bag</span>

                </span>

            </div>

            <h3 class="text-headline-md font-bold text-on-surface"><?= number_format((int) $total_sales) ?></h3>

            <?= $renderDelta($sales_delta) ?>

        </div>

        <div class="glass-card rounded-2xl p-lg flex flex-col gap-sm soft-shadow metric-card-hover">

            <div class="flex items-center justify-between">

                <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Pending Orders</p>

                <span class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">

                    <span class="material-symbols-outlined fill-icon">pending_actions</span>

                </span>

            </div>

            <h3 class="text-headline-md font-bold text-on-surface"><?= number_format((int) $pending_orders) ?></h3>

            <?= $renderDelta($pending_delta) ?>

        </div>

        <div class="glass-card rounded-2xl p-lg flex flex-col gap-sm soft-shadow metric-card-hover">

            <div class="flex items-center justify-between">

                <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Printing Requests</p>

                <span class="w-10 h-10 rounded-xl bg-tertiary-container/20 text-tertiary flex items-center justify-center">

                    <span class="material-symbols-outlined fill-icon">print</span>

                </span>

            </div>

            <h3 class="text-headline-md font-bold text-tertiary"><?= number_format((int) $printing_count) ?></h3>

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

            <div class="text-center py-xl text-on-surface-variant">

                <span class="material-symbols-outlined text-3xl">check_circle</span>

                <p class="text-label-sm mt-sm">All items are sufficiently stocked.</p>

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

                    <tbody>

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

                            <tr><td colspan="5" class="py-lg text-center text-on-surface-variant">No orders yet.</td></tr>

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

                    <tbody>

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

                            <tr><td colspan="5" class="py-lg text-center text-on-surface-variant">No printing requests yet.</td></tr>

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

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            const currentSale = localStorage.getItem('blax_last_sale');
            if (currentSale && currentSale !== lastSaleHandled) {
                loadSalesRange(lastActiveBtn);
            }
        }
    });
})();
</script>

<?= $this->endSection() ?>
