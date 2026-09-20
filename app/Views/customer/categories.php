<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<?php
    $totalPages  = $totalPages ?? 1;
    $currentPage = $currentPage ?? 1;
    $trending    = $trending ?? [];
?>

<main class="flex-grow w-full max-w-container-max mx-auto px-4 md:px-8 lg:px-10 py-8 md:py-xl flex flex-col gap-8 md:gap-12 lg:gap-[80px]">

    <!-- Header Section -->
    <section class="flex flex-col gap-md">

        <nav aria-label="Breadcrumb" class="flex text-label-sm font-label-sm text-on-surface-variant">

            <ol class="inline-flex items-center space-x-1 md:space-x-3">

                <li class="inline-flex items-center">

                    <a class="inline-flex items-center hover:text-primary transition-colors" href="<?= base_url('/') ?>">
                        Home
                    </a>

                </li>

                <li>

                    <div class="flex items-center">

                        <span class="material-symbols-outlined text-sm mx-1">chevron_right</span>
                        <span class="text-on-surface ml-1 md:ml-2 font-medium">Categories</span>

                    </div>

                </li>

            </ol>

        </nav>

        <h1 class="text-headline-lg font-headline-lg text-on-surface">Explore Categories</h1>
        <p class="text-body-lg font-body-lg text-on-surface-variant max-w-2xl">Browse our extensive collection of high-quality merchandise, carefully curated to meet your everyday needs.</p>

    </section>

    <!-- Trending Categories (Distinct Mobile Rail vs Desktop Bento) -->
    <?php if (!empty($trending)): ?>

        <section class="flex flex-col gap-sm sm:gap-lg">

            <div class="flex items-center justify-between">

                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">trending_up</span>
                    <h2 class="text-title-lg font-title-lg text-on-surface">Trending Categories</h2>
                </div>

                <a href="#all-categories" class="text-primary-container hover:text-primary text-label-sm sm:text-button font-button flex items-center gap-1 transition-colors">
                    <span>View All</span> <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>

            </div>

            <!-- Mobile: Horizontal snap rail. Desktop: Bento grid -->
            <div class="flex overflow-x-auto no-scrollbar snap-x snap-mandatory gap-3 pb-2 md:grid md:grid-cols-12 md:gap-gutter h-auto md:min-h-[400px]">

                <?php if (isset($trending[0])): ?>

                    <!-- Main Trending Card -->
                    <a href="<?= base_url('category/' . ($trending[0]['slug'] ?? $trending[0]['id'])) ?>" class="w-64 sm:w-72 md:w-auto shrink-0 snap-start md:col-span-8 rounded-2xl overflow-hidden relative group cursor-pointer border border-outline-variant/20 shadow-sm hover:shadow-md transition-all duration-300">

                        <div class="bg-cover bg-center w-full h-full min-h-[220px] md:min-h-[300px] group-hover:scale-105 transition-transform duration-700" style="background-image: url('<?= esc($trending[0]['image_url']) ?>');"></div>

                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent"></div>

                        <div class="absolute bottom-0 left-0 p-3 sm:p-lg w-full flex justify-between items-end">

                            <div>

                                <span class="px-2 py-0.5 bg-primary text-on-primary text-[10px] sm:text-xs font-bold rounded-md mb-1 sm:mb-2 inline-flex items-center gap-1 shadow-sm">
                                    <span class="material-symbols-outlined text-[12px]">local_fire_department</span>
                                    #1 Trending
                                </span>
                                <h3 class="text-title-md sm:text-headline-md font-bold text-white"><?= esc($trending[0]['name']) ?></h3>
                                <p class="text-xs sm:text-body-md text-white/80 mt-0.5"><?= (int) ($trending[0]['product_count'] ?? 0) ?> items available</p>

                            </div>

                            <span class="bg-white/20 backdrop-blur-md text-white p-1.5 sm:p-2 rounded-full hover:bg-white hover:text-primary transition-colors shrink-0">
                                <span class="material-symbols-outlined text-sm sm:text-base">arrow_outward</span>
                            </span>

                        </div>

                    </a>

                <?php endif; ?>

                <?php if (isset($trending[1]) || isset($trending[2])): ?>

                    <!-- Secondary Trending Items -->
                    <div class="flex gap-3 md:contents">

                        <?php for ($ti = 1; $ti <= 3; $ti++): ?>

                            <?php if (isset($trending[$ti])): ?>

                                <a href="<?= base_url('category/' . ($trending[$ti]['slug'] ?? $trending[$ti]['id'])) ?>" class="w-56 sm:w-64 md:w-auto shrink-0 snap-start md:col-span-4 rounded-2xl overflow-hidden relative group cursor-pointer border border-outline-variant/20 shadow-sm hover:shadow-md transition-all duration-300">

                                    <div class="bg-cover bg-center w-full h-full min-h-[220px] md:min-h-[188px] group-hover:scale-105 transition-transform duration-700" style="background-image: url('<?= esc($trending[$ti]['image_url']) ?>');"></div>

                                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-transparent"></div>

                                    <div class="absolute bottom-0 left-0 p-3 sm:p-md w-full">

                                        <span class="px-1.5 py-0.5 bg-secondary text-white text-[9px] font-bold rounded mb-1 inline-block">Trending</span>
                                        <h3 class="text-sm sm:text-title-lg font-bold text-white"><?= esc($trending[$ti]['name']) ?></h3>
                                        <p class="text-[11px] sm:text-label-sm text-white/80"><?= (int) ($trending[$ti]['product_count'] ?? 0) ?> items</p>

                                    </div>

                                </a>

                            <?php endif; ?>

                        <?php endfor; ?>

                    </div>

                <?php endif; ?>

            </div>

        </section>

    <?php endif; ?>

    <!-- All Categories Grid (3 Columns on Mobile) -->
    <section id="all-categories" class="flex flex-col gap-sm sm:gap-lg">

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">grid_view</span>
                <h2 class="text-title-lg font-title-lg text-on-surface">All Categories</h2>
            </div>
            <span class="text-xs text-on-surface-variant font-medium"><?= count($categories ?? []) ?> categories</span>
        </div>

        <div class="grid grid-cols-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-gutter">

            <?php if (!empty($categories)): ?>

                <?php foreach ($categories as $cat): ?>

                    <a class="group flex flex-col bg-surface-container-lowest rounded-xl border border-outline-variant/30 overflow-hidden shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-300" href="<?= base_url('category/' . ($cat['slug'] ?? $cat['id'])) ?>">

                        <div class="aspect-[4/3] sm:aspect-video w-full overflow-hidden relative bg-surface-container-low">

                            <?php if (!empty($cat['image_url'])): ?>

                                <div class="bg-cover bg-center w-full h-full group-hover:scale-110 transition-transform duration-500" style="background-image: url('<?= esc($cat['image_url']) ?>');"></div>

                            <?php else: ?>

                                <div class="w-full h-full bg-surface-container flex items-center justify-center">

                                    <span class="material-symbols-outlined text-primary text-2xl sm:text-4xl">category</span>

                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="p-1.5 sm:p-md flex items-center justify-between">

                            <span class="text-[11px] sm:text-body-md font-bold text-on-surface group-hover:text-primary transition-colors truncate w-full text-center sm:text-left"><?= esc($cat['name']) ?></span>
                            <span class="material-symbols-outlined text-outline group-hover:text-primary transition-colors transform group-hover:translate-x-1 hidden sm:block">arrow_forward</span>

                        </div>

                    </a>

                <?php endforeach; ?>

            <?php else: ?>

                <p class="text-on-surface-variant text-sm col-span-full">No categories available.</p>

            <?php endif; ?>

        </div>

        <?php
            $pageUrl = function (int $p) {
                return base_url('categories?page=' . $p);
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

            <div class="flex justify-center items-center gap-md mt-xl">

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

<button aria-label="Go Back" onclick="window.history.back()" class="fixed bottom-8 left-8 w-16 h-16 bg-surface-container-highest text-on-surface rounded-full shadow-lg hover:shadow-xl hover:bg-surface-container-high transition-all duration-300 flex items-center justify-center z-50 group active:scale-95 border border-outline-variant/30">

    <span class="material-symbols-outlined text-[32px] transition-transform group-hover:scale-110">arrow_back</span>

</button>

<?= $this->endSection() ?>
