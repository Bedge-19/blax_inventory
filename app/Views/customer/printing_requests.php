<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="p-md rounded-xl bg-green-100 text-green-800 text-sm font-medium mb-lg"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="p-md rounded-xl bg-error-container text-on-error-container text-sm font-medium mb-lg"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="flex flex-1 flex-col md:flex-row max-w-container-max mx-auto w-full">

    <?= view('components/profile_sidebar', ['activeNav' => 'printing']) ?>

    <!-- Main Content Area -->
    <main class="flex-1 p-md md:p-xl overflow-y-auto">

        <div class="flex flex-col gap-gutter">

            <?php if (!empty($requests)): ?>

                <nav class="flex items-center gap-lg border-b border-outline-variant/20 pb-2 mb-2">

                    <button class="pr-tab px-md py-2 text-label-sm font-semibold text-primary border-b-2 border-primary transition-all" data-filter="all" data-label="printing requests">All Printing</button>
                    <button class="pr-tab px-md py-2 text-label-sm font-medium text-on-surface-variant hover:text-primary transition-all" data-filter="active" data-label="active printing requests">Active</button>
                    <button class="pr-tab px-md py-2 text-label-sm font-medium text-on-surface-variant hover:text-primary transition-all" data-filter="completed" data-label="completed printing requests">Completed</button>
                    <button class="pr-tab px-md py-2 text-label-sm font-medium text-on-surface-variant hover:text-primary transition-all" data-filter="cancelled" data-label="cancelled printing requests">Cancelled</button>

                </nav>

                <?php foreach ($requests as $req): ?>

                    <?php
                        $reqStatus   = strtolower((string) ($req['status'] ?? 'new'));
                        $reqBinding  = $req['binding_option'] ?? 'none';
                        $reqService  = match ($reqBinding) { 'spiral' => 'Spiral Binding', 'hardcover' => 'Hardcover Binding', 'staple' => 'Staple Binding', default => 'Document Printing' };
                        $reqIcon     = match ($reqBinding) { 'hardcover' => 'menu_book', 'spiral' => 'auto_stories', default => 'description' };
                        $reqGroup    = in_array($reqStatus, ['new', 'in_production', 'ready_for_pickup', 'ready_for_delivery']) ? 'active' : $reqStatus;
                        $reqLabel    = humanize_status($reqStatus);
                        $reqPill     = match ($reqStatus) {
                            'ready_for_pickup', 'ready_for_delivery' => 'bg-green-100 text-green-700',
                            'in_production', 'new'                    => 'bg-primary/10 text-primary',
                            'completed'                              => 'bg-surface-container-highest text-on-surface-variant',
                            'cancelled'                              => 'bg-error/10 text-error',
                            default                                  => 'bg-surface-variant text-on-surface-variant',
                        };
                        $reqPriceLbl = match ($reqStatus) { 'completed' => 'Paid', 'cancelled' => 'Total', 'new', 'in_production' => 'Estimated Total', default => 'Total Price' };
                        $reqPriceCls = in_array($reqStatus, ['ready_for_pickup', 'ready_for_delivery']) ? 'text-primary' : 'text-on-surface-variant';
                        $reqPrice    = number_format((float) ($req['total_price'] ?? 0), 2);
                        $reqCopies   = (int) ($req['copies'] ?? 1);
                        $reqPages    = (int) ($req['page_count'] ?? 0);
                        $reqSpec     = implode(', ', array_filter([
                            $req['paper_stock'] ?? null,
                            ($reqPages > 0 ? $reqPages . ' Page' . ($reqPages !== 1 ? 's' : '') : null),
                            ($reqCopies > 1 ? $reqCopies . ' Copies' : null),
                            ($req['paper_size'] ?? null) ? strtoupper(($req['paper_size'] ?? 'A4')) : null,
                            $req['color_mode'] ?? null,
                            ($reqBinding !== 'none' ? $reqBinding : null),
                        ]));
                        $reqPrinter  = $req['printer_assigned'] ?? '';
                        $reqMethod   = (($req['fulfillment_method'] ?? 'pickup') === 'delivery') ? 'Delivery' : 'Pickup';
                        $reqCreated  = !empty($req['created_at']) ? date('M d, Y', strtotime($req['created_at'])) : '';
                        $reqDone     = !empty($req['completed_at']) ? date('M d, Y', strtotime($req['completed_at'])) : '';
                        $reqProgress = (int) ($req['progress_percent'] ?? 0);
                    ?>

                    <div class="pr-card bg-surface-container-lowest rounded-2xl border border-outline-variant/30 p-lg shadow-sm flex flex-col gap-md" data-group="<?= esc($reqGroup) ?>">

                        <div class="flex justify-between items-start">

                            <div class="flex flex-col">

                                <span class="text-label-sm text-outline font-medium tracking-tight">#<?= esc($req['request_number'] ?? ('PR-' . $req['id'])) ?></span>
                                <h3 class="text-headline-md font-bold text-on-surface mt-1"><?= esc($req['file_name'] ?? 'Document.pdf') ?></h3>

                            </div>

                            <span class="px-sm py-1 <?= esc($reqPill) ?> text-[10px] font-bold rounded-full uppercase tracking-wider"><?= esc($reqLabel) ?></span>

                        </div>

                        <div class="flex items-center gap-md py-md border-y border-outline-variant/10">

                            <div class="w-12 h-12 rounded-xl bg-primary/5 flex items-center justify-center">
                                <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;"><?= esc($reqIcon) ?></span>
                            </div>

                            <div>

                                <p class="text-body-md font-semibold text-on-surface"><?= esc($reqService) ?></p>
                                <p class="text-label-sm text-on-surface-variant"><?= esc($reqSpec) ?><?= !empty($req['shop_name']) ? ' · ' . esc($req['shop_name']) : '' ?></p>

                            </div>

                        </div>

                        <div class="flex justify-between items-end mt-sm gap-md">

                            <div class="flex flex-col gap-1">

                                <span class="text-[10px] text-outline uppercase font-bold tracking-widest"><?= esc($reqPriceLbl) ?></span>
                                <span class="text-headline-lg font-bold <?= esc($reqPriceCls) ?>">₱<?= esc($reqPrice) ?></span>

                            </div>

                            <?php if (in_array($reqStatus, ['new', 'in_production', 'ready_for_pickup', 'ready_for_delivery', 'completed'])): ?>

                                <div class="flex items-center gap-md">

                                    <?php if (($req['fulfillment_method'] ?? 'pickup') === 'pickup' && in_array($reqStatus, ['ready_for_pickup', 'completed'])): ?>

                                        <button type="button" class="pr-qr-btn flex items-center gap-xs px-xl py-md bg-primary text-on-primary rounded-xl text-button font-button hover:bg-primary-container transition-all active:scale-95 shadow-lg shadow-primary/20" data-number="<?= esc($req['request_number'] ?? ('PR-' . $req['id'])) ?>">
                                            <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                                            Pick-up QR
                                        </button>

                                    <?php endif; ?>

                                    <?php if (in_array($reqStatus, ['new', 'in_production', 'ready_for_delivery'])): ?>

                                        <button type="button" class="pr-track flex items-center gap-xs px-xl py-md border border-primary/30 text-primary rounded-xl text-button font-button hover:bg-primary/5 transition-all active:scale-95" data-number="<?= esc($req['request_number'] ?? ('PR-' . $req['id'])) ?>" data-name="<?= esc($req['file_name'] ?? 'Document.pdf') ?>" data-status="<?= esc($reqStatus) ?>" data-status-label="<?= esc($reqLabel) ?>" data-progress="<?= esc($reqProgress) ?>" data-printer="<?= esc($reqPrinter) ?>" data-method="<?= esc($reqMethod) ?>" data-created="<?= esc($reqCreated) ?>" data-completed="<?= esc($reqDone) ?>">
                                            <span class="material-symbols-outlined text-[18px]">monitoring</span>
                                            Track Status
                                        </button>

                                    <?php endif; ?>



                                    <?php if (in_array($reqStatus, ['new', 'in_production', 'ready_for_pickup', 'ready_for_delivery'])): ?>

                                        <form method="post" action="<?= base_url('printing/cancel') ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="request_id" value="<?= esc($req['id']) ?>">
                                            <button type="submit" <?= $reqStatus !== 'new' ? 'disabled' : '' ?> class="flex items-center gap-xs px-xl py-md border border-error/30 rounded-xl text-button font-button transition-all active:scale-95 <?= $reqStatus === 'new' ? 'text-error hover:bg-error/10' : 'opacity-40 grayscale cursor-not-allowed' ?>">
                                                <span class="material-symbols-outlined text-[18px]">close</span>
                                                Cancel Request
                                            </button>
                                        </form>

                                    <?php endif; ?>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

                <div id="pr-empty" class="hidden bg-surface-container-lowest rounded-2xl border border-outline-variant/30 p-xl text-center text-on-surface-variant">

                    <span class="material-symbols-outlined text-4xl text-outline mb-2">print</span>
                    <p>No <span id="pr-empty-label">printing requests</span> found.</p>

                </div>

            <?php else: ?>

                <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 p-xl text-center text-on-surface-variant">

                    <span class="material-symbols-outlined text-4xl text-outline mb-2">print</span>
                    <p>No custom printing requests found.</p>

                </div>

            <?php endif; ?>

        </div>

    </main>

