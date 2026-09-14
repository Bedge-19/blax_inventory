<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="space-y-xl">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-md rounded-xl bg-green-100 text-green-800 text-sm"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-md rounded-xl bg-error-container text-on-error-container text-sm"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <!-- Summary Cards — prototype payments -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow">
            <div class="flex justify-between items-start mb-2"><p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Pending GCash</p><span class="material-symbols-outlined text-error">pending_actions</span></div>
            <h3 class="text-headline-md font-bold text-error">₱<?= number_format($pending_total ?? 0, 2) ?></h3>
            <p class="text-xs text-on-surface-variant mt-xs"><?= esc($pending_count ?? 0) ?> requests · GCash only</p>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow">
            <div class="flex justify-between items-start mb-2"><p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Completed GCash</p><span class="material-symbols-outlined text-emerald-600">check_circle</span></div>
            <h3 class="text-headline-md font-bold text-emerald-700">₱<?= number_format($completed_total ?? 0, 2) ?></h3>
            <p class="text-xs text-on-surface-variant mt-xs">Total disbursed via GCash</p>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow">
            <div class="flex justify-between items-start mb-2"><p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Admin Fee (3%)</p><span class="material-symbols-outlined text-primary">payments</span></div>
            <h3 class="text-headline-md font-bold text-primary">3%</h3>
            <p class="text-xs text-on-surface-variant mt-xs">Deducted on each GCash withdrawal</p>
        </div>
    </section>

    <section class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 soft-shadow overflow-hidden">
        <div class="p-6 border-b border-outline-variant/20">
            <h3 class="text-title-lg font-bold text-on-surface">GCash Payment Requests</h3>
            <p class="text-xs text-on-surface-variant">Manage and approve merchant payout requests — GCash transactions only.</p>
        </div>
        <div class="px-6 py-4 border-b border-outline-variant/20 bg-surface-container-low/50 flex flex-wrap gap-sm items-center">
            <form method="get" action="<?= base_url('admin/payments') ?>" class="flex flex-wrap gap-sm flex-1">
                <div class="relative flex-1 min-w-0 sm:min-w-[220px]">
                    <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                    <input name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Search pending requests..." class="w-full pl-xl pr-md py-sm bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                </div>
                <select name="status" class="bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-sm text-sm">
                    <option value="">All Status</option>
                    <option value="pending" <?= ($filters['status']??'')==='pending'?'selected':'' ?>>Pending</option>
                    <option value="processing" <?= ($filters['status']??'')==='processing'?'selected':'' ?>>In Progress</option>
                    <option value="completed" <?= ($filters['status']??'')==='completed'?'selected':'' ?>>Completed</option>
                    <option value="failed" <?= ($filters['status']??'')==='failed'?'selected':'' ?>>Failed</option>
                </select>
                <button type="submit" class="bg-primary text-on-primary px-md py-sm rounded-lg text-sm font-semibold">Filter</button>
                <a href="<?= base_url('admin/payments') ?>" class="px-md py-sm text-sm text-on-surface-variant hover:underline">Reset</a>
            </form>
        </div>
        <div class="responsive-table">
            <table class="w-full text-left border-collapse">
                <thead><tr class="bg-surface-container-low/50 text-label-sm text-on-surface-variant/70 uppercase">
                    <th class="py-sm px-md">Request ID</th><th class="py-sm px-md">Merchant</th><th class="py-sm px-md">Amount</th><th class="py-sm px-md">GCash Fee (3%)</th><th class="py-sm px-md">Method</th><th class="py-sm px-md">Date Requested</th><th class="py-sm px-md">Status</th><th class="py-sm px-md text-right">Actions</th>
                </tr></thead>
                <tbody>
                    <?php if (!empty($payment_requests)): foreach ($payment_requests as $p): ?>
                        <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low">
                            <td class="py-md px-md font-mono text-sm font-semibold text-primary">#<?= esc($p['reference_number'] ?? ('WD-'.$p['id'])) ?></td>
                            <td class="py-md px-md text-sm"><?= esc($p['shop_name'] ?? 'Merchant') ?></td>
                            <td class="py-md px-md font-bold text-on-surface">₱<?= number_format($p['amount'],2) ?></td>
                            <td class="py-md px-md text-xs text-tertiary font-semibold">₱<?= number_format(($p['fee'] ?? 0) >0 ? $p['fee'] : $p['amount']*0.03,2) ?></td>
                            <td class="py-md px-md"><span class="inline-flex items-center gap-xs px-sm py-xs bg-primary-container/30 text-on-primary-container rounded-full text-xs font-bold"><span class="material-symbols-outlined text-xs">phone_iphone</span> GCash</span></td>
                            <td class="py-md px-md text-xs text-on-surface-variant"><?= date('M d, Y H:i', strtotime($p['requested_at'] ?? $p['created_at'] ?? 'now')) ?></td>
                            <td class="py-md px-md"><?= status_badge($p['status'] ?? 'pending') ?></td>
