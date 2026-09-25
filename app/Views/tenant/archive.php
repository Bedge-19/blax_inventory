<?= $this->extend('layouts/tenant') ?>
<?= $this->section('content') ?>

<div class="space-y-xl">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 text-sm font-medium flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-600 text-[20px]">check_circle</span>
            <span><?= session()->getFlashdata('success') ?></span>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-4 rounded-xl bg-error-container/40 border border-error/20 text-on-error-container text-sm font-medium flex items-center gap-2">
            <span class="material-symbols-outlined text-error text-[20px]">error</span>
            <span><?= session()->getFlashdata('error') ?></span>
        </div>
    <?php endif; ?>

    <!-- Header Section with Quick Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">archive</span>
                </div>
                <div>
                    <h2 class="text-headline-md font-bold text-on-surface">Archive & Recovery Hub</h2>
                    <p class="text-body-sm text-on-surface-variant">Review, inspect, and restore archived records.</p>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-surface-container-high border border-outline-variant/30 text-xs text-on-surface-variant font-medium" title="Orders and prints completed more than 3 days ago are preserved here safely.">
                <span class="material-symbols-outlined text-[16px] text-primary">auto_delete</span>
                <span>Auto-Archived (&gt;3 days old)</span>
            </div>
        </div>
    </div>

    <!-- 4 KPI Stat Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/20 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[26px]">inventory</span>
            </div>
            <div>
                <p class="text-label-sm font-semibold text-on-surface-variant uppercase tracking-wider">Total Stored</p>
                <h3 class="text-headline-sm font-bold text-on-surface mt-0.5"><?= number_format($kpis['total'] ?? 0) ?></h3>
                <p class="text-[11px] text-on-surface-variant mt-0.5">Archived items</p>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/20 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[26px]">shopping_bag</span>
            </div>
            <div>
                <p class="text-label-sm font-semibold text-on-surface-variant uppercase tracking-wider">Archived Orders</p>
                <h3 class="text-headline-sm font-bold text-on-surface mt-0.5"><?= number_format($kpis['orders'] ?? 0) ?></h3>
                <p class="text-[11px] text-blue-600 dark:text-blue-400 mt-0.5">Fulfilled &amp; preserved</p>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/20 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[26px]">inventory_2</span>
            </div>
            <div>
                <p class="text-label-sm font-semibold text-on-surface-variant uppercase tracking-wider">Archived Products</p>
                <h3 class="text-headline-sm font-bold text-on-surface mt-0.5"><?= number_format($kpis['inventory'] ?? 0) ?></h3>
                <p class="text-[11px] text-amber-600 dark:text-amber-400 mt-0.5">Products &amp; catalog</p>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/20 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[26px]">print</span>
            </div>
            <div>
                <p class="text-label-sm font-semibold text-on-surface-variant uppercase tracking-wider">Archived Prints</p>
                <h3 class="text-headline-sm font-bold text-on-surface mt-0.5"><?= number_format($kpis['printing'] ?? 0) ?></h3>
                <p class="text-[11px] text-purple-600 dark:text-purple-400 mt-0.5">Completed printing jobs</p>
            </div>
        </div>
    </div>

    <!-- Filter Toolbar & Category Tabs -->
    <div class="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant/20 shadow-sm space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <!-- Category Tabs with Live Pill Counters -->
            <div class="inline-flex items-center gap-1.5 bg-surface-container-low rounded-xl p-1 border border-outline-variant/20" role="tablist">
                <button type="button" data-tab="all" class="archive-tab px-3.5 py-1.5 rounded-lg text-label-sm font-semibold transition-all inline-flex items-center gap-1.5" role="tab">
                    <span>All Items</span>
                    <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-surface-container-highest/60 text-on-surface-variant font-bold"><?= (int) ($kpis['total'] ?? 0) ?></span>
                </button>
                <button type="button" data-tab="order" class="archive-tab px-3.5 py-1.5 rounded-lg text-label-sm font-semibold transition-all inline-flex items-center gap-1.5" role="tab">
                    <span>Orders</span>
                    <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-surface-container-highest/60 text-on-surface-variant font-bold"><?= (int) ($kpis['orders'] ?? 0) ?></span>
                </button>
                <button type="button" data-tab="inventory" class="archive-tab px-3.5 py-1.5 rounded-lg text-label-sm font-semibold transition-all inline-flex items-center gap-1.5" role="tab">
                    <span>Inventory</span>
                    <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-surface-container-highest/60 text-on-surface-variant font-bold"><?= (int) ($kpis['inventory'] ?? 0) ?></span>
                </button>
                <button type="button" data-tab="printing_request" class="archive-tab px-3.5 py-1.5 rounded-lg text-label-sm font-semibold transition-all inline-flex items-center gap-1.5" role="tab">
                    <span>Printing</span>
                    <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-surface-container-highest/60 text-on-surface-variant font-bold"><?= (int) ($kpis['printing'] ?? 0) ?></span>
                </button>
            </div>

            <!-- Per-Page Selector & Instant Search Input -->
            <div class="flex items-center gap-2 flex-wrap flex-1 justify-end">
                <form method="get" action="<?= base_url('tenant/archive') ?>" class="flex items-center gap-1.5 text-xs text-on-surface-variant">
                    <?php if (!empty($activeType)): ?>
                        <input type="hidden" name="type" value="<?= esc($activeType) ?>">
                    <?php endif; ?>
                    <?php if (!empty($searchQuery)): ?>
                        <input type="hidden" name="q" value="<?= esc($searchQuery) ?>">
                    <?php endif; ?>
                    <label for="archive_per_page" class="font-medium">Show:</label>
                    <select name="per_page" id="archive_per_page" onchange="this.form.submit()" class="bg-surface-container-low border border-outline-variant/40 rounded-xl px-2.5 py-1.5 text-xs font-semibold focus:ring-2 focus:ring-primary">
                        <option value="5" <?= ($per_page ?? 10) == 5 ? 'selected' : '' ?>>5</option>
                        <option value="10" <?= ($per_page ?? 10) == 10 ? 'selected' : '' ?>>10</option>
                        <option value="15" <?= ($per_page ?? 10) == 15 ? 'selected' : '' ?>>15</option>
                        <option value="20" <?= ($per_page ?? 10) == 20 ? 'selected' : '' ?>>20</option>
                    </select>
                </form>
                <div class="relative flex-1 sm:max-w-xs">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                    <input id="archiveSearch" type="text" placeholder="Search by name, #ID, or order..." class="w-full pl-9 pr-8 py-2 bg-surface-container-low border border-outline-variant/40 rounded-xl text-body-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <button type="button" id="clearSearchBtn" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Floating Multi-select Batch Action Bar -->
        <div id="floatingArchiveBar" class="hidden p-3 bg-primary text-on-primary rounded-xl flex flex-wrap items-center justify-between gap-3 shadow-lg transition-all animate-fade-in">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">check_box</span>
                <span id="selectedCountLabel" class="text-label-sm font-bold">0 item(s) selected</span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="submitBulkRestore()" class="px-3 py-1.5 bg-white text-primary rounded-lg text-label-sm font-bold hover:bg-slate-100 transition-colors inline-flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-[16px]">restore</span>
                    <span>Restore Selected</span>
                </button>
                <button type="button" onclick="deselectAllItems()" class="px-2.5 py-1.5 text-on-primary/80 hover:text-on-primary text-label-sm underline transition-colors">
                    Deselect All
                </button>
            </div>
        </div>
    </div>

    <!-- Archive Table Card -->
    <div class="bg-surface-container-lowest rounded-2xl overflow-hidden border border-outline-variant/20 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low/60 border-b border-outline-variant/20">
                    <tr>
                        <th class="py-3 px-4 w-10 text-center">
                            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer">
                        </th>
                        <th class="py-3 px-4 text-label-sm font-bold text-on-surface-variant uppercase tracking-wider">Item Type</th>
                        <th class="py-3 px-4 text-label-sm font-bold text-on-surface-variant uppercase tracking-wider">Item Details &amp; Reference</th>
                        <th class="py-3 px-4 text-label-sm font-bold text-on-surface-variant uppercase tracking-wider">Archived Date</th>
                        <th class="py-3 px-4 text-label-sm font-bold text-on-surface-variant uppercase tracking-wider">Archived By</th>
                        <th class="py-3 px-4 text-label-sm font-bold text-on-surface-variant uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10 archive-table-body">
                    <?php if (!empty($archivedItems)): ?>
                        <?php foreach ($archivedItems as $p): ?>
                            <?php
                            $type = $p['item_type'] ?? 'order';
                            $details = $p['details'] ?? [];
                            $archiverName = trim(($p['archived_by_first'] ?? '') . ' ' . ($p['archived_by_last'] ?? ''));
                            $archiverInitials = $archiverName !== '' ? mb_strtoupper(mb_substr($archiverName, 0, 2)) : 'SY';
                            $archiverImage = trim((string) ($p['archived_by_image'] ?? ''));
                            $searchText = strtolower(($p['item_label'] ?? '') . ' ' . ($p['item_id'] ?? '') . ' ' . ($details['name'] ?? '') . ' ' . ($details['customer_name'] ?? ''));
                            ?>
                            <tr class="hover:bg-surface-container-low/40 transition-colors archive-row" data-id="<?= (int) $p['id'] ?>" data-type="<?= esc($type) ?>" data-search="<?= esc($searchText) ?>">
                                <!-- Checkbox -->
                                <td class="py-3 px-4 text-center">
                                    <input type="checkbox" value="<?= (int) $p['id'] ?>" class="archive-item-cb w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer" onchange="updateBatchToolbar()">
                                </td>

                                <!-- Item Type Badge -->
                                <td class="py-3 px-4">
                                    <?php if ($type === 'inventory'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20 uppercase tracking-wide">
                                            <span class="material-symbols-outlined text-[14px]">inventory_2</span>
                                            Inventory
                                        </span>
                                    <?php elseif ($type === 'order'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20 uppercase tracking-wide">
                                            <span class="material-symbols-outlined text-[14px]">shopping_bag</span>
                                            Order
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-500/10 text-purple-700 dark:text-purple-400 border border-purple-500/20 uppercase tracking-wide">
                                            <span class="material-symbols-outlined text-[14px]">print</span>
                                            Printing
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Rich Item Details -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <?php if ($type === 'inventory'): ?>
                                            <div class="w-10 h-10 rounded-xl bg-surface-container-high border border-outline-variant/30 flex items-center justify-center shrink-0 overflow-hidden">
                                                <?php if (!empty($details['image_url'])): ?>
                                                    <img src="<?= esc(product_image_url($details['image_url'])) ?>" alt="product" class="w-full h-full object-cover" onerror="this.remove()">
                                                <?php else: ?>
                                                    <span class="material-symbols-outlined text-outline text-[20px]">package_2</span>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <p class="text-body-sm font-bold text-on-surface"><?= esc($p['item_label'] ?? ('Product #' . $p['item_id'])) ?></p>
                                                <div class="flex items-center gap-2 mt-0.5 text-xs text-on-surface-variant">
                                                    <span>#<?= (int) $p['item_id'] ?></span>
                                                    <span>&bull;</span>
                                                    <span class="font-semibold text-primary">₱<?= number_format($details['price'] ?? 0, 2) ?></span>
                                                    <?php if (!empty($details['category'])): ?>
                                                        <span>&bull;</span>
                                                        <span><?= esc($details['category']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php elseif ($type === 'order'): ?>
                                            <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-600 flex items-center justify-center shrink-0">
                                                <span class="material-symbols-outlined text-[20px]">receipt_long</span>
                                            </div>
                                            <div>
                                                <p class="text-body-sm font-bold text-on-surface"><?= esc($p['item_label'] ?? ('Order #' . $p['item_id'])) ?></p>
                                                <div class="flex items-center gap-2 mt-0.5 text-xs text-on-surface-variant">
                                                    <span class="font-semibold text-on-surface"><?= esc($details['customer_name'] ?? 'Customer') ?></span>
                                                    <span>&bull;</span>
                                                    <span class="font-semibold text-emerald-600">₱<?= number_format($details['total_amount'] ?? 0, 2) ?></span>
                                                    <span>&bull;</span>
                                                    <span class="capitalize"><?= esc($details['fulfillment_method'] ?? 'pickup') ?></span>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-600 flex items-center justify-center shrink-0">
                                                <span class="material-symbols-outlined text-[20px]">picture_as_pdf</span>
                                            </div>
                                            <div>
                                                <p class="text-body-sm font-bold text-on-surface"><?= esc($p['item_label'] ?? ('Print #' . $p['item_id'])) ?></p>
                                                <div class="flex items-center gap-2 mt-0.5 text-xs text-on-surface-variant">
                                                    <span class="font-semibold text-on-surface"><?= esc($details['file_name'] ?? 'Document.pdf') ?></span>
                                                    <span>&bull;</span>
                                                    <span class="font-semibold text-purple-600">₱<?= number_format($details['total_price'] ?? 0, 2) ?></span>
                                                    <span>&bull;</span>
                                                    <span><?= esc($details['customer_name'] ?? 'Customer') ?></span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Archive Date -->
                                <td class="py-3 px-4">
                                    <p class="text-body-sm text-on-surface font-medium"><?= date('M d, Y', strtotime($p['archived_at'] ?? 'now')) ?></p>
                                    <p class="text-xs text-on-surface-variant"><?= date('h:i A', strtotime($p['archived_at'] ?? 'now')) ?></p>
                                </td>

                                <!-- Archived By -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-surface-container-highest text-on-surface-variant flex items-center justify-center text-[11px] font-bold overflow-hidden shrink-0">
                                            <?php if ($archiverImage !== ''): ?>
                                                <img class="w-full h-full object-cover" src="<?= esc(profile_image_url($archiverImage)) ?>" alt="avatar" onerror="this.remove()">
                                            <?php else: ?>
                                                <span><?= esc($archiverInitials) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-body-sm text-on-surface font-medium"><?= esc($archiverName !== '' ? $archiverName : 'System') ?></span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Quick Preview Details Modal Button -->
                                        <button type="button" onclick="openArchiveDetailModal(<?= (int) $p['id'] ?>)" class="p-2 hover:bg-surface-container-high rounded-lg text-on-surface-variant hover:text-on-surface transition-colors" title="View details before restoring">
                                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                                        </button>

                                        <!-- Single Restore Form -->
                                        <form action="<?= base_url('tenant/archive/restore/' . (int) $p['id']) ?>" method="POST" onsubmit="return confirm('Restore this item back to active records?');" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary text-on-primary rounded-lg text-label-sm font-bold hover:bg-primary/90 transition-colors shadow-sm">
                                                <span class="material-symbols-outlined text-[16px]">restore</span>
                                                <span>Restore</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-on-surface-variant">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-full bg-surface-container-high flex items-center justify-center text-outline mb-2">
                                        <span class="material-symbols-outlined text-[28px]">inbox</span>
                                    </div>
                                    <p class="text-body-md font-semibold text-on-surface">Archive is completely clear</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">Completed orders and prints older than 3 days will automatically appear here.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php
        $aTot     = (int) $pager->getTotal('archive');
        $aCur     = (int) $pager->getCurrentPage('archive');
        $aPag     = (int) $pager->getPageCount('archive');
        $aPerPage = (int) ($per_page ?? ($pager ? $pager->getPerPage('archive') : 10));
        $aStart   = $aTot === 0 ? 0 : ($aCur - 1) * $aPerPage + 1;
        $aEnd     = min($aCur * $aPerPage, $aTot);
        ?>
        <?php if ($aPag > 1): ?>
            <div class="px-6 py-4 bg-surface-container-low/40 flex justify-between items-center border-t border-outline-variant/20 flex-wrap gap-sm">
                <p class="text-label-sm font-label-sm text-on-surface-variant">Showing <?= number_format($aStart) ?> to <?= number_format($aEnd) ?> of <?= number_format($aTot) ?> items</p>
                <div class="flex items-center gap-1">
                    <a class="p-1.5 rounded hover:bg-surface-container-high <?= $aCur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('archive') ?>">
                        <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                    </a>
                    <?php
                    $w = [];
                    for ($i = 1; $i <= $aPag; $i++) {
                        if ($i === 1 || $i === $aPag || abs($i - $aCur) <= 2) { $w[] = $i; }
                    }
                    $pv = 0;
                    foreach ($w as $n):
                        if ($n - $pv > 1): ?><span class="px-1 text-outline">...</span><?php endif; ?>
                        <a class="w-8 h-8 rounded flex items-center justify-center text-label-sm <?= $aCur === $n ? 'bg-primary text-on-primary font-bold shadow-sm' : 'hover:bg-surface-container-high text-on-surface' ?>" href="<?= $pager->getPageURI($n, 'archive') ?>"><?= $n ?></a>
                    <?php $pv = $n; endforeach; ?>
                    <a class="p-1.5 rounded hover:bg-surface-container-high <?= $aCur >= $aPag ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('archive') ?>">
                        <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal: Archived Item Preview / Details Inspector -->
<div id="archiveDetailModal" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4 backdrop-blur-sm transition-opacity">
    <div class="bg-surface-container-lowest rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-outline-variant/20 flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-outline-variant/20 flex items-center justify-between bg-surface-container-low/40">
            <div class="flex items-center gap-2">
                <span id="modalItemTypeBadge" class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider">Item</span>
                <h3 id="modalItemTitle" class="text-title-md font-bold text-on-surface">Archived Item Details</h3>
            </div>
            <button type="button" onclick="closeArchiveDetailModal()" class="p-1.5 rounded-lg text-outline hover:text-on-surface hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <!-- Modal Body (Dynamic AJAX Content) -->
        <div id="modalItemBody" class="p-6 overflow-y-auto space-y-4 text-body-sm">
            <div class="py-8 flex flex-col items-center justify-center text-outline">
                <span class="material-symbols-outlined animate-spin text-[32px] text-primary">progress_activity</span>
                <p class="text-xs mt-2">Loading item details...</p>
            </div>
        </div>

        <!-- Modal Footer Actions -->
        <div class="px-6 py-3.5 border-t border-outline-variant/20 bg-surface-container-low/40 flex items-center justify-end gap-2">
            <button type="button" onclick="closeArchiveDetailModal()" class="px-4 py-2 border border-outline-variant rounded-xl text-label-sm font-semibold hover:bg-surface-container-high transition-colors">
                Close
            </button>
            <button type="button" id="modalRestoreBtn" class="px-4 py-2 bg-primary text-on-primary rounded-xl text-label-sm font-bold hover:bg-primary/90 transition-colors inline-flex items-center gap-1.5 shadow-sm">
                <span class="material-symbols-outlined text-[16px]">restore</span>
                <span>Restore Item</span>
            </button>
        </div>
    </div>
</div>

<script>
    let archiveTab = 'all';
    const activeTabClasses = 'bg-primary text-on-primary shadow-sm';
    const inactiveTabClasses = 'text-on-surface-variant hover:bg-surface-container-high';
    const CSRF_NAME = '<?= csrf_token() ?>';
    const CSRF_HASH = '<?= csrf_hash() ?>';

    function applyArchiveFilters() {
        const query = document.getElementById('archiveSearch').value.trim().toLowerCase();
        document.getElementById('clearSearchBtn').classList.toggle('hidden', query === '');
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
            emptyRow.innerHTML = '<td colspan="6" class="py-12 text-center text-on-surface-variant font-medium">No archived items match your selected filter or search query.</td>';
            document.querySelector('.archive-table-body')?.appendChild(emptyRow);
        }
        emptyRow.classList.toggle('hidden', visible > 0 || document.querySelectorAll('.archive-row').length === 0);
    }

    document.querySelectorAll('.archive-tab').forEach((btn) => {
        btn.addEventListener('click', () => {
            archiveTab = btn.dataset.tab;
            document.querySelectorAll('.archive-tab').forEach((b) => {
                b.classList.remove('bg-primary', 'text-on-primary', 'shadow-sm');
                b.classList.add('text-on-surface-variant', 'hover:bg-surface-container-high');
            });
            btn.classList.remove('text-on-surface-variant', 'hover:bg-surface-container-high');
            btn.classList.add('bg-primary', 'text-on-primary', 'shadow-sm');
            applyArchiveFilters();
        });
    });

    document.getElementById('archiveSearch').addEventListener('input', applyArchiveFilters);
    document.getElementById('clearSearchBtn').addEventListener('click', () => {
        document.getElementById('archiveSearch').value = '';
        applyArchiveFilters();
    });

    // Default tab active state
    document.querySelectorAll('.archive-tab').forEach((btn) => btn.classList.add('text-on-surface-variant', 'hover:bg-surface-container-high'));
    const defaultTab = document.querySelector('.archive-tab[data-tab="all"]');
    if (defaultTab) {
        defaultTab.classList.remove('text-on-surface-variant', 'hover:bg-surface-container-high');
        defaultTab.classList.add('bg-primary', 'text-on-primary', 'shadow-sm');
    }

    // Multi-Select Batch Actions
    function toggleSelectAll(masterCb) {
        const cbs = document.querySelectorAll('.archive-item-cb');
        cbs.forEach((cb) => {
            if (!cb.closest('tr').classList.contains('hidden')) {
                cb.checked = masterCb.checked;
            }
        });
        updateBatchToolbar();
    }

    function updateBatchToolbar() {
        const checked = document.querySelectorAll('.archive-item-cb:checked');
        const bar = document.getElementById('floatingArchiveBar');
        const countLabel = document.getElementById('selectedCountLabel');
        if (checked.length > 0) {
            bar.classList.remove('hidden');
            countLabel.textContent = checked.length + ' item(s) selected';
        } else {
            bar.classList.add('hidden');
            const selectAll = document.getElementById('selectAllCheckbox');
            if (selectAll) selectAll.checked = false;
        }
    }

    function deselectAllItems() {
        document.querySelectorAll('.archive-item-cb').forEach((cb) => cb.checked = false);
        const selectAll = document.getElementById('selectAllCheckbox');
        if (selectAll) selectAll.checked = false;
        updateBatchToolbar();
    }

    async function submitBulkRestore() {
        const checked = Array.from(document.querySelectorAll('.archive-item-cb:checked')).map(c => c.value);
        if (checked.length === 0) return;
        if (!confirm('Restore ' + checked.length + ' selected item(s) back to active records?')) return;

        const formData = new FormData();
        formData.append(CSRF_NAME, CSRF_HASH);
        checked.forEach(id => formData.append('archive_ids[]', id));

        try {
            const res = await fetch('<?= base_url('tenant/archive/bulk-restore') ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to restore items.');
            }
        } catch (e) {
            location.reload();
        }
    }

    async function submitBulkPermanentDelete() {
        const checked = Array.from(document.querySelectorAll('.archive-item-cb:checked')).map(c => c.value);
        if (checked.length === 0) return;
        if (!confirm('WARNING: Permanently purge ' + checked.length + ' item(s) from the archive? This cannot be undone.')) return;

        const formData = new FormData();
        formData.append(CSRF_NAME, CSRF_HASH);
        checked.forEach(id => formData.append('archive_ids[]', id));

        try {
            const res = await fetch('<?= base_url('tenant/archive/bulk-delete') ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to purge items.');
            }
        } catch (e) {
            location.reload();
        }
    }

    // Archived Item Preview Modal
    async function openArchiveDetailModal(archiveId) {
        const modal = document.getElementById('archiveDetailModal');
        const body = document.getElementById('modalItemBody');
        const badge = document.getElementById('modalItemTypeBadge');
        const title = document.getElementById('modalItemTitle');
        const restoreBtn = document.getElementById('modalRestoreBtn');

        modal.classList.remove('hidden');
        body.innerHTML = `
            <div class="py-12 flex flex-col items-center justify-center text-outline">
                <span class="material-symbols-outlined animate-spin text-[32px] text-primary">progress_activity</span>
                <p class="text-xs mt-2">Loading item details...</p>
            </div>
        `;

        try {
            const res = await fetch('<?= base_url('tenant/archive/detail') ?>/' + archiveId, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!json.success || !json.data) {
                body.innerHTML = `<div class="p-4 bg-error-container text-on-error-container rounded-xl text-xs font-semibold">${json.error || 'Unable to load details.'}</div>`;
                return;
            }

            const d = json.data;
            const ent = d.entity || {};

            title.textContent = d.item_label;
            badge.textContent = d.item_type.toUpperCase();
            badge.className = 'px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider ' + 
                (d.item_type === 'inventory' ? 'bg-amber-500/10 text-amber-600' : (d.item_type === 'order' ? 'bg-blue-500/10 text-blue-600' : 'bg-purple-500/10 text-purple-600'));

            // Build dynamic detail cards
            let html = `
                <div class="bg-surface-container-low rounded-xl p-4 border border-outline-variant/20 space-y-2">
                    <div class="flex items-center justify-between text-xs text-on-surface-variant">
                        <span>Archive Date:</span>
                        <span class="font-semibold text-on-surface">${d.archived_at}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-on-surface-variant">
                        <span>Archived By:</span>
                        <span class="font-semibold text-on-surface">${d.archived_by_name} (${d.archived_by_email || 'System'})</span>
                    </div>
                </div>
            `;

            if (d.item_type === 'inventory') {
                html += `
                    <div class="space-y-3">
                        <h4 class="font-bold text-on-surface text-sm flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px] text-amber-500">inventory_2</span>
                            Product Specifications
                        </h4>
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/20">
                                <span class="text-on-surface-variant block">Price</span>
                                <span class="text-sm font-bold text-primary">₱${parseFloat(ent.price || 0).toFixed(2)}</span>
                            </div>
                            <div class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/20">
                                <span class="text-on-surface-variant block">Stock Before Archive</span>
                                <span class="text-sm font-bold text-on-surface">${ent.stock_quantity || 0}</span>
                            </div>
                        </div>
                        ${ent.sku ? `<div class="text-xs text-on-surface-variant">SKU: <span class="font-mono font-semibold text-on-surface">${ent.sku}</span></div>` : ''}
                        ${ent.description ? `<div class="p-3 bg-surface-container-low rounded-xl text-xs text-on-surface-variant">${ent.description}</div>` : ''}
                    </div>
                `;
            } else if (d.item_type === 'order') {
                html += `
                    <div class="space-y-3">
                        <h4 class="font-bold text-on-surface text-sm flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px] text-blue-500">shopping_bag</span>
                            Order Information
                        </h4>
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/20">
                                <span class="text-on-surface-variant block">Total Amount</span>
                                <span class="text-sm font-bold text-emerald-600">₱${parseFloat(ent.total_amount || 0).toFixed(2)}</span>
                            </div>
                            <div class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/20">
                                <span class="text-on-surface-variant block">Fulfillment</span>
                                <span class="text-sm font-bold text-on-surface capitalize">${ent.fulfillment_method || 'pickup'}</span>
                            </div>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-xl text-xs text-on-surface-variant space-y-1">
                            <p><span class="font-semibold text-on-surface">Customer:</span> ${ent.first_name || ''} ${ent.last_name || ''}</p>
                            <p><span class="font-semibold text-on-surface">Email:</span> ${ent.email || 'N/A'}</p>
                            ${ent.phone_number ? `<p><span class="font-semibold text-on-surface">Phone:</span> ${ent.phone_number}</p>` : ''}
                        </div>
                    </div>
                `;
            } else if (d.item_type === 'printing_request') {
                html += `
                    <div class="space-y-3">
                        <h4 class="font-bold text-on-surface text-sm flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px] text-purple-500">print</span>
                            Printing Job Specifications
                        </h4>
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/20">
                                <span class="text-on-surface-variant block">Print Total</span>
                                <span class="text-sm font-bold text-purple-600">₱${parseFloat(ent.total_price || 0).toFixed(2)}</span>
                            </div>
                            <div class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/20">
                                <span class="text-on-surface-variant block">Pages / Copies</span>
                                <span class="text-sm font-bold text-on-surface">${ent.page_count || 1} pages &bull; ${ent.copies || 1} copies</span>
                            </div>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-xl text-xs text-on-surface-variant space-y-1">
                            <p><span class="font-semibold text-on-surface">File:</span> ${ent.file_name || 'Document.pdf'}</p>
                            <p><span class="font-semibold text-on-surface">Customer:</span> ${ent.first_name || ''} ${ent.last_name || ''}</p>
                        </div>
                    </div>
                `;
            }

            body.innerHTML = html;

            restoreBtn.onclick = () => {
                if (confirm('Restore this item back to active records?')) {
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.action = '<?= base_url('tenant/archive/restore') ?>/' + archiveId;
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = CSRF_NAME;
                    inp.value = CSRF_HASH;
                    f.appendChild(inp);
                    document.body.appendChild(f);
                    f.submit();
                }
            };
        } catch (e) {
            body.innerHTML = `<div class="p-4 bg-error-container text-on-error-container rounded-xl text-xs font-semibold">Error loading details.</div>`;
        }
    }

    function closeArchiveDetailModal() {
        document.getElementById('archiveDetailModal').classList.add('hidden');
    }
</script>

<?= $this->endSection() ?>