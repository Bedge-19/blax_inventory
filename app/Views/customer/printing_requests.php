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
                        $reqGroup    = in_array($reqStatus, ['new', 'in_production', 'ready_for_pickup', 'ready_for_delivery', 'in_transit']) ? 'active' : $reqStatus;
                        $reqLabel    = humanize_status($reqStatus);
                        $reqPill     = match ($reqStatus) {
                            'ready_for_pickup', 'ready_for_delivery' => 'bg-green-100 text-green-700',
                            'in_transit'                             => 'bg-blue-100 text-blue-700',
                            'in_production', 'new'                   => 'bg-primary/10 text-primary',
                            'completed'                              => 'bg-surface-container-highest text-on-surface-variant',
                            'cancelled'                              => 'bg-error/10 text-error',
                            default                                  => 'bg-surface-variant text-on-surface-variant',
                        };
                        $reqPriceLbl = match ($reqStatus) { 'completed' => 'Paid', 'cancelled' => 'Total', 'new', 'in_production' => 'Estimated Total', default => 'Total Price' };
                        $reqPriceCls = in_array($reqStatus, ['ready_for_pickup', 'ready_for_delivery', 'in_transit']) ? 'text-primary' : 'text-on-surface-variant';
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
                        $isPickup    = (($req['fulfillment_method'] ?? 'pickup') === 'pickup');
                        $reqMethod   = $isPickup ? 'Pickup' : 'Delivery';
                        $reqCreated  = !empty($req['created_at']) ? date('M d, Y', strtotime($req['created_at'])) : '';
                        $reqDone     = !empty($req['completed_at']) ? date('M d, Y', strtotime($req['completed_at'])) : '';
                        $reqProgress = (int) ($req['progress_percent'] ?? 0);
                        $shopName    = !empty($req['shop_name']) ? $req['shop_name'] : 'Printing Shop';
                        $shopAddress = !empty($req['shop_address']) ? $req['shop_address'] : 'Poblacion, Polomolok';
                        $isPaid      = !empty($req['payment_status']) && strtolower($req['payment_status']) === 'paid';
                    ?>

                    <div class="pr-card bg-surface-container-lowest rounded-2xl border border-outline-variant/30 p-lg shadow-sm flex flex-col gap-md" data-group="<?= esc($reqGroup) ?>">

                        <!-- Top row: Ref #, Title, Badges -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">

                            <div class="flex flex-col">
                                <span class="text-label-sm text-outline font-medium tracking-tight">#<?= esc($req['request_number'] ?? ('PR-' . $req['id'])) ?></span>
                                <h3 class="text-headline-md font-bold text-on-surface mt-0.5"><?= esc($req['file_name'] ?? 'Document.pdf') ?></h3>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap">
                                <!-- Fulfillment Method Badge -->
                                <?php if ($isPickup): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/15 text-amber-900 border border-amber-500/30 shadow-2xs">
                                        <span class="material-symbols-outlined text-[14px]">storefront</span>
                                        <span>Store Pick-up</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-500/15 text-blue-900 border border-blue-500/30 shadow-2xs">
                                        <span class="material-symbols-outlined text-[14px]">local_shipping</span>
                                        <span>Doorstep Delivery</span>
                                    </span>
                                <?php endif; ?>

                                <!-- Status Pill -->
                                <span class="px-sm py-1 <?= esc($reqPill) ?> text-[10px] font-bold rounded-full uppercase tracking-wider"><?= esc($reqLabel) ?></span>
                            </div>

                        </div>

                        <!-- Middle row: Icon, Service, Spec, Shop -->
                        <div class="flex items-center gap-md py-md border-y border-outline-variant/10">

                            <div class="w-12 h-12 rounded-xl bg-primary/5 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;"><?= esc($reqIcon) ?></span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="text-body-md font-semibold text-on-surface truncate"><?= esc($reqService) ?></p>
                                <p class="text-label-sm text-on-surface-variant truncate"><?= esc($reqSpec) ?> · <span class="font-semibold text-on-surface"><?= esc($shopName) ?></span></p>
                            </div>

                        </div>

                        <!-- Fulfillment Progress Stepper -->
                        <?php if ($reqStatus !== 'cancelled'): ?>
                            <?php
                                // Define stepper steps based on fulfillment method
                                if ($isPickup) {
                                    $prSteps = [
                                        ['key' => 'new',              'label' => 'Placed'],
                                        ['key' => 'in_production',    'label' => 'Processing'],
                                        ['key' => 'ready_for_pickup', 'label' => 'Ready'],
                                        ['key' => 'completed',        'label' => 'Completed'],
                                    ];
                                } else {
                                    $prSteps = [
                                        ['key' => 'new',                'label' => 'Placed'],
                                        ['key' => 'in_production',      'label' => 'Processing'],
                                        ['key' => 'ready_for_delivery', 'label' => 'Shipped'],
                                        ['key' => 'completed',          'label' => 'Completed'],
                                    ];
                                }

                                // Build an order map: step key => numeric index
                                $prStepOrder = [];
                                foreach ($prSteps as $si => $st) { $prStepOrder[$st['key']] = $si; }
                                // Map in_transit to the same index as ready_for_delivery
                                $prStepOrder['in_transit'] = $prStepOrder['ready_for_delivery'] ?? 2;

                                $prCurrentIdx = $prStepOrder[$reqStatus] ?? 0;
                            ?>
                            <div class="py-md">
                                <div class="flex items-center w-full">
                                    <?php foreach ($prSteps as $si => $st):
                                        $isDone   = ($si < $prCurrentIdx) || ($reqStatus === 'completed');
                                        $isActive = ($si === $prCurrentIdx) && ($reqStatus !== 'completed');
                                    ?>
                                        <?php if ($si > 0): ?>
                                            <!-- Connector line -->
                                            <div class="flex-1 h-[3px] mx-0.5 rounded-full transition-all duration-500 <?= ($isDone || ($isActive && $si <= $prCurrentIdx) || $reqStatus === 'completed') ? 'bg-blue-600' : 'bg-outline-variant/30' ?>"></div>
                                        <?php endif; ?>

                                        <!-- Step node -->
                                        <div class="flex flex-col items-center gap-1 shrink-0">
                                            <?php if ($isDone): ?>
                                                <!-- Completed step -->
                                                <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-sm ring-2 ring-blue-600/20">
                                                    <span class="material-symbols-outlined text-[16px]">check</span>
                                                </div>
                                            <?php elseif ($isActive): ?>
                                                <!-- Active step -->
                                                <div class="relative w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-sm">
                                                    <span class="absolute w-full h-full rounded-full bg-blue-500 animate-ping opacity-30"></span>
                                                    <span class="w-3 h-3 rounded-full bg-white relative z-10"></span>
                                                </div>
                                            <?php else: ?>
                                                <!-- Pending step -->
                                                <div class="w-8 h-8 rounded-full bg-surface-container-high border-2 border-outline-variant/50 flex items-center justify-center">
                                                    <span class="w-2 h-2 rounded-full bg-outline-variant/60"></span>
                                                </div>
                                            <?php endif; ?>
                                            <span class="text-[10px] font-bold <?= $isDone ? 'text-blue-700' : ($isActive ? 'text-blue-600' : 'text-on-surface-variant/60') ?> whitespace-nowrap"><?= esc($st['label']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Bottom row: Price & Action Buttons -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mt-sm gap-md">

                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] text-outline uppercase font-bold tracking-widest"><?= esc($reqPriceLbl) ?></span>
                                <span class="text-headline-lg font-bold <?= esc($reqPriceCls) ?>">₱<?= esc($reqPrice) ?></span>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-start sm:justify-end">

                                <!-- Rate Service Button: for completed requests -->
                                <?php if ($reqStatus === 'completed'): ?>
                                    <button type="button" 
                                            class="rate-pr-btn bg-amber-500 hover:bg-amber-600 text-white py-2 px-3.5 rounded-xl font-button text-xs transition-colors flex items-center justify-center gap-1.5 shadow-sm active:scale-95 cursor-pointer"
                                            data-req='<?= esc(json_encode($req), 'attr') ?>'>
                                        <span class="material-symbols-outlined text-[16px]">star</span>
                                        <span>Rate Shop & Service</span>
                                    </button>
                                <?php endif; ?>

                                <!-- Store Pick-up Actions -->
                                <?php if ($isPickup && $reqStatus !== 'cancelled'): ?>
                                    <?php if (in_array($reqStatus, ['ready_for_pickup', 'completed'], true)): ?>
                                        <button type="button" 
                                                class="pr-qr-btn bg-primary text-on-primary py-2 px-3.5 rounded-xl font-button text-xs hover:bg-primary/90 transition-all flex items-center justify-center gap-1.5 shadow-sm active:scale-95 cursor-pointer" 
                                                data-number="<?= esc($req['request_number'] ?? ('PR-' . $req['id'])) ?>" 
                                                data-name="<?= esc($req['file_name'] ?? 'Document.pdf') ?>"
                                                data-shop="<?= esc($shopName) ?>"
                                                data-location="<?= esc($shopAddress) ?>"
                                                data-paid="<?= $isPaid ? 'PAID' : 'PAY AT COUNTER' ?>">
                                            <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                                            <span>View Pick-up QR</span>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-xs text-on-surface-variant/70 font-medium py-2 px-3 bg-surface-container/70 border border-outline-variant/30 rounded-xl flex items-center justify-center gap-1">
                                            <span class="material-symbols-outlined text-[15px] text-outline">schedule</span>
                                            <span>QR available when ready</span>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- Doorstep Delivery Actions -->
                                <?php if (!$isPickup && $reqStatus !== 'cancelled'): ?>
                                    <?php if (in_array($reqStatus, ['ready_for_delivery', 'in_transit', 'completed', 'delivered'], true)): ?>
                                        <a href="<?= base_url('customer/printing/track/' . esc($req['request_number'] ?? $req['id'])) ?>" 
                                           class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-sm transition gap-1.5 active:scale-95 cursor-pointer">
                                            <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                                            <span>Track Delivery</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-on-surface-variant/70 font-medium py-2 px-3 bg-surface-container/70 border border-outline-variant/30 rounded-xl flex items-center justify-center gap-1">
                                            <span class="material-symbols-outlined text-[15px] text-outline">schedule</span>
                                            <span>Tracking available once dispatched</span>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- Track Status Popup Button -->
                                <?php if (in_array($reqStatus, ['new', 'in_production', 'ready_for_delivery', 'ready_for_pickup', 'in_transit'])): ?>
                                    <button type="button" 
                                            class="pr-track flex items-center gap-1 px-3 py-2 border border-outline-variant/50 text-on-surface hover:bg-surface-container rounded-xl text-xs font-bold transition-all active:scale-95 cursor-pointer" 
                                            data-number="<?= esc($req['request_number'] ?? ('PR-' . $req['id'])) ?>" 
                                            data-name="<?= esc($req['file_name'] ?? 'Document.pdf') ?>" 
                                            data-status="<?= esc($reqStatus) ?>" 
                                            data-status-label="<?= esc($reqLabel) ?>" 
                                            data-progress="<?= esc($reqProgress) ?>" 
                                            data-printer="<?= esc($reqPrinter) ?>" 
                                            data-method="<?= esc($reqMethod) ?>" 
                                            data-created="<?= esc($reqCreated) ?>" 
                                            data-completed="<?= esc($reqDone) ?>">
                                        <span class="material-symbols-outlined text-[16px] text-primary">monitoring</span>
                                        <span>Status</span>
                                    </button>
                                <?php endif; ?>

                                <!-- Cancel Request Button: only visible if new or in_production -->
                                <?php if (in_array($reqStatus, ['new', 'in_production'], true)): ?>
                                    <button type="button" 
                                            class="cancel-pr-btn border border-error/50 text-error hover:bg-error/10 py-2 px-3 rounded-xl font-button text-xs transition-colors flex items-center justify-center gap-1 active:scale-95 cursor-pointer"
                                            data-id="<?= (int) $req['id'] ?>"
                                            data-number="<?= esc($req['request_number'] ?? ('PR-' . $req['id'])) ?>">
                                        <span class="material-symbols-outlined text-[16px]">close</span>
                                        <span>Cancel Request</span>
                                    </button>
                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

                <div id="pr-empty" class="hidden bg-surface-container-lowest rounded-2xl border border-outline-variant/30 p-xl text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-4xl text-outline mb-2">print</span>
                    <p>No <span id="pr-empty-label">printing requests</span> found.</p>
                </div>

            <?php else: ?>

                <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 p-xl text-center text-on-surface-variant flex flex-col items-center">
                    <div class="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center mb-md">
                        <span class="material-symbols-outlined text-3xl">print</span>
                    </div>
                    <h3 class="text-title-lg font-bold text-on-surface mb-xs">No printing requests yet</h3>
                    <p class="text-body-md text-on-surface-variant max-w-md mb-lg">When you request custom document printing or bookbinding, you will be able to track live progress and store pick-up passes right here.</p>
                    <a href="<?= base_url('printing-services') ?>" class="inline-flex items-center gap-xs bg-primary text-on-primary px-lg py-md rounded-xl font-button hover:bg-primary-container transition-all shadow-sm">
                        <span class="material-symbols-outlined text-[20px]">storefront</span>
                        <span>Find Printing Shops</span>
                    </a>
                </div>

            <?php endif; ?>

        </div>

    </main>

