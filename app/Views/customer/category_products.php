<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<?php
    $totalPages  = $totalPages ?? 1;
    $currentPage = $currentPage ?? 1;
    $totalProducts = (int) ($totalProducts ?? 0);
    $categorySlug = $category['slug'] ?? ($category['id'] ?? '');
    $categoryName = $category['name'] ?? 'Category Products';
?>

<main class="max-w-container-max mx-auto px-4 md:px-8 lg:px-10 flex-grow w-full py-6 sm:py-8 md:py-10 flex flex-col gap-6 sm:gap-8">

    <!-- Breadcrumbs -->
    <nav aria-label="Breadcrumb" class="flex text-xs font-semibold text-slate-500">
        <ol class="inline-flex items-center space-x-1.5 md:space-x-2">
            <li class="inline-flex items-center">
                <a class="hover:text-primary transition-colors flex items-center gap-1" href="<?= base_url('/') ?>">
                    <span class="material-symbols-outlined text-[16px]">home</span>
                    <span>Home</span>
                </a>
            </li>
            <li class="flex items-center">
                <span class="material-symbols-outlined text-[16px] text-slate-400 mx-0.5">chevron_right</span>
                <a class="hover:text-primary transition-colors" href="<?= base_url('categories') ?>">Categories</a>
            </li>
            <li class="flex items-center">
                <span class="material-symbols-outlined text-[16px] text-slate-400 mx-0.5">chevron_right</span>
                <span class="text-slate-800 font-bold ml-1"><?= esc($categoryName) ?></span>
            </li>
        </ol>
    </nav>

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider mb-2">
                <span class="material-symbols-outlined text-[14px]">category</span>
                Department Catalog
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight"><?= esc($categoryName) ?></h1>
            <p class="text-sm text-slate-500 mt-1">Curated collection of quality merchandise and essentials available in Polomolok.</p>
        </div>

        <div class="flex items-center gap-3 text-xs font-semibold text-slate-600">
            <span class="bg-slate-100 px-3.5 py-1.5 rounded-full"><?= $totalProducts ?> item<?= $totalProducts === 1 ? '' : 's' ?> found</span>
        </div>
    </div>

    <!-- Product Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4 md:gap-5">

        <?php if (!empty($products)): ?>
            <?php foreach ($products as $p): ?>
                <?php
                    $imageUrl = product_image_url($p['image_url'] ?? null);
                    $rating   = $p['rating_average'] ?? 0;
                ?>
                <div class="card-elevated rounded-2xl overflow-hidden flex flex-col group">
                    
                    <div class="relative aspect-square bg-slate-50 overflow-hidden">
                        <a href="<?= base_url('product/' . $p['id']) ?>" class="block w-full h-full">
                            <img src="<?= esc($imageUrl) ?>" alt="<?= esc($p['name']) ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                        </a>

                        <?php if (!empty($p['shop_name'])): ?>
                            <span class="absolute top-2 left-2 bg-white/90 backdrop-blur-xs text-slate-800 text-[9px] font-bold px-2 py-0.5 rounded-full shadow-2xs truncate max-w-[120px]">
                                <?= esc($p['shop_name']) ?>
                            </span>
                        <?php endif; ?>

                        <button type="button" class="absolute top-2 right-2 bg-white/90 backdrop-blur-xs hover:bg-white text-slate-500 hover:text-red-500 p-1.5 rounded-full opacity-0 group-hover:opacity-100 transition-all shadow-xs" aria-label="Add to favorites">
                            <span class="material-symbols-outlined text-[15px]">favorite</span>
                        </button>
                    </div>

                    <div class="p-3 sm:p-4 flex flex-col flex-grow">
                        <h4 class="text-xs sm:text-sm font-semibold text-slate-900 group-hover:text-primary transition-colors line-clamp-2 leading-snug mb-1.5">
                            <a href="<?= base_url('product/' . $p['id']) ?>"><?= esc($p['name']) ?></a>
                        </h4>

                        <div class="flex items-center gap-1 mb-3">
                            <span class="material-symbols-outlined text-amber-500 text-[13px] fill-icon">star</span>
                            <span class="text-[11px] font-bold text-slate-700"><?= number_format((float) $rating, 1) ?></span>
                        </div>

                        <div class="mt-auto flex justify-between items-center pt-2 border-t border-slate-100">
                            <div>
                                <span class="text-[11px] font-bold text-primary">₱</span>
                                <span class="text-sm sm:text-base font-black text-slate-900"><?= number_format($p['price'], 2) ?></span>
                            </div>

                            <form action="<?= base_url('cart/add') ?>" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="quantity" value="1">

                                <button type="submit" class="bg-primary/10 hover:bg-primary text-primary hover:text-white p-2 rounded-xl transition-all flex items-center justify-center shrink-0 w-8 h-8 sm:w-9 sm:h-9" aria-label="Add to cart" title="Add to cart">
                                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">add_shopping_cart</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full flex flex-col items-center text-center p-12 bg-white rounded-3xl border border-slate-200">
                <span class="material-symbols-outlined text-5xl text-slate-300 mb-2">inventory_2</span>
                <h3 class="text-base font-bold text-slate-800 mb-1">No Products in This Category</h3>
                <p class="text-xs text-slate-500 max-w-md mb-4">Check back soon as merchants regularly update their catalog inventory.</p>
                <a href="<?= base_url('categories') ?>" class="inline-block bg-primary text-white text-xs font-bold px-5 py-2.5 rounded-xl shadow-xs hover:bg-blue-700 transition-colors">
                    Explore Other Categories
                </a>
            </div>
        <?php endif; ?>

    </div>

    <!-- Pagination -->
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
            if ($left > 2) $windowPages[] = '...';
            for ($pg = $left; $pg <= $right; $pg++) $windowPages[] = $pg;
            if ($right < $totalPages - 1) $windowPages[] = '...';
            $windowPages[] = $totalPages;
        }
    ?>

    <?php if ($totalPages > 1): ?>
        <div class="flex justify-center items-center gap-1.5 sm:gap-2 mt-6">
            <?php if ($currentPage > 1): ?>
                <a href="<?= $pageUrl($currentPage - 1) ?>" class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-colors shadow-2xs" aria-label="Previous page">
                    <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                </a>
            <?php else: ?>
                <span class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-100 bg-slate-50 text-slate-300 cursor-not-allowed">
                    <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                </span>
            <?php endif; ?>

            <div class="flex items-center gap-1">
                <?php foreach ($windowPages as $pg): ?>
                    <?php if ($pg === '...'): ?>
                        <span class="px-2 text-slate-400 text-xs font-semibold">...</span>
                    <?php elseif ((int) $pg === $currentPage): ?>
                        <a href="<?= $pageUrl((int) $pg) ?>" aria-current="page" class="w-9 h-9 flex items-center justify-center rounded-xl bg-primary text-white font-bold text-xs shadow-xs"><?= (int) $pg ?></a>
                    <?php else: ?>
                        <a href="<?= $pageUrl((int) $pg) ?>" class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors font-semibold text-xs"><?= (int) $pg ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <?php if ($currentPage < $totalPages): ?>
                <a href="<?= $pageUrl($currentPage + 1) ?>" class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-colors shadow-2xs" aria-label="Next page">
                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                </a>
            <?php else: ?>
                <span class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-100 bg-slate-50 text-slate-300 cursor-not-allowed">
                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</main>

<?= $this->endSection() ?>
