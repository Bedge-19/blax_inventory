<?php
    $activeNav = $activeNav ?? 'overview';
    $userName  = session()->get('user_name') ?? 'Customer';
    $userEmail = session()->get('user_email') ?? '';
    $userImage = session()->get('profile_image_url') ?? '';
    $userInitial = strtoupper(substr($userName, 0, 1));
?>

<!-- Mobile Segmented Tabs (Sticky Glassmorphism Header Rail) -->
<div class="md:hidden w-full bg-surface-container-lowest/95 backdrop-blur-md border-b border-outline-variant/30 sticky top-[60px] sm:top-[72px] z-30 shadow-2xs transition-all">
    <nav class="mobile-profile-tabs px-3 py-2 flex items-center gap-1.5 overflow-x-auto no-scrollbar scroll-smooth">
        <a class="px-3.5 py-1.5 whitespace-nowrap text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all <?= $activeNav === 'overview' ? 'bg-primary text-white shadow-xs' : 'bg-surface-container-low text-on-surface-variant hover:text-primary hover:bg-surface-container border border-outline-variant/40' ?>" href="<?= base_url('customer/profile') ?>">
            <span class="material-symbols-outlined text-[16px] <?= $activeNav === 'overview' ? 'fill-icon text-white' : '' ?>">account_circle</span>
            <span>Overview</span>
        </a>
        <a class="px-3.5 py-1.5 whitespace-nowrap text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all <?= $activeNav === 'orders' ? 'bg-primary text-white shadow-xs' : 'bg-surface-container-low text-on-surface-variant hover:text-primary hover:bg-surface-container border border-outline-variant/40' ?>" href="<?= base_url('customer/orders') ?>">
            <span class="material-symbols-outlined text-[16px] <?= $activeNav === 'orders' ? 'fill-icon text-white' : '' ?>">shopping_bag</span>
            <span>My Orders</span>
        </a>
        <a class="px-3.5 py-1.5 whitespace-nowrap text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all <?= $activeNav === 'printing' ? 'bg-primary text-white shadow-xs' : 'bg-surface-container-low text-on-surface-variant hover:text-primary hover:bg-surface-container border border-outline-variant/40' ?>" href="<?= base_url('customer/printing') ?>">
            <span class="material-symbols-outlined text-[16px] <?= $activeNav === 'printing' ? 'fill-icon text-white' : '' ?>">print</span>
            <span>Printing</span>
        </a>
        <a class="px-3.5 py-1.5 whitespace-nowrap text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all <?= $activeNav === 'addresses' ? 'bg-primary text-white shadow-xs' : 'bg-surface-container-low text-on-surface-variant hover:text-primary hover:bg-surface-container border border-outline-variant/40' ?>" href="<?= base_url('customer/addresses') ?>">
            <span class="material-symbols-outlined text-[16px] <?= $activeNav === 'addresses' ? 'fill-icon text-white' : '' ?>">location_on</span>
            <span>Addresses</span>
        </a>
        <a class="px-3.5 py-1.5 whitespace-nowrap text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all <?= $activeNav === 'favorites' ? 'bg-primary text-white shadow-xs' : 'bg-surface-container-low text-on-surface-variant hover:text-primary hover:bg-surface-container border border-outline-variant/40' ?>" href="<?= base_url('customer/favorites') ?>">
            <span class="material-symbols-outlined text-[16px] <?= $activeNav === 'favorites' ? 'fill-icon text-white' : '' ?>">favorite</span>
            <span>Favorites</span>
        </a>
    </nav>
</div>

