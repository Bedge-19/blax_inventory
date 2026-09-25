<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<?php
    $totalPages  = $totalPages ?? 1;
    $currentPage = $currentPage ?? 1;
    $trending    = $trending ?? [];
?>

<main class="flex-grow w-full max-w-container-max mx-auto px-4 md:px-8 lg:px-10 py-6 sm:py-8 md:py-10 flex flex-col gap-8 sm:gap-10 md:gap-12">

    <!-- Header & Breadcrumbs -->
    <section class="flex flex-col gap-3">
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
                    <span class="text-slate-800 font-bold ml-1">Categories</span>
                </li>
            </ol>
        </nav>

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pt-1">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider mb-2">
                    <span class="material-symbols-outlined text-[14px]">grid_view</span>
                    Marketplace Taxonomy
                </span>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Explore Categories</h1>
                <p class="text-sm sm:text-base text-slate-500 mt-1 max-w-2xl">Browse our extensive collection of merchandise and printing supplies curated across Polomolok.</p>
            </div>

            <!-- Instant Client-Side Category Filter Bar -->
            <div class="w-full md:w-72 relative">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                <input id="category-search-input" type="text" placeholder="Filter categories..." class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs font-medium placeholder:text-slate-400 focus:ring-2 focus:ring-primary focus:border-primary shadow-2xs">
            </div>
        </div>
    </section>

    <!-- Trending Categories (High-Impact Showcase Cards) -->
    <?php if (!empty($trending)): ?>
        <section class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-amber-500/15 text-amber-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px] fill-icon">trending_up</span>
                    </div>
                    <div>
                        <h2 class="text-lg sm:text-xl font-extrabold text-slate-900">Trending Categories</h2>
                        <p class="text-xs text-slate-500">Most active departments this week</p>
                    </div>
                </div>

                <a href="#all-categories" class="text-xs sm:text-sm font-bold text-primary hover:underline flex items-center gap-1">
                    <span>View All</span>
                    <span class="material-symbols-outlined text-[16px]">arrow_downward</span>
                </a>
            </div>

            <!-- Trending Cards Grid (3 Columns) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                <?php foreach ($trending as $index => $tCat): ?>
                    <?php 
                        $isRank1 = ($index === 0);
                        $isRank2 = ($index === 1);
                        
                        $badgeBg = $isRank1 
                            ? 'bg-amber-500 text-white' 
                            : ($isRank2 ? 'bg-blue-600 text-white' : 'bg-indigo-600 text-white');
                        $badgeText = $isRank1 
                            ? '#1 Trending' 
                            : ($isRank2 ? '#2 Trending' : '#3 Trending');
                        $badgeIcon = $isRank1 ? 'local_fire_department' : 'trending_up';
                        $imgSrc = product_image_url($tCat['image_url'] ?? null, 'card');
                    ?>
                    <a href="<?= base_url('category/' . ($tCat['slug'] ?? $tCat['id'])) ?>" 
                       class="card-elevated bg-white border <?= $isRank1 ? 'border-amber-300/80 shadow-md' : 'border-slate-200/90 shadow-2xs' ?> hover:border-primary/50 hover:shadow-lg rounded-2xl overflow-hidden flex flex-col justify-between group transition-all duration-300">
                        
                        <!-- Top Image Showcase Banner -->
                        <div class="h-44 sm:h-48 w-full overflow-hidden relative bg-slate-100">
                            <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" 
                                 src="<?= esc($imgSrc) ?>" 
                                 alt="<?= esc($tCat['name']) ?>" 
                                 loading="lazy"
                                 onerror="this.src='https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=600&q=80';">
                            
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent"></div>

                            <!-- Rank Pill Badge -->
                            <div class="absolute top-3 left-3">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] sm:text-xs font-black uppercase tracking-wider <?= $badgeBg ?> shadow-md">
                                    <span class="material-symbols-outlined text-[13px] fill-icon"><?= $badgeIcon ?></span>
                                    <span><?= $badgeText ?></span>
                                </span>
                            </div>

                            <!-- Item Count Pill -->
                            <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between text-white">
                                <span class="text-xs font-bold bg-black/50 backdrop-blur-xs px-2.5 py-0.5 rounded-full border border-white/20">
                                    <?= (int) ($tCat['product_count'] ?? 0) ?> items available
                                </span>
                            </div>
                        </div>

                        <!-- Card Info Footer -->
                        <div class="p-4 flex items-center justify-between gap-3 bg-white">
                            <div class="min-w-0">
                                <h3 class="text-sm sm:text-base font-extrabold text-slate-900 group-hover:text-primary transition-colors truncate">
                                    <?= esc($tCat['name']) ?>
                                </h3>
                                <p class="text-[11px] text-slate-500 font-medium mt-0.5">Explore catalog &amp; deals</p>
                            </div>

                            <span class="w-9 h-9 rounded-xl <?= $isRank1 ? 'bg-amber-500/10 text-amber-700 group-hover:bg-amber-500 group-hover:text-white' : 'bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white' ?> transition-colors flex items-center justify-center shrink-0 shadow-2xs">
                                <span class="material-symbols-outlined text-[18px]">arrow_outward</span>
                            </span>
                        </div>

                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- All Categories Grid -->
    <section id="all-categories" class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">category</span>
                <h2 class="text-xl font-extrabold text-slate-900">All Departments</h2>
            </div>
            <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-full"><?= count($categories ?? []) ?> categories</span>
        </div>

        <div id="all-categories-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-4 md:gap-5">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                    <a class="category-card card-elevated rounded-2xl overflow-hidden flex flex-col group transition-all duration-300" href="<?= base_url('category/' . ($cat['slug'] ?? $cat['id'])) ?>" data-name="<?= esc(strtolower($cat['name'])) ?>">
                        
                        <div class="aspect-[4/3] w-full overflow-hidden relative bg-slate-100">
                            <?php if (!empty($cat['image_url'])): ?>
                                <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" 
                                     src="<?= esc(product_image_url($cat['image_url'], 'card')) ?>" 
                                     alt="<?= esc($cat['name']) ?>" 
                                     loading="lazy"
                                     onerror="this.src='https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=600&q=80';">
                            <?php else: ?>
                                <div class="w-full h-full bg-gradient-to-br from-blue-50 to-slate-100 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary/70 text-4xl group-hover:scale-110 transition-transform">category</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="absolute inset-0 bg-slate-900/10 group-hover:bg-transparent transition-colors"></div>
                        </div>

                        <div class="p-3.5 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-primary transition-colors truncate">
                                    <?= esc($cat['name']) ?>
                                </h3>
                                <span class="text-[11px] text-slate-400 block mt-0.5">Explore Products</span>
                            </div>

                            <div class="w-7 h-7 rounded-lg bg-slate-50 text-slate-400 group-hover:bg-primary group-hover:text-white transition-colors flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                            </div>
                        </div>

                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-slate-500 text-sm col-span-full py-8 text-center bg-white rounded-2xl border border-slate-200">No categories available.</p>
            <?php endif; ?>
        </div>

        <!-- No search matches feedback element -->
        <div id="no-cat-matches" class="hidden col-span-full text-center py-10 bg-white rounded-2xl border border-slate-200">
            <span class="material-symbols-outlined text-4xl text-slate-300 mb-1">search_off</span>
            <p class="text-xs font-semibold text-slate-600">No categories match your search.</p>
        </div>

        <!-- Pagination -->
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
                    <span class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-100 bg-slate-50 text-slate-300 cursor-not-allowed" aria-disabled="true">
                        <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                    </span>
                <?php endif; ?>

                <div class="flex items-center gap-1">
                    <?php foreach ($windowPages as $pg): ?>
                        <?php if ($pg === '...'): ?>
                            <span class="px-2 text-slate-400 text-xs font-semibold" aria-hidden="true">...</span>
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
                    <span class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-100 bg-slate-50 text-slate-300 cursor-not-allowed" aria-disabled="true">
                        <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </section>

</main>

<script>
// Instant Category Search/Filter on Client Side
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('category-search-input');
    const cards = document.querySelectorAll('.category-card');
    const emptyNotice = document.getElementById('no-cat-matches');

    if (!input || !cards.length) return;

    input.addEventListener('input', function() {
        const query = input.value.trim().toLowerCase();
        let visibleCount = 0;

        cards.forEach(card => {
            const name = card.getAttribute('data-name') || '';
            if (name.includes(query)) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        if (emptyNotice) {
            if (visibleCount === 0) {
                emptyNotice.classList.remove('hidden');
            } else {
                emptyNotice.classList.add('hidden');
            }
        }
    });
});
</script>

<?= $this->endSection() ?>
