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
?>

<div class="flex-1 space-y-lg">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-md rounded-xl bg-green-100 text-green-800 text-sm font-medium"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-md rounded-xl bg-red-100 text-red-800 text-sm font-medium"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-lg items-start">

        <!-- ===================== SHOP PROFILE ===================== -->
        <div class="lg:col-span-8 glass-card rounded-2xl p-xl space-y-lg">

            <h3 class="text-title-lg font-bold text-on-surface border-b border-outline-variant/20 pb-sm">Shop Profile</h3>

            <div class="flex items-center gap-lg">

                <div class="w-24 h-24 rounded-full overflow-hidden flex items-center justify-center bg-primary-container/30 border border-outline-variant/30 shrink-0">

                    <?php if ($logoUrl !== ''): ?>
                        <img src="<?= esc(logo_url($logoUrl)) ?>" alt="<?= esc($shop['shop_name'] ?? 'Shop') ?> logo" class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="text-headline-md font-bold text-primary"><?= esc($initials) ?></span>
                    <?php endif; ?>

                </div>

                <div class="space-y-xs">

                    <p class="text-label-sm font-bold text-on-surface-variant">Shop Logo</p>
                    <p class="text-xs text-on-surface-variant">JPG, PNG, WEBP or GIF. Max 2 MB.</p>

                    <form action="<?= base_url('tenant/settings/logo') ?>" method="POST" enctype="multipart/form-data" id="logoForm" class="hidden">
                        <?= csrf_field() ?>
                        <input type="file" name="logo" id="logoInput" accept="image/jpeg,image/png,image/webp,image/gif">
                    </form>

                    <button type="button" onclick="document.getElementById('logoInput').click()" class="inline-flex items-center gap-sm px-md py-sm rounded-xl bg-surface-container-high text-on-surface font-semibold hover:bg-surface-variant transition-colors text-label-sm">
                        <span class="material-symbols-outlined text-[18px]">photo_library</span>
                        Change Logo
                    </button>

                </div>

            </div>

            <form action="<?= base_url('tenant/settings/save') ?>" method="POST" class="space-y-lg">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="profile">
                <input type="hidden" name="latitude" id="shop_latitude" value="<?= esc($shop['latitude'] ?? '') ?>">
                <input type="hidden" name="longitude" id="shop_longitude" value="<?= esc($shop['longitude'] ?? '') ?>">

                <div class="grid grid-cols-1 gap-lg">

                    <div>
                        <label class="text-label-sm font-bold text-on-surface-variant block mb-xs" for="shop_name">Shop Name</label>
                        <input type="text" name="shop_name" id="shop_name" value="<?= esc($shop['shop_name'] ?? '') ?>" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl" required>
                    </div>

                    <div>
                        <label class="text-label-sm font-bold text-on-surface-variant block mb-xs" for="description">Shop Description</label>
                        <textarea name="description" id="description" rows="4" maxlength="500" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl"><?= esc($shop['description'] ?? '') ?></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                        <div>
                            <label class="text-label-sm font-bold text-on-surface-variant block mb-xs" for="street">Street Address</label>
                            <input type="text" name="street" id="street" value="<?= esc($shop['street'] ?? '') ?>" placeholder="House No., Street Name, Purok / Bldg" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary font-medium">
                        </div>

                        <div>
                            <label class="text-label-sm font-bold text-on-surface-variant block mb-xs" for="barangay">Barangay (Polomolok)</label>
                            <select name="barangay" id="barangay" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary font-medium">
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

                    <div>
                        <label class="text-label-sm font-bold text-on-surface-variant block mb-xs" for="address_line">Address Details / Full Address</label>
                        <input type="text" name="address_line" id="address_line" value="<?= esc($shop['address_line'] ?? '') ?>" placeholder="e.g. Polomolok, South Cotabato" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary font-medium">
                    </div>

                    <label class="flex items-center gap-md cursor-pointer">

                        <input type="checkbox" name="offers_printing" value="1" <?= !empty($shop['offers_printing']) ? 'checked' : '' ?> class="w-5 h-5 text-primary">
                        <div>
                            <p class="font-semibold text-on-surface">Printing Services</p>
                            <p class="text-xs text-on-surface-variant">Allow customers to submit printing requests to your shop</p>
                        </div>

                    </label>

                </div>

                <button type="submit" class="bg-primary text-on-primary px-xl py-md rounded-xl font-bold hover:bg-primary/90 transition-colors shadow-md">Save Profile</button>

            </form>

        </div>

        <!-- ===================== PAYMENT DETAILS ===================== -->
        <div class="lg:col-span-4 glass-card rounded-2xl p-xl space-y-lg">

            <h3 class="text-title-lg font-bold text-on-surface border-b border-outline-variant/20 pb-sm">Payment Details</h3>
            <p class="text-xs text-on-surface-variant">Configure where your payouts will be sent.</p>

            <form action="<?= base_url('tenant/settings/save') ?>" method="POST" class="space-y-lg">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="payment">

                <div>
                    <label class="text-label-sm font-bold text-on-surface-variant block mb-xs" for="gcash_number">GCash Number</label>
                    <input type="text" name="gcash_number" id="gcash_number" value="<?= esc($shop['gcash_number'] ?? '') ?>" placeholder="0917 123 4567" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl">
                </div>

                <div>
                    <label class="text-label-sm font-bold text-on-surface-variant block mb-xs" for="gcash_account_name">Account Name</label>
                    <input type="text" name="gcash_account_name" id="gcash_account_name" value="<?= esc($shop['gcash_account_name'] ?? '') ?>" placeholder="e.g. RHK General Merchandise" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl">
                </div>

                <button type="submit" class="w-full bg-primary text-on-primary px-xl py-md rounded-xl font-bold hover:bg-primary/90 transition-colors shadow-md">Update Payment</button>

            </form>

        </div>

        <!-- ===================== BUSINESS HOURS ===================== -->
        <div class="lg:col-span-6 glass-card rounded-2xl p-xl space-y-lg">

            <h3 class="text-title-lg font-bold text-on-surface border-b border-outline-variant/20 pb-sm">Business Hours</h3>
            <p class="text-xs text-on-surface-variant">Hours are saved automatically when you change them.</p>

            <div class="space-y-lg">

                <div class="flex flex-col md:flex-row md:items-center gap-md md:gap-lg">

                    <div class="md:w-40 shrink-0">
                        <p class="font-semibold text-on-surface">Monday - Friday</p>
                    </div>

                    <div class="flex items-center gap-md flex-1">

                        <div class="flex-1">
                            <label class="text-label-sm text-on-surface-variant block mb-xs">Open</label>
                            <input type="time" id="open_mf" value="<?= esc($mfOpen) ?>" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl">
                        </div>

                        <span class="text-on-surface-variant mt-lg">—</span>

                        <div class="flex-1">
                            <label class="text-label-sm text-on-surface-variant block mb-xs">Close</label>
                            <input type="time" id="close_mf" value="<?= esc($mfClose) ?>" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl">
                        </div>

                    </div>

                </div>

                <div class="flex flex-col md:flex-row md:items-center gap-md md:gap-lg">

                    <div class="md:w-40 shrink-0">
                        <p class="font-semibold text-on-surface">Saturday</p>
                    </div>

                    <div class="flex items-center gap-md flex-1">

                        <div class="flex-1">
                            <label class="text-label-sm text-on-surface-variant block mb-xs">Open</label>
                            <input type="time" id="open_6" value="<?= esc($satOpen) ?>" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl">
                        </div>

                        <span class="text-on-surface-variant mt-lg">—</span>

                        <div class="flex-1">
                            <label class="text-label-sm text-on-surface-variant block mb-xs">Close</label>
                            <input type="time" id="close_6" value="<?= esc($satClose) ?>" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl">
                        </div>

                    </div>

                </div>

                <div class="flex items-center gap-md">

                    <div class="md:w-40 shrink-0">
                        <p class="font-semibold text-on-surface">Sunday</p>
                    </div>

                    <label class="flex items-center gap-md cursor-pointer">
                        <input type="checkbox" id="closed_0" <?= $sunClosed ? 'checked' : '' ?> class="w-5 h-5 text-primary">
                        <span class="text-label-sm font-semibold text-on-surface-variant">Closed</span>
                    </label>

                </div>

                <p id="hoursFeedback" class="text-label-sm text-on-surface-variant"></p>

            </div>

        </div>

        <!-- ===================== NOTIFICATION PREFERENCES ===================== -->
        <div class="lg:col-span-6 glass-card rounded-2xl p-xl space-y-lg">

            <h3 class="text-title-lg font-bold text-on-surface border-b border-outline-variant/20 pb-sm">Notification Preferences</h3>
            <p class="text-xs text-on-surface-variant">Preferences are saved automatically when you change them.</p>

            <div class="space-y-lg">

                <label class="flex items-center gap-md cursor-pointer">
                    <input type="checkbox" id="pref_new_orders" <?= !empty($notifPrefs['new_orders']) ? 'checked' : '' ?> class="w-5 h-5 text-primary">
                    <div>
                        <p class="font-semibold text-on-surface">New Orders</p>
                        <p class="text-xs text-on-surface-variant">Get notified when a customer places an order</p>
                    </div>
                </label>

                <label class="flex items-center gap-md cursor-pointer">
                    <input type="checkbox" id="pref_low_stock" <?= !empty($notifPrefs['low_stock']) ? 'checked' : '' ?> class="w-5 h-5 text-primary">
                    <div>
                        <p class="font-semibold text-on-surface">Low Stock Alerts</p>
                        <p class="text-xs text-on-surface-variant">Get notified when a product is running low</p>
                    </div>
                </label>

                <p id="notifFeedback" class="text-label-sm text-on-surface-variant"></p>

            </div>

        </div>

    </div>

