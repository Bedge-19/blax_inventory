<?php
    $segMap = [
        'dashboard' => ['dashboard', 'Platform Overview', 'Monitor overall marketplace health'],
        'tenants' => ['tenants', 'Tenants', 'Manage all registered merchant shops'],
        'customers' => ['customers', 'Customers', 'Manage registered platform users'],
        'analytics' => ['analytics', 'Analytics', 'Admin revenue & performance insights'],
        'payments' => ['payments', 'Payment Requests', 'GCash merchant payouts — 3% admin fee'],
        'compliance' => ['compliance', 'Compliance', 'Review and resolve compliance reports'],
        'tracking' => ['tracking', 'Live Tracking', 'Real-time oversight of shop deliveries across Polomolok'],
        'audit-log' => ['audit', 'Audit Log', 'Security and activity log'],
        'content' => ['content', 'Content Management', 'Manage customer-facing text and images'],
    ];

    try {
        $seg = service('request')->getUri()->getSegment(2) ?: 'dashboard';
    } catch (\Throwable $e) {
        $seg = 'dashboard';
    }
    $resolved = $segMap[$seg] ?? ['', $seg, ''];
    $activeNav = $activeNav ?? $resolved[0];
    $pageTitle = $title ?? $resolved[1];
    $pageSubtitle = $subtitle ?? $resolved[2];
    $adminName = session()->get('user_name') ?? 'Super Admin';
    $adminEmail = session()->get('user_email') ?? 'admin@blax.com';
    $adminInitials = strtoupper(substr($adminName, 0, 1) . (substr($adminName, strpos($adminName, ' ') + 1, 1) ?? ''));

    $adminUserId = (int) session()->get('user_id');
    $adminUnreadCount = 0;
    $adminNotifications = [];
    if ($adminUserId > 0) {
        $notifModel = new \App\Models\NotificationModel();
        $adminUnreadCount = $notifModel->getUnreadCount($adminUserId);
        $adminNotifications = $notifModel->getRecent($adminUserId, 6);
    }
?>

<!DOCTYPE html><html class="light" lang="en"><head>

    <?= view('components/head', ['title' => 'Blax | Platform Master Console']) ?>

</head>

<body class="bg-surface text-on-surface">

    <div class="flex h-screen overflow-hidden">

        <div id="admin-sidebar-overlay" class="sidebar-overlay"></div>

        <!-- SideNavBar (Polaris / Linear Design System) -->
        <aside id="admin-sidebar" class="bg-surface-container-lowest hidden md:flex flex-col h-full py-4 px-3 z-40 border-r border-outline-variant/30 shadow-xs w-64 fixed left-0 h-screen transition-transform duration-300">

            <!-- Brand Identity & Console Card -->
            <div class="px-2 mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-slate-900 via-indigo-950 to-primary text-white flex items-center justify-center font-black shadow-md shadow-slate-900/20 shrink-0 border border-slate-700/50">
                        <span class="material-symbols-outlined text-[22px]">admin_panel_settings</span>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span class="text-base font-bold text-on-surface tracking-tight">Blax</span>
                            <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-full bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 border border-slate-700/30">Console</span>
                        </div>
                        <p class="text-[11px] text-on-surface-variant/70 truncate">Master Platform Ops</p>
                    </div>
                </div>
                <button id="admin-sidebar-close" type="button" class="md:hidden text-on-surface-variant hover:text-on-surface p-1.5 rounded-lg hover:bg-surface-container transition-colors" aria-label="Close navigation">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <!-- Grouped Semantic Navigation -->
            <nav class="flex-1 overflow-y-auto custom-scrollbar space-y-4 pr-1">

                <!-- 1. CORE PLATFORM -->
                <div>
                    <p class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-widest px-3 mb-1.5">Core Platform</p>
                    <div class="space-y-0.5">
                        <a class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'dashboard' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('admin/dashboard') ?>">
                            <?php if ($activeNav === 'dashboard'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'dashboard' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">dashboard</span>
                            <span class="truncate">Overview</span>
                        </a>

                        <a class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'tenants' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('admin/tenants') ?>">
                            <?php if ($activeNav === 'tenants'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'tenants' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">storefront</span>
                            <span class="truncate">Tenants &amp; Merchants</span>
                        </a>

                        <a class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'customers' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('admin/customers') ?>">
                            <?php if ($activeNav === 'customers'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'customers' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">group</span>
                            <span class="truncate">Customers</span>
                        </a>
                    </div>
                </div>

                <!-- 2. FINANCE & RISK -->
                <div>
                    <p class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-widest px-3 mb-1.5">Finance &amp; Risk</p>
                    <div class="space-y-0.5">
                        <a class="group relative flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'payments' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('admin/payments') ?>">
                            <?php if ($activeNav === 'payments'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'payments' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">payments</span>
                                <span class="truncate">Payment Requests</span>
                            </div>
                        </a>

                        <a class="group relative flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'compliance' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('admin/compliance') ?>">
                            <?php if ($activeNav === 'compliance'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'compliance' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">policy</span>
                                <span class="truncate">Compliance &amp; Reports</span>
                            </div>
                        </a>

                        <a class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'audit' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('admin/audit-log') ?>">
                            <?php if ($activeNav === 'audit'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'audit' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">history_toggle_off</span>
                            <span class="truncate">Audit Log</span>
                        </a>
                    </div>
                </div>

                <!-- 3. INTELLIGENCE & OPERATIONS -->
                <div>
                    <p class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-widest px-3 mb-1.5">Intelligence &amp; Ops</p>
                    <div class="space-y-0.5">
                        <a class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'analytics' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('admin/analytics') ?>">
                            <?php if ($activeNav === 'analytics'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'analytics' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">insights</span>
                            <span class="truncate">Platform Analytics</span>
                        </a>

                        <a class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'tracking' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('admin/tracking') ?>">
                            <?php if ($activeNav === 'tracking'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'tracking' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">route</span>
                            <span class="truncate">Live Dispatch &amp; Tracking</span>
                        </a>

                        <a class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition-all <?= $activeNav === 'content' ? 'bg-primary/10 text-primary font-bold shadow-2xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/60 font-medium' ?>" href="<?= base_url('admin/content') ?>">
                            <?php if ($activeNav === 'content'): ?><span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-primary"></span><?php endif; ?>
                            <span class="material-symbols-outlined text-[19px] shrink-0 <?= $activeNav === 'content' ? 'fill-icon text-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors' ?>">web</span>
                            <span class="truncate">Content Management</span>
                        </a>
                    </div>
                </div>

            </nav>

            <!-- Bottom Super Admin Profile Card -->
            <div class="mt-auto pt-3 border-t border-outline-variant/20 space-y-2">
                <div class="flex items-center gap-2.5 p-2 rounded-xl bg-surface-container-low border border-outline-variant/30 shadow-2xs">
                    <div class="w-8 h-8 rounded-full bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 flex items-center justify-center font-black text-xs shrink-0 shadow-2xs">
                        <?= esc($adminInitials) ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-on-surface truncate"><?= esc($adminName) ?></p>
                        <p class="text-[10px] text-on-surface-variant/70 truncate flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Super Administrator
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between px-1">
                    <a href="<?= base_url('/') ?>" target="_blank" rel="noopener noreferrer" class="text-[11px] font-semibold text-primary hover:underline flex items-center gap-1">
                        <span>Public Marketplace</span>
                        <span class="material-symbols-outlined text-[13px]">open_in_new</span>
                    </a>
                    <a class="flex items-center gap-1 text-[11px] font-medium text-error hover:underline transition-all" href="<?= base_url('logout') ?>">
                        <span class="material-symbols-outlined text-[14px]">logout</span>
                        <span>Sign Out</span>
                    </a>
                </div>
            </div>

        </aside>

        <!-- Main Content Area -->
        <main id="main-content" class="flex-1 ml-0 md:ml-64 overflow-y-auto bg-surface relative">

            <!-- Top Header (Shopify Polaris / Linear Style) -->
            <header class="sticky top-0 z-30 bg-surface/85 backdrop-blur-xl border-b border-outline-variant/30 px-4 md:px-8 lg:px-10 py-3 flex justify-between items-center transition-all">

                <!-- Left: Mobile Trigger + Breadcrumb & Title -->
                <div class="flex items-center gap-3 min-w-0">
                    <button id="admin-sidebar-toggle" type="button" class="md:hidden text-on-surface-variant p-2 rounded-xl hover:bg-surface-container border border-outline-variant/30 transition-colors" aria-label="Open navigation">
                        <span class="material-symbols-outlined text-[20px]">menu</span>
                    </button>
                    
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5 text-[11px] text-on-surface-variant/70 font-medium">
                            <span class="hover:text-on-surface cursor-default">Console</span>
                            <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                            <span class="text-on-surface font-semibold truncate"><?= esc($pageTitle) ?></span>
                        </div>
                        <h2 class="text-lg md:text-xl font-bold text-on-surface tracking-tight truncate leading-tight"><?= esc($pageTitle) ?></h2>
                    </div>
                </div>

                <!-- Right: Platform Status, Notifications & Profile -->
                <div class="flex items-center gap-2 md:gap-3 shrink-0">

                    <!-- Live Telemetry Status Pill -->
                    <div class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-xs font-semibold">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-[11px]">System Nominal</span>
                    </div>

                    <!-- Notification Bell Dropdown -->
                    <div class="relative">
                        <button id="admin-notif-toggle" type="button" class="w-9 h-9 rounded-xl border border-outline-variant/30 hover:bg-surface-container flex items-center justify-center text-on-surface-variant relative transition-colors" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
                            <span class="material-symbols-outlined text-[20px]">notifications</span>
                            <?php if ($adminUnreadCount > 0): ?>
                                <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-error text-on-error text-[10px] font-bold rounded-full flex items-center justify-center shadow-xs animate-bounce"><?= (int) $adminUnreadCount ?></span>
                            <?php endif; ?>
                        </button>

                        <div id="admin-notif-panel" class="hidden absolute right-0 top-full mt-2 w-80 max-w-[calc(100vw_-_2rem)] bg-surface-container-lowest rounded-2xl shadow-xl border border-outline-variant/40 z-50 overflow-hidden">
                            <div class="px-4 py-3 border-b border-outline-variant/20 flex items-center justify-between bg-surface-container-low/50">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-on-surface">Platform Alerts</span>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-primary/10 text-primary"><?= (int) $adminUnreadCount ?> new</span>
                                </div>
                                <?php if ($adminUnreadCount > 0): ?>
                                    <form method="post" action="<?= base_url('notifications/mark-all-read') ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="text-[11px] font-semibold text-primary hover:underline">Mark all read</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div class="max-h-80 overflow-y-auto divide-y divide-outline-variant/10 text-xs">
                                <?php if (!empty($adminNotifications)): ?>
                                    <?php foreach ($adminNotifications as $n): ?>
                                        <a href="<?= base_url('notifications/click/' . (int)$n['id']) ?>" class="flex items-start gap-3 p-3.5 hover:bg-surface-container-low transition-colors block <?= empty($n['is_read']) ? 'bg-primary/5' : 'opacity-70 hover:opacity-100' ?>">
                                            <span class="material-symbols-outlined text-[18px] text-primary mt-0.5 shrink-0">
                                                <?= match($n['type'] ?? '') {
                                                    'merchant_verification' => 'storefront',
                                                    'customer_registration' => 'person_add',
                                                    'compliance'            => 'flag',
                                                    default                 => 'notifications',
                                                } ?>
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center justify-between gap-1">
                                                    <p class="font-semibold text-on-surface truncate"><?= esc($n['title']) ?></p>
                                                    <span class="text-[9px] text-outline font-normal shrink-0"><?= date('M d, H:i', strtotime($n['created_at'])) ?></span>
                                                </div>
                                                <p class="text-[11px] text-on-surface-variant line-clamp-2 mt-0.5"><?= esc($n['message']) ?></p>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-6 text-center">
                                        <span class="material-symbols-outlined text-outline text-3xl mb-1">notifications_off</span>
                                        <p class="text-xs text-on-surface-variant">No alerts at the moment.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Admin Dropdown -->
                    <div class="relative">
                        <button type="button" class="flex items-center gap-2 pl-1 pr-2 py-1 rounded-xl hover:bg-surface-container border border-outline-variant/30 transition-colors" onclick="document.getElementById('admin-profile-quickmenu').classList.toggle('hidden')">
                            <div class="w-7 h-7 rounded-lg bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 flex items-center justify-center font-bold text-xs shrink-0">
                                <?= esc($adminInitials) ?>
                            </div>
                            <span class="hidden md:inline text-xs font-semibold text-on-surface truncate max-w-[100px]"><?= esc($adminName) ?></span>
                            <span class="material-symbols-outlined text-[16px] text-on-surface-variant">arrow_drop_down</span>
                        </button>
                        <div id="admin-profile-quickmenu" class="hidden absolute right-0 top-full mt-2 w-48 bg-surface-container-lowest rounded-xl shadow-xl border border-outline-variant/40 py-1.5 z-50 text-xs">
                            <div class="px-3 py-2 border-b border-outline-variant/20 mb-1">
                                <p class="font-bold text-on-surface truncate"><?= esc($adminName) ?></p>
                                <p class="text-[10px] text-on-surface-variant truncate"><?= esc($adminEmail) ?></p>
                            </div>
                            <a href="<?= base_url('admin/audit-log') ?>" class="flex items-center gap-2 px-3 py-2 hover:bg-surface-container text-on-surface">
                                <span class="material-symbols-outlined text-[16px]">security</span>
                                <span>Security Logs</span>
                            </a>
                            <a href="<?= base_url('logout') ?>" class="flex items-center gap-2 px-3 py-2 hover:bg-error-container/20 text-error">
                                <span class="material-symbols-outlined text-[16px]">logout</span>
                                <span>Sign Out</span>
                            </a>
                        </div>
                    </div>

                </div>

            </header>

            <div class="p-4 md:p-8 lg:p-10 max-w-container-max mx-auto space-y-xl">

                <?= $this->renderSection('content') ?>

            </div>

        </main>

    </div>

    <?= $this->renderSection('scripts') ?>

    <script>
    (function() {
        var sidebar = document.getElementById('admin-sidebar');
        var toggle = document.getElementById('admin-sidebar-toggle');
        var close = document.getElementById('admin-sidebar-close');
        var overlay = document.getElementById('admin-sidebar-overlay');

        if (!sidebar || !toggle || !overlay) return;

        function openSidebar() {
            sidebar.classList.remove('hidden');
            sidebar.classList.add('flex', 'z-50');
            overlay.classList.add('active');
            document.body.classList.add('mobile-nav-open');
        }

        function closeSidebar() {
            sidebar.classList.add('hidden');
            sidebar.classList.remove('flex', 'z-50');
            overlay.classList.remove('active');
            document.body.classList.remove('mobile-nav-open');
        }

        toggle.addEventListener('click', openSidebar);
        if (close) close.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);
    })();
    </script>

    <!-- Admin Real-time Marketplace Alerts Container -->
    <div id="blax-admin-toast-container" class="fixed top-5 right-5 z-50 flex flex-col gap-2.5 pointer-events-none max-w-sm w-full"></div>

    <script>
    (function() {
        let lastOrderId = null;
        let lastPrintingId = null;
        let isInitial = true;

        function playAdminChime() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                if (ctx.state === 'suspended') ctx.resume();

                const now = ctx.currentTime;
                // Double chime note: D5 (587.33Hz) -> A5 (880Hz)
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(587.33, now);
                gain1.gain.setValueAtTime(0.16, now);
                gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start(now);
                osc1.stop(now + 0.3);

                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(880.00, now + 0.12);
                gain2.gain.setValueAtTime(0.2, now + 0.12);
                gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.55);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(now + 0.12);
                osc2.stop(now + 0.55);
            } catch(e) {
                console.debug('Admin chime error:', e);
            }
        }

        function showAdminToast(type, data) {
            const container = document.getElementById('blax-admin-toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = 'pointer-events-auto bg-surface-container-lowest border border-outline-variant/40 rounded-2xl p-4 shadow-xl flex items-start gap-3 transform translate-y-[-10px] opacity-0 transition-all duration-300';
            
            const isOrder = type === 'order';
            const icon = isOrder ? 'shopping_cart' : 'print';
            const iconBg = isOrder ? 'bg-primary/10 text-primary' : 'bg-purple-500/10 text-purple-600';
            const title = isOrder ? `Platform Order #${data.order_number}` : `Printing #${data.request_number}`;
            const sub = `${data.shop_name} • ${data.customer_name} (₱${data.total_amount_fmt})`;

            toast.innerHTML = `
                <div class="w-10 h-10 rounded-xl ${iconBg} flex items-center justify-center shrink-0 shadow-2xs">
                    <span class="material-symbols-outlined text-[20px]">${icon}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-1">
                        <span class="text-xs font-bold text-on-surface truncate">${title}</span>
                        <span class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full">Realtime</span>
                    </div>
                    <p class="text-xs text-on-surface-variant mt-0.5 truncate">${sub}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-[10px] text-outline font-medium uppercase tracking-wider">${data.fulfillment_method || 'Delivery'}</span>
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

        function checkAdminRealtime() {
            let url = '<?= base_url('admin/realtime/check') ?>';
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

                    if (typeof res.unread_count === 'number') {
                        updateAdminNotifBadge(res.unread_count);
                    }

                    if (res.has_new_orders && Array.isArray(res.new_orders) && res.new_orders.length > 0) {
                        playAdminChime();
                        res.new_orders.forEach(ord => {
                            showAdminToast('order', ord);
                            window.dispatchEvent(new CustomEvent('blax:admin-new-order', { detail: ord }));
                        });
                    }

                    if (res.has_new_printing && Array.isArray(res.new_printing) && res.new_printing.length > 0) {
                        playAdminChime();
                        res.new_printing.forEach(pr => {
                            showAdminToast('printing', pr);
                            window.dispatchEvent(new CustomEvent('blax:admin-new-printing', { detail: pr }));
                        });
                    }
                })
                .catch(err => {
                    console.debug('Admin realtime poll skipped:', err);
                });
        }

        function updateAdminNotifBadge(count) {
            const toggleBtn = document.getElementById('admin-notif-toggle');
            if (!toggleBtn) return;

            let badge = toggleBtn.querySelector('span.bg-error');
            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-error text-on-error text-[10px] font-bold rounded-full flex items-center justify-center shadow-xs animate-bounce';
                    toggleBtn.appendChild(badge);
                }
                badge.textContent = count;
            } else if (badge) {
                badge.remove();
            }

            const headerUnread = document.querySelector('#admin-notif-panel .bg-primary\\/10');
            if (headerUnread) {
                if (count > 0) {
                    headerUnread.textContent = count + ' new';
                    headerUnread.classList.remove('hidden');
                } else {
                    headerUnread.classList.add('hidden');
                }
            }
        }

        // AJAX Mark All Read handler for Admin
        document.addEventListener('DOMContentLoaded', function() {
            const markAllForm = document.querySelector('#admin-notif-panel form');
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
                            updateAdminNotifBadge(0);
                            document.querySelectorAll('#admin-notif-panel a.bg-primary\\/5').forEach(el => {
                                el.classList.remove('bg-primary/5');
                                el.classList.add('opacity-70');
                            });
                            const btn = markAllForm.querySelector('button');
                            if (btn) btn.textContent = 'All read ✓';
                        }
                    })
                    .catch(() => {
                        markAllForm.submit();
                    });
                });
            }

            document.querySelectorAll('#admin-notif-panel a').forEach(item => {
                item.addEventListener('click', function() {
                    this.classList.remove('bg-primary/5');
                    this.classList.add('opacity-70');
                    const badge = document.querySelector('#admin-notif-toggle span.bg-error');
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

        checkAdminRealtime();
        setInterval(checkAdminRealtime, 5000);

        // Universal showToast helper for admin pages
        window.showToast = function(opts) {
            if (typeof opts === 'string') opts = { message: opts, type: 'info' };
            const type = opts.type || 'info';
            const title = opts.title || (type === 'success' ? 'Success' : (type === 'error' ? 'Notice' : 'System Notification'));
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

            const container = document.getElementById('blax-admin-toast-container');
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
            playAdminChime();

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
                title: 'Admin Action Completed',
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