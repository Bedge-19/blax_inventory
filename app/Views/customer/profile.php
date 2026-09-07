<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<?php
    $fullName  = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    if ($fullName === '') {
        $fullName = session()->get('user_name') ?? 'User';
    }
    $avatarInitial = strtoupper(substr($fullName, 0, 1));
    $profileImage  = $user['profile_image_url'] ?? '';

    $totalOrders    = $totalOrders ?? 0;
    $activePrinting = $activePrinting ?? 0;
    $recentActivity = $recentActivity ?? [];

    $memberSince = date('F Y', strtotime($user['created_at'] ?? 'now'));
?>

<div class="flex-1 flex flex-col md:flex-row max-w-container-max mx-auto w-full">

    <?= view('components/profile_sidebar', ['activeNav' => 'overview']) ?>

    <!-- Main Content Canvas -->
    <main class="flex-1 p-lg md:p-xl lg:p-xxl bg-surface/50 overflow-y-auto">

        <?php if (session()->getFlashdata('success')): ?>

            <div class="p-md rounded-xl bg-green-100 text-green-800 text-sm font-medium mb-lg"><?= session()->getFlashdata('success') ?></div>

        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>

            <div class="p-md rounded-xl bg-error-container text-on-error-container text-sm font-medium mb-lg"><?= session()->getFlashdata('error') ?></div>

        <?php endif; ?>

        <div class="max-w-4xl mx-auto space-y-lg pb-xl">

            <!-- Profile Header -->
            <section class="glass-card rounded-xl p-lg flex flex-col md:flex-row items-center gap-lg shadow-sm hover:shadow-md transition-all duration-300">

                <div class="relative group">

                    <div class="w-32 h-32 rounded-full border-4 border-white shadow-lg overflow-hidden bg-primary/10 flex items-center justify-center text-4xl font-bold text-primary">

                        <?php if (!empty($profileImage)): ?>

                            <img class="w-full h-full object-cover" src="<?= esc(base_url($profileImage)) ?>" alt="<?= esc($fullName) ?> avatar">

                        <?php else: ?>

                            <?= esc($avatarInitial) ?>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="text-center md:text-left flex-1">

                    <h1 class="text-headline-lg font-headline-lg text-on-surface"><?= esc($fullName) ?></h1>

                    <p class="text-body-md font-body-md text-on-surface-variant opacity-80 flex items-center justify-center md:justify-start gap-xs mt-xs">

                        <span class="material-symbols-outlined text-[18px] text-primary">verified</span>
                        Member since <?= esc($memberSince) ?>

                    </p>

                </div>

                <div class="w-full md:w-auto">

                    <button type="button" data-open-edit-modal class="w-full md:w-auto bg-primary text-on-primary px-xl py-sm rounded-lg font-button hover:translate-y-[-2px] transition-all shadow-md active:scale-95 inline-block text-center">Edit Profile</button>

                </div>

            </section>

            <!-- Quick Stats Bento -->
            <section class="grid grid-cols-1 sm:grid-cols-2 gap-lg">

                <div class="bg-surface-container-lowest border border-outline-variant/30 p-xl rounded-xl shadow-sm hover:shadow-md transition-all group">

                    <div class="flex items-center justify-between mb-md">

                        <span class="text-on-surface-variant font-label-sm uppercase tracking-wider">Total Orders</span>
                        <span class="material-symbols-outlined text-primary opacity-40 group-hover:opacity-100 transition-opacity">shopping_cart</span>

                    </div>

                    <p class="text-[32px] font-bold leading-tight"><?= (int) $totalOrders ?></p>

                    <p class="text-label-sm text-primary mt-sm font-semibold"><?= $totalOrders === 1 ? '1 order placed' : ($totalOrders . ' orders placed') ?></p>

                </div>

                <div class="bg-surface-container-lowest border border-outline-variant/30 p-xl rounded-xl shadow-sm hover:shadow-md transition-all group">

                    <div class="flex items-center justify-between mb-md">

                        <span class="text-on-surface-variant font-label-sm uppercase tracking-wider">Active Printing</span>
                        <span class="material-symbols-outlined text-primary opacity-40 group-hover:opacity-100 transition-opacity">print</span>

                    </div>

                    <p class="text-[32px] font-bold leading-tight"><?= str_pad((string) (int) $activePrinting, 2, '0', STR_PAD_LEFT) ?></p>

                    <p class="text-label-sm text-tertiary mt-sm font-semibold"><?= $activePrinting === 1 ? '1 in production' : ($activePrinting . ' in production') ?></p>

                </div>

            </section>

            <!-- Account Information Section -->
            <section id="account-information" class="bg-surface-container-lowest border border-outline-variant/30 rounded-xl overflow-hidden shadow-sm scroll-mt-24">

                <div class="px-lg py-md border-b border-outline-variant/20 bg-surface-container-low flex justify-between items-center">

                    <h2 class="text-title-lg font-title-lg text-on-surface">Account Information</h2>

                </div>

                <div class="p-lg grid grid-cols-1 md:grid-cols-2 gap-lg">

                    <div class="space-y-base">

                        <span class="text-label-sm font-label-sm text-on-surface-variant block">First Name</span>
                        <p class="text-body-md font-body-md text-on-surface"><?= esc($user['first_name'] ?? '—') ?></p>

                    </div>

                    <div class="space-y-base">

                        <span class="text-label-sm font-label-sm text-on-surface-variant block">Last Name</span>
                        <p class="text-body-md font-body-md text-on-surface"><?= esc($user['last_name'] ?? '—') ?></p>

                    </div>

                    <div class="space-y-base">

                        <span class="text-label-sm font-label-sm text-on-surface-variant block">Email Address</span>
                        <p class="text-body-md font-body-md text-on-surface"><?= esc($user['email'] ?? '—') ?></p>

                    </div>

                    <div class="space-y-base">

                        <span class="text-label-sm font-label-sm text-on-surface-variant block">Phone Number</span>
                        <p class="text-body-md font-body-md text-on-surface"><?= esc($user['phone'] !== null && $user['phone'] !== '' ? $user['phone'] : '—') ?></p>

                    </div>

                </div>

            </section>

            <!-- Recent Activity Table -->
            <section class="bg-surface-container-lowest border border-outline-variant/30 rounded-xl overflow-hidden shadow-sm">

                <div class="px-lg py-md border-b border-outline-variant/20 bg-surface-container-low">

                    <h2 class="text-title-lg font-title-lg text-on-surface">Recent Activity</h2>

                </div>

                <div class="table-responsive">

                    <?php if (!empty($recentActivity)): ?>

                        <table class="w-full text-left">

                            <thead class="bg-surface-container-high/30">

                                <tr>

                                    <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-60 uppercase">Reference</th>
                                    <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-60 uppercase">Service</th>
                                    <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-60 uppercase">Date</th>
                                    <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-60 uppercase">Status</th>

                                </tr>

                            </thead>

                            <tbody class="divide-y divide-outline-variant/20">

                                <?php foreach ($recentActivity as $activity): ?>

                                    <tr class="hover:bg-surface-container-low/50 transition-colors">

                                        <td class="px-lg py-md font-medium">#<?= esc($activity['reference']) ?></td>
                                        <td class="px-lg py-md"><?= esc($activity['service']) ?></td>
                                        <td class="px-lg py-md text-on-surface-variant"><?= $activity['date'] ? date('M d, Y', strtotime($activity['date'])) : '—' ?></td>
                                        <td class="px-lg py-md"><?= status_badge($activity['status']) ?></td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    <?php else: ?>

                        <div class="p-xl text-center text-on-surface-variant">

                            <span class="material-symbols-outlined text-4xl text-outline mb-2">history</span>

                            <p>No recent activity yet.</p>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

        </div>

    </main>

