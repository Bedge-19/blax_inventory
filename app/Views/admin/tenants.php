<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="space-y-xl">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-md rounded-xl bg-green-100 text-green-800 text-sm"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-md rounded-xl bg-error-container text-on-error-container text-sm"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('warning')): ?>
        <div class="p-md rounded-xl bg-amber-100 text-amber-800 text-sm"><?= esc(session()->getFlashdata('warning')) ?></div>
    <?php endif; ?>

    <!-- Bento Stats — prototype: Total Active, Pending Approvals — no revenue -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow">
            <div class="flex justify-between items-start mb-2"><p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Total Tenants</p><span class="material-symbols-outlined text-primary">storefront</span></div>
            <h3 class="text-headline-lg font-bold text-on-surface"><?= esc($total_count ?? count($tenants ?? [])) ?></h3>
            <p class="text-xs text-emerald-600 flex items-center gap-xs mt-xs"><span class="material-symbols-outlined text-sm">trending_up</span> All registered shops</p>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow">
            <div class="flex justify-between items-start mb-2"><p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Active Tenants</p><span class="material-symbols-outlined text-tertiary">verified</span></div>
            <h3 class="text-headline-lg font-bold text-on-surface"><?= esc($active_count ?? 0) ?></h3>
            <p class="text-xs text-on-surface-variant mt-xs">Verified & operating</p>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow">
            <div class="flex justify-between items-start mb-2"><p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Pending Approvals</p><span class="material-symbols-outlined text-error">pending_actions</span></div>
            <h3 class="text-headline-lg font-bold text-on-surface"><?= esc($pending_count ?? 0) ?></h3>
            <p class="text-xs text-error mt-xs">Needs review in next 48h</p>
        </div>
    </section>

    <!-- Data Table — prototype toolbar -->
    <section class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 soft-shadow overflow-hidden">
        <div class="p-6 border-b border-outline-variant/20 flex flex-wrap items-start justify-between gap-md">
            <h3 class="text-title-lg font-bold text-on-surface">Tenants Data Table</h3>
            <p class="text-xs text-on-surface-variant">Monitor and manage all active shops — no financial data displayed.</p>
            <?php if (!empty($pending_count)): ?>
                <span class="inline-flex items-center gap-xs px-md py-sm rounded-full bg-amber-100 text-amber-800 text-xs font-bold">
                    <span class="material-symbols-outlined text-[16px]">pending_actions</span>
                    <?= (int) $pending_count ?> awaiting review
                </span>
            <?php endif; ?>
        </div>
        <div class="px-6 py-4 border-b border-outline-variant/20 bg-surface-container-low/50 flex flex-wrap gap-sm items-center">
            <form method="get" action="<?= base_url('admin/tenants') ?>" class="flex flex-wrap gap-sm flex-1">
                <div class="relative flex-1 min-w-0 sm:min-w-[220px]">
                    <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                    <input name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Search shops, owners, or IDs..." class="w-full pl-xl pr-md py-sm bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                </div>
                <select name="status" class="bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-sm text-sm">
                    <option value="">All Status</option>
                    <option value="active" <?= ($filters['status']??'')==='active'?'selected':'' ?>>Active</option>
                    <option value="pending" <?= ($filters['status']??'')==='pending'?'selected':'' ?>>Pending</option>
                    <option value="suspended" <?= ($filters['status']??'')==='suspended'?'selected':'' ?>>Suspended</option>
                    <option value="rejected" <?= ($filters['status']??'')==='rejected'?'selected':'' ?>>Rejected</option>
                </select>
                <button type="submit" class="bg-primary text-on-primary px-md py-sm rounded-lg text-sm font-semibold">Filter</button>
                <a href="<?= base_url('admin/tenants') ?>" class="px-md py-sm text-sm text-on-surface-variant hover:underline">Reset</a>
            </form>
        </div>
        <div class="responsive-table">
            <table class="w-full text-left border-collapse">
                <thead><tr class="bg-surface-container-low/50 text-label-sm text-on-surface-variant/70 uppercase">
                    <th class="py-sm px-md">Shop Name</th><th class="py-sm px-md">Owner</th><th class="py-sm px-md">Permit</th><th class="py-sm px-md">Date Joined</th><th class="py-sm px-md text-center">Status</th><th class="py-sm px-md text-right">Actions</th>
                </tr></thead>
                <tbody>
                    <?php if (!empty($tenants)): foreach ($tenants as $t): ?>
                        <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low">
                            <td class="py-md px-md font-semibold"><?= esc($t['shop_name']) ?><p class="text-xs text-on-surface-variant font-normal">ID: TEN-<?= esc(str_pad($t['id'],3,'0',STR_PAD_LEFT)) ?></p></td>
                            <td class="py-md px-md text-sm"><?= esc(($t['first_name']??'') . ' ' . ($t['last_name']??'')) ?><p class="text-xs text-on-surface-variant"><?= esc($t['email']??'') ?></p></td>
                            <td class="py-md px-md text-xs">
                                <?php if (!empty($t['business_permit_url'])): ?>
                                    <button type="button" data-permit-url="<?= esc(base_url('admin/tenants/permit/' . (int) $t['id']), 'attr') ?>" data-shop-name="<?= esc($t['shop_name'], 'attr') ?>" onclick="openPermitModal(this)" class="inline-flex items-center gap-xs text-primary font-semibold hover:underline">
                                        <span class="material-symbols-outlined text-[18px]">description</span> View
                                    </button>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-xs text-error font-semibold"><span class="material-symbols-outlined text-[18px]">warning</span> No permit</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-md px-md text-xs text-on-surface-variant"><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                            <td class="py-md px-md text-center"><?= status_badge($t['status'] ?? 'active') ?></td>
                            <td class="py-md px-md text-right">
                                <?php $tenantStatus = $t['status'] ?? 'active'; ?>
                                <?php if (in_array($tenantStatus, ['pending', 'active', 'suspended'], true)): ?>
                                    <div class="relative inline-block">
                                        <button type="button" data-row="<?= (int) $t['id'] ?>" onclick="toggleTenantMenu(this)" class="tenant-more-toggle p-xs hover:bg-surface-container-high rounded text-on-surface-variant" title="Tenant actions" aria-haspopup="true" aria-expanded="false">
                                            <span class="material-symbols-outlined">more_vert</span>
                                        </button>
                                        <div id="tenant-more-menu-<?= (int) $t['id'] ?>" class="hidden tenant-more-menu z-50 bg-surface-container-lowest border border-outline-variant/30 rounded-xl shadow-lg p-sm min-w-[190px]" role="menu">
                                            <?php if (!empty($t['business_permit_url'])): ?>
                                                <button type="button" data-permit-url="<?= esc(base_url('admin/tenants/permit/' . (int) $t['id']), 'attr') ?>" data-shop-name="<?= esc($t['shop_name'], 'attr') ?>" onclick="openPermitModal(this)" class="w-full flex items-center gap-sm px-sm py-sm rounded-lg text-left text-label-sm text-on-surface hover:bg-surface-container-low">
                                                    <span class="material-symbols-outlined text-[18px]">description</span> View Permit
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($tenantStatus === 'pending'): ?>
                                                <form action="<?= base_url('admin/tenants/approve') ?>" method="POST" class="border-t border-outline-variant/20 mt-xs pt-xs">
                                                    <?= csrf_field() ?><input type="hidden" name="tenant_id" value="<?= (int) $t['id'] ?>">
                                                    <button type="submit" class="w-full flex items-center gap-sm px-sm py-sm rounded-lg text-label-sm text-primary font-semibold hover:bg-primary/10" onclick="return confirm('Approve this tenant?');">
                                                        <span class="material-symbols-outlined text-[18px]">verified</span> Approve Tenant
                                                    </button>
                                                </form>
                                                <form action="<?= base_url('admin/tenants/reject') ?>" method="POST" class="space-y-xs border-t border-outline-variant/20 mt-xs pt-xs">
                                                    <?= csrf_field() ?><input type="hidden" name="tenant_id" value="<?= (int) $t['id'] ?>">
                                                    <textarea name="rejection_reason" required maxlength="255" rows="2" class="w-full p-sm bg-surface-container-low border border-outline-variant rounded-lg text-label-sm" placeholder="Rejection reason"></textarea>
                                                    <button type="submit" class="w-full flex items-center justify-center gap-sm px-sm py-sm rounded-lg text-label-sm text-error font-semibold hover:bg-error/10" onclick="return confirm('Reject this tenant application?');">
                                                        <span class="material-symbols-outlined text-[18px]">cancel</span> Reject Tenant
                                                    </button>
                                                </form>
                                            <?php elseif ($tenantStatus === 'active'): ?>
                                                <form action="<?= base_url('admin/tenants/toggle-status') ?>" method="POST" class="border-t border-outline-variant/20 mt-xs pt-xs">
                                                    <?= csrf_field() ?><input type="hidden" name="tenant_id" value="<?= (int) $t['id'] ?>">
                                                    <button type="submit" class="w-full flex items-center gap-sm px-sm py-sm rounded-lg text-label-sm text-error font-semibold hover:bg-error/10"><span class="material-symbols-outlined text-[18px]">block</span> Suspend Tenant</button>
                                                </form>
                                            <?php elseif ($tenantStatus === 'suspended'): ?>
                                                <form action="<?= base_url('admin/tenants/toggle-status') ?>" method="POST" class="border-t border-outline-variant/20 mt-xs pt-xs">
                                                    <?= csrf_field() ?><input type="hidden" name="tenant_id" value="<?= (int) $t['id'] ?>">
                                                    <button type="submit" class="w-full flex items-center gap-sm px-sm py-sm rounded-lg text-label-sm text-primary font-semibold hover:bg-primary/10"><span class="material-symbols-outlined text-[18px]">check_circle</span> Activate Tenant</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php elseif ($tenantStatus === 'rejected'): ?>
                                    <div class="text-left max-w-[220px]"><span class="text-xs text-error">Rejected<?= !empty($t['rejection_reason']) ? ': ' . esc($t['rejection_reason']) : '' ?></span></div>
                                <?php else: ?>
                                    <span class="text-xs text-on-surface-variant">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="py-lg text-center text-on-surface-variant">No tenant shops found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (isset($pager)): ?>
            <?php
            $total   = (int) $pager->getTotal('tenants');
            $perPage = 15;
            $cur     = (int) $pager->getCurrentPage('tenants');
            $pages   = (int) $pager->getPageCount('tenants');
            $start   = $total === 0 ? 0 : ($cur - 1) * $perPage + 1;
            $end     = min($cur * $perPage, $total);
            ?>
            <div class="px-6 py-3 bg-surface-container-low/30 flex justify-between items-center border-t border-outline-variant/20 flex-wrap gap-sm">
                <p class="text-xs text-on-surface-variant">Showing <?= number_format($start) ?> to <?= number_format($end) ?> of <?= number_format($total) ?> results · Confidential revenue hidden</p>
                <?php if ($pages > 1): ?>
                    <div class="flex items-center gap-xs">
                        <a class="p-sm rounded hover:bg-surface-container-high <?= $cur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('tenants') ?>" title="Previous">
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
                            <a class="w-8 h-8 rounded flex items-center justify-center text-xs <?= $cur === $num ? 'bg-primary text-on-primary font-semibold' : 'hover:bg-surface-container-high text-on-surface' ?>" href="<?= $pager->getPageURI($num, 'tenants') ?>"><?= $num ?></a>
                        <?php $prev = $num; endforeach; ?>
                        <a class="p-sm rounded hover:bg-surface-container-high <?= $cur >= $pages ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('tenants') ?>" title="Next">
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="px-6 py-3 bg-surface-container-low/30 border-t border-outline-variant/20 text-xs text-on-surface-variant">Showing 1 to <?= count($tenants ?? []) ?> of <?= esc($total_count ?? count($tenants ?? [])) ?> results · Confidential revenue hidden</div>
        <?php endif; ?>
    </section>

