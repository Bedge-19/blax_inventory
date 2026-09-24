<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="space-y-6">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            <span><?= session()->getFlashdata('success') ?></span>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">error</span>
            <span><?= session()->getFlashdata('error') ?></span>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('warning')): ?>
        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-300 text-xs font-semibold flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">warning</span>
            <span><?= esc(session()->getFlashdata('warning')) ?></span>
        </div>
    <?php endif; ?>

    <!-- Bento Stats Overview -->
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Card 1: Total Tenants -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/30 shadow-xs flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <span class="text-xs font-bold text-on-surface-variant/70 uppercase tracking-wider">Total Merchants</span>
                <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">storefront</span>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl lg:text-3xl font-black text-on-surface tracking-tight"><?= esc($total_count ?? count($tenants ?? [])) ?></h3>
                <p class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1 mt-1">
                    <span class="material-symbols-outlined text-[14px]">check</span>
                    <span>All onboarded shops</span>
                </p>
            </div>
        </div>

        <!-- Card 2: Active Tenants -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/30 shadow-xs flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <span class="text-xs font-bold text-on-surface-variant/70 uppercase tracking-wider">Active &amp; Operating</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">verified</span>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl lg:text-3xl font-black text-emerald-600 tracking-tight"><?= esc($active_count ?? 0) ?></h3>
                <p class="text-[11px] text-on-surface-variant/70 font-medium mt-1">Verified storefronts selling online</p>
            </div>
        </div>

        <!-- Card 3: Pending Approvals -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 border <?= !empty($pending_count) ? 'border-amber-500/40 bg-amber-500/5' : 'border-outline-variant/30' ?> shadow-xs flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <span class="text-xs font-bold uppercase tracking-wider <?= !empty($pending_count) ? 'text-amber-800 dark:text-amber-300' : 'text-on-surface-variant/70' ?>">Pending Verification</span>
                <div class="w-9 h-9 rounded-xl <?= !empty($pending_count) ? 'bg-amber-500/20 text-amber-700' : 'bg-surface-container text-on-surface-variant' ?> flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">pending_actions</span>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl lg:text-3xl font-black <?= !empty($pending_count) ? 'text-amber-700' : 'text-on-surface' ?> tracking-tight"><?= esc($pending_count ?? 0) ?></h3>
                <p class="text-[11px] <?= !empty($pending_count) ? 'text-amber-800 font-bold' : 'text-on-surface-variant/70 font-medium' ?> mt-1">
                    <?= !empty($pending_count) ? 'Action required in next 48h' : 'No applications pending' ?>
                </p>
            </div>
        </div>
    </section>

    <!-- Shopify Polaris Segmented Control & Realtime Search Bar (No Filter Button) -->
    <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/30 shadow-xs space-y-4">
        
        <!-- Segmented Status Tabs -->
        <div class="flex items-center gap-1.5 p-1 bg-surface-container-low rounded-xl border border-outline-variant/20 overflow-x-auto">
            <button type="button" onclick="filterByStatus('')" class="status-tab-btn px-3.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all <?= empty($filters['status']) ? 'bg-surface-container-lowest text-primary shadow-xs' : 'text-on-surface-variant hover:text-on-surface' ?>" data-status="">
                All Merchants (<?= (int) ($total_count ?? count($tenants ?? [])) ?>)
            </button>
            <button type="button" onclick="filterByStatus('active')" class="status-tab-btn px-3.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all <?= ($filters['status'] ?? '') === 'active' ? 'bg-surface-container-lowest text-emerald-600 shadow-xs' : 'text-on-surface-variant hover:text-on-surface' ?>" data-status="active">
                <span class="inline-flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Active (<?= (int) $active_count ?>)</span>
                </span>
            </button>
            <button type="button" onclick="filterByStatus('pending')" class="status-tab-btn px-3.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all <?= ($filters['status'] ?? '') === 'pending' ? 'bg-surface-container-lowest text-amber-600 shadow-xs' : 'text-on-surface-variant hover:text-on-surface' ?>" data-status="pending">
                <span class="inline-flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-amber-500 <?= !empty($pending_count) ? 'animate-pulse' : '' ?>"></span>
                    <span>Pending (<?= (int) $pending_count ?>)</span>
                </span>
            </button>
            <button type="button" onclick="filterByStatus('suspended')" class="status-tab-btn px-3.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all <?= ($filters['status'] ?? '') === 'suspended' ? 'bg-surface-container-lowest text-slate-700 dark:text-slate-200 shadow-xs' : 'text-on-surface-variant hover:text-on-surface' ?>" data-status="suspended">
                <span class="inline-flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                    <span>Suspended</span>
                </span>
            </button>
            <button type="button" onclick="filterByStatus('rejected')" class="status-tab-btn px-3.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all <?= ($filters['status'] ?? '') === 'rejected' ? 'bg-surface-container-lowest text-rose-600 shadow-xs' : 'text-on-surface-variant hover:text-on-surface' ?>" data-status="rejected">
                <span class="inline-flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    <span>Rejected</span>
                </span>
            </button>
        </div>

        <!-- Realtime Search & Controls Bar -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <!-- Realtime Search Input -->
            <div class="relative flex-1 min-w-[260px]">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                <input id="realtimeSearchInput" 
                       type="text" 
                       value="<?= esc($filters['q'] ?? '') ?>" 
                       placeholder="Instant search by shop name, owner, email, or ID..." 
                       class="w-full pl-9 pr-8 py-2 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs text-on-surface focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all placeholder:text-on-surface-variant/50">
                <button type="button" id="clearSearchBtn" onclick="clearRealtimeSearch()" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface p-0.5 rounded-full" title="Clear search">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </button>
            </div>

            <!-- Show Pages & Stats Indicator -->
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 text-xs text-on-surface-variant font-medium">
                    <label for="per_page_select" class="font-bold text-[11px] uppercase tracking-wider text-on-surface-variant/70">Show:</label>
                    <select name="per_page" id="per_page_select" onchange="changePageSize(this.value)" class="bg-surface-container-low border border-outline-variant/40 rounded-xl px-3 py-1.5 text-xs font-bold text-on-surface focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="5" <?= ($per_page ?? 10) == 5 ? 'selected' : '' ?>>5</option>
                        <option value="10" <?= ($per_page ?? 10) == 10 ? 'selected' : '' ?>>10</option>
                        <option value="20" <?= ($per_page ?? 10) == 20 ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= ($per_page ?? 10) == 50 ? 'selected' : '' ?>>50</option>
                    </select>
                </div>

                <span id="realtimeMatchCount" class="text-xs font-semibold text-primary px-2.5 py-1 rounded-lg bg-primary/10 border border-primary/20">
                    <?= count($tenants ?? []) ?> items shown
                </span>
            </div>
        </div>
    </div>

    <!-- Tenants Data Table (Polaris IndexTable Aesthetic) -->
    <section class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 shadow-xs overflow-hidden">
        <div class="overflow-x-auto w-full">
            <table class="w-full text-left border-collapse min-w-[980px]" id="tenantsTable">
                <thead class="bg-surface-container-low/70 border-b border-outline-variant/30 text-[11px] font-bold text-on-surface-variant/70 uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-5 whitespace-nowrap">Merchant Details</th>
                        <th class="py-3 px-5 whitespace-nowrap">Owner / Contact</th>
                        <th class="py-3 px-5 whitespace-nowrap">Permit Status</th>
                        <th class="py-3 px-5 whitespace-nowrap">Date Onboarded</th>
                        <th class="py-3 px-5 text-center whitespace-nowrap">Status</th>
                        <th class="py-3 px-5 text-right whitespace-nowrap min-w-[190px]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20 text-xs" id="tenantsTableBody">
                    <?php if (!empty($tenants)): foreach ($tenants as $t): ?>
                        <?php 
                        $tStatus = $t['status'] ?? 'active'; 
                        $shopSlug = $t['slug'] ?? '';
                        $ownerFull = trim(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? ''));
                        ?>
                        <tr class="tenant-row hover:bg-surface-container-low/40 transition-colors group" 
                            id="tenant-row-<?= (int) $t['id'] ?>"
                            data-shop-name="<?= esc(strtolower($t['shop_name'] ?? '')) ?>"
                            data-owner="<?= esc(strtolower($ownerFull)) ?>"
                            data-email="<?= esc(strtolower($t['email'] ?? '')) ?>"
                            data-id="ten-<?= esc(str_pad($t['id'], 3, '0', STR_PAD_LEFT)) ?>"
                            data-status="<?= esc($tStatus) ?>">
                            
                            <!-- Merchant Details -->
                            <td class="py-3.5 px-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-primary/20 via-blue-500/20 to-indigo-500/20 text-primary border border-primary/20 flex items-center justify-center font-black text-xs shrink-0 shadow-2xs">
                                        <?php if (!empty($t['logo_url'])): ?>
                                            <img src="<?= esc(logo_url($t['logo_url'])) ?>" alt="<?= esc($t['shop_name']) ?>" class="w-full h-full object-cover rounded-xl">
                                        <?php else: ?>
                                            <span><?= esc(mb_strtoupper(mb_substr($t['shop_name'] ?? 'S', 0, 2))) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-on-surface group-hover:text-primary transition-colors truncate max-w-xs md:max-w-sm">
                                                <?= esc($t['shop_name']) ?>
                                            </span>
                                            <?php if (!empty($shopSlug)): ?>
                                                <a href="<?= base_url('shop/' . esc($shopSlug)) ?>" target="_blank" rel="noopener" class="text-on-surface-variant hover:text-primary inline-flex items-center transition-colors" title="View live storefront">
                                                    <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex items-center gap-2 mt-0.5 text-[11px] text-on-surface-variant/70">
                                            <span class="font-mono text-[10px] bg-surface-container px-1.5 py-0.2 rounded border border-outline-variant/20">
                                                TEN-<?= esc(str_pad($t['id'], 3, '0', STR_PAD_LEFT)) ?>
                                            </span>
                                            <span class="uppercase tracking-wider font-semibold text-[10px] text-outline">
                                                <?= esc($t['plan'] ?? 'standard') ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Owner / Contact -->
                            <td class="py-3.5 px-5">
                                <div class="min-w-0">
                                    <p class="font-semibold text-on-surface truncate">
                                        <?= esc($ownerFull ?: 'Unassigned Owner') ?>
                                    </p>
                                    <p class="text-[11px] text-on-surface-variant/70 truncate flex items-center gap-1 mt-0.5">
                                        <span class="material-symbols-outlined text-[13px] text-outline">mail</span>
                                        <span><?= esc($t['email'] ?? 'No email provided') ?></span>
                                    </p>
                                </div>
                            </td>

                            <!-- Permit Status -->
                            <td class="py-3.5 px-5 whitespace-nowrap">
                                <?php if (!empty($t['business_permit_url'])): ?>
                                    <button type="button" 
                                            data-permit-url="<?= esc(base_url('admin/tenants/permit/' . (int) $t['id']), 'attr') ?>" 
                                            data-shop-name="<?= esc($t['shop_name'], 'attr') ?>" 
                                            onclick="openPermitModal(this)" 
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-surface-container-low hover:bg-surface-container border border-outline-variant/30 hover:border-primary/40 text-primary font-bold text-[11px] transition-all group/btn shadow-2xs whitespace-nowrap">
                                        <span class="material-symbols-outlined text-[16px] text-primary">description</span>
                                        <span>View Document</span>
                                    </button>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-400 font-semibold text-[11px] whitespace-nowrap">
                                        <span class="material-symbols-outlined text-[14px]">warning</span>
                                        <span>No Permit</span>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Date Onboarded -->
                            <td class="py-3.5 px-5 text-on-surface-variant font-medium whitespace-nowrap">
                                <?= date('M d, Y', strtotime($t['created_at'])) ?>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                <?= status_badge($tStatus) ?>
                            </td>

                            <!-- Redesigned Actions Column -->
                            <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-1.5 flex-nowrap">
                                    <?php if ($tStatus === 'pending'): ?>
                                        <!-- Quick Approve Button -->
                                        <form action="<?= base_url('admin/tenants/approve') ?>" method="POST" class="inline-flex items-center shrink-0" onsubmit="return confirm('Approve <?= esc($t['shop_name'], 'js') ?> for active marketplace operations?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="tenant_id" value="<?= (int) $t['id'] ?>">
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs active:scale-95 transition-all whitespace-nowrap shrink-0" title="Approve Merchant Application">
                                                <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                                <span>Approve</span>
                                            </button>
                                        </form>

                                        <!-- Quick Reject Trigger Modal -->
                                        <button type="button" 
                                                onclick="openRejectModal(<?= (int)$t['id'] ?>, '<?= esc($t['shop_name'], 'js') ?>')" 
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-400 border border-rose-500/30 font-bold text-xs transition-all whitespace-nowrap shrink-0" title="Reject Merchant Application">
                                            <span class="material-symbols-outlined text-[16px]">cancel</span>
                                            <span>Reject</span>
                                        </button>

                                    <?php elseif ($tStatus === 'active'): ?>
                                        <!-- Suspend Button -->
                                        <form action="<?= base_url('admin/tenants/toggle-status') ?>" method="POST" class="inline-flex items-center shrink-0" onsubmit="return confirm('Suspend <?= esc($t['shop_name'], 'js') ?>? Products and storefront access will be temporarily locked.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="tenant_id" value="<?= (int) $t['id'] ?>">
                                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-surface-container-low hover:bg-rose-500/10 text-on-surface-variant hover:text-rose-600 border border-outline-variant/30 hover:border-rose-500/30 font-semibold text-xs transition-all whitespace-nowrap shrink-0" title="Suspend Merchant">
                                                <span class="material-symbols-outlined text-[16px]">block</span>
                                                <span>Suspend</span>
                                            </button>
                                        </form>

                                    <?php elseif ($tStatus === 'suspended'): ?>
                                        <!-- Activate Button -->
                                        <form action="<?= base_url('admin/tenants/toggle-status') ?>" method="POST" class="inline-flex items-center shrink-0" onsubmit="return confirm('Reactivate <?= esc($t['shop_name'], 'js') ?>? Storefront will resume normal sales.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="tenant_id" value="<?= (int) $t['id'] ?>">
                                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-primary text-on-primary hover:bg-primary/90 font-bold text-xs shadow-xs active:scale-95 transition-all whitespace-nowrap shrink-0" title="Reactivate Merchant">
                                                <span class="material-symbols-outlined text-[16px]">check</span>
                                                <span>Activate</span>
                                            </button>
                                        </form>

                                    <?php elseif ($tStatus === 'rejected'): ?>
                                        <span class="text-[11px] text-rose-600 font-semibold truncate max-w-[150px] inline-block shrink-0" title="<?= esc($t['rejection_reason'] ?? 'Rejected') ?>">
                                            <?= !empty($t['rejection_reason']) ? esc($t['rejection_reason']) : 'Rejected' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr id="emptyRowState">
                            <td colspan="6" class="py-12 text-center">
                                <div class="w-12 h-12 rounded-2xl bg-surface-container flex items-center justify-center mx-auto mb-2 text-outline">
                                    <span class="material-symbols-outlined text-2xl">store_off</span>
                                </div>
                                <p class="text-sm font-bold text-on-surface">No tenant merchants found</p>
                                <p class="text-xs text-on-surface-variant mt-0.5">Try modifying your search or clearing active filters.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <?php if (isset($pager)): ?>
            <?php
            $total   = (int) $pager->getTotal('tenants');
            $perPage = (int) ($per_page ?? ($pager ? $pager->getPerPage('tenants') : 10));
            $cur     = (int) $pager->getCurrentPage('tenants');
            $pages   = (int) $pager->getPageCount('tenants');
            $start   = $total === 0 ? 0 : ($cur - 1) * $perPage + 1;
            $end     = min($cur * $perPage, $total);
            ?>
            <div class="px-5 py-3.5 bg-surface-container-low/70 flex justify-between items-center border-t border-outline-variant/30 flex-wrap gap-2 text-xs">
                <p class="font-medium text-on-surface-variant">
                    Showing <span class="font-bold text-on-surface"><?= number_format($start) ?></span> to <span class="font-bold text-on-surface"><?= number_format($end) ?></span> of <span class="font-bold text-on-surface"><?= number_format($total) ?></span> merchants
                </p>
                <?php if ($pages > 1): ?>
                    <div class="flex items-center gap-1">
                        <a class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container border border-outline-variant/30 <?= $cur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('tenants') ?>" title="Previous Page">
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
                            <a class="w-8 h-8 rounded-lg flex items-center justify-center font-bold <?= $cur === $num ? 'bg-primary text-on-primary shadow-xs' : 'hover:bg-surface-container border border-outline-variant/30 text-on-surface-variant' ?>" href="<?= $pager->getPageURI($num, 'tenants') ?>"><?= $num ?></a>
                        <?php $prev = $num; endforeach; ?>
                        <a class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container border border-outline-variant/30 <?= $cur >= $pages ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('tenants') ?>" title="Next Page">
                            <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="px-5 py-3.5 bg-surface-container-low/70 border-t border-outline-variant/30 text-xs text-on-surface-variant">
                Showing 1 to <?= count($tenants ?? []) ?> of <?= esc($total_count ?? count($tenants ?? [])) ?> merchants
            </div>
        <?php endif; ?>
    </section>

