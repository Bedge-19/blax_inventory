<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?= esc($order['order_number'] ?? $printing['request_number'] ?? 'Sales Receipt') ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <style>
        :root {
            --primary: #2563eb;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #cbd5e1;
            --bg-paper: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #f1f5f9;
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            padding: 24px 16px;
        }

        .no-print-bar {
            width: 100%;
            max-width: 400px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }

        .btn-primary {
            background-color: #0f172a;
            color: #ffffff;
            flex: 1;
        }
        .btn-primary:hover {
            background-color: #1e293b;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
        }
        .btn-secondary:hover {
            background-color: #cbd5e1;
        }

        /* Standard 80mm POS Thermal Receipt Layout */
        .receipt-card {
            width: 100%;
            max-width: 380px;
            background: var(--bg-paper);
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
            padding: 24px 20px;
            position: relative;
        }

        .receipt-header {
            text-align: center;
            border-bottom: 1.5px dashed var(--border-color);
            padding-bottom: 14px;
            margin-bottom: 14px;
        }

        .shop-name {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.3px;
        }

        .shop-meta {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 3px;
            line-height: 1.4;
        }

        .badge-official {
            display: inline-block;
            margin-top: 8px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            padding: 3px 10px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            color: #334155;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 12px;
            font-size: 11.5px;
            border-bottom: 1.5px dashed var(--border-color);
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 9.5px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .meta-val {
            font-weight: 700;
            color: #0f172a;
            font-family: 'JetBrains Mono', monospace;
            margin-top: 1px;
        }

        /* Products Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 14px;
        }

        .items-table th {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            padding-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
        }

        .items-table td {
            padding: 8px 0;
            vertical-align: top;
            border-bottom: 1px dashed #f1f5f9;
        }

        .item-title {
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
        }

        .item-variant {
            font-size: 10px;
            font-weight: 600;
            color: #2563eb;
            margin-top: 2px;
        }

        .item-calc {
            font-size: 10.5px;
            color: var(--text-muted);
            font-family: 'JetBrains Mono', monospace;
            margin-top: 2px;
        }

        .item-price {
            text-align: right;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            color: #0f172a;
            white-space: nowrap;
        }

        /* Summary section */
        .summary-box {
            border-top: 1.5px dashed var(--border-color);
            padding-top: 12px;
            margin-bottom: 16px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 11.5px;
            color: var(--text-muted);
            margin-bottom: 5px;
            font-family: 'JetBrains Mono', monospace;
        }

        .summary-row.bold {
            font-weight: 700;
            color: #0f172a;
        }

        .summary-row.grand-total {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            border-top: 1.5px solid #0f172a;
            border-bottom: 1.5px solid #0f172a;
            padding: 8px 0;
            margin: 8px 0;
        }

        /* QR Code Container */
        .qr-section {
            text-align: center;
            border-top: 1.5px dashed var(--border-color);
            padding-top: 14px;
            margin-top: 10px;
        }

        .qr-img {
            width: 140px;
            height: 140px;
            margin: 0 auto 6px auto;
            display: block;
            border: 2px solid #0f172a;
            border-radius: 12px;
            padding: 4px;
            background: #fff;
        }

        .qr-label {
            font-size: 10.5px;
            font-weight: 700;
            color: #0f172a;
            font-family: 'JetBrains Mono', monospace;
        }

        .qr-subtext {
            font-size: 9.5px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px dashed var(--border-color);
            font-size: 10px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
            }

            .no-print-bar {
                display: none !important;
            }

            .receipt-card {
                max-width: 100% !important;
                width: 100% !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                padding: 10px 8px !important;
            }

            @page {
                margin: 6mm 4mm;
                size: auto;
            }
        }
    </style>