</div>

<div id="permit-preview-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-md" role="dialog" aria-modal="true" aria-labelledby="permit-preview-title" aria-hidden="true">
    <div class="absolute inset-0 bg-black/50" data-permit-modal-close></div>
    <div class="relative flex h-[calc(100vh_-_2rem)] max-h-[850px] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-2xl">
        <div class="flex flex-wrap items-center justify-between gap-sm border-b border-outline-variant/20 px-md py-sm">
            <h3 id="permit-preview-title" class="text-title-lg font-bold text-on-surface">Business Permit</h3>
            <div class="flex items-center gap-xs">
                <a id="permit-preview-open-tab" href="#" target="_blank" rel="noopener" class="inline-flex items-center gap-xs rounded-lg px-sm py-xs text-label-sm font-semibold text-primary hover:bg-primary/10">
                    <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                    Open in new tab
                </a>
                <button type="button" data-permit-modal-close class="rounded-full p-xs text-on-surface-variant hover:bg-surface-container-high" aria-label="Close permit preview">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        <div class="min-h-0 flex-1 bg-surface-container-low">
            <iframe id="permit-preview-frame" src="about:blank" title="Business permit preview" class="h-full w-full border-0 bg-white"></iframe>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    let activeTenantMenu = null;
    let permitModalTrigger = null;

    function openPermitModal(button) {
        const modal = document.getElementById('permit-preview-modal');
        const frame = document.getElementById('permit-preview-frame');
        const title = document.getElementById('permit-preview-title');
        const openInNewTab = document.getElementById('permit-preview-open-tab');
        const permitUrl = button.dataset.permitUrl;

        if (!modal || !frame || !title || !openInNewTab || !permitUrl) return;

        closeTenantMenus();
        permitModalTrigger = button;
        title.textContent = button.dataset.shopName ? button.dataset.shopName + ' — Business Permit' : 'Business Permit';
        openInNewTab.href = permitUrl;
        frame.src = permitUrl;
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');

        const closeButton = modal.querySelector('[data-permit-modal-close]');
        if (closeButton) closeButton.focus();
    }

    function closePermitModal() {
        const modal = document.getElementById('permit-preview-modal');
        const frame = document.getElementById('permit-preview-frame');

        if (!modal || modal.classList.contains('hidden')) return false;

        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        if (frame) frame.src = 'about:blank';
        if (permitModalTrigger && permitModalTrigger.getClientRects().length) permitModalTrigger.focus();
        permitModalTrigger = null;
        return true;
    }

    function closeTenantMenus() {
        if (activeTenantMenu) {
            activeTenantMenu.classList.add('hidden');
            activeTenantMenu = null;
        }
        document.querySelectorAll('.tenant-more-toggle[aria-expanded="true"]').forEach((button) => button.setAttribute('aria-expanded', 'false'));
    }

    function toggleTenantMenu(button) {
        const menu = document.getElementById('tenant-more-menu-' + button.dataset.row);
        if (!menu) return;
        if (activeTenantMenu === menu) {
            closeTenantMenus();
            return;
        }
        closeTenantMenus();
        document.body.appendChild(menu);
        menu.classList.remove('hidden');
        menu.style.position = 'fixed';
        const rect = button.getBoundingClientRect();
        const gap = 8;
        const left = rect.right + menu.offsetWidth <= window.innerWidth - gap
            ? rect.right - menu.offsetWidth
            : Math.max(gap, window.innerWidth - menu.offsetWidth - gap);
        menu.style.left = left + 'px';
        if (rect.bottom + gap + menu.offsetHeight <= window.innerHeight - gap) {
            menu.style.top = (rect.bottom + gap) + 'px';
            menu.style.bottom = '';
        } else {
            menu.style.top = '';
            menu.style.bottom = (window.innerHeight - rect.top + gap) + 'px';
        }
        activeTenantMenu = menu;
        button.setAttribute('aria-expanded', 'true');
    }

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-permit-modal-close]')) {
            closePermitModal();
            return;
        }
        if (!event.target.closest('.tenant-more-menu') && !event.target.closest('.tenant-more-toggle')) closeTenantMenus();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !closePermitModal()) closeTenantMenus();
    });
    window.addEventListener('scroll', closeTenantMenus, true);
    window.addEventListener('resize', closeTenantMenus);
</script>
<?= $this->endSection() ?>
