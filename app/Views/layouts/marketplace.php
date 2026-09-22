<?php
    $activeNav = $activeNav ?? '';
    if ($activeNav === '') {
        $seg = service('request')->getUri()->getSegment(1) ?? '';
        $navMap = ['home' => 'home', 'categories' => 'categories', 'category' => 'categories', 'shops' => 'shops', 'shop' => 'shops', 'printing-services' => 'printing', 'cart' => 'cart'];
        $activeNav = $navMap[$seg] ?? '';
        if ($activeNav === '' && $seg === '') {
            $activeNav = 'home';
        }
    }

    $isLogged = (bool) session()->get('isLoggedIn');
    $cartCount = 0;
    $unreadCount = 0;
    $activeOrdersCount = 0;
    $recentNotifs = [];
    if ($isLogged) {
        $userId = (int) session()->get('user_id');
        $userModel = new \App\Models\UserModel();
        $stats = $userModel->getCustomerHeaderStats($userId);
        $cartCount = $stats['cart_count'];
        $activeOrdersCount = $stats['active_orders_count'];
        $unreadCount = $stats['unread_count'];

        $notifModel = new \App\Models\NotificationModel();
        $recentNotifs = $notifModel->getRecent($userId, 6);
    }

    // Global CMS contents for announcement bar and footer across all customer pages
    if (!isset($cmsGlobal) || empty($cmsGlobal)) {
        try {
            $scm = new \App\Models\SiteContentModel();
            $cmsGlobal = $scm->getAllKeyMap();
        } catch (\Throwable $e) {
            $cmsGlobal = [];
        }
    }
    if (isset($siteContents) && is_array($siteContents)) {
        $cmsGlobal = array_merge($cmsGlobal, $siteContents);
    }
?>

<!DOCTYPE html><html class="light" lang="en"><head>

    <?= view('components/head', ['title' => $title ?? 'MarketPlace']) ?>

</head>

<body class="bg-background text-on-surface antialiased min-h-screen flex flex-col relative pb-16 md:pb-0">

    <div class="flex-grow flex flex-col">

        <?= view('components/announcement_bar', ['cms' => $cmsGlobal]) ?>

        <?= view('components/marketplace_header', [
            'activeNav'          => $activeNav,
            'cartCount'          => $cartCount,
            'activeOrdersCount'  => $activeOrdersCount,
            'unreadCount'        => $unreadCount,
            'recentNotifs'       => $recentNotifs,
            'searchQuery'        => $searchQuery ?? ''
        ]) ?>
        <?= $this->renderSection('content') ?>
        <?= view('components/footer', ['cms' => $cmsGlobal]) ?>

    </div>

    <!-- Mobile Bottom Sticky Navigation (Guests & Users) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-surface-container-lowest/95 backdrop-blur-md border-t border-outline-variant/30 shadow-[0_-4px_16px_rgba(0,0,0,0.06)] px-2 pt-1.5 pb-[calc(0.5rem+env(safe-area-inset-bottom,0px))] flex items-center justify-around">
        
        <!-- 1. Home -->
        <a href="<?= base_url('/') ?>" class="flex flex-col items-center justify-center min-w-[56px] py-1 text-center transition-colors <?= $activeNav === 'home' ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary' ?>">
            <span class="material-symbols-outlined text-[22px] <?= $activeNav === 'home' ? 'text-primary scale-105' : '' ?> transition-transform">home</span>
            <span class="text-[10px] tracking-tight leading-none mt-1">Home</span>
        </a>

        <!-- 2. Categories -->
        <a href="<?= base_url('categories') ?>" class="flex flex-col items-center justify-center min-w-[56px] py-1 text-center transition-colors <?= $activeNav === 'categories' ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary' ?>">
            <span class="material-symbols-outlined text-[22px] <?= $activeNav === 'categories' ? 'text-primary scale-105' : '' ?> transition-transform">grid_view</span>
            <span class="text-[10px] tracking-tight leading-none mt-1">Categories</span>
        </a>

        <!-- 3. Printing Services (Rush/Featured) -->
        <a href="<?= base_url('printing-services') ?>" class="flex flex-col items-center justify-center min-w-[56px] py-1 text-center relative transition-colors <?= $activeNav === 'printing' ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary' ?>">
            <span class="material-symbols-outlined text-[22px] <?= $activeNav === 'printing' ? 'text-primary scale-105' : '' ?> transition-transform">print</span>
            <span class="text-[10px] tracking-tight leading-none mt-1">Printing</span>
        </a>

        <!-- 4. Cart -->
        <a href="<?= base_url('cart') ?>" class="flex flex-col items-center justify-center min-w-[56px] py-1 text-center relative transition-colors <?= $activeNav === 'cart' ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary' ?>">
            <div class="relative inline-flex items-center justify-center">
                <span class="material-symbols-outlined text-[22px] <?= $activeNav === 'cart' ? 'text-primary scale-105' : '' ?> transition-transform">shopping_cart</span>
                <span id="mobile-bottom-cart-badge" class="<?= ($cartCount > 0) ? '' : 'hidden' ?> absolute -top-1 -right-2.5 bg-error text-on-error text-[9px] font-bold rounded-full min-w-[15px] h-[15px] px-1 flex items-center justify-center leading-none shadow-xs">
                    <?= $cartCount ?>
                </span>
            </div>
            <span class="text-[10px] tracking-tight leading-none mt-1">Cart</span>
        </a>

        <!-- 5. Guest Sign In / Logged in Profile -->
        <?php if ($isLogged): ?>
            <a href="<?= base_url('customer/profile') ?>" class="flex flex-col items-center justify-center min-w-[56px] py-1 text-center transition-colors <?= $activeNav === 'profile' ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary' ?>">
                <span class="material-symbols-outlined text-[22px] <?= $activeNav === 'profile' ? 'text-primary scale-105' : '' ?> transition-transform">account_circle</span>
                <span class="text-[10px] tracking-tight leading-none mt-1">Account</span>
            </a>
        <?php else: ?>
            <a href="<?= base_url('login') ?>" class="flex flex-col items-center justify-center min-w-[56px] py-1 text-center group transition-colors">
                <div class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-all shadow-2xs">
                    <span class="material-symbols-outlined text-[16px]">login</span>
                </div>
                <span class="text-[10px] font-bold text-primary tracking-tight leading-none mt-1">Sign In</span>
            </a>
        <?php endif; ?>

    </nav>

    <?php if (($showAiAssistant ?? true) !== false): ?>

        <?= view('components/ai_assistant') ?>

    <?php endif; ?>

    <?= $this->renderSection('scripts') ?>

