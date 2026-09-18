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
                    <th class="py-sm px-md">Report ID</th>
                    <th class="py-sm px-md">Reporter</th>
                    <th class="py-sm px-md">Merchant/Entity</th>
                    <th class="py-sm px-md">Issue Details</th>
                    <th class="py-sm px-md">Status</th>
                    <th class="py-sm px-md text-right">Actions</th>
                </tr></thead>
                <tbody>
                    <?php if (!empty($compliance_items)): foreach ($compliance_items as $ci): ?>
                        <?php 
                            $shopId = (int)($ci['shop_id'] ?? $ci['reported_shop_id'] ?? 0);
                            $reportId = (int)$ci['id'];
                            $isCustomerReport = !empty($ci['reported_user_id']);
                            $customerName = trim(($ci['reported_customer_first'] ?? '') . ' ' . ($ci['reported_customer_last'] ?? ''));
                            $entityName = $isCustomerReport 
                                ? ('Customer: ' . ($customerName ?: 'User #' . $ci['reported_user_id']))
                                : ($ci['shop_name'] ?? 'Merchant Shop');
                            $issueDesc = $ci['description'] ?? $ci['issue_type'] ?? 'Reported for review';
                        ?>
                        <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low transition-colors">
                            <td class="py-md px-md font-mono text-sm font-semibold text-primary">#<?= esc($ci['report_number'] ?? 'REP-'.$ci['id']) ?></td>
                            <td class="py-md px-md text-sm"><?= esc(($ci['reporter_first']??'') . ' ' . ($ci['reporter_last']??'') ?: 'Reporter') ?></td>
                            <td class="py-md px-md text-sm">
                                <p class="font-bold text-on-surface"><?= esc($entityName) ?></p>
                                <?php if (!$isCustomerReport): ?>
                                    <p class="text-xs text-error font-medium">Unresolved Flags: <?= esc($ci['flag_count'] ?? 0) ?></p>
                                <?php else: ?>
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold bg-secondary-container/40 text-secondary mt-0.5">Reported Customer</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-md px-md text-xs max-w-[280px]">
                                <span class="font-semibold text-on-surface block"><?= esc(ucwords(str_replace('_', ' ', $ci['issue'] ?? $ci['issue_type'] ?? 'Policy Violation'))) ?></span>
                                <span class="text-on-surface-variant line-clamp-2 mt-0.5"><?= esc($issueDesc) ?></span>
                            </td>
                            <td class="py-md px-md"><?= status_badge($ci['compliance_status'] ?? $ci['status'] ?? 'pending') ?></td>
                            <td class="py-md px-md text-right">
                                <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                    <?php if (!$isCustomerReport && $shopId > 0): ?>
                                        <!-- Issue Warning Modal Button -->
                                        <button type="button" 
                                                onclick="openWarnModal(<?= $shopId ?>, <?= $reportId ?>, '<?= esc($entityName, 'js') ?>')" 
                                                class="text-xs font-bold px-2.5 py-1 bg-amber-500/10 text-amber-800 border border-amber-500/30 rounded-lg hover:bg-amber-500 hover:text-white transition-all"
                                                title="Issue Formal Warning">
                                            Warn
                                        </button>

                                        <!-- Suspend Shop Modal Button -->
                                        <button type="button" 
                                                onclick="openSuspendModal(<?= $shopId ?>, '<?= esc($entityName, 'js') ?>')" 
                                                class="text-xs font-bold px-2.5 py-1 bg-error-container/40 text-on-error-container border border-error/30 rounded-lg hover:bg-error hover:text-white transition-all"
                                                title="Deactivate / Suspend Shop">
                                            Suspend
                                        </button>
                                    <?php endif; ?>

                                    <!-- Resolve Button -->
                                    <form action="<?= base_url('admin/compliance/resolve') ?>" method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="shop_id" value="<?= $shopId ?>">
                                        <input type="hidden" name="report_id" value="<?= $reportId ?>">
                                        <button type="submit" class="text-xs font-bold px-2.5 py-1 bg-primary text-on-primary rounded-lg hover:bg-primary/90 transition-all shadow-2xs" title="Mark Resolved">
                                            Resolve
                                        </button>
                                    </form>
                                </div>
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

