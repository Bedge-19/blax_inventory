<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="p-md rounded-xl bg-green-100 text-green-800 text-sm font-medium mb-lg"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="p-md rounded-xl bg-error-container text-on-error-container text-sm font-medium mb-lg"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="flex flex-1 flex-col md:flex-row w-full min-h-[calc(100vh-72px)] bg-slate-50/50">

    <?= view('components/profile_sidebar', ['activeNav' => 'favorites']) ?>

    <!-- Main Content Area -->
    <main class="flex-1 p-4 md:p-8 lg:p-10 overflow-y-auto">

        <div class="max-w-6xl mx-auto">

            <div class="mb-xl">

                <h1 class="font-headline-lg text-headline-lg text-on-surface mb-sm">Favorite Shops</h1>
                <p class="font-body-md text-body-md text-on-surface-variant max-w-2xl">Manage and quickly access your most-visited local merchants.</p>

            </div>

            <?php if (!empty($shops)): ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">

                    <?php foreach ($shops as $fav): ?>

                        <?php
                            $shopSlug    = $fav['slug'] ?? $fav['shop_id'];
                            $shopUrl     = base_url('shop/' . $shopSlug);
                            $shopRating  = number_format((float) ($fav['rating_average'] ?? 0), 1);
                            $shopDesc    = $fav['description'] ?? 'Verified Merchant';
                        ?>

                        <div class="group bg-surface-container-lowest border border-outline-variant rounded-xl p-lg flex flex-col gap-md transition-all duration-200 hover:-translate-y-1 hover:shadow-md">

                            <div class="flex justify-between items-start">

                                <div class="w-16 h-16 rounded-full overflow-hidden bg-surface-container shadow-sm border border-outline-variant flex items-center justify-center">

                                    <?php if (!empty($fav['logo_url'])): ?>

                                        <img class="w-full h-full object-cover" src="<?= esc(logo_url($fav['logo_url'])) ?>" alt="<?= esc($fav['shop_name']) ?> logo">

                                    <?php else: ?>

                                        <span class="material-symbols-outlined text-3xl text-primary">store</span>

                                    <?php endif; ?>

                                </div>

                                <form method="post" action="<?= base_url('customer/favorites/remove') ?>" onsubmit="return confirm('Remove this shop from your favorites?');">

                                    <?= csrf_field() ?>
                                    <input type="hidden" name="shop_id" value="<?= esc($fav['shop_id'] ?? $fav['id']) ?>">

                                    <button type="submit" class="p-2 rounded-full text-error hover:bg-error-container transition-colors" title="Remove from favorites" aria-label="Remove from favorites">

                                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">favorite</span>

                                    </button>

                                </form>

                            </div>

                            <div>

                                <div class="flex items-center justify-between mb-xs">

                                    <h3 class="font-title-lg text-title-lg text-on-surface"><?= esc($fav['shop_name']) ?></h3>

                                    <div class="flex items-center gap-xs text-on-surface-variant">

                                        <span class="material-symbols-outlined text-[18px] text-yellow-500" style="font-variation-settings: 'FILL' 1;">star</span>
                                        <span class="text-label-sm font-semibold"><?= esc($shopRating) ?></span>

                                    </div>

                                </div>

                                <p class="text-label-sm text-secondary"><?= esc($shopDesc) ?></p>

                            </div>

                            <div class="mt-auto flex items-center gap-sm">

                                <a href="<?= esc($shopUrl) ?>" class="flex-1 bg-primary text-on-primary font-button text-button py-2.5 rounded-lg text-center block hover:opacity-90 transition-opacity">Visit Shop</a>

                                <button type="button" class="fav-share px-3 py-2.5 border border-outline-variant rounded-lg hover:bg-surface-container-high transition-colors" data-url="<?= esc($shopUrl) ?>" title="Share this shop" aria-label="Share this shop">

                                    <span class="material-symbols-outlined text-on-surface-variant">share</span>

                                </button>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="glass-card rounded-2xl border border-outline-variant/30 p-xxl text-center text-on-surface-variant mb-lg flex flex-col items-center">
                    <div class="w-20 h-20 rounded-full bg-error-container/30 text-error flex items-center justify-center mb-md">
                        <span class="material-symbols-outlined text-4xl">favorite</span>
                    </div>
                    <h2 class="text-title-lg font-bold text-on-surface mb-xs">No favorite shops yet</h2>
                    <p class="text-body-md text-on-surface-variant max-w-md mb-lg">When browsing merchant stores, tap the heart icon to bookmark your favorite shops for one-tap access.</p>
                    <a href="<?= base_url('shops') ?>" class="inline-flex items-center gap-xs bg-primary text-on-primary px-lg py-md rounded-xl font-button hover:bg-primary-container transition-all shadow-sm">
                        <span class="material-symbols-outlined text-[20px]">store</span>
                        <span>Browse Verified Shops</span>
                    </a>
                </div>

            <?php endif; ?>

            <div class="mt-xxl bg-secondary-container rounded-2xl p-xl flex flex-col md:flex-row items-center gap-lg border border-outline-variant">

                <div class="p-lg bg-surface-container-lowest rounded-full">

                    <span class="material-symbols-outlined text-primary text-[48px]">explore</span>

                </div>

                <div class="flex-1 text-center md:text-left">

                    <h4 class="font-headline-md text-headline-md text-on-secondary-container mb-xs">Discover more shops</h4>
                    <p class="font-body-md text-body-md text-on-secondary-container opacity-80">Explore thousands of highly-rated merchants across the marketplace ecosystem.</p>

                </div>

                <a href="<?= base_url('shops') ?>" class="bg-primary text-on-primary px-lg py-md rounded-lg font-button text-button shadow-sm hover:translate-y-[-2px] transition-all">Explore Marketplace</a>

            </div>

        </div>

    </main>

</div>



<script>
    (function () {
        document.querySelectorAll('.fav-share').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url = btn.dataset.url;
                if (navigator.share) {
                    navigator.share({ title: 'Shop', url: url }).catch(function () {});
                } else if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(function () {
                        var label = btn.querySelector('span');
                        label.textContent = 'check';
                        setTimeout(function () { label.textContent = 'share'; }, 1500);
                    }).catch(function () {});
                } else {
                    window.prompt('Copy shop link:', url);
                }
            });
        });
    })();
</script>

<?= $this->endSection() ?>