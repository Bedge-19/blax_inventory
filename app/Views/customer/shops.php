<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<?php
    $totalPages  = $totalPages ?? 1;
    $currentPage = $currentPage ?? 1;
    $totalShops  = (int) ($totalShops ?? 0);
    $searchQuery = $searchQuery ?? '';
    $category    = $category ?? '';
    $sort        = $sort ?? 'top-rated';
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

    <section class="py-xl">

        <div class="mb-xl">

            <h1 class="text-display font-display text-on-surface mb-sm">All Shops</h1>
            <p class="text-body-lg text-body-lg text-on-surface-variant">Discover trusted local merchants and professional service providers.</p>

        </div>

        <!-- Search / Filter Bar -->
        <form method="GET" action="<?= base_url('shops') ?>" class="flex flex-col md:flex-row gap-md mb-xl" role="search">

            <div class="relative flex-1">

                <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline">search</span>
                <input name="q" value="<?= esc($searchQuery) ?>" class="w-full bg-white border border-outline-variant rounded-xl py-md pl-xl pr-sm focus:ring-2 focus:ring-primary focus:outline-none text-body-md" placeholder="Search shops by name or specialty..." type="text">

            </div>

            <div class="flex gap-md">

                <select name="category" class="bg-white border border-outline-variant rounded-xl px-lg py-md text-body-md focus:ring-2 focus:ring-primary focus:outline-none">

                    <option value="">All Categories</option>
                    <option value="printing" <?= $category === 'printing' ? 'selected' : '' ?>>Printing</option>
                    <option value="enterprise" <?= $category === 'enterprise' ? 'selected' : '' ?>>Enterprise</option>

                </select>

                <select name="sort" class="bg-white border border-outline-variant rounded-xl px-lg py-md text-body-md focus:ring-2 focus:ring-primary focus:outline-none">

                    <option value="top-rated" <?= $sort === 'top-rated' ? 'selected' : '' ?>>Top Rated</option>
                    <option value="most-recent" <?= $sort === 'most-recent' ? 'selected' : '' ?>>Most Recent</option>

                </select>

            </div>

        </form>

        <!-- Shop Grid (3 Columns on Mobile) -->
        <div class="grid grid-cols-3 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-lg">

            <?php if (!empty($shops)): ?>

                <?php $i = 0; ?>

                <?php foreach ($shops as $s): ?>

                    <?php $borderIndex = $i % 3; ?>

                    <div class="bg-white p-2 sm:p-lg rounded-2xl sm:rounded-3xl text-center shadow-xs hover:shadow-lg transition-all border border-outline-variant/30 flex flex-col justify-between">

                        <div>

                            <div class="w-12 h-12 sm:w-24 sm:h-24 rounded-full mx-auto mb-1.5 sm:mb-md border-2 sm:border-4 <?= $borderColors[$borderIndex] ?> overflow-hidden bg-primary/10 flex items-center justify-center">

                                <?php if (!empty($s['logo_url'])): ?>

                                    <img class="w-full h-full object-cover" src="<?= esc(logo_url($s['logo_url'])) ?>" alt="<?= esc($s['shop_name']) ?> logo">

                                <?php else: ?>

                                    <span class="material-symbols-outlined text-primary text-xl sm:text-4xl">store</span>

                                <?php endif; ?>

                            </div>

                            <h5 class="text-xs sm:text-title-lg font-bold text-on-surface mb-0.5 sm:mb-xs truncate" title="<?= esc($s['shop_name']) ?>"><?= esc($s['shop_name']) ?></h5>

                            <div class="flex justify-center items-center gap-0.5 sm:gap-xs text-yellow-500 mb-1 sm:mb-sm">

                                <span class="material-symbols-outlined text-[12px] sm:text-[18px]" style="font-variation-settings: 'FILL' 1;">star</span>
                                <span class="text-[10px] sm:text-label-sm font-bold"><?= number_format($s['rating_average'] ?? 5.0, 1) ?> <span class="hidden sm:inline">(<?= $compactCount($s['rating_count'] ?? 0) ?>)</span></span>

                            </div>

                            <p class="text-label-sm text-on-surface-variant mb-lg line-clamp-2 hidden sm:block"><?= esc($s['description'] ?? 'Verified RHK Merchant') ?></p>

                        </div>

                        <a href="<?= base_url('shop/' . ($s['slug'] ?? $s['id'])) ?>" class="w-full py-1 sm:py-md bg-primary text-on-primary rounded-lg sm:rounded-xl font-bold text-[10px] sm:text-button hover:bg-primary/90 transition-colors inline-block mt-1">Visit Shop</a>

                    </div>

                    <?php $i++; ?>

                <?php endforeach; ?>

            <?php else: ?>

                <p class="text-on-surface-variant text-sm col-span-full">

                    <?php if ($searchQuery !== ''): ?>

                        No shops matched your search for &quot;<?= esc($searchQuery) ?>&quot;.

                    <?php else: ?>

                        No shops found.

                    <?php endif; ?>

                </p>

            <?php endif; ?>

        </div>

        <?php
            $pageUrl = function (int $p) use ($searchQuery, $category, $sort) {
                $params = ['page' => $p];
                if ($searchQuery !== '') {
                    $params['q'] = $searchQuery;
                }
                if ($category !== '') {
                    $params['category'] = $category;
                }
                if ($sort !== 'top-rated') {
                    $params['sort'] = $sort;
                }
                return base_url('shops?' . http_build_query($params));
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

            <div class="mt-xl flex justify-center">

                <nav class="flex items-center gap-sm" aria-label="Pagination">

                    <?php if ($currentPage > 1): ?>

                        <a href="<?= $pageUrl($currentPage - 1) ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors" aria-label="Previous page">

                            <span class="material-symbols-outlined">chevron_left</span>

                        </a>

                    <?php else: ?>

                        <span class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant text-outline-variant/50 cursor-not-allowed" aria-disabled="true">

                            <span class="material-symbols-outlined">chevron_left</span>

                        </span>

                    <?php endif; ?>

                    <?php foreach ($windowPages as $pg): ?>

                        <?php if ($pg === '...'): ?>

                            <span class="px-xs text-outline-variant" aria-hidden="true">...</span>

                        <?php elseif ((int) $pg === $currentPage): ?>

                            <a href="<?= $pageUrl((int) $pg) ?>" aria-current="page" class="w-10 h-10 flex items-center justify-center rounded-lg bg-primary text-on-primary font-button text-button"><?= (int) $pg ?></a>

                        <?php else: ?>

                            <a href="<?= $pageUrl((int) $pg) ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors font-button text-button"><?= (int) $pg ?></a>

                        <?php endif; ?>

                    <?php endforeach; ?>

                    <?php if ($currentPage < $totalPages): ?>

                        <a href="<?= $pageUrl($currentPage + 1) ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors" aria-label="Next page">

                            <span class="material-symbols-outlined">chevron_right</span>

                        </a>

                    <?php else: ?>

                        <span class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant text-outline-variant/50 cursor-not-allowed" aria-disabled="true">

                            <span class="material-symbols-outlined">chevron_right</span>

                        </span>

                    <?php endif; ?>

                </nav>

            </div>

        <?php endif; ?>

    </section>

</main>

<button aria-label="Go Back" onclick="window.history.back()" class="fixed bottom-lg left-lg w-16 h-16 bg-surface-container-highest text-on-surface rounded-full shadow-xl flex items-center justify-center z-50 hover:scale-105 transition-transform duration-200 border border-outline-variant">

    <span class="material-symbols-outlined text-[32px]">arrow_back</span>

</button>

<?= $this->endSection() ?>