<!-- Modal: Issue Formal Warning -->
<div id="warnModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl max-w-md w-full p-lg shadow-2xl space-y-md">
        <div class="flex items-center justify-between border-b border-outline-variant/20 pb-sm">
            <div class="flex items-center gap-sm">
                <span class="p-2 bg-amber-500/10 text-amber-700 rounded-xl material-symbols-outlined">warning</span>
                <div>
                    <h3 class="text-title-md font-bold text-on-surface">Issue Formal Warning</h3>
                    <p id="warnShopName" class="text-xs text-on-surface-variant">Merchant Shop</p>
                </div>
            </div>
            <button type="button" onclick="closeWarnModal()" class="text-outline hover:text-on-surface p-1 rounded-full"><span class="material-symbols-outlined">close</span></button>
        </div>

        <form action="<?= base_url('admin/compliance/warn') ?>" method="POST" class="space-y-md">
            <?= csrf_field() ?>
            <input type="hidden" name="shop_id" id="warnShopId">
            <input type="hidden" name="report_id" id="warnReportId">

            <div>
                <label class="text-xs font-bold text-on-surface-variant uppercase">Warning Message / Notice to Merchant</label>
                <textarea name="warning_message" rows="3" required class="w-full mt-1 p-3 bg-surface-container-low border border-outline-variant rounded-xl text-xs focus:ring-2 focus:ring-amber-500" placeholder="Specify why the shop is receiving a formal warning and required actions..."></textarea>
            </div>

            <div class="p-3 bg-amber-500/10 border border-amber-500/30 rounded-xl text-xs text-amber-900">
                <p class="font-semibold">⚠️ Notice:</p>
                <p class="mt-0.5">This will send an in-app warning notification to the merchant dashboard and flag this report for tracking.</p>
            </div>

            <div class="flex gap-sm pt-sm border-t border-outline-variant/20">
                <button type="button" onclick="closeWarnModal()" class="flex-1 py-2 rounded-xl text-xs font-bold border border-outline-variant hover:bg-surface-container">Cancel</button>
                <button type="submit" class="flex-1 py-2 rounded-xl text-xs font-bold bg-amber-600 text-white hover:bg-amber-700 shadow-sm">Send Warning</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Suspend Shop -->
<div id="suspendModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl max-w-md w-full p-lg shadow-2xl space-y-md">
        <div class="flex items-center justify-between border-b border-outline-variant/20 pb-sm">
            <div class="flex items-center gap-sm">
                <span class="p-2 bg-error-container/40 text-on-error-container rounded-xl material-symbols-outlined">block</span>
                <div>
                    <h3 class="text-title-md font-bold text-on-surface">Deactivate & Suspend Shop</h3>
                    <p id="suspendShopName" class="text-xs text-on-surface-variant">Merchant Shop</p>
                </div>
            </div>
            <button type="button" onclick="closeSuspendModal()" class="text-outline hover:text-on-surface p-1 rounded-full"><span class="material-symbols-outlined">close</span></button>
        </div>

        <form action="<?= base_url('admin/compliance/suspend') ?>" method="POST" class="space-y-md">
            <?= csrf_field() ?>
            <input type="hidden" name="shop_id" id="suspendShopId">

            <div>
                <label class="text-xs font-bold text-on-surface-variant uppercase">Reason for Suspension / Deactivation</label>
                <textarea name="suspension_reason" rows="3" required class="w-full mt-1 p-3 bg-surface-container-low border border-outline-variant rounded-xl text-xs focus:ring-2 focus:ring-error" placeholder="State policy violations causing suspension..."></textarea>
            </div>

            <div class="p-3 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800/40 rounded-xl text-xs text-red-800 dark:text-red-300">
                <p class="font-bold">🚫 High Impact Action:</p>
                <p class="mt-0.5">This will instantly deactivate the storefront, disable products, and suspend merchant storefront actions.</p>
            </div>

            <div class="flex gap-sm pt-sm border-t border-outline-variant/20">
                <button type="button" onclick="closeSuspendModal()" class="flex-1 py-2 rounded-xl text-xs font-bold border border-outline-variant hover:bg-surface-container">Cancel</button>
                <button type="submit" class="flex-1 py-2 rounded-xl text-xs font-bold bg-error text-on-error hover:bg-error/90 shadow-sm">Confirm Suspension</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openWarnModal(shopId, reportId, shopName) {
        document.getElementById('warnShopId').value = shopId;
        document.getElementById('warnReportId').value = reportId;
        document.getElementById('warnShopName').textContent = shopName;
        document.getElementById('warnModal').classList.remove('hidden');
    }
    function closeWarnModal() {
        document.getElementById('warnModal').classList.add('hidden');
    }

    function openSuspendModal(shopId, shopName) {
        document.getElementById('suspendShopId').value = shopId;
        document.getElementById('suspendShopName').textContent = shopName;
        document.getElementById('suspendModal').classList.remove('hidden');
    }
    function closeSuspendModal() {
        document.getElementById('suspendModal').classList.add('hidden');
    }
</script>

<?= $this->endSection() ?>
