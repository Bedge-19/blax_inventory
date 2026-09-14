<?= $this->extend('layouts/tenant') ?>

<?php
function pr_binding_label(?string $b): string {
    return [
        'none'       => 'Standard',
        'spiral'     => 'Spiral',
        'hardcover'  => 'Hardcover',
        'staple'     => 'Stapled',
    ][$b ?? 'none'] ?? 'Standard';
}

function pr_spec_line(array $r): string {
    $parts = [
        (int) $r['page_count'] . ' pages',
        (int) $r['copies'] . ' cop' . ((int) $r['copies'] === 1 ? 'y' : 'ies'),
        pr_binding_label($r['binding_option'] ?? 'none'),
    ];
    return implode(' • ', $parts);
}

$nextStates = [
    'new'                => ['in_production' => 'Start Production', 'cancelled' => 'Cancel Request'],
    'in_production'      => ['ready_for_pickup' => 'Ready for Pickup', 'ready_for_delivery' => 'Ready for Delivery', 'cancelled' => 'Cancel Request'],
    'ready_for_pickup'   => ['ready_for_delivery' => 'Mark Ready for Delivery', 'completed' => 'Mark Completed', 'cancelled' => 'Cancel Request'],
    'ready_for_delivery' => ['ready_for_pickup' => 'Mark Ready for Pickup', 'completed' => 'Mark Completed', 'cancelled' => 'Cancel Request'],
    'completed'          => [],
    'cancelled'          => [],
];
?>

<?= $this->section('content') ?>

<div class="flex-1 space-y-gutter">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-md rounded-xl bg-green-100 text-green-800 text-sm font-medium flex items-center gap-sm">
            <span class="material-symbols-outlined text-[18px]">check_circle</span><?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-md rounded-xl bg-error-container text-on-error-container text-sm font-medium flex items-center gap-sm">
            <span class="material-symbols-outlined text-[18px]">error</span><?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="flex flex-wrap justify-between items-center gap-md">
        <div>
            <h2 class="text-headline-lg font-headline-lg text-on-surface">Printing Requests Management</h2>
            <p class="text-body-md font-body-md text-on-surface-variant">Track requests your customers submit and manage the production queue.</p>
        </div>
        <button type="button" onclick="openPrintingSettingsModal()" class="px-lg py-sm bg-surface-container-high hover:bg-surface-variant border border-outline-variant/40 rounded-xl text-on-surface font-semibold text-xs flex items-center gap-xs transition-all shadow-2xs hover:shadow-xs active:scale-95">
            <span class="material-symbols-outlined text-[18px]">settings</span>
            <span>Printing Settings</span>
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-gutter">
        <div class="glass-card p-lg rounded-xl hover-lift flex flex-col justify-between h-32">
            <div class="flex justify-between items-start">
                <span class="text-label-sm font-label-sm text-on-surface-variant">New Requests</span>
                <span class="material-symbols-outlined text-primary bg-primary-fixed p-xs rounded">new_releases</span>
            </div>
            <div class="text-headline-md font-headline-md text-primary"><?= number_format((int) $summary['new']) ?></div>
        </div>
        <div class="glass-card p-lg rounded-xl hover-lift flex flex-col justify-between h-32">
            <div class="flex justify-between items-start">
                <span class="text-label-sm font-label-sm text-on-surface-variant">In Production</span>
                <span class="material-symbols-outlined text-tertiary bg-tertiary-fixed p-xs rounded">manufacturing</span>
            </div>
            <div class="text-headline-md font-headline-md text-tertiary"><?= number_format((int) $summary['in_production']) ?></div>
        </div>
        <div class="glass-card p-lg rounded-xl hover-lift flex flex-col justify-between h-32">
            <div class="flex justify-between items-start">
                <span class="text-label-sm font-label-sm text-on-surface-variant">Ready for Pickup</span>
                <span class="material-symbols-outlined text-secondary bg-secondary-container p-xs rounded">local_shipping</span>
            </div>
            <div class="text-headline-md font-headline-md text-secondary"><?= number_format((int) $summary['ready_for_pickup']) ?></div>
        </div>
        <div class="glass-card p-lg rounded-xl hover-lift flex flex-col justify-between h-32">
            <div class="flex justify-between items-start">
                <span class="text-label-sm font-label-sm text-on-surface-variant">Ready for Delivery</span>
                <span class="material-symbols-outlined text-outline bg-outline/10 p-xs rounded">local_post_office</span>
            </div>
            <div class="text-headline-md font-headline-md text-outline"><?= number_format((int) $summary['ready_for_delivery']) ?></div>
        </div>
        <div class="glass-card p-lg rounded-xl hover-lift flex flex-col justify-between h-32">
            <div class="flex justify-between items-start">
                <span class="text-label-sm font-label-sm text-on-surface-variant">Completed</span>
                <span class="material-symbols-outlined text-green-600 bg-green-100 p-xs rounded">check_circle</span>
            </div>
            <div class="text-headline-md font-headline-md text-green-600"><?= number_format((int) $summary['completed']) ?></div>
        </div>
    </div>

    <!-- Recent Requests -->
    <div class="glass-card rounded-xl overflow-hidden shadow-sm">
        <div class="px-lg py-md flex flex-wrap justify-between items-center gap-md border-b border-outline-variant/30">
            <h3 class="text-title-lg font-bold text-on-surface">Recent Requests</h3>
            <form method="get" action="<?= base_url('tenant/printing') ?>" class="flex flex-wrap items-center gap-sm">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                    <input name="q" value="<?= esc($filters['q']) ?>" placeholder="Search request or customer..." class="pl-xl pr-md py-sm bg-surface-container-low border border-outline-variant rounded-lg text-body-md focus:outline-none focus:ring-2 focus:ring-primary" type="text">
                </div>
                <select name="status" class="bg-surface-container-low border border-outline-variant rounded-lg px-md py-sm text-label-sm font-label-sm text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All Statuses</option>
                    <option value="new" <?= $filters['status'] === 'new' ? 'selected' : '' ?>>New</option>
                    <option value="in_production" <?= $filters['status'] === 'in_production' ? 'selected' : '' ?>>In Production</option>
                    <option value="ready_for_pickup" <?= $filters['status'] === 'ready_for_pickup' ? 'selected' : '' ?>>Ready for Pickup</option>
                    <option value="ready_for_delivery" <?= $filters['status'] === 'ready_for_delivery' ? 'selected' : '' ?>>Ready for Delivery</option>
                    <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <button type="submit" class="bg-primary text-on-primary px-md py-sm rounded-lg text-label-sm font-semibold hover:bg-primary/90 transition-colors">Filter</button>
                <a href="<?= base_url('tenant/printing') ?>" class="px-md py-sm text-on-surface-variant hover:text-on-surface text-label-sm font-semibold">Reset</a>
            </form>
        </div>

        <div class="responsive-table">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low border-b border-outline-variant/30">
                    <tr>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Request ID</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Customer</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">File</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Specifications</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider text-center">Status</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $r): ?>
                            <?php
