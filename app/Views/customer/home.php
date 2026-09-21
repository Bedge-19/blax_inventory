<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<main class="flex-1 w-full max-w-container-max mx-auto px-4 md:px-8 lg:px-10 py-4 sm:py-8 md:py-xl flex flex-col gap-6 sm:gap-8 md:gap-12 lg:gap-[80px]">

    <!-- Mobile Quick Search Bar (Prominent on small screens) -->
    <div class="block md:hidden w-full">
        <form action="<?= base_url('search') ?>" method="GET" class="flex items-center relative w-full shadow-xs">
            <span class="material-symbols-outlined absolute left-3.5 text-primary text-[20px]">search</span>
            <input name="q" value="<?= esc($searchQuery ?? '') ?>" maxlength="200" autocomplete="off" class="bg-surface-container-lowest border border-outline-variant/30 rounded-xl pl-10 pr-4 py-2.5 w-full text-xs font-medium placeholder:text-outline focus:ring-2 focus:ring-primary focus:border-primary" placeholder="Describe what you need in Polomolok..." type="text">
        </form>
    </div>

    <!-- Hero Section — content managed via Admin > Content Management -->
    <?php
        $sc = $siteContents ?? [];
        $heroBadge = $sc['hero_badge']['text_value'] ?? 'Seasonal Event';
        $heroTitle = $sc['hero_title']['text_value'] ?? 'The Ultimate Merchandise Selection';
        $heroSubtitle = $sc['hero_subtitle']['text_value'] ?? 'Discover premium goods, exclusive deals, and top-tier printing services all in one place from our network of verified elite shops.';
        $rawHeroImg = !empty($sc['hero_image']['image_url']) 
            ? $sc['hero_image']['image_url'] 
            : (!empty($sc['hero_image']['text_value']) ? $sc['hero_image']['text_value'] : null);
        $heroImage = cms_image_url($rawHeroImg, 'https://images.unsplash.com/photo-1556742049-0a67daf64f42?auto=format&fit=crop&w=1440&q=80');
        $catalogTitle = $sc['catalog_title']['text_value'] ?? 'Global Product Catalog';
        $catalogSubtitle = $sc['catalog_subtitle']['text_value'] ?? 'Aggregation of all products currently available across the entire RHK network.';
    ?>
    <section class="relative w-full rounded-2xl sm:rounded-3xl overflow-hidden shadow-md bg-surface-container-lowest min-h-[260px] sm:min-h-[340px] lg:h-[420px] py-6 sm:py-10 flex items-center group">

        <div class="absolute inset-0 bg-cover bg-center w-full h-full opacity-90 transition-transform duration-700 ease-in-out transform group-hover:scale-105" style="background-image: url('<?= esc($heroImage) ?>');"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-surface-container-lowest/98 via-surface-container-lowest/85 to-surface-container-lowest/40 sm:to-transparent"></div>

        <div class="relative z-10 px-4 sm:px-8 lg:px-[80px] max-w-2xl flex flex-col gap-2.5 sm:gap-md">

            <span class="text-[11px] sm:text-label-sm font-label-sm text-primary uppercase tracking-wider font-extrabold flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                <?= esc($heroBadge) ?>
            </span>
            <h1 class="text-xl sm:text-3xl lg:text-display font-display text-on-surface font-extrabold leading-tight"><?= esc($heroTitle) ?></h1>
            <p class="text-xs sm:text-base lg:text-body-lg font-body-lg text-on-surface-variant max-w-md line-clamp-3 sm:line-clamp-none leading-relaxed"><?= esc($heroSubtitle) ?></p>

            <div class="mt-2 sm:mt-sm flex flex-wrap items-center gap-2 sm:gap-md">

                <a href="#catalog" class="bg-primary text-on-primary text-xs sm:text-button font-semibold sm:font-button px-4 py-2.5 sm:px-lg sm:py-md rounded-lg hover:bg-on-primary-fixed-variant transition-all shadow-sm hover:shadow-md transform hover:-translate-y-0.5 duration-300">
                    <?= esc($sc['hero_cta_text']['text_value'] ?? 'Explore Marketplace') ?>
                </a>

                <?php if (!session()->get('isLoggedIn')): ?>
                    <a href="<?= base_url('signup') ?>" class="bg-surface-container-lowest/90 backdrop-blur-xs text-primary border border-primary/30 text-xs sm:text-button font-semibold sm:font-button px-3.5 py-2.5 sm:px-lg sm:py-md rounded-lg hover:bg-primary/10 transition-all shadow-xs">
                        Create Account
                    </a>
                <?php endif; ?>

            </div>

            <!-- Mobile Trust Badges -->
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 pt-1 text-[10px] sm:text-xs text-on-surface-variant font-medium opacity-90">
                <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[14px] text-green-600">verified</span> Polomolok Verified</span>
                <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[14px] text-primary">local_shipping</span> Fast Delivery</span>
                <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[14px] text-amber-600">print</span> Rush Printing</span>
            </div>

        </div>

    </section>

    <?php if (($searchQuery ?? '') !== ''): ?>

        <!-- Search Results: Matching Shops -->
        <section class="flex flex-col gap-lg">

            <div class="flex justify-between items-center mb-xl">

                <h2 class="font-headline-lg text-headline-lg text-on-surface">Shops matching &quot;<?= esc($searchQuery) ?>&quot;</h2>
                <a class="text-primary font-button text-button hover:underline" href="<?= base_url('shops?q=' . urlencode($searchQuery)) ?>">View All Shops</a>

            </div>

            <?php if (!empty($shopResults)): ?>

                <div class="flex flex-wrap justify-center sm:justify-start items-start gap-4 sm:gap-6 lg:gap-xl">

                    <?php foreach ($shopResults as $s): ?>

                        <a href="<?= base_url('shop/' . ($s['slug'] ?? $s['id'])) ?>" class="flex flex-col items-center gap-md group cursor-pointer">

                            <div class="rounded-full border-2 border-error-container p-1 transition-transform group-hover:scale-105 w-32 h-32">

                                <div class="w-full h-full rounded-full bg-surface-container-high flex items-center justify-center overflow-hidden">

                                    <?php if (!empty($s['logo_url'])): ?>

                                        <img class="w-full h-full object-cover" src="<?= esc(logo_url($s['logo_url'])) ?>" alt="<?= esc($s['shop_name']) ?>">

                                    <?php else: ?>

                                        <span class="material-symbols-outlined text-primary text-5xl">store</span>

                                    <?php endif; ?>

                                </div>

                            </div>

                            <span class="text-body-md font-medium text-on-surface group-hover:text-primary transition-colors"><?= esc($s['shop_name']) ?></span>

                        </a>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <p class="text-on-surface-variant text-sm">No shops matched &quot;<?= esc($searchQuery) ?>&quot;.</p>

            <?php endif; ?>

        </section>

    <?php else: ?>

    <!-- Featured Shops - Most Rated, max 5 -->
    <section class="flex flex-col gap-lg">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-1 sm:gap-sm mb-3 sm:mb-md">
            <div>
                <h2 class="font-bold text-lg sm:text-headline-lg text-on-surface">Featured Shops</h2>
                <p class="text-xs sm:text-body-md text-on-surface-variant mt-0.5">Discover top-rated local merchants and specialty stores</p>
            </div>
            <a class="text-primary text-xs sm:text-button font-semibold hover:underline inline-flex items-center gap-1 shrink-0" href="<?= base_url('shops') ?>">
                <span>View All Shops</span>
                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        </div>

        <div class="flex overflow-x-auto no-scrollbar gap-2.5 sm:gap-md pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 sm:grid sm:grid-cols-3 md:grid-cols-5 lg:gap-lg snap-x snap-mandatory">

            <?php if (!empty($shops)): ?>

                <?php foreach ($shops as $s): ?>

                    <a href="<?= base_url('shop/' . ($s['slug'] ?? $s['id'])) ?>" class="glass-card bg-surface-container-lowest border border-outline-variant/20 rounded-2xl p-3 sm:p-md lg:p-lg flex flex-col items-center text-center group cursor-pointer transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:border-primary/40 relative overflow-hidden w-32 sm:w-auto shrink-0 snap-start">

                        <!-- Ambient Glow -->
                        <div class="absolute inset-0 bg-gradient-to-b from-primary/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>

                        <!-- Shop Avatar with Gradient Ring -->
                        <div class="relative w-14 h-14 sm:w-20 sm:h-20 rounded-full p-0.5 sm:p-1 bg-gradient-to-tr from-primary/30 via-primary/10 to-primary/50 group-hover:from-primary group-hover:to-primary/80 transition-all duration-300 shadow-sm shrink-0 mb-2">
                            <div class="w-full h-full rounded-full bg-white flex items-center justify-center overflow-hidden">

                                <?php if (!empty($s['logo_url'])): ?>

                                    <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" src="<?= esc(logo_url($s['logo_url'])) ?>" alt="<?= esc($s['shop_name']) ?>">

                                <?php else: ?>

                                    <div class="w-full h-full bg-gradient-to-br from-primary/10 via-primary-container/20 to-primary/20 flex flex-col items-center justify-center text-primary font-bold">
                                        <span class="material-symbols-outlined text-[20px] sm:text-[28px] mb-[-2px]">storefront</span>
                                        <span class="text-[9px] sm:text-[10px] tracking-wider uppercase font-extrabold"><?= esc(substr($s['shop_name'], 0, 3)) ?></span>
                                    </div>

                                <?php endif; ?>

                            </div>
                        </div>

                        <!-- Shop Info -->
                        <div class="w-full space-y-0.5 sm:space-y-xs flex flex-col items-center">
                            <h3 class="text-xs sm:text-body-md font-bold text-on-surface group-hover:text-primary transition-colors line-clamp-1 w-full text-center" title="<?= esc($s['shop_name']) ?>">
                                <?= esc($s['shop_name']) ?>
                            </h3>

                            <!-- Rating & Reviews -->
                            <div class="flex items-center justify-center gap-0.5 sm:gap-xs text-[10px] sm:text-xs text-on-surface-variant font-medium">
                                <span class="material-symbols-outlined text-[13px] sm:text-[16px] text-amber-500 fill-icon">star</span>
                                <span class="font-bold text-on-surface"><?= number_format($s['rating_average'] ?? 5.0, 1) ?></span>
                                <span class="text-on-surface-variant/70 text-[9px] sm:text-[11px]">(<?= number_format($s['rating_count'] ?? 0) ?>)</span>
                            </div>

                            <?php if (!empty($s['offers_printing'])): ?>
                                <span class="inline-flex items-center gap-0.5 sm:gap-xs text-[9px] sm:text-[10px] font-bold text-primary bg-primary/10 px-1.5 py-[1px] sm:px-xs sm:py-[2px] rounded-full mt-1">
                                    <span class="material-symbols-outlined text-[10px] sm:text-[12px]">print</span>
                                    Printing
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-0.5 sm:gap-xs text-[9px] sm:text-[10px] font-bold text-on-surface-variant bg-surface-container-high px-1.5 py-[1px] sm:px-xs sm:py-[2px] rounded-full mt-1">
                                    Merchant
                                </span>
                            <?php endif; ?>
                        </div>

                    </a>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="col-span-full text-center p-xl text-on-surface-variant bg-surface-container-low rounded-2xl border border-outline-variant/20">
                    <p class="text-body-md">No shops available at the moment.</p>
                </div>

            <?php endif; ?>

        </div>

    </section>

    <?php endif; ?>

    <!-- Global Product Catalog Section -->
    <section id="catalog" class="bg-surface-container-low/50 py-6 sm:py-10 md:py-12 lg:py-[80px] -mx-4 md:-mx-8 lg:-mx-10 px-4 md:px-8 lg:px-10 rounded-2xl sm:rounded-3xl">

        <div class="max-w-container-max mx-auto">

            <!-- Mobile & Desktop Quick Category Chips -->
            <?php if (!empty($categories)): ?>
                <div class="mb-4 sm:mb-6 -mx-4 px-4 sm:mx-0 sm:px-0">
                    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
                        <a href="<?= base_url('/') ?>#catalog" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all <?= empty($categoryId) ? 'bg-primary text-white shadow-xs' : 'bg-white text-on-surface-variant hover:bg-surface-container border border-outline-variant/30' ?>">
                            <span class="material-symbols-outlined text-[14px]">auto_awesome</span>
                            <span>All Items</span>
                        </a>
                        <?php foreach ($categories as $cat): ?>
                            <?php $isCatActive = ((int) ($categoryId ?? 0) === (int) $cat['id']); ?>
                            <a href="<?= base_url('?category_id=' . $cat['id']) ?>#catalog" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all <?= $isCatActive ? 'bg-primary text-white shadow-xs' : 'bg-white text-on-surface-variant hover:bg-surface-container border border-outline-variant/30' ?>">
                                <span><?= esc($cat['name']) ?></span>
                            </a>
                        <?php endforeach; ?>
                        <a href="<?= base_url('printing-services') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all bg-white text-primary border border-primary/30 hover:bg-primary/5">
                            <span class="material-symbols-outlined text-[14px]">print</span>
                            <span>Printing Services</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="flex flex-col sm:flex-row justify-between sm:items-end items-start mb-4 sm:mb-xl gap-3 sm:gap-lg">

                <div>

                    <h2 class="font-bold text-lg sm:text-headline-lg mb-1 sm:mb-sm"><?= esc($catalogTitle) ?></h2>
                    <p class="text-xs sm:text-body-md text-on-surface-variant"><?= esc($catalogSubtitle) ?></p>

                    <?php if (($searchQuery ?? '') !== ''): ?>

                        <div class="flex flex-wrap items-center gap-sm mt-md">

                            <?php if (!empty($semantic)): ?>

                                <span class="inline-flex items-center gap-xs bg-secondary-container text-on-secondary-container text-label-sm font-semibold px-md py-xs rounded-full">
                                    <span class="material-symbols-outlined text-sm">auto_awesome</span>
                                    AI matched
                                </span>

                            <?php endif; ?>

                            <span class="text-label-sm text-on-surface-variant">
                                <?= (int) ($totalProducts ?? 0) ?> result<?= ((int) ($totalProducts ?? 0)) === 1 ? '' : 's' ?> for &quot;<?= esc($searchQuery) ?>&quot;
                            </span>

                            <?php if (!empty($semanticNotice)): ?>

                                <span class="text-label-sm text-on-surface-variant italic">Smart search is temporarily unavailable, showing keyword results.</span>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </div>

                <div class="flex gap-2 sm:gap-sm shrink-0">

                    <a href="<?= base_url('categories') ?>" class="flex items-center gap-1.5 sm:gap-sm bg-white border border-outline-variant/30 rounded-lg px-3 py-1.5 sm:px-md sm:py-sm cursor-pointer hover:bg-surface-container transition-colors shadow-xs">

                        <span class="material-symbols-outlined text-outline text-[16px] sm:text-base">filter_list</span>
                        <span class="text-xs sm:text-label-sm font-medium">Filter</span>

                    </a>

                    <a href="<?= base_url('search?q=') ?>" class="flex items-center gap-1.5 sm:gap-sm bg-white border border-outline-variant/30 rounded-lg px-3 py-1.5 sm:px-md sm:py-sm cursor-pointer hover:bg-surface-container transition-colors shadow-xs">

                        <span class="material-symbols-outlined text-outline text-[16px] sm:text-base">swap_vert</span>
                        <span class="text-xs sm:text-label-sm font-medium">Sort By</span>

                    </a>

                </div>

            </div>

            <!-- Responsive Product Grid: 2 columns on mobile, 3 on tablet, 4-5 on desktop -->
            <div id="product-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2.5 sm:gap-4 md:gap-gutter">

                <?php if (!empty($products)): ?>

                    <?php foreach ($products as $p): ?>

                        <?php
                            $imageUrl = product_image_url($p['image_url'] ?? null);
                            $rating   = $p['rating_average'] ?? 0;
                        ?>

                        <div class="group flex flex-col bg-white rounded-xl border border-outline-variant/20 overflow-hidden hover:shadow-lg transition-all duration-300">

                            <div class="relative aspect-square bg-surface-container overflow-hidden">

                                <a href="<?= base_url('product/' . $p['id']) ?>" class="block w-full h-full">
                                    <img src="<?= esc($imageUrl) ?>" alt="<?= esc($p['name']) ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                                </a>
                                <button type="button" class="absolute top-1.5 right-1.5 sm:top-sm sm:right-sm bg-white/80 backdrop-blur p-1 sm:p-xs rounded-full opacity-0 group-hover:opacity-100 transition-opacity" aria-label="Add to favorites">

                                    <span class="material-symbols-outlined text-[13px] sm:text-on-surface-variant">favorite</span>

                                </button>

                            </div>

                            <div class="p-2 sm:p-3 md:p-md flex flex-col flex-grow">

                                <span class="text-[9px] sm:text-label-sm text-primary font-bold mb-0.5 sm:mb-xs uppercase tracking-tight truncate block"><?= esc($p['shop_name'] ?? 'Blax Store') ?></span>
                                <h4 class="text-xs sm:text-body-md font-semibold text-on-surface mb-1 group-hover:text-primary transition-colors line-clamp-2 leading-snug"><a href="<?= base_url('product/' . $p['id']) ?>"><?= esc($p['name']) ?></a></h4>

                                <div class="flex items-center gap-0.5 sm:gap-xs mb-1.5 sm:mb-md">

                                    <span class="material-symbols-outlined text-amber-500 text-[11px] sm:text-xs" style="font-variation-settings: 'FILL' 1;">star</span>
                                    <span class="text-[10px] sm:text-label-sm font-semibold"><?= number_format((float) $rating, 1) ?></span>

                                </div>

                                <div class="mt-auto flex justify-between items-center pt-1.5 border-t border-outline-variant/10">

                                    <span class="text-xs sm:text-title-lg font-extrabold text-primary">₱<?= number_format($p['price'], 2) ?></span>

                                    <form action="<?= base_url('cart/add') ?>" method="POST">

                                        <?= csrf_field() ?>

                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="quantity" value="1">

                                        <button type="submit" class="bg-surface-container-high hover:bg-primary hover:text-white p-1.5 sm:p-2 rounded-lg transition-all flex items-center justify-center shrink-0 w-8 h-8 sm:w-9 sm:h-9" aria-label="Add to cart">

                                            <span class="material-symbols-outlined text-[16px] sm:text-[18px]">add_shopping_cart</span>

                                        </button>

                                    </form>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="col-span-full text-center py-xl bg-surface-container-lowest rounded-2xl border border-outline-variant/30">

                        <span class="material-symbols-outlined text-4xl text-outline mb-2">inventory_2</span>

                        <?php if (($searchQuery ?? '') !== ''): ?>

                            <p class="text-on-surface-variant font-medium">No products matched &quot;<?= esc($searchQuery) ?>&quot;.</p>
                            <p class="text-label-sm text-on-surface-variant mt-xs">Try describing the item differently &mdash; for example &quot;pang sulat nga blue&quot;, &quot;gamit pang drawing&quot;, or &quot;notebook for notes&quot;.</p>
                            <a href="<?= base_url('/') ?>" class="inline-block mt-md text-primary font-button text-button hover:underline">Clear search</a>

                        <?php else: ?>

                            <p class="text-on-surface-variant font-medium">No products currently available.</p>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            </div>

            <?php
                $totalPages   = $totalPages ?? 1;
                $currentPage  = $currentPage ?? 1;
                $searchQuery  = $searchQuery ?? '';
                $categoryId   = $categoryId ?? null;
                $pageUrl = function (int $p) use ($searchQuery, $categoryId) {
                    $params = ['page' => $p];
                    if ($searchQuery !== '') {
                        $params['q'] = $searchQuery;
                    }
                    if (!empty($categoryId)) {
                        $params['category_id'] = $categoryId;
                    }
                    return base_url('?' . http_build_query($params));
                };

                $windowPages = [];
                if ($totalPages <= 7) {
                    $windowPages = range(1, $totalPages);
                } else {
                    $windowPages = [1];
                    $left = max(2, $currentPage - 2);
                    $right = min($totalPages - 1, $currentPage + 2);
                    if ($left > 2) {
                        $windowPages[] = '...';
                    }
                    for ($i = $left; $i <= $right; $i++) {
                        $windowPages[] = $i;
                    }
                    if ($right < $totalPages - 1) {
                        $windowPages[] = '...';
                    }
                    $windowPages[] = $totalPages;
                }
            ?>

            <?php if ($totalPages > 1): ?>

                <div class="mt-6 sm:mt-lg flex flex-wrap justify-center items-center gap-1.5 sm:gap-md px-2">

                    <?php if ($currentPage > 1): ?>

                        <a href="<?= $pageUrl($currentPage - 1) ?>" class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg border border-outline-variant flex items-center justify-center text-on-surface-variant hover:bg-surface-container transition-colors shrink-0" aria-label="Previous page">

                            <span class="material-symbols-outlined text-[18px] sm:text-[22px]">chevron_left</span>

                        </a>

                    <?php else: ?>

                        <span class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg border border-outline-variant/40 flex items-center justify-center text-outline-variant/40 cursor-not-allowed shrink-0" aria-disabled="true">

                            <span class="material-symbols-outlined text-[18px] sm:text-[22px]">chevron_left</span>

                        </span>

                    <?php endif; ?>

                    <div class="flex flex-wrap items-center gap-1 sm:gap-sm">

                        <?php foreach ($windowPages as $pg): ?>

                            <?php if ($pg === '...'): ?>

                                <span class="flex items-end pb-1 sm:pb-2 px-0.5 sm:px-1 text-outline-variant text-xs sm:text-sm font-semibold" aria-hidden="true">...</span>

                            <?php elseif ((int) $pg === $currentPage): ?>

                                <a href="<?= $pageUrl((int) $pg) ?>" aria-current="page" class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-primary text-on-primary flex items-center justify-center font-bold text-xs sm:text-button shadow-xs"><?= (int) $pg ?></a>

                            <?php else: ?>

                                <a href="<?= $pageUrl((int) $pg) ?>" class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg border border-outline-variant flex items-center justify-center text-on-surface-variant hover:bg-surface-container transition-colors font-medium text-xs sm:text-button"><?= (int) $pg ?></a>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </div>

                    <?php if ($currentPage < $totalPages): ?>

                        <a href="<?= $pageUrl($currentPage + 1) ?>" class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg border border-outline-variant flex items-center justify-center text-on-surface-variant hover:bg-surface-container transition-colors shrink-0" aria-label="Next page">

                            <span class="material-symbols-outlined text-[18px] sm:text-[22px]">chevron_right</span>

                        </a>

                    <?php else: ?>

                        <span class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg border border-outline-variant/40 flex items-center justify-center text-outline-variant/40 cursor-not-allowed shrink-0" aria-disabled="true">

                            <span class="material-symbols-outlined text-[18px] sm:text-[22px]">chevron_right</span>

                        </span>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

<?= $this->endSection() ?>