</div>

<!-- Reject Tenant Modal -->
<div id="reject-tenant-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-md" role="dialog" aria-modal="true" aria-labelledby="reject-modal-title">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-xs" onclick="closeRejectModal()"></div>
    <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-outline-variant/20 pb-3">
            <div class="flex items-center gap-2 text-rose-600">
                <span class="material-symbols-outlined text-[22px]">cancel</span>
                <h3 id="reject-modal-title" class="text-base font-bold text-on-surface">Reject Application</h3>
            </div>
            <button type="button" onclick="closeRejectModal()" class="rounded-lg p-1 text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-colors">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        <p class="text-xs text-on-surface-variant">
            Provide a mandatory rejection reason for <strong id="reject-shop-name" class="text-on-surface"></strong>. The applicant will be notified via their registered email.
        </p>

        <form action="<?= base_url('admin/tenants/reject') ?>" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="tenant_id" id="reject-tenant-id" value="">
            <div>
                <label for="rejection_reason" class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant/70 mb-1.5">
                    Reason for Rejection <span class="text-rose-600">*</span>
                </label>
                <textarea name="rejection_reason" 
                          id="rejection_reason" 
                          required 
                          maxlength="255" 
                          rows="3" 
                          class="w-full p-3 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs text-on-surface focus:outline-none focus:ring-2 focus:ring-rose-500 placeholder:text-on-surface-variant/50" 
                          placeholder="e.g. Expired Mayor's permit, incomplete business registration documents, or illegible photos."></textarea>
                <span class="text-[10px] text-on-surface-variant/60 block mt-1">Maximum 255 characters.</span>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-outline-variant/20">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs active:scale-95 transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">cancel</span>
                    <span>Confirm Rejection</span>
                </button>
            </div>
        </form>
    </div>
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
    let permitModalTrigger = null;
    let activeFilterStatus = '<?= esc($filters['status'] ?? '') ?>';
    let searchDebounceTimeout = null;

    // Permit Modal controls
    function openPermitModal(button) {
        const modal = document.getElementById('permit-preview-modal');
        const frame = document.getElementById('permit-preview-frame');
        const title = document.getElementById('permit-preview-title');
        const openInNewTab = document.getElementById('permit-preview-open-tab');
        const permitUrl = button.dataset.permitUrl;

        if (!modal || !frame || !title || !openInNewTab || !permitUrl) return;

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

    // Reject Modal controls
    function openRejectModal(tenantId, shopName) {
        const modal = document.getElementById('reject-tenant-modal');
        const idInput = document.getElementById('reject-tenant-id');
        const shopLabel = document.getElementById('reject-shop-name');
        const reasonInput = document.getElementById('rejection_reason');

        if (!modal) return;
        idInput.value = tenantId;
        shopLabel.textContent = shopName;
        if (reasonInput) reasonInput.value = '';

        modal.classList.remove('hidden');
        setTimeout(() => reasonInput && reasonInput.focus(), 50);
    }

    function closeRejectModal() {
        const modal = document.getElementById('reject-tenant-modal');
        if (modal) modal.classList.add('hidden');
    }

    // Realtime Client-Side Filtering
    function applyRealtimeFilters() {
        const searchInput = document.getElementById('realtimeSearchInput');
        const clearBtn = document.getElementById('clearSearchBtn');
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const rows = document.querySelectorAll('#tenantsTableBody tr.tenant-row');
        const countBadge = document.getElementById('realtimeMatchCount');
        let visibleCount = 0;

        if (clearBtn) {
            if (query.length > 0) clearBtn.classList.remove('hidden');
            else clearBtn.classList.add('hidden');
        }

        rows.forEach(row => {
            const shopName = row.dataset.shopName || '';
            const owner = row.dataset.owner || '';
            const email = row.dataset.email || '';
            const id = row.dataset.id || '';
            const status = row.dataset.status || '';

            const matchesStatus = !activeFilterStatus || (status === activeFilterStatus);
            const matchesQuery = !query || (
                shopName.includes(query) ||
                owner.includes(query) ||
                email.includes(query) ||
                id.includes(query)
            );

            if (matchesStatus && matchesQuery) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (countBadge) {
            countBadge.textContent = visibleCount + ' items shown';
        }

        const emptyState = document.getElementById('emptyRowState');
        if (emptyState) {
            if (visibleCount === 0 && rows.length > 0) {
                emptyState.style.display = '';
            } else if (visibleCount > 0) {
                emptyState.style.display = 'none';
            }
        }
    }

    // Status Tab Switcher (Shopify Polaris pattern)
    function filterByStatus(status) {
        activeFilterStatus = status;

        // Update Tab Button Styles
        document.querySelectorAll('.status-tab-btn').forEach(btn => {
            const btnStatus = btn.dataset.status || '';
            if (btnStatus === status) {
                btn.className = 'status-tab-btn px-3.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all bg-surface-container-lowest text-primary shadow-xs';
            } else {
                btn.className = 'status-tab-btn px-3.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all text-on-surface-variant hover:text-on-surface';
            }
        });

        applyRealtimeFilters();

        // If paginated dataset is larger than page, sync URL after brief delay
        clearTimeout(searchDebounceTimeout);
        searchDebounceTimeout = setTimeout(() => {
            syncUrlParams();
        }, 600);
    }

    function clearRealtimeSearch() {
        const searchInput = document.getElementById('realtimeSearchInput');
        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }
        applyRealtimeFilters();
        syncUrlParams();
    }

    function changePageSize(perPage) {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', perPage);
        url.searchParams.delete('page_tenants');
        window.location.href = url.toString();
    }

    function syncUrlParams() {
        const searchInput = document.getElementById('realtimeSearchInput');
        const q = searchInput ? searchInput.value.trim() : '';
        const url = new URL(window.location.href);

        let changed = false;
        if (q) {
            if (url.searchParams.get('q') !== q) { url.searchParams.set('q', q); changed = true; }
        } else {
            if (url.searchParams.has('q')) { url.searchParams.delete('q'); changed = true; }
        }

        if (activeFilterStatus) {
            if (url.searchParams.get('status') !== activeFilterStatus) { url.searchParams.set('status', activeFilterStatus); changed = true; }
        } else {
            if (url.searchParams.has('status')) { url.searchParams.delete('status'); changed = true; }
        }

        if (changed) {
            url.searchParams.delete('page_tenants');
            window.location.href = url.toString();
        }
    }

    // Input event for instant typing search
    document.getElementById('realtimeSearchInput')?.addEventListener('input', function() {
        applyRealtimeFilters();
        clearTimeout(searchDebounceTimeout);
        searchDebounceTimeout = setTimeout(() => {
            syncUrlParams();
        }, 800);
    });

    // Close modals on escape or outside click
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (!closePermitModal()) closeRejectModal();
        }
    });

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-permit-modal-close]')) {
            closePermitModal();
        }
    });

    // Run filter check on load
    applyRealtimeFilters();
</script>
<?= $this->endSection() ?>