</div>

<button type="button" class="fixed bottom-8 left-8 w-16 h-16 bg-surface-container-highest text-on-surface rounded-full shadow-lg border border-outline-variant/30 flex items-center justify-center hover:scale-110 transition-transform active:scale-95 z-50 group" aria-label="Go Back" onclick="window.history.back()">
    <span class="material-symbols-outlined text-[32px] group-hover:-translate-x-1 transition-transform">arrow_back</span>
</button>

<!-- Track Progress Modal -->
<div id="track-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-md">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-xs" id="track-overlay"></div>
    <div class="relative bg-surface-container-lowest rounded-2xl border border-outline-variant/30 shadow-xl w-full max-w-md p-lg flex flex-col gap-md z-10">
        <div class="flex justify-between items-start gap-md">
            <div class="flex flex-col">
                <span class="text-label-sm text-outline font-medium tracking-tight" id="track-number">#PR-00000</span>
                <h3 class="text-headline-md font-bold text-on-surface mt-1" id="track-name">Document.pdf</h3>
            </div>
            <button type="button" id="track-close" class="p-2 rounded-full hover:bg-surface-container-high text-on-surface-variant cursor-pointer">
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
        <button type="button" id="track-done" class="self-end px-xl py-md bg-primary text-on-primary rounded-xl text-button font-button hover:bg-primary-container transition-all active:scale-95 shadow-md cursor-pointer">Close</button>
    </div>