</div>

<button aria-label="Go Back" onclick="window.history.back()" class="fixed bottom-lg left-lg w-16 h-16 bg-surface-container-highest text-on-surface rounded-full shadow-xl flex items-center justify-center z-50 hover:scale-105 transition-transform duration-200 border border-outline-variant">

    <span class="material-symbols-outlined text-[32px]">arrow_back</span>

</button>

<!-- Edit Profile Modal -->
<div id="edit-profile-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-lg">

    <div class="absolute inset-0 bg-black/40" data-close-edit-modal></div>

    <div class="glass-panel w-full max-w-lg rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.2)] flex flex-col relative overflow-hidden">

        <div class="px-lg py-md border-b border-outline-variant/30 bg-surface-container-lowest/50 flex justify-between items-center">

            <div class="flex items-center gap-md">

                <div class="w-10 h-10 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center">

                    <span class="material-symbols-outlined text-title-lg fill-icon">person</span>

                </div>

                <div>

                    <h2 class="text-title-lg font-title-lg text-on-surface m-0">Edit Profile</h2>
                    <p class="text-label-sm font-label-sm text-on-surface-variant m-0">Update your account details</p>

                </div>

            </div>

            <button type="button" data-close-edit-modal class="p-2 text-on-surface-variant hover:bg-surface-container rounded-full transition-colors">

                <span class="material-symbols-outlined">close</span>

            </button>

        </div>

        <form action="<?= base_url('customer/profile/update') ?>" method="POST" enctype="multipart/form-data" class="p-lg space-y-lg overflow-y-auto max-h-[calc(100vh-160px)]">

            <?= csrf_field() ?>

            <div class="flex flex-col items-center gap-md">

                <div id="edit-avatar-preview" class="w-28 h-28 rounded-full border-4 border-white shadow-lg overflow-hidden bg-primary/10 flex items-center justify-center text-4xl font-bold text-primary">

                    <?php if (!empty($profileImage)): ?>

                        <img class="w-full h-full object-cover" src="<?= esc(base_url($profileImage)) ?>" alt="<?= esc($fullName) ?> avatar">

                    <?php else: ?>

                        <?= esc($avatarInitial) ?>

                    <?php endif; ?>

                </div>

                <label class="text-primary font-button text-button cursor-pointer flex items-center gap-xs hover:underline">

                    <span class="material-symbols-outlined text-[18px]">photo_camera</span> Change Photo

                    <input id="profile_image" name="profile_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden">

                </label>

                <?php if (!empty($profileImage)): ?>

                    <label class="text-error font-button text-button cursor-pointer flex items-center gap-xs hover:underline">

                        <input type="checkbox" name="remove_profile_image" value="1" class="accent-primary"> Remove current picture

                    </label>

                <?php endif; ?>

                <p id="edit-avatar-error" class="text-error text-label-sm font-label-sm hidden"></p>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">

                <div class="space-y-base">

                    <label for="first_name" class="text-label-sm font-label-sm text-on-surface-variant block">First Name</label>
                    <input id="first_name" name="first_name" type="text" value="<?= esc($user['first_name'] ?? '') ?>" class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg px-md py-sm focus:ring-primary focus:border-primary transition-all">

                </div>

                <div class="space-y-base">

                    <label for="last_name" class="text-label-sm font-label-sm text-on-surface-variant block">Last Name</label>
                    <input id="last_name" name="last_name" type="text" value="<?= esc($user['last_name'] ?? '') ?>" class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg px-md py-sm focus:ring-primary focus:border-primary transition-all">

                </div>

                <div class="space-y-base md:col-span-2">

                    <label for="email" class="text-label-sm font-label-sm text-on-surface-variant block">Email Address</label>

                    <div class="relative">

                        <input id="email" name="email" type="email" value="<?= esc($user['email'] ?? '') ?>" class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg px-md py-sm pr-xl focus:ring-primary focus:border-primary transition-all">

                        <?php if (!empty($user['email'])): ?>

                            <span class="material-symbols-outlined absolute right-md top-1/2 -translate-y-1/2 text-primary text-[20px]">check_circle</span>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="space-y-base md:col-span-2">

                    <label for="phone" class="text-label-sm font-label-sm text-on-surface-variant block">Phone Number</label>
                    <input id="phone" name="phone" type="tel" value="<?= esc($user['phone'] ?? '') ?>" class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg px-md py-sm focus:ring-primary focus:border-primary transition-all">

                </div>

            </div>

            <div class="flex justify-end gap-md pt-lg border-t border-outline-variant/20">

                <button type="button" data-close-edit-modal class="px-lg py-sm rounded-lg border border-outline-variant/40 text-on-surface-variant hover:bg-surface-container transition-all font-button">Cancel</button>

                <button type="submit" class="px-lg py-sm rounded-lg bg-primary text-on-primary font-button hover:translate-y-[-2px] transition-all shadow-md active:scale-95">Save Changes</button>

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
