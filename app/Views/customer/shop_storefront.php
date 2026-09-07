<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<style>
    .drag-active { border-color: #2563eb !important; background-color: #eeefff !important; }
</style>

<main class="max-w-container-max mx-auto px-4 md:px-8 lg:px-10 py-8 md:py-xl flex-grow w-full">

    <!-- Shop Header -->
    <section class="mb-xxl flex flex-col md:flex-row gap-lg items-start md:items-center">

        <div class="w-32 h-32 md:w-48 md:h-48 rounded-full border-4 border-white shadow-md overflow-hidden flex-shrink-0 bg-surface-container flex items-center justify-center">

            <?php if (!empty($shop['logo_url'])): ?>

                <img class="w-full h-full object-cover" src="<?= esc(logo_url($shop['logo_url'])) ?>" alt="<?= esc($shop['shop_name']) ?> logo">

            <?php else: ?>

                <span class="material-symbols-outlined text-primary text-6xl">store</span>

            <?php endif; ?>

        </div>

        <div class="flex-grow">

            <div class="flex flex-col md:flex-row md:items-center gap-sm mb-xs">

                <h1 class="text-headline-lg font-headline-lg text-on-surface"><?= esc($shop['shop_name']) ?></h1>

                <div class="flex items-center gap-xs bg-secondary-container px-sm py-xs rounded-full">

                    <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: &quot;FILL&quot; 1;">star</span>
                    <span class="text-label-sm font-label-sm text-on-secondary-container"><?= number_format($shop['rating_average'] ?? 5.0, 1) ?> (<?= number_format($shop['rating_count'] ?? 0) ?> Reviews)</span>

                </div>

            </div>

            <p class="text-body-lg font-body-lg text-on-surface-variant max-w-2xl">

                <?= esc($shop['description'] ?? 'Premium printing and stationery solutions. High-quality products and fast service guaranteed.') ?>

            </p>

            <div class="mt-md flex gap-sm">

                <button class="px-lg py-sm bg-primary text-on-primary rounded-lg font-button text-button hover:opacity-90 transition-all flex items-center gap-xs">

                    <span class="material-symbols-outlined">mail</span> Contact Shop

                </button>

                <button class="px-lg py-sm border border-outline text-on-surface rounded-lg font-button text-button hover:bg-surface-container transition-all">

                    Follow Store

                </button>

            </div>

        </div>

    </section>

    <?php if (!empty($businessHours)): ?>

        <!-- Business Hours -->
        <section class="mb-xxl max-w-3xl bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-lg md:p-xl">

            <div class="flex items-center gap-sm border-b border-outline-variant/30 pb-md mb-md">
                <span class="material-symbols-outlined text-primary">schedule</span>
                <h2 class="text-title-lg font-bold text-on-surface">Business Hours</h2>
            </div>

            <dl class="space-y-sm">
                <?php foreach ($businessHours as $hours): ?>
                    <div class="flex items-center justify-between gap-md text-body-md">
                        <dt class="font-medium text-on-surface"><?= esc($hours['label']) ?></dt>
                        <dd class="text-on-surface-variant <?= $hours['is_closed'] ? 'font-medium text-error' : '' ?>">
                            <?= $hours['is_closed'] ? 'Closed' : esc($hours['open'] . ' – ' . $hours['close']) ?>
                        </dd>
                    </div>
                <?php endforeach; ?>
            </dl>

        </section>

    <?php endif; ?>

    <!-- PDF Printing Services Bento (If Shop Offers Printing) -->
    <!-- PDF Printing Services Bento (If Shop Offers Printing) -->
    <?php if (!empty($shop['offers_printing'])): ?>

        <section class="mb-xxl" id="printing-services-section">

            <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-lg gap-md">

                <div>

                    <div class="flex items-center gap-xs text-primary font-bold text-xs uppercase tracking-wider mb-1">
                        <span class="material-symbols-outlined text-[18px]">print</span>
                        Document Replication
                    </div>
                    <h2 class="text-headline-md font-headline-md text-on-surface">Printing Services</h2>
                    <p class="text-on-surface-variant font-body-md text-sm">High-fidelity document reproduction for PDF files</p>

                </div>

                <!-- Subtle 3-Step Indicator -->
                <div class="inline-flex items-center gap-1.5 p-1.5 bg-surface-container-low rounded-xl border border-outline-variant/30 text-xs font-medium self-start sm:self-auto">
                    <span class="flex items-center gap-1 text-primary font-bold px-2 py-1 bg-primary/10 rounded-lg">
                        <span class="w-4 h-4 rounded-full bg-primary text-on-primary text-[10px] flex items-center justify-center font-bold">1</span> Upload
                    </span>
                    <span class="text-outline-variant text-xs">→</span>
                    <span class="flex items-center gap-1 text-on-surface-variant px-2 py-1">
                        <span class="w-4 h-4 rounded-full bg-surface-container-high text-on-surface-variant text-[10px] flex items-center justify-center font-bold">2</span> Configure
                    </span>
                    <span class="text-outline-variant text-xs">→</span>
                    <span class="flex items-center gap-1 text-on-surface-variant px-2 py-1">
                        <span class="w-4 h-4 rounded-full bg-surface-container-high text-on-surface-variant text-[10px] flex items-center justify-center font-bold">3</span> Pay 50%
                    </span>
                </div>

            </div>

            <form action="<?= base_url('printing/request') ?>" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-12 gap-gutter items-start">

                <?= csrf_field() ?>

                <input type="hidden" name="shop_id" value="<?= $shop['id'] ?>">
                <input type="hidden" name="page_count" id="pdf-page-count" value="0">

                <!-- Left Column: Compact Upload & Document Status (5 cols on Desktop) -->
                <div class="lg:col-span-5 space-y-md">

                    <!-- Compact Dropzone Card -->
                    <div class="bg-surface-container-lowest border-2 border-dashed border-outline-variant rounded-2xl p-lg min-h-[240px] max-h-[300px] flex flex-col items-center justify-center text-center transition-all hover:border-primary group cursor-pointer relative" id="dropzone">

                        <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary mb-sm group-hover:scale-110 group-hover:bg-primary group-hover:text-on-primary transition-all duration-200" id="dropzone-icon-box">

                            <span class="material-symbols-outlined text-2xl">picture_as_pdf</span>

                        </div>

                        <h3 class="text-title-md font-bold text-on-surface mb-0.5">Upload your PDF</h3>
                        <p class="text-on-surface-variant text-xs mb-md max-w-xs leading-relaxed">Drag &amp; drop your documents here, or click to browse files from your device.</p>

                        <input accept=".pdf" class="sr-only" id="fileInput" type="file" name="document">

                        <button type="button" id="selectPdfBtn" class="bg-primary text-on-primary px-lg py-2 rounded-xl font-button text-xs shadow-sm hover:shadow-md hover:bg-primary/90 transition-all flex items-center gap-xs">

                            <span class="material-symbols-outlined text-[18px]">upload_file</span>
                            Select PDF Document

                        </button>

                        <p class="mt-sm text-[11px] text-outline">Max file size: 50MB &bull; Formats: PDF only</p>

                    </div>

                    <!-- Upload Verification Status Chip -->
                    <div class="bg-surface-container-low rounded-xl p-md border border-outline-variant/30 flex flex-col gap-xs" id="upload-status-container">

                        <div class="flex items-center justify-between gap-sm flex-wrap">

                            <span class="px-sm py-1 rounded-full text-xs font-semibold bg-surface-container-high text-on-surface-variant flex items-center gap-xs" id="upload-badge">

                                <span class="material-symbols-outlined text-[15px]">info</span>No PDF selected

                            </span>

                            <span class="text-xs font-bold text-on-surface-variant" id="page-count">No file selected</span>

                        </div>

                        <p class="text-xs text-on-surface-variant hidden mt-1" id="upload-hint">Page count will be verified automatically once your PDF is uploaded.</p>

                    </div>

                    <!-- Same-Day Pickup Promotion Card (Balanced under upload) -->
                    <div class="bg-gradient-to-br from-primary to-primary-container text-on-primary rounded-2xl p-md sm:p-lg relative overflow-hidden shadow-sm">

                        <div class="relative z-10">

                            <div class="flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider mb-1 opacity-90">
                                <span class="material-symbols-outlined text-[16px]">bolt</span> Same-Day Ready
                            </div>
                            <h4 class="text-title-sm font-bold mb-0.5">Quick Kiosk Pick-up</h4>
                            <p class="text-xs opacity-90 leading-relaxed">Submit your request before 2:00 PM and pick up your finished prints today at any available shop kiosk.</p>

                        </div>

                        <span class="material-symbols-outlined absolute -bottom-3 -right-3 text-7xl opacity-10 select-none pointer-events-none">print</span>

                    </div>

                </div>

                <!-- Right Column: Compact Order Configuration (7 cols on Desktop) -->
                <div class="lg:col-span-7">

                    <div class="bg-surface-container-lowest rounded-2xl p-lg md:p-xl border border-outline-variant/30 shadow-sm space-y-md">

                        <div class="flex items-center justify-between border-b border-outline-variant/20 pb-sm">

                            <h4 class="text-title-md font-bold text-on-surface flex items-center gap-xs">

                                <span class="material-symbols-outlined text-primary text-[22px]">tune</span>
                                Order Configuration

                            </h4>

                            <span class="text-[11px] font-bold text-primary uppercase tracking-wider bg-primary/10 px-2 py-0.5 rounded-full">Step 2 of 3</span>

                        </div>

                        <div class="space-y-md">

                            <!-- Paper Size & Copies -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">

                                <div>

                                    <label class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider mb-1 block">Paper Size</label>

                                    <div class="relative">

                                        <select name="paper_size" class="w-full py-2 px-3 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs font-medium focus:ring-2 focus:ring-primary focus:border-primary text-on-surface transition-all appearance-none pr-8">

                                            <option value="letter">Letter (8.5 x 11)</option>
                                            <option value="legal">Legal (8.5 x 14)</option>
                                            <option value="a4">A4 (8.27 x 11.69)</option>
                                            <option value="a3">A3 (11.7 x 16.5)</option>
                                            <option value="a2">A2 (16.5 x 23.4)</option>
                                            <option value="a1">A1 (23.4 x 33.1)</option>
                                            <option value="a0">A0 (33.1 x 46.8)</option>
                                            <option value="a5">A5</option>
                                            <option value="b5">B5</option>
                                            <option value="b4">B4</option>

                                        </select>

                                        <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[18px] text-outline pointer-events-none">expand_more</span>

                                    </div>

                                </div>

                                <div>

                                    <label class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider mb-1 block">Copies</label>

                                    <div class="relative">

                                        <select name="copies" class="w-full py-2 px-3 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs font-medium focus:ring-2 focus:ring-primary focus:border-primary text-on-surface transition-all appearance-none pr-8">

                                            <option value="1">1 Copy</option>
                                            <option value="2">2 Copies</option>
                                            <option value="5">5 Copies</option>
                                            <option value="10">10 Copies</option>

                                        </select>

                                        <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[18px] text-outline pointer-events-none">expand_more</span>

                                    </div>

                                </div>

                            </div>

                            <!-- Color Mode -->
                            <div>

                                <label class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider mb-1 block">Color Mode</label>

                                <div class="grid grid-cols-2 gap-sm">

                                    <label class="cursor-pointer">

                                        <input type="radio" name="color_mode" value="colored" class="peer sr-only" checked>
                                        <span class="flex items-center justify-center gap-1.5 py-2 px-3 text-center rounded-xl text-xs font-bold border border-outline-variant/40 bg-surface-container-low text-on-surface transition-all peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary peer-checked:shadow-sm">
                                            <span class="material-symbols-outlined text-[16px]">palette</span> Full Color
                                        </span>

                                    </label>

                                    <label class="cursor-pointer">

                                        <input type="radio" name="color_mode" value="black_white" class="peer sr-only">
                                        <span class="flex items-center justify-center gap-1.5 py-2 px-3 text-center rounded-xl text-xs font-bold border border-outline-variant/40 bg-surface-container-low text-on-surface transition-all peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary peer-checked:shadow-sm">
                                            <span class="material-symbols-outlined text-[16px]">grayscale</span> Black &amp; White
                                        </span>

                                    </label>

                                </div>

                            </div>

                            <!-- Binding Options -->
                            <div>

                                <label class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider mb-1 block">Binding Options</label>

                                <div class="grid grid-cols-3 gap-sm">

                                    <label class="cursor-pointer">

                                        <input checked class="peer sr-only" name="binding" type="radio" value="none">
                                        <div class="p-2 text-center rounded-xl border border-outline-variant/40 bg-surface-container-low hover:bg-surface-container transition-all peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-1 peer-checked:ring-primary flex flex-col items-center justify-center">
                                            <span class="text-xs font-bold text-on-surface block">No Binding</span>
                                            <span class="text-[11px] text-outline font-medium">Free</span>
                                        </div>

                                    </label>

                                    <label class="cursor-pointer">

                                        <input class="peer sr-only" name="binding" type="radio" value="stapled">
                                        <div class="p-2 text-center rounded-xl border border-outline-variant/40 bg-surface-container-low hover:bg-surface-container transition-all peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-1 peer-checked:ring-primary flex flex-col items-center justify-center">
                                            <span class="text-xs font-bold text-on-surface block">Stapled</span>
                                            <span class="text-[11px] text-outline font-medium">Free</span>
                                        </div>

                                    </label>

                                    <label class="cursor-pointer">

                                        <input class="peer sr-only" name="binding" type="radio" value="spiral">
                                        <div class="p-2 text-center rounded-xl border border-outline-variant/40 bg-surface-container-low hover:bg-surface-container transition-all peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-1 peer-checked:ring-primary flex flex-col items-center justify-center">
                                            <span class="text-xs font-bold text-on-surface block">Spiral</span>
                                            <span class="text-[11px] text-primary font-bold">+₱35.00</span>
                                        </div>

                                    </label>

                                </div>

                            </div>

                            <!-- Fulfillment Method -->
                            <div>

                                <label class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider mb-1 block">Fulfillment Method</label>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-sm">

                                    <label class="cursor-pointer">

                                        <input checked class="peer sr-only" name="fulfillment_method" type="radio" value="pickup">
                                        <div class="p-2.5 rounded-xl border border-outline-variant/40 bg-surface-container-low hover:bg-surface-container transition-all peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-1 peer-checked:ring-primary flex items-center gap-2">

                                            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                                <span class="material-symbols-outlined text-[18px]">storefront</span>
                                            </div>

                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-on-surface truncate">Store Pick-up</p>
                                                <p class="text-[11px] text-outline truncate">Free at shop branch</p>
                                            </div>

                                        </div>

                                    </label>

                                    <label class="cursor-pointer">

                                        <input class="peer sr-only" name="fulfillment_method" type="radio" value="delivery">
                                        <div class="p-2.5 rounded-xl border border-outline-variant/40 bg-surface-container-low hover:bg-surface-container transition-all peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-1 peer-checked:ring-primary flex items-center gap-2">

                                            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                                <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                                            </div>

                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-on-surface truncate">Doorstep Delivery</p>
                                                <p class="text-[11px] text-outline truncate">Polomolok area only</p>
                                            </div>

                                        </div>

                                    </label>

                                </div>

                            </div>

                            <!-- Special Instructions -->
                            <div>

                                <label class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider mb-1 block">Special Instructions (Optional)</label>
                                <textarea name="notes" rows="2" placeholder="e.g. Back-to-back, page range to print, specific paper color..." class="w-full p-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs focus:ring-2 focus:ring-primary focus:border-primary text-on-surface"></textarea>

                            </div>

                            <!-- Pricing Breakdown & Down Payment -->
                            <div class="pt-sm border-t border-outline-variant/20 space-y-sm">

                                <div class="bg-surface-container-low p-md rounded-xl space-y-xs border border-outline-variant/20">

                                    <div class="flex justify-between items-center text-xs text-on-surface-variant">
                                        <span>Total Printing Price:</span>
                                        <span class="text-sm font-bold text-on-surface" id="printing-total-price">₱0.00</span>
                                    </div>

                                    <div class="flex justify-between items-center text-sm font-bold border-t border-outline-variant/20 pt-xs">
                                        <span class="text-on-surface flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[17px] text-[#007DFE]">account_balance_wallet</span>
                                            50% Down Payment:
                                        </span>
                                        <span class="text-title-md font-bold text-[#007DFE]" id="printing-down-payment">₱0.00</span>
                                    </div>

                                </div>

                                <!-- Trust & Security Subtext -->
                                <div class="flex items-center gap-2 px-1 text-[11px] text-on-surface-variant">
                                    <span class="material-symbols-outlined text-[16px] text-[#007DFE]">verified_user</span>
                                    <span>Pay 50% now via GCash / PayMongo. Remaining balance paid on pickup or delivery.</span>
                                </div>

                                <!-- Primary Submit Button -->
                                <button type="submit" class="w-full py-3 bg-[#007DFE] hover:bg-[#006bd6] text-white rounded-xl font-button text-sm shadow-md hover:shadow-lg active:scale-95 transition-all flex items-center justify-center gap-2">

                                    <span class="material-symbols-outlined text-[18px]">payments</span>
                                    <span>Pay 50% Down Payment via GCash</span>

                                </button>

                            </div>

                        </div>

                    </div>

                </div>

            </form>

        </section>

    <?php endif; ?>

    <!-- Product Catalog -->
    <section class="mb-xxl">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-lg gap-md">

            <div>

                <h2 class="text-headline-md font-headline-md text-on-surface">Product Catalog</h2>
                <p class="text-on-surface-variant font-body-md">Essential school and office supplies</p>

            </div>

            <form method="GET" action="<?= base_url('shop/' . $shop['slug']) ?>" class="flex gap-sm" role="search">

                <div class="relative">

                    <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                    <input name="q" value="<?= esc($searchQuery ?? '') ?>" class="pl-11 pr-lg py-sm bg-surface-container rounded-lg border-none focus:ring-2 focus:ring-primary text-body-md" placeholder="Search supplies..." type="text">

                </div>

                <button type="submit" aria-label="Filter products" class="p-sm bg-surface-container rounded-lg text-on-surface-variant hover:text-primary transition-colors">

                    <span class="material-symbols-outlined">filter_list</span>

                </button>

            </form>

        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-gutter">

            <?php if (!empty($products)): ?>

                <?php foreach ($products as $p): ?>

                    <div class="group bg-surface-container-lowest rounded-xl overflow-hidden border border-outline-variant/30 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">

                        <a href="<?= base_url('product/' . $p['id']) ?>" class="block h-64 overflow-hidden bg-surface-container relative">

                            <img class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" src="<?= esc(product_image_url($p['image_url'] ?? null)) ?>" alt="<?= esc($p['name']) ?>">

                            <?php if (!empty($p['is_bestseller'])): ?>

                                <span class="absolute top-md right-md bg-primary-container text-on-primary-container px-sm py-xs rounded-full text-label-sm font-label-sm shadow-sm">Bestseller</span>

                            <?php endif; ?>

                        </a>

                        <div class="p-md">

                            <h3 class="text-title-lg font-title-lg mb-xs group-hover:text-primary transition-colors"><a href="<?= base_url('product/' . $p['id']) ?>"><?= esc($p['name']) ?></a></h3>
                            <p class="text-on-surface-variant text-label-sm font-label-sm mb-md line-clamp-1"><?= esc($p['description'] ?? '') ?></p>

                            <div class="flex justify-between items-center">

                                <span class="text-headline-md font-headline-md text-on-surface">₱<?= number_format($p['price'], 2) ?></span>

                                <form action="<?= base_url('cart/add') ?>" method="POST">

                                    <?= csrf_field() ?>

                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">

                                    <button type="submit" class="w-10 h-10 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center hover:bg-primary hover:text-on-primary transition-colors">

                                        <span class="material-symbols-outlined">add_shopping_cart</span>

                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <?php if (($searchQuery ?? '') !== ''): ?>

                    <p class="text-on-surface-variant text-sm col-span-full">No products matched your search for &quot;<?= esc($searchQuery) ?>&quot;.</p>

                <?php else: ?>

                    <p class="text-on-surface-variant text-sm col-span-full">No products in shop catalog.</p>

                <?php endif; ?>

            <?php endif; ?>

        </div>

        <?php
            $totalPages = $totalPages ?? 1;
            $currentPage = $currentPage ?? 1;
            $searchQuery = $searchQuery ?? '';
            $pageUrl = function (int $p) use ($shop, $searchQuery) {
                $params = ['page' => $p];
                if ($searchQuery !== '') {
                    $params['q'] = $searchQuery;
                }
                return base_url('shop/' . $shop['slug'] . '?' . http_build_query($params));
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

            <div class="mt-xl flex justify-center">

                <nav class="flex items-center gap-sm" aria-label="Pagination">

                    <?php if ($currentPage > 1): ?>

                        <a href="<?= $pageUrl($currentPage - 1) ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors" aria-label="Previous page">

                            <span class="material-symbols-outlined">chevron_left</span>

                        </a>

                    <?php else: ?>

                        <span class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant text-outline-variant/50 cursor-not-allowed" aria-disabled="true">

                            <span class="material-symbols-outlined">chevron_left</span>

                        </span>

                    <?php endif; ?>

                    <?php foreach ($windowPages as $pg): ?>

                        <?php if ($pg === '...'): ?>

                            <span class="px-xs text-outline-variant" aria-hidden="true">...</span>

                        <?php elseif ((int) $pg === $currentPage): ?>

                            <a href="<?= $pageUrl((int) $pg) ?>" aria-current="page" class="w-10 h-10 flex items-center justify-center rounded-lg bg-primary text-on-primary font-button text-button"><?= (int) $pg ?></a>

                        <?php else: ?>

                            <a href="<?= $pageUrl((int) $pg) ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors font-button text-button"><?= (int) $pg ?></a>

                        <?php endif; ?>

                    <?php endforeach; ?>

                    <?php if ($currentPage < $totalPages): ?>

                        <a href="<?= $pageUrl($currentPage + 1) ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors" aria-label="Next page">

                            <span class="material-symbols-outlined">chevron_right</span>

                        </a>

                    <?php else: ?>

                        <span class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant text-outline-variant/50 cursor-not-allowed" aria-disabled="true">

                            <span class="material-symbols-outlined">chevron_right</span>

                        </span>

                    <?php endif; ?>

                </nav>

            </div>

        <?php endif; ?>

    </section>

    <!-- Shop Reviews Section -->
    <section id="shop-reviews" class="mt-xxl">
        <div class="flex items-center justify-between mb-lg">
            <h2 class="text-headline-md font-headline-md">Shop Reviews</h2>
            <span class="text-body-md text-on-surface-variant">
                <?= (int) ($shopReviewCount ?? 0) ?> review<?= ((int) ($shopReviewCount ?? 0)) === 1 ? '' : 's' ?>
            </span>
        </div>

        <?php if ($session = session()->get('isLoggedIn')): ?>
            <div class="bg-surface-container-lowest p-lg rounded-2xl border border-outline-variant/20 mb-xl">
                <h3 class="text-title-lg font-bold mb-md">
                    <?= $userShopReview ? 'Update Your Review' : 'Write a Shop Review' ?>
                </h3>

                <form action="<?= base_url('reviews/shop/save') ?>" method="POST" class="space-y-lg" id="shop-review-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="shop_id" value="<?= esc($shop['id']) ?>">

                    <div>
                        <label class="block text-label-sm font-semibold text-on-surface mb-sm">Your Rating</label>
                        <div class="star-rating flex items-center gap-sm" role="radiogroup" aria-label="Select rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="rating" value="<?= $i ?>" id="shop-star-<?= $i ?>" class="sr-only" <?= ($userShopReview && (int) $userShopReview['rating'] === $i) ? 'checked' : '' ?> required>
                                <label for="shop-star-<?= $i ?>" class="cursor-pointer text-3xl text-outline-variant hover:text-primary transition-colors <?= ($userShopReview && (int) $userShopReview['rating'] >= $i) ? 'text-primary fill-icon' : '' ?>" data-star="<?= $i ?>">
                                    <span class="material-symbols-outlined">star</span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div>
                        <label class="block text-label-sm font-semibold text-on-surface mb-sm" for="shop-review-comment">Your Review (optional)</label>
                        <textarea name="comment" id="shop-review-comment" rows="4" class="w-full p-md bg-surface-container-lowest border border-outline-variant rounded-xl text-body-md text-on-surface placeholder-on-surface-variant/60 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" placeholder="Share your experience with this shop... (optional)" maxlength="2000"><?= esc($userShopReview['comment'] ?? '') ?></textarea>
                        <p class="text-xs text-on-surface-variant/60 mt-xs text-right">Max 2000 characters</p>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-primary text-on-primary px-lg py-md rounded-xl font-semibold hover:bg-primary-container transition-colors">
                            <?= $userShopReview ? 'Update Review' : 'Submit Review' ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="bg-surface-container-lowest p-lg rounded-2xl border border-outline-variant/20 mb-xl text-center">
                <p class="text-body-md text-on-surface-variant mb-md">Please <a href="<?= base_url('login') ?>" class="text-primary font-semibold hover:underline">sign in</a> to write a shop review.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($shopReviews)): ?>
            <div class="space-y-lg" id="shop-reviews-list">
                <?php foreach ($shopReviews as $review): ?>
                    <div class="bg-surface-container-lowest p-lg rounded-2xl border border-outline-variant/20">
                        <div class="flex items-start gap-md mb-md">
                            <div class="w-10 h-10 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center font-bold text-body-md flex-shrink-0">
                                <?= strtoupper(substr(($review['first_name'] ?? '') . ($review['last_name'] ?? ''), 0, 1)) ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-on-surface truncate"><?= esc(($review['first_name'] ?? '') . ' ' . ($review['last_name'] ?? '')) ?></p>
                                <div class="flex items-center gap-sm mt-xs">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="material-symbols-outlined text-[18px] <?= $i <= (int) $review['rating'] ? 'fill-icon text-primary' : 'text-outline-variant' ?>">star</span>
                                    <?php endfor; ?>
                                    <span class="text-label-sm text-on-surface-variant ml-sm"><?= date('M d, Y', strtotime($review['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($review['comment'])): ?>
                            <p class="text-body-md text-on-surface leading-relaxed"><?= esc($review['comment']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-surface-container-lowest p-xl rounded-2xl border border-outline-variant/20 text-center">
                <span class="material-symbols-outlined text-4xl text-outline mb-md">rate_review</span>
                <p class="text-body-md text-on-surface-variant">No reviews yet. Be the first to review this shop!</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- PDF Upload Status Modal -->
    <div id="pdf-upload-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden p-4">
        <div class="glass-card bg-surface rounded-2xl p-xl max-w-md w-full shadow-2xl border border-outline-variant/30 space-y-lg text-center">
            
            <div id="pdf-modal-icon-bg" class="w-16 h-16 rounded-full mx-auto flex items-center justify-center transition-all duration-300">
                <span id="pdf-modal-icon" class="material-symbols-outlined !text-4xl"></span>
            </div>

            <div class="space-y-xs">
                <h3 id="pdf-modal-title" class="text-title-lg font-bold text-on-surface"></h3>
                <p id="pdf-modal-message" class="text-body-md text-on-surface-variant max-w-xs mx-auto"></p>
            </div>

            <div id="pdf-modal-details" class="bg-surface-container-low p-md rounded-xl border border-outline-variant/20 text-left space-y-xs hidden">
                <div class="flex justify-between items-center text-xs text-on-surface-variant">
                    <span>File Name:</span>
                    <span id="pdf-modal-filename" class="font-semibold text-on-surface truncate max-w-[200px]"></span>
                </div>
                <div class="flex justify-between items-center text-xs text-on-surface-variant">
                    <span>Total Pages:</span>
                    <span id="pdf-modal-pagecount" class="font-bold text-primary"></span>
                </div>
            </div>

            <div class="pt-sm">
                <button type="button" id="pdf-modal-close-btn" class="w-full py-md px-xl rounded-xl font-button text-button transition-all shadow-md active:scale-95 flex items-center justify-center gap-xs">
                    <span>Continue</span>
                </button>
            </div>

        </div>
    </div>

</main>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const selectPdfBtn = document.getElementById('selectPdfBtn');
const badge = document.getElementById('upload-badge');
const pageCount = document.getElementById('page-count');
const uploadHint = document.getElementById('upload-hint');
const hiddenPageCount = document.getElementById('pdf-page-count');

function showPdfModal(status, title, message, filename, pages) {
    const modal = document.getElementById('pdf-upload-modal');
    const iconBg = document.getElementById('pdf-modal-icon-bg');
    const icon = document.getElementById('pdf-modal-icon');
    const titleEl = document.getElementById('pdf-modal-title');
    const msgEl = document.getElementById('pdf-modal-message');
    const details = document.getElementById('pdf-modal-details');
    const filenameEl = document.getElementById('pdf-modal-filename');
    const pagecountEl = document.getElementById('pdf-modal-pagecount');
    const closeBtn = document.getElementById('pdf-modal-close-btn');

    if (!modal) return;

    if (status === 'success') {
        iconBg.className = 'w-16 h-16 rounded-full mx-auto flex items-center justify-center transition-all duration-300 bg-green-100 text-green-600';
        icon.textContent = 'check_circle';
        titleEl.textContent = title || 'PDF Uploaded Successfully';
        titleEl.className = 'text-title-lg font-bold text-green-700';
        msgEl.textContent = message || 'Your document has been verified and processed for printing.';

        if (filename && pages !== undefined && pages !== null) {
            filenameEl.textContent = filename;
            pagecountEl.textContent = pages + ' Page' + (parseInt(pages, 10) > 1 ? 's' : '');
            details.classList.remove('hidden');
        } else {
            details.classList.add('hidden');
        }

        closeBtn.className = 'w-full py-md px-xl rounded-xl font-button text-button bg-primary text-on-primary hover:bg-primary/90 transition-all shadow-md active:scale-95 flex items-center justify-center gap-xs';
        closeBtn.querySelector('span').textContent = 'Continue';

    } else {
        iconBg.className = 'w-16 h-16 rounded-full mx-auto flex items-center justify-center transition-all duration-300 bg-red-100 text-red-600';
        icon.textContent = 'cancel';
        titleEl.textContent = title || 'PDF Upload Failed';
        titleEl.className = 'text-title-lg font-bold text-red-700';
        msgEl.textContent = message || 'An error occurred while uploading your file.';
        details.classList.add('hidden');

        closeBtn.className = 'w-full py-md px-xl rounded-xl font-button text-button bg-red-600 hover:bg-red-700 text-white transition-all shadow-md active:scale-95 flex items-center justify-center gap-xs';
        closeBtn.querySelector('span').textContent = 'Try Again';
    }

    modal.classList.remove('hidden');
}

const modalCloseBtn = document.getElementById('pdf-modal-close-btn');
const pdfModal = document.getElementById('pdf-upload-modal');
if (modalCloseBtn && pdfModal) {
    modalCloseBtn.addEventListener('click', function() {
        pdfModal.classList.add('hidden');
    });
    pdfModal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });
}

function setUploadState(state, pageCountValue) {
    if (!badge || !pageCount || !hiddenPageCount) return;
    const badgeIcon = badge.querySelector('.material-symbols-outlined');
    if (state === 'ready') {
        badge.className = 'px-sm py-0.5 rounded-full text-label-sm font-medium bg-green-100 text-green-700 flex items-center gap-xs';
        if (badgeIcon) badgeIcon.textContent = 'check_circle';
        badge.lastChild.textContent = ' Ready Successfully';
        pageCount.className = 'text-label-sm font-medium text-primary';
        pageCount.textContent = 'Total Pages: ' + pageCountValue;
        hiddenPageCount.value = pageCountValue;
        if (uploadHint) uploadHint.classList.add('hidden');
    } else if (state === 'error') {
        badge.className = 'px-sm py-0.5 rounded-full text-label-sm font-medium bg-red-100 text-red-700 flex items-center gap-xs';
        if (badgeIcon) badgeIcon.textContent = 'error';
        badge.lastChild.textContent = ' ' + pageCountValue;
        pageCount.className = 'text-label-sm font-medium text-error';
        pageCount.textContent = 'Unable to verify PDF';
        hiddenPageCount.value = 0;
        if (uploadHint) uploadHint.classList.remove('hidden');
        uploadHint.textContent = pageCountValue + ' Please select a valid PDF file.';
    } else {
        badge.className = 'px-sm py-0.5 rounded-full text-label-sm font-medium bg-surface-container-high text-on-surface-variant flex items-center gap-xs';
        if (badgeIcon) badgeIcon.textContent = 'info';
        badge.lastChild.textContent = ' No PDF selected';
        pageCount.className = 'text-label-sm font-medium text-on-surface-variant';
        pageCount.textContent = 'No file selected';
        hiddenPageCount.value = 0;
        if (uploadHint) uploadHint.classList.add('hidden');
    }
}

function isValidPdfFile(file) {
    return file && /\.pdf$/i.test(file.name);
}

if (dropzone && fileInput) {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => dropzone.classList.add('drag-active'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => dropzone.classList.remove('drag-active'), false);
    });

    dropzone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }

    dropzone.addEventListener('click', function(e) {
        if (e.target !== selectPdfBtn) {
            fileInput.click();
        }
    });

    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });

    if (selectPdfBtn) {
        selectPdfBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            fileInput.click();
        });
    }

    function handleFiles(files) {
        if (!files || files.length === 0) {
            setUploadState('empty');
            return;
        }

        const file = files[0];

        if (!isValidPdfFile(file)) {
            const errMsg = 'Invalid file type. Only PDF documents are accepted.';
            setUploadState('error', errMsg);
            showPdfModal('error', 'PDF Upload Failed', errMsg);
            return;
        }

        try {
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
        } catch (err) {}

        if (badge) {
            badge.className = 'px-sm py-0.5 rounded-full text-label-sm font-medium bg-amber-100 text-amber-700 flex items-center gap-xs';
            if (badge.querySelector('.material-symbols-outlined')) badge.querySelector('.material-symbols-outlined').textContent = 'sync';
            badge.lastChild.textContent = ' Processing PDF...';
            pageCount.className = 'text-label-sm font-medium text-on-surface-variant';
            pageCount.textContent = 'Counting pages...';
        }

        const formData = new FormData();
        formData.append('document', file, file.name);

        fetch('<?= base_url('printing/count-pages') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json().then(data => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
            if (ok && data && data.success) {
                setUploadState('ready', data.page_count);
                updatePrintingPrice();
                showPdfModal('success', 'PDF Uploaded Successfully', 'Your document was verified and page count was calculated.', file.name, data.page_count);
            } else {
                const msg = (data && data.error) ? data.error : 'Unable to determine page count.';
                setUploadState('error', msg);
                updatePrintingPrice();
                showPdfModal('error', 'PDF Upload Failed', msg);
            }
        })
        .catch(() => {
            const msg = 'Unable to determine page count or process PDF.';
            setUploadState('error', msg);
            updatePrintingPrice();
            showPdfModal('error', 'PDF Upload Failed', msg);
        });
    }

    function updatePrintingPrice() {
        const pages = parseInt(hiddenPageCount ? hiddenPageCount.value : 0, 10) || 0;
        const colorRadio = document.querySelector('input[name="color_mode"]:checked');
        const isColored = colorRadio ? (colorRadio.value === 'colored') : true;
        const paperSizeEl = document.querySelector('select[name="paper_size"]');
        const paperSize = paperSizeEl ? paperSizeEl.value : 'letter';
        const copiesEl = document.querySelector('select[name="copies"]');
        const copies = parseInt(copiesEl ? copiesEl.value : 1, 10) || 1;
        const bindingRadio = document.querySelector('input[name="binding"]:checked');
        const binding = bindingRadio ? bindingRadio.value : 'none';

        if (pages <= 0) {
            const elTotal = document.getElementById('printing-total-price');
            const elDown = document.getElementById('printing-down-payment');
            if (elTotal) elTotal.textContent = '₱0.00';
            if (elDown) elDown.textContent = '₱0.00';
            return;
        }

        let basePerPage = isColored ? 5.00 : 2.00;
        let sizeMultiplier = 1.0;
        if (paperSize === 'legal' || paperSize === 'a3') sizeMultiplier = 1.5;
        else if (['a2', 'a1', 'a0'].includes(paperSize)) sizeMultiplier = 2.5;

        let bindingCost = 0;
        if (binding === 'stapled') bindingCost = 10.00;
        else if (binding === 'spiral') bindingCost = 35.00;

        let total = ((pages * basePerPage * sizeMultiplier) + bindingCost) * copies;
        let downPayment = total * 0.50;

        const elTotal = document.getElementById('printing-total-price');
        const elDown = document.getElementById('printing-down-payment');
        if (elTotal) elTotal.textContent = '₱' + total.toFixed(2);
        if (elDown) elDown.textContent = '₱' + downPayment.toFixed(2);
    }

    document.querySelectorAll('input[name="color_mode"], select[name="paper_size"], select[name="copies"], input[name="binding"]').forEach(el => {
        el.addEventListener('change', updatePrintingPrice);
    });
}
</script>

<?= $this->endSection() ?>
