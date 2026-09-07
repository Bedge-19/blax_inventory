<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<?php
    $totalPages  = $totalPages ?? 1;
    $currentPage = $currentPage ?? 1;
    $totalShops  = (int) ($totalShops ?? 0);
    $borderColors = ['border-primary-fixed', 'border-secondary-fixed', 'border-tertiary-fixed'];

    $compactCount = function ($n) {
        $n = (int) $n;
        if ($n >= 1000) {
            return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'k';
        }
        return number_format($n);
    };
?>

<main class="max-w-container-max mx-auto px-4 md:px-8 lg:px-10 flex-grow w-full">

    <!-- Hero Section — content managed -->
    <?php
        $sc = $siteContents ?? [];
        $psTitle = $sc['hero_title']['text_value'] ?? 'Professional Printing, Everywhere You Are.';
        $psSubtitle = $sc['hero_subtitle']['text_value'] ?? 'Every merchant on RHK General Merchandise is equipped with high-grade industrial printing technology. From document replication to custom large-format branding, we bring the print shop to your doorstep.';
        $psImage = $sc['hero_image']['image_url'] ?? 'https://lh3.googleusercontent.com/aida-public/AB6AXuALj99m6rNYpuzcI2hITJI8dWsLgwxtTRsXZh0ejlc2r4MQILYmsYyTt-zY6Q_HiME0k2Xfl7yocjXJtOjQkQBA-JOdGudDagzQCCMUTcdxRMdFyPvDffb2KVDnL15Z3f2v2LC87xuuuNdnJ7vuJpu2mWwjoKRQarHsNHEkmTpYzsueCqlQW7SNe3CCMUSOMDCyAcVrlEjNB-u6S00n9xoV5R74eo4GYVK82SIGacEAfHlj97O_MW0Hda-y5hfziEqa6yHvHaC2FbPY';
        if ($psImage && !str_starts_with($psImage,'http')) $psImage = base_url($psImage);
    ?>
    <section class="py-xxl relative overflow-hidden">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-xl items-center">

            <div class="z-10">

                <h1 class="text-display font-display text-on-surface mb-md"><?= esc($psTitle) ?></h1>
                <p class="text-body-lg text-on-surface-variant mb-xl max-w-lg"><?= esc($psSubtitle) ?></p>

                <div class="flex gap-md">

                    <a href="<?= base_url('shops') ?>" class="bg-primary text-on-primary px-xl py-md rounded-xl font-button shadow-md hover:translate-y-[-2px] transition-all duration-200 inline-block">Start a Printing Request</a>
                    <a href="#master-printers" class="border border-outline-variant text-on-surface px-xl py-md rounded-xl font-button hover:bg-surface-container-low transition-all inline-block">Browse Printing Shops</a>

                </div>

            </div>

            <div class="relative">

                <div class="glass-card rounded-[32px] p-sm rotate-2 shadow-xl">

                    <img class="rounded-[24px] w-full h-[480px] object-cover" alt="Printing supplies" src="<?= esc($psImage) ?>">

                </div>

                <div class="absolute -bottom-md -left-md sm:-bottom-lg sm:-left-lg glass-card p-lg sm:p-xl rounded-xl shadow-lg border border-primary/20 max-w-[240px]">

                    <span class="text-primary font-bold text-headline-md block"><?= $totalShops ?>+</span>
                    <span class="text-label-sm text-on-surface-variant">Certified Printing Partners across the local network.</span>

                </div>

            </div>

        </div>

    </section>

    <!-- How it Works -->
    <section class="py-xxl">

        <div class="text-center mb-xxl">

            <h2 class="text-headline-lg font-headline-lg text-on-surface">Seamless From File to Finish</h2>
            <p class="text-body-md text-on-surface-variant">Our integrated workflow makes professional printing effortless.</p>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-xl relative">

            <!-- Connector Line -->
            <div class="hidden md:block absolute top-12 left-0 w-full h-[2px] bg-outline-variant/30 z-0"></div>

            <div class="relative z-10 flex flex-col items-center text-center">

                <div class="w-16 h-16 rounded-full bg-surface-container-highest border-4 border-background flex items-center justify-center text-primary shadow-sm mb-lg">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">storefront</span>
                </div>
                <h4 class="text-title-lg font-title-lg mb-sm">1. Choose a Shop</h4>
                <p class="text-label-sm text-on-surface-variant px-md">Filter by location, equipment type, or verified rating.</p>

            </div>

            <div class="relative z-10 flex flex-col items-center text-center">

                <div class="w-16 h-16 rounded-full bg-surface-container-highest border-4 border-background flex items-center justify-center text-primary shadow-sm mb-lg">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">upload_file</span>
                </div>
                <h4 class="text-title-lg font-title-lg mb-sm">2. Upload Files</h4>
                <p class="text-label-sm text-on-surface-variant px-md">Securely upload PDFs, high-res images, or CAD files.</p>

            </div>

            <div class="relative z-10 flex flex-col items-center text-center">

                <div class="w-16 h-16 rounded-full bg-surface-container-highest border-4 border-background flex items-center justify-center text-primary shadow-sm mb-lg">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">shopping_cart_checkout</span>
                </div>
                <h4 class="text-title-lg font-title-lg mb-sm">3. Place Order</h4>
                <p class="text-label-sm text-on-surface-variant px-md">Configure options and pay through our secure checkout.</p>

            </div>

            <div class="relative z-10 flex flex-col items-center text-center">

                <div class="w-16 h-16 rounded-full bg-primary-container border-4 border-background flex items-center justify-center text-on-primary-container shadow-md mb-lg">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">local_shipping</span>
                </div>
                <h4 class="text-title-lg font-title-lg mb-sm">4. Pickup or Delivery</h4>
                <p class="text-label-sm text-on-surface-variant px-md">Get it delivered to your door or pick up in-store same-day.</p>

            </div>

        </div>

    </section>

    <!-- Master Printers -->
    <section id="master-printers" class="py-xl bg-surface-container-low rounded-[40px] px-lg">

        <div class="flex justify-between items-end mb-xl">

            <div>

                <h2 class="text-headline-lg font-headline-lg text-on-surface">Master Printers</h2>
                <p class="text-body-md text-on-surface-variant">Top-rated shops specializing in advanced printing techniques.</p>

            </div>

            <a href="<?= base_url('shops') ?>" class="text-primary font-button flex items-center gap-sm hover:underline">
                View all <?= $totalShops ?>+ shops <span class="material-symbols-outlined">arrow_forward</span>
            </a>

        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-lg">

            <?php if (!empty($shops)): ?>

                <?php $i = 0; ?>

                <?php foreach ($shops as $s): ?>

                    <?php $borderIndex = $i % 3; ?>

                    <div class="bg-white p-lg rounded-3xl text-center shadow-sm hover:shadow-lg transition-all transform hover:-translate-y-1">

                        <div class="w-24 h-24 rounded-full mx-auto mb-md border-4 <?= $borderColors[$borderIndex] ?> overflow-hidden">

                            <?php if (!empty($s['logo_url'])): ?>

                                <img class="w-full h-full object-cover" src="<?= esc(logo_url($s['logo_url'])) ?>" alt="<?= esc($s['shop_name']) ?>">

                            <?php else: ?>

                                <div class="w-full h-full bg-primary/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary text-4xl">print</span>
                                </div>

                            <?php endif; ?>

                        </div>

                        <h5 class="text-title-lg font-bold"><?= esc($s['shop_name']) ?></h5>

                        <div class="flex justify-center items-center gap-xs text-yellow-500 mb-sm">

                            <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="text-label-sm font-bold"><?= number_format($s['rating_average'] ?? 5.0, 1) ?> (<?= $compactCount($s['rating_count'] ?? 0) ?>)</span>

                        </div>

                        <p class="text-label-sm text-on-surface-variant mb-lg"><?= esc($s['description'] ?? 'Specialists in high-quality printing services.') ?></p>

                        <a href="<?= base_url('shop/' . ($s['slug'] ?? $s['id'])) ?>" class="block w-full py-sm border border-outline rounded-lg text-label-sm font-bold hover:bg-surface-container transition-colors text-center">Visit Shop</a>

                    </div>

                    <?php $i++; ?>

                <?php endforeach; ?>

            <?php else: ?>

                <p class="text-on-surface-variant text-sm col-span-full">No printing service providers available currently.</p>

            <?php endif; ?>

        </div>

        <?php
            $pageUrl = function (int $p) {
                return base_url('printing-services?page=' . $p);
            };

            $windowPages = [];
            if ($totalPages <= 7) {
                $windowPages = range(1, $totalPages);
            } else {
                $windowPages = [1];
                $left  = max(2, $currentPage - 2);
                $right = min($totalPages - 1, $currentPage + 2);
                if ($left > 2) {
                    $windowPages[] = '...';
                }
                for ($pg = $left; $pg <= $right; $pg++) {
                    $windowPages[] = $pg;
                }
                if ($right < $totalPages - 1) {
                    $windowPages[] = '...';
                }
                $windowPages[] = $totalPages;
            }
        ?>

        <?php if ($totalPages > 1): ?>

            <div class="flex justify-center items-center gap-md mt-xl pb-lg">

                <?php if ($currentPage > 1): ?>

                    <a href="<?= $pageUrl($currentPage - 1) ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors" aria-label="Previous page">

                        <span class="material-symbols-outlined">chevron_left</span>

                    </a>

                <?php else: ?>

                    <button disabled class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors disabled:opacity-30" aria-disabled="true">

                        <span class="material-symbols-outlined">chevron_left</span>

                    </button>

                <?php endif; ?>

                <div class="flex items-center gap-sm">

                    <?php foreach ($windowPages as $pg): ?>

                        <?php if ($pg === '...'): ?>

                            <span class="px-xs text-outline-variant" aria-hidden="true">...</span>

                        <?php elseif ((int) $pg === $currentPage): ?>

                            <a href="<?= $pageUrl((int) $pg) ?>" aria-current="page" class="w-10 h-10 flex items-center justify-center rounded-lg bg-primary text-on-primary font-button shadow-sm"><?= (int) $pg ?></a>

                        <?php else: ?>

                            <a href="<?= $pageUrl((int) $pg) ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors font-button"><?= (int) $pg ?></a>

                        <?php endif; ?>

                    <?php endforeach; ?>

                </div>

                <?php if ($currentPage < $totalPages): ?>

                    <a href="<?= $pageUrl($currentPage + 1) ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors" aria-label="Next page">

                        <span class="material-symbols-outlined">chevron_right</span>

                    </a>

                <?php else: ?>

                    <button disabled class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors disabled:opacity-30" aria-disabled="true">

                        <span class="material-symbols-outlined">chevron_right</span>

                    </button>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </section>

</main>

<button aria-label="Go Back" onclick="window.history.back()" class="fixed bottom-lg left-margin w-16 h-16 bg-surface-container-highest text-on-surface rounded-full shadow-xl flex items-center justify-center z-50 hover:scale-105 transition-transform duration-200">

    <span class="material-symbols-outlined text-[32px]">arrow_back</span>

</button>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('button');
    buttons.forEach(btn => {
        btn.addEventListener('mousedown', () => {
            btn.classList.add('scale-95');
        });
        btn.addEventListener('mouseup', () => {
            btn.classList.remove('scale-95');
        });
        btn.addEventListener('mouseleave', () => {
            btn.classList.remove('scale-95');
        });
    });
});
</script>

<?= $this->endSection() ?>
