<?= $this->extend('layouts/tenant') ?>
<?= $this->section('content') ?>

<?php
$ranges = ['7' => 'Last 7 days', '30' => 'Last 30 days', 'year' => 'This year'];
$statusColors = [
    'completed'        => '#10b981', // Emerald 500
    'ready_for_pickup' => '#8b5cf6', // Purple 500
    'delivered'        => '#06b6d4', // Cyan 500
    'in_transit'       => '#0284c7', // Sky 600
    'shipped'          => '#38bdf8', // Light Sky 400
    'processing'       => '#3b82f6', // Blue 500
    'pending'          => '#f59e0b', // Amber 500
    'cancelled'        => '#ef4444', // Rose 500
    'returned'         => '#64748b', // Slate 500
];

$statusBgLight = [
    'completed'        => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
    'ready_for_pickup' => 'bg-purple-500/10 text-purple-700 border-purple-500/20',
    'delivered'        => 'bg-cyan-500/10 text-cyan-700 border-cyan-500/20',
    'in_transit'       => 'bg-sky-500/10 text-sky-700 border-sky-500/20',
    'shipped'          => 'bg-sky-500/10 text-sky-700 border-sky-500/20',
    'processing'       => 'bg-blue-500/10 text-blue-700 border-blue-500/20',
    'pending'          => 'bg-amber-500/10 text-amber-700 border-amber-500/20',
    'cancelled'        => 'bg-rose-500/10 text-rose-700 border-rose-500/20',
    'returned'         => 'bg-slate-500/10 text-slate-700 border-slate-500/20',
];

// Sort status distribution by count descending
if (!empty($status_dist)) {
    usort($status_dist, fn($a, $b) => ((int) ($b['count'] ?? 0)) <=> ((int) ($a['count'] ?? 0)));
}

$completedCount = 0;
$segs = [];
$acc  = 0;
foreach ($status_dist as $s) {
    $pct = (float) $s['pct'];
    if (in_array($s['status'], ['completed', 'delivered'], true)) {
        $completedCount += (int) $s['count'];
    }
    if ($pct <= 0) {
        continue;
    }
    $col = $statusColors[$s['status']] ?? '#64748b';
    $segs[] = $col . ' ' . $acc . '% ' . ($acc + $pct) . '%';
    $acc += $pct;
}
$conic = !empty($segs) ? 'conic-gradient(' . implode(', ', $segs) . ')' : '#e2e8f0';
$fulfilledRate = $status_total > 0 ? round(($completedCount / $status_total) * 100, 1) : 0;

$catColors = ['bg-primary', 'bg-secondary', 'bg-tertiary', 'bg-outline', 'bg-error'];
$chartMax  = max((float) $chart_max, 0.01);

$seasonDef       = $seasonal_data['season'] ?? [];
$isSeasonActive  = !empty($seasonal_data['is_currently_active']);
$activeKey       = $seasonal_data['selected_season_key'] ?? 'school';
$availableSeasons = $seasonal_data['available_seasons'] ?? [];
$recommendations = $seasonal_data['recommendations'] ?? [];
$expansionIdeas  = $seasonal_data['expansion_ideas'] ?? [];
$detectedSeason  = $seasonal_data['detected_active_season'] ?? 'school';
?>