</div>

<!-- Pick-up QR Modal (Store Pick-up Pass) -->
<div id="qr-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-md">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="qr-overlay"></div>
    <div class="relative bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-2xl w-full max-w-sm p-xl flex flex-col items-center gap-md text-center z-10">
        <div class="flex justify-between items-center w-full border-b border-outline-variant/20 pb-md">
            <div class="flex items-center gap-xs text-primary font-bold">
                <span class="material-symbols-outlined text-2xl">qr_code_2</span>
                <span class="text-title-md">Store Pick-up Pass</span>
            </div>
            <button type="button" id="qr-close" class="p-1 rounded-full hover:bg-surface-container-high text-on-surface-variant transition-colors cursor-pointer">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <div class="flex flex-col items-center justify-center bg-white p-6 rounded-2xl shadow-inner border border-slate-100 w-full max-w-[240px] aspect-square mx-auto">
            <div id="qr-canvas-container" class="flex justify-center items-center w-full h-full"></div>
        </div>
        
        <div class="flex flex-col items-center w-full space-y-1">
            <span class="text-[10px] uppercase tracking-widest text-outline font-bold">Request Identifier</span>
            <span id="qr-request-number" class="text-headline-sm font-mono font-bold text-primary">#PR-00000</span>
            
            <div class="flex items-center justify-center gap-2 mt-1">
                <span id="qr-shop" class="text-xs font-semibold text-on-surface">Store</span>
                <span class="text-on-surface-variant/40">•</span>
                <span id="qr-payment" class="text-[11px] font-bold px-2 py-0.5 rounded-full"></span>
            </div>

            <div id="qr-location-box" class="text-[11px] text-on-surface-variant/90 mt-1 flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-[14px] text-primary">location_on</span>
                <span id="qr-location">Poblacion, Polomolok</span>
            </div>

            <p class="text-xs text-on-surface-variant/80 mt-2.5 leading-relaxed italic px-2">
                Present this QR code to the cashier at <span id="qr-inst-shop" class="font-semibold text-on-surface not-italic">the store</span> upon claiming your printed document.
            </p>

            <div class="mt-3 p-2 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-center gap-1.5 text-amber-800 text-[11px] font-medium text-left w-full">
                <span class="material-symbols-outlined text-[16px] text-amber-600 shrink-0">brightness_high</span>
                <span>Turn up screen brightness for fast barcode scanning</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 w-full">
            <button type="button" id="qr-download" class="w-full py-md bg-surface-container-highest hover:bg-outline-variant text-on-surface rounded-xl text-button font-button transition-all flex items-center justify-center gap-xs cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">download</span>
                <span>Download QR</span>
            </button>
            <button type="button" id="qr-done" class="w-full py-md bg-primary text-on-primary rounded-xl text-button font-button hover:bg-primary-container transition-all active:scale-95 shadow-md cursor-pointer">Close</button>
        </div>
    </div>
