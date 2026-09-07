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

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-xl">

            <div class="flex flex-col gap-sm">

                <h4 class="text-body-md font-semibold text-on-surface mb-xs">Quick Links</h4>

                <nav class="flex flex-col gap-xs">

                    <a class="text-label-sm text-on-surface-variant hover:text-primary underline" href="<?= base_url('categories') ?>">Categories</a>
                    <a class="text-label-sm text-on-surface-variant hover:text-primary underline" href="<?= base_url('printing-services') ?>">Printing Services</a>
                    <a class="text-label-sm text-on-surface-variant hover:text-primary underline" href="<?= base_url('shops') ?>">Shops</a>
                    <a class="text-label-sm text-on-surface-variant hover:text-primary underline" href="<?= base_url('signup/merchant') ?>">Merchant Portal</a>

                </nav>

            </div>

            <div class="flex flex-col gap-sm">

                <h4 class="text-body-md font-semibold text-on-surface mb-xs">Support</h4>

                <nav class="flex flex-col gap-xs">

                    <a class="text-label-sm text-on-surface-variant hover:text-primary underline" href="#">Contact Support</a>
                    <a class="text-label-sm text-on-surface-variant hover:text-primary underline" href="#">Terms of Service</a>
                    <a class="text-label-sm text-on-surface-variant hover:text-primary underline" href="#">Privacy Policy</a>

                </nav>

            </div>

        </div>

    </div>

    <div class="w-full py-lg border-t border-outline-variant/30 text-center">

        <p class="text-label-sm text-on-surface-variant">© <?= date('Y') ?> Blax. All rights reserved.</p>

    </div>

</footer>