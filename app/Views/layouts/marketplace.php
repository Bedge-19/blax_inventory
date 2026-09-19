<?php
    $activeNav = $activeNav ?? '';
    if ($activeNav === '') {
        $seg = service('request')->getUri()->getSegment(1) ?? '';
        $navMap = ['home' => 'home', 'categories' => 'categories', 'category' => 'categories', 'shops' => 'shops', 'shop' => 'shops', 'printing-services' => 'printing'];
        $activeNav = $navMap[$seg] ?? '';
        if ($activeNav === '' && $seg === '') {
            $activeNav = 'home';
        }
    }

    $cartCount = 0;
    $unreadCount = 0;
    $recentNotifs = [];
    if (session()->get('isLoggedIn')) {
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

<body class="bg-background text-on-surface antialiased min-h-screen flex flex-col relative">

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

    <?php if (($showAiAssistant ?? true) !== false): ?>

        <?= view('components/ai_assistant') ?>

    <?php endif; ?>

    <?= $this->renderSection('scripts') ?>

<script>
/* ── AJAX Add-to-Cart (global) ─────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    const cartBadge = document.getElementById('cart-count-badge');

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

                // Update cart badge
                if (cartBadge) {
                    const cur = parseInt(cartBadge.textContent) || 0;
                    cartBadge.textContent = cur + 1;
                    cartBadge.classList.remove('hidden');
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