</div>

<!-- Cancel Printing Confirmation Modal -->
<div id="cancel-pr-modal" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-md">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="cancel-pr-overlay"></div>
    <div class="relative bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-2xl w-full max-w-md p-lg md:p-xl flex flex-col gap-md z-10">
        <div class="flex items-center gap-sm">
            <div class="w-12 h-12 rounded-2xl bg-error-container/20 flex items-center justify-center shrink-0 text-error">
                <span class="material-symbols-outlined text-2xl">warning</span>
            </div>
            <div>
                <h3 class="text-title-lg font-bold text-on-surface" id="cancel-pr-title">Cancel Printing Request?</h3>
                <p class="text-xs text-on-surface-variant font-medium">Permanent Action</p>
            </div>
        </div>

        <p class="text-body-md text-on-surface-variant leading-relaxed">
            Are you sure you want to cancel this printing request? This action cannot be undone and will notify the shop attendant.
        </p>

        <div id="cancel-pr-error" class="hidden p-sm bg-error-container/20 border border-error-container/50 rounded-xl text-xs text-error font-semibold flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">error</span>
            <span id="cancel-pr-error-text">Failed to cancel printing request.</span>
        </div>

        <div class="flex items-center gap-sm pt-sm border-t border-outline-variant/20">
            <button type="button" id="cancel-pr-keep" class="flex-1 py-md bg-surface-container-high hover:bg-surface-container-highest text-on-surface rounded-xl font-button text-button transition-all font-semibold cursor-pointer">Keep Request</button>
            <button type="button" id="cancel-pr-confirm" class="flex-1 py-md bg-error hover:bg-error/90 text-white rounded-xl font-button text-button transition-all font-semibold flex items-center justify-center gap-xs shadow-md active:scale-95 cursor-pointer">
                <span id="cancel-pr-spinner" class="material-symbols-outlined text-[16px] animate-spin hidden">progress_activity</span>
                <span id="cancel-pr-confirm-text">Yes, Cancel</span>
            </button>
        </div>
    </div>
