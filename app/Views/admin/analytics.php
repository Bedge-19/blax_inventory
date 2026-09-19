<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="space-y-lg">

    <!-- Top Command & Status Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-md bg-surface-container-lowest p-md sm:p-lg rounded-2xl border border-outline-variant/30 shadow-xs">
        <div>
            <div class="flex items-center gap-sm">
                <h2 class="text-title-lg sm:text-headline-sm font-black text-on-surface tracking-tight">Platform Analytics &amp; Intelligence</h2>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    Live Data
                </span>
            </div>
            <p class="text-xs text-on-surface-variant mt-0.5">Executive revenue, merchant volume, and Polomolok delivery operations</p>
        </div>
        <div class="flex items-center gap-sm">
            <span class="hidden md:inline-flex items-center gap-1 px-sm py-1.5 rounded-xl bg-surface-container-low text-on-surface-variant text-xs font-medium border border-outline-variant/20">
                <span class="material-symbols-outlined text-[16px] text-primary">location_on</span>
                Polomolok Zone: 6.10°–6.32° N
            </span>
            <a href="<?= base_url('admin/tracking') ?>" class="inline-flex items-center gap-1.5 px-md py-sm rounded-xl text-xs font-bold bg-primary text-on-primary hover:bg-primary/90 transition-all shadow-xs">
                <span class="material-symbols-outlined text-[18px]">two_wheeler</span>
                <span>Live Fleet Tracking</span>
            </a>
            <button type="button" id="btnRefreshAnalytics" onclick="refreshAnalyticsData()" class="p-2 rounded-xl text-on-surface-variant hover:bg-surface-container border border-outline-variant/30 transition-all" title="Refresh data">
                <span class="material-symbols-outlined text-[18px]" id="refreshIcon">refresh</span>
            </button>
        </div>
    </div>

    <!-- 4 High-Impact KPI Scorecards -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-md">
        <!-- Admin Net Revenue -->
        <div class="bg-surface-container-lowest rounded-2xl p-lg border border-outline-variant/30 shadow-xs hover:shadow-md transition-all duration-200 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-24 h-24 bg-primary/5 rounded-full blur-2xl -mr-6 -mt-6 group-hover:bg-primary/10 transition-all"></div>
            <div class="flex items-center justify-between mb-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Admin Revenue</span>
                <span class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">payments</span>
                </span>
            </div>
            <h3 class="text-headline-md font-black text-primary tracking-tight">₱<?= number_format((float) ($admin_revenue ?? 0), 2) ?></h3>
            <div class="mt-xs flex items-center justify-between text-xs text-on-surface-variant">
                <span>3.0% fee on GCash payouts</span>
                <span class="font-bold text-emerald-600 bg-emerald-500/10 px-1.5 py-0.5 rounded"><?= number_format((int) ($payout_count ?? 0)) ?> payouts</span>
            </div>
            <div class="mt-sm pt-xs border-t border-outline-variant/20 flex items-center justify-between text-[11px] text-on-surface-variant">
                <span>Average Fee per Payout</span>
                <span class="font-bold text-on-surface">₱<?= number_format((float) ($avg_fee ?? 0), 2) ?></span>
            </div>
        </div>

        <!-- Marketplace GMV -->
        <div class="bg-surface-container-lowest rounded-2xl p-lg border border-outline-variant/30 shadow-xs hover:shadow-md transition-all duration-200 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl -mr-6 -mt-6 group-hover:bg-emerald-500/10 transition-all"></div>
            <div class="flex items-center justify-between mb-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Marketplace GMV</span>
                <span class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">currency_exchange</span>
                </span>
            </div>
            <h3 class="text-headline-md font-black text-on-surface tracking-tight">₱<?= number_format((float) ($platform_gmv ?? 0), 2) ?></h3>
            <div class="mt-xs flex items-center justify-between text-xs text-on-surface-variant">
                <span>Total transacted volume</span>
                <span class="font-bold text-emerald-600 bg-emerald-500/10 px-1.5 py-0.5 rounded">All Channels</span>
            </div>
            <div class="mt-sm pt-xs border-t border-outline-variant/20 flex items-center justify-between text-[11px] text-on-surface-variant">
                <span>Avg. Order Value</span>
                <span class="font-bold text-on-surface">₱<?= ($total_orders ?? 0) > 0 ? number_format(($platform_gmv ?? 0) / $total_orders, 2) : '0.00' ?></span>
            </div>
        </div>

        <!-- Order Throughput & Volume -->
        <div class="bg-surface-container-lowest rounded-2xl p-lg border border-outline-variant/30 shadow-xs hover:shadow-md transition-all duration-200 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-24 h-24 bg-sky-500/5 rounded-full blur-2xl -mr-6 -mt-6 group-hover:bg-sky-500/10 transition-all"></div>
            <div class="flex items-center justify-between mb-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Total Orders</span>
                <span class="w-9 h-9 rounded-xl bg-sky-500/10 text-sky-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">local_shipping</span>
                </span>
            </div>
            <h3 class="text-headline-md font-black text-on-surface tracking-tight"><?= number_format((int) ($total_orders ?? 0)) ?></h3>
            <div class="mt-xs flex items-center justify-between text-xs text-on-surface-variant">
                <span>Orders across all merchants</span>
                <span class="font-bold text-sky-600 bg-sky-500/10 px-1.5 py-0.5 rounded"><?= number_format((int) ($active_deliveries_count ?? 0)) ?> Active Now</span>
            </div>
            <div class="mt-sm pt-xs border-t border-outline-variant/20 flex items-center justify-between text-[11px] text-on-surface-variant">
                <span>Fulfillment Split</span>
                <span class="font-bold text-on-surface">🛵 <?= esc($fulfillment_dist['delivery_pct'] ?? 0) ?>% Delivery · 🏪 <?= esc($fulfillment_dist['pickup_pct'] ?? 0) ?>% Pick-up</span>
            </div>
        </div>

        <!-- Merchant Network & Coverage -->
        <div class="bg-surface-container-lowest rounded-2xl p-lg border border-outline-variant/30 shadow-xs hover:shadow-md transition-all duration-200 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-500/5 rounded-full blur-2xl -mr-6 -mt-6 group-hover:bg-indigo-500/10 transition-all"></div>
            <div class="flex items-center justify-between mb-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Merchant Network</span>
                <span class="w-9 h-9 rounded-xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">storefront</span>
                </span>
            </div>
            <h3 class="text-headline-md font-black text-on-surface tracking-tight"><?= number_format((int) ($active_shops ?? 0)) ?></h3>
            <div class="mt-xs flex items-center justify-between text-xs text-on-surface-variant">
                <span>Active shops in Polomolok</span>
                <span class="font-bold text-indigo-600 bg-indigo-500/10 px-1.5 py-0.5 rounded"><?= number_format((int) ($total_shops ?? 0)) ?> registered</span>
            </div>
            <div class="mt-sm pt-xs border-t border-outline-variant/20 flex items-center justify-between text-[11px] text-on-surface-variant">
                <span>Ecosystem Composition</span>
                <span class="font-bold text-on-surface">🖨️ <?= (int) ($printing_shops ?? 0) ?> Print Hubs · 👥 <?= number_format((int) ($total_customers ?? 0)) ?> Customers</span>
            </div>
        </div>
    </section>

    <!-- Primary Charts & Distribution Section -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-md">
        <!-- Revenue Velocity Chart (2 Columns) -->
        <div class="lg:col-span-2 bg-surface-container-lowest rounded-2xl border border-outline-variant/30 shadow-xs p-md sm:p-lg flex flex-col justify-between">
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-sm mb-md">
                    <div>
                        <h3 class="text-title-md font-bold text-on-surface flex items-center gap-xs">
                            <span class="material-symbols-outlined text-primary text-[20px]">trending_up</span>
                            Admin Revenue Growth &amp; Fee Velocity
                        </h3>
                        <p class="text-xs text-on-surface-variant">Commission and transaction fees earned from verified merchant disbursements</p>
                    </div>
                    <!-- Range Switcher -->
                    <div class="inline-flex items-center gap-1 bg-surface-container-low p-1 rounded-xl border border-outline-variant/20 shrink-0 self-start sm:self-auto">
                        <button type="button" data-range="7" class="rangeBtn px-3 py-1 rounded-lg text-xs font-bold transition-all <?= ($range ?? '30') === '7' ? 'bg-primary text-on-primary shadow-xs' : 'text-on-surface-variant hover:text-on-surface' ?>">7 Days</button>
                        <button type="button" data-range="30" class="rangeBtn px-3 py-1 rounded-lg text-xs font-bold transition-all <?= ($range ?? '30') === '30' ? 'bg-primary text-on-primary shadow-xs' : 'text-on-surface-variant hover:text-on-surface' ?>">30 Days</button>
                        <button type="button" data-range="year" class="rangeBtn px-3 py-1 rounded-lg text-xs font-bold transition-all <?= ($range ?? '30') === 'year' ? 'bg-primary text-on-primary shadow-xs' : 'text-on-surface-variant hover:text-on-surface' ?>">This Year</button>
                    </div>
                </div>

                <!-- Micro KPI summary strip for the selected period -->
                <div class="grid grid-cols-3 gap-xs sm:gap-md p-sm bg-surface-container-low/60 rounded-xl border border-outline-variant/20 mb-md text-center">
                    <div>
                        <p class="text-[10px] sm:text-xs text-on-surface-variant uppercase font-bold">Period Total</p>
                        <p id="metric-period-total" class="text-sm sm:text-title-sm font-black text-primary">₱<?= number_format((float) ($chart_sum ?? 0), 2) ?></p>
                    </div>
                    <div class="border-x border-outline-variant/20">
                        <p class="text-[10px] sm:text-xs text-on-surface-variant uppercase font-bold">Daily Average</p>
                        <p id="metric-period-avg" class="text-sm sm:text-title-sm font-black text-on-surface">₱<?= number_format((float) ($chart_avg ?? 0), 2) ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] sm:text-xs text-on-surface-variant uppercase font-bold">Peak Day</p>
                        <p id="metric-period-peak" class="text-sm sm:text-title-sm font-black text-emerald-600">₱<?= number_format((float) ($chart_peak ?? 0), 2) ?></p>
                    </div>
                </div>

                <!-- High-DPI Interactive Canvas Chart -->
                <div id="analyticsChartWrap" class="relative h-72 sm:h-80 w-full bg-surface-container-lowest rounded-xl border border-outline-variant/20 p-2 sm:p-4">
                    <canvas id="analyticsChart" class="w-full h-full block cursor-crosshair"></canvas>
                    <div id="analyticsTooltip" class="hidden absolute z-30 pointer-events-none whitespace-nowrap text-xs font-medium px-3 py-2 rounded-xl shadow-xl bg-slate-900/95 text-slate-100 border border-slate-700/60 backdrop-blur-md transition-[opacity,transform] duration-75"></div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-sm mt-xs border-t border-outline-variant/10 text-[11px] text-on-surface-variant">
                <span>Hover data points for exact timestamps and settlement figures.</span>
                <span class="inline-flex items-center gap-1 font-semibold text-primary">
                    <span class="material-symbols-outlined text-[14px]">verified</span>
                    Audited Payouts
                </span>
            </div>
        </div>

        <!-- Real Marketplace Distribution (1 Column) -->
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 shadow-xs p-md sm:p-lg flex flex-col justify-between space-y-md">
            <div>
                <div class="flex items-center justify-between mb-sm">
                    <h3 class="text-title-md font-bold text-on-surface flex items-center gap-xs">
                        <span class="material-symbols-outlined text-secondary text-[20px]">pie_chart</span>
                        Channel &amp; Logistics Share
                    </h3>
                    <span class="text-[11px] font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-full">Live Stats</span>
                </div>
                <p class="text-xs text-on-surface-variant mb-md">Verified distribution across fulfillment and transaction methods</p>

                <!-- Section 1: Fulfillment Breakdown -->
                <div class="space-y-sm mb-lg">
                    <div class="flex items-center justify-between text-xs font-bold text-on-surface">
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px] text-sky-600">two_wheeler</span>
                            Doorstep Delivery
                        </span>
                        <span><?= esc($fulfillment_dist['delivery_pct'] ?? 0) ?>% <span class="font-normal text-on-surface-variant">(<?= number_format((int) ($fulfillment_dist['delivery_count'] ?? 0)) ?>)</span></span>
                    </div>
                    <div class="w-full bg-surface-container-high rounded-full h-2.5 overflow-hidden">
                        <div class="bg-primary h-full rounded-full transition-all duration-500" style="width: <?= max(4, min(100, (int) ($fulfillment_dist['delivery_pct'] ?? 0))) ?>%"></div>
                    </div>

                    <div class="flex items-center justify-between text-xs font-bold text-on-surface pt-xs">
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px] text-secondary">store</span>
                            Store Pick-up
                        </span>
                        <span><?= esc($fulfillment_dist['pickup_pct'] ?? 0) ?>% <span class="font-normal text-on-surface-variant">(<?= number_format((int) ($fulfillment_dist['pickup_count'] ?? 0)) ?>)</span></span>
                    </div>
                    <div class="w-full bg-surface-container-high rounded-full h-2.5 overflow-hidden">
                        <div class="bg-secondary h-full rounded-full transition-all duration-500" style="width: <?= max(4, min(100, (int) ($fulfillment_dist['pickup_pct'] ?? 0))) ?>%"></div>
                    </div>
                </div>

                <!-- Section 2: Payment Methods Share -->
                <div class="pt-sm border-t border-outline-variant/20 space-y-sm mb-md">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-xs">Payment Channels</h4>
                    
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-on-surface flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span> GCash Digital
                        </span>
                        <span class="font-bold text-on-surface"><?= esc($payment_dist['gcash']['pct'] ?? 0) ?>% <span class="font-normal text-on-surface-variant">(<?= number_format((int) ($payment_dist['gcash']['count'] ?? 0)) ?>)</span></span>
                    </div>
                    <div class="w-full bg-surface-container-high rounded-full h-2">
                        <div class="bg-blue-600 h-full rounded-full" style="width: <?= max(4, min(100, (int) ($payment_dist['gcash']['pct'] ?? 0))) ?>%"></div>
                    </div>

                    <div class="flex items-center justify-between text-xs pt-xs">
                        <span class="font-semibold text-on-surface flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span> Online (PayMongo)
                        </span>
                        <span class="font-bold text-on-surface"><?= esc($payment_dist['online']['pct'] ?? 0) ?>% <span class="font-normal text-on-surface-variant">(<?= number_format((int) ($payment_dist['online']['count'] ?? 0)) ?>)</span></span>
                    </div>
                    <div class="w-full bg-surface-container-high rounded-full h-2">
                        <div class="bg-emerald-600 h-full rounded-full" style="width: <?= max(4, min(100, (int) ($payment_dist['online']['pct'] ?? 0))) ?>%"></div>
                    </div>

                    <div class="flex items-center justify-between text-xs pt-xs">
                        <span class="font-semibold text-on-surface flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-500"></span> Cash on Delivery
                        </span>
                        <span class="font-bold text-on-surface"><?= esc($payment_dist['cash']['pct'] ?? 0) ?>% <span class="font-normal text-on-surface-variant">(<?= number_format((int) ($payment_dist['cash']['count'] ?? 0)) ?>)</span></span>
                    </div>
                    <div class="w-full bg-surface-container-high rounded-full h-2">
                        <div class="bg-slate-500 h-full rounded-full" style="width: <?= max(4, min(100, (int) ($payment_dist['cash']['pct'] ?? 0))) ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Ecosystem Summary Badge -->
            <div class="p-sm rounded-xl bg-surface-container-low border border-outline-variant/20 flex items-center justify-between text-xs">
                <span class="text-on-surface-variant">Merchant Specialization</span>
                <span class="font-bold text-primary"><?= (int) ($printing_shops ?? 0) ?> Printing Hubs · <?= max(0, (int) ($active_shops ?? 0) - (int) ($printing_shops ?? 0)) ?> Retail</span>
            </div>
        </div>
    </section>

    <!-- Secondary Section: Merchant Leaderboard & Polomolok Logistics -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-md">
        <!-- Top Performing Merchants Leaderboard (2 Columns) -->
        <div class="lg:col-span-2 bg-surface-container-lowest rounded-2xl border border-outline-variant/30 shadow-xs overflow-hidden">
            <div class="p-md sm:p-lg border-b border-outline-variant/20 flex flex-col sm:flex-row sm:items-center justify-between gap-xs">
                <div>
                    <h3 class="text-title-md font-bold text-on-surface flex items-center gap-xs">
                        <span class="material-symbols-outlined text-amber-500 text-[22px]">trophy</span>
                        Merchant Performance Leaderboard
                    </h3>
                    <p class="text-xs text-on-surface-variant">Top-ranked partner shops by volume and gross transaction value</p>
                </div>
                <a href="<?= base_url('admin/tenants') ?>" class="text-xs font-bold text-primary hover:underline inline-flex items-center gap-0.5">
                    View All Tenants
                    <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/60 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/20">
                            <th class="py-3 px-4">Rank &amp; Shop</th>
                            <th class="py-3 px-4 text-center">Plan</th>
                            <th class="py-3 px-4 text-center">Orders</th>
                            <th class="py-3 px-4 text-right">Volume (GMV)</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs divide-y divide-outline-variant/10">
                        <?php if (!empty($top_shops)): ?>
                            <?php 
                            $rank = 1;
                            $medals = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
                            foreach ($top_shops as $s): 
                                $medal = $medals[$rank] ?? "#{$rank}";
                            ?>
                                <tr class="hover:bg-surface-container-low/40 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-sm">
                                            <span class="w-7 text-center font-black text-sm <?= $rank <= 3 ? 'text-amber-500' : 'text-on-surface-variant' ?>"><?= $medal ?></span>
                                            <div class="w-9 h-9 rounded-xl bg-surface-container flex items-center justify-center font-bold text-xs text-primary border border-outline-variant/30 overflow-hidden shrink-0">
                                                <?php if (!empty($s['logo_url'])): ?>
                                                    <img src="<?= esc($s['logo_url']) ?>" alt="<?= esc($s['shop_name']) ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <?= esc(strtoupper(substr($s['shop_name'] ?? 'S', 0, 2))) ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <p class="font-bold text-on-surface text-sm"><?= esc($s['shop_name']) ?></p>
                                                <div class="flex items-center gap-1.5 mt-0.5 text-[11px] text-on-surface-variant">
                                                    <?php if (!empty($s['offers_printing'])): ?>
                                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded bg-tertiary/10 text-tertiary font-medium">
                                                            <span class="material-symbols-outlined text-[12px]">print</span> Print Hub
                                                        </span>
                                                    <?php endif; ?>
                                                    <span>Verified Partner</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-surface-container text-on-surface-variant border border-outline-variant/30">
                                            <?= esc($s['plan'] ?? 'standard') ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="font-black text-primary text-sm"><?= number_format((int) ($s['total_orders'] ?? 0)) ?></span>
                                        <span class="block text-[10px] text-on-surface-variant">orders</span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-black text-on-surface text-sm">
                                        ₱<?= number_format((float) ($s['total_gmv'] ?? 0), 2) ?>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <a href="<?= base_url('admin/tenants?search=' . urlencode($s['shop_name'])) ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold text-primary hover:bg-primary/10 transition-colors">
                                            Manage
                                            <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php $rank++; endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-8 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-3xl text-outline mb-1 block">store</span>
                                    No merchant order activity recorded yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Polomolok Delivery & Logistics Operations (1 Column) -->
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 shadow-xs p-md sm:p-lg flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-sm">
                    <h3 class="text-title-md font-bold text-on-surface flex items-center gap-xs">
                        <span class="material-symbols-outlined text-primary text-[20px]">near_me</span>
                        Polomolok Logistics Hub
                    </h3>
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 bg-emerald-500/10 px-2 py-0.5 rounded-full">
                        Bounded
                    </span>
                </div>
                <p class="text-xs text-on-surface-variant mb-md">All delivery coordinates constrained to Polomolok municipality</p>

                <!-- Core Zone Coordinates Box -->
                <div class="p-sm rounded-xl bg-surface-container-low border border-outline-variant/20 mb-md">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-bold text-on-surface flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px] text-primary">pin_drop</span>
                            Operational Zone
                        </span>
                        <span class="text-[11px] text-primary font-bold">Active &amp; Guarded</span>
                    </div>
                    <p class="text-[11px] text-on-surface-variant font-mono">Latitude: 6.1000° to 6.3200° N</p>
                    <p class="text-[11px] text-on-surface-variant font-mono">Longitude: 124.9500° to 125.1800° E</p>
                </div>

                <!-- Top Areas Breakdown -->
                <div class="space-y-sm mb-md">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-xs">Key Delivery Destinations</h4>
                    <?php if (!empty($area_counts)): ?>
                        <?php foreach ($area_counts as $area): ?>
                            <div class="flex items-center justify-between text-xs p-2 rounded-xl bg-surface-container-low/50 hover:bg-surface-container-low transition-colors">
                                <span class="font-semibold text-on-surface flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[16px] text-outline">location_city</span>
                                    <?= esc($area['area']) ?>
                                </span>
                                <span class="font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md">
                                    <?= number_format((int) $area['count']) ?> deliveries
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="flex items-center justify-between text-xs p-2 rounded-xl bg-surface-container-low/50">
                            <span class="font-semibold text-on-surface flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-outline">location_city</span>
                                Poblacion / Cannery Site
                            </span>
                            <span class="font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md">Polomolok Core</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CTA to Live Fleet Tracking -->
            <div class="pt-sm border-t border-outline-variant/20">
                <a href="<?= base_url('admin/tracking') ?>" class="w-full flex items-center justify-center gap-2 px-md py-2.5 rounded-xl text-xs font-bold bg-surface-container-low text-on-surface hover:bg-primary hover:text-on-primary border border-outline-variant/30 hover:border-primary transition-all group shadow-xs">
                    <span class="material-symbols-outlined text-[18px] text-primary group-hover:text-on-primary">map</span>
                    <span>Launch Live GPS Fleet Map</span>
                </a>
            </div>
        </div>
    </section>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function() {
    let labels = <?= json_encode($chart_labels ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
    let values = <?= json_encode($chart_values ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
    const canvas  = document.getElementById('analyticsChart'),
          wrap    = document.getElementById('analyticsChartWrap'),
          tooltip = document.getElementById('analyticsTooltip');
    
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let hoveredIdx = -1;

    function draw(activeIdx = -1) {
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        const w = rect.width, h = rect.height;
        const pad = { t: 20, r: 24, b: 32, l: 60 };
        const max = Math.max(...values, 1);
        const stepX = (w - pad.l - pad.r) / Math.max(labels.length - 1, 1);
        ctx.clearRect(0, 0, w, h);

        // Grid lines & labels
        ctx.strokeStyle = 'rgba(115, 118, 134, 0.10)';
        ctx.lineWidth = 1;
        ctx.fillStyle = '#64748b';
        ctx.font = '10px Inter, sans-serif';
        ctx.textAlign = 'right';

        for (let i = 0; i <= 4; i++) {
            const y = pad.t + (h - pad.t - pad.b) * (i / 4);
            ctx.beginPath();
            ctx.moveTo(pad.l, y);
            ctx.lineTo(w - pad.r, y);
            ctx.stroke();

            const val = max * (1 - i / 4);
            ctx.fillText('₱' + Math.round(val).toLocaleString(), pad.l - 8, y + 3);
        }

        // Plot points calculation
        const pts = values.map((v, i) => ({
            x: pad.l + i * stepX,
            y: pad.t + (h - pad.t - pad.b) * (1 - v / max),
            v: v,
            label: labels[i],
            index: i
        }));
        canvas._pts = pts;

        if (pts.length === 0) return;

        // Smooth curve path (Catmull-Rom or Bézier interpolation)
        function drawCurve(targetCtx) {
            targetCtx.beginPath();
            targetCtx.moveTo(pts[0].x, pts[0].y);
            for (let i = 0; i < pts.length - 1; i++) {
                const p0 = pts[Math.max(i - 1, 0)];
                const p1 = pts[i];
                const p2 = pts[i + 1];
                const p3 = pts[Math.min(i + 2, pts.length - 1)];

                const cp1x = p1.x + (p2.x - p0.x) / 6;
                const cp1y = p1.y + (p2.y - p0.y) / 6;
                const cp2x = p2.x - (p3.x - p1.x) / 6;
                const cp2y = p2.y - (p3.y - p1.y) / 6;

                targetCtx.bezierCurveTo(cp1x, cp1y, cp2x, cp2y, p2.x, p2.y);
            }
        }

        // Area Gradient
        ctx.save();
        drawCurve(ctx);
        ctx.lineTo(pts[pts.length - 1].x, h - pad.b);
        ctx.lineTo(pts[0].x, h - pad.b);
        ctx.closePath();

        const grad = ctx.createLinearGradient(0, pad.t, 0, h - pad.b);
        grad.addColorStop(0, 'rgba(37, 99, 235, 0.22)');
        grad.addColorStop(0.7, 'rgba(37, 99, 235, 0.04)');
        grad.addColorStop(1, 'rgba(37, 99, 235, 0.00)');
        ctx.fillStyle = grad;
        ctx.fill();
        ctx.restore();

        // Curve stroke
        ctx.save();
        drawCurve(ctx);
        ctx.strokeStyle = '#2563eb';
        ctx.lineWidth = 2.5;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.stroke();
        ctx.restore();

        // Vertical guide on hover
        if (activeIdx >= 0 && activeIdx < pts.length) {
            const ap = pts[activeIdx];
            ctx.save();
            ctx.beginPath();
            ctx.strokeStyle = 'rgba(37, 99, 235, 0.35)';
            ctx.lineWidth = 1.5;
            ctx.setLineDash([4, 4]);
            ctx.moveTo(ap.x, pad.t);
            ctx.lineTo(ap.x, h - pad.b);
            ctx.stroke();
            ctx.restore();
        }

        // Data points
        pts.forEach((p, i) => {
            const isHovered = (i === activeIdx);
            if (isHovered) {
                ctx.beginPath();
                ctx.arc(p.x, p.y, 8, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(37, 99, 235, 0.25)';
                ctx.fill();
            }

            ctx.beginPath();
            ctx.arc(p.x, p.y, isHovered ? 5 : 3.5, 0, Math.PI * 2);
            ctx.fillStyle = '#2563eb';
            ctx.fill();

            ctx.beginPath();
            ctx.arc(p.x, p.y, isHovered ? 2.5 : 1.5, 0, Math.PI * 2);
            ctx.fillStyle = '#ffffff';
            ctx.fill();
        });

        // X-axis labels
        ctx.textAlign = 'center';
        ctx.fillStyle = '#64748b';
        ctx.font = '10px Inter, sans-serif';

        const labelStep = Math.max(1, Math.floor(labels.length / 6));
        for (let i = 0; i < labels.length; i += labelStep) {
            ctx.fillText(labels[i] || '', pts[i].x, h - 10);
        }
        if ((labels.length - 1) % labelStep !== 0) {
            const lastIdx = labels.length - 1;
            ctx.fillText(labels[lastIdx] || '', pts[lastIdx].x, h - 10);
        }
    }

    draw();
    window.addEventListener('resize', () => draw(hoveredIdx));

    // Canvas hover handler
    canvas.addEventListener('mousemove', e => {
        if (!canvas._pts || !canvas._pts.length) return;
        const canvasRect = canvas.getBoundingClientRect();
        const mx = e.clientX - canvasRect.left;
        let best = null, minDiff = 9999;

        canvas._pts.forEach(p => {
            const diff = Math.abs(p.x - mx);
            if (diff < minDiff) {
                minDiff = diff;
                best = p;
            }
        });

        if (best && minDiff < 32) {
            if (hoveredIdx !== best.index) {
                hoveredIdx = best.index;
                draw(hoveredIdx);
            }

            tooltip.innerHTML = `
                <div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider mb-0.5">${best.label}</div>
                <div class="text-sm font-black text-emerald-400">₱${Number(best.v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                <div class="text-[9px] text-slate-400 mt-0.5">Admin Fee Yield</div>
            `;
            tooltip.classList.remove('hidden');

            const wrapRect = wrap.getBoundingClientRect();
            const ptX = (canvasRect.left - wrapRect.left) + best.x;
            const ptY = (canvasRect.top - wrapRect.top) + best.y;

            const tipW = tooltip.offsetWidth || 130;
            const tipH = tooltip.offsetHeight || 50;

            let left = ptX - (tipW / 2);
            left = Math.max(8, Math.min(left, wrapRect.width - tipW - 8));

            let top = ptY - tipH - 12;
            if (top < 8) top = ptY + 14;

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

    // AJAX range switcher
    document.querySelectorAll('.rangeBtn').forEach(btn => {
        btn.addEventListener('click', () => {
            const range = btn.dataset.range;
            loadAnalyticsRange(range);
        });
    });

    function loadAnalyticsRange(range) {
        document.querySelectorAll('.rangeBtn').forEach(b => {
            if (b.dataset.range === range) {
                b.className = 'rangeBtn px-3 py-1 rounded-lg text-xs font-bold transition-all bg-primary text-on-primary shadow-xs';
            } else {
                b.className = 'rangeBtn px-3 py-1 rounded-lg text-xs font-bold transition-all text-on-surface-variant hover:text-on-surface';
            }
        });

        fetch('<?= base_url('admin/analytics/data') ?>?range=' + range, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                labels = data.labels || [];
                values = data.values || [];
                hoveredIdx = -1;
                draw();

                // Update summary KPI pills
                const sumEl = document.getElementById('metric-period-total');
                const avgEl = document.getElementById('metric-period-avg');
                const peakEl = document.getElementById('metric-period-peak');

                if (sumEl) sumEl.textContent = '₱' + Number(data.total || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (avgEl) avgEl.textContent = '₱' + Number(data.avg || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (peakEl) peakEl.textContent = '₱' + Number(data.peak || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        })
        .catch(err => console.error('Failed to load analytics range:', err));
    }

    // Global refresh helper
    window.refreshAnalyticsData = function() {
        const icon = document.getElementById('refreshIcon');
        if (icon) icon.classList.add('animate-spin');
        const activeBtn = document.querySelector('.rangeBtn.bg-primary') || document.querySelector('.rangeBtn');
        const range = activeBtn ? activeBtn.dataset.range : '30';
        
        loadAnalyticsRange(range);
        setTimeout(() => {
            if (icon) icon.classList.remove('animate-spin');
            if (window.showToast) window.showToast('Analytics refreshed successfully', 'success');
        }, 600);
    };
})();
</script>
<?= $this->endSection() ?>
