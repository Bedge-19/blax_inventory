<?= $this->extend('layouts/tenant') ?>
<?= $this->section('content') ?>

<div class="flex-1 space-y-lg">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-md rounded-xl bg-green-100 text-green-800 text-sm font-medium mb-lg"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-md rounded-xl bg-error-container/40 text-on-error-container text-sm font-medium mb-lg"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-md">
        <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant/20 shadow-sm">
            <div class="flex justify-between items-start mb-sm">
                <div class="p-xs bg-primary-container/10 rounded-lg">
                    <span class="material-symbols-outlined text-primary">shopping_cart</span>
                </div>
            </div>
            <h3 class="text-label-sm text-on-surface-variant font-medium">Total Orders</h3>
            <p class="text-headline-md font-bold mt-xs"><?= number_format((int) $summary['total_orders']) ?></p>
        </div>
        <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant/20 shadow-sm">
            <div class="flex justify-between items-start mb-sm">
                <div class="p-xs bg-tertiary-container/10 rounded-lg">
                    <span class="material-symbols-outlined text-tertiary">local_shipping</span>
                </div>
            </div>
            <h3 class="text-label-sm text-on-surface-variant font-medium">Pending Shipments</h3>
            <p class="text-headline-md font-bold mt-xs"><?= number_format((int) $summary['pending_shipments']) ?></p>
        </div>
        <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant/20 shadow-sm">
            <div class="flex justify-between items-start mb-sm">
                <div class="p-xs bg-secondary-container/30 rounded-lg">
                    <span class="material-symbols-outlined text-secondary">storefront</span>
                </div>
            </div>
            <h3 class="text-label-sm text-on-surface-variant font-medium">Ready for Pickup</h3>
            <p class="text-headline-md font-bold mt-xs"><?= number_format((int) $summary['ready_for_pickup']) ?></p>
        </div>
        <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant/20 shadow-sm">
            <div class="flex justify-between items-start mb-sm">
                <div class="p-xs bg-secondary-container/30 rounded-lg">
                    <span class="material-symbols-outlined text-secondary">payments</span>
                </div>
            </div>
            <h3 class="text-label-sm text-on-surface-variant font-medium">Revenue Today</h3>
            <p class="text-headline-md font-bold mt-xs">₱<?= number_format((float) $summary['revenue_today'], 2) ?></p>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="glass-card rounded-xl overflow-hidden shadow-sm">
        <div class="px-lg py-md flex flex-wrap justify-between items-center gap-md border-b border-outline-variant/30">
            <div class="flex items-center gap-md">
                <h3 class="text-title-lg font-bold text-on-surface">All Orders</h3>
                <a href="<?= base_url('tenant/pos') ?>" class="flex items-center gap-xs px-md py-xs bg-secondary text-on-secondary rounded-lg text-label-sm font-bold hover:bg-secondary/90 shadow-sm transition-all cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">point_of_sale</span>
                    <span>Open POS</span>
                </a>
            </div>
            <form method="get" action="<?= base_url('tenant/orders') ?>" class="flex flex-wrap items-center gap-sm">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                    <input name="q" value="<?= esc($filters['q']) ?>" placeholder="Search order, customer, or SKU..." class="pl-xl pr-md py-sm bg-surface-container-low border border-outline-variant rounded-lg text-body-md focus:outline-none focus:ring-2 focus:ring-primary" type="text">
                </div>
                <select name="status" class="bg-surface-container-low border border-outline-variant rounded-lg px-md py-sm text-label-sm font-label-sm text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All Statuses</option>
                    <?php foreach ($statusOptions as $val => $label): ?>
                        <option value="<?= esc($val) ?>" <?= $filters['status'] === $val ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <input name="from" value="<?= esc($filters['from']) ?>" type="date" class="bg-surface-container-low border border-outline-variant rounded-lg px-md py-sm text-label-sm font-label-sm text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-primary">
                <input name="to" value="<?= esc($filters['to']) ?>" type="date" class="bg-surface-container-low border border-outline-variant rounded-lg px-md py-sm text-label-sm font-label-sm text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-primary">
                <button type="submit" class="bg-primary text-on-primary px-md py-sm rounded-lg text-label-sm font-semibold hover:bg-primary/90 transition-colors">Filter</button>
                <a href="<?= base_url('tenant/orders') ?>" class="text-on-surface-variant hover:text-on-surface text-label-sm font-semibold py-sm transition-colors">Reset</a>
            </form>
        </div>

        <div class="responsive-table">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low border-b border-outline-variant/30">
                    <tr>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Order ID</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Customer</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Date</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider text-center">Fulfillment</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Amount</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider text-center">Status</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $o): ?>
                            <?php
                            $fullName = trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? ''));
                            $initials = $fullName !== '' ? mb_strtoupper(mb_substr($fullName, 0, 2)) : 'GU';
                            $profileImage = trim((string) ($o['profile_image_url'] ?? ''));
                            $isPickup = ($o['fulfillment_method'] ?? 'delivery') === 'pickup';
                            ?>
                            <tr class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="px-lg py-md">
                                    <span class="font-mono text-body-md font-semibold text-primary">#<?= esc($o['order_number']) ?></span>
                                </td>
                                <td class="px-lg py-md">
                                    <div class="flex items-center gap-sm">
                                        <div class="w-8 h-8 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center text-label-sm font-bold <?= $profileImage !== '' ? 'relative overflow-hidden' : '' ?>">
                                            <span><?= esc($initials) ?></span>
                                            <?php if ($profileImage !== ''): ?>
                                                <img class="absolute inset-0 w-full h-full object-cover" src="<?= esc(base_url($profileImage)) ?>" alt="<?= esc($fullName) ?> avatar" loading="lazy" onerror="this.remove();">
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-body-md text-on-surface"><?= esc($fullName !== '' ? $fullName : 'Customer') ?></span>
                                    </div>
                                </td>
                                <td class="px-lg py-md text-body-md text-on-surface-variant"><?= esc(date('M d, Y h:i A', strtotime($o['placed_at']))) ?></td>
                                <td class="px-lg py-md text-center">
                                    <?php if ($isPickup): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-500/10 text-amber-800 border border-amber-500/20 shadow-2xs">
                                            <span class="material-symbols-outlined text-[14px]">storefront</span>
                                            <span>Store Pick-up</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-500/10 text-blue-800 border border-blue-500/20 shadow-2xs">
                                            <span class="material-symbols-outlined text-[14px]">local_shipping</span>
                                            <span>Doorstep Delivery</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-lg py-md text-body-md font-bold text-on-surface">₱<?= number_format((float) $o['total_amount'], 2) ?></td>
                                <td class="px-lg py-md text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <?= status_badge($o['status']) ?>
                                        <?php if (($o['payment_status'] ?? '') === 'paid'): ?>
                                            <span class="inline-flex items-center gap-xs px-2 py-0.5 rounded-full text-[11px] font-bold bg-green-100 text-green-800">
                                                <span class="material-symbols-outlined text-[12px]">verified</span> Paid
                                            </span>
                                            <?php if (!in_array($o['status'], ['delivered', 'completed'], true)): ?>
                                                <span class="text-[10px] text-amber-700 font-medium">Escrow Holding</span>
                                            <?php else: ?>
                                                <span class="text-[10px] text-green-700 font-medium">Released</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-lg py-md text-right">
                                    <div class="flex justify-end items-center gap-sm">
                                        <?php if ($o['status'] === 'processing'): ?>
                                            <?php
                                             $locationStr = trim(($o['address_line1'] ?? '') . ', ' . ($o['city'] ?? '')) ?: 'Polomolok, South Cotabato';
                                             $labelStr    = esc($o['address_label'] ?? 'Home');
                                             $phoneStr    = esc($o['customer_phone'] ?? ($o['phone'] ?? 'N/A'));
                                             $shopNameStr = esc($shop['shop_name'] ?? 'Blax Storefront');
                                            ?>
                                            <button type="button"
                                                    onclick="openOrderQrModal(
                                                        '<?= esc($o['order_number']) ?>',
                                                        '<?= $shopNameStr ?>',
                                                        '<?= esc($fullName !== '' ? $fullName : 'Customer') ?>',
                                                        '<?= esc($locationStr) ?>',
                                                        '<?= $labelStr ?>',
                                                        '<?= $phoneStr ?>',
                                                        '<?= esc(date('M d, Y h:i A', strtotime($o['placed_at']))) ?>',
                                                        '<?= esc(base_url('order/' . $o['order_number'])) ?>'
                                                    )"
                                                    class="p-xs hover:bg-primary/10 rounded text-primary transition-colors flex items-center gap-xs px-2 py-1 border border-primary/20"
                                                    title="Generate & Download QR Code">
                                                <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                                                <span class="text-[11px] font-bold">QR</span>
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($isPickup && !in_array($o['status'], ['pending', 'completed', 'delivered', 'cancelled'], true)): ?>
                                            <a href="<?= base_url('tenant/pos?order_id=' . $o['id']) ?>" 
                                               class="p-xs hover:bg-secondary-container/50 rounded text-secondary transition-colors flex items-center gap-xs px-2.5 py-1 border border-secondary/40 bg-secondary-container/20 font-bold text-[11px] shadow-sm" 
                                               title="Add POS for Store Pick-up">
                                                <span class="material-symbols-outlined text-[16px]">point_of_sale</span>
                                                <span><?= $o['status'] === 'ready_for_pickup' ? 'Add POS' : 'POS' ?></span>
                                            </a>
                                        <?php endif; ?>

                                        <button type="button"
                                                onclick="openOrderDetails(<?= (int) $o['id'] ?>)"
                                                class="p-xs hover:bg-surface-container-high rounded text-on-surface-variant"
                                                title="View Details">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                        <div class="relative">
                                            <button type="button" data-row="<?= (int) $o['id'] ?>" onclick="toggleDropdown(this)" class="more-toggle p-xs hover:bg-surface-container-high rounded text-on-surface-variant" title="More Actions" aria-haspopup="true" aria-expanded="false">
                                                <span class="material-symbols-outlined">more_vert</span>
                                            </button>
                                            <div id="more-menu-<?= (int) $o['id'] ?>" class="hidden more-menu z-50 bg-surface-container-lowest border border-outline-variant/30 rounded-xl shadow-lg p-sm min-w-[220px]" role="menu">
                                                <p class="text-label-sm font-bold text-on-surface-variant px-sm pb-xs">Update Status</p>
                                                <form action="<?= base_url('tenant/orders/update-status') ?>" method="POST" class="space-y-xs px-sm pb-sm">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                                                    <?php
                                                    // Dynamically constrain statuses based on fulfillment method
                                                    $rowStatusOptions = $isPickup
                                                        ? [
                                                            'pending'          => 'Pending',
                                                            'processing'       => 'Processing',
                                                            'ready_for_pickup' => 'Ready for Pickup',
                                                            'cancelled'        => 'Cancelled',
                                                        ]
                                                        : [
                                                            'pending'          => 'Pending',
                                                            'processing'       => 'Processing',
                                                            'shipped'          => 'Shipped',
                                                            'cancelled'        => 'Cancelled',
                                                        ];

                                                    if (!isset($rowStatusOptions[$o['status']])) {
                                                        $rowStatusOptions[$o['status']] = humanize_status($o['status']);
                                                    }
                                                    ?>
                                                    <select name="status" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-lg text-label-sm font-label-sm">
                                                        <?php foreach ($rowStatusOptions as $val => $label): ?>
                                                            <option value="<?= esc($val) ?>" <?= $o['status'] === $val ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="w-full py-sm bg-primary text-on-primary rounded-lg text-label-sm font-semibold hover:bg-primary/90">Apply Status</button>
                                                </form>
                                                <div class="border-t border-outline-variant/20 my-xs"></div>
                                                <?php if ($isPickup && !in_array($o['status'], ['pending', 'completed', 'delivered', 'cancelled'], true)): ?>
                                                    <a href="<?= base_url('tenant/pos?order_id=' . $o['id']) ?>" class="w-full text-left px-sm py-sm rounded-lg text-secondary font-bold text-label-sm hover:bg-secondary-container/20 flex items-center gap-xs">
                                                        <span class="material-symbols-outlined text-[18px]">point_of_sale</span> Store Pick-up POS
                                                    </a>
                                                <?php endif; ?>
                                                <button type="button" onclick="openOrderDetails(<?= (int) $o['id'] ?>)" class="w-full text-left px-sm py-sm rounded-lg text-on-surface-variant text-label-sm font-semibold hover:bg-surface-container-high flex items-center gap-xs">
                                                    <span class="material-symbols-outlined text-[18px]">visibility</span> View Details
                                                </button>
                                                <?php if ($o['status'] === 'processing'): ?>
                                                    <button type="button" onclick="openOrderQrModal(
                                                        '<?= esc($o['order_number']) ?>',
                                                        '<?= $shopNameStr ?>',
                                                        '<?= esc($fullName !== '' ? $fullName : 'Customer') ?>',
                                                        '<?= esc($locationStr) ?>',
                                                        '<?= $labelStr ?>',
                                                        '<?= $phoneStr ?>',
                                                        '<?= esc(date('M d, Y h:i A', strtotime($o['placed_at']))) ?>',
                                                        '<?= esc($o['order_number']) ?>'
                                                    )" class="w-full text-left px-sm py-sm rounded-lg text-primary text-label-sm font-semibold hover:bg-primary/10 flex items-center gap-xs mt-xs">
                                                        <span class="material-symbols-outlined text-[18px]">qr_code_2</span> Generate & Download QR
                                                    </button>
                                                <?php endif; ?>
                                                <div class="border-t border-outline-variant/20 my-xs"></div>
                                                <button type="button" onclick="openReportCustomerModal(<?= (int) ($o['customer_id'] ?? 0) ?>, '<?= esc($fullName !== '' ? $fullName : 'Customer', 'js') ?>', '<?= esc($o['order_number'], 'js') ?>')" class="w-full text-left px-sm py-sm rounded-lg text-error text-label-sm font-semibold hover:bg-error-container/20 flex items-center gap-xs">
                                                    <span class="material-symbols-outlined text-[18px]">flag</span> Report Customer
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="py-lg text-center text-on-surface-variant">No orders match your filters.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php
        $oTot  = (int) $pager->getTotal('orders');
        $oCur  = (int) $pager->getCurrentPage('orders');
        $oPag  = (int) $pager->getPageCount('orders');
        $oStart = $oTot === 0 ? 0 : ($oCur - 1) * 10 + 1;
        $oEnd   = min($oCur * 10, $oTot);
        ?>
        <?php if ($oPag > 1): ?>
            <div class="px-lg py-md bg-surface-container-low flex justify-between items-center border-t border-outline-variant/30 flex-wrap gap-sm">
                <p class="text-label-sm font-label-sm text-on-surface-variant">Showing <?= number_format($oStart) ?> to <?= number_format($oEnd) ?> of <?= number_format($oTot) ?> orders</p>
                <div class="flex items-center gap-xs">
                    <a class="p-sm rounded hover:bg-surface-container-high <?= $oCur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('orders') ?>">
                        <span class="material-symbols-outlined">chevron_left</span>
                    </a>
                    <?php
                    $w = [];
                    for ($i = 1; $i <= $oPag; $i++) {
                        if ($i === 1 || $i === $oPag || abs($i - $oCur) <= 2) { $w[] = $i; }
                    }
                    $pv = 0;
                    foreach ($w as $n):
                        if ($n - $pv > 1): ?><span class="px-xs text-outline">...</span><?php endif; ?>
                        <a class="w-8 h-8 rounded flex items-center justify-center text-label-sm <?= $oCur === $n ? 'bg-primary text-on-primary font-semibold' : 'hover:bg-surface-container-high' ?>" href="<?= $pager->getPageURI($n, 'orders') ?>"><?= $n ?></a>
                    <?php $pv = $n; endforeach; ?>
                    <a class="p-sm rounded hover:bg-surface-container-high <?= $oCur >= $oPag ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('orders') ?>">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest rounded-2xl p-xl max-w-lg w-full border border-outline-variant/30 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm mb-md">
            <h3 class="text-title-lg font-bold">Order <span id="omNumber" class="text-primary"></span></h3>
            <button onclick="document.getElementById('orderModal').classList.add('hidden')" class="text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="space-y-sm mb-md">
            <div class="flex justify-between items-center"><span class="text-label-sm text-on-surface-variant">Customer</span><div class="flex items-center gap-2"><span id="omCustomer" class="text-body-md font-semibold text-on-surface"></span><button type="button" id="omReportCustomerBtn" class="text-[11px] font-bold text-error hover:underline flex items-center gap-0.5"><span class="material-symbols-outlined text-[14px]">flag</span> Report</button></div></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Status</span><span id="omStatus" class="text-body-md text-on-surface"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Date</span><span id="omDate" class="text-body-md text-on-surface"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Payment</span><span id="omPayment" class="text-body-md text-on-surface uppercase"></span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Fulfillment</span><span id="omFulfillment" class="text-body-md text-on-surface"></span></div>
        </div>
        <div class="responsive-table rounded-xl border border-outline-variant/20">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="px-md py-sm text-label-sm font-bold text-on-surface-variant/70 uppercase tracking-wider">Product</th>
                        <th class="px-md py-sm text-label-sm font-bold text-on-surface-variant/70 uppercase tracking-wider text-right">Qty</th>
                        <th class="px-md py-sm text-label-sm font-bold text-on-surface-variant/70 uppercase tracking-wider text-right">Unit</th>
                        <th class="px-md py-sm text-label-sm font-bold text-on-surface-variant/70 uppercase tracking-wider text-right">Line</th>
                    </tr>
                </thead>
                <tbody id="omItems" class="divide-y divide-outline-variant/10"></tbody>
            </table>
        </div>
        <div class="space-y-sm mt-md border-t border-outline-variant/20 pt-md">
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Subtotal</span><span id="omSubtotal" class="text-body-md text-on-surface">₱0.00</span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Shipping</span><span id="omShipping" class="text-body-md text-on-surface">₱0.00</span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Tax</span><span id="omTax" class="text-body-md text-on-surface">₱0.00</span></div>
            <div class="flex justify-between"><span class="text-label-sm text-on-surface-variant">Total</span><span id="omTotal" class="text-body-md font-bold text-primary">₱0.00</span></div>
        </div>
        <div id="omAcceptContainer" class="hidden mt-md pt-md border-t border-outline-variant/20">
            <button type="button" id="omAcceptBtn" class="w-full py-md bg-primary text-on-primary rounded-xl font-bold hover:bg-primary/90 transition-all flex items-center justify-center gap-xs text-sm shadow-md active:scale-95">
                <span class="material-symbols-outlined text-[18px]">check_circle</span>
                <span>Accept &amp; Set to Processing</span>
            </button>
        </div>
        <div id="omQrContainer" class="hidden mt-md pt-md border-t border-outline-variant/20">
            <button type="button" id="omQrBtn" class="w-full py-sm bg-primary/10 text-primary border border-primary/30 rounded-xl font-semibold hover:bg-primary/20 transition-all flex items-center justify-center gap-xs text-label-sm">
                <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                <span>Generate & Download QR Code</span>
            </button>
        </div>
        <div id="omPosContainer" class="hidden mt-sm">
            <button type="button" id="omPosBtn" class="w-full py-sm bg-secondary text-on-secondary rounded-xl font-bold hover:bg-secondary/90 transition-all flex items-center justify-center gap-xs text-label-sm shadow-sm">
                <span class="material-symbols-outlined text-[18px]">point_of_sale</span>
                <span>Open POS</span>
            </button>
        </div>
    </div>