<div class="flex-1 space-y-6 pb-12">

    <!-- Page Header & Executive Summary Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/30 shadow-xs">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <span class="p-1.5 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">insights</span>
                </span>
                <span class="text-xs font-bold text-on-surface-variant/70 uppercase tracking-widest">Store Telemetry &amp; Demand AI</span>
            </div>
            <h1 class="text-2xl font-black text-on-surface tracking-tight">Business Analytics &amp; Predictive Forecasting</h1>
            <p class="text-xs text-on-surface-variant max-w-2xl">
                Real-time transaction metrics, multi-period revenue growth, and seasonal demand predictions for merchants in Polomolok.
            </p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <!-- Active Calendar Season Indicator Pill -->
            <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-surface-container border border-outline-variant/40 shadow-2xs">
                <span class="material-symbols-outlined text-[18px] text-primary"><?= esc($seasonDef['icon'] ?? 'calendar_month') ?></span>
                <div class="text-left">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Calendar Cycle</span>
                        <span class="w-1.5 h-1.5 rounded-full <?= $isSeasonActive ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' ?>"></span>
                    </div>
                    <span class="text-xs font-bold text-on-surface block"><?= esc($seasonDef['short_name'] ?? 'Seasonal Rush') ?></span>
                </div>
            </div>

            <!-- Jump to Inventory button -->
            <a href="<?= base_url('tenant/inventory') ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-outline-variant/40 bg-surface-container hover:bg-surface-container-high text-xs font-semibold text-on-surface transition-all shadow-2xs">
                <span class="material-symbols-outlined text-[17px] text-primary">inventory_2</span>
                <span>Open Inventory</span>
            </a>
        </div>
    </div>

    <!-- Executive KPI Bento Grid (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KPI 1: Gross Revenue -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/25 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2 mb-2">
                <span class="text-xs font-bold text-on-surface-variant/70 uppercase tracking-wider">Gross Revenue</span>
                <span class="p-2 rounded-xl bg-primary/10 text-primary">
                    <span class="material-symbols-outlined text-[20px]">payments</span>
                </span>
            </div>
            <div>
                <p class="text-2xl font-black text-on-surface tracking-tight">₱<?= number_format((float) $total_revenue, 2) ?></p>
                <p class="text-[11px] text-on-surface-variant mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px] text-emerald-600">verified</span>
                    <span>Delivered &amp; completed orders</span>
                </p>
            </div>
        </div>

        <!-- KPI 2: Avg. Order Value -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/25 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2 mb-2">
                <span class="text-xs font-bold text-on-surface-variant/70 uppercase tracking-wider">Avg. Order Value</span>
                <span class="p-2 rounded-xl bg-secondary/10 text-secondary">
                    <span class="material-symbols-outlined text-[20px]">shopping_cart</span>
                </span>
            </div>
            <div>
                <p class="text-2xl font-black text-on-surface tracking-tight">₱<?= number_format((float) $avg_order_value, 2) ?></p>
                <p class="text-[11px] text-on-surface-variant mt-1">Across <?= number_format((int) $avg_order_base) ?> verified orders</p>
            </div>
        </div>

        <!-- KPI 3: Top Selling Category -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/25 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2 mb-2">
                <span class="text-xs font-bold text-on-surface-variant/70 uppercase tracking-wider">Top Category</span>
                <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-600">
                    <span class="material-symbols-outlined text-[20px]">category</span>
                </span>
            </div>
            <div>
                <p class="text-xl font-black text-on-surface truncate" title="<?= esc($top_category) ?>"><?= esc($top_category) ?></p>
                <p class="text-[11px] text-on-surface-variant mt-1">Highest revenue contributor</p>
            </div>
        </div>

        <!-- KPI 4: Seasonal AI Restock Opportunity -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-indigo-200/80 shadow-xs hover:shadow-md transition-all flex flex-col justify-between relative overflow-hidden bg-gradient-to-br from-indigo-50/70 via-surface-container-lowest to-blue-50/50">
            <div class="flex items-center justify-between gap-2 mb-2 relative z-10">
                <span class="text-xs font-bold text-indigo-700 uppercase tracking-wider">Seasonal Opportunity</span>
                <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-600">
                    <span class="material-symbols-outlined text-[20px]">auto_awesome</span>
                </span>
            </div>
            <div class="relative z-10">
                <p class="text-2xl font-black text-indigo-950 tracking-tight">₱<?= number_format((float) ($seasonal_data['est_seasonal_opp_revenue'] ?? 0), 2) ?></p>
                <p class="text-[11px] text-on-surface-variant mt-1 flex items-center gap-1">
                    <?php if (($seasonal_data['critical_stockout_count'] ?? 0) > 0): ?>
                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                        <strong class="text-rose-600 font-bold"><?= (int) $seasonal_data['critical_stockout_count'] ?></strong> items need immediate restock
                    <?php else: ?>
                        <span class="material-symbols-outlined text-[14px] text-emerald-600">verified</span>
                        <span class="text-emerald-700 font-semibold">Safety stock well-maintained</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- AI SEASONAL INVENTORY INTELLIGENCE & DEMAND FORECASTING HUB       -->
    <!-- ================================================================= -->
    <div id="seasonal-ai-hub" class="bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-md overflow-hidden relative">

        <!-- Header Section with High-Impact Gradient & Solid Navy Fallback -->
        <div class="bg-slate-900 bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-950 text-white p-6 sm:p-8 relative overflow-hidden" style="background-color: #0f172a;">
            <!-- Ambient Highlights -->
            <div class="absolute -top-24 -right-24 w-80 h-80 bg-primary/25 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="max-w-2xl space-y-2">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-white/15 text-sky-200 border border-white/20 backdrop-blur-xs">
                            <span class="material-symbols-outlined text-[15px]">auto_awesome</span>
                            <span>AI Demand Forecasting Engine</span>
                        </span>
                        <span id="active-season-status-pill" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider <?= $isSeasonActive ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-amber-500/20 text-amber-300 border border-amber-500/40' ?>">
                            <span class="w-1.5 h-1.5 rounded-full <?= $isSeasonActive ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400' ?>"></span>
                            <span id="active-season-status-text"><?= $isSeasonActive ? 'Active Calendar Cycle' : 'Simulation Mode' ?></span>
                        </span>
                    </div>

                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                        <span id="hub-season-icon" class="material-symbols-outlined text-[28px] text-sky-300"><?= esc($seasonDef['icon'] ?? 'school') ?></span>
                        <span id="hub-season-title"><?= esc($seasonDef['name'] ?? 'Seasonal Rush') ?></span>
                    </h2>

                    <p id="hub-season-description" class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        <?= esc($seasonDef['description'] ?? 'Local demand surges during this operational cycle. Replenish high-velocity items to maximize sales.') ?>
                    </p>

                    <!-- Cohere Generative AI Strategic Advisory Banner -->
                    <div id="cohere-advisory-box" class="<?= !empty($seasonal_data['cohere_summary']) ? '' : 'hidden' ?> mt-3 p-3.5 rounded-2xl bg-white/10 border border-sky-300/30 text-xs text-sky-100 backdrop-blur-xs flex items-start gap-2.5">
                        <span class="material-symbols-outlined text-amber-300 text-[20px] shrink-0 mt-0.5">smart_toy</span>
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-white uppercase tracking-wider text-[10px]">Cohere AI Strategic Retail Advisory</span>
                                <span class="px-1.5 py-0.5 rounded-full bg-primary text-white text-[9px] font-mono">command-a</span>
                            </div>
                            <p id="cohere-executive-summary" class="text-xs text-slate-200 leading-relaxed">
                                <?= esc($seasonal_data['cohere_summary'] ?? '') ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right Velocity & Period Badge Group -->
                <div class="flex lg:flex-col items-center lg:items-end gap-3 shrink-0">
                    <div class="bg-white/10 border border-white/15 rounded-2xl px-4 py-2.5 backdrop-blur-xs text-right">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-300 block">Demand Multiplier</span>
                        <span id="hub-season-multiplier" class="text-xl font-black text-amber-300"><?= number_format((float) ($seasonDef['velocity_multiplier'] ?? 2.0), 1) ?>x Velocity</span>
                    </div>
                    <span id="hub-season-period" class="text-xs text-slate-300 font-medium bg-black/30 px-3 py-1.5 rounded-xl border border-white/10">
                        <?= esc($seasonDef['period_label'] ?? '') ?>
                    </span>
                </div>
            </div>

            <!-- Interactive Season Switcher Tabs -->
            <div class="relative z-10 mt-6 pt-5 border-t border-white/15">
                <div class="flex items-center justify-between gap-3 mb-2.5">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">tune</span>
                        <span>Simulate &amp; Explore Other Market Seasons</span>
                    </span>
                    <span class="text-[11px] text-slate-400">Click a season to project inventory requirements</span>
                </div>

                <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar" id="seasonal-tabs-container">
                    <?php foreach ($availableSeasons as $key => $s): ?>
                        <?php
                        $isCurrentTab = ($key === $activeKey);
                        $isCalendarCurrent = ($key === $detectedSeason);
                        ?>
                        <button type="button"
                                data-season-key="<?= esc($key) ?>"
                                class="season-tab-btn shrink-0 inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all <?= $isCurrentTab ? 'bg-white text-slate-900 shadow-md ring-2 ring-sky-300/80 scale-102' : 'bg-white/10 hover:bg-white/20 text-white border border-white/10' ?>">
                            <span class="material-symbols-outlined text-[16px] <?= $isCurrentTab ? 'text-primary' : 'text-slate-300' ?>"><?= esc($s['icon']) ?></span>
                            <span><?= esc($s['short_name']) ?></span>
                            <?php if ($isCalendarCurrent): ?>
                                <span class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-full <?= $isCurrentTab ? 'bg-emerald-600 text-white' : 'bg-emerald-400 text-slate-900' ?>">Active</span>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Dynamic Hub Content with Loading Overlay -->
        <div class="relative p-6 sm:p-8 space-y-6">

            <!-- Loading Spinner for AJAX Tab Switching -->
            <div id="seasonal-loading-overlay" class="hidden absolute inset-0 bg-surface-container-lowest/80 backdrop-blur-xs flex items-center justify-center z-20 transition-opacity">
                <div class="flex items-center gap-3 px-5 py-3 rounded-2xl bg-surface-container-lowest shadow-xl border border-outline-variant/30 text-primary text-xs font-bold">
                    <span class="material-symbols-outlined text-[20px] animate-spin">progress_activity</span>
                    <span>AI analyzing inventory &amp; seasonal demand...</span>
                </div>
            </div>

            <!-- Seasonal Metrics Overview Bar -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="p-4 rounded-2xl bg-surface-container border border-outline-variant/20">
                    <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider block">Demand Surge</span>
                    <span id="metric-surge" class="text-xl font-black text-on-surface mt-1 block">
                        +<?= round(((float) ($seasonDef['velocity_multiplier'] ?? 2.0) - 1.0) * 100) ?>%
                    </span>
                    <span class="text-[10px] text-on-surface-variant">Above normal monthly run rate</span>
                </div>

                <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20">
                    <span class="text-[11px] font-bold text-rose-700 uppercase tracking-wider block">Critical Stockouts</span>
                    <span id="metric-critical" class="text-xl font-black text-rose-700 mt-1 block">
                        <?= (int) ($seasonal_data['critical_stockout_count'] ?? 0) ?> items
                    </span>
                    <span class="text-[10px] text-rose-600">Stock ≤ 0 or under threshold</span>
                </div>

                <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20">
                    <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider block">Restock Recommended</span>
                    <span id="metric-restock-count" class="text-xl font-black text-amber-800 mt-1 block">
                        <?= (int) ($seasonal_data['items_needing_restock'] ?? 0) ?> items
                    </span>
                    <span class="text-[10px] text-amber-700">Below 60-day safety buffer</span>
                </div>

                <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20">
                    <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider block">Est. Revenue Uplift</span>
                    <span id="metric-opp-revenue" class="text-xl font-black text-emerald-800 mt-1 block">
                        ₱<?= number_format((float) ($seasonal_data['est_seasonal_opp_revenue'] ?? 0), 2) ?>
                    </span>
                    <span class="text-[10px] text-emerald-700">Protected by full replenishment</span>
                </div>
            </div>

            <!-- Recommendations Section Title & Table -->
            <div class="space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[20px]">inventory</span>
                            <span>AI Product Replenishment Recommendations</span>
                        </h3>
                        <p class="text-xs text-on-surface-variant">
                            Prioritized by stockout urgency, seasonal multiplier, and potential revenue uplift.
                        </p>
                    </div>

                    <div class="flex items-center gap-2 text-xs">
                        <span class="inline-flex items-center gap-1 font-bold text-rose-600">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <span>Critical</span>
                        </span>
                        <span class="inline-flex items-center gap-1 font-bold text-amber-600 ml-2">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>High Urgency</span>
                        </span>
                        <span class="inline-flex items-center gap-1 font-bold text-emerald-600 ml-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Healthy Buffer</span>
                        </span>
                    </div>
                </div>

                <!-- Product Recommendations Table (Desktop) / Cards (Mobile) -->
                <div class="rounded-2xl border border-outline-variant/30 overflow-hidden bg-surface-container-lowest">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[700px]" id="seasonal-recommendations-table">
                            <thead class="bg-surface-container-low text-[11px] uppercase tracking-wider font-bold text-on-surface-variant/80 border-b border-outline-variant/20">
                                <tr>
                                    <th class="px-5 py-3">Product Name &amp; Category</th>
                                    <th class="px-4 py-3 text-center">Current vs Target</th>
                                    <th class="px-4 py-3 text-center">AI Restock Order</th>
                                    <th class="px-4 py-3 text-center">Urgency Status</th>
                                    <th class="px-4 py-3 text-right">Est. Uplift</th>
                                    <th class="px-5 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/15 text-xs" id="seasonal-table-body">
                                <?php if (!empty($recommendations)): ?>
                                    <?php foreach ($recommendations as $idx => $r): ?>
                                        <?php
                                        $urgencyClass = match ($r['urgency']) {
                                            'critical' => 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950/40 dark:text-rose-300',
                                            'high'     => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/40 dark:text-amber-300',
                                            'moderate' => 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-950/40 dark:text-blue-300',
                                            default    => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300',
                                        };
                                        $barColor = match ($r['urgency']) {
                                            'critical' => 'bg-rose-500',
                                            'high'     => 'bg-amber-500',
                                            'moderate' => 'bg-blue-500',
                                            default    => 'bg-emerald-500',
                                        };
                                        ?>
                                        <tr class="hover:bg-surface-container-high/40 transition-colors group">
                                            <!-- Product Info -->
                                            <td class="px-5 py-3.5">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center shrink-0 overflow-hidden border border-outline-variant/20">
                                                        <?php if (!empty($r['image_url'])): ?>
                                                            <img src="<?= esc($r['image_url']) ?>" alt="<?= esc($r['name']) ?>" class="w-full h-full object-cover">
                                                        <?php else: ?>
                                                            <span class="material-symbols-outlined text-[20px] text-primary">inventory_2</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <span class="font-bold text-on-surface text-xs block truncate max-w-xs" title="<?= esc($r['name']) ?>">
                                                            <?= esc($r['name']) ?>
                                                        </span>
                                                        <div class="flex items-center gap-2 mt-0.5">
                                                            <span class="text-[10px] text-on-surface-variant font-medium"><?= esc($r['category']) ?></span>
                                                            <span class="text-[10px] text-on-surface-variant/50">&bull;</span>
                                                            <span class="text-[10px] text-on-surface-variant font-mono"><?= esc($r['sku']) ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Current vs Target Buffer -->
                                            <td class="px-4 py-3.5 text-center">
                                                <div class="inline-block text-left w-36">
                                                    <div class="flex items-center justify-between text-[11px] mb-1">
                                                        <span class="font-extrabold text-on-surface"><?= $r['current_stock'] ?></span>
                                                        <span class="text-on-surface-variant text-[10px]">Buffer: <?= $r['target_buffer'] ?> pcs</span>
                                                    </div>
                                                    <div class="w-full bg-surface-container-highest h-2 rounded-full overflow-hidden">
                                                        <div class="h-full rounded-full <?= $barColor ?> transition-all duration-300" style="width: <?= $r['stock_pct'] ?>%"></div>
                                                    </div>
                                                    <span class="text-[10px] text-on-surface-variant mt-1 block">
                                                        <?php if ($r['current_stock'] <= 0): ?>
                                                            <strong class="text-rose-600 font-bold">Depleted</strong>
                                                        <?php else: ?>
                                                            ~<?= $r['days_remaining'] ?> days remaining
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <!-- AI Restock Order -->
                                            <td class="px-4 py-3.5 text-center">
                                                <?php if ($r['restock_units'] > 0): ?>
                                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-black bg-primary/10 text-primary border border-primary/20">
                                                        <span class="material-symbols-outlined text-[14px]">add</span>
                                                        <span><?= $r['restock_units'] ?> pcs</span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[11px] font-semibold text-emerald-700 bg-emerald-50">
                                                        <span class="material-symbols-outlined text-[13px]">check</span>
                                                        <span>Optimal</span>
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Urgency Status -->
                                            <td class="px-4 py-3.5 text-center">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border uppercase tracking-wider <?= $urgencyClass ?>">
                                                    <span class="w-1.5 h-1.5 rounded-full <?= $r['urgency'] === 'critical' ? 'bg-rose-500 animate-pulse' : ($r['urgency'] === 'high' ? 'bg-amber-500' : 'bg-emerald-500') ?>"></span>
                                                    <span><?= esc(humanize_status($r['urgency'])) ?></span>
                                                </span>
                                            </td>

                                            <!-- Est Revenue Uplift -->
                                            <td class="px-4 py-3.5 text-right font-bold text-on-surface">
                                                ₱<?= number_format((float) $r['est_revenue_uplift'], 2) ?>
                                            </td>

                                            <!-- Action & AI Insight Drawer -->
                                            <td class="px-5 py-3.5 text-right">
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <!-- AI Rationale Popover Button -->
                                                    <button type="button"
                                                            onclick="toggleAiReason(<?= $idx ?>)"
                                                            class="p-1.5 rounded-xl hover:bg-surface-container text-on-surface-variant hover:text-primary transition-colors"
                                                            title="View AI Strategic Rationale">
                                                        <span class="material-symbols-outlined text-[18px]">psychology</span>
                                                    </button>
                                                    <a href="<?= base_url('tenant/inventory?search=' . urlencode($r['name'])) ?>"
                                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-primary hover:bg-primary/90 text-on-primary text-xs font-bold transition-all shadow-xs active:scale-95">
                                                        <span>Restock</span>
                                                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Expandable AI Strategic Rationale Row -->
                                        <tr id="ai-reason-row-<?= $idx ?>" class="hidden bg-surface-container-low/60 border-t-0">
                                            <td colspan="6" class="px-5 py-3">
                                                <div class="flex items-start gap-2.5 p-3 rounded-xl bg-surface-container-lowest border border-outline-variant/30 text-xs">
                                                    <span class="material-symbols-outlined text-[20px] text-primary shrink-0 mt-0.5">auto_awesome</span>
                                                    <div>
                                                        <span class="font-bold text-on-surface block mb-0.5">AI Strategic Forecast Rationale</span>
                                                        <p class="text-on-surface-variant text-[11px] leading-relaxed">
                                                            <?= esc($r['ai_rationale']) ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="py-10 text-center text-on-surface-variant">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <span class="material-symbols-outlined text-4xl text-emerald-500">task_alt</span>
                                                <p class="font-bold text-on-surface text-sm">Inventory Fully Prepared</p>
                                                <p class="text-xs text-on-surface-variant max-w-sm">No items in your catalog require urgent restock for the <?= esc($seasonDef['short_name'] ?? 'selected') ?> cycle.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Seasonal Expansion Suggestions (High-Demand Items Not Yet In Catalog) -->
            <div class="pt-4 border-t border-outline-variant/20">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div class="flex items-center gap-2">
                        <span class="p-1 rounded-lg bg-amber-500/10 text-amber-600">
                            <span class="material-symbols-outlined text-[18px]">lightbulb</span>
                        </span>
                        <div>
                            <h4 class="text-sm font-bold text-on-surface">Marketplace Catalog Expansion Ideas</h4>
                            <p class="text-[11px] text-on-surface-variant">High-demand items in Polomolok for <?= esc($seasonDef['short_name'] ?? 'this season') ?> not yet stocked in your store.</p>
                        </div>
                    </div>
                    <a href="<?= base_url('tenant/inventory') ?>" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                        <span>+ Add New Product</span>
                        <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3" id="seasonal-expansion-container">
                    <?php if (!empty($expansionIdeas)): ?>
                        <?php foreach ($expansionIdeas as $idea): ?>
                            <div class="bg-surface-container rounded-2xl p-4 border border-outline-variant/25 flex flex-col justify-between hover:border-primary/40 transition-all">
                                <div>
                                    <div class="flex items-start justify-between gap-2 mb-1.5">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70"><?= esc($idea['category']) ?></span>
                                        <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            <?= esc($idea['demand_surge']) ?>
                                        </span>
                                    </div>
                                    <h5 class="text-xs font-bold text-on-surface"><?= esc($idea['name']) ?></h5>
                                    <p class="text-[11px] text-on-surface-variant mt-1.5 leading-relaxed"><?= esc($idea['reason']) ?></p>
                                </div>
                                <div class="mt-3 pt-2.5 border-t border-outline-variant/20 flex items-center justify-between">
                                    <span class="text-xs font-bold text-on-surface">₱<?= number_format((float) $idea['est_price'], 2) ?> <span class="text-[10px] font-normal text-on-surface-variant">sugg. price</span></span>
                                    <a href="<?= base_url('tenant/inventory') ?>" class="text-xs font-bold text-primary hover:underline flex items-center gap-0.5">
                                        <span>Stock Item</span>
                                        <span class="material-symbols-outlined text-[13px]">add</span>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- ================================================================= -->
    <!-- REVENUE OVER TIME (Interactive Multi-Period Chart)                 -->
    <!-- ================================================================= -->
    <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 shadow-xs border border-outline-variant/30 space-y-4">
        <div class="flex flex-wrap justify-between items-center gap-4">
            <div>
                <h3 class="text-lg font-bold text-on-surface">Revenue Over Time</h3>
                <p class="text-xs text-on-surface-variant">Gross revenue from completed retail transactions and finished printing orders.</p>
            </div>
            <div class="flex items-center gap-1 bg-surface-container p-1 rounded-xl">
                <?php foreach ($ranges as $key => $label): ?>
                    <button type="button" data-range="<?= esc($key) ?>"
                            class="analytics-range-btn px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all <?= $range === $key ? 'bg-primary text-on-primary shadow-xs' : 'text-on-surface-variant hover:bg-surface-container-high' ?>"><?= esc($label) ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Metric Comparison Badges -->
        <div class="flex flex-wrap items-center justify-between gap-4 pb-3 border-b border-outline-variant/20">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2">
                    <span class="w-3.5 h-3.5 rounded-full bg-primary ring-4 ring-primary/20"></span>
                    <div>
                        <span class="text-[10px] text-on-surface-variant font-medium block uppercase tracking-wider">Current Period</span>
                        <span id="analytics-current-total" class="text-lg font-black text-on-surface">₱<?= number_format((float) $chart_total, 2) ?></span>
                    </div>
                </div>
                <div id="analytics-prev-container" class="flex items-center gap-2 <?= (float) $chart_previous_total > 0 ? '' : 'hidden' ?>">
                    <span class="w-3.5 h-3.5 rounded-full bg-outline-variant ring-4 ring-outline-variant/20"></span>
                    <div>
                        <span class="text-[10px] text-on-surface-variant font-medium block uppercase tracking-wider">Previous Period</span>
                        <span id="analytics-prev-total" class="text-lg font-black text-on-surface-variant">₱<?= number_format((float) $chart_previous_total, 2) ?></span>
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
                <span id="analytics-growth-badge" class="inline-flex items-center gap-1 text-xs font-bold px-3 py-1 rounded-full <?= (float) $chart_previous_total > 0 ? ($growthPct >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') : 'hidden' ?>">
                    <span id="analytics-growth-icon" class="material-symbols-outlined text-[16px]"><?= $growthPct >= 0 ? 'trending_up' : 'trending_down' ?></span>
                    <span id="analytics-growth-text"><?= $growthPct >= 0 ? '+' : '' ?><?= $growthPct ?>% vs prev</span>
                </span>
            </div>
        </div>

        <!-- Inline error alert -->
        <div id="analytics-error" class="hidden p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">error</span>
                <span id="analytics-error-msg">Failed to refresh analytics data.</span>
            </div>
            <button type="button" id="analytics-retry-btn" class="font-bold underline ml-2 hover:text-rose-950">Retry</button>
        </div>

        <!-- Interactive Line Chart Container -->
        <div id="chart-wrapper" class="relative w-full min-h-[300px] h-80 rounded-2xl bg-surface-container-low/20 p-2 overflow-hidden" style="min-height: 300px; height: 320px;">
            <div id="analytics-loading" class="hidden absolute inset-0 rounded-2xl bg-surface-container-lowest/70 backdrop-blur-xs flex items-center justify-center z-20 transition-opacity">
                <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-surface-container-lowest shadow-md border border-outline-variant/30 text-primary text-xs font-semibold">
                    <span class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                    <span>Updating sales telemetry...</span>
                </div>
            </div>
            <canvas id="revenueLineChart" class="w-full h-full block transition-opacity duration-200"></canvas>
            <div id="revenueTooltip" class="hidden absolute z-30 pointer-events-none whitespace-nowrap text-xs font-medium px-3 py-2 rounded-xl shadow-xl bg-slate-900/95 text-slate-100 border border-slate-700/60 backdrop-blur-xs transition-[opacity,transform] duration-75"></div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- SALES BY CATEGORY & ORDER STATUS DISTRIBUTION                     -->
    <!-- ================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Sales by Category -->
        <div class="bg-surface-container-lowest rounded-3xl p-6 shadow-xs border border-outline-variant/30 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between gap-3 mb-1">
                    <h3 class="text-base font-bold text-on-surface">Sales by Category</h3>
                    <span class="text-xs font-bold text-on-surface-variant/80"><?= count($category_sales) ?> Categories</span>
                </div>
                <p class="text-xs text-on-surface-variant mb-5">Revenue composition across merchandise classifications.</p>

                <?php if (!empty($category_sales)): ?>
                    <div class="space-y-3">
                        <?php foreach ($category_sales as $i => $c): ?>
                            <?php $catPct = (float) $c['pct']; ?>
                            <div class="p-3 rounded-2xl bg-surface-container/30 hover:bg-surface-container/60 transition-all border border-outline-variant/15 space-y-1.5">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-on-surface font-bold truncate max-w-xs"><?= esc($c['category']) ?></span>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="font-extrabold text-on-surface">₱<?= number_format((float) $c['revenue'], 2) ?></span>
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-primary/10 text-primary">
                                            <?= number_format($catPct, 1) ?>%
                                        </span>
                                    </div>
                                </div>
                                <div class="h-2 bg-surface-container-highest rounded-full overflow-hidden">
                                    <div class="h-full rounded-full <?= $catColors[$i % count($catColors)] ?> transition-all duration-500" style="width: <?= min(100, max(0, $catPct)) ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="py-12 text-center text-on-surface-variant flex flex-col items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-4xl text-on-surface-variant/40">category</span>
                        <p class="text-xs text-on-surface-variant">Not enough category data recorded yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Status Distribution -->
        <div class="bg-surface-container-lowest rounded-3xl p-6 shadow-xs border border-outline-variant/30 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between gap-3 mb-1">
                    <h3 class="text-base font-bold text-on-surface">Order Status Distribution</h3>
                    <?php if ($status_total > 0): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-extrabold bg-emerald-500/10 text-emerald-700 border border-emerald-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span><?= $fulfilledRate ?>% Fulfilled</span>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-on-surface-variant mb-5">Fulfillment workflow status across all recorded customer orders.</p>

                <?php if ($status_total > 0): ?>
                    <div class="flex flex-col sm:flex-row items-center gap-6 lg:gap-7">
                        <!-- Donut Visual with Concentric Ring -->
                        <div class="relative w-40 h-40 shrink-0 mx-auto sm:mx-0">
                            <div class="w-40 h-40 rounded-full shadow-sm ring-4 ring-surface-container/60 transition-transform duration-300" style="background: <?= $conic ?>"></div>
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <div class="w-28 h-28 rounded-full bg-surface-container-lowest flex flex-col items-center justify-center shadow-md border border-outline-variant/20">
                                    <span class="text-xl font-black text-on-surface tracking-tight"><?= number_format((int) $status_total) ?></span>
                                    <span class="text-[9px] text-on-surface-variant uppercase font-bold tracking-widest mt-0.5">Orders</span>
                                    <span class="text-[9px] font-bold text-emerald-600 mt-0.5"><?= $fulfilledRate ?>% Done</span>
                                </div>
                            </div>
                        </div>

                        <!-- Structured Compact Status Rows -->
                        <div class="flex-1 w-full space-y-1.5">
                            <?php foreach ($status_dist as $s): ?>
                                <?php
                                $col = $statusColors[$s['status']] ?? '#64748b';
                                $badgeStyle = $statusBgLight[$s['status']] ?? 'bg-surface-container text-on-surface';
                                $pct = (float) $s['pct'];
                                ?>
                                <div class="group px-3 py-2 rounded-xl bg-surface-container/40 hover:bg-surface-container transition-all border border-outline-variant/15 flex items-center justify-between gap-3 text-xs">
                                    <!-- Left: Color Indicator & Status Name -->
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="w-3 h-3 rounded-full shrink-0 shadow-2xs" style="background: <?= $col ?>"></span>
                                        <span class="font-bold text-on-surface truncate"><?= esc(humanize_status($s['status'])) ?></span>
                                    </div>

                                    <!-- Right: Count & Percentage Badges -->
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="font-bold text-on-surface text-xs"><?= number_format((int) $s['count']) ?></span>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold border <?= $badgeStyle ?>">
                                            <?= number_format($pct, 1) ?>%
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="py-12 text-center text-on-surface-variant flex flex-col items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-4xl text-on-surface-variant/40">shopping_bag</span>
                        <p class="text-xs text-on-surface-variant">No customer orders recorded yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- TOP PERFORMING PRODUCTS TABLE                                     -->
    <!-- ================================================================= -->
    <div class="bg-surface-container-lowest rounded-3xl overflow-hidden shadow-xs border border-outline-variant/30">
        <div class="p-6 border-b border-outline-variant/20 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-base font-bold text-on-surface">Top Performing Products</h3>
                <p class="text-xs text-on-surface-variant">Ranked by gross revenue generated across confirmed orders.</p>
            </div>
            <a href="<?= base_url('tenant/inventory') ?>" class="text-xs font-bold text-primary hover:underline flex items-center gap-1 self-start sm:self-auto">
                <span>View Full Catalog</span>
                <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[600px]">
                <thead class="bg-surface-container-low text-[11px] uppercase tracking-wider font-bold text-on-surface-variant/80">
                    <tr>
                        <th class="px-6 py-3.5">Product Name</th>
                        <th class="px-4 py-3.5 text-right">Units Sold</th>
                        <th class="px-4 py-3.5 text-right">Revenue Generated</th>
                        <th class="px-6 py-3.5 text-right">Stock Health</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/15 text-xs">
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
                            <tr class="hover:bg-surface-container-high/40 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-surface-container flex items-center justify-center text-primary shrink-0">
                                            <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                                        </div>
                                        <span class="font-bold text-on-surface truncate max-w-sm"><?= esc($tp['name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-on-surface text-right font-medium"><?= number_format((int) $tp['units']) ?> pcs</td>
                                <td class="px-4 py-4 font-black text-on-surface text-right">₱<?= number_format((float) $tp['revenue'], 2) ?></td>
                                <td class="px-6 py-4 text-right">
                                    <?php if ($stockKey !== null): ?>
                                        <?= status_badge($stockKey) ?>
                                    <?php else: ?>
                                        <span class="text-on-surface-variant">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="py-10 text-center text-on-surface-variant">Not enough transactions recorded yet.</td>
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
// Toggle AI Strategic Rationale drawer
function toggleAiReason(idx) {
    const row = document.getElementById('ai-reason-row-' + idx);
    if (row) {
        row.classList.toggle('hidden');
    }
}

// -------------------------------------------------------------
// Interactive AI Seasonal Switcher (AJAX)
// -------------------------------------------------------------
(function () {
    const seasonalEndpoint = '<?= site_url('tenant/analytics/seasonal') ?>';
    const inventoryBaseUrl = '<?= site_url('tenant/inventory') ?>';
    const tabButtons = document.querySelectorAll('.season-tab-btn');
    const loadingOverlay = document.getElementById('seasonal-loading-overlay');
    const tableBody = document.getElementById('seasonal-table-body');
    const expansionContainer = document.getElementById('seasonal-expansion-container');

    const hubSeasonIcon = document.getElementById('hub-season-icon');
    const hubSeasonTitle = document.getElementById('hub-season-title');
    const hubSeasonDesc = document.getElementById('hub-season-description');
    const hubSeasonMultiplier = document.getElementById('hub-season-multiplier');
    const hubSeasonPeriod = document.getElementById('hub-season-period');
    const activeStatusPill = document.getElementById('active-season-status-pill');
    const activeStatusText = document.getElementById('active-season-status-text');

    const metricSurge = document.getElementById('metric-surge');
    const metricCritical = document.getElementById('metric-critical');
    const metricRestockCount = document.getElementById('metric-restock-count');
    const metricOppRevenue = document.getElementById('metric-opp-revenue');

    function formatMoney(v) {
        return '₱' + Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    async function switchSeason(seasonKey, clickedBtn) {
        if (!seasonKey) return;

        // Update button styles
        tabButtons.forEach(btn => {
            btn.classList.remove('bg-white', 'text-slate-900', 'shadow-md', 'ring-2', 'ring-sky-300/80', 'scale-102');
            btn.classList.add('bg-white/10', 'text-white', 'border', 'border-white/10');
            const icon = btn.querySelector('.material-symbols-outlined');
            if (icon) {
                icon.classList.remove('text-primary');
                icon.classList.add('text-slate-300');
            }
        });

        if (clickedBtn) {
            clickedBtn.classList.remove('bg-white/10', 'text-white', 'border', 'border-white/10');
            clickedBtn.classList.add('bg-white', 'text-slate-900', 'shadow-md', 'ring-2', 'ring-sky-300/80', 'scale-102');
            const icon = clickedBtn.querySelector('.material-symbols-outlined');
            if (icon) {
                icon.classList.remove('text-slate-300');
                icon.classList.add('text-primary');
            }
        }

        if (loadingOverlay) loadingOverlay.classList.remove('hidden');

        try {
            const res = await fetch(`${seasonalEndpoint}?season=${encodeURIComponent(seasonKey)}&_t=${Date.now()}`, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();

            if (!data || !data.season) {
                throw new Error('Invalid seasonal payload');
            }

            // Update Hub Header
            const s = data.season;
            if (hubSeasonIcon) hubSeasonIcon.textContent = s.icon || 'school';
            if (hubSeasonTitle) hubSeasonTitle.textContent = s.name || s.short_name;
            if (hubSeasonDesc) hubSeasonDesc.textContent = s.description || '';
            if (hubSeasonMultiplier) hubSeasonMultiplier.textContent = (Number(s.velocity_multiplier) || 2.0).toFixed(1) + 'x Velocity';
            if (hubSeasonPeriod) hubSeasonPeriod.textContent = s.period_label || '';

            // Update Active Status Pill
            const isActive = !!data.is_currently_active;
            if (activeStatusPill) {
                if (isActive) {
                    activeStatusPill.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/40';
                    activeStatusPill.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span><span>Active Calendar Cycle</span>';
                } else {
                    activeStatusPill.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-500/40';
                    activeStatusPill.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span><span>Simulation Mode</span>';
                }
            }

            // Update Cohere AI Executive Advisory
            const cohereBox = document.getElementById('cohere-advisory-box');
            const cohereSummaryEl = document.getElementById('cohere-executive-summary');
            if (data.cohere_summary) {
                if (cohereSummaryEl) cohereSummaryEl.textContent = data.cohere_summary;
                if (cohereBox) cohereBox.classList.remove('hidden');
            } else {
                if (cohereBox) cohereBox.classList.add('hidden');
            }

            // Update Metrics Bar
            const mult = Number(s.velocity_multiplier) || 2.0;
            if (metricSurge) metricSurge.textContent = '+' + Math.round((mult - 1) * 100) + '%';
            if (metricCritical) metricCritical.textContent = (data.critical_stockout_count || 0) + ' items';
            if (metricRestockCount) metricRestockCount.textContent = (data.items_needing_restock || 0) + ' items';
            if (metricOppRevenue) metricOppRevenue.textContent = formatMoney(data.est_seasonal_opp_revenue);

            // Re-render Recommendations Table
            if (tableBody) {
                const recs = data.recommendations || [];
                if (recs.length === 0) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="py-10 text-center text-on-surface-variant">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-emerald-500">task_alt</span>
                                    <p class="font-bold text-on-surface text-sm">Inventory Fully Prepared</p>
                                    <p class="text-xs text-on-surface-variant max-w-sm">No items in your catalog require urgent restock for the ${escapeHtml(s.short_name)} cycle.</p>
                                </div>
                            </td>
                        </tr>
                    `;
                } else {
                    let html = '';
                    recs.forEach((r, idx) => {
                        let urgencyClass = 'bg-emerald-100 text-emerald-800 border-emerald-300';
                        let barColor = 'bg-emerald-500';
                        let dotColor = 'bg-emerald-500';
                        if (r.urgency === 'critical') {
                            urgencyClass = 'bg-rose-100 text-rose-800 border-rose-300';
                            barColor = 'bg-rose-500';
                            dotColor = 'bg-rose-500 animate-pulse';
                        } else if (r.urgency === 'high') {
                            urgencyClass = 'bg-amber-100 text-amber-800 border-amber-300';
                            barColor = 'bg-amber-500';
                            dotColor = 'bg-amber-500';
                        } else if (r.urgency === 'moderate') {
                            urgencyClass = 'bg-blue-100 text-blue-800 border-blue-300';
                            barColor = 'bg-blue-500';
                            dotColor = 'bg-blue-500';
                        }

                        const stockNote = (r.current_stock <= 0)
                            ? '<strong class="text-rose-600 font-bold">Depleted</strong>'
                            : `~${r.days_remaining} days remaining`;

                        const restockPill = (r.restock_units > 0)
                            ? `<span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-black bg-primary/10 text-primary border border-primary/20">
                                   <span class="material-symbols-outlined text-[14px]">add</span>
                                   <span>${r.restock_units} pcs</span>
                               </span>`
                            : `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[11px] font-semibold text-emerald-700 bg-emerald-50">
                                   <span class="material-symbols-outlined text-[13px]">check</span>
                                   <span>Optimal</span>
                               </span>`;

                        const imgHtml = r.image_url
                            ? `<img src="${escapeHtml(r.image_url)}" alt="${escapeHtml(r.name)}" class="w-full h-full object-cover">`
                            : `<span class="material-symbols-outlined text-[20px] text-primary">inventory_2</span>`;

                        html += `
                            <tr class="hover:bg-surface-container-high/40 transition-colors group">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center shrink-0 overflow-hidden border border-outline-variant/20">
                                            ${imgHtml}
                                        </div>
                                        <div class="min-w-0">
                                            <span class="font-bold text-on-surface text-xs block truncate max-w-xs" title="${escapeHtml(r.name)}">${escapeHtml(r.name)}</span>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[10px] text-on-surface-variant font-medium">${escapeHtml(r.category)}</span>
                                                <span class="text-[10px] text-on-surface-variant/50">&bull;</span>
                                                <span class="text-[10px] text-on-surface-variant font-mono">${escapeHtml(r.sku)}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <div class="inline-block text-left w-36">
                                        <div class="flex items-center justify-between text-[11px] mb-1">
                                            <span class="font-extrabold text-on-surface">${r.current_stock}</span>
                                            <span class="text-on-surface-variant text-[10px]">Buffer: ${r.target_buffer} pcs</span>
                                        </div>
                                        <div class="w-full bg-surface-container-highest h-2 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full ${barColor} transition-all duration-300" style="width: ${r.stock_pct}%"></div>
                                        </div>
                                        <span class="text-[10px] text-on-surface-variant mt-1 block">${stockNote}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-center">${restockPill}</td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border uppercase tracking-wider ${urgencyClass}">
                                        <span class="w-1.5 h-1.5 rounded-full ${dotColor}"></span>
                                        <span>${escapeHtml(r.urgency)}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right font-bold text-on-surface">${formatMoney(r.est_revenue_uplift)}</td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" onclick="toggleAiReason(${idx})" class="p-1.5 rounded-xl hover:bg-surface-container text-on-surface-variant hover:text-primary transition-colors" title="View AI Strategic Rationale">
                                            <span class="material-symbols-outlined text-[18px]">psychology</span>
                                        </button>
                                        <a href="${inventoryBaseUrl}?search=${encodeURIComponent(r.name)}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-primary hover:bg-primary/90 text-on-primary text-xs font-bold transition-all shadow-xs active:scale-95">
                                            <span>Restock</span>
                                            <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr id="ai-reason-row-${idx}" class="hidden bg-surface-container-low/60 border-t-0">
                                <td colspan="6" class="px-5 py-3">
                                    <div class="flex items-start gap-2.5 p-3 rounded-xl bg-surface-container-lowest border border-outline-variant/30 text-xs">
                                        <span class="material-symbols-outlined text-[20px] text-primary shrink-0 mt-0.5">auto_awesome</span>
                                        <div>
                                            <span class="font-bold text-on-surface block mb-0.5">AI Strategic Forecast Rationale</span>
                                            <p class="text-on-surface-variant text-[11px] leading-relaxed">${escapeHtml(r.ai_rationale)}</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    tableBody.innerHTML = html;
                }
            }

            // Re-render Catalog Expansion Ideas
            if (expansionContainer) {
                const ideas = data.expansion_ideas || [];
                let ideasHtml = '';
                ideas.forEach(idea => {
                    ideasHtml += `
                        <div class="bg-surface-container rounded-2xl p-4 border border-outline-variant/25 flex flex-col justify-between hover:border-primary/40 transition-all">
                            <div>
                                <div class="flex items-start justify-between gap-2 mb-1.5">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">${escapeHtml(idea.category)}</span>
                                    <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        ${escapeHtml(idea.demand_surge)}
                                    </span>
                                </div>
                                <h5 class="text-xs font-bold text-on-surface">${escapeHtml(idea.name)}</h5>
                                <p class="text-[11px] text-on-surface-variant mt-1.5 leading-relaxed">${escapeHtml(idea.reason)}</p>
                            </div>
                            <div class="mt-3 pt-2.5 border-t border-outline-variant/20 flex items-center justify-between">
                                <span class="text-xs font-bold text-on-surface">${formatMoney(idea.est_price)} <span class="text-[10px] font-normal text-on-surface-variant">sugg. price</span></span>
                                <a href="${inventoryBaseUrl}" class="text-xs font-bold text-primary hover:underline flex items-center gap-0.5">
                                    <span>Stock Item</span>
                                    <span class="material-symbols-outlined text-[13px]">add</span>
                                </a>
                            </div>
                        </div>
                    `;
                });
                expansionContainer.innerHTML = ideasHtml;
            }

        } catch (err) {
            console.error('Failed to switch season:', err);
        } finally {
            if (loadingOverlay) loadingOverlay.classList.add('hidden');
        }
    }

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const seasonKey = this.dataset.seasonKey;
            switchSeason(seasonKey, this);
        });
    });
})();

