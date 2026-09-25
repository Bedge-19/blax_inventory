<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<?php
    $fullName  = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    if ($fullName === '') {
        $fullName = session()->get('user_name') ?? 'Customer';
    }
    $avatarInitial = strtoupper(substr($fullName, 0, 1));
    $profileImage  = $user['profile_image_url'] ?? '';

    $totalOrders         = $totalOrders ?? 0;
    $activePrinting      = $activePrinting ?? 0;
    $savedAddressesCount = $savedAddressesCount ?? 0;
    $favoriteCount       = $favoriteCount ?? 0;
    $recentActivity      = $recentActivity ?? [];
    $defaultAddress      = $defaultAddress ?? null;

    $memberSince = date('F Y', strtotime($user['created_at'] ?? 'now'));
?>

<div class="flex-1 flex flex-col md:flex-row w-full min-h-[calc(100vh-72px)] bg-slate-50/50">

    <?= view('components/profile_sidebar', ['activeNav' => 'overview']) ?>

    <!-- Main Content Canvas -->
    <main class="flex-1 p-3 sm:p-6 md:p-8 lg:p-10 overflow-y-auto">

        <?php if (session()->getFlashdata('success')): ?>
            <div class="p-4 rounded-2xl bg-green-500/10 border border-green-500/30 text-green-700 dark:text-green-300 text-xs sm:text-sm font-semibold mb-6 flex items-center gap-2.5 shadow-2xs animate-fade-in">
                <span class="material-symbols-outlined text-lg shrink-0">check_circle</span>
                <span><?= session()->getFlashdata('success') ?></span>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="p-4 rounded-2xl bg-error-container/20 border border-error/30 text-error text-xs sm:text-sm font-semibold mb-6 flex items-center gap-2.5 shadow-2xs animate-fade-in">
                <span class="material-symbols-outlined text-lg shrink-0">error</span>
                <span><?= session()->getFlashdata('error') ?></span>
            </div>
        <?php endif; ?>

        <div class="max-w-4xl mx-auto space-y-6 pb-12">

            <!-- 1. Hero Profile Header -->
            <section class="bg-surface-container-lowest rounded-3xl border border-outline-variant/30 overflow-hidden shadow-2xs hover:shadow-sm transition-all">
                <!-- Cover Banner Gradient -->
                <div class="h-24 sm:h-32 bg-gradient-to-r from-primary/80 via-blue-600 to-indigo-600 relative">
                    <div class="absolute inset-0 bg-black/10"></div>
                </div>

                <div class="px-5 pb-6 sm:px-8 sm:pb-8 flex flex-col sm:flex-row items-center sm:items-end justify-between gap-4 -mt-12 sm:-mt-14 relative z-10 text-center sm:text-left">
                    <!-- Avatar & User Titles -->
                    <div class="flex flex-col sm:flex-row items-center gap-4 min-w-0">
                        <div class="relative group shrink-0">
                            <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full border-4 border-surface-container-lowest shadow-md overflow-hidden bg-primary/10 text-primary flex items-center justify-center text-3xl sm:text-4xl font-black ring-2 ring-primary/20">
                                <?php if (!empty($profileImage)): ?>
                                    <img class="w-full h-full object-cover" src="<?= esc(profile_image_url($profileImage)) ?>" alt="<?= esc($fullName) ?> avatar">
                                <?php else: ?>
                                    <?= esc($avatarInitial) ?>
                                <?php endif; ?>
                            </div>
                            <span class="absolute bottom-1 right-1 w-4 h-4 rounded-full bg-emerald-500 border-2 border-surface-container-lowest" title="Account Active"></span>
                        </div>

                        <div class="min-w-0">
                            <h1 class="text-xl sm:text-2xl font-black text-on-surface truncate"><?= esc($fullName) ?></h1>
                            <p class="text-xs text-on-surface-variant flex items-center justify-center sm:justify-start gap-1.5 mt-1">
                                <span class="material-symbols-outlined text-[16px] text-primary">verified</span>
                                <span>Verified Customer</span>
                                <span class="text-outline">•</span>
                                <span>Member since <?= esc($memberSince) ?></span>
                            </p>
                        </div>
                    </div>

                    <!-- Edit Profile Action Button -->
                    <div class="shrink-0 w-full sm:w-auto">
                        <button type="button" data-open-edit-modal class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 bg-primary text-white px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm hover:bg-primary/90 hover:shadow-md transition-all active:scale-95 cursor-pointer shadow-2xs">
                            <span class="material-symbols-outlined text-[17px]">edit</span>
                            <span>Edit Profile</span>
                        </button>
                    </div>
                </div>
            </section>

            <!-- 2. Bento KPI Metric Cards (2x2 on Mobile, 4x1 on Desktop) -->
            <section class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                
                <!-- Total Orders -->
                <a href="<?= base_url('customer/orders') ?>" class="bg-surface-container-lowest border border-outline-variant/30 p-4 sm:p-5 rounded-2xl shadow-2xs hover:shadow-sm hover:border-primary/40 transition-all group flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-on-surface-variant">Orders</span>
                        <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-[18px]">shopping_bag</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-xl sm:text-2xl font-black text-on-surface leading-tight"><?= (int) $totalOrders ?></p>
                        <span class="text-[10px] sm:text-xs font-semibold text-primary mt-1 inline-flex items-center gap-0.5">
                            <span>View Orders</span>
                            <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                        </span>
                    </div>
                </a>

                <!-- Active Printing -->
                <a href="<?= base_url('customer/printing') ?>" class="bg-surface-container-lowest border border-outline-variant/30 p-4 sm:p-5 rounded-2xl shadow-2xs hover:shadow-sm hover:border-primary/40 transition-all group flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-on-surface-variant">Printing</span>
                        <div class="w-8 h-8 rounded-xl bg-purple-500/10 text-purple-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-[18px]">print</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-xl sm:text-2xl font-black text-on-surface leading-tight"><?= (int) $activePrinting ?></p>
                        <span class="text-[10px] sm:text-xs font-semibold text-purple-600 dark:text-purple-400 mt-1 inline-flex items-center gap-0.5">
                            <span><?= $activePrinting > 0 ? $activePrinting . ' in progress' : 'Active Jobs' ?></span>
                            <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                        </span>
                    </div>
                </a>

                <!-- Saved Addresses -->
                <a href="<?= base_url('customer/addresses') ?>" class="bg-surface-container-lowest border border-outline-variant/30 p-4 sm:p-5 rounded-2xl shadow-2xs hover:shadow-sm hover:border-primary/40 transition-all group flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-on-surface-variant">Addresses</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-[18px]">location_on</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-xl sm:text-2xl font-black text-on-surface leading-tight"><?= (int) $savedAddressesCount ?></p>
                        <span class="text-[10px] sm:text-xs font-semibold text-emerald-600 dark:text-emerald-400 mt-1 inline-flex items-center gap-0.5">
                            <span>Manage</span>
                            <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                        </span>
                    </div>
                </a>

                <!-- Favorite Shops -->
                <a href="<?= base_url('customer/favorites') ?>" class="bg-surface-container-lowest border border-outline-variant/30 p-4 sm:p-5 rounded-2xl shadow-2xs hover:shadow-sm hover:border-primary/40 transition-all group flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-on-surface-variant">Favorites</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-500/10 text-rose-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-[18px]">favorite</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-xl sm:text-2xl font-black text-on-surface leading-tight"><?= (int) $favoriteCount ?></p>
                        <span class="text-[10px] sm:text-xs font-semibold text-rose-600 dark:text-rose-400 mt-1 inline-flex items-center gap-0.5">
                            <span>Saved Shops</span>
                            <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                        </span>
                    </div>
                </a>

            </section>

            <!-- 3. Personal & Contact Information Card -->
            <section class="bg-surface-container-lowest border border-outline-variant/30 rounded-3xl overflow-hidden shadow-2xs">
                <div class="px-5 py-4 bg-surface-container-low border-b border-outline-variant/20 flex items-center justify-between">
                    <h2 class="text-xs sm:text-sm font-extrabold uppercase tracking-wider text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-primary">person</span>
                        <span>Personal Information</span>
                    </h2>
                    <button type="button" data-open-edit-modal class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-[15px]">edit</span>
                        <span>Edit</span>
                    </button>
                </div>

                <div class="p-5 sm:p-6 grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 text-xs sm:text-sm">
                    <div class="space-y-1 p-3 rounded-2xl bg-surface-container-low/50 border border-outline-variant/20">
                        <span class="text-[10px] uppercase font-bold text-outline tracking-wider block">First Name</span>
                        <p class="font-bold text-on-surface text-sm sm:text-base"><?= esc($user['first_name'] ?? '—') ?></p>
                    </div>

                    <div class="space-y-1 p-3 rounded-2xl bg-surface-container-low/50 border border-outline-variant/20">
                        <span class="text-[10px] uppercase font-bold text-outline tracking-wider block">Last Name</span>
                        <p class="font-bold text-on-surface text-sm sm:text-base"><?= esc($user['last_name'] ?? '—') ?></p>
                    </div>

                    <div class="space-y-1 p-3 rounded-2xl bg-surface-container-low/50 border border-outline-variant/20">
                        <span class="text-[10px] uppercase font-bold text-outline tracking-wider block">Email Address</span>
                        <p class="font-bold text-on-surface text-sm sm:text-base break-all flex items-center gap-1.5">
                            <span><?= esc($user['email'] ?? '—') ?></span>
                            <span class="material-symbols-outlined text-primary text-[16px]">verified</span>
                        </p>
                    </div>

                    <div class="space-y-1 p-3 rounded-2xl bg-surface-container-low/50 border border-outline-variant/20">
                        <span class="text-[10px] uppercase font-bold text-outline tracking-wider block">Contact Phone</span>
                        <p class="font-bold text-on-surface text-sm sm:text-base">
                            <?= !empty($user['phone']) ? esc($user['phone']) : '<span class="text-outline italic font-normal">Not provided</span>' ?>
                        </p>
                    </div>
                </div>
            </section>

            <!-- 4. Default Shipping Address Spotlight Card -->
            <section class="bg-surface-container-lowest border border-outline-variant/30 rounded-3xl overflow-hidden shadow-2xs">
                <div class="px-5 py-4 bg-surface-container-low border-b border-outline-variant/20 flex items-center justify-between">
                    <h2 class="text-xs sm:text-sm font-extrabold uppercase tracking-wider text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-primary">local_shipping</span>
                        <span>Primary Delivery Address</span>
                    </h2>
                    <a href="<?= base_url('customer/addresses') ?>" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                        <span>Manage All</span>
                        <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                    </a>
                </div>

                <div class="p-5 sm:p-6">
                    <?php if ($defaultAddress): ?>
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-xl">home</span>
                            </div>
                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="font-bold text-sm text-on-surface"><?= esc($defaultAddress['recipient_name'] ?? $fullName) ?></h3>
                                    <span class="text-xs text-on-surface-variant font-medium"><?= esc($defaultAddress['phone_number'] ?? '') ?></span>
                                    <span class="px-2 py-0.2 rounded-full text-[10px] font-bold bg-primary/10 text-primary border border-primary/20">Default Address</span>
                                </div>
                                <p class="text-xs sm:text-sm text-on-surface-variant leading-relaxed">
                                    <?= esc($defaultAddress['address_line1']) ?><?= !empty($defaultAddress['address_line2']) ? (', ' . esc($defaultAddress['address_line2'])) : '' ?>,
                                    <?= esc($defaultAddress['city']) ?>, <?= esc($defaultAddress['province']) ?> <?= esc($defaultAddress['postal_code'] ?? '') ?>
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 space-y-2">
                            <p class="text-xs sm:text-sm text-on-surface-variant">No default shipping address set yet.</p>
                            <a href="<?= base_url('customer/addresses') ?>" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                                <span class="material-symbols-outlined text-[16px]">add_location</span>
                                <span>Add a shipping address</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- 5. Recent Activity Feed (Mobile Responsive Timeline) -->
            <section class="bg-surface-container-lowest border border-outline-variant/30 rounded-3xl overflow-hidden shadow-2xs">
                <div class="px-5 py-4 bg-surface-container-low border-b border-outline-variant/20 flex items-center justify-between">
                    <h2 class="text-xs sm:text-sm font-extrabold uppercase tracking-wider text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-primary">history</span>
                        <span>Recent Activity</span>
                    </h2>
                </div>

                <div class="p-4 sm:p-6">
                    <?php if (!empty($recentActivity)): ?>
                        <div class="divide-y divide-outline-variant/15">
                            <?php foreach ($recentActivity as $activity): ?>
                                <?php
                                    $isPr = str_starts_with($activity['reference'] ?? '', 'PR-');
                                    $actIcon = $isPr ? 'print' : 'shopping_bag';
                                    $actIconColor = $isPr ? 'bg-purple-500/10 text-purple-600' : 'bg-blue-500/10 text-blue-600';
                                    $actDate = !empty($activity['date']) ? date('M d, Y • h:i A', strtotime($activity['date'])) : '—';
                                ?>
                                <div class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-2xl <?= $actIconColor ?> flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-lg"><?= $actIcon ?></span>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-mono font-bold text-xs sm:text-sm text-primary">#<?= esc($activity['reference']) ?></span>
                                                <span class="text-xs font-bold text-on-surface truncate"><?= esc($activity['service']) ?></span>
                                            </div>
                                            <p class="text-[11px] text-on-surface-variant mt-0.5"><?= esc($actDate) ?></p>
                                        </div>
                                    </div>

                                    <div class="shrink-0">
                                        <?= status_badge($activity['status']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-6 text-on-surface-variant space-y-1">
                            <span class="material-symbols-outlined text-3xl text-outline mb-1">history</span>
                            <p class="text-xs sm:text-sm">No recent activity found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

        </div>

    </main>

</div>

<!-- Edit Profile Modal -->
<div id="edit-profile-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" data-close-edit-modal></div>

    <div class="bg-surface-container-lowest w-full max-w-lg rounded-3xl shadow-2xl border border-outline-variant/30 flex flex-col relative overflow-hidden z-10 animate-scale-up">
        
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-outline-variant/20 bg-surface-container-low flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">person</span>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-on-surface m-0">Edit Profile</h2>
                    <p class="text-xs text-on-surface-variant m-0">Update your account information</p>
                </div>
            </div>

            <button type="button" data-close-edit-modal class="p-1.5 text-on-surface-variant hover:bg-surface-container rounded-full transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <!-- Modal Form -->
        <form action="<?= base_url('customer/profile/update') ?>" method="POST" enctype="multipart/form-data" class="p-6 space-y-5 overflow-y-auto max-h-[calc(100vh_-_140px)]">
            <?= csrf_field() ?>

            <!-- Avatar Uploader -->
            <div class="flex flex-col items-center gap-3">
                <div id="edit-avatar-preview" class="w-24 h-24 rounded-full border-4 border-surface-container-lowest shadow-md overflow-hidden bg-primary/10 text-primary flex items-center justify-center text-3xl font-black ring-2 ring-primary/20">
                    <?php if (!empty($profileImage)): ?>
                        <img class="w-full h-full object-cover" src="<?= esc(profile_image_url($profileImage)) ?>" alt="<?= esc($fullName) ?> avatar">
                    <?php else: ?>
                        <?= esc($avatarInitial) ?>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-3">
                    <label class="text-primary font-bold text-xs cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary/10 hover:bg-primary/20 transition-colors">
                        <span class="material-symbols-outlined text-[16px]">photo_camera</span>
                        <span>Change Photo</span>
                        <input id="profile_image" name="profile_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden">
                    </label>

                    <?php if (!empty($profileImage)): ?>
                        <label class="text-error font-bold text-xs cursor-pointer inline-flex items-center gap-1 hover:underline">
                            <input type="checkbox" name="remove_profile_image" value="1" class="accent-error">
                            <span>Remove</span>
                        </label>
                    <?php endif; ?>
                </div>

                <p id="edit-avatar-error" class="text-error text-xs font-semibold hidden"></p>
            </div>

            <!-- Profile Input Fields -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label for="first_name" class="text-xs font-bold text-on-surface-variant block">First Name</label>
                    <input id="first_name" name="first_name" type="text" value="<?= esc($user['first_name'] ?? '') ?>" class="w-full bg-surface-container-low border border-outline-variant/40 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold focus:ring-2 focus:ring-primary focus:border-primary transition-all" required>
                </div>

                <div class="space-y-1">
                    <label for="last_name" class="text-xs font-bold text-on-surface-variant block">Last Name</label>
                    <input id="last_name" name="last_name" type="text" value="<?= esc($user['last_name'] ?? '') ?>" class="w-full bg-surface-container-low border border-outline-variant/40 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold focus:ring-2 focus:ring-primary focus:border-primary transition-all" required>
                </div>

                <div class="space-y-1 sm:col-span-2">
                    <label for="email" class="text-xs font-bold text-on-surface-variant block">Email Address</label>
                    <div class="relative">
                        <input id="email" name="email" type="email" value="<?= esc($user['email'] ?? '') ?>" class="w-full bg-surface-container-low border border-outline-variant/40 rounded-xl px-3 py-2 pr-10 text-xs sm:text-sm font-semibold focus:ring-2 focus:ring-primary focus:border-primary transition-all" required>
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-primary text-[18px]">verified</span>
                    </div>
                </div>

                <div class="space-y-1 sm:col-span-2">
                    <label for="phone" class="text-xs font-bold text-on-surface-variant block">Phone Number</label>
                    <input id="phone" name="phone" type="tel" value="<?= esc($user['phone'] ?? '') ?>" placeholder="09XXXXXXXXX" class="w-full bg-surface-container-low border border-outline-variant/40 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                </div>
            </div>

            <!-- Modal Footer Buttons -->
            <div class="flex justify-end gap-3 pt-3 border-t border-outline-variant/20">
                <button type="button" data-close-edit-modal class="px-4 py-2.5 rounded-xl border border-outline-variant/40 text-on-surface-variant hover:bg-surface-container transition-all text-xs font-bold">Cancel</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary text-white font-bold text-xs hover:bg-primary/90 transition-all shadow-md active:scale-95">Save Changes</button>
            </div>

        </form>

    </div>

</div>

<script>
(function () {
    var modal        = document.getElementById('edit-profile-modal');
    var openers      = document.querySelectorAll('[data-open-edit-modal]');
    var closers      = document.querySelectorAll('[data-close-edit-modal]');
    var fileInput    = document.getElementById('profile_image');
    var avatarBox    = document.getElementById('edit-avatar-preview');
    var avatarError  = document.getElementById('edit-avatar-error');

    function openModal() {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if (avatarError) avatarError.classList.add('hidden');
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    openers.forEach(function (btn) { btn.addEventListener('click', openModal); });
    closers.forEach(function (btn) { btn.addEventListener('click', closeModal); });

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0];
            if (!file) return;

            var allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (allowed.indexOf(file.type) === -1) {
                if (avatarError) {
                    avatarError.textContent = 'Only JPG, PNG, WEBP or GIF images are allowed.';
                    avatarError.classList.remove('hidden');
                }
                fileInput.value = '';
                return;
            }

            if (file.size > 2097152) {
                if (avatarError) {
                    avatarError.textContent = 'Profile picture must be 2 MB or smaller.';
                    avatarError.classList.remove('hidden');
                }
                fileInput.value = '';
                return;
            }

            if (avatarError) avatarError.classList.add('hidden');
            if (avatarBox) {
                avatarBox.innerHTML = '<img class="w-full h-full object-cover" src="' + URL.createObjectURL(file) + '" alt="Profile preview">';
            }
        });
    }
})();
</script>

<?= $this->endSection() ?>
