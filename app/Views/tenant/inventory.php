<?= $this->extend('layouts/tenant') ?>

<?php
function inv_stock_pill(int $stock, int $threshold): array {
    if ($stock <= 0) {
        return ['bg-red-100 text-red-700', 'block'];
    }
    if ($stock <= $threshold) {
        return ['bg-amber-100 text-amber-700', 'warning'];
    }
    return ['bg-green-100 text-green-700', 'check_circle'];
}
?>

<?= $this->section('content') ?>

<div class="flex-1 space-y-gutter">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-md rounded-xl bg-green-100 text-green-800 text-sm font-medium flex items-center gap-sm">
            <span class="material-symbols-outlined text-[18px]">check_circle</span><?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-md rounded-xl bg-error-container text-on-error-container text-sm font-medium flex items-center gap-sm">
            <span class="material-symbols-outlined text-[18px]">error</span><?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>

    <!-- Header & Action Row (Shopify Polaris Style) -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/30 shadow-xs">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <span class="text-xs font-bold text-on-surface-variant/70 uppercase tracking-widest">Active Stock Catalog</span>
            </div>
            <h1 class="text-2xl font-black text-on-surface tracking-tight">Inventory Management</h1>
            <p class="text-xs text-on-surface-variant max-w-xl">
                Oversee stock availability, variants, SKU health, and product pricing across your entire storefront catalog.
            </p>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap w-full sm:w-auto">
            <a href="<?= base_url('tenant/inventory?stock=low') ?>" class="px-3.5 py-2 rounded-xl border border-outline-variant/40 bg-surface-container hover:bg-surface-container-high text-xs font-semibold text-on-surface transition-all flex items-center gap-1.5 shadow-2xs">
                <span class="material-symbols-outlined text-[17px] text-amber-500">warning</span>
                <span>Restock Alerts</span>
            </a>
            <button onclick="openProductModal(null)" class="flex-1 sm:flex-initial bg-primary hover:bg-primary/90 text-on-primary px-4 py-2 rounded-xl flex items-center justify-center gap-2 font-bold text-xs shadow-md shadow-primary/20 active:scale-95 transition-all">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>New Product</span>
            </button>
        </div>
    </div>

    <!-- Inventory Overview Cards (Linear & Stripe Bento Grid with Clickable Quick Filters) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total SKUs -->
        <a href="<?= base_url('tenant/inventory') ?>" class="group bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/30 hover:border-primary/50 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <span class="text-xs font-bold text-on-surface-variant/70 uppercase tracking-wider">Total SKUs</span>
                <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-black text-on-surface tracking-tight"><?= number_format((int) $summary['total_sku']) ?></div>
                <div class="flex items-center gap-1 text-[11px] font-medium text-on-surface-variant/70 mt-1">
                    <span>Active in catalog</span>
                    <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </div>
            </div>
        </a>

        <!-- Card 2: Low Stock Warning -->
        <a href="<?= base_url('tenant/inventory?stock=low') ?>" class="group bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/30 hover:border-amber-500/50 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <span class="text-xs font-bold text-on-surface-variant/70 uppercase tracking-wider">Low Stock</span>
                <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">warning</span>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-black text-amber-600 tracking-tight"><?= number_format((int) $summary['low_stock']) ?></div>
                <div class="flex items-center gap-1 text-[11px] font-semibold text-amber-600/90 mt-1">
                    <span>Below threshold</span>
                    <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </div>
            </div>
        </a>

        <!-- Card 3: Out of Stock -->
        <a href="<?= base_url('tenant/inventory?stock=out') ?>" class="group bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/30 hover:border-rose-500/50 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <span class="text-xs font-bold text-on-surface-variant/70 uppercase tracking-wider">Out of Stock</span>
                <div class="w-9 h-9 rounded-xl bg-rose-500/10 text-rose-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">block</span>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-black text-rose-600 tracking-tight"><?= number_format((int) $summary['out_of_stock']) ?></div>
                <div class="flex items-center gap-1 text-[11px] font-semibold text-rose-600/90 mt-1">
                    <span>Requires immediate restock</span>
                    <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </div>
            </div>
        </a>

        <!-- Card 4: Inventory Valuation -->
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/30 shadow-xs flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <span class="text-xs font-bold text-on-surface-variant/70 uppercase tracking-wider">Inventory Value</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-black text-on-surface tracking-tight">₱<?= number_format((float) $summary['inventory_value'], 2) ?></div>
                <div class="text-[11px] font-medium text-emerald-600/90 mt-1">
                    Gross estimated value
                </div>
            </div>
        </div>
    </div>

    <?php if ((int)($summary['low_stock'] ?? 0) > 0 || (int)($summary['out_of_stock'] ?? 0) > 0): ?>
        <!-- Low Stock / Out of Stock Alert Banner (Shopify Polaris Callout) -->
        <div class="rounded-2xl p-4 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-surface-container-lowest border border-amber-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-700 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[22px]">notification_important</span>
                </div>
                <div>
                    <h4 class="font-bold text-on-surface text-sm">Inventory Attention Required</h4>
                    <p class="text-xs text-on-surface-variant mt-0.5">
                        <span class="font-bold text-amber-700"><?= (int)($summary['low_stock'] ?? 0) ?> items</span> are reaching depletion and 
                        <span class="font-bold text-rose-600"><?= (int)($summary['out_of_stock'] ?? 0) ?> items</span> are completely out of stock.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <?php if ((int)($summary['low_stock'] ?? 0) > 0): ?>
                    <a href="<?= base_url('tenant/inventory?stock=low') ?>" class="px-3 py-1.5 bg-amber-600 text-white rounded-xl text-xs font-bold hover:bg-amber-700 transition-colors shadow-xs">
                        View Low Stock
                    </a>
                <?php endif; ?>
                <?php if ((int)($summary['out_of_stock'] ?? 0) > 0): ?>
                    <a href="<?= base_url('tenant/inventory?stock=out') ?>" class="px-3 py-1.5 bg-rose-600 text-white rounded-xl text-xs font-bold hover:bg-rose-700 transition-colors shadow-xs">
                        View Out of Stock
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Shopify Polaris Segmented Control & Search Bar -->
    <div class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/30 shadow-xs space-y-3">
        <!-- Segmented Stock Tabs -->
        <div class="flex items-center gap-1.5 p-1 bg-surface-container-low rounded-xl border border-outline-variant/20 overflow-x-auto">
            <a href="<?= base_url('tenant/inventory') ?>" class="px-3.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all <?= empty($filters['stock']) ? 'bg-surface-container-lowest text-primary shadow-xs' : 'text-on-surface-variant hover:text-on-surface' ?>">
                All Products (<?= (int)$summary['total_sku'] ?>)
            </a>
            <a href="<?= base_url('tenant/inventory?stock=in') ?>" class="px-3.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all <?= ($filters['stock'] ?? '') === 'in' ? 'bg-surface-container-lowest text-emerald-600 shadow-xs' : 'text-on-surface-variant hover:text-on-surface' ?>">
                In Stock
            </a>
            <a href="<?= base_url('tenant/inventory?stock=low') ?>" class="px-3.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all <?= ($filters['stock'] ?? '') === 'low' ? 'bg-surface-container-lowest text-amber-600 shadow-xs' : 'text-on-surface-variant hover:text-on-surface' ?>">
                Low Stock (<?= (int)$summary['low_stock'] ?>)
            </a>
            <a href="<?= base_url('tenant/inventory?stock=out') ?>" class="px-3.5 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all <?= ($filters['stock'] ?? '') === 'out' ? 'bg-surface-container-lowest text-rose-600 shadow-xs' : 'text-on-surface-variant hover:text-on-surface' ?>">
                Out of Stock (<?= (int)$summary['out_of_stock'] ?>)
            </a>
        </div>

        <!-- Search & Filter Controls -->
        <form method="get" action="<?= base_url('tenant/inventory') ?>" class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="stock" value="<?= esc($filters['stock'] ?? '') ?>">
            
            <div class="relative flex-1 min-w-[240px]">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                <input name="q" value="<?= esc($filters['q']) ?>" class="w-full pl-9 pr-3 py-2 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs text-on-surface focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all placeholder:text-on-surface-variant/50" placeholder="Filter by title, description or SKU..." type="text">
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <select name="category" class="bg-surface-container-low border border-outline-variant/40 rounded-xl px-3 py-2 text-xs font-medium text-on-surface focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) $filters['category'] === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="button" onclick="document.getElementById('advancedFilters').classList.toggle('hidden')" class="bg-surface-container hover:bg-surface-container-high border border-outline-variant/40 text-on-surface px-3 py-2 rounded-xl flex items-center gap-1.5 text-xs font-semibold transition-colors">
                    <span class="material-symbols-outlined text-[17px] text-outline">tune</span>
                    <span>More Filters</span>
                </button>

                <button type="submit" class="bg-primary text-on-primary px-3.5 py-2 rounded-xl text-xs font-bold hover:bg-primary/90 transition-colors shadow-2xs">
                    Filter
                </button>

                <?php if (!empty($filters['q']) || !empty($filters['category']) || !empty($filters['sku']) || !empty($filters['bestseller']) || !empty($filters['stock'])): ?>
                    <a href="<?= base_url('tenant/inventory') ?>" class="px-2.5 py-2 text-on-surface-variant hover:text-on-surface text-xs font-medium flex items-center gap-1">
                        <span class="material-symbols-outlined text-[15px]">clear</span>
                        <span>Clear</span>
                    </a>
                <?php endif; ?>
            </div>

            <div id="advancedFilters" class="hidden w-full flex flex-wrap items-end gap-3 border-t border-outline-variant/20 pt-3 mt-1">
                <div class="flex-1 min-w-[180px]">
                    <label class="text-[11px] font-bold text-on-surface-variant/70 uppercase tracking-wider block mb-1">SKU Exact Match</label>
                    <input name="sku" value="<?= esc($filters['sku']) ?>" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-primary" placeholder="e.g. PRD-001">
                </div>
                <label class="flex items-center gap-2 text-xs font-semibold text-on-surface pb-2.5 cursor-pointer">
                    <input type="checkbox" name="bestseller" value="1" <?= $filters['bestseller'] ? 'checked' : '' ?> class="rounded border-outline-variant text-primary focus:ring-primary">
                    <span>Show Bestsellers Only</span>
                </label>
            </div>
        </form>
    </div>

    <!-- Product Table Container (Shopify Polaris IndexTable Aesthetic) -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 overflow-hidden shadow-xs">
        <div class="responsive-table">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low/70 border-b border-outline-variant/30">
                    <tr>
                        <th class="py-3 px-4 w-10">
                            <input type="checkbox" id="selectAll" class="rounded border-outline-variant/60 text-primary focus:ring-primary h-4 w-4">
                        </th>
                        <th class="py-3 px-4 text-[11px] font-bold text-on-surface-variant/70 uppercase tracking-wider">Product Info</th>
                        <th class="py-3 px-4 text-[11px] font-bold text-on-surface-variant/70 uppercase tracking-wider">SKU</th>
                        <th class="py-3 px-4 text-[11px] font-bold text-on-surface-variant/70 uppercase tracking-wider">Category</th>
                        <th class="py-3 px-4 text-[11px] font-bold text-on-surface-variant/70 uppercase tracking-wider text-center">Details</th>
                        <th class="py-3 px-4 text-[11px] font-bold text-on-surface-variant/70 uppercase tracking-wider text-center">Stock Health</th>
                        <th class="py-3 px-4 text-[11px] font-bold text-on-surface-variant/70 uppercase tracking-wider text-right">Price</th>
                        <th class="py-3 px-4 text-[11px] font-bold text-on-surface-variant/70 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20 text-xs">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $p): ?>
                            <?php
                            $stock = (int) $p['stock_quantity'];
                            $thr   = (int) ($p['low_stock_threshold'] ?? 5);
                            [$pill, $icon] = inv_stock_pill($stock, $thr);
                            $hasVariants = !empty($productVariants[$p['id']]);
                            ?>
                            <tr class="hover:bg-surface-container-low/40 transition-colors group" id="row-<?= (int) $p['id'] ?>">
                                <td class="py-3 px-4">
                                    <input type="checkbox" name="selected_products[]" value="<?= esc($p['id']) ?>" class="product-checkbox rounded border-outline-variant/60 text-primary focus:ring-primary h-4 w-4">
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-xl bg-surface-container border border-outline-variant/30 overflow-hidden flex items-center justify-center shrink-0">
                                            <?php if (!empty($p['image_url'])): ?>
                                                <?php $imgSrc = product_image_url($p['image_url'], 'thumbnail'); ?>
                                                <img src="<?= esc($imgSrc) ?>" alt="<?= esc($p['name']) ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <span class="material-symbols-outlined text-[20px] text-outline">inventory_2</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="font-bold text-on-surface group-hover:text-primary transition-colors block truncate max-w-xs md:max-w-sm"><?= esc($p['name']) ?></span>
                                            <?php if ($hasVariants): ?>
                                                <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-primary/80 mt-0.5">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                                                    <?= count($productVariants[$p['id']]) ?> Variants
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-mono text-[11px] font-semibold text-on-surface-variant bg-surface-container-low px-2 py-0.5 rounded-md border border-outline-variant/30">
                                        <?= esc($p['sku'] ?: '—') ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-md bg-surface-container text-on-surface text-[11px] font-medium border border-outline-variant/20">
                                        <?= esc($p['category_name'] ?? 'General') ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button type="button"
                                            onclick="openDescriptionModal(this)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-surface-container-low hover:bg-surface-container text-on-surface text-[11px] font-semibold transition-colors border border-outline-variant/30 hover:border-primary/40"
                                            title="View Description"
                                            data-name="<?= esc($p['name']) ?>"
                                            data-description="<?= esc($p['description'] ?? '') ?>">
                                        <span class="material-symbols-outlined text-[15px] text-primary">visibility</span>
                                        <span>View</span>
                                    </button>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="inline-flex flex-col items-center">
                                        <span id="stock-pill-<?= (int) $p['id'] ?>" class="px-2.5 py-0.5 rounded-full <?= $pill ?> text-[11px] font-bold inline-flex items-center gap-1 border border-current/20">
                                            <span class="material-symbols-outlined text-[13px]"><?= $icon ?></span>
                                            <?= number_format($stock) ?>
                                        </span>
                                        <span class="text-[10px] text-on-surface-variant/60 font-medium mt-0.5">Threshold: <?= $thr ?></span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="font-bold text-on-surface text-sm">₱<?= number_format((float) $p['price'], 2) ?></div>
                                    <?php if (!empty($p['compare_at_price']) && (float)$p['compare_at_price'] > (float)$p['price']): ?>
                                        <div class="text-[10px] text-on-surface-variant/60 line-through">₱<?= number_format((float) $p['compare_at_price'], 2) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button"
                                                onclick="openProductModal(this)"
                                                class="w-7 h-7 rounded-lg hover:bg-surface-container text-on-surface-variant hover:text-on-surface flex items-center justify-center border border-transparent hover:border-outline-variant/30 transition-colors"
                                                title="Edit Product"
                                                data-id="<?= (int) $p['id'] ?>"
                                                data-name="<?= esc($p['name']) ?>"
                                                data-sku="<?= esc($p['sku']) ?>"
                                                data-category="<?= (int) ($p['category_id'] ?? 0) ?>"
                                                data-price="<?= esc($p['price']) ?>"
                                                data-compare="<?= esc($p['compare_at_price'] ?? '') ?>"
                                                data-stock="<?= (int) $p['stock_quantity'] ?>"
                                                data-threshold="<?= (int) ($p['low_stock_threshold'] ?? 5) ?>"
                                                data-shipping-fee="<?= esc($p['shipping_fee'] ?? '0.00') ?>"
                                                data-description="<?= esc($p['description'] ?? '') ?>"
                                                data-image="<?= esc(!empty($p['image_url']) ? (str_starts_with($p['image_url'], 'http') ? $p['image_url'] : base_url($p['image_url'])) : '') ?>"
                                                data-images="<?= esc(json_encode($productImages[$p['id']] ?? []), 'attr') ?>"
                                                data-variants="<?= esc(json_encode($productVariants[$p['id']] ?? []), 'attr') ?>">
                                            <span class="material-symbols-outlined text-[17px]">edit</span>
                                        </button>
                                        <button type="button"
                                                onclick="openStockModal(<?= (int) $p['id'] ?>, <?= (int) $p['stock_quantity'] ?>, '<?= esc($p['name'], 'js') ?>')"
                                                class="w-7 h-7 rounded-lg hover:bg-primary/10 text-primary flex items-center justify-center border border-transparent hover:border-primary/20 transition-colors"
                                                title="Adjust / Restock">
                                            <span class="material-symbols-outlined text-[17px]">autorenew</span>
                                        </button>
                                        <form action="<?= base_url('tenant/products/archive/' . (int) $p['id']) ?>" method="POST" onsubmit="return confirm('Archive this product? It will be hidden from your storefront and can be restored from the Archive page.')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="w-7 h-7 rounded-lg hover:bg-rose-500/10 text-rose-600 flex items-center justify-center border border-transparent hover:border-rose-500/20 transition-colors" title="Archive">
                                                <span class="material-symbols-outlined text-[17px]">archive</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="py-12 text-center">
                                <div class="w-12 h-12 rounded-2xl bg-surface-container flex items-center justify-center mx-auto mb-2 text-outline">
                                    <span class="material-symbols-outlined text-2xl">search_off</span>
                                </div>
                                <p class="text-sm font-bold text-on-surface">No products found</p>
                                <p class="text-xs text-on-surface-variant mt-0.5">Try modifying your search or clearing active filters.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>

        <?php
        $total = (int) $pager->getTotal('inventory');
        $perPage = 10;
        $cur  = (int) $pager->getCurrentPage('inventory');
        $pages = (int) $pager->getPageCount('inventory');
        $start = $total === 0 ? 0 : ($cur - 1) * $perPage + 1;
        $end   = min($cur * $perPage, $total);
        ?>
        <div class="px-6 py-3 bg-surface-container-low/70 flex justify-between items-center border-t border-outline-variant/30 flex-wrap gap-2 text-xs">
            <p class="font-medium text-on-surface-variant">Showing <span class="font-bold text-on-surface"><?= number_format($start) ?></span> to <span class="font-bold text-on-surface"><?= number_format($end) ?></span> of <span class="font-bold text-on-surface"><?= number_format($total) ?></span> items</p>
            <?php if ($pages > 1): ?>
                <div class="flex items-center gap-1">
                    <a class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container border border-outline-variant/30 <?= $cur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('inventory') ?>">
                        <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                    </a>
                    <?php
                    $window = [];
                    for ($i = 1; $i <= $pages; $i++) {
                        if ($i === 1 || $i === $pages || abs($i - $cur) <= 2) {
                            $window[] = $i;
                        }
                    }
                    $prev = 0;
                    foreach ($window as $num):
                        if ($num - $prev > 1): ?>
                            <span class="px-1 text-outline">...</span>
                        <?php endif; ?>
                        <a class="w-8 h-8 rounded-lg flex items-center justify-center font-bold <?= $cur === $num ? 'bg-primary text-on-primary shadow-xs' : 'hover:bg-surface-container border border-outline-variant/30 text-on-surface-variant' ?>" href="<?= $pager->getPageURI($num, 'inventory') ?>"><?= $num ?></a>
                    <?php $prev = $num; endforeach; ?>
                    <a class="p-sm rounded hover:bg-surface-container-high <?= $cur >= $pages ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getNextPageURI('inventory') ?>">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Read-only View Product Description Modal -->
