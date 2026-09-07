<?= $this->extend('layouts/tenant') ?>
<?= $this->section('content') ?>

<div class="flex-1">

    <!-- Balance Summary Card -->
    <div class="glass-card rounded-2xl p-xl mb-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-lg">

        <div class="flex flex-col sm:flex-row gap-lg items-start sm:items-center">

            <div>
                <p class="text-label-sm text-on-surface-variant uppercase tracking-wider mb-xs">Available Balance</p>
                <h3 class="text-headline-lg font-bold text-primary">₱<?= number_format($available_balance ?? 0.00, 2) ?></h3>
                <p class="text-xs text-on-surface-variant mt-xs">Eligible for withdrawal (Delivered / Completed orders)</p>
            </div>

            <div class="sm:border-l sm:border-outline-variant/30 sm:pl-lg">
                <p class="text-label-sm text-amber-800 uppercase tracking-wider mb-xs flex items-center gap-xs">
                    <span class="material-symbols-outlined text-sm">lock</span> Funds in Escrow
                </p>
                <h3 class="text-headline-md font-bold text-amber-700">₱<?= number_format($escrow_holding ?? 0.00, 2) ?></h3>
                <p class="text-xs text-on-surface-variant mt-xs">Held until orders are confirmed delivered to customer</p>
            </div>

        </div>

        <button onclick="document.getElementById('withdrawModal').classList.remove('hidden')" class="bg-primary text-on-primary px-xl py-md rounded-xl font-bold hover:bg-primary/90 transition-colors shadow-md">

            Request Withdrawal

        </button>

    </div>

    <!-- Withdrawal History Table -->
    <div class="glass-card rounded-2xl p-lg table-responsive">

        <h3 class="text-title-lg font-bold text-on-surface mb-md">Withdrawal History</h3>

        <table class="w-full text-left border-collapse">

            <thead>

                <tr class="border-b border-outline-variant/30 text-label-sm text-on-surface-variant uppercase">

                    <th class="py-sm px-md">Request ID</th>
                    <th class="py-sm px-md">Amount</th>
                    <th class="py-sm px-md">Method</th>
                    <th class="py-sm px-md">Status</th>
                    <th class="py-sm px-md">Date</th>

                </tr>

            </thead>

            <tbody>

                <?php if (!empty($withdrawals)): ?>

                    <?php foreach ($withdrawals as $w): ?>

                        <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low">

                            <td class="py-md px-md font-semibold">#<?= esc($w['reference_number'] ?? ('WD-' . $w['id'])) ?></td>
                            <td class="py-md px-md font-bold text-primary">₱<?= number_format($w['amount'], 2) ?></td>
                            <td class="py-md px-md text-xs text-on-surface-variant uppercase"><?= esc($w['method'] ?? 'GCash') ?></td>
                            <td class="py-md px-md"><?= status_badge($w['status'] ?? 'pending') ?></td>
                            <td class="py-md px-md text-xs text-on-surface-variant"><?= date('M d, Y', strtotime($w['created_at'])) ?></td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr><td colspan="5" class="py-lg text-center text-on-surface-variant">No withdrawal requests yet.</td></tr>

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