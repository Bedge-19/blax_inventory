<?php
    $activeNav = $activeNav ?? '';
    $cartCount = $cartCount ?? 0;
    $isLogged  = session()->get('isLoggedIn');
    $userName  = session()->get('user_name') ?? 'User';
    $userEmail = session()->get('user_email') ?? '';
    $profileImage = session()->get('profile_image_url') ?? '';
    $avatarChar = strtoupper(substr($userName, 0, 1));
?>

<header class="bg-surface-container-lowest shadow-sm sticky top-0 z-40 w-full border-b border-outline-variant/30">
    <div class="flex justify-between items-center w-full min-h-[60px] sm:min-h-[72px] py-2 px-4 sm:px-6 md:px-8 lg:px-10">

    <div class="flex items-center gap-2 sm:gap-4 md:gap-6 lg:gap-xl">

        <a href="<?= base_url('/') ?>" class="text-base sm:text-title-lg font-bold text-primary tracking-tight">MarketPlace</a>

        <form id="ai-search-form" action="<?= base_url('search') ?>" method="GET" class="hidden md:flex items-center relative">

            <span id="ai-search-icon" class="material-symbols-outlined absolute left-3 text-outline" data-icon="search">search</span>
            <input id="ai-search-input" name="q" value="<?= esc($searchQuery ?? '') ?>" maxlength="200" autocomplete="off" class="bg-surface-container-low border-none rounded-lg pl-10 pr-4 py-2 w-64 text-label-sm focus:ring-2 focus:ring-primary" placeholder="Describe what you need..." title="Describe a product in English, Bisaya or Tagalog - e.g. &quot;stick pang sulat nga blue ang tubig&quot;" type="text">

        </form>

        <script>
        (function () {
            // Loading state for the natural-language search. The form still
            // performs a normal GET submit; this only gives feedback while the
            // server resolves the AI relevance ranking.
            var f = document.getElementById('ai-search-form');
            if (!f) return;
            var input = document.getElementById('ai-search-input');
            var icon  = document.getElementById('ai-search-icon');
            f.addEventListener('submit', function () {
                if (!input || input.value.trim() === '') return;
                if (icon) {
                    icon.textContent = 'progress_activity';
                    icon.classList.add('animate-spin');
                }
                input.classList.add('opacity-60');
                input.setAttribute('readonly', 'readonly');

                var grid = document.getElementById('product-grid');
                if (grid) {
                    var skeletonCard = `
                        <div class="flex flex-col bg-white rounded-xl border border-outline-variant/20 overflow-hidden shadow-sm animate-pulse">
                            <div class="aspect-square bg-surface-container-high"></div>
                            <div class="p-md space-y-2">
                                <div class="h-3 w-1/3 bg-surface-container-high rounded"></div>
                                <div class="h-4 w-3/4 bg-surface-container-high rounded"></div>
                                <div class="h-3 w-1/4 bg-surface-container-high rounded"></div>
                                <div class="pt-2 flex justify-between items-center">
                                    <div class="h-5 w-1/3 bg-surface-container-high rounded"></div>
                                    <div class="h-8 w-8 bg-surface-container-high rounded-lg"></div>
                                </div>
                            </div>
                        </div>`;
                    grid.innerHTML = skeletonCard.repeat(10);
                }
            });
        })();
        </script>

    </div>

    <div class="flex items-center gap-md">

        <nav class="hidden lg:flex items-center gap-lg mr-md">

            <a class="<?= $activeNav === 'home' ? 'text-primary font-bold border-b-2 border-primary pb-1' : 'text-on-surface-variant hover:text-primary transition-colors' ?> font-body-md" href="<?= base_url('/') ?>">Home</a>

            <a class="<?= $activeNav === 'categories' ? 'text-primary font-bold border-b-2 border-primary pb-1' : 'text-on-surface-variant hover:text-primary transition-colors' ?> font-body-md" href="<?= base_url('categories') ?>">Categories</a>
            <a class="<?= $activeNav === 'printing' ? 'text-primary font-bold border-b-2 border-primary pb-1' : 'text-on-surface-variant hover:text-primary transition-colors' ?> font-body-md" href="<?= base_url('printing-services') ?>">Printing Services</a>
            <a class="<?= $activeNav === 'shops' ? 'text-primary font-bold border-b-2 border-primary pb-1' : 'text-on-surface-variant hover:text-primary transition-colors' ?> font-body-md" href="<?= base_url('shops') ?>">Shops</a>

        </nav>

        <div class="flex items-center gap-sm">

            <!-- Theme Toggle Button -->
            <button type="button" class="theme-toggle-btn p-2 rounded-full hover:bg-surface-container-high relative flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors" title="Toggle dark / light theme" aria-label="Toggle theme">
                <span class="material-symbols-outlined theme-toggle-icon text-[20px]">dark_mode</span>
            </button>

            <div class="relative block">
                <button id="notif-dropdown-toggle" type="button" class="p-2 rounded-full hover:bg-surface-container-high relative flex items-center justify-center" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
                    <span class="material-symbols-outlined text-on-surface-variant">notifications</span>
                    <?php if (!empty($unreadCount) && $unreadCount > 0): ?>
                        <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-error rounded-full border-2 border-surface-container-lowest animate-pulse"></span>
                    <?php endif; ?>
                </button>

                <div id="notif-dropdown" class="hidden fixed inset-x-3 top-16 sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-2 sm:w-80 w-auto max-w-[calc(100vw-1.5rem)] bg-surface-container-lowest rounded-2xl shadow-xl border border-outline-variant/30 py-2 z-50">
                    <div class="px-md py-xs border-b border-outline-variant/20 flex justify-between items-center">
                        <div class="flex items-center gap-xs">
                            <span class="font-bold text-xs text-on-surface">Notifications</span>
                            <span class="text-[10px] text-primary font-semibold"><?= (int)($unreadCount ?? 0) ?> unread</span>
                        </div>
                        <?php if (!empty($unreadCount) && $unreadCount > 0): ?>
                            <form method="post" action="<?= base_url('notifications/mark-all-read') ?>" class="inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-[10px] text-primary font-semibold hover:underline">Mark all read</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="max-h-64 overflow-y-auto divide-y divide-outline-variant/10 text-xs">
                        <?php if (!empty($recentNotifs)): ?>
                            <?php foreach ($recentNotifs as $n): ?>
                                <a href="<?= base_url('notifications/click/' . (int)$n['id']) ?>" class="block p-md hover:bg-surface-container-low transition-colors <?= empty($n['is_read']) ? 'bg-primary/5' : 'opacity-70 hover:opacity-100' ?>">
                                    <div class="font-semibold text-on-surface flex items-center justify-between gap-xs">
                                        <div class="flex items-center gap-xs truncate">
                                            <?php if (empty($n['is_read'])): ?>
                                                <span class="w-1.5 h-1.5 rounded-full bg-primary shrink-0"></span>
                                            <?php endif; ?>
                                            <span class="truncate"><?= esc($n['title']) ?></span>
                                        </div>
                                        <span class="text-[9px] text-outline font-normal shrink-0"><?= date('M d, H:i', strtotime($n['created_at'])) ?></span>
                                    </div>
                                    <p class="text-[11px] text-on-surface-variant mt-0.5 leading-snug"><?= esc($n['message']) ?></p>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-md text-center text-outline text-xs">No notifications yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <a href="<?= base_url('cart') ?>" class="p-2 rounded-full hover:bg-surface-container-high relative flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors" title="Cart" aria-label="Shopping Cart">
                <span class="material-symbols-outlined text-on-surface-variant">shopping_cart</span>
                <span id="cart-count-badge" class="absolute top-0 right-0 bg-primary text-white text-[10px] min-w-[16px] h-4 px-1 rounded-full flex items-center justify-center font-bold <?= ($cartCount > 0) ? '' : 'hidden' ?>"><?= $cartCount ?></span>
            </a>

            <?php if ($isLogged): ?>
                <a href="<?= base_url('customer/orders') ?>" class="p-2 rounded-full hover:bg-surface-container-high relative flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors" title="My Orders" aria-label="My Orders">
                    <span class="material-symbols-outlined">receipt_long</span>
                    <?php if (!empty($activeOrdersCount) && $activeOrdersCount > 0): ?>
                        <span id="active-orders-badge" class="absolute top-0 right-0 bg-secondary text-white text-[10px] min-w-[16px] h-4 px-1 rounded-full flex items-center justify-center font-bold" title="<?= (int)$activeOrdersCount ?> active order(s)"><?= (int)$activeOrdersCount ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>

        <button id="mobile-menu-toggle" type="button" class="lg:hidden p-2 text-on-surface-variant hover:bg-surface-container-high rounded-full" aria-label="Open menu">
            <span class="material-symbols-outlined">menu</span>
        </button>

        <div class="flex items-center gap-xs sm:gap-sm pl-2 sm:pl-md border-l border-outline-variant/30 relative">

            <?php if ($isLogged): ?>

                <div class="relative">
                    <button id="profile-dropdown-toggle" type="button" class="profile-dropdown-toggle w-9 h-9 sm:w-10 sm:h-10 rounded-full overflow-hidden bg-primary/10 border border-primary/20 flex items-center justify-center font-bold text-primary focus:ring-2 focus:ring-primary transition-all text-xs sm:text-base" aria-haspopup="true" aria-expanded="false" aria-label="Profile menu">

                        <?php if (!empty($profileImage)): ?>

                            <img class="w-full h-full object-cover" src="<?= esc(profile_image_url($profileImage)) ?>" alt="<?= esc($userName) ?> avatar">

                        <?php else: ?>

                            <?= $avatarChar ?>

                        <?php endif; ?>

                    </button>

                    <!-- Profile Dropdown -->
                    <div id="profile-dropdown" class="dropdown-menu fixed inset-x-3 top-16 sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-2 sm:w-64 w-auto max-w-[calc(100vw-1.5rem)] bg-surface-container-lowest rounded-2xl sm:rounded-xl shadow-xl border border-outline-variant/30 py-2 z-50">

                    <div class="px-md py-sm border-b border-outline-variant/10 flex items-center gap-md">

                        <div class="w-10 h-10 rounded-full overflow-hidden bg-primary/10 border border-primary/20 flex items-center justify-center font-bold text-primary flex-shrink-0">

                            <?php if (!empty($profileImage)): ?>

                                <img class="w-full h-full object-cover" src="<?= esc(profile_image_url($profileImage)) ?>" alt="<?= esc($userName) ?> avatar">

                            <?php else: ?>

                                <?= $avatarChar ?>

                            <?php endif; ?>

                        </div>

                        <div class="min-w-0">

                            <p class="font-bold text-on-surface text-body-md truncate"><?= esc($userName) ?></p>
                            <p class="text-xs text-on-surface-variant opacity-80 truncate"><?= esc($userEmail) ?></p>

                        </div>

                    </div>

                    <a href="<?= base_url('customer/profile') ?>" class="flex items-center gap-md px-md py-sm text-body-md text-on-surface hover:bg-surface-container-low transition-colors">

                        <span class="material-symbols-outlined text-outline">person</span>
                        <span>Profile Account</span>

                    </a>

                    <a href="<?= base_url('customer/printing') ?>" class="flex items-center gap-md px-md py-sm text-body-md text-on-surface hover:bg-surface-container-low transition-colors">

                        <span class="material-symbols-outlined text-outline">print</span>
                        <span>Printing Request</span>

                    </a>

                    <a href="<?= base_url('customer/orders') ?>" class="flex items-center gap-md px-md py-sm text-body-md text-on-surface hover:bg-surface-container-low transition-colors">

                        <span class="material-symbols-outlined text-outline">shopping_bag</span>
                        <span>Purchase Product</span>

                    </a>

                    <button type="button" class="theme-toggle-btn w-full flex items-center justify-between px-md py-sm text-body-md text-on-surface hover:bg-surface-container-low transition-colors text-left">
                        <div class="flex items-center gap-md">
                            <span class="material-symbols-outlined theme-toggle-icon text-outline">dark_mode</span>
                            <span>Theme</span>
                        </div>
                        <span class="text-[11px] font-semibold text-primary px-2 py-0.5 rounded-md bg-primary/10 theme-mode-label">Toggle</span>
                    </button>

                    <div class="my-1 border-t border-outline-variant/20"></div>

                    <a href="<?= base_url('logout') ?>" class="flex items-center gap-md px-md py-sm text-body-md text-error hover:bg-error-container/20 transition-colors">

                        <span class="material-symbols-outlined text-error">logout</span>
                        <span class="font-medium text-error">Logout</span>

                    </a>

                </div>

            <?php else: ?>

                <a href="<?= base_url('login') ?>" class="bg-primary text-on-primary text-xs sm:text-button font-semibold sm:font-button px-3 py-1.5 sm:px-lg sm:py-md rounded-lg hover:bg-on-primary-fixed-variant transition-all shadow-xs sm:shadow-sm whitespace-nowrap">
                    Sign In
                </a>

            <?php endif; ?>

        </div>

    </div>

    </div>

