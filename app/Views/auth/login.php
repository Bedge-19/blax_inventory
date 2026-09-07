<?= $this->extend('layouts/auth') ?>
<?= $this->section('cardClass') ?>max-w-md<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$isShopOwner = ($initialRole ?? 'customer') === 'shop_owner';
?>

<!-- Header -->
<div class="text-center mb-md">
    <a href="<?= base_url('/') ?>" class="inline-flex flex-col items-center gap-xs mb-xs">
        <img src="<?= base_url('icon.png') ?>" alt="Blax" class="w-16 h-16 object-contain">
    </a>
    <h1 class="text-title-lg text-on-surface font-semibold">Sign In to Blax</h1>
    <p id="role-subtitle" class="text-label-sm text-outline mt-xs transition-opacity duration-200">
        <?= $isShopOwner ? 'Access your shop dashboard, orders, and inventory' : 'Access your customer account, cart, and orders' ?>
    </p>
</div>

<!-- Account Type Selector (Contextual Selector) -->
<div class="flex justify-center mb-md">
    <div class="inline-flex items-center bg-surface-container rounded-full p-1 w-full shadow-sm" role="tablist" aria-label="Select account type">
        <button type="button"
                id="tab-customer"
                role="tab"
                aria-selected="<?= $isShopOwner ? 'false' : 'true' ?>"
                class="flex-1 py-xs px-sm rounded-full text-label-sm font-button transition-all duration-200 text-center <?= $isShopOwner ? 'bg-transparent text-on-surface-variant hover:text-primary' : 'bg-white text-primary font-bold shadow-sm' ?>">
            <span class="inline-flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-[16px]">shopping_bag</span>
                Customer
            </span>
        </button>
        <button type="button"
                id="tab-shop-owner"
                role="tab"
                aria-selected="<?= $isShopOwner ? 'true' : 'false' ?>"
                class="flex-1 py-xs px-sm rounded-full text-label-sm font-button transition-all duration-200 text-center <?= $isShopOwner ? 'bg-white text-primary font-bold shadow-sm' : 'bg-transparent text-on-surface-variant hover:text-primary' ?>">
            <span class="inline-flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-[16px]">storefront</span>
                Shop Owner
            </span>
        </button>
    </div>
</div>

<!-- Form -->
<form class="flex flex-col gap-md" action="<?= base_url('login') ?>" method="POST">

    <?= csrf_field() ?>

    <div class="flex flex-col gap-xs">
        <label class="font-label-sm text-label-sm text-on-surface-variant font-medium" for="email">Email Address</label>
        <input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-md font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all placeholder:text-outline/50" id="email" name="email" placeholder="you@example.com" type="email" autocomplete="email" required>
    </div>

    <div class="flex flex-col gap-xs">
        <div class="flex justify-between items-center">
            <label class="font-label-sm text-label-sm text-on-surface-variant font-medium" for="password">Password</label>
            <a class="font-label-sm text-label-sm text-primary hover:underline" href="<?= base_url('forgot-password') ?>">Forgot password?</a>
        </div>
        <div class="relative">
            <input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-md font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all placeholder:text-outline/50 pr-[48px]" id="password" name="password" placeholder="••••••••" type="password" autocomplete="current-password" required>
            <button class="absolute right-md top-1/2 -translate-y-1/2 text-outline hover:text-primary transition-colors" type="button" id="toggle-password" aria-label="Show password">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 0;">visibility</span>
            </button>
        </div>
    </div>

    <div class="flex items-center gap-sm mt-xs">
        <input class="rounded text-primary border-outline-variant focus:ring-primary h-4 w-4" id="remember" type="checkbox">
        <label class="font-label-sm text-label-sm text-on-surface-variant select-none" for="remember">Remember me on this device</label>
    </div>

    <button class="w-full bg-primary hover:bg-primary-container text-on-primary font-button text-button rounded-lg py-md mt-sm shadow-lg hover:shadow-xl hover:-translate-y-[2px] transition-all duration-200 uppercase tracking-wider font-semibold" type="submit" id="submit-button">
        Sign In
    </button>

