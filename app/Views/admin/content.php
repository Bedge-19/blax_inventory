<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php
$sectionMeta = [
    'home_banners' => [
        'title' => 'Homepage Banners',
        'icon' => 'view_carousel',
        'desc' => 'Hero badge, main headlines, background imagery, and primary CTA buttons on customer homepage.'
    ],
    'announcement_bar' => [
        'title' => 'Announcement Bar',
        'icon' => 'campaign',
        'desc' => 'Global notification bar displayed across the top of all customer pages.'
    ],
    'promotional_blocks' => [
        'title' => 'Promotional Blocks',
        'icon' => 'local_offer',
        'desc' => 'Seasonal sales, printing promotions, and trending merchandise highlight cards.'
    ],
    'footer_info' => [
        'title' => 'Footer & Contact Info',
        'icon' => 'contact_support',
        'desc' => 'Marketplace support hotline, email, Polomolok hub address, and legal copyright notices.'
    ],
];
?>

<div class="space-y-xl">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-md rounded-xl bg-green-100 text-green-800 text-sm flex items-center gap-sm">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-md rounded-xl bg-error-container text-on-error-container text-sm flex items-center gap-sm">
            <span class="material-symbols-outlined text-[18px]">error</span>
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <!-- CMS Overview Header -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 soft-shadow p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-md">
        <div>
            <h2 class="text-headline-sm font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">edit_note</span>
                <span>Content Management System (CMS)</span>
            </h2>
            <p class="text-body-md text-on-surface-variant mt-1">Manage public storefront text, banner images, and promotional notices across all customer touchpoints.</p>
        </div>
        <button onclick="document.getElementById('addEntryModal').classList.remove('hidden')" class="px-md py-sm bg-primary text-on-primary rounded-xl text-xs font-bold hover:bg-primary/90 transition-all flex items-center gap-1 shrink-0 shadow-sm">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>Add Custom Field</span>
        </button>
    </div>

    <!-- 4 Grouped Sections Loop -->
    <?php foreach (($grouped ?? []) as $page => $items): ?>
        <?php 
            $meta = $sectionMeta[$page] ?? [
                'title' => ucwords(str_replace('_', ' ', $page)),
                'icon' => 'folder',
                'desc' => 'Custom page content entries.'
            ];
        ?>
        <section class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 soft-shadow overflow-hidden">
            <!-- Section Header -->
            <div class="px-6 py-4 bg-surface-container-low/50 border-b border-outline-variant/20 flex flex-wrap justify-between items-center gap-sm">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                        <span class="material-symbols-outlined text-[22px]"><?= esc($meta['icon']) ?></span>
                    </span>
                    <div>
                        <h3 class="text-title-lg font-bold text-on-surface"><?= esc($meta['title']) ?></h3>
                        <p class="text-xs text-on-surface-variant"><?= esc($meta['desc']) ?></p>
                    </div>
                </div>
                <span class="text-xs font-bold px-3 py-1 rounded-full bg-surface-container text-on-surface-variant border border-outline-variant/30">
                    <?= count($items) ?> Field<?= count($items) === 1 ? '' : 's' ?>
                </span>
            </div>

            <!-- Entries List -->
            <div class="divide-y divide-outline-variant/10">
                <?php foreach ($items as $it): ?>
                    <div class="p-6 flex flex-col lg:flex-row gap-lg items-start hover:bg-surface-container-low/30 transition-colors">
                        
                        <!-- Left: Field Details & Preview -->
                        <div class="flex-1 min-w-0 space-y-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-bold font-mono px-2 py-0.5 rounded bg-primary/10 text-primary">
                                    <?= esc($it['content_key']) ?>
                                </span>
                                <span class="text-xs font-semibold text-on-surface">
                                    <?= esc($it['label'] ?: $it['content_key']) ?>
                                </span>
                                <span class="text-[11px] uppercase tracking-wider text-outline px-1.5 py-0.5 rounded border border-outline-variant/30">
                                    <?= esc($it['content_type']) ?>
                                </span>
                            </div>

                            <?php if ($it['content_type'] === 'image'): ?>
                                <div class="mt-2 flex items-start gap-4">
                                    <?php 
                                        $img = $it['image_url'] ?? ''; 
                                        $src = !empty($img) ? (str_starts_with($img, 'http') ? $img : base_url($img)) : '';
                                    ?>
                                    <?php if (!empty($src)): ?>
                                        <div class="relative w-48 h-28 rounded-xl overflow-hidden border border-outline-variant/40 shadow-xs group bg-black/5">
                                            <img src="<?= esc($src) ?>" alt="<?= esc($it['content_key']) ?>" class="w-full h-full object-cover">
                                            <a href="<?= esc($src) ?>" target="_blank" class="absolute inset-0 bg-black/40 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-xs font-bold gap-1">
                                                <span class="material-symbols-outlined text-[16px]">open_in_new</span> Full Preview
                                            </a>
                                        </div>
                                        <p class="text-xs text-on-surface-variant font-mono truncate max-w-sm self-center"><?= esc($img) ?></p>
                                    <?php else: ?>
                                        <div class="w-48 h-28 rounded-xl border-2 border-dashed border-outline-variant flex flex-col items-center justify-center text-outline text-xs">
                                            <span class="material-symbols-outlined text-2xl">image</span>
                                            <span>No image uploaded</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/20 text-xs sm:text-sm text-on-surface break-words">
                                    <?= nl2br(esc($it['text_value'] ?? '')) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Right: Edit Form Controls -->
                        <div class="w-full lg:w-96 space-y-3 shrink-0">
                            <?php if ($it['content_type'] === 'image'): ?>
                                <form action="<?= base_url('admin/content/upload') ?>" method="POST" enctype="multipart/form-data" class="space-y-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $it['id'] ?>">
                                    <label class="text-[11px] font-bold text-on-surface-variant uppercase">Replace Image (JPG, PNG, WEBP)</label>
                                    <input type="file" name="image" accept="image/*" class="w-full text-xs border border-outline-variant rounded-xl px-3 py-2 bg-surface-container-low" required>
                                    <button type="submit" class="w-full bg-primary text-on-primary py-2 rounded-xl text-xs font-bold hover:bg-primary/90 transition-all shadow-2xs">Upload Image</button>
                                </form>
                            <?php else: ?>
                                <form action="<?= base_url('admin/content/save') ?>" method="POST" class="space-y-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $it['id'] ?>">
                                    <label class="text-[11px] font-bold text-on-surface-variant uppercase">Update Value</label>
                                    <?php if ($it['content_type'] === 'textarea'): ?>
                                        <textarea name="text_value" rows="3" class="w-full border border-outline-variant rounded-xl px-3 py-2 text-xs bg-surface-container-low focus:ring-2 focus:ring-primary"><?= esc($it['text_value'] ?? '') ?></textarea>
                                    <?php else: ?>
                                        <input name="text_value" value="<?= esc($it['text_value'] ?? '') ?>" class="w-full border border-outline-variant rounded-xl px-3 py-2 text-xs bg-surface-container-low focus:ring-2 focus:ring-primary">
                                    <?php endif; ?>
                                    <button type="submit" class="w-full bg-primary text-on-primary py-2 rounded-xl text-xs font-bold hover:bg-primary/90 transition-all shadow-2xs">Save Text</button>
                                </form>
                            <?php endif; ?>

                            <!-- Label Update -->
                            <form action="<?= base_url('admin/content/save') ?>" method="POST" class="flex gap-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $it['id'] ?>">
                                <input name="label" value="<?= esc($it['label'] ?? '') ?>" placeholder="Display Label" class="flex-1 border border-outline-variant rounded-xl px-3 py-1.5 text-xs bg-surface-container-low">
                                <button type="submit" class="px-3 py-1.5 border border-outline-variant rounded-xl text-xs font-bold hover:bg-surface-container transition-colors">Rename</button>
                            </form>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

