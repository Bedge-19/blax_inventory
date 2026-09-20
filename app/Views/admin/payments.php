<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="space-y-xl">

    <!-- Flash Notifications -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-md rounded-2xl bg-green-100 text-green-800 text-sm font-semibold flex items-center gap-sm border border-green-200 shadow-sm">
            <span class="material-symbols-outlined text-[20px] text-green-700">check_circle</span>
            <span><?= esc(session()->getFlashdata('success')) ?></span>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-md rounded-2xl bg-error-container text-on-error-container text-sm font-semibold flex items-center gap-sm border border-error/20 shadow-sm">
            <span class="material-symbols-outlined text-[20px] text-error">error</span>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
        </div>
    <?php endif; ?>

    <!-- 4-Card Payout Metrics Grid -->
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-gutter">
        <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/30 soft-shadow flex flex-col justify-between">
            <div class="flex justify-between items-start mb-2">
                <p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Pending Review</p>
                <span class="w-9 h-9 rounded-xl bg-amber-500/15 text-amber-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">pending_actions</span>
                </span>
            </div>
            <div>
                <h3 class="text-headline-md font-bold text-amber-700">₱<?= number_format($pending_total ?? 0, 2) ?></h3>
                <p class="text-xs text-on-surface-variant mt-xs"><?= esc($pending_count ?? 0) ?> request(s) awaiting review</p>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/30 soft-shadow flex flex-col justify-between">
            <div class="flex justify-between items-start mb-2">
                <p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">In Transfer / Review</p>
                <span class="w-9 h-9 rounded-xl bg-primary-container/20 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">swap_horiz</span>
                </span>
            </div>
            <div>
                <h3 class="text-headline-md font-bold text-primary">₱<?= number_format($processing_total ?? 0, 2) ?></h3>
                <p class="text-xs text-on-surface-variant mt-xs"><?= esc($processing_count ?? 0) ?> in progress or PayMongo rail</p>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/30 soft-shadow flex flex-col justify-between">
            <div class="flex justify-between items-start mb-2">
                <p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Total Disbursed</p>
                <span class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">check_circle</span>
                </span>
            </div>
            <div>
                <h3 class="text-headline-md font-bold text-emerald-700">₱<?= number_format($completed_total ?? 0, 2) ?></h3>
                <p class="text-xs text-on-surface-variant mt-xs">Completed GCash transfers</p>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/30 soft-shadow flex flex-col justify-between">
            <div class="flex justify-between items-start mb-2">
                <p class="text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold">Platform Fee Rate</p>
                <span class="w-9 h-9 rounded-xl bg-secondary-container/30 text-secondary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">percent</span>
                </span>
            </div>
            <div>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-headline-md font-bold text-on-surface"><?= number_format($deduction_percent ?? 3.00, 2) ?>%</h3>
                    <span class="text-xs text-on-surface-variant">deduction</span>
                </div>
                <form action="<?= base_url('admin/payments/deduction') ?>" method="POST" class="mt-2 flex items-center gap-1.5">
                    <?= csrf_field() ?>
                    <input type="number" step="0.01" min="0" max="50" name="deduction_percent" value="<?= esc($deduction_percent ?? 3.00) ?>" class="w-20 px-2 py-1 text-xs bg-surface-container-low border border-outline-variant rounded-lg font-mono font-bold focus:ring-2 focus:ring-primary focus:outline-none">
                    <button type="submit" class="px-2.5 py-1 text-xs font-bold bg-primary text-on-primary rounded-lg hover:bg-primary/90 transition-all shadow-sm">Save</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Table Section -->
    <section class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 soft-shadow overflow-hidden">
        <div class="p-6 border-b border-outline-variant/20 flex flex-col sm:flex-row sm:items-center justify-between gap-sm">
            <div>
                <h3 class="text-title-lg font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">payments</span>
                    <span>Merchant GCash Disbursals</span>
                </h3>
                <p class="text-xs text-on-surface-variant mt-0.5">Two-step payout governance: Review merchant records before dispatching real-time transfers via PayMongo Wallet API.</p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="px-6 py-4 border-b border-outline-variant/20 bg-surface-container-low/40 flex flex-wrap gap-sm items-center">
            <form method="get" action="<?= base_url('admin/payments') ?>" class="flex flex-wrap gap-sm flex-1">
                <div class="relative flex-1 min-w-0 sm:min-w-[240px]">
                    <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                    <input name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Search ref, shop name, or phone..." class="w-full pl-xl pr-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                </div>
                <select name="status" class="bg-surface-container-lowest border border-outline-variant rounded-xl px-md py-sm text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                    <option value="processing" <?= ($filters['status'] ?? '') === 'processing' ? 'selected' : '' ?>>In Review</option>
                    <option value="transfer_pending" <?= ($filters['status'] ?? '') === 'transfer_pending' ? 'selected' : '' ?>>Transfer Pending</option>
                    <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="failed" <?= ($filters['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
                </select>
                <div class="flex items-center gap-1.5 text-xs text-on-surface-variant">
                    <label for="per_page_select" class="font-medium">Show:</label>
                    <select name="per_page" id="per_page_select" onchange="this.form.submit()" class="bg-surface-container-lowest border border-outline-variant rounded-xl px-2.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                        <option value="5" <?= ($per_page ?? 10) == 5 ? 'selected' : '' ?>>5</option>
                        <option value="10" <?= ($per_page ?? 10) == 10 ? 'selected' : '' ?>>10</option>
                        <option value="15" <?= ($per_page ?? 10) == 15 ? 'selected' : '' ?>>15</option>
                        <option value="20" <?= ($per_page ?? 10) == 20 ? 'selected' : '' ?>>20</option>
                    </select>
                </div>
                <button type="submit" class="bg-primary text-on-primary px-md py-sm rounded-xl text-sm font-semibold hover:bg-primary/90 transition-all shadow-sm">Filter</button>
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low/50 text-label-sm text-on-surface-variant/70 uppercase border-b border-outline-variant/20">
                        <th class="py-sm px-md">Request ID</th>
                        <th class="py-sm px-md">Merchant & Owner</th>
                        <th class="py-sm px-md">Gross</th>
                        <th class="py-sm px-md">Fee</th>
                        <th class="py-sm px-md">Net Disbursed</th>
                        <th class="py-sm px-md">Recipient Snapshot</th>
                        <th class="py-sm px-md">Status & Rails</th>
                        <th class="py-sm px-md">Date</th>
                        <th class="py-sm px-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($payment_requests)): ?>
                        <?php foreach ($payment_requests as $p): ?>
                            <?php
                                $status = strtolower($p['status'] ?? 'pending');
                                $gross  = (float) $p['amount'];
                                $rate   = (float) ($p['deduction_percent'] ?? $deduction_percent ?? 3.00);
                                $fee    = (float) (($p['fee'] ?? 0) > 0 ? $p['fee'] : round($gross * ($rate / 100), 2));
                                $net    = (float) ($p['net_amount'] ?? max(0, $gross - $fee));

                                $destNumber = $p['destination_detail'] ?? $p['account_details'] ?? '';
                                $maskedPhone = strlen($destNumber) === 11 ? substr($destNumber, 0, 2) . '••••••' . substr($destNumber, -4) : $destNumber;
                                $recipientName = $p['recipient_account_name'] ?? $p['shop_name'] ?? 'Merchant';
                                $ownerFullName = trim(($p['owner_first_name'] ?? '') . ' ' . ($p['owner_last_name'] ?? ''));
                            ?>
                            <tr class="border-b border-outline-variant/10 hover:bg-surface-container-low/60 transition-colors">
                                <!-- Request ID -->
                                <td class="py-md px-md font-mono text-xs font-bold text-primary">
                                    #<?= esc($p['reference_number'] ?? ('WD-' . $p['id'])) ?>
                                </td>

                                <!-- Merchant & Owner -->
                                <td class="py-md px-md text-xs">
                                    <div class="font-bold text-on-surface"><?= esc($p['shop_name'] ?? 'Merchant') ?></div>
                                    <?php if (!empty($ownerFullName)): ?>
                                        <div class="text-[11px] text-on-surface-variant flex items-center gap-1 mt-0.5">
                                            <span class="material-symbols-outlined text-[13px]">person</span>
                                            <span><?= esc($ownerFullName) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Gross -->
                                <td class="py-md px-md font-bold font-mono text-on-surface text-xs">
                                    ₱<?= number_format($gross, 2) ?>
                                </td>

                                <!-- Fee -->
                                <td class="py-md px-md text-xs font-mono text-amber-800 font-semibold">
                                    -₱<?= number_format($fee, 2) ?>
                                    <span class="block text-[10px] text-outline font-normal"><?= number_format($rate, 1) ?>%</span>
                                </td>

                                <!-- Net Disbursed -->
                                <td class="py-md px-md font-bold font-mono text-emerald-700 text-xs">
                                    ₱<?= number_format($net, 2) ?>
                                </td>

                                <!-- Recipient Snapshot -->
                                <td class="py-md px-md text-xs">
                                    <div class="font-semibold text-on-surface"><?= esc($recipientName) ?></div>
                                    <div class="font-mono text-[11px] text-primary flex items-center gap-1 mt-0.5">
                                        <span class="material-symbols-outlined text-[13px]">phone_iphone</span>
                                        <span><?= esc($maskedPhone) ?></span>
                                    </div>
                                    <div class="text-[10px] text-outline mt-0.5"><?= esc($p['recipient_institution'] ?? 'G-Xchange, Inc.') ?></div>
                                </td>

                                <!-- Status & Rails -->
                                <td class="py-md px-md text-xs">
                                    <div class="flex items-center gap-1">
                                        <?= status_badge($status) ?>
                                    </div>

                                    <?php if (!empty($p['paymongo_transfer_id'])): ?>
                                        <div class="font-mono text-[10px] text-outline-variant text-on-surface-variant mt-1" title="PayMongo Transfer ID: <?= esc($p['paymongo_transfer_id']) ?>">
                                            ID: <?= esc(substr($p['paymongo_transfer_id'], 0, 15)) ?>...
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($p['processor_first_name'])): ?>
                                        <div class="text-[10px] text-on-surface-variant mt-0.5">
                                            By: <?= esc($p['processor_first_name']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($status === 'failed' && !empty($p['failure_reason'])): ?>
                                        <div class="text-[10px] text-error font-medium mt-1 max-w-[160px] truncate" title="<?= esc($p['failure_reason']) ?>">
                                            <?= esc($p['failure_reason']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Date -->
                                <td class="py-md px-md text-xs text-on-surface-variant whitespace-nowrap">
                                    <?= date('M d, Y', strtotime($p['requested_at'] ?? $p['created_at'] ?? 'now')) ?>
                                    <span class="block text-[11px] opacity-75"><?= date('h:i A', strtotime($p['requested_at'] ?? $p['created_at'] ?? 'now')) ?></span>
                                </td>

                                <!-- Actions -->
                                <td class="py-md px-md text-right whitespace-nowrap">
                                    <?php if ($status === 'pending'): ?>
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button"
                                                    onclick="openProcessModal(<?= (int)$p['id'] ?>, '<?= esc($p['reference_number'] ?? ('WD-'.$p['id'])) ?>', '<?= esc(addslashes($p['shop_name'] ?? 'Merchant')) ?>', '<?= esc(addslashes($recipientName)) ?>', '<?= esc($maskedPhone) ?>', '<?= number_format($gross, 2) ?>', '<?= number_format($fee, 2) ?>', '<?= number_format($net, 2) ?>')"
                                                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-primary text-on-primary hover:bg-primary/90 transition-all flex items-center gap-1 shadow-sm">
                                                <span class="material-symbols-outlined text-[15px]">rate_review</span>
                                                <span>Process</span>
                                            </button>
                                            <button type="button"
                                                    onclick="openRejectModal(<?= (int)$p['id'] ?>, '<?= esc($p['reference_number'] ?? ('WD-'.$p['id'])) ?>', '<?= esc(addslashes($p['shop_name'] ?? 'Merchant')) ?>', '<?= number_format($gross, 2) ?>')"
                                                    class="px-2 py-1.5 rounded-lg text-xs font-semibold bg-surface-container-high text-error hover:bg-error/10 transition-all"
                                                    title="Reject Request">
                                                <span class="material-symbols-outlined text-[15px]">close</span>
                                            </button>
                                        </div>

                                    <?php elseif ($status === 'processing'): ?>
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button"
                                                    onclick="openSendModal(<?= (int)$p['id'] ?>, '<?= esc($p['reference_number'] ?? ('WD-'.$p['id'])) ?>', '<?= esc(addslashes($p['shop_name'] ?? 'Merchant')) ?>', '<?= esc(addslashes($ownerFullName)) ?>', '<?= esc(addslashes($recipientName)) ?>', '<?= esc($destNumber) ?>', '<?= esc($maskedPhone) ?>', '<?= number_format($net, 2) ?>')"
                                                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 transition-all flex items-center gap-1 shadow-sm">
                                                <span class="material-symbols-outlined text-[15px]">send</span>
                                                <span>Send to GCash</span>
                                            </button>
                                            <button type="button"
                                                    onclick="openRejectModal(<?= (int)$p['id'] ?>, '<?= esc($p['reference_number'] ?? ('WD-'.$p['id'])) ?>', '<?= esc(addslashes($p['shop_name'] ?? 'Merchant')) ?>', '<?= number_format($gross, 2) ?>')"
                                                    class="px-2 py-1.5 rounded-lg text-xs font-semibold bg-surface-container-high text-error hover:bg-error/10 transition-all"
                                                    title="Reject Request">
                                                <span class="material-symbols-outlined text-[15px]">close</span>
                                            </button>
                                        </div>

                                    <?php elseif ($status === 'transfer_pending'): ?>
                                        <div class="flex items-center justify-end gap-1.5">
                                            <form action="<?= base_url('admin/payments/sync-status') ?>" method="POST" class="inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="payout_id" value="<?= (int)$p['id'] ?>">
                                                <button type="submit"
                                                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-secondary/15 text-secondary border border-secondary/30 hover:bg-secondary/25 transition-all flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-[15px] animate-spin">refresh</span>
                                                    <span>Check Status</span>
                                                </button>
                                            </form>
                                        </div>

                                    <?php elseif ($status === 'failed'): ?>
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button"
                                                    onclick="openSendModal(<?= (int)$p['id'] ?>, '<?= esc($p['reference_number'] ?? ('WD-'.$p['id'])) ?>', '<?= esc(addslashes($p['shop_name'] ?? 'Merchant')) ?>', '<?= esc(addslashes($ownerFullName)) ?>', '<?= esc(addslashes($recipientName)) ?>', '<?= esc($destNumber) ?>', '<?= esc($maskedPhone) ?>', '<?= number_format($net, 2) ?>')"
                                                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-amber-600 text-white hover:bg-amber-700 transition-all flex items-center gap-1 shadow-sm"
                                                    title="Retry disbursement after resolving wallet balance or network error">
                                                <span class="material-symbols-outlined text-[15px]">replay</span>
                                                <span>Retry</span>
                                            </button>
                                            <button type="button"
                                                    onclick="openRejectModal(<?= (int)$p['id'] ?>, '<?= esc($p['reference_number'] ?? ('WD-'.$p['id'])) ?>', '<?= esc(addslashes($p['shop_name'] ?? 'Merchant')) ?>', '<?= number_format($gross, 2) ?>')"
                                                    class="px-2 py-1.5 rounded-lg text-xs font-semibold bg-surface-container-high text-error hover:bg-error/10 transition-all"
                                                    title="Reject and Restore Balance">
                                                <span class="material-symbols-outlined text-[15px]">close</span>
                                            </button>
                                        </div>

                                    <?php else: ?>
                                        <span class="text-xs text-on-surface-variant font-medium">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="py-xl text-center text-on-surface-variant">
                                <div class="flex flex-col items-center gap-xs">
                                    <span class="material-symbols-outlined text-4xl text-outline-variant">payments</span>
                                    <span class="text-sm font-semibold text-on-surface">No payout requests found</span>
                                    <span class="text-xs text-on-surface-variant">There are currently no requests matching your filter criteria.</span>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (isset($pager)): ?>
            <?php
            $total   = (int) $pager->getTotal('payments');
            $perPage = (int) ($per_page ?? ($pager ? $pager->getPerPage('payments') : 10));
            $cur     = (int) $pager->getCurrentPage('payments');
            $pages   = (int) $pager->getPageCount('payments');
            $start   = $total === 0 ? 0 : ($cur - 1) * $perPage + 1;
            $end     = min($cur * $perPage, $total);
            ?>
            <div class="px-6 py-4 bg-surface-container-low/30 flex justify-between items-center border-t border-outline-variant/20 flex-wrap gap-sm">
                <p class="text-xs text-on-surface-variant">Showing <?= number_format($start) ?> to <?= number_format($end) ?> of <?= number_format($total) ?> requests</p>
                <?php if ($pages > 1): ?>
                    <div class="flex items-center gap-xs">
                        <a class="p-sm rounded hover:bg-surface-container-high <?= $cur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('payments') ?>" title="Previous">
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
                            <a class="w-8 h-8 rounded flex items-center justify-center text-xs <?= $cur === $num ? 'bg-primary text-on-primary font-semibold' : 'hover:bg-surface-container-high text-on-surface' ?>" href="<?= $pager->getPageURI($num, 'payments') ?>"><?= $num ?></a>
                        <?php $prev = $num; endforeach; ?>
                        <a class="p-sm rounded hover:bg-surface-container-high <?= $cur >= $pages ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('payments') ?>" title="Next">
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="px-6 py-3 bg-surface-container-low/30 border-t border-outline-variant/20 text-xs text-on-surface-variant">Showing <?= count($payment_requests ?? []) ?> requests</div>
        <?php endif; ?>
    </section>

</div>

<!-- Modal 1: Step 1 Process Review Modal (Strictly Internal - NO PayMongo Transfer) -->
<div id="processModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest rounded-2xl p-xl max-w-lg w-full space-y-md border border-outline-variant/30 shadow-2xl">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">
            <h3 class="text-title-lg font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">rate_review</span>
                <span>Review Payout Request</span>
            </h3>
            <button type="button" onclick="closeModal('processModal')" class="text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="p-3 bg-primary-container/15 border border-primary/25 rounded-xl text-xs text-on-surface-variant space-y-1">
            <div class="font-bold text-primary flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">info</span> Step 1: Internal Balance Audit
            </div>
            <p>Moving this request to <strong>In Progress (processing)</strong> marks it as verified by an administrator. <strong>No funds will be sent yet.</strong> You can execute the PayMongo transfer in the next step.</p>
        </div>

        <div class="p-3 bg-surface-container-low border border-outline-variant/25 rounded-xl space-y-2 text-xs">
            <div class="flex justify-between"><span class="text-on-surface-variant">Reference:</span><span id="processRef" class="font-mono font-bold text-primary">#WD-...</span></div>
            <div class="flex justify-between"><span class="text-on-surface-variant">Merchant:</span><span id="processShop" class="font-semibold text-on-surface">Shop</span></div>
            <div class="flex justify-between"><span class="text-on-surface-variant">GCash Recipient:</span><span id="processRecipient" class="font-semibold text-on-surface">Name</span></div>
            <div class="flex justify-between"><span class="text-on-surface-variant">GCash Mobile:</span><span id="processPhone" class="font-mono text-on-surface">09••••••</span></div>
            <div class="flex justify-between border-t border-outline-variant/20 pt-2"><span class="text-on-surface-variant">Gross Amount:</span><span id="processGross" class="font-mono font-semibold">₱0.00</span></div>
            <div class="flex justify-between text-amber-800"><span class="text-on-surface-variant">Platform Admin Fee:</span><span id="processFee" class="font-mono font-semibold">-₱0.00</span></div>
            <div class="flex justify-between text-on-surface font-bold text-sm border-t border-outline-variant/20 pt-2"><span>Net Disbursed:</span><span id="processNet" class="font-mono text-emerald-700">₱0.00</span></div>
        </div>

        <form action="<?= base_url('admin/payments/process') ?>" method="POST" class="space-y-md">
            <?= csrf_field() ?>
            <input type="hidden" name="payout_id" id="processPayoutId">
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('processModal')" class="px-md py-sm rounded-xl text-xs font-semibold text-on-surface-variant hover:bg-surface-container-high transition-all">Cancel</button>
                <button type="submit" class="px-xl py-sm rounded-xl text-xs font-bold bg-primary text-on-primary hover:bg-primary/90 transition-all shadow-md flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">check</span>
                    <span>Confirm & Mark as In Progress</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Step 2 Send to GCash Modal (Calls PayMongo API with Confirmation Warning) -->
<div id="sendModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest rounded-2xl p-xl max-w-lg w-full space-y-md border border-outline-variant/30 shadow-2xl">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">
            <h3 class="text-title-lg font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600">send</span>
                <span>Send to GCash (PayMongo)</span>
            </h3>
            <button type="button" onclick="closeModal('sendModal')" class="text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Caution Alert -->
        <div class="p-3.5 bg-amber-500/15 border border-amber-500/30 rounded-xl text-xs text-amber-900 space-y-1">
            <div class="font-bold flex items-center gap-1.5 text-amber-800">
                <span class="material-symbols-outlined text-[18px]">warning</span> Caution: Irreversible Transfer Action
            </div>
            <p>You are about to initiate an <strong>instant fund transfer</strong> from the platform's PayMongo account directly to the merchant's GCash mobile wallet. Ensure the recipient snapshot matches the merchant's verified information.</p>
        </div>

        <!-- Transfer Breakdown Card -->
        <div class="p-3.5 bg-surface-container-low border border-outline-variant/25 rounded-xl space-y-2.5 text-xs">
            <div class="flex justify-between"><span class="text-on-surface-variant">Reference:</span><span id="sendRef" class="font-mono font-bold text-primary">#WD-...</span></div>
            <div class="flex justify-between"><span class="text-on-surface-variant">Shop:</span><span id="sendShop" class="font-semibold text-on-surface">Shop</span></div>
            <div class="flex justify-between"><span class="text-on-surface-variant">Owner:</span><span id="sendOwner" class="text-on-surface">Owner</span></div>
            <div class="border-t border-outline-variant/20 pt-2 space-y-2">
                <div class="flex justify-between"><span class="text-on-surface-variant">Destination Institution:</span><span class="font-semibold text-on-surface">G-Xchange, Inc. (GCash)</span></div>
                <div class="flex justify-between"><span class="text-on-surface-variant">Recipient Name:</span><span id="sendRecipient" class="font-bold text-on-surface">Recipient Name</span></div>
                <div class="flex justify-between"><span class="text-on-surface-variant">GCash Mobile:</span><span id="sendPhone" class="font-mono font-bold text-primary">09••••••</span></div>
            </div>
            <div class="border-t border-outline-variant/20 pt-2.5 flex items-center justify-between">
                <div>
                    <span class="text-label-sm font-bold text-on-surface block">Net Amount to Disburse:</span>
                    <span class="text-[11px] text-on-surface-variant">Deductions already withheld</span>
                </div>
                <span id="sendNet" class="font-mono text-headline-sm font-bold text-emerald-700">₱0.00</span>
            </div>
        </div>

        <form action="<?= base_url('admin/payments/send') ?>" method="POST" class="space-y-md">
            <?= csrf_field() ?>
            <input type="hidden" name="payout_id" id="sendPayoutId">

            <div class="flex items-center gap-2 p-2.5 bg-surface-container-high/40 rounded-xl border border-outline-variant/30">
                <input type="checkbox" id="sendConfirmCheck" required class="rounded border-outline text-emerald-600 focus:ring-emerald-600 h-4 w-4">
                <label for="sendConfirmCheck" class="text-xs text-on-surface font-medium cursor-pointer">
                    I confirm that the recipient snapshot is correct and authorize this transfer.
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('sendModal')" class="px-md py-sm rounded-xl text-xs font-semibold text-on-surface-variant hover:bg-surface-container-high transition-all">Cancel</button>
                <button type="submit" class="px-xl py-sm rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 transition-all shadow-md flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">send</span>
                    <span>Confirm & Disburse via GCash</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Reject Payout Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest rounded-2xl p-xl max-w-md w-full space-y-md border border-outline-variant/30 shadow-2xl">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">
            <h3 class="text-title-lg font-bold text-error flex items-center gap-2">
                <span class="material-symbols-outlined text-error">cancel</span>
                <span>Reject Withdrawal Request</span>
            </h3>
            <button type="button" onclick="closeModal('rejectModal')" class="text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="p-3 bg-error-container/20 border border-error/20 rounded-xl text-xs text-on-error-container space-y-1">
            <div class="font-bold flex items-center gap-1 text-error">
                <span class="material-symbols-outlined text-[16px]">restore</span> Accounting Rollback
            </div>
            <p>Rejecting will cancel the request and automatically restore the reserved gross amount (<strong id="rejectGross" class="font-mono">₱0.00</strong>) to the merchant's Available Balance.</p>
        </div>

        <form action="<?= base_url('admin/payments/reject') ?>" method="POST" class="space-y-md">
            <?= csrf_field() ?>
            <input type="hidden" name="payout_id" id="rejectPayoutId">

            <div>
                <label class="text-label-sm font-bold text-on-surface-variant block mb-1">Reason for Rejection <span class="text-error">*</span></label>
                <textarea name="rejection_reason" required rows="3" placeholder="Provide a reason for the merchant (e.g., GCash name mismatch, invalid number)..." class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl text-xs focus:ring-2 focus:ring-error focus:outline-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('rejectModal')" class="px-md py-sm rounded-xl text-xs font-semibold text-on-surface-variant hover:bg-surface-container-high transition-all">Cancel</button>
                <button type="submit" class="px-xl py-sm rounded-xl text-xs font-bold bg-error text-on-error hover:bg-error/90 transition-all shadow-md flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">block</span>
                    <span>Reject & Restore Balance</span>
                </button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.add('hidden');
    }

    function openProcessModal(id, ref, shop, recipient, phone, gross, fee, net) {
        document.getElementById('processPayoutId').value = id;
        document.getElementById('processRef').textContent = '#' + ref;
        document.getElementById('processShop').textContent = shop;
        document.getElementById('processRecipient').textContent = recipient;
        document.getElementById('processPhone').textContent = phone;
        document.getElementById('processGross').textContent = '₱' + gross;
        document.getElementById('processFee').textContent = '-₱' + fee;
        document.getElementById('processNet').textContent = '₱' + net;
        document.getElementById('processModal').classList.remove('hidden');
    }

    function openSendModal(id, ref, shop, owner, recipient, fullPhone, maskedPhone, net) {
        document.getElementById('sendPayoutId').value = id;
        document.getElementById('sendRef').textContent = '#' + ref;
        document.getElementById('sendShop').textContent = shop;
        document.getElementById('sendOwner').textContent = owner || '—';
        document.getElementById('sendRecipient').textContent = recipient;
        document.getElementById('sendPhone').textContent = maskedPhone;
        document.getElementById('sendNet').textContent = '₱' + net;
        const check = document.getElementById('sendConfirmCheck');
        if (check) check.checked = false;
        document.getElementById('sendModal').classList.remove('hidden');
    }

    function openRejectModal(id, ref, shop, gross) {
        document.getElementById('rejectPayoutId').value = id;
        document.getElementById('rejectGross').textContent = '₱' + gross;
        document.getElementById('rejectModal').classList.remove('hidden');
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal('processModal');
            closeModal('sendModal');
            closeModal('rejectModal');
        }
    });

    ['processModal', 'sendModal', 'rejectModal'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('click', (e) => {
                if (e.target === el) closeModal(id);
            });
        }
    });
</script>
<?= $this->endSection() ?>
