<?php
    $sc = $cms ?? $siteContents ?? [];
    $contactEmail = trim((string) ($sc['footer_contact_email']['text_value'] ?? 'support@blaxinventory.com'));
    $contactPhone = trim((string) ($sc['footer_phone']['text_value'] ?? '+63 917 123 4567'));
    $contactAddress = trim((string) ($sc['footer_address']['text_value'] ?? 'Poblacion, Polomolok, South Cotabato, Philippines 9504'));
    $copyrightText = trim((string) ($sc['footer_copyright']['text_value'] ?? ''));
    if ($copyrightText === '') {
        $copyrightText = '© ' . date('Y') . ' Blax. All rights reserved.';
    }
?>
<footer class="bg-surface-container-highest border-t border-outline-variant/50 w-full mt-auto">

    <div class="w-full py-8 md:py-12 lg:py-[80px] px-4 md:px-8 lg:px-10 max-w-container-max mx-auto flex flex-col md:flex-row justify-between gap-8 md:gap-xl">

        <div class="flex flex-col gap-sm max-w-sm">

            <div class="flex items-center gap-sm mb-xs">
                <img src="<?= base_url('icon.png') ?>" alt="Blax" class="w-8 h-8 rounded-lg object-contain">
                <a class="text-title-lg font-display font-bold text-on-surface" href="<?= base_url('/') ?>">Blax</a>
            </div>
            <p class="text-body-md font-body-md text-on-surface-variant">A modern high-trust ecosystem for professional merchants and enterprise service providers.</p>

            <div class="flex gap-lg mt-md">

                <a href="#" class="w-10 h-10 rounded-full bg-surface-container-low flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors"><span class="material-symbols-outlined">public</span></a>
                <a href="#" class="w-10 h-10 rounded-full bg-surface-container-low flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors"><span class="material-symbols-outlined">shield</span></a>
                <a href="#" class="w-10 h-10 rounded-full bg-surface-container-low flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors"><span class="material-symbols-outlined">verified</span></a>

            </div>

        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 lg:gap-xl">

            <!-- Quick Links -->
            <div class="flex flex-col gap-sm">

                <h4 class="text-body-md font-semibold text-on-surface mb-xs">Quick Links</h4>

                <nav class="flex flex-col gap-xs">

                    <a class="text-label-sm text-on-surface-variant hover:text-primary underline" href="<?= base_url('categories') ?>">Categories</a>
                    <a class="text-label-sm text-on-surface-variant hover:text-primary underline" href="<?= base_url('printing-services') ?>">Printing Services</a>
                    <a class="text-label-sm text-on-surface-variant hover:text-primary underline" href="<?= base_url('shops') ?>">Shops</a>
                    <a class="text-label-sm text-on-surface-variant hover:text-primary underline" href="<?= base_url('signup/merchant') ?>">Merchant Portal</a>

                </nav>

            </div>

            <!-- Support -->
            <div class="flex flex-col gap-sm">

                <h4 class="text-body-md font-semibold text-on-surface mb-xs">Support</h4>

                <nav class="flex flex-col gap-xs">

                    <a class="text-label-sm text-on-surface-variant hover:text-primary underline" href="#">Contact Support</a>
                    <a class="text-label-sm text-on-surface-variant hover:text-primary underline" href="#">Terms of Service</a>
                    <a class="text-label-sm text-on-surface-variant hover:text-primary underline" href="#">Privacy Policy</a>

                </nav>

            </div>

            <!-- Contact & Office Info (CMS Managed) -->
            <div class="flex flex-col gap-sm">

                <h4 class="text-body-md font-semibold text-on-surface mb-xs">Contact Us</h4>

                <div class="flex flex-col gap-2.5 text-label-sm text-on-surface-variant">

                    <?php if (!empty($contactEmail)): ?>
                        <a href="mailto:<?= esc($contactEmail) ?>" class="inline-flex items-center gap-2 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-[16px] text-primary shrink-0">mail</span>
                            <span class="truncate"><?= esc($contactEmail) ?></span>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($contactPhone)): ?>
                        <a href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $contactPhone)) ?>" class="inline-flex items-center gap-2 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-[16px] text-primary shrink-0">call</span>
                            <span><?= esc($contactPhone) ?></span>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($contactAddress)): ?>
                        <div class="inline-flex items-start gap-2">
                            <span class="material-symbols-outlined text-[16px] text-primary shrink-0 mt-0.5">location_on</span>
                            <span class="leading-relaxed"><?= esc($contactAddress) ?></span>
                        </div>
                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

    <div class="w-full py-lg border-t border-outline-variant/30 text-center px-4">

        <p class="text-label-sm text-on-surface-variant"><?= esc($copyrightText) ?></p>

    </div>

</footer>