$fullName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                            $initials = $fullName !== '' ? mb_strtoupper(mb_substr($fullName, 0, 2)) : 'GU';
                            $profileImage = trim((string) ($r['profile_image_url'] ?? ''));
                            ?>
                            <tr class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="px-lg py-md">
                                    <span class="font-mono text-body-md font-semibold text-primary">#<?= esc($r['request_number']) ?></span>
                                </td>
                                <td class="px-lg py-md">
                                    <div class="flex items-center gap-sm">
                                        <div class="w-8 h-8 rounded-full bg-primary-container text-primary flex items-center justify-center text-label-sm font-bold <?= $profileImage !== '' ? 'relative overflow-hidden' : '' ?>">
                                            <span><?= esc($initials) ?></span>
                            $fullName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                            $initials = $fullName !== '' ? mb_strtoupper(mb_substr($fullName, 0, 2)) : 'GU';
                            $profileImage = trim((string) ($r['profile_image_url'] ?? ''));
                            ?>
                            <tr class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="px-lg py-md">
                                    <span class="font-mono text-body-md font-semibold text-primary">#<?= esc($r['request_number']) ?></span>
                                </td>
                                <td class="px-lg py-md">
                                    <div class="flex items-center gap-sm">
                                        <div class="w-8 h-8 rounded-full bg-primary-container text-primary flex items-center justify-center text-label-sm font-bold <?= $profileImage !== '' ? 'relative overflow-hidden' : '' ?>">
                                            <span><?= esc($initials) ?></span>
                                            <?php if ($profileImage !== ''): ?>
                                                <img class="absolute inset-0 w-full h-full object-cover" src="<?= esc(base_url($profileImage)) ?>" alt="<?= esc($fullName) ?> avatar" loading="lazy" onerror="this.remove();">
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-body-md text-on-surface"><?= esc($fullName !== '' ? $fullName : 'Customer') ?></span>
                                    </div>
                                </td>
                                <td class="px-lg py-md">
                                    <div class="flex items-center gap-sm text-on-surface-variant">
                                        <span class="material-symbols-outlined text-outline">description</span>
                                        <div>
                                            <span class="text-body-md max-w-[180px] truncate block" title="<?= esc($r['file_name']) ?>"><?= esc($r['file_name']) ?></span>
                                            <?php if (!empty($r['document_type']) && $r['document_type'] === 'docx'): ?>
                                                <div class="flex items-center gap-1 mt-0.5">
                                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-100 text-blue-800 uppercase tracking-wider">DOCX</span>
                                                    <?php if (($r['doc_change_type'] ?? '') === 'has_changes'): ?>
                                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Has Changes</span>
                                                    <?php else: ?>
                                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-medium bg-surface-container text-outline">As Is</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-lg py-md">
                                    <div class="space-y-xs">
                                        <span class="inline-flex items-center gap-xs px-sm py-0.5 rounded-full bg-surface-variant text-on-surface-variant text-label-sm font-semibold">
                                            <?= strtoupper(esc($r['color_mode'] === 'color' ? 'Color' : 'B&W')) ?> • <?= esc(ucfirst($r['paper_size'] ?? 'A4')) ?>
                                        </span>
                                        <p class="text-label-sm text-on-surface-variant"><?= esc(pr_spec_line($r)) ?></p>
                                    </div>
                                </td>
                                <td class="px-lg py-md text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <?= status_badge($r['status']) ?>
                                        <?php if (stripos($r['status'], 'down payment') !== false || ($r['down_payment'] > 0 && $r['status'] !== 'cancelled')): ?>
                                            <span class="inline-flex items-center gap-xs px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800">
                                                <span class="material-symbols-outlined text-[12px]">payments</span> Down Payment Paid
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-lg py-md text-right">
                                    <div class="flex justify-end items-center gap-md">
                                         <button type="button"
                                                 onclick="openRequestDetails(this)"
                                                 class="p-xs hover:bg-surface-container-high rounded text-on-surface-variant"
                                                 title="View Details"
                                                 data-request="<?= esc($r['request_number']) ?>"
                                                 data-customer="<?= esc($fullName !== '' ? $fullName : 'Customer') ?>"
                                                 data-file="<?= esc($r['file_name']) ?>"
                                                 data-doctype="<?= esc(strtoupper($r['document_type'] ?? 'PDF')) ?>"
                                                 data-changetype="<?= esc(($r['doc_change_type'] ?? '') === 'has_changes' ? 'Requested Changes' : 'Print As-Is') ?>"
                                                 data-instructions="<?= esc($r['special_instructions'] ?? '') ?>"
                                                 data-attachments="<?= esc(json_encode($r['attachments'] ?? []), 'attr') ?>"
                                                 data-specs="<?= esc(pr_spec_line($r)) ?>"
                                                 data-color="<?= esc(strtoupper($r['color_mode'] === 'color' ? 'Color' : 'B&W')) ?> • <?= esc(ucfirst($r['paper_size'] ?? 'A4')) ?>"
                                                 data-fulfillment="<?= esc(ucfirst(str_replace('_', ' ', $r['fulfillment_method'] ?? 'delivery'))) ?>"
                                                 data-printer="<?= esc($r['printer_assigned'] ?? 'Not assigned') ?>"
                                                 data-progress="<?= (int) $r['progress_percent'] ?>"
                                                 data-amount="<?= esc(number_format((float) $r['total_price'], 2)) ?>"
                                                 data-down="<?= esc(number_format((float) $r['down_payment'], 2)) ?>"
                                                 data-date="<?= esc(date('M d, Y h:i A', strtotime($r['created_at']))) ?>">
                                             <span class="material-symbols-outlined">visibility</span>
                                         </button>
                                         <button type="button"
                                                 onclick="openTenantQrModal('<?= esc($r['request_number']) ?>', '<?= esc(ucfirst(str_replace('_', ' ', $r['fulfillment_method'] ?? 'pickup'))) ?>')"
                                                 class="p-xs hover:bg-surface-container-high rounded text-secondary"
                                                 title="View QR Code">
                                             <span class="material-symbols-outlined">qr_code_2</span>
                                         </button>
                                         <a href="<?= base_url('tenant/printing/download/' . (int) $r['id']) ?>" class="p-xs hover:bg-surface-container-high rounded text-primary" title="Download File">
                                             <span class="material-symbols-outlined">download</span>
                                         </a>
                                        <div class="relative">
                                            <button type="button" data-row="<?= (int) $r['id'] ?>" onclick="toggleDropdown(this)" class="more-toggle p-xs hover:bg-surface-container-high rounded text-on-surface-variant" title="More Actions" aria-haspopup="true" aria-expanded="false">
                                                <span class="material-symbols-outlined">more_vert</span>
                                            </button>
                                            <div id="more-menu-<?= (int) $r['id'] ?>" class="hidden more-menu z-50 bg-surface-container-lowest border border-outline-variant/30 rounded-xl shadow-lg p-sm min-w-[220px]" role="menu">
                                                <p class="text-label-sm font-bold text-on-surface-variant px-sm pb-xs">Update Status</p>
                                                <?php if (!empty($nextStates[$r['status']])): ?>
                                                    <form action="<?= base_url('tenant/printing/update-status') ?>" method="POST" class="space-y-xs px-sm pb-sm">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="request_id" value="<?= (int) $r['id'] ?>">
                                                        <select name="status" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-lg text-label-sm font-label-sm">
                                                            <?php foreach ($nextStates[$r['status']] as $val => $label): ?>
                                                                <option value="<?= $val ?>"><?= esc($label) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="submit" class="w-full py-sm bg-primary text-on-primary rounded-lg text-label-sm font-semibold hover:bg-primary/90">Apply Status</button>
                                                    </form>
                                                <?php else: ?>
                                                    <p class="text-label-sm text-on-surface-variant px-sm pb-sm">This request is <?= esc($r['status']) ?> and can no longer be updated.</p>
                                                <?php endif; ?>
                                                <div class="border-t border-outline-variant/20 my-xs"></div>
                                                <form action="<?= base_url('tenant/printing/archive/' . (int) $r['id']) ?>" method="POST" onsubmit="return confirm('Archive this request?')">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="w-full text-left px-sm py-sm rounded-lg text-error text-label-sm font-semibold hover:bg-error-container/20 flex items-center gap-xs">
                                                        <span class="material-symbols-outlined text-[18px]">archive</span> Archive
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-lg text-center text-on-surface-variant">No requests match your filters.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php
        $rTotal = (int) $pager->getTotal('recent');
        $rCur   = (int) $pager->getCurrentPage('recent');
        $rPages = (int) $pager->getPageCount('recent');
        $rStart = $rTotal === 0 ? 0 : ($rCur - 1) * 10 + 1;
        $rEnd   = min($rCur * 10, $rTotal);
        ?>
        <?php if ($rPages > 1): ?>
            <div class="px-lg py-md bg-surface-container-low flex justify-between items-center border-t border-outline-variant/30 flex-wrap gap-sm">
                <p class="text-label-sm font-label-sm text-on-surface-variant">Showing <?= number_format($rStart) ?> to <?= number_format($rEnd) ?> of <?= number_format($rTotal) ?> requests</p>
                <div class="flex items-center gap-xs">
                    <a class="p-sm rounded hover:bg-surface-container-high <?= $rCur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('recent') ?>">
                        <span class="material-symbols-outlined">chevron_left</span>
                    </a>
                    <?php
                    $w = [];
                    for ($i = 1; $i <= $rPages; $i++) {
                        if ($i === 1 || $i === $rPages || abs($i - $rCur) <= 2) { $w[] = $i; }
                    }
                    $pv = 0;
                    foreach ($w as $n):
                        if ($n - $pv > 1): ?><span class="px-xs text-outline">...</span><?php endif; ?>
                        <a class="w-8 h-8 rounded flex items-center justify-center text-label-sm <?= $rCur === $n ? 'bg-primary text-on-primary font-semibold' : 'hover:bg-surface-container-high' ?>" href="<?= $pager->getPageURI($n, 'recent') ?>"><?= $n ?></a>
                    <?php $pv = $n; endforeach; ?>
                    <a class="p-sm rounded hover:bg-surface-container-high <?= $rCur >= $rPages ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('recent') ?>">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Production Queue Timeline -->
    <div class="glass-card rounded-xl p-lg shadow-sm">
        <div class="flex items-center gap-sm mb-md">
            <span class="material-symbols-outlined text-tertiary">pending_actions</span>
            <h3 class="text-title-lg font-bold text-on-surface">Production Queue</h3>
        </div>
        <?php if (!empty($queue)): ?>
            <div class="space-y-md">
                <?php foreach ($queue as $i => $q): ?>
                    <div class="flex gap-md">
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 rounded-full bg-tertiary-container/20 text-tertiary flex items-center justify-center font-bold text-label-sm relative">
                                <?= $i + 1 ?>
                                <?php if ($i === 0): ?>
                                    <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-tertiary animate-pulse"></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($i < count($queue) - 1): ?>
                                <div class="w-px flex-1 bg-outline-variant/40"></div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 pb-md">
                            <div class="flex flex-wrap justify-between items-center gap-sm">
                                <div class="flex items-center gap-sm flex-wrap">
                                    <span class="font-mono text-body-md font-semibold text-on-surface">#<?= esc($q['request_number']) ?></span>
                                    <?php if ($i === 0): ?>
                                        <span class="px-sm py-0.5 rounded-full bg-tertiary-container/20 text-tertiary text-label-sm font-bold">Active Now</span>
                                    <?php else: ?>
                                        <span class="px-sm py-0.5 rounded-full bg-surface-variant text-on-surface-variant text-label-sm font-semibold">In Queue</span>
                                    <?php endif; ?>
                                    <span class="text-label-sm text-on-surface-variant"><?= esc($q['printer_assigned'] ?? 'No printer assigned') ?></span>
                                </div>
                                <span class="text-label-sm text-on-surface-variant"><?= esc(date('M d, Y h:i A', strtotime($q['created_at']))) ?></span>
                            </div>
                            <p class="text-body-md text-on-surface-variant mb-xs truncate" title="<?= esc($q['file_name']) ?>"><?= esc($q['file_name']) ?></p>
                            <div class="h-2 bg-surface-variant rounded-full overflow-hidden">
                                <div class="h-full bg-tertiary rounded-full transition-all" style="width: <?= min(100, max(0, (int) $q['progress_percent'])) ?>%"></div>
                            </div>
                            <p class="text-label-sm text-on-surface-variant mt-xs"><?= (int) $q['progress_percent'] ?>% complete</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-body-md text-on-surface-variant">No requests are currently in production.</p>
        <?php endif; ?>
    </div>

    <!-- Completed Requests -->
    <div class="glass-card rounded-xl overflow-hidden shadow-sm">
        <div class="px-lg py-md flex flex-wrap justify-between items-center gap-md border-b border-outline-variant/30">
            <h3 class="text-title-lg font-bold text-on-surface">Completed Requests</h3>
            <div class="flex flex-wrap items-center gap-sm">
                <form method="get" action="<?= base_url('tenant/printing') ?>" class="relative">
                    <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                    <input name="cq" value="<?= esc($filters['cq']) ?>" placeholder="Search completed..." class="pl-xl pr-md py-sm bg-surface-container-low border border-outline-variant rounded-lg text-body-md focus:outline-none focus:ring-2 focus:ring-primary" type="text">
                </form>
                <form action="<?= base_url('tenant/printing/archive-all') ?>" method="POST" onsubmit="return confirm('Archive all completed requests? They can be restored from the Archive page.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="bg-surface-container-high text-on-surface px-md py-sm rounded-lg flex items-center gap-xs text-label-sm font-semibold hover:bg-surface-variant transition-colors">
                        <span class="material-symbols-outlined text-[18px]">archive</span>
                        Archive All
                    </button>
                </form>
            </div>
        </div>

        <div class="responsive-table">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low border-b border-outline-variant/30">
                    <tr>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Request ID</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Customer</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">File</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Specifications</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php if (!empty($completed)): ?>
                        <?php foreach ($completed as $r): ?>
                            <?php
                            $fullName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                            $initials = $fullName !== '' ? mb_strtoupper(mb_substr($fullName, 0, 2)) : 'GU';
                            ?>
                            <tr class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="px-lg py-md">
                                    <span class="font-mono text-body-md font-semibold text-primary">#<?= esc($r['request_number']) ?></span>
                                </td>
                                <td class="px-lg py-md">
                                    <div class="flex items-center gap-sm">
                                        <div class="w-8 h-8 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-label-sm font-bold"><?= esc($initials) ?></div>
                                        <span class="text-body-md text-on-surface"><?= esc($fullName !== '' ? $fullName : 'Customer') ?></span>
                                    </div>
                                </td>
                                <td class="px-lg py-md">
                                    <div class="flex items-center gap-sm text-on-surface-variant">
                                        <span class="material-symbols-outlined text-outline">description</span>
                                        <div>
                                            <span class="text-body-md max-w-[180px] truncate block" title="<?= esc($r['file_name']) ?>"><?= esc($r['file_name']) ?></span>
                                            <?php if (!empty($r['document_type']) && $r['document_type'] === 'docx'): ?>
                                                <div class="flex items-center gap-1 mt-0.5">
                                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-100 text-blue-800 uppercase tracking-wider">DOCX</span>
                                                    <?php if (($r['doc_change_type'] ?? '') === 'has_changes'): ?>
                                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Has Changes</span>
                                                    <?php else: ?>
                                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-medium bg-surface-container text-outline">As Is</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-lg py-md">
                                    <div class="space-y-xs">
                                        <span class="inline-flex items-center gap-xs px-sm py-0.5 rounded-full bg-surface-variant text-on-surface-variant text-label-sm font-semibold">
                                            <?= strtoupper(esc($r['color_mode'] === 'color' ? 'Color' : 'B&W')) ?> • <?= esc(ucfirst($r['paper_size'] ?? 'A4')) ?>
                                        </span>
                                        <p class="text-label-sm text-on-surface-variant"><?= esc(pr_spec_line($r)) ?></p>
                                    </div>
                                </td>
                                <td class="px-lg py-md text-right">
                                    <div class="flex justify-end items-center gap-md">
                                        <button type="button"
                                                onclick="openRequestDetails(this)"
                                                class="p-xs hover:bg-surface-container-high rounded text-on-surface-variant"
                                                title="View Details"
                                                data-request="<?= esc($r['request_number']) ?>"
                                                data-customer="<?= esc($fullName !== '' ? $fullName : 'Customer') ?>"
                                                data-file="<?= esc($r['file_name']) ?>"
                                                data-doctype="<?= esc(strtoupper($r['document_type'] ?? 'PDF')) ?>"
                                                data-changetype="<?= esc(($r['doc_change_type'] ?? '') === 'has_changes' ? 'Requested Changes' : 'Print As-Is') ?>"
                                                data-instructions="<?= esc($r['special_instructions'] ?? '') ?>"
                                                data-attachments="<?= esc(json_encode($r['attachments'] ?? []), 'attr') ?>"
                                                data-specs="<?= esc(pr_spec_line($r)) ?>"
                                                data-color="<?= esc(strtoupper($r['color_mode'] === 'color' ? 'Color' : 'B&W')) ?> • <?= esc(ucfirst($r['paper_size'] ?? 'A4')) ?>"
                                                data-fulfillment="<?= esc(ucfirst(str_replace('_', ' ', $r['fulfillment_method'] ?? 'delivery'))) ?>"
                                                data-printer="<?= esc($r['printer_assigned'] ?? 'Not assigned') ?>"
                                                data-progress="<?= (int) $r['progress_percent'] ?>"
                                                data-amount="<?= esc(number_format((float) $r['total_price'], 2)) ?>"
                                                data-down="<?= esc(number_format((float) $r['down_payment'], 2)) ?>"
                                                data-date="<?= esc(date('M d, Y h:i A', strtotime($r['created_at']))) ?>">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                        <button type="button"
                                                onclick="openTenantQrModal('<?= esc($r['request_number']) ?>', '<?= esc(ucfirst(str_replace('_', ' ', $r['fulfillment_method'] ?? 'pickup'))) ?>')"
                                                class="p-xs hover:bg-surface-container-high rounded text-secondary"
                                                title="View QR Code">
                                            <span class="material-symbols-outlined">qr_code_2</span>
                                        </button>
                                        <a href="<?= base_url('tenant/printing/download/' . (int) $r['id']) ?>" class="p-xs hover:bg-surface-container-high rounded text-primary" title="Download File">
                                            <span class="material-symbols-outlined">download</span>
                                        </a>
                                        <form action="<?= base_url('tenant/printing/archive/' . (int) $r['id']) ?>" method="POST" onsubmit="return confirm('Archive this request?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="p-xs hover:bg-error-container/20 rounded text-error" title="Archive">
                                                <span class="material-symbols-outlined">archive</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-lg text-center text-on-surface-variant">No completed requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php
        $cTotal = (int) $completed_pager->getTotal('completed');
        $cCur   = (int) $completed_pager->getCurrentPage('completed');
        $cPages = (int) $completed_pager->getPageCount('completed');
        $cStart = $cTotal === 0 ? 0 : ($cCur - 1) * 8 + 1;
        $cEnd   = min($cCur * 8, $cTotal);
        ?>
        <?php if ($cPages > 1): ?>
            <div class="px-lg py-md bg-surface-container-low flex justify-between items-center border-t border-outline-variant/30 flex-wrap gap-sm">
                <p class="text-label-sm font-label-sm text-on-surface-variant">Showing <?= number_format($cStart) ?> to <?= number_format($cEnd) ?> of <?= number_format($cTotal) ?> requests</p>
                <div class="flex items-center gap-xs">
                    <a class="p-sm rounded hover:bg-surface-container-high <?= $cCur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $completed_pager->getPreviousPageURI('completed') ?>">
                        <span class="material-symbols-outlined">chevron_left</span>
                    </a>
                    <?php
                    $w = [];
                    for ($i = 1; $i <= $cPages; $i++) {
                        if ($i === 1 || $i === $cPages || abs($i - $cCur) <= 2) { $w[] = $i; }
                    }
                    $pv = 0;
                    foreach ($w as $n):
                        if ($n - $pv > 1): ?><span class="px-xs text-outline">...</span><?php endif; ?>
                        <a class="w-8 h-8 rounded flex items-center justify-center text-label-sm <?= $cCur === $n ? 'bg-primary text-on-primary font-semibold' : 'hover:bg-surface-container-high' ?>" href="<?= $completed_pager->getPageURI($n, 'completed') ?>"><?= $n ?></a>
                    <?php $pv = $n; endforeach; ?>
                    <a class="p-sm rounded hover:bg-surface-container-high <?= $cCur >= $cPages ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $completed_pager->getNextPageURI('completed') ?>">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Request Details Modal -->
