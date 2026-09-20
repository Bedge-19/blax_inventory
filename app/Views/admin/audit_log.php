<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="space-y-xl">

    <!-- Header Section with Live Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">security</span>
                </div>
                <div>
                    <h2 class="text-headline-md font-bold text-on-surface">Security &amp; Activity Audit Log</h2>
                    <p class="text-body-sm text-on-surface-variant">Real-time forensic telemetry, administrative interventions, and network events.</p>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="window.location.reload()" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-surface-container-high hover:bg-surface-container-highest text-on-surface border border-outline-variant/30 rounded-xl text-label-sm font-semibold transition-colors" title="Reload live events">
                <span class="material-symbols-outlined text-[18px]">refresh</span>
                <span>Refresh</span>
            </button>
            <?php
            $exportParams = http_build_query($filters ?? []);
            ?>
            <a href="<?= base_url('admin/audit-log/export?' . $exportParams) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-on-primary hover:bg-primary/90 rounded-xl text-label-sm font-semibold transition-colors shadow-sm">
                <span class="material-symbols-outlined text-[18px]">download</span>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    <!-- 4 Glassmorphic KPI Summary Cards -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total 24h -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/20 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[26px]">browse_activity</span>
            </div>
            <div>
                <p class="text-label-sm font-semibold text-on-surface-variant uppercase tracking-wider">Total (24h)</p>
                <h3 class="text-headline-sm font-bold text-on-surface mt-0.5"><?= number_format($total_24h ?? 0) ?></h3>
                <p class="text-xs text-primary flex items-center gap-1 mt-0.5 font-medium">
                    <span class="material-symbols-outlined text-[14px]">trending_up</span> Platform actions
                </p>
            </div>
        </div>

        <!-- Security Alerts / Failed Actions -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/20 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl <?= ($critical ?? 0) > 0 ? 'bg-error/10 text-error animate-pulse' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' ?> flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[26px]"><?= ($critical ?? 0) > 0 ? 'warning' : 'verified_user' ?></span>
            </div>
            <div>
                <p class="text-label-sm font-semibold text-on-surface-variant uppercase tracking-wider">Failed / Alerts</p>
                <h3 class="text-headline-sm font-bold <?= ($critical ?? 0) > 0 ? 'text-error' : 'text-emerald-600 dark:text-emerald-400' ?> mt-0.5"><?= esc($critical ?? 0) ?></h3>
                <p class="text-xs <?= ($critical ?? 0) > 0 ? 'text-error font-semibold' : 'text-emerald-600 dark:text-emerald-400 font-medium' ?> mt-0.5">
                    <?= ($critical ?? 0) > 0 ? 'Action required' : 'Zero security alerts' ?>
                </p>
            </div>
        </div>

        <!-- Financial Operations Audited -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/20 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[26px]">account_balance_wallet</span>
            </div>
            <div>
                <p class="text-label-sm font-semibold text-on-surface-variant uppercase tracking-wider">Financial Ops</p>
                <h3 class="text-headline-sm font-bold text-on-surface mt-0.5"><?= number_format($financial_ops ?? 0) ?></h3>
                <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5 font-medium">Payouts &amp; settlements</p>
            </div>
        </div>

        <!-- Active Entities / Users -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/20 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[26px]">group</span>
            </div>
            <div>
                <p class="text-label-sm font-semibold text-on-surface-variant uppercase tracking-wider">Active Entities</p>
                <h3 class="text-headline-sm font-bold text-on-surface mt-0.5"><?= number_format($active_actors ?? 0) ?></h3>
                <p class="text-xs text-purple-600 dark:text-purple-400 mt-0.5 font-medium">Unique actors in 24h</p>
            </div>
        </div>
    </section>

    <!-- Filter Presets & Search Toolbar -->
    <section class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/20 shadow-sm space-y-4">
        <!-- Quick Preset Filter Chips -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mr-1">Quick Presets:</span>
            <a href="<?= base_url('admin/audit-log') ?>" class="px-3 py-1 rounded-full text-xs font-semibold <?= empty($filters['role']) && empty($filters['status']) && empty($filters['range']) ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant hover:bg-surface-container-highest' ?> transition-colors">
                All Logs
            </a>
            <a href="<?= base_url('admin/audit-log?status=failed') ?>" class="px-3 py-1 rounded-full text-xs font-semibold <?= ($filters['status'] ?? '') === 'failed' ? 'bg-error text-on-error' : 'bg-error/10 text-error hover:bg-error/20' ?> transition-colors inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]">error</span>
                <span>Critical / Failed</span>
            </a>
            <a href="<?= base_url('admin/audit-log?role=admin') ?>" class="px-3 py-1 rounded-full text-xs font-semibold <?= ($filters['role'] ?? '') === 'admin' ? 'bg-purple-600 text-white' : 'bg-purple-500/10 text-purple-700 dark:text-purple-300 hover:bg-purple-500/20' ?> transition-colors">
                Admin Actions
            </a>
            <a href="<?= base_url('admin/audit-log?role=tenant') ?>" class="px-3 py-1 rounded-full text-xs font-semibold <?= ($filters['role'] ?? '') === 'tenant' ? 'bg-blue-600 text-white' : 'bg-blue-500/10 text-blue-700 dark:text-blue-300 hover:bg-blue-500/20' ?> transition-colors">
                Tenant Ops
            </a>
            <a href="<?= base_url('admin/audit-log?role=customer') ?>" class="px-3 py-1 rounded-full text-xs font-semibold <?= ($filters['role'] ?? '') === 'customer' ? 'bg-teal-600 text-white' : 'bg-teal-500/10 text-teal-700 dark:text-teal-300 hover:bg-teal-500/20' ?> transition-colors">
                Customer Activity
            </a>
            <a href="<?= base_url('admin/audit-log?range=today') ?>" class="px-3 py-1 rounded-full text-xs font-semibold <?= ($filters['range'] ?? '') === 'today' ? 'bg-amber-600 text-white' : 'bg-amber-500/10 text-amber-700 dark:text-amber-300 hover:bg-amber-500/20' ?> transition-colors">
                Today Only
            </a>
        </div>

        <!-- Filter Form -->
        <form method="get" action="<?= base_url('admin/audit-log') ?>" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
            <!-- Search -->
            <div class="relative md:col-span-2">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                <input name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Search action, target, actor, email, IP..." class="w-full pl-9 pr-3 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl text-body-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>

            <!-- Role Selector -->
            <div>
                <select name="role" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl text-body-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All Roles</option>
                    <option value="admin" <?= ($filters['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="tenant" <?= ($filters['role'] ?? '') === 'tenant' ? 'selected' : '' ?>>Tenant</option>
                    <option value="customer" <?= ($filters['role'] ?? '') === 'customer' ? 'selected' : '' ?>>Customer</option>
                    <option value="system" <?= ($filters['role'] ?? '') === 'system' ? 'selected' : '' ?>>System</option>
                </select>
            </div>

            <!-- Status Selector -->
            <div>
                <select name="status" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl text-body-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All Statuses</option>
                    <option value="success" <?= ($filters['status'] ?? '') === 'success' ? 'selected' : '' ?>>Success</option>
                    <option value="failed" <?= ($filters['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
                </select>
            </div>

            <!-- Timeframe Range -->
            <div>
                <select name="range" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl text-body-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All Time</option>
                    <option value="today" <?= ($filters['range'] ?? '') === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="7d" <?= ($filters['range'] ?? '') === '7d' ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="30d" <?= ($filters['range'] ?? '') === '30d' ? 'selected' : '' ?>>Last 30 Days</option>
                </select>
            </div>

            <!-- If IP filter is active -->
            <?php if (!empty($filters['ip'])): ?>
                <input type="hidden" name="ip" value="<?= esc($filters['ip']) ?>">
                <div class="md:col-span-5 flex items-center gap-2 p-2 bg-primary/10 rounded-xl text-xs font-semibold text-primary">
                    <span class="material-symbols-outlined text-[16px]">filter_alt</span>
                    <span>Filtering by IP: <code class="font-mono"><?= esc($filters['ip']) ?></code></span>
                    <a href="<?= base_url('admin/audit-log') ?>" class="ml-auto underline hover:text-primary/80">Clear IP Filter</a>
                </div>
            <?php endif; ?>

            <!-- Submit Actions & Page Size -->
            <div class="md:col-span-5 flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-outline-variant/10">
                <div class="flex items-center gap-1.5 text-xs text-on-surface-variant">
                    <label for="audit_per_page" class="font-medium">Show per page:</label>
                    <select name="per_page" id="audit_per_page" onchange="this.form.submit()" class="bg-surface-container-low border border-outline-variant/30 rounded-xl px-3 py-1.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="5" <?= ($per_page ?? 10) == 5 ? 'selected' : '' ?>>5</option>
                        <option value="10" <?= ($per_page ?? 10) == 10 ? 'selected' : '' ?>>10</option>
                        <option value="20" <?= ($per_page ?? 10) == 20 ? 'selected' : '' ?>>20</option>
                    </select>
                </div>
                <button type="submit" class="px-5 py-2 bg-primary text-on-primary rounded-xl text-label-sm font-bold hover:bg-primary/90 transition-colors shadow-sm inline-flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">filter_list</span>
                    <span>Apply Filters</span>
                </button>
            </div>
        </form>
    </section>

    <!-- Audit Events Table -->
    <section class="bg-surface-container-lowest rounded-2xl border border-outline-variant/20 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low/60 border-b border-outline-variant/20">
                    <tr>
                        <th class="py-3 px-4 text-label-sm font-bold text-on-surface-variant uppercase tracking-wider">Timestamp</th>
                        <th class="py-3 px-4 text-label-sm font-bold text-on-surface-variant uppercase tracking-wider">Actor / User</th>
                        <th class="py-3 px-4 text-label-sm font-bold text-on-surface-variant uppercase tracking-wider">Role</th>
                        <th class="py-3 px-4 text-label-sm font-bold text-on-surface-variant uppercase tracking-wider">Action</th>
                        <th class="py-3 px-4 text-label-sm font-bold text-on-surface-variant uppercase tracking-wider">Target Resource</th>
                        <th class="py-3 px-4 text-label-sm font-bold text-on-surface-variant uppercase tracking-wider">Network IP</th>
                        <th class="py-3 px-4 text-label-sm font-bold text-on-surface-variant uppercase tracking-wider">Status</th>
                        <th class="py-3 px-4 text-label-sm font-bold text-on-surface-variant uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    <?php if (!empty($audit_logs)): foreach ($audit_logs as $log): ?>
                        <?php
                        $actorName = trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: ($log['actor_role'] === 'system' ? 'System' : 'Unknown');
                        $actorInitials = $actorName !== '' ? mb_strtoupper(mb_substr($actorName, 0, 2)) : '??';
                        $role = strtolower($log['actor_role'] ?? 'system');
                        $roleBadgeClasses = [
                            'admin'    => 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border border-purple-500/20',
                            'tenant'   => 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20',
                            'customer' => 'bg-teal-500/10 text-teal-700 dark:text-teal-400 border border-teal-500/20',
                            'system'   => 'bg-surface-variant text-on-surface-variant border border-outline-variant/30',
                        ];
                        $status = strtolower($log['status'] ?? 'success');
                        ?>
                        <tr class="hover:bg-surface-container-low/40 transition-colors">
                            <!-- Timestamp -->
                            <td class="py-3 px-4">
                                <p class="text-body-sm font-medium text-on-surface"><?= date('M d, Y', strtotime($log['created_at'])) ?></p>
                                <p class="text-xs text-on-surface-variant font-mono"><?= date('h:i:s A', strtotime($log['created_at'])) ?></p>
                            </td>

                            <!-- Actor -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-surface-container-highest text-on-surface-variant flex items-center justify-center text-xs font-bold shrink-0">
                                        <?= esc($actorInitials) ?>
                                    </div>
                                    <div>
                                        <p class="text-body-sm font-bold text-on-surface"><?= esc($actorName) ?></p>
                                        <p class="text-xs text-on-surface-variant"><?= esc($log['email'] ?? '—') ?></p>
                                    </div>
                                </div>
                            </td>

                            <!-- Role -->
                            <td class="py-3 px-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wide <?= $roleBadgeClasses[$role] ?? 'bg-surface-variant text-on-surface-variant' ?>">
                                    <?= esc($role) ?>
                                </span>
                            </td>

                            <!-- Action -->
                            <td class="py-3 px-4">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-surface-container-high border border-outline-variant/30 text-on-surface uppercase tracking-wide">
                                    <?= esc(str_replace('_', ' ', $log['action'])) ?>
                                </span>
                            </td>

                            <!-- Target -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-1.5 text-body-sm">
                                    <span class="font-semibold text-on-surface capitalize"><?= esc($log['target_type'] ?? '—') ?></span>
                                    <?php if (!empty($log['target_id'])): ?>
                                        <span class="px-1.5 py-0.2 rounded bg-surface-container-high text-xs font-mono font-bold text-on-surface-variant">#<?= (int) $log['target_id'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Network IP -->
                            <td class="py-3 px-4">
                                <?php if (!empty($log['ip_address'])): ?>
                                    <a href="<?= base_url('admin/audit-log?ip=' . urlencode($log['ip_address'])) ?>" class="px-2 py-0.5 rounded font-mono text-xs bg-surface-container-high hover:bg-surface-container-highest text-primary font-semibold transition-colors" title="Filter events by this IP">
                                        <?= esc($log['ip_address']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-xs text-outline font-mono">127.0.0.1</span>
                                <?php endif; ?>
                            </td>

                            <!-- Status -->
                            <td class="py-3 px-4">
                                <?php if ($status === 'success'): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 uppercase tracking-wide">
                                        <span class="material-symbols-outlined text-[14px]">check</span>
                                        Success
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-error/10 text-error border border-error/20 uppercase tracking-wide">
                                        <span class="material-symbols-outlined text-[14px]">close</span>
                                        Failed
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td class="py-3 px-4 text-right">
                                <button type="button" onclick="inspectAuditEvent(<?= (int) $log['id'] ?>)" class="inline-flex items-center gap-1 px-3 py-1.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface border border-outline-variant/30 rounded-xl text-label-sm font-semibold transition-colors" title="Inspect full audit telemetry">
                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                    <span>Inspect</span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr>
                            <td colspan="8" class="py-12 text-center text-on-surface-variant">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-full bg-surface-container-high flex items-center justify-center text-outline mb-2">
                                        <span class="material-symbols-outlined text-[28px]">search_off</span>
                                    </div>
                                    <p class="text-body-md font-semibold text-on-surface">No audit log entries found</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">Try resetting your search query or selecting a broader date range.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (isset($pager)): ?>
            <?php
            $total   = (int) $pager->getTotal('audit_log');
            $perPage = (int) ($per_page ?? ($pager ? $pager->getPerPage('audit_log') : 10));
            $cur     = (int) $pager->getCurrentPage('audit_log');
            $pages   = (int) $pager->getPageCount('audit_log');
            $start   = $total === 0 ? 0 : ($cur - 1) * $perPage + 1;
            $end     = min($cur * $perPage, $total);
            ?>
            <div class="px-6 py-4 bg-surface-container-low/40 flex justify-between items-center border-t border-outline-variant/20 flex-wrap gap-sm">
                <p class="text-label-sm text-on-surface-variant">Showing <?= number_format($start) ?> to <?= number_format($end) ?> of <?= number_format($total) ?> entries</p>
                <?php if ($pages > 1): ?>
                    <div class="flex items-center gap-1">
                        <a class="p-1.5 rounded hover:bg-surface-container-high <?= $cur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('audit_log') ?>" title="Previous">
                            <span class="material-symbols-outlined text-[20px]">chevron_left</span>
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
                                <span class="px-1 text-outline text-xs">...</span>
                            <?php endif; ?>
                            <a class="w-8 h-8 rounded flex items-center justify-center text-label-sm <?= $cur === $num ? 'bg-primary text-on-primary font-bold shadow-sm' : 'hover:bg-surface-container-high text-on-surface' ?>" href="<?= $pager->getPageURI($num, 'audit_log') ?>"><?= $num ?></a>
                        <?php $prev = $num; endforeach; ?>
                        <a class="p-1.5 rounded hover:bg-surface-container-high <?= $cur >= $pages ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('audit_log') ?>" title="Next">
                            <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

</div>

<!-- Modal: Audit Event Inspector -->
<div id="auditInspectModal" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4 backdrop-blur-sm transition-opacity">
    <div class="bg-surface-container-lowest rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-outline-variant/20 flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-outline-variant/20 flex items-center justify-between bg-surface-container-low/40">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-primary"></span>
                <h3 class="text-title-md font-bold text-on-surface">Audit Event Inspector</h3>
            </div>
            <button type="button" onclick="closeAuditInspectModal()" class="p-1.5 rounded-lg text-outline hover:text-on-surface hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <!-- Modal Body (Dynamic AJAX Content) -->
        <div id="auditInspectBody" class="p-6 overflow-y-auto space-y-4 text-body-sm">
            <div class="py-12 flex flex-col items-center justify-center text-outline">
                <span class="material-symbols-outlined animate-spin text-[32px] text-primary">progress_activity</span>
                <p class="text-xs mt-2">Loading event telemetry...</p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-3.5 border-t border-outline-variant/20 bg-surface-container-low/40 flex items-center justify-between">
            <span id="auditInspectTimeAgo" class="text-xs text-on-surface-variant font-mono">Timestamp</span>
            <button type="button" onclick="closeAuditInspectModal()" class="px-4 py-2 bg-surface-container-high hover:bg-surface-container-highest text-on-surface rounded-xl text-label-sm font-semibold transition-colors">
                Close Inspector
            </button>
        </div>
    </div>
</div>

<script>
    async function inspectAuditEvent(logId) {
        const modal = document.getElementById('auditInspectModal');
        const body  = document.getElementById('auditInspectBody');
        const timeAgo = document.getElementById('auditInspectTimeAgo');

        modal.classList.remove('hidden');
        body.innerHTML = `
            <div class="py-12 flex flex-col items-center justify-center text-outline">
                <span class="material-symbols-outlined animate-spin text-[32px] text-primary">progress_activity</span>
                <p class="text-xs mt-2">Loading telemetry...</p>
            </div>
        `;

        try {
            const res = await fetch('<?= base_url('admin/audit-log/detail') ?>/' + logId, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!json.success || !json.data) {
                body.innerHTML = `<div class="p-4 bg-error-container text-on-error-container rounded-xl text-xs font-semibold">${json.error || 'Unable to load details.'}</div>`;
                return;
            }

            const d = json.data;
            timeAgo.textContent = d.time_ago;

            const isSuccess = (d.status || '').toLowerCase() === 'success';

            let targetCardHtml = '';
            if (d.target_type) {
                targetCardHtml = `
                    <div class="bg-surface-container-low rounded-xl p-4 border border-outline-variant/20 space-y-1.5">
                        <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Target Resource</p>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-on-surface capitalize">${d.target_type} ${d.target_id ? '#' + d.target_id : ''}</span>
                            ${d.target_info ? `<span class="text-xs px-2 py-0.5 rounded bg-surface-container-highest font-mono">${JSON.stringify(d.target_info)}</span>` : ''}
                        </div>
                    </div>
                `;
            }

            body.innerHTML = `
                <!-- Action & Status Banner -->
                <div class="p-4 rounded-xl ${isSuccess ? 'bg-emerald-500/10 border border-emerald-500/20' : 'bg-error/10 border border-error/20'} flex items-center justify-between">
                    <div>
                        <p class="text-xs text-on-surface-variant font-medium">Recorded Action</p>
                        <h4 class="text-title-sm font-bold text-on-surface uppercase tracking-wide mt-0.5">${d.action}</h4>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider ${isSuccess ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300' : 'bg-error/20 text-error'}">
                        ${d.status}
                    </span>
                </div>

                <!-- Actor Profile Details -->
                <div class="bg-surface-container-low rounded-xl p-4 border border-outline-variant/20 space-y-2">
                    <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Actor Information</p>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-on-surface-variant block">Full Name</span>
                            <span class="font-bold text-on-surface text-sm">${d.actor_name}</span>
                        </div>
                        <div>
                            <span class="text-on-surface-variant block">Actor Role</span>
                            <span class="font-bold text-primary uppercase">${d.actor_role}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-on-surface-variant block">Email Address</span>
                            <span class="font-mono text-on-surface">${d.actor_email || 'None / System'}</span>
                        </div>
                    </div>
                </div>

                ${targetCardHtml}

                <!-- Network & Security Telemetry -->
                <div class="bg-surface-container-low rounded-xl p-4 border border-outline-variant/20 space-y-2">
                    <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Network Footprint</p>
                    <div class="flex items-center justify-between text-xs">
                        <div>
                            <span class="text-on-surface-variant block">IP Address</span>
                            <span class="font-mono font-bold text-primary">${d.ip_address}</span>
                        </div>
                        <a href="<?= base_url('admin/audit-log') ?>?ip=${encodeURIComponent(d.ip_address)}" class="px-3 py-1 rounded-lg bg-surface-container-highest hover:bg-surface-container text-primary text-xs font-bold transition-colors">
                            Filter by this IP
                        </a>
                    </div>
                </div>
            `;
        } catch (e) {
            body.innerHTML = `<div class="p-4 bg-error-container text-on-error-container rounded-xl text-xs font-semibold">Error loading telemetry.</div>`;
        }
    }

    function closeAuditInspectModal() {
        document.getElementById('auditInspectModal').classList.add('hidden');
    }
</script>

<?= $this->endSection() ?>