<!-- Desktop Elevated Sidebar (Self-Start Sticky & Independent Scroll) -->
<aside class="w-64 lg:w-72 flex-shrink-0 hidden md:flex flex-col justify-between self-start sticky top-[72px] h-[calc(100vh_-_72px)] bg-surface-container-lowest border-r border-outline-variant/30 p-5 lg:p-6 shadow-2xs z-20 overflow-y-auto">

    <div class="flex flex-col gap-6">

        <!-- User Mini Profile Banner -->
        <div class="flex items-center gap-3 p-3 rounded-2xl bg-surface-container-low border border-outline-variant/30">
            <div class="w-11 h-11 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-base overflow-hidden border border-primary/20 shrink-0">
                <?php if (!empty($userImage)): ?>
                    <img src="<?= esc(profile_image_url($userImage, 'thumbnail')) ?>" alt="<?= esc($userName) ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <span><?= esc($userInitial) ?></span>
                <?php endif; ?>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-on-surface truncate"><?= esc($userName) ?></p>
                <p class="text-[11px] text-on-surface-variant truncate"><?= esc($userEmail) ?></p>
            </div>
        </div>

        <!-- Navigation Groups -->
        <nav class="flex flex-col gap-5">

            <!-- Section 1: Account -->
            <div>
                <span class="px-2 text-[10px] font-extrabold uppercase tracking-wider text-outline block mb-2">
                    Account &amp; Details
                </span>
                <div class="flex flex-col gap-1">
                    <a href="<?= base_url('customer/profile') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 <?= $activeNav === 'overview' ? 'bg-primary text-white shadow-xs' : 'text-on-surface-variant hover:text-primary hover:bg-surface-container-low' ?>">
                        <span class="material-symbols-outlined text-[18px] <?= $activeNav === 'overview' ? 'fill-icon text-white' : '' ?>">account_circle</span>
                        <span>Profile Overview</span>
                    </a>

                    <a href="<?= base_url('customer/addresses') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 <?= $activeNav === 'addresses' ? 'bg-primary text-white shadow-xs' : 'text-on-surface-variant hover:text-primary hover:bg-surface-container-low' ?>">
                        <span class="material-symbols-outlined text-[18px] <?= $activeNav === 'addresses' ? 'fill-icon text-white' : '' ?>">location_on</span>
                        <span>Shipping Addresses</span>
                    </a>
                </div>
            </div>

            <!-- Section 2: Orders & Services -->
            <div>
                <span class="px-2 text-[10px] font-extrabold uppercase tracking-wider text-outline block mb-2">
                    Purchases &amp; Services
                </span>
                <div class="flex flex-col gap-1">
                    <a href="<?= base_url('customer/orders') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 <?= $activeNav === 'orders' ? 'bg-primary text-white shadow-xs' : 'text-on-surface-variant hover:text-primary hover:bg-surface-container-low' ?>">
                        <span class="material-symbols-outlined text-[18px] <?= $activeNav === 'orders' ? 'fill-icon text-white' : '' ?>">shopping_bag</span>
                        <span>My Orders</span>
                    </a>

                    <a href="<?= base_url('customer/printing') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 <?= $activeNav === 'printing' ? 'bg-primary text-white shadow-xs' : 'text-on-surface-variant hover:text-primary hover:bg-surface-container-low' ?>">
                        <span class="material-symbols-outlined text-[18px] <?= $activeNav === 'printing' ? 'fill-icon text-white' : '' ?>">print</span>
                        <span>Printing Requests</span>
                    </a>
                </div>
            </div>

            <!-- Section 3: Saved -->
            <div>
                <span class="px-2 text-[10px] font-extrabold uppercase tracking-wider text-outline block mb-2">
                    Bookmarks
                </span>
                <div class="flex flex-col gap-1">
                    <a href="<?= base_url('customer/favorites') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 <?= $activeNav === 'favorites' ? 'bg-primary text-white shadow-xs' : 'text-on-surface-variant hover:text-primary hover:bg-surface-container-low' ?>">
                        <span class="material-symbols-outlined text-[18px] <?= $activeNav === 'favorites' ? 'fill-icon text-white' : '' ?>">favorite</span>
                        <span>Favorite Shops</span>
                    </a>
                </div>
            </div>

        </nav>

    </div>

    <!-- Bottom Shortcut: Return to Marketplace -->
    <div class="pt-4 border-t border-outline-variant/30">
        <a href="<?= base_url('/') ?>" class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-on-surface-variant hover:text-primary hover:bg-surface-container-low transition-colors group">
            <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">storefront</span>
                <span>Back to Marketplace</span>
            </span>
            <span class="material-symbols-outlined text-[16px] transform group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
        </a>
    </div>

</aside>