<div id="requestModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest rounded-2xl p-xl max-w-md w-full space-y-md border border-outline-variant/30">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">
            <h3 class="text-title-lg font-bold">Request <span id="rmRequest" class="text-primary"></span></h3>
            <button onclick="document.getElementById('requestModal').classList.add('hidden')" class="text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="space-y-sm max-h-[75vh] overflow-y-auto pr-1">
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Customer</span><span id="rmCustomer" class="text-body-md font-semibold text-on-surface"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">File</span><span id="rmFile" class="text-body-md text-on-surface"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Document Type</span><span id="rmDocType" class="text-body-md font-semibold text-on-surface"></span></div>
            <div class="flex justify-between" id="rmChangeTypeRow"><span class="text-label-sm text-on-surface-variant">Change Type</span><span id="rmChangeType" class="text-body-md text-on-surface"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Specifications</span><span id="rmSpecs" class="text-body-md text-on-surface text-right"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Color / Size</span><span id="rmColor" class="text-body-md text-on-surface"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Fulfillment</span><span id="rmFulfillment" class="text-body-md text-on-surface"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Printer</span><span id="rmPrinter" class="text-body-md text-on-surface"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Progress</span><span id="rmProgress" class="text-body-md font-semibold text-on-surface"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Total Price</span><span id="rmAmount" class="text-body-md font-semibold text-on-surface">₱0.00</span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Down Payment</span><span id="rmDown" class="text-body-md text-on-surface">₱0.00</span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Submitted</span><span id="rmDate" class="text-body-md text-on-surface"></span></div>

            <!-- Special Instructions Box -->
            <div id="rmInstructionsBox" class="hidden pt-2 border-t border-outline-variant/20">
                <span class="text-[11px] font-bold text-on-surface-variant block uppercase tracking-wider mb-1">Special Instructions / Changes</span>
                <p id="rmInstructions" class="text-xs text-on-surface bg-surface-container-low p-2.5 rounded-xl border border-outline-variant/30 whitespace-pre-wrap"></p>
            </div>

            <!-- Reference Attachments -->
            <div id="rmAttachmentsBox" class="hidden pt-2 border-t border-outline-variant/20">
                <span class="text-[11px] font-bold text-on-surface-variant block uppercase tracking-wider mb-1">Reference Attachments</span>
                <div id="rmAttachmentsList" class="grid grid-cols-2 gap-2 mt-1"></div>
            </div>
        </div>
    </div>