</div>

<!-- Modal: Add New Content Entry -->
<div id="addEntryModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-md">
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl max-w-md w-full p-lg shadow-2xl space-y-md">
        <div class="flex items-center justify-between border-b border-outline-variant/20 pb-sm">
            <h3 class="text-title-md font-bold text-on-surface">Add New CMS Field</h3>
            <button type="button" onclick="document.getElementById('addEntryModal').classList.add('hidden')" class="text-outline hover:text-on-surface p-1 rounded-full"><span class="material-symbols-outlined">close</span></button>
        </div>

        <form action="<?= base_url('admin/content/save') ?>" method="POST" class="space-y-md">
            <?= csrf_field() ?>
            
            <div>
                <label class="text-xs font-bold text-on-surface-variant uppercase">Section / Category</label>
                <select name="page" class="w-full mt-1 p-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs" required>
                    <option value="home_banners">Homepage Banners (home_banners)</option>
                    <option value="announcement_bar">Announcement Bar (announcement_bar)</option>
                    <option value="promotional_blocks">Promotional Blocks (promotional_blocks)</option>
                    <option value="footer_info">Footer Info (footer_info)</option>
                    <option value="printing_services">Printing Services Page</option>
                    <option value="categories">Categories Page</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-on-surface-variant uppercase">Content Key (e.g. hero_banner_2)</label>
                <input name="content_key" placeholder="unique_key_name" required class="w-full mt-1 p-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono">
            </div>

            <div>
                <label class="text-xs font-bold text-on-surface-variant uppercase">Field Type</label>
                <select name="content_type" class="w-full mt-1 p-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs">
                    <option value="text">Single Line Text</option>
                    <option value="textarea">Multi-line Textarea</option>
                    <option value="image">Image Upload</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-on-surface-variant uppercase">Display Label</label>
                <input name="label" placeholder="e.g. Hero Secondary Banner" class="w-full mt-1 p-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs">
            </div>

            <div class="flex gap-sm pt-sm border-t border-outline-variant/20">
                <button type="button" onclick="document.getElementById('addEntryModal').classList.add('hidden')" class="flex-1 py-2 rounded-xl text-xs font-bold border border-outline-variant hover:bg-surface-container">Cancel</button>
                <button type="submit" class="flex-1 py-2 rounded-xl text-xs font-bold bg-primary text-on-primary hover:bg-primary/90 shadow-sm">Create Field</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
