<?php
    $segMap = [
        'dashboard' => ['dashboard', 'Dashboard'],
        'inventory' => ['inventory', 'Inventory'],
        'printing' => ['printing', 'Printing Requests'],
        'printing-requests' => ['printing', 'Printing Requests'],
        'orders' => ['orders', 'Orders'],
        'analytics' => ['analytics', 'Analytics'],
        'deliveries' => ['delivery', 'Deliveries'],
        'withdrawals' => ['withdrawals', 'Transfer & Withdrawal'],
        'transfer' => ['withdrawals', 'Transfer & Withdrawal'],
        'archive' => ['archive', 'Archive'],
        'settings' => ['settings', 'Settings'],
        'pos' => ['pos', 'Point of Sale (POS)'],
    ];

    $uri = service('request')->getUri();
    $seg = ($uri->getTotalSegments() >= 2) ? $uri->getSegment(2) : 'dashboard';
    $resolved = $segMap[$seg] ?? ['', $seg];
    $activeNav = $activeNav ?? $resolved[0];
    $pageTitle = $title ?? $resolved[1];
    $shopName  = $shop['shop_name'] ?? 'Blax Admin';
    $fullscreenLayout = !empty($fullscreenLayout) && ($activeNav === 'pos');
?>

<!DOCTYPE html><html class="light" lang="en"><head>

    <?= view('components/head', ['title' => $shopName . ' | Blax Admin']) ?>

</head>

