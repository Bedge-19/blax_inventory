<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php
$sectionMeta = [
    'home_banners' => [
        'title' => 'Homepage Banners',
        'icon' => 'view_carousel',
        'desc' => 'Hero banner badge, main headline, description, background image, CTA button, and catalog headings.'
    ],
    'announcement_bar' => [
        'title' => 'Announcement Bar',
        'icon' => 'campaign',
        'desc' => 'Global notification bar displayed across the top of all public customer storefront pages.'
    ],
    'footer_info' => [
        'title' => 'Footer & Contact Info',
        'icon' => 'contact_support',
        'desc' => 'Customer support email, hotline number, Polomolok hub physical location, and copyright notice.'
    ],
];

// Pre-calculate preview values from grouped items
$previewData = [];
foreach (($grouped ?? []) as $grpPage => $grpItems) {
    foreach ($grpItems as $it) {
        $previewData[$it['content_key']] = [
            'type'  => $it['content_type'],
            'text'  => $it['text_value'] ?? '',
            'image' => $it['image_url'] ?? '',
        ];
    }
}

// Recommended image dimensions & aspect ratio presets
$imageSpecs = [
    'hero_image' => [
        'aspect' => 'aspect-[3/1]',
        'guide'  => 'Recommended: 1440×480px (3:1 panoramic ratio, max 3MB)',
    ],
];

// Determine primary section order
$orderedSections = ['home_banners', 'announcement_bar', 'footer_info'];
foreach (array_keys($grouped ?? []) as $pageKey) {
    if (!in_array($pageKey, $orderedSections, true)) {
        $orderedSections[] = $pageKey;
    }
}
?>

