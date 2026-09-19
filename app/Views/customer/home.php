<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<main class="flex-1 w-full max-w-container-max mx-auto px-4 md:px-8 lg:px-10 py-8 md:py-xl flex flex-col gap-8 md:gap-12 lg:gap-[80px]">

    <!-- Hero Section — content managed via Admin > Content Management -->
    <?php
        $sc = $siteContents ?? [];
        $heroBadge = $sc['hero_badge']['text_value'] ?? 'Seasonal Event';
        $heroTitle = $sc['hero_title']['text_value'] ?? 'The Ultimate Merchandise Selection';
        $heroSubtitle = $sc['hero_subtitle']['text_value'] ?? 'Discover premium goods, exclusive deals, and top-tier printing services all in one place from our network of verified elite shops.';
        $heroImage = $sc['hero_image']['image_url'] ?? 'https://images.unsplash.com/photo-1556742049-0a67daf64f42?auto=format&fit=crop&w=1440&q=80';
        if ($heroImage && !str_starts_with($heroImage,'http')) $heroImage = base_url($heroImage);
        $catalogTitle = $sc['catalog_title']['text_value'] ?? 'Global Product Catalog';
        $catalogSubtitle = $sc['catalog_subtitle']['text_value'] ?? 'Aggregation of all products currently available across the entire RHK network.';
    ?>
    <section class="relative w-full rounded-xl overflow-hidden shadow-md bg-surface-container-lowest h-[240px] sm:h-[320px] lg:h-[400px] flex items-center group">

        <div class="absolute inset-0 bg-cover bg-center w-full h-full opacity-90 transition-transform duration-700 ease-in-out transform group-hover:scale-105" style="background-image: url('<?= esc($heroImage) ?>');"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-surface-container-lowest/90 via-surface-container-lowest/50 to-transparent"></div>

        <div class="relative z-10 px-4 md:px-8 lg:px-[80px] max-w-2xl flex flex-col gap-md">

            <span class="text-label-sm font-label-sm text-primary uppercase tracking-wider font-semibold"><?= esc($heroBadge) ?></span>
            <h1 class="text-2xl sm:text-3xl lg:text-display font-display text-on-surface"><?= esc($heroTitle) ?></h1>
            <p class="text-sm sm:text-base lg:text-body-lg font-body-lg text-on-surface-variant max-w-md"><?= esc($heroSubtitle) ?></p>

            <div class="mt-sm flex gap-md">

                <a href="#catalog" class="bg-primary text-on-primary text-button font-button px-lg py-md rounded-lg hover:bg-on-primary-fixed-variant transition-all shadow-sm hover:shadow-md transform hover:-translate-y-0.5 duration-300 hover:scale-105 hover:shadow-lg">
                    <?= esc($sc['hero_cta_text']['text_value'] ?? 'Shop Now') ?>
                </a>

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

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-sm mb-md">
            <div>
                <h2 class="font-headline-lg text-headline-lg text-on-surface">Featured Shops</h2>
                <p class="text-body-md text-on-surface-variant mt-xs">Discover top-rated local merchants and specialty stores</p>
            </div>
            <a class="text-primary font-button text-button hover:underline inline-flex items-center gap-xs font-semibold" href="<?= base_url('shops') ?>">
                <span>View All Shops</span>
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-md lg:gap-lg">

            <?php if (!empty($shops)): ?>

                <?php foreach ($shops as $s): ?>

                    <a href="<?= base_url('shop/' . ($s['slug'] ?? $s['id'])) ?>" class="glass-card bg-surface-container-lowest border border-outline-variant/20 rounded-2xl p-md md:p-lg flex flex-col items-center text-center group cursor-pointer transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:border-primary/40 relative overflow-hidden">

                        <!-- Ambient Glow -->
                        <div class="absolute inset-0 bg-gradient-to-b from-primary/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>

                        <!-- Shop Avatar with Gradient Ring -->
                        <div class="relative w-20 h-20 md:w-22 md:h-22 rounded-full p-1 bg-gradient-to-tr from-primary/30 via-primary/10 to-primary/50 group-hover:from-primary group-hover:to-primary/80 transition-all duration-300 shadow-sm shrink-0 mb-sm">
                            <div class="w-full h-full rounded-full bg-white flex items-center justify-center overflow-hidden">

                                <?php if (!empty($s['logo_url'])): ?>

                                    <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" src="<?= esc(logo_url($s['logo_url'])) ?>" alt="<?= esc($s['shop_name']) ?>">

                                <?php else: ?>

                                    <div class="w-full h-full bg-gradient-to-br from-primary/10 via-primary-container/20 to-primary/20 flex flex-col items-center justify-center text-primary font-bold">
                                        <span class="material-symbols-outlined text-[28px] mb-[-2px]">storefront</span>
                                        <span class="text-[10px] tracking-wider uppercase font-extrabold"><?= esc(substr($s['shop_name'], 0, 3)) ?></span>
                                    </div>

                                <?php endif; ?>

                            </div>
                        </div>

                        <!-- Shop Info -->
                        <div class="w-full space-y-xs flex flex-col items-center">
                            <h3 class="text-body-md font-bold text-on-surface group-hover:text-primary transition-colors line-clamp-1 w-full text-center" title="<?= esc($s['shop_name']) ?>">
                                <?= esc($s['shop_name']) ?>
                            </h3>

                            <!-- Rating & Reviews -->
                            <div class="flex items-center justify-center gap-xs text-xs text-on-surface-variant font-medium">
                                <span class="material-symbols-outlined text-[16px] text-amber-500 fill-icon">star</span>
                                <span class="font-bold text-on-surface"><?= number_format($s['rating_average'] ?? 5.0, 1) ?></span>
                                <span class="text-on-surface-variant/70 text-[11px]">(<?= number_format($s['rating_count'] ?? 0) ?>)</span>
                            </div>

                            <?php if (!empty($s['offers_printing'])): ?>
                                <span class="inline-flex items-center gap-xs text-[10px] font-bold text-primary bg-primary/10 px-xs py-[2px] rounded-full mt-xs">
                                    <span class="material-symbols-outlined text-[12px]">print</span>
                                    Printing
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-xs text-[10px] font-bold text-on-surface-variant bg-surface-container-high px-xs py-[2px] rounded-full mt-xs">
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
    <section id="catalog" class="bg-surface-container-low/50 py-8 md:py-12 lg:py-[80px] -mx-4 md:-mx-8 lg:-mx-10 px-4 md:px-8 lg:px-10 rounded-3xl">

        <div class="max-w-container-max mx-auto">

            <div class="flex flex-col md:flex-row justify-between items-end mb-xl gap-lg">

                <div>

                    <h2 class="font-headline-lg text-headline-lg mb-sm"><?= esc($catalogTitle) ?></h2>
                    <p class="text-body-md text-on-surface-variant"><?= esc($catalogSubtitle) ?></p>

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

                <div class="flex gap-sm">

                    <div class="flex items-center gap-sm bg-white border border-outline-variant/30 rounded-lg px-md py-sm cursor-pointer hover:bg-surface-container transition-colors">

                        <span class="material-symbols-outlined text-outline text-base">filter_list</span>
                        <span class="text-label-sm">Filter</span>

                    </div>

                    <div class="flex items-center gap-sm bg-white border border-outline-variant/30 rounded-lg px-md py-sm cursor-pointer hover:bg-surface-container transition-colors">

                        <span class="material-symbols-outlined text-outline text-base">swap_vert</span>
                        <span class="text-label-sm">Sort By</span>

                    </div>

                </div>

            </div>

            <div id="product-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-gutter">

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
                                <button type="button" class="absolute top-sm right-sm bg-white/80 backdrop-blur p-xs rounded-full opacity-0 group-hover:opacity-100 transition-opacity" aria-label="Add to favorites">

                                    <span class="material-symbols-outlined text-on-surface-variant">favorite</span>

                                </button>

                            </div>

                            <div class="p-md flex flex-col flex-grow">

                                <span class="text-label-sm text-primary font-medium mb-xs uppercase tracking-tighter"><?= esc($p['shop_name'] ?? 'RHK Store') ?></span>
                                <h4 class="font-title-lg text-body-md text-on-surface mb-xs group-hover:text-primary transition-colors line-clamp-1"><a href="<?= base_url('product/' . $p['id']) ?>"><?= esc($p['name']) ?></a></h4>

                                <div class="flex items-center gap-xs mb-md">

                                    <span class="material-symbols-outlined text-amber-500 text-xs" style="font-variation-settings: 'FILL' 1;">star</span>
                                    <span class="text-label-sm font-semibold"><?= number_format((float) $rating, 1) ?></span>

                                </div>

                                <div class="mt-auto flex justify-between items-center">

                                    <span class="font-headline-md text-title-lg text-on-surface">₱<?= number_format($p['price'], 2) ?></span>

                                    <form action="<?= base_url('cart/add') ?>" method="POST">

                                        <?= csrf_field() ?>

                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="quantity" value="1">

                                        <button type="submit" class="bg-surface-container-high p-sm rounded-lg hover:bg-primary-container hover:text-white transition-all" aria-label="Add to cart">

                                            <span class="material-symbols-outlined text-base">add_shopping_cart</span>

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

                <div class="mt-lg flex justify-center items-center gap-md">

                    <?php if ($currentPage > 1): ?>

                        <a href="<?= $pageUrl($currentPage - 1) ?>" class="w-10 h-10 rounded-lg border border-outline-variant flex items-center justify-center text-on-surface-variant hover:bg-surface-container transition-colors" aria-label="Previous page">

                            <span class="material-symbols-outlined">chevron_left</span>

                        </a>

                    <?php else: ?>

                        <span class="w-10 h-10 rounded-lg border border-outline-variant flex items-center justify-center text-outline-variant/50 cursor-not-allowed" aria-disabled="true">

                            <span class="material-symbols-outlined">chevron_left</span>

                        </span>

                    <?php endif; ?>

                    <div class="flex gap-sm">

                        <?php foreach ($windowPages as $pg): ?>

                            <?php if ($pg === '...'): ?>

                                <span class="flex items-end pb-2 px-1 text-outline-variant" aria-hidden="true">...</span>

                            <?php elseif ((int) $pg === $currentPage): ?>

                                <a href="<?= $pageUrl((int) $pg) ?>" aria-current="page" class="w-10 h-10 rounded-lg bg-primary text-on-primary flex items-center justify-center font-button text-button"><?= (int) $pg ?></a>

                            <?php else: ?>

                                <a href="<?= $pageUrl((int) $pg) ?>" class="w-10 h-10 rounded-lg border border-outline-variant flex items-center justify-center text-on-surface-variant hover:bg-surface-container transition-colors font-button text-button"><?= (int) $pg ?></a>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </div>

                    <?php if ($currentPage < $totalPages): ?>

                        <a href="<?= $pageUrl($currentPage + 1) ?>" class="w-10 h-10 rounded-lg border border-outline-variant flex items-center justify-center text-on-surface-variant hover:bg-surface-container transition-colors" aria-label="Next page">

                            <span class="material-symbols-outlined">chevron_right</span>

                        </a>

                    <?php else: ?>

                        <span class="w-10 h-10 rounded-lg border border-outline-variant flex items-center justify-center text-outline-variant/50 cursor-not-allowed" aria-disabled="true">

                            <span class="material-symbols-outlined">chevron_right</span>

                        </span>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

<?= $this->endSection() ?>