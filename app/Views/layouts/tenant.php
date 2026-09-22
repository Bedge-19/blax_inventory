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
        <aside id="tenant-sidebar" class="bg-surface-container-lowest flex-col h-full py-md px-base z-40 border-r border-outline-variant/20 shadow-md w-64 fixed left-0 h-screen <?= $fullscreenLayout ? 'hidden' : 'hidden md:flex' ?> transition-transform duration-300">

            <div class="px-sm mb-xl flex justify-between items-start">
                <div>
                    <h1 class="text-headline-md font-bold text-primary">Blax</h1>
                    <p class="text-label-sm text-on-surface-variant/60">Manage Storefront</p>
                </div>
                <button id="tenant-sidebar-close" type="button" class="<?= $fullscreenLayout ? '' : 'md:hidden' ?> text-on-surface-variant p-1 -mr-2">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <nav class="flex-1 space-y-1">

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'dashboard' ? 'bg-outline-variant/20 text-primary font-semibold' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('tenant/dashboard') ?>">

                    <span class="material-symbols-outlined <?= $activeNav === 'dashboard' ? 'fill-icon' : '' ?>">dashboard</span>
                    <span class="text-label-sm">Dashboard</span>

                </a>

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'inventory' ? 'bg-outline-variant/20 text-primary font-semibold' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('tenant/inventory') ?>">

                    <span class="material-symbols-outlined <?= $activeNav === 'inventory' ? 'fill-icon' : '' ?>">inventory_2</span>
                    <span class="text-label-sm">Inventory</span>

                </a>

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'printing' ? 'bg-outline-variant/20 text-primary font-semibold' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('tenant/printing') ?>">

                    <span class="material-symbols-outlined <?= $activeNav === 'printing' ? 'fill-icon' : '' ?>">print</span>
                    <span class="text-label-sm">Printing Requests</span>

                </a>

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'orders' ? 'bg-outline-variant/20 text-primary font-semibold' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('tenant/orders') ?>">

                    <span class="material-symbols-outlined <?= $activeNav === 'orders' ? 'fill-icon' : '' ?>">shopping_bag</span>
                    <span class="text-label-sm">Orders</span>

                </a>

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'pos' ? 'bg-outline-variant/20 text-primary font-semibold' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('tenant/pos') ?>">

                    <span class="material-symbols-outlined <?= $activeNav === 'pos' ? 'fill-icon' : '' ?>">point_of_sale</span>
                    <span class="text-label-sm">Point of Sale (POS)</span>

                </a>

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'analytics' ? 'bg-outline-variant/20 text-primary font-semibold' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('tenant/analytics') ?>">

                    <span class="material-symbols-outlined <?= $activeNav === 'analytics' ? 'fill-icon' : '' ?>">leaderboard</span>
                    <span class="text-label-sm">Analytics</span>

                </a>

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'delivery' ? 'bg-outline-variant/20 text-primary font-semibold' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('tenant/deliveries') ?>">

                    <span class="material-symbols-outlined <?= $activeNav === 'delivery' ? 'fill-icon' : '' ?>">local_shipping</span>
                    <span class="text-label-sm">Delivery</span>

                </a>

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'archive' ? 'bg-outline-variant/20 text-primary font-semibold' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('tenant/archive') ?>">

                    <span class="material-symbols-outlined <?= $activeNav === 'archive' ? 'fill-icon' : '' ?>">archive</span>
                    <span class="text-label-sm">Archive</span>

                </a>

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'withdrawals' ? 'bg-outline-variant/20 text-primary font-semibold' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('tenant/withdrawals') ?>">
                    <span class="material-symbols-outlined <?= $activeNav === 'withdrawals' ? 'fill-icon' : '' ?>">payments</span>
                    <span class="text-label-sm">Transfer & Withdrawal</span>
                </a>

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'settings' ? 'bg-outline-variant/20 text-primary font-semibold' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('tenant/settings') ?>">
                    <span class="material-symbols-outlined <?= $activeNav === 'settings' ? 'fill-icon' : '' ?>">settings</span>
                    <span class="text-label-sm">Settings</span>
                </a>

            </nav>

            <div class="mt-auto pt-md space-y-2 border-t border-outline-variant/10">
                <!-- Merchant Profile Widget -->
                <a href="<?= base_url('tenant/settings') ?>" class="flex items-center gap-3 p-2 rounded-xl bg-surface-container-low/50 hover:bg-surface-container-high border border-outline-variant/30 transition-all group shadow-2xs">
                    <div class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs overflow-hidden shrink-0 border border-primary/20">
                        <?php if (!empty($shop['logo_url'])): ?>
                            <img src="<?= base_url($shop['logo_url']) ?>" alt="<?= esc($shopName) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span><?= esc(strtoupper(substr($shopName, 0, 2))) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-on-surface truncate group-hover:text-primary transition-colors"><?= esc($shopName) ?></p>
                        <p class="text-[10px] text-on-surface-variant/80 truncate flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active Merchant
                        </p>
                    </div>
                    <span class="material-symbols-outlined text-[16px] text-outline group-hover:text-primary transition-transform group-hover:translate-x-0.5">chevron_right</span>
                </a>

                <a class="flex items-center gap-md px-md py-sm text-error hover:bg-error-container/10 rounded-lg transition-all" href="<?= base_url('logout') ?>">
                    <span class="material-symbols-outlined text-[18px]">logout</span>
                    <span class="text-label-sm font-semibold">Sign Out</span>
                </a>
            </div>

        </aside>

        <!-- Main Content -->
        <main class="flex-1 <?= $fullscreenLayout ? 'ml-0' : 'ml-0 md:ml-64' ?> overflow-y-auto bg-surface relative">

            <header class="sticky top-0 z-30 bg-surface-container-lowest backdrop-blur-xl border-b border-outline-variant/30 px-4 md:px-8 lg:px-10 py-md flex justify-between items-center">

                <div class="flex items-center gap-md">

                    <button id="sidebar-toggle" class="<?= $fullscreenLayout ? '' : 'md:hidden' ?> p-2 text-on-surface-variant hover:bg-surface-container rounded-full" aria-label="Toggle sidebar">

                        <span class="material-symbols-outlined">menu</span>

                    </button>

                    <div>

                        <h2 class="text-title-lg font-title-lg text-on-surface"><?= esc($pageTitle) ?></h2>

                    </div>

                </div>

                <div class="flex items-center gap-md">

                    <div class="relative">

                        <button id="notif-toggle" class="text-on-surface-variant p-2 hover:bg-surface-container rounded-full relative" aria-label="Notifications">

                            <span class="material-symbols-outlined">notifications</span>

                            <?php if (($unread_count ?? 0) > 0): ?>

                                <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 bg-error text-on-error text-[10px] font-bold rounded-full flex items-center justify-center"><?= (int) $unread_count ?></span>

                            <?php endif; ?>

                        </button>

                        <div id="notif-panel" class="hidden absolute right-0 top-full mt-2 w-80 max-w-[calc(100vw_-_2rem)] bg-surface-container-lowest rounded-xl shadow-lg border border-outline-variant/30 z-50 overflow-hidden">

                            <div class="px-md py-sm border-b border-outline-variant/20 flex items-center justify-between">

                                <p class="text-label-sm font-semibold text-on-surface">Notifications</p>

                                <?php if (!empty($notifications)): ?>

                                    <form method="post" action="<?= base_url('tenant/notifications/mark-read') ?>">

                                        <?= csrf_field() ?>

                                        <button type="submit" class="text-[11px] font-semibold text-primary hover:underline">Mark all as read</button>

                                    </form>

                                <?php endif; ?>

                            </div>

                            <div class="max-h-80 overflow-y-auto custom-scrollbar">

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

                                    <?php foreach (array_slice($notifications, 0, 5) as $n): ?>

                                        <a href="<?= base_url('notifications/click/' . (int)$n['id']) ?>" class="flex items-start gap-sm px-md py-sm hover:bg-surface-container-low transition-colors block <?= empty($n['is_read']) ? 'bg-primary/5' : 'opacity-60 hover:opacity-100' ?>">

                                            <span class="material-symbols-outlined text-[18px] text-primary mt-0.5 shrink-0"><?= esc($notifIcons[$n['type']] ?? 'notifications') ?></span>

                                            <div class="min-w-0 flex-1">

                                                <div class="flex items-center justify-between gap-xs">
                                                    <p class="text-label-sm font-semibold text-on-surface truncate"><?= esc($n['title']) ?></p>
                                                    <?php if (empty($n['is_read'])): ?>
                                                        <span class="w-1.5 h-1.5 rounded-full bg-primary shrink-0"></span>
                                                    <?php endif; ?>
                                                </div>

                                                <p class="text-[11px] text-on-surface-variant line-clamp-2"><?= esc($n['message']) ?></p>

                                                <p class="text-[10px] text-on-surface-variant/70 mt-0.5"><?= date('M d, H:i', strtotime($n['created_at'])) ?></p>

                                            </div>

                                        </a>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <p class="px-md py-lg text-center text-label-sm text-on-surface-variant">No notifications.</p>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                    <div class="flex items-center gap-sm relative">

                        <?php
                            $tenantAvatarUrl = '';
                            if (!empty($shop['logo_url'])) {
                                $tenantAvatarUrl = logo_url($shop['logo_url']);
                            } elseif (!empty(session()->get('profile_image_url'))) {
                                $tenantAvatarUrl = logo_url(session()->get('profile_image_url'));
                            }
                        ?>

                        <button id="tenant-profile-toggle" type="button" class="profile-dropdown-toggle w-8 h-8 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center text-primary focus:ring-2 focus:ring-primary transition-all overflow-hidden" aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
                            <?php if ($tenantAvatarUrl !== ''): ?>
                                <img src="<?= esc($tenantAvatarUrl) ?>" class="w-8 h-8 rounded-full object-cover" alt="<?= esc($shopName) ?> profile">
                            <?php else: ?>
                                <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode(substr($shopName, 0, 2)) ?>&backgroundColor=2563eb" class="w-8 h-8 rounded-full object-cover" alt="Profile">
                            <?php endif; ?>
                        </button>

                        <div id="tenant-profile-dropdown" class="dropdown-menu absolute right-0 top-full mt-2 w-48 bg-surface-container-lowest rounded-xl shadow-lg border border-outline-variant/30 py-2 z-50">

                            <a href="<?= base_url('tenant/settings') ?>" class="flex items-center gap-md px-md py-sm text-label-sm text-on-surface hover:bg-surface-container-low rounded-lg <?= $activeNav === 'settings' ? 'bg-primary-container/10 text-primary font-bold' : '' ?>">

                                <span class="material-symbols-outlined text-[18px]">settings</span>Settings

                            </a>

                            <div class="my-1 border-t border-outline-variant/10"></div>

                            <a href="<?= base_url('tenant/withdrawals') ?>" class="flex items-center gap-md px-md py-sm text-label-sm text-on-surface hover:bg-surface-container-low rounded-lg <?= $activeNav === 'withdrawals' ? 'bg-primary-container/10 text-primary font-bold' : '' ?>">

                                <span class="material-symbols-outlined text-[18px]">payments</span>Transfer

                            </a>

                            <div class="my-1 border-t border-outline-variant/10"></div>

                            <a href="<?= base_url('tenant/settings') ?>" class="flex items-center gap-md px-md py-sm text-label-sm text-on-surface hover:bg-surface-container-low rounded-lg">

                                <span class="material-symbols-outlined text-[18px]">person</span>Profile

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

    <script>
    (function () {
        var sb = document.getElementById('sidebar-toggle');
        var closeSb = document.getElementById('tenant-sidebar-close');
        var aside = document.getElementById('tenant-sidebar');
        var overlay = document.getElementById('tenant-sidebar-overlay');
        
        function openSidebar() {
            if (!aside) return;
            aside.classList.remove('hidden');
            aside.classList.add('flex', 'z-50');
            if (overlay) overlay.classList.add('active');
            document.body.classList.add('mobile-nav-open');
        }
        
        function closeSidebar() {
            if (!aside) return;
            aside.classList.add('hidden');
            aside.classList.remove('flex', 'z-50');
            if (overlay) overlay.classList.remove('active');
            document.body.classList.remove('mobile-nav-open');
        }
        
        if (sb) sb.addEventListener('click', openSidebar);
        if (closeSb) closeSb.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);

        var nt = document.getElementById('notif-toggle');
        var np = document.getElementById('notif-panel');
        if (nt && np) {
            nt.addEventListener('click', function (e) {
                e.stopPropagation();
                np.classList.toggle('hidden');
            });
            np.addEventListener('click', function (e) {
                e.stopPropagation();
            });
            document.addEventListener('click', function () {
                np.classList.add('hidden');
            });
        }

        // Tenant profile dropdown toggle
        var tdd = document.getElementById('tenant-profile-dropdown');
        var tdt = document.getElementById('tenant-profile-toggle');
        if (tdd && tdt) {
            var tdo = false;
            function tds(open) {
                tdo = open;
                if (open) tdd.classList.add('open'); else tdd.classList.remove('open');
                tdt.setAttribute('aria-expanded', open);
            }
            tdt.addEventListener('click', function (e) { e.stopPropagation(); tds(!tdo); });
            document.addEventListener('click', function () { tds(false); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') tds(false); });
        }
    })();
    </script>

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
                .then(r => r.json())
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

                    // 1. New orders
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

                    // 2. New printing requests
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