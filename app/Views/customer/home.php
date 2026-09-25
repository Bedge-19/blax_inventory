<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<main class="flex-1 w-full max-w-container-max mx-auto px-4 md:px-8 lg:px-10 py-4 sm:py-8 md:py-10 flex flex-col gap-8 sm:gap-10 md:gap-14">

    <!-- Mobile Quick Search Bar -->
    <div class="block md:hidden w-full">
        <form action="<?= base_url('search') ?>" method="GET" class="flex items-center relative w-full shadow-xs">
            <span class="material-symbols-outlined absolute left-3.5 text-primary text-[20px]">search</span>
            <input name="q" value="<?= esc($searchQuery ?? '') ?>" maxlength="200" autocomplete="off" class="bg-white border border-slate-200 rounded-2xl pl-10 pr-4 py-3 w-full text-xs font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-primary focus:border-primary shadow-xs" placeholder="Describe what you need in Polomolok..." type="text">
        </form>
    </div>

    <!-- Hero Section — CMS Configurable with Dual-Intent Focus -->
    <?php
        $sort = $sort ?? null;
        $rotationInfo = $rotationInfo ?? \App\Models\ProductModel::getRotationInfo(3);
        $sc = $siteContents ?? [];
        $heroBadge = $sc['hero_badge']['text_value'] ?? 'Polomolok Premier Hub';
        $heroTitle = $sc['hero_title']['text_value'] ?? 'The Ultimate Merchandise & Printing Hub';
        $heroSubtitle = $sc['hero_subtitle']['text_value'] ?? 'Discover premium goods, exclusive deals, and top-tier printing services all in one place across Polomolok.';
        $rawHeroImg = !empty($sc['hero_image']['image_url']) 
            ? $sc['hero_image']['image_url'] 
            : (!empty($sc['hero_image']['text_value']) ? $sc['hero_image']['text_value'] : null);
        $heroImage = cms_image_url($rawHeroImg, 'https://images.unsplash.com/photo-1556742049-0a67daf64f42?auto=format&fit=crop&w=1440&q=80');
        $catalogTitle = $sc['catalog_title']['text_value'] ?? 'Global Product Catalog';
        $catalogSubtitle = $sc['catalog_subtitle']['text_value'] ?? 'Explore top-rated products from verified local merchants across Polomolok.';
    ?>
    <section class="relative w-full rounded-3xl overflow-hidden shadow-lg border border-slate-200/80 bg-white min-h-[340px] sm:min-h-[400px] lg:min-h-[460px] py-8 sm:py-12 flex items-center group">

        <!-- Background Image with Ambient Overlay -->
        <div class="absolute inset-0 bg-cover bg-center w-full h-full scale-105 filter blur-[3px] opacity-25 transition-transform duration-700 ease-out group-hover:scale-110 pointer-events-none" style="background-image: url('<?= esc($heroImage) ?>');"></div>
        
        <!-- High-Contrast Clean Gradient Backdrop -->
        <div class="absolute inset-0 pointer-events-none bg-gradient-to-r from-white via-white/95 sm:via-white/90 to-blue-50/70"></div>

        <!-- Ambient Glow Orbs -->
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-sky-400/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 w-full px-5 sm:px-10 lg:px-14 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">

            <!-- Hero Text Content -->
            <div class="lg:col-span-7 flex flex-col gap-4 max-w-2xl">

                <!-- Event / Location Badge -->
                <div class="flex items-center">
                    <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-bold tracking-wide uppercase shadow-2xs backdrop-blur-xs">
                        <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                        <?= esc($heroBadge) ?>
                    </span>
                </div>

                <!-- Headline -->
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 leading-[1.15] tracking-tight">
                    <?= esc($heroTitle) ?>
                </h1>

                <!-- Subtitle -->
                <p class="text-sm sm:text-base text-slate-600 leading-relaxed font-medium">
                    <?= esc($heroSubtitle) ?>
                </p>

                <!-- Dual Action CTAs -->
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <a href="#catalog" class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-blue-700 text-white text-xs sm:text-sm font-bold px-5 sm:px-6 py-3 rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200">
                        <span><?= esc($sc['hero_cta_text']['text_value'] ?? 'Explore Marketplace') ?></span>
                        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </a>

                    <a href="<?= base_url('printing-services') ?>" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-800 border border-slate-300/90 text-xs sm:text-sm font-bold px-5 sm:px-6 py-3 rounded-xl shadow-xs hover:shadow-sm hover:-translate-y-0.5 transition-all duration-200">
                        <span class="material-symbols-outlined text-[18px] text-primary">print</span>
                        <span>Custom Printing</span>
                    </a>

                    <?php if (!session()->get('isLoggedIn')): ?>
                        <a href="<?= base_url('signup') ?>" class="text-xs sm:text-sm font-semibold text-slate-500 hover:text-primary transition-colors px-3 py-2">
                            Create Account &rarr;
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Micro Trust Badges -->
                <div class="flex flex-wrap items-center gap-2.5 pt-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/90 border border-slate-200/90 text-slate-700 font-semibold shadow-2xs">
                        <span class="material-symbols-outlined text-[15px] text-emerald-600 fill-icon">verified</span>
                        <span>Polomolok Verified</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/90 border border-slate-200/90 text-slate-700 font-semibold shadow-2xs">
                        <span class="material-symbols-outlined text-[15px] text-amber-500 fill-icon">bolt</span>
                        <span>Rush Printing Available</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/90 border border-slate-200/90 text-slate-700 font-semibold shadow-2xs">
                        <span class="material-symbols-outlined text-[15px] text-primary">local_shipping</span>
                        <span>Townwide Delivery</span>
                    </span>
                </div>

            </div>

            <!-- Hero Right: Dynamic Showcase Floating Card (Desktop) -->
            <div class="hidden lg:flex lg:col-span-5 justify-end">
                <div class="w-full max-w-sm bg-white/95 backdrop-blur-md rounded-3xl p-5 border border-slate-200/90 shadow-xl relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-primary/10 rounded-full blur-2xl pointer-events-none"></div>

                    <!-- Spotlight Badge -->
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[11px] font-bold tracking-wider uppercase text-primary bg-primary/10 px-2.5 py-1 rounded-full flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">local_fire_department</span>
                            Hub Highlights
                        </span>
                        <span class="text-xs text-slate-500 font-medium">Polomolok, SC</span>
                    </div>

                    <!-- Hub Features Mini List -->
                    <div class="space-y-3">
                        <a href="<?= base_url('printing-services') ?>" class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 hover:bg-blue-50/50 border border-slate-100 hover:border-primary/30 transition-all group">
                            <div class="w-10 h-10 rounded-xl bg-primary text-white flex items-center justify-center shrink-0 shadow-xs">
                                <span class="material-symbols-outlined text-[20px]">print</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-slate-800 group-hover:text-primary transition-colors flex items-center justify-between">
                                    <span>Print Requests Online</span>
                                    <span class="material-symbols-outlined text-[16px] text-slate-400 group-hover:text-primary transform group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                                </div>
                                <div class="text-[11px] text-slate-500 truncate">Tarpaulins, Documents, Shirts & Decals</div>
                            </div>
                        </a>

                        <a href="#catalog" class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 hover:bg-blue-50/50 border border-slate-100 hover:border-primary/30 transition-all group">
                            <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                                <span class="material-symbols-outlined text-[20px]">shopping_bag</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-slate-800 group-hover:text-primary transition-colors flex items-center justify-between">
                                    <span>Direct Local Catalog</span>
                                    <span class="material-symbols-outlined text-[16px] text-slate-400 group-hover:text-primary transform group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                                </div>
                                <div class="text-[11px] text-slate-500 truncate">Supplies, stationery & specialty items</div>
                            </div>
                        </a>

                        <a href="<?= base_url('shops') ?>" class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 hover:bg-blue-50/50 border border-slate-100 hover:border-primary/30 transition-all group">
                            <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                                <span class="material-symbols-outlined text-[20px]">storefront</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-slate-800 group-hover:text-primary transition-colors flex items-center justify-between">
                                    <span>Verified Merchants</span>
                                    <span class="material-symbols-outlined text-[16px] text-slate-400 group-hover:text-primary transform group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                                </div>
                                <div class="text-[11px] text-slate-500 truncate">Support local stores in Polomolok</div>
                            </div>
                        </a>
                    </div>

                    <!-- Bottom Live Status -->
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            All systems operational
                        </span>
                        <span class="font-semibold text-slate-700">Same-day pickup</span>
                    </div>

                </div>
            </div>

        </div>

    </section>

    <!-- Quick Category Pill Rail -->
    <?php if (!empty($categories)): ?>
        <section class="w-full">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-primary">category</span>
                    Browse by Department
                </span>
                <a href="<?= base_url('categories') ?>" class="text-xs font-semibold text-primary hover:underline flex items-center gap-0.5">
                    <span>All Categories</span>
                    <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </a>
            </div>

            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 -mx-4 px-4 sm:mx-0 sm:px-0">
                <?php
                    $allParams = [];
                    if (!empty($sort) && $sort !== 'discovery') $allParams['sort'] = $sort;
                    $allUrl = base_url(!empty($allParams) ? '?' . http_build_query($allParams) : '/') . '#catalog';
                ?>
                <a href="<?= $allUrl ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all duration-200 <?= empty($categoryId) ? 'bg-primary text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/90 shadow-2xs' ?>">
                    <span class="material-symbols-outlined text-[15px]">auto_awesome</span>
                    <span>All Items</span>
                </a>

                <?php foreach ($categories as $cat): ?>
                    <?php 
                        $isCatActive = ((int) ($categoryId ?? 0) === (int) $cat['id']);
                        $catParams = ['category_id' => $cat['id']];
                        if (!empty($sort) && $sort !== 'discovery') $catParams['sort'] = $sort;
                    ?>
                    <a href="<?= base_url('?' . http_build_query($catParams)) ?>#catalog" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all duration-200 <?= $isCatActive ? 'bg-primary text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/90 shadow-2xs' ?>">
                        <span><?= esc($cat['name']) ?></span>
                    </a>
                <?php endforeach; ?>

                <a href="<?= base_url('printing-services') ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all duration-200 bg-blue-50 text-primary border border-primary/30 hover:bg-primary hover:text-white shadow-2xs">
                    <span class="material-symbols-outlined text-[15px]">print</span>
                    <span>Printing Services</span>
                </a>
            </div>
        </section>
    <?php endif; ?>

    <!-- Search Results: Matching Shops (if searching) -->
    <?php if (($searchQuery ?? '') !== ''): ?>
        <section class="flex flex-col gap-4">
            <div class="flex justify-between items-center">
                <h2 class="font-extrabold text-xl sm:text-2xl text-slate-900">Shops matching &quot;<?= esc($searchQuery) ?>&quot;</h2>
                <a class="text-primary font-bold text-xs sm:text-sm hover:underline flex items-center gap-1" href="<?= base_url('shops?q=' . urlencode($searchQuery)) ?>">
                    <span>View All Shops</span>
                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </a>
            </div>

            <?php if (!empty($shopResults)): ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                    <?php foreach ($shopResults as $s): ?>
                        <a href="<?= base_url('shop/' . ($s['slug'] ?? $s['id'])) ?>" class="card-elevated rounded-2xl p-4 flex flex-col items-center text-center group cursor-pointer">
                            <div class="w-16 h-16 rounded-full border-2 border-primary/20 overflow-hidden mb-2 bg-slate-100 flex items-center justify-center group-hover:scale-105 transition-transform">
                                <?php if (!empty($s['logo_url'])): ?>
                                    <img class="w-full h-full object-cover" src="<?= esc(logo_url($s['logo_url'])) ?>" alt="<?= esc($s['shop_name']) ?>">
                                <?php else: ?>
                                    <span class="material-symbols-outlined text-primary text-3xl">storefront</span>
                                <?php endif; ?>
                            </div>
                            <span class="text-xs font-bold text-slate-900 group-hover:text-primary transition-colors line-clamp-1 w-full"><?= esc($s['shop_name']) ?></span>
                            <span class="text-[10px] text-slate-500 mt-0.5">Verified Shop</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-slate-500 text-xs">No shops matched &quot;<?= esc($searchQuery) ?>&quot;.</p>
            <?php endif; ?>
        </section>
    <?php else: ?>

    <!-- Featured Shops - Rich Merchant Cards -->
    <section class="flex flex-col gap-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-1">
            <div>
                <h2 class="font-extrabold text-xl sm:text-2xl text-slate-900">Featured Local Shops</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Top-rated local merchants and certified printing shops across Polomolok</p>
            </div>
            <a class="text-primary text-xs sm:text-sm font-bold hover:underline inline-flex items-center gap-1 shrink-0" href="<?= base_url('shops') ?>">
                <span>View All Shops</span>
                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        </div>

        <div class="flex overflow-x-auto no-scrollbar snap-x snap-mandatory gap-2.5 sm:gap-4 pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 sm:grid sm:grid-cols-3 lg:grid-cols-5">
            <?php if (!empty($shops)): ?>
                <?php foreach ($shops as $s): ?>
                    <a href="<?= base_url('shop/' . ($s['slug'] ?? $s['id'])) ?>" class="card-elevated bg-white border border-slate-200/90 shadow-2xs hover:shadow-md hover:border-primary/40 rounded-2xl overflow-hidden flex flex-col group transition-all duration-300 w-[140px] sm:w-auto shrink-0 snap-start">
                        
                        <!-- Mini Decorative Banner Header -->
                        <div class="h-10 sm:h-12 w-full bg-gradient-to-r from-blue-600/15 via-indigo-600/20 to-primary/15 relative">
                            <?php if (!empty($s['offers_printing'])): ?>
                                <span class="absolute top-1.5 right-1.5 sm:top-2 sm:right-2 inline-flex items-center gap-0.5 sm:gap-1 bg-white/90 text-primary text-[8px] sm:text-[9px] font-bold px-1.5 sm:px-2 py-0.5 rounded-full shadow-2xs">
                                    <span class="material-symbols-outlined text-[10px] sm:text-[11px]">print</span>
                                    <span>Print</span>
                                </span>
                            <?php else: ?>
                                <span class="absolute top-1.5 right-1.5 sm:top-2 sm:right-2 inline-flex items-center gap-0.5 sm:gap-1 bg-white/90 text-slate-700 text-[8px] sm:text-[9px] font-bold px-1.5 sm:px-2 py-0.5 rounded-full shadow-2xs">
                                    <span class="material-symbols-outlined text-[10px] sm:text-[11px]">storefront</span>
                                    <span>Retail</span>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Card Body -->
                        <div class="p-2.5 sm:p-4 pt-0 flex flex-col items-center text-center flex-grow -mt-5 sm:-mt-6">
                            
                            <!-- Overlapping Avatar -->
                            <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full p-0.5 bg-white shadow-md border border-slate-200/80 overflow-hidden mb-2 sm:mb-2.5 group-hover:scale-105 transition-transform shrink-0">
                                <?php if (!empty($s['logo_url'])): ?>
                                    <img class="w-full h-full object-cover rounded-full" src="<?= esc(logo_url($s['logo_url'])) ?>" alt="<?= esc($s['shop_name']) ?>">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-br from-primary/10 to-blue-100 rounded-full flex flex-col items-center justify-center text-primary font-black">
                                        <span class="material-symbols-outlined text-[18px] sm:text-[20px]">storefront</span>
                                        <span class="text-[8px] sm:text-[9px] tracking-wider uppercase"><?= esc(substr($s['shop_name'], 0, 3)) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Shop Name -->
                            <h3 class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-primary transition-colors line-clamp-1 w-full" title="<?= esc($s['shop_name']) ?>">
                                <?= esc($s['shop_name']) ?>
                            </h3>

                            <!-- Rating & Reviews -->
                            <div class="flex items-center justify-center gap-0.5 sm:gap-1 mt-0.5 sm:mt-1 text-[10px] sm:text-xs">
                                <span class="material-symbols-outlined text-[12px] sm:text-[14px] text-amber-500 fill-icon">star</span>
                                <span class="font-bold text-slate-800"><?= number_format($s['rating_average'] ?? 5.0, 1) ?></span>
                                <span class="text-slate-400 text-[9px] sm:text-[10px]">(<?= number_format($s['rating_count'] ?? 0) ?>)</span>
                            </div>

                            <!-- Visit Trigger -->
                            <div class="w-full mt-2.5 sm:mt-3 pt-2 sm:pt-2.5 border-t border-slate-100 flex items-center justify-center gap-1 text-[10px] sm:text-[11px] font-bold text-primary group-hover:text-blue-700">
                                <span>Visit Store</span>
                                <span class="material-symbols-outlined text-[12px] sm:text-[14px] transform group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                            </div>

                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-8 bg-white rounded-2xl border border-slate-200 text-slate-500 text-sm">
                    No shops available at the moment.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php endif; ?>

    <!-- Global Product Catalog Section -->
    <section id="catalog" class="w-full pt-4">

        <div class="flex flex-col sm:flex-row justify-between sm:items-end items-start mb-6 gap-3">
            <div>
                <div class="flex flex-wrap items-center gap-2.5 mb-1.5">
                    <h2 class="font-extrabold text-2xl sm:text-3xl text-slate-900"><?= esc($catalogTitle) ?></h2>
                    
                    <!-- Dynamic 3-Hour Discovery Rotation Badge -->
                    <?php if (empty($sort) || $sort === 'discovery'): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs" title="Featured showcase rotates every 3 hours across all shops">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>3h Discovery &bull; <span id="catalog-rotation-timer" class="font-bold" data-seconds="<?= (int) ($rotationInfo['seconds_remaining'] ?? 0) ?>"><?= esc($rotationInfo['formatted_time_left'] ?? '3h') ?> left</span></span>
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                            <span class="material-symbols-outlined text-[14px] text-primary">sort</span>
                            <span>Sorted by <?= esc(ucwords(str_replace('_', ' ', $sort))) ?></span>
                            <a href="<?= base_url(empty($categoryId) ? '/' : '?category_id=' . $categoryId) ?>#catalog" class="ml-1 text-primary font-bold hover:underline" title="Switch back to 3-hour discovery rotation">&times; Reset</a>
                        </span>
                    <?php endif; ?>
                </div>

                <p class="text-xs sm:text-sm text-slate-500"><?= esc($catalogSubtitle) ?></p>

                <?php if (($searchQuery ?? '') !== ''): ?>
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        <?php if (!empty($semantic)): ?>
                            <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                                <span class="material-symbols-outlined text-xs">auto_awesome</span>
                                AI matched
                            </span>
                        <?php endif; ?>
                        <span class="text-xs text-slate-500">
                            <?= (int) ($totalProducts ?? 0) ?> result<?= ((int) ($totalProducts ?? 0)) === 1 ? '' : 's' ?> for &quot;<?= esc($searchQuery) ?>&quot;
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Filter & Sort Bar -->
            <div class="flex items-center gap-2 shrink-0">
                <a href="<?= base_url('categories') ?>" class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors shadow-2xs">
                    <span class="material-symbols-outlined text-[16px] text-slate-500">filter_list</span>
                    <span>Categories</span>
                </a>

                <!-- Sort By Dropdown -->
                <div class="relative" id="sort-dropdown-container">
                    <button type="button" id="sort-menu-btn" onclick="toggleSortMenu(event)" class="flex items-center gap-1.5 bg-white border <?= (!empty($sort) && $sort !== 'discovery') ? 'border-primary text-primary font-bold' : 'border-slate-200 text-slate-700 font-semibold' ?> rounded-xl px-3.5 py-2 text-xs hover:bg-slate-50 transition-colors shadow-2xs">
                        <span class="material-symbols-outlined text-[16px]">swap_vert</span>
                        <span>
                            <?php
                                $sortLabels = [
                                    'discovery'  => 'Discovery (3h)',
                                    'rating'     => 'Top Rated',
                                    'price_asc'  => 'Price: Low-High',
                                    'price_desc' => 'Price: High-Low',
                                    'newest'     => 'Newest',
                                ];
                                echo esc($sortLabels[$sort ?? 'discovery'] ?? 'Sort By');
                            ?>
                        </span>
                        <span class="material-symbols-outlined text-[14px]">expand_more</span>
                    </button>

                    <div id="sort-menu" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-200 py-2 z-40">
                        <?php
                            $buildSortLink = function($s) use ($categoryId, $searchQuery) {
                                $p = [];
                                if ($s !== 'discovery') $p['sort'] = $s;
                                if (!empty($categoryId)) $p['category_id'] = $categoryId;
                                if ($searchQuery !== '') $p['q'] = $searchQuery;
                                return base_url(!empty($p) ? '?' . http_build_query($p) : '/') . '#catalog';
                            };
                        ?>
                        <a href="<?= $buildSortLink('discovery') ?>" class="flex items-center gap-2 px-3.5 py-2 text-xs hover:bg-slate-50 transition-colors <?= (empty($sort) || $sort === 'discovery') ? 'text-primary font-bold bg-blue-50' : 'text-slate-700' ?>">
                            <span class="material-symbols-outlined text-[16px] text-primary">autorenew</span>
                            <div>
                                <div>Discovery Rotation</div>
                                <div class="text-[10px] text-slate-400 font-normal">Rotates every 3 hours</div>
                            </div>
                        </a>
                        <a href="<?= $buildSortLink('rating') ?>" class="flex items-center gap-2 px-3.5 py-2 text-xs hover:bg-slate-50 transition-colors <?= $sort === 'rating' ? 'text-primary font-bold bg-blue-50' : 'text-slate-700' ?>">
                            <span class="material-symbols-outlined text-[16px] text-amber-500 fill-icon">star</span>
                            <span>Top Rated First</span>
                        </a>
                        <a href="<?= $buildSortLink('price_asc') ?>" class="flex items-center gap-2 px-3.5 py-2 text-xs hover:bg-slate-50 transition-colors <?= $sort === 'price_asc' ? 'text-primary font-bold bg-blue-50' : 'text-slate-700' ?>">
                            <span class="material-symbols-outlined text-[16px]">arrow_upward</span>
                            <span>Price: Low to High</span>
                        </a>
                        <a href="<?= $buildSortLink('price_desc') ?>" class="flex items-center gap-2 px-3.5 py-2 text-xs hover:bg-slate-50 transition-colors <?= $sort === 'price_desc' ? 'text-primary font-bold bg-blue-50' : 'text-slate-700' ?>">
                            <span class="material-symbols-outlined text-[16px]">arrow_downward</span>
                            <span>Price: High to Low</span>
                        </a>
                        <a href="<?= $buildSortLink('newest') ?>" class="flex items-center gap-2 px-3.5 py-2 text-xs hover:bg-slate-50 transition-colors <?= $sort === 'newest' ? 'text-primary font-bold bg-blue-50' : 'text-slate-700' ?>">
                            <span class="material-symbols-outlined text-[16px]">new_releases</span>
                            <span>Newest Arrivals</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Grid: 2 cols on mobile, 3 on tablet, 4-5 on desktop -->
        <div id="product-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4 md:gap-5">

            <?php if (!empty($products)): ?>
                <?php foreach ($products as $p): ?>
                    <?php
                        $imageUrl = product_image_url($p['image_url'] ?? null);
                        $rating   = $p['rating_average'] ?? 0;
                    ?>
                    <div class="card-elevated rounded-2xl overflow-hidden flex flex-col group">
                        
                        <!-- Image Container with Hover Zoom -->
                        <div class="relative aspect-square bg-slate-50 overflow-hidden">
                            <a href="<?= base_url('product/' . $p['id']) ?>" class="block w-full h-full">
                                <img src="<?= esc($imageUrl) ?>" alt="<?= esc($p['name']) ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                            </a>
                            
                            <!-- Merchant Tag Badge -->
                            <span class="absolute top-2 left-2 bg-white/90 backdrop-blur-xs text-slate-800 text-[9px] font-bold px-2 py-0.5 rounded-full shadow-2xs truncate max-w-[120px]">
                                <?= esc($p['shop_name'] ?? 'Blax Store') ?>
                            </span>

                            <button type="button" class="absolute top-2 right-2 bg-white/90 backdrop-blur-xs hover:bg-white text-slate-500 hover:text-red-500 p-1.5 rounded-full opacity-0 group-hover:opacity-100 transition-all shadow-xs" aria-label="Add to favorites">
                                <span class="material-symbols-outlined text-[15px]">favorite</span>
                            </button>
                        </div>

                        <!-- Card Details -->
                        <div class="p-3 sm:p-4 flex flex-col flex-grow">
                            
                            <h4 class="text-xs sm:text-sm font-semibold text-slate-900 group-hover:text-primary transition-colors line-clamp-2 leading-snug mb-1.5">
                                <a href="<?= base_url('product/' . $p['id']) ?>"><?= esc($p['name']) ?></a>
                            </h4>

                            <!-- Rating -->
                            <div class="flex items-center gap-1 mb-3">
                                <span class="material-symbols-outlined text-amber-500 text-[13px] fill-icon">star</span>
                                <span class="text-[11px] font-bold text-slate-700"><?= number_format((float) $rating, 1) ?></span>
                            </div>

                            <!-- Footer Price & Add To Cart -->
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
                <div class="col-span-full text-center py-12 bg-white rounded-3xl border border-slate-200 p-6">
                    <span class="material-symbols-outlined text-5xl text-slate-300 mb-2">inventory_2</span>
                    <?php if (($searchQuery ?? '') !== ''): ?>
                        <p class="text-slate-800 font-bold text-sm">No products matched &quot;<?= esc($searchQuery) ?>&quot;.</p>
                        <p class="text-xs text-slate-500 mt-1">Try searching with other terms or in Bisaya/Tagalog (e.g. &quot;pang sulat nga blue&quot; or &quot;gamit pang drawing&quot;).</p>
                        <a href="<?= base_url('/') ?>" class="inline-block mt-4 text-xs font-bold text-primary hover:underline">Clear Search Filter</a>
                    <?php else: ?>
                        <p class="text-slate-600 font-medium text-sm">No products currently available in this category.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>

        <!-- Pagination Controls -->
        <?php
            $totalPages   = $totalPages ?? 1;
            $currentPage  = $currentPage ?? 1;
            $searchQuery  = $searchQuery ?? '';
            $categoryId   = $categoryId ?? null;
            $sortParam    = (!empty($sort) && $sort !== 'discovery') ? $sort : null;
            $pageUrl = function (int $p) use ($searchQuery, $categoryId, $sortParam) {
                $params = ['page' => $p];
                if ($searchQuery !== '') $params['q'] = $searchQuery;
                if (!empty($categoryId)) $params['category_id'] = $categoryId;
                if (!empty($sortParam)) $params['sort'] = $sortParam;
                return base_url('?' . http_build_query($params)) . '#catalog';
            };

            $windowPages = [];
            if ($totalPages <= 7) {
                $windowPages = range(1, $totalPages);
            } else {
                $windowPages = [1];
                $left = max(2, $currentPage - 2);
                $right = min($totalPages - 1, $currentPage + 2);
                if ($left > 2) $windowPages[] = '...';
                for ($i = $left; $i <= $right; $i++) $windowPages[] = $i;
                if ($right < $totalPages - 1) $windowPages[] = '...';
                $windowPages[] = $totalPages;
            }
        ?>

        <?php if ($totalPages > 1): ?>
            <div class="mt-8 flex flex-wrap justify-center items-center gap-1.5 sm:gap-2 px-2">
                <?php if ($currentPage > 1): ?>
                    <a href="<?= $pageUrl($currentPage - 1) ?>" class="w-9 h-9 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors shadow-2xs" aria-label="Previous page">
                        <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                    </a>
                <?php else: ?>
                    <span class="w-9 h-9 rounded-xl border border-slate-100 bg-slate-50 flex items-center justify-center text-slate-300 cursor-not-allowed">
                        <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                    </span>
                <?php endif; ?>

                <div class="flex flex-wrap items-center gap-1">
                    <?php foreach ($windowPages as $pg): ?>
                        <?php if ($pg === '...'): ?>
                            <span class="px-2 text-slate-400 text-xs font-semibold">...</span>
                        <?php elseif ((int) $pg === $currentPage): ?>
                            <a href="<?= $pageUrl((int) $pg) ?>" aria-current="page" class="w-9 h-9 rounded-xl bg-primary text-white flex items-center justify-center font-bold text-xs shadow-xs"><?= (int) $pg ?></a>
                        <?php else: ?>
                            <a href="<?= $pageUrl((int) $pg) ?>" class="w-9 h-9 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-700 hover:bg-slate-50 transition-colors font-semibold text-xs"><?= (int) $pg ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= $pageUrl($currentPage + 1) ?>" class="w-9 h-9 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors shadow-2xs" aria-label="Next page">
                        <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                    </a>
                <?php else: ?>
                    <span class="w-9 h-9 rounded-xl border border-slate-100 bg-slate-50 flex items-center justify-center text-slate-300 cursor-not-allowed">
                        <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </section>

    <!-- Local Community Trust Grid (4 Pillars) -->
    <section class="mt-4 pt-10 border-t border-slate-200/80">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <div class="card-elevated rounded-2xl p-5 flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-primary flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[24px]">verified</span>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-900 mb-1">Polomolok Verified</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">Direct support for vetted neighborhood entrepreneurs and certified printers.</p>
                </div>
            </div>

            <div class="card-elevated rounded-2xl p-5 flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[24px]">print</span>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-900 mb-1">Rush Printing Ready</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">Upload documents or merch files online with instant quote transparency.</p>
                </div>
            </div>

            <div class="card-elevated rounded-2xl p-5 flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[24px]">local_shipping</span>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-900 mb-1">Pickup or Fast Delivery</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">Convenient in-store local pickup or direct delivery right to your door.</p>
                </div>
            </div>

            <div class="card-elevated rounded-2xl p-5 flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[24px]">shield</span>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-900 mb-1">Buyer Guarantee</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">Every purchase backed by verified merchant accountability & customer service.</p>
                </div>
            </div>

        </div>
    </section>

