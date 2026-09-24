<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="space-y-6">

    <!-- Executive Header & Real-time Operations Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/30 shadow-xs">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-bold text-on-surface-variant/70 uppercase tracking-widest">Platform Master Telemetry</span>
            </div>
            <h1 class="text-2xl font-black text-on-surface tracking-tight">Platform Overview &amp; Health</h1>
            <p class="text-xs text-on-surface-variant max-w-2xl">
                Real-time marketplace revenue, merchant verification queues, platform activity, and Polomolok delivery operations.
            </p>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="<?= base_url('admin/payments') ?>" class="px-3.5 py-2 rounded-xl border border-outline-variant/40 bg-surface-container hover:bg-surface-container-high text-xs font-semibold text-on-surface transition-all flex items-center gap-1.5 shadow-2xs">
                <span class="material-symbols-outlined text-[17px] text-primary">payments</span>
                <span>Review Payouts</span>
                <?php if ((int)($pending_payments ?? 0) > 0): ?>
                    <span class="px-1.5 py-0.5 rounded-full bg-amber-500 text-white font-bold text-[10px]"><?= (int)$pending_payments ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= base_url('admin/tracking') ?>" class="px-3.5 py-2 rounded-xl border border-outline-variant/40 bg-surface-container hover:bg-surface-container-high text-xs font-semibold text-on-surface transition-all flex items-center gap-1.5 shadow-2xs">
                <span class="material-symbols-outlined text-[17px] text-emerald-600">route</span>
                <span>Live Fleet Dispatch</span>
            </a>
            <a href="<?= base_url('admin/tenants') ?>" class="bg-primary hover:bg-primary/90 text-on-primary px-4 py-2 rounded-xl flex items-center justify-center gap-2 font-bold text-xs shadow-md shadow-primary/20 active:scale-95 transition-all">
                <span class="material-symbols-outlined text-[18px]">storefront</span>
                <span>Manage Merchants</span>
            </a>
        </div>
    </div>

    <!-- Stripe Bento Metric Scorecards -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Admin Revenue -->
        <div class="relative overflow-hidden bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/30 shadow-xs hover:shadow-md transition-all group flex flex-col justify-between">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-primary/5 rounded-full blur-2xl group-hover:bg-primary/10 transition-all"></div>
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant/70">Admin Revenue</span>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">3% fee on GCash withdrawals</p>
                </div>
                <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                    <span class="material-symbols-outlined text-[20px]">payments</span>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl lg:text-3xl font-black text-primary tracking-tight">₱<?= number_format($admin_revenue ?? 0, 2) ?></h3>
                <div class="mt-2.5 h-1.5 w-full bg-surface-container-high rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-primary to-indigo-500 rounded-full" style="width: 100%"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-on-surface-variant mt-1.5 font-medium">
                    <span>Protocol Net</span>
                    <span class="text-emerald-600 font-bold">100% Settled</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Active Merchant Shops -->
        <a href="<?= base_url('admin/tenants') ?>" class="relative overflow-hidden bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/30 hover:border-primary/50 shadow-xs hover:shadow-md transition-all group flex flex-col justify-between">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl group-hover:bg-emerald-500/10 transition-all"></div>
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant/70">Active Shops</span>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">Verified storefront merchants</p>
                </div>
                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                    <span class="material-symbols-outlined text-[20px]">storefront</span>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-baseline gap-2">
                    <h3 class="text-2xl lg:text-3xl font-black text-on-surface tracking-tight"><?= esc($active_shops ?? $total_shops ?? 0) ?></h3>
                    <span class="text-xs text-on-surface-variant font-medium">of <?= esc($total_shops ?? 0) ?> registered</span>
                </div>
                <div class="flex items-center justify-between text-[11px] mt-2.5 pt-2 border-t border-outline-variant/20">
                    <?php if ((int)($pending_shops ?? 0) > 0): ?>
                        <span class="text-amber-600 font-bold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            <?= (int)$pending_shops ?> Pending Approval
                        </span>
                    <?php else: ?>
                        <span class="text-emerald-600 font-semibold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            All Merchants Verified
                        </span>
                    <?php endif; ?>
                    <span class="material-symbols-outlined text-[15px] text-on-surface-variant group-hover:text-primary transition-colors">chevron_right</span>
                </div>
            </div>
        </a>

        <!-- Card 3: Total Customers -->
        <a href="<?= base_url('admin/customers') ?>" class="relative overflow-hidden bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/30 hover:border-primary/50 shadow-xs hover:shadow-md transition-all group flex flex-col justify-between">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-sky-500/5 rounded-full blur-2xl group-hover:bg-sky-500/10 transition-all"></div>
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant/70">Customers</span>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">Platform consumer accounts</p>
                </div>
                <div class="w-9 h-9 rounded-xl bg-sky-500/10 text-sky-600 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                    <span class="material-symbols-outlined text-[20px]">group</span>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl lg:text-3xl font-black text-on-surface tracking-tight"><?= number_format($total_customers ?? 0) ?></h3>
                <div class="flex items-center justify-between text-[11px] mt-2.5 pt-2 border-t border-outline-variant/20">
                    <span class="text-on-surface-variant flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span> Polomolok Consumer Base
                    </span>
                    <span class="material-symbols-outlined text-[15px] text-on-surface-variant group-hover:text-primary transition-colors">chevron_right</span>
                </div>
            </div>
        </a>

        <!-- Card 4: Pending GCash Payout Requests -->
        <a href="<?= base_url('admin/payments') ?>" class="relative overflow-hidden bg-surface-container-lowest rounded-2xl p-5 border <?= (int)($pending_payments ?? 0) > 0 ? 'border-rose-500/40 hover:border-rose-500' : 'border-outline-variant/30 hover:border-primary/50' ?> shadow-xs hover:shadow-md transition-all group flex flex-col justify-between">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-500/5 rounded-full blur-2xl group-hover:bg-rose-500/10 transition-all"></div>
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant/70">Payout Queue</span>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">GCash merchant requests</p>
                </div>
                <div class="w-9 h-9 rounded-xl <?= (int)($pending_payments ?? 0) > 0 ? 'bg-rose-500/10 text-rose-600' : 'bg-surface-container text-on-surface-variant' ?> flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                    <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-baseline gap-2">
                    <h3 class="text-2xl lg:text-3xl font-black <?= (int)($pending_payments ?? 0) > 0 ? 'text-rose-600' : 'text-on-surface' ?> tracking-tight"><?= esc($pending_payments ?? 0) ?></h3>
                    <span class="text-xs text-on-surface-variant font-medium">pending action</span>
                </div>
                <div class="flex items-center justify-between text-[11px] mt-2.5 pt-2 border-t border-outline-variant/20">
                    <?php if ((int)($pending_payments ?? 0) > 0): ?>
                        <span class="text-rose-600 font-bold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span> Action Required (48h)
                        </span>
                    <?php else: ?>
                        <span class="text-emerald-600 font-semibold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Queue Cleared
                        </span>
                    <?php endif; ?>
                    <span class="material-symbols-outlined text-[15px] text-on-surface-variant group-hover:text-primary transition-colors">chevron_right</span>
                </div>
            </div>
        </a>
    </section>

    <!-- Analytics & Live Platform Operations (Linear / Stripe Split View) -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Interactive Admin Revenue Chart (2 Columns) -->
        <div class="lg:col-span-2 bg-surface-container-lowest rounded-2xl border border-outline-variant/30 shadow-xs p-6 flex flex-col justify-between">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary"></span>
                        <h3 class="text-base font-bold text-on-surface tracking-tight">Admin Revenue Velocity</h3>
                    </div>
                    <p class="text-xs text-on-surface-variant mt-0.5">GCash merchant payout commissions (3.0% protocol rate) over past 30 days.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold px-2.5 py-1 bg-primary/10 text-primary border border-primary/20 rounded-full">3% Protocol Fee</span>
                    <a href="<?= base_url('admin/analytics') ?>" class="text-[11px] font-semibold text-on-surface-variant hover:text-primary transition-colors flex items-center gap-0.5">
                        <span>Full Insights</span>
                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </div>
            </div>

            <!-- Canvas Chart Wrap with Precision Tooltip -->
            <div id="dashboardChartWrap" class="relative h-64 w-full bg-surface-container-low/60 rounded-xl border border-outline-variant/20 p-4">
                <canvas id="dashboardChart" class="w-full h-full cursor-crosshair"></canvas>
                <div id="dashboardTooltip" class="hidden absolute z-30 pointer-events-none whitespace-nowrap text-xs font-semibold px-3 py-1.5 rounded-xl shadow-xl bg-slate-900/95 text-slate-100 border border-slate-700/60 backdrop-blur-md transition-[opacity,transform] duration-75"></div>
            </div>

            <!-- Chart Footer Timeline Badges -->
            <div class="flex justify-between items-center text-[11px] font-semibold text-on-surface-variant/80 mt-3 pt-2 border-t border-outline-variant/20">
                <span class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                    <?= esc(($admin_revenue_chart_labels[0] ?? 'Past 30 Days')) ?>
                </span>
                <span class="text-primary font-bold">● Active 30-Day Trend</span>
                <span><?= esc(end($admin_revenue_chart_labels) ?? 'Today') ?></span>
            </div>
        </div>

        <!-- Right: Platform Health & Operational Status (1 Column) -->
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 shadow-xs p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-on-surface tracking-tight">Platform Telemetry &amp; Alerts</h3>
                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">Online</span>
                </div>

                <div class="space-y-3">
                    <!-- Alert 1: GCash Payout Queue -->
                    <div class="p-3.5 rounded-xl <?= (int)($pending_payments ?? 0) > 0 ? 'bg-amber-500/10 border border-amber-500/20' : 'bg-surface-container-low border border-outline-variant/20' ?> transition-colors">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg <?= (int)($pending_payments ?? 0) > 0 ? 'bg-amber-500/20 text-amber-700' : 'bg-surface-container-high text-on-surface-variant' ?> flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-bold text-on-surface">GCash Payout Queue</p>
                                    <span class="text-[10px] font-bold <?= (int)($pending_payments ?? 0) > 0 ? 'text-amber-700' : 'text-emerald-600' ?>">
                                        <?= (int)($pending_payments ?? 0) ?> Pending
                                    </span>
                                </div>
                                <p class="text-[11px] text-on-surface-variant mt-0.5 leading-snug">
                                    <?php if ((int)($pending_payments ?? 0) > 0): ?>
                                        Withdrawal requests require manual disbursement review within 48h.
                                    <?php else: ?>
                                        All merchant withdrawal requests have been processed and settled.
                                    <?php endif; ?>
                                </p>
                                <?php if ((int)($pending_payments ?? 0) > 0): ?>
                                    <a href="<?= base_url('admin/payments') ?>" class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-700 hover:underline mt-2">
                                        <span>Resolve Payouts</span>
                                        <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Alert 2: Polomolok Geofence Guard -->
                    <div class="p-3.5 rounded-xl bg-surface-container-low border border-outline-variant/20">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[18px]">share_location</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-bold text-on-surface">Polomolok Geo-Boundary</p>
                                    <span class="text-[10px] font-bold text-emerald-600">Active</span>
                                </div>
                                <p class="text-[11px] text-on-surface-variant mt-0.5 leading-snug">
                                    Fulfillment strictly restricted to Polomolok municipality (6.10°–6.32° N).
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Alert 3: Tenant Managed Deliveries -->
                    <div class="p-3.5 rounded-xl bg-surface-container-low border border-outline-variant/20">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[18px]">two_wheeler</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-bold text-on-surface">Storefront Fleets</p>
                                    <span class="text-[10px] font-bold text-primary">In-House</span>
                                </div>
                                <p class="text-[11px] text-on-surface-variant mt-0.5 leading-snug">
                                    Merchants dispatch orders directly with tenant riders or pick-up counters.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-outline-variant/20 mt-4 flex items-center justify-between">
                <a href="<?= base_url('admin/audit-log') ?>" class="text-[11px] font-semibold text-on-surface-variant hover:text-on-surface flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">shield</span>
                    <span>Audit Logs</span>
                </a>
                <a href="<?= base_url('admin/compliance') ?>" class="text-[11px] font-semibold text-primary hover:underline flex items-center gap-1">
                    <span>Compliance Portal</span>
                    <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Recent Merchant Registrations (Shopify Polaris IndexTable Layout) -->
    <section class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 shadow-xs overflow-hidden">
        <!-- Table Toolbar -->
        <div class="p-5 border-b border-outline-variant/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-on-surface tracking-tight">Active Tenants &amp; Recent Registrations</h3>
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant">
                        <?= (int) ($total_shops ?? count($recent_shops)) ?> Total
                    </span>
                </div>
                <p class="text-xs text-on-surface-variant">Real-time merchant onboarding records, assigned subscription plans, and verification status.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <form method="get" action="<?= base_url('admin/dashboard') ?>" class="flex items-center gap-1.5 text-xs text-on-surface-variant">
                    <label for="dashboard_per_page" class="font-medium">Show:</label>
                    <select name="per_page" id="dashboard_per_page" onchange="this.form.submit()" class="bg-surface-container-low border border-outline-variant/40 rounded-xl px-2.5 py-1.5 text-xs font-semibold focus:ring-2 focus:ring-primary/30">
                        <option value="5" <?= ($per_page ?? 5) == 5 ? 'selected' : '' ?>>5</option>
                        <option value="10" <?= ($per_page ?? 5) == 10 ? 'selected' : '' ?>>10</option>
                        <option value="20" <?= ($per_page ?? 5) == 20 ? 'selected' : '' ?>>20</option>
                    </select>
                </form>
                <a href="<?= base_url('admin/tenants') ?>" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                    <span>View All Merchants</span>
                    <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                </a>
            </div>
        </div>

        <!-- IndexTable -->
        <div class="responsive-table">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low/70 border-b border-outline-variant/30 text-[11px] font-bold text-on-surface-variant/70 uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-5">Merchant Details</th>
                        <th class="py-3 px-5">Owner / Contact</th>
                        <th class="py-3 px-5">Tier Plan</th>
                        <th class="py-3 px-5 text-center">Status</th>
                        <th class="py-3 px-5">Registered Date</th>
                        <th class="py-3 px-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20 text-xs">
                    <?php if (!empty($recent_shops)): ?>
                        <?php foreach ($recent_shops as $s): ?>
                            <tr class="hover:bg-surface-container-low/40 transition-colors group">
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-primary/20 to-blue-500/20 text-primary border border-primary/20 flex items-center justify-center text-xs font-black shrink-0">
                                            <?= esc(mb_strtoupper(mb_substr($s['shop_name'] ?? 'S', 0, 2))) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="font-bold text-on-surface group-hover:text-primary transition-colors block truncate">
                                                <?= esc($s['shop_name']) ?>
                                            </span>
                                            <span class="text-[10px] text-on-surface-variant/70 block truncate">
                                                ID: #<?= (int) ($s['id'] ?? 0) ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-5">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-on-surface truncate">
                                            <?= esc(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')) ?: '—' ?>
                                        </p>
                                        <p class="text-[11px] text-on-surface-variant/70 truncate">
                                            <?= esc($s['email'] ?? 'No email') ?>
                                        </p>
                                    </div>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="font-semibold text-[11px] uppercase tracking-wider px-2 py-0.5 rounded-md bg-surface-container border border-outline-variant/20 text-on-surface">
                                        <?= esc($s['plan'] ?? 'standard') ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-center">
                                    <?= status_badge($s['status'] ?? 'active') ?>
                                </td>
                                <td class="py-3.5 px-5 text-on-surface-variant font-medium">
                                    <?= date('M d, Y', strtotime($s['created_at'])) ?>
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    <a href="<?= base_url('admin/tenants?q=' . urlencode($s['shop_name'])) ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-surface-container-low hover:bg-surface-container text-primary font-bold text-[11px] border border-outline-variant/30 hover:border-primary/40 transition-all">
                                        <span>Inspect</span>
                                        <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <div class="w-12 h-12 rounded-2xl bg-surface-container flex items-center justify-center mx-auto mb-2 text-outline">
                                    <span class="material-symbols-outlined text-2xl">store_off</span>
                                </div>
                                <p class="text-sm font-bold text-on-surface">No merchant registrations found</p>
                                <p class="text-xs text-on-surface-variant mt-0.5">Newly approved tenants will show up in this overview.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (isset($pager)): ?>
            <?php
            $total   = (int) $pager->getTotal('recent');
            $perPage = (int) ($per_page ?? 5);
            $cur     = (int) $pager->getCurrentPage('recent');
            $pages   = (int) $pager->getPageCount('recent');
            $start   = $total === 0 ? 0 : ($cur - 1) * $perPage + 1;
            $end     = min($cur * $perPage, $total);
            ?>
            <div class="px-5 py-3 bg-surface-container-low/70 flex justify-between items-center border-t border-outline-variant/30 flex-wrap gap-2 text-xs">
                <p class="font-medium text-on-surface-variant">
                    Showing <span class="font-bold text-on-surface"><?= number_format($start) ?></span> to <span class="font-bold text-on-surface"><?= number_format($end) ?></span> of <span class="font-bold text-on-surface"><?= number_format($total) ?></span> registrations
                </p>
                <?php if ($pages > 1): ?>
                    <div class="flex items-center gap-1">
                        <a class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container border border-outline-variant/30 <?= $cur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('recent') ?>" title="Previous">
                            <span class="material-symbols-outlined text-[16px]">chevron_left</span>
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
                                <span class="px-1 text-outline">...</span>
                            <?php endif; ?>
                            <a class="w-8 h-8 rounded-lg flex items-center justify-center font-bold <?= $cur === $num ? 'bg-primary text-on-primary shadow-xs' : 'hover:bg-surface-container border border-outline-variant/30 text-on-surface-variant' ?>" href="<?= $pager->getPageURI($num, 'recent') ?>"><?= $num ?></a>
                        <?php $prev = $num; endforeach; ?>
                        <a class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container border border-outline-variant/30 <?= $cur >= $pages ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('recent') ?>" title="Next">
                            <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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
