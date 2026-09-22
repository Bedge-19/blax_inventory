<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>

<!-- Card Header -->
<div class="space-y-1.5">
    <h2 class="text-2xl font-display font-extrabold text-slate-900 tracking-tight">Create Account</h2>
    <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
        Sign up to start shopping verified Polomolok stores and order rush prints.
    </p>

</div>

<!-- Account Type Toggle -->
<div class="pt-1">
    <div class="bg-slate-100 p-1 rounded-xl flex items-center gap-1 border border-slate-200/60" role="tablist">
        <a href="<?= base_url('signup') ?>" 
           class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-all duration-200 flex items-center justify-center gap-1.5 bg-white text-primary shadow-xs">
            <span class="material-symbols-outlined text-[17px]">shopping_bag</span>
            <span>Customer</span>
        </a>
        <a href="<?= base_url('merchant-signup') ?>" 
           class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-all duration-200 flex items-center justify-center gap-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-200/50">
            <span class="material-symbols-outlined text-[17px]">storefront</span>
            <span>Shop Owner</span>
        </a>
    </div>
</div>

<!-- Customer Registration Form -->
<form id="signup-form" action="<?= base_url('signup') ?>" method="POST" class="space-y-3.5 pt-1" novalidate>

    <?= csrf_field() ?>

    <!-- Name Row (2 columns) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        
        <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block" for="first-name">First Name</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">person</span>
                <input class="w-full bg-slate-50/60 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                       id="first-name" 
                       name="first_name" 
                       placeholder="e.g. Maria" 
                       required 
                       type="text">
            </div>
            <span class="field-error text-[11px] text-red-600 font-medium hidden" id="err-first-name"></span>
        </div>

        <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block" for="last-name">Last Name</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">person</span>
                <input class="w-full bg-slate-50/60 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                       id="last-name" 
                       name="last_name" 
                       placeholder="e.g. Santos" 
                       required 
                       type="text">
            </div>
            <span class="field-error text-[11px] text-red-600 font-medium hidden" id="err-last-name"></span>
        </div>

    </div>

    <!-- Email Address -->
    <div class="space-y-1">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block" for="email">Email Address</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">mail</span>
            <input class="w-full bg-slate-50/60 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                   id="email" 
                   name="email" 
                   placeholder="you@example.com" 
                   required 
                   type="email"
                   autocomplete="email">
        </div>
        <span class="field-error text-[11px] text-red-600 font-medium hidden" id="err-email"></span>
    </div>

    <!-- Phone Number -->
    <div class="space-y-1">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block" for="phone">Cellphone Number (Optional)</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">call</span>
            <input class="w-full bg-slate-50/60 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                   id="phone" 
                   name="phone" 
                   placeholder="0912 345 6789" 
                   type="tel">
        </div>
        <span class="field-error text-[11px] text-red-600 font-medium hidden" id="err-phone"></span>
    </div>

    <!-- Password & Confirm Password (2 columns) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

        <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block" for="password">Password</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">lock</span>
                <input class="w-full bg-slate-50/60 border border-slate-200 rounded-xl pl-9 pr-9 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                       id="password" 
                       name="password" 
                       placeholder="Min. 8 chars" 
                       required 
                       type="password" 
                       minlength="8">
                <button type="button" class="toggle-pwd-btn absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1" data-target="password">
                    <span class="material-symbols-outlined text-[17px]">visibility</span>
                </button>
            </div>
            <span class="field-error text-[11px] text-red-600 font-medium hidden" id="err-password"></span>
        </div>

        <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block" for="confirm-password">Confirm Password</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">lock</span>
                <input class="w-full bg-slate-50/60 border border-slate-200 rounded-xl pl-9 pr-9 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                       id="confirm-password" 
                       name="confirm_password" 
                       placeholder="Repeat password" 
                       required 
                       type="password" 
                       minlength="8">
                <button type="button" class="toggle-pwd-btn absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1" data-target="confirm-password">
                    <span class="material-symbols-outlined text-[17px]">visibility</span>
                </button>
            </div>
            <span class="field-error text-[11px] text-red-600 font-medium hidden" id="err-confirm-password"></span>
        </div>

    </div>

    <!-- Terms Note -->
    <p class="text-[11px] text-slate-500 leading-snug pt-1">
        By creating an account, you agree to Blax's Terms of Service and acknowledge local delivery policies in Polomolok.
    </p>

    <!-- Submit Button -->
    <button class="w-full bg-primary hover:bg-primary-container text-white font-bold py-3 px-4 rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs sm:text-sm uppercase tracking-wider flex items-center justify-center gap-2 mt-2" 
            type="submit" 
            id="signup-submit-btn">
        <span>Create Customer Account</span>
        <span class="material-symbols-outlined text-[18px]">person_add</span>
    </button>

