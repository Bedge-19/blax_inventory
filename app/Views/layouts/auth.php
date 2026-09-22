<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <?= view('components/head', ['title' => $title ?? 'Blax | Marketplace Authentication']) ?>
</head>

<body class="bg-[#f8fafc] text-on-surface font-body-md min-h-screen flex flex-col selection:bg-primary selection:text-white">

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- Top Marketplace Navigation Bar                                         -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <header class="bg-white/90 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            
            <!-- Brand Logo & Marketplace Badge -->
            <a href="<?= base_url('/') ?>" class="flex items-center gap-2.5 group">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center border border-primary/20 group-hover:scale-105 transition-transform">
                    <img src="<?= base_url('icon.png') ?>" alt="Blax" class="w-6 h-6 object-contain">
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] sm:text-[11px] font-extrabold uppercase bg-primary/10 text-primary border border-primary/20">
                    Polomolok Marketplace
                </span>
            </a>

            <!-- Right Navigation Actions -->
            <div class="flex items-center gap-3 sm:gap-5">
                <a href="<?= base_url('shops') ?>" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-[16px]">storefront</span>
                    <span>Browse Shops</span>
                </a>
                <div class="hidden sm:block w-px h-4 bg-slate-200"></div>
                <a href="<?= base_url('/') ?>" class="inline-flex items-center gap-1.5 text-xs font-bold text-primary hover:text-primary-container transition-colors py-1.5 px-3 rounded-lg hover:bg-primary/5">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    <span>Return to Storefront</span>
                </a>
            </div>

        </div>
    </header>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- Main Content Area                                                      -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <main class="flex-1 flex items-center justify-center py-8 sm:py-12 px-4 sm:px-6 lg:px-8">

        <?php $layoutMode = $this->renderSection('layoutMode'); ?>

        <?php if ($layoutMode === 'centered'): ?>
            <!-- Centered Layout Mode (e.g. for comprehensive forms like merchant registration) -->
            <div class="w-full max-w-2xl mx-auto">
                <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-xl shadow-slate-200/50 p-6 sm:p-10">
                    
                    <!-- Flash Messages -->
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs sm:text-sm font-medium flex items-center gap-2.5 mb-6">
                            <span class="material-symbols-outlined text-[20px] text-red-600 shrink-0">error</span>
                            <span><?= session()->getFlashdata('error') ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-medium flex items-center gap-2.5 mb-6">
                            <span class="material-symbols-outlined text-[20px] text-emerald-600 shrink-0">check_circle</span>
                            <span><?= session()->getFlashdata('success') ?></span>
                        </div>
                    <?php endif; ?>

                    <?= $this->renderSection('content') ?>
                    <?= $this->renderSection('footer') ?>

                </div>
            </div>

        <?php else: ?>
            <!-- Marketplace Split Layout (Desktop 2-Columns: Left Showcase, Right Form) -->
            <div class="w-full max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">

                <!-- Left Column: Marketplace Brand Showcase (Visible on lg+) -->
                <div class="hidden lg:flex lg:col-span-6 xl:col-span-7 flex-col justify-center gap-7 pr-4">
                    
                    <!-- Marketplace Live Status Pill -->
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-bold tracking-wide w-fit">
                        <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                        <span>POLOMOLOK MERCHANDISE & PRINTING HUB</span>
                    </div>

                    <!-- Main Headline -->
                    <div class="space-y-3">
                        <h1 class="text-3xl xl:text-4xl font-display font-extrabold text-slate-900 tracking-tight leading-tight">
                            Your Local Hub for Everyday Goods & Custom Printing
                        </h1>
                        <p class="text-slate-600 text-sm xl:text-base leading-relaxed max-w-lg">
                            Connect directly with verified local merchants across Polomolok. Order school & office supplies, submit documents for rush printing, and track delivery in real time.
                        </p>
                    </div>

                    <!-- Marketplace Benefit Highlights -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-2">
                        
                        <div class="p-3.5 rounded-2xl bg-white/80 border border-slate-200/80 shadow-2xs flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                                <span class="material-symbols-outlined text-[20px]">verified</span>
                            </div>
                            <div class="text-xs">
                                <h4 class="font-bold text-slate-800">Verified Local Shops</h4>
                                <p class="text-slate-500 mt-0.5 leading-snug">Browse trusted stores like InkMaster & PrintPixel.</p>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-white/80 border border-slate-200/80 shadow-2xs flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                                <span class="material-symbols-outlined text-[20px]">print</span>
                            </div>
                            <div class="text-xs">
                                <h4 class="font-bold text-slate-800">Rush Printing Hub</h4>
                                <p class="text-slate-500 mt-0.5 leading-snug">Direct document, apparel, and tarpaulin printing.</p>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-white/80 border border-slate-200/80 shadow-2xs flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 text-primary flex items-center justify-center shrink-0 border border-blue-100">
                                <span class="material-symbols-outlined text-[20px]">local_shipping</span>
                            </div>
                            <div class="text-xs">
                                <h4 class="font-bold text-slate-800">Doorstep Delivery</h4>
                                <p class="text-slate-500 mt-0.5 leading-snug">Fast fulfillment across Polomolok barangays.</p>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-white/80 border border-slate-200/80 shadow-2xs flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0 border border-purple-100">
                                <span class="material-symbols-outlined text-[20px]">qr_code_scanner</span>
                            </div>
                            <div class="text-xs">
                                <h4 class="font-bold text-slate-800">Secure QR Pickups</h4>
                                <p class="text-slate-500 mt-0.5 leading-snug">Instant verification upon store collection.</p>
                            </div>
                        </div>

                    </div>

                    <!-- Social Proof / Local Trust Tag -->
                    <div class="flex items-center gap-3 pt-2 text-xs text-slate-500 font-medium">
                        <div class="flex -space-x-2 overflow-hidden">
                            <span class="inline-block h-7 w-7 rounded-full ring-2 ring-white bg-primary/20 text-primary font-bold text-[10px] flex items-center justify-center">IM</span>
                            <span class="inline-block h-7 w-7 rounded-full ring-2 ring-white bg-emerald-500/20 text-emerald-700 font-bold text-[10px] flex items-center justify-center">PP</span>
                            <span class="inline-block h-7 w-7 rounded-full ring-2 ring-white bg-amber-500/20 text-amber-700 font-bold text-[10px] flex items-center justify-center">TT</span>
                            <span class="inline-block h-7 w-7 rounded-full ring-2 ring-white bg-purple-500/20 text-purple-700 font-bold text-[10px] flex items-center justify-center">FP</span>
                        </div>
                        <span>Partnered with certified merchants in South Cotabato</span>
                    </div>

                </div>

                <!-- Right Column: Elevated Authentication Form Card -->
                <div class="w-full lg:col-span-6 xl:col-span-5 max-w-md mx-auto">
                    <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-xl shadow-slate-200/50 p-6 sm:p-8 space-y-6">
                        
                        <!-- Flash Messages -->
                        <?php if (session()->getFlashdata('error')): ?>
                            <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs sm:text-sm font-medium flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-[20px] text-red-600 shrink-0">error</span>
                                <span><?= session()->getFlashdata('error') ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (session()->getFlashdata('success')): ?>
                            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-medium flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-[20px] text-emerald-600 shrink-0">check_circle</span>
                                <span><?= session()->getFlashdata('success') ?></span>
                            </div>
                        <?php endif; ?>

                        <?= $this->renderSection('content') ?>
                        <?= $this->renderSection('footer') ?>

                    </div>
                </div>

            </div>
        <?php endif; ?>

    </main>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- Minimal Marketplace Footer                                             -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <footer class="bg-white/70 border-t border-slate-200/80 py-4 px-4 sm:px-8 text-xs text-slate-500">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2.5">
            <p>&copy; <?= date('Y') ?> Blax Marketplace. All rights reserved. Polomolok, South Cotabato.</p>
            <div class="flex items-center gap-4 text-[11px] font-medium text-slate-600">
                <a href="<?= base_url('shops') ?>" class="hover:text-primary transition-colors">Merchant Directory</a>
                <span>&bull;</span>
                <a href="<?= base_url('printing-services') ?>" class="hover:text-primary transition-colors">Printing Services</a>
                <span>&bull;</span>
                <span class="text-slate-400">Secure 256-bit SSL</span>
            </div>
        </div>
    </footer>

    <?= $this->renderSection('scripts') ?>

</body>
</html>