<body class="bg-surface text-on-surface">

    <div class="flex h-screen overflow-hidden">

        <div id="tenant-sidebar-overlay" class="sidebar-overlay"></div>

        <!-- SideNavBar -->
        <aside id="tenant-sidebar" class="bg-surface-container-lowest flex-col h-full py-md px-3 z-40 border-r border-outline-variant/30 shadow-sm w-64 fixed left-0 h-screen <?= $fullscreenLayout ? 'hidden' : 'hidden md:flex' ?> transition-transform duration-300">

            <!-- Brand Identity & Store Card -->
            <div class="px-2 mb-md flex items-center justify-between">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-primary via-blue-600 to-indigo-500 text-white flex items-center justify-center font-black shadow-md shadow-primary/20 shrink-0">
                        <span class="material-symbols-outlined text-[22px]">storefront</span>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span class="text-base font-bold text-on-surface tracking-tight">Blax</span>
                            <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20">Merchant</span>
                        </div>
                        <p class="text-[11px] text-on-surface-variant/70 truncate" title="<?= esc($shopName) ?>"><?= esc($shopName) ?></p>
                    </div>
                </div>
                <button id="tenant-sidebar-close" type="button" class="<?= $fullscreenLayout ? '' : 'md:hidden' ?> text-on-surface-variant hover:text-on-surface p-1.5 rounded-lg hover:bg-surface-container transition-colors cursor-pointer relative z-50" aria-label="Close navigation" onclick="const s=document.getElementById('tenant-sidebar');if(s){s.classList.remove('mobile-open','flex','z-50');s.classList.add('hidden');}const o=document.getElementById('tenant-sidebar-overlay');if(o){o.classList.remove('active');}document.body.classList.remove('mobile-nav-open');">
                    <span class="material-symbols-outlined text-[20px] pointer-events-none">close</span>
                </button>
            </div>

            <!-- Grouped Navigation (Polaris / Linear Style) -->
            <nav class="flex-1 overflow-y-auto custom-scrollbar space-y-4 pr-1">

                <!-- 1. OPERATIONS -->
                <div>
                    <p class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-widest px-3 mb-1.5">Operations</p>
                    <div class="space-y-0.5">
                        <a class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'dashboard' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('tenant/dashboard') ?>">
                            <?php if ($activeNav === 'dashboard'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'dashboard' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">dashboard</span>
                            <span class="truncate">Dashboard</span>
                        </a>

                        <a class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'pos' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('tenant/pos') ?>">
                            <?php if ($activeNav === 'pos'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'pos' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">point_of_sale</span>
                            <span class="truncate">Point of Sale (POS)</span>
                        </a>

                        <a class="group relative flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'orders' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('tenant/orders') ?>">
                            <?php if ($activeNav === 'orders'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'orders' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">shopping_bag</span>
                                <span class="truncate">Orders</span>
                            </div>
                        </a>

                        <a class="group relative flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'printing' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('tenant/printing') ?>">
                            <?php if ($activeNav === 'printing'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'printing' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">print</span>
                                <span class="truncate">Printing Requests</span>
                            </div>
                        </a>

                        <a class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'delivery' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('tenant/deliveries') ?>">
                            <?php if ($activeNav === 'delivery'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'delivery' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">local_shipping</span>
                            <span class="truncate">Delivery</span>
                        </a>
                    </div>
                </div>

                <!-- 2. CATALOG & STOCK -->
                <div>
                    <p class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-widest px-3 mb-1.5">Catalog &amp; Stock</p>
                    <div class="space-y-0.5">
                        <a class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'inventory' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('tenant/inventory') ?>">
                            <?php if ($activeNav === 'inventory'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'inventory' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">inventory_2</span>
                            <span class="truncate">Inventory</span>
                        </a>

                        <a class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'archive' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('tenant/archive') ?>">
                            <?php if ($activeNav === 'archive'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'archive' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">archive</span>
                            <span class="truncate">Archive</span>
                        </a>
                    </div>
                </div>

                <!-- 3. FINANCE & INSIGHTS -->
                <div>
                    <p class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-widest px-3 mb-1.5">Finance &amp; Insights</p>
                    <div class="space-y-0.5">
                        <a class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'analytics' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('tenant/analytics') ?>">
                            <?php if ($activeNav === 'analytics'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'analytics' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">leaderboard</span>
                            <span class="truncate">Analytics</span>
                        </a>

                        <a class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'withdrawals' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('tenant/withdrawals') ?>">
                            <?php if ($activeNav === 'withdrawals'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'withdrawals' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">payments</span>
                            <span class="truncate">Transfer &amp; Payouts</span>
                        </a>
                    </div>
                </div>

                <!-- 4. PREFERENCES -->
                <div>
                    <p class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-widest px-3 mb-1.5">Preferences</p>
                    <div class="space-y-0.5">
                        <a class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'settings' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('tenant/settings') ?>">
                            <?php if ($activeNav === 'settings'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'settings' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">settings</span>
                            <span class="truncate">Store Settings</span>
                        </a>
                    </div>
                </div>

            </nav>

            <!-- Bottom Merchant Profile Card -->
            <div class="mt-auto pt-3 border-t border-outline-variant/20 space-y-2">
                <a href="<?= base_url('tenant/settings') ?>" class="flex items-center gap-2.5 p-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high border border-outline-variant/30 transition-all group shadow-2xs">
                    <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs overflow-hidden shrink-0 border border-primary/20">
                        <?php if (!empty($shop['logo_url'])): ?>
                            <img src="<?= esc(logo_url($shop['logo_url'])) ?>" alt="<?= esc($shopName) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span><?= esc(strtoupper(substr($shopName, 0, 2))) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-on-surface truncate group-hover:text-primary transition-colors"><?= esc($shopName) ?></p>
                        <p class="text-[10px] text-on-surface-variant/70 truncate flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Verified Merchant
                        </p>
                    </div>
                    <span class="material-symbols-outlined text-[16px] text-outline group-hover:text-primary transition-transform group-hover:translate-x-0.5">chevron_right</span>
                </a>

                <div class="flex items-center justify-between px-1">
                    <?php $storeSlug = $shop['slug'] ?? ''; ?>
                    <?php if (!empty($storeSlug)): ?>
                        <a href="<?= base_url('shop/' . esc($storeSlug)) ?>" target="_blank" rel="noopener noreferrer" class="text-[11px] font-semibold text-primary hover:underline flex items-center gap-1">
                            <span>Live Store</span>
                            <span class="material-symbols-outlined text-[13px]">open_in_new</span>
                        </a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                    <a class="flex items-center gap-1 text-[11px] font-semibold text-error hover:underline transition-colors" href="<?= base_url('logout') ?>">
                        <span class="material-symbols-outlined text-[15px]">logout</span>
                        <span>Sign Out</span>
                    </a>
                </div>
            </div>

        </aside>

        <!-- Main Content -->
        <main class="flex-1 <?= $fullscreenLayout ? 'ml-0' : 'ml-0 md:ml-64' ?> overflow-y-auto bg-surface relative">

            <header class="sticky top-0 z-30 bg-surface-container-lowest/80 backdrop-blur-xl border-b border-outline-variant/30 px-4 md:px-8 py-2.5 flex justify-between items-center transition-all">

                <!-- Left: Mobile Toggle & Breadcrumbs -->
                <div class="flex items-center gap-3">
                    <button id="sidebar-toggle" type="button" class="<?= $fullscreenLayout ? '' : 'md:hidden' ?> p-2 text-on-surface-variant hover:bg-surface-container rounded-xl transition-colors" aria-label="Toggle sidebar">
                        <span class="material-symbols-outlined text-[20px]">menu</span>
                    </button>

                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1.5 text-xs text-on-surface-variant/70">
                            <span class="font-medium hidden sm:inline">Store</span>
                            <span class="text-[10px] text-outline-variant hidden sm:inline">/</span>
                            <span class="font-bold text-on-surface text-sm sm:text-base tracking-tight"><?= esc($pageTitle) ?></span>
                        </div>
                        <span class="hidden lg:inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 ml-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Live
                        </span>
                    </div>
                </div>

                <!-- Right: Quick Preview, Notification Bell & Profile -->
                <div class="flex items-center gap-2.5 sm:gap-3.5">
                    <?php $storeSlug = $shop['slug'] ?? ''; ?>
                    <?php if (!empty($storeSlug)): ?>
                        <a href="<?= base_url('shop/' . esc($storeSlug)) ?>" target="_blank" rel="noopener noreferrer" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-outline-variant/30 bg-surface-container-lowest hover:bg-surface-container text-xs font-semibold text-on-surface transition-all shadow-2xs group" title="Preview public storefront in new tab">
                            <span class="material-symbols-outlined text-[15px] text-primary group-hover:scale-110 transition-transform">open_in_new</span>
                            <span>View Store</span>
                        </a>
                    <?php endif; ?>

                    <!-- Theme Switcher -->
                    <button type="button" class="theme-toggle-btn text-on-surface-variant hover:text-on-surface p-2 hover:bg-surface-container rounded-xl relative transition-colors" title="Toggle dark / light theme" aria-label="Toggle theme">
                        <span class="material-symbols-outlined theme-toggle-icon text-[22px]">dark_mode</span>
                    </button>

                    <!-- Notification Bell -->
                    <div class="relative">
                        <button id="notif-toggle" type="button" class="text-on-surface-variant hover:text-on-surface p-2 hover:bg-surface-container rounded-xl relative transition-colors" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
                            <span class="material-symbols-outlined text-[22px]">notifications</span>
                            <?php if (($unread_count ?? 0) > 0): ?>
                                <span class="absolute top-1.5 right-1.5 min-w-[17px] h-[17px] px-1 bg-error text-on-error text-[9px] font-bold rounded-full flex items-center justify-center border-2 border-surface-container-lowest animate-pulse"><?= (int) $unread_count ?></span>
                            <?php endif; ?>
                        </button>

                        <div id="notif-panel" class="hidden absolute right-0 top-full mt-2 w-80 max-w-[calc(100vw-2rem)] bg-surface-container-lowest rounded-2xl shadow-2xl border border-outline-variant/30 z-50 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                            <div class="px-md py-sm border-b border-outline-variant/20 flex items-center justify-between bg-surface-container-low/40">
                                <div class="flex items-center gap-1.5">
                                    <p class="text-xs font-bold text-on-surface">Notifications</p>
                                    <?php if (($unread_count ?? 0) > 0): ?>
                                        <span class="text-[10px] font-semibold text-primary px-1.5 py-0.5 rounded-md bg-primary/10"><?= (int) $unread_count ?> unread</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($notifications)): ?>
                                    <form method="post" action="<?= base_url('tenant/notifications/mark-read') ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="text-[11px] font-semibold text-primary hover:underline">Mark all read</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <div class="max-h-80 overflow-y-auto custom-scrollbar divide-y divide-outline-variant/10 text-xs">
                                <?php if (!empty($notifications)): ?>
                                    <?php
                                        $notifIcons = [
                                            'new_order'             => 'shopping_bag',
                                            'low_stock'             => 'inventory_2',
                                            'new_printing_request'  => 'print',
                                            'order_shipped'         => 'local_shipping',
                                            'printing_ready'        => 'print',
                                            'payout'                => 'payments',
                                        ];
                                    ?>
                                    <?php foreach (array_slice($notifications, 0, 6) as $n): ?>
                                        <a href="<?= base_url('notifications/click/' . (int)$n['id']) ?>" class="flex items-start gap-2.5 px-3 py-2.5 hover:bg-surface-container-low transition-colors block <?= empty($n['is_read']) ? 'bg-primary/5' : 'opacity-65 hover:opacity-100' ?>">
                                            <div class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0 mt-0.5">
                                                <span class="material-symbols-outlined text-[16px]"><?= esc($notifIcons[$n['type']] ?? 'notifications') ?></span>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center justify-between gap-1">
                                                    <p class="font-bold text-on-surface truncate text-xs"><?= esc($n['title']) ?></p>
                                                    <span class="text-[9px] text-on-surface-variant/60 shrink-0 font-normal"><?= date('M d, H:i', strtotime($n['created_at'])) ?></span>
                                                </div>
                                                <p class="text-[11px] text-on-surface-variant line-clamp-2 mt-0.5 leading-snug"><?= esc($n['message']) ?></p>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="px-md py-lg text-center text-xs text-on-surface-variant/70">
                                        <span class="material-symbols-outlined text-2xl text-outline mb-1 block">notifications_off</span>
                                        No new notifications.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Dropdown -->
                    <div class="relative inline-flex items-center">
                        <?php
                            $tenantAvatarUrl = '';
                            if (!empty($shop['logo_url'])) {
                                $tenantAvatarUrl = logo_url($shop['logo_url']);
                            } elseif (!empty(session()->get('profile_image_url'))) {
                                $tenantAvatarUrl = logo_url(session()->get('profile_image_url'));
                            }
                        ?>
                        <button id="tenant-profile-toggle" type="button" class="profile-dropdown-toggle flex items-center gap-2 p-1 rounded-xl hover:bg-surface-container transition-all group" aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
                            <div class="w-8 h-8 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center text-primary font-bold text-xs overflow-hidden shrink-0 group-hover:ring-2 group-hover:ring-primary/30 transition-all">
                                <?php if ($tenantAvatarUrl !== ''): ?>
                                    <img src="<?= esc($tenantAvatarUrl) ?>" class="w-full h-full object-cover" alt="<?= esc($shopName) ?>">
                                <?php else: ?>
                                    <span><?= esc(strtoupper(substr($shopName, 0, 2))) ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="material-symbols-outlined text-[16px] text-outline group-hover:text-on-surface transition-transform group-hover:translate-y-0.5 hidden sm:inline">expand_more</span>
                        </button>

                        <div id="tenant-profile-dropdown" class="dropdown-menu absolute right-0 top-full mt-2 min-w-[240px] w-max max-w-[calc(100vw-1.5rem)] bg-surface-container-lowest rounded-2xl shadow-2xl border border-outline-variant/30 p-2 z-50 origin-top-right">
                            <div class="px-3 py-2 border-b border-outline-variant/15 mb-1.5">
                                <p class="text-xs font-bold text-on-surface truncate"><?= esc($shopName) ?></p>
                                <p class="text-[10px] text-on-surface-variant truncate"><?= esc(session()->get('user_email') ?? 'Merchant') ?></p>
                            </div>
                            <a href="<?= base_url('tenant/settings') ?>" class="flex items-center gap-2.5 px-3 py-2 text-xs text-on-surface hover:bg-surface-container-low rounded-xl transition-colors <?= $activeNav === 'settings' ? 'bg-primary/10 text-primary font-bold' : '' ?>">
                                <span class="material-symbols-outlined text-[17px] text-primary">settings</span>
                                <span>Store Settings</span>
                            </a>
                            <a href="<?= base_url('tenant/withdrawals') ?>" class="flex items-center gap-2.5 px-3 py-2 text-xs text-on-surface hover:bg-surface-container-low rounded-xl transition-colors <?= $activeNav === 'withdrawals' ? 'bg-primary/10 text-primary font-bold' : '' ?>">
                                <span class="material-symbols-outlined text-[17px] text-emerald-600">payments</span>
                                <span>Transfer &amp; Payouts</span>
                            </a>
                            <?php if (!empty($storeSlug)): ?>
                                <a href="<?= base_url('shop/' . esc($storeSlug)) ?>" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2.5 px-3 py-2 text-xs text-on-surface hover:bg-surface-container-low rounded-xl transition-colors">
                                    <span class="material-symbols-outlined text-[17px] text-blue-500">storefront</span>
                                    <span>Public Storefront ↗</span>
                                </a>
                            <?php endif; ?>
                            <div class="my-1.5 border-t border-outline-variant/15"></div>
                            <a href="<?= base_url('logout') ?>" class="flex items-center gap-2.5 px-3 py-2 text-xs text-error hover:bg-error-container/20 rounded-xl transition-colors font-medium">
                                <span class="material-symbols-outlined text-[17px] text-error">logout</span>
                                <span>Log Out</span>
                            </a>
                        </div>
                    </div>
                </div>

            </header>

            <div class="<?= $fullscreenLayout ? 'p-2 sm:p-3 md:p-4 w-full' : 'p-4 md:p-gutter max-w-container-max mx-auto space-y-xl w-full' ?>">

                <?= $this->renderSection('content') ?>

            </div>

        </main>

    </div>

    <?= $this->renderSection('scripts') ?>



    <!-- Real-time Order & Printing Notification Toast Container -->
    <div id="blax-tenant-toast-container" class="fixed top-5 right-5 z-50 flex flex-col gap-2.5 pointer-events-none max-w-sm w-full"></div>

    <script>
    (function() {
        let lastOrderId = null;
        let lastPrintingId = null;
        let pollTimer = null;
        let isInitial = true;

        // Web Audio synthesized two-tone notification chime (zero file dependencies)
        function playChime() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                if (ctx.state === 'suspended') ctx.resume();

                const now = ctx.currentTime;
                // Note 1: F5 (698.46Hz)
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(698.46, now);
                gain1.gain.setValueAtTime(0.18, now);
                gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start(now);
                osc1.stop(now + 0.3);

                // Note 2: C6 (1046.50Hz) - higher bright ping
                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(1046.50, now + 0.12);
                gain2.gain.setValueAtTime(0.22, now + 0.12);
                gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.55);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(now + 0.12);
                osc2.stop(now + 0.55);
            } catch(e) {
                console.debug('Chime audio error:', e);
            }
        }

        function showRealtimeToast(type, data) {
            const container = document.getElementById('blax-tenant-toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = 'pointer-events-auto bg-surface-container-lowest border border-outline-variant/40 rounded-2xl p-4 shadow-xl flex items-start gap-3 transform translate-y-[-10px] opacity-0 transition-all duration-300';
            
            const isOrder = type === 'order';
            const icon = isOrder ? 'shopping_bag' : 'print';
            const iconBg = isOrder ? 'bg-primary/10 text-primary' : 'bg-purple-500/10 text-purple-600';
            const title = isOrder ? `New Order #${data.order_number}` : `New Print #${data.request_number}`;
            const sub = isOrder 
                ? `${data.customer_name} • ₱${data.total_amount_fmt} (${data.is_pickup ? 'Pick-up' : 'Delivery'})`
                : `${data.customer_name} • ${data.service_name} (₱${data.total_amount_fmt})`;
            const linkUrl = isOrder ? '<?= base_url('tenant/orders') ?>' : '<?= base_url('tenant/printing') ?>';

            toast.innerHTML = `
                <div class="w-10 h-10 rounded-xl ${iconBg} flex items-center justify-center shrink-0 shadow-2xs">
                    <span class="material-symbols-outlined text-[20px]">${icon}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-1">
                        <span class="text-xs font-bold text-on-surface truncate">${title}</span>
                        <span class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full">Just Now</span>
                    </div>
                    <p class="text-xs text-on-surface-variant mt-0.5 truncate">${sub}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <a href="${linkUrl}" class="text-[11px] font-bold text-primary hover:underline flex items-center gap-0.5">
                            <span>Open in Queue</span>
                            <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                        </a>
                    </div>
                </div>
                <button type="button" class="text-on-surface-variant/60 hover:text-on-surface p-1 -mr-1 text-xs" onclick="this.closest('div.pointer-events-auto').remove()">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </button>
            `;

            container.appendChild(toast);
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-[-10px]', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
            });

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-x-full');
                setTimeout(() => toast.remove(), 350);
            }, 7000);
        }

        function checkRealtime() {
            let url = '<?= base_url('tenant/realtime/check') ?>';
            const params = [];
            if (lastOrderId !== null) params.push('last_order_id=' + lastOrderId);
            if (lastPrintingId !== null) params.push('last_printing_id=' + lastPrintingId);
            if (params.length > 0) url += '?' + params.join('&');

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => {
                    if (!r.ok) return null;
                    return r.json();
                })
                .then(res => {
                    if (!res || !res.success) return;

                    if (isInitial) {
                        lastOrderId = res.max_order_id || 0;
                        lastPrintingId = res.max_printing_id || 0;
                        isInitial = false;
                        return;
                    }

                    if (res.max_order_id) lastOrderId = Math.max(lastOrderId || 0, res.max_order_id);
                    if (res.max_printing_id) lastPrintingId = Math.max(lastPrintingId || 0, res.max_printing_id);

                    // 1. Live Notification Count & List Sync
                    if (typeof res.unread_count === 'number') {
                        updateTenantNotifBadge(res.unread_count, res.notifications);
                    }

                    // 2. New orders
                    if (res.has_new_orders && Array.isArray(res.new_orders) && res.new_orders.length > 0) {
                        playChime();
                        res.new_orders.forEach(ord => {
                            showRealtimeToast('order', ord);
                            window.dispatchEvent(new CustomEvent('blax:new-order', { detail: ord }));
                        });
                        if (res.summary) {
                            window.dispatchEvent(new CustomEvent('blax:summary-update', { detail: res.summary }));
                        }
                    }

                    // 3. New printing requests
                    if (res.has_new_printing && Array.isArray(res.new_printing) && res.new_printing.length > 0) {
                        playChime();
                        res.new_printing.forEach(pr => {
                            showRealtimeToast('printing', pr);
                            window.dispatchEvent(new CustomEvent('blax:new-printing', { detail: pr }));
                        });
                    }
                })
                .catch(err => {
                    console.debug('Tenant realtime poll skipped:', err);
                });
        }

        function updateTenantNotifBadge(count, notifs) {
            const toggleBtn = document.getElementById('notif-toggle');
            if (!toggleBtn) return;

            let badge = toggleBtn.querySelector('span.bg-error');
            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'absolute top-1.5 right-1.5 min-w-[17px] h-[17px] px-1 bg-error text-on-error text-[9px] font-bold rounded-full flex items-center justify-center border-2 border-surface-container-lowest animate-pulse';
                    toggleBtn.appendChild(badge);
                }
                badge.textContent = count;
            } else if (badge) {
                badge.remove();
            }

            const headerUnread = document.querySelector('#notif-panel .bg-primary\\/10');
            if (headerUnread) {
                if (count > 0) {
                    headerUnread.textContent = count + ' unread';
                    headerUnread.classList.remove('hidden');
                } else {
                    headerUnread.classList.add('hidden');
                }
            }
        }

        // AJAX Mark All Read handler
        document.addEventListener('DOMContentLoaded', function() {
            const markAllForm = document.querySelector('#notif-panel form');
            if (markAllForm) {
                markAllForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    fetch(markAllForm.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.success) {
                            updateTenantNotifBadge(0);
                            document.querySelectorAll('#notif-panel a.bg-primary\\/5').forEach(el => {
                                el.classList.remove('bg-primary/5');
                                el.classList.add('opacity-65');
                            });
                            const btn = markAllForm.querySelector('button');
                            if (btn) btn.textContent = 'All read ✓';
                        }
                    })
                    .catch(() => {
                        markAllForm.submit(); // fallback to normal submit if fetch fails
                    });
                });
            }

            // Click listener on individual notification items for instant visual read feedback
            document.querySelectorAll('#notif-panel a').forEach(item => {
                item.addEventListener('click', function() {
                    this.classList.remove('bg-primary/5');
                    this.classList.add('opacity-65');
                    const badge = document.querySelector('#notif-toggle span.bg-error');
                    if (badge) {
                        const current = parseInt(badge.textContent || '0', 10);
                        if (current > 1) {
                            badge.textContent = current - 1;
                        } else {
                            badge.remove();
                        }
                    }
                });
            });
        });

        // Start polling loop every 4 seconds
        checkRealtime();
        pollTimer = setInterval(checkRealtime, 4000);

        // Universal showToast helper for tenant pages
        window.showToast = function(opts) {
            if (typeof opts === 'string') opts = { message: opts, type: 'info' };
            const type = opts.type || 'info';
            const title = opts.title || (type === 'success' ? 'Success' : (type === 'error' ? 'Notice' : 'Notification'));
            const message = opts.message || '';
            const url = opts.url || '';
            const icon = opts.icon || (
                type === 'success' ? 'check_circle' :
                type === 'error' ? 'error' :
                type === 'warning' ? 'warning' :
                type === 'order' ? 'shopping_bag' :
                type === 'delivery' ? 'two_wheeler' :
                type === 'printing' ? 'print' : 'notifications'
            );

            const container = document.getElementById('blax-tenant-toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = 'pointer-events-auto bg-surface-container-lowest/95 backdrop-blur-md border border-outline-variant/40 rounded-2xl p-3.5 shadow-xl flex items-start gap-3 transform translate-y-[-10px] opacity-0 transition-all duration-300';
            
            const colorMap = {
                success: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                error: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
                warning: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                order: 'bg-primary/10 text-primary border-primary/20',
                printing: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
                info: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'
            };
            const badgeStyle = colorMap[type] || colorMap.info;

            let actionHtml = '';
            if (url) {
                actionHtml = `
                    <a href="${url}" class="inline-flex items-center gap-1 text-[11px] font-bold text-primary hover:underline mt-1.5">
                        <span>View Details</span>
                        <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                    </a>
                `;
            }

            toast.innerHTML = `
                <div class="w-8 h-8 rounded-xl ${badgeStyle} border flex items-center justify-center shrink-0 shadow-2xs mt-0.5">
                    <span class="material-symbols-outlined text-[18px]">${icon}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-xs font-bold text-on-surface leading-tight">${title}</h4>
                    <p class="text-xs text-on-surface-variant mt-0.5 leading-snug break-words">${message}</p>
                    ${actionHtml}
                </div>
                <button type="button" class="text-on-surface-variant/60 hover:text-on-surface p-1 -mr-1 rounded-lg transition-colors" onclick="this.closest('div.pointer-events-auto').remove()">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </button>
            `;

            container.appendChild(toast);
            playChime();

            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-[-10px]', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
            });

            const duration = opts.duration || 6000;
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-x-full');
                setTimeout(() => toast.remove(), 350);
            }, duration);
        };

        // Flashdata auto-triggers
        <?php if (session()->getFlashdata('success')): ?>
            window.showToast({
                type: 'success',
                title: 'Action Completed',
                message: <?= json_encode((string)session()->getFlashdata('success')) ?>
            });
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            window.showToast({
                type: 'error',
                title: 'Notice',
                message: <?= json_encode((string)session()->getFlashdata('error')) ?>
            });
        <?php endif; ?>
    })();
    </script>

</body></html>