</div>

<button type="button" class="fixed bottom-8 left-8 w-16 h-16 bg-surface-container-highest text-on-surface rounded-full shadow-lg border border-outline-variant/30 flex items-center justify-center hover:scale-110 transition-transform active:scale-95 z-50 group" aria-label="Go Back" onclick="window.history.back()">
    <span class="material-symbols-outlined text-[32px] group-hover:-translate-x-1 transition-transform">arrow_back</span>
</button>

<div id="track-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-md">
    <div class="absolute inset-0 bg-black/40" id="track-overlay"></div>
    <div class="relative bg-surface-container-lowest rounded-2xl border border-outline-variant/30 shadow-xl w-full max-w-md p-lg flex flex-col gap-md">
        <div class="flex justify-between items-start gap-md">
            <div class="flex flex-col">
                <span class="text-label-sm text-outline font-medium tracking-tight" id="track-number">#PR-00000</span>
                <h3 class="text-headline-md font-bold text-on-surface mt-1" id="track-name">Document.pdf</h3>
            </div>
            <button type="button" id="track-close" class="p-2 rounded-full hover:bg-surface-container-high text-on-surface-variant">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <span id="track-status" class="self-start px-sm py-1 bg-primary/10 text-primary text-[10px] font-bold rounded-full uppercase tracking-wider">In Production</span>
        <div class="flex flex-col gap-1">
            <div class="flex justify-between items-center">
                <span class="text-[10px] text-outline uppercase font-bold tracking-widest">Progress</span>
                <span class="text-label-sm font-semibold text-on-surface" id="track-progress">0%</span>
            </div>
            <div class="w-full h-2 bg-surface-container-highest rounded-full overflow-hidden">
                <div id="track-progress-bar" class="h-2 bg-primary rounded-full transition-all" style="width:0%"></div>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-md">
            <div class="flex flex-col gap-1">
                <span class="text-[10px] text-outline uppercase font-bold tracking-widest">Printer</span>
                <span class="text-body-md font-semibold text-on-surface" id="track-printer">Not assigned yet</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-[10px] text-outline uppercase font-bold tracking-widest">Fulfillment</span>
                <span class="text-body-md font-semibold text-on-surface" id="track-method">Pickup</span>
            </div>
        </div>
        <div class="flex flex-col gap-1">
            <span class="text-[10px] text-outline uppercase font-bold tracking-widest">Requested On</span>
            <span class="text-body-md font-semibold text-on-surface" id="track-created">—</span>
        </div>
        <div id="track-completed-row" class="flex flex-col gap-1 hidden">
            <span class="text-[10px] text-outline uppercase font-bold tracking-widest">Completed On</span>
            <span class="text-body-md font-semibold text-on-surface" id="track-completed">—</span>
        </div>
        <button type="button" id="track-done" class="self-end px-xl py-md bg-primary text-on-primary rounded-xl text-button font-button hover:bg-primary-container transition-all active:scale-95 shadow-lg shadow-primary/20">Close</button>
    </div>
