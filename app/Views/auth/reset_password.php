<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>

<!-- Card Header -->
<div class="space-y-1.5">
    <h2 class="text-2xl font-display font-extrabold text-slate-900 tracking-tight">Set New Password</h2>
    <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
        Please choose a secure new password of at least 8 characters.
    </p>

</div>

<!-- Reset Password Form -->
<form class="space-y-4 pt-2" action="<?= base_url('reset-password/' . esc($token)) ?>" method="POST">

    <?= csrf_field() ?>

    <div class="space-y-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block" for="password">New Password</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[19px] pointer-events-none">lock</span>
            <input class="w-full bg-slate-50/60 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                   id="password" 
                   name="password" 
                   type="password" 
                   minlength="8" 
                   placeholder="Min. 8 characters"
                   required 
                   autofocus>
        </div>
    </div>

    <div class="space-y-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block" for="confirm_password">Confirm New Password</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[19px] pointer-events-none">lock</span>
            <input class="w-full bg-slate-50/60 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                   id="confirm_password" 
                   name="confirm_password" 
                   type="password" 
                   minlength="8" 
                   placeholder="Repeat new password"
                   required>
        </div>
    </div>

    <button class="w-full bg-primary hover:bg-primary-container text-white font-bold py-3 px-4 rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs sm:text-sm uppercase tracking-wider flex items-center justify-center gap-2" 
            type="submit">
        <span>Update Password</span>
        <span class="material-symbols-outlined text-[18px]">key</span>
    </button>

</form>

<?= $this->endSection() ?>

<?= $this->section('footer') ?>

<div class="pt-4 border-t border-slate-200/80 text-center">
    <a class="text-xs sm:text-sm text-primary hover:underline font-bold inline-flex items-center gap-1" href="<?= base_url('login') ?>">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        <span>Back to sign in</span>
    </a>
</div>

<?= $this->endSection() ?>
