<?= $this->extend('layouts/auth') ?>
<?= $this->section('cardClass') ?>max-w-lg<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Header -->
<div class="text-center flex flex-col gap-sm mb-md">

    <a href="<?= base_url('/') ?>" class="inline-flex flex-col items-center gap-xs mx-auto">
        <img src="<?= base_url('icon.png') ?>" alt="Blax" class="w-16 h-16 object-contain">
    </a>
    <p class="font-body-lg text-body-lg text-on-surface-variant">Create your account</p>

</div>

<!-- Account Type Toggle -->
<div class="flex justify-center mb-md">
    <div class="inline-flex items-center bg-surface-container rounded-full p-1 w-full max-w-xs shadow-sm">
        <a href="<?= base_url('signup') ?>" class="flex-1 py-xs px-sm rounded-full text-label-sm font-button text-center bg-white text-primary font-bold shadow-sm transition-all duration-200">
            <span class="inline-flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-[16px]">shopping_bag</span>
                Customer
            </span>
        </a>
        <a href="<?= base_url('merchant-signup') ?>" class="flex-1 py-xs px-sm rounded-full text-label-sm font-button text-center bg-transparent text-on-surface-variant hover:text-primary transition-all duration-200">
            <span class="inline-flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-[16px]">storefront</span>
                Shop Owner
            </span>
        </a>
    </div>
</div>

<!-- Form -->
<form action="<?= base_url('signup') ?>" method="POST" class="flex flex-col gap-md">

    <?= csrf_field() ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-sm">

        <div class="flex flex-col gap-xs">

            <label class="font-label-sm text-label-sm text-on-surface-variant font-medium" for="first-name">First Name</label>
            <input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-sm font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" id="first-name" name="first_name" placeholder="John" required type="text">

        </div>

        <div class="flex flex-col gap-xs">

            <label class="font-label-sm text-label-sm text-on-surface-variant font-medium" for="last-name">Last Name</label>
            <input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-sm font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" id="last-name" name="last_name" placeholder="Doe" required type="text">

        </div>

    </div>

    <div class="flex flex-col gap-xs">

        <label class="font-label-sm text-label-sm text-on-surface-variant font-medium" for="email">Email Address</label>
        <input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-sm font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" id="email" name="email" placeholder="you@example.com" required type="email">

    </div>

    <div class="flex flex-col gap-xs">

        <label class="font-label-sm text-label-sm text-on-surface-variant font-medium" for="phone">Cellphone Number</label>
        <input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-sm font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" id="phone" name="phone" placeholder="0912 345 6789" type="tel">

    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-sm">

        <div class="flex flex-col gap-xs">

            <label class="font-label-sm text-label-sm text-on-surface-variant font-medium" for="password">Password</label>
            <input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-sm font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" id="password" name="password" placeholder="••••••••" required type="password">

        </div>

        <div class="flex flex-col gap-xs">

            <label class="font-label-sm text-label-sm text-on-surface-variant font-medium" for="confirm-password">Confirm Password</label>
            <input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-sm font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" id="confirm-password" name="confirm_password" placeholder="••••••••" required type="password">

        </div>

    </div>

    <button class="w-full bg-primary hover:bg-primary-fixed-variant text-on-primary font-button text-button rounded-lg py-md mt-sm shadow-[0_1px_3px_rgba(0,0,0,0.1)] hover:shadow-[0_4px_6px_-1px_rgba(0,0,0,0.1)] hover:-translate-y-[1px] transition-all duration-200 text-body-md" type="submit">

        Create Account

    </button>

    <div class="text-center mt-sm">

        <p class="text-on-surface-variant font-body-md text-sm">Already have an account? <a href="<?= base_url('login') ?>" class="text-primary font-medium hover:underline">Sign in</a></p>

    </div>

</form>

<?= $this->endSection() ?>