<script>
/* ── AJAX Add-to-Cart (global) ─────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    const cartBadge = document.getElementById('cart-count-badge');
    const mobileCartBadge = document.getElementById('mobile-bottom-cart-badge');

    document.body.addEventListener('submit', async (e) => {
        const form = e.target.closest('form[action*="cart/add"]');
        if (!form) return;

        // Do not intercept if submission is for Buy Now or targets a non-cart endpoint
        const submitter = e.submitter;
        if (submitter) {
            const formaction = submitter.getAttribute('formaction');
            if (formaction && !formaction.includes('cart/add')) return;
            if (submitter.id === 'buy-now-btn' || submitter.classList.contains('buy-now-btn')) return;
        }

        e.preventDefault();

        const btn = form.querySelector('button[type="submit"]');
        if (!btn || btn.dataset.loading) return;
        btn.dataset.loading = '1';

        const icon = btn.querySelector('.material-symbols-outlined');
        const origIcon  = icon ? icon.textContent.trim() : '';
        const origClass = btn.className;

        const fd = new FormData(form);
        fd.append('X-Requested-With', 'XMLHttpRequest');

        try {
            const res  = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            });
            const data = await res.json();

            if (data.status === 'success') {
                // ✅ Success: green + check icon
                btn.className = btn.className
                    .replace(/bg-\S+/g, '')
                    .replace(/text-on-primary\b/g, '')
                    .trim()
                    + ' bg-green-500 text-white scale-110';
                if (icon) icon.textContent = 'check';

                // Update cart badges
                if (cartBadge) {
                    const cur = parseInt(cartBadge.textContent) || 0;
                    cartBadge.textContent = cur + 1;
                    cartBadge.classList.remove('hidden');
                }
                if (mobileCartBadge) {
                    const cur = parseInt(mobileCartBadge.textContent) || 0;
                    mobileCartBadge.textContent = cur + 1;
                    mobileCartBadge.classList.remove('hidden');
                }

                setTimeout(() => {
                    btn.className = origClass;
                    if (icon) icon.textContent = origIcon;
                    delete btn.dataset.loading;
                }, 1500);
            } else {
                // ❌ Error: red flash
                btn.className = btn.className.replace(/bg-\S+/g, '').trim() + ' bg-red-500 text-white';
                if (icon) icon.textContent = 'error';
                setTimeout(() => {
                    btn.className = origClass;
                    if (icon) icon.textContent = origIcon;
                    delete btn.dataset.loading;
                }, 1500);
            }
        } catch {
            btn.className = origClass;
            delete btn.dataset.loading;
        }
    });
});
</script>

<!-- Global Toast Container (Fixed Top Right) -->
<div id="blax-toast-container" class="fixed top-4 right-4 z-[9999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-3 sm:px-0"></div>

<script>
(function() {
    // 1. Synthesize clean melodic audio chime with Web Audio API
    function playChime(type) {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            if (ctx.state === 'suspended') ctx.resume();

            const now = ctx.currentTime;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            if (type === 'error') {
                osc.frequency.setValueAtTime(300, now);
                osc.frequency.exponentialRampToValueAtTime(180, now + 0.25);
            } else if (type === 'order' || type === 'delivery') {
                osc.frequency.setValueAtTime(587.33, now); // D5
                osc.frequency.setValueAtTime(880.00, now + 0.12); // A5
            } else {
                osc.frequency.setValueAtTime(523.25, now); // C5
                osc.frequency.setValueAtTime(659.25, now + 0.10); // E5
            }

            gain.gain.setValueAtTime(0.08, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start(now);
            osc.stop(now + 0.36);
        } catch (e) {}
    }

    // 2. Universal Toast Notification Function
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

        let container = document.getElementById('blax-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'blax-toast-container';
            container.className = 'fixed top-4 right-4 z-[9999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-3 sm:px-0';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = 'pointer-events-auto bg-surface-container-lowest/95 dark:bg-surface-container-low/95 backdrop-blur-md border border-outline-variant/40 rounded-2xl shadow-xl p-3.5 flex items-start gap-3 transform translate-y-[-10px] opacity-0 transition-all duration-300 ease-out';

        const colorMap = {
            success: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
            error: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
            warning: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
            order: 'bg-primary/10 text-primary border-primary/20',
            delivery: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
            printing: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
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
        playChime(type);

        requestAnimationFrame(() => {
            toast.classList.remove('translate-y-[-10px]', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
        });

        const duration = opts.duration || 6000;
        const autoDismiss = setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-x-full');
            setTimeout(() => toast.remove(), 350);
        }, duration);

        toast.addEventListener('mouseenter', () => clearTimeout(autoDismiss));
    };

    // 3. Auto-render PHP Flashdata as Toasts on Page Load
    document.addEventListener('DOMContentLoaded', () => {
        <?php if (session()->getFlashdata('success')): ?>
            window.showToast({
                type: 'success',
                title: 'Action Successful',
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

        <?php if (session()->getFlashdata('info')): ?>
            window.showToast({
                type: 'info',
                title: 'Information',
                message: <?= json_encode((string)session()->getFlashdata('info')) ?>
            });
        <?php endif; ?>
    });

    // 4. Customer Real-time Notification & Delivery Status Poller (if logged in)
    <?php if ($isLogged): ?>
    let lastNotifId = <?= !empty($recentNotifs[0]['id']) ? (int)$recentNotifs[0]['id'] : 0 ?>;
    let knownOrderStatuses = {};
    let isInitialCustomerPoll = true;

    function pollCustomerRealtime() {
        let url = '<?= base_url('customer/realtime/check') ?>';
        if (lastNotifId > 0) url += '?last_notif_id=' + lastNotifId;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(res => {
                if (!res || !res.success) return;

                if (isInitialCustomerPoll) {
                    lastNotifId = res.max_notif_id || lastNotifId;
                    if (Array.isArray(res.active_orders)) {
                        res.active_orders.forEach(o => { knownOrderStatuses[o.id] = o.status; });
                    }
                    isInitialCustomerPoll = false;
                    return;
                }

                if (res.max_notif_id && res.max_notif_id > lastNotifId) {
                    lastNotifId = res.max_notif_id;
                }

                // Show new notification toasts
                if (res.has_new && Array.isArray(res.new_notifs)) {
                    res.new_notifs.forEach(n => {
                        window.showToast({
                            type: n.type && n.type.includes('delivery') ? 'delivery' : (n.type && n.type.includes('printing') ? 'printing' : 'order'),
                            title: n.title || 'Notification',
                            message: n.message || '',
                            url: n.link_url || '<?= base_url('customer/orders') ?>'
                        });
                    });
                }

                // Check order status transitions
                if (Array.isArray(res.active_orders)) {
                    res.active_orders.forEach(o => {
                        const prev = knownOrderStatuses[o.id];
                        if (prev && prev !== o.status) {
                            knownOrderStatuses[o.id] = o.status;
                            const statusNames = {
                                'processing': 'is being prepared by the shop',
                                'shipped': 'has been dispatched for delivery',
                                'in_transit': 'is out for delivery to your doorstep',
                                'ready_for_pickup': 'is ready for pick-up at the counter',
                                'delivered': 'has been successfully delivered'
                            };
                            const statusText = statusNames[o.status] || `status changed to ${o.status}`;
                            window.showToast({
                                type: 'delivery',
                                title: `Order #${o.order_number}`,
                                message: `Your package ${statusText}!`,
                                url: `<?= base_url('customer/orders/track/') ?>/${o.order_number}`
                            });
                        } else {
                            knownOrderStatuses[o.id] = o.status;
                        }
                    });
                }
            })
            .catch(() => {});
    }

    setInterval(pollCustomerRealtime, 6000);
    <?php endif; ?>
})();
</script>

</body></html>