</header>

<!-- Mobile Navigation Drawer -->
<div id="mobile-drawer-backdrop" class="drawer-backdrop"></div>
<div id="mobile-drawer" class="mobile-drawer flex flex-col justify-between">
    <div>
        <div class="flex justify-between items-center p-4 border-b border-outline-variant/30">
            <span class="text-lg font-bold text-primary flex items-center gap-1.5">
                <span class="material-symbols-outlined text-xl">storefront</span>
                MarketPlace
            </span>
            <button id="mobile-menu-close" type="button" class="p-2 text-on-surface-variant hover:bg-surface-container-high rounded-full" aria-label="Close menu">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <?php if (!$isLogged): ?>
            <!-- Guest Welcome Card -->
            <div class="p-4 bg-gradient-to-br from-primary/10 via-primary/5 to-surface-container-low border-b border-outline-variant/20">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-primary/15 text-primary flex items-center justify-center font-bold shrink-0">
                        <span class="material-symbols-outlined text-[22px]">person</span>
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold text-xs text-on-surface">Welcome, Guest!</p>
                        <p class="text-[11px] text-on-surface-variant truncate">Sign in for orders, printing & cart</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <a href="<?= base_url('login') ?>" class="bg-primary text-on-primary text-center font-semibold text-xs py-2 px-3 rounded-lg hover:bg-on-primary-fixed-variant transition-all shadow-xs flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">login</span>
                        Sign In
                    </a>
                    <a href="<?= base_url('signup') ?>" class="bg-surface-container-lowest text-primary border border-primary/30 text-center font-semibold text-xs py-2 px-3 rounded-lg hover:bg-primary/5 transition-all flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">person_add</span>
                        Sign Up
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="p-4 border-b border-outline-variant/20 md:hidden">
            <form action="<?= base_url('search') ?>" method="GET" class="flex items-center relative w-full">
                <span class="material-symbols-outlined absolute left-3 text-outline text-[18px]">search</span>
                <input name="q" value="<?= esc($searchQuery ?? '') ?>" maxlength="200" autocomplete="off" class="bg-surface-container-low border-none rounded-lg pl-9 pr-4 py-2 w-full text-xs focus:ring-2 focus:ring-primary" placeholder="Describe what you need..." type="text">
            </form>
        </div>

        <nav class="flex flex-col py-2 divide-y divide-outline-variant/10">
            <a class="<?= $activeNav === 'home' ? 'text-primary bg-primary/10 font-bold' : 'text-on-surface hover:bg-surface-container-low' ?> text-xs sm:text-body-md px-4 py-3 flex items-center gap-3 transition-colors" href="<?= base_url('/') ?>">
                <span class="material-symbols-outlined text-[18px] <?= $activeNav === 'home' ? 'text-primary' : 'text-outline' ?>">home</span>
                <span>Home</span>
            </a>
            <a class="<?= $activeNav === 'categories' ? 'text-primary bg-primary/10 font-bold' : 'text-on-surface hover:bg-surface-container-low' ?> text-xs sm:text-body-md px-4 py-3 flex items-center gap-3 transition-colors" href="<?= base_url('categories') ?>">
                <span class="material-symbols-outlined text-[18px] <?= $activeNav === 'categories' ? 'text-primary' : 'text-outline' ?>">category</span>
                <span>Categories</span>
            </a>
            <a class="<?= $activeNav === 'printing' ? 'text-primary bg-primary/10 font-bold' : 'text-on-surface hover:bg-surface-container-low' ?> text-xs sm:text-body-md px-4 py-3 flex items-center gap-3 transition-colors" href="<?= base_url('printing-services') ?>">
                <span class="material-symbols-outlined text-[18px] <?= $activeNav === 'printing' ? 'text-primary' : 'text-outline' ?>">print</span>
                <span>Printing Services</span>
                <span class="ml-auto text-[9px] font-bold bg-primary/15 text-primary px-1.5 py-0.5 rounded-full">Rush</span>
            </a>
            <a class="<?= $activeNav === 'shops' ? 'text-primary bg-primary/10 font-bold' : 'text-on-surface hover:bg-surface-container-low' ?> text-xs sm:text-body-md px-4 py-3 flex items-center gap-3 transition-colors" href="<?= base_url('shops') ?>">
                <span class="material-symbols-outlined text-[18px] <?= $activeNav === 'shops' ? 'text-primary' : 'text-outline' ?>">storefront</span>
                <span>Featured Shops</span>
            </a>
        </nav>
    </div>

    <!-- Theme Switcher in Mobile Drawer -->
    <div class="p-4 border-t border-outline-variant/20 bg-surface-container-low/30 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-primary text-[20px] theme-toggle-icon">dark_mode</span>
            <div>
                <p class="text-xs font-bold text-on-surface">Theme Mode</p>
                <p class="text-[10px] text-on-surface-variant">Switch light/dark appearance</p>
            </div>
        </div>
        <button type="button" class="theme-toggle-btn px-3 py-1.5 rounded-xl bg-surface-container-lowest hover:bg-surface-container-high border border-outline-variant/30 text-xs font-bold text-on-surface flex items-center gap-1.5 transition-all shadow-2xs">
            <span class="material-symbols-outlined theme-toggle-icon text-[16px] text-primary">dark_mode</span>
            <span class="theme-mode-label">Toggle</span>
        </button>
    </div>

    <!-- Merchant Portal Callout at Bottom of Drawer -->
    <div class="p-4 border-t border-outline-variant/20 bg-surface-container-low/50">
        <div class="flex items-center gap-2.5 mb-2">
            <span class="material-symbols-outlined text-primary text-[20px]">store</span>
            <span class="text-xs font-bold text-on-surface">Are you a merchant?</span>
        </div>
        <p class="text-[11px] text-on-surface-variant mb-2.5 leading-snug">Sell school supplies, merchandise, or offer printing services in Polomolok.</p>
        <a href="<?= base_url('signup/merchant') ?>" class="block w-full text-center text-xs font-semibold text-primary bg-surface-container-lowest border border-primary/30 py-2 px-3 rounded-lg hover:bg-primary hover:text-white transition-all">
            Register Shop / Merchant
        </a>
    </div>
</div>