</form>

<?= $this->endSection() ?>

<?= $this->section('footer') ?>

<!-- Card Footer Navigation -->
<div class="pt-4 border-t border-slate-200/80 space-y-2 text-center">
    <p class="text-xs sm:text-sm text-slate-600">
        Already have an account? <a href="<?= base_url('login') ?>" class="text-primary hover:underline font-bold">Sign in</a>
    </p>
    <div class="flex items-center justify-center gap-1 text-[11px] text-slate-400">
        <span class="material-symbols-outlined text-[13px] text-emerald-600">verified_user</span>
        <span>Secure registration with instant verification</span>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>
(function() {
    // Password visibility toggles
    document.querySelectorAll('.toggle-pwd-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = btn.getAttribute('data-target');
            var targetInput = document.getElementById(targetId);
            if (targetInput) {
                var isPwd = targetInput.type === 'password';
                targetInput.type = isPwd ? 'text' : 'password';
                var icon = btn.querySelector('span');
                if (icon) icon.textContent = isPwd ? 'visibility_off' : 'visibility';
            }
        });
    });

    var form = document.getElementById('signup-form');
    if (!form) return;

    var firstName = document.getElementById('first-name');
    var lastName  = document.getElementById('last-name');
    var email     = document.getElementById('email');
    var password  = document.getElementById('password');
    var confirmP  = document.getElementById('confirm-password');
    var submitBtn = document.getElementById('signup-submit-btn');

    function setError(input, errId, msg) {
        var el = document.getElementById(errId);
        if (!el) return;
        if (msg) {
            el.textContent = msg;
            el.classList.remove('hidden');
            input.classList.add('border-red-500', 'bg-red-50/30');
            input.classList.remove('border-slate-200');
        } else {
            el.textContent = '';
            el.classList.add('hidden');
            input.classList.remove('border-red-500', 'bg-red-50/30');
            input.classList.add('border-slate-200');
        }
    }

    function validateFirstName() {
        if (!firstName.value.trim()) { setError(firstName, 'err-first-name', 'First name is required.'); return false; }
        setError(firstName, 'err-first-name', ''); return true;
    }
    function validateLastName() {
        if (!lastName.value.trim()) { setError(lastName, 'err-last-name', 'Last name is required.'); return false; }
        setError(lastName, 'err-last-name', ''); return true;
    }
    function validateEmail() {
        var v = email.value.trim();
        if (!v) { setError(email, 'err-email', 'Email address is required.'); return false; }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) { setError(email, 'err-email', 'Enter a valid email address.'); return false; }
        setError(email, 'err-email', ''); return true;
    }
    function validatePassword() {
        var v = password.value;
        if (!v) { setError(password, 'err-password', 'Password is required.'); return false; }
        if (v.length < 8) { setError(password, 'err-password', 'Password must be at least 8 characters.'); return false; }
        setError(password, 'err-password', ''); return true;
    }
    function validateConfirm() {
        var v = confirmP.value;
        if (!v) { setError(confirmP, 'err-confirm-password', 'Please confirm your password.'); return false; }
        if (v !== password.value) { setError(confirmP, 'err-confirm-password', 'Passwords do not match.'); return false; }
        setError(confirmP, 'err-confirm-password', ''); return true;
    }

    firstName.addEventListener('input', validateFirstName);
    lastName.addEventListener('input', validateLastName);
    email.addEventListener('input', validateEmail);
    password.addEventListener('input', function() { validatePassword(); if (confirmP.value) validateConfirm(); });
    confirmP.addEventListener('input', validateConfirm);

    form.addEventListener('submit', function(e) {
        var ok1 = validateFirstName();
        var ok2 = validateLastName();
        var ok3 = validateEmail();
        var ok4 = validatePassword();
        var ok5 = validateConfirm();
        if (!ok1 || !ok2 || !ok3 || !ok4 || !ok5) {
            e.preventDefault();
        } else if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            var span = submitBtn.querySelector('span:first-child');
            if (span) span.textContent = 'Creating Account...';
        }
    });
})();
</script>

<?= $this->endSection() ?>