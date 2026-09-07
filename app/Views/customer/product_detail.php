<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<main class="max-w-container-max mx-auto px-4 md:px-8 lg:px-10 py-8 md:py-xl flex-grow w-full">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-xxl">

        <!-- Left: Visual Assets -->
        <div class="lg:col-span-7 space-y-lg">

            <div class="relative rounded-2xl overflow-hidden bg-white shadow-md aspect-square group">

                <img id="main-product-image" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" src="<?= esc(product_image_url($product['image_url'] ?? null)) ?>" alt="<?= esc($product['name']) ?>">

                <?php if (!empty($product['is_bestseller'])): ?>

                    <div class="absolute top-md left-md bg-primary-container/90 backdrop-blur text-on-primary-container px-md py-xs rounded-full font-label-sm text-label-sm">Bestseller</div>

                <?php endif; ?>

            </div>

            <?php if (!empty($productImages)): ?>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-md">

                    <?php foreach ($productImages as $idx => $img): ?>

                        <?php $thumbUrl = product_image_url($img['image_url'] ?? null); ?>

                        <button type="button" class="gallery-thumb rounded-xl overflow-hidden h-24 cursor-pointer transition-colors p-0 <?= (!empty($img['is_primary']) || $idx === 0) ? 'border-2 border-primary' : 'border border-outline-variant/30 hover:border-primary' ?>" data-image="<?= esc($thumbUrl) ?>" aria-label="View product image">

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

                <?php $outOfStock = (int) ($product['stock_quantity'] ?? 0) <= 0; ?>

                <form action="<?= base_url('cart/add') ?>" method="POST" class="space-y-md">

                    <?= csrf_field() ?>

                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
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

                        <button type="submit" class="flex-1 bg-secondary-container text-on-secondary-container py-md rounded-full font-button text-button hover:brightness-95 transition-all flex items-center justify-center gap-sm" <?= $outOfStock ? 'disabled' : '' ?>>

                            <span class="material-symbols-outlined text-[20px]">shopping_cart</span>
                            <?= $outOfStock ? 'Out of Stock' : 'Add to Cart' ?>

                        </button>

                    </div>

                    <button type="submit" class="w-full bg-primary text-on-primary py-lg rounded-full font-headline-md text-headline-md hover:shadow-lg transition-all active:scale-[0.98]" <?= $outOfStock ? 'disabled' : '' ?>>

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