<div class="space-y-6">

    <!-- Flash Alerts -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
            <span class="material-symbols-outlined text-[20px] text-emerald-600">check_circle</span>
            <span class="font-medium"><?= session()->getFlashdata('success') ?></span>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-4 rounded-xl bg-error-container/40 border border-error/20 text-on-error-container text-sm flex items-center gap-3">
            <span class="material-symbols-outlined text-[20px] text-error">error</span>
            <span class="font-medium"><?= session()->getFlashdata('error') ?></span>
        </div>
    <?php endif; ?>

    <!-- CMS Overview Header & Action Bar -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 soft-shadow p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-xl bg-primary/10 text-primary">
                    <span class="material-symbols-outlined text-[24px]">edit_note</span>
                </span>
                <h2 class="text-headline-sm font-bold text-on-surface">Storefront Content Management (CMS)</h2>
            </div>
            <p class="text-body-sm text-on-surface-variant mt-1.5">
                Edit storefront headlines, banners, announcements, and contact information with instant real-time live preview.
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0 flex-wrap">
            <a href="<?= base_url('/') ?>" target="_blank" class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-outline-variant hover:bg-surface-container transition-colors inline-flex items-center gap-1.5 text-on-surface">
                <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                <span>View Live Site</span>
            </a>
            <button type="button" onclick="document.getElementById('addEntryModal').classList.remove('hidden')" class="px-4 py-2 bg-surface-container-high hover:bg-surface-container-highest text-on-surface border border-outline-variant/50 rounded-xl text-xs font-semibold transition-all inline-flex items-center gap-1.5 shadow-2xs">
                <span class="material-symbols-outlined text-[16px] text-primary">add_circle</span>
                <span>Custom Field</span>
            </button>
        </div>
    </div>

    <!-- Main Split Grid: Left = Edit Forms, Right = Sticky Live Preview -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- LEFT COLUMN: Structured Form Cards (7 cols on lg screens)          -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="lg:col-span-7 space-y-6">

            <?php foreach ($orderedSections as $page): ?>
                <?php 
                    $items = $grouped[$page] ?? [];
                    if (empty($items)) continue;
                    $meta = $sectionMeta[$page] ?? [
                        'title' => ucwords(str_replace('_', ' ', $page)),
                        'icon' => 'tune',
                        'desc' => 'Custom page settings and storefront parameters.'
                    ];
                ?>

                <section id="section-<?= esc($page) ?>" class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 soft-shadow overflow-hidden">
                    
                    <!-- Section Header -->
                    <div class="px-6 py-4 bg-surface-container-low/60 border-b border-outline-variant/20 flex flex-wrap justify-between items-center gap-3">
                        <div class="flex items-center gap-3">
                            <span class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                                <span class="material-symbols-outlined text-[20px]"><?= esc($meta['icon']) ?></span>
                            </span>
                            <div>
                                <h3 class="text-title-md font-bold text-on-surface"><?= esc($meta['title']) ?></h3>
                                <p class="text-xs text-on-surface-variant mt-0.5"><?= esc($meta['desc']) ?></p>
                            </div>
                        </div>
                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-surface-container text-on-surface-variant border border-outline-variant/30">
                            <?= count($items) ?> <?= count($items) === 1 ? 'Field' : 'Fields' ?>
                        </span>
                    </div>

                    <!-- Entries List sorted top-to-bottom -->
                    <div class="divide-y divide-outline-variant/10">
                        <?php foreach ($items as $it): ?>
                            <?php 
                                $key = $it['content_key'];
                                $isLive = isset($connectedFields[$key]);
                                $liveLocation = $connectedFields[$key] ?? null;
                                $displayLabel = !empty($it['label']) ? $it['label'] : ucwords(str_replace('_', ' ', $key));
                            ?>
                            <div class="p-6 space-y-4 hover:bg-surface-container-low/20 transition-colors">
                                
                                <!-- Field Header: Plain-language Label + Live Status Badge -->
                                <div class="flex items-start justify-between gap-3 flex-wrap">
                                    <div class="space-y-1">
                                        <label for="field-<?= esc($it['id']) ?>" class="text-sm font-bold text-on-surface block">
                                            <?= esc($displayLabel) ?>
                                        </label>
                                        <?php if ($key === 'announcement_active'): ?>
                                            <p class="text-xs text-on-surface-variant">Turn on to display the announcement bar across all customer pages.</p>
                                        <?php elseif ($key === 'hero_cta_text'): ?>
                                            <p class="text-xs text-on-surface-variant">Label on the primary button inside the hero banner.</p>
                                        <?php elseif ($key === 'footer_copyright'): ?>
                                            <p class="text-xs text-on-surface-variant">Legal statement displayed at the very bottom of every customer page.</p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Live Status Badge -->
                                    <div class="shrink-0">
                                        <?php if ($isLive): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-500/10 text-emerald-700 border border-emerald-500/20 shadow-2xs" title="This field is actively rendered on the live customer storefront">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                <span>Live on <?= esc($liveLocation) ?></span>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-300" title="Custom field created by admin; not connected to any storefront template yet">
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                <span>Not connected to any page</span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Field Form: Image Upload or Text Controls -->
                                <?php if ($it['content_type'] === 'image'): ?>
                                    <?php 
                                        $img = $it['image_url'] ?? ''; 
                                        $src = !empty($img) ? (str_starts_with($img, 'http') ? $img : base_url($img)) : '';
                                        $spec = $imageSpecs[$key] ?? ['aspect' => 'aspect-[2/1]', 'guide' => 'Recommended: 1200×600px (2:1 ratio, max 3MB)'];
                                    ?>
                                    <div class="space-y-3">
                                        <!-- Proportional Preview Frame -->
                                        <div class="relative w-full max-w-xl <?= esc($spec['aspect']) ?> rounded-xl overflow-hidden border border-outline-variant/40 bg-surface-container-low/50 shadow-2xs group">
                                            <?php if (!empty($src)): ?>
                                                <img src="<?= esc($src) ?>" alt="<?= esc($displayLabel) ?>" id="frame-img-<?= esc($it['id']) ?>" class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-300">
                                                <a href="<?= esc($src) ?>" target="_blank" class="absolute inset-0 bg-black/40 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-xs font-bold gap-1.5">
                                                    <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                                                    <span>View Original Image</span>
                                                </a>
                                            <?php else: ?>
                                                <div class="w-full h-full flex flex-col items-center justify-center text-outline text-xs gap-1">
                                                    <span class="material-symbols-outlined text-3xl">image</span>
                                                    <span>No banner uploaded</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Upload Form -->
                                        <form action="<?= base_url('admin/content/upload') ?>" method="POST" enctype="multipart/form-data" class="bg-surface-container-low/40 p-3.5 rounded-xl border border-outline-variant/20 space-y-2.5 max-w-xl">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $it['id'] ?>">

                                            <div class="flex items-center justify-between text-[11px] text-on-surface-variant font-medium">
                                                <span class="uppercase font-bold tracking-wider">Replace Image (JPG, PNG, WEBP)</span>
                                                <span class="font-mono text-primary font-semibold"><?= esc($spec['guide']) ?></span>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <input type="file" name="image" accept="image/*" class="flex-1 text-xs border border-outline-variant rounded-xl px-3 py-1.5 bg-surface-container-lowest file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20" required>
                                                <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-xl text-xs font-bold hover:bg-primary/90 transition-all shadow-2xs shrink-0 flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-[16px]">upload</span>
                                                    <span>Upload</span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                <?php else: ?>
                                    <!-- Text / Textarea Form -->
                                    <form action="<?= base_url('admin/content/save') ?>" method="POST" class="space-y-3">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $it['id'] ?>">

                                        <?php if ($key === 'announcement_active'): ?>
                                            <!-- Friendly Toggle Selector for Announcement Active -->
                                            <?php $isActive = in_array(trim((string)($it['text_value'] ?? '0')), ['1', 'true', 'on'], true); ?>
                                            <div class="flex items-center gap-4 bg-surface-container-low/40 p-3 rounded-xl border border-outline-variant/20">
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" name="text_value" value="1" <?= $isActive ? 'checked' : '' ?> data-preview-key="<?= esc($key) ?>" class="text-primary focus:ring-primary h-4 w-4">
                                                    <span class="text-xs font-bold text-emerald-700 flex items-center gap-1">
                                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active (Show Bar)
                                                    </span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" name="text_value" value="0" <?= !$isActive ? 'checked' : '' ?> data-preview-key="<?= esc($key) ?>" class="text-primary focus:ring-primary h-4 w-4">
                                                    <span class="text-xs font-semibold text-on-surface-variant flex items-center gap-1">
                                                        <span class="w-2 h-2 rounded-full bg-slate-400"></span> Inactive (Hide Bar)
                                                    </span>
                                                </label>
                                                <button type="submit" class="ml-auto px-4 py-1.5 bg-primary text-on-primary rounded-xl text-xs font-bold hover:bg-primary/90 transition-all shadow-2xs">
                                                    Save Status
                                                </button>
                                            </div>

                                        <?php elseif ($it['content_type'] === 'textarea'): ?>
                                            <div class="space-y-1.5">
                                                <textarea id="field-<?= esc($it['id']) ?>" name="text_value" rows="3" data-preview-key="<?= esc($key) ?>" class="w-full border border-outline-variant rounded-xl p-3 text-xs sm:text-sm bg-surface-container-low focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:outline-none transition-all leading-relaxed" placeholder="Enter <?= esc($displayLabel) ?>..."><?= esc($it['text_value'] ?? '') ?></textarea>
                                                <div class="flex justify-between items-center">
                                                    <span class="text-[11px] text-on-surface-variant">Changes preview instantly on the right. Click Save to persist.</span>
                                                    <button type="submit" class="px-4 py-1.5 bg-primary text-on-primary rounded-xl text-xs font-bold hover:bg-primary/90 transition-all shadow-2xs flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-[16px]">save</span>
                                                        <span>Save Text</span>
                                                    </button>
                                                </div>
                                            </div>

                                        <?php else: ?>
                                            <div class="flex items-center gap-2">
                                                <input id="field-<?= esc($it['id']) ?>" name="text_value" value="<?= esc($it['text_value'] ?? '') ?>" data-preview-key="<?= esc($key) ?>" class="flex-1 border border-outline-variant rounded-xl px-3.5 py-2 text-xs sm:text-sm bg-surface-container-low focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary focus:outline-none transition-all" placeholder="Enter <?= esc($displayLabel) ?>...">
                                                <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-xl text-xs font-bold hover:bg-primary/90 transition-all shadow-2xs shrink-0 flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-[16px]">save</span>
                                                    <span>Save</span>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>

                                <!-- Collapsed Technical Details / Rename for Developers -->
                                <details class="text-xs text-on-surface-variant group pt-1">
                                    <summary class="cursor-pointer font-medium hover:text-primary inline-flex items-center gap-1 text-[11px] select-none text-outline">
                                        <span class="material-symbols-outlined text-[14px] transition-transform group-open:rotate-90">chevron_right</span>
                                        <span>Advanced Developer Details</span>
                                    </summary>
                                    <div class="mt-2.5 p-3 rounded-xl bg-surface-container-low/60 border border-outline-variant/30 space-y-2">
                                        <div class="flex flex-wrap items-center gap-2 text-[11px]">
                                            <span class="font-mono bg-surface-container px-2 py-0.5 rounded border border-outline-variant/40 text-on-surface font-semibold">Key: <?= esc($key) ?></span>
                                            <span class="font-mono bg-surface-container px-2 py-0.5 rounded border border-outline-variant/40 text-on-surface">Type: <?= esc($it['content_type']) ?></span>
                                            <span class="font-mono bg-surface-container px-2 py-0.5 rounded border border-outline-variant/40 text-on-surface">Page: <?= esc($page) ?></span>
                                        </div>
                                        <!-- Inline rename form -->
                                        <form action="<?= base_url('admin/content/save') ?>" method="POST" class="flex items-center gap-2 pt-1 border-t border-outline-variant/20">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $it['id'] ?>">
                                            <label class="text-[11px] font-bold text-on-surface-variant uppercase shrink-0">Display Label:</label>
                                            <input name="label" value="<?= esc($it['label'] ?? '') ?>" placeholder="Label name" class="flex-1 border border-outline-variant rounded-lg px-2.5 py-1 text-xs bg-surface-container-lowest">
                                            <button type="submit" class="px-2.5 py-1 border border-outline-variant rounded-lg text-xs font-semibold hover:bg-surface-container transition-colors shrink-0">
                                                Update Label
                                            </button>
                                        </form>
                                    </div>
                                </details>

                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>

        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- RIGHT COLUMN: Sticky Real-time Storefront Live Preview             -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="lg:col-span-5 sticky top-6 space-y-3">
            
            <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 soft-shadow overflow-hidden">
                
                <!-- Preview Chrome Bar -->
                <div class="px-4 py-3 bg-surface-container-low/80 border-b border-outline-variant/30 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                        <span class="ml-2 text-[11px] font-mono text-on-surface-variant font-medium bg-surface-container px-2.5 py-0.5 rounded-md border border-outline-variant/30 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[12px] text-emerald-600">lock</span>
                            <span>blax.ph/home</span>
                        </span>
                    </div>

                    <div class="flex items-center gap-1.5 text-[11px] font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                        <span>Live Preview</span>
                    </div>
                </div>

                <!-- Preview Viewport Container -->
                <div class="p-3 bg-slate-100/70 overflow-y-auto max-h-[calc(100vh-140px)] space-y-3 text-on-surface text-xs select-none">
                    
                    <!-- 1. Preview Announcement Bar -->
                    <?php 
                        $annText = $previewData['announcement_text']['text'] ?? '🚀 Free doorstep delivery on orders over ₱500 within Polomolok! Use code BLAXSHIP';
                        $annActive = in_array(trim((string)($previewData['announcement_active']['text'] ?? '1')), ['1', 'true', 'on'], true);
                    ?>
                    <div id="pv-announcement-bar" class="<?= $annActive ? '' : 'hidden' ?> bg-gradient-to-r from-primary via-primary-container to-primary text-on-primary text-[10px] py-1.5 px-3 rounded-lg shadow-2xs flex items-center justify-between gap-2 transition-all">
                        <div class="flex-1 flex items-center justify-center gap-1 text-center font-medium truncate">
                            <span class="material-symbols-outlined text-[13px] text-amber-300 shrink-0">campaign</span>
                            <span id="pv-announcement-text" class="truncate"><?= esc($annText) ?></span>
                        </div>
                        <span class="text-[10px] text-on-primary/70">✕</span>
                    </div>

                    <!-- 2. Preview Mini Header -->
                    <div class="bg-surface-container-lowest rounded-xl p-2.5 border border-outline-variant/30 shadow-2xs flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            <img src="<?= base_url('icon.png') ?>" alt="Blax" class="w-5 h-5 rounded object-contain">
                            <span class="font-bold text-xs font-display text-primary">Blax</span>
                        </div>
                        <div class="flex-1 max-w-[140px] bg-surface-container-low rounded-lg px-2 py-1 text-[10px] text-on-surface-variant truncate">
                            🔍 Search items...
                        </div>
                        <div class="flex items-center gap-2 text-on-surface-variant">
                            <span class="material-symbols-outlined text-[15px]">shopping_cart</span>
                            <span class="material-symbols-outlined text-[15px]">person</span>
                        </div>
                    </div>

                    <!-- 3. Preview Hero Banner -->
                    <?php
                        $heroBadge = $previewData['hero_badge']['text'] ?? 'Seasonal Event';
                        $heroTitle = $previewData['hero_title']['text'] ?? 'The Ultimate Merchandise & Printing Hub';
                        $heroSubtitle = $previewData['hero_subtitle']['text'] ?? 'Discover premium goods, exclusive deals, and top-tier printing services all in one place across Polomolok.';
                        $heroCta = $previewData['hero_cta_text']['text'] ?? 'Explore Marketplace';
                        $heroImg = $previewData['hero_image']['image'] ?? 'https://images.unsplash.com/photo-1556742049-0a67daf64f42?auto=format&fit=crop&w=1440&q=80';
                        if ($heroImg && !str_starts_with($heroImg, 'http')) $heroImg = base_url($heroImg);
                    ?>
                    <div id="pv-hero-banner" class="relative w-full rounded-xl overflow-hidden shadow-2xs bg-surface-container-lowest min-h-[140px] flex items-center group">
                        <div id="pv-hero-bg" class="absolute inset-0 bg-cover bg-center w-full h-full scale-105 filter blur-[3px] opacity-85 transition-transform duration-500 pointer-events-none" style="background-image: url('<?= esc($heroImg) ?>');"></div>
                        <div class="absolute inset-0 pointer-events-none" style="background: linear-gradient(90deg, rgba(255, 255, 255, 0.98) 0%, rgba(255, 255, 255, 0.90) 50%, rgba(255, 255, 255, 0.45) 80%, rgba(255, 255, 255, 0.1) 100%);"></div>
                        
                        <div class="relative z-10 p-3.5 max-w-[210px] space-y-1">
                            <span id="pv-hero-badge" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary/10 border border-primary/20 text-[9px] font-bold text-primary uppercase tracking-wider truncate backdrop-blur-xs">
                                <?= esc($heroBadge) ?>
                            </span>
                            <h4 id="pv-hero-title" class="text-xs font-bold font-display text-slate-900 line-clamp-2 leading-snug">
                                <?= esc($heroTitle) ?>
                            </h4>
                            <p id="pv-hero-subtitle" class="text-[10px] text-slate-700 font-medium line-clamp-2 leading-tight">
                                <?= esc($heroSubtitle) ?>
                            </p>
                            <div class="pt-1">
                                <span id="pv-hero-cta" class="inline-block bg-primary text-on-primary text-[9px] font-bold px-2.5 py-1 rounded-md shadow-2xs truncate">
                                    <?= esc($heroCta) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Preview Catalog Section Header -->
                    <?php
                        $catTitle = $previewData['catalog_title']['text'] ?? 'Global Product Catalog';
                        $catSub = $previewData['catalog_subtitle']['text'] ?? 'Aggregation of all products currently available across the entire RHK network.';
                    ?>
                    <div class="bg-surface-container-lowest rounded-xl p-3 border border-outline-variant/30 space-y-1">
                        <div class="flex justify-between items-center">
                            <h5 id="pv-catalog-title" class="text-[11px] font-bold text-on-surface truncate">
                                <?= esc($catTitle) ?>
                            </h5>
                            <span class="text-[9px] text-primary font-semibold">Filter & Sort</span>
                        </div>
                        <p id="pv-catalog-subtitle" class="text-[9px] text-on-surface-variant line-clamp-1 leading-tight">
                            <?= esc($catSub) ?>
                        </p>
                        <!-- Mock Product Mini Row -->
                        <div class="grid grid-cols-3 gap-1.5 pt-1.5">
                            <div class="bg-surface-container-low rounded p-1 text-center space-y-0.5">
                                <div class="w-full h-9 bg-surface-container-high rounded flex items-center justify-center text-[10px] text-outline">👕</div>
                                <div class="text-[8px] font-medium truncate">T-Shirt</div>
                                <div class="text-[8px] font-bold text-primary">₱250</div>
                            </div>
                            <div class="bg-surface-container-low rounded p-1 text-center space-y-0.5">
                                <div class="w-full h-9 bg-surface-container-high rounded flex items-center justify-center text-[10px] text-outline">📄</div>
                                <div class="text-[8px] font-medium truncate">Print Flyer</div>
                                <div class="text-[8px] font-bold text-primary">₱5</div>
                            </div>
                            <div class="bg-surface-container-low rounded p-1 text-center space-y-0.5">
                                <div class="w-full h-9 bg-surface-container-high rounded flex items-center justify-center text-[10px] text-outline">☕</div>
                                <div class="text-[8px] font-medium truncate">Mug</div>
                                <div class="text-[8px] font-bold text-primary">₱180</div>
                            </div>
                        </div>
                    </div>

                    <!-- 6. Preview Mini Footer -->
                    <?php
                        $ftEmail = $previewData['footer_contact_email']['text'] ?? 'support@blaxinventory.com';
                        $ftPhone = $previewData['footer_phone']['text'] ?? '+63 917 123 4567';
                        $ftAddress = $previewData['footer_address']['text'] ?? 'Poblacion, Polomolok, South Cotabato, Philippines 9504';
                        $ftCopy = $previewData['footer_copyright']['text'] ?? ('© ' . date('Y') . ' Blax. All rights reserved.');
                    ?>
                    <div id="pv-footer" class="bg-surface-container-highest rounded-xl p-3 border border-outline-variant/40 space-y-2 text-[9px]">
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <span class="font-bold block text-on-surface">Quick Links</span>
                                <span class="text-on-surface-variant block mt-0.5">Shops • Print</span>
                            </div>
                            <div>
                                <span class="font-bold block text-on-surface">Support</span>
                                <span class="text-on-surface-variant block mt-0.5">Help • Terms</span>
                            </div>
                            <div>
                                <span class="font-bold block text-on-surface">Contact</span>
                                <span id="pv-footer-email" class="text-on-surface-variant block truncate mt-0.5"><?= esc($ftEmail) ?></span>
                                <span id="pv-footer-phone" class="text-on-surface-variant block truncate"><?= esc($ftPhone) ?></span>
                            </div>
                        </div>
                        <div class="pt-1.5 border-t border-outline-variant/30 text-[8px] text-on-surface-variant text-center leading-tight">
                            <span id="pv-footer-copyright"><?= esc($ftCopy) ?></span>
                        </div>
                    </div>

                </div>

                <div class="px-4 py-2 bg-surface-container-low text-[11px] text-on-surface-variant text-center border-t border-outline-variant/20 flex items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-[14px] text-primary">sync</span>
                    <span>Preview updates in real-time as you edit fields on the left</span>
                </div>

            </div>

        </div>

    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- MODAL: Add New Custom Field (Gated with Clear Developer Warning)       -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div id="addEntryModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
        
        <div class="flex items-center justify-between border-b border-outline-variant/20 pb-3">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[22px]">developer_mode</span>
                <h3 class="text-title-md font-bold text-on-surface">Add New Custom CMS Field</h3>
            </div>
            <button type="button" onclick="document.getElementById('addEntryModal').classList.add('hidden')" class="text-outline hover:text-on-surface p-1 rounded-full transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Prominent Developer Warning -->
        <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-900 text-xs space-y-1">
            <div class="flex items-center gap-1.5 font-bold">
                <span class="material-symbols-outlined text-[18px] text-amber-600">warning</span>
                <span>Important Developer Notice</span>
            </div>
            <p class="leading-relaxed text-[11px] text-amber-800">
                Custom fields added here will <strong>not automatically appear</strong> anywhere on the customer website. A software developer must write code in the corresponding template file (e.g. reading <code>$siteContents['your_key']</code>) before customers will be able to see it.
            </p>
        </div>

        <form action="<?= base_url('admin/content/save') ?>" method="POST" class="space-y-3">
            <?= csrf_field() ?>
            
            <div>
                <label class="text-xs font-bold text-on-surface-variant uppercase">Section Group</label>
                <select name="page" class="w-full mt-1 p-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:ring-2 focus:ring-primary focus:outline-none" required>
                    <option value="home_banners">Homepage Banners (home_banners)</option>
                    <option value="announcement_bar">Announcement Bar (announcement_bar)</option>
                    <option value="footer_info">Footer Info (footer_info)</option>
                    <option value="custom_page">Custom / Other Section (custom_page)</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-on-surface-variant uppercase">Unique Key Name (snake_case)</label>
                <input name="content_key" placeholder="e.g. flash_sale_discount_badge" required pattern="[a-z0-9_]+" class="w-full mt-1 p-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono focus:ring-2 focus:ring-primary focus:outline-none">
                <p class="text-[10px] text-on-surface-variant mt-1">Lowercase letters, numbers, and underscores only.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-on-surface-variant uppercase">Field Type</label>
                    <select name="content_type" class="w-full mt-1 p-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium">
                        <option value="text">Single Line Text</option>
                        <option value="textarea">Multi-line Textarea</option>
                        <option value="image">Image Upload</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-on-surface-variant uppercase">Display Label</label>
                    <input name="label" placeholder="e.g. Flash Sale Badge" class="w-full mt-1 p-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium">
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-on-surface-variant uppercase">Initial Text Value (Optional)</label>
                <textarea name="text_value" rows="2" placeholder="Initial value or placeholder..." class="w-full mt-1 p-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs"></textarea>
            </div>

            <div class="flex gap-2.5 pt-2 border-t border-outline-variant/20">
                <button type="button" onclick="document.getElementById('addEntryModal').classList.add('hidden')" class="flex-1 py-2 rounded-xl text-xs font-bold border border-outline-variant hover:bg-surface-container transition-colors">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-2 rounded-xl text-xs font-bold bg-primary text-on-primary hover:bg-primary/90 transition-all shadow-sm">
                    Create Field
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- Real-time Live Preview JS Synchronization                              -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Map content_key to preview element IDs
    const textTargetMap = {
        'hero_badge': 'pv-hero-badge',
        'hero_title': 'pv-hero-title',
        'hero_subtitle': 'pv-hero-subtitle',
        'hero_cta_text': 'pv-hero-cta',
        'catalog_title': 'pv-catalog-title',
        'catalog_subtitle': 'pv-catalog-subtitle',
        'announcement_text': 'pv-announcement-text',
        'footer_contact_email': 'pv-footer-email',
        'footer_phone': 'pv-footer-phone',
        'footer_address': 'pv-footer-address',
        'footer_copyright': 'pv-footer-copyright',
    };

    function updatePreview(key, val) {
        if (!key) return;

        // Announcement Active Toggle
        if (key === 'announcement_active') {
            const bar = document.getElementById('pv-announcement-bar');
            if (bar) {
                const isActive = (val === '1' || val === 'true' || val === 'on');
                if (isActive) {
                    bar.classList.remove('hidden');
                } else {
                    bar.classList.add('hidden');
                }
            }
            return;
        }

        const targetId = textTargetMap[key];
        if (targetId) {
            const el = document.getElementById(targetId);
            if (el) {
                el.textContent = val || '—';
            }
        }
    }

    // Listen on input and change across all previewable fields
    document.addEventListener('input', (e) => {
        const key = e.target.getAttribute('data-preview-key');
        if (key) {
            updatePreview(key, e.target.value);
        }
    });

    document.addEventListener('change', (e) => {
        const key = e.target.getAttribute('data-preview-key');
        if (key) {
            if (e.target.type === 'radio') {
                if (e.target.checked) updatePreview(key, e.target.value);
            } else {
                updatePreview(key, e.target.value);
            }
        }
    });
});
</script>

<?= $this->endSection() ?>
