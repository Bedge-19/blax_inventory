<?= $this->extend('layouts/auth') ?>
<?= $this->section('cardClass') ?>max-w-md<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="text-center mb-md">
    <a href="<?= base_url('/') ?>" class="inline-flex flex-col items-center gap-xs mb-xs">
        <img src="<?= base_url('icon.png') ?>" alt="Blax" class="w-16 h-16 object-contain">
    </a>
    <p class="text-title-lg text-on-surface-variant font-medium">Reset password</p>
    <p class="text-label-sm text-outline mt-xs">For testing, enter your registered email and a new password.</p>
</div>

<form class="flex flex-col gap-md" action="<?= base_url('forgot-password') ?>" method="POST">
    <?= csrf_field() ?>

    <div class="flex flex-col gap-xs">
        <label class="font-label-sm text-label-sm text-on-surface-variant" for="email">Email Address</label>
        <input class="w-full bg-surface-container-lowest border border-outline-variant rounded px-md py-md font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all placeholder:text-outline/50" id="email" name="email" placeholder="you@example.com" type="email" required>
    </div>

    <div class="flex flex-col gap-xs">
        <label class="font-label-sm text-label-sm text-on-surface-variant" for="password">New Password</label>
        <input class="w-full bg-surface-container-lowest border border-outline-variant rounded px-md py-md font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all placeholder:text-outline/50" id="password" name="password" type="password" minlength="8" required>
    </div>

    <div class="flex flex-col gap-xs">
        <label class="font-label-sm text-label-sm text-on-surface-variant" for="confirm_password">Confirm New Password</label>
        <input class="w-full bg-surface-container-lowest border border-outline-variant rounded px-md py-md font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all placeholder:text-outline/50" id="confirm_password" name="confirm_password" type="password" minlength="8" required>
    </div>

    <button class="w-full bg-primary hover:bg-primary-container text-on-primary font-button text-button rounded py-md mt-md shadow-lg hover:shadow-xl hover:-translate-y-[2px] transition-all duration-200 uppercase tracking-wider" type="submit">Change Password</button>
</form>

<?= $this->endSection() ?>

<?= $this->section('footer') ?>
<div class="text-center mt-md pt-lg border-t border-outline-variant/30">
    <a class="text-primary hover:underline font-medium" href="<?= base_url('login') ?>">Back to sign in</a>
</div>
<?= $this->endSection() ?>