</head>
<body>

    <!-- On-screen Control Action Bar -->
    <div class="no-print-bar">
        <button class="btn btn-primary" onclick="window.print()">
            <span class="material-symbols-outlined" style="font-size: 18px;">print</span>
            <span>Print Receipt</span>
        </button>
        <button class="btn btn-secondary" onclick="window.close()">
            <span class="material-symbols-outlined" style="font-size: 18px;">close</span>
            <span>Close</span>
        </button>
    </div>

    <!-- Printable Receipt Container -->
    <div class="receipt-card">

        <!-- 1. Shop Header -->
        <div class="receipt-header">
            <div class="shop-name"><?= esc($shop['shop_name'] ?? 'Blax Store Partner') ?></div>
            <div class="shop-meta">
                <?php 
                    $shopLocation = implode(', ', array_filter([
                        $shop['street'] ?? '',
                        $shop['barangay'] ?? '',
                        $shop['address_line'] ?? '',
                        $shop['city'] ?? 'Polomolok',
                        $shop['province'] ?? 'South Cotabato'
                    ]));
                ?>
                <div><?= esc($shopLocation) ?></div>
                <?php if (!empty($shop['phone_number'])): ?>
                    <div>Tel: <?= esc($shop['phone_number']) ?></div>
                <?php endif; ?>
            </div>
            <span class="badge-official">
                <?= ($type ?? 'order') === 'printing' ? 'Printing Job Ticket & Receipt' : 'Official Sales Receipt' ?>
            </span>
        </div>

        <!-- 2. Order Meta Grid -->
        <div class="meta-grid">
            <div class="meta-item">
                <span class="meta-label">Reference</span>
                <span class="meta-val">#<?= esc($order['order_number'] ?? $printing['request_number'] ?? 'N/A') ?></span>
            </div>
            <div class="meta-item" style="text-align: right;">
                <span class="meta-label">Date & Time</span>
                <span class="meta-val" style="font-size: 10.5px;">
                    <?= date('M d, Y • h:i A', strtotime($order['completed_at'] ?? $order['placed_at'] ?? $printing['completed_at'] ?? $printing['created_at'] ?? 'now')) ?>
                </span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Customer</span>
                <span class="meta-val" style="font-size: 11px;">
                    <?php 
                        $custName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
                        if ($custName === '' && !empty($order['cancel_reason']) && str_starts_with($order['cancel_reason'], 'Walk-in:')) {
                            $custName = substr($order['cancel_reason'], 8);
                        }
                    ?>
                    <?= esc($custName ?: 'Counter Customer') ?>
                </span>
            </div>
            <div class="meta-item" style="text-align: right;">
                <span class="meta-label">Fulfillment</span>
                <?php 
                    $ful = strtolower(trim((string) ($order['fulfillment_method'] ?? 'pickup')));
                    $fulLabel = ($ful === 'pickup' || $ful === 'store pick-up') ? 'Store Pick-up' : 'Doorstep Delivery';
                ?>
                <span class="meta-val" style="font-size: 11px;"><?= esc($fulLabel) ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Cashier / Staff</span>
                <span class="meta-val" style="font-size: 10.5px;"><?= esc($cashierName ?? 'Staff') ?></span>
            </div>
            <div class="meta-item" style="text-align: right;">
                <span class="meta-label">Status</span>
                <span class="meta-val" style="color: #059669; font-size: 11px;">
                    <?= strtoupper($order['payment_status'] ?? 'PAID') ?>
                </span>
            </div>
        </div>

        <!-- 3. Items Table -->
        <?php if (($type ?? 'order') === 'printing' && !empty($printing)): ?>
            <!-- Printing Specs -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="text-align: left;">Printing Specs</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="item-title"><?= esc($printing['file_name'] ?? 'Document Print Job') ?></div>
                            <div class="item-variant"><?= esc($printing['paper_size'] ?? 'A4') ?> • <?= esc($printing['color_mode'] ?? 'Color') ?> • <?= esc($printing['binding_option'] ?: 'No Binding') ?></div>
                            <div class="item-calc"><?= (int) ($printing['page_count'] ?? 1) ?> pages &times; <?= (int) ($printing['copies'] ?? 1) ?> copies</div>
                        </td>
                        <td class="item-price">
                            ₱<?= number_format((float) ($printing['total_price'] ?? 0), 2) ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        <?php else: ?>
            <!-- Product Items List -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="text-align: left; width: 65%;">Item & Details</th>
                        <th style="text-align: right; width: 35%;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td>
                                    <div class="item-title"><?= esc($it['product_name'] ?? 'Product Item') ?></div>
                                    <?php if (!empty($it['variant_label'])): ?>
                                        <div class="item-variant"><?= esc($it['variant_label']) ?></div>
                                    <?php endif; ?>
                                    <div class="item-calc">
                                        <?= (int) ($it['quantity'] ?? 1) ?> &times; ₱<?= number_format((float) ($it['unit_price'] ?? 0), 2) ?>
                                    </div>
                                </td>
                                <td class="item-price">
                                    ₱<?= number_format((float) ($it['line_total'] ?? (($it['unit_price'] ?? 0) * ($it['quantity'] ?? 1))), 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" style="text-align: center; color: var(--text-muted); padding: 12px 0;">
                                Counter Sale Transaction
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- 4. Summary & Payments -->
        <div class="summary-box">
            <?php 
                $subtotal = (float) ($order['subtotal'] ?? $printing['total_price'] ?? 0);
                $shipping = (float) ($order['shipping_fee'] ?? 0);
                $posAdd   = (float) ($order['pos_additional_amount'] ?? 0);
                $grand    = (float) ($order['total_amount'] ?? $printing['total_price'] ?? 0);
                $payMethod = strtoupper($order['pos_payment_method'] ?? $order['payment_method'] ?? 'CASH');
                if ($payMethod === 'COUNTER_CASH') $payMethod = 'CASH';
            ?>
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>₱<?= number_format($subtotal, 2) ?></span>
            </div>

            <?php if ($shipping > 0): ?>
                <div class="summary-row">
                    <span>Delivery Fee:</span>
                    <span>₱<?= number_format($shipping, 2) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($posAdd > 0 && $posAdd != $grand): ?>
                <div class="summary-row">
                    <span>In-Store POS Additions:</span>
                    <span>+₱<?= number_format($posAdd, 2) ?></span>
                </div>
            <?php endif; ?>

            <div class="summary-row grand-total">
                <span>TOTAL AMOUNT:</span>
                <span>₱<?= number_format($grand, 2) ?></span>
            </div>

            <div class="summary-row bold">
                <span>Payment Method:</span>
                <span><?= esc($payMethod) ?></span>
            </div>
        </div>

        <!-- 5. Verification QR Code Section -->
        <div class="qr-section">
            <img src="<?= esc($qrUrl) ?>" alt="Order QR Code" class="qr-img" />
            <div class="qr-label">#<?= esc($order['order_number'] ?? $printing['request_number'] ?? 'VERIFIED') ?></div>
            <div class="qr-subtext">Scan with Blax POS or Camera to verify order authenticity</div>
        </div>

        <!-- 6. Footer Notes -->
        <div class="receipt-footer">
            <p><strong>Thank you for choosing <?= esc($shop['shop_name'] ?? 'our store') ?>!</strong></p>
            <p style="margin-top: 3px;">Please keep this receipt for order tracking & pick-up verification.</p>
            <p style="margin-top: 3px; font-size: 9px; opacity: 0.7;">Powered by Blax Inventory • Polomolok, South Cotabato</p>
        </div>

    </div>

    <script>
        // Automatically trigger print dialog when opened in a new tab
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
