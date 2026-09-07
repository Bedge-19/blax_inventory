<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="space-y-xl">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="p-md rounded-xl bg-green-100 text-green-800 text-sm"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="p-md rounded-xl bg-error-container text-on-error-container text-sm"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 soft-shadow p-6">
        <h3 class="text-title-lg font-bold text-on-surface">Content Management</h3>
        <p class="text-body-md text-on-surface-variant">Edit customer-facing text and images. Changes appear immediately on customer pages (Home, Printing Services, Categories).</p>
        <p class="text-xs text-on-surface-variant mt-sm">Pages: <span class="font-semibold">home</span> · <span class="font-semibold">printing_services</span> · <span class="font-semibold">categories</span></p>
    </div>

    <!-- Add new content -->
    <section class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 soft-shadow p-6">
        <h4 class="text-label-sm font-bold text-on-surface uppercase tracking-wider mb-md">Add New Content Entry</h4>
        <form action="<?= base_url('admin/content/save') ?>" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-sm">
            <?= csrf_field() ?>
            <select name="page" class="border border-outline-variant rounded-lg px-md py-sm text-sm" required>
                <option value="home">home</option>
                <option value="printing_services">printing_services</option>
                <option value="categories">categories</option>
                <option value="shops">shops</option>
            </select>
            <input name="content_key" placeholder="key e.g. hero_title" class="border border-outline-variant rounded-lg px-md py-sm text-sm" required>
            <select name="content_type" class="border border-outline-variant rounded-lg px-md py-sm text-sm">
                <option value="text">text</option>
                <option value="textarea">textarea</option>
                <option value="image">image</option>
            </select>
            <input name="label" placeholder="Label" class="border border-outline-variant rounded-lg px-md py-sm text-sm">
            <button type="submit" class="bg-primary text-on-primary px-md py-sm rounded-lg text-sm font-semibold">Add</button>
        </form>
    </section>

    <?php foreach (($grouped ?? []) as $page => $items): ?>
        <section class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 soft-shadow overflow-hidden">
            <div class="px-6 py-4 bg-surface-container-low/50 border-b border-outline-variant/20 flex justify-between items-center">
                <h4 class="text-title-lg font-bold text-on-surface"><?= esc(ucwords(str_replace('_',' ', $page))) ?> <span class="text-xs font-normal text-on-surface-variant">(<?= count($items) ?> items)</span></h4>
                <span class="text-xs text-on-surface-variant">Page: <?= esc($page) ?></span>
            </div>
            <div class="divide-y divide-outline-variant/10">
                <?php foreach ($items as $it): ?>
                    <div class="p-6 flex flex-col lg:flex-row gap-lg items-start">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-sm mb-sm">
                                <span class="text-label-sm font-bold text-primary"><?= esc($it['content_key']) ?></span>
                                <span class="text-xs px-sm py-xs bg-surface-container-high rounded-full"><?= esc($it['content_type']) ?></span>
                                <span class="text-xs text-on-surface-variant"><?= esc($it['label'] ?? '') ?></span>
                            </div>
                            <?php if ($it['content_type'] === 'image'): ?>
                                <div class="flex gap-md items-center">
                                    <?php $img = $it['image_url'] ?? ''; ?>
                                    <?php if ($img): ?>
                                        <?php $src = str_starts_with($img,'http') ? $img : base_url($img); ?>
                                        <img src="<?= esc($src) ?>" alt="<?= esc($it['content_key']) ?>" class="w-32 h-20 object-cover rounded-lg border border-outline-variant/30">
                                        <p class="text-xs text-on-surface-variant break-all"><?= esc($img) ?></p>
                                    <?php else: ?>
                                        <p class="text-xs text-on-surface-variant">No image yet</p>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-sm text-on-surface bg-surface-container-low p-md rounded-lg border border-outline-variant/20 break-words"><?= esc($it['text_value'] ?? '') ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="w-full lg:w-80 space-y-md">
                            <?php if ($it['content_type'] === 'image'): ?>
                                <form action="<?= base_url('admin/content/upload') ?>" method="POST" enctype="multipart/form-data" class="space-y-sm">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $it['id'] ?>">
                                    <input type="file" name="image" accept="image/*" class="w-full text-sm border border-outline-variant rounded-lg px-sm py-sm" required>
                                    <button type="submit" class="w-full bg-primary text-on-primary py-sm rounded-lg text-sm font-semibold">Upload Image</button>
                                </form>
                            <?php else: ?>
                                <form action="<?= base_url('admin/content/save') ?>" method="POST" class="space-y-sm">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $it['id'] ?>">
                                    <?php if ($it['content_type'] === 'textarea'): ?>
                                        <textarea name="text_value" rows="3" class="w-full border border-outline-variant rounded-lg px-md py-sm text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"><?= esc($it['text_value'] ?? '') ?></textarea>
                                    <?php else: ?>
                                        <input name="text_value" value="<?= esc($it['text_value'] ?? '') ?>" class="w-full border border-outline-variant rounded-lg px-md py-sm text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                                    <?php endif; ?>
                                    <button type="submit" class="w-full bg-primary text-on-primary py-sm rounded-lg text-sm font-semibold">Save Text</button>
                                </form>
                            <?php endif; ?>
                            <form action="<?= base_url('admin/content/save') ?>" method="POST" class="flex gap-sm">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $it['id'] ?>">
                                <input name="label" value="<?= esc($it['label'] ?? '') ?>" placeholder="Label" class="flex-1 border border-outline-variant rounded-lg px-sm py-xs text-xs">
                                <button type="submit" class="px-sm py-xs border border-outline-variant rounded-lg text-xs font-semibold hover:bg-surface-container-high">Update Label</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

</div>

<?= $this->endSection() ?>