<td class="py-md px-md text-right">
                                <?php
                                    $status = $p['status'] ?? 'pending';
                                    $showDropdown = in_array($status, ['pending', 'processing'], true);
                                    $options = [
                                        'processing' => 'In Progress',
                                        'completed'  => 'Completed',
                                        'failed'     => 'Reject',
                                    ];
                                ?>
                                <?php if ($showDropdown): ?>
                                    <div class="relative">
                                        <button type="button" data-row="<?= (int) $p['id'] ?>" onclick="toggleDropdown(this)" class="more-toggle p-xs hover:bg-surface-container-high rounded text-on-surface-variant" title="Update Status" aria-haspopup="true" aria-expanded="false">
                                            <span class="material-symbols-outlined">more_vert</span>
                                        </button>
                                        <div id="more-menu-<?= (int) $p['id'] ?>" class="hidden more-menu z-50 bg-surface-container-lowest border border-outline-variant/30 rounded-xl shadow-lg p-sm min-w-[160px]" role="menu">
                                            <p class="text-label-sm font-bold text-on-surface-variant px-sm pb-xs">Update Status</p>
                                            <form action="<?= base_url('admin/payments/update-status') ?>" method="POST" class="space-y-xs px-sm pb-sm">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="withdrawal_id" value="<?= (int) $p['id'] ?>">
                                                <select name="status" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-lg text-label-sm font-label-sm">
                                                    <?php foreach ($options as $value => $label): ?>
                                                        <?php if ($value === $status) continue; ?>
                                                        <option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>><?= $label ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="w-full py-sm bg-primary text-on-primary rounded-lg text-label-sm font-semibold hover:bg-primary/90">Apply Status</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-on-surface-variant">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="py-lg text-center text-on-surface-variant">No GCash payment requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (isset($pager)): ?>
            <?php
            $total   = (int) $pager->getTotal('payments');
            $perPage = 15;
            $cur     = (int) $pager->getCurrentPage('payments');
            $pages   = (int) $pager->getPageCount('payments');
            $start   = $total === 0 ? 0 : ($cur - 1) * $perPage + 1;
            $end     = min($cur * $perPage, $total);
            ?>
            <div class="px-6 py-3 bg-surface-container-low/30 flex justify-between items-center border-t border-outline-variant/20 flex-wrap gap-sm">
                <p class="text-xs text-on-surface-variant">Showing <?= number_format($start) ?> to <?= number_format($end) ?> of <?= number_format($total) ?> GCash entries</p>
                <?php if ($pages > 1): ?>
                    <div class="flex items-center gap-xs">
                        <a class="p-sm rounded hover:bg-surface-container-high <?= $cur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('payments') ?>" title="Previous">
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
                            <a class="w-8 h-8 rounded flex items-center justify-center text-xs <?= $cur === $num ? 'bg-primary text-on-primary font-semibold' : 'hover:bg-surface-container-high text-on-surface' ?>" href="<?= $pager->getPageURI($num, 'payments') ?>"><?= $num ?></a>
                        <?php $prev = $num; endforeach; ?>
                        <a class="p-sm rounded hover:bg-surface-container-high <?= $cur >= $pages ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('payments') ?>" title="Next">
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="px-6 py-3 bg-surface-container-low/30 border-t border-outline-variant/20 text-xs text-on-surface-variant">Showing <?= count($payment_requests ?? []) ?> GCash entries</div>
        <?php endif; ?>
    </section>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    let activeMenu = null;

    function closeMenus() {
        if (activeMenu) {
            activeMenu.classList.add('hidden');
            activeMenu = null;
        }
        document.querySelectorAll('.more-toggle[aria-expanded="true"]').forEach((b) => b.setAttribute('aria-expanded', 'false'));
    }

    function toggleDropdown(btn) {
        const id   = btn.dataset.row;
        const menu = document.getElementById('more-menu-' + id);
        if (!menu) return;

        if (activeMenu === menu) {
            closeMenus();
            return;
        }
        closeMenus();

        document.body.appendChild(menu);
        menu.classList.remove('hidden');
        menu.style.position = 'fixed';
        menu.style.left = '';
        menu.style.right = '';
        menu.style.top = '';
        menu.style.bottom = '';

        const rect       = btn.getBoundingClientRect();
        const gap        = 8;
        const menuWidth  = menu.offsetWidth;
        const menuHeight = menu.offsetHeight;

        const left = rect.left + menuWidth <= window.innerWidth - gap
            ? rect.left
            : Math.max(gap, window.innerWidth - menuWidth - gap);
        menu.style.left = left + 'px';

        if (rect.bottom + gap + menuHeight <= window.innerHeight - gap) {
            menu.style.top = (rect.bottom + gap) + 'px';
        } else {
            menu.style.bottom = (window.innerHeight - rect.top + gap) + 'px';
        }

        activeMenu = menu;
        btn.setAttribute('aria-expanded', 'true');
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.more-menu') && !e.target.closest('.more-toggle')) {
            closeMenus();
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeMenus();
    });
    window.addEventListener('scroll', closeMenus, true);
    window.addEventListener('resize', closeMenus);
</script>
<?= $this->endSection() ?>