</div>

<!-- Tenant QR Modal -->
<div id="tenantQrModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest rounded-3xl p-xl max-w-sm w-full space-y-lg border border-outline-variant/30 text-center flex flex-col items-center shadow-2xl">
        <div class="flex justify-between items-center w-full border-b border-outline-variant/20 pb-sm">
            <div class="flex items-center gap-xs text-primary font-bold">
                <span class="material-symbols-outlined text-2xl">qr_code_2</span>
                <span class="text-title-md">Request QR Code</span>
            </div>
            <button onclick="document.getElementById('tenantQrModal').classList.add('hidden')" class="p-1 rounded-full hover:bg-surface-container-high text-on-surface-variant transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <div class="flex flex-col items-center justify-center bg-white p-6 rounded-2xl shadow-inner border border-slate-100">
            <div id="tenant-qr-canvas" class="flex justify-center items-center min-w-[200px] min-h-[200px]"></div>
        </div>

        <div class="flex flex-col items-center">
            <span class="text-[10px] uppercase tracking-widest text-outline font-bold">Request Number</span>
            <span id="tqrRequest" class="text-headline-sm font-mono font-bold text-primary mt-0.5">#PR-00000</span>
            <span id="tqrFulfillment" class="mt-2 text-xs px-md py-1 bg-primary/10 text-primary rounded-full font-semibold uppercase tracking-wider">Pickup</span>
        </div>

        <button type="button" onclick="document.getElementById('tenantQrModal').classList.add('hidden')" class="w-full py-md bg-primary text-on-primary rounded-xl text-button font-button hover:bg-primary-container transition-all active:scale-95 shadow-md">Close</button>
    </div>
