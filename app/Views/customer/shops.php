<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<?php
    $totalPages  = $totalPages ?? 1;
    $currentPage = $currentPage ?? 1;
    $totalShops  = (int) ($totalShops ?? 0);
    $searchQuery = $searchQuery ?? '';
    $category    = $category ?? '';
    $sort        = $sort ?? 'top-rated';

    $compactCount = function ($n) {
        $n = (int) $n;
        if ($n >= 1000) {
            return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'k';
        }
        return number_format($n);
    };

    $filterUrl = function ($newCat = null, $newSort = null, $newQ = null) use ($category, $sort, $searchQuery) {
        $params = [];
        $c = $newCat !== null ? $newCat : $category;
        $s = $newSort !== null ? $newSort : $sort;
        $q = $newQ !== null ? $newQ : $searchQuery;

        if ($q !== '') $params['q'] = $q;
        if ($c !== '') $params['category'] = $c;
        if ($s !== 'top-rated') $params['sort'] = $s;
        return base_url('shops' . (!empty($params) ? '?' . http_build_query($params) : ''));
    };
?>

<main class="max-w-container-max mx-auto px-4 md:px-8 lg:px-10 flex-grow w-full py-6 sm:py-8 md:py-10 flex flex-col gap-6 sm:gap-8">

    <!-- Header & Breadcrumbs -->
    <section class="flex flex-col gap-2">
        <nav aria-label="Breadcrumb" class="flex text-xs font-semibold text-slate-500 mb-1">
            <ol class="inline-flex items-center space-x-1.5 md:space-x-2">
                <li class="inline-flex items-center">
                    <a class="hover:text-primary transition-colors flex items-center gap-1" href="<?= base_url('/') ?>">
                        <span class="material-symbols-outlined text-[16px]">home</span>
                        <span>Home</span>
                    </a>
                </li>
                <li class="flex items-center">
                    <span class="material-symbols-outlined text-[16px] text-slate-400 mx-0.5">chevron_right</span>
                    <span class="text-slate-800 font-bold ml-1">Shops Directory</span>
                </li>
            </ol>
        </nav>

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider mb-2">
                    <span class="material-symbols-outlined text-[14px]">storefront</span>
                    Polomolok Merchant Directory
                </span>
                <h1 class="text-2xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Verified Local Shops</h1>
                <p class="text-xs sm:text-base text-slate-500 mt-1">Discover trusted local merchants, specialty stationery stores, and professional print providers.</p>
            </div>

            <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-3.5 py-1.5 rounded-full self-start md:self-auto shrink-0">
                <?= $totalShops ?> shops listed
            </span>
        </div>
    </section>

    <!-- Search & Filter Controls -->
    <section class="card-elevated rounded-2xl p-3 sm:p-5 flex flex-col md:flex-row gap-3 sm:gap-4 items-stretch md:items-center justify-between">
        
        <!-- Search Input Form -->
        <form method="GET" action="<?= base_url('shops') ?>" class="flex-1 relative" role="search">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
            <input name="q" value="<?= esc($searchQuery) ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2 sm:py-2.5 text-xs font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-primary focus:bg-white transition-all shadow-2xs" placeholder="Search shops by name or specialty..." type="text">
            
            <?php if ($category !== ''): ?>
                <input type="hidden" name="category" value="<?= esc($category) ?>">
            <?php endif; ?>
            <?php if ($sort !== 'top-rated'): ?>
                <input type="hidden" name="sort" value="<?= esc($sort) ?>">
            <?php endif; ?>
        </form>

        <!-- Segmented Category Filters -->
        <div class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto no-scrollbar pb-0.5">
            
            <a href="<?= $filterUrl('') ?>" class="inline-flex items-center gap-1 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all <?= empty($category) ? 'bg-primary text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
                <span>All Shops</span>
            </a>

            <a href="<?= $filterUrl('printing') ?>" class="inline-flex items-center gap-1 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all <?= $category === 'printing' ? 'bg-primary text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
                <span class="material-symbols-outlined text-[14px] sm:text-[15px]">print</span>
                <span>Printing</span>
            </a>

            <a href="<?= $filterUrl('enterprise') ?>" class="inline-flex items-center gap-1 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all <?= $category === 'enterprise' ? 'bg-primary text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
                <span class="material-symbols-outlined text-[14px] sm:text-[15px]">corporate_fare</span>
                <span>Enterprise</span>
            </a>

            <!-- Sort By Select -->
            <form method="GET" action="<?= base_url('shops') ?>" class="inline-flex shrink-0">
                <?php if ($searchQuery !== ''): ?>
                    <input type="hidden" name="q" value="<?= esc($searchQuery) ?>">
                <?php endif; ?>
                <?php if ($category !== ''): ?>
                    <input type="hidden" name="category" value="<?= esc($category) ?>">
                <?php endif; ?>

                <select name="sort" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 sm:px-3 sm:py-2 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-primary focus:bg-white shadow-2xs cursor-pointer">
                    <option value="top-rated" <?= $sort === 'top-rated' ? 'selected' : '' ?>>Top Rated</option>
                    <option value="most-recent" <?= $sort === 'most-recent' ? 'selected' : '' ?>>Most Recent</option>
                </select>
            </form>

        </div>

    </section>

    <!-- Shop Cards Grid (2 Columns on Mobile, 2 on Tablet, 3-4 on Desktop) -->
    <section>
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-4 md:gap-5">
            <?php if (!empty($shops)): ?>
                <?php foreach ($shops as $s): ?>
                    <div class="card-elevated bg-white border border-slate-200/90 shadow-2xs hover:shadow-md hover:border-primary/40 rounded-2xl overflow-hidden flex flex-col justify-between group transition-all duration-300">
                        
                        <div>
                            <!-- Header Cover Banner (Compact on mobile) -->
                            <div class="h-12 sm:h-18 w-full bg-gradient-to-r from-blue-700/20 via-indigo-600/20 to-primary/20 relative p-1.5 sm:p-2.5 flex justify-between items-start">
                                
                                <span class="inline-flex items-center gap-1 bg-white/90 backdrop-blur-xs text-slate-800 text-[8px] sm:text-[10px] font-bold px-1.5 sm:px-2.5 py-0.5 rounded-full shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>Verified</span>
                                </span>

                                <?php if (!empty($s['offers_printing'])): ?>
                                    <span class="inline-flex items-center gap-1 bg-primary text-white text-[8px] sm:text-[10px] font-bold px-1.5 sm:px-2 py-0.5 rounded-full shadow-2xs">
                                        <span class="material-symbols-outlined text-[10px] sm:text-[12px]">print</span>
                                        <span>Print</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 bg-white/90 text-slate-700 text-[8px] sm:text-[10px] font-bold px-1.5 sm:px-2 py-0.5 rounded-full shadow-2xs">
                                        <span class="material-symbols-outlined text-[10px] sm:text-[12px]">storefront</span>
                                        <span>Retail</span>
                                    </span>
                                <?php endif; ?>

                            </div>

                            <!-- Shop Info Body with Scaled-Down Avatar -->
                            <div class="p-2.5 sm:p-4 pt-0 flex flex-col items-center text-center -mt-6 sm:-mt-8">
                                
                                <!-- Proportional Avatar (w-12 h-12 on mobile, w-16 h-16 on desktop) -->
                                <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full p-0.5 sm:p-1 bg-white shadow-md border border-slate-200/90 overflow-hidden mb-2 sm:mb-2.5 group-hover:scale-105 transition-transform shrink-0">
                                    <div class="w-full h-full rounded-full overflow-hidden bg-slate-100 flex items-center justify-center">
                                        <?php if (!empty($s['logo_url'])): ?>
                                            <img class="w-full h-full object-cover" src="<?= esc(logo_url($s['logo_url'])) ?>" alt="<?= esc($s['shop_name']) ?> logo">
                                        <?php else: ?>
                                            <div class="w-full h-full bg-gradient-to-br from-primary/10 to-blue-100 flex flex-col items-center justify-center text-primary font-bold">
                                                <span class="material-symbols-outlined text-[18px] sm:text-[24px]">storefront</span>
                                                <span class="text-[8px] sm:text-[9px] uppercase tracking-wider"><?= esc(substr($s['shop_name'], 0, 3)) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Shop Title -->
                                <h3 class="text-xs sm:text-base font-bold text-slate-900 group-hover:text-primary transition-colors line-clamp-1 w-full" title="<?= esc($s['shop_name']) ?>">
                                    <a href="<?= base_url('shop/' . ($s['slug'] ?? $s['id'])) ?>">
                                        <?= esc($s['shop_name']) ?>
                                    </a>
                                </h3>

                                <!-- Rating & Reviews -->
                                <div class="flex items-center justify-center gap-0.5 sm:gap-1 text-[10px] sm:text-xs mt-0.5 sm:mt-1">
                                    <span class="material-symbols-outlined text-[12px] sm:text-[14px] text-amber-500 fill-icon">star</span>
                                    <span class="font-bold text-slate-800"><?= number_format($s['rating_average'] ?? 5.0, 1) ?></span>
                                    <span class="text-slate-400 text-[9px] sm:text-[11px]">(<?= $compactCount($s['rating_count'] ?? 0) ?>)</span>
                                </div>

                                <!-- Description (Hidden on mobile to keep grid compact & neat) -->
                                <p class="text-xs text-slate-500 mt-2 line-clamp-2 leading-relaxed font-normal hidden sm:block">
                                    <?= esc($s['description'] ?? 'Verified Polomolok merchant offering quality merchandise and services.') ?>
                                </p>

                            </div>
                        </div>

                        <!-- Card Action -->
                        <div class="p-2 sm:p-4 pt-0">
                            <a href="<?= base_url('shop/' . ($s['slug'] ?? $s['id'])) ?>" class="w-full py-1.5 sm:py-2.5 bg-primary hover:bg-blue-700 text-white rounded-lg sm:rounded-xl text-[10px] sm:text-xs font-bold transition-all text-center flex items-center justify-center gap-1 shadow-2xs hover:shadow-xs">
                                <span>Visit Store</span>
                                <span class="material-symbols-outlined text-[13px] sm:text-[16px] transform group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-12 bg-white rounded-3xl border border-slate-200 p-6">
                    <span class="material-symbols-outlined text-5xl text-slate-300 mb-2">store_mall_directory</span>
                    <?php if ($searchQuery !== ''): ?>
                        <p class="text-slate-800 font-bold text-sm">No shops matched &quot;<?= esc($searchQuery) ?>&quot;.</p>
                        <p class="text-xs text-slate-500 mt-1">Try adjusting your search query or removing the category filter.</p>
                        <a href="<?= base_url('shops') ?>" class="inline-block mt-4 text-xs font-bold text-primary hover:underline">Reset Filters</a>
                    <?php else: ?>
                        <p class="text-slate-600 font-medium text-sm">No shops found in this category.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php
            $pageUrl = function (int $p) use ($searchQuery, $category, $sort) {
                $params = ['page' => $p];
                if ($searchQuery !== '') $params['q'] = $searchQuery;
                if ($category !== '') $params['category'] = $category;
                if ($sort !== 'top-rated') $params['sort'] = $sort;
                return base_url('shops?' . http_build_query($params));
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
            <div class="mt-8 flex justify-center">
                <nav class="flex items-center gap-1.5 sm:gap-2" aria-label="Pagination">
                    <?php if ($currentPage > 1): ?>
                        <a href="<?= $pageUrl($currentPage - 1) ?>" class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-colors shadow-2xs" aria-label="Previous page">
                            <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                        </a>
                    <?php else: ?>
                        <span class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-100 bg-slate-50 text-slate-300 cursor-not-allowed" aria-disabled="true">
                            <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                        </span>
                    <?php endif; ?>

                    <?php foreach ($windowPages as $pg): ?>
                        <?php if ($pg === '...'): ?>
                            <span class="px-2 text-slate-400 text-xs font-semibold">...</span>
                        <?php elseif ((int) $pg === $currentPage): ?>
                            <a href="<?= $pageUrl((int) $pg) ?>" aria-current="page" class="w-9 h-9 flex items-center justify-center rounded-xl bg-primary text-white font-bold text-xs shadow-xs"><?= (int) $pg ?></a>
                        <?php else: ?>
                            <a href="<?= $pageUrl((int) $pg) ?>" class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors font-semibold text-xs"><?= (int) $pg ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?= $pageUrl($currentPage + 1) ?>" class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-colors shadow-2xs" aria-label="Next page">
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </a>
                    <?php else: ?>
                        <span class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-100 bg-slate-50 text-slate-300 cursor-not-allowed" aria-disabled="true">
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </span>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>

    </section>

</main>

<?= $this->endSection() ?>
