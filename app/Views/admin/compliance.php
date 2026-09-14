<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="space-y-xl">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-md rounded-xl bg-green-100 text-green-800 text-sm"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <!-- Summary Cards — prototype compliance -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow">
            <div class="flex justify-between items-start mb-2"><p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Total Reports</p><span class="material-symbols-outlined text-primary">report_problem</span></div>
            <h3 class="text-headline-lg font-bold text-on-surface"><?= esc($total_reports ?? 0) ?></h3>
            <p class="text-xs text-emerald-600 mt-xs">All compliance reports</p>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow">
            <div class="flex justify-between items-start mb-2"><p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Pending Reviews</p><span class="material-symbols-outlined text-error">visibility</span></div>
            <h3 class="text-headline-lg font-bold text-error"><?= esc($pending_reviews ?? 0) ?></h3>
            <p class="text-xs text-error mt-xs">Action Required</p>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow">
            <div class="flex justify-between items-start mb-2"><p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Resolved Cases</p><span class="material-symbols-outlined text-emerald-600">task_alt</span></div>
            <h3 class="text-headline-lg font-bold text-on-surface"><?= esc($resolved_cases ?? 0) ?></h3>
            <p class="text-xs text-on-surface-variant mt-xs">Closed & resolved</p>
        </div>
    </section>

    <section class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 soft-shadow overflow-hidden">
        <div class="p-6 border-b border-outline-variant/20">
            <h3 class="text-title-lg font-bold text-on-surface">Compliance Reports</h3>
            <p class="text-xs text-on-surface-variant">Monitor platform integrity and resolve merchant or customer disputes.</p>
        </div>
        <div class="px-6 py-4 border-b border-outline-variant/20 bg-surface-container-low/50 flex flex-wrap gap-sm items-center">
            <form method="get" action="<?= base_url('admin/compliance') ?>" class="flex flex-wrap gap-sm flex-1">
                <div class="relative flex-1 min-w-0 sm:min-w-[220px]">
                    <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                    <input name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Search compliance reports..." class="w-full pl-xl pr-md py-sm bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                </div>
                <select name="status" class="bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-sm text-sm">
                    <option value="">All Status</option>
                    <option value="pending" <?= ($filters['status']??'')==='pending'?'selected':'' ?>>Pending</option>
                    <option value="under_review" <?= ($filters['status']??'')==='under_review'?'selected':'' ?>>Under Review</option>
                    <option value="flagged" <?= ($filters['status']??'')==='flagged'?'selected':'' ?>>Flagged</option>
                    <option value="resolved" <?= ($filters['status']??'')==='resolved'?'selected':'' ?>>Resolved</option>
                </select>
                <button type="submit" class="bg-primary text-on-primary px-md py-sm rounded-lg text-sm font-semibold">Filter</button>
                <a href="<?= base_url('admin/compliance') ?>" class="px-md py-sm text-sm text-on-surface-variant hover:underline">Reset</a>
            </form>
        </div>
        <div class="responsive-table">
            <table class="w-full text-left border-collapse">
                <thead><tr class="bg-surface-container-low/50 text-label-sm text-on-surface-variant/70 uppercase">
                    <th class="py-sm px-md">Report ID</th><th class="py-sm px-md">Reporter</th><th class="py-sm px-md">Merchant/Entity</th><th class="py-sm px-md">Issue Type</th><th class="py-sm px-md">Status</th><th class="py-sm px-md text-right">Actions</th>
                </tr></thead>
                <tbody>
                    <?php if (!empty($compliance_items)): foreach ($compliance_items as $ci): ?>
                        <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low">
                            <td class="py-md px-md font-mono text-sm font-semibold text-primary">#<?= esc($ci['report_number'] ?? 'REP-'.$ci['id']) ?></td>
                            <td class="py-md px-md text-sm"><?= esc(($ci['reporter_first']??'') . ' ' . ($ci['reporter_last']??'') ?: 'Reporter') ?></td>
                            <td class="py-md px-md text-sm"><?= esc($ci['shop_name'] ?? 'Entity') ?><p class="text-xs text-error">Flags: <?= esc($ci['flag_count'] ?? 0) ?></p></td>
                            <td class="py-md px-md text-xs"><?= esc($ci['issue'] ?? $ci['issue_type'] ?? 'Policy violation') ?></td>
                            <td class="py-md px-md"><?= status_badge($ci['compliance_status'] ?? $ci['status'] ?? 'pending') ?></td>
                            <td class="py-md px-md text-right">
                                <form action="<?= base_url('admin/compliance/resolve') ?>" method="POST" class="inline"><?= csrf_field() ?><input type="hidden" name="shop_id" value="<?= $ci['shop_id'] ?? $ci['reported_shop_id'] ?? $ci['id'] ?>"><button type="submit" class="text-xs font-bold px-sm py-xs bg-primary text-on-primary rounded-full hover:bg-primary/90">Resolve</button></form>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="py-lg text-center text-on-surface-variant">No compliance issues found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (isset($pager)): ?>
            <?php
            $total   = (int) $pager->getTotal('compliance');
            $perPage = 15;
            $cur     = (int) $pager->getCurrentPage('compliance');
            $pages   = (int) $pager->getPageCount('compliance');
            $start   = $total === 0 ? 0 : ($cur - 1) * $perPage + 1;
            $end     = min($cur * $perPage, $total);
            ?>
            <div class="px-6 py-3 bg-surface-container-low/30 flex justify-between items-center border-t border-outline-variant/20 flex-wrap gap-sm">
                <p class="text-xs text-on-surface-variant">Showing <?= number_format($start) ?> to <?= number_format($end) ?> of <?= number_format($total) ?> reports</p>
                <?php if ($pages > 1): ?>
                    <div class="flex items-center gap-xs">
                        <a class="p-sm rounded hover:bg-surface-container-high <?= $cur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('compliance') ?>" title="Previous">
                            <span class="material-symbols-outlined text-[18px]">chevron_left</span>
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
                                <span class="px-xs text-outline text-xs">...</span>
                            <?php endif; ?>
                            <a class="w-8 h-8 rounded flex items-center justify-center text-xs <?= $cur === $num ? 'bg-primary text-on-primary font-semibold' : 'hover:bg-surface-container-high text-on-surface' ?>" href="<?= $pager->getPageURI($num, 'compliance') ?>"><?= $num ?></a>
                        <?php $prev = $num; endforeach; ?>
                        <a class="p-sm rounded hover:bg-surface-container-high <?= $cur >= $pages ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('compliance') ?>" title="Next">
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="px-6 py-3 bg-surface-container-low/30 border-t border-outline-variant/20 text-xs text-on-surface-variant">Showing <?= count($compliance_items ?? []) ?> reports</div>
        <?php endif; ?>
    </section>

</div>

<?= $this->endSection() ?>
