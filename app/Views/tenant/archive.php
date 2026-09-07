<?= $this->extend('layouts/tenant') ?>
<?= $this->section('content') ?>

<div class="flex-1 space-y-lg">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-md rounded-xl bg-green-100 text-green-800 text-sm font-medium mb-lg"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-md rounded-xl bg-error-container/40 text-on-error-container text-sm font-medium mb-lg"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <!-- Header -->
    <div>
        <h2 class="text-headline-md font-bold text-on-surface">Archive & Recovery</h2>
        <p class="text-body-md text-on-surface-variant">Restore archived orders, inventory items, and printing requests.</p>
    </div>

    <!-- Tabs + Search -->
    <div class="flex flex-wrap justify-between items-center gap-md">
        <div class="inline-flex items-center gap-xs bg-surface-container-low rounded-xl p-xs border border-outline-variant/20" role="tablist">
            <button type="button" data-tab="all" class="archive-tab px-md py-sm rounded-lg text-label-sm font-semibold transition-colors" role="tab">All</button>
            <button type="button" data-tab="order" class="archive-tab px-md py-sm rounded-lg text-label-sm font-semibold transition-colors" role="tab">Orders</button>
            <button type="button" data-tab="inventory" class="archive-tab px-md py-sm rounded-lg text-label-sm font-semibold transition-colors" role="tab">Inventory</button>
            <button type="button" data-tab="printing_request" class="archive-tab px-md py-sm rounded-lg text-label-sm font-semibold transition-colors" role="tab">Printing</button>
        </div>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
            <input id="archiveSearch" type="text" placeholder="Search archived items..." class="pl-xl pr-md py-sm bg-surface-container-low border border-outline-variant rounded-lg text-body-md focus:outline-none focus:ring-2 focus:ring-primary w-64 max-w-full">
        </div>
    </div>

    <!-- Archive Table -->
    <div class="glass-card rounded-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low border-b border-outline-variant/30">
                    <tr>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Item Type</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">ID / Name</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Archive Date</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Archived By</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20 archive-table-body">
                    <?php if (!empty($archivedItems)): ?>
                        <?php foreach ($archivedItems as $p): ?>
                            <?php
                            $type = $p['item_type'] ?? 'order';
                            $typeBadges = [
                                'order'            => 'bg-primary/10 text-primary',
                                'inventory'        => 'bg-secondary-container/40 text-secondary',
                                'printing_request' => 'bg-tertiary-container/10 text-tertiary-container',
                            ];
                            $typeLabels = [
                                'order'            => 'Order',
                                'inventory'        => 'Inventory',
                                'printing_request' => 'Printing',
                            ];
                            $archiverName = trim(($p['archived_by_first'] ?? '') . ' ' . ($p['archived_by_last'] ?? ''));
                            $archiverInitials = $archiverName !== '' ? mb_strtoupper(mb_substr($archiverName, 0, 2)) : 'SY';
                            $archiverImage = trim((string) ($p['archived_by_image'] ?? ''));
                            $searchText = strtolower(($p['item_label'] ?? '') . ' ' . ($p['item_id'] ?? ''));
                            ?>
                            <tr class="hover:bg-surface-container-low/50 transition-colors archive-row" data-type="<?= esc($type) ?>" data-search="<?= esc($searchText) ?>">
                                <td class="px-lg py-md">
                                    <span class="inline-flex items-center px-sm py-xs rounded-full text-[11px] font-semibold uppercase tracking-wide <?= $typeBadges[$type] ?? 'bg-surface-variant text-on-surface-variant' ?>"><?= esc($typeLabels[$type] ?? ucfirst(str_replace('_', ' ', $type))) ?></span>
                                </td>
                                <td class="px-lg py-md">
                                    <span class="text-body-md font-semibold text-on-surface"><?= esc($p['item_label'] ?? ('Item #' . $p['item_id'])) ?></span>
                                    <p class="text-[11px] text-on-surface-variant">#<?= (int) $p['item_id'] ?></p>
                                </td>
                                <td class="px-lg py-md text-body-md text-on-surface-variant"><?= date('M d, Y h:i A', strtotime($p['archived_at'] ?? 'now')) ?></td>
                                <td class="px-lg py-md">
                                    <div class="flex items-center gap-sm">
                                        <div class="w-8 h-8 rounded-full bg-tertiary-container text-on-tertiary-container flex items-center justify-center text-label-sm font-bold <?= $archiverImage !== '' ? 'relative overflow-hidden' : '' ?>">
                                            <span><?= esc($archiverInitials) ?></span>
                                            <?php if ($archiverImage !== ''): ?>
                                                <img class="absolute inset-0 w-full h-full object-cover" src="<?= esc(base_url($archiverImage)) ?>" alt="<?= esc($archiverName) ?> avatar" loading="lazy" onerror="this.remove();">
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-body-md text-on-surface"><?= esc($archiverName !== '' ? $archiverName : 'System') ?></span>
                                    </div>
                                </td>
                                <td class="px-lg py-md text-right">
                                    <form action="<?= base_url('tenant/archive/restore/' . (int) $p['id']) ?>" method="POST"
                                          onsubmit="return confirm('Restore this item to your active records?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="inline-flex items-center gap-1 px-md py-sm bg-primary text-on-primary rounded-lg text-label-sm font-bold hover:bg-primary/90 transition-colors">
                                            <span class="material-symbols-outlined text-[16px]">restore</span> Restore
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-lg text-center text-on-surface-variant">No archived items found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php
        $aTot  = (int) $pager->getTotal('archive');
        $aCur  = (int) $pager->getCurrentPage('archive');
        $aPag  = (int) $pager->getPageCount('archive');
        $aStart = $aTot === 0 ? 0 : ($aCur - 1) * 10 + 1;
        $aEnd   = min($aCur * 10, $aTot);
        ?>
        <?php if ($aPag > 1): ?>
            <div class="px-lg py-md bg-surface-container-low flex justify-between items-center border-t border-outline-variant/30 flex-wrap gap-sm">
                <p class="text-label-sm font-label-sm text-on-surface-variant">Showing <?= number_format($aStart) ?> to <?= number_format($aEnd) ?> of <?= number_format($aTot) ?> items</p>
                <div class="flex items-center gap-xs">
                    <a class="p-sm rounded hover:bg-surface-container-high <?= $aCur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('archive') ?>">
                        <span class="material-symbols-outlined">chevron_left</span>
                    </a>
                    <?php
                    $w = [];
                    for ($i = 1; $i <= $aPag; $i++) {
                        if ($i === 1 || $i === $aPag || abs($i - $aCur) <= 2) { $w[] = $i; }
                    }
                    $pv = 0;
                    foreach ($w as $n):
                        if ($n - $pv > 1): ?><span class="px-xs text-outline">...</span><?php endif; ?>
                        <a class="w-8 h-8 rounded flex items-center justify-center text-label-sm <?= $aCur === $n ? 'bg-primary text-on-primary font-semibold' : 'hover:bg-surface-container-high' ?>" href="<?= $pager->getPageURI($n, 'archive') ?>"><?= $n ?></a>
                    <?php $pv = $n; endforeach; ?>
                    <a class="p-sm rounded hover:bg-surface-container-high <?= $aCur >= $aPag ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('archive') ?>">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    let archiveTab = 'all';
    const activeTabClasses = 'bg-primary text-on-primary';
    const inactiveTabClasses = 'text-on-surface-variant hover:bg-surface-container-high';

    function applyArchiveFilters() {
        const query = document.getElementById('archiveSearch').value.trim().toLowerCase();
        let visible = 0;
        document.querySelectorAll('.archive-row').forEach((row) => {
            const typeMatch = archiveTab === 'all' || row.dataset.type === archiveTab;
            const searchMatch = query === '' || (row.dataset.search || '').indexOf(query) !== -1;
            const show = typeMatch && searchMatch;
            row.classList.toggle('hidden', !show);
            if (show) visible++;
        });

        let emptyRow = document.getElementById('archiveEmptyRow');
        if (!emptyRow) {
            emptyRow = document.createElement('tr');
            emptyRow.id = 'archiveEmptyRow';
            emptyRow.innerHTML = '<td colspan="5" class="py-lg text-center text-on-surface-variant">No archived items match your filters.</td>';
            document.querySelector('.archive-table-body')?.appendChild(emptyRow);
        }
        emptyRow.classList.toggle('hidden', visible > 0 || document.querySelectorAll('.archive-row').length === 0);
    }

    document.querySelectorAll('.archive-tab').forEach((btn) => {
        btn.addEventListener('click', () => {
            archiveTab = btn.dataset.tab;
            document.querySelectorAll('.archive-tab').forEach((b) => {
                b.classList.remove(activeTabClasses, 'bg-primary/10', 'text-primary');
                b.classList.add(inactiveTabClasses);
            });
            btn.classList.remove(inactiveTabClasses);
            btn.classList.add(activeTabClasses);
            applyArchiveFilters();
        });
    });

    document.querySelectorAll('.archive-tab').forEach((btn) => btn.classList.add(inactiveTabClasses));
    const defaultTab = document.querySelector('.archive-tab[data-tab="all"]');
    if (defaultTab) {
        defaultTab.classList.remove(inactiveTabClasses);
        defaultTab.classList.add(activeTabClasses);
    }

    document.getElementById('archiveSearch').addEventListener('input', applyArchiveFilters);
</script>

<?= $this->endSection() ?>