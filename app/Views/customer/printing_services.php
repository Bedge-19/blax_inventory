<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<?php
    $totalPages  = $totalPages ?? 1;
    $currentPage = $currentPage ?? 1;
    $totalShops  = (int) ($totalShops ?? 0);
    $searchQuery = $searchQuery ?? '';
    $sort        = $sort ?? 'top-rated';

    $compactCount = function ($n) {
        $n = (int) $n;
        if ($n >= 1000) {
            return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'k';
        }
        return number_format($n);
    };

    $sc = $siteContents ?? [];
    $psTitle = $sc['hero_title']['text_value'] ?? 'Professional Printing, Everywhere You Are.';
    $psSubtitle = $sc['hero_subtitle']['text_value'] ?? 'Every verified printing partner on our Polomolok network is equipped with commercial-grade production equipment. From quick thesis binding to large-format event tarpaulins, we bring the print shop to your fingertips.';
    $psImage = $sc['hero_image']['image_url'] ?? 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=1200&q=80';
    if ($psImage && !str_starts_with($psImage, 'http')) $psImage = base_url($psImage);
?>

<main class="max-w-container-max mx-auto px-4 md:px-8 lg:px-10 flex-grow w-full py-6 sm:py-8 md:py-10 flex flex-col gap-10 sm:gap-14 md:gap-20">

    <!-- Hero Section -->
    <section class="relative rounded-3xl overflow-hidden bg-white border border-slate-200/90 shadow-lg p-6 sm:p-10 lg:p-12">
        <!-- Ambient Background Gradients -->
        <div class="absolute -top-32 -left-32 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-80 h-80 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">

            <!-- Hero Left Column -->
            <div class="lg:col-span-7 flex flex-col gap-4">
                
                <div class="flex items-center">
                    <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-bold tracking-wide uppercase shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                        Polomolok On-Demand Print Network
                    </span>
                </div>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 leading-[1.15] tracking-tight">
                    <?= esc($psTitle) ?>
                </h1>

                <p class="text-sm sm:text-base text-slate-600 leading-relaxed font-medium">
                    <?= esc($psSubtitle) ?>
                </p>

                <!-- Dual Action CTAs -->
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <a href="<?= base_url('shops?category=printing') ?>" class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-blue-700 text-white text-xs sm:text-sm font-bold px-6 py-3 rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200">
                        <span class="material-symbols-outlined text-[18px]">upload_file</span>
                        <span>Start a Printing Request</span>
                    </a>

                    <a href="#master-printers" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-800 border border-slate-300 text-xs sm:text-sm font-bold px-6 py-3 rounded-xl shadow-2xs hover:shadow-xs hover:-translate-y-0.5 transition-all duration-200">
                        <span class="material-symbols-outlined text-[18px] text-primary">storefront</span>
                        <span>Browse Printing Shops</span>
                    </a>
                </div>

                <!-- Trust Metrics -->
                <div class="flex flex-wrap items-center gap-4 pt-3 text-xs text-slate-600 font-semibold border-t border-slate-100 mt-2">
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-emerald-600 text-[18px] fill-icon">verified</span>
                        <span>Verified Commercial Printers</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-amber-500 text-[18px] fill-icon">bolt</span>
                        <span>Rush & Same-Day Services</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-primary text-[18px]">local_shipping</span>
                        <span>Townwide Delivery & Pickup</span>
                    </div>
                </div>

            </div>

            <!-- Hero Right Column: Visual Mockup Showcase -->
            <div class="lg:col-span-5 relative">
                <div class="rounded-3xl overflow-hidden border border-slate-200/90 shadow-xl bg-slate-100 aspect-[4/3] sm:aspect-square relative group">
                    <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out" alt="Professional printing equipment and supplies" src="<?= esc($psImage) ?>">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent"></div>

                    <!-- Floating Certified Count Badge -->
                    <div class="absolute bottom-4 left-4 right-4 p-4 rounded-2xl bg-white/95 backdrop-blur-md border border-white/70 shadow-lg flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-primary text-white flex items-center justify-center shrink-0 shadow-xs">
                            <span class="material-symbols-outlined text-[24px]">print</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xl font-extrabold text-slate-900"><?= max(1, $totalShops) ?>+ Certified Print Shops</span>
                            </div>
                            <p class="text-xs text-slate-500 font-medium">Ready for your custom orders & rush requests</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Print Categories & Formats (Vistaprint Style Grid) -->
    <section class="flex flex-col gap-5">
        <div class="flex flex-col sm:flex-row justify-between sm:items-end gap-1">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-primary flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">format_paint</span>
                    Service Directory
                </span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Popular Printing Capabilities</h2>
                <p class="text-xs sm:text-sm text-slate-500">Choose a service category to connect with specialized local equipment</p>
            </div>
            <a href="<?= base_url('shops?category=printing') ?>" class="text-xs sm:text-sm font-bold text-primary hover:underline flex items-center gap-1 shrink-0">
                <span>View All Printing Shops</span>
                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            
            <!-- Category 1: Tarpaulins & Large Format -->
            <a href="<?= base_url('shops?category=printing') ?>" class="card-elevated rounded-2xl p-5 flex flex-col justify-between group">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-11 h-11 rounded-xl bg-blue-50 text-primary flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-[22px]">view_carousel</span>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-wide bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full border border-amber-200/60">Same-Day Ready</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 group-hover:text-primary transition-colors">Tarpaulins & Large Format</h3>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Outdoor banners, birthday backdrops, vinyl signage, event standees, and photo canvas prints.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-primary">
                    <span>Find Tarpaulin Printers</span>
                    <span class="material-symbols-outlined text-[16px] transform group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>

            <!-- Category 2: Documents & Thesis Binding -->
            <a href="<?= base_url('shops?category=printing') ?>" class="card-elevated rounded-2xl p-5 flex flex-col justify-between group">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-[22px]">menu_book</span>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-wide bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full border border-blue-200/60">High Speed Mono/Color</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 group-hover:text-primary transition-colors">Documents, Booklets & Binding</h3>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Thesis hardbound & softbound, book duplication, laminating, manuals, and school/office paperwork.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-indigo-600">
                    <span>Find Document Printers</span>
                    <span class="material-symbols-outlined text-[16px] transform group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>

            <!-- Category 3: Stickers & Decals -->
            <a href="<?= base_url('shops?category=printing') ?>" class="card-elevated rounded-2xl p-5 flex flex-col justify-between group">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-[22px]">loyalty</span>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-wide bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full border border-emerald-200/60">Die-Cut & Waterproof</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 group-hover:text-primary transition-colors">Stickers & Product Labels</h3>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Custom bottle labels, business branding stickers, vinyl laptop decals, and kiss-cut sheet printing.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-emerald-600">
                    <span>Find Sticker Cutters</span>
                    <span class="material-symbols-outlined text-[16px] transform group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>

            <!-- Category 4: Business Stationery -->
            <a href="<?= base_url('shops?category=printing') ?>" class="card-elevated rounded-2xl p-5 flex flex-col justify-between group">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-[22px]">badge</span>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-wide bg-purple-50 text-purple-700 px-2 py-0.5 rounded-full border border-purple-200/60">Commercial Forms</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 group-hover:text-primary transition-colors">Business Cards & Receipts</h3>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Premium calling cards, official receipts, sales invoices, carbonless forms, and company letterheads.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-purple-600">
                    <span>Find Commercial Printers</span>
                    <span class="material-symbols-outlined text-[16px] transform group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>

            <!-- Category 5: Apparel & Merchandise -->
            <a href="<?= base_url('shops?category=printing') ?>" class="card-elevated rounded-2xl p-5 flex flex-col justify-between group">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center group-hover:bg-rose-600 group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-[22px]">apparel</span>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-wide bg-rose-50 text-rose-700 px-2 py-0.5 rounded-full border border-rose-200/60">DTF & Sublimation</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 group-hover:text-primary transition-colors">Custom Apparel & Giveaways</h3>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Custom printed t-shirts, tote bags, caps, personalized mugs, keychains, and corporate giveaways.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-rose-600">
                    <span>Find Apparel Printers</span>
                    <span class="material-symbols-outlined text-[16px] transform group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>

            <!-- Category 6: Engineering & Blueprint CAD -->
            <a href="<?= base_url('shops?category=printing') ?>" class="card-elevated rounded-2xl p-5 flex flex-col justify-between group">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-11 h-11 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center group-hover:bg-teal-600 group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-[22px]">architecture</span>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-wide bg-teal-50 text-teal-700 px-2 py-0.5 rounded-full border border-teal-200/60">Architectural Plans</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 group-hover:text-primary transition-colors">Blueprints & Engineering CAD</h3>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Plotting for architects and engineers: A0, A1, A2 CAD plots, building plans, and schematic prints.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-teal-600">
                    <span>Find CAD Plotters</span>
                    <span class="material-symbols-outlined text-[16px] transform group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>

        </div>
    </section>

    <!-- How It Works: Interactive 4-Step Timeline -->
    <section class="rounded-3xl bg-slate-900 text-white p-6 sm:p-10 lg:p-12 relative overflow-hidden">
        <div class="absolute -top-24 -right-24 w-80 h-80 bg-primary/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="text-center max-w-xl mx-auto mb-10">
            <span class="text-xs font-bold uppercase tracking-widest text-primary-container bg-primary/20 px-3 py-1 rounded-full inline-block mb-2">Step-by-Step Workflow</span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-white">Seamless From File to Finish</h2>
            <p class="text-xs sm:text-sm text-slate-300 mt-1">Our integrated online printing workflow takes the friction out of custom print orders.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 sm:gap-6 relative z-10">
            
            <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 flex flex-col">
                <div class="w-10 h-10 rounded-xl bg-primary text-white flex items-center justify-center font-bold text-sm mb-3 shadow-xs">
                    01
                </div>
                <h4 class="text-sm font-bold text-white mb-1">Choose a Verified Shop</h4>
                <p class="text-xs text-slate-400 leading-relaxed">Browse local Polomolok shops based on equipment, location, and verified customer ratings.</p>
            </div>

            <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 flex flex-col">
                <div class="w-10 h-10 rounded-xl bg-primary text-white flex items-center justify-center font-bold text-sm mb-3 shadow-xs">
                    02
                </div>
                <h4 class="text-sm font-bold text-white mb-1">Upload Your Files</h4>
                <p class="text-xs text-slate-400 leading-relaxed">Easily upload PDF, PNG, JPG, or DOCX documents with your paper size, color, and finish preferences.</p>
            </div>

            <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 flex flex-col">
                <div class="w-10 h-10 rounded-xl bg-primary text-white flex items-center justify-center font-bold text-sm mb-3 shadow-xs">
                    03
                </div>
                <h4 class="text-sm font-bold text-white mb-1">Quote & Direct Approval</h4>
                <p class="text-xs text-slate-400 leading-relaxed">The merchant reviews specifications, provides transparent pricing, and prepares your proofs.</p>
            </div>

            <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-5 flex flex-col">
                <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-bold text-sm mb-3 shadow-xs">
                    04
                </div>
                <h4 class="text-sm font-bold text-white mb-1">Pickup or Fast Delivery</h4>
                <p class="text-xs text-slate-400 leading-relaxed">Collect in-store for free, or get your printed goods delivered directly to your doorstep.</p>
            </div>

        </div>
    </section>

    <!-- Master Printers Directory -->
    <section id="master-printers" class="flex flex-col gap-5">
        <div class="flex flex-col sm:flex-row justify-between sm:items-end gap-1">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-primary flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">verified</span>
                    Certified Partners
                </span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Master Printers in Polomolok</h2>
                <p class="text-xs sm:text-sm text-slate-500">Top-rated shops equipped for professional volume and custom finishes</p>
            </div>

            <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-full self-start sm:self-auto">
                <?= $totalShops ?> shops active
            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-5">
            <?php if (!empty($shops)): ?>
                <?php foreach ($shops as $s): ?>
                    <div class="card-elevated rounded-2xl overflow-hidden flex flex-col justify-between group">
                        
                        <!-- Header Banner & Avatar -->
                        <div>
                            <div class="h-14 bg-gradient-to-r from-blue-700/20 via-primary/20 to-indigo-600/20 relative">
                                <span class="absolute top-2 right-2 inline-flex items-center gap-1 bg-white/90 text-primary text-[9px] font-bold px-2 py-0.5 rounded-full shadow-2xs">
                                    <span class="material-symbols-outlined text-[11px]">print</span>
                                    <span>Print Partner</span>
                                </span>
                            </div>

                            <div class="p-3 sm:p-4 pt-0 flex flex-col items-center text-center -mt-7">
                                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-white shadow-md border border-slate-200/80 p-0.5 overflow-hidden mb-2.5 group-hover:scale-105 transition-transform">
                                    <?php if (!empty($s['logo_url'])): ?>
                                        <img class="w-full h-full object-cover rounded-full" src="<?= esc(logo_url($s['logo_url'])) ?>" alt="<?= esc($s['shop_name']) ?>">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-primary/10 rounded-full flex items-center justify-center text-primary font-bold">
                                            <span class="material-symbols-outlined text-[24px]">print</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <h3 class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-primary transition-colors line-clamp-1 w-full" title="<?= esc($s['shop_name']) ?>">
                                    <?= esc($s['shop_name']) ?>
                                </h3>

                                <div class="flex items-center justify-center gap-1 text-xs mt-1">
                                    <span class="material-symbols-outlined text-[13px] text-amber-500 fill-icon">star</span>
                                    <span class="font-bold text-slate-800"><?= number_format($s['rating_average'] ?? 5.0, 1) ?></span>
                                    <span class="text-slate-400 text-[10px]">(<?= $compactCount($s['rating_count'] ?? 0) ?>)</span>
                                </div>

                                <p class="text-[11px] text-slate-500 mt-2 line-clamp-2 leading-relaxed">
                                    <?= esc($s['description'] ?? 'Equipped for rush documents, tarpaulins, and custom printing services.') ?>
                                </p>
                            </div>
                        </div>

                        <!-- Card Action -->
                        <div class="p-3 sm:p-4 pt-0">
                            <a href="<?= base_url('shop/' . ($s['slug'] ?? $s['id'])) ?>" class="w-full py-2 bg-primary hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all text-center flex items-center justify-center gap-1 shadow-2xs">
                                <span>Visit Printer</span>
                                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-10 bg-white rounded-2xl border border-slate-200 text-slate-500 text-sm">
                    No printing service providers available currently.
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php
            $pageUrl = function (int $p) {
                return base_url('printing-services?page=' . $p);
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

    </section>

</main>

<?= $this->endSection() ?>
