<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="p-md rounded-xl bg-green-100 text-green-800 text-sm font-medium mb-lg"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="p-md rounded-xl bg-error-container text-on-error-container text-sm font-medium mb-lg"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="flex flex-1 flex-col md:flex-row max-w-container-max mx-auto w-full relative">

    <?= view('components/profile_sidebar', ['activeNav' => 'addresses']) ?>

    <!-- Content Area -->
    <main class="flex-1 p-lg md:p-xl overflow-y-auto">

        <div class="max-w-5xl mx-auto">

            <div class="flex flex-col md:flex-row md:items-center justify-between mb-xl gap-lg">

                <div>

                    <h1 class="text-headline-lg font-bold text-on-surface mb-xs">Shipping Addresses</h1>
                    <p class="text-body-md text-on-surface-variant">Manage your saved delivery locations and default shipping routes.</p>

                </div>

                <button type="button" id="addr-add-btn" class="flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-xl font-button text-button shadow-md hover:-translate-y-0.5 transition-all active:scale-95">

                    <span class="material-symbols-outlined">add_location_alt</span>
                    Add New Address

                </button>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">

                <div class="space-y-lg lg:col-span-12">

                    <?php if (!empty($addresses)): ?>

                        <?php foreach ($addresses as $addr): ?>

                            <?php
                                $addrDefault = (int) ($addr['is_default'] ?? 0) === 1;
                                $addrLabel   = strtolower((string) ($addr['label'] ?? ''));
                                $addrIcon    = 'location_on';
                                if ($addrDefault || str_contains($addrLabel, 'home')) {
                                    $addrIcon = 'home';
                                } elseif (str_contains($addrLabel, 'regional') || str_contains($addrLabel, 'logistics') || str_contains($addrLabel, 'center')) {
                                    $addrIcon = 'business';
                                } elseif (str_contains($addrLabel, 'office') || str_contains($addrLabel, 'work')) {
                                    $addrIcon = 'work';
                                }
                                $addrPhone  = $addr['phone'] ?? '';
                                $addrLine2  = $addr['address_line2'] ?? '';
                                $addrCity   = $addr['city'] ?? '';
                                $addrProv   = $addr['province'] ?? '';
                                $addrZip    = $addr['postal_code'] ?? '';
                                $addrCountry= $addr['country'] ?? 'Philippines';
                                $addrRegion = trim($addrCity . ($addrProv !== '' ? ', ' . $addrProv : '') . ($addrZip !== '' ? ' ' . $addrZip : ''));
                            ?>

                            <div class="<?= $addrDefault ? 'glass-card border-2 border-primary p-lg rounded-xl relative group hover:shadow-lg transition-all cursor-pointer hover:ring-2 hover:ring-primary' : 'bg-white border border-outline-variant p-lg rounded-xl hover:shadow-md transition-all group' ?>">

                                <?php if ($addrDefault): ?>

                                    <div class="absolute top-4 right-4 flex gap-1">

                                        <span class="bg-primary/10 text-primary px-3 py-1 rounded-full text-label-sm flex items-center gap-1">

                                            <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                            Default

                                        </span>

                                    </div>

                                <?php endif; ?>

                                <div class="flex gap-lg items-start">

                                    <div class="w-12 h-12 rounded-xl <?= $addrDefault ? 'bg-primary/10 text-primary' : 'bg-secondary-container/50 text-secondary' ?> flex items-center justify-center shrink-0">

                                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;"><?= esc($addrIcon) ?></span>

                                    </div>

                                    <div class="flex-1">

                                        <h3 class="text-title-lg font-bold text-on-surface mb-1"><?= esc($addr['label'] ?? 'Address') ?></h3>

                                        <p class="text-body-md text-on-surface-variant leading-relaxed">

                                            <span class="font-medium text-on-surface"><?= esc($addr['address_line1'] ?? '') ?></span><br>
                                            <?php if ($addrLine2 !== ''): ?>
                                                <span class="inline-flex items-center gap-1 text-primary font-semibold text-xs bg-primary/10 px-2 py-0.5 rounded-md my-0.5">
                                                    <span class="material-symbols-outlined text-[13px]">location_city</span>
                                                    Brgy. <?= esc($addrLine2) ?>
                                                </span><br>
                                            <?php endif; ?>
                                            <?= esc($addrCity !== '' ? $addrCity : 'Polomolok') ?>, <?= esc($addrProv !== '' ? $addrProv : 'South Cotabato') ?> <?= esc($addrZip !== '' ? $addrZip : '9504') ?><br>
                                            <?= esc($addrCountry) ?>

                                        </p>

                                        <p class="text-label-sm text-on-surface-variant mt-1">To: <?= esc($addr['recipient_name'] ?? '') ?></p>

                                        <div class="mt-md flex items-center gap-2 text-on-surface-variant">

                                            <span class="material-symbols-outlined text-[18px]">call</span>
                                            <span class="text-body-md"><?= esc($addrPhone !== '' ? $addrPhone : 'No phone number') ?></span>

                                        </div>

                                        <div class="mt-lg flex items-center gap-xl border-t border-outline-variant/30 pt-md <?= $addrDefault ? '' : 'opacity-0 group-hover:opacity-100 transition-opacity' ?>">

                                            <button type="button" class="addr-edit text-primary hover:underline text-button font-button flex items-center gap-1.5"
                                                data-id="<?= esc($addr['id']) ?>"
                                                data-label="<?= esc($addr['label'] ?? '') ?>"
                                                data-recipient="<?= esc($addr['recipient_name'] ?? '') ?>"
                                                data-phone="<?= esc($addrPhone) ?>"
                                                data-line1="<?= esc($addr['address_line1'] ?? '') ?>"
                                                data-line2="<?= esc($addrLine2) ?>"
                                                data-city="<?= esc($addrCity) ?>"
                                                data-province="<?= esc($addrProv) ?>"
                                                data-zip="<?= esc($addrZip) ?>"
                                                data-country="<?= esc($addrCountry) ?>"
                                                data-lat="<?= esc($addr['latitude'] ?? '') ?>"
                                                data-lng="<?= esc($addr['longitude'] ?? '') ?>"
                                                data-place_id="<?= esc($addr['place_id'] ?? '') ?>">

                                                <span class="material-symbols-outlined text-[18px]">edit</span> Edit

                                            </button>

                                            <?php if (!$addrDefault): ?>

                                                <form method="post" action="<?= base_url('customer/addresses/set-default') ?>">

                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="address_id" value="<?= esc($addr['id']) ?>">

                                                    <button type="submit" class="text-on-surface-variant hover:text-primary text-button font-button flex items-center gap-1.5">

                                                        <span class="material-symbols-outlined text-[18px]">star</span> Set as Default

                                                    </button>

                                                </form>

                                            <?php endif; ?>

                                            <form method="post" action="<?= base_url('customer/addresses/delete') ?>" onsubmit="return confirm('Delete this shipping address?');">

                                                <?= csrf_field() ?>
                                                <input type="hidden" name="address_id" value="<?= esc($addr['id']) ?>">

                                                <button type="submit" class="text-error hover:underline text-button font-button flex items-center gap-1.5">

                                                    <span class="material-symbols-outlined text-[18px]">delete</span> Delete

                                                </button>

                                            </form>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-xl text-center text-on-surface-variant">

                            <span class="material-symbols-outlined text-4xl text-outline mb-2">location_off</span>

                            <p class="mb-lg">No saved shipping addresses yet.</p>

                            <button type="button" id="addr-add-btn-empty" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-xl font-button text-button shadow-md hover:-translate-y-0.5 transition-all active:scale-95">

                                <span class="material-symbols-outlined">add_location_alt</span>
                                Add New Address

                            </button>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </main>

