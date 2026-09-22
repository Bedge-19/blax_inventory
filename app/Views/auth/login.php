<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>

<?php
$isShopOwner = ($initialRole ?? 'customer') === 'shop_owner';
?>

<!-- Card Header -->
<div class="space-y-1.5">
    <h2 class="text-2xl font-display font-extrabold text-slate-900 tracking-tight">Sign In</h2>
    <p id="role-subtitle" class="text-xs sm:text-sm text-slate-500 leading-relaxed transition-opacity duration-200">
        <?= $isShopOwner ? 'Access your shop dashboard, orders, and printing requests.' : 'Access your customer account, cart, and tracking orders.' ?>
    </p>

</div>

<!-- Contextual Role Selector (Segmented Pill Switcher) -->
<div class="pt-1">
    <div class="bg-slate-100 p-1 rounded-xl flex items-center gap-1 border border-slate-200/60" role="tablist" aria-label="Select account type">
        <button type="button"
                id="tab-customer"
                role="tab"
                aria-selected="<?= $isShopOwner ? 'false' : 'true' ?>"
                class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-all duration-200 flex items-center justify-center gap-1.5 <?= $isShopOwner ? 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50' : 'bg-white text-primary shadow-xs' ?>">
            <span class="material-symbols-outlined text-[17px]">shopping_bag</span>
            <span>Customer</span>
        </button>
        <button type="button"
                id="tab-shop-owner"
                role="tab"
                aria-selected="<?= $isShopOwner ? 'true' : 'false' ?>"
                class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-all duration-200 flex items-center justify-center gap-1.5 <?= $isShopOwner ? 'bg-white text-primary shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50' ?>">
            <span class="material-symbols-outlined text-[17px]">storefront</span>
            <span>Shop Owner</span>
        </button>
    </div>
</div>

<!-- Login Form -->
<form class="space-y-4 pt-1" action="<?= base_url('login') ?>" method="POST">

    <?= csrf_field() ?>

    <!-- Email Field -->
    <div class="space-y-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block" for="email">Email Address</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[19px] pointer-events-none">mail</span>
            <input class="w-full bg-slate-50/60 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                   id="email" 
                   name="email" 
                   placeholder="you@example.com" 
                   type="email" 
                   autocomplete="email" 
                   required>
        </div>
    </div>

    <!-- Password Field -->
    <div class="space-y-1.5">
        <div class="flex justify-between items-center">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block" for="password">Password</label>
            <a class="text-xs font-semibold text-primary hover:text-primary-container hover:underline transition-colors" href="<?= base_url('forgot-password') ?>">
                Forgot password?
            </a>
        </div>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[19px] pointer-events-none">lock</span>
            <input class="w-full bg-slate-50/60 border border-slate-200 rounded-xl pl-10 pr-10 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                   id="password" 
                   name="password" 
                   placeholder="••••••••" 
                   type="password" 
                   autocomplete="current-password" 
                   required>
            <button class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 transition-colors p-1" 
                    type="button" 
                    id="toggle-password" 
                    aria-label="Toggle password visibility">
                <span class="material-symbols-outlined text-[19px]">visibility</span>
            </button>
        </div>
    </div>

    <!-- Remember Me Checkbox -->
    <div class="flex items-center gap-2 pt-0.5">
        <input class="rounded border-slate-300 text-primary focus:ring-primary/30 h-4 w-4 transition-colors cursor-pointer" 
               id="remember" 
               type="checkbox">
        <label class="text-xs font-medium text-slate-600 select-none cursor-pointer" for="remember">
            Remember me on this device
        </label>
    </div>

    <!-- Submit Button -->
    <button class="w-full bg-primary hover:bg-primary-container text-white font-bold py-3 px-4 rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs sm:text-sm uppercase tracking-wider flex items-center justify-center gap-2" 
            type="submit" 
            id="submit-button">
        <span><?= $isShopOwner ? 'Sign In as Shop Owner' : 'Sign In to Account' ?></span>
        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
    </button>

</form>

<?= $this->endSection() ?>

<?= $this->section('footer') ?>

