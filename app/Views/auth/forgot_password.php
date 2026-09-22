<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>

<!-- Card Header -->
<div class="space-y-1.5">
    <h2 class="text-2xl font-display font-extrabold text-slate-900 tracking-tight">Forgot Password</h2>
    <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
        Enter your registered email address and we will send you a secure password reset link.
    </p>

</div>

<!-- Forgot Password Form -->
<form class="space-y-4 pt-2" action="<?= base_url('forgot-password') ?>" method="POST">

    <?= csrf_field() ?>

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

    <button class="w-full bg-primary hover:bg-primary-container text-white font-bold py-3 px-4 rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs sm:text-sm uppercase tracking-wider flex items-center justify-center gap-2" 
            type="submit">
        <span>Send Reset Link</span>
        <span class="material-symbols-outlined text-[18px]">send</span>
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