</div>

<div id="addr-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-md">

    <div class="absolute inset-0 bg-black/40" id="addr-overlay"></div>

    <div class="relative bg-surface-container-lowest rounded-2xl border border-outline-variant/30 shadow-xl w-full max-w-lg p-lg flex flex-col gap-md max-h-[90vh] overflow-y-auto">

        <div class="flex justify-between items-start gap-md">

            <h3 class="text-headline-md font-bold text-on-surface" id="addr-modal-title">Add New Address</h3>

            <button type="button" id="addr-modal-close" class="p-2 rounded-full hover:bg-surface-container-high text-on-surface-variant">

                <span class="material-symbols-outlined">close</span>

            </button>

        </div>

        <form method="post" action="<?= base_url('customer/addresses/save') ?>" class="flex flex-col gap-md">

            <?= csrf_field() ?>
            <input type="hidden" name="address_id" id="addr-id" value="">
            <input type="hidden" name="latitude" id="addr-latitude" value="">
            <input type="hidden" name="longitude" id="addr-longitude" value="">
            <input type="hidden" name="place_id" id="addr-place-id" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">

                <div class="flex flex-col gap-1">

                    <label for="addr-label" class="text-label-sm font-semibold text-on-surface-variant">Label</label>
                    <input type="text" id="addr-label" name="label" class="w-full p-md bg-surface-container-lowest border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary" placeholder="e.g. Home" required>

                </div>

                <div class="flex flex-col gap-1">

                    <label for="addr-recipient" class="text-label-sm font-semibold text-on-surface-variant">Recipient Name</label>
                    <input type="text" id="addr-recipient" name="recipient_name" class="w-full p-md bg-surface-container-lowest border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary" required>

                </div>

            </div>

            <div class="flex flex-col gap-1">

                <label for="addr-phone" class="text-label-sm font-semibold text-on-surface-variant">Phone Number</label>
                <input type="text" id="addr-phone" name="phone" class="w-full p-md bg-surface-container-lowest border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary" placeholder="+63 ...">

            </div>

            <div class="flex flex-col gap-1">

                <label for="addr-line1" class="text-label-sm font-semibold text-on-surface-variant">Address Line 1 / Street <span class="text-error">*</span></label>
                <input type="text" id="addr-line1" name="address_line1" placeholder="House No., Street Name, Purok / Block & Lot" class="w-full p-md bg-surface-container-lowest border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary" required>

            </div>

            <div class="flex flex-col gap-1">

                <label for="addr-barangay" class="text-label-sm font-semibold text-on-surface-variant">Barangay (Polomolok) <span class="text-error">*</span></label>
                <select id="addr-barangay" name="barangay" class="w-full p-md bg-surface-container-lowest border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary font-medium" required>
                    <option value="">-- Select Barangay --</option>
                    <option value="Bentung">Bentung</option>
                    <option value="Cannery Site">Cannery Site</option>
                    <option value="Crossing Palkan">Crossing Palkan</option>
                    <option value="Glamang">Glamang</option>
                    <option value="Kinilis">Kinilis</option>
                    <option value="Klinan 6">Klinan 6</option>
                    <option value="Koronadal Proper">Koronadal Proper</option>
                    <option value="Lam-Caliaf">Lam-Caliaf</option>
                    <option value="Landan">Landan</option>
                    <option value="Lumakil">Lumakil</option>
                    <option value="Maligo">Maligo</option>
                    <option value="Palkan">Palkan</option>
                    <option value="Poblacion">Poblacion</option>
                    <option value="Polo">Polo</option>
                    <option value="Pula Bato">Pula Bato</option>
                    <option value="Rubber">Rubber</option>
                    <option value="Silway 7">Silway 7</option>
                    <option value="Silway 8">Silway 8</option>
                    <option value="Sulit">Sulit</option>
                    <option value="Sumbakil">Sumbakil</option>
                    <option value="Upper Klinan">Upper Klinan</option>
                    <option value="Pagalungan">Pagalungan</option>
                    <option value="Magsaysay">Magsaysay</option>
                </select>
                <input type="hidden" id="addr-line2" name="address_line2" value="">

            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">

                <div class="flex flex-col gap-1">

                    <label for="addr-city" class="text-label-sm font-semibold text-on-surface-variant flex items-center justify-between">
                        <span>City / Municipality</span>
                        <span class="text-[11px] text-outline font-normal">Fixed</span>
                    </label>
                    <input type="text" id="addr-city" name="city" value="Polomolok" readonly class="w-full p-md bg-surface-container/50 border border-outline-variant/60 rounded-xl text-sm font-medium text-on-surface cursor-not-allowed select-none">

                </div>

                <div class="flex flex-col gap-1">

                    <label for="addr-province" class="text-label-sm font-semibold text-on-surface-variant flex items-center justify-between">
                        <span>Province</span>
                        <span class="text-[11px] text-outline font-normal">Fixed</span>
                    </label>
                    <input type="text" id="addr-province" name="province" value="South Cotabato" readonly class="w-full p-md bg-surface-container/50 border border-outline-variant/60 rounded-xl text-sm font-medium text-on-surface cursor-not-allowed select-none">

                </div>

            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">

                <div class="flex flex-col gap-1">

                    <label for="addr-zip" class="text-label-sm font-semibold text-on-surface-variant flex items-center justify-between">
                        <span>Postal Code</span>
                        <span class="text-[11px] text-outline font-normal">Fixed</span>
                    </label>
                    <input type="text" id="addr-zip" name="postal_code" value="9504" readonly class="w-full p-md bg-surface-container/50 border border-outline-variant/60 rounded-xl text-sm font-medium text-on-surface cursor-not-allowed select-none">

                </div>

                <div class="flex flex-col gap-1">

                    <label for="addr-country" class="text-label-sm font-semibold text-on-surface-variant flex items-center justify-between">
                        <span>Country</span>
                        <span class="text-[11px] text-outline font-normal">Fixed</span>
                    </label>
                    <input type="text" id="addr-country" name="country" value="Philippines" readonly class="w-full p-md bg-surface-container/50 border border-outline-variant/60 rounded-xl text-sm font-medium text-on-surface cursor-not-allowed select-none">

                </div>

            </div>

            <label class="flex items-center gap-md text-body-md text-on-surface" id="addr-default-wrap">

                <input type="checkbox" id="addr-default" name="is_default" value="1" class="w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary">
                Set as my default shipping address

            </label>

            <div class="flex justify-end gap-md border-t border-outline-variant/30 pt-md">

                <button type="button" id="addr-modal-cancel" class="px-xl py-md border border-outline-variant rounded-xl text-button font-button text-on-surface-variant hover:bg-surface-container-high transition-all">Cancel</button>
                <button type="submit" class="px-xl py-md bg-primary text-on-primary rounded-xl text-button font-button hover:bg-primary-container transition-all active:scale-95 shadow-lg shadow-primary/20">Save Address</button>

            </div>

        </form>

    </div>