</form>

<?= $this->endSection() ?>

<?= $this->section('footer') ?>

<div class="text-center mt-md pt-lg border-t border-outline-variant/30 flex flex-col gap-sm">

    <!-- Dynamic Sign-up Path -->
    <div id="signup-prompt">
        <p id="customer-signup-link" class="font-body-md text-body-md text-on-surface-variant <?= $isShopOwner ? 'hidden' : '' ?>">
            Don't have an account? <a class="text-primary hover:underline font-semibold" href="<?= base_url('signup') ?>">Create customer account</a>
        </p>
        <p id="merchant-signup-link" class="font-body-md text-body-md text-on-surface-variant <?= $isShopOwner ? '' : 'hidden' ?>">
            Looking to sell on Blax? <a class="text-primary hover:underline font-semibold" href="<?= base_url('merchant-signup') ?>">Register your shop</a>
        </p>
    </div>

    <!-- Administrator Access Note -->
    <div class="pt-xs">
        <p class="text-[12px] text-outline flex items-center justify-center gap-1">
            <span class="material-symbols-outlined text-[15px]">admin_panel_settings</span>
            <span>Administrator? Use your admin credentials above.</span>
        </p>
    </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>
    (function () {
        // Password toggle
        var toggle = document.getElementById('toggle-password');
        if (toggle) {
            toggle.addEventListener('click', function () {
                var input = document.getElementById('password');
                var icon = toggle.querySelector('span');
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                icon.textContent = show ? 'visibility_off' : 'visibility';
            });
        }

        // Account type tab switcher
        var tabCustomer = document.getElementById('tab-customer');
        var tabShopOwner = document.getElementById('tab-shop-owner');
        var subtitle = document.getElementById('role-subtitle');
        var customerSignupLink = document.getElementById('customer-signup-link');
        var merchantSignupLink = document.getElementById('merchant-signup-link');

        function setRole(role) {
            var isShop = role === 'shop_owner';

            if (isShop) {
                tabShopOwner.setAttribute('aria-selected', 'true');
                tabCustomer.setAttribute('aria-selected', 'false');

                tabShopOwner.classList.remove('bg-transparent', 'text-on-surface-variant');
                tabShopOwner.classList.add('bg-white', 'text-primary', 'font-bold', 'shadow-sm');

                tabCustomer.classList.remove('bg-white', 'text-primary', 'font-bold', 'shadow-sm');
                tabCustomer.classList.add('bg-transparent', 'text-on-surface-variant');

                if (subtitle) subtitle.textContent = 'Access your shop dashboard, orders, and inventory';
                if (customerSignupLink) customerSignupLink.classList.add('hidden');
                if (merchantSignupLink) merchantSignupLink.classList.remove('hidden');
            } else {
                tabCustomer.setAttribute('aria-selected', 'true');
                tabShopOwner.setAttribute('aria-selected', 'false');

                tabCustomer.classList.remove('bg-transparent', 'text-on-surface-variant');
                tabCustomer.classList.add('bg-white', 'text-primary', 'font-bold', 'shadow-sm');

                tabShopOwner.classList.remove('bg-white', 'text-primary', 'font-bold', 'shadow-sm');
                tabShopOwner.classList.add('bg-transparent', 'text-on-surface-variant');

                if (subtitle) subtitle.textContent = 'Access your customer account, cart, and orders';
                if (customerSignupLink) customerSignupLink.classList.remove('hidden');
                if (merchantSignupLink) merchantSignupLink.classList.add('hidden');
            }

            // Sync URL query without reloading
            if (window.history && window.history.replaceState) {
                var url = new URL(window.location);
                if (isShop) {
                    url.searchParams.set('role', 'shop_owner');
                } else {
                    url.searchParams.delete('role');
                }
                window.history.replaceState({}, '', url);
            }
        }

        if (tabCustomer && tabShopOwner) {
            tabCustomer.addEventListener('click', function () {
                setRole('customer');
            });
            tabShopOwner.addEventListener('click', function () {
                setRole('shop_owner');
            });
        }
    })();
</script>

<?= $this->endSection() ?>