<!-- Card Footer Navigation -->
<div class="pt-4 border-t border-slate-200/80 space-y-3 text-center">

    <!-- Dynamic Sign-up Prompt -->
    <div id="signup-prompt" class="text-xs sm:text-sm text-slate-600">
        <p id="customer-signup-link" class="<?= $isShopOwner ? 'hidden' : '' ?>">
            New to Blax? <a class="text-primary hover:underline font-bold" href="<?= base_url('signup') ?>">Create customer account</a>
        </p>
        <p id="merchant-signup-link" class="<?= $isShopOwner ? '' : 'hidden' ?>">
            Looking to sell? <a class="text-primary hover:underline font-bold" href="<?= base_url('merchant-signup') ?>">Register your shop</a>
        </p>
    </div>

    <!-- Security & Administrator Reassurance -->
    <div class="pt-1 flex flex-col gap-1 items-center justify-center text-[11px] text-slate-400">
        <div class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[14px] text-slate-400">admin_panel_settings</span>
            <span>Administrator? Use your admin credentials above.</span>
        </div>
        <div class="flex items-center gap-1 text-slate-400 text-[10px]">
            <span class="material-symbols-outlined text-[13px] text-emerald-600">lock</span>
            <span>Encrypted Polomolok Multi-Tenant Authentication</span>
        </div>
    </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>
    (function () {
        // Password toggle
        var toggle = document.getElementById('toggle-password');
        var input = document.getElementById('password');
        if (toggle && input) {
            toggle.addEventListener('click', function () {
                var icon = toggle.querySelector('span');
                var isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                if (icon) {
                    icon.textContent = isPassword ? 'visibility_off' : 'visibility';
                }
            });
        }

        // Account type tab switcher
        var tabCustomer = document.getElementById('tab-customer');
        var tabShopOwner = document.getElementById('tab-shop-owner');
        var subtitle = document.getElementById('role-subtitle');
        var submitBtn = document.getElementById('submit-button');
        var customerSignupLink = document.getElementById('customer-signup-link');
        var merchantSignupLink = document.getElementById('merchant-signup-link');

        function setRole(role) {
            var isShop = role === 'shop_owner';

            if (isShop) {
                tabShopOwner.setAttribute('aria-selected', 'true');
                tabCustomer.setAttribute('aria-selected', 'false');

                tabShopOwner.className = 'flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-all duration-200 flex items-center justify-center gap-1.5 bg-white text-primary shadow-xs';
                tabCustomer.className = 'flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-all duration-200 flex items-center justify-center gap-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-200/50';

                if (subtitle) subtitle.textContent = 'Access your shop dashboard, orders, and printing requests.';
                if (submitBtn) {
                    var span = submitBtn.querySelector('span:first-child');
                    if (span) span.textContent = 'Sign In as Shop Owner';
                }
                if (customerSignupLink) customerSignupLink.classList.add('hidden');
                if (merchantSignupLink) merchantSignupLink.classList.remove('hidden');
            } else {
                tabCustomer.setAttribute('aria-selected', 'true');
                tabShopOwner.setAttribute('aria-selected', 'false');

                tabCustomer.className = 'flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-all duration-200 flex items-center justify-center gap-1.5 bg-white text-primary shadow-xs';
                tabShopOwner.className = 'flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-all duration-200 flex items-center justify-center gap-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-200/50';

                if (subtitle) subtitle.textContent = 'Access your customer account, cart, and tracking orders.';
                if (submitBtn) {
                    var span = submitBtn.querySelector('span:first-child');
                    if (span) span.textContent = 'Sign In to Account';
                }
                if (customerSignupLink) customerSignupLink.classList.remove('hidden');
                if (merchantSignupLink) merchantSignupLink.classList.add('hidden');
            }

            // Sync URL query
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
            tabCustomer.addEventListener('click', function () { setRole('customer'); });
            tabShopOwner.addEventListener('click', function () { setRole('shop_owner'); });
        }

        // Prevent accidental double-submission
        var form = document.querySelector('form[action*="login"]');
        if (form && submitBtn) {
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                var span = submitBtn.querySelector('span:first-child');
                if (span) span.textContent = 'Signing In...';
            });
        }
    })();
</script>

<?= $this->endSection() ?>
