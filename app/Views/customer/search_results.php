<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<?php
    $totalPages    = $totalPages ?? 1;
    $currentPage   = $currentPage ?? 1;
    $totalProducts = (int) ($totalProducts ?? 0);
    $searchQuery   = $searchQuery ?? '';
    $categoryId    = (int) ($categoryId ?? 0);
    $categories    = $categories ?? [];
?>

<main class="max-w-container-max mx-auto px-4 md:px-8 lg:px-10 py-6 md:py-8 flex-grow w-full">

    <!-- Breadcrumbs -->
    <nav aria-label="Breadcrumb" class="flex items-center gap-xs text-label-sm font-label-sm text-on-surface-variant mb-lg">
        <a class="hover:text-primary transition-colors" href="<?= base_url('/') ?>">Home</a>
        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
        <span class="text-on-surface font-semibold">Search Results</span>
    </nav>

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-md mb-xl pb-md border-b border-outline-variant/30">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-3xl">search</span>
                <?php if ($searchQuery !== ''): ?>
                    Results for "<span class="text-primary font-bold"><?= esc($searchQuery) ?></span>"
                <?php else: ?>
                    All Products
                <?php endif; ?>
            </h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-xs">
                Found <?= number_format($totalProducts) ?> matching product<?= $totalProducts === 1 ? '' : 's' ?> in our marketplace.
            </p>
        </div>

        <!-- Filter bar -->
        <form method="GET" action="<?= base_url('search') ?>" class="flex items-center gap-sm flex-wrap">
            <input type="hidden" name="q" value="<?= esc($searchQuery) ?>">
            <select name="category_id" onchange="this.form.submit()" class="bg-surface-container border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-medium text-on-surface focus:ring-2 focus:ring-primary">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= $categoryId === (int) $cat['id'] ? 'selected' : '' ?>>
                        <?= esc($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Results Grid (3 Columns on Mobile) -->
    <?php if (!empty($products)): ?>
        <div class="grid grid-cols-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-gutter" id="product-grid">
            <?php foreach ($products as $p): ?>
                <?php
                    $imageUrl = product_image_url($p['image_url'] ?? null);
                    $price = (float) ($p['price'] ?? 0);
                    $comparePrice = (float) ($p['compare_at_price'] ?? 0);
                    $hasDiscount = $comparePrice > $price;
                ?>
                <div class="group bg-surface-container-lowest border border-outline-variant/40 rounded-xl sm:rounded-2xl overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 flex flex-col justify-between">
                    <div class="relative aspect-square overflow-hidden bg-surface-container-high/40">
                        <a href="<?= base_url('product/' . $p['id']) ?>" class="block w-full h-full">
                            <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="<?= esc($imageUrl) ?>" alt="<?= esc($p['name']) ?>" loading="lazy">
                        </a>

                        <?php if ($hasDiscount): ?>
                            <span class="absolute top-1 left-1 sm:top-3 sm:left-3 bg-error text-on-error px-1.5 py-0.5 sm:px-2.5 sm:py-1 rounded-full text-[9px] sm:text-xs font-bold shadow-md">
                                SALE
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($p['shop_name'])): ?>
                            <span class="absolute bottom-1 left-1 sm:bottom-3 sm:left-3 bg-black/60 backdrop-blur-sm text-white px-1.5 py-0.2 sm:px-2.5 sm:py-0.5 rounded-full text-[9px] sm:text-[11px] font-medium truncate max-w-[85%]">
                                <?= esc($p['shop_name']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="p-1.5 sm:p-md flex flex-col flex-grow justify-between gap-1 sm:gap-sm">
                        <div>
                            <?php if (!empty($p['category_name'])): ?>
                                <span class="text-[9px] sm:text-[11px] text-outline font-semibold uppercase tracking-wider block mb-0.5 truncate">
                                    <?= esc($p['category_name']) ?>
                                </span>
                            <?php endif; ?>

                            <a href="<?= base_url('product/' . $p['id']) ?>" class="font-bold text-[11px] sm:text-title-sm text-on-surface hover:text-primary transition-colors line-clamp-2 block leading-tight">
                                <?= esc($p['name']) ?>
                            </a>
                        </div>

                        <div class="pt-1 sm:pt-2 border-t border-outline-variant/20 flex items-center justify-between">
                            <div>
                                <span class="text-xs sm:text-title-md font-extrabold text-primary">₱<?= number_format($price, 2) ?></span>
                                <?php if ($hasDiscount): ?>
                                    <span class="text-[9px] sm:text-xs text-outline line-through ml-0.5 hidden sm:inline">₱<?= number_format($comparePrice, 2) ?></span>
                                <?php endif; ?>
                            </div>

                            <a href="<?= base_url('product/' . $p['id']) ?>" class="p-1 sm:p-2 rounded-lg sm:rounded-xl bg-primary/10 text-primary hover:bg-primary hover:text-on-primary transition-all flex items-center justify-center shadow-xs" title="View product">
                                <span class="material-symbols-outlined text-[14px] sm:text-[18px]">visibility</span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="mt-12 flex justify-center items-center gap-2">
                <?php if ($currentPage > 1): ?>
                    <a href="<?= base_url('search?q=' . urlencode($searchQuery) . '&category_id=' . $categoryId . '&page=' . ($currentPage - 1)) ?>" class="px-4 py-2 bg-surface-container text-on-surface rounded-xl text-sm font-semibold hover:bg-surface-container-high transition-colors">
                        Previous
                    </a>
                <?php endif; ?>

                <span class="text-xs text-outline font-medium px-2">Page <?= $currentPage ?> of <?= $totalPages ?></span>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= base_url('search?q=' . urlencode($searchQuery) . '&category_id=' . $categoryId . '&page=' . ($currentPage + 1)) ?>" class="px-4 py-2 bg-primary text-on-primary rounded-xl text-sm font-semibold hover:bg-primary-container transition-colors shadow-sm">
                        Next
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="text-center py-16 bg-surface-container-lowest border border-outline-variant/30 rounded-3xl p-8 max-w-lg mx-auto shadow-sm">
            <div class="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-3xl">search_off</span>
            </div>
            <h3 class="text-title-lg font-bold text-on-surface mb-2">No matching products found</h3>
            <p class="text-body-md text-on-surface-variant mb-6">We couldn't find any products matching your search term. Try checking for spelling errors or searching for a different keyword.</p>
            <a href="<?= base_url('categories') ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-xl font-semibold shadow-md hover:bg-primary-container transition-all">
                <span class="material-symbols-outlined text-[18px]">category</span>
                Browse Categories
            </a>
        </div>
    <?php endif; ?>

</main>

<?= $this->endSection() ?>
