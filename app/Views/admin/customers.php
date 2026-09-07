<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="space-y-xl">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-md rounded-xl bg-green-100 text-green-800 text-sm"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-md rounded-xl bg-error-container text-on-error-container text-sm"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <!-- Summary Cards — prototype: Total Registered, Active Today, New This Week — no spend -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow">
            <div class="flex justify-between items-start mb-2"><p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Total Registered Customers</p><span class="material-symbols-outlined text-primary">group</span></div>
            <h3 class="text-headline-lg font-bold text-on-surface"><?= number_format($total_count ?? count($customers ?? [])) ?></h3>
            <p class="text-xs text-emerald-600 flex items-center gap-xs mt-xs"><span class="material-symbols-outlined text-sm">trending_up</span> All registered users</p>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow">
            <div class="flex justify-between items-start mb-2"><p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Active Today</p><span class="material-symbols-outlined text-tertiary">how_to_reg</span></div>
            <h3 class="text-headline-lg font-bold text-on-surface"><?= number_format($active_today ?? 0) ?></h3>
            <p class="text-xs text-on-surface-variant mt-xs">Logged in today</p>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 soft-shadow">
            <div class="flex justify-between items-start mb-2"><p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">New This Week</p><span class="material-symbols-outlined text-secondary">person_add</span></div>
            <h3 class="text-headline-lg font-bold text-on-surface"><?= number_format($new_this_week ?? 0) ?></h3>
            <p class="text-xs text-on-surface-variant mt-xs">Joined in last 7 days</p>
        </div>
    </section>

    <section class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 soft-shadow overflow-hidden">
        <div class="p-6 border-b border-outline-variant/20">
            <h3 class="text-title-lg font-bold text-on-surface">Customers Data Table</h3>
            <p class="text-xs text-on-surface-variant">Oversee all registered customers — total spend is confidential and hidden.</p>
        </div>
        <div class="px-6 py-4 border-b border-outline-variant/20 bg-surface-container-low/50 flex flex-wrap gap-sm items-center">
            <form method="get" action="<?= base_url('admin/customers') ?>" class="flex flex-wrap gap-sm flex-1">
                <div class="relative flex-1 min-w-0 sm:min-w-[220px]">
                    <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                    <input name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Search customers..." class="w-full pl-xl pr-md py-sm bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                </div>
                <select name="status" class="bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-sm text-sm">
                    <option value="">All Status</option>
                    <option value="active" <?= ($filters['status']??'')==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= ($filters['status']??'')==='inactive'?'selected':'' ?>>Inactive</option>
                    <option value="suspended" <?= ($filters['status']??'')==='suspended'?'selected':'' ?>>Suspended</option>
                    <option value="pending" <?= ($filters['status']??'')==='pending'?'selected':'' ?>>Pending</option>
                </select>
                <button type="submit" class="bg-primary text-on-primary px-md py-sm rounded-lg text-sm font-semibold">Filter</button>
                <a href="<?= base_url('admin/customers') ?>" class="px-md py-sm text-sm text-on-surface-variant hover:underline">Reset</a>
            </form>
        </div>
        <div class="responsive-table">
            <table class="w-full text-left border-collapse">
                <thead><tr class="bg-surface-container-low/50 text-label-sm text-on-surface-variant/70 uppercase">
                    <th class="py-sm px-md">Customer Name</th><th class="py-sm px-md">Email</th><th class="py-sm px-md">Total Orders</th><th class="py-sm px-md">Status</th><th class="py-sm px-md">Date Joined</th><th class="py-sm px-md text-right">Actions</th>
                </tr></thead>
                <tbody>
                    <?php if (!empty($customers)): foreach ($customers as $c): ?>
                        <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low">
                            <td class="py-md px-md font-semibold flex items-center gap-sm">
                                <span class="w-8 h-8 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center text-xs font-bold"><?= esc(mb_strtoupper(mb_substr(($c['first_name']??'') . ($c['last_name']??''),0,2) ?: 'CU')) ?></span>
                                <?= esc(($c['first_name']??'') . ' ' . ($c['last_name']??'')) ?>
                            </td>
                            <td class="py-md px-md text-xs"><?= esc($c['email']) ?><p class="text-[11px] text-on-surface-variant"><?= esc($c['phone'] ?? 'N/A') ?></p></td>
                            <td class="py-md px-md font-bold text-primary"><?= esc($c['order_count'] ?? 0) ?></td>
                            <td class="py-md px-md"><?= status_badge($c['status'] ?? 'active') ?></td>
                            <td class="py-md px-md text-xs text-on-surface-variant"><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                            <td class="py-md px-md text-right">
                                <?php $customerStatus = $c['status'] ?? 'active'; ?>
                                <?php if (in_array($customerStatus, ['active', 'inactive'], true)): ?>
                                    <div class="relative inline-block">
                                        <button type="button" data-row="<?= (int) $c['id'] ?>" onclick="toggleCustomerMenu(this)" class="customer-more-toggle p-xs hover:bg-surface-container-high rounded text-on-surface-variant" title="Customer actions" aria-haspopup="true" aria-expanded="false">
                                            <span class="material-symbols-outlined">more_vert</span>
                                        </button>
                                        <div id="customer-more-menu-<?= (int) $c['id'] ?>" class="hidden customer-more-menu z-50 bg-surface-container-lowest border border-outline-variant/30 rounded-xl shadow-lg p-sm min-w-[210px]" role="menu">
                                            <form action="<?= base_url('admin/customers/toggle-status') ?>" method="POST">
                                                <?= csrf_field() ?><input type="hidden" name="customer_id" value="<?= (int) $c['id'] ?>">
                                                <?php if ($customerStatus === 'active'): ?>
                                                    <button type="submit" class="w-full flex items-center gap-sm px-sm py-sm rounded-lg text-label-sm text-error font-semibold hover:bg-error/10" onclick="return confirm('Deactivate this customer account?');">
                                                        <span class="material-symbols-outlined text-[18px]">block</span> Deactivate Account
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="w-full flex items-center gap-sm px-sm py-sm rounded-lg text-label-sm text-primary font-semibold hover:bg-primary/10" onclick="return confirm('Activate this customer account?');">
                                                        <span class="material-symbols-outlined text-[18px]">check_circle</span> Activate Account
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-on-surface-variant">â€”</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="py-lg text-center text-on-surface-variant">No customers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 bg-surface-container-low/30 border-t border-outline-variant/20 text-xs text-on-surface-variant">Showing <?= count($customers ?? []) ?> of <?= esc($total_count ?? count($customers ?? [])) ?> results</div>
    </section>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    let activeCustomerMenu = null;

    function closeCustomerMenus() {
        if (activeCustomerMenu) {
            activeCustomerMenu.classList.add('hidden');
            activeCustomerMenu = null;
        }
        document.querySelectorAll('.customer-more-toggle[aria-expanded="true"]').forEach((button) => button.setAttribute('aria-expanded', 'false'));
    }

    function toggleCustomerMenu(button) {
        const menu = document.getElementById('customer-more-menu-' + button.dataset.row);
        if (!menu) return;
        if (activeCustomerMenu === menu) {
            closeCustomerMenus();
            return;
        }
        closeCustomerMenus();
        document.body.appendChild(menu);
        menu.classList.remove('hidden');
        menu.style.position = 'fixed';
        const rect = button.getBoundingClientRect();
        const gap = 8;
        menu.style.left = Math.max(gap, Math.min(rect.right - menu.offsetWidth, window.innerWidth - menu.offsetWidth - gap)) + 'px';
        if (rect.bottom + gap + menu.offsetHeight <= window.innerHeight - gap) {
            menu.style.top = (rect.bottom + gap) + 'px';
            menu.style.bottom = '';
        } else {
            menu.style.top = '';
            menu.style.bottom = (window.innerHeight - rect.top + gap) + 'px';
        }
        activeCustomerMenu = menu;
        button.setAttribute('aria-expanded', 'true');
    }

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.customer-more-menu') && !event.target.closest('.customer-more-toggle')) closeCustomerMenus();
    });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeCustomerMenus(); });
    window.addEventListener('scroll', closeCustomerMenus, true);
    window.addEventListener('resize', closeCustomerMenus);
</script>
<?= $this->endSection() ?>
