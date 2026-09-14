<?php $activeNav = $activeNav ?? 'overview'; ?>

<!-- Mobile Tabs -->
<div class="md:hidden w-full bg-surface-container-lowest border-b border-outline-variant/30 mb-4 sticky top-16 z-30">
    <nav class="mobile-profile-tabs">
        <a class="px-4 py-3 whitespace-nowrap text-label-sm font-semibold rounded-lg <?= $activeNav === 'overview' ? 'bg-secondary-container text-on-secondary-container' : 'text-on-surface-variant hover:bg-surface-container-high' ?>" href="<?= base_url('customer/profile') ?>">Overview</a>
        <a class="px-4 py-3 whitespace-nowrap text-label-sm font-semibold rounded-lg <?= $activeNav === 'orders' ? 'bg-secondary-container text-on-secondary-container' : 'text-on-surface-variant hover:bg-surface-container-high' ?>" href="<?= base_url('customer/orders') ?>">Orders</a>
        <a class="px-4 py-3 whitespace-nowrap text-label-sm font-semibold rounded-lg <?= $activeNav === 'printing' ? 'bg-secondary-container text-on-secondary-container' : 'text-on-surface-variant hover:bg-surface-container-high' ?>" href="<?= base_url('customer/printing') ?>">Printing</a>
        <a class="px-4 py-3 whitespace-nowrap text-label-sm font-semibold rounded-lg <?= $activeNav === 'addresses' ? 'bg-secondary-container text-on-secondary-container' : 'text-on-surface-variant hover:bg-surface-container-high' ?>" href="<?= base_url('customer/addresses') ?>">Addresses</a>
        <a class="px-4 py-3 whitespace-nowrap text-label-sm font-semibold rounded-lg <?= $activeNav === 'favorites' ? 'bg-secondary-container text-on-secondary-container' : 'text-on-surface-variant hover:bg-surface-container-high' ?>" href="<?= base_url('customer/favorites') ?>">Favorites</a>
    </nav>
</div>

<!-- Desktop Sidebar -->
<aside class="w-64 flex-shrink-0 hidden md:block sticky top-20 h-[calc(100vh-80px)] bg-surface-container-lowest border-r border-outline-variant/30 py-lg">

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