</div>

<!-- Printing Settings Modal -->
<div id="printingSettingsModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-md overflow-y-auto">
    <div class="bg-surface-container-lowest rounded-3xl p-lg sm:p-xl max-w-2xl w-full space-y-lg border border-outline-variant/30 shadow-2xl my-auto">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">
            <div class="flex items-center gap-xs text-primary font-bold">
                <span class="material-symbols-outlined text-2xl">settings</span>
                <span class="text-title-lg">Printing Service Settings</span>
            </div>
            <button type="button" onclick="closePrintingSettingsModal()" class="p-1 rounded-full hover:bg-surface-container-high text-on-surface-variant transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="printingSettingsForm" onsubmit="handleSavePrintingSettings(event)" class="space-y-lg">
            <?= csrf_field() ?>

            <!-- General Options & Binding Pricing -->
            <div class="bg-surface-container-low p-md rounded-2xl border border-outline-variant/30 space-y-md">
                <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px] text-primary">payments</span>
                    General &amp; Binding Options
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-md">
                    <div>
                        <label class="text-[11px] font-bold text-on-surface-variant block mb-1">Down Payment (%)</label>
                        <div class="relative">
                            <input type="number" step="1" min="0" max="100" name="down_payment_percent" id="set_down_payment" value="<?= esc($printingSettings['down_payment_percent'] ?? 50) ?>" required class="w-full py-2 px-3 bg-surface-container border border-outline-variant/40 rounded-xl text-xs font-semibold text-on-surface focus:ring-1 focus:ring-primary focus:border-primary">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-outline font-bold">%</span>
                        </div>
                        <p class="text-[10px] text-on-surface-variant mt-0.5">Required before job queued</p>
                    </div>

                    <div>
                        <label class="text-[11px] font-bold text-on-surface-variant block mb-1">Staple Binding (₱)</label>
                        <div class="relative">
                            <input type="number" step="0.50" min="0" name="price_staple" id="set_price_staple" value="<?= esc($printingSettings['price_staple'] ?? 10) ?>" required class="w-full py-2 px-3 bg-surface-container border border-outline-variant/40 rounded-xl text-xs font-semibold text-on-surface focus:ring-1 focus:ring-primary focus:border-primary">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-outline font-bold">₱</span>
                        </div>
                        <p class="text-[10px] text-on-surface-variant mt-0.5">Price added per copy</p>
                    </div>

                    <div>
                        <label class="text-[11px] font-bold text-on-surface-variant block mb-1">Spiral Binding (₱)</label>
                        <div class="relative">
                            <input type="number" step="0.50" min="0" name="price_spiral" id="set_price_spiral" value="<?= esc($printingSettings['price_spiral'] ?? 35) ?>" required class="w-full py-2 px-3 bg-surface-container border border-outline-variant/40 rounded-xl text-xs font-semibold text-on-surface focus:ring-1 focus:ring-primary focus:border-primary">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-outline font-bold">₱</span>
                        </div>
                        <p class="text-[10px] text-on-surface-variant mt-0.5">Price added per copy</p>
                    </div>
                </div>
            </div>

            <!-- Paper Sizes & Per-Page Pricing Table -->
            <div class="space-y-sm">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px] text-primary">format_size</span>
                        Supported Paper Sizes &amp; Page Rates
                    </h4>
                    <span class="text-[11px] text-outline">Disabled sizes are hidden from customers</span>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-outline-variant/30 max-h-72 overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-surface-container-low text-[11px] font-bold text-on-surface-variant uppercase tracking-wider sticky top-0 border-b border-outline-variant/30 z-10">
                            <tr>
                                <th class="py-2.5 px-3">Paper Size</th>
                                <th class="py-2.5 px-3 text-center w-24">Enabled</th>
                                <th class="py-2.5 px-3 text-right w-36">Color Rate (₱)</th>
                                <th class="py-2.5 px-3 text-right w-36">B&amp;W Rate (₱)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20 bg-surface-container-lowest">
                            <?php if (!empty($paperSizes)): ?>
                                <?php foreach ($paperSizes as $sKey => $sVal): ?>
                                    <?php $isEnabled = !empty($sVal['is_enabled']); ?>
                                    <tr id="row_size_<?= esc($sKey) ?>" class="size-row <?= $isEnabled ? '' : 'opacity-50 bg-surface-container-low/40' ?> hover:bg-surface-container-low/30 transition-colors">
                                        <td class="py-2 px-3 font-semibold text-on-surface">
                                            <div><?= esc($sVal['label'] ?? strtoupper($sKey)) ?></div>
                                            <span class="text-[10px] text-outline font-mono uppercase"><?= esc($sKey) ?></span>
                                        </td>
                                        <td class="py-2 px-3 text-center">
                                            <input type="hidden" name="paper_sizes[<?= esc($sKey) ?>][is_enabled]" value="0">
                                            <input type="checkbox" name="paper_sizes[<?= esc($sKey) ?>][is_enabled]" value="1" <?= $isEnabled ? 'checked' : '' ?> onchange="togglePaperSizeRow('<?= esc($sKey) ?>', this.checked)" class="rounded border-outline-variant text-primary focus:ring-primary h-4 w-4 cursor-pointer">
                                        </td>
                                        <td class="py-2 px-3 text-right">
                                            <input type="number" step="0.25" min="0" name="paper_sizes[<?= esc($sKey) ?>][price_color]" value="<?= number_format((float) ($sVal['price_color'] ?? 5.00), 2, '.', '') ?>" class="w-24 ml-auto py-1 px-2 text-xs bg-surface-container border border-outline-variant/40 rounded-lg text-right font-medium text-on-surface focus:ring-1 focus:ring-primary focus:border-primary">
                                        </td>
                                        <td class="py-2 px-3 text-right">
                                            <input type="number" step="0.25" min="0" name="paper_sizes[<?= esc($sKey) ?>][price_bw]" value="<?= number_format((float) ($sVal['price_bw'] ?? 2.00), 2, '.', '') ?>" class="w-24 ml-auto py-1 px-2 text-xs bg-surface-container border border-outline-variant/40 rounded-lg text-right font-medium text-on-surface focus:ring-1 focus:ring-primary focus:border-primary">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="settingsFeedback" class="hidden p-sm rounded-xl text-xs font-semibold"></div>

            <div class="flex items-center justify-end gap-sm pt-sm border-t border-outline-variant/20">
                <button type="button" onclick="closePrintingSettingsModal()" class="px-lg py-2 rounded-xl border border-outline-variant/40 hover:bg-surface-container text-on-surface text-xs font-semibold transition-colors">
                    Cancel
                </button>
                <button type="submit" id="btnSavePrintingSettings" class="px-xl py-2 bg-primary text-on-primary rounded-xl text-xs font-bold hover:bg-primary/90 transition-all flex items-center gap-xs shadow-sm active:scale-95">
                    <span class="material-symbols-outlined text-[16px]">save</span>
                    <span>Save Settings</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
    function openPrintingSettingsModal() {
        document.getElementById('printingSettingsModal').classList.remove('hidden');
    }

    function closePrintingSettingsModal() {
        document.getElementById('printingSettingsModal').classList.add('hidden');
    }

    function togglePaperSizeRow(key, isChecked) {
        const row = document.getElementById('row_size_' + key);
        if (!row) return;
        if (isChecked) {
            row.classList.remove('opacity-50', 'bg-surface-container-low/40');
        } else {
            row.classList.add('opacity-50', 'bg-surface-container-low/40');
        }
    }

    async function handleSavePrintingSettings(e) {
        e.preventDefault();
        const form = document.getElementById('printingSettingsForm');
        const btn = document.getElementById('btnSavePrintingSettings');
        const fb = document.getElementById('settingsFeedback');

        btn.disabled = true;
        btn.classList.add('opacity-70');
        fb.className = 'hidden';

        try {
            const formData = new FormData(form);
            const res = await fetch('<?= base_url('tenant/printing/settings/save') ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                fb.className = 'p-sm rounded-xl text-xs font-semibold bg-green-100 text-green-800 flex items-center gap-1';
                fb.innerHTML = `<span class="material-symbols-outlined text-[16px]">check_circle</span> <span>${data.message || 'Settings saved successfully!'}</span>`;
                setTimeout(() => {
                    closePrintingSettingsModal();
                    window.location.reload();
                }, 800);
            } else {
                fb.className = 'p-sm rounded-xl text-xs font-semibold bg-red-100 text-red-800';
                fb.textContent = data.error || 'Failed to save settings.';
            }
        } catch (err) {
            fb.className = 'p-sm rounded-xl text-xs font-semibold bg-red-100 text-red-800';
            fb.textContent = 'Network error while saving settings.';
        } finally {
            btn.disabled = false;
            btn.classList.remove('opacity-70');
        }
    }
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

        // Teleport out of the table card: the .glass-card wrapper uses
        // backdrop-filter, which would otherwise become the containing
        // block for position:fixed and clip/misplace the menu.
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

    function openRequestDetails(btn) {
        document.getElementById('rmRequest').textContent = '#' + (btn.dataset.request || '');
        document.getElementById('rmCustomer').textContent = btn.dataset.customer || '';
        document.getElementById('rmFile').textContent = btn.dataset.file || '';
        document.getElementById('rmDocType').textContent = btn.dataset.doctype || 'PDF';
        
        const changeTypeRow = document.getElementById('rmChangeTypeRow');
        const changeTypeVal = document.getElementById('rmChangeType');
        if ((btn.dataset.doctype || '').toUpperCase() === 'DOCX') {
            changeTypeRow.classList.remove('hidden');
            changeTypeVal.textContent = btn.dataset.changetype || 'Print As-Is';
        } else {
            changeTypeRow.classList.add('hidden');
        }

        document.getElementById('rmSpecs').textContent = btn.dataset.specs || '';
        document.getElementById('rmColor').textContent = btn.dataset.color || '';
        document.getElementById('rmFulfillment').textContent = btn.dataset.fulfillment || '';
        document.getElementById('rmPrinter').textContent = btn.dataset.printer || '';
        document.getElementById('rmProgress').textContent = (btn.dataset.progress || 0) + '% complete';
        document.getElementById('rmAmount').textContent = '₱' + (btn.dataset.amount || '0.00');
        document.getElementById('rmDown').textContent = '₱' + (btn.dataset.down || '0.00');
        document.getElementById('rmDate').textContent = btn.dataset.date || '';

        // Special Instructions
        const instructionsBox = document.getElementById('rmInstructionsBox');
        const instructionsText = document.getElementById('rmInstructions');
        const inst = btn.dataset.instructions || '';
        if (inst.trim() !== '') {
            instructionsBox.classList.remove('hidden');
            instructionsText.textContent = inst;
        } else {
            instructionsBox.classList.add('hidden');
            instructionsText.textContent = '';
        }

        // Attachments
        const attBox = document.getElementById('rmAttachmentsBox');
        const attList = document.getElementById('rmAttachmentsList');
        attList.innerHTML = '';
        let attachments = [];
        try {
            attachments = JSON.parse(btn.dataset.attachments || '[]');
        } catch (e) {
            attachments = [];
        }

        if (attachments && attachments.length > 0) {
            attBox.classList.remove('hidden');
            attachments.forEach((att) => {
                const item = document.createElement('a');
                item.href = '<?= base_url() ?>/' + att.file_path;
                item.target = '_blank';
                item.className = 'p-2 rounded-xl bg-surface-container-low hover:bg-surface-container border border-outline-variant/30 flex items-center gap-2 text-xs transition-colors group';
                item.innerHTML = `
                    <span class="material-symbols-outlined text-primary text-[18px]">image</span>
                    <span class="truncate flex-1 font-medium text-on-surface group-hover:text-primary">${att.file_name}</span>
                    <span class="material-symbols-outlined text-outline text-[14px]">open_in_new</span>
                `;
                attList.appendChild(item);
            });
        } else {
            attBox.classList.add('hidden');
        }

        document.getElementById('requestModal').classList.remove('hidden');
    }

    function openTenantQrModal(reqNum, fulfillment) {
        document.getElementById('tqrRequest').textContent = '#' + reqNum;
        document.getElementById('tqrFulfillment').textContent = fulfillment;
        const container = document.getElementById('tenant-qr-canvas');
        container.innerHTML = '';
        if (window.QRCode) {
            new QRCode(container, {
                text: reqNum,
                width: 200,
                height: 200,
                colorDark: "#0f172a",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        }
        document.getElementById('tenantQrModal').classList.remove('hidden');
    }
</script>

<?= $this->endSection() ?>
