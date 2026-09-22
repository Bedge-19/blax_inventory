<?= $this->extend('layouts/tenant') ?>
<?= $this->section('content') ?>

<?php
    $pendingTotal = 0;
    $pendingCount = 0;
    $completedTotal = 0;
    $completedCount = 0;
    $activeWithdrawal = null;
    if (!empty($withdrawals)) {
        foreach ($withdrawals as $w) {
            $st = strtolower($w['status'] ?? 'pending');
            $amt = (float) ($w['amount'] ?? 0);
            if (in_array($st, ['pending', 'processing', 'under_review'], true)) {
                $pendingTotal += $amt;
                $pendingCount++;
                if (!$activeWithdrawal) {
                    $activeWithdrawal = $w;
                }
            } elseif (in_array($st, ['completed', 'approved', 'disbursed', 'paid', 'success'], true)) {
                $completedTotal += $amt;
                $completedCount++;
            }
        }
    }
?>

<div class="flex-1 space-y-lg">

    <!-- Flash Messages -->
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

    <!-- 4-Card Payout Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-gutter">
        <div class="glass-card rounded-2xl p-lg flex flex-col justify-between soft-shadow">
            <div class="flex items-center justify-between">
                <span class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider">Available Balance</span>
                <span class="w-9 h-9 rounded-xl bg-primary-container/20 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
                </span>
            </div>
            <div class="mt-sm">
                <h3 class="text-headline-md font-bold text-primary">₱<?= number_format($available_balance ?? 0.00, 2) ?></h3>
                <p class="text-[11px] text-on-surface-variant mt-1">Ready for withdrawal</p>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-lg flex flex-col justify-between soft-shadow">
            <div class="flex items-center justify-between">
                <span class="text-label-sm font-bold text-amber-800 uppercase tracking-wider">Funds in Escrow</span>
                <span class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">lock</span>
                </span>
            </div>
            <div class="mt-sm">
                <h3 class="text-headline-md font-bold text-amber-700">₱<?= number_format($escrow_holding ?? 0.00, 2) ?></h3>
                <p class="text-[11px] text-on-surface-variant mt-1">Held until delivery confirmed</p>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-lg flex flex-col justify-between soft-shadow">
            <div class="flex items-center justify-between">
                <span class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider">Pending Payouts</span>
                <span class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">pending</span>
                </span>
            </div>
            <div class="mt-sm">
                <h3 class="text-headline-md font-bold text-on-surface">₱<?= number_format($pendingTotal, 2) ?></h3>
                <p class="text-[11px] text-on-surface-variant mt-1"><?= $pendingCount ?> request(s) being processed</p>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-lg flex flex-col justify-between soft-shadow">
            <div class="flex items-center justify-between">
                <span class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider">Total Disbursed</span>
                <span class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">task_alt</span>
                </span>
            </div>
            <div class="mt-sm">
                <h3 class="text-headline-md font-bold text-emerald-700">₱<?= number_format($completedTotal, 2) ?></h3>
                <p class="text-[11px] text-on-surface-variant mt-1"><?= $completedCount ?> completed payout(s)</p>
            </div>
        </div>
    </div>

    <!-- GCash Incomplete Warning Banner -->
    <?php if (empty($is_gcash_complete)): ?>
        <div class="glass-card rounded-2xl p-lg border border-amber-500/30 bg-amber-500/10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-md">
            <div class="flex items-start gap-3">
                <span class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-800 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[24px]">warning</span>
                </span>
                <div>
                    <h4 class="font-bold text-on-surface text-base">GCash Information Incomplete</h4>
                    <p class="text-xs text-on-surface-variant mt-0.5">Your GCash account name and 11-digit mobile number must be verified and saved in settings before requesting withdrawals.</p>
                </div>
            </div>
            <a href="<?= base_url('tenant/settings?tab=payment') ?>" class="px-xl py-md bg-amber-600 text-white rounded-xl font-bold text-xs hover:bg-amber-700 transition-colors shadow-md flex items-center gap-xs whitespace-nowrap">
                <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                Set Up GCash Information
            </a>
        </div>
    <?php endif; ?>

    <!-- Request Withdrawal CTA Banner -->
    <div class="glass-card rounded-2xl p-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-md border border-outline-variant/30">
        <div>
            <h3 class="text-title-lg font-bold text-on-surface">Merchant Payouts</h3>
            <p class="text-xs text-on-surface-variant mt-0.5">Withdraw earnings to your registered GCash account (minimum ₱20.00). Standard <?= number_format($deduction_percent ?? 3.00, 2) ?>% administrative fee applies to cover gateway disbursement.</p>
        </div>
        <?php if (!empty($is_gcash_complete)): ?>
            <button onclick="document.getElementById('withdrawModal').classList.remove('hidden')" class="px-xl py-md bg-primary text-on-primary rounded-xl font-bold text-xs hover:bg-primary/90 transition-colors shadow-md flex items-center gap-xs whitespace-nowrap active:scale-95">
                <span class="material-symbols-outlined text-[18px]">payments</span>
                Request Withdrawal
            </button>
        <?php else: ?>
            <a href="<?= base_url('tenant/settings?tab=payment') ?>" class="px-xl py-md bg-surface-container-high text-on-surface-variant rounded-xl font-bold text-xs hover:bg-surface-container-highest transition-colors shadow-sm flex items-center gap-xs whitespace-nowrap" title="Complete your GCash details in Settings first">
                <span class="material-symbols-outlined text-[18px]">lock</span>
                Set Up GCash to Withdraw
            </a>
        <?php endif; ?>
    </div>

    <?php if ($activeWithdrawal): ?>
        <?php
            $activeStatus = strtolower($activeWithdrawal['status'] ?? 'pending');
        ?>
        <!-- Prominent Active Payout Status Tracker -->
        <div class="rounded-2xl p-lg bg-amber-500/10 border border-amber-500/30 space-y-md shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-sm border-b border-amber-500/20 pb-sm">
                <div class="flex items-center gap-sm">
                    <span class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-800 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px] animate-pulse">sync</span>
                    </span>
                    <div>
                        <h4 class="font-bold text-on-surface text-body-md">Active Payout Request #<?= esc($activeWithdrawal['reference_number'] ?? ('WD-' . $activeWithdrawal['id'])) ?></h4>
                        <p class="text-[11px] text-on-surface-variant">Submitted on <?= date('M d, Y h:i A', strtotime($activeWithdrawal['created_at'])) ?> &bull; Recipient: <?= esc($activeWithdrawal['recipient_account_name'] ?? $shop['gcash_name'] ?? 'GCash') ?></p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="font-mono text-headline-sm font-bold text-primary">₱<?= number_format((float)($activeWithdrawal['net_amount'] ?? $activeWithdrawal['amount']), 2) ?></span>
                    <span class="text-[11px] text-on-surface-variant block font-mono">Gross: ₱<?= number_format((float)$activeWithdrawal['amount'], 2) ?></span>
                    <span class="block mt-1"><?= status_badge($activeWithdrawal['status']) ?></span>
                </div>
            </div>

            <!-- Payout Lifecycle Stepper -->
            <div class="grid grid-cols-4 gap-2 text-center text-xs">
                <div class="p-sm rounded-xl <?= in_array($activeStatus, ['pending', 'processing', 'transfer_pending', 'completed'], true) ? 'bg-amber-500/20 text-amber-900 font-bold border border-amber-500/40' : 'bg-surface-container-low text-on-surface-variant font-medium' ?>">
                    <div class="flex items-center justify-center gap-1 mb-1">
                        <span class="material-symbols-outlined text-[16px] text-amber-700">check_circle</span>
                        <span>1. Requested</span>
                    </div>
                    <span class="text-[10px] opacity-75 font-normal">Reserved</span>
                </div>
                <div class="p-sm rounded-xl <?= in_array($activeStatus, ['processing', 'transfer_pending', 'completed'], true) ? 'bg-amber-500/20 text-amber-900 font-bold border border-amber-500/40' : 'bg-surface-container-low text-on-surface-variant font-medium' ?>">
                    <div class="flex items-center justify-center gap-1 mb-1">
                        <span class="material-symbols-outlined text-[16px]">hourglass_top</span>
                        <span>2. Review</span>
                    </div>
                    <span class="text-[10px] opacity-75 font-normal">Audited</span>
                </div>
                <div class="p-sm rounded-xl <?= in_array($activeStatus, ['transfer_pending', 'completed'], true) ? 'bg-amber-500/20 text-amber-900 font-bold border border-amber-500/40' : 'bg-surface-container-low text-on-surface-variant font-medium' ?>">
                    <div class="flex items-center justify-center gap-1 mb-1">
                        <span class="material-symbols-outlined text-[16px]">swap_horiz</span>
                        <span>3. Transferring</span>
                    </div>
                    <span class="text-[10px] opacity-75 font-normal">PayMongo Rail</span>
                </div>
                <div class="p-sm rounded-xl <?= $activeStatus === 'completed' ? 'bg-emerald-500/20 text-emerald-900 font-bold border border-emerald-500/40' : 'bg-surface-container-low text-on-surface-variant font-medium' ?>">
                    <div class="flex items-center justify-center gap-1 mb-1">
                        <span class="material-symbols-outlined text-[16px]">payments</span>
                        <span>4. Disbursed</span>
                    </div>
                    <span class="text-[10px] opacity-75 font-normal">GCash Credited</span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tab Navigation: Settled GCash Earnings | Held in Escrow | Withdrawal Requests -->
    <div class="flex items-center gap-2 border-b border-outline-variant/30 pb-2 overflow-x-auto">
        <button type="button" 
                id="tabBtnSettled" 
                onclick="switchWithdrawalTab('settled')"
                class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all bg-primary text-on-primary shadow-sm cursor-pointer shrink-0">
            <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
            <span>Settled GCash Earnings</span>
            <span id="tabCountSettled" class="px-2 py-0.5 rounded-full text-xs font-extrabold bg-on-primary/20 text-on-primary">
                <?= count($settled_records ?? []) ?>
            </span>
        </button>

        <button type="button" 
                id="tabBtnEscrow" 
                onclick="switchWithdrawalTab('escrow')"
                class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all bg-surface-container-low hover:bg-surface-container text-on-surface-variant border border-outline-variant/20 cursor-pointer shrink-0">
            <span class="material-symbols-outlined text-[18px]">lock</span>
            <span>Held in Escrow</span>
            <span id="tabCountEscrow" class="px-2 py-0.5 rounded-full text-xs font-extrabold bg-amber-100 text-amber-800">
                <?= count($escrow_records ?? []) ?>
            </span>
        </button>

        <button type="button" 
                id="tabBtnHistory" 
                onclick="switchWithdrawalTab('history')"
                class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all bg-surface-container-low hover:bg-surface-container text-on-surface-variant border border-outline-variant/20 cursor-pointer shrink-0">
            <span class="material-symbols-outlined text-[18px]">history</span>
            <span>Withdrawal Requests</span>
            <span id="tabCountHistory" class="px-2 py-0.5 rounded-full text-xs font-extrabold bg-surface-container-high text-on-surface">
                <?= isset($pager) ? number_format((int) $pager->getTotal('withdrawals')) : count($withdrawals ?? []) ?>
            </span>
        </button>
    </div>

    <!-- Tab Pane 1: Settled GCash Earnings -->
    <div id="tabPaneSettled" class="glass-card rounded-2xl p-lg table-responsive space-y-md">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-sm border-b border-outline-variant/20 pb-md">
            <div>
                <h3 class="text-title-lg font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">verified</span>
                    <span>Completed GCash Transactions</span>
                </h3>
                <p class="text-xs text-on-surface-variant mt-0.5">
                    Orders and printing requests paid through GCash that have been fulfilled and released to your Available Balance.
                </p>
            </div>
            <div class="flex items-center gap-2 self-start sm:self-auto">
                <span class="px-3 py-1 rounded-xl text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    Gross Settled: ₱<?= number_format($released_earnings ?? 0, 2) ?>
                </span>
                <span class="text-xs font-semibold text-on-surface-variant">
                    <?= count($settled_records ?? []) ?> total records
                </span>
            </div>
        </div>

        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-outline-variant/30 text-label-sm text-on-surface-variant uppercase">
                    <th class="py-sm px-md">Reference</th>
                    <th class="py-sm px-md">Type</th>
                    <th class="py-sm px-md">Customer</th>
                    <th class="py-sm px-md">Payment Channel</th>
                    <th class="py-sm px-md">Settled GCash</th>
                    <th class="py-sm px-md text-center">Status</th>
                    <th class="py-sm px-md text-right">Completed Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($settled_records)): ?>
                    <?php foreach ($settled_records as $rec): ?>
                        <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low/60 transition-colors">
                            <td class="py-md px-md font-mono font-bold text-primary text-xs">
                                <?php if ($rec['type'] === 'order'): ?>
                                    <a href="<?= base_url('tenant/orders') ?>" class="hover:underline flex items-center gap-1">
                                        <span>#<?= esc($rec['reference']) ?></span>
                                    </a>
                                <?php else: ?>
                                    <a href="<?= base_url('tenant/printing') ?>" class="hover:underline flex items-center gap-1">
                                        <span>#<?= esc($rec['reference']) ?></span>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="py-md px-md">
                                <?php if ($rec['type'] === 'order'): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-secondary/15 text-secondary border border-secondary/25">
                                        <span class="material-symbols-outlined text-[14px]">inventory_2</span>
                                        <span>Product Order</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-primary/15 text-primary border border-primary/25">
                                        <span class="material-symbols-outlined text-[14px]">print</span>
                                        <span>Printing Request</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-md px-md text-xs font-semibold text-on-surface">
                                <?= esc($rec['customer_name']) ?>
                                <?php if (!empty($rec['file_name'])): ?>
                                    <span class="block text-[11px] text-outline font-normal truncate max-w-xs"><?= esc($rec['file_name']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="py-md px-md text-xs text-on-surface-variant font-medium">
                                <span class="inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px] text-primary">account_balance_wallet</span>
                                    <span><?= esc($rec['channel']) ?></span>
                                </span>
                            </td>
                            <td class="py-md px-md font-bold font-mono text-emerald-700 text-sm">
                                ₱<?= number_format((float) $rec['amount'], 2) ?>
                            </td>
                            <td class="py-md px-md text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <span class="material-symbols-outlined text-[13px]">check_circle</span>
                                    <span>Released</span>
                                </span>
                            </td>
                            <td class="py-md px-md text-xs text-on-surface-variant text-right font-medium">
                                <?= date('M d, Y • h:i A', strtotime($rec['completed_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="py-xl text-center">
                            <div class="flex flex-col items-center gap-xs">
                                <span class="material-symbols-outlined text-4xl text-outline-variant">account_balance_wallet</span>
                                <span class="text-sm font-semibold text-on-surface">No completed GCash payments yet</span>
                                <span class="text-xs text-on-surface-variant max-w-md">
                                    Only customer orders and printing requests paid through GCash that have been fulfilled and delivered will be recorded here and credited to your Available Balance.
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Tab Pane 2: Held in Escrow -->
    <div id="tabPaneEscrow" class="glass-card rounded-2xl p-lg table-responsive space-y-md hidden">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-sm border-b border-outline-variant/20 pb-md">
            <div>
                <h3 class="text-title-lg font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-700">lock</span>
                    <span>GCash Funds in Escrow</span>
                </h3>
                <p class="text-xs text-on-surface-variant mt-0.5">
                    Customer GCash payments captured and safely held in escrow until order delivery or store pickup completion.
                </p>
            </div>
            <div class="flex items-center gap-2 self-start sm:self-auto">
                <span class="px-3 py-1 rounded-xl text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                    Total in Escrow: ₱<?= number_format($escrow_holding ?? 0, 2) ?>
                </span>
                <span class="text-xs font-semibold text-on-surface-variant">
                    <?= count($escrow_records ?? []) ?> total records
                </span>
            </div>
        </div>

        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-outline-variant/30 text-label-sm text-on-surface-variant uppercase">
                    <th class="py-sm px-md">Reference</th>
                    <th class="py-sm px-md">Type</th>
                    <th class="py-sm px-md">Customer</th>
                    <th class="py-sm px-md">Payment Channel</th>
                    <th class="py-sm px-md">Amount in Escrow</th>
                    <th class="py-sm px-md text-center">Fulfillment Status</th>
                    <th class="py-sm px-md text-right">Order Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($escrow_records)): ?>
                    <?php foreach ($escrow_records as $rec): ?>
                        <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low/60 transition-colors">
                            <td class="py-md px-md font-mono font-bold text-primary text-xs">
                                #<?= esc($rec['reference']) ?>
                            </td>
                            <td class="py-md px-md">
                                <?php if ($rec['type'] === 'order'): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-secondary/15 text-secondary border border-secondary/25">
                                        <span class="material-symbols-outlined text-[14px]">inventory_2</span>
                                        <span>Product Order</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-primary/15 text-primary border border-primary/25">
                                        <span class="material-symbols-outlined text-[14px]">print</span>
                                        <span>Printing Request</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-md px-md text-xs font-semibold text-on-surface">
                                <?= esc($rec['customer_name']) ?>
                                <?php if (!empty($rec['file_name'])): ?>
                                    <span class="block text-[11px] text-outline font-normal truncate max-w-xs"><?= esc($rec['file_name']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="py-md px-md text-xs text-on-surface-variant font-medium">
                                <span class="inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px] text-amber-700">lock</span>
                                    <span><?= esc($rec['channel']) ?></span>
                                </span>
                            </td>
                            <td class="py-md px-md font-bold font-mono text-amber-700 text-sm">
                                ₱<?= number_format((float) $rec['amount'], 2) ?>
                            </td>
                            <td class="py-md px-md text-center">
                                <?= status_badge($rec['status']) ?>
                            </td>
                            <td class="py-md px-md text-xs text-on-surface-variant text-right font-medium">
                                <?= date('M d, Y • h:i A', strtotime($rec['placed_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="py-xl text-center">
                            <div class="flex flex-col items-center gap-xs">
                                <span class="material-symbols-outlined text-4xl text-outline-variant">lock_open</span>
                                <span class="text-sm font-semibold text-on-surface">No active funds in escrow</span>
                                <span class="text-xs text-on-surface-variant max-w-md">
                                    When customers place orders with GCash, payments are held here until you complete preparation and delivery.
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Tab Pane 3: Withdrawal Requests -->
    <div id="tabPaneHistory" class="glass-card rounded-2xl p-lg table-responsive space-y-md hidden">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-outline-variant/20 pb-md">
            <div>
                <h3 class="text-title-lg font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">history</span>
                    <span>Withdrawal History</span>
                </h3>
                <p class="text-xs text-on-surface-variant mt-0.5">Complete record of payout requests and settlement statuses.</p>
            </div>
            <div class="flex items-center gap-3">
                <form method="get" action="<?= base_url('tenant/withdrawals') ?>" class="flex items-center gap-1.5 text-xs text-on-surface-variant">
                    <input type="hidden" name="tab" value="history">
                    <label for="withdrawals_per_page" class="font-medium">Show:</label>
                    <select name="per_page" id="withdrawals_per_page" onchange="this.form.submit()" class="bg-surface-container-lowest border border-outline-variant rounded-xl px-2.5 py-1.5 text-xs font-semibold focus:ring-2 focus:ring-primary/30">
                        <option value="5" <?= ($per_page ?? 10) == 5 ? 'selected' : '' ?>>5</option>
                        <option value="10" <?= ($per_page ?? 10) == 10 ? 'selected' : '' ?>>10</option>
                        <option value="15" <?= ($per_page ?? 10) == 15 ? 'selected' : '' ?>>15</option>
                        <option value="20" <?= ($per_page ?? 10) == 20 ? 'selected' : '' ?>>20</option>
                    </select>
                </form>
                <span class="text-xs font-semibold text-on-surface-variant"><?= isset($pager) ? number_format((int) $pager->getTotal('withdrawals')) : count($withdrawals ?? []) ?> total records</span>
            </div>
        </div>

        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-outline-variant/30 text-label-sm text-on-surface-variant uppercase">
                    <th class="py-sm px-md">Request ID</th>
                    <th class="py-sm px-md">Gross Amount</th>
                    <th class="py-sm px-md">Admin Fee (<?= number_format($deduction_percent ?? 3.00, 2) ?>%)</th>
                    <th class="py-sm px-md">Net Payout</th>
                    <th class="py-sm px-md">Recipient Snapshot</th>
                    <th class="py-sm px-md text-center">Status</th>
                    <th class="py-sm px-md text-right">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($withdrawals)): ?>
                    <?php foreach ($withdrawals as $w): ?>
                        <?php 
                            $gross = (float)$w['amount'];
                            $feeRate = (float)($w['deduction_percent'] ?? $deduction_percent ?? 3.00) / 100;
                            $fee = (float)($w['fee'] ?? ($gross * $feeRate));
                            $net = (float)($w['net_amount'] ?? max(0, $gross - $fee));
                        ?>
                        <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low/60 transition-colors">
                            <td class="py-md px-md font-mono font-semibold text-primary">#<?= esc($w['reference_number'] ?? ('WD-' . $w['id'])) ?></td>
                            <td class="py-md px-md font-bold font-mono text-on-surface">₱<?= number_format($gross, 2) ?></td>
                            <td class="py-md px-md text-xs font-mono text-amber-800 font-semibold">-₱<?= number_format($fee, 2) ?></td>
                            <td class="py-md px-md font-bold font-mono text-emerald-700">₱<?= number_format($net, 2) ?></td>
                            <td class="py-md px-md text-xs">
                                <div class="font-semibold text-on-surface"><?= esc($w['recipient_account_name'] ?? $shop['gcash_name'] ?? '—') ?></div>
                                <div class="font-mono text-[11px] text-on-surface-variant flex items-center gap-1 mt-0.5">
                                    <span class="material-symbols-outlined text-[13px] text-primary">phone_iphone</span>
                                    <?php
                                        $dest = $w['destination_detail'] ?? $w['account_details'] ?? '';
                                        echo esc(strlen($dest) === 11 ? substr($dest, 0, 2) . '••••••' . substr($dest, -4) : $dest);
                                    ?>
                                </div>
                                <div class="text-[10px] text-outline mt-0.5"><?= esc($w['recipient_institution'] ?? 'G-Xchange, Inc.') ?></div>
                            </td>
                            <td class="py-md px-md text-center"><?= status_badge($w['status'] ?? 'pending') ?></td>
                            <td class="py-md px-md text-xs text-on-surface-variant text-right"><?= date('M d, Y h:i A', strtotime($w['created_at'] ?? $w['requested_at'] ?? 'now')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="py-xl text-center">
                            <div class="flex flex-col items-center gap-xs">
                                <span class="material-symbols-outlined text-4xl text-outline-variant">payments</span>
                                <span class="text-sm font-semibold text-on-surface">No withdrawal requests yet</span>
                                <span class="text-xs text-on-surface-variant max-w-xs">When you have delivered customer orders, your escrow funds will release to available balance for withdrawal.</span>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (isset($pager)): ?>
            <?php
            $total   = (int) $pager->getTotal('withdrawals');
            $perPage = (int) ($per_page ?? ($pager ? $pager->getPerPage('withdrawals') : 10));
            $cur     = (int) $pager->getCurrentPage('withdrawals');
            $pages   = (int) $pager->getPageCount('withdrawals');
            $start   = $total === 0 ? 0 : ($cur - 1) * $perPage + 1;
            $end     = min($cur * $perPage, $total);
            ?>
            <div class="px-md py-md bg-surface-container-low/30 rounded-xl flex justify-between items-center border-t border-outline-variant/20 flex-wrap gap-sm mt-md">
                <p class="text-xs text-on-surface-variant">Showing <?= number_format($start) ?> to <?= number_format($end) ?> of <?= number_format($total) ?> records</p>
                <?php if ($pages > 1): ?>
                    <div class="flex items-center gap-xs">
                        <a class="p-sm rounded hover:bg-surface-container-high <?= $cur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('withdrawals') ?>" title="Previous">
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
                            <a class="w-8 h-8 rounded flex items-center justify-center text-xs <?= $cur === $num ? 'bg-primary text-on-primary font-semibold' : 'hover:bg-surface-container-high text-on-surface' ?>" href="<?= $pager->getPageURI($num, 'withdrawals') ?>"><?= $num ?></a>
                        <?php $prev = $num; endforeach; ?>
                        <a class="p-sm rounded hover:bg-surface-container-high <?= $cur >= $pages ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('withdrawals') ?>" title="Next">
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Withdraw Modal -->
<div id="withdrawModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-md">

    <div class="bg-surface-container-lowest rounded-2xl p-xl max-w-md w-full space-y-md border border-outline-variant/30 shadow-2xl">

        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">

            <h3 class="text-title-lg font-bold">Request Withdrawal</h3>

            <button onclick="document.getElementById('withdrawModal').classList.add('hidden')" class="text-on-surface-variant"><span class="material-symbols-outlined">close</span></button>

        </div>

        <form action="<?= base_url('tenant/withdrawals/request') ?>" method="POST" class="space-y-md">

            <?= csrf_field() ?>

            <div>
                <div class="flex justify-between items-center">
                    <label class="text-label-sm font-bold text-on-surface-variant">Withdrawal Amount (₱)</label>
                    <span class="text-xs text-primary font-bold">Max: ₱<?= number_format($available_balance ?? 0, 2) ?></span>
                </div>
                <input type="number" step="0.01" min="20" <?= ($available_balance ?? 0) >= 20 ? 'max="' . ($available_balance ?? 0) . '"' : '' ?> name="amount" id="withdrawAmountInput" oninput="updateWithdrawalBreakdown(this.value)" required placeholder="e.g. 50.00" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl mt-xs font-mono font-bold text-base focus:ring-2 focus:ring-primary">
                <p class="text-[11px] text-on-surface-variant mt-1 flex items-center justify-between">
                    <span>Minimum withdrawal: <strong class="text-on-surface font-semibold">₱20.00</strong></span>
                    <?php if (($available_balance ?? 0) < 20): ?>
                        <span class="text-amber-700 font-medium">Insufficient balance</span>
                    <?php endif; ?>
                </p>
            </div>

            <?php if (($available_balance ?? 0) < 20): ?>
                <div class="p-2.5 bg-amber-500/10 border border-amber-500/30 rounded-xl flex items-center gap-2 text-xs text-amber-800">
                    <span class="material-symbols-outlined text-[18px] text-amber-600 shrink-0">info</span>
                    <span>Your available balance of ₱<?= number_format($available_balance ?? 0, 2) ?> is below the minimum withdrawal amount of <strong>₱20.00</strong>.</span>
                </div>
            <?php endif; ?>

            <!-- Live Calculation Breakdown -->
            <div class="p-3 bg-surface-container-low border border-outline-variant/30 rounded-xl space-y-1.5 text-xs">
                <div class="flex justify-between text-on-surface-variant">
                    <span>Gross Withdrawal:</span>
                    <span id="breakdownGross" class="font-mono font-semibold">₱0.00</span>
                </div>
                <div class="flex justify-between text-amber-700">
                    <span>Platform Admin Fee (<?= number_format($deduction_percent ?? 3.00, 2) ?>%):</span>
                    <span id="breakdownFee" class="font-mono font-semibold">-₱0.00</span>
                </div>
                <div class="flex justify-between text-on-surface border-t border-outline-variant/20 pt-1.5 font-bold">
                    <span>Net Disbursed to GCash:</span>
                    <span id="breakdownNet" class="font-mono text-sm text-emerald-700">₱0.00</span>
                </div>
            </div>

            <!-- Verified GCash Recipient Destination Snapshot -->
            <div class="p-3 bg-surface-container-low border border-outline-variant/30 rounded-xl space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider text-[10px]">Verified Recipient Details</span>
                    <span class="inline-flex items-center gap-1 text-[11px] text-emerald-700 font-semibold">
                        <span class="material-symbols-outlined text-[14px]">verified</span> Verified GCash
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <span class="text-on-surface-variant block text-[11px]">Account Name</span>
                        <span class="font-bold text-on-surface truncate block" title="<?= esc($gcash_account_name ?: ($shop['gcash_name'] ?? '—')) ?>"><?= esc($gcash_account_name ?: ($shop['gcash_name'] ?? '—')) ?></span>
                    </div>
                    <div>
                        <span class="text-on-surface-variant block text-[11px]">Mobile Number</span>
                        <span class="font-mono font-bold text-primary"><?= esc($masked_gcash_number ?: ($shop['gcash_number'] ?? '—')) ?></span>
                    </div>
                    <div class="col-span-2 pt-1 border-t border-outline-variant/20 flex items-center justify-between text-[11px] text-on-surface-variant">
                        <span>Institution: <strong class="text-on-surface">G-Xchange, Inc. (GCash)</strong></span>
                        <a href="<?= base_url('tenant/settings?tab=payment') ?>" class="text-primary hover:underline font-semibold flex items-center gap-0.5" title="Change GCash recipient in Payment Settings">
                            <span class="material-symbols-outlined text-[13px]">edit</span> Edit in Settings
                        </a>
                    </div>
                </div>
            </div>

            <input type="hidden" name="method" value="GCash">
            <input type="hidden" name="account_details" value="<?= esc($shop['gcash_number'] ?? '') ?>">

            <button type="submit" <?= ($available_balance ?? 0) < 20 ? 'disabled class="w-full py-md bg-surface-container-high text-on-surface-variant/50 rounded-xl font-bold cursor-not-allowed shadow-none"' : 'class="w-full py-md bg-primary text-on-primary rounded-xl font-bold hover:bg-primary/90 transition-all shadow-md active:scale-98"' ?>>Submit Request</button>

        </form>

    </div>

</div>

<script>
    (function () {
        var gcashNumber = <?= json_encode($shop['gcash_number'] ?? '') ?>;
        var detailsInput = document.querySelector('#withdrawModal input[name="account_details"]');
        if (detailsInput && gcashNumber && detailsInput.value === '') {
            detailsInput.value = gcashNumber;
        }
    })();

    function updateWithdrawalBreakdown(val) {
        const gross = parseFloat(val) || 0;
        const feeRate = <?= json_encode((float)($deduction_percent ?? 3.00)) ?> / 100;
        const fee = Math.round(gross * feeRate * 100) / 100;
        const net = Math.max(0, gross - fee);

        document.getElementById('breakdownGross').textContent = '₱' + gross.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('breakdownFee').textContent = '-₱' + fee.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('breakdownNet').textContent = '₱' + net.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function switchWithdrawalTab(tab) {
        const tabs = ['settled', 'escrow', 'history'];
        tabs.forEach(t => {
            const btn = document.getElementById('tabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
            const pane = document.getElementById('tabPane' + t.charAt(0).toUpperCase() + t.slice(1));
            if (!btn || !pane) return;

            if (t === tab) {
                btn.className = 'px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all bg-primary text-on-primary shadow-sm cursor-pointer shrink-0';
                pane.classList.remove('hidden');
            } else {
                btn.className = 'px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all bg-surface-container-low hover:bg-surface-container text-on-surface-variant border border-outline-variant/20 cursor-pointer shrink-0';
                pane.classList.add('hidden');
            }
        });
        try {
            sessionStorage.setItem('blax_withdrawal_active_tab', tab);
        } catch (e) {}
    }

    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('page_withdrawals') || urlParams.get('tab') === 'history' || urlParams.has('per_page')) {
            switchWithdrawalTab('history');
        } else {
            try {
                const saved = sessionStorage.getItem('blax_withdrawal_active_tab');
                if (saved && ['settled', 'escrow', 'history'].includes(saved)) {
                    switchWithdrawalTab(saved);
                }
            } catch (e) {}
        }
    });
</script>

<?= $this->endSection() ?>