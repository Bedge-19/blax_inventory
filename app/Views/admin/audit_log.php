<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="space-y-xl">

    <!-- Summary Cards — prototype audit log -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow">
            <p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold mb-2">Total Activities (24h)</p>
            <h3 class="text-headline-lg font-bold text-on-surface"><?= number_format($total_24h ?? 0) ?></h3>
            <p class="text-xs text-primary flex items-center gap-xs mt-xs"><span class="material-symbols-outlined text-sm">trending_up</span> Last 24 hours</p>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow">
            <p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold mb-2">Critical Events</p>
            <h3 class="text-headline-lg font-bold <?= ($critical??0)>0?'text-error':'text-emerald-600' ?>"><?= esc($critical ?? 0) ?></h3>
            <p class="text-xs <?= ($critical??0)>0?'text-error':'text-emerald-600' ?> mt-xs"><?= ($critical??0)>0?'Review failed actions':'No security alerts' ?></p>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow">
            <p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold mb-2">New Accounts</p>
            <h3 class="text-headline-lg font-bold text-on-surface"><?= esc($new_accounts ?? 0) ?></h3>
            <p class="text-xs text-on-surface-variant mt-xs">Created in last 24h</p>
        </div>
    </section>

    <section class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 soft-shadow overflow-hidden">
        <div class="p-6 border-b border-outline-variant/20">
            <h3 class="text-title-lg font-bold text-on-surface">Audit Log</h3>
            <p class="text-xs text-on-surface-variant">Comprehensive record of all platform activities and system events.</p>
        </div>
        <div class="px-6 py-4 border-b border-outline-variant/20 bg-surface-container-low/50 flex flex-wrap gap-sm items-center">
            <form method="get" action="<?= base_url('admin/audit-log') ?>" class="flex flex-wrap gap-sm flex-1">
                <div class="relative flex-1 min-w-[180px]">
                    <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                    <input name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Search actions..." class="w-full pl-xl pr-md py-sm bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                </div>
                <select name="role" class="bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-sm text-sm">
                    <option value="">All Roles</option>
                    <option value="admin" <?= ($filters['role']??'')==='admin'?'selected':'' ?>>Admin</option>
                    <option value="tenant" <?= ($filters['role']??'')==='tenant'?'selected':'' ?>>Tenant</option>
                    <option value="customer" <?= ($filters['role']??'')==='customer'?'selected':'' ?>>Customer</option>
                    <option value="system" <?= ($filters['role']??'')==='system'?'selected':'' ?>>System</option>
                </select>
                <select name="status" class="bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-sm text-sm">
                    <option value="">All Status</option>
                    <option value="success" <?= ($filters['status']??'')==='success'?'selected':'' ?>>Success</option>
                    <option value="failed" <?= ($filters['status']??'')==='failed'?'selected':'' ?>>Failed</option>
                </select>
                <button type="submit" class="bg-primary text-on-primary px-md py-sm rounded-lg text-sm font-semibold">Filter</button>
                <a href="<?= base_url('admin/audit-log') ?>" class="px-md py-sm text-sm text-on-surface-variant hover:underline">Reset</a>
            </form>
        </div>
        <div class="responsive-table">
            <table class="w-full text-left border-collapse">
                <thead><tr class="bg-surface-container-low/50 text-label-sm text-on-surface-variant/70 uppercase">
                    <th class="py-sm px-md">Timestamp</th><th class="py-sm px-md">User/Entity</th><th class="py-sm px-md">Role</th><th class="py-sm px-md">Action</th><th class="py-sm px-md">Target</th><th class="py-sm px-md">Status</th>
                </tr></thead>
                <tbody>
                    <?php if (!empty($audit_logs)): foreach ($audit_logs as $log): ?>
                        <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low">
                            <td class="py-md px-md text-xs text-on-surface-variant"><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                            <td class="py-md px-md text-sm font-semibold"><?= esc(($log['first_name']??'') . ' ' . ($log['last_name']??'') ?: ($log['actor'] ?? 'Admin')) ?><p class="text-[11px] text-on-surface-variant"><?= esc($log['email'] ?? '') ?></p></td>
                            <td class="py-md px-md text-xs uppercase"><?= esc($log['actor_role'] ?? $log['role'] ?? '—') ?></td>
                            <td class="py-md px-md"><span class="px-sm py-xs bg-primary/10 text-primary rounded-full text-xs font-bold uppercase"><?= esc($log['action']) ?></span></td>
                            <td class="py-md px-md text-xs"><?= esc(($log['target_type'] ?? '') . ($log['target_id'] ? ' #'.$log['target_id'] : '') ?: ($log['target'] ?? '—')) ?></td>
                            <td class="py-md px-md"><span class="px-sm py-xs rounded-full text-xs font-bold <?= ($log['status']??'success')==='success'?'bg-emerald-100 text-emerald-700':'bg-error-container text-on-error-container' ?>"><?= esc(ucfirst($log['status'] ?? 'success')) ?></span></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="py-lg text-center text-on-surface-variant">No audit log entries found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 bg-surface-container-low/30 border-t border-outline-variant/20 text-xs text-on-surface-variant">Showing up to 100 most recent entries</div>
    </section>

</div>

<?= $this->endSection() ?>
