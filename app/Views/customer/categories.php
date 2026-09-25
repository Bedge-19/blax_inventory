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

    <!-- Trending Categories (Bento Grid) -->
    <?php if (!empty($trending)): ?>
        <section class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center">
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

            <!-- Bento Layout -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                
                <?php if (isset($trending[0])): ?>
                    <!-- #1 Trending Hero Bento Tile (Span 7) -->
                    <a href="<?= base_url('category/' . ($trending[0]['slug'] ?? $trending[0]['id'])) ?>" class="md:col-span-7 rounded-3xl overflow-hidden relative group cursor-pointer border border-slate-200/90 shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 min-h-[260px] sm:min-h-[320px] flex flex-col justify-end">
                        
                        <div class="absolute inset-0 bg-cover bg-center w-full h-full group-hover:scale-105 transition-transform duration-700 ease-out" style="background-image: url('<?= esc($trending[0]['image_url']) ?>');"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-transparent"></div>

                        <!-- Content Glass Card Overlay -->
                        <div class="relative z-10 m-4 sm:m-6 p-4 sm:p-5 rounded-2xl bg-white/95 backdrop-blur-md border border-white/60 shadow-lg flex items-center justify-between gap-4">
                            <div>
                                <span class="px-2.5 py-0.5 bg-amber-500 text-white text-[10px] font-extrabold rounded-full mb-1.5 inline-flex items-center gap-1 shadow-2xs uppercase tracking-wide">
                                    <span class="material-symbols-outlined text-[12px] fill-icon">local_fire_department</span>
                                    #1 Trending
                                </span>
                                <h3 class="text-base sm:text-xl font-extrabold text-slate-900"><?= esc($trending[0]['name']) ?></h3>
                                <p class="text-xs text-slate-500 mt-0.5 font-medium"><?= (int) ($trending[0]['product_count'] ?? 0) ?> items available</p>
                            </div>

                            <span class="w-10 h-10 rounded-xl bg-primary text-white flex items-center justify-center shrink-0 shadow-xs group-hover:bg-blue-700 transition-colors">
                                <span class="material-symbols-outlined text-[18px]">arrow_outward</span>
                            </span>
                        </div>

                    </a>
                <?php endif; ?>

                <?php if (isset($trending[1]) || isset($trending[2])): ?>
                    <!-- Secondary Trending Stack (Span 5) -->
                    <div class="md:col-span-5 flex flex-col gap-4">
                        <?php for ($ti = 1; $ti <= 2; $ti++): ?>
                            <?php if (isset($trending[$ti])): ?>
                                <a href="<?= base_url('category/' . ($trending[$ti]['slug'] ?? $trending[$ti]['id'])) ?>" class="flex-1 rounded-3xl overflow-hidden relative group cursor-pointer border border-slate-200/90 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 min-h-[150px] flex flex-col justify-end">
                                    
                                    <div class="absolute inset-0 bg-cover bg-center w-full h-full group-hover:scale-105 transition-transform duration-700 ease-out" style="background-image: url('<?= esc($trending[$ti]['image_url']) ?>');"></div>
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/75 via-slate-950/15 to-transparent"></div>

                                    <div class="relative z-10 m-3 sm:m-4 p-3 sm:p-4 rounded-xl bg-white/95 backdrop-blur-md border border-white/60 shadow-md flex items-center justify-between">
                                        <div>
                                            <span class="px-2 py-0.5 bg-blue-100 text-primary text-[9px] font-bold rounded-full mb-1 inline-block uppercase tracking-wide">Trending</span>
                                            <h3 class="text-sm sm:text-base font-bold text-slate-900"><?= esc($trending[$ti]['name']) ?></h3>
                                            <p class="text-[11px] text-slate-500"><?= (int) ($trending[$ti]['product_count'] ?? 0) ?> items</p>
                                        </div>
                                        <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 group-hover:bg-primary group-hover:text-white transition-colors flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-[16px]">arrow_outward</span>
                                        </span>
                                    </div>

                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

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
                                <div class="bg-cover bg-center w-full h-full group-hover:scale-105 transition-transform duration-500" style="background-image: url('<?= esc($cat['image_url']) ?>');"></div>
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