</div>

<!-- Rate Shop & Service Modal -->
<div id="rate-pr-modal" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-md">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="rate-pr-overlay"></div>
    <div class="relative bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-2xl w-full max-w-lg p-lg md:p-xl flex flex-col gap-md z-10 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-md">
            <div>
                <span class="text-[10px] uppercase font-bold text-outline tracking-wider">Leave Feedback</span>
                <h3 class="text-title-lg font-bold text-on-surface" id="rate-pr-title">Rate Printing Service</h3>
            </div>
            <button type="button" id="rate-pr-close" class="p-1 rounded-full hover:bg-surface-container-high text-on-surface-variant transition-colors cursor-pointer">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="rate-pr-form" class="space-y-lg">
            <input type="hidden" id="rate-pr-shop-id" value="">
            <input type="hidden" id="rate-pr-request-id" value="">

            <!-- Shop & Printing Rating -->
            <div class="bg-surface-container-low p-md rounded-2xl border border-outline-variant/20 space-y-xs">
                <div class="flex justify-between items-center">
                    <label class="text-xs font-bold text-on-surface uppercase tracking-wide">Shop & Service Rating: <span id="rate-pr-shop-name" class="text-primary font-semibold">Store</span></label>
                    <span id="rate-pr-val-text" class="text-xs font-bold text-amber-500">5 / 5</span>
                </div>
                <div class="flex items-center gap-1" id="pr-star-group">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="pr-star text-amber-400 hover:scale-110 transition-transform cursor-pointer" data-val="<?= $i ?>">
                            <span class="material-symbols-outlined text-[28px]" style="font-variation-settings: 'FILL' 1;">star</span>
                        </button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" id="rate-pr-score" value="5">
                <textarea id="rate-pr-comment" rows="3" placeholder="How was the print quality, paper stock, and customer turnaround time?" class="w-full text-xs p-2.5 rounded-xl border border-outline-variant/40 bg-surface focus:border-primary focus:ring-1 focus:ring-primary text-on-surface"></textarea>
            </div>

            <div id="rate-pr-error" class="hidden p-sm bg-error-container/20 border border-error-container/50 rounded-xl text-xs text-error font-semibold flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">error</span>
                <span id="rate-pr-error-text">Failed to submit review.</span>
            </div>

            <div class="flex justify-end gap-sm pt-xs border-t border-outline-variant/20">
                <button type="button" id="rate-pr-cancel" class="py-sm px-lg bg-surface-container-high hover:bg-surface-container-highest text-on-surface rounded-xl font-button text-button transition-all font-semibold cursor-pointer">Cancel</button>
                <button type="submit" id="rate-pr-submit-btn" class="py-sm px-xl bg-primary hover:bg-primary-container text-on-primary rounded-xl font-button text-button transition-all font-semibold flex items-center justify-center gap-xs shadow-md active:scale-95 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">send</span>
                    <span>Submit Review</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<?php if (!empty($requests)): ?>