</div>

<!-- Pick-up QR Modal -->
<div id="qr-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-md">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="qr-overlay"></div>
    <div class="relative bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-2xl w-full max-w-sm p-xl flex flex-col items-center gap-lg text-center z-10">
        <div class="flex justify-between items-center w-full border-b border-outline-variant/20 pb-md">
            <div class="flex items-center gap-xs text-primary font-bold">
                <span class="material-symbols-outlined text-2xl">qr_code_2</span>
                <span class="text-title-md">Pick-up QR Code</span>
            </div>
            <button type="button" id="qr-close" class="p-1 rounded-full hover:bg-surface-container-high text-on-surface-variant transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <div class="flex flex-col items-center justify-center bg-white p-6 rounded-2xl shadow-inner border border-slate-100">
            <div id="qr-canvas-container" class="flex justify-center items-center min-w-[200px] min-h-[200px]"></div>
        </div>
        
        <div class="flex flex-col items-center">
            <span class="text-[10px] uppercase tracking-widest text-outline font-bold">Request Number</span>
            <span id="qr-request-number" class="text-headline-sm font-mono font-bold text-primary mt-0.5">#PR-00000</span>
            <p class="text-xs text-on-surface-variant/80 mt-2 leading-relaxed">Present this QR code to the store attendant upon pick-up.</p>
        </div>

        <button type="button" id="qr-done" class="w-full py-md bg-primary text-on-primary rounded-xl text-button font-button hover:bg-primary-container transition-all active:scale-95 shadow-md">Close</button>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<?php if (!empty($requests)): ?>
    <script>
        (function () {
            var tabs = document.querySelectorAll('.pr-tab');
            var cards = document.querySelectorAll('.pr-card');
            var empty = document.getElementById('pr-empty');
            var emptyLabel = document.getElementById('pr-empty-label');

            tabs.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var filter = btn.dataset.filter;

                    tabs.forEach(function (b) {
                        b.classList.remove('text-primary', 'border-b-2', 'border-primary', 'font-semibold');
                        b.classList.add('text-on-surface-variant', 'font-medium');
                    });
                    btn.classList.add('text-primary', 'border-b-2', 'border-primary', 'font-semibold');
                    btn.classList.remove('text-on-surface-variant', 'font-medium');

                    var visible = 0;
                    cards.forEach(function (card) {
                        var show = filter === 'all' || card.dataset.group === filter;
                        card.classList.toggle('hidden', !show);
                        if (show) visible++;
                    });

                    if (empty) {
                        var showEmpty = visible === 0;
                        empty.classList.toggle('hidden', !showEmpty);
                        if (showEmpty && emptyLabel) emptyLabel.textContent = btn.dataset.label;
                    }
                });
            });

            var trackBtn = document.querySelectorAll('.pr-track');
            var trackModal = document.getElementById('track-modal');
            var trackOverlay = document.getElementById('track-overlay');
            var pillMap = {
                ready_for_pickup: 'bg-green-100 text-green-700',
                ready_for_delivery: 'bg-green-100 text-green-700',
                in_production: 'bg-primary/10 text-primary',
                new: 'bg-primary/10 text-primary',
                completed: 'bg-surface-container-highest text-on-surface-variant',
                cancelled: 'bg-error/10 text-error'
            };

            function openTrack(btn) {
                if (!trackModal) return;
                document.getElementById('track-number').textContent = '#' + (btn.dataset.number || '');
                document.getElementById('track-name').textContent = btn.dataset.name || '';

                var statusEl = document.getElementById('track-status');
                statusEl.textContent = btn.dataset.statusLabel || '';
                statusEl.className = 'self-start px-sm py-1 text-[10px] font-bold rounded-full uppercase tracking-wider ' + (pillMap[btn.dataset.status] || 'bg-surface-variant text-on-surface-variant');

                var progress = parseInt(btn.dataset.progress || '0', 10);
                document.getElementById('track-progress').textContent = progress + '%';
                document.getElementById('track-progress-bar').style.width = progress + '%';
                document.getElementById('track-printer').textContent = btn.dataset.printer || 'Not assigned yet';
                document.getElementById('track-method').textContent = btn.dataset.method || 'Pickup';
                document.getElementById('track-created').textContent = btn.dataset.created || '—';

                var completedRow = document.getElementById('track-completed-row');
                if (btn.dataset.completed) {
                    completedRow.classList.remove('hidden');
                    document.getElementById('track-completed').textContent = btn.dataset.completed;
                } else {
                    completedRow.classList.add('hidden');
                }

                trackModal.classList.remove('hidden');
            }

            function closeTrack() {
                if (trackModal) trackModal.classList.add('hidden');
            }

            trackBtn.forEach(function (btn) {
                btn.addEventListener('click', function () { openTrack(btn); });
            });
            if (trackOverlay) trackOverlay.addEventListener('click', closeTrack);
            var trackClose = document.getElementById('track-close');
            if (trackClose) trackClose.addEventListener('click', closeTrack);
            var trackDone = document.getElementById('track-done');
            if (trackDone) trackDone.addEventListener('click', closeTrack);

            // --- QR Modal Handlers ---
            var qrModal = document.getElementById('qr-modal');
            var qrOverlay = document.getElementById('qr-overlay');
            var qrClose = document.getElementById('qr-close');
            var qrDone = document.getElementById('qr-done');
            var qrContainer = document.getElementById('qr-canvas-container');
            var qrNumEl = document.getElementById('qr-request-number');

            function openQrModal(reqNum) {
                if (!qrModal) return;
                qrNumEl.textContent = '#' + reqNum;
                qrContainer.innerHTML = '';

                if (window.QRCode) {
                    new QRCode(qrContainer, {
                        text: reqNum,
                        width: 200,
                        height: 200,
                        colorDark: "#0f172a",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } else {
                    qrContainer.innerHTML = '<p class="text-error text-xs">QR engine unavailable.</p>';
                }

                qrModal.classList.remove('hidden');
            }

            function closeQrModal() {
                if (qrModal) qrModal.classList.add('hidden');
            }

            document.querySelectorAll('.pr-qr-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openQrModal(btn.dataset.number);
                });
            });

            if (qrOverlay) qrOverlay.addEventListener('click', closeQrModal);
            if (qrClose) qrClose.addEventListener('click', closeQrModal);
            if (qrDone) qrDone.addEventListener('click', closeQrModal);
        })();
    </script>
<?php endif; ?>

<?= $this->endSection() ?>