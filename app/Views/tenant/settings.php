<?= $this->extend('layouts/tenant') ?>
<?= $this->section('content') ?>

<?php
    $mfOpen    = isset($businessHours[1]) && !empty($businessHours[1]['open_time'])  ? substr($businessHours[1]['open_time'], 0, 5)  : '08:00';
    $mfClose   = isset($businessHours[1]) && !empty($businessHours[1]['close_time']) ? substr($businessHours[1]['close_time'], 0, 5) : '18:00';
    $satOpen   = isset($businessHours[6]) && !empty($businessHours[6]['open_time'])  ? substr($businessHours[6]['open_time'], 0, 5)  : '09:00';
    $satClose  = isset($businessHours[6]) && !empty($businessHours[6]['close_time']) ? substr($businessHours[6]['close_time'], 0, 5) : '15:00';
    $sunClosed = (int) ($businessHours[0]['is_closed'] ?? 1);
    $logoUrl   = $shop['logo_url'] ?? '';
    $initials  = strtoupper(substr(trim((string) ($shop['shop_name'] ?? 'RHK')), 0, 2));
    $shopSlug  = $shop['slug'] ?? $shop['id'] ?? '';
    $hasCoords = !empty($shop['latitude']) && !empty($shop['longitude']);
?>

<div class="flex-1 space-y-6 max-w-7xl mx-auto">

    <!-- Flash Alerts -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center gap-3 shadow-2xs animate-in fade-in duration-200">
            <span class="material-symbols-outlined text-[20px] text-emerald-600">check_circle</span>
            <span><?= esc(session()->getFlashdata('success')) ?></span>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-4 rounded-2xl bg-error-container/40 border border-error/20 text-on-error-container text-sm font-medium flex items-center gap-3 shadow-2xs animate-in fade-in duration-200">
            <span class="material-symbols-outlined text-[20px] text-error">error</span>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
        </div>
    <?php endif; ?>

    <!-- Header & Storefront Status Banner -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 soft-shadow p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="p-2 rounded-xl bg-primary/10 text-primary">
                    <span class="material-symbols-outlined text-[24px]">storefront</span>
                </span>
                <div>
                    <h2 class="text-headline-sm font-bold text-on-surface">Store Settings</h2>
                    <p class="text-body-sm text-on-surface-variant">Manage your shop profile, payout destination, operating hours, and store preferences.</p>
                </div>
            </div>
        </div>

        <!-- Quick Storefront Link & Status Badge -->
        <div class="flex items-center gap-3 shrink-0 flex-wrap">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-surface-container-low border border-outline-variant/30 text-xs">
                <span class="w-2 h-2 rounded-full <?= ($shop['status'] ?? '') === 'active' ? 'bg-emerald-500' : 'bg-amber-500' ?>"></span>
                <span class="font-bold text-on-surface capitalize"><?= esc($shop['status'] ?? 'Active') ?> Merchant</span>
            </div>

            <?php if (!empty($shopSlug)): ?>
                <a href="<?= base_url('shop/' . $shopSlug) ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-primary text-on-primary hover:bg-primary/90 transition-all shadow-2xs">
                    <span>View Public Storefront</span>
                    <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Responsive Sub-Navigation Tabs -->
    <nav class="flex items-center gap-2 border-b border-outline-variant/30 pb-px overflow-x-auto" aria-label="Settings Tabs">
        <button type="button" onclick="switchSettingsTab('profile')" id="tab-btn-profile" class="settings-tab-btn inline-flex items-center gap-2 px-4 py-3 border-b-2 text-xs font-bold transition-all whitespace-nowrap border-primary text-primary">
            <span class="material-symbols-outlined text-[18px]">store</span>
            <span>Shop Profile</span>
        </button>
        <button type="button" onclick="switchSettingsTab('payment')" id="tab-btn-payment" class="settings-tab-btn inline-flex items-center gap-2 px-4 py-3 border-b-2 text-xs font-bold transition-all whitespace-nowrap border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant">
            <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
            <span>GCash Payouts</span>
        </button>
        <button type="button" onclick="switchSettingsTab('hours')" id="tab-btn-hours" class="settings-tab-btn inline-flex items-center gap-2 px-4 py-3 border-b-2 text-xs font-bold transition-all whitespace-nowrap border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant">
            <span class="material-symbols-outlined text-[18px]">schedule</span>
            <span>Operating Hours</span>
        </button>
        <button type="button" onclick="switchSettingsTab('preferences')" id="tab-btn-preferences" class="settings-tab-btn inline-flex items-center gap-2 px-4 py-3 border-b-2 text-xs font-bold transition-all whitespace-nowrap border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant">
            <span class="material-symbols-outlined text-[18px]">tune</span>
            <span>Services & Alerts</span>
        </button>
    </nav>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 1: SHOP PROFILE                                                    -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div id="tab-panel-profile" class="settings-tab-panel space-y-6">
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 soft-shadow p-6 md:p-8 space-y-8">
            
            <!-- Section Header -->
            <div class="flex items-start justify-between border-b border-outline-variant/20 pb-4">
                <div>
                    <h3 class="text-title-md font-bold text-on-surface">Store Identity & Branding</h3>
                    <p class="text-xs text-on-surface-variant mt-0.5">Customize your brand logo, merchant name, and physical location in Polomolok.</p>
                </div>
            </div>

            <!-- Logo Upload Card -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 p-5 rounded-2xl bg-surface-container-low/50 border border-outline-variant/20">
                <!-- Avatar with Preview -->
                <div class="relative w-24 h-24 rounded-2xl overflow-hidden flex items-center justify-center bg-primary-container/20 border-2 border-outline-variant/40 shrink-0 shadow-2xs group">
                    <img id="logoPreviewImg" src="<?= !empty($logoUrl) ? esc(logo_url($logoUrl)) : '' ?>" alt="Logo" class="w-full h-full object-cover <?= empty($logoUrl) ? 'hidden' : '' ?>">
                    <span id="logoInitialsSpan" class="text-headline-md font-bold text-primary <?= !empty($logoUrl) ? 'hidden' : '' ?>"><?= esc($initials) ?></span>
                    
                    <button type="button" onclick="document.getElementById('logoInput').click()" class="absolute inset-0 bg-black/40 text-white flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-[11px] font-bold gap-1 cursor-pointer" title="Change logo">
                        <span class="material-symbols-outlined text-[20px]">photo_camera</span>
                        <span>Change</span>
                    </button>
                </div>

                <div class="space-y-2 flex-1">
                    <div>
                        <h4 class="text-sm font-bold text-on-surface">Shop Brand Logo</h4>
                        <p class="text-xs text-on-surface-variant mt-0.5">Displays across the storefront, product catalog, search results, and printed receipts.</p>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-[11px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded-md border border-outline-variant/30">
                            Square 1:1 • Min 300×300px • Max 2MB
                        </span>
                        <span class="text-[11px] text-on-surface-variant">JPG, PNG, WEBP, GIF</span>
                    </div>

                    <!-- Hidden Logo Form for Direct Upload -->
                    <form action="<?= base_url('tenant/settings/logo') ?>" method="POST" enctype="multipart/form-data" id="logoForm" class="hidden">
                        <?= csrf_field() ?>
                        <input type="file" name="logo" id="logoInput" accept="image/jpeg,image/png,image/webp,image/gif">
                    </form>

                    <div class="pt-1 flex items-center gap-2">
                        <button type="button" onclick="document.getElementById('logoInput').click()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-bold transition-colors text-xs border border-outline-variant/30 shadow-2xs">
                            <span class="material-symbols-outlined text-[16px] text-primary">upload</span>
                            <span>Upload New Logo</span>
                        </button>
                        <span id="logoUploadHint" class="text-xs text-on-surface-variant hidden"></span>
                    </div>
                </div>
            </div>

            <!-- Profile Details Form -->
            <form action="<?= base_url('tenant/settings/save') ?>" method="POST" class="space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="profile">
                <input type="hidden" name="latitude" id="shop_latitude" value="<?= esc($shop['latitude'] ?? '') ?>">
                <input type="hidden" name="longitude" id="shop_longitude" value="<?= esc($shop['longitude'] ?? '') ?>">

                <!-- Shop Name -->
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-on-surface uppercase tracking-wider block" for="shop_name">
                        Shop Name <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant">
                            <span class="material-symbols-outlined text-[18px]">storefront</span>
                        </span>
                        <input type="text" name="shop_name" id="shop_name" value="<?= esc($shop['shop_name'] ?? '') ?>" placeholder="e.g. RHK General Merchandise" class="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:outline-none transition-all" required>
                    </div>
                </div>

                <!-- Shop Description -->
                <div class="space-y-1.5">
                    <div class="flex justify-between items-center">
                        <label class="text-xs font-bold text-on-surface uppercase tracking-wider block" for="description">
                            Shop Bio / Description
                        </label>
                        <span id="charCount" class="text-[11px] text-on-surface-variant font-mono">0 / 500</span>
                    </div>
                    <textarea name="description" id="description" rows="3" maxlength="500" placeholder="Briefly describe your products, specialties, and customer policy..." class="w-full p-3.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:outline-none transition-all leading-relaxed"><?= esc($shop['description'] ?? '') ?></textarea>
                </div>

                <!-- Physical Address Card -->
                <div class="space-y-4 pt-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-on-surface">Store Location in Polomolok</h4>
                            <p class="text-xs text-on-surface-variant">Accurate address coordinates enable doorstep deliveries and store pick-up routing.</p>
                        </div>
                        <?php if ($hasCoords): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-500/10 text-emerald-700 border border-emerald-500/20" title="Latitude: <?= esc($shop['latitude']) ?>, Longitude: <?= esc($shop['longitude']) ?>">
                                <span class="material-symbols-outlined text-[14px]">pin_drop</span>
                                <span>GPS Synced</span>
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-amber-500/10 text-amber-700 border border-amber-500/20">
                                <span class="material-symbols-outlined text-[14px]">location_searching</span>
                                <span>Pending Geocode</span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Street Address -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block" for="street">
                                Street Address / Bldg / Purok
                            </label>
                            <div id="shop-autocomplete-wrapper" class="hidden mb-2"></div>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant">
                                    <span class="material-symbols-outlined text-[18px]">location_city</span>
                                </span>
                                <input type="text" name="street" id="street" value="<?= esc($shop['street'] ?? '') ?>" placeholder="House No., Street Name, Purok / Bldg" class="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:outline-none transition-all">
                            </div>
                        </div>

                        <!-- Barangay -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block" for="barangay">
                                Barangay (Polomolok)
                            </label>
                            <div class="relative">
                                <select name="barangay" id="barangay" class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:outline-none transition-all">
                                    <option value="">-- Select Barangay --</option>
                                    <?php
                                    $validBarangays = [
                                        'Bentung', 'Cannery Site', 'Crossing Palkan', 'Glamang', 'Kinilis',
                                        'Klinan 6', 'Koronadal Proper', 'Lam-Caliaf', 'Landan', 'Lumakil',
                                        'Maligo', 'Palkan', 'Poblacion', 'Polo', 'Pula Bato', 'Rubber',
                                        'Silway 7', 'Silway 8', 'Sulit', 'Sumbakil', 'Upper Klinan',
                                        'Pagalungan', 'Magsaysay'
                                    ];
                                    $curBrgy = $shop['barangay'] ?? '';
                                    foreach ($validBarangays as $b): ?>
                                        <option value="<?= esc($b) ?>" <?= ($curBrgy === $b) ? 'selected' : '' ?>><?= esc($b) ?></option>
                                    <?php endforeach; ?>
                                    <?php if ($curBrgy !== '' && !in_array($curBrgy, $validBarangays, true)): ?>
                                        <option value="<?= esc($curBrgy) ?>" selected><?= esc($curBrgy) ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Full Address Line -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block" for="address_line">
                            Address Line (Full Formatted Address)
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant">
                                <span class="material-symbols-outlined text-[18px]">map</span>
                            </span>
                            <input type="text" name="address_line" id="address_line" value="<?= esc($shop['address_line'] ?? '') ?>" placeholder="e.g. Poblacion, Polomolok, South Cotabato" class="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:outline-none transition-all">
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-outline-variant/20 flex items-center justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary rounded-xl font-bold hover:bg-primary/90 transition-all shadow-sm flex items-center gap-2 text-xs">
                        <span class="material-symbols-outlined text-[18px]">save</span>
                        <span>Save Store Profile</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 2: GCASH PAYOUTS                                                   -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div id="tab-panel-payment" class="settings-tab-panel hidden space-y-6">
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 soft-shadow p-6 md:p-8 space-y-6 max-w-2xl">
            
            <div class="flex items-start justify-between border-b border-outline-variant/20 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                            <span class="material-symbols-outlined text-[18px]">payments</span>
                        </span>
                        <h3 class="text-title-md font-bold text-on-surface">GCash Disbursement Destination</h3>
                    </div>
                    <p class="text-xs text-on-surface-variant mt-1">Configure the registered GCash account where your marketplace earnings will be deposited.</p>
                </div>
            </div>

            <!-- Payout Advisory Card -->
            <div class="p-4 rounded-2xl bg-blue-500/10 border border-blue-500/20 text-xs text-on-surface space-y-1.5">
                <div class="flex items-center gap-2 font-bold text-blue-800">
                    <span class="material-symbols-outlined text-[18px]">verified_user</span>
                    <span>GCash KYC Verification Notice</span>
                </div>
                <p class="text-blue-900/80 leading-relaxed text-[11px]">
                    Ensure your GCash account name exactly matches the name registered with GCash to prevent transfer delays or bank reversals. Payouts are reviewed and disbursed by the admin team.
                </p>
            </div>

            <form action="<?= base_url('tenant/settings/save') ?>" method="POST" class="space-y-5">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="payment">

                <!-- GCash Number -->
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-on-surface uppercase tracking-wider block" for="gcash_number">
                        GCash Mobile Number <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant">
                            <span class="material-symbols-outlined text-[18px]">phone_iphone</span>
                        </span>
                        <input type="text" name="gcash_number" id="gcash_number" value="<?= esc($shop['gcash_number'] ?? '') ?>" placeholder="09171234567" maxlength="13" required class="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-mono font-semibold focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:outline-none transition-all">
                    </div>
                    <p class="text-[11px] text-on-surface-variant">Format: 11-digit Philippine mobile number starting with 09.</p>
                </div>

                <!-- Confirm GCash Number -->
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-on-surface uppercase tracking-wider block" for="confirm_gcash_number">
                        Confirm GCash Mobile Number <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant">
                            <span class="material-symbols-outlined text-[18px]">verified</span>
                        </span>
                        <input type="text" name="confirm_gcash_number" id="confirm_gcash_number" value="<?= esc($shop['gcash_number'] ?? '') ?>" placeholder="Re-enter 09XXXXXXXXX" maxlength="13" required class="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-mono font-semibold focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:outline-none transition-all">
                    </div>
                    <p class="text-[11px] text-on-surface-variant">Must exactly match the GCash mobile number entered above.</p>
                </div>

                <!-- Account Name -->
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-on-surface uppercase tracking-wider block" for="gcash_account_name">
                        GCash Registered Account Name <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant">
                            <span class="material-symbols-outlined text-[18px]">person</span>
                        </span>
                        <input type="text" name="gcash_account_name" id="gcash_account_name" value="<?= esc($shop['gcash_account_name'] ?? '') ?>" placeholder="e.g. Juan Dela Cruz / RHK Merchandise" maxlength="100" required class="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:outline-none transition-all">
                    </div>
                    <p class="text-[11px] text-on-surface-variant">The full name registered with your verified GCash mobile wallet.</p>
                </div>

                <!-- Ownership Declaration Checkbox -->
                <div class="p-3.5 rounded-xl bg-surface-container-low border border-outline-variant/40 flex items-start gap-3">
                    <input type="checkbox" name="gcash_confirm_ownership" id="gcash_confirm_ownership" value="1" required class="mt-0.5 w-4 h-4 rounded text-primary focus:ring-primary border-outline-variant cursor-pointer">
                    <label for="gcash_confirm_ownership" class="text-xs text-on-surface leading-snug cursor-pointer select-none font-medium">
                        I confirm that the GCash account information I provided is correct and belongs to me/my business.
                    </label>
                </div>

                <div class="pt-3 border-t border-outline-variant/20 flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary rounded-xl font-bold hover:bg-primary/90 transition-all shadow-sm flex items-center gap-2 text-xs">
                        <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                        <span>Save Payment Details</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 3: OPERATING HOURS                                                 -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div id="tab-panel-hours" class="settings-tab-panel hidden space-y-6">
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 soft-shadow p-6 md:p-8 space-y-6 max-w-3xl">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-outline-variant/20 pb-4">
                <div>
                    <h3 class="text-title-md font-bold text-on-surface">Store Operating Hours</h3>
                    <p class="text-xs text-on-surface-variant mt-0.5">Let customers know when your counter is open for orders and pickups.</p>
                </div>
                <div class="flex items-center gap-1 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full font-semibold shrink-0">
                    <span class="material-symbols-outlined text-[16px]">sync</span>
                    <span id="hoursFeedback">Auto-saved when changed</span>
                </div>
            </div>

            <!-- Schedule Configuration Cards -->
            <div class="space-y-4">
                
                <!-- Monday to Friday -->
                <div class="p-4 rounded-2xl bg-surface-container-low/50 border border-outline-variant/30 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">M-F</span>
                            <div>
                                <h4 class="text-xs font-bold text-on-surface">Monday – Friday (Weekdays)</h4>
                                <p class="text-[11px] text-on-surface-variant">Core business days</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-1">
                        <div>
                            <label class="text-[11px] font-bold text-on-surface-variant block mb-1" for="open_mf">Opening Time</label>
                            <input type="time" id="open_mf" value="<?= esc($mfOpen) ?>" class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl text-xs font-medium focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-on-surface-variant block mb-1" for="close_mf">Closing Time</label>
                            <input type="time" id="close_mf" value="<?= esc($mfClose) ?>" class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl text-xs font-medium focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Saturday -->
                <div class="p-4 rounded-2xl bg-surface-container-low/50 border border-outline-variant/30 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">SAT</span>
                            <div>
                                <h4 class="text-xs font-bold text-on-surface">Saturday (Weekend)</h4>
                                <p class="text-[11px] text-on-surface-variant">Half-day or weekend schedule</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-1">
                        <div>
                            <label class="text-[11px] font-bold text-on-surface-variant block mb-1" for="open_6">Opening Time</label>
                            <input type="time" id="open_6" value="<?= esc($satOpen) ?>" class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl text-xs font-medium focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-on-surface-variant block mb-1" for="close_6">Closing Time</label>
                            <input type="time" id="close_6" value="<?= esc($satClose) ?>" class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl text-xs font-medium focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Sunday -->
                <div class="p-4 rounded-2xl bg-surface-container-low/50 border border-outline-variant/30 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-xl bg-surface-container-high text-on-surface-variant flex items-center justify-center font-bold text-xs">SUN</span>
                        <div>
                            <h4 class="text-xs font-bold text-on-surface">Sunday</h4>
                            <p class="text-[11px] text-on-surface-variant">Weekly store closure or holiday</p>
                        </div>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="closed_0" <?= $sunClosed ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-surface-container-highest border border-outline-variant/50 rounded-full peer peer-checked:bg-primary transition-colors after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all after:shadow-sm peer-checked:after:translate-x-5 peer-checked:after:border-white"></div>
                        <span class="ml-3 text-xs font-bold text-on-surface-variant">Closed on Sunday</span>
                    </label>
                </div>

            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 4: SERVICES & ALERTS                                               -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div id="tab-panel-preferences" class="settings-tab-panel hidden space-y-6">
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 soft-shadow p-6 md:p-8 space-y-8 max-w-3xl">
            
            <div class="flex items-start justify-between border-b border-outline-variant/20 pb-4">
                <div>
                    <h3 class="text-title-md font-bold text-on-surface">Store Services & Notification Preferences</h3>
                    <p class="text-xs text-on-surface-variant mt-0.5">Toggle optional services like document printing and configure automatic merchant alerts.</p>
                </div>
            </div>

            <!-- Service Offerings -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Service Modules</h4>
                    <span id="servicesFeedback" class="text-xs text-emerald-700 font-semibold"></span>
                </div>

                <div class="space-y-3">
                    <!-- Printing Service Toggle -->
                    <div class="p-4 rounded-2xl bg-surface-container-low/50 border border-outline-variant/30 flex items-center justify-between gap-4 transition-all hover:bg-surface-container-low">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px] text-primary">print</span>
                                <span class="font-bold text-sm text-on-surface">Document &amp; Photo Printing Services</span>
                            </div>
                            <p class="text-xs text-on-surface-variant leading-relaxed">
                                Allows customers in Polomolok to submit custom printing requests (PDF, DOCX, photos) directly to your store counter.
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" id="service_offers_printing" data-module="offers_printing" <?= !empty($shop['offers_printing']) ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-11 h-6 bg-surface-container-highest border border-outline-variant/50 rounded-full peer peer-checked:bg-primary transition-colors after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all after:shadow-sm peer-checked:after:translate-x-5 peer-checked:after:border-white"></div>
                        </label>
                    </div>

                    <!-- Doorstep Delivery Service Toggle -->
                    <div class="p-4 rounded-2xl bg-surface-container-low/50 border border-outline-variant/30 flex items-center justify-between gap-4 transition-all hover:bg-surface-container-low">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px] text-[#007DFE]">local_shipping</span>
                                <span class="font-bold text-sm text-on-surface">Doorstep Delivery Services</span>
                            </div>
                            <p class="text-xs text-on-surface-variant leading-relaxed">
                                Allows customers in Polomolok to request direct doorstep delivery (COD &amp; Online) straight to their residence or business address.
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" id="service_offers_delivery" data-module="offers_delivery" <?= (isset($shop['offers_delivery']) && (int)$shop['offers_delivery'] === 0) ? '' : 'checked' ?> class="sr-only peer">
                            <div class="w-11 h-6 bg-surface-container-highest border border-outline-variant/50 rounded-full peer peer-checked:bg-primary transition-colors after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all after:shadow-sm peer-checked:after:translate-x-5 peer-checked:after:border-white"></div>
                        </label>
                    </div>

                    <!-- Store Pick-up Service Toggle -->
                    <div class="p-4 rounded-2xl bg-surface-container-low/50 border border-outline-variant/30 flex items-center justify-between gap-4 transition-all hover:bg-surface-container-low">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px] text-secondary">storefront</span>
                                <span class="font-bold text-sm text-on-surface">Store Pick-up Services</span>
                            </div>
                            <p class="text-xs text-on-surface-variant leading-relaxed">
                                Allows customers to place orders online and personally claim packages at your physical counter with verified pick-up passes.
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" id="service_offers_pickup" data-module="offers_pickup" <?= (isset($shop['offers_pickup']) && (int)$shop['offers_pickup'] === 0) ? '' : 'checked' ?> class="sr-only peer">
                            <div class="w-11 h-6 bg-surface-container-highest border border-outline-variant/50 rounded-full peer peer-checked:bg-primary transition-colors after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all after:shadow-sm peer-checked:after:translate-x-5 peer-checked:after:border-white"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Merchant Notification Alerts -->
            <div class="space-y-4 pt-2">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Merchant Notifications</h4>
                    <span id="notifFeedback" class="text-xs text-emerald-700 font-semibold"></span>
                </div>

                <div class="divide-y divide-outline-variant/20 rounded-2xl bg-surface-container-low/50 border border-outline-variant/30 overflow-hidden">
                    
                    <!-- New Orders -->
                    <div class="p-4 flex items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <p class="font-bold text-xs text-on-surface">New Order Notifications</p>
                            <p class="text-[11px] text-on-surface-variant">Receive in-app alerts and notifications whenever a customer places an order.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" id="pref_new_orders" <?= !empty($notifPrefs['new_orders']) ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-11 h-6 bg-surface-container-highest border border-outline-variant/50 rounded-full peer peer-checked:bg-primary transition-colors after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all after:shadow-sm peer-checked:after:translate-x-5 peer-checked:after:border-white"></div>
                        </label>
                    </div>

                    <!-- Low Stock Alerts -->
                    <div class="p-4 flex items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <p class="font-bold text-xs text-on-surface">Low Stock Inventory Warnings</p>
                            <p class="text-[11px] text-on-surface-variant">Alert me when any product stock count drops to 5 units or below.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" id="pref_low_stock" <?= !empty($notifPrefs['low_stock']) ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-11 h-6 bg-surface-container-highest border border-outline-variant/50 rounded-full peer peer-checked:bg-primary transition-colors after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all after:shadow-sm peer-checked:after:translate-x-5 peer-checked:after:border-white"></div>
                        </label>
                    </div>

                </div>
            </div>

        </div>
    </div>

</div>

<!-- CSRF Hidden Form for AJAX -->
<form id="settingsCsrfForm" class="hidden"><?= csrf_field() ?></form>

<!-- Toast Notification -->
<div id="settingsToast" class="hidden fixed bottom-6 right-6 z-50 px-5 py-3 rounded-2xl shadow-xl text-xs font-bold text-on-primary transition-all duration-300 transform"></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>
(function () {
    // ── 1. Tab Switching with Hash Support ─────────────────────────
    const tabs = ['profile', 'payment', 'hours', 'preferences'];

    window.switchSettingsTab = function (tabName) {
        if (!tabs.includes(tabName)) tabName = 'profile';

        tabs.forEach(t => {
            const panel = document.getElementById('tab-panel-' + t);
            const btn = document.getElementById('tab-btn-' + t);

            if (t === tabName) {
                if (panel) panel.classList.remove('hidden');
                if (btn) {
                    btn.classList.remove('border-transparent', 'text-on-surface-variant');
                    btn.classList.add('border-primary', 'text-primary');
                }
            } else {
                if (panel) panel.classList.add('hidden');
                if (btn) {
                    btn.classList.remove('border-primary', 'text-primary');
                    btn.classList.add('border-transparent', 'text-on-surface-variant');
                }
            }
        });

        // Sync URL hash without triggering scroll jump
        history.replaceState(null, null, '#' + tabName);
    };

    // Auto-select tab based on URL query param (?tab=...) or hash (#...) on page load
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        const hash = window.location.hash.replace('#', '');

        if (tabParam && tabs.includes(tabParam)) {
            switchSettingsTab(tabParam);
        } else if (tabs.includes(hash)) {
            switchSettingsTab(hash);
        }
    });

    // ── 2. Toast Notifications ─────────────────────────────────────
    function csrfToken() {
        return document.querySelector('#settingsCsrfForm [name="csrf_test_name"]').value;
    }

    var toast = document.getElementById('settingsToast');
    var toastTimer = null;

    function showToast(message, isError) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.remove('hidden', 'bg-red-600', 'bg-emerald-600');
        toast.classList.add(isError ? 'bg-red-600' : 'bg-emerald-600');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.classList.add('hidden'); }, 3500);
    }

    async function postSettings(body) {
        try {
            var resp = await fetch("<?= base_url('tenant/settings/save') ?>", {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            });
            var data = await resp.json();
            showToast(data.message || (data.success ? 'Saved.' : 'Save failed.'), !data.success);
            return data.success;
        } catch (err) {
            showToast('Could not reach the server.', true);
            return false;
        }
    }

    // ── 3. Character Counter for Description ───────────────────────
    var descEl = document.getElementById('description');
    var charCountEl = document.getElementById('charCount');
    if (descEl && charCountEl) {
        function updateCharCount() {
            charCountEl.textContent = descEl.value.length + ' / 500';
        }
        descEl.addEventListener('input', updateCharCount);
        updateCharCount();
    }

    // ── 4. GCash Number Formatter ──────────────────────────────────
    var gcashEl = document.getElementById('gcash_number');
    if (gcashEl) {
        gcashEl.addEventListener('input', function (e) {
            var val = e.target.value.replace(/\D/g, '');
            if (val.length > 11) val = val.substring(0, 11);
            if (val.length > 4 && val.length <= 7) {
                val = val.substring(0, 4) + ' ' + val.substring(4);
            } else if (val.length > 7) {
                val = val.substring(0, 4) + ' ' + val.substring(4, 7) + ' ' + val.substring(7);
            }
            e.target.value = val;
        });
    }

    // ── 5. Instant Logo Preview & Immediate Submit ─────────────────
    var logoInput = document.getElementById('logoInput');
    var logoPreviewImg = document.getElementById('logoPreviewImg');
    var logoInitialsSpan = document.getElementById('logoInitialsSpan');
    var logoUploadHint = document.getElementById('logoUploadHint');

    if (logoInput) {
        logoInput.addEventListener('change', function () {
            if (logoInput.files && logoInput.files[0]) {
                var file = logoInput.files[0];
                var reader = new FileReader();
                reader.onload = function (e) {
                    if (logoPreviewImg) {
                        logoPreviewImg.src = e.target.result;
                        logoPreviewImg.classList.remove('hidden');
                    }
                    if (logoInitialsSpan) logoInitialsSpan.classList.add('hidden');
                    if (logoUploadHint) {
                        logoUploadHint.textContent = 'Uploading logo...';
                        logoUploadHint.classList.remove('hidden');
                    }
                    document.getElementById('logoForm').submit();
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // ── 6. Business Hours Auto-Save ────────────────────────────────
    function collectHours() {
        var fd = new FormData();
        fd.append('csrf_test_name', csrfToken());
        fd.append('section', 'hours');

        var mfOpen = document.getElementById('open_mf').value;
        var mfClose = document.getElementById('close_mf').value;
        var satOpen = document.getElementById('open_6').value;
        var satClose = document.getElementById('close_6').value;
        var sunClosed = document.getElementById('closed_0').checked ? 1 : 0;

        for (var d = 0; d <= 6; d++) {
            fd.append('closed_' + d, 0);
            if (d === 0) {
                fd.append('closed_0', sunClosed);
                fd.append('open_0', '');
                fd.append('close_0', '');
            } else if (d === 6) {
                fd.append('open_6', satOpen);
                fd.append('close_6', satClose);
            } else {
                fd.append('open_' + d, mfOpen);
                fd.append('close_' + d, mfClose);
            }
        }
        return fd;
    }

    function wireHours() {
        var ids = ['open_mf', 'close_mf', 'open_6', 'close_6', 'closed_0'];
        ids.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', function () {
                    var feedback = document.getElementById('hoursFeedback');
                    if (feedback) { feedback.textContent = 'Saving...'; }
                    postSettings(collectHours()).then(function (ok) {
                        if (feedback) { feedback.textContent = ok ? 'Hours saved.' : 'Save failed.'; }
                    });
                });
            }
        });
    }

    // ── 7. Notification Preferences Auto-Save ──────────────────────
    function collectNotifs() {
        var fd = new FormData();
        fd.append('csrf_test_name', csrfToken());
        fd.append('section', 'notifications');
        fd.append('new_orders', document.getElementById('pref_new_orders').checked ? 1 : 0);
        fd.append('low_stock', document.getElementById('pref_low_stock').checked ? 1 : 0);
        return fd;
    }

    function wireNotifs() {
        ['pref_new_orders', 'pref_low_stock'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', function () {
                    var feedback = document.getElementById('notifFeedback');
                    if (feedback) { feedback.textContent = 'Saving...'; }
                    postSettings(collectNotifs()).then(function (ok) {
                        if (feedback) { feedback.textContent = ok ? 'Preferences saved.' : ''; }
                    });
                });
            }
        });
    }

    // ── 7.1 Service Modules Auto-Save ──────────────────────────────
    function wireServices() {
        ['service_offers_printing', 'service_offers_delivery', 'service_offers_pickup'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', function () {
                    var moduleName = el.getAttribute('data-module');
                    var feedback = document.getElementById('servicesFeedback');
                    if (feedback) { feedback.textContent = 'Saving...'; }
                    var fd = new FormData();
                    fd.append('csrf_test_name', csrfToken());
                    fd.append('section', 'services');
                    fd.append('module', moduleName);
                    fd.append('enabled', el.checked ? 1 : 0);
                    postSettings(fd).then(function (ok) {
                        if (feedback) { feedback.textContent = ok ? 'Service updated.' : 'Save failed.'; }
                        if (!ok) { el.checked = !el.checked; }
                    });
                });
            }
        });
    }

    // ── 8. Street & Barangay Auto-Sync to Address Line ─────────────
    var streetEl = document.getElementById('street');
    var brgyEl = document.getElementById('barangay');
    var addrEl = document.getElementById('address_line');
    if (streetEl && brgyEl && addrEl) {
        var userModifiedAddr = false;
        addrEl.addEventListener('input', function() {
            userModifiedAddr = true;
        });

        function syncAddressLine() {
            if (userModifiedAddr) return;
            var st = streetEl.value.trim();
            var bg = brgyEl.value.trim();
            var parts = [];
            if (st) parts.push(st);
            if (bg) parts.push(bg);
            parts.push('Polomolok', 'South Cotabato');
            if (st || bg) {
                addrEl.value = parts.join(', ');
            }
        }

        streetEl.addEventListener('input', syncAddressLine);
        brgyEl.addEventListener('change', syncAddressLine);
    }

    wireHours();
    wireNotifs();
    wireServices();

    // ── 9. Google Maps Autocomplete & Geocoding ────────────────────
    let shopAutocomplete = null;

    function handleShopPlaceSelection(place) {
        if (!place) return;
        let lat = null;
        let lng = null;

        if (place.geometry && place.geometry.location) {
            lat = typeof place.geometry.location.lat === 'function' ? place.geometry.location.lat() : place.geometry.location.lat;
            lng = typeof place.geometry.location.lng === 'function' ? place.geometry.location.lng() : place.geometry.location.lng;
        } else if (place.location) {
            lat = typeof place.location.lat === 'function' ? place.location.lat() : place.location.lat;
            lng = typeof place.location.lng === 'function' ? place.location.lng() : place.location.lng;
        }

        const latInput = document.getElementById('shop_latitude');
        const lngInput = document.getElementById('shop_longitude');
        if (latInput && lat !== null) latInput.value = lat;
        if (lngInput && lng !== null) lngInput.value = lng;

        const components = place.address_components || place.addressComponents;
        if (components) {
            for (const component of components) {
                const types = component.types || [];
                const longName = component.long_name || component.longText || '';
                if (types.includes('sublocality') || types.includes('sublocality_level_1') || types.includes('neighborhood') || types.includes('political')) {
                    const brgyName = longName.replace(/^(Barangay|Brgy\.?)\s+/i, '').trim();
                    const brgySelect = document.getElementById('barangay');
                    if (brgySelect) {
                        for (let i = 0; i < brgySelect.options.length; i++) {
                            if (brgySelect.options[i].value.toLowerCase() === brgyName.toLowerCase()) {
                                brgySelect.selectedIndex = i;
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (addrEl && !userModifiedAddr) {
            addrEl.value = place.formatted_address || place.formattedAddress || place.displayName || place.name || '';
        }
    }

    window.initShopAddressAutocomplete = async function() {
        const input = document.getElementById('street');
        if (!input || typeof google === 'undefined' || !google.maps || !google.maps.places) return;

        const poloBounds = new google.maps.LatLngBounds(
            { lat: 6.10, lng: 124.95 },
            { lat: 6.32, lng: 125.18 }
        );

        if (google.maps.places.PlaceAutocompleteElement) {
            try {
                const elem = new google.maps.places.PlaceAutocompleteElement();
                elem.componentRestrictions = { country: 'ph' };

                const onSelect = async (event) => {
                    let place = event.place;
                    if (event.placePrediction && typeof event.placePrediction.toPlace === 'function') {
                        place = event.placePrediction.toPlace();
                    }
                    if (place && typeof place.fetchFields === 'function') {
                        await place.fetchFields({
                            fields: ['displayName', 'formattedAddress', 'location', 'addressComponents']
                        });
                    }
                    if (place && place.formattedAddress) {
                        input.value = place.formattedAddress;
                    } else if (place && place.displayName) {
                        input.value = place.displayName;
                    }
                    handleShopPlaceSelection(place);
                };

                elem.addEventListener('gmp-placeselect', onSelect);
                elem.addEventListener('gmp-select', onSelect);

                const wrapper = document.getElementById('shop-autocomplete-wrapper');
                if (wrapper && !wrapper.hasChildNodes()) {
                    elem.classList.add('w-full');
                    wrapper.appendChild(elem);
                    wrapper.classList.remove('hidden');
                    shopAutocomplete = elem;
                    return;
                }
            } catch (err) {
                console.debug('PlaceAutocompleteElement fallback:', err);
            }
        }

        shopAutocomplete = new google.maps.places.Autocomplete(input, {
            bounds: poloBounds,
            componentRestrictions: { country: 'ph' },
            fields: ['address_components', 'geometry', 'name', 'place_id', 'formatted_address'],
            strictBounds: false
        });

        shopAutocomplete.addListener('place_changed', function() {
            const place = shopAutocomplete.getPlace();
            handleShopPlaceSelection(place);
        });
    };

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof google !== 'undefined' && google.maps && google.maps.places && !shopAutocomplete) {
            window.initShopAddressAutocomplete();
        }
    });
})();
</script>

<!-- Google Maps Places API -->
<script src="https://maps.googleapis.com/maps/api/js?key=<?= esc(env('GOOGLE_MAPS_API_KEY')) ?>&libraries=places&loading=async&callback=initShopAddressAutocomplete" async defer></script>

<?= $this->endSection() ?>