<div id="descriptionModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest rounded-2xl p-xl max-w-md w-full space-y-md border border-outline-variant/30 max-h-[85vh] flex flex-col shadow-2xl">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">
            <h3 id="descModalTitle" class="text-title-lg font-bold text-on-surface break-words">Product Details</h3>
            <button type="button" onclick="closeDescriptionModal()" class="text-on-surface-variant hover:text-on-surface p-1 rounded-lg hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="overflow-y-auto flex-1 py-xs">
            <label class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider block mb-1">Description</label>
            <div id="descModalBody" class="text-body-md text-on-surface whitespace-pre-line leading-relaxed break-words bg-surface-container-low/60 p-md rounded-xl border border-outline-variant/20 min-h-[100px]"></div>
        </div>
        <div class="pt-sm border-t border-outline-variant/20 flex justify-end">
            <button type="button" onclick="closeDescriptionModal()" class="px-lg py-sm bg-surface-container-high text-on-surface hover:bg-surface-variant rounded-xl font-bold transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Add / Edit Product Modal -->
<div id="productModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest rounded-2xl p-xl max-w-3xl w-full space-y-md border border-outline-variant/30 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">
            <h3 id="productModalTitle" class="text-title-lg font-bold">Add New Product</h3>
            <button onclick="document.getElementById('productModal').classList.add('hidden')" class="text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="productModalForm" action="<?= base_url('tenant/products/save') ?>" method="POST" enctype="multipart/form-data" class="space-y-md">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" id="product_id">

            <!-- Product Images Upload & Gallery -->
            <div class="space-y-xs">
                <label class="text-label-sm font-bold text-on-surface-variant flex justify-between items-center">
                    <span>Product Photos (Multiple Images Supported)</span>
                    <span class="text-[11px] font-normal text-on-surface-variant">JPG, PNG, WEBP — Max 5MB each</span>
                </label>

                <!-- Existing uploaded thumbnails -->
                <div id="p_existing_gallery" class="hidden mb-xs p-xs bg-surface-container-low rounded-xl border border-outline-variant/30">
                    <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1 px-1">Uploaded Photos</p>
                    <div id="p_existing_thumbnails" class="flex flex-wrap gap-xs"></div>
                </div>

                <!-- Dropzone / File input -->
                <div id="p_img_dropzone" onclick="document.getElementById('p_images').click()" class="cursor-pointer border-2 border-dashed border-outline-variant rounded-xl flex flex-col items-center justify-center gap-xs p-md hover:border-primary transition-colors bg-surface-container-low min-h-[90px] relative">
                    <div class="flex flex-col items-center gap-xs text-on-surface-variant text-center">
                        <span class="material-symbols-outlined text-3xl text-primary">add_photo_alternate</span>
                        <span class="text-label-sm font-medium">Click to select 1 or more product photos</span>
                        <span class="text-[11px] text-outline">You can select multiple images at once</span>
                    </div>
                </div>
                <input type="file" name="product_images[]" id="p_images" multiple accept="image/jpeg,image/png,image/webp" class="sr-only">

                <!-- New selected files preview list -->
                <div id="p_new_preview_container" class="hidden mt-xs p-xs bg-surface-container-low rounded-xl border border-outline-variant/30">
                    <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1 px-1">Selected New Photos</p>
                    <div id="p_new_preview_list" class="flex flex-wrap gap-xs"></div>
                </div>
            </div>

            <div>
                <label class="text-label-sm font-bold text-on-surface-variant">Product Name</label>
                <input type="text" name="name" id="p_name" required class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl">
            </div>
            <div class="grid grid-cols-2 gap-md">
                <div>
                    <label class="text-label-sm font-bold text-on-surface-variant">SKU</label>
                    <input type="text" name="sku" id="p_sku" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl" placeholder="Auto-generated if blank">
                </div>
                <div>
                    <label class="text-label-sm font-bold text-on-surface-variant">Category</label>
                    <select name="category_id" id="p_category" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl">
                        <option value="0">General</option>
                        <?php foreach ($all_categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"><?= esc($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-md">
                <div>
                    <label class="text-label-sm font-bold text-on-surface-variant">Price (₱)</label>
                    <input type="number" step="0.01" min="0" name="price" id="p_price" required class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl">
                </div>
                <div>
                    <label class="text-label-sm font-bold text-on-surface-variant">Compare-at Price (₱)</label>
                    <input type="number" step="0.01" min="0" name="compare_at_price" id="p_compare" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl" placeholder="Optional">
                </div>
                <div>
                    <label class="text-label-sm font-bold text-on-surface-variant">Standard Shipping (₱)</label>
                    <input type="number" step="0.01" min="0" name="shipping_fee" id="p_shipping_fee" value="0.00" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl" placeholder="0.00">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-md">
                <div>
                    <label class="text-label-sm font-bold text-on-surface-variant">Stock Quantity</label>
                    <input type="number" min="0" name="stock_quantity" id="p_stock" required class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl">
                </div>
                <div>
                    <label class="text-label-sm font-bold text-on-surface-variant">Low-Stock Threshold</label>
                    <input type="number" min="0" name="low_stock_threshold" id="p_threshold" value="5" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl">
                </div>
            </div>
            <div>
                <label class="text-label-sm font-bold text-on-surface-variant">Description</label>
                <textarea name="description" id="p_description" rows="3" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl"></textarea>
            </div>

            <!-- Product Variations / Options (Compose-then-Apply UX) -->
            <div class="border-t border-outline-variant/30 pt-md space-y-md">
                <div>
                    <div class="flex items-center gap-xs">
                        <span class="material-symbols-outlined text-[20px] text-primary">tune</span>
                        <h4 class="text-title-sm font-bold text-on-surface">Product Variations / Types</h4>
                    </div>
                    <p class="text-xs text-on-surface-variant mt-0.5">
                        Compose and apply product options (e.g., Color, Size) with individual stock and price overrides.
                    </p>
                </div>

                <!-- Compose Panel -->
                <div class="bg-surface-container-low p-md rounded-2xl border border-outline-variant/30 space-y-sm">
                    <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider block">
                        Compose Variant
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-sm items-end">
                        <div class="sm:col-span-3">
                            <label class="text-[11px] font-semibold text-on-surface-variant block mb-1">Type Category</label>
                            <input type="text" id="compose_variant_name" list="variant_category_suggestions" placeholder="e.g., Color" class="w-full py-2 px-3 text-xs bg-surface-container border border-outline-variant/50 rounded-xl text-on-surface focus:border-primary focus:ring-1 focus:ring-primary">
                            <datalist id="variant_category_suggestions">
                                <option value="Color">
                                <option value="Size">
                                <option value="Material">
                                <option value="Format">
                            </datalist>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="text-[11px] font-semibold text-on-surface-variant block mb-1">Option Value <span class="text-error">*</span></label>
                            <input type="text" id="compose_variant_value" placeholder="e.g., Black, Blue, Large..." class="w-full py-2 px-3 text-xs bg-surface-container border border-outline-variant/50 rounded-xl text-on-surface focus:border-primary focus:ring-1 focus:ring-primary">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-[11px] font-semibold text-on-surface-variant block mb-1">Stock Qty <span class="text-error">*</span></label>
                            <input type="number" id="compose_variant_stock" min="0" value="0" placeholder="0" class="w-full py-2 px-3 text-xs bg-surface-container border border-outline-variant/50 rounded-xl text-on-surface focus:border-primary focus:ring-1 focus:ring-primary">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-[11px] font-semibold text-on-surface-variant block mb-1">Price Override (₱)</label>
                            <input type="number" id="compose_variant_price" step="0.01" min="0" placeholder="Optional" class="w-full py-2 px-3 text-xs bg-surface-container border border-outline-variant/50 rounded-xl text-on-surface focus:border-primary focus:ring-1 focus:ring-primary">
                        </div>
                        <div class="sm:col-span-2">
                            <button type="button" id="btnApplyVariant" class="w-full py-2 px-3 bg-primary text-on-primary rounded-xl text-xs font-bold hover:bg-primary/90 flex items-center justify-center gap-1 transition-colors shadow-sm active:scale-95">
                                <span class="material-symbols-outlined text-[16px]" id="btnApplyVariantIcon">add</span>
                                <span id="btnApplyVariantText">Apply</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Applied Container -->
                <div class="space-y-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">
                            Applied Variants
                        </span>
                        <span id="variantCountBadge" class="text-[11px] font-semibold text-on-surface-variant">0 options</span>
                    </div>

                    <!-- Empty Hint -->
                    <div id="variantEmptyHint" class="p-4 text-center bg-surface-container-low/50 rounded-xl border border-dashed border-outline-variant/40 flex items-center justify-center gap-2 text-on-surface-variant text-xs">
                        <span class="material-symbols-outlined text-[18px]">info</span>
                        <span>No variants added yet. Use the composer above to add options, or proceed with base product stock and price.</span>
                    </div>

                    <!-- Applied List (Grouped by Category) -->
                    <div id="appliedVariantsList" class="space-y-3">
                        <!-- Populated dynamically by JS -->
                    </div>
                </div>

                <!-- Hidden inputs container synced upon form submit -->
                <div id="variantHiddenInputsContainer"></div>
            </div>

            <button type="submit" class="w-full py-md bg-primary text-on-primary rounded-xl font-bold hover:bg-primary/90 shadow-md">Save Product</button>
        </form>
    </div>
</div>

<!-- Adjust / Restock Modal -->
<div id="stockModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest rounded-2xl p-xl max-w-md w-full space-y-md border border-outline-variant/30">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">
            <h3 class="text-title-lg font-bold">Adjust Stock</h3>
            <button onclick="document.getElementById('stockModal').classList.add('hidden')" class="text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
        </div>
        <p id="stockProductName" class="text-body-md font-semibold text-on-surface"></p>
        <p class="text-label-sm text-on-surface-variant">Current stock: <span id="stockCurrent" class="font-bold text-on-surface">0</span></p>
        <div>
            <label class="text-label-sm font-bold text-on-surface-variant">Quantity</label>
            <input type="number" min="1" id="stockQty" value="10" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl">
        </div>
        <div class="flex gap-sm">
            <button onclick="submitStockAdjust('add')" class="flex-1 py-md bg-primary text-on-primary rounded-xl font-bold hover:bg-primary/90">Add Stock</button>
            <button onclick="submitStockAdjust('set')" class="flex-1 py-md bg-surface-container-high text-on-surface rounded-xl font-bold hover:bg-surface-variant">Set Exact</button>
        </div>
        <p id="stockFeedback" class="text-label-sm text-on-surface-variant"></p>
        <form id="stockCsrfForm" class="hidden"><?= csrf_field() ?></form>
    </div>
</div>

<!-- Bulk Actions Floating Bar -->
<div id="bulkActionsBar" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-surface-container-lowest/95 backdrop-blur border border-primary/30 shadow-2xl rounded-2xl px-lg py-sm flex items-center gap-md transition-all">
    <div class="flex items-center gap-xs text-xs font-bold text-on-surface">
        <span class="w-6 h-6 rounded-full bg-primary text-on-primary flex items-center justify-center text-xs" id="selectedCountBadge">0</span>
        <span class="whitespace-nowrap">products selected</span>
    </div>
    <div class="h-4 w-[1px] bg-outline-variant/40"></div>
    <button type="button" onclick="openBulkStockModal()" class="px-md py-1.5 bg-primary text-on-primary rounded-xl text-xs font-bold hover:bg-primary/90 flex items-center gap-xs transition-colors shadow-sm whitespace-nowrap">
        <span class="material-symbols-outlined text-[16px]">autorenew</span>
        Bulk Adjust Stock
    </button>
    <button type="button" onclick="submitBulkArchive()" class="px-md py-1.5 bg-error-container text-on-error-container border border-error/30 rounded-xl text-xs font-bold hover:bg-error-container/80 flex items-center gap-xs transition-colors whitespace-nowrap">
        <span class="material-symbols-outlined text-[16px]">archive</span>
        Bulk Archive
    </button>
    <button type="button" onclick="deselectAllProducts()" class="p-1 hover:bg-surface-container rounded-lg text-on-surface-variant" title="Deselect All">
        <span class="material-symbols-outlined text-[18px]">close</span>
    </button>
</div>

<!-- Bulk Adjust Stock Modal -->
<div id="bulkStockModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest rounded-2xl p-xl max-w-md w-full space-y-md border border-outline-variant/30">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">
            <h3 class="text-title-lg font-bold">Bulk Stock Adjustment</h3>
            <button onclick="document.getElementById('bulkStockModal').classList.add('hidden')" class="text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
        </div>
        <p class="text-body-md text-on-surface">Apply stock update to <span id="bulkStockCountText" class="font-bold text-primary">0</span> selected product(s).</p>
        <div>
            <label class="text-label-sm font-bold text-on-surface-variant block mb-1">Adjustment Quantity</label>
            <input type="number" id="bulkStockQty" value="10" min="0" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl text-body-md">
            <p class="text-[11px] text-on-surface-variant mt-1">Use "Add / Restock" to increase quantities or "Set Exact" to override stock.</p>
        </div>
        <div class="flex gap-sm pt-xs">
            <button type="button" onclick="submitBulkStockAdjust('add')" class="flex-1 py-md bg-primary text-on-primary rounded-xl font-bold hover:bg-primary/90 flex items-center justify-center gap-xs">
                <span class="material-symbols-outlined text-[16px]">add_circle</span> Add to Stock
            </button>
            <button type="button" onclick="submitBulkStockAdjust('set')" class="flex-1 py-md bg-surface-container-high text-on-surface rounded-xl font-bold hover:bg-surface-variant flex items-center justify-center gap-xs">
                <span class="material-symbols-outlined text-[16px]">pin</span> Set Exact
            </button>
        </div>
        <p id="bulkStockFeedback" class="text-xs text-on-surface-variant font-medium"></p>
    </div>
</div>

<script>
    function openDescriptionModal(btn) {
        const modal = document.getElementById('descriptionModal');
        const title = document.getElementById('descModalTitle');
        const body = document.getElementById('descModalBody');

        if (btn && title && body) {
            title.textContent = btn.dataset.name || 'Product Details';
            const desc = (btn.dataset.description || '').trim();
            if (desc) {
                body.textContent = desc;
                body.classList.remove('italic', 'opacity-60');
            } else {
                body.textContent = 'No description provided for this product.';
                body.classList.add('italic', 'opacity-60');
            }
        }
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    function closeDescriptionModal() {
        const modal = document.getElementById('descriptionModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    document.getElementById('descriptionModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeDescriptionModal();
        }
    });

    function openProductModal(btn) {
        const modal = document.getElementById('productModal');
        document.getElementById('productModalTitle').textContent = 'Add New Product';
        document.getElementById('product_id').value = '';
        document.getElementById('p_name').value = '';
        document.getElementById('p_sku').value = '';
        document.getElementById('p_category').value = '0';
        document.getElementById('p_price').value = '';
        document.getElementById('p_compare').value = '';
        document.getElementById('p_stock').value = '';
        document.getElementById('p_threshold').value = '5';
        document.getElementById('p_shipping_fee').value = '0.00';
        document.getElementById('p_description').value = '';
        
        // Reset file inputs and preview lists
        resetProductImages();

        // Reset variants state
        variantState = [];
        editingVariantIndex = null;
        resetVariantComposer();

        if (btn) {
            document.getElementById('productModalTitle').textContent = 'Edit Product';
            document.getElementById('product_id').value = btn.dataset.id;
            document.getElementById('p_name').value = btn.dataset.name || '';
            document.getElementById('p_sku').value = btn.dataset.sku || '';
            document.getElementById('p_category').value = btn.dataset.category || '0';
            document.getElementById('p_price').value = btn.dataset.price || '';
            document.getElementById('p_compare').value = btn.dataset.compare || '';
            document.getElementById('p_shipping_fee').value = btn.dataset.shippingFee || '0.00';
            document.getElementById('p_stock').value = btn.dataset.stock || '';
            document.getElementById('p_threshold').value = btn.dataset.threshold || '5';
            document.getElementById('p_description').value = btn.dataset.description || '';

            // Populate existing images
            let images = [];
            try {
                images = JSON.parse(btn.dataset.images || '[]');
            } catch (e) {
                images = [];
            }
            populateExistingImages(images);

            // Populate existing variants into variantState
            let variants = [];
            try {
                variants = JSON.parse(btn.dataset.variants || '[]');
            } catch (e) {
                variants = [];
            }
            if (Array.isArray(variants) && variants.length > 0) {
                variantState = variants.map(v => ({
                    id: v.id || null,
                    name: (v.name || 'Color').trim(),
                    value: (v.value || '').trim(),
                    sku: (v.sku_suffix || '').trim(),
                    stock: Math.max(0, parseInt(v.stock_quantity, 10) || 0),
                    price: (v.price_override !== null && v.price_override !== '' && v.price_override !== undefined) ? parseFloat(v.price_override) : ''
                })).filter(v => v.value !== '');
            }
        }
        renderAppliedVariants();
        modal.classList.remove('hidden');
    }

    let newSelectedFiles = [];

    function syncFileInput() {
        const fileInput = document.getElementById('p_images');
        if (!fileInput) return;
        const dt = new DataTransfer();
        newSelectedFiles.forEach(f => dt.items.add(f));
        fileInput.files = dt.files;
    }

    function renderNewImagePreviews() {
        const previewContainer = document.getElementById('p_new_preview_container');
        const list = document.getElementById('p_new_preview_list');
        if (!previewContainer || !list) return;

        list.innerHTML = '';
        if (newSelectedFiles.length === 0) {
            previewContainer.classList.add('hidden');
            syncFileInput();
            return;
        }

        previewContainer.classList.remove('hidden');
        newSelectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(ev) {
                const item = document.createElement('div');
                item.className = 'relative w-16 h-16 rounded-lg overflow-hidden border border-outline-variant/40 bg-surface-variant group';
                item.innerHTML = `
                    <img src="${ev.target.result}" class="w-full h-full object-cover">
                    <span class="absolute bottom-0 inset-x-0 bg-black/60 text-[8px] text-white text-center py-0.5 truncate px-1">${escapeHtml(file.name)}</span>
                    <button type="button" onclick="removeNewSelectedImage(${index})" class="absolute top-0.5 right-0.5 bg-error text-white rounded-full w-5 h-5 flex items-center justify-center text-xs shadow hover:scale-110 transition-transform" title="Cancel upload">
                        <span class="material-symbols-outlined text-[12px]">close</span>
                    </button>
                `;
                list.appendChild(item);
            };
            reader.readAsDataURL(file);
        });

        syncFileInput();
    }

    function removeNewSelectedImage(index) {
        if (index >= 0 && index < newSelectedFiles.length) {
            newSelectedFiles.splice(index, 1);
            renderNewImagePreviews();
        }
    }

    function resetProductImages() {
        newSelectedFiles = [];
        syncFileInput();
        const fileInput = document.getElementById('p_images');
        if (fileInput) fileInput.value = '';
        const previewContainer = document.getElementById('p_new_preview_container');
        if (previewContainer) previewContainer.classList.add('hidden');
        const previewList = document.getElementById('p_new_preview_list');
        if (previewList) previewList.innerHTML = '';
        const existingGallery = document.getElementById('p_existing_gallery');
        if (existingGallery) existingGallery.classList.add('hidden');
        const existingThumbnails = document.getElementById('p_existing_thumbnails');
        if (existingThumbnails) existingThumbnails.innerHTML = '';
    }

    function populateExistingImages(images) {
        const gallery = document.getElementById('p_existing_gallery');
        const container = document.getElementById('p_existing_thumbnails');
        if (!gallery || !container) return;

        container.innerHTML = '';
        if (images && images.length > 0) {
            gallery.classList.remove('hidden');
            images.forEach(img => {
                const wrap = document.createElement('div');
                wrap.className = 'relative w-16 h-16 rounded-lg overflow-hidden border border-outline-variant/40 group bg-surface-variant';
                wrap.id = 'img-thumb-' + img.id;
                const src = img.image_url.startsWith('http') ? img.image_url : '<?= base_url() ?>/' + img.image_url;
                wrap.innerHTML = `
                    <img src="${src}" class="w-full h-full object-cover">
                    ${img.is_primary == 1 ? '<span class="absolute bottom-0 inset-x-0 bg-primary text-[9px] text-white text-center font-bold py-0.5">Primary</span>' : ''}
                    <button type="button" onclick="deleteExistingImage(${img.id}, this)" class="absolute top-0.5 right-0.5 bg-error text-white rounded-full w-5 h-5 flex items-center justify-center text-xs shadow hover:scale-110 transition-transform" title="Remove image">
                        <span class="material-symbols-outlined text-[12px]">close</span>
                    </button>
                `;
                container.appendChild(wrap);
            });
        } else {
            gallery.classList.add('hidden');
        }
    }

    async function deleteExistingImage(imageId, btn) {
        if (!confirm('Remove this photo from the product?')) return;
        btn.disabled = true;

        const fd = new FormData();
        const csrfToken = document.querySelector('input[name="<?= csrf_token() ?>"]')?.value || '<?= csrf_hash() ?>';
        fd.append('<?= csrf_token() ?>', csrfToken);

        try {
            const res = await fetch('<?= base_url('tenant/products/images/delete/') ?>/' + imageId, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            });
            const data = await res.json();
            if (data.success) {
                const thumb = document.getElementById('img-thumb-' + imageId);
                if (thumb) thumb.remove();
                const container = document.getElementById('p_existing_thumbnails');
                if (container && container.children.length === 0) {
                    document.getElementById('p_existing_gallery').classList.add('hidden');
                }
            } else {
                alert(data.error || 'Failed to delete photo.');
                btn.disabled = false;
            }
        } catch (err) {
            alert('Network error while deleting photo.');
            btn.disabled = false;
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /* ==========================================================================
       Product Variations / Options State & Controller (Compose-then-Apply UX)
       ========================================================================== */
    let variantState = [];
    let editingVariantIndex = null;

    function resetVariantComposer() {
        editingVariantIndex = null;
        const nameInput = document.getElementById('compose_variant_name');
        if (nameInput) nameInput.value = '';
        const valInput = document.getElementById('compose_variant_value');
        if (valInput) valInput.value = '';
        const stockInput = document.getElementById('compose_variant_stock');
        if (stockInput) stockInput.value = '0';
        const priceInput = document.getElementById('compose_variant_price');
        if (priceInput) priceInput.value = '';

        const btnText = document.getElementById('btnApplyVariantText');
        if (btnText) btnText.textContent = 'Apply';
        const btnIcon = document.getElementById('btnApplyVariantIcon');
        if (btnIcon) btnIcon.textContent = 'add';
    }

    function renderAppliedVariants() {
        const listContainer = document.getElementById('appliedVariantsList');
        const emptyHint = document.getElementById('variantEmptyHint');
        const countBadge = document.getElementById('variantCountBadge');

        if (!listContainer || !emptyHint) return;

        if (countBadge) {
            countBadge.textContent = variantState.length + (variantState.length === 1 ? ' option' : ' options');
        }

        if (variantState.length === 0) {
            emptyHint.classList.remove('hidden');
            listContainer.innerHTML = '';
            return;
        }

        emptyHint.classList.add('hidden');

        // Group variants by Category
        const groups = {};
        variantState.forEach((item, idx) => {
            const cat = item.name && item.name.trim() !== '' ? item.name.trim() : 'Type';
            if (!groups[cat]) groups[cat] = [];
            groups[cat].push({ item, idx });
        });

        let html = '';
        for (const [catName, entries] of Object.entries(groups)) {
            html += `
                <div class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/30 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-primary uppercase tracking-wider flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">label</span>
                            ${escapeHtml(catName)}
                        </span>
                        <span class="text-[10px] text-on-surface-variant font-medium">${entries.length} option${entries.length === 1 ? '' : 's'}</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
            `;

            entries.forEach(({ item, idx }) => {
                const isOut = item.stock <= 0;
                const isLow = !isOut && item.stock <= 5;
                const stockBadgeClass = isOut ? 'bg-red-100 text-red-700 border-red-200' : (isLow ? 'bg-amber-100 text-amber-700 border-amber-200' : 'bg-green-100 text-green-700 border-green-200');
                const stockLabel = isOut ? 'Out of Stock' : (isLow ? `Low: ${item.stock}` : `Stock: ${item.stock}`);
                const priceBadge = (item.price !== null && item.price !== '' && item.price !== undefined) ? `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-primary/10 text-primary border border-primary/20">₱${Number(item.price).toFixed(2)}</span>` : '';

                html += `
                    <div class="inline-flex items-center gap-2 py-1.5 px-3 bg-surface-container rounded-xl border border-outline-variant/40 shadow-xs hover:border-outline-variant transition-colors">
                        <span class="font-semibold text-xs text-on-surface">${escapeHtml(item.value)}</span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold border ${stockBadgeClass}">${stockLabel}</span>
                        ${priceBadge}
                        <div class="flex items-center gap-1 border-l border-outline-variant/30 pl-1.5 ml-0.5">
                            <button type="button" onclick="editVariant(${idx})" class="p-1 text-on-surface-variant hover:text-primary rounded-lg transition-colors flex items-center justify-center" title="Edit variant">
                                <span class="material-symbols-outlined text-[15px]">edit</span>
                            </button>
                            <button type="button" onclick="removeVariant(${idx})" class="p-1 text-on-surface-variant hover:text-error rounded-lg transition-colors flex items-center justify-center" title="Remove variant">
                                <span class="material-symbols-outlined text-[15px]">close</span>
                            </button>
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        }

        listContainer.innerHTML = html;
    }

    function applyVariant() {
        const nameInput = document.getElementById('compose_variant_name');
        const valInput = document.getElementById('compose_variant_value');
        const stockInput = document.getElementById('compose_variant_stock');
        const priceInput = document.getElementById('compose_variant_price');

        if (!valInput) return;
        const val = valInput.value.trim();
        if (val === '') {
            valInput.focus();
            valInput.classList.add('border-error');
            setTimeout(() => valInput.classList.remove('border-error'), 1500);
            return;
        }

        const rawName = nameInput ? nameInput.value.trim() : '';
        const name = rawName !== '' ? rawName : 'Type';
        const stock = stockInput && stockInput.value !== '' ? Math.max(0, parseInt(stockInput.value, 10) || 0) : 0;
        const price = priceInput && priceInput.value !== '' ? parseFloat(priceInput.value) : '';

        if (editingVariantIndex !== null && editingVariantIndex >= 0 && editingVariantIndex < variantState.length) {
            variantState[editingVariantIndex] = {
                ...variantState[editingVariantIndex],
                name: name,
                value: val,
                stock: stock,
                price: price
            };
        } else {
            // Check duplicate (case-insensitive name + val)
            const existingIdx = variantState.findIndex(v => v.name.toLowerCase() === name.toLowerCase() && v.value.toLowerCase() === val.toLowerCase());
            if (existingIdx >= 0) {
                variantState[existingIdx].stock = stock;
                variantState[existingIdx].price = price;
            } else {
                variantState.push({
                    id: null,
                    name: name,
                    value: val,
                    stock: stock,
                    price: price,
                    sku: ''
                });
            }
        }

        resetVariantComposer();
        renderAppliedVariants();
        if (valInput) valInput.focus();
    }

    function editVariant(idx) {
        if (idx < 0 || idx >= variantState.length) return;
        const item = variantState[idx];
        editingVariantIndex = idx;

        const nameInput = document.getElementById('compose_variant_name');
        if (nameInput) nameInput.value = item.name || '';
        const valInput = document.getElementById('compose_variant_value');
        if (valInput) valInput.value = item.value || '';
        const stockInput = document.getElementById('compose_variant_stock');
        if (stockInput) stockInput.value = item.stock;
        const priceInput = document.getElementById('compose_variant_price');
        if (priceInput) priceInput.value = (item.price !== null && item.price !== '' && item.price !== undefined) ? item.price : '';

        const btnText = document.getElementById('btnApplyVariantText');
        if (btnText) btnText.textContent = 'Update';
        const btnIcon = document.getElementById('btnApplyVariantIcon');
        if (btnIcon) btnIcon.textContent = 'check';

        if (valInput) valInput.focus();
    }

    function removeVariant(idx) {
        if (idx < 0 || idx >= variantState.length) return;
        if (editingVariantIndex === idx) {
            resetVariantComposer();
        } else if (editingVariantIndex !== null && editingVariantIndex > idx) {
            editingVariantIndex--;
        }
        variantState.splice(idx, 1);
        renderAppliedVariants();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const btnApply = document.getElementById('btnApplyVariant');
        if (btnApply) {
            btnApply.addEventListener('click', applyVariant);
        }

        ['compose_variant_value', 'compose_variant_stock', 'compose_variant_price'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        applyVariant();
                    }
                });
            }
        });

        // Form submit hook: serialize variantState into hidden inputs for backend compatibility
        const productForm = document.getElementById('productModalForm') || document.querySelector('#productModal form');
        if (productForm) {
            productForm.addEventListener('submit', function() {
                const hiddenContainer = document.getElementById('variantHiddenInputsContainer');
                if (!hiddenContainer) return;
                hiddenContainer.innerHTML = '';
                variantState.forEach(v => {
                    hiddenContainer.insertAdjacentHTML('beforeend', `
                        <input type="hidden" name="variant_name[]" value="${escapeHtml(v.name || 'Type')}">
                        <input type="hidden" name="variant_value[]" value="${escapeHtml(v.value)}">
                        <input type="hidden" name="variant_stock[]" value="${v.stock}">
                        <input type="hidden" name="variant_price[]" value="${v.price !== null && v.price !== undefined ? v.price : ''}">
                        <input type="hidden" name="variant_sku[]" value="${escapeHtml(v.sku || '')}">
                    `);
                });
            });
        }
    });

    document.getElementById('p_images')?.addEventListener('change', function(e) {
        if (this.files && this.files.length > 0) {
            Array.from(this.files).forEach(file => {
                if (!newSelectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    newSelectedFiles.push(file);
                }
            });
            renderNewImagePreviews();
        }
    });

    let stockProductId = null;
    function openStockModal(id, current, name) {
        stockProductId = id;
        document.getElementById('stockProductName').textContent = name;
        document.getElementById('stockCurrent').textContent = current;
        document.getElementById('stockQty').value = '10';
        document.getElementById('stockFeedback').textContent = '';
        document.getElementById('stockModal').classList.remove('hidden');
    }

    async function submitStockAdjust(mode) {
        const id = stockProductId;
        const qty = parseInt(document.getElementById('stockQty').value, 10);
        if (!id || !qty || qty < 1) {
            document.getElementById('stockFeedback').textContent = 'Enter a quantity of at least 1.';
            return;
        }
        const csrf = document.querySelector('#stockCsrfForm [name="csrf_test_name"]').value;
        const body = new FormData();
        body.append('csrf_test_name', csrf);
        body.append('product_id', id);
        if (mode === 'add') { body.append('delta', qty); } else { body.append('set_to', qty); }
        document.getElementById('stockFeedback').textContent = 'Updating...';
        try {
            const resp = await fetch('<?= base_url('tenant/products/adjust-stock') ?>', { method: 'POST', body });
            const data = await resp.json();
            if (!data.success) {
                document.getElementById('stockFeedback').textContent = data.error || 'Update failed.';
                return;
            }
            document.getElementById('stockCurrent').textContent = data.stock_quantity;
            const cell = document.getElementById('stock-pill-' + data.product_id);
            if (cell) {
                cell.textContent = Number(data.stock_quantity).toLocaleString();
                cell.className = 'px-md py-1 rounded-full text-label-sm font-semibold inline-flex items-center gap-xs ' +
                    (data.stock_status === 'out' ? 'bg-red-100 text-red-700' : (data.stock_status === 'low' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'));
            }
            document.getElementById('stockFeedback').textContent = 'Stock updated.';
            setTimeout(() => document.getElementById('stockModal').classList.add('hidden'), 700);
        } catch (e) {
            document.getElementById('stockFeedback').textContent = 'Could not reach the server.';
        }
    }

    // --- Bulk Action Handling ---
    function getSelectedProductIds() {
        const checkboxes = document.querySelectorAll('.product-checkbox:checked');
        return Array.from(checkboxes).map(cb => cb.value);
    }

    function updateBulkBar() {
        const selected = getSelectedProductIds();
        const bar = document.getElementById('bulkActionsBar');
        const badge = document.getElementById('selectedCountBadge');
        if (!bar || !badge) return;
        badge.textContent = selected.length;
        if (selected.length > 0) {
            bar.classList.remove('hidden');
        } else {
            bar.classList.add('hidden');
        }
    }

    function deselectAllProducts() {
        document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = false);
        const selectAll = document.getElementById('selectAll');
        if (selectAll) selectAll.checked = false;
        updateBulkBar();
    }

    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = selectAllCheckbox.checked);
            updateBulkBar();
        });
    }

    document.querySelectorAll('.product-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const all = document.querySelectorAll('.product-checkbox');
            const checked = document.querySelectorAll('.product-checkbox:checked');
            if (selectAllCheckbox) selectAllCheckbox.checked = (all.length > 0 && all.length === checked.length);
            updateBulkBar();
        });
    });

    function openBulkStockModal() {
        const selected = getSelectedProductIds();
        if (selected.length === 0) return;
        document.getElementById('bulkStockCountText').textContent = selected.length;
        document.getElementById('bulkStockFeedback').textContent = '';
        document.getElementById('bulkStockModal').classList.remove('hidden');
    }

    async function submitBulkStockAdjust(mode) {
        const selected = getSelectedProductIds();
        if (selected.length === 0) return;
        const qtyVal = document.getElementById('bulkStockQty').value;
        const qty = parseInt(qtyVal, 10);
        if (isNaN(qty) || qty < 0) {
            document.getElementById('bulkStockFeedback').textContent = 'Please enter a valid non-negative number.';
            return;
        }

        const fb = document.getElementById('bulkStockFeedback');
        fb.textContent = 'Updating ' + selected.length + ' products...';

        const body = new FormData();
        selected.forEach(id => body.append('product_ids[]', id));
        if (mode === 'add') {
            body.append('delta', qty);
        } else {
            body.append('set_to', qty);
        }

        try {
            const resp = await fetch('<?= base_url('tenant/products/bulk-adjust-stock') ?>', {
                method: 'POST',
                body: body
            });
            const data = await resp.json();
            if (data.success) {
                fb.textContent = data.message || 'Stock updated successfully!';
                setTimeout(() => window.location.reload(), 800);
            } else {
                fb.textContent = data.error || 'Failed to update stock.';
            }
        } catch (err) {
            fb.textContent = 'Network error while updating stock.';
        }
    }

    async function submitBulkArchive() {
        const selected = getSelectedProductIds();
        if (selected.length === 0) return;
        if (!confirm('Archive ' + selected.length + ' selected product(s)? They will be hidden from the storefront and can be restored from the Archive page.')) {
            return;
        }

        const body = new FormData();
        selected.forEach(id => body.append('product_ids[]', id));

        try {
            const resp = await fetch('<?= base_url('tenant/products/bulk-archive') ?>', {
                method: 'POST',
                body: body
            });
            const data = await resp.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || 'Failed to archive products.');
            }
        } catch (err) {
            alert('Network error while archiving products.');
        }
    }
</script>

<?= $this->endSection() ?>