</div>

<!-- Order QR Code & Customer Info Modal -->
<div id="orderQrModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-md backdrop-blur-sm">
    <div class="glass-card bg-surface-container-lowest rounded-2xl p-lg md:p-xl max-w-lg w-full space-y-md border border-outline-variant/30 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        
        <button type="button" onclick="closeOrderQrModal()" class="absolute top-md right-md text-on-surface-variant hover:text-on-surface p-1 rounded-full hover:bg-surface-container">
            <span class="material-symbols-outlined">close</span>
        </button>

        <!-- Header Status & Order No -->
        <div class="flex items-center justify-between border-b border-outline-variant/20 pb-sm">
            <div>
                <span class="inline-flex items-center gap-xs px-md py-xs rounded-full text-label-sm font-bold bg-amber-100 text-amber-800">
                    <span class="material-symbols-outlined text-[14px]">sync</span> Processing Order
                </span>
                <h3 id="qrModalOrderNumber" class="text-headline-sm font-bold text-on-surface mt-xs font-mono"></h3>
            </div>
            <div class="text-right">
                <span id="qrModalPlacedAt" class="text-xs text-on-surface-variant font-medium"></span>
            </div>
        </div>

        <!-- 2-Column Table UI: QR on Left, Shop Name / Customer / Location on Right -->
        <div class="bg-surface-container-low p-md rounded-2xl border border-outline-variant/20">
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-md items-center">
                
                <!-- Left Side: QR Code -->
                <div class="sm:col-span-5 flex flex-col items-center justify-center">
                    <div class="bg-white p-2 rounded-xl border border-outline-variant/30 shadow-sm flex items-center justify-center">
                        <img id="qrModalImg" src="" alt="Order QR Code" class="w-36 h-36 object-contain rounded-lg">
                    </div>
                    <span class="text-[10px] text-on-surface-variant mt-xs text-center font-medium">Scan to Verify</span>
                </div>

                <!-- Right Side: Shop Name, Customer Name & Location Only -->
                <div class="sm:col-span-7 space-y-md border-t sm:border-t-0 sm:border-l border-outline-variant/20 pt-md sm:pt-0 sm:pl-md text-left">
                    
                    <!-- Shop Name & Customer Phone -->
                    <div class="flex items-start justify-between gap-xs">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-outline tracking-wider block">Shop Name</span>
                            <h4 id="qrModalShopName" class="text-title-md font-extrabold text-primary leading-tight mt-0.5"></h4>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-[10px] uppercase font-bold text-outline tracking-wider block">Customer Phone</span>
                            <span id="qrModalPhone" class="text-xs font-bold text-on-surface bg-surface-container-high px-2 py-0.5 rounded-lg font-mono"></span>
                        </div>
                    </div>

                    <div class="border-t border-outline-variant/15 pt-sm space-y-sm">
                        <!-- Customer Name -->
                        <div>
                            <span class="text-[10px] uppercase font-bold text-on-surface-variant/70 tracking-wider flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-[14px] text-primary">person</span> Customer Name
                            </span>
                            <p id="qrModalCustName" class="text-body-md font-bold text-on-surface truncate mt-0.5"></p>
                        </div>

                        <!-- Location -->
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] uppercase font-bold text-on-surface-variant/70 tracking-wider flex items-center gap-0.5">
                                    <span class="material-symbols-outlined text-[14px] text-primary">location_on</span> Location
                                </span>
                                <span id="qrModalAddressLabel" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-container uppercase"></span>
                            </div>
                            <p id="qrModalLocation" class="text-body-sm font-semibold text-on-surface-variant mt-0.5 leading-snug"></p>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <!-- Printable & Downloadable Actions -->
        <div class="flex gap-sm pt-xs">
            <button type="button" onclick="triggerQrDownload()" class="flex-1 py-md px-md bg-primary text-on-primary rounded-xl font-semibold hover:bg-primary/90 transition-all shadow-md active:scale-95 flex items-center justify-center gap-xs text-button font-button">
                <span class="material-symbols-outlined text-[18px]">download</span>
                <span>Download QR</span>
            </button>
            <button type="button" onclick="printQrCode()" class="flex-1 py-md px-md bg-surface-container-high text-on-surface rounded-xl font-semibold hover:bg-surface-variant transition-all active:scale-95 flex items-center justify-center gap-xs text-button font-button" title="Print Waybill & QR Code">
                <span class="material-symbols-outlined text-[18px]">print</span>
                <span>Print Waybill</span>
            </button>
        </div>

    </div>