</div>

<form id="settingsCsrfForm" class="hidden"><?= csrf_field() ?></form>

<div id="settingsToast" class="hidden fixed bottom-6 right-6 z-50 px-xl py-md rounded-xl shadow-lg text-sm font-semibold text-on-primary"></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>
(function () {
    function csrfToken() {
        return document.querySelector('#settingsCsrfForm [name="csrf_test_name"]').value;
    }

    var toast = document.getElementById('settingsToast');
    var toastTimer = null;

    function showToast(message, isError) {
        toast.textContent = message;
        toast.classList.remove('hidden');
        toast.classList.toggle('bg-red-600', !!isError);
        toast.classList.toggle('bg-green-600', !isError);
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.classList.add('hidden'); }, 3500);
    }

    async function postSettings(body) {
        try {
            var resp = await fetch("<?= base_url('tenant/settings/save') ?>", { method: 'POST', body: body });
            var data = await resp.json();
            showToast(data.message || (data.success ? 'Saved.' : 'Save failed.'), !data.success);
            return data.success;
        } catch (err) {
            showToast('Could not reach the server.', true);
            return false;
        }
    }

    // ---- Business Hours auto-save ----
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
                        if (feedback) { feedback.textContent = ok ? 'Business hours saved.' : ''; }
                    });
                });
            }
        });
    }

    // ---- Notification Preferences auto-save ----
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
                        if (feedback) { feedback.textContent = ok ? 'Notification preferences saved.' : ''; }
                    });
                });
            }
        });
    }

    // Logo input: submit immediately on file selection.
    var logoInput = document.getElementById('logoInput');
    if (logoInput) {
        logoInput.addEventListener('change', function () {
            if (logoInput.files && logoInput.files.length > 0) {
                document.getElementById('logoForm').submit();
            }
        });
    }

    // Auto-sync Street & Barangay to Address Line when not customized
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

    let shopAutocomplete = null;
    window.initShopAddressAutocomplete = function() {
        const input = document.getElementById('street');
        if (!input || typeof google === 'undefined' || !google.maps || !google.maps.places) return;

        const poloBounds = new google.maps.LatLngBounds(
            { lat: 6.10, lng: 124.95 },
            { lat: 6.32, lng: 125.18 }
        );

        shopAutocomplete = new google.maps.places.Autocomplete(input, {
            bounds: poloBounds,
            componentRestrictions: { country: 'ph' },
            fields: ['address_components', 'geometry', 'name', 'place_id', 'formatted_address'],
            strictBounds: false
        });

        shopAutocomplete.addListener('place_changed', function() {
            const place = shopAutocomplete.getPlace();
            if (!place || !place.geometry || !place.geometry.location) return;

            const lat = place.geometry.location.lat();
            const lng = place.geometry.location.lng();

            const latInput = document.getElementById('shop_latitude');
            const lngInput = document.getElementById('shop_longitude');
            if (latInput) latInput.value = lat;
            if (lngInput) lngInput.value = lng;

            // Auto-match barangay in dropdown
            if (place.address_components) {
                for (const component of place.address_components) {
                    const types = component.types;
                    if (types.includes('sublocality') || types.includes('sublocality_level_1') || types.includes('neighborhood') || types.includes('political')) {
                        const brgyName = component.long_name.replace(/^(Barangay|Brgy\.?)\s+/i, '').trim();
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
                addrEl.value = place.formatted_address || place.name || '';
            }
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