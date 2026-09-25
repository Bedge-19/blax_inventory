<?php
    $activeNav = $activeNav ?? 'overview';
    $userName  = session()->get('user_name') ?? 'Customer';
    $userEmail = session()->get('user_email') ?? '';
    $userImage = session()->get('profile_image_url') ?? '';
    $userInitial = strtoupper(substr($userName, 0, 1));
?>

<!-- Mobile Segmented Tabs (Horizontal Scroll Rail) -->
<div class="md:hidden w-full bg-white border-b border-slate-200/90 mb-4 sticky top-14 z-30 shadow-2xs">
    <nav class="mobile-profile-tabs px-3 py-2 flex items-center gap-2 overflow-x-auto no-scrollbar">
        <a class="px-3.5 py-1.5 whitespace-nowrap text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all <?= $activeNav === 'overview' ? 'bg-primary text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200/60' ?>" href="<?= base_url('customer/profile') ?>">
            <span class="material-symbols-outlined text-[16px] <?= $activeNav === 'overview' ? 'fill-icon' : '' ?>">account_circle</span>
            <span>Overview</span>
        </a>
        <a class="px-3.5 py-1.5 whitespace-nowrap text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all <?= $activeNav === 'orders' ? 'bg-primary text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200/60' ?>" href="<?= base_url('customer/orders') ?>">
            <span class="material-symbols-outlined text-[16px] <?= $activeNav === 'orders' ? 'fill-icon' : '' ?>">shopping_bag</span>
            <span>Orders</span>
        </a>
        <a class="px-3.5 py-1.5 whitespace-nowrap text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all <?= $activeNav === 'printing' ? 'bg-primary text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200/60' ?>" href="<?= base_url('customer/printing') ?>">
            <span class="material-symbols-outlined text-[16px] <?= $activeNav === 'printing' ? 'fill-icon' : '' ?>">print</span>
            <span>Printing</span>
        </a>
        <a class="px-3.5 py-1.5 whitespace-nowrap text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all <?= $activeNav === 'addresses' ? 'bg-primary text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200/60' ?>" href="<?= base_url('customer/addresses') ?>">
            <span class="material-symbols-outlined text-[16px] <?= $activeNav === 'addresses' ? 'fill-icon' : '' ?>">location_on</span>
            <span>Addresses</span>
        </a>
        <a class="px-3.5 py-1.5 whitespace-nowrap text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all <?= $activeNav === 'favorites' ? 'bg-primary text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200/60' ?>" href="<?= base_url('customer/favorites') ?>">
            <span class="material-symbols-outlined text-[16px] <?= $activeNav === 'favorites' ? 'fill-icon' : '' ?>">favorite</span>
            <span>Favorites</span>
        </a>
    </nav>
</div>

<!-- Desktop Elevated Sidebar -->
<aside class="w-64 lg:w-72 flex-shrink-0 hidden md:flex flex-col justify-between sticky top-[72px] h-[calc(100vh_-_72px)] bg-white border-r border-slate-200/90 p-5 lg:p-6 shadow-2xs z-20 overflow-y-auto">

    <div class="flex flex-col gap-6">

        <!-- Navigation Groups -->
        <nav class="flex flex-col gap-5">

            <!-- Section 1: Account -->
            <div>
                <span class="px-2 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-2">
                    Account & Details
                </span>
                <div class="flex flex-col gap-1">
                    <a href="<?= base_url('customer/profile') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 <?= $activeNav === 'overview' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-primary hover:bg-slate-50' ?>">
                        <span class="material-symbols-outlined text-[18px] <?= $activeNav === 'overview' ? 'fill-icon' : '' ?>">account_circle</span>
                        <span>Profile Overview</span>
                    </a>

                    <a href="<?= base_url('customer/addresses') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 <?= $activeNav === 'addresses' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-primary hover:bg-slate-50' ?>">
                        <span class="material-symbols-outlined text-[18px] <?= $activeNav === 'addresses' ? 'fill-icon' : '' ?>">location_on</span>
                        <span>Shipping Addresses</span>
                    </a>
                </div>
            </div>

            <!-- Section 2: Orders & Services -->
            <div>
                <span class="px-2 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-2">
                    Purchases & Services
                </span>
                <div class="flex flex-col gap-1">
                    <a href="<?= base_url('customer/orders') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 <?= $activeNav === 'orders' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-primary hover:bg-slate-50' ?>">
                        <span class="material-symbols-outlined text-[18px] <?= $activeNav === 'orders' ? 'fill-icon' : '' ?>">shopping_bag</span>
                        <span>My Orders</span>
                    </a>

                    <a href="<?= base_url('customer/printing') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 <?= $activeNav === 'printing' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-primary hover:bg-slate-50' ?>">
                        <span class="material-symbols-outlined text-[18px] <?= $activeNav === 'printing' ? 'fill-icon' : '' ?>">print</span>
                        <span>Printing Requests</span>
                    </a>
                </div>
            </div>

            <!-- Section 3: Saved -->
            <div>
                <span class="px-2 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-2">
                    Bookmarks
                </span>
                <div class="flex flex-col gap-1">
                    <a href="<?= base_url('customer/favorites') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 <?= $activeNav === 'favorites' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-primary hover:bg-slate-50' ?>">
                        <span class="material-symbols-outlined text-[18px] <?= $activeNav === 'favorites' ? 'fill-icon text-white' : '' ?>">favorite</span>
                        <span>Favorite Shops</span>
                    </a>
                </div>
            </div>

        </nav>

    </div>

    <!-- Bottom Shortcut: Return to Marketplace -->
    <div class="pt-4 border-t border-slate-100">
        <a href="<?= base_url('/') ?>" class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-primary hover:bg-slate-50 transition-colors group">
            <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">storefront</span>
                <span>Back to Marketplace</span>
            </span>
            <span class="material-symbols-outlined text-[16px] transform group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
        </a>
    </div>

</aside>