</main>

<script>
function toggleSortMenu(e) {
    e.stopPropagation();
    const menu = document.getElementById('sort-menu');
    if (menu) menu.classList.toggle('hidden');
}

document.addEventListener('click', function(e) {
    const container = document.getElementById('sort-dropdown-container');
    const menu = document.getElementById('sort-menu');
    if (container && menu && !container.contains(e.target)) {
        menu.classList.add('hidden');
    }
});

// Live 3-Hour Dynamic Rotation Countdown
document.addEventListener('DOMContentLoaded', function() {
    const timerEl = document.getElementById('catalog-rotation-timer');
    if (!timerEl) return;

    let seconds = parseInt(timerEl.getAttribute('data-seconds'), 10) || 0;

    function formatTime(s) {
        if (s <= 0) return '0s';
        const h = Math.floor(s / 3600);
        const m = Math.floor((s % 3600) / 60);
        const sec = s % 60;
        if (h > 0) return `${h}h ${m}m`;
        if (m > 0) return `${m}m ${sec}s`;
        return `${sec}s`;
    }

    if (seconds > 0) {
        setInterval(function() {
            if (seconds > 0) {
                seconds--;
                timerEl.textContent = formatTime(seconds) + ' left';
            } else {
                timerEl.textContent = 'Refreshed!';
            }
        }, 1000);
    }
});
</script>

<?= $this->endSection() ?>