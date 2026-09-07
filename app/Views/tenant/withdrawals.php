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

    <!-- Request Withdrawal CTA Banner -->
    <div class="glass-card rounded-2xl p-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-md border border-outline-variant/30">
        <div>
            <h3 class="text-title-lg font-bold text-on-surface">Merchant Payouts</h3>
            <p class="text-xs text-on-surface-variant mt-0.5">Withdraw earnings to your registered GCash account. Standard 3% administrative fee applies to cover gateway disbursement.</p>
        </div>
        <button onclick="document.getElementById('withdrawModal').classList.remove('hidden')" class="px-xl py-md bg-primary text-on-primary rounded-xl font-bold text-xs hover:bg-primary/90 transition-colors shadow-md flex items-center gap-xs whitespace-nowrap active:scale-95">
            <span class="material-symbols-outlined text-[18px]">payments</span>
            Request Withdrawal
        </button>
    </div>

    <?php if ($activeWithdrawal): ?>
        <!-- Prominent Active Payout Status Tracker -->
        <div class="rounded-2xl p-lg bg-amber-500/10 border border-amber-500/30 space-y-md shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-sm border-b border-amber-500/20 pb-sm">
                <div class="flex items-center gap-sm">
                    <span class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-800 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px] animate-pulse">sync</span>
                    </span>
                    <div>
                        <h4 class="font-bold text-on-surface text-body-md">Active Payout Request #<?= esc($activeWithdrawal['reference_number'] ?? ('WD-' . $activeWithdrawal['id'])) ?></h4>
                        <p class="text-[11px] text-on-surface-variant">Submitted on <?= date('M d, Y h:i A', strtotime($activeWithdrawal['created_at'])) ?> &bull; Method: <?= esc($activeWithdrawal['method'] ?? 'GCash') ?></p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="font-mono text-headline-sm font-bold text-primary">₱<?= number_format((float)$activeWithdrawal['amount'], 2) ?></span>
                    <span class="block"><?= status_badge($activeWithdrawal['status']) ?></span>
                </div>
            </div>

            <!-- Payout Lifecycle Stepper -->
            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                <div class="p-sm rounded-xl bg-amber-500/20 text-amber-900 font-bold border border-amber-500/40">
                    <div class="flex items-center justify-center gap-1 mb-1">
                        <span class="material-symbols-outlined text-[16px] text-amber-700">check_circle</span>
                        <span>1. Requested</span>
                    </div>
                    <span class="text-[10px] opacity-75 font-normal">Details recorded</span>
                </div>
                <div class="p-sm rounded-xl <?= in_array(strtolower($activeWithdrawal['status']), ['processing', 'under_review'], true) ? 'bg-amber-500/20 text-amber-900 font-bold border border-amber-500/40' : 'bg-surface-container-low text-on-surface-variant font-medium' ?>">
                    <div class="flex items-center justify-center gap-1 mb-1">
                        <span class="material-symbols-outlined text-[16px]">hourglass_top</span>
                        <span>2. Admin Review</span>
                    </div>
                    <span class="text-[10px] opacity-75 font-normal">Verifying balance</span>
                </div>
                <div class="p-sm rounded-xl bg-surface-container-low text-on-surface-variant font-medium">
                    <div class="flex items-center justify-center gap-1 mb-1">
                        <span class="material-symbols-outlined text-[16px]">send</span>
                        <span>3. GCash Disbursed</span>
                    </div>
                    <span class="text-[10px] opacity-75 font-normal">Sent to mobile wallet</span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Withdrawal History Table -->
    <div class="glass-card rounded-2xl p-lg table-responsive">
        <div class="flex items-center justify-between mb-md">
            <div>
                <h3 class="text-title-lg font-bold text-on-surface">Withdrawal History</h3>
                <p class="text-xs text-on-surface-variant mt-0.5">Complete record of payout requests and settlement statuses.</p>
            </div>
            <span class="text-xs font-semibold text-on-surface-variant"><?= count($withdrawals ?? []) ?> total records</span>
        </div>

        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-outline-variant/30 text-label-sm text-on-surface-variant uppercase">
                    <th class="py-sm px-md">Request ID</th>
                    <th class="py-sm px-md">Amount</th>
                    <th class="py-sm px-md">Method</th>
                    <th class="py-sm px-md">Account / Phone</th>
                    <th class="py-sm px-md text-center">Status</th>
                    <th class="py-sm px-md text-right">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($withdrawals)): ?>
                    <?php foreach ($withdrawals as $w): ?>
                        <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low/60 transition-colors">
                            <td class="py-md px-md font-mono font-semibold text-primary">#<?= esc($w['reference_number'] ?? ('WD-' . $w['id'])) ?></td>
                            <td class="py-md px-md font-bold font-mono text-on-surface">₱<?= number_format($w['amount'], 2) ?></td>
                            <td class="py-md px-md text-xs text-on-surface font-semibold uppercase">
                                <span class="inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[15px] text-primary">smartphone</span>
                                    <?= esc($w['method'] ?? 'GCash') ?>
                                </span>
                            </td>
                            <td class="py-md px-md text-xs font-mono text-on-surface-variant"><?= esc($w['account_details'] ?? '—') ?></td>
                            <td class="py-md px-md text-center"><?= status_badge($w['status'] ?? 'pending') ?></td>
                            <td class="py-md px-md text-xs text-on-surface-variant text-right"><?= date('M d, Y h:i A', strtotime($w['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="py-xl text-center">
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
    </div>

</div>

<!-- Withdraw Modal -->
<div id="withdrawModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-md">

    <div class="bg-surface-container-lowest rounded-2xl p-xl max-w-md w-full space-y-md border border-outline-variant/30">

        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">

            <h3 class="text-title-lg font-bold">Request Withdrawal</h3>

            <button onclick="document.getElementById('withdrawModal').classList.add('hidden')" class="text-on-surface-variant"><span class="material-symbols-outlined">close</span></button>

        </div>

        <form action="<?= base_url('tenant/withdrawals/request') ?>" method="POST" class="space-y-md">

            <?= csrf_field() ?>

            <div>

                <label class="text-label-sm font-bold text-on-surface-variant">Withdrawal Amount (₱)</label>
                <input type="number" step="0.01" name="amount" max="<?= $available_balance ?? 0 ?>" required class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl mt-xs">

            </div>

            <div>

                <label class="text-label-sm font-bold text-on-surface-variant">Payment Method</label>
                <div class="w-full p-md bg-primary-container/20 border border-outline-variant rounded-xl mt-xs flex items-center gap-sm text-sm font-semibold text-primary">
                    <span class="material-symbols-outlined text-[18px]">phone_iphone</span> GCash — 3% admin fee applies
                </div>
                <input type="hidden" name="method" value="GCash">
                <p class="text-xs text-on-surface-variant mt-xs">Only GCash withdrawals are supported. A 3% fee is deducted and recorded as admin revenue.</p>

            </div>

            <div>

                <label class="text-label-sm font-bold text-on-surface-variant">GCash Number</label>
                <input type="text" name="account_details" placeholder="09XXXXXXXXX" pattern="09[0-9]{9}" required class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl mt-xs">
                <p class="text-xs text-on-surface-variant mt-xs">Format: 09XXXXXXXXX (11 digits)</p>

            </div>

            <button type="submit" class="w-full py-md bg-primary text-on-primary rounded-xl font-bold hover:bg-primary/90">Submit Request</button>

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
</script>

<?= $this->endSection() ?>