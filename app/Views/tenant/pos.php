<?= $this->extend('layouts/tenant') ?>

<?= $this->section('content') ?>

<?php
$isStorePickup = !empty($order);
$customerFullName = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
$isOnlinePaid = ($order['payment_status'] ?? '') === 'paid';
$originalTotal = (float) ($order['total_amount'] ?? 0);
$existingPosAdd = (float) ($order['pos_additional_amount'] ?? 0);
$baseOnlineAmount = max(0, $originalTotal - $existingPosAdd);
$pickupOrders = $pickupOrders ?? [];
$categories = $categories ?? [];
$orderItems = $orderItems ?? [];
?>

<div class="space-y-lg">
    <!-- TOP: POS Header & Mode Switcher -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-md bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-lg shadow-sm">
        <div class="flex items-center gap-md">
            <div class="w-12 h-12 rounded-xl <?= $isStorePickup ? 'bg-secondary-container text-on-secondary-container' : 'bg-primary-container text-on-primary-container' ?> flex items-center justify-center shrink-0 shadow-sm">
                <span class="material-symbols-outlined text-2xl"><?= $isStorePickup ? 'storefront' : 'point_of_sale' ?></span>
            </div>
            <div>
                <div class="flex items-center gap-sm flex-wrap">
                    <h1 class="text-headline-md font-bold text-on-surface">
                        Point of Sale (POS)
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold <?= $isStorePickup ? 'bg-secondary/15 text-secondary' : 'bg-primary/15 text-primary' ?>">
                        <?= $isStorePickup ? 'Store Pick-up POS' : 'Walk-in Counter POS' ?>
                    </span>
                </div>
                <p class="text-label-sm text-on-surface-variant">
                    <?= $isStorePickup 
                        ? 'Fulfill online pick-up order, add in-store purchases, and record counter payment without double-charging.' 
                        : 'Quick counter retail billing and instant real-time inventory deduction for walk-in customers.' ?>
                </p>
            </div>
        </div>

        <!-- Mode Buttons: [ New Walk-in Sale ], [ Store Pickup ], and [ Scan Customer QR ] -->
        <div class="flex flex-wrap items-center gap-sm">
            <a href="<?= base_url('tenant/pos') ?>" 
               class="px-md py-sm rounded-xl text-label-sm font-bold flex items-center gap-xs transition-all shadow-sm <?= !$isStorePickup ? 'bg-primary text-on-primary shadow-md' : 'border border-outline-variant hover:bg-surface-container text-on-surface' ?>">
                <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span>
                <span>New Walk-in Sale</span>
            </a>

            <button type="button" 
                    onclick="openPickupSelectorModal()" 
                    class="px-md py-sm rounded-xl text-label-sm font-bold flex items-center gap-xs transition-all shadow-sm <?= $isStorePickup ? 'bg-secondary text-on-secondary shadow-md' : 'border border-outline-variant hover:bg-surface-container text-on-surface' ?>">
                <span class="material-symbols-outlined text-[18px]">shopping_bag</span>
                <span>Store Pickup <?= $isStorePickup ? '(Active)' : (!empty($pickupOrders) ? '(' . count($pickupOrders) . ' Pending)' : '') ?></span>
            </button>

            <button type="button" 
                    onclick="openQrScannerModal()" 
                    class="px-md py-sm rounded-xl text-label-sm font-bold flex items-center gap-xs transition-all shadow-sm border border-secondary/40 bg-secondary-container/30 text-secondary hover:bg-secondary-container/50">
                <span class="material-symbols-outlined text-[18px]">qr_code_scanner</span>
                <span>Scan Customer QR</span>
            </button>

            <a href="<?= base_url('tenant/orders') ?>" class="px-md py-sm rounded-xl border border-outline-variant hover:bg-surface-container text-label-sm font-semibold text-on-surface flex items-center gap-xs transition-all">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>Orders</span>
            </a>
        </div>
    </div>

    <?php if ($isStorePickup): ?>
        <!-- Online Order Card (Pickup Mode) -->
        <div class="bg-surface-container-lowest border border-secondary/30 rounded-2xl p-lg shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-md border-b border-outline-variant/20 pb-md mb-md">
                <div class="flex items-center gap-sm">
                    <div class="p-2 bg-secondary-container/40 text-secondary rounded-xl">
                        <span class="material-symbols-outlined text-xl">local_mall</span>
                    </div>
                    <div>
                        <span class="text-xs text-on-surface-variant font-bold uppercase tracking-wider">Store Pick-up Order</span>
                        <div class="text-title-md font-bold text-on-surface font-mono">
                            #<?= esc($order['order_number']) ?>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-sm">
                    <?php if ($isOnlinePaid): ?>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">verified</span>
                            <span>PAID ONLINE (<?= esc(strtoupper($order['payment_method'] ?? 'ONLINE')) ?>)</span>
                        </span>
                    <?php else: ?>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">warning</span>
                            <span>UNPAID ONLINE (Collect ₱<?= number_format($baseOnlineAmount, 2) ?> at counter)</span>
                        </span>
                    <?php endif; ?>
                    <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-surface-container text-on-surface-variant">
                        Placed <?= esc(date('M d, Y h:i A', strtotime($order['placed_at']))) ?>
                    </span>
                    <button type="button" onclick="openPickupSelectorModal()" class="text-xs font-bold text-primary hover:underline ml-xs">
                        Change Order
                    </button>
                </div>
            </div>

            <!-- Notice Banner -->
            <div class="mb-md p-md rounded-xl text-body-md font-medium flex items-center gap-sm <?= $isOnlinePaid ? 'bg-green-50 text-green-900 border border-green-200' : 'bg-amber-50 text-amber-900 border border-amber-200' ?>">
                <span class="material-symbols-outlined shrink-0 text-xl <?= $isOnlinePaid ? 'text-green-600' : 'text-amber-600' ?>"><?= $isOnlinePaid ? 'check_circle' : 'info' ?></span>
                <div class="text-xs sm:text-sm">
                    <?php if ($isOnlinePaid): ?>
                        <strong>ORIGINAL ONLINE ORDER: ₱<?= number_format($baseOnlineAmount, 2) ?> PAID.</strong> Customer already paid online via <?= esc(strtoupper($order['payment_method'] ?? 'ONLINE')) ?>. <strong>NEVER charge this original ₱<?= number_format($baseOnlineAmount, 2) ?> again.</strong> Only collect payment for any additional in-store items selected below.
                    <?php else: ?>
                        <strong>ONLINE ORDER IS UNPAID:</strong> Collect ₱<?= number_format($baseOnlineAmount, 2) ?> plus any additional in-store items at pickup.
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-md text-body-md">
                <div class="p-sm bg-surface-container-low rounded-xl">
                    <span class="text-xs text-on-surface-variant block font-medium">Customer</span>
                    <span class="font-bold text-on-surface"><?= esc($customerFullName ?: 'Online Customer') ?></span>
                    <?php if (!empty($order['phone'])): ?>
                        <span class="text-xs text-outline block mt-0.5"><?= esc($order['phone']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="p-sm bg-surface-container-low rounded-xl">
                    <span class="text-xs text-on-surface-variant block font-medium">Original Online Order</span>
                    <span class="font-bold font-mono text-on-surface">₱<?= number_format($baseOnlineAmount, 2) ?></span>
                    <span class="text-xs <?= $isOnlinePaid ? 'text-green-700 font-bold' : 'text-amber-700' ?> block mt-0.5">
                        <?= $isOnlinePaid ? '✓ Paid Online' : '⚠ Unpaid' ?>
                    </span>
                </div>

                <div class="p-sm bg-surface-container-low rounded-xl">
                    <span class="text-xs text-on-surface-variant block font-medium">Prior In-Store Additions</span>
                    <span class="font-bold font-mono text-secondary">₱<?= number_format($existingPosAdd, 2) ?></span>
                    <span class="text-xs text-outline block mt-0.5"><?= $existingPosAdd > 0 ? 'Recorded earlier' : 'None' ?></span>
                </div>

                <div class="p-sm bg-surface-container-low rounded-xl">
                    <span class="text-xs text-on-surface-variant block font-medium">Current Total on Record</span>
                    <span class="font-bold font-mono text-primary">₱<?= number_format($originalTotal, 2) ?></span>
                    <span class="text-xs text-outline block mt-0.5"><?= esc(ucfirst($order['status'])) ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- POS Main Interactive Area -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-lg items-start">
        
        <!-- Left Side: Product Search, Category Chips & Product Cards Grid (7 cols) -->
        <div class="lg:col-span-7 space-y-md">
            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-lg shadow-sm space-y-md">
                
                <!-- SEARCH Area -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-sm">
                    <div class="flex items-center gap-xs">
                        <span class="material-symbols-outlined text-primary">manage_search</span>
                        <h2 class="text-title-lg font-bold text-on-surface">Find Products</h2>
                    </div>
                    <span class="text-xs text-on-surface-variant font-medium">Search by product name or SKU</span>
                </div>

                <div class="relative">
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined absolute left-3 text-outline text-[20px] pointer-events-none">search</span>
                        <input type="text" 
                               id="posProductSearch" 
                               placeholder="🔍 Search products by name, brand, or SKU..." 
                               autocomplete="off"
                               class="w-full pl-10 pr-10 py-3 bg-surface-container-low border border-outline-variant rounded-xl text-body-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                        <button type="button" id="posClearSearch" class="hidden absolute right-3 text-outline hover:text-on-surface">
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    </div>

                    <!-- Live Results Dropdown -->
                    <div id="posSearchResults" class="hidden absolute left-0 right-0 top-full mt-1 bg-surface-container-lowest border border-outline-variant/40 rounded-xl shadow-xl z-40 max-h-80 overflow-y-auto divide-y divide-outline-variant/20 custom-scrollbar">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <!-- CATEGORY CHIPS Area -->
                <div>
                    <div class="flex items-center justify-between mb-xs">
                        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Category Filter</span>
                        <span id="posActiveCategoryText" class="text-xs text-outline">All Products</span>
                    </div>
                    <div class="flex items-center gap-xs overflow-x-auto pb-xs pt-0.5 custom-scrollbar">
                        <button type="button" 
                                class="pos-cat-chip px-3 py-1.5 rounded-full text-xs font-bold transition-all shrink-0 bg-primary text-on-primary shadow-sm" 
                                data-category="">
                            All Categories
                        </button>
                        <?php foreach ($categories as $cat): ?>
                            <button type="button" 
                                    class="pos-cat-chip px-3 py-1.5 rounded-full text-xs font-bold transition-all shrink-0 bg-surface-container text-on-surface-variant hover:bg-surface-container-high border border-outline-variant/30" 
                                    data-category="<?= (int) $cat['id'] ?>">
                                <?= esc($cat['name']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- MAIN: Product Card/Grid Area -->
                <div class="pt-sm border-t border-outline-variant/20">
                    <div class="flex items-center justify-between mb-sm">
                        <h3 class="text-label-sm font-bold text-on-surface uppercase tracking-wider flex items-center gap-1">
                            <span class="material-symbols-outlined text-[18px] text-primary">grid_view</span>
                            Product Catalog
                        </h3>
                        <span id="posCatalogHint" class="text-xs text-outline">Showing in-stock items</span>
                    </div>

                    <!-- Modern Product Cards Grid -->
                    <div id="posQuickCatalog" class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-3 gap-md max-h-[620px] overflow-y-auto pr-1 custom-scrollbar">
                        <!-- Populated dynamically via JS product cards -->
                        <div class="col-span-full py-xl text-center text-on-surface-variant/70 flex flex-col items-center gap-xs">
                            <span class="material-symbols-outlined text-3xl animate-spin text-primary">progress_activity</span>
                            <span class="text-xs font-medium">Loading store products...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Sticky Current Sale Summary (5 cols) -->
        <div class="lg:col-span-5 space-y-md lg:sticky lg:top-4">
            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-lg shadow-sm space-y-md">
                
                <!-- Summary Header -->
                <div class="flex items-center justify-between border-b border-outline-variant/20 pb-sm">
                    <div class="flex items-center gap-xs">
                        <span class="material-symbols-outlined text-primary">receipt_long</span>
                        <h2 class="text-title-lg font-bold text-on-surface">
                            <?= $isStorePickup ? 'Sale & Pick-up Summary' : 'Counter Cart' ?>
                        </h2>
                    </div>
                    <span id="posCartCountBadge" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-surface-container text-on-surface-variant font-mono">0 items</span>
                </div>

                <?php if ($isStorePickup): ?>
                    <!-- Section: ORIGINAL ONLINE ORDER -->
                    <div class="border border-outline-variant/30 rounded-xl p-md bg-surface-container-low/40 space-y-xs">
                        <div class="flex items-center justify-between mb-xs">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface flex items-center gap-xs">
                                <span class="material-symbols-outlined text-[16px] text-primary">shopping_bag</span>
                                Original Online Order
                            </h4>
                            <span class="text-[11px] px-2 py-0.5 rounded-full font-bold <?= $isOnlinePaid ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' ?>">
                                <?= $isOnlinePaid ? 'PAID ONLINE' : 'UNPAID' ?>
                            </span>
                        </div>

                        <!-- Original Online Items List -->
                        <div class="border border-outline-variant/20 rounded-lg overflow-hidden bg-surface-container-lowest divide-y divide-outline-variant/10 text-xs">
                            <?php if (!empty($orderItems)): ?>
                                <?php foreach ($orderItems as $oit): ?>
                                    <?php if (empty($oit['is_pos_addition'])): ?>
                                        <div class="p-xs px-sm flex items-center justify-between">
                                            <div class="min-w-0 flex-1 pr-2">
                                                <p class="font-semibold text-on-surface truncate"><?= esc($oit['product_name']) ?></p>
                                                <p class="text-[11px] text-outline font-mono">Qty: <?= (int) $oit['quantity'] ?> × ₱<?= number_format((float) $oit['unit_price'], 2) ?></p>
                                            </div>
                                            <span class="font-mono font-bold text-on-surface">₱<?= number_format((float) $oit['line_total'], 2) ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-xs text-center text-outline">Items details loaded from online order.</div>
                            <?php endif; ?>
                        </div>

                        <div class="flex justify-between items-center text-xs font-bold pt-xs">
                            <span class="text-on-surface-variant">Original Online Total:</span>
                            <span class="font-mono <?= $isOnlinePaid ? 'text-green-700' : 'text-on-surface' ?>">
                                ₱<?= number_format($baseOnlineAmount, 2) ?> <?= $isOnlinePaid ? '(PAID)' : '' ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Section: ADDITIONAL IN-STORE ITEMS (or Walk-in Items) -->
                <div>
                    <div class="flex items-center justify-between mb-xs">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface flex items-center gap-xs">
                            <span class="material-symbols-outlined text-[16px] text-secondary">add_shopping_cart</span>
                            <?= $isStorePickup ? 'Additional POS Items' : 'Sale Items' ?>
                        </h4>
                        <span class="text-[11px] text-outline" id="posItemsHintText"><?= $isStorePickup ? 'Add items at counter' : 'Selected items' ?></span>
                    </div>

                    <!-- Cart Items List with Quantity Controls and Remove -->
                    <div class="space-y-xs min-h-[140px] max-h-[280px] overflow-y-auto custom-scrollbar" id="posCartList">
                        <div id="posEmptyCartNotice" class="py-lg text-center text-on-surface-variant/70 flex flex-col items-center gap-xs border border-dashed border-outline-variant/40 rounded-xl">
                            <span class="material-symbols-outlined text-3xl text-outline-variant">remove_shopping_cart</span>
                            <span class="text-xs font-medium px-md">
                                <?= $isStorePickup 
                                    ? 'No additional in-store items. (You can complete store pickup without adding any extra items)' 
                                    : 'No items in sale yet. Click [+ Add] on products from the catalog.' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Walk-in Customer Name / Note Field -->
                <?php if (!$isStorePickup): ?>
                    <div class="pt-xs">
                        <label for="posCustomerName" class="text-xs font-bold text-on-surface uppercase tracking-wider block mb-1">
                            Customer Name / Note (Optional)
                        </label>
                        <input type="text" id="posCustomerName" placeholder="e.g. Walk-in Student, Cash Customer" class="w-full px-md py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                <?php endif; ?>

                <!-- Settlement Breakdown -->
                <div class="pt-sm border-t border-outline-variant/20 space-y-xs">
                    <?php if ($isStorePickup): ?>
                        <div class="flex justify-between items-center text-xs text-on-surface-variant">
                            <span>Original Online Order:</span>
                            <span class="font-mono font-medium">₱<?= number_format($baseOnlineAmount, 2) ?> <?= $isOnlinePaid ? '<span class="text-green-700 font-bold">(PAID)</span>' : '' ?></span>
                        </div>
                        <div class="flex justify-between items-center text-xs text-on-surface-variant">
                            <span>Additional POS Purchase:</span>
                            <span class="font-mono font-bold text-secondary" id="posAdditionalSubtotal">₱0.00</span>
                        </div>
                        <div class="flex justify-between items-center text-xs text-on-surface-variant pt-xs border-t border-outline-variant/10">
                            <span>Combined Order Total:</span>
                            <span class="font-mono font-bold text-on-surface" id="posUpdatedGrandTotal">₱<?= number_format($originalTotal, 2) ?></span>
                        </div>
                        <!-- Amount to collect now: MUST NEVER CHARGE ONLINE PAID AMOUNT AGAIN -->
                        <div class="flex justify-between items-center p-sm rounded-xl bg-secondary-container/30 border border-secondary/30 mt-xs">
                            <span class="text-xs sm:text-sm font-bold text-on-surface flex items-center gap-1">
                                <span class="material-symbols-outlined text-secondary text-[20px]">payments</span>
                                Amount to collect now:
                            </span>
                            <span class="font-mono text-title-lg font-extrabold text-secondary" id="posAmountDueNow">
                                ₱<?= number_format($isOnlinePaid ? 0.00 : $originalTotal, 2) ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="flex justify-between items-center text-xs text-on-surface-variant">
                            <span>Subtotal:</span>
                            <span class="font-mono font-medium" id="posWalkinSubtotal">₱0.00</span>
                        </div>
                        <div class="flex justify-between items-center p-sm rounded-xl bg-primary-container/30 border border-primary/30 mt-xs">
                            <span class="text-xs sm:text-sm font-bold text-on-surface flex items-center gap-1">
                                <span class="material-symbols-outlined text-primary text-[20px]">receipt_long</span>
                                Total Amount Due:
                            </span>
                            <span class="font-mono text-title-lg font-extrabold text-primary" id="posAmountDueNow">₱0.00</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Payment Method Selector -->
                <div class="pt-sm border-t border-outline-variant/20 space-y-sm">
                    <label class="text-xs font-bold text-on-surface uppercase tracking-wider block">
                        Payment Method
                    </label>
                    <div class="grid grid-cols-3 gap-xs">
                        <label class="cursor-pointer">
                            <input type="radio" name="pos_payment_method" value="cash" class="peer sr-only" checked>
                            <span class="flex flex-col items-center justify-center p-2 rounded-xl border border-outline-variant/40 bg-surface-container-low text-xs font-bold text-on-surface transition-all peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary shadow-sm">
                                <span class="material-symbols-outlined text-[18px]">payments</span>
                                Cash
                            </span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="pos_payment_method" value="gcash" class="peer sr-only">
                            <span class="flex flex-col items-center justify-center p-2 rounded-xl border border-outline-variant/40 bg-surface-container-low text-xs font-bold text-on-surface transition-all peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary shadow-sm">
                                <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                                GCash
                            </span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="pos_payment_method" value="card" class="peer sr-only">
                            <span class="flex flex-col items-center justify-center p-2 rounded-xl border border-outline-variant/40 bg-surface-container-low text-xs font-bold text-on-surface transition-all peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary shadow-sm">
                                <span class="material-symbols-outlined text-[18px]">credit_card</span>
                                Card
                            </span>
                        </label>
                    </div>

                    <!-- Cash Calculation Container -->
                    <div id="posCashCalculationContainer" class="p-sm bg-surface-container-low border border-outline-variant/30 rounded-xl space-y-sm">
                        <div class="flex items-center justify-between gap-sm">
                            <label for="posCashTendered" class="text-xs font-bold text-on-surface">Cash Tendered (₱):</label>
                            <input type="number" id="posCashTendered" step="any" min="0" placeholder="0.00" class="w-32 py-1 px-2 text-right font-mono font-bold text-sm bg-surface-container-lowest border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary">
                        </div>

                        <!-- Quick Cash Denominations -->
                        <div class="flex flex-wrap items-center gap-1 justify-end">
                            <button type="button" class="pos-denom-btn px-2 py-0.5 text-[11px] font-semibold bg-surface-container-lowest border border-outline-variant/40 rounded hover:bg-surface-container" data-val="exact">Exact</button>
                            <button type="button" class="pos-denom-btn px-2 py-0.5 text-[11px] font-semibold bg-surface-container-lowest border border-outline-variant/40 rounded hover:bg-surface-container" data-val="50">+50</button>
                            <button type="button" class="pos-denom-btn px-2 py-0.5 text-[11px] font-semibold bg-surface-container-lowest border border-outline-variant/40 rounded hover:bg-surface-container" data-val="100">+100</button>
                            <button type="button" class="pos-denom-btn px-2 py-0.5 text-[11px] font-semibold bg-surface-container-lowest border border-outline-variant/40 rounded hover:bg-surface-container" data-val="500">+500</button>
                            <button type="button" class="pos-denom-btn px-2 py-0.5 text-[11px] font-semibold bg-surface-container-lowest border border-outline-variant/40 rounded hover:bg-surface-container" data-val="1000">+1000</button>
                        </div>

                        <div class="flex justify-between items-center text-xs font-bold border-t border-outline-variant/20 pt-xs">
                            <span class="text-on-surface-variant">Change Due:</span>
                            <span id="posChangeDueText" class="font-mono text-sm font-bold text-green-700">₱0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Error Notice Container -->
                <div id="posErrorMessage" class="hidden p-sm bg-error-container/30 border border-error/20 text-on-error-container rounded-xl text-xs font-medium"></div>

                <!-- Complete Button -->
                <button type="button" id="posSubmitBtn" class="w-full py-3.5 bg-primary text-on-primary rounded-xl font-button text-sm font-bold shadow-md hover:shadow-lg hover:bg-primary/90 active:scale-[0.98] transition-all flex items-center justify-center gap-xs">
                    <span class="material-symbols-outlined text-[20px]">check_circle</span>
                    <span id="posSubmitBtnLabel"><?= $isStorePickup ? 'Complete Store Pick-up' : 'Complete Walk-in Sale' ?></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Store Pick-up Order Selector -->
<div id="posPickupSelectorModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl max-w-2xl w-full p-lg shadow-2xl space-y-md max-h-[90vh] flex flex-col">
        
        <div class="flex items-center justify-between border-b border-outline-variant/20 pb-sm shrink-0">
            <div class="flex items-center gap-sm">
                <div class="p-2 bg-secondary-container/40 text-secondary rounded-xl">
                    <span class="material-symbols-outlined text-xl">storefront</span>
                </div>
                <div>
                    <h3 class="text-title-lg font-bold text-on-surface">Select Store Pick-up Order</h3>
                    <p class="text-xs text-on-surface-variant">Select an active online order to fulfill at your counter</p>
                </div>
            </div>
            <button type="button" onclick="closePickupSelectorModal()" class="text-outline hover:text-on-surface p-1 rounded-full hover:bg-surface-container">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Filter Input inside modal -->
        <div class="relative shrink-0">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
            <input type="text" 
                   id="posPickupFilterInput" 
                   placeholder="Filter by order number or customer name..." 
                   oninput="filterPickupOrders(this.value)"
                   class="w-full pl-10 pr-md py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:ring-2 focus:ring-primary focus:border-primary">
        </div>

        <!-- Pickup Orders List -->
        <div class="overflow-y-auto space-y-sm flex-1 pr-1 custom-scrollbar" id="posPickupOrdersList">
            <?php if (!empty($pickupOrders)): ?>
                <?php foreach ($pickupOrders as $po): ?>
                    <?php 
                    $poName = trim(($po['first_name'] ?? '') . ' ' . ($po['last_name'] ?? ''));
                    $isPoPaid = ($po['payment_status'] ?? '') === 'paid';
                    ?>
                    <div class="pos-pickup-row p-md bg-surface-container-low hover:bg-surface-container border border-outline-variant/20 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-sm transition-all"
                         data-search="<?= strtolower(esc($po['order_number'] . ' ' . $poName)) ?>">
                        <div class="min-w-0">
                            <div class="flex items-center gap-xs flex-wrap">
                                <span class="font-mono font-bold text-sm text-on-surface">#<?= esc($po['order_number']) ?></span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $isPoPaid ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' ?>">
                                    <?= $isPoPaid ? 'PAID ONLINE' : 'UNPAID' ?>
                                </span>
                                <span class="text-xs text-outline">• <?= esc(date('M d, Y h:i A', strtotime($po['placed_at']))) ?></span>
                            </div>
                            <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                                Customer: <strong class="text-on-surface"><?= esc($poName ?: 'Online Customer') ?></strong>
                                <?php if (!empty($po['phone'])): ?> (<?= esc($po['phone']) ?>)<?php endif; ?>
                            </p>
                        </div>
                        <div class="flex items-center justify-between sm:justify-end gap-md shrink-0 pt-xs sm:pt-0 border-t sm:border-t-0 border-outline-variant/10">
                            <span class="font-mono font-bold text-sm text-primary">₱<?= number_format((float) $po['total_amount'], 2) ?></span>
                            <a href="<?= base_url('tenant/pos?order_id=' . $po['id']) ?>" 
                               class="px-md py-1.5 bg-secondary text-on-secondary hover:bg-secondary/90 rounded-lg text-xs font-bold transition-all flex items-center gap-xs shadow-sm">
                                <span class="material-symbols-outlined text-[16px]">point_of_sale</span>
                                Open in POS
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="py-xl text-center text-outline text-xs flex flex-col items-center gap-xs">
                    <span class="material-symbols-outlined text-3xl">inbox</span>
                    <span>No pending Store Pick-up orders found for your shop.</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-end border-t border-outline-variant/20 pt-sm shrink-0">
            <button type="button" onclick="closePickupSelectorModal()" class="px-md py-sm rounded-lg border border-outline-variant text-xs font-semibold hover:bg-surface-container">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Receipt / Success Modal -->
<div id="posSuccessModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl max-w-md w-full p-lg shadow-2xl space-y-md">
        <div class="text-center space-y-xs">
            <div class="w-14 h-14 bg-green-100 text-green-700 rounded-full mx-auto flex items-center justify-center">
                <span class="material-symbols-outlined text-3xl">done_all</span>
            </div>
            <h3 class="text-headline-md font-bold text-on-surface">Sale Completed!</h3>
            <p id="posSuccessModalMessage" class="text-xs text-on-surface-variant">Transaction processed successfully.</p>
        </div>

        <div id="posReceiptSummary" class="bg-surface-container-low p-md rounded-xl space-y-xs text-xs">
            <!-- Receipt lines populated dynamically -->
        </div>

        <div class="flex items-center gap-sm pt-xs">
            <button type="button" id="posPrintReceiptBtn" class="flex-1 py-2.5 border border-outline-variant hover:bg-surface-container text-on-surface rounded-xl text-xs font-bold flex items-center justify-center gap-xs">
                <span class="material-symbols-outlined text-[18px]">print</span>
                Print Receipt
            </button>
            <button type="button" id="posNewSaleBtn" class="flex-1 py-2.5 bg-primary hover:bg-primary/90 text-on-primary rounded-xl text-xs font-bold flex items-center justify-center gap-xs">
                <span class="material-symbols-outlined text-[18px]">autorenew</span>
                Next Sale
            </button>
        </div>
    </div>
</div>

<!-- Customer QR Scanner Modal -->
<div id="posQrScannerModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-md">
    <div class="glass-card bg-surface-container-lowest rounded-2xl p-lg max-w-md w-full border border-outline-variant/30 shadow-2xl space-y-md">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">
            <div class="flex items-center gap-xs text-secondary font-bold">
                <span class="material-symbols-outlined text-2xl">qr_code_scanner</span>
                <div>
                    <h3 class="text-title-md text-on-surface">Scan Customer Pick-up QR</h3>
                    <p class="text-[11px] text-on-surface-variant font-normal">Scan phone screen or type order number</p>
                </div>
            </div>
            <button type="button" onclick="closeQrScannerModal()" class="text-on-surface-variant hover:text-on-surface p-1 rounded-full hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div id="posScanFeedback" class="hidden text-xs rounded-xl p-sm font-medium"></div>

        <!-- Camera Container -->
        <div class="rounded-xl overflow-hidden bg-black aspect-square relative flex items-center justify-center">
            <div id="posQrReader" class="w-full h-full"></div>
            <div id="posQrCameraPlaceholder" class="absolute inset-0 flex flex-col items-center justify-center text-white/70 p-md text-center bg-black/80">
                <span class="material-symbols-outlined text-4xl mb-1 text-secondary">photo_camera</span>
                <span class="text-xs font-semibold">Starting camera...</span>
                <span class="text-[10px] opacity-70 mt-1">Please allow camera permissions if prompted</span>
            </div>
        </div>

        <!-- Manual Lookup Fallback -->
        <div class="space-y-xs pt-xs border-t border-outline-variant/20">
            <label for="posManualOrderCode" class="text-xs font-bold text-on-surface uppercase tracking-wider block">
                Manual Order Code / Number
            </label>
            <div class="flex gap-xs">
                <input type="text" id="posManualOrderCode" placeholder="e.g. ORD-88102" class="flex-1 px-md py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono font-bold focus:ring-2 focus:ring-primary focus:border-primary">
                <button type="button" id="posManualLookupBtn" onclick="verifyManualOrderCode()" class="bg-primary hover:bg-primary/90 text-on-primary px-md py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-xs">
                    <span class="material-symbols-outlined text-[16px]">search</span>
                    <span>Verify</span>
                </button>
            </div>
        </div>

        <div class="flex items-center justify-end pt-xs">
            <button type="button" onclick="closeQrScannerModal()" class="px-md py-2 rounded-xl border border-outline-variant text-xs font-semibold text-on-surface hover:bg-surface-container">
                Cancel
            </button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
(function() {
    const isStorePickup = <?= $isStorePickup ? 'true' : 'false' ?>;
    const storePickupOrderId = <?= (int) ($order['id'] ?? 0) ?>;
    const onlineOrderBaseAmount = <?= (float) $baseOnlineAmount ?>;
    const isOnlinePaid = <?= $isOnlinePaid ? 'true' : 'false' ?>;

    const BASE_URL = '<?= rtrim(base_url(), '/') ?>';
    const CSRF_TOKEN_NAME = '<?= csrf_token() ?>';
    let CSRF_HASH_VAL = '<?= csrf_hash() ?>';

    let cart = []; // Array of { product_id, name, price, stock_quantity, quantity, sku, image_url }
    let currentCategoryId = '';
    let searchDebounceTimer = null;
    let catalogProducts = [];

    // DOM Elements
    const searchInput = document.getElementById('posProductSearch');
    const clearSearchBtn = document.getElementById('posClearSearch');
    const searchResultsBox = document.getElementById('posSearchResults');
    const quickCatalogBox = document.getElementById('posQuickCatalog');
    const cartListBox = document.getElementById('posCartList');
    const emptyCartNotice = document.getElementById('posEmptyCartNotice');
    const cartCountBadge = document.getElementById('posCartCountBadge');
    const amountDueNowEl = document.getElementById('posAmountDueNow');
    const additionalSubtotalEl = document.getElementById('posAdditionalSubtotal');
    const updatedGrandTotalEl = document.getElementById('posUpdatedGrandTotal');
    const walkinSubtotalEl = document.getElementById('posWalkinSubtotal');
    const cashInput = document.getElementById('posCashTendered');
    const changeDueText = document.getElementById('posChangeDueText');
    const submitBtn = document.getElementById('posSubmitBtn');
    const submitBtnLabel = document.getElementById('posSubmitBtnLabel');
    const errBox = document.getElementById('posErrorMessage');

    const successModal = document.getElementById('posSuccessModal');
    const successMessage = document.getElementById('posSuccessModalMessage');
    const receiptSummary = document.getElementById('posReceiptSummary');
    const printReceiptBtn = document.getElementById('posPrintReceiptBtn');
    const newSaleBtn = document.getElementById('posNewSaleBtn');

    // Currency Formatter
    function formatMoney(amount) {
        return '₱' + (parseFloat(amount) || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function esc(str) {
        return String(str || '').replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        })[m]);
    }

    // Load Catalog with optional category & query
    function loadCatalog(query = '', categoryId = '') {
        quickCatalogBox.innerHTML = `
            <div class="col-span-full py-xl text-center text-on-surface-variant/70 flex flex-col items-center gap-xs">
                <span class="material-symbols-outlined text-3xl animate-spin text-primary">progress_activity</span>
                <span class="text-xs font-medium">Loading catalog products...</span>
            </div>
        `;

        let url = `${BASE_URL}/tenant/pos/search-products?q=${encodeURIComponent(query)}`;
        if (categoryId) {
            url += `&category_id=${encodeURIComponent(categoryId)}`;
        }

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.products && data.products.length > 0) {
                catalogProducts = data.products;
                renderProductCards(data.products);
            } else {
                quickCatalogBox.innerHTML = '<div class="col-span-full py-lg text-center text-outline text-xs">No products found matching your selection.</div>';
            }
        })
        .catch(() => {
            quickCatalogBox.innerHTML = '<div class="col-span-full py-lg text-center text-error text-xs">Failed to load shop catalog.</div>';
        });
    }

    // Render modern product cards grid
    function renderProductCards(products) {
        quickCatalogBox.innerHTML = products.map(p => {
            const outOfStock = p.stock_quantity <= 0;
            const isLowStock = p.stock_quantity > 0 && p.stock_quantity <= 5;
            return `
                <div class="bg-surface-container-lowest hover:bg-surface-container-low border border-outline-variant/30 rounded-2xl p-sm flex flex-col justify-between gap-sm transition-all hover:shadow-md group">
                    <!-- Product Image -->
                    <div class="w-full aspect-square rounded-xl bg-surface-container overflow-hidden flex items-center justify-center relative">
                        ${p.image_url 
                            ? `<img src="${p.image_url}" class="w-full h-full object-cover group-hover:scale-105 transition-transform" alt="${esc(p.name)}">` 
                            : `<div class="text-primary/40 flex flex-col items-center gap-xs"><span class="material-symbols-outlined text-4xl">inventory_2</span><span class="text-[10px] uppercase font-bold text-outline">No Image</span></div>`}
                        
                        <!-- Stock Badge Overlay -->
                        <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-[10px] font-bold shadow-sm ${
                            outOfStock ? 'bg-error text-on-error' : (isLowStock ? 'bg-amber-500 text-white' : 'bg-surface-container-lowest/90 text-on-surface')
                        }">
                            Stock: ${p.stock_quantity}
                        </span>
                    </div>

                    <!-- Product Details -->
                    <div class="space-y-xs min-w-0">
                        <p class="text-xs font-bold text-on-surface truncate" title="${esc(p.name)}">${esc(p.name)}</p>
                        <p class="text-[10px] text-outline truncate font-mono">SKU: ${esc(p.sku || 'N/A')}</p>
                        <p class="text-body-md font-mono font-bold text-primary">₱${p.price.toFixed(2)}</p>
                    </div>

                    <!-- Add Button -->
                    <button type="button" 
                            class="pos-card-add-btn w-full py-2 rounded-xl text-xs font-bold flex items-center justify-center gap-1 transition-all shadow-sm ${
                                outOfStock ? 'bg-outline/20 text-outline cursor-not-allowed' : 'bg-primary text-on-primary hover:bg-primary/90 active:scale-95'
                            }" 
                            data-id="${p.id}" 
                            ${outOfStock ? 'disabled' : ''}>
                        <span class="material-symbols-outlined text-[16px]">add_shopping_cart</span>
                        <span>Add</span>
                    </button>
                </div>
            `;
        }).join('');

        quickCatalogBox.querySelectorAll('.pos-card-add-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = parseInt(this.dataset.id);
                const prod = catalogProducts.find(x => x.id === id);
                if (prod) addToCart(prod);
            });
        });
    }

    // Category chips click handler
    document.querySelectorAll('.pos-cat-chip').forEach(chip => {
        chip.addEventListener('click', function() {
            document.querySelectorAll('.pos-cat-chip').forEach(c => {
                c.className = 'pos-cat-chip px-3 py-1.5 rounded-full text-xs font-bold transition-all shrink-0 bg-surface-container text-on-surface-variant hover:bg-surface-container-high border border-outline-variant/30';
            });
            this.className = 'pos-cat-chip px-3 py-1.5 rounded-full text-xs font-bold transition-all shrink-0 bg-primary text-on-primary shadow-sm';

            currentCategoryId = this.dataset.category || '';
            const activeCategoryText = document.getElementById('posActiveCategoryText');
            if (activeCategoryText) {
                activeCategoryText.textContent = this.textContent.trim();
            }

            const query = searchInput ? searchInput.value.trim() : '';
            loadCatalog(query, currentCategoryId);
        });
    });

    // Search input handler
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchDebounceTimer);
            const query = e.target.value.trim();

            if (query.length > 0) {
                clearSearchBtn.classList.remove('hidden');
            } else {
                clearSearchBtn.classList.add('hidden');
                searchResultsBox.classList.add('hidden');
                searchResultsBox.innerHTML = '';
            }

            searchDebounceTimer = setTimeout(() => {
                loadCatalog(query, currentCategoryId);
            }, 250);
        });

        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            clearSearchBtn.classList.add('hidden');
            searchResultsBox.classList.add('hidden');
            searchResultsBox.innerHTML = '';
            loadCatalog('', currentCategoryId);
        });
    }

    // Add product to cart
    function addToCart(prod) {
        hideError();
        const existing = cart.find(x => x.product_id === prod.id);
        if (existing) {
            if (existing.quantity < prod.stock_quantity) {
                existing.quantity += 1;
            } else {
                showError(`Cannot add more than ${prod.stock_quantity} units available in inventory.`);
                return;
            }
        } else {
            cart.push({
                product_id: prod.id,
                name: prod.name,
                price: parseFloat(prod.price),
                stock_quantity: parseInt(prod.stock_quantity),
                sku: prod.sku || '',
                quantity: 1
            });
        }
        renderCart();
    }

    window.posUpdateQty = function(productId, delta) {
        hideError();
        const item = cart.find(x => x.product_id === productId);
        if (!item) return;

        const newQty = item.quantity + delta;
        if (newQty <= 0) {
            cart = cart.filter(x => x.product_id !== productId);
        } else if (newQty <= item.stock_quantity) {
            item.quantity = newQty;
        } else {
            showError(`Cannot exceed available inventory of ${item.stock_quantity} units.`);
        }
        renderCart();
    };

    window.posRemoveItem = function(productId) {
        hideError();
        cart = cart.filter(x => x.product_id !== productId);
        renderCart();
    };

    // Render cart items & totals
    function renderCart() {
        const totalItemsCount = cart.reduce((acc, it) => acc + it.quantity, 0);
        cartCountBadge.textContent = `${totalItemsCount} ${totalItemsCount === 1 ? 'item' : 'items'}`;

        if (cart.length === 0) {
            cartListBox.innerHTML = `
                <div id="posEmptyCartNotice" class="py-lg text-center text-on-surface-variant/70 flex flex-col items-center gap-xs border border-dashed border-outline-variant/40 rounded-xl">
                    <span class="material-symbols-outlined text-3xl text-outline-variant">remove_shopping_cart</span>
                    <span class="text-xs font-medium px-md">
                        ${isStorePickup ? 'No additional in-store items. (You can complete store pickup without adding any extra items)' : 'No items in sale yet. Click [+ Add] on products from the catalog.'}
                    </span>
                </div>
            `;
        } else {
            cartListBox.innerHTML = cart.map(it => {
                const lineTotal = it.price * it.quantity;
                return `
                    <div class="p-sm bg-surface-container-low rounded-xl flex items-center justify-between gap-sm border border-outline-variant/20">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-on-surface truncate">${esc(it.name)}</p>
                            <p class="text-[11px] text-outline font-mono">₱${it.price.toFixed(2)} ea • Line: <span class="font-bold text-on-surface">₱${lineTotal.toFixed(2)}</span></p>
                        </div>
                        <div class="flex items-center gap-xs">
                            <div class="inline-flex items-center border border-outline-variant/40 rounded-lg overflow-hidden bg-surface-container-lowest">
                                <button type="button" onclick="posUpdateQty(${it.product_id}, -1)" class="w-6 h-6 flex items-center justify-center font-bold text-xs hover:bg-surface-container">-</button>
                                <span class="px-2 font-mono font-bold text-xs">${it.quantity}</span>
                                <button type="button" onclick="posUpdateQty(${it.product_id}, 1)" class="w-6 h-6 flex items-center justify-center font-bold text-xs hover:bg-surface-container">+</button>
                            </div>
                            <button type="button" onclick="posRemoveItem(${it.product_id})" class="p-1 text-error hover:bg-error/10 rounded-lg" title="Remove">
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        const additionalSubtotal = cart.reduce((acc, it) => acc + (it.price * it.quantity), 0);

        if (isStorePickup) {
            if (additionalSubtotalEl) additionalSubtotalEl.textContent = formatMoney(additionalSubtotal);
            const updatedGrandTotal = onlineOrderBaseAmount + additionalSubtotal;
            if (updatedGrandTotalEl) updatedGrandTotalEl.textContent = formatMoney(updatedGrandTotal);
            const dueNow = isOnlinePaid ? additionalSubtotal : updatedGrandTotal;
            if (amountDueNowEl) amountDueNowEl.textContent = formatMoney(dueNow);
            calculateChange(dueNow);
        } else {
            if (walkinSubtotalEl) walkinSubtotalEl.textContent = formatMoney(additionalSubtotal);
            if (amountDueNowEl) amountDueNowEl.textContent = formatMoney(additionalSubtotal);
            calculateChange(additionalSubtotal);
        }
    }

    // Cash calculations
    function calculateChange(dueAmount) {
        const tendered = parseFloat(cashInput.value) || 0;
        const change = Math.max(0, tendered - dueAmount);
        changeDueText.textContent = formatMoney(change);
    }

    if (cashInput) {
        cashInput.addEventListener('input', () => {
            const due = getDueAmount();
            calculateChange(due);
        });
    }

    document.querySelectorAll('.pos-denom-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const val = this.dataset.val;
            const due = getDueAmount();
            if (val === 'exact') {
                cashInput.value = due.toFixed(2);
            } else {
                const addVal = parseFloat(val) || 0;
                const current = parseFloat(cashInput.value) || 0;
                cashInput.value = (current + addVal).toFixed(2);
            }
            calculateChange(due);
        });
    });

    function getDueAmount() {
        const additionalSubtotal = cart.reduce((acc, it) => acc + (it.price * it.quantity), 0);
        if (isStorePickup) {
            return isOnlinePaid ? additionalSubtotal : (onlineOrderBaseAmount + additionalSubtotal);
        }
        return additionalSubtotal;
    }

    function showError(msg) {
        errBox.textContent = msg;
        errBox.classList.remove('hidden');
    }

    function hideError() {
        errBox.classList.add('hidden');
        errBox.textContent = '';
    }

    // Submit handler
    if (submitBtn) {
        submitBtn.addEventListener('click', async function() {
            hideError();

            const due = getDueAmount();
            const selectedPaymentMethod = document.querySelector('input[name="pos_payment_method"]:checked')?.value || 'cash';

            if (!isStorePickup && cart.length === 0) {
                showError('Please add at least one product to the sale before completing.');
                return;
            }

            // If cash and customer owes an amount, verify tendered cash
            if (selectedPaymentMethod === 'cash' && due > 0) {
                const tendered = parseFloat(cashInput.value) || 0;
                if (tendered < due) {
                    showError(`Tendered cash (${formatMoney(tendered)}) is less than total due (${formatMoney(due)}).`);
                    return;
                }
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-symbols-outlined text-[20px] animate-spin">progress_activity</span><span>Processing...</span>';

            const payload = new FormData();
            payload.append(CSRF_TOKEN_NAME, CSRF_HASH_VAL);
            payload.append('counter_payment_method', selectedPaymentMethod);
            payload.append('items', JSON.stringify(cart.map(i => ({
                product_id: i.product_id,
                quantity: i.quantity
            }))));

            let endpoint = '';
            if (isStorePickup) {
                endpoint = `${BASE_URL}/tenant/pos/complete-pickup`;
                payload.append('order_id', storePickupOrderId);
            } else {
                endpoint = `${BASE_URL}/tenant/pos/complete-walkin`;
                const custName = document.getElementById('posCustomerName')?.value.trim() || '';
                payload.append('customer_name', custName);
            }

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: payload
                });

                const data = await response.json();

                if (!data.success) {
                    showError(data.error || 'Failed to complete transaction.');
                    resetSubmitButton();
                    return;
                }

                // Show Receipt Modal
                showSuccessReceipt(data, selectedPaymentMethod, due);

            } catch (err) {
                showError('Network or server error while completing transaction. Please try again.');
                resetSubmitButton();
            }
        });
    }

    function resetSubmitButton() {
        submitBtn.disabled = false;
        submitBtn.innerHTML = `<span class="material-symbols-outlined text-[20px]">check_circle</span><span>${isStorePickup ? 'Complete Store Pick-up' : 'Complete Walk-in Sale'}</span>`;
    }

    function showSuccessReceipt(data, paymentMethod, dueAmount) {
        successMessage.textContent = data.message || 'Sale processed successfully.';

        const tendered = parseFloat(cashInput.value) || dueAmount;
        const change = Math.max(0, tendered - dueAmount);

        let linesHtml = `
            <div class="flex justify-between py-1 border-b border-outline-variant/20">
                <span class="text-outline">Order Reference:</span>
                <span class="font-bold text-on-surface font-mono">${esc(data.order_number || ('#' + data.order_id))}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-outline-variant/20">
                <span class="text-outline">Payment Method:</span>
                <span class="font-bold uppercase text-on-surface">${paymentMethod}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-outline-variant/20">
                <span class="text-outline">Total Collected:</span>
                <span class="font-bold font-mono text-primary">${formatMoney(dueAmount)}</span>
            </div>
        `;

        if (paymentMethod === 'cash' && dueAmount > 0) {
            linesHtml += `
                <div class="flex justify-between py-1 border-b border-outline-variant/20">
                    <span class="text-outline">Cash Tendered:</span>
                    <span class="font-bold font-mono">${formatMoney(tendered)}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-outline-variant/20">
                    <span class="text-outline">Change Returned:</span>
                    <span class="font-bold font-mono text-green-700">${formatMoney(change)}</span>
                </div>
            `;
        }

        receiptSummary.innerHTML = linesHtml;
        successModal.classList.remove('hidden');
    }

    if (printReceiptBtn) {
        printReceiptBtn.addEventListener('click', () => {
            window.print();
        });
    }

    if (newSaleBtn) {
        newSaleBtn.addEventListener('click', () => {
            if (isStorePickup) {
                window.location.href = `${BASE_URL}/tenant/orders`;
            } else {
                window.location.reload();
            }
        });
    }

    // Modal functions for Store Pick-up selector
    window.openPickupSelectorModal = function() {
        const modal = document.getElementById('posPickupSelectorModal');
        if (modal) modal.classList.remove('hidden');
    };

    window.closePickupSelectorModal = function() {
        const modal = document.getElementById('posPickupSelectorModal');
        if (modal) modal.classList.add('hidden');
    };

    window.filterPickupOrders = function(query) {
        const q = query.toLowerCase().trim();
        document.querySelectorAll('.pos-pickup-row').forEach(row => {
            const searchData = row.dataset.search || '';
            if (q === '' || searchData.includes(q)) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
    };

    // QR Scanner & Verification Handlers
    let html5QrCode = null;

    window.openQrScannerModal = function() {
        const modal = document.getElementById('posQrScannerModal');
        const feedback = document.getElementById('posScanFeedback');
        const manualInput = document.getElementById('posManualOrderCode');
        const placeholder = document.getElementById('posQrCameraPlaceholder');

        if (feedback) {
            feedback.classList.add('hidden');
            feedback.textContent = '';
        }
        if (manualInput) {
            manualInput.value = '';
            manualInput.disabled = false;
        }
        if (placeholder) {
            placeholder.classList.remove('hidden');
            placeholder.innerHTML = `
                <span class="material-symbols-outlined text-4xl mb-1 text-secondary animate-pulse">photo_camera</span>
                <span class="text-xs font-semibold">Starting camera...</span>
                <span class="text-[10px] opacity-70 mt-1">Please allow camera permissions if prompted</span>
            `;
        }

        if (modal) modal.classList.remove('hidden');

        if (window.Html5Qrcode) {
            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("posQrReader");
            }

            const config = { fps: 10, qrbox: { width: 220, height: 220 } };
            html5QrCode.start(
                { facingMode: "environment" },
                config,
                (decodedText) => {
                    stopQrScanner();
                    sendVerifyQrRequest(decodedText);
                },
                (errorMessage) => {
                    // Ignore frame scan failures
                }
            ).then(() => {
                if (placeholder) placeholder.classList.add('hidden');
            }).catch(err => {
                if (placeholder) {
                    placeholder.classList.remove('hidden');
                    placeholder.innerHTML = `
                        <span class="material-symbols-outlined text-3xl mb-1 text-amber-400">videocam_off</span>
                        <span class="text-xs font-semibold">Camera unavailable or blocked</span>
                        <span class="text-[10px] opacity-70 mt-1">Enter order number manually below</span>
                    `;
                }
            });
        }
    };

    window.stopQrScanner = function() {
        if (html5QrCode) {
            try {
                if (html5QrCode.isScanning) {
                    html5QrCode.stop().catch(e => console.error(e));
                }
            } catch (e) {
                console.error(e);
            }
        }
    };

    window.closeQrScannerModal = function() {
        stopQrScanner();
        const modal = document.getElementById('posQrScannerModal');
        if (modal) modal.classList.add('hidden');
    };

    window.verifyManualOrderCode = function() {
        const input = document.getElementById('posManualOrderCode');
        const val = input ? input.value.trim() : '';
        if (!val) {
            const feedback = document.getElementById('posScanFeedback');
            if (feedback) {
                feedback.className = 'text-xs rounded-xl p-sm font-semibold bg-amber-100 text-amber-900 border border-amber-300';
                feedback.textContent = 'Please enter an order number or scan a QR code.';
                feedback.classList.remove('hidden');
            }
            return;
        }
        stopQrScanner();
        sendVerifyQrRequest(val);
    };

    // Allow Enter key in manual order code input
    const manualOrderInput = document.getElementById('posManualOrderCode');
    if (manualOrderInput) {
        manualOrderInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                verifyManualOrderCode();
            }
        });
    }

    function sendVerifyQrRequest(code) {
        const feedback = document.getElementById('posScanFeedback');
        const manualBtn = document.getElementById('posManualLookupBtn');
        const manualInput = document.getElementById('posManualOrderCode');

        if (feedback) {
            feedback.className = 'text-xs rounded-xl p-sm font-semibold bg-primary/10 text-primary border border-primary/20 flex items-center gap-xs';
            feedback.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span> Verifying Store Pick-up Order...';
            feedback.classList.remove('hidden');
        }
        if (manualBtn) manualBtn.disabled = true;
        if (manualInput) manualInput.disabled = true;

        const fd = new FormData();
        fd.append('qr_code', code);
        fd.append(CSRF_TOKEN_NAME, CSRF_HASH_VAL);

        fetch(`${BASE_URL}/tenant/pos/verify-qr`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
        .then(r => r.json().then(data => ({ status: r.status, data: data })))
        .then(({ status, data }) => {
            if (manualBtn) manualBtn.disabled = false;
            if (manualInput) manualInput.disabled = false;
            if (data.csrf_hash) CSRF_HASH_VAL = data.csrf_hash;

            if (data.success) {
                if (feedback) {
                    feedback.className = 'text-xs rounded-xl p-sm font-semibold bg-green-100 text-green-900 border border-green-300';
                    feedback.textContent = `${data.message} Customer notified. Loading order...`;
                }
                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 600);
            } else {
                if (feedback) {
                    feedback.className = 'text-xs rounded-xl p-sm font-semibold bg-red-100 text-red-900 border border-red-300';
                    feedback.textContent = data.error || 'Verification failed.';
                }
            }
        })
        .catch(() => {
            if (manualBtn) manualBtn.disabled = false;
            if (manualInput) manualInput.disabled = false;
            if (feedback) {
                feedback.className = 'text-xs rounded-xl p-sm font-semibold bg-red-100 text-red-900 border border-red-300';
                feedback.textContent = 'Server or network error while verifying order.';
            }
        });
    }

    // Initialize catalog
    loadCatalog('', '');
    renderCart();
})();
</script>
<?= $this->endSection() ?>
