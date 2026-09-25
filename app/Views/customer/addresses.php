<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<div class="flex flex-1 flex-col md:flex-row w-full min-h-[calc(100vh-72px)] bg-slate-50/50 relative">

    <?= view('components/profile_sidebar', ['activeNav' => 'addresses']) ?>

    <!-- Content Area -->
    <main class="flex-1 p-4 md:p-8 lg:p-10 overflow-y-auto">

        <div class="max-w-6xl mx-auto space-y-6">

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-6 shadow-xs">
                <div>
                    <div class="flex items-center gap-2 text-primary text-xs font-bold uppercase tracking-wider mb-1">
                        <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                        <span>Doorstep Delivery Routing</span>
                    </div>
                    <h1 class="text-headline-sm sm:text-headline-md font-extrabold text-on-surface tracking-tight">Shipping Addresses</h1>
                    <p class="text-body-sm text-on-surface-variant mt-0.5">Manage your saved doorstep locations, GPS map pins, and primary delivery routes in Polomolok &amp; Tupi.</p>
                </div>

                <button type="button" id="addr-add-btn" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-primary text-on-primary rounded-xl font-button text-xs sm:text-sm font-semibold shadow-sm hover:bg-primary/90 hover:shadow-md transition-all active:scale-95 shrink-0">
                    <span class="material-symbols-outlined text-[18px]">add_location_alt</span>
                    <span>Add New Address</span>
                </button>
            </div>

            <!-- Addresses Bento Grid -->
            <div class="space-y-4">

                <?php if (!empty($addresses)): ?>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                        <?php foreach ($addresses as $addr): ?>

                            <?php
                                $addrDefault = (int) ($addr['is_default'] ?? 0) === 1;
                                $addrLabel   = strtolower((string) ($addr['label'] ?? ''));
                                $addrIcon    = 'location_on';
                                $badgeColor  = 'bg-surface-container-high text-on-surface-variant';
                                if ($addrDefault || str_contains($addrLabel, 'home')) {
                                    $addrIcon   = 'home';
                                    $badgeColor = 'bg-blue-500/10 text-blue-600 dark:text-blue-400';
                                } elseif (str_contains($addrLabel, 'office') || str_contains($addrLabel, 'work')) {
                                    $addrIcon   = 'work';
                                    $badgeColor = 'bg-purple-500/10 text-purple-600 dark:text-purple-400';
                                } elseif (str_contains($addrLabel, 'business') || str_contains($addrLabel, 'store') || str_contains($addrLabel, 'shop')) {
                                    $addrIcon   = 'storefront';
                                    $badgeColor = 'bg-amber-500/10 text-amber-600 dark:text-amber-400';
                                } elseif (str_contains($addrLabel, 'apartment') || str_contains($addrLabel, 'dorm') || str_contains($addrLabel, 'boarding')) {
                                    $addrIcon   = 'apartment';
                                    $badgeColor = 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400';
                                }

                                $addrPhone   = $addr['phone'] ?? '';
                                $addrLine1   = $addr['address_line1'] ?? '';
                                $addrLine2   = $addr['address_line2'] ?? '';
                                $addrCity    = $addr['city'] ?: 'Polomolok';
                                $addrProv    = $addr['province'] ?: 'South Cotabato';
                                $addrZip     = $addr['postal_code'] ?: ($addrCity === 'Tupi' ? '9505' : '9504');
                                $addrCountry = $addr['country'] ?: 'Philippines';
                                $hasGps      = !empty($addr['latitude']) && !empty($addr['longitude']) && (float)$addr['latitude'] != 0;
                            ?>

                            <div class="bg-surface-container-lowest border <?= $addrDefault ? 'border-primary ring-2 ring-primary/10 shadow-sm' : 'border-outline-variant/30 hover:border-outline-variant/60 shadow-2xs' ?> rounded-2xl p-5 flex flex-col justify-between transition-all group">

                                <div class="space-y-3">
                                    <!-- Card Header: Icon, Label & Status Badge -->
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <div class="w-10 h-10 rounded-xl <?= $badgeColor ?> flex items-center justify-center shrink-0 shadow-2xs">
                                                <span class="material-symbols-outlined text-[20px]"><?= esc($addrIcon) ?></span>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <h3 class="text-sm font-bold text-on-surface truncate"><?= esc($addr['label'] ?: 'Shipping Address') ?></h3>
                                                    <?php if ($addrDefault): ?>
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                                            <span class="material-symbols-outlined text-[12px]">check_circle</span>
                                                            Default
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="text-[11px] text-on-surface-variant flex items-center gap-1 mt-0.5">
                                                    <span class="material-symbols-outlined text-[14px] text-outline">person</span>
                                                    <span class="font-semibold text-on-surface"><?= esc($addr['recipient_name'] ?: 'Customer') ?></span>
                                                    <span class="text-outline">&bull;</span>
                                                    <span class="font-mono text-outline"><?= esc($addrPhone ?: 'No phone provided') ?></span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Street & Location Breakdown -->
                                    <div class="p-3 bg-surface-container-low/50 rounded-xl border border-outline-variant/20 space-y-1.5 text-xs">
                                        <div class="flex items-start gap-2 text-on-surface">
                                            <span class="material-symbols-outlined text-[16px] text-primary shrink-0 mt-0.5">home_pin</span>
                                            <div class="min-w-0 flex-1 leading-relaxed">
                                                <p class="font-semibold text-on-surface"><?= esc($addrLine1 ?: 'Street / House Address') ?></p>
                                                <div class="flex items-center gap-1.5 flex-wrap mt-1">
                                                    <?php if ($addrLine2 !== ''): ?>
                                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-primary/10 text-primary border border-primary/20">
                                                            Brgy. <?= esc($addrLine2) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="text-on-surface-variant font-medium">
                                                        <?= esc($addrCity) ?>, <?= esc($addrProv) ?> <?= esc($addrZip) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- GPS Mapping Status Pill -->
                                        <div class="pt-1.5 border-t border-outline-variant/15 flex items-center justify-between text-[11px]">
                                            <div class="flex items-center gap-1.5 text-on-surface-variant">
                                                <span class="w-2 h-2 rounded-full <?= $hasGps ? 'bg-emerald-500' : 'bg-amber-500' ?>"></span>
                                                <span><?= $hasGps ? 'Doorstep GPS Pinned' : 'Barangay Centroid' ?></span>
                                            </div>
                                            <?php if ($hasGps): ?>
                                                <span class="font-mono text-[10px] text-outline">
                                                    <?= number_format((float)$addr['latitude'], 4) ?>, <?= number_format((float)$addr['longitude'], 4) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Actions Footer -->
                                <div class="mt-4 pt-3 border-t border-outline-variant/20 flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" 
                                                class="addr-edit inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-surface-container border border-outline-variant/40 text-on-surface hover:bg-surface-container-high transition-colors"
                                                data-id="<?= esc($addr['id']) ?>"
                                                data-label="<?= esc($addr['label'] ?? '') ?>"
                                                data-recipient="<?= esc($addr['recipient_name'] ?? '') ?>"
                                                data-phone="<?= esc($addrPhone) ?>"
                                                data-line1="<?= esc($addrLine1) ?>"
                                                data-line2="<?= esc($addrLine2) ?>"
                                                data-city="<?= esc($addrCity) ?>"
                                                data-province="<?= esc($addrProv) ?>"
                                                data-zip="<?= esc($addrZip) ?>"
                                                data-country="<?= esc($addrCountry) ?>"
                                                data-lat="<?= esc($addr['latitude'] ?? '') ?>"
                                                data-lng="<?= esc($addr['longitude'] ?? '') ?>"
                                                data-place_id="<?= esc($addr['place_id'] ?? '') ?>"
                                                data-default="<?= $addrDefault ? '1' : '0' ?>">
                                            <span class="material-symbols-outlined text-[15px]">edit</span>
                                            <span>Edit</span>
                                        </button>

                                        <form method="post" action="<?= base_url('customer/addresses/delete') ?>" onsubmit="return confirm('Are you sure you want to delete this shipping address?');" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="address_id" value="<?= esc($addr['id']) ?>">
                                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-error hover:bg-error-container/20 transition-colors">
                                                <span class="material-symbols-outlined text-[15px]">delete</span>
                                                <span>Delete</span>
                                            </button>
                                        </form>
                                    </div>

                                    <?php if (!$addrDefault): ?>
                                        <form method="post" action="<?= base_url('customer/addresses/set-default') ?>" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="address_id" value="<?= esc($addr['id']) ?>">
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-primary hover:bg-primary/10 border border-primary/20 transition-colors">
                                                <span class="material-symbols-outlined text-[15px]">star</span>
                                                <span>Set as Default</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <!-- Empty State -->
                    <div class="bg-surface-container-lowest rounded-2xl border border-dashed border-outline-variant/50 p-12 text-center shadow-xs space-y-3">
                        <div class="w-16 h-16 rounded-full bg-primary/10 text-primary mx-auto flex items-center justify-center">
                            <span class="material-symbols-outlined text-3xl">location_off</span>
                        </div>
                        <h3 class="text-base font-bold text-on-surface">No Saved Shipping Addresses</h3>
                        <p class="text-xs text-on-surface-variant max-w-sm mx-auto">
                            You haven't saved any doorstep delivery locations yet. Add an address with an exact map pin to speed up checkout.
                        </p>
                        <div class="pt-2">
                            <button type="button" id="addr-add-btn-empty" class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary text-on-primary rounded-xl font-button text-xs font-semibold shadow-sm hover:bg-primary/90 transition-all active:scale-95">
                                <span class="material-symbols-outlined text-[18px]">add_location_alt</span>
                                <span>Add First Address</span>
                            </button>
                        </div>
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>

