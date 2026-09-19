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
        <a href="<?= base_url('tenant/printing?status=new') ?>" class="glass-card p-lg rounded-xl hover-lift flex flex-col justify-between h-32 transition-all cursor-pointer group hover:border-primary/40">
            <div class="flex justify-between items-start">
                <span class="text-label-sm font-label-sm text-on-surface-variant group-hover:text-primary">New Requests</span>
                <span class="material-symbols-outlined text-primary bg-primary-fixed p-xs rounded">new_releases</span>
            </div>
            <div class="text-headline-md font-headline-md text-primary"><?= number_format((int) $summary['new']) ?></div>
        </a>
        <div onclick="document.getElementById('in-production-container')?.scrollIntoView({ behavior: 'smooth', block: 'start' })" class="glass-card p-lg rounded-xl hover-lift flex flex-col justify-between h-32 transition-all cursor-pointer group hover:border-tertiary/50 hover:shadow-md">
            <div class="flex justify-between items-start">
                <span class="text-label-sm font-label-sm text-on-surface-variant group-hover:text-tertiary flex items-center gap-1">
                    <span>In Production</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-tertiary <?= !empty($queue) ? 'animate-pulse' : '' ?>"></span>
                </span>
                <span class="material-symbols-outlined text-tertiary bg-tertiary-fixed p-xs rounded">manufacturing</span>
            </div>
            <div class="text-headline-md font-headline-md text-tertiary"><?= number_format((int) $summary['in_production']) ?></div>
        </div>
        <a href="<?= base_url('tenant/printing?status=ready_for_pickup') ?>" class="glass-card p-lg rounded-xl hover-lift flex flex-col justify-between h-32 transition-all cursor-pointer group hover:border-secondary/40">
            <div class="flex justify-between items-start">
                <span class="text-label-sm font-label-sm text-on-surface-variant group-hover:text-secondary">Ready for Pickup</span>
                <span class="material-symbols-outlined text-secondary bg-secondary-container p-xs rounded">local_shipping</span>
            </div>
            <div class="text-headline-md font-headline-md text-secondary"><?= number_format((int) $summary['ready_for_pickup']) ?></div>
        </a>
        <a href="<?= base_url('tenant/printing?status=ready_for_delivery') ?>" class="glass-card p-lg rounded-xl hover-lift flex flex-col justify-between h-32 transition-all cursor-pointer group hover:border-outline/40">
            <div class="flex justify-between items-start">
                <span class="text-label-sm font-label-sm text-on-surface-variant group-hover:text-outline">Ready for Delivery</span>
                <span class="material-symbols-outlined text-outline bg-outline/10 p-xs rounded">local_post_office</span>
            </div>
            <div class="text-headline-md font-headline-md text-outline"><?= number_format((int) $summary['ready_for_delivery']) ?></div>
        </a>
        <a href="<?= base_url('tenant/printing?status=completed') ?>" class="glass-card p-lg rounded-xl hover-lift flex flex-col justify-between h-32 transition-all cursor-pointer group hover:border-green-400">
            <div class="flex justify-between items-start">
                <span class="text-label-sm font-label-sm text-on-surface-variant group-hover:text-green-700">Completed</span>
                <span class="material-symbols-outlined text-green-600 bg-green-100 p-xs rounded">check_circle</span>
            </div>
            <div class="text-headline-md font-headline-md text-green-600"><?= number_format((int) $summary['completed']) ?></div>
        </a>
    </div>    <!-- Main Workspace: 2-Column Responsive Layout -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-gutter items-start">

        <!-- Left Column: Requests & Completed Tables (8 cols on XL) -->
        <div class="xl:col-span-8 space-y-gutter">

            <!-- Recent Requests -->
            <div class="glass-card rounded-2xl overflow-hidden shadow-sm border border-outline-variant/30">
                <div class="px-lg py-md flex flex-wrap justify-between items-center gap-md border-b border-outline-variant/30 bg-surface-container-lowest">
                    <div class="flex items-center gap-sm">
                        <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                            <span class="material-symbols-outlined text-[20px]">print</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-title-lg font-bold text-on-surface">Recent Requests</h3>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-primary/10 text-primary border border-primary/20">
                                    <?= number_format((int) ($pager->getTotal('recent') ?? count($requests))) ?> total
                                </span>
                            </div>
                            <p class="text-[11px] text-on-surface-variant">Incoming and ongoing customer printing orders</p>
                        </div>
                    </div>
                    <form method="get" action="<?= base_url('tenant/printing') ?>" id="recentFiltersForm" class="flex flex-wrap items-center gap-xs sm:gap-sm">
                        <input type="hidden" name="doc_type" id="filterDocTypeInput" value="<?= esc($filters['doc_type'] ?? '') ?>">
                        
                        <!-- Real-time Search with Clear Button -->
                        <div class="relative min-w-[190px] sm:min-w-[220px]">
                            <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                            <input name="q" id="recentSearchInput" value="<?= esc($filters['q']) ?>" oninput="handleSearchInput(this)" placeholder="Search request or customer..." autocomplete="off" class="w-full pl-xl pr-8 py-sm bg-surface-container-low border border-outline-variant rounded-xl text-body-md focus:outline-none focus:ring-2 focus:ring-primary text-xs" type="text">
                            <button type="button" id="searchClearBtn" onclick="clearSearchInput()" class="<?= empty($filters['q']) ? 'hidden ' : '' ?>absolute right-2 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface p-0.5 rounded-full transition-colors" title="Clear search">
                                <span class="material-symbols-outlined text-[16px]">close</span>
                            </button>
                        </div>

                        <!-- Real-time Document Type Toggle Buttons (All, PDF, Docs) -->
                        <div class="inline-flex rounded-xl bg-surface-container-low p-1 border border-outline-variant/40 shadow-2xs">
                            <button type="button" onclick="setDocTypeFilter('')" class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all <?= ($filters['doc_type'] ?? '') === '' ? 'bg-primary text-on-primary shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' ?>" title="Show all file types">
                                All
                            </button>
                            <button type="button" onclick="setDocTypeFilter('pdf')" class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 <?= ($filters['doc_type'] ?? '') === 'pdf' ? 'bg-red-600 text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' ?>" title="Filter only PDF requests">
                                <span class="w-2 h-2 rounded-full <?= ($filters['doc_type'] ?? '') === 'pdf' ? 'bg-white' : 'bg-red-500' ?>"></span>
                                <span>PDF</span>
                            </button>
                            <button type="button" onclick="setDocTypeFilter('docx')" class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 <?= ($filters['doc_type'] ?? '') === 'docx' ? 'bg-blue-600 text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' ?>" title="Filter only Word / DOCS requests">
                                <span class="w-2 h-2 rounded-full <?= ($filters['doc_type'] ?? '') === 'docx' ? 'bg-white' : 'bg-blue-500' ?>"></span>
                                <span>Docs</span>
                            </button>
                        </div>

                        <!-- Real-time Status Filter -->
                        <select name="status" onchange="document.getElementById('recentFiltersForm').submit()" class="bg-surface-container-low border border-outline-variant rounded-xl px-md py-sm text-xs font-semibold text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-primary cursor-pointer">
                            <option value="">All Statuses</option>
                            <option value="new" <?= $filters['status'] === 'new' ? 'selected' : '' ?>>New</option>
                            <option value="in_production" <?= $filters['status'] === 'in_production' ? 'selected' : '' ?>>In Production</option>
                            <option value="ready_for_pickup" <?= $filters['status'] === 'ready_for_pickup' ? 'selected' : '' ?>>Ready for Pickup</option>
                            <option value="ready_for_delivery" <?= $filters['status'] === 'ready_for_delivery' ? 'selected' : '' ?>>Ready for Delivery</option>
                            <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>

                        <!-- Real-time Per Page Filter -->
                        <select name="per_page" onchange="document.getElementById('recentFiltersForm').submit()" class="bg-surface-container-low border border-outline-variant rounded-xl px-md py-sm text-xs font-semibold text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-primary cursor-pointer">
                            <option value="10" <?= (isset($_GET['per_page']) && $_GET['per_page'] == '10') ? 'selected' : '' ?>>10 / page</option>
                            <option value="20" <?= (isset($_GET['per_page']) && $_GET['per_page'] == '20') ? 'selected' : '' ?>>20 / page</option>
                            <option value="50" <?= (isset($_GET['per_page']) && $_GET['per_page'] == '50') ? 'selected' : '' ?>>50 / page</option>
                        </select>
                    </form>
                </div>

                <div class="overflow-x-auto w-full scrollbar-thin">
                    <table class="w-full text-left border-collapse min-w-[780px]">
                        <thead class="bg-surface-container-low/70 border-b border-outline-variant/30 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Request ID</th>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">File</th>
                                <th class="px-4 py-3">Specifications</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20 bg-surface-container-lowest">
                            <?php if (!empty($requests)): ?>
                                <?php foreach ($requests as $r): ?>
                                    <?php
                                    $rawReqNum = trim((string) ($r['request_number'] ?? ''));
                                    $reqNum = $rawReqNum !== '' ? $rawReqNum : ('PR-' . $r['id']);
                                    $cleanReq = '#' . ltrim($reqNum, '#');
                                    $fullName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                                    $initials = $fullName !== '' ? mb_strtoupper(mb_substr($fullName, 0, 2)) : 'GU';
                                    $profileImage = trim((string) ($r['profile_image_url'] ?? ''));
                                    $isDeliv = ($r['fulfillment_method'] ?? 'pickup') === 'delivery';
                                    $fileName = !empty($r['file_name']) ? $r['file_name'] : 'Document.pdf';
                                    $docType = strtoupper(!empty($r['document_type']) ? $r['document_type'] : (pathinfo($fileName, PATHINFO_EXTENSION) ?: 'PDF'));
                                    ?>
                                    <tr class="hover:bg-surface-container-low/40 transition-colors">
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            <button type="button"
                                                    onclick="openRequestDetails(this)"
                                                    class="font-mono text-xs font-bold text-primary hover:text-primary-container bg-primary/5 hover:bg-primary/15 border border-primary/20 px-2.5 py-1 rounded-lg inline-flex items-center gap-1 transition-all active:scale-95"
                                                    title="View Full Details"
                                                    data-id="<?= (int) $r['id'] ?>"
                                                    data-request="<?= esc($reqNum) ?>"
                                                    data-customer="<?= esc($fullName !== '' ? $fullName : 'Customer') ?>"
                                                    data-file="<?= esc($fileName) ?>"
                                                    data-doctype="<?= esc($docType) ?>"
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
                                                <span class="material-symbols-outlined text-[13px]">tag</span>
                                                <span><?= esc($cleanReq) ?></span>
                                            </button>
                                            <div class="text-[10px] text-on-surface-variant/70 font-medium mt-1 flex items-center gap-1 whitespace-nowrap">
                                                <span class="material-symbols-outlined text-[12px]">schedule</span>
                                                <span><?= esc(date('M d, g:i A', strtotime($r['created_at']))) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <div class="flex items-center gap-2.5 min-w-[130px]">
                                                <div class="w-8 h-8 rounded-full bg-primary-container text-primary flex items-center justify-center text-xs font-bold shrink-0 <?= $profileImage !== '' ? 'relative overflow-hidden' : '' ?> ring-2 ring-surface">
                                                    <span><?= esc($initials) ?></span>
                                                    <?php if ($profileImage !== ''): ?>
                                                        <img class="absolute inset-0 w-full h-full object-cover" src="<?= esc(base_url($profileImage)) ?>" alt="<?= esc($fullName) ?> avatar" loading="lazy" onerror="this.remove();">
                                                    <?php endif; ?>
                                                </div>
                                                <div class="min-w-0">
                                                    <span class="text-xs font-bold text-on-surface truncate block leading-tight max-w-[130px]" title="<?= esc($fullName !== '' ? $fullName : 'Customer') ?>"><?= esc($fullName !== '' ? $fullName : 'Guest Customer') ?></span>
                                                    <span class="text-[10px] text-on-surface-variant truncate block max-w-[130px] mt-0.5"><?= esc(!empty($r['phone']) ? $r['phone'] : (!empty($r['email']) ? $r['email'] : 'Direct Order')) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary shrink-0 border border-outline-variant/30">
                                                    <span class="material-symbols-outlined text-[18px]">description</span>
                                                </div>
                                                <div class="min-w-0 max-w-[150px] sm:max-w-[170px]">
                                                    <span class="text-xs font-semibold text-on-surface truncate block" title="<?= esc($fileName) ?>"><?= esc($fileName) ?></span>
                                                    <div class="flex items-center gap-1.5 mt-0.5">
                                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold tracking-wider uppercase <?= ($docType === 'DOCX') ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800' ?>">
                                                            <?= esc($docType) ?>
                                                        </span>
                                                        <?php if ($docType === 'DOCX'): ?>
                                                            <span class="text-[10px] font-medium <?= (($r['doc_change_type'] ?? '') === 'has_changes') ? 'text-amber-700 font-semibold' : 'text-on-surface-variant' ?>">
                                                                <?= (($r['doc_change_type'] ?? '') === 'has_changes') ? 'Changes' : 'As-Is' ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-surface-container text-on-surface text-[11px] font-semibold border border-outline-variant/20 whitespace-nowrap">
                                                        <?= strtoupper(esc($r['color_mode'] === 'color' ? 'Color' : 'B&W')) ?> • <?= esc(ucfirst($r['paper_size'] ?? 'A4')) ?>
                                                    </span>
                                                    <?php if ($isDeliv): ?>
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 text-[10px] font-bold border border-blue-200/50 whitespace-nowrap" title="Doorstep Delivery">
                                                            <span class="material-symbols-outlined text-[12px]">local_shipping</span> Delivery
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200/50 whitespace-nowrap" title="Store Pick-up">
                                                            <span class="material-symbols-outlined text-[12px]">storefront</span> Pick-up
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex items-center gap-1.5 text-[11px] text-on-surface-variant whitespace-nowrap">
                                                    <span><?= (int) $r['page_count'] ?> pgs • <?= (int) $r['copies'] ?> <?= (int) $r['copies'] === 1 ? 'copy' : 'copies' ?></span>
                                                    <span class="text-outline-variant/60">•</span>
                                                    <span class="font-bold text-on-surface">₱<?= number_format((float) $r['total_price'], 2) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                            <div class="flex flex-col items-center gap-1">
                                                <?= status_badge($r['status']) ?>
                                                <?php if (stripos($r['status'], 'down payment') !== false || ($r['down_payment'] > 0 && $r['status'] !== 'cancelled')): ?>
                                                    <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 whitespace-nowrap">
                                                        <span class="material-symbols-outlined text-[11px]">payments</span> Paid
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-1.5">

                                                <!-- Outward View Details Button -->
                                                <button type="button"
                                                        onclick="openRequestDetails(this)"
                                                        class="px-2.5 py-1.5 rounded-xl bg-surface-container hover:bg-primary hover:text-on-primary text-on-surface font-bold text-xs flex items-center gap-1 transition-all shadow-2xs active:scale-95 border border-outline-variant/30"
                                                        title="View Full Details"
                                                        data-id="<?= (int) $r['id'] ?>"
                                                        data-request="<?= esc($reqNum) ?>"
                                                        data-customer="<?= esc($fullName !== '' ? $fullName : 'Customer') ?>"
                                                        data-file="<?= esc($fileName) ?>"
                                                        data-doctype="<?= esc($docType) ?>"
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
                                                    <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                    <span>Details</span>
                                                </button>

                                                <!-- Direct Fulfillment-Aware Action Button for In Production & New Requests -->
                                                <?php if ($r['status'] === 'in_production'): ?>
                                                    <?php if ($isDeliv): ?>
                                                        <form action="<?= base_url('tenant/printing/update-status') ?>" method="POST" class="inline">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="request_id" value="<?= (int) $r['id'] ?>">
                                                            <input type="hidden" name="status" value="ready_for_delivery">
                                                            <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-1 shadow-2xs transition-all active:scale-95" title="Mark Shipped for Delivery">
                                                                <span class="material-symbols-outlined text-[15px]">local_shipping</span>
                                                                <span>Shipped</span>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form action="<?= base_url('tenant/printing/update-status') ?>" method="POST" class="inline">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="request_id" value="<?= (int) $r['id'] ?>">
                                                            <input type="hidden" name="status" value="ready_for_pickup">
                                                            <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1 shadow-2xs transition-all active:scale-95" title="Mark Ready for Store Pick-up">
                                                                <span class="material-symbols-outlined text-[15px]">storefront</span>
                                                                <span>Ready for Pick-up</span>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php elseif ($r['status'] === 'new'): ?>
                                                    <form action="<?= base_url('tenant/printing/update-status') ?>" method="POST" class="inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="request_id" value="<?= (int) $r['id'] ?>">
                                                        <input type="hidden" name="status" value="in_production">
                                                        <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs flex items-center gap-1 shadow-2xs transition-all active:scale-95" title="Start Production Queue">
                                                            <span class="material-symbols-outlined text-[15px]">play_circle</span>
                                                            <span>Start</span>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <!-- Secondary Actions Dropdown (Download, Receipt, POS, Status, Archive) -->
                                                <div class="relative">
                                                    <button type="button" data-row="<?= (int) $r['id'] ?>" onclick="toggleDropdown(this)" class="more-toggle p-1.5 hover:bg-surface-container-high rounded-xl text-on-surface-variant hover:text-on-surface transition-colors flex items-center justify-center" title="More options" aria-haspopup="true" aria-expanded="false">
                                                        <span class="material-symbols-outlined text-[18px]">more_vert</span>
                                                    </button>
                                                    <div id="more-menu-<?= (int) $r['id'] ?>" class="hidden more-menu z-50 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl shadow-xl p-1.5 min-w-[200px] text-left" role="menu">
                                                        <div class="px-sm py-1 border-b border-outline-variant/15 mb-1">
                                                            <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">More Options</span>
                                                        </div>

                                                        <a href="<?= base_url('tenant/printing/download/' . (int) $r['id']) ?>" onclick="closeMenus()" class="w-full text-left px-sm py-2 rounded-xl text-on-surface text-label-sm font-semibold hover:bg-surface-container-high flex items-center gap-2.5 transition-colors">
                                                            <span class="material-symbols-outlined text-[18px] text-primary">download</span>
                                                            <span>Download File</span>
                                                        </a>

                                                        <a href="<?= base_url('tenant/printing/receipt/' . (int) $r['id']) ?>" target="_blank" onclick="closeMenus()" class="w-full text-left px-sm py-2 rounded-xl text-on-surface text-label-sm font-semibold hover:bg-surface-container-high flex items-center gap-2.5 transition-colors">
                                                            <span class="material-symbols-outlined text-[18px] text-primary">receipt_long</span>
                                                            <span>Print Receipt</span>
                                                        </a>

                                                        <?php if ($r['status'] === 'ready_for_pickup'): ?>
                                                            <a href="<?= base_url('tenant/pos') ?>" onclick="closeMenus()" class="w-full text-left px-sm py-2 rounded-xl text-emerald-700 text-label-sm font-bold hover:bg-emerald-50 flex items-center gap-2.5 transition-colors">
                                                                <span class="material-symbols-outlined text-[18px] text-emerald-600">point_of_sale</span>
                                                                <span>Complete in POS</span>
                                                            </a>
                                                        <?php endif; ?>

                                                        <div class="border-t border-outline-variant/15 my-1"></div>

                                                        <p class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant px-sm pt-1 pb-xs">Change Status</p>
                                                        <?php if (!empty($nextStates[$r['status']])): ?>
                                                            <form action="<?= base_url('tenant/printing/update-status') ?>" method="POST" class="space-y-xs px-sm pb-sm">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="request_id" value="<?= (int) $r['id'] ?>">
                                                                <select name="status" class="w-full p-2 bg-surface-container-low border border-outline-variant/40 rounded-lg text-xs font-semibold">
                                                                    <?php foreach ($nextStates[$r['status']] as $val => $label): ?>
                                                                        <option value="<?= $val ?>"><?= esc($label) ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <button type="submit" class="w-full py-1.5 bg-primary text-on-primary rounded-lg text-xs font-bold hover:bg-primary/90 transition-all">Apply</button>
                                                            </form>
                                                        <?php else: ?>
                                                            <p class="text-xs text-on-surface-variant px-sm pb-sm">Status is <?= esc($r['status']) ?>.</p>
                                                        <?php endif; ?>

                                                        <div class="border-t border-outline-variant/15 my-1"></div>
                                                        <form action="<?= base_url('tenant/printing/archive/' . (int) $r['id']) ?>" method="POST" onsubmit="return confirm('Archive this request?')">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="w-full text-left px-sm py-2 rounded-xl text-error text-label-sm font-semibold hover:bg-error-container/20 flex items-center gap-2.5 transition-colors">
                                                                <span class="material-symbols-outlined text-[18px]">archive</span>
                                                                <span>Archive Request</span>
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
                                    <td colspan="6" class="py-lg text-center text-on-surface-variant text-xs">No requests match your filters.</td>
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
                        <p class="text-xs text-on-surface-variant">Showing <?= number_format($rStart) ?> to <?= number_format($rEnd) ?> of <?= number_format($rTotal) ?> requests</p>
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
                                <a class="w-8 h-8 rounded flex items-center justify-center text-xs font-semibold <?= $rCur === $n ? 'bg-primary text-on-primary' : 'hover:bg-surface-container-high' ?>" href="<?= $pager->getPageURI($n, 'recent') ?>"><?= $n ?></a>
                            <?php $pv = $n; endforeach; ?>
                            <a class="p-sm rounded hover:bg-surface-container-high <?= $rCur >= $rPages ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('recent') ?>">
                                <span class="material-symbols-outlined">chevron_right</span>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Completed Requests -->
            <div class="glass-card rounded-2xl overflow-hidden shadow-sm border border-outline-variant/30">
                <div class="px-lg py-md flex flex-wrap justify-between items-center gap-md border-b border-outline-variant/30 bg-surface-container-lowest">
                    <div>
                        <h3 class="text-title-lg font-bold text-on-surface">Completed Requests</h3>
                        <p class="text-[11px] text-on-surface-variant">Finished printing jobs history</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-sm">
                        <form method="get" action="<?= base_url('tenant/printing') ?>" class="relative">
                            <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                            <input name="cq" value="<?= esc($filters['cq']) ?>" placeholder="Search completed..." class="pl-xl pr-md py-sm bg-surface-container-low border border-outline-variant rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-primary" type="text">
                        </form>
                        <form action="<?= base_url('tenant/printing/archive-all') ?>" method="POST" onsubmit="return confirm('Archive all completed requests? They can be restored from the Archive page.')">
                            <?= csrf_field() ?>
                            <button type="submit" class="bg-surface-container-high text-on-surface px-md py-sm rounded-xl flex items-center gap-xs text-xs font-semibold hover:bg-surface-variant transition-colors">
                                <span class="material-symbols-outlined text-[16px]">archive</span>
                                <span>Archive All</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="overflow-x-auto w-full scrollbar-thin">
                    <table class="w-full text-left border-collapse min-w-[780px]">
                        <thead class="bg-surface-container-low/70 border-b border-outline-variant/30 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Request ID</th>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">File</th>
                                <th class="px-4 py-3">Specifications</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20 bg-surface-container-lowest">
                            <?php if (!empty($completed)): ?>
                                <?php foreach ($completed as $r): ?>
                                    <?php
                                    $rawReqNum = trim((string) ($r['request_number'] ?? ''));
                                    $reqNum = $rawReqNum !== '' ? $rawReqNum : ('PR-' . $r['id']);
                                    $cleanReq = '#' . ltrim($reqNum, '#');
                                    $fullName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                                    $initials = $fullName !== '' ? mb_strtoupper(mb_substr($fullName, 0, 2)) : 'GU';
                                    $profileImage = trim((string) ($r['profile_image_url'] ?? ''));
                                    $fileName = !empty($r['file_name']) ? $r['file_name'] : 'Document.pdf';
                                    $docType = strtoupper(!empty($r['document_type']) ? $r['document_type'] : (pathinfo($fileName, PATHINFO_EXTENSION) ?: 'PDF'));
                                    ?>
                                    <tr class="hover:bg-surface-container-low/40 transition-colors">
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            <button type="button"
                                                    onclick="openRequestDetails(this)"
                                                    class="font-mono text-xs font-bold text-primary hover:text-primary-container bg-primary/5 hover:bg-primary/15 border border-primary/20 px-2.5 py-1 rounded-lg inline-flex items-center gap-1 transition-all active:scale-95"
                                                    title="View Full Details"
                                                    data-id="<?= (int) $r['id'] ?>"
                                                    data-request="<?= esc($reqNum) ?>"
                                                    data-customer="<?= esc($fullName !== '' ? $fullName : 'Customer') ?>"
                                                    data-file="<?= esc($fileName) ?>"
                                                    data-doctype="<?= esc($docType) ?>"
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
                                                <span class="material-symbols-outlined text-[13px]">tag</span>
                                                <span><?= esc($cleanReq) ?></span>
                                            </button>
                                            <div class="text-[10px] text-on-surface-variant/70 font-medium mt-1 flex items-center gap-1 whitespace-nowrap">
                                                <span class="material-symbols-outlined text-[12px]">schedule</span>
                                                <span><?= esc(date('M d, g:i A', strtotime($r['created_at']))) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <div class="flex items-center gap-2.5 min-w-[130px]">
                                                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs font-bold shrink-0 <?= $profileImage !== '' ? 'relative overflow-hidden' : '' ?> ring-2 ring-surface">
                                                    <span><?= esc($initials) ?></span>
                                                    <?php if ($profileImage !== ''): ?>
                                                        <img class="absolute inset-0 w-full h-full object-cover" src="<?= esc(base_url($profileImage)) ?>" alt="<?= esc($fullName) ?> avatar" loading="lazy" onerror="this.remove();">
                                                    <?php endif; ?>
                                                </div>
                                                <div class="min-w-0">
                                                    <span class="text-xs font-bold text-on-surface truncate block leading-tight max-w-[130px]" title="<?= esc($fullName !== '' ? $fullName : 'Customer') ?>"><?= esc($fullName !== '' ? $fullName : 'Guest Customer') ?></span>
                                                    <span class="text-[10px] text-on-surface-variant truncate block max-w-[130px] mt-0.5"><?= esc(!empty($r['phone']) ? $r['phone'] : (!empty($r['email']) ? $r['email'] : 'Direct Order')) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary shrink-0 border border-outline-variant/30">
                                                    <span class="material-symbols-outlined text-[18px]">description</span>
                                                </div>
                                                <div class="min-w-0 max-w-[150px] sm:max-w-[170px]">
                                                    <span class="text-xs font-semibold text-on-surface truncate block" title="<?= esc($fileName) ?>"><?= esc($fileName) ?></span>
                                                    <div class="flex items-center gap-1.5 mt-0.5">
                                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold tracking-wider uppercase <?= ($docType === 'DOCX') ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800' ?>">
                                                            <?= esc($docType) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-surface-container text-on-surface text-[11px] font-semibold border border-outline-variant/20 whitespace-nowrap">
                                                        <?= strtoupper(esc($r['color_mode'] === 'color' ? 'Color' : 'B&W')) ?> • <?= esc(ucfirst($r['paper_size'] ?? 'A4')) ?>
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-1.5 text-[11px] text-on-surface-variant whitespace-nowrap">
                                                    <span><?= (int) $r['page_count'] ?> pgs • <?= (int) $r['copies'] ?> <?= (int) $r['copies'] === 1 ? 'copy' : 'copies' ?></span>
                                                    <span class="text-outline-variant/60">•</span>
                                                    <span class="font-bold text-on-surface">₱<?= number_format((float) $r['total_price'], 2) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-1.5">

                                                <!-- Outward View Details Button for Completed Requests -->
                                                <button type="button"
                                                        onclick="openRequestDetails(this)"
                                                        class="px-2.5 py-1.5 rounded-xl bg-surface-container hover:bg-primary hover:text-on-primary text-on-surface font-bold text-xs flex items-center gap-1 transition-all shadow-2xs active:scale-95 border border-outline-variant/30"
                                                        title="View Full Details"
                                                        data-id="<?= (int) $r['id'] ?>"
                                                        data-request="<?= esc($reqNum) ?>"
                                                        data-customer="<?= esc($fullName !== '' ? $fullName : 'Customer') ?>"
                                                        data-file="<?= esc($fileName) ?>"
                                                        data-doctype="<?= esc($docType) ?>"
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
                                                    <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                    <span>Details</span>
                                                </button>

                                                <a href="<?= base_url('tenant/printing/download/' . (int) $r['id']) ?>" class="p-1.5 hover:bg-surface-container-high rounded-xl text-on-surface-variant hover:text-primary transition-colors flex items-center justify-center" title="Download File">
                                                    <span class="material-symbols-outlined text-[18px]">download</span>
                                                </a>

                                                <a href="<?= base_url('tenant/printing/receipt/' . (int) $r['id']) ?>" target="_blank" class="p-1.5 hover:bg-surface-container-high rounded-xl text-on-surface-variant hover:text-primary transition-colors flex items-center justify-center" title="Print Receipt">
                                                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                                                </a>

                                                <form action="<?= base_url('tenant/printing/archive/' . (int) $r['id']) ?>" method="POST" onsubmit="return confirm('Archive this request?')" class="inline">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="p-1.5 hover:bg-error-container/20 rounded-xl text-on-surface-variant hover:text-error transition-colors flex items-center justify-center" title="Archive Request">
                                                        <span class="material-symbols-outlined text-[18px]">archive</span>
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-lg text-center text-on-surface-variant text-xs">No completed requests found.</td>
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
                        <p class="text-xs text-on-surface-variant">Showing <?= number_format($cStart) ?> to <?= number_format($cEnd) ?> of <?= number_format($cTotal) ?> requests</p>
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
                                <a class="w-8 h-8 rounded flex items-center justify-center text-xs font-semibold <?= $cCur === $n ? 'bg-primary text-on-primary' : 'hover:bg-surface-container-high' ?>" href="<?= $completed_pager->getPageURI($n, 'completed') ?>"><?= $n ?></a>
                            <?php $pv = $n; endforeach; ?>
                            <a class="p-sm rounded hover:bg-surface-container-high <?= $cCur >= $cPages ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $completed_pager->getNextPageURI('completed') ?>">
                                <span class="material-symbols-outlined">chevron_right</span>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Right Column: Dedicated "In-Production" Side Container (4 cols on XL) -->
        <div class="xl:col-span-4 space-y-gutter xl:sticky xl:top-4" id="in-production-container">
            <div class="glass-card rounded-2xl p-5 shadow-sm space-y-4 border border-outline-variant/30 bg-surface-container-lowest">
                <div class="flex items-center justify-between border-b border-outline-variant/20 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-xl bg-tertiary/10 text-tertiary flex items-center justify-center">
                            <span class="material-symbols-outlined text-[20px]">manufacturing</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-on-surface">In Production</h3>
                            <p class="text-[11px] text-on-surface-variant">Active production queue</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-tertiary/15 text-tertiary flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-tertiary <?= !empty($queue) ? 'animate-pulse' : '' ?>"></span>
                        <?= count($queue) ?> Active
                    </span>
                </div>

                <?php if (!empty($queue)): ?>
                    <div class="space-y-3 max-h-[calc(100vh-200px)] overflow-y-auto pr-1">
                        <?php foreach ($queue as $q): ?>
                            <?php
                            $qFullName = trim(($q['first_name'] ?? '') . ' ' . ($q['last_name'] ?? ''));
                            $qInitials = $qFullName !== '' ? mb_strtoupper(mb_substr($qFullName, 0, 2)) : 'CU';
                            $qIsDelivery = ($q['fulfillment_method'] ?? 'pickup') === 'delivery';
                            ?>
                            <div class="bg-surface-container-low border border-outline-variant/30 rounded-2xl p-3.5 shadow-2xs space-y-2.5 transition-all hover:border-tertiary/40 group">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-bold text-primary">#<?= esc($q['request_number']) ?></span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-tertiary/10 text-tertiary flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-tertiary animate-pulse"></span>
                                            In Production
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-outline font-medium"><?= esc(date('M d, h:i A', strtotime($q['created_at']))) ?></span>
                                </div>

                                <div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-primary-container text-primary flex items-center justify-center text-[10px] font-bold shrink-0">
                                            <?= esc($qInitials) ?>
                                        </div>
                                        <span class="text-xs font-bold text-on-surface truncate"><?= esc($qFullName !== '' ? $qFullName : 'Customer') ?></span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-on-surface-variant text-xs mt-1.5">
                                        <span class="material-symbols-outlined text-[15px] text-outline shrink-0">description</span>
                                        <span class="truncate flex-1 font-medium text-[11px]" title="<?= esc($q['file_name']) ?>"><?= esc($q['file_name']) ?></span>
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold <?= (strtolower($q['document_type'] ?? '') === 'docx') ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800' ?> uppercase shrink-0">
                                            <?= esc(strtoupper($q['document_type'] ?? 'PDF')) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-1 text-[10px]">
                                    <span class="px-2 py-0.5 rounded-md bg-surface-container font-semibold text-on-surface-variant">
                                        <?= strtoupper(esc($q['color_mode'] === 'color' ? 'Color' : 'B&W')) ?> • <?= esc(ucfirst($q['paper_size'] ?? 'Letter')) ?>
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md bg-surface-container text-outline">
                                        <?= (int) $q['page_count'] ?>p • <?= (int) $q['copies'] ?> <?= (int) $q['copies'] === 1 ? 'copy' : 'copies' ?>
                                    </span>
                                    <?php if (!empty($q['binding_option']) && $q['binding_option'] !== 'none'): ?>
                                        <span class="px-2 py-0.5 rounded-md bg-surface-container font-medium text-primary">
                                            <?= esc(ucfirst($q['binding_option'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-center justify-between pt-1 border-t border-outline-variant/20">
                                    <span class="text-[11px] font-medium text-outline">Fulfillment:</span>
                                    <?php if ($qIsDelivery): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800">
                                            <span class="material-symbols-outlined text-[13px]">local_shipping</span> Doorstep Delivery
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                            <span class="material-symbols-outlined text-[13px]">storefront</span> Store Pick-up
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- PRIMARY FULFILLMENT ACTION BUTTON -->
                                <div class="pt-1">
                                    <?php if ($qIsDelivery): ?>
                                        <form action="<?= base_url('tenant/printing/update-status') ?>" method="POST" class="w-full">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="request_id" value="<?= (int) $q['id'] ?>">
                                            <input type="hidden" name="status" value="ready_for_delivery">
                                            <button type="submit" class="w-full py-2 px-3 bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 shadow-sm transition-all" title="Advance status to Shipped for Delivery">
                                                <span class="material-symbols-outlined text-[17px]">local_shipping</span>
                                                <span>Mark as Shipped</span>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?= base_url('tenant/printing/update-status') ?>" method="POST" class="w-full">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="request_id" value="<?= (int) $q['id'] ?>">
                                            <input type="hidden" name="status" value="ready_for_pickup">
                                            <button type="submit" class="w-full py-2 px-3 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 shadow-sm transition-all" title="Advance status to Ready for Pick-up">
                                                <span class="material-symbols-outlined text-[17px]">storefront</span>
                                                <span>Ready for Pick-up</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>

                                <!-- Secondary Actions: Details, Download, Receipt -->
                                <div class="grid grid-cols-3 gap-1.5 pt-1">
                                    <button type="button"
                                            onclick="openRequestDetails(this)"
                                            class="py-1.5 px-2 rounded-lg bg-surface-container hover:bg-surface-container-high border border-outline-variant/30 text-on-surface text-[11px] font-semibold flex items-center justify-center gap-1 transition-colors"
                                            data-id="<?= (int) $q['id'] ?>"
                                            data-request="<?= esc($q['request_number']) ?>"
                                            data-customer="<?= esc($qFullName !== '' ? $qFullName : 'Customer') ?>"
                                            data-file="<?= esc($q['file_name']) ?>"
                                            data-doctype="<?= esc(strtoupper($q['document_type'] ?? 'PDF')) ?>"
                                            data-changetype="<?= esc(($q['doc_change_type'] ?? '') === 'has_changes' ? 'Requested Changes' : 'Print As-Is') ?>"
                                            data-instructions="<?= esc($q['special_instructions'] ?? '') ?>"
                                            data-attachments="<?= esc(json_encode($q['attachments'] ?? []), 'attr') ?>"
                                            data-specs="<?= esc(pr_spec_line($q)) ?>"
                                            data-color="<?= esc(strtoupper($q['color_mode'] === 'color' ? 'Color' : 'B&W')) ?> • <?= esc(ucfirst($q['paper_size'] ?? 'A4')) ?>"
                                            data-fulfillment="<?= esc(ucfirst(str_replace('_', ' ', $q['fulfillment_method'] ?? 'delivery'))) ?>"
                                            data-printer="<?= esc($q['printer_assigned'] ?? 'Not assigned') ?>"
                                            data-progress="<?= (int) $q['progress_percent'] ?>"
                                            data-amount="<?= esc(number_format((float) $q['total_price'], 2)) ?>"
                                            data-down="<?= esc(number_format((float) $q['down_payment'], 2)) ?>"
                                            data-date="<?= esc(date('M d, Y h:i A', strtotime($q['created_at']))) ?>">
                                        <span class="material-symbols-outlined text-[14px] text-primary">visibility</span>
                                        <span>Details</span>
                                    </button>

                                    <a href="<?= base_url('tenant/printing/download/' . (int) $q['id']) ?>" class="py-1.5 px-2 rounded-lg bg-surface-container hover:bg-surface-container-high border border-outline-variant/30 text-on-surface text-[11px] font-semibold flex items-center justify-center gap-1 transition-colors">
                                        <span class="material-symbols-outlined text-[14px] text-primary">download</span>
                                        <span>File</span>
                                    </a>

                                    <a href="<?= base_url('tenant/printing/receipt/' . (int) $q['id']) ?>" target="_blank" class="py-1.5 px-2 rounded-lg bg-surface-container hover:bg-surface-container-high border border-outline-variant/30 text-on-surface text-[11px] font-semibold flex items-center justify-center gap-1 transition-colors">
                                        <span class="material-symbols-outlined text-[14px] text-primary">receipt_long</span>
                                        <span>Receipt</span>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="py-10 px-4 text-center bg-surface-container-low/50 rounded-2xl border border-dashed border-outline-variant/40 space-y-2">
                        <div class="w-12 h-12 rounded-2xl bg-tertiary/10 text-tertiary mx-auto flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl">print</span>
                        </div>
                        <p class="text-xs font-bold text-on-surface">No Print Jobs in Production</p>
                        <p class="text-[11px] text-outline max-w-xs mx-auto">Click "Start" on any new request from the list to begin production.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<!-- Request Details Modal -->
<div id="requestModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest rounded-3xl p-xl max-w-lg w-full space-y-md border border-outline-variant/30 shadow-2xl">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[22px]">receipt_long</span>
                <h3 class="text-title-lg font-bold">Request <span id="rmRequest" class="text-primary font-mono"></span></h3>
            </div>
            <button onclick="document.getElementById('requestModal').classList.add('hidden')" class="p-1 rounded-full hover:bg-surface-container-high text-on-surface-variant transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="space-y-sm max-h-[75vh] overflow-y-auto pr-1">

            <!-- Word / DOCS Special Notice & Instructions Card -->
            <div id="rmDocsCalloutBox" class="hidden p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 space-y-2.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-amber-900 font-bold text-xs">
                        <span class="material-symbols-outlined text-[18px] text-amber-700">description</span>
                        <span>Word Document Request</span>
                    </div>
                    <span id="rmDocsChangeBadge" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-200 text-amber-900 border border-amber-300">
                        Requested Changes
                    </span>
                </div>
                <div>
                    <span class="text-[11px] font-bold text-amber-950 block mb-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[15px] text-amber-800">edit_note</span>
                        <span>Customer Special Instructions:</span>
                    </span>
                    <p id="rmInstructions" class="text-xs text-on-surface bg-surface-container-lowest p-2.5 rounded-xl border border-amber-200/80 whitespace-pre-wrap font-medium leading-relaxed shadow-2xs"></p>
                </div>
            </div>

            <!-- Generic Instructions Box (when not DOCX but has instructions) -->
            <div id="rmGenericInstructionsBox" class="hidden p-3 rounded-2xl bg-surface-container-low border border-outline-variant/30 space-y-1">
                <span class="text-[11px] font-bold text-on-surface-variant flex items-center gap-1 uppercase tracking-wider">
                    <span class="material-symbols-outlined text-[15px] text-primary">edit_note</span>
                    <span>Special Instructions</span>
                </span>
                <p id="rmGenericInstructions" class="text-xs text-on-surface bg-surface-container-lowest p-2 rounded-xl border border-outline-variant/20 whitespace-pre-wrap"></p>
            </div>

            <!-- Customer Reference Images / Photos Gallery -->
            <div id="rmAttachmentsBox" class="hidden p-3 rounded-2xl bg-surface-container-low border border-outline-variant/30 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-on-surface uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[17px] text-primary">photo_library</span>
                        <span>Customer Reference Photos</span>
                        <span id="rmAttachmentsCount" class="text-[10px] px-2 py-0.2 rounded-full bg-primary text-on-primary font-bold"></span>
                    </span>
                    <span class="text-[10px] text-outline">Click image to preview</span>
                </div>
                <div id="rmAttachmentsList" class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-1"></div>
            </div>

            <!-- Core Specs Table -->
            <div class="bg-surface-container-low p-3 rounded-2xl border border-outline-variant/20 space-y-2 text-xs">
                <div class="flex justify-between items-center"><span class="text-label-sm text-on-surface-variant font-medium">Customer</span><span id="rmCustomer" class="font-bold text-on-surface"></span></div>
                <div class="flex justify-between items-center"><span class="text-label-sm text-on-surface-variant font-medium">Main File</span><span id="rmFile" class="font-semibold text-primary truncate max-w-[220px]" title=""></span></div>
                <div class="flex justify-between items-center"><span class="text-label-sm text-on-surface-variant font-medium">Document Type</span><span id="rmDocType" class="font-bold uppercase"></span></div>
                <div class="flex justify-between items-center"><span class="text-label-sm text-on-surface-variant font-medium">Specifications</span><span id="rmSpecs" class="font-medium text-on-surface text-right"></span></div>
                <div class="flex justify-between items-center"><span class="text-label-sm text-on-surface-variant font-medium">Color / Paper Size</span><span id="rmColor" class="font-semibold text-on-surface"></span></div>
                <div class="flex justify-between items-center"><span class="text-label-sm text-on-surface-variant font-medium">Fulfillment</span><span id="rmFulfillment" class="font-bold text-on-surface"></span></div>
                <div class="flex justify-between items-center"><span class="text-label-sm text-on-surface-variant font-medium">Printer Assigned</span><span id="rmPrinter" class="text-on-surface-variant"></span></div>
                <div class="flex justify-between items-center"><span class="text-label-sm text-on-surface-variant font-medium">Progress</span><span id="rmProgress" class="font-bold text-on-surface"></span></div>
                <div class="flex justify-between items-center"><span class="text-label-sm text-on-surface-variant font-medium">Total Price</span><span id="rmAmount" class="font-bold text-primary text-sm">₱0.00</span></div>
                <div class="flex justify-between items-center"><span class="text-label-sm text-on-surface-variant font-medium">Down Payment</span><span id="rmDown" class="font-semibold text-emerald-700">₱0.00</span></div>
                <div class="flex justify-between items-center"><span class="text-label-sm text-on-surface-variant font-medium">Submitted On</span><span id="rmDate" class="text-on-surface-variant"></span></div>
            </div>

            <!-- Print Receipt & Document Actions -->
            <div class="pt-2 border-t border-outline-variant/20 space-y-2">
                <div class="grid grid-cols-2 gap-2">
                    <a id="rmDownloadFileBtn" href="#" class="py-2.5 px-3 bg-surface-container-high text-on-surface border border-outline-variant/40 rounded-xl font-bold hover:bg-surface-variant transition-all flex items-center justify-center gap-1.5 text-xs shadow-xs">
                        <span class="material-symbols-outlined text-[16px] text-primary">download</span>
                        <span>Download Main File</span>
                    </a>
                    <button type="button" id="rmPrintReceiptBtn" class="py-2.5 px-3 bg-primary text-on-primary rounded-xl font-bold hover:bg-primary/90 transition-all flex items-center justify-center gap-1.5 text-xs shadow-xs">
                        <span class="material-symbols-outlined text-[16px]">receipt_long</span>
                        <span>Print Receipt</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attachment Lightbox Modal -->
<div id="attachmentLightboxModal" class="hidden fixed inset-0 bg-black/85 backdrop-blur-sm z-[70] flex items-center justify-center p-md" onclick="if (event.target === this) closeAttachmentLightbox()">
    <div class="relative max-w-3xl w-full bg-surface-container-lowest rounded-3xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
        <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant/30 flex justify-between items-center">
            <span id="lightboxFileName" class="text-xs font-bold text-on-surface truncate max-w-md">Reference Image Preview</span>
            <div class="flex items-center gap-2">
                <a id="lightboxDownloadBtn" href="#" target="_blank" download class="py-1 px-2.5 bg-primary/10 hover:bg-primary/20 text-primary rounded-lg transition-colors flex items-center gap-1 text-xs font-bold" title="Download Image">
                    <span class="material-symbols-outlined text-[16px]">download</span>
                    <span>Download</span>
                </a>
                <button type="button" onclick="closeAttachmentLightbox()" class="p-1 rounded-full hover:bg-surface-container-high text-on-surface-variant hover:text-on-surface transition-colors">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>
        </div>
        <div class="p-3 flex-1 flex items-center justify-center overflow-auto bg-black/30 min-h-[300px]">
            <img id="lightboxImage" src="" alt="Reference Preview" class="max-w-full max-h-[75vh] object-contain rounded-xl shadow-lg">
        </div>
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

            <!-- Page Pricing (Global Rates) -->
            <div class="bg-surface-container-low p-md rounded-2xl border border-outline-variant/30 space-y-md">
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px] text-primary">price_change</span>
                        Page Pricing (Global Rates)
                    </h4>
                    <p class="text-[11px] text-outline mt-0.5">Applied per page across all supported paper sizes</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                    <div>
                        <label class="text-[11px] font-bold text-on-surface-variant block mb-1">Color Rate per Page (₱)</label>
                        <div class="relative">
                            <input type="number" step="0.25" min="0" name="price_color_per_page" id="set_price_color_per_page" value="<?= esc($printingSettings['price_color_per_page'] ?? 5.00) ?>" required class="w-full py-2 px-3 bg-surface-container border border-outline-variant/40 rounded-xl text-xs font-semibold text-on-surface focus:ring-1 focus:ring-primary focus:border-primary">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-outline font-bold">₱</span>
                        </div>
                        <p class="text-[10px] text-on-surface-variant mt-0.5">Standard colored print per page</p>
                    </div>

                    <div>
                        <label class="text-[11px] font-bold text-on-surface-variant block mb-1">Black &amp; White Rate per Page (₱)</label>
                        <div class="relative">
                            <input type="number" step="0.25" min="0" name="price_bw_per_page" id="set_price_bw_per_page" value="<?= esc($printingSettings['price_bw_per_page'] ?? 2.00) ?>" required class="w-full py-2 px-3 bg-surface-container border border-outline-variant/40 rounded-xl text-xs font-semibold text-on-surface focus:ring-1 focus:ring-primary focus:border-primary">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-outline font-bold">₱</span>
                        </div>
                        <p class="text-[10px] text-on-surface-variant mt-0.5">Monochrome print per page</p>
                    </div>
                </div>
            </div>

            <!-- Supported Paper Sizes -->
            <div class="space-y-sm">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px] text-primary">format_size</span>
                        Supported Paper Sizes
                    </h4>
                    <span class="text-[11px] text-outline">Disabled sizes are hidden from customers</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-60 overflow-y-auto p-1 rounded-2xl border border-outline-variant/30 bg-surface-container-lowest">
                    <?php if (!empty($paperSizes)): ?>
                        <?php foreach ($paperSizes as $sKey => $sVal): ?>
                            <?php $isEnabled = !empty($sVal['is_enabled']); ?>
                            <label id="card_size_<?= esc($sKey) ?>" class="size-card flex items-center justify-between p-3 rounded-xl border border-outline-variant/30 cursor-pointer transition-all hover:bg-surface-container-low <?= $isEnabled ? 'bg-surface-container-low/60 border-primary/30' : 'opacity-60 bg-surface-container-lowest' ?>">
                                <div class="flex items-center gap-2.5">
                                    <input type="hidden" name="paper_sizes[<?= esc($sKey) ?>][is_enabled]" value="0">
                                    <input type="checkbox" name="paper_sizes[<?= esc($sKey) ?>][is_enabled]" value="1" <?= $isEnabled ? 'checked' : '' ?> onchange="togglePaperSizeCard('<?= esc($sKey) ?>', this.checked)" class="rounded border-outline-variant text-primary focus:ring-primary h-4 w-4 cursor-pointer">
                                    <div>
                                        <div class="text-xs font-bold text-on-surface"><?= esc($sVal['label'] ?? strtoupper($sKey)) ?></div>
                                        <span class="text-[10px] text-outline font-mono uppercase"><?= esc($sKey) ?></span>
                                    </div>
                                </div>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full <?= $isEnabled ? 'bg-primary/10 text-primary' : 'bg-surface-container text-outline' ?>" id="badge_size_<?= esc($sKey) ?>">
                                    <?= $isEnabled ? 'Available' : 'Disabled' ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
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

<script>
    function openPrintingSettingsModal() {
        document.getElementById('printingSettingsModal').classList.remove('hidden');
    }

    function closePrintingSettingsModal() {
        document.getElementById('printingSettingsModal').classList.add('hidden');
    }

    function togglePaperSizeCard(key, isChecked) {
        const card = document.getElementById('card_size_' + key);
        const badge = document.getElementById('badge_size_' + key);
        if (!card) return;
        if (isChecked) {
            card.classList.remove('opacity-60', 'bg-surface-container-lowest');
            card.classList.add('bg-surface-container-low/60', 'border-primary/30');
            if (badge) {
                badge.className = 'text-[10px] font-semibold px-2 py-0.5 rounded-full bg-primary/10 text-primary';
                badge.textContent = 'Available';
            }
        } else {
            card.classList.remove('bg-surface-container-low/60', 'border-primary/30');
            card.classList.add('opacity-60', 'bg-surface-container-lowest');
            if (badge) {
                badge.className = 'text-[10px] font-semibold px-2 py-0.5 rounded-full bg-surface-container text-outline';
                badge.textContent = 'Disabled';
            }
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

    function setDocTypeFilter(type) {
        const input = document.getElementById('filterDocTypeInput');
        if (input) {
            input.value = type;
            const form = document.getElementById('recentFiltersForm');
            if (form) form.submit();
        }
    }

    let searchDebounceTimer = null;
    function handleSearchInput(input) {
        clearTimeout(searchDebounceTimer);
        const clearBtn = document.getElementById('searchClearBtn');
        if (clearBtn) {
            clearBtn.classList.toggle('hidden', !input.value.trim());
        }
        searchDebounceTimer = setTimeout(() => {
            sessionStorage.setItem('printingSearchActive', 'true');
            document.getElementById('recentFiltersForm').submit();
        }, 400);
    }

    function clearSearchInput() {
        const input = document.getElementById('recentSearchInput');
        if (input) {
            input.value = '';
            sessionStorage.removeItem('printingSearchActive');
            document.getElementById('recentFiltersForm').submit();
        }
    }

    window.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('recentSearchInput');
        if (searchInput && sessionStorage.getItem('printingSearchActive') === 'true') {
            sessionStorage.removeItem('printingSearchActive');
            searchInput.focus();
            const len = searchInput.value.length;
            searchInput.setSelectionRange(len, len);
        }
    });

    function openAttachmentLightbox(url, name) {
        document.getElementById('lightboxImage').src = url;
        document.getElementById('lightboxFileName').textContent = name || 'Reference Photo Preview';
        document.getElementById('lightboxDownloadBtn').href = url;
        document.getElementById('attachmentLightboxModal').classList.remove('hidden');
    }

    function closeAttachmentLightbox() {
        document.getElementById('attachmentLightboxModal').classList.add('hidden');
        document.getElementById('lightboxImage').src = '';
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const lb = document.getElementById('attachmentLightboxModal');
            if (lb && !lb.classList.contains('hidden')) {
                closeAttachmentLightbox();
                return;
            }
            const rm = document.getElementById('requestModal');
            if (rm && !rm.classList.contains('hidden')) {
                rm.classList.add('hidden');
            }
        }
    });

    function openRequestDetails(btn) {
        const reqNum = btn.dataset.request || '';
        document.getElementById('rmRequest').textContent = '#' + reqNum;
        document.getElementById('rmCustomer').textContent = btn.dataset.customer || '';
        
        const fileElem = document.getElementById('rmFile');
        const fileName = btn.dataset.file || 'Document.pdf';
        fileElem.textContent = fileName;
        fileElem.title = fileName;

        const docType = (btn.dataset.doctype || 'PDF').toUpperCase();
        document.getElementById('rmDocType').textContent = docType;

        const changeType = btn.dataset.changetype || 'Print As-Is';
        const inst = (btn.dataset.instructions || '').trim();

        // High-visibility Word / DOCS Callout Box
        const docsBox = document.getElementById('rmDocsCalloutBox');
        const docsBadge = document.getElementById('rmDocsChangeBadge');
        const rmInst = document.getElementById('rmInstructions');
        const genericBox = document.getElementById('rmGenericInstructionsBox');
        const genericInst = document.getElementById('rmGenericInstructions');

        if (docType === 'DOCX') {
            docsBox.classList.remove('hidden');
            docsBadge.textContent = changeType;
            if (changeType.toLowerCase().includes('change')) {
                docsBadge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-200 text-amber-900 border border-amber-300';
            } else {
                docsBadge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300';
            }
            rmInst.textContent = inst !== '' ? inst : 'No special instructions provided (Customer selected "Print As-Is").';
            genericBox.classList.add('hidden');
        } else {
            docsBox.classList.add('hidden');
            if (inst !== '') {
                genericBox.classList.remove('hidden');
                genericInst.textContent = inst;
            } else {
                genericBox.classList.add('hidden');
            }
        }

        document.getElementById('rmSpecs').textContent = btn.dataset.specs || '';
        document.getElementById('rmColor').textContent = btn.dataset.color || '';
        document.getElementById('rmFulfillment').textContent = btn.dataset.fulfillment || '';
        document.getElementById('rmPrinter').textContent = btn.dataset.printer || 'Not assigned';
        document.getElementById('rmProgress').textContent = (btn.dataset.progress || 0) + '% complete';
        document.getElementById('rmAmount').textContent = '₱' + (btn.dataset.amount || '0.00');
        document.getElementById('rmDown').textContent = '₱' + (btn.dataset.down || '0.00');
        document.getElementById('rmDate').textContent = btn.dataset.date || '';

        // Reference Attachments / Photos Gallery
        const attBox = document.getElementById('rmAttachmentsBox');
        const attList = document.getElementById('rmAttachmentsList');
        const attCount = document.getElementById('rmAttachmentsCount');
        attList.innerHTML = '';

        let attachments = [];
        try {
            attachments = JSON.parse(btn.dataset.attachments || '[]');
        } catch (e) {
            attachments = [];
        }

        if (attachments && attachments.length > 0) {
            attBox.classList.remove('hidden');
            attCount.textContent = attachments.length;
            attachments.forEach((att, idx) => {
                const imgPath = att.image_url || att.file_path || '';
                if (!imgPath) return;
                const fullUrl = imgPath.startsWith('http') ? imgPath : ('<?= base_url() ?>/' + imgPath.replace(/^\/+/, ''));
                const safeName = att.file_name || imgPath.split('/').pop() || ('Reference Photo ' + (idx + 1));

                const card = document.createElement('div');
                card.className = 'group relative rounded-xl overflow-hidden border border-outline-variant/30 bg-surface-container-lowest shadow-2xs hover:shadow-md transition-all flex flex-col';
                card.innerHTML = `
                    <div class="aspect-video w-full overflow-hidden bg-surface-container relative cursor-pointer flex items-center justify-center">
                        <img src="${fullUrl}" alt="${safeName}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" loading="lazy" onerror="this.onerror=null; this.src=''; this.parentElement.innerHTML='<div class=\\'flex flex-col items-center justify-center text-outline p-2\\'><span class=\\'material-symbols-outlined text-2xl\\'>broken_image</span><span class=\\'text-[10px]\\'>Preview unavailable</span></div>';">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1.5 pointer-events-none">
                            <span class="p-1.5 rounded-full bg-white/90 text-primary shadow">
                                <span class="material-symbols-outlined text-[16px] block">zoom_in</span>
                            </span>
                        </div>
                    </div>
                    <div class="p-1.5 flex items-center justify-between gap-1 text-[11px] bg-surface-container-low/60 border-t border-outline-variant/20">
                        <span class="truncate font-semibold text-on-surface flex-1" title="${safeName}">${safeName}</span>
                        <a href="${fullUrl}" download="${safeName}" target="_blank" class="p-1 hover:text-primary transition-colors shrink-0" title="Download Reference Photo">
                            <span class="material-symbols-outlined text-[15px]">download</span>
                        </a>
                    </div>
                `;

                // Click image area to open lightbox
                const imgArea = card.querySelector('.aspect-video');
                if (imgArea) {
                    imgArea.onclick = () => openAttachmentLightbox(fullUrl, safeName);
                }

                attList.appendChild(card);
            });
        } else {
            attBox.classList.add('hidden');
            attCount.textContent = '';
        }

        const reqId = btn.dataset.id || '';
        const rmDlBtn = document.getElementById('rmDownloadFileBtn');
        if (rmDlBtn) {
            rmDlBtn.href = reqId ? ('<?= base_url('tenant/printing/download') ?>/' + reqId) : '#';
        }

        const rmPrintBtn = document.getElementById('rmPrintReceiptBtn');
        if (rmPrintBtn) {
            rmPrintBtn.onclick = function() {
                window.open('<?= base_url('tenant/printing/receipt') ?>/' + encodeURIComponent(btn.dataset.request || ''), '_blank');
            };
        }

        document.getElementById('requestModal').classList.remove('hidden');
    }
</script>

<?= $this->endSection() ?>
