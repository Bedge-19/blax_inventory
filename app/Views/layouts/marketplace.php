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
    $recentNotifs = [];
    if ($isLogged) {
        $userId = session()->get('user_id');
        $cartModel = new \App\Models\CartModel();
        $cartItemModel = new \App\Models\CartItemModel();
        $cart = $cartModel->getOrCreateCart($userId);
        $cartCount = $cartItemModel->where('cart_id', $cart['id'])->countAllResults();

        $orderModel = new \App\Models\OrderModel();
        $activeOrdersCount = $orderModel->where('customer_id', $userId)
            ->whereIn('status', ['pending', 'processing', 'shipped', 'ready_for_pickup'])
            ->countAllResults();

        $notifModel = new \App\Models\NotificationModel();
        $unreadCount = $notifModel->getUnreadCount($userId);
        $recentNotifs = $notifModel->getRecent($userId, 6);
    } else {
        $activeOrdersCount = 0;
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

</body></html>