// -------------------------------------------------------------
// Multi-Period Revenue Over Time Chart (Chart.js + Native Canvas)
// -------------------------------------------------------------
(function () {
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

    async function switchAnalyticsRange(btn) {
        if (!btn) return;
        activeBtn = btn;
        const rangeVal = btn.dataset.range || '30';

        document.querySelectorAll('.analytics-range-btn').forEach(function (b) {
            b.classList.remove('bg-primary', 'text-on-primary', 'shadow-xs');
            b.classList.add('text-on-surface-variant');
        });
        btn.classList.add('bg-primary', 'text-on-primary', 'shadow-xs');
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

            if (currentTotalEl) {
                currentTotalEl.textContent = formatCurrency(data.total);
            }

            const prevTotal = Number(data.previous_total) || 0;
            if (prevTotal > 0) {
                if (prevTotalEl) prevTotalEl.textContent = formatCurrency(prevTotal);
                if (prevContainer) prevContainer.classList.remove('hidden');

                const growth = Number(data.growth_pct) || 0;
                if (growthBadge) {
                    growthBadge.className = 'inline-flex items-center gap-1 text-xs font-bold px-3 py-1 rounded-full ' + (growth >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800');
                    if (growthIcon) growthIcon.textContent = growth >= 0 ? 'trending_up' : 'trending_down';
                    if (growthText) growthText.textContent = (growth >= 0 ? '+' : '') + growth + '% vs prev';
                    growthBadge.classList.remove('hidden');
                }
            } else {
                if (prevContainer) prevContainer.classList.add('hidden');
                if (growthBadge) growthBadge.classList.add('hidden');
            }

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

    window.addEventListener('storage', function (e) {
        if (e.key === 'blax_last_sale' && e.newValue) {
            if (activeBtn) switchAnalyticsRange(activeBtn);
        }
    });

    setTimeout(initOrUpdateChart, 50);
})();
</script>
<?= $this->endSection() ?>