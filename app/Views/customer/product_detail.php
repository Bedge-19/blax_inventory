<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<main class="max-w-container-max mx-auto px-4 md:px-8 lg:px-10 py-8 md:py-xl flex-grow w-full">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-xxl">

        <!-- Left: Visual Assets -->
        <div class="lg:col-span-7 space-y-lg">

            <?php
                $primaryImgUrl = null;
                if (!empty($productImages)) {
                    foreach ($productImages as $img) {
                        if (!empty($img['is_primary'])) {
                            $primaryImgUrl = $img['image_url'];
                            break;
                        }
                    }
                    if (!$primaryImgUrl && isset($productImages[0])) {
                        $primaryImgUrl = $productImages[0]['image_url'];
                    }
                }
                $displayMainImg = $primaryImgUrl ?: ($product['image_url'] ?? null);
            ?>

            <div class="relative rounded-2xl overflow-hidden bg-white shadow-md aspect-square group">

                <img id="main-product-image" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" src="<?= esc(product_image_url($displayMainImg)) ?>" alt="<?= esc($product['name']) ?>">

                <?php if (!empty($product['is_bestseller'])): ?>

                    <div class="absolute top-md left-md bg-primary-container/90 backdrop-blur text-on-primary-container px-md py-xs rounded-full font-label-sm text-label-sm">Bestseller</div>

                <?php endif; ?>

            </div>

            <?php if (!empty($productImages)): ?>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-md">

                    <?php foreach ($productImages as $idx => $img): ?>

                        <?php 
                            $thumbUrl = product_image_url($img['image_url'] ?? null); 
                            $isMainThumb = (!empty($img['is_primary']) || ($idx === 0 && !$primaryImgUrl));
                        ?>

                        <button type="button" class="gallery-thumb rounded-xl overflow-hidden h-24 cursor-pointer transition-colors p-0 <?= $isMainThumb ? 'border-2 border-primary' : 'border border-outline-variant/30 hover:border-primary' ?>" data-image="<?= esc($thumbUrl) ?>" aria-label="View product image">

                            <img class="w-full h-full object-cover" src="<?= esc($thumbUrl) ?>" alt="<?= esc($img['alt_text'] ?? $product['name']) ?>">

                        </button>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

        <!-- Right: Product Controls & Info -->
        <div class="lg:col-span-5 flex flex-col">

            <nav class="flex items-center gap-xs text-label-sm font-label-sm text-on-surface-variant mb-md flex-wrap">

                <a class="hover:text-primary" href="<?= base_url('/') ?>">Home</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>

                <?php if (!empty($product['category_name'])): ?>

                    <a class="hover:text-primary" href="<?= base_url('categories') ?>"><?= esc($product['category_name']) ?></a>
                    <span class="material-symbols-outlined text-[14px]">chevron_right</span>

                <?php endif; ?>

                <span class="text-on-surface"><?= esc($product['name']) ?></span>

            </nav>

            <h1 class="text-headline-lg font-headline-lg text-on-surface mb-xs"><?= esc($product['name']) ?></h1>

            <div class="flex items-center gap-md mb-lg flex-wrap">

                <div class="flex items-center text-primary">

                    <?php
                        $rating    = (float) ($product['rating_average'] ?? 0);
                        $fullStars = (int) floor($rating);
                        $hasHalf   = ($rating - $fullStars) >= 0.5;
                    ?>

                    <?php for ($i = 1; $i <= 5; $i++): ?>

                        <?php if ($i <= $fullStars): ?>

                            <span class="material-symbols-outlined fill-icon text-[18px]">star</span>

                        <?php elseif ($i === $fullStars + 1 && $hasHalf): ?>

                            <span class="material-symbols-outlined text-[18px]">star_half</span>

                        <?php else: ?>

                            <span class="material-symbols-outlined text-[18px] text-outline-variant">star</span>

                        <?php endif; ?>

                    <?php endfor; ?>

                    <span class="ml-xs font-bold text-label-sm"><?= number_format($rating, 1) ?></span>

                </div>

                <span class="text-on-surface-variant text-label-sm">(<?= number_format($product['rating_count'] ?? 0) ?> reviews)</span>

                <div class="h-4 w-[1px] bg-outline-variant"></div>

                <?php if (!empty($product['sku'])): ?>

                    <span class="text-secondary text-label-sm">SKU: <?= esc($product['sku']) ?></span>

                <?php endif; ?>

                <a href="<?= base_url('shop/' . ($product['shop_slug'] ?? $product['shop_id'] ?? '')) ?>" class="text-primary font-bold text-label-sm hover:underline">Shop: <?= esc($product['shop_name'] ?? 'RHK Merchant') ?></a>

            </div>

            <div class="bg-surface-container-low rounded-2xl p-lg mb-lg border border-outline-variant/20">

                <div class="flex items-baseline gap-sm mb-base flex-wrap">

                    <span class="text-display font-display text-primary">₱<?= number_format($product['price'], 2) ?></span>

                    <?php if (!empty($product['compare_at_price']) && $product['compare_at_price'] > $product['price']): ?>

                        <span class="text-on-surface-variant line-through text-body-md">₱<?= number_format($product['compare_at_price'], 2) ?></span>
                        <span class="text-error font-bold text-label-sm">Save <?= round((1 - $product['price'] / $product['compare_at_price']) * 100) ?>%</span>

                    <?php endif; ?>

                </div>

                <p class="text-on-surface-variant font-body-md leading-relaxed">
                    <?= esc($product['description'] ?? 'No product description available.') ?>
                </p>

            </div>

            <!-- Action Controls -->
            <div class="mt-auto space-y-md">

                <?php
                // Note: Currently supports single-axis variant selection (e.g. Color OR Size). For multi-axis products (Color AND Size combinations), a matrix option model with combined SKU/stock will be needed in a future update.
                $groupedVariants = [];
                if (!empty($variants)) {
                    foreach ($variants as $v) {
                        $groupedVariants[$v['name']][] = $v;
                    }
                }
                $hasVariants = !empty($groupedVariants);
                $firstGroupName = $hasVariants ? array_key_first($groupedVariants) : 'option';
                $outOfStock = (int) ($product['stock_quantity'] ?? 0) <= 0;
                $cartDisabled = $outOfStock || $hasVariants;
                ?>

                <?php if ($hasVariants): ?>
                    <div class="bg-surface-container-low/70 rounded-2xl p-md mb-md border border-outline-variant/30 space-y-md" id="variants-section">
                        <?php foreach ($groupedVariants as $groupName => $options): ?>
                            <div>
                                <div class="flex justify-between items-center mb-1.5">
                                    <span class="text-label-sm font-bold text-on-surface uppercase tracking-wider">Select <?= esc($groupName) ?>:</span>
                                    <span id="selected-val-<?= esc(url_title($groupName, '-', true)) ?>" class="text-xs font-bold text-primary"></span>
                                </div>
                                <div class="flex flex-wrap gap-xs">
                                    <?php foreach ($options as $opt): ?>
                                        <?php $isOutOfStock = (int) $opt['stock_quantity'] <= 0; ?>
                                        <button type="button"
                                                class="variant-pill px-md py-1.5 rounded-xl border text-label-sm font-semibold transition-all flex items-center gap-xs <?= $isOutOfStock ? 'opacity-40 border-dashed border-outline-variant cursor-not-allowed line-through bg-surface-container/50 text-on-surface-variant' : 'border-outline-variant/50 hover:border-primary bg-surface-container hover:bg-surface-variant text-on-surface' ?>"
                                                <?= $isOutOfStock ? 'disabled title="Out of Stock"' : '' ?>
                                                data-id="<?= (int) $opt['id'] ?>"
                                                data-group="<?= esc($groupName) ?>"
                                                data-group-slug="<?= esc(url_title($groupName, '-', true)) ?>"
                                                data-value="<?= esc($opt['value']) ?>"
                                                data-stock="<?= (int) $opt['stock_quantity'] ?>"
                                                data-price="<?= $opt['price_override'] !== null ? (float) $opt['price_override'] : '' ?>">
                                            <span><?= esc($opt['value']) ?></span>
                                            <?php if ($opt['price_override'] !== null && (float) $opt['price_override'] > 0): ?>
                                                <span class="text-[11px] opacity-75 font-normal">₱<?= number_format((float) $opt['price_override'], 2) ?></span>
                                            <?php endif; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div id="variant-stock-status" class="text-xs font-semibold text-on-surface-variant flex items-center gap-xs pt-1 border-t border-outline-variant/20">
                            <span class="material-symbols-outlined text-[16px] text-primary">info</span>
                            <span id="variant-stock-text">Please select a <?= esc($firstGroupName) ?> above</span>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('cart/add') ?>" method="POST" id="product-purchase-form" class="space-y-md">

                    <?= csrf_field() ?>

                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <input type="hidden" name="variant_id" id="selected-variant-id" value="">
                    <input type="hidden" name="quantity" id="quantity-input" value="1" min="1" max="<?= esc($product['stock_quantity'] ?? 99) ?>">

                    <div class="flex items-center gap-md">

                        <div class="flex items-center bg-surface-container-high rounded-full px-md py-xs border border-outline-variant/30">

                            <button type="button" id="qty-minus" class="p-xs px-sm hover:bg-surface-container text-on-surface-variant" <?= $outOfStock ? 'disabled' : '' ?> aria-label="Decrease quantity">

                                <span class="material-symbols-outlined text-[20px]">remove</span>

                            </button>

                            <span id="quantity-display" class="w-12 text-center font-bold">1</span>

                            <button type="button" id="qty-plus" class="p-xs px-sm hover:bg-surface-container text-on-surface-variant" <?= $outOfStock ? 'disabled' : '' ?> aria-label="Increase quantity">

                                <span class="material-symbols-outlined text-[20px]">add</span>

                            </button>

                        </div>

                        <button type="submit" id="add-to-cart-btn" formaction="<?= base_url('cart/add') ?>" formmethod="POST" class="flex-1 bg-secondary-container text-on-secondary-container py-md rounded-full font-button text-button hover:brightness-95 transition-all flex items-center justify-center gap-sm disabled:opacity-50 disabled:cursor-not-allowed" <?= $cartDisabled ? 'disabled' : '' ?>>

                            <span class="material-symbols-outlined text-[20px]">shopping_cart</span>
                            <span id="cart-btn-text"><?= $outOfStock ? 'Out of Stock' : ($hasVariants ? 'Select an option to continue' : 'Add to Cart') ?></span>

                        </button>

                    </div>

                    <button type="button" id="buy-now-btn" class="w-full bg-primary text-on-primary py-lg rounded-full font-headline-md text-headline-md hover:shadow-lg transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed" <?= $cartDisabled ? 'disabled' : '' ?>>

                        Buy Now

                    </button>

                </form>

                <div class="flex justify-center gap-lg py-md border-t border-outline-variant/20 mt-md flex-wrap">

                    <?php if (!empty($product['warranty_period'])): ?>

                        <div class="flex items-center gap-xs text-label-sm text-on-surface-variant">

                            <span class="material-symbols-outlined text-[16px] text-primary">verified_user</span>
                            <?= esc($product['warranty_period']) ?>

                        </div>

                    <?php endif; ?>

                    <?php if (!empty($product['return_policy_days'])): ?>

                        <div class="flex items-center gap-xs text-label-sm text-on-surface-variant">

                            <span class="material-symbols-outlined text-[16px] text-primary">keyboard_return</span>
                            <?= (int) $product['return_policy_days'] ?>-Day Returns

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

    <!-- Details Section (Bento Style) -->
    <section class="mt-xxl grid grid-cols-1 md:grid-cols-3 gap-lg">

        <div class="bg-surface-container-lowest p-xl rounded-2xl border border-outline-variant/20 shadow-sm col-span-1 md:col-span-2">

            <h2 class="text-headline-md font-headline-md mb-lg">About this product</h2>

            <p class="text-body-md text-on-surface-variant leading-relaxed">
                <?= esc($product['description'] ?? 'No product description available.') ?>
            </p>

        </div>

        <div class="bg-surface-container-highest p-xl rounded-2xl border border-outline-variant/30 flex flex-col justify-center items-center text-center">

            <div class="mb-md p-md rounded-full bg-white shadow-md">

                <span class="material-symbols-outlined text-[48px] text-primary">storefront</span>

            </div>

            <h3 class="text-title-lg font-title-lg mb-sm"><?= esc($product['shop_name'] ?? 'RHK Merchant') ?></h3>

            <p class="text-body-md text-on-surface-variant mb-lg">View this shop's full catalog and other products.</p>

            <a class="text-primary font-bold hover:underline flex items-center gap-xs" href="<?= base_url('shop/' . ($product['shop_slug'] ?? $product['shop_id'] ?? '')) ?>">

                Visit Shop
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>

            </a>

        </div>

    </section>

    <!-- Reviews Section -->
    <section id="reviews" class="mt-xxl">
        <div class="flex items-center justify-between mb-lg">
            <h2 class="text-headline-md font-headline-md">Reviews</h2>
            <span class="text-body-md text-on-surface-variant">
                <?= (int) ($reviewCount ?? 0) ?> review<?= ((int) ($reviewCount ?? 0)) === 1 ? '' : 's' ?>
            </span>
        </div>

        <?php if ($session = session()->get('isLoggedIn')): ?>
            <div class="bg-surface-container-lowest p-lg rounded-2xl border border-outline-variant/20 mb-xl">
                <h3 class="text-title-lg font-bold mb-md">
                    <?= $userReview ? 'Update Your Review' : 'Write a Review' ?>
                </h3>

                <form action="<?= base_url('reviews/product/save') ?>" method="POST" class="space-y-lg" id="product-review-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= esc($product['id']) ?>">
                    <input type="hidden" name="shop_id" value="<?= esc($product['shop_id'] ?? '') ?>">

                    <div>
                        <label class="block text-label-sm font-semibold text-on-surface mb-sm">Your Rating</label>
                        <div class="star-rating flex items-center gap-sm" role="radiogroup" aria-label="Select rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="rating" value="<?= $i ?>" id="star-<?= $i ?>" class="sr-only" <?= ($userReview && (int) $userReview['rating'] === $i) ? 'checked' : '' ?> required>
                                <label for="star-<?= $i ?>" class="cursor-pointer text-3xl text-outline-variant hover:text-primary transition-colors <?= ($userReview && (int) $userReview['rating'] >= $i) ? 'text-primary fill-icon' : '' ?>" data-star="<?= $i ?>">
                                    <span class="material-symbols-outlined">star</span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div>
                        <label class="block text-label-sm font-semibold text-on-surface mb-sm" for="review-comment">Your Review (optional)</label>
                        <textarea name="comment" id="review-comment" rows="4" class="w-full p-md bg-surface-container-lowest border border-outline-variant rounded-xl text-body-md text-on-surface placeholder-on-surface-variant/60 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" placeholder="Share your experience with this product... (optional)" maxlength="2000"><?= esc($userReview['comment'] ?? '') ?></textarea>
                        <p class="text-xs text-on-surface-variant/60 mt-xs text-right">Max 2000 characters</p>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-primary text-on-primary px-lg py-md rounded-xl font-semibold hover:bg-primary-container transition-colors">
                            <?= $userReview ? 'Update Review' : 'Submit Review' ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="bg-surface-container-lowest p-lg rounded-2xl border border-outline-variant/20 mb-xl text-center">
                <p class="text-body-md text-on-surface-variant mb-md">Please <a href="<?= base_url('login') ?>" class="text-primary font-semibold hover:underline">sign in</a> to write a review.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($reviews)): ?>
            <div class="space-y-lg" id="reviews-list">
                <?php foreach ($reviews as $review): ?>
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
                <p class="text-body-md text-on-surface-variant">No reviews yet. Be the first to review this product!</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- Related Products Section (Shopee / Lazada Style) -->
    <?php if (!empty($relatedProducts)): ?>
        <section class="mt-2xl pt-xl border-t border-outline-variant/30">
            <div class="flex items-center justify-between mb-lg">
                <div class="flex items-center gap-sm">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">auto_awesome</span>
                    </div>
                    <div>
                        <h2 class="text-title-lg md:text-headline-sm font-bold text-on-surface">Related Products</h2>
                        <p class="text-label-sm md:text-body-sm text-on-surface-variant">Katulad na mga produkto sa parehong kategorya</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2 sm:gap-md">
                <?php foreach ($relatedProducts as $rel): ?>
                    <?php
                        $relImg = product_image_url($rel['image_url'] ?? null);
                        $relPrice = (float) $rel['price'];
                        $relCompare = !empty($rel['compare_at_price']) ? (float) $rel['compare_at_price'] : 0;
                        $hasDiscount = $relCompare > $relPrice;
                        $discountPct = $hasDiscount ? round((($relCompare - $relPrice) / $relCompare) * 100) : 0;
                        $relRating = round((float) ($rel['rating_average'] ?? 0), 1);
                        $relRatingCount = (int) ($rel['rating_count'] ?? 0);
                    ?>
                    <div class="group bg-surface-container-lowest border border-outline-variant/30 rounded-xl sm:rounded-2xl overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 flex flex-col justify-between">
                        <div>
                            <div class="relative aspect-square overflow-hidden bg-surface-container-low">
                                <a href="<?= base_url('product/' . $rel['id']) ?>" class="block w-full h-full">
                                    <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="<?= esc($relImg) ?>" alt="<?= esc($rel['name']) ?>" loading="lazy">
                                </a>

                                <?php if (!empty($rel['is_bestseller'])): ?>
                                    <span class="absolute top-1.5 left-1.5 bg-primary-container text-on-primary text-[9px] sm:text-[10px] font-bold px-1.5 py-0.5 rounded-full shadow-xs">Bestseller</span>
                                <?php elseif ($hasDiscount): ?>
                                    <span class="absolute top-1.5 left-1.5 bg-error text-white text-[9px] sm:text-[10px] font-bold px-1.5 py-0.5 rounded-full shadow-xs">-<?= $discountPct ?>%</span>
                                <?php endif; ?>
                            </div>

                            <div class="p-1.5 sm:p-md">
                                <a href="<?= base_url('product/' . $rel['id']) ?>" class="text-[11px] sm:text-body-sm font-semibold text-on-surface line-clamp-2 group-hover:text-primary transition-colors leading-snug min-h-[2rem] sm:min-h-[2.5rem]" title="<?= esc($rel['name']) ?>">
                                    <?= esc($rel['name']) ?>
                                </a>

                                <div class="hidden sm:flex items-center gap-1 mt-xs text-xs text-on-surface-variant">
                                    <span class="material-symbols-outlined text-[14px] text-amber-500" style="font-variation-settings: 'FILL' 1;">star</span>
                                    <span class="font-bold text-on-surface"><?= $relRating > 0 ? number_format($relRating, 1) : '5.0' ?></span>
                                    <span class="text-[11px] text-outline font-normal">(<?= $relRatingCount ?>)</span>
                                </div>

                                <div class="mt-1 sm:mt-sm">
                                    <div class="flex items-baseline gap-1 flex-wrap">
                                        <span class="text-xs sm:text-title-sm md:text-title-md font-bold text-primary">₱<?= number_format($relPrice, 2) ?></span>
                                        <?php if ($hasDiscount): ?>
                                            <span class="text-[9px] sm:text-[11px] text-outline line-through">₱<?= number_format($relCompare, 2) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($rel['shop_name'])): ?>
                                        <p class="text-[10px] sm:text-[11px] text-on-surface-variant/70 truncate mt-0.5"><?= esc($rel['shop_name']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="hidden sm:block px-sm pb-sm md:px-md md:pb-md pt-0">
                            <a href="<?= base_url('product/' . $rel['id']) ?>" class="w-full block text-center py-1.5 px-2 bg-surface-container hover:bg-primary hover:text-on-primary text-primary text-xs font-bold rounded-lg transition-colors">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

</main>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>
(function () {
    var mainImage = document.getElementById('main-product-image');
    var thumbs    = document.querySelectorAll('.gallery-thumb');
    var display   = document.getElementById('quantity-display');
    var input     = document.getElementById('quantity-input');
    var minus     = document.getElementById('qty-minus');
    var plus      = document.getElementById('qty-plus');

    thumbs.forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            if (mainImage) {
                mainImage.src = this.getAttribute('data-image');
            }
            thumbs.forEach(function (t) {
                t.classList.remove('border-2', 'border-primary');
                t.classList.add('border', 'border-outline-variant/30');
            });
            this.classList.add('border-2', 'border-primary');
            this.classList.remove('border-outline-variant/30');
        });
    });

    if (display && input) {
        var maxQty = parseInt(input.getAttribute('max'), 10) || 99;

        function update(n) {
            n = Math.max(1, Math.min(maxQty, n));
            input.value = n;
            display.textContent = n;
        }

        if (minus) {
            minus.addEventListener('click', function () {
                update(parseInt(display.textContent, 10) - 1);
            });
        }
        if (plus) {
            plus.addEventListener('click', function () {
                update(parseInt(display.textContent, 10) + 1);
            });
        }
    }

    // Variant selection logic
    var hasVariants = <?= !empty($groupedVariants) ? 'true' : 'false' ?>;
    var defaultGroupPrompt = 'Select an option to continue';
    var variantPills = document.querySelectorAll('.variant-pill');
    var variantInput = document.getElementById('selected-variant-id');
    var priceDisplay = document.querySelector('.text-display.font-display.text-primary');
    var defaultPriceText = priceDisplay ? priceDisplay.textContent : '';
    var cartBtn = document.getElementById('add-to-cart-btn') || document.querySelector('button[type="submit"].bg-secondary-container');
    var cartBtnText = document.getElementById('cart-btn-text');
    var buyBtn = document.getElementById('buy-now-btn') || document.querySelector('button[type="submit"].bg-primary');
    var variantStockText = document.getElementById('variant-stock-text');
    var purchaseForm = document.getElementById('product-purchase-form') || document.querySelector('form[action*="cart/add"]');

    variantPills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            if (this.hasAttribute('disabled')) return;

            var group = this.getAttribute('data-group');
            var groupSlug = this.getAttribute('data-group-slug');
            var val = this.getAttribute('data-value');
            var stock = parseInt(this.getAttribute('data-stock'), 10);
            var price = this.getAttribute('data-price');
            var id = this.getAttribute('data-id');

            // Toggle active styling among sibling pills in same group
            document.querySelectorAll('.variant-pill[data-group="' + group + '"]').forEach(function (sibling) {
                sibling.classList.remove('ring-2', 'ring-primary', 'border-primary', 'bg-primary/10', 'text-primary');
                sibling.classList.add('border-outline-variant/50', 'bg-surface-container', 'text-on-surface');
            });

            this.classList.remove('border-outline-variant/50', 'bg-surface-container', 'text-on-surface');
            this.classList.add('ring-2', 'ring-primary', 'border-primary', 'bg-primary/10', 'text-primary');

            // Update selected value text
            var labelSpan = document.getElementById('selected-val-' + groupSlug);
            if (labelSpan) {
                labelSpan.textContent = val;
            }

            // Set hidden variant_id
            if (variantInput) {
                variantInput.value = id;
            }

            // Update price if overridden
            if (priceDisplay) {
                if (price && parseFloat(price) > 0) {
                    priceDisplay.textContent = '₱' + parseFloat(price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                } else {
                    priceDisplay.textContent = defaultPriceText;
                }
            }

            // Update stock availability and quantity bounds
            if (stock > 0) {
                if (variantStockText) {
                    variantStockText.textContent = val + ' — ' + stock + ' in stock';
                    variantStockText.className = 'text-xs font-semibold text-primary';
                }
                if (cartBtn) {
                    cartBtn.removeAttribute('disabled');
                    if (cartBtnText) {
                        cartBtnText.textContent = 'Add to Cart';
                    } else {
                        cartBtn.innerHTML = '<span class="material-symbols-outlined text-[20px]">shopping_cart</span> Add to Cart';
                    }
                }
                if (buyBtn) {
                    buyBtn.removeAttribute('disabled');
                }
                if (input) {
                    input.setAttribute('max', stock);
                    maxQty = stock;
                    if (parseInt(input.value, 10) > stock) {
                        update(stock);
                    }
                }
            } else {
                if (variantStockText) {
                    variantStockText.textContent = val + ' is currently out of stock';
                    variantStockText.className = 'text-xs font-semibold text-error';
                }
                if (cartBtn) {
                    cartBtn.setAttribute('disabled', 'disabled');
                    if (cartBtnText) {
                        cartBtnText.textContent = 'Out of Stock';
                    } else {
                        cartBtn.innerHTML = '<span class="material-symbols-outlined text-[20px]">remove_shopping_cart</span> Out of Stock';
                    }
                }
                if (buyBtn) {
                    buyBtn.setAttribute('disabled', 'disabled');
                }
            }
        });
    });

    // Direct Buy Now Navigation Handler
    if (buyBtn) {
        buyBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (this.hasAttribute('disabled')) return;

            if (hasVariants && (!variantInput || !variantInput.value)) {
                if (variantStockText) {
                    variantStockText.textContent = 'Please select an option to continue.';
                    variantStockText.className = 'text-xs font-bold text-error';
                }
                var variantsSection = document.getElementById('variants-section');
                if (variantsSection) {
                    variantsSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    variantsSection.classList.add('ring-2', 'ring-error');
                    setTimeout(function () {
                        variantsSection.classList.remove('ring-2', 'ring-error');
                    }, 2500);
                }
                return;
            }

            var productId = '<?= (int) $product['id'] ?>';
            var qtyInput = document.getElementById('quantity-input');
            var qty = qtyInput ? (parseInt(qtyInput.value, 10) || 1) : 1;
            var variantId = variantInput ? variantInput.value : '';

            var targetUrl = '<?= base_url('buy-now') ?>?product_id=' + encodeURIComponent(productId) + '&quantity=' + encodeURIComponent(qty);
            if (variantId) {
                targetUrl += '&variant_id=' + encodeURIComponent(variantId);
            }
            window.location.href = targetUrl;
        });
    }

    // Client-side submit guard: require variant selection before submitting Add to Cart
    if (purchaseForm) {
        purchaseForm.addEventListener('submit', function (e) {
            if (hasVariants && (!variantInput || !variantInput.value)) {
                e.preventDefault();
                if (variantStockText) {
                    variantStockText.textContent = 'Please select an option to continue.';
                    variantStockText.className = 'text-xs font-bold text-error';
                }
                var variantsSection = document.getElementById('variants-section');
                if (variantsSection) {
                    variantsSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    variantsSection.classList.add('ring-2', 'ring-error');
                    setTimeout(function () {
                        variantsSection.classList.remove('ring-2', 'ring-error');
                    }, 2500);
                }
            }
        });
    }

    document.querySelectorAll('button').forEach(function (button) {
        button.addEventListener('mousedown', function () {
            this.classList.add('scale-95');
        });
        button.addEventListener('mouseup', function () {
            this.classList.remove('scale-95');
        });
        button.addEventListener('mouseleave', function () {
            this.classList.remove('scale-95');
        });
    });

    // Star rating interaction
    var starLabels = document.querySelectorAll('.star-rating label');
    var starInputs = document.querySelectorAll('.star-rating input[type="radio"]');
    starLabels.forEach(function (label) {
        label.addEventListener('mouseenter', function () {
            var star = parseInt(this.getAttribute('data-star'), 10);
            starLabels.forEach(function (lbl) {
                var s = parseInt(lbl.getAttribute('data-star'), 10);
                if (s <= star) {
                    lbl.classList.add('text-primary', 'fill-icon');
                    lbl.classList.remove('text-outline-variant');
                } else {
                    lbl.classList.remove('text-primary', 'fill-icon');
                    lbl.classList.add('text-outline-variant');
                }
            });
        });
        label.addEventListener('mouseleave', function () {
            var checked = document.querySelector('.star-rating input[type="radio"]:checked');
            var checkedStar = checked ? parseInt(checked.value, 10) : 0;
            starLabels.forEach(function (lbl) {
                var s = parseInt(lbl.getAttribute('data-star'), 10);
                if (s <= checkedStar) {
                    lbl.classList.add('text-primary', 'fill-icon');
                    lbl.classList.remove('text-outline-variant');
                } else {
                    lbl.classList.remove('text-primary', 'fill-icon');
                    lbl.classList.add('text-outline-variant');
                }
            });
        });
    });
    starInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            // Update visual state on change
            var checkedStar = parseInt(this.value, 10);
            starLabels.forEach(function (lbl) {
                var s = parseInt(lbl.getAttribute('data-star'), 10);
                if (s <= checkedStar) {
                    lbl.classList.add('text-primary', 'fill-icon');
                    lbl.classList.remove('text-outline-variant');
                } else {
                    lbl.classList.remove('text-primary', 'fill-icon');
                    lbl.classList.add('text-outline-variant');
                }
            });
        });
    });
})();
</script>

<?= $this->endSection() ?>