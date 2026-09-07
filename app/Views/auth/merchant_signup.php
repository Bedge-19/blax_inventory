<?= $this->extend('layouts/auth') ?>
<?= $this->section('cardClass') ?>max-w-[640px]<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Branding Header -->
<div class="text-center flex flex-col items-center gap-sm mb-md">

    <img src="<?= base_url('icon.png') ?>" alt="Blax" class="w-16 h-16 object-contain mb-md">
    <h1 class="font-display text-headline-lg text-on-surface font-bold tracking-tight">Blax Store</h1>
    <p class="text-on-surface-variant font-body-md">Register your business to start selling</p>

</div>

<!-- Account Type Toggle -->
<div class="flex justify-center mb-md">
    <div class="inline-flex items-center bg-surface-container rounded-full p-1 w-full max-w-xs shadow-sm">
        <a href="<?= base_url('signup') ?>" class="flex-1 py-xs px-sm rounded-full text-label-sm font-button text-center bg-transparent text-on-surface-variant hover:text-primary transition-all duration-200">
            <span class="inline-flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-[16px]">shopping_bag</span>
                Customer
            </span>
        </a>
        <a href="<?= base_url('merchant-signup') ?>" class="flex-1 py-xs px-sm rounded-full text-label-sm font-button text-center bg-white text-primary font-bold shadow-sm transition-all duration-200">
            <span class="inline-flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-[16px]">storefront</span>
                Shop Owner
            </span>
        </a>
    </div>
</div>

<!-- Registration Form -->
<form action="<?= base_url('merchant-signup') ?>" method="POST" enctype="multipart/form-data" class="flex flex-col gap-md">

    <?= csrf_field() ?>

    <!-- Name Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">

        <div class="flex flex-col gap-xs">

            <label class="text-label-sm font-label-sm text-on-surface-variant px-xs" for="merchant-first-name">First Name</label>
            <input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg p-md focus:ring-2 focus:ring-primary focus:border-primary transition-all" id="merchant-first-name" name="first_name" placeholder="e.g. John" type="text" required>

        </div>

        <div class="flex flex-col gap-xs">

            <label class="text-label-sm font-label-sm text-on-surface-variant px-xs" for="merchant-last-name">Last Name</label>
            <input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg p-md focus:ring-2 focus:ring-primary focus:border-primary transition-all" id="merchant-last-name" name="last_name" placeholder="e.g. Doe" type="text" required>

        </div>

    </div>

    <!-- Email Address -->
    <div class="flex flex-col gap-xs">

        <label class="text-label-sm font-label-sm text-on-surface-variant px-xs" for="merchant-email">Email Address</label>
        <div class="relative">

            <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-on-surface-variant">mail</span>
            <input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg p-md pl-[48px] focus:ring-2 focus:ring-primary focus:border-primary transition-all" id="merchant-email" name="email" placeholder="you@example.com" type="email" required>

        </div>

    </div>

    <!-- Store Name -->
    <div class="flex flex-col gap-xs">

        <label class="text-label-sm font-label-sm text-on-surface-variant px-xs" for="merchant-shop-name">Store Name</label>
        <div class="relative">

            <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-on-surface-variant">store</span>
            <input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg p-md pl-[48px] focus:ring-2 focus:ring-primary focus:border-primary transition-all" id="merchant-shop-name" name="shop_name" placeholder="Your brand name" type="text" required>

        </div>

    </div>

    <!-- Location -->
    <div class="flex flex-col gap-xs">

        <label class="text-label-sm font-label-sm text-on-surface-variant px-xs" for="merchant-location">Location of the Shop</label>
        <div class="relative">

            <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-on-surface-variant">location_on</span>
            <input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg p-md pl-[48px] focus:ring-2 focus:ring-primary focus:border-primary transition-all" id="merchant-location" name="address_line" placeholder="Search address or city" type="text">

        </div>

    </div>

    <!-- Business Permit Upload -->
    <div class="flex flex-col gap-xs">

        <label class="text-label-sm font-label-sm text-on-surface-variant px-xs">Upload Business Permit for Verification</label>

        <label class="border-2 border-dashed border-outline-variant rounded-xl p-xl bg-surface-container-low/50 flex items-center justify-center gap-sm cursor-pointer hover:bg-surface-container-high transition-all group" id="permit-dropzone">

            <input type="file" id="business-permit" name="business_permit" class="hidden" accept=".pdf,.png,.jpg,.jpeg">

            <div class="flex items-center gap-md w-full">

                <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-primary-fixed-dim text-primary shrink-0">
                    <span class="material-symbols-outlined" data-icon="upload_file">upload_file</span>
                </div>

                <div class="text-left flex-1 min-w-0">

                    <p class="font-button text-on-surface" id="permit-label">Business Permit / License</p>
                    <p class="text-label-sm text-on-surface-variant">PDF, PNG, JPG (Max. 10MB)</p>

                </div>

                <button type="button" class="px-base py-xs bg-surface-container-highest rounded-lg text-label-sm font-button hover:bg-outline-variant transition-colors">Browse</button>

            </div>

        </label>

    </div>

    <!-- Password Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">

        <div class="flex flex-col gap-xs">

            <label class="text-label-sm font-label-sm text-on-surface-variant px-xs" for="merchant-password">Password</label>
            <input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg p-md focus:ring-2 focus:ring-primary focus:border-primary transition-all" id="merchant-password" name="password" placeholder="••••••••" type="password" required>

        </div>

        <div class="flex flex-col gap-xs">

            <label class="text-label-sm font-label-sm text-on-surface-variant px-xs" for="merchant-confirm-password">Confirm Password</label>
            <input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg p-md focus:ring-2 focus:ring-primary focus:border-primary transition-all" id="merchant-confirm-password" name="confirm_password" placeholder="••••••••" type="password" required>

        </div>

    </div>

    <!-- Primary Action -->
    <button class="text-white font-button text-button h-[48px] rounded-lg mt-md shadow-lg hover:shadow-xl hover:-translate-y-0.5 active:scale-95 transition-all bg-primary" type="submit">

        Complete Registration

    </button>

    <div class="text-center mt-sm">

        <p class="text-on-surface-variant font-body-md">Already have an account? <a href="<?= base_url('login?role=shop_owner') ?>" class="text-primary font-bold hover:underline">Sign in as Shop Owner</a></p>

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
                    dropzone.classList.add('border-primary', 'bg-primary-container/10');
                });
            });

            ['dragleave', 'drop'].forEach(function (name) {
                dropzone.addEventListener(name, function (e) {
                    e.preventDefault();
                    dropzone.classList.remove('border-primary', 'bg-primary-container/10');
                });
            });
        }
    })();
</script>

<?= $this->endSection() ?>