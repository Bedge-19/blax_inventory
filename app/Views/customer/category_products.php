<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<?php
    $totalPages  = $totalPages ?? 1;
    $currentPage = $currentPage ?? 1;
    $totalProducts = (int) ($totalProducts ?? 0);
    $categorySlug = $category['slug'] ?? ($category['id'] ?? '');
    $categoryName = $category['name'] ?? 'Category Products';
?>

<main class="max-w-container-max mx-auto px-4 md:px-8 lg:px-10 flex-grow w-full">

    <!-- Breadcrumbs -->
    <nav aria-label="Breadcrumb" class="flex items-center gap-xs text-label-sm font-label-sm text-on-surface-variant mb-xl">

        <a class="hover:text-primary transition-colors" href="<?= base_url('/') ?>">Home</a>
        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
        <a class="hover:text-primary transition-colors" href="<?= base_url('categories') ?>">Categories</a>
        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
        <span class="text-on-surface font-semibold"><?= esc($categoryName) ?></span>

    </nav>

    <div class="flex flex-col lg:flex-row gap-gutter">

        <!-- Main Content Area -->
        <div class="flex-1">

            <div class="flex flex-col md:flex-row md:items-end justify-between mb-xl gap-md">

                <div>

                    <h1 class="font-headline-lg text-headline-lg text-on-surface"><?= esc($categoryName) ?></h1>
                    <p class="font-body-md text-body-md text-on-surface-variant mt-xs">Curated collection of professional writing essentials.</p>

                </div>

                <div class="flex items-center gap-md">

                    <span class="font-label-sm text-label-sm text-on-surface-variant">Sort by: <span class="text-on-surface font-semibold">Most Relevant</span></span>
                    <span class="material-symbols-outlined text-on-surface-variant cursor-pointer">tune</span>

                </div>

            </div>

            <!-- Product Grid (3 Columns on Mobile) -->
            <div class="grid grid-cols-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-gutter">

                <?php if (!empty($products)): ?>

                    <?php foreach ($products as $p): ?>

                        <?php
                            $imageUrl = product_image_url($p['image_url'] ?? null);
                        ?>

                        <div class="group bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 flex flex-col justify-between">

                            <div class="relative aspect-square overflow-hidden bg-surface-container-low">

                                <a href="<?= base_url('product/' . $p['id']) ?>" class="block w-full h-full">

                                    <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="<?= esc($imageUrl) ?>" alt="<?= esc($p['name']) ?>">

                                </a>

                                <?php if (!empty($p['is_bestseller'])): ?>

                                    <span class="absolute top-1 left-1 sm:top-md sm:left-md bg-primary-container text-on-primary px-1.5 py-0.5 sm:px-sm sm:py-xs rounded-full text-[9px] sm:text-label-sm font-label-sm shadow-sm">Hot</span>

                                <?php endif; ?>

                                <div class="absolute top-1 right-1 sm:top-md sm:right-md">

                                    <button type="button" class="w-6 h-6 sm:w-10 sm:h-10 rounded-full glass-card flex items-center justify-center text-on-surface hover:text-error active:scale-90 transition-all hover:scale-105 duration-300" aria-label="Add to favorites">

                                        <span class="material-symbols-outlined text-[13px] sm:text-base">favorite</span>

                                    </button>

                                </div>

                            </div>

                            <div class="p-1.5 sm:p-md flex flex-col flex-grow justify-between">

                                <a href="<?= base_url('product/' . $p['id']) ?>" class="font-bold text-[11px] sm:text-title-lg text-on-surface mb-0.5 sm:mb-xs line-clamp-2 block group-hover:text-primary transition-colors leading-tight"><?= esc($p['name']) ?></a>

                                <div class="flex items-center justify-between mt-1 sm:mt-md pt-1 border-t border-outline-variant/10">

                                    <span class="font-bold text-xs sm:text-headline-md text-primary">₱<?= number_format($p['price'], 2) ?></span>

                                    <form action="<?= base_url('cart/add') ?>" method="POST">

                                        <?= csrf_field() ?>

                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="quantity" value="1">

                                        <button type="submit" aria-label="Add to cart" class="w-6 h-6 sm:w-10 sm:h-10 bg-primary text-on-primary rounded-md sm:rounded-lg flex items-center justify-center hover:bg-primary-fixed-dim transition-colors hover:scale-105 transition-all duration-300">

                                            <span class="material-symbols-outlined text-[13px] sm:text-base">shopping_cart</span>

                                        </button>

                                    </form>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <!-- Empty State -->
                    <div class="col-span-full flex flex-col items-center text-center p-xl bg-surface-container-lowest rounded-xl border border-outline-variant/30">

                        <div class="w-16 h-16 bg-error-container text-on-error-container rounded-full flex items-center justify-center mb-md">

                            <span class="material-symbols-outlined text-3xl">warning</span>

                        </div>

                        <h3 class="text-title-lg font-title-lg text-on-surface mb-xs">No Products in Category</h3>

                        <p class="text-body-md text-on-surface-variant max-w-md mb-md">No products were found in this category in the database.</p>

                        <a href="<?= base_url('/') ?>" class="inline-block bg-primary text-on-primary px-lg py-md rounded-lg font-button text-button">Return Home</a>

                    </div>

                <?php endif; ?>

            </div>

            <?php
                $pageUrl = function (int $pg) use ($categorySlug) {
                    return base_url('category/' . $categorySlug . '?page=' . $pg);
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

                <div class="col-span-full py-xl flex justify-center border-t border-outline-variant mt-xl">

                    <nav class="flex items-center gap-xs" aria-label="Pagination">

                        <?php if ($currentPage > 1): ?>

                            <a href="<?= $pageUrl($currentPage - 1) ?>" class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-surface-container-high text-on-surface-variant transition-colors hover:scale-105 transition-all duration-300" aria-label="Previous page">

                                <span class="material-symbols-outlined">chevron_left</span>

                            </a>

                        <?php else: ?>

                            <button type="button" disabled class="w-10 h-10 flex items-center justify-center rounded-lg text-on-surface-variant disabled:opacity-30 cursor-not-allowed" aria-disabled="true">

                                <span class="material-symbols-outlined">chevron_left</span>

                            </button>

                        <?php endif; ?>

                        <?php foreach ($windowPages as $pg): ?>

                            <?php if ($pg === '...'): ?>

                                <span class="w-10 h-10 flex items-center justify-center text-on-surface-variant" aria-hidden="true">...</span>

                            <?php elseif ((int) $pg === $currentPage): ?>

                                <a href="<?= $pageUrl((int) $pg) ?>" aria-current="page" class="w-10 h-10 flex items-center justify-center rounded-lg bg-primary text-on-primary font-bold hover:scale-105 transition-all duration-300"><?= (int) $pg ?></a>

                            <?php else: ?>

                                <a href="<?= $pageUrl((int) $pg) ?>" class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-surface-container-high transition-colors hover:scale-105 transition-all duration-300"><?= (int) $pg ?></a>

                            <?php endif; ?>

                        <?php endforeach; ?>

                        <?php if ($currentPage < $totalPages): ?>

                            <a href="<?= $pageUrl($currentPage + 1) ?>" class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-surface-container-high text-on-surface-variant transition-colors hover:scale-105 transition-all duration-300" aria-label="Next page">

                                <span class="material-symbols-outlined">chevron_right</span>

                            </a>

                        <?php else: ?>

                            <button type="button" disabled class="w-10 h-10 flex items-center justify-center rounded-lg text-on-surface-variant disabled:opacity-30 cursor-not-allowed" aria-disabled="true">

                                <span class="material-symbols-outlined">chevron_right</span>

                            </button>

                        <?php endif; ?>

                    </nav>

                </div>

            <?php endif; ?>

        </div>

    </div>

</main>

<button aria-label="Go Back" onclick="window.history.back()" class="hidden md:flex fixed bottom-lg left-lg w-16 h-16 bg-surface-container-lowest text-on-surface rounded-full shadow-sm border border-outline-variant items-center justify-center hover:bg-surface-container-high active:scale-90 transition-all duration-200 hover:scale-110 hover:shadow-xl z-50">

    <span class="material-symbols-outlined !text-[32px]">arrow_back</span>

</button>

<?= $this->endSection() ?>
