<?= $this->extend('layouts/auth') ?>

<?= $this->section('layoutMode') ?>centered<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Branding Header -->
<div class="text-center space-y-2 mb-6">
    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 text-primary mx-auto mb-1">
        <span class="material-symbols-outlined text-[26px]">storefront</span>
    </div>
    <h1 class="font-display text-2xl sm:text-3xl text-slate-900 font-extrabold tracking-tight">Register as Shop Owner</h1>
    <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto leading-relaxed">
        Join the Blax merchant network in Polomolok. List merchandise, receive custom printing orders, and manage local deliveries.
    </p>
</div>

<!-- Account Type Toggle -->
<div class="flex justify-center mb-6">
    <div class="bg-slate-100 p-1 rounded-xl flex items-center gap-1 border border-slate-200/60 w-full max-w-xs" role="tablist">
        <a href="<?= base_url('signup') ?>" 
           class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-all duration-200 flex items-center justify-center gap-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-200/50">
            <span class="material-symbols-outlined text-[17px]">shopping_bag</span>
            <span>Customer</span>
        </a>
        <a href="<?= base_url('merchant-signup') ?>" 
           class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-all duration-200 flex items-center justify-center gap-1.5 bg-white text-primary shadow-xs">
            <span class="material-symbols-outlined text-[17px]">storefront</span>
            <span>Shop Owner</span>
        </a>
    </div>
</div>

<!-- Merchant Registration Form -->
<form action="<?= base_url('merchant-signup') ?>" method="POST" enctype="multipart/form-data" class="space-y-4">

    <?= csrf_field() ?>

    <!-- Name Row (2 columns) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">

        <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block" for="merchant-first-name">First Name</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">person</span>
                <input class="w-full bg-slate-50/60 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                       id="merchant-first-name" 
                       name="first_name" 
                       placeholder="e.g. Juan" 
                       type="text" 
                       required>
            </div>
        </div>

        <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block" for="merchant-last-name">Last Name</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">person</span>
                <input class="w-full bg-slate-50/60 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                       id="merchant-last-name" 
                       name="last_name" 
                       placeholder="e.g. Dela Cruz" 
                       type="text" 
                       required>
            </div>
        </div>

    </div>

    <!-- Contact Row (Email & Phone) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">

        <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block" for="merchant-email">Email Address</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">mail</span>
                <input class="w-full bg-slate-50/60 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                       id="merchant-email" 
                       name="email" 
                       placeholder="store@example.com" 
                       type="email" 
                       required>
            </div>
        </div>

        <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block" for="merchant-shop-name">Store / Brand Name</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">store</span>
                <input class="w-full bg-slate-50/60 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                       id="merchant-shop-name" 
                       name="shop_name" 
                       placeholder="e.g. Polomolok Print Hub" 
                       type="text" 
                       required>
            </div>
        </div>

    </div>

    <!-- Location -->
    <div class="space-y-1">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block" for="merchant-location">Shop Physical Address (Polomolok)</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">location_on</span>
            <input class="w-full bg-slate-50/60 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                   id="merchant-location" 
                   name="address_line" 
                   placeholder="e.g. Purok 3, Poblacion, Polomolok" 
                   type="text">
        </div>
    </div>

    <!-- Business Permit Upload Dropzone -->
    <div class="space-y-1.5 pt-1">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block">Mayor's Permit / DTI Certificate</label>
        
        <label class="border-2 border-dashed border-slate-200 rounded-2xl p-5 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-center gap-4 cursor-pointer hover:bg-slate-100/70 hover:border-primary/50 transition-all group" id="permit-dropzone">
            <input type="file" id="business-permit" name="business_permit" class="hidden" accept=".pdf,.png,.jpg,.jpeg">
            
            <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-primary/10 text-primary shrink-0 group-hover:scale-105 transition-transform">
                <span class="material-symbols-outlined text-[26px]">upload_file</span>
            </div>

            <div class="text-center sm:text-left flex-1 min-w-0">
                <p class="text-xs sm:text-sm font-bold text-slate-800" id="permit-label">Upload Business Permit / License</p>
                <p class="text-[11px] text-slate-400 mt-0.5">Accepts PDF, PNG, or JPG (Max 10MB)</p>
            </div>

            <button type="button" class="px-3.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 pointer-events-none">
                Browse File
            </button>
        </label>
    </div>

    <!-- Password Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1">

        <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block" for="merchant-password">Password</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">lock</span>
                <input class="w-full bg-slate-50/60 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                       id="merchant-password" 
                       name="password" 
                       placeholder="••••••••" 
                       type="password" 
                       required 
                       minlength="8">
            </div>
        </div>

        <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block" for="merchant-confirm-password">Confirm Password</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">lock</span>
                <input class="w-full bg-slate-50/60 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                       id="merchant-confirm-password" 
                       name="confirm_password" 
                       placeholder="••••••••" 
                       type="password" 
                       required 
                       minlength="8">
            </div>
        </div>

    </div>

    <!-- Submit Button -->
    <button class="w-full bg-primary hover:bg-primary-container text-white font-bold py-3.5 px-4 rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs sm:text-sm uppercase tracking-wider flex items-center justify-center gap-2 mt-4" 
            type="submit">
        <span>Complete Merchant Registration</span>
        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
    </button>

    <div class="text-center pt-3 border-t border-slate-200/80">
        <p class="text-xs sm:text-sm text-slate-600">
            Already registered as a seller? <a href="<?= base_url('login?role=shop_owner') ?>" class="text-primary font-bold hover:underline">Sign in to Merchant Portal</a>
        </p>
    </div>

</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>
    (function () {
        var input = document.getElementById('business-permit');
        var label = document.getElementById('permit-label');
        var dropzone = document.getElementById('permit-dropzone');

        if (input && label) {
            input.addEventListener('change', function () {
                if (input.files.length > 0) {
                    label.textContent = input.files[0].name;
                }
            });
        }

        if (dropzone) {
            ['dragenter', 'dragover'].forEach(function (name) {
                dropzone.addEventListener(name, function (e) {
                    e.preventDefault();
                    dropzone.classList.add('border-primary', 'bg-primary/5');
                });
            });

            ['dragleave', 'drop'].forEach(function (name) {
                dropzone.addEventListener(name, function (e) {
                    e.preventDefault();
                    dropzone.classList.remove('border-primary', 'bg-primary/5');
                });
            });
        }
    })();
</script>

<?= $this->endSection() ?>