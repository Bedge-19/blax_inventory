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

    <!-- Header & Action Row -->
    <div class="flex flex-wrap justify-between items-center gap-md">
        <div>
            <h2 class="text-headline-lg font-headline-lg text-on-surface">Inventory Management</h2>
            <p class="text-body-md font-body-md text-on-surface-variant">Track and manage your product stock levels across all categories.</p>
        </div>
        <button onclick="openProductModal(null)" class="bg-primary text-on-primary px-lg py-sm rounded-lg flex items-center gap-sm font-button text-button hover:bg-primary/90 transition-colors shadow-sm active:scale-95">
            <span class="material-symbols-outlined">add</span>
            New Product
        </button>
    </div>

    <!-- Inventory Overview Cards (Bento Style) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-gutter">
        <div class="glass-card p-lg rounded-xl hover-lift flex flex-col justify-between h-32">
            <div class="flex justify-between items-start">
                <span class="text-label-sm font-label-sm text-on-surface-variant">Total SKU Count</span>
                <span class="material-symbols-outlined text-primary bg-primary-fixed p-xs rounded">inventory</span>
            </div>
            <div class="text-headline-md font-headline-md"><?= number_format((int) $summary['total_sku']) ?></div>
        </div>
        <div class="glass-card p-lg rounded-xl hover-lift flex flex-col justify-between h-32">
            <div class="flex justify-between items-start">
                <span class="text-label-sm font-label-sm text-on-surface-variant">Low Stock Items</span>
                <span class="material-symbols-outlined text-secondary bg-secondary-container p-xs rounded">warning</span>
            </div>
            <div class="text-headline-md font-headline-md text-secondary"><?= number_format((int) $summary['low_stock']) ?></div>
        </div>
        <div class="glass-card p-lg rounded-xl hover-lift flex flex-col justify-between h-32">
            <div class="flex justify-between items-start">
                <span class="text-label-sm font-label-sm text-on-surface-variant">Out of Stock</span>
                <span class="material-symbols-outlined text-error bg-error-container p-xs rounded">block</span>
            </div>
            <div class="text-headline-md font-headline-md text-error"><?= number_format((int) $summary['out_of_stock']) ?></div>
        </div>
        <div class="glass-card p-lg rounded-xl hover-lift flex flex-col justify-between h-32">
            <div class="flex justify-between items-start">
                <span class="text-label-sm font-label-sm text-on-surface-variant">Total Inventory Value</span>
                <span class="material-symbols-outlined text-tertiary bg-tertiary-fixed p-xs rounded">payments</span>
            </div>
            <div class="text-headline-md font-headline-md">₱<?= number_format((float) $summary['inventory_value'], 2) ?></div>
        </div>
    </div>

    <?php if ((int)($summary['low_stock'] ?? 0) > 0 || (int)($summary['out_of_stock'] ?? 0) > 0): ?>
        <!-- Low Stock / Out of Stock Alert Banner -->
        <div class="rounded-2xl p-md lg:p-lg bg-amber-500/10 border border-amber-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-md shadow-sm">
            <div class="flex items-center gap-md">
                <span class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-700 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">notification_important</span>
                </span>
                <div>
                    <h4 class="font-bold text-on-surface text-body-md">Low Stock &amp; Out of Stock Warning</h4>
                    <p class="text-xs text-on-surface-variant">
                        <span class="font-semibold text-amber-800"><?= (int)($summary['low_stock'] ?? 0) ?> items</span> are at or below threshold and 
                        <span class="font-semibold text-red-700"><?= (int)($summary['out_of_stock'] ?? 0) ?> items</span> are completely out of stock.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-sm flex-wrap">
                <?php if ((int)($summary['low_stock'] ?? 0) > 0): ?>
                    <a href="<?= base_url('tenant/inventory?stock=low') ?>" class="px-md py-1.5 bg-amber-600 text-white rounded-xl text-xs font-bold hover:bg-amber-700 transition-colors shadow-sm">
                        Filter Low Stock
                    </a>
                <?php endif; ?>
                <?php if ((int)($summary['out_of_stock'] ?? 0) > 0): ?>
                    <a href="<?= base_url('tenant/inventory?stock=out') ?>" class="px-md py-1.5 bg-red-600 text-white rounded-xl text-xs font-bold hover:bg-red-700 transition-colors shadow-sm">
                        Filter Out of Stock
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Search and Filter Bar -->
    <form method="get" action="<?= base_url('tenant/inventory') ?>" class="glass-card p-md rounded-xl flex flex-wrap items-center gap-md">
        <div class="relative flex-1 min-w-0 sm:min-w-[240px]">
            <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline">search</span>
            <input name="q" value="<?= esc($filters['q']) ?>" class="w-full pl-xl pr-md py-sm bg-surface-container-low border border-outline-variant rounded-lg text-body-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" placeholder="Search products or SKUs..." type="text">
        </div>
        <div class="flex flex-wrap items-center gap-sm">
            <select name="category" class="bg-surface-container-low border border-outline-variant rounded-lg px-md py-sm text-label-sm font-label-sm text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">All Categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (int) $filters['category'] === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="stock" class="bg-surface-container-low border border-outline-variant rounded-lg px-md py-sm text-label-sm font-label-sm text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Status: All</option>
                <option value="in" <?= $filters['stock'] === 'in' ? 'selected' : '' ?>>In Stock</option>
                <option value="low" <?= $filters['stock'] === 'low' ? 'selected' : '' ?>>Low Stock</option>
                <option value="out" <?= $filters['stock'] === 'out' ? 'selected' : '' ?>>Out of Stock</option>
            </select>
            <button type="button" onclick="document.getElementById('advancedFilters').classList.toggle('hidden')" class="bg-surface-container-high text-on-surface px-md py-sm rounded-lg flex items-center gap-xs font-label-sm hover:bg-surface-variant transition-colors">
                <span class="material-symbols-outlined text-[18px]">filter_list</span>
                Advanced Filters
            </button>
        </div>
        <div id="advancedFilters" class="hidden w-full flex flex-wrap items-end gap-md border-t border-outline-variant/20 pt-md mt-xs">
            <div class="flex-1 min-w-0 sm:min-w-[200px]">
                <label class="text-label-sm font-label-sm text-on-surface-variant block mb-xs">SKU</label>
                <input name="sku" value="<?= esc($filters['sku']) ?>" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant rounded-lg text-body-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Filter by SKU...">
            </div>
            <label class="flex items-center gap-sm text-label-sm font-label-sm text-on-surface-variant pb-sm">
                <input type="checkbox" name="bestseller" value="1" <?= $filters['bestseller'] ? 'checked' : '' ?> class="rounded border-outline-variant text-primary">
                Bestsellers only
            </label>
            <button type="submit" class="bg-primary text-on-primary px-md py-sm rounded-lg text-label-sm font-semibold hover:bg-primary/90 transition-colors">Apply</button>
            <a href="<?= base_url('tenant/inventory') ?>" class="px-md py-sm text-on-surface-variant hover:text-on-surface text-label-sm font-semibold">Reset</a>
        </div>
    </form>

    <!-- Product Table Container -->
    <div class="glass-card rounded-xl overflow-hidden shadow-sm">
        <div class="responsive-table">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low border-b border-outline-variant/30">
                    <tr>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider w-12">
                            <input type="checkbox" id="selectAll" class="rounded border-outline-variant text-primary focus:ring-primary h-4 w-4">
                        </th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Product</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">SKU</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Category</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider">Description</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider text-center">Stock Level</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider text-right">Unit Price</th>
                        <th class="px-lg py-md text-label-sm font-label-sm text-on-surface-variant opacity-70 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $p): ?>
                            <?php
                            $stock = (int) $p['stock_quantity'];
                            $thr   = (int) ($p['low_stock_threshold'] ?? 5);
                            [$pill, $icon] = inv_stock_pill($stock, $thr);
                            ?>
                            <tr class="hover:bg-surface-container-low/50 transition-colors" id="row-<?= (int) $p['id'] ?>">
                                <td class="px-lg py-md">
                                    <input type="checkbox" name="selected_products[]" value="<?= esc($p['id']) ?>" class="product-checkbox rounded border-outline-variant text-primary focus:ring-primary h-4 w-4">
                                </td>
                                <td class="px-lg py-md">
                                    <div class="flex items-center gap-md">
                                        <?php if (!empty($p['image_url'])): ?>
                                            <?php $imgSrc = str_starts_with($p['image_url'], 'http') ? $p['image_url'] : base_url($p['image_url']); ?>
                                            <img src="<?= esc($imgSrc) ?>" alt="<?= esc($p['name']) ?>" class="w-12 h-12 rounded-lg object-cover bg-surface-variant">
                                        <?php else: ?>
                                            <div class="w-12 h-12 rounded-lg bg-surface-variant flex items-center justify-center text-on-surface-variant">
                                                <span class="material-symbols-outlined">inventory_2</span>
                                            </div>
                                        <?php endif; ?>
                                        <span class="text-body-md font-semibold text-on-surface"><?= esc($p['name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-lg py-md text-body-md text-on-surface-variant"><?= esc($p['sku']) ?></td>
                                <td class="px-lg py-md text-body-md text-on-surface-variant"><?= esc($p['category_name'] ?? 'General') ?></td>
                                <td class="px-lg py-md text-body-md text-on-surface-variant max-w-[200px]" title="<?= esc($p['description'] ?? '') ?>">
                                    <?= esc(mb_strimwidth($p['description'] ?? '', 0, 60, '…')) ?>
                                </td>
                                <td class="px-lg py-md text-center">
                                    <span id="stock-pill-<?= (int) $p['id'] ?>" class="px-md py-1 rounded-full <?= $pill ?> text-label-sm font-semibold inline-flex items-center gap-xs">
                                        <span class="material-symbols-outlined text-[14px]"><?= $icon ?></span>
                                        <?= number_format($stock) ?>
                                    </span>
                                    <span class="block text-[10px] text-on-surface-variant opacity-70 mt-0.5">Min: <?= $thr ?></span>
                                </td>
                                <td class="px-lg py-md text-body-md text-on-surface text-right">₱<?= number_format((float) $p['price'], 2) ?></td>
                                <td class="px-lg py-md text-right">
                                    <div class="flex items-center justify-end gap-sm">
                                        <button type="button"
                                                onclick="openDescriptionModal(this)"
                                                class="p-xs hover:bg-surface-container-high rounded text-on-surface-variant"
                                                title="View Description"
                                                data-name="<?= esc($p['name']) ?>"
                                                data-description="<?= esc($p['description'] ?? '') ?>">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                        <button type="button"
                                                onclick="openProductModal(this)"
                                                class="p-xs hover:bg-surface-container-high rounded text-on-surface-variant"
                                                title="Edit Product"
                                                data-id="<?= (int) $p['id'] ?>"
                                                data-name="<?= esc($p['name']) ?>"
                                                data-sku="<?= esc($p['sku']) ?>"
                                                data-category="<?= (int) ($p['category_id'] ?? 0) ?>"
                                                data-price="<?= esc($p['price']) ?>"
                                                data-compare="<?= esc($p['compare_at_price'] ?? '') ?>"
                                                data-stock="<?= (int) $p['stock_quantity'] ?>"
                                                data-threshold="<?= (int) ($p['low_stock_threshold'] ?? 5) ?>"
                                                data-description="<?= esc($p['description'] ?? '') ?>"
                                                data-image="<?= esc(!empty($p['image_url']) ? (str_starts_with($p['image_url'], 'http') ? $p['image_url'] : base_url($p['image_url'])) : '') ?>">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                        <button type="button"
                                                onclick="openStockModal(<?= (int) $p['id'] ?>, <?= (int) $p['stock_quantity'] ?>, '<?= esc($p['name'], 'js') ?>')"
                                                class="p-xs hover:bg-surface-container-high rounded text-primary"
                                                title="Adjust / Restock">
                                            <span class="material-symbols-outlined">autorenew</span>
                                        </button>
                                        <form action="<?= base_url('tenant/products/archive/' . (int) $p['id']) ?>" method="POST" onsubmit="return confirm('Archive this product? It will be hidden from your storefront and can be restored from the Archive page.')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="p-xs hover:bg-error-container/20 rounded text-error" title="Archive">
                                                <span class="material-symbols-outlined">archive</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="py-lg text-center text-on-surface-variant">No products match your filters.</td>
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
        <div class="px-lg py-md bg-surface-container-low flex justify-between items-center border-t border-outline-variant/30 flex-wrap gap-sm">
            <p class="text-label-sm font-label-sm text-on-surface-variant">Showing <?= number_format($start) ?> to <?= number_format($end) ?> of <?= number_format($total) ?> items</p>
            <?php if ($pages > 1): ?>
                <div class="flex items-center gap-xs">
                    <a class="p-sm rounded hover:bg-surface-container-high <?= $cur <= 1 ? 'pointer-events-none opacity-30' : '' ?>" href="<?= $pager->getPreviousPageURI('inventory') ?>">
                        <span class="material-symbols-outlined">chevron_left</span>
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
                            <span class="px-xs text-outline">...</span>
                        <?php endif; ?>
                        <a class="w-8 h-8 rounded flex items-center justify-center text-label-sm <?= $cur === $num ? 'bg-primary text-on-primary font-semibold' : 'hover:bg-surface-container-high' ?>" href="<?= $pager->getPageURI($num, 'inventory') ?>"><?= $num ?></a>
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
    <div class="bg-surface-container-lowest rounded-2xl p-xl max-w-lg w-full space-y-md border border-outline-variant/30 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b border-outline-variant/20 pb-sm">
            <h3 id="productModalTitle" class="text-title-lg font-bold">Add New Product</h3>
            <button onclick="document.getElementById('productModal').classList.add('hidden')" class="text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form action="<?= base_url('tenant/products/save') ?>" method="POST" enctype="multipart/form-data" class="space-y-md">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" id="product_id">

            <!-- Product Image Upload -->
            <div>
                <label class="text-label-sm font-bold text-on-surface-variant">Product Photo</label>
                <div id="p_img_dropzone" onclick="document.getElementById('p_image').click()" class="mt-xs cursor-pointer border-2 border-dashed border-outline-variant rounded-xl flex flex-col items-center justify-center gap-sm p-md hover:border-primary transition-colors bg-surface-container-low min-h-[120px] relative overflow-hidden">
                    <img id="p_img_preview" src="" alt="Preview" class="absolute inset-0 w-full h-full object-cover rounded-xl hidden">
                    <div id="p_img_placeholder" class="flex flex-col items-center gap-xs text-on-surface-variant">
                        <span class="material-symbols-outlined text-4xl">add_photo_alternate</span>
                        <span class="text-label-sm">Click to upload product photo</span>
                        <span class="text-[11px] text-outline">JPG, PNG, WEBP — Max 5MB</span>
                    </div>
                    <button type="button" id="p_img_clear" onclick="clearProductImage(event)" class="hidden absolute top-1 right-1 bg-error text-on-error rounded-full w-6 h-6 flex items-center justify-center shadow">
                        <span class="material-symbols-outlined text-[14px]">close</span>
                    </button>
                </div>
                <input type="file" name="product_image" id="p_image" accept="image/jpeg,image/png,image/webp" class="sr-only">
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
            <div class="grid grid-cols-2 gap-md">
                <div>
                    <label class="text-label-sm font-bold text-on-surface-variant">Price (₱)</label>
                    <input type="number" step="0.01" min="0" name="price" id="p_price" required class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl">
                </div>
                <div>
                    <label class="text-label-sm font-bold text-on-surface-variant">Compare-at Price (₱)</label>
                    <input type="number" step="0.01" min="0" name="compare_at_price" id="p_compare" class="w-full p-md bg-surface-container-low border border-outline-variant rounded-xl">
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
            <button type="submit" class="w-full py-md bg-primary text-on-primary rounded-xl font-bold hover:bg-primary/90">Save Product</button>
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
        document.getElementById('p_description').value = '';
        resetProductImage();
        if (btn) {
            document.getElementById('productModalTitle').textContent = 'Edit Product';
            document.getElementById('product_id').value = btn.dataset.id;
            document.getElementById('p_name').value = btn.dataset.name || '';
            document.getElementById('p_sku').value = btn.dataset.sku || '';
            document.getElementById('p_category').value = btn.dataset.category || '0';
            document.getElementById('p_price').value = btn.dataset.price || '';
            document.getElementById('p_compare').value = btn.dataset.compare || '';
            document.getElementById('p_stock').value = btn.dataset.stock || '';
            document.getElementById('p_threshold').value = btn.dataset.threshold || '5';
            document.getElementById('p_description').value = btn.dataset.description || '';
            // Show existing image if available
            const imgUrl = btn.dataset.image || '';
            if (imgUrl) {
                document.getElementById('p_img_preview').src = imgUrl;
                document.getElementById('p_img_preview').classList.remove('hidden');
                document.getElementById('p_img_placeholder').classList.add('hidden');
                document.getElementById('p_img_clear').classList.remove('hidden');
            }
        }
        modal.classList.remove('hidden');
    }

    function resetProductImage() {
        document.getElementById('p_image').value = '';
        document.getElementById('p_img_preview').src = '';
        document.getElementById('p_img_preview').classList.add('hidden');
        document.getElementById('p_img_placeholder').classList.remove('hidden');
        document.getElementById('p_img_clear').classList.add('hidden');
    }

    function clearProductImage(e) {
        e.stopPropagation();
        resetProductImage();
    }

    document.getElementById('p_image').addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('p_img_preview').src = e.target.result;
            document.getElementById('p_img_preview').classList.remove('hidden');
            document.getElementById('p_img_placeholder').classList.add('hidden');
            document.getElementById('p_img_clear').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
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