</div>

<script>
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

    function money(v) {
        return '₱' + Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function openOrderDetails(id) {
        const url = "<?= base_url('tenant/orders/items') ?>/" + encodeURIComponent(id);
        document.getElementById('omItems').innerHTML = '<tr><td colspan="4" class="py-md text-center text-on-surface-variant">Loading...</td></tr>';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => r.json())
            .then((data) => {
                if (!data.success) {
                    document.getElementById('omItems').innerHTML = '<tr><td colspan="4" class="py-md text-center text-error">' + (data.error || 'Could not load order.') + '</td></tr>';
                    return;
                }
                const o = data.order;
                document.getElementById('omNumber').textContent = '#' + (o.order_number || '');
                document.getElementById('omCustomer').textContent = o.customer || 'Customer';

                const reportBtn = document.getElementById('omReportCustomerBtn');
                if (reportBtn) {
                    if (o.customer_id) {
                        reportBtn.classList.remove('hidden');
                        reportBtn.onclick = function() {
                            openReportCustomerModal(o.customer_id, o.customer, o.order_number);
                        };
                    } else {
                        reportBtn.classList.add('hidden');
                    }
                }

                document.getElementById('omStatus').textContent = o.status || '';
                document.getElementById('omDate').textContent = o.placed_at || '';
                document.getElementById('omPayment').textContent = o.payment_method || '';
                document.getElementById('omFulfillment').textContent = o.fulfillment_method || '';
                document.getElementById('omSubtotal').textContent = money(o.subtotal);
                document.getElementById('omShipping').textContent = money(o.shipping_fee);
                document.getElementById('omTax').textContent = money(o.tax_amount);
                document.getElementById('omTotal').textContent = money(o.total_amount);

                const tbody = document.getElementById('omItems');
                tbody.innerHTML = '';
                (data.items || []).forEach((it) => {
                    const tr = document.createElement('tr');
                    
                    // Product cell with image thumbnail
                    const prodTd = document.createElement('td');
                    prodTd.className = 'px-md py-sm text-body-md text-left font-medium text-on-surface';
                    
                    let imgTag = '';
                    if (it.image_url) {
                        imgTag = `<img src="${escapeHtml(it.image_url)}" class="w-10 h-10 object-cover rounded-lg border border-outline-variant/30 shrink-0 bg-surface-container" alt="${escapeHtml(it.product_name)}">`;
                    } else {
                        imgTag = `<div class="w-10 h-10 rounded-lg bg-surface-container flex items-center justify-center text-outline shrink-0"><span class="material-symbols-outlined text-[18px]">inventory_2</span></div>`;
                    }

                    let nameHtml = `<div><p class="font-semibold text-on-surface leading-snug">${escapeHtml(it.product_name)}</p>`;
                    if (it.variant_label) {
                        nameHtml += `<span class="inline-block text-[11px] font-semibold text-primary bg-primary/10 px-1.5 py-0.5 rounded mt-0.5">${escapeHtml(it.variant_label)}</span>`;
                    }
                    nameHtml += `</div>`;

                    prodTd.innerHTML = `<div class="flex items-center gap-sm">${imgTag}${nameHtml}</div>`;
                    tr.appendChild(prodTd);

                    // Qty cell
                    const qtyTd = document.createElement('td');
                    qtyTd.className = 'px-md py-sm text-body-md text-right text-on-surface-variant font-semibold';
                    qtyTd.textContent = it.quantity;
                    tr.appendChild(qtyTd);

                    // Unit price cell
                    const unitTd = document.createElement('td');
                    unitTd.className = 'px-md py-sm text-body-md text-right text-on-surface-variant';
                    unitTd.textContent = money(it.unit_price);
                    tr.appendChild(unitTd);

                    // Line total cell
                    const lineTd = document.createElement('td');
                    lineTd.className = 'px-md py-sm text-body-md text-right font-bold text-on-surface';
                    lineTd.textContent = money(it.line_total);
                    tr.appendChild(lineTd);

                    tbody.appendChild(tr);
                });
                if (!data.items || data.items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="py-md text-center text-on-surface-variant">No items recorded for this order.</td></tr>';
                }

                // Accept & Set to Processing Button (for pending orders)
                const acceptContainer = document.getElementById('omAcceptContainer');
                const acceptBtn = document.getElementById('omAcceptBtn');
                if (acceptContainer && acceptBtn) {
                    if (o.raw_status === 'pending') {
                        acceptContainer.classList.remove('hidden');
                        acceptBtn.onclick = function() {
                            acceptBtn.disabled = true;
                            acceptBtn.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span> Updating...';

                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = '<?= base_url('tenant/orders/update-status') ?>';
                            
                            const csrfInput = document.createElement('input');
                            csrfInput.type = 'hidden';
                            csrfInput.name = '<?= csrf_token() ?>';
                            csrfInput.value = document.querySelector('input[name="<?= csrf_token() ?>"]')?.value || '<?= csrf_hash() ?>';
                            form.appendChild(csrfInput);

                            const orderIdInput = document.createElement('input');
                            orderIdInput.type = 'hidden';
                            orderIdInput.name = 'order_id';
                            orderIdInput.value = id;
                            form.appendChild(orderIdInput);

                            const statusInput = document.createElement('input');
                            statusInput.type = 'hidden';
                            statusInput.name = 'status';
                            statusInput.value = 'processing';
                            form.appendChild(statusInput);

                            document.body.appendChild(form);
                            form.submit();
                        };
                    } else {
                        acceptContainer.classList.add('hidden');
                    }
                }

                const qrContainer = document.getElementById('omQrContainer');
                const qrBtn = document.getElementById('omQrBtn');
                if (qrContainer && qrBtn) {
                    if (o.raw_status === 'processing') {
                        qrContainer.classList.remove('hidden');
                        qrBtn.onclick = function() {
                            openOrderQrModal(
                                o.order_number,
                                o.shop_name || '<?= esc($shop['shop_name'] ?? 'Blax Storefront') ?>',
                                o.customer,
                                o.location || 'Polomolok, South Cotabato',
                                o.address_label || 'Home',
                                o.phone || 'N/A',
                                o.placed_at,
                                '<?= base_url('order/') ?>' + encodeURIComponent(o.order_number)
                            );
                        };
                    } else {
                        qrContainer.classList.add('hidden');
                    }
                }

                const posContainer = document.getElementById('omPosContainer');
                const posBtn = document.getElementById('omPosBtn');
                if (posContainer && posBtn) {
                    if (o.raw_fulfillment_method === 'pickup' && !['pending', 'completed', 'delivered', 'cancelled'].includes(o.raw_status)) {
                        posContainer.classList.remove('hidden');
                        posBtn.onclick = function() {
                            window.location.href = '<?= base_url('tenant/pos') ?>?order_id=' + id;
                        };
                    } else {
                        posContainer.classList.add('hidden');
                    }
                }

                document.getElementById('orderModal').classList.remove('hidden');
            })
            .catch(() => {
                document.getElementById('omItems').innerHTML = '<tr><td colspan="4" class="py-md text-center text-error">Could not load order.</td></tr>';
            });
    }

    let currentQrData = {
        orderNumber: '',
        shopName: '',
        customerName: '',
        location: '',
        addressLabel: '',
        phone: '',
        placedAt: '',
        qrUrl: '',
        filename: ''
    };

    function openOrderQrModal(orderNumber, shopName, customerName, location, addressLabel, phone, placedAt, verifyUrl) {
        closeMenus();
        const modal = document.getElementById('orderQrModal');
        if (!modal) return;

        document.getElementById('qrModalOrderNumber').textContent = '#' + orderNumber;
        document.getElementById('qrModalPlacedAt').textContent = placedAt || '';
        document.getElementById('qrModalShopName').textContent = shopName || 'Blax Storefront';
        document.getElementById('qrModalCustName').textContent = customerName || 'Customer';
        document.getElementById('qrModalLocation').textContent = location || 'Polomolok, South Cotabato';
        document.getElementById('qrModalAddressLabel').textContent = addressLabel || 'Home';
        document.getElementById('qrModalPhone').textContent = phone || 'N/A';

        const qrContent = orderNumber;
        const apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' + encodeURIComponent(qrContent);

        document.getElementById('qrModalImg').src = apiUrl;

        currentQrData = {
            orderNumber: orderNumber,
            shopName: shopName || 'Blax Storefront',
            customerName: customerName || 'Customer',
            location: location || 'Polomolok, South Cotabato',
            addressLabel: addressLabel || 'Home',
            phone: phone || 'N/A',
            placedAt: placedAt || '',
            qrUrl: apiUrl,
            filename: 'QR-' + orderNumber + '.png'
        };

        modal.classList.remove('hidden');
    }

    function closeOrderQrModal() {
        const modal = document.getElementById('orderQrModal');
        if (modal) modal.classList.add('hidden');
    }

    function triggerQrDownload() {
        if (!currentQrData.qrUrl) return;

        fetch(currentQrData.qrUrl)
            .then(res => res.blob())
            .then(blob => {
                const blobUrl = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = blobUrl;
                a.download = currentQrData.filename || 'Order-QR.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(blobUrl);
            })
            .catch(() => {
                window.open(currentQrData.qrUrl, '_blank');
            });
    }

    function printQrCode() {
        if (!currentQrData.qrUrl) return;
        const printWin = window.open('', '_blank');
        printWin.document.write(`
            <!DOCTYPE html>
            <html>
                <head>
                    <title>Order Waybill - #${currentQrData.orderNumber}</title>
                    <style>
                        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; padding: 20px; color: #1e293b; background: #ffffff; margin: 0; }
                        .card { border: 2px solid #2563eb; border-radius: 16px; padding: 20px; max-width: 500px; margin: 0 auto; background: #ffffff; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
                        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed #cbd5e1; padding-bottom: 12px; margin-bottom: 16px; }
                        .order-no { font-size: 22px; font-weight: 900; color: #0f172a; margin: 0; font-family: monospace; }
                        .badge { background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 99px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
                        
                        .main-grid { display: flex; gap: 20px; align-items: center; margin: 16px 0; }
                        .left-qr { width: 170px; text-align: center; }
                        .qr-img { width: 150px; height: 150px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 6px; background: #fff; }
                        .right-info { flex: 1; border-left: 2px solid #e2e8f0; padding-left: 18px; text-align: left; }
                        
                        .shop-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
                        .shop-label { font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin: 0; }
                        .shop-name { font-size: 18px; font-weight: 900; color: #2563eb; margin: 2px 0 0 0; line-height: 1.2; }
                        .phone-badge { font-size: 12px; font-weight: 800; color: #0f172a; font-family: monospace; background: #f1f5f9; padding: 3px 8px; border-radius: 6px; margin-top: 2px; }
                        
                        .field-group { margin-bottom: 10px; }
                        .field-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
                        .field-value { font-size: 14px; font-weight: 800; color: #0f172a; margin-top: 2px; line-height: 1.3; }
                        .address-label { display: inline-block; font-size: 10px; background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 4px; font-weight: 800; text-transform: uppercase; margin-left: 6px; }

                        .footer-note { text-align: center; font-size: 11px; color: #64748b; border-top: 1px dashed #e2e8f0; padding-top: 12px; margin-top: 16px; }
                    </style>
                </head>
                <body>
                    <div class="card">
                        <div class="header">
                            <div>
                                <span class="badge">Processing Order</span>
                                <h1 class="order-no">#${currentQrData.orderNumber}</h1>
                            </div>
                            <div style="font-size: 11px; color: #64748b; text-align: right;">${currentQrData.placedAt}</div>
                        </div>

                        <div class="main-grid">
                            <!-- Left Side: QR Code -->
                            <div class="left-qr">
                                <img src="${currentQrData.qrUrl}" class="qr-img" />
                                <p style="font-size: 10px; color: #64748b; margin-top: 4px; font-weight: 600;">Scan to Verify Order</p>
                            </div>

                            <!-- Right Side: Shop Name, Phone Number, Customer Name, Location -->
                            <div class="right-info">
                                <div class="shop-header">
                                    <div>
                                        <p class="shop-label">Shop Name</p>
                                        <h2 class="shop-name">${currentQrData.shopName}</h2>
                                    </div>
                                    <div style="text-align: right;">
                                        <p class="shop-label">Customer Phone</p>
                                        <div class="phone-badge">${currentQrData.phone}</div>
                                    </div>
                                </div>

                                <div class="field-group">
                                    <span class="field-label">Customer Name</span>
                                    <div class="field-value">${currentQrData.customerName}</div>
                                </div>

                                <div class="field-group">
                                    <span class="field-label">Location</span> <span class="address-label">${currentQrData.addressLabel}</span>
                                    <div class="field-value">${currentQrData.location}</div>
                                </div>
                            </div>
                        </div>

                        <div class="footer-note">
                            Blax Storefront Order Waybill &amp; Verification System
                        </div>
                    </div>
                    <script>window.onload = function() { window.print(); window.close(); }<\/script>
                </body>
            </html>
        `);
        printWin.document.close();
    }

    function escHtml(str) {
        return String(str || '').replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    function handleExportClick(btn, label) {
        const icon = btn.querySelector('.material-symbols-outlined');
        const textSpan = btn.querySelector('span:not(.material-symbols-outlined)');
        const origIcon = icon ? icon.textContent : 'download';
        const origText = textSpan ? textSpan.textContent : 'Export';
        if (icon) {
            icon.textContent = 'progress_activity';
            icon.classList.add('animate-spin');
        }
        if (textSpan) textSpan.textContent = label || 'Exporting...';
        btn.classList.add('opacity-75', 'pointer-events-none');
        setTimeout(() => {
            if (icon) {
                icon.textContent = origIcon;
                icon.classList.remove('animate-spin');
            }
            if (textSpan) textSpan.textContent = origText;
            btn.classList.remove('opacity-75', 'pointer-events-none');
        }, 3500);
    }

    function openReportCustomerModal(customerId, customerName, orderNumber) {
        document.getElementById('rcCustomerId').value = customerId;
        document.getElementById('rcCustomerName').textContent = customerName + (orderNumber ? ' (Order #' + orderNumber + ')' : '');
        document.getElementById('reportCustomerModal').classList.remove('hidden');
    }
    function closeReportCustomerModal() {
        document.getElementById('reportCustomerModal').classList.add('hidden');
    }
</script>

<!-- Modal: Report Customer -->
<div id="reportCustomerModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl max-w-md w-full p-lg shadow-2xl space-y-md">
        <div class="flex items-center justify-between border-b border-outline-variant/20 pb-sm">
            <div class="flex items-center gap-sm">
                <span class="p-2 bg-error-container/30 text-error rounded-xl material-symbols-outlined">flag</span>
                <div>
                    <h3 class="text-title-md font-bold text-on-surface">Report Customer</h3>
                    <p id="rcCustomerName" class="text-xs text-on-surface-variant font-semibold">Customer Name</p>
                </div>
            </div>
            <button type="button" onclick="closeReportCustomerModal()" class="text-outline hover:text-on-surface p-1 rounded-full"><span class="material-symbols-outlined">close</span></button>
        </div>

        <form action="<?= base_url('tenant/compliance/report-customer') ?>" method="POST" class="space-y-md">
            <?= csrf_field() ?>
            <input type="hidden" name="customer_id" id="rcCustomerId">

            <div>
                <label class="text-xs font-bold text-on-surface-variant uppercase">Issue Type</label>
                <select name="issue_type" required class="w-full mt-1 p-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:ring-2 focus:ring-primary">
                    <option value="Bogus Buyer / Refused Order">Bogus Buyer / Refused Order</option>
                    <option value="Unresponsive Customer">Unresponsive Customer</option>
                    <option value="Abusive Language / Harassment">Abusive Language / Harassment</option>
                    <option value="Payment Dispute">Payment Dispute</option>
                    <option value="Suspicious / Fraudulent Account">Suspicious / Fraudulent Account</option>
                    <option value="Other Policy Violation">Other Policy Violation</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-on-surface-variant uppercase">Incident Description & Evidence</label>
                <textarea name="description" rows="4" required class="w-full mt-1 p-3 bg-surface-container-low border border-outline-variant rounded-xl text-xs focus:ring-2 focus:ring-primary" placeholder="Detail the incident, dates, messages, or order reference..."></textarea>
            </div>

            <div class="flex gap-sm pt-sm border-t border-outline-variant/20">
                <button type="button" onclick="closeReportCustomerModal()" class="flex-1 py-2 rounded-xl text-xs font-bold border border-outline-variant hover:bg-surface-container">Cancel</button>
                <button type="submit" class="flex-1 py-2 rounded-xl text-xs font-bold bg-error text-on-error hover:bg-error/90 shadow-sm">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>