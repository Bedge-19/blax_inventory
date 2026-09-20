<?php $activeNav = $activeNav ?? 'overview'; ?>

<!-- Mobile Tabs -->
<div class="md:hidden w-full bg-surface-container-lowest border-b border-outline-variant/30 mb-4 sticky top-14 z-30 shadow-2xs">
    <nav class="mobile-profile-tabs px-2 py-1.5 flex items-center gap-1.5 overflow-x-auto no-scrollbar">
        <a class="px-3 py-1.5 whitespace-nowrap text-xs font-bold rounded-xl flex items-center gap-1 transition-all <?= $activeNav === 'overview' ? 'bg-primary text-on-primary shadow-xs' : 'text-on-surface-variant hover:bg-surface-container' ?>" href="<?= base_url('customer/profile') ?>">
            <span class="material-symbols-outlined text-[16px]">person</span>
            <span>Overview</span>
        </a>
        <a class="px-3 py-1.5 whitespace-nowrap text-xs font-bold rounded-xl flex items-center gap-1 transition-all <?= $activeNav === 'orders' ? 'bg-primary text-on-primary shadow-xs' : 'text-on-surface-variant hover:bg-surface-container' ?>" href="<?= base_url('customer/orders') ?>">
            <span class="material-symbols-outlined text-[16px]">shopping_bag</span>
            <span>Orders</span>
        </a>
        <a class="px-3 py-1.5 whitespace-nowrap text-xs font-bold rounded-xl flex items-center gap-1 transition-all <?= $activeNav === 'printing' ? 'bg-primary text-on-primary shadow-xs' : 'text-on-surface-variant hover:bg-surface-container' ?>" href="<?= base_url('customer/printing') ?>">
            <span class="material-symbols-outlined text-[16px]">print</span>
            <span>Printing</span>
        </a>
        <a class="px-3 py-1.5 whitespace-nowrap text-xs font-bold rounded-xl flex items-center gap-1 transition-all <?= $activeNav === 'addresses' ? 'bg-primary text-on-primary shadow-xs' : 'text-on-surface-variant hover:bg-surface-container' ?>" href="<?= base_url('customer/addresses') ?>">
            <span class="material-symbols-outlined text-[16px]">location_on</span>
            <span>Addresses</span>
        </a>
        <a class="px-3 py-1.5 whitespace-nowrap text-xs font-bold rounded-xl flex items-center gap-1 transition-all <?= $activeNav === 'favorites' ? 'bg-primary text-on-primary shadow-xs' : 'text-on-surface-variant hover:bg-surface-container' ?>" href="<?= base_url('customer/favorites') ?>">
            <span class="material-symbols-outlined text-[16px]">favorite</span>
            <span>Favorites</span>
        </a>
    </nav>
</div>

<!-- Desktop Sidebar -->
<aside class="w-64 flex-shrink-0 hidden md:block sticky top-20 h-[calc(100vh_-_80px)] bg-surface-container-lowest border-r border-outline-variant/30 py-lg">

    <div class="px-lg mb-lg">

        <h2 class="text-title-lg font-bold text-primary">Profile</h2>

    </div>

    <nav class="flex flex-col gap-1 px-sm">

        <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'overview' ? 'bg-secondary-container text-on-secondary-container font-semibold translate-x-1 transition-transform' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('customer/profile') ?>">

            <span class="material-symbols-outlined <?= $activeNav === 'overview' ? 'fill-icon' : '' ?>">person</span>
            <span class="text-label-sm">Profile Overview</span>

        </a>

        <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'orders' ? 'bg-secondary-container text-on-secondary-container font-semibold translate-x-1 transition-transform' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('customer/orders') ?>">

            <span class="material-symbols-outlined <?= $activeNav === 'orders' ? 'fill-icon' : '' ?>">shopping_bag</span>
            <span class="text-label-sm">My Orders</span>

        </a>

        <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'printing' ? 'bg-secondary-container text-on-secondary-container font-semibold translate-x-1 transition-transform' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('customer/printing') ?>">

            <span class="material-symbols-outlined <?= $activeNav === 'printing' ? 'fill-icon' : '' ?>">print</span>
            <span class="text-label-sm">Printing Requests</span>

        </a>

        <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'addresses' ? 'bg-secondary-container text-on-secondary-container font-semibold translate-x-1 transition-transform' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('customer/addresses') ?>">

            <span class="material-symbols-outlined <?= $activeNav === 'addresses' ? 'fill-icon' : '' ?>">location_on</span>
            <span class="text-label-sm">Shipping Addresses</span>

        </a>

        <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'favorites' ? 'bg-secondary-container text-on-secondary-container font-semibold translate-x-1 transition-transform' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('customer/favorites') ?>">

            <span class="material-symbols-outlined <?= $activeNav === 'favorites' ? 'fill-icon' : '' ?>">favorite</span>
            <span class="text-label-sm">Favorite Shops</span>

        </a>

    </nav>



</aside>