<script>
(function () {
    // 1. Filter Tabs
    var tabs  = document.querySelectorAll('.pr-tab');
    var cards = document.querySelectorAll('.pr-card');
    var empty = document.getElementById('pr-empty');
    var emptyLabel = document.getElementById('pr-empty-label');

    function applyFilter(filter) {
        var visible = 0;
        cards.forEach(function (card) {
            var show = filter === 'all' || card.getAttribute('data-group') === filter;
            card.classList.toggle('hidden', !show);
            if (show) visible++;
        });

        if (empty) {
            var showEmpty = visible === 0;
            empty.classList.toggle('hidden', !showEmpty);
            if (showEmpty && emptyLabel) {
                emptyLabel.textContent = filter + ' printing requests';
            }
        }
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) {
                t.classList.remove('text-primary', 'border-b-2', 'border-primary', 'font-semibold');
                t.classList.add('text-on-surface-variant', 'font-medium');
            });
            tab.classList.remove('text-on-surface-variant', 'font-medium');
            tab.classList.add('text-primary', 'border-b-2', 'border-primary', 'font-semibold');
            applyFilter(tab.getAttribute('data-filter'));
        });
    });

    // 2. Track Modal Logic
    var trackBtn = document.querySelectorAll('.pr-track');
    var trackModal = document.getElementById('track-modal');
    var trackOverlay = document.getElementById('track-overlay');
    var trackClose = document.getElementById('track-close');
    var trackDone = document.getElementById('track-done');

    var pillMap = {
        ready_for_pickup: 'bg-green-100 text-green-700',
        ready_for_delivery: 'bg-green-100 text-green-700',
        in_transit: 'bg-blue-100 text-blue-700',
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
    if (trackClose) trackClose.addEventListener('click', closeTrack);
    if (trackDone) trackDone.addEventListener('click', closeTrack);

    // 3. Store Pick-up QR Modal Logic (matching orders.php)
    var qrModal    = document.getElementById('qr-modal');
    var qrOverlay  = document.getElementById('qr-overlay');
    var qrClose    = document.getElementById('qr-close');
    var qrDone     = document.getElementById('qr-done');
    var qrCanvas   = document.getElementById('qr-canvas-container');
    var qrNumber   = document.getElementById('qr-request-number');
    var qrShop     = document.getElementById('qr-shop');
    var qrPayment  = document.getElementById('qr-payment');
    var qrLocation = document.getElementById('qr-location');
    var qrInstShop = document.getElementById('qr-inst-shop');

    function openQr(reqNum, fileName, shopName, locationText, isPaid) {
        if (!qrModal) return;
        qrNumber.textContent = '#' + reqNum;
        qrShop.textContent = shopName || 'Storefront';
        if (qrInstShop) qrInstShop.textContent = shopName || 'the store attendant';
        if (qrLocation) qrLocation.textContent = locationText || 'Poblacion, Polomolok';

        if (isPaid === 'PAID') {
            qrPayment.textContent = 'PAID ONLINE';
            qrPayment.className = 'text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800';
        } else {
            qrPayment.textContent = 'PAY AT COUNTER';
            qrPayment.className = 'text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-900';
        }

        qrCanvas.innerHTML = '';
        if (window.QRCode) {
            new QRCode(qrCanvas, {
                text: reqNum,
                width: 200,
                height: 200,
                colorDark: '#0f172a',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        } else {
            qrCanvas.innerHTML = '<p class="text-error text-xs font-mono">#' + reqNum + '</p>';
        }

        qrModal.classList.remove('hidden');
    }

    function closeQr() {
        if (qrModal) qrModal.classList.add('hidden');
    }

    document.querySelectorAll('.pr-qr-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            openQr(btn.dataset.number, btn.dataset.name, btn.dataset.shop, btn.dataset.location, btn.dataset.paid);
        });
    });

    if (qrOverlay) qrOverlay.addEventListener('click', closeQr);
    if (qrClose) qrClose.addEventListener('click', closeQr);
    if (qrDone) qrDone.addEventListener('click', closeQr);

    var qrDownloadBtn = document.getElementById('qr-download');
    if (qrDownloadBtn) {
        qrDownloadBtn.addEventListener('click', function () {
            var canvas = qrCanvas.querySelector('canvas');
            var img = qrCanvas.querySelector('img');
            var dataUrl = null;
            if (canvas) {
                dataUrl = canvas.toDataURL('image/png');
            } else if (img && img.src) {
                dataUrl = img.src;
            }
            if (dataUrl) {
                var a = document.createElement('a');
                a.href = dataUrl;
                a.download = 'BLAX-Printing-QR-' + (qrNumber.textContent.replace('#', '') || 'request') + '.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
        });
    }

    // 4. Cancel Printing Request Modal (matching cancel-order-modal)
    var cancelModal   = document.getElementById('cancel-pr-modal');
    var cancelOverlay = document.getElementById('cancel-pr-overlay');
    var cancelKeep    = document.getElementById('cancel-pr-keep');
    var cancelConfirm = document.getElementById('cancel-pr-confirm');
    var cancelTitle   = document.getElementById('cancel-pr-title');
    var cancelErr     = document.getElementById('cancel-pr-error');
    var cancelErrText = document.getElementById('cancel-pr-error-text');
    var cancelSpinner = document.getElementById('cancel-pr-spinner');
    var cancelText    = document.getElementById('cancel-pr-confirm-text');

    var currentCancelId = null;

    function openCancelModal(reqId, reqNumber) {
        currentCancelId = reqId;
        if (cancelTitle) cancelTitle.textContent = 'Cancel Printing Request #' + reqNumber + '?';
        if (cancelErr) cancelErr.classList.add('hidden');
        if (cancelModal) cancelModal.classList.remove('hidden');
    }

    function closeCancelModal() {
        if (cancelModal) cancelModal.classList.add('hidden');
        currentCancelId = null;
    }

    if (cancelOverlay) cancelOverlay.addEventListener('click', closeCancelModal);
    if (cancelKeep) cancelKeep.addEventListener('click', closeCancelModal);

    document.querySelectorAll('.cancel-pr-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            openCancelModal(this.getAttribute('data-id'), this.getAttribute('data-number'));
        });
    });

    if (cancelConfirm) {
        cancelConfirm.addEventListener('click', function () {
            if (!currentCancelId) return;

            cancelSpinner.classList.remove('hidden');
            cancelText.textContent = 'Cancelling...';
            cancelConfirm.disabled = true;

            var csrfToken = (typeof window.getCsrfToken === 'function') ? window.getCsrfToken() : '';
            var csrfHeader = (typeof window.getCsrfHeader === 'function') ? window.getCsrfHeader() : 'X-CSRF-TOKEN';

            fetch('<?= base_url('customer/printing/cancel') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    [csrfHeader]: csrfToken
                },
                body: new URLSearchParams({
                    'request_id': currentCancelId
                })
            })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, status: res.status, data: data };
                });
            })
            .then(function (result) {
                cancelSpinner.classList.add('hidden');
                cancelText.textContent = 'Yes, Cancel';
                cancelConfirm.disabled = false;

                if (result.ok && result.data && result.data.success) {
                    closeCancelModal();
                    if (typeof showToast === 'function') {
                        showToast(result.data.message || 'Printing request cancelled successfully.', 'success');
                    }
                    setTimeout(function () {
                        window.location.reload();
                    }, 600);
                } else {
                    if (cancelErr && cancelErrText) {
                        cancelErrText.textContent = (result.data && result.data.error) ? result.data.error : 'Failed to cancel printing request.';
                        cancelErr.classList.remove('hidden');
                    }
                }
            })
            .catch(function (err) {
                cancelSpinner.classList.add('hidden');
                cancelText.textContent = 'Yes, Cancel';
                cancelConfirm.disabled = false;
                if (cancelErr && cancelErrText) {
                    cancelErrText.textContent = 'Network or server error. Please try again.';
                    cancelErr.classList.remove('hidden');
                }
            });
        });
    }

    // 5. Rate Printing Request Modal (matching rate-modal in orders.php)
    var rateModal      = document.getElementById('rate-pr-modal');
    var rateOverlay    = document.getElementById('rate-pr-overlay');
    var rateClose      = document.getElementById('rate-pr-close');
    var rateCancel     = document.getElementById('rate-pr-cancel');
    var rateForm       = document.getElementById('rate-pr-form');
    var rateShopName   = document.getElementById('rate-pr-shop-name');
    var rateShopId     = document.getElementById('rate-pr-shop-id');
    var rateReqId      = document.getElementById('rate-pr-request-id');
    var rateScore      = document.getElementById('rate-pr-score');
    var rateComment    = document.getElementById('rate-pr-comment');
    var rateValText    = document.getElementById('rate-pr-val-text');
    var rateErr        = document.getElementById('rate-pr-error');
    var rateErrText    = document.getElementById('rate-pr-error-text');

    function closeRateModal() {
        if (rateModal) rateModal.classList.add('hidden');
    }

    if (rateOverlay) rateOverlay.addEventListener('click', closeRateModal);
    if (rateClose) rateClose.addEventListener('click', closeRateModal);
    if (rateCancel) rateCancel.addEventListener('click', closeRateModal);

    function updatePrStars(score) {
        var stars = document.querySelectorAll('#pr-star-group .pr-star');
        stars.forEach(function (s) {
            var val = parseInt(s.dataset.val, 10);
            var icon = s.querySelector('.material-symbols-outlined');
            if (icon) {
                if (val <= score) {
                    icon.style.fontVariationSettings = "'FILL' 1";
                    s.classList.remove('opacity-40');
                } else {
                    icon.style.fontVariationSettings = "'FILL' 0";
                    s.classList.add('opacity-40');
                }
            }
        });
        if (rateValText) rateValText.textContent = score + ' / 5';
    }

    document.querySelectorAll('#pr-star-group .pr-star').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var val = parseInt(this.dataset.val, 10) || 5;
            rateScore.value = val;
            updatePrStars(val);
        });
    });

    document.querySelectorAll('.rate-pr-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            try {
                var req = JSON.parse(this.getAttribute('data-req'));
                if (!req) return;
                rateReqId.value = req.id || '';
                rateShopId.value = req.shop_id || '';
                rateShopName.textContent = req.shop_name || 'Printing Shop';
                rateScore.value = '5';
                rateComment.value = '';
                updatePrStars(5);
                if (rateErr) rateErr.classList.add('hidden');
                if (rateModal) rateModal.classList.remove('hidden');
            } catch (err) {
                console.error('Failed to open printing rate modal:', err);
            }
        });
    });

    if (rateForm) {
        rateForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var csrfToken = (typeof window.getCsrfToken === 'function') ? window.getCsrfToken() : '';
            var csrfHeader = (typeof window.getCsrfHeader === 'function') ? window.getCsrfHeader() : 'X-CSRF-TOKEN';
            var submitBtn = document.getElementById('rate-pr-submit-btn');
            if (submitBtn) submitBtn.disabled = true;

            var postData = new URLSearchParams({
                shop_id: rateShopId.value,
                rating: rateScore.value,
                review: rateComment.value,
                order_id: rateReqId.value
            });

            fetch('<?= base_url('customer/reviews/shop') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    [csrfHeader]: csrfToken
                },
                body: postData
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (submitBtn) submitBtn.disabled = false;
                closeRateModal();
                if (typeof showToast === 'function') {
                    showToast('Thank you! Your review for the printing shop has been submitted.', 'success');
                } else {
                    alert('Thank you! Your review for the printing shop has been submitted.');
                }
            })
            .catch(function (err) {
                if (submitBtn) submitBtn.disabled = false;
                if (rateErr && rateErrText) {
                    rateErrText.textContent = 'Failed to submit review. Please try again.';
                    rateErr.classList.remove('hidden');
                }
            });
        });
    }

})();
</script>
<?php endif; ?>

<?= $this->endSection() ?>