</div>

<button type="button" class="fixed bottom-8 left-8 w-16 h-16 bg-surface-container-highest text-on-surface rounded-full shadow-lg border border-outline-variant/30 flex items-center justify-center hover:scale-110 transition-transform active:scale-95 z-50 group" aria-label="Go Back" onclick="window.history.back()">
    <span class="material-symbols-outlined text-[32px] group-hover:-translate-x-1 transition-transform">arrow_back</span>
</button>

<script>
    (function () {
        var modal = document.getElementById('addr-modal');
        var modalTitle = document.getElementById('addr-modal-title');
        var addrId = document.getElementById('addr-id');
        var defaultWrap = document.getElementById('addr-default-wrap');

        function field(id) { return document.getElementById(id); }

        function openAdd() {
            addrId.value = '';
            modalTitle.textContent = 'Add New Address';
            field('addr-label').value = '';
            field('addr-recipient').value = '';
            field('addr-phone').value = '';
            field('addr-line1').value = '';
            if (field('addr-latitude')) field('addr-latitude').value = '';
            if (field('addr-longitude')) field('addr-longitude').value = '';
            if (field('addr-place-id')) field('addr-place-id').value = '';
            if (field('addr-barangay')) field('addr-barangay').value = '';
            if (field('addr-line2')) field('addr-line2').value = '';
            field('addr-city').value = 'Polomolok';
            field('addr-province').value = 'South Cotabato';
            field('addr-zip').value = '9504';
            field('addr-country').value = 'Philippines';
            field('addr-default').checked = false;
            defaultWrap.classList.remove('hidden');
            modal.classList.remove('hidden');
        }

        function openEdit(btn) {
            addrId.value = btn.dataset.id;
            modalTitle.textContent = 'Edit Address';
            field('addr-label').value = btn.dataset.label || '';
            field('addr-recipient').value = btn.dataset.recipient || '';
            field('addr-phone').value = btn.dataset.phone || '';
            field('addr-line1').value = btn.dataset.line1 || '';
            if (field('addr-latitude')) field('addr-latitude').value = btn.dataset.lat || '';
            if (field('addr-longitude')) field('addr-longitude').value = btn.dataset.lng || '';
            if (field('addr-place-id')) field('addr-place-id').value = btn.dataset.place_id || '';
            var brgy = btn.dataset.line2 || '';
            if (field('addr-barangay')) field('addr-barangay').value = brgy;
            if (field('addr-line2')) field('addr-line2').value = brgy;
            field('addr-city').value = 'Polomolok';
            field('addr-province').value = 'South Cotabato';
            field('addr-zip').value = '9504';
            field('addr-country').value = 'Philippines';
            defaultWrap.classList.add('hidden');
            modal.classList.remove('hidden');
        }

        var bgSelect = field('addr-barangay');
        if (bgSelect) {
            bgSelect.addEventListener('change', function () {
                var l2 = field('addr-line2');
                if (l2) l2.value = this.value;
            });
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        var addBtn = document.getElementById('addr-add-btn');
        if (addBtn) addBtn.addEventListener('click', openAdd);
        var addBtnEmpty = document.getElementById('addr-add-btn-empty');
        if (addBtnEmpty) addBtnEmpty.addEventListener('click', openAdd);

        document.querySelectorAll('.addr-edit').forEach(function (btn) {
            btn.addEventListener('click', function () { openEdit(btn); });
        });

        document.getElementById('addr-overlay').addEventListener('click', closeModal);
        document.getElementById('addr-modal-close').addEventListener('click', closeModal);
        document.getElementById('addr-modal-cancel').addEventListener('click', closeModal);

        // Auto-open modal if redirected with ?open_add=1 or no saved address
        var params = new URLSearchParams(window.location.search);
        if (params.get('open_add') === '1' || window.location.hash === '#add' || <?= empty($addresses) ? 'true' : 'false' ?>) {
            openAdd();
        }

        let addressAutocomplete = null;
        window.initAddressAutocomplete = function() {
            const input = document.getElementById('addr-line1');
            if (!input || typeof google === 'undefined' || !google.maps || !google.maps.places) return;

            const poloBounds = new google.maps.LatLngBounds(
                { lat: 6.10, lng: 124.95 },
                { lat: 6.32, lng: 125.18 }
            );

            addressAutocomplete = new google.maps.places.Autocomplete(input, {
                bounds: poloBounds,
                componentRestrictions: { country: 'ph' },
                fields: ['address_components', 'geometry', 'name', 'place_id', 'formatted_address'],
                strictBounds: false
            });

            addressAutocomplete.addListener('place_changed', function() {
                const place = addressAutocomplete.getPlace();
                if (!place || !place.geometry || !place.geometry.location) return;

                const lat = place.geometry.location.lat();
                const lng = place.geometry.location.lng();
                const placeId = place.place_id || '';

                const latInput = document.getElementById('addr-latitude');
                const lngInput = document.getElementById('addr-longitude');
                const placeIdInput = document.getElementById('addr-place-id');

                if (latInput) latInput.value = lat;
                if (lngInput) lngInput.value = lng;
                if (placeIdInput) placeIdInput.value = placeId;

                // Auto-match barangay in dropdown if detected
                if (place.address_components) {
                    for (const component of place.address_components) {
                        const types = component.types;
                        if (types.includes('sublocality') || types.includes('sublocality_level_1') || types.includes('neighborhood') || types.includes('political')) {
                            const brgyName = component.long_name.replace(/^(Barangay|Brgy\.?)\s+/i, '').trim();
                            const brgySelect = document.getElementById('addr-barangay');
                            if (brgySelect) {
                                for (let i = 0; i < brgySelect.options.length; i++) {
                                    if (brgySelect.options[i].value.toLowerCase() === brgyName.toLowerCase()) {
                                        brgySelect.selectedIndex = i;
                                        const l2 = document.getElementById('addr-line2');
                                        if (l2) l2.value = brgySelect.options[i].value;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            });
        };

        document.addEventListener('DOMContentLoaded', function() {
            if (typeof google !== 'undefined' && google.maps && google.maps.places && !addressAutocomplete) {
                window.initAddressAutocomplete();
            }
        });
    })();
</script>

<!-- Google Maps Places API -->
<script src="https://maps.googleapis.com/maps/api/js?key=<?= esc(env('GOOGLE_MAPS_API_KEY')) ?>&libraries=places&loading=async&callback=initAddressAutocomplete" async defer></script>

<?= $this->endSection() ?>