<!-- Add / Edit Shipping Address Modal -->
<div id="addr-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-4 md:p-6 overflow-y-auto">

    <!-- Modal Backdrop Blur -->
    <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" id="addr-overlay"></div>

    <!-- Modal Dialog -->
    <div class="relative bg-surface-container-lowest rounded-3xl border border-outline-variant/30 shadow-2xl w-full max-w-2xl my-auto max-h-[92vh] flex flex-col overflow-hidden z-10 animate-in fade-in zoom-in-95 duration-200">

        <!-- Modal Header -->
        <div class="p-4 sm:p-5 border-b border-outline-variant/20 bg-surface-container-low/40 flex items-center justify-between gap-3 shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[20px]" id="addr-modal-icon">pin_drop</span>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-on-surface" id="addr-modal-title">Add New Address</h3>
                    <p class="text-[11px] text-on-surface-variant">Polomolok &amp; Tupi Doorstep Delivery</p>
                </div>
            </div>

            <button type="button" id="addr-modal-close" class="w-8 h-8 rounded-full hover:bg-surface-container-high text-on-surface-variant flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <!-- Modal Body / Form -->
        <form method="post" action="<?= base_url('customer/addresses/save') ?>" id="addressForm" class="overflow-y-auto flex-1 p-4 sm:p-6 space-y-4">

            <?= csrf_field() ?>
            <input type="hidden" name="address_id" id="addr-id" value="">
            <input type="hidden" name="latitude" id="addr-latitude" value="6.2185">
            <input type="hidden" name="longitude" id="addr-longitude" value="125.0650">
            <input type="hidden" name="place_id" id="addr-place-id" value="">
            <input type="hidden" name="address_line2" id="addr-line2" value="Poblacion">

            <!-- Address Category Presets -->
            <div>
                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block mb-1.5">1. Address Label</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <button type="button" class="addr-preset-btn flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl border border-outline-variant/40 bg-surface-container-low text-xs font-bold text-on-surface hover:border-primary transition-all" data-preset="Home" data-icon="home">
                        <span class="material-symbols-outlined text-[16px] text-blue-600">home</span>
                        <span>Home</span>
                    </button>
                    <button type="button" class="addr-preset-btn flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl border border-outline-variant/40 bg-surface-container-low text-xs font-bold text-on-surface hover:border-primary transition-all" data-preset="Office" data-icon="work">
                        <span class="material-symbols-outlined text-[16px] text-purple-600">work</span>
                        <span>Office</span>
                    </button>
                    <button type="button" class="addr-preset-btn flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl border border-outline-variant/40 bg-surface-container-low text-xs font-bold text-on-surface hover:border-primary transition-all" data-preset="Apartment" data-icon="apartment">
                        <span class="material-symbols-outlined text-[16px] text-emerald-600">apartment</span>
                        <span>Apartment</span>
                    </button>
                    <button type="button" class="addr-preset-btn flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl border border-outline-variant/40 bg-surface-container-low text-xs font-bold text-on-surface hover:border-primary transition-all" data-preset="Other" data-icon="location_on">
                        <span class="material-symbols-outlined text-[16px] text-amber-600">location_on</span>
                        <span>Other</span>
                    </button>
                </div>
                <input type="text" id="addr-label" name="label" class="mt-2 w-full py-2 px-3 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs font-semibold text-on-surface focus:ring-1 focus:ring-primary focus:border-primary" placeholder="Custom label name (e.g. My House, Dormitory, Shop Gate)" value="Home" required>
            </div>

            <!-- Contact Information -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="addr-recipient" class="text-xs font-bold text-on-surface-variant block mb-1">
                        Recipient Full Name <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" id="addr-recipient" name="recipient_name" class="w-full py-2 pl-9 pr-3 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs font-medium text-on-surface focus:ring-1 focus:ring-primary focus:border-primary" placeholder="e.g. Juan Dela Cruz" required>
                        <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-[18px] text-outline pointer-events-none">person</span>
                    </div>
                </div>

                <div>
                    <label for="addr-phone" class="text-xs font-bold text-on-surface-variant block mb-1">
                        Mobile Phone Number <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <input type="tel" id="addr-phone" name="phone" class="w-full py-2 pl-9 pr-3 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs font-medium text-on-surface focus:ring-1 focus:ring-primary focus:border-primary" placeholder="09XX-XXX-XXXX or +639..." required>
                        <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-[18px] text-outline pointer-events-none">call</span>
                    </div>
                </div>
            </div>

            <!-- City / Municipality, Province, Postal Code, and Barangay Dropdowns -->
            <div class="p-3.5 bg-surface-container-low/60 rounded-2xl border border-outline-variant/30 space-y-3">
                <label class="text-xs font-bold text-primary uppercase tracking-wider block">2. Territorial Jurisdiction (Dropdown Selection)</label>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- City / Municipality Dropdown -->
                    <div>
                        <label for="addr-city" class="text-xs font-bold text-on-surface block mb-1">
                            City / Municipality <span class="text-error">*</span>
                        </label>
                        <div class="relative">
                            <select id="addr-city" name="city" class="w-full py-2 pl-3 pr-8 bg-surface-container-lowest border border-outline-variant/40 rounded-xl text-xs font-bold text-on-surface focus:ring-1 focus:ring-primary focus:border-primary appearance-none cursor-pointer" required>
                                <option value="Polomolok" selected>Polomolok</option>
                                <option value="Tupi">Tupi</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[18px] text-outline pointer-events-none">expand_more</span>
                        </div>
                    </div>

                    <!-- Dynamic Barangay Dropdown -->
                    <div>
                        <label for="addr-barangay" class="text-xs font-bold text-on-surface block mb-1">
                            Barangay <span class="text-error">*</span>
                        </label>
                        <div class="relative">
                            <select id="addr-barangay" name="barangay" class="w-full py-2 pl-3 pr-8 bg-surface-container-lowest border border-outline-variant/40 rounded-xl text-xs font-bold text-on-surface focus:ring-1 focus:ring-primary focus:border-primary appearance-none cursor-pointer" required>
                                <!-- Populated dynamically by JavaScript -->
                            </select>
                            <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[18px] text-outline pointer-events-none">expand_more</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Province Dropdown -->
                    <div>
                        <label for="addr-province" class="text-xs font-bold text-on-surface block mb-1">
                            Province <span class="text-error">*</span>
                        </label>
                        <div class="relative">
                            <select id="addr-province" name="province" class="w-full py-2 pl-3 pr-8 bg-surface-container-lowest border border-outline-variant/40 rounded-xl text-xs font-bold text-on-surface focus:ring-1 focus:ring-primary focus:border-primary appearance-none cursor-pointer" required>
                                <option value="South Cotabato" selected>South Cotabato</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[18px] text-outline pointer-events-none">expand_more</span>
                        </div>
                    </div>

                    <!-- Postal Code Dropdown -->
                    <div>
                        <label for="addr-zip" class="text-xs font-bold text-on-surface block mb-1">
                            Postal Code <span class="text-error">*</span>
                        </label>
                        <div class="relative">
                            <select id="addr-zip" name="postal_code" class="w-full py-2 pl-3 pr-8 bg-surface-container-lowest border border-outline-variant/40 rounded-xl text-xs font-bold text-on-surface focus:ring-1 focus:ring-primary focus:border-primary appearance-none cursor-pointer" required>
                                <option value="9504" selected>9504 (Polomolok)</option>
                                <option value="9505">9505 (Tupi)</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[18px] text-outline pointer-events-none">expand_more</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Address Line 1 / Street -->
            <div>
                <label for="addr-line1" class="text-xs font-bold text-on-surface-variant block mb-1">
                    3. Detailed Street / House / Purok / Landmark <span class="text-error">*</span>
                </label>
                <input type="text" id="addr-line1" name="address_line1" placeholder="House No., Street Name, Purok / Block & Lot, Landmark" class="w-full py-2 px-3 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs font-medium text-on-surface focus:ring-1 focus:ring-primary focus:border-primary" required>
            </div>

            <!-- Interactive Embedded Google Map Pin Picker -->
            <div class="space-y-2">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px] text-primary">my_location</span>
                        <span>4. Exact Doorstep GPS Pin (Interactive Map)</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button" id="btn-address-map-type" onclick="toggleAddressMapType()" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-surface-container border border-outline-variant/40 text-on-surface hover:bg-surface-container-high transition-all cursor-pointer" title="Toggle Satellite / Roadmap View">
                            <span id="addressMapTypeIcon" class="material-symbols-outlined text-[14px] text-primary">satellite_alt</span>
                            <span id="addressMapTypeText">Satellite</span>
                        </button>
                        <button type="button" id="btn-locate-me" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-primary/10 text-primary hover:bg-primary hover:text-white transition-all">
                            <span class="material-symbols-outlined text-[14px]">near_me</span>
                            <span>Locate My GPS</span>
                        </button>
                    </div>
                </div>

                <div class="relative rounded-2xl overflow-hidden border border-outline-variant/40 bg-surface-container shadow-2xs">
                    <!-- Google Map Container -->
                    <div id="modalAddressMap" class="w-full h-[220px] bg-surface-container-high" style="min-height: 220px;"></div>

                    <!-- Map Floating Hint Badge -->
                    <div class="absolute top-2.5 left-2.5 bg-surface-container-lowest/90 backdrop-blur-md px-2.5 py-1 rounded-lg border border-outline-variant/30 text-[10px] font-semibold text-on-surface shadow-xs flex items-center gap-1.5 pointer-events-none">
                        <span class="w-2 h-2 rounded-full bg-primary animate-ping"></span>
                        <span>Click map or drag pin to your doorstep</span>
                    </div>

                    <!-- Realtime Coordinate Display Pill -->
                    <div class="absolute bottom-2.5 right-2.5 bg-surface-container-lowest/90 backdrop-blur-md px-2.5 py-1 rounded-lg border border-outline-variant/30 text-[10px] font-mono text-outline shadow-xs pointer-events-none" id="map-coord-pill">
                        Lat: 6.2185, Lng: 125.0650
                    </div>
                </div>
            </div>

            <!-- Default Address Toggle -->
            <div id="addr-default-wrap" class="pt-1">
                <label class="flex items-center gap-2.5 cursor-pointer select-none">
                    <input type="checkbox" id="addr-default" name="is_default" value="1" class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer">
                    <span class="text-xs font-bold text-on-surface">Set as my primary default delivery address</span>
                </label>
            </div>

            <!-- Modal Footer / Buttons -->
            <div class="pt-3 border-t border-outline-variant/20 flex items-center justify-end gap-2.5">
                <button type="button" id="addr-modal-cancel" class="px-4 py-2 rounded-xl border border-outline-variant/40 text-xs font-bold text-on-surface-variant hover:bg-surface-container-high transition-colors">
                    Cancel
                </button>
                <button type="submit" id="addr-submit-btn" class="px-5 py-2 rounded-xl bg-primary text-on-primary text-xs font-bold hover:bg-primary/90 shadow-sm transition-all active:scale-95 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">save</span>
                    <span>Save Address</span>
                </button>
            </div>

        </form>

    </div>

</div>



<script>
(function () {
    const BARANGAYS_BY_CITY = {
        'Polomolok': [
            'Bentung', 'Cannery Site', 'Crossing Palkan', 'Glamang', 'Kinilis',
            'Klinan 6', 'Koronadal Proper', 'Lam-Caliaf', 'Landan', 'Lumakil',
            'Magsaysay', 'Maligo', 'Pagalungan', 'Palkan', 'Poblacion', 'Polo',
            'Pula Bato', 'Rubber', 'Silway 7', 'Silway 8', 'Sulit', 'Sumbakil',
            'Upper Klinan'
        ],
        'Tupi': [
            'Acmonan', 'Bololmala', 'Bunao', 'Cebuano', 'Crossing Rubber', 'Dajay',
            'Kablon', 'Kalkam', 'Linan', 'Lunen', 'Miaso', 'Palian',
            'Poblacion', 'Polonoling', 'Simbo', 'Tubeng'
        ]
    };

    const BARANGAY_COORDS = {
        // Polomolok Centroids
        'bentung': [6.2625, 125.0480],
        'cannery site': [6.2415, 125.0740],
        'crossing palkan': [6.2580, 125.1050],
        'glamang': [6.1785, 125.0390],
        'kinilis': [6.2840, 125.0210],
        'klinan 6': [6.1650, 125.0920],
        'koronadal proper': [6.2250, 125.1020],
        'lam-caliaf': [6.2910, 125.0680],
        'landan': [6.3050, 125.0410],
        'lumakil': [6.1950, 125.0780],
        'maligo': [6.2890, 125.0990],
        'palkan': [6.2710, 125.1180],
        'poblacion': [6.2185, 125.0650],
        'polo': [6.2080, 125.0350],
        'pula bato': [6.2460, 125.0230],
        'rubber': [6.1890, 125.1120],
        'silway 7': [6.1550, 125.1250],
        'silway 8': [6.1420, 125.1480],
        'sulit': [6.2340, 125.1320],
        'sumbakil': [6.2120, 125.1450],
        'upper klinan': [6.1820, 125.0710],
        'pagalungan': [6.1710, 125.0530],
        'magsaysay': [6.2280, 125.0480],
        // Tupi Centroids
        'acmonan': [6.3350, 124.9650],
        'bololmala': [6.3120, 124.9380],
        'bunao': [6.3480, 124.9450],
        'cebuano': [6.3620, 124.9320],
        'crossing rubber': [6.3210, 124.9750],
        'dajay': [6.3750, 124.9180],
        'kablon': [6.3290, 125.0120],
        'kalkam': [6.3420, 124.9850],
        'linan': [6.2980, 124.9250],
        'lunen': [6.3550, 124.9720],
        'miaso': [6.3680, 124.9580],
        'palian': [6.3150, 124.9920],
        'polonoling': [6.3010, 124.9680],
        'simbo': [6.3510, 124.9150],
        'tubeng': [6.3820, 124.9350]
    };

    const modal = document.getElementById('addr-modal');
    const modalTitle = document.getElementById('addr-modal-title');
    const addrId = document.getElementById('addr-id');
    const defaultWrap = document.getElementById('addr-default-wrap');
    const citySelect = document.getElementById('addr-city');
    const brgySelect = document.getElementById('addr-barangay');
    const zipSelect = document.getElementById('addr-zip');
    const line2Input = document.getElementById('addr-line2');
    const coordPill = document.getElementById('map-coord-pill');

    let modalMap = null;
    let modalMarker = null;

    function populateBarangays(selectedCity, selectedBrgy) {
        const city = selectedCity === 'Tupi' ? 'Tupi' : 'Polomolok';
        const list = BARANGAYS_BY_CITY[city] || BARANGAYS_BY_CITY['Polomolok'];
        
        brgySelect.innerHTML = '';
        list.forEach(b => {
            const opt = document.createElement('option');
            opt.value = b;
            opt.textContent = b;
            if (selectedBrgy && selectedBrgy.toLowerCase() === b.toLowerCase()) {
                opt.selected = true;
            }
            brgySelect.appendChild(opt);
        });

        // Sync postal code
        if (zipSelect) {
            zipSelect.value = city === 'Tupi' ? '9505' : '9504';
        }

        // Sync hidden line2
        if (line2Input) {
            line2Input.value = brgySelect.value || 'Poblacion';
        }
    }

    function updateCoordinateFields(lat, lng) {
        const latInput = document.getElementById('addr-latitude');
        const lngInput = document.getElementById('addr-longitude');
        if (latInput) latInput.value = Number(lat).toFixed(7);
        if (lngInput) lngInput.value = Number(lng).toFixed(7);
        if (coordPill) {
            coordPill.textContent = `Lat: ${Number(lat).toFixed(4)}, Lng: ${Number(lng).toFixed(4)}`;
        }
    }

    const ADDR_MAP_TYPE_STORAGE_KEY = 'blax_address_modal_map_type';
    let currentAddrMapType = localStorage.getItem(ADDR_MAP_TYPE_STORAGE_KEY) || 'hybrid';

    function updateAddressMapTypeToggleUI() {
        const icon = document.getElementById('addressMapTypeIcon');
        const text = document.getElementById('addressMapTypeText');
        if (!icon || !text) return;
        if (currentAddrMapType === 'hybrid') {
            icon.textContent = 'map';
            text.textContent = 'Roadmap';
        } else {
            icon.textContent = 'satellite_alt';
            text.textContent = 'Satellite';
        }
    }

    function toggleAddressMapType() {
        if (!modalMap || typeof google === 'undefined' || !google.maps) return;
        if (currentAddrMapType === 'hybrid') {
            currentAddrMapType = 'roadmap';
            modalMap.setMapTypeId(google.maps.MapTypeId.ROADMAP);
        } else {
            currentAddrMapType = 'hybrid';
            modalMap.setMapTypeId(google.maps.MapTypeId.HYBRID);
        }
        localStorage.setItem(ADDR_MAP_TYPE_STORAGE_KEY, currentAddrMapType);
        updateAddressMapTypeToggleUI();
    }

    function initOrUpdateModalMap(initialLat, initialLng) {
        const lat = parseFloat(initialLat) || 6.2185;
        const lng = parseFloat(initialLng) || 125.0650;
        const center = { lat, lng };

        updateCoordinateFields(lat, lng);

        const mapEl = document.getElementById('modalAddressMap');
        if (!mapEl || typeof google === 'undefined' || !google.maps) return;

        if (!modalMap) {
            const initialMapTypeId = currentAddrMapType === 'roadmap' ? google.maps.MapTypeId.ROADMAP : google.maps.MapTypeId.HYBRID;
            modalMap = new google.maps.Map(mapEl, {
                center: center,
                zoom: 15,
                mapTypeId: initialMapTypeId,
                mapId: 'ADDRESS_PICKER_MAP',
                disableDefaultUI: false,
                zoomControl: true,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true
            });
            updateAddressMapTypeToggleUI();

            // Create draggable marker
            if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
                const pinImg = document.createElement('div');
                pinImg.style.width = '32px';
                pinImg.style.height = '32px';
                pinImg.style.borderRadius = '50%';
                pinImg.style.backgroundColor = '#2563eb';
                pinImg.style.border = '3px solid #ffffff';
                pinImg.style.boxShadow = '0 3px 10px rgba(0,0,0,0.3)';
                pinImg.style.display = 'flex';
                pinImg.style.alignItems = 'center';
                pinImg.style.justifyContent = 'center';
                pinImg.style.color = '#ffffff';
                pinImg.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">home_pin</span>';

                modalMarker = new google.maps.marker.AdvancedMarkerElement({
                    map: modalMap,
                    position: center,
                    gmpDraggable: true,
                    content: pinImg,
                    title: 'Your Doorstep Delivery Pin'
                });

                modalMarker.addListener('dragend', () => {
                    const pos = modalMarker.position;
                    const pLat = typeof pos.lat === 'function' ? pos.lat() : pos.lat;
                    const pLng = typeof pos.lng === 'function' ? pos.lng() : pos.lng;
                    updateCoordinateFields(pLat, pLng);
                });
            } else {
                modalMarker = new google.maps.Marker({
                    map: modalMap,
                    position: center,
                    draggable: true,
                    title: 'Your Doorstep Delivery Pin'
                });

                modalMarker.addListener('dragend', (evt) => {
                    updateCoordinateFields(evt.latLng.lat(), evt.latLng.lng());
                });
            }

            // Click anywhere on map to reposition marker
            modalMap.addListener('click', (evt) => {
                const clickedLat = evt.latLng.lat();
                const clickedLng = evt.latLng.lng();
                if (modalMarker.position && typeof modalMarker.position.lat === 'function') {
                    modalMarker.setPosition(new google.maps.LatLng(clickedLat, clickedLng));
                } else {
                    modalMarker.position = { lat: clickedLat, lng: clickedLng };
                }
                updateCoordinateFields(clickedLat, clickedLng);
            });
        } else {
            modalMap.setCenter(center);
            modalMap.setZoom(15);
            if (modalMarker) {
                if (modalMarker.position && typeof modalMarker.position.lat === 'function') {
                    modalMarker.setPosition(new google.maps.LatLng(lat, lng));
                } else {
                    modalMarker.position = center;
                }
            }
            google.maps.event.trigger(modalMap, 'resize');
        }
    }

    function panToBarangayCentroid() {
        const brgy = (brgySelect.value || '').toLowerCase().trim();
        const city = (citySelect.value || '').toLowerCase().trim();
        let target = BARANGAY_COORDS[brgy];

        if (!target) {
            target = (city === 'tupi') ? [6.3333, 124.9500] : [6.2185, 125.0650];
        }

        const newLat = target[0];
        const newLng = target[1];

        if (modalMap && modalMarker) {
            const newPos = { lat: newLat, lng: newLng };
            modalMap.panTo(newPos);
            modalMap.setZoom(15);
            if (modalMarker.position && typeof modalMarker.position.lat === 'function') {
                modalMarker.setPosition(new google.maps.LatLng(newLat, newLng));
            } else {
                modalMarker.position = newPos;
            }
        }
        updateCoordinateFields(newLat, newLng);
    }

    // City Change -> update barangays and postal code
    if (citySelect) {
        citySelect.addEventListener('change', function () {
            populateBarangays(this.value, null);
            panToBarangayCentroid();
        });
    }

    // Barangay Change -> sync hidden input and pan map
    if (brgySelect) {
        brgySelect.addEventListener('change', function () {
            if (line2Input) line2Input.value = this.value;
            panToBarangayCentroid();
        });
    }

    // Preset Category Pills
    document.querySelectorAll('.addr-preset-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const labelInput = document.getElementById('addr-label');
            if (labelInput) {
                labelInput.value = this.dataset.preset || 'Home';
            }
            document.querySelectorAll('.addr-preset-btn').forEach(b => {
                b.classList.remove('border-primary', 'bg-primary/10', 'text-primary');
            });
            this.classList.add('border-primary', 'bg-primary/10', 'text-primary');
        });
    });

    // Locate Me GPS Button
    const locateBtn = document.getElementById('btn-locate-me');
    if (locateBtn) {
        locateBtn.addEventListener('click', function () {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser.');
                return;
            }
            locateBtn.disabled = true;
            locateBtn.innerHTML = '<span class="material-symbols-outlined text-[14px] animate-spin">refresh</span><span>Locating...</span>';

            navigator.geolocation.getCurrentPosition(
                pos => {
                    locateBtn.disabled = false;
                    locateBtn.innerHTML = '<span class="material-symbols-outlined text-[14px]">near_me</span><span>Locate My GPS</span>';
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;

                    // Bounds check (Polomolok/Tupi)
                    if (lat >= 6.10 && lat <= 6.45 && lng >= 124.85 && lng <= 125.20) {
                        initOrUpdateModalMap(lat, lng);
                    } else {
                        initOrUpdateModalMap(lat, lng);
                    }

                    // Reverse-geocode to sync City/Barangay selects
                    if (typeof google !== 'undefined' && google.maps && google.maps.Geocoder) {
                        const geocoder = new google.maps.Geocoder();
                        geocoder.geocode({ location: { lat, lng } }, (results, status) => {
                            if (status === 'OK' && results && results.length > 0) {
                                let detectedCity = '';
                                let detectedBarangay = '';
                                for (const result of results) {
                                    for (const comp of result.address_components) {
                                        if (comp.types.includes('locality') || comp.types.includes('administrative_area_level_3')) {
                                            detectedCity = comp.long_name;
                                        }
                                        if (comp.types.includes('sublocality') || comp.types.includes('sublocality_level_1') || comp.types.includes('neighborhood')) {
                                            if (!detectedBarangay) detectedBarangay = comp.long_name;
                                        }
                                    }
                                }
                                // Try to match and set City select
                                const citySelect = document.getElementById('addr-city');
                                if (citySelect && detectedCity) {
                                    for (const opt of citySelect.options) {
                                        if (opt.value && detectedCity.toLowerCase().includes(opt.value.toLowerCase())) {
                                            citySelect.value = opt.value;
                                            citySelect.dispatchEvent(new Event('change', { bubbles: true }));
                                            break;
                                        }
                                    }
                                }
                                // Try to match and set Barangay select (after city change triggers list update)
                                setTimeout(() => {
                                    const brgySelect = document.getElementById('addr-barangay');
                                    if (brgySelect && detectedBarangay) {
                                        for (const opt of brgySelect.options) {
                                            if (opt.value && (opt.value === detectedBarangay || detectedBarangay.toLowerCase().includes(opt.value.toLowerCase()))) {
                                                brgySelect.value = opt.value;
                                                brgySelect.dispatchEvent(new Event('change', { bubbles: true }));
                                                break;
                                            }
                                        }
                                    }
                                }, 300);
                            }
                        });
                    }
                },
                err => {
                    locateBtn.disabled = false;
                    locateBtn.innerHTML = '<span class="material-symbols-outlined text-[14px]">near_me</span><span>Locate My GPS</span>';
                    alert('Unable to retrieve location. Please grant location permissions in your browser.');
                },
                { enableHighAccuracy: true, timeout: 8000 }
            );
        });
    }

    function openAdd() {
        addrId.value = '';
        modalTitle.textContent = 'Add New Shipping Address';
        document.getElementById('addr-modal-icon').textContent = 'add_location_alt';
        document.getElementById('addr-label').value = 'Home';
        document.getElementById('addr-recipient').value = '';
        document.getElementById('addr-phone').value = '';
        document.getElementById('addr-line1').value = '';
        citySelect.value = 'Polomolok';
        populateBarangays('Polomolok', 'Poblacion');
        document.getElementById('addr-default').checked = false;
        defaultWrap.classList.remove('hidden');
        modal.classList.remove('hidden');

        setTimeout(() => {
            initOrUpdateModalMap(6.2185, 125.0650);
        }, 150);
    }

    function openEdit(btn) {
        addrId.value = btn.dataset.id;
        modalTitle.textContent = 'Edit Shipping Address';
        document.getElementById('addr-modal-icon').textContent = 'edit_location';
        document.getElementById('addr-label').value = btn.dataset.label || 'Home';
        document.getElementById('addr-recipient').value = btn.dataset.recipient || '';
        document.getElementById('addr-phone').value = btn.dataset.phone || '';
        document.getElementById('addr-line1').value = btn.dataset.line1 || '';
        
        const city = btn.dataset.city || 'Polomolok';
        citySelect.value = city;
        populateBarangays(city, btn.dataset.line2 || '');

        const isDef = btn.dataset.default === '1';
        document.getElementById('addr-default').checked = isDef;
        if (isDef) {
            defaultWrap.classList.add('hidden');
        } else {
            defaultWrap.classList.remove('hidden');
        }

        modal.classList.remove('hidden');

        const lat = parseFloat(btn.dataset.lat) || 6.2185;
        const lng = parseFloat(btn.dataset.lng) || 125.0650;

        setTimeout(() => {
            initOrUpdateModalMap(lat, lng);
        }, 150);
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    const addBtn = document.getElementById('addr-add-btn');
    if (addBtn) addBtn.addEventListener('click', openAdd);
    const addBtnEmpty = document.getElementById('addr-add-btn-empty');
    if (addBtnEmpty) addBtnEmpty.addEventListener('click', openAdd);

    document.querySelectorAll('.addr-edit').forEach(btn => {
        btn.addEventListener('click', () => openEdit(btn));
    });

    document.getElementById('addr-overlay').addEventListener('click', closeModal);
    document.getElementById('addr-modal-close').addEventListener('click', closeModal);
    document.getElementById('addr-modal-cancel').addEventListener('click', closeModal);

    // Initial setup
    populateBarangays('Polomolok', 'Poblacion');

    // Auto-open modal if requested
    const params = new URLSearchParams(window.location.search);
    if (params.get('open_add') === '1' || window.location.hash === '#add' || <?= empty($addresses) ? 'true' : 'false' ?>) {
        openAdd();
    }
})();
</script>

<!-- Google Maps JavaScript API -->
<script src="https://maps.googleapis.com/maps/api/js?key=<?= esc(env('GOOGLE_MAPS_API_KEY')) ?>&libraries=marker,geometry&loading=async" async defer></script>

<?= $this->endSection() ?>