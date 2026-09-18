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

    $seg = service('request')->getUri()->getSegment(2) ?? 'dashboard';
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

        <!-- SideNavBar -->
        <aside id="admin-sidebar" class="bg-surface-container-lowest hidden md:flex flex-col h-full py-md px-base z-40 border-r border-outline-variant/20 shadow-md w-64 fixed left-0 h-screen transition-transform duration-300">

            <div class="px-sm mb-xl flex justify-between items-start">
                <div>
                    <h1 class="text-headline-md font-bold text-primary">Blax Console</h1>
                    <p class="text-label-sm text-on-surface-variant/60">Platform Master Console</p>
                </div>
                <button id="admin-sidebar-close" type="button" class="md:hidden text-on-surface-variant p-1 -mr-2">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <nav class="flex-1 space-y-1">

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'dashboard' ? 'bg-secondary-container text-on-secondary-container font-semibold translate-x-1 transition-transform' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('admin/dashboard') ?>">

                    <span class="material-symbols-outlined">dashboard</span>
                    <span class="text-label-sm">Dashboard</span>

                </a>

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'tenants' ? 'bg-secondary-container text-on-secondary-container font-semibold translate-x-1 transition-transform' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('admin/tenants') ?>">

                    <span class="material-symbols-outlined">storefront</span>
                    <span class="text-label-sm">Tenants</span>

                </a>

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'customers' ? 'bg-secondary-container text-on-secondary-container font-semibold translate-x-1 transition-transform' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('admin/customers') ?>">

                    <span class="material-symbols-outlined">person</span>
                    <span class="text-label-sm">Customers</span>

                </a>

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'analytics' ? 'bg-secondary-container text-on-secondary-container font-semibold translate-x-1 transition-transform' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('admin/analytics') ?>">

                    <span class="material-symbols-outlined">analytics</span>
                    <span class="text-label-sm">Analytics</span>

                </a>

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'payments' ? 'bg-secondary-container text-on-secondary-container font-semibold translate-x-1 transition-transform' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('admin/payments') ?>">

                    <span class="material-symbols-outlined">payments</span>
                    <span class="text-label-sm">Payment requests</span>

                </a>

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'compliance' ? 'bg-secondary-container text-on-secondary-container font-semibold translate-x-1 transition-transform' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('admin/compliance') ?>">

                    <span class="material-symbols-outlined">assignment_turned_in</span>
                    <span class="text-label-sm">Compliance</span>

                </a>

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'tracking' ? 'bg-secondary-container text-on-secondary-container font-semibold translate-x-1 transition-transform' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('admin/tracking') ?>">

                    <span class="material-symbols-outlined">distance</span>
                    <span class="text-label-sm">Live Tracking</span>

                </a>

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'audit' ? 'bg-secondary-container text-on-secondary-container font-semibold translate-x-1 transition-transform' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('admin/audit-log') ?>">

                    <span class="material-symbols-outlined">history</span>
                    <span class="text-label-sm">Audit log</span>

                </a>

                <a class="flex items-center gap-md px-md py-sm rounded-lg <?= $activeNav === 'content' ? 'bg-secondary-container text-on-secondary-container font-semibold translate-x-1 transition-transform' : 'text-on-surface-variant hover:bg-surface-container-high transition-all' ?>" href="<?= base_url('admin/content') ?>">

                    <span class="material-symbols-outlined">article</span>
                    <span class="text-label-sm">Content Management</span>

                </a>

            </nav>

            <div class="mt-auto pt-xl space-y-1 border-t border-outline-variant/10">

                <div class="flex items-center gap-md px-md py-md mb-md">

                    <div class="w-10 h-10 rounded-full overflow-hidden bg-primary-container flex items-center justify-center text-white font-bold">

                        <?= esc($adminInitials) ?>

                    </div>

                    <div>

                        <p class="text-label-sm text-on-surface font-bold"><?= esc($adminName) ?></p>
                        <p class="text-[10px] text-on-surface-variant"><?= esc($adminEmail) ?></p>

                    </div>

                </div>



                <a class="flex items-center gap-md px-md py-sm text-error hover:bg-error-container/10 rounded-lg transition-all" href="<?= base_url('logout') ?>">

                    <span class="material-symbols-outlined">logout</span>
                    <span class="text-label-sm">Sign Out</span>

                </a>

            </div>

        </aside>

        <!-- Main Content Area -->
        <main id="main-content" class="flex-1 ml-0 md:ml-64 overflow-y-auto bg-surface relative">

            <header class="sticky top-0 z-30 bg-surface/80 backdrop-blur-xl border-b border-outline-variant/30 px-4 md:px-8 lg:px-10 py-md flex justify-between items-center">

                <div class="flex items-center gap-md">
                    <button id="admin-sidebar-toggle" type="button" class="md:hidden text-on-surface-variant p-1 -ml-2 rounded-full hover:bg-surface-container">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div>
                        <h2 class="text-title-lg font-title-lg text-on-surface"><?= esc($pageTitle) ?></h2>

                    <?php if (!empty($pageSubtitle)): ?>

                        <p class="text-label-sm font-label-sm text-on-surface-variant"><?= esc($pageSubtitle) ?></p>

                    <?php endif; ?>

                    </div>
                </div>

                <div class="flex items-center gap-md">

                    <div class="relative">
                        <button id="admin-notif-toggle" type="button" onclick="document.getElementById('admin-notif-panel').classList.toggle('hidden')" class="text-on-surface-variant p-2 hover:bg-surface-container rounded-full relative" aria-label="Notifications">
                            <span class="material-symbols-outlined">notifications</span>
                            <?php if ($adminUnreadCount > 0): ?>
                                <span class="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 bg-error text-on-error text-[10px] font-bold rounded-full flex items-center justify-center"><?= (int) $adminUnreadCount ?></span>
                            <?php endif; ?>
                        </button>

                        <div id="admin-notif-panel" class="hidden absolute right-0 top-full mt-2 w-80 max-w-[calc(100vw_-_2rem)] bg-surface-container-lowest rounded-xl shadow-xl border border-outline-variant/30 z-50 overflow-hidden">
                            <div class="px-md py-sm border-b border-outline-variant/20 flex items-center justify-between">
                                <div class="flex items-center gap-xs">
                                    <span class="text-label-sm font-semibold text-on-surface">Admin Alerts</span>
                                    <span class="text-[10px] text-primary font-semibold"><?= (int) $adminUnreadCount ?> unread</span>
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
                                        <a href="<?= base_url('notifications/click/' . (int)$n['id']) ?>" class="flex items-start gap-sm p-md hover:bg-surface-container-low transition-colors block <?= empty($n['is_read']) ? 'bg-primary/5' : 'opacity-70 hover:opacity-100' ?>">
                                            <span class="material-symbols-outlined text-[18px] text-primary mt-0.5 shrink-0">
                                                <?= match($n['type'] ?? '') {
                                                    'merchant_verification' => 'storefront',
                                                    'customer_registration' => 'person_add',
                                                    'compliance'            => 'flag',
                                                    default                 => 'notifications',
                                                } ?>
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center justify-between gap-xs">
                                                    <p class="font-semibold text-on-surface truncate"><?= esc($n['title']) ?></p>
                                                    <span class="text-[9px] text-outline font-normal shrink-0"><?= date('M d, H:i', strtotime($n['created_at'])) ?></span>
                                                </div>
                                                <p class="text-[11px] text-on-surface-variant line-clamp-2 mt-0.5"><?= esc($n['message']) ?></p>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="px-md py-lg text-center text-label-sm text-on-surface-variant">No alerts yet.</p>
                                <?php endif; ?>
                            </div>
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
</body></html>