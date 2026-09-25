<?= $this->extend('layouts/marketplace') ?>
<?= $this->section('content') ?>

<style>
    .drag-active { border-color: #2563eb !important; background-color: #eeefff !important; }
</style>

<main class="max-w-container-max mx-auto px-4 md:px-8 lg:px-10 py-8 md:py-xl flex-grow w-full">

    <!-- Shop Header -->
    <section class="mb-lg sm:mb-xxl flex flex-col md:flex-row gap-md sm:gap-lg items-center md:items-center text-center md:text-left">

        <div class="w-20 h-20 sm:w-32 sm:h-32 md:w-48 md:h-48 rounded-full border-2 sm:border-4 border-white shadow-md overflow-hidden flex-shrink-0 bg-surface-container flex items-center justify-center relative">
            <?php $stLogo = logo_url($shop['logo_url'] ?? null); ?>
            <?php if (!empty($stLogo)): ?>
                <img class="w-full h-full object-cover" src="<?= esc($stLogo) ?>" alt="<?= esc($shop['shop_name']) ?> logo" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                <span class="hidden material-symbols-outlined text-primary text-3xl sm:text-6xl items-center justify-center w-full h-full">store</span>
            <?php else: ?>
                <span class="material-symbols-outlined text-primary text-3xl sm:text-6xl">store</span>
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

            <div class="mt-md flex items-center gap-sm flex-wrap">

                <?php if (!empty($isFavorite)): ?>
                    <form action="<?= base_url('customer/favorites/remove') ?>" method="POST" class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="shop_id" value="<?= (int) $shop['id'] ?>">
                        <button type="submit" class="px-lg py-sm bg-error-container text-error rounded-xl font-button text-button hover:bg-error-container/80 transition-all flex items-center gap-xs font-semibold shadow-sm">
                            <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">favorite</span>
                            Following Store
                        </button>
                    </form>
                <?php else: ?>
                    <form action="<?= base_url('customer/favorites/add') ?>" method="POST" class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="shop_id" value="<?= (int) $shop['id'] ?>">
                        <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded-xl font-button text-button hover:bg-primary-container transition-all flex items-center gap-xs font-semibold shadow-sm">
                            <span class="material-symbols-outlined text-[18px]">favorite_border</span>
                            Follow Store
                        </button>
                    </form>
                <?php endif; ?>

                <button type="button" id="btn-open-report-modal" class="px-md py-sm border border-outline-variant/60 text-outline hover:text-error hover:border-error/50 rounded-xl font-button text-button transition-all flex items-center gap-xs text-xs font-semibold">
                    <span class="material-symbols-outlined text-[16px]">flag</span>
                    Report Shop
                </button>

            </div>

        </div>

    </section>

    <?php if (!empty($businessHours)): ?>
        <?php
            $todayName = strtolower(date('l'));
            $currentStatusText = 'Open Today';
            $isOpenNow = true;
            foreach ($businessHours as $h) {
                $label = strtolower($h['label'] ?? '');
                if (str_contains($label, $todayName) || $label === 'daily') {
                    if (!empty($h['is_closed'])) {
                        $currentStatusText = 'Closed Today';
                        $isOpenNow = false;
                    } else {
                        $currentStatusText = 'Open Today • ' . ($h['open'] ?? '8:00 AM') . ' – ' . ($h['close'] ?? '5:00 PM');
                        $isOpenNow = true;
                    }
                    break;
                }
            }
        ?>
        <!-- Compact Business Hours Widget -->
        <section class="mb-xl max-w-xl bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-md shadow-sm">
            <div class="flex items-center justify-between cursor-pointer select-none" onclick="document.getElementById('full-business-hours').classList.toggle('hidden'); document.getElementById('hours-chevron').classList.toggle('rotate-180');">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full <?= $isOpenNow ? 'bg-green-500 animate-pulse' : 'bg-error' ?>"></span>
                    <span class="text-sm font-semibold text-on-surface"><?= esc($currentStatusText) ?></span>
                </div>
                <button type="button" class="text-xs font-semibold text-primary flex items-center gap-1 hover:underline">
                    <span>Weekly Schedule</span>
                    <span id="hours-chevron" class="material-symbols-outlined text-[18px] transition-transform duration-200">expand_more</span>
                </button>
            </div>

            <div id="full-business-hours" class="hidden mt-3 pt-3 border-t border-outline-variant/20 space-y-1.5 text-xs">
                <?php foreach ($businessHours as $hours): ?>
                    <div class="flex items-center justify-between py-1 text-on-surface-variant">
                        <span class="font-medium text-on-surface"><?= esc($hours['label']) ?></span>
                        <span class="<?= $hours['is_closed'] ? 'text-error font-medium' : '' ?>">
                            <?= $hours['is_closed'] ? 'Closed' : esc($hours['open'] . ' – ' . $hours['close']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Report Shop Modal -->
    <div id="report-shop-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden p-4">
        <div class="glass-card bg-surface-container-lowest border border-outline-variant/50 rounded-2xl p-6 w-full max-w-md shadow-2xl relative">
            <div class="flex items-center justify-between pb-3 border-b border-outline-variant/30 mb-4">
                <div class="flex items-center gap-2 text-error">
                    <span class="material-symbols-outlined text-2xl">report</span>
                    <h3 class="font-title-md font-bold text-on-surface">Report Shop</h3>
                </div>
                <button type="button" id="btn-close-report-modal" class="p-1 rounded-full text-outline hover:text-on-surface hover:bg-surface-container">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <form action="<?= base_url('customer/shop/report') ?>" method="POST" class="flex flex-col gap-4">
                <?= csrf_field() ?>
                <input type="hidden" name="shop_id" value="<?= (int) $shop['id'] ?>">

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-on-surface-variant" for="report-reason">Reason for Reporting <span class="text-error">*</span></label>
                    <select id="report-reason" name="issue_type" class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl p-2.5 text-sm font-medium text-on-surface focus:ring-2 focus:ring-primary focus:border-primary" required>
                        <option value="" disabled selected>Select a reason...</option>
                        <option value="Counterfeit/Fake Product">Counterfeit/Fake Product</option>
                        <option value="Item Not as Described">Item Not as Described</option>
                        <option value="Harassment/Abusive Behavior">Harassment/Abusive Behavior</option>
                        <option value="Scam/Fraud">Scam/Fraud</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-on-surface-variant" for="report-desc">Additional Details (Optional)</label>
                    <textarea id="report-desc" name="description" rows="3" class="w-full bg-surface-container-lowest border border-outline-variant rounded-xl p-2.5 text-sm text-on-surface focus:ring-2 focus:ring-primary placeholder:text-outline/50" placeholder="Provide extra details to help our team investigate..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-outline-variant/30">
                    <button type="button" id="btn-cancel-report" class="px-4 py-2 text-sm font-semibold text-on-surface-variant hover:bg-surface-container rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 text-sm font-semibold bg-error text-on-error rounded-xl shadow hover:bg-error/90 transition-all">
                        Submit Report
                    </button>
                </div>
            </form>
        </div>
    </div>

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

            <form action="<?= base_url('printing/request') ?>" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-12 gap-gutter items-start" id="printingRequestForm" onsubmit="return validatePrintingSubmit(event)">

                <?= csrf_field() ?>

                <input type="hidden" name="shop_id" value="<?= $shop['id'] ?>">
                <input type="hidden" name="page_count" id="pdf-page-count" value="0">

                <!-- Left Column: Compact Upload & Document Status (5 cols on Desktop) -->
                <div class="lg:col-span-5 space-y-md">

                    <!-- Document Format Selection Toggle -->
                    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-md shadow-2xs">
                        <label class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider mb-2 block">1. Select Document Format</label>
                        <div class="grid grid-cols-2 gap-sm">
                            <label class="cursor-pointer">
                                <input type="radio" name="document_type" value="pdf" id="docTypePdf" class="peer sr-only" checked onchange="toggleDocType('pdf')">
                                <span class="flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl text-xs font-bold border border-outline-variant/40 bg-surface-container-low text-on-surface transition-all peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary peer-checked:shadow-sm">
                                    <span class="material-symbols-outlined text-[17px]">picture_as_pdf</span> PDF Document
                                </span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="document_type" value="docx" id="docTypeDocx" class="peer sr-only" onchange="toggleDocType('docx')">
                                <span class="flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl text-xs font-bold border border-outline-variant/40 bg-surface-container-low text-on-surface transition-all peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary peer-checked:shadow-sm">
                                    <span class="material-symbols-outlined text-[17px]">description</span> Word (.doc, .docx)
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Compact Dropzone Card -->
                    <div class="bg-surface-container-lowest border-2 border-dashed border-outline-variant rounded-2xl p-lg min-h-[220px] max-h-[290px] flex flex-col items-center justify-center text-center transition-all hover:border-primary group cursor-pointer relative" id="dropzone">

                        <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary mb-sm group-hover:scale-110 group-hover:bg-primary group-hover:text-on-primary transition-all duration-200" id="dropzone-icon-box">
                            <span class="material-symbols-outlined text-2xl" id="dropzoneIcon">picture_as_pdf</span>
                        </div>

                        <h3 class="text-title-md font-bold text-on-surface mb-0.5" id="dropzoneTitle">Upload your PDF</h3>
                        <p class="text-on-surface-variant text-xs mb-md max-w-xs leading-relaxed" id="dropzoneDesc">Drag &amp; drop your document here, or click to browse files from your device.</p>

                        <input accept=".pdf" class="sr-only" id="fileInput" type="file" name="document">

                        <button type="button" id="selectPdfBtn" class="bg-primary text-on-primary px-lg py-2 rounded-xl font-button text-xs shadow-sm hover:shadow-md hover:bg-primary/90 transition-all flex items-center gap-xs">
                            <span class="material-symbols-outlined text-[18px]">upload_file</span>
                            <span id="selectBtnText">Select PDF Document</span>
                        </button>

                        <p class="mt-sm text-[11px] text-outline" id="dropzoneFooter">Max file size: 50MB &bull; Formats: PDF only</p>

                    </div>

                    <!-- Upload Verification Status Chip -->
                    <div class="bg-surface-container-low rounded-xl p-md border border-outline-variant/30 flex flex-col gap-xs" id="upload-status-container">

                        <div class="flex items-center justify-between gap-sm flex-wrap">
                            <span class="px-sm py-1 rounded-full text-xs font-semibold bg-surface-container-high text-on-surface-variant flex items-center gap-xs" id="upload-badge">
                                <span class="material-symbols-outlined text-[15px]">info</span>No file selected
                            </span>
                            <span class="text-xs font-bold text-on-surface-variant" id="page-count">No file selected</span>
                        </div>

                        <p class="text-xs text-on-surface-variant hidden mt-1" id="upload-hint">Page count will be verified automatically once your PDF is uploaded.</p>

                    </div>

                    <!-- DOCX Manual Page Count (Word documents) -->
                    <div id="docxPageCountContainer" class="hidden bg-surface-container-low rounded-xl p-md border border-outline-variant/30 space-y-1">
                        <label for="docxEstimatedPages" class="text-xs font-bold text-on-surface flex items-center justify-between">
                            <span>Estimated Page Count <span class="text-error">*</span></span>
                            <span class="text-[10px] text-outline font-normal">Enter total pages</span>
                        </label>
                        <input type="number" name="estimated_page_count" id="docxEstimatedPages" min="1" step="1" value="1" placeholder="e.g. 5" class="w-full py-2 px-3 bg-surface-container border border-outline-variant/40 rounded-xl text-xs font-bold text-on-surface focus:ring-1 focus:ring-primary focus:border-primary" oninput="handleManualPageCount(this.value)" onchange="handleManualPageCount(this.value)">
                        <p class="text-[11px] text-on-surface-variant">Word files require estimated page count for pricing calculations.</p>
                    </div>

                    <!-- DOCX Change Request & Reference Attachments -->
                    <div id="docxChangesContainer" class="hidden bg-surface-container-low rounded-xl p-md border border-outline-variant/30 space-y-md">
                        <div>
                            <label class="text-xs font-bold text-on-surface block mb-1">Printing Requirement</label>
                            <div class="grid grid-cols-2 gap-sm">
                                <label class="cursor-pointer">
                                    <input type="radio" name="doc_change_type" value="as_is" class="peer sr-only" checked onchange="toggleDocxChanges('as_is')">
                                    <span class="flex items-center justify-center gap-1.5 py-2 px-2 text-center rounded-xl text-xs font-bold border border-outline-variant/40 bg-surface-container text-on-surface transition-all peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary">
                                        <span class="material-symbols-outlined text-[16px]">print</span> Print As-Is
                                    </span>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="doc_change_type" value="has_changes" class="peer sr-only" onchange="toggleDocxChanges('has_changes')">
                                    <span class="flex items-center justify-center gap-1.5 py-2 px-2 text-center rounded-xl text-xs font-bold border border-outline-variant/40 bg-surface-container text-on-surface transition-all peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary">
                                        <span class="material-symbols-outlined text-[16px]">edit_note</span> With Changes
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Reference Photos for Changes -->
                        <div id="docxReferencePhotosContainer" class="hidden space-y-1.5">
                            <label class="text-xs font-bold text-on-surface flex items-center justify-between">
                                <span>Reference Photos / Markups</span>
                                <span class="text-[10px] text-outline font-normal">Max 5 images</span>
                            </label>
                            <input type="file" name="reference_photos[]" id="referencePhotosInput" multiple accept="image/*" class="w-full py-1.5 px-2 bg-surface-container border border-outline-variant/40 rounded-xl text-xs text-on-surface file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer">
                            <p class="text-[11px] text-on-surface-variant">Attach photos or screenshots showing edits requested.</p>

                            <!-- Live Photo Preview Gallery -->
                            <div id="refPhotosPreviewGrid" class="hidden grid grid-cols-3 sm:grid-cols-4 gap-2 pt-2 border-t border-outline-variant/20"></div>
                        </div>
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

                                        <select name="paper_size" id="paperSizeSelect" class="w-full py-2 px-3 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs font-medium focus:ring-2 focus:ring-primary focus:border-primary text-on-surface transition-all appearance-none pr-8">
                                            <?php if (!empty($paperSizes)): ?>
                                                <?php foreach ($paperSizes as $sKey => $sVal): ?>
                                                    <?php if (!empty($sVal['is_enabled'])): ?>
                                                        <option value="<?= esc($sKey) ?>" <?= $sKey === 'letter' ? 'selected' : '' ?>>
                                                            <?= esc($sVal['label'] ?? strtoupper($sKey)) ?>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="letter" selected>Letter (8.5" x 11")</option>
                                                <option value="legal">Legal (8.5" x 14")</option>
                                                <option value="a4">A4 (8.27" x 11.69")</option>
                                            <?php endif; ?>
                                        </select>

                                        <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[18px] text-outline pointer-events-none">expand_more</span>

                                    </div>

                                </div>

                                <div>

                                    <label class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider mb-1 block">Copies</label>

                                    <div class="flex items-center">
                                        <button type="button" id="btnCopiesMinus" class="w-9 h-9 flex items-center justify-center bg-surface-container border border-outline-variant/40 rounded-l-xl text-on-surface hover:bg-surface-container-high transition-colors active:scale-95 font-bold text-base select-none">−</button>
                                        <input type="number" name="copies" id="copiesInput" min="1" max="500" value="1" class="w-full py-2 px-2 text-center bg-surface-container-low border-y border-outline-variant/40 text-xs font-semibold text-on-surface focus:ring-1 focus:ring-primary focus:border-primary [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                        <button type="button" id="btnCopiesPlus" class="w-9 h-9 flex items-center justify-center bg-surface-container border border-outline-variant/40 rounded-r-xl text-on-surface hover:bg-surface-container-high transition-colors active:scale-95 font-bold text-base select-none">+</button>
                                    </div>

                                </div>

                            </div>

                            <!-- Color Mode -->
                            <div>
                                <?php
                                    $priceColor = (float) ($printingSettings['price_color_per_page'] ?? 5.00);
                                    $priceBw    = (float) ($printingSettings['price_bw_per_page'] ?? 2.00);
                                ?>

                                <label class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider mb-1 block">Color Mode</label>

                                <div class="grid grid-cols-2 gap-sm">

                                    <label class="cursor-pointer">

                                        <input type="radio" name="color_mode" value="colored" class="peer sr-only" checked>
                                        <div class="flex items-center justify-between gap-1.5 py-2 px-3 text-center rounded-xl text-xs font-bold border border-outline-variant/40 bg-surface-container-low text-on-surface transition-all peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary peer-checked:shadow-sm">
                                            <span class="flex items-center gap-1.5 truncate">
                                                <span class="material-symbols-outlined text-[16px] shrink-0">palette</span>
                                                <span class="truncate">Full Color</span>
                                            </span>
                                            <span class="text-[11px] font-semibold opacity-90 shrink-0">₱<?= number_format($priceColor, 2) ?>/page</span>
                                        </div>

                                    </label>

                                    <label class="cursor-pointer">

                                        <input type="radio" name="color_mode" value="black_white" class="peer sr-only">
                                        <div class="flex items-center justify-between gap-1.5 py-2 px-3 text-center rounded-xl text-xs font-bold border border-outline-variant/40 bg-surface-container-low text-on-surface transition-all peer-checked:bg-primary peer-checked:text-on-primary peer-checked:border-primary peer-checked:shadow-sm">
                                            <span class="flex items-center gap-1.5 truncate">
                                                <span class="material-symbols-outlined text-[16px] shrink-0">grayscale</span>
                                                <span class="truncate">Black &amp; White</span>
                                            </span>
                                            <span class="text-[11px] font-semibold opacity-90 shrink-0">₱<?= number_format($priceBw, 2) ?>/page</span>
                                        </div>

                                    </label>

                                </div>

                            </div>

                            <!-- Binding Options -->
                            <div>

                                <label class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider mb-1 block">Binding Options</label>

                                <div class="grid grid-cols-3 gap-sm">

                                    <label class="cursor-pointer">

                                        <input checked class="peer sr-only" name="binding" type="radio" value="none" data-cost="0">
                                        <div class="p-2 text-center rounded-xl border border-outline-variant/40 bg-surface-container-low hover:bg-surface-container transition-all peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-1 peer-checked:ring-primary flex flex-col items-center justify-center">
                                            <span class="text-xs font-bold text-on-surface block">No Binding</span>
                                            <span class="text-[11px] text-outline font-medium">Free</span>
                                        </div>

                                    </label>

                                    <label class="cursor-pointer">
                                        <?php $staplePrice = (float) ($printingSettings['price_staple'] ?? 10.00); ?>
                                        <input class="peer sr-only" name="binding" type="radio" value="stapled" data-cost="<?= $staplePrice ?>">
                                        <div class="p-2 text-center rounded-xl border border-outline-variant/40 bg-surface-container-low hover:bg-surface-container transition-all peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-1 peer-checked:ring-primary flex flex-col items-center justify-center">
                                            <span class="text-xs font-bold text-on-surface block">Stapled</span>
                                            <span class="text-[11px] text-primary font-bold"><?= $staplePrice > 0 ? ('+₱' . number_format($staplePrice, 2)) : 'Free' ?></span>
                                        </div>

                                    </label>

                                    <label class="cursor-pointer">
                                        <?php $spiralPrice = (float) ($printingSettings['price_spiral'] ?? 35.00); ?>
                                        <input class="peer sr-only" name="binding" type="radio" value="spiral" data-cost="<?= $spiralPrice ?>">
                                        <div class="p-2 text-center rounded-xl border border-outline-variant/40 bg-surface-container-low hover:bg-surface-container transition-all peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-1 peer-checked:ring-primary flex flex-col items-center justify-center">
                                            <span class="text-xs font-bold text-on-surface block">Spiral</span>
                                            <span class="text-[11px] text-primary font-bold">+₱<?= number_format($spiralPrice, 2) ?></span>
                                        </div>

                                    </label>

                                </div>

                            </div>

                            <!-- Fulfillment Method -->
                            <div>
                                <?php
                                    $shopOffersPickup   = !isset($shop['offers_pickup']) || (int)$shop['offers_pickup'] === 1;
                                    $shopOffersDelivery = !isset($shop['offers_delivery']) || (int)$shop['offers_delivery'] === 1;
                                    $defaultFulfillment = $shopOffersPickup ? 'pickup' : ($shopOffersDelivery ? 'delivery' : '');
                                ?>
                                <label class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider mb-1 block">Fulfillment Method</label>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-sm">

                                    <!-- Store Pick-up Option -->
                                    <label class="<?= $shopOffersPickup ? 'cursor-pointer' : 'cursor-not-allowed opacity-50 pointer-events-none' ?>" title="<?= $shopOffersPickup ? 'Store Pick-up' : 'Store Pick-up is not available for this shop' ?>">
                                        <input <?= ($defaultFulfillment === 'pickup') ? 'checked' : '' ?> <?= !$shopOffersPickup ? 'disabled' : '' ?> class="peer sr-only" name="fulfillment_method" type="radio" value="pickup">
                                        <div class="p-2.5 rounded-xl border border-outline-variant/40 bg-surface-container-low hover:bg-surface-container transition-all peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-1 peer-checked:ring-primary flex items-center gap-2 h-full">
                                            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                                <span class="material-symbols-outlined text-[18px]">storefront</span>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-on-surface truncate">Store Pick-up</p>
                                                <p class="text-[11px] text-outline truncate"><?= $shopOffersPickup ? 'Free at shop branch' : 'Service currently unavailable' ?></p>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- Doorstep Delivery Option -->
                                    <label class="<?= $shopOffersDelivery ? 'cursor-pointer' : 'cursor-not-allowed opacity-50 pointer-events-none' ?>" title="<?= $shopOffersDelivery ? 'Doorstep Delivery' : 'Doorstep Delivery is not available for this shop' ?>">
                                        <input <?= ($defaultFulfillment === 'delivery') ? 'checked' : '' ?> <?= !$shopOffersDelivery ? 'disabled' : '' ?> class="peer sr-only" name="fulfillment_method" type="radio" value="delivery">
                                        <div class="p-2.5 rounded-xl border border-outline-variant/40 bg-surface-container-low hover:bg-surface-container transition-all peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-1 peer-checked:ring-primary flex items-center gap-2 h-full">
                                            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                                <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-on-surface truncate">Doorstep Delivery</p>
                                                <p class="text-[11px] text-outline truncate"><?= $shopOffersDelivery ? 'Polomolok area only' : 'Service currently unavailable' ?></p>
                                            </div>
                                        </div>
                                    </label>

                                </div>

                                <?php if (!$shopOffersDelivery && $shopOffersPickup): ?>
                                    <div class="mt-2 p-2 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-300 text-xs flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[16px] text-amber-600 shrink-0">info</span>
                                        <span><strong>Paalala:</strong> Kasalukuyang walang Doorstep Delivery service ang tindahang ito. Store Pick-up lamang ang maaari.</span>
                                    </div>
                                <?php elseif (!$shopOffersPickup && $shopOffersDelivery): ?>
                                    <div class="mt-2 p-2 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-300 text-xs flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[16px] text-amber-600 shrink-0">info</span>
                                        <span><strong>Paalala:</strong> Kasalukuyang walang Store Pick-up service ang tindahang ito. Doorstep Delivery lamang ang maaari.</span>
                                    </div>
                                <?php elseif (!$shopOffersPickup && !$shopOffersDelivery): ?>
                                    <div class="mt-2 p-2 rounded-xl bg-error-container/20 border border-error/30 text-error text-xs flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[16px] shrink-0">warning</span>
                                        <span><strong>Paalala:</strong> Kasalukuyang sarado ang lahat ng fulfillment services ng tindahang ito.</span>
                                    </div>
                                <?php endif; ?>

                            </div>

                            <!-- Payment Method -->
                            <div>
                                <?php $dpPercent = (float) ($printingSettings['down_payment_percent'] ?? 50.00); ?>
                                <label class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider mb-1 block">Payment Method</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-sm" id="prPaymentMethodGroup">
                                    <label class="cursor-pointer">
                                        <input checked class="peer sr-only" name="payment_method" type="radio" value="gcash" id="payMethodGcash">
                                        <div class="p-2.5 rounded-xl border border-outline-variant/40 bg-surface-container-low hover:bg-surface-container transition-all peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-1 peer-checked:ring-primary flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-lg bg-[#007DFE]/10 text-[#007DFE] flex items-center justify-center shrink-0">
                                                <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-on-surface truncate">GCash Online</p>
                                                <p class="text-[11px] text-outline truncate"><?= round($dpPercent) ?>% Down Payment</p>
                                            </div>
                                        </div>
                                    </label>

                                    <label class="cursor-pointer" id="payMethodPickupLabel">
                                        <input class="peer sr-only" name="payment_method" type="radio" value="pickup" id="payMethodPickup">
                                        <div class="p-2.5 rounded-xl border border-outline-variant/40 bg-surface-container-low hover:bg-surface-container transition-all peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-1 peer-checked:ring-primary flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-700 flex items-center justify-center shrink-0">
                                                <span class="material-symbols-outlined text-[18px]">storefront</span>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-on-surface truncate">Pay at Counter</p>
                                                <p class="text-[11px] text-outline truncate">Upon Store Pick-up</p>
                                            </div>
                                        </div>
                                    </label>

                                    <label class="cursor-pointer hidden" id="payMethodCodLabel">
                                        <input class="peer sr-only" name="payment_method" type="radio" value="cod" id="payMethodCod">
                                        <div class="p-2.5 rounded-xl border border-outline-variant/40 bg-surface-container-low hover:bg-surface-container transition-all peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-1 peer-checked:ring-primary flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                                                <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-on-surface truncate">Cash on Delivery</p>
                                                <p class="text-[11px] text-outline truncate">Pay upon Arrival</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Special Instructions -->
                            <div>

                                <label class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider mb-1 block" id="notesLabel">Special Instructions (Optional)</label>
                                <textarea name="notes" id="printingNotes" rows="2" placeholder="e.g. Back-to-back, page range to print, specific paper color..." class="w-full p-2.5 bg-surface-container-low border border-outline-variant/40 rounded-xl text-xs focus:ring-2 focus:ring-primary focus:border-primary text-on-surface"></textarea>

                            </div>

                            <!-- Pricing Breakdown & Down Payment -->
                            <?php $dpPercent = (float) ($printingSettings['down_payment_percent'] ?? 50.00); ?>
                            <div class="pt-sm border-t border-outline-variant/20 space-y-sm">

                                <div class="bg-surface-container-low p-md rounded-xl space-y-xs border border-outline-variant/20">

                                    <div class="flex justify-between items-center text-xs text-on-surface-variant">
                                        <div>
                                            <span>Total Printing Price:</span>
                                            <span class="text-[11px] text-outline block font-normal" id="printing-rate-helper"></span>
                                        </div>
                                        <span class="text-sm font-bold text-on-surface" id="printing-total-price">₱0.00</span>
                                    </div>

                                    <div class="flex justify-between items-center text-sm font-bold border-t border-outline-variant/20 pt-xs" id="prDownPaymentRow">
                                        <span class="text-on-surface flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[17px] text-[#007DFE]">account_balance_wallet</span>
                                            <span id="dpPercentLabel"><?= round($dpPercent) ?>%</span> Down Payment:
                                        </span>
                                        <span class="text-title-md font-bold text-[#007DFE]" id="printing-down-payment">₱0.00</span>
                                    </div>

                                </div>

                                <!-- Trust & Security Subtext -->
                                <div class="flex items-center gap-2 px-1 text-[11px] text-on-surface-variant">
                                    <span class="material-symbols-outlined text-[16px] text-[#007DFE]">verified_user</span>
                                    <span id="prTrustSubtext">Pay <?= round($dpPercent) ?>% now via GCash / PayMongo. Remaining balance paid on pickup or delivery.</span>
                                </div>

                                <!-- Primary Submit Button -->
                                <button type="submit" id="btnSubmitPrinting" class="w-full py-3 bg-[#007DFE] hover:bg-[#006bd6] text-white rounded-xl font-button text-sm shadow-md hover:shadow-lg active:scale-95 transition-all flex items-center justify-center gap-2">

                                    <span class="material-symbols-outlined text-[18px]">payments</span>
                                    <span id="submitBtnText">Pay <?= round($dpPercent) ?>% Down Payment via GCash</span>

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

        <div class="grid grid-cols-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-gutter">

            <?php if (!empty($products)): ?>

                <?php foreach ($products as $p): ?>

                    <div class="group bg-surface-container-lowest rounded-xl overflow-hidden border border-outline-variant/30 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">

                        <a href="<?= base_url('product/' . $p['id']) ?>" class="block relative aspect-square overflow-hidden bg-surface-container">

                            <img class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" src="<?= esc(product_image_url($p['image_url'] ?? null)) ?>" alt="<?= esc($p['name']) ?>">

                            <?php if (!empty($p['is_bestseller'])): ?>

                                <span class="absolute top-1 right-1 sm:top-md sm:right-md bg-primary-container text-on-primary-container px-1.5 py-0.5 sm:px-sm sm:py-xs rounded-full text-[9px] sm:text-label-sm font-label-sm shadow-sm">Hot</span>

                            <?php endif; ?>

                        </a>

                        <div class="p-1.5 sm:p-md flex flex-col flex-grow justify-between">

                            <div>
                                <h3 class="text-[11px] sm:text-title-lg font-bold mb-0.5 sm:mb-xs group-hover:text-primary transition-colors line-clamp-2 leading-tight"><a href="<?= base_url('product/' . $p['id']) ?>"><?= esc($p['name']) ?></a></h3>
                                <p class="text-on-surface-variant text-label-sm font-label-sm mb-md line-clamp-1 hidden sm:block"><?= esc($p['description'] ?? '') ?></p>
                            </div>

                            <div class="flex justify-between items-center mt-1 sm:mt-md pt-1 border-t border-outline-variant/10">

                                <span class="text-xs sm:text-headline-md font-bold text-primary">₱<?= number_format($p['price'], 2) ?></span>

                                <form action="<?= base_url('cart/add') ?>" method="POST">

                                    <?= csrf_field() ?>

                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">

                                    <button type="submit" class="w-6 h-6 sm:w-10 sm:h-10 rounded-md sm:rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center hover:bg-primary hover:text-on-primary transition-colors" aria-label="Add to cart">

                                        <span class="material-symbols-outlined text-[13px] sm:text-base">add_shopping_cart</span>

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

function isValidFile(file) {
    if (!file) return false;
    const isDocx = document.getElementById('docTypeDocx')?.checked;
    if (isDocx) {
        return /\.(docx|doc)$/i.test(file.name);
    }
    return /\.pdf$/i.test(file.name);
}

function toggleDocType(type) {
    const isDocx = type === 'docx';
    const dropzoneTitle = document.getElementById('dropzoneTitle');
    const dropzoneDesc = document.getElementById('dropzoneDesc');
    const dropzoneFooter = document.getElementById('dropzoneFooter');
    const selectBtnText = document.getElementById('selectBtnText');
    const dropzoneIcon = document.getElementById('dropzoneIcon');
    const fileInput = document.getElementById('fileInput');
    const docxPageCountContainer = document.getElementById('docxPageCountContainer');
    const docxChangesContainer = document.getElementById('docxChangesContainer');
    const hiddenPageCount = document.getElementById('pdf-page-count');
    const badge = document.getElementById('upload-badge');
    const pageCount = document.getElementById('page-count');

    // Reset current file input
    if (fileInput) fileInput.value = '';

    if (isDocx) {
        if (fileInput) fileInput.setAttribute('accept', '.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        if (dropzoneTitle) dropzoneTitle.textContent = 'Upload your Word Document';
        if (dropzoneDesc) dropzoneDesc.textContent = 'Drag & drop your .doc or .docx file here, or click to browse.';
        if (dropzoneFooter) dropzoneFooter.textContent = 'Max file size: 50MB • Formats: DOC, DOCX';
        if (selectBtnText) selectBtnText.textContent = 'Select Word Document';
        if (dropzoneIcon) dropzoneIcon.textContent = 'description';

        if (docxPageCountContainer) docxPageCountContainer.classList.remove('hidden');
        if (docxChangesContainer) docxChangesContainer.classList.remove('hidden');

        const estInput = document.getElementById('docxEstimatedPages');
        let estPages = parseInt(estInput?.value || '0', 10);
        if (estPages <= 0) {
            estPages = 1;
            if (estInput) estInput.value = '1';
        }
        if (hiddenPageCount) hiddenPageCount.value = estPages;
        if (badge) {
            badge.className = 'px-sm py-0.5 rounded-full text-label-sm font-medium bg-surface-container-high text-on-surface-variant flex items-center gap-xs';
            const icon = badge.querySelector('.material-symbols-outlined');
            if (icon) icon.textContent = 'description';
            badge.lastChild.textContent = ' Word Document selected';
        }
        if (pageCount) {
            pageCount.className = 'text-label-sm font-medium text-primary';
            pageCount.textContent = estPages + ' estimated page' + (estPages === 1 ? '' : 's');
        }
    } else {
        if (fileInput) fileInput.setAttribute('accept', '.pdf');
        if (dropzoneTitle) dropzoneTitle.textContent = 'Upload your PDF';
        if (dropzoneDesc) dropzoneDesc.textContent = 'Drag & drop your document here, or click to browse files from your device.';
        if (dropzoneFooter) dropzoneFooter.textContent = 'Max file size: 50MB • Formats: PDF only';
        if (selectBtnText) selectBtnText.textContent = 'Select PDF Document';
        if (dropzoneIcon) dropzoneIcon.textContent = 'picture_as_pdf';

        if (docxPageCountContainer) docxPageCountContainer.classList.add('hidden');
        if (docxChangesContainer) docxChangesContainer.classList.add('hidden');
        const refContainer = document.getElementById('docxReferencePhotosContainer');
        if (refContainer) refContainer.classList.add('hidden');

        if (hiddenPageCount) hiddenPageCount.value = 0;
        setUploadState('empty');
        toggleDocxChanges('as_is');
    }

    updatePrintingPrice();
}

function toggleDocxChanges(mode) {
    const hasChanges = mode === 'has_changes';
    const photosContainer = document.getElementById('docxReferencePhotosContainer');
    const notesLabel = document.getElementById('notesLabel');
    const notesTextarea = document.getElementById('printingNotes');

    if (hasChanges) {
        if (photosContainer) photosContainer.classList.remove('hidden');
        if (notesLabel) notesLabel.innerHTML = 'Special Instructions / Change Details <span class="text-error font-bold">*</span>';
        if (notesTextarea) notesTextarea.placeholder = 'Please describe in detail the exact changes, edits, or formatting adjustments to make before printing...';
    } else {
        if (photosContainer) photosContainer.classList.add('hidden');
        if (notesLabel) notesLabel.innerHTML = 'Special Instructions (Optional)';
        if (notesTextarea) notesTextarea.placeholder = 'e.g. Back-to-back, page range to print, specific paper color...';
    }
}

function handleManualPageCount(val) {
    const pages = Math.max(1, parseInt(val, 10) || 1);
    const hiddenPageCount = document.getElementById('pdf-page-count');
    const pageCount = document.getElementById('page-count');
    if (hiddenPageCount) hiddenPageCount.value = pages;
    if (pageCount) {
        pageCount.className = 'text-label-sm font-medium text-primary';
        pageCount.textContent = pages + ' estimated page' + (pages === 1 ? '' : 's');
    }
    updatePrintingPrice();
}

function validatePrintingSubmit(e) {
    const isDocx = document.getElementById('docTypeDocx')?.checked;
    const fileInput = document.getElementById('fileInput');
    const hiddenPageCount = document.getElementById('pdf-page-count');
    const pages = parseInt(hiddenPageCount?.value || '0', 10);

    if (!fileInput.files || fileInput.files.length === 0) {
        alert('Please attach a document file before submitting.');
        e.preventDefault();
        return false;
    }

    if (pages <= 0) {
        if (isDocx) {
            alert('Please enter an estimated page count of at least 1 for your Word document.');
            document.getElementById('docxEstimatedPages')?.focus();
        } else {
            alert('Please wait for the PDF page count to be verified or upload a valid PDF.');
        }
        e.preventDefault();
        return false;
    }

    if (isDocx) {
        const changeRadio = document.querySelector('input[name="doc_change_type"]:checked');
        if (changeRadio && changeRadio.value === 'has_changes') {
            const notes = (document.getElementById('printingNotes')?.value || '').trim();
            if (notes === '') {
                alert('Special Instructions / Change Details are required when requesting document changes.');
                document.getElementById('printingNotes')?.focus();
                e.preventDefault();
                return false;
            }
        }
    }

    const payMethodVal = document.querySelector('input[name="payment_method"]:checked')?.value || 'gcash';
    const btn = document.getElementById('btnSubmitPrinting');
    if (btn) {
        btn.disabled = true;
        btn.classList.add('opacity-70');
        const btnText = document.getElementById('submitBtnText');
        if (btnText) btnText.textContent = payMethodVal === 'gcash' ? 'Connecting to PayMongo...' : 'Submitting Request...';
    }

    return true;
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
        const isDocx = document.getElementById('docTypeDocx')?.checked;

        if (!isValidFile(file)) {
            const errMsg = isDocx
                ? 'Invalid file type. Only Word documents (.doc, .docx) are accepted.'
                : 'Invalid file type. Only PDF documents are accepted.';
            setUploadState('error', errMsg);
            showPdfModal('error', 'Upload Failed', errMsg);
            return;
        }

        try {
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
        } catch (err) {}

        if (isDocx) {
            if (badge) {
                badge.className = 'px-sm py-0.5 rounded-full text-label-sm font-medium bg-green-100 text-green-700 flex items-center gap-xs';
                const icon = badge.querySelector('.material-symbols-outlined');
                if (icon) icon.textContent = 'check_circle';
                badge.lastChild.textContent = ' Word Document Attached';
            }

            const formData = new FormData();
            formData.append('document', file, file.name);

            fetch('<?= base_url('printing/count-pages') ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                const estInput = document.getElementById('docxEstimatedPages');
                let count = 1;
                if (data && data.success && data.page_count > 0) {
                    count = data.page_count;
                } else if (estInput && parseInt(estInput.value, 10) > 0) {
                    count = parseInt(estInput.value, 10);
                }
                if (estInput) estInput.value = count;
                if (hiddenPageCount) hiddenPageCount.value = count;
                if (pageCount) {
                    pageCount.className = 'text-label-sm font-medium text-primary';
                    pageCount.textContent = count + ' page' + (count === 1 ? '' : 's') + ' detected';
                }
                updatePrintingPrice();
                showPdfModal('success', 'Word Document Uploaded', 'Your document was verified and page count was calculated.', file.name, count);
            })
            .catch(() => {
                const estInput = document.getElementById('docxEstimatedPages');
                let estVal = parseInt(estInput?.value || '1', 10);
                if (estVal <= 0) estVal = 1;
                if (estInput) estInput.value = estVal;
                if (hiddenPageCount) hiddenPageCount.value = estVal;
                if (pageCount) {
                    pageCount.className = 'text-label-sm font-medium text-primary';
                    pageCount.textContent = estVal + ' estimated page' + (estVal === 1 ? '' : 's');
                }
                updatePrintingPrice();
                showPdfModal('success', 'Word Document Uploaded', 'Document attached. Please verify your estimated page count below.', file.name, estVal);
            });
            return;
        }

        // PDF Document handling
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
            const pageManualContainer = document.getElementById('docxPageCountContainer');
            if (ok && data && data.success && data.page_count > 0) {
                if (pageManualContainer && !document.getElementById('docTypeDocx')?.checked) {
                    pageManualContainer.classList.add('hidden');
                }
                setUploadState('ready', data.page_count);
                updatePrintingPrice();
                showPdfModal('success', 'PDF Uploaded Successfully', 'Your document was verified and page count was calculated.', file.name, data.page_count);
            } else if (data && data.can_manual) {
                if (pageManualContainer) {
                    pageManualContainer.classList.remove('hidden');
                    const label = pageManualContainer.querySelector('label span:first-child');
                    if (label) label.innerHTML = 'Confirm Total PDF Pages <span class="text-error">*</span>';
                }
                const fallbackPages = Math.max(1, parseInt(document.getElementById('docxEstimatedPages')?.value || '1', 10));
                setUploadState('ready', fallbackPages);
                updatePrintingPrice();
                showPdfModal('success', 'PDF Attached', 'Document attached. Please confirm your total page count below.', file.name, fallbackPages);
            } else {
                const msg = (data && data.error) ? data.error : 'Unable to determine page count.';
                setUploadState('error', msg);
                updatePrintingPrice();
                showPdfModal('error', 'PDF Upload Failed', msg);
            }
        })
        .catch(() => {
            const pageManualContainer = document.getElementById('docxPageCountContainer');
            if (pageManualContainer) {
                pageManualContainer.classList.remove('hidden');
                const label = pageManualContainer.querySelector('label span:first-child');
                if (label) label.innerHTML = 'Confirm Total PDF Pages <span class="text-error">*</span>';
            }
            const fallbackPages = Math.max(1, parseInt(document.getElementById('docxEstimatedPages')?.value || '1', 10));
            setUploadState('ready', fallbackPages);
            updatePrintingPrice();
            showPdfModal('success', 'PDF Attached', 'Document attached. Please confirm your total page count below.', file.name, fallbackPages);
        });
    }

    function updatePrintingPrice() {
        const globalPriceColor = <?= (float) ($printingSettings['price_color_per_page'] ?? 5.00) ?>;
        const globalPriceBw    = <?= (float) ($printingSettings['price_bw_per_page'] ?? 2.00) ?>;

        const isDocx = document.getElementById('docTypeDocx')?.checked;
        let pages = parseInt(hiddenPageCount ? hiddenPageCount.value : 0, 10) || 0;
        if (isDocx && pages <= 0) {
            const estInput = document.getElementById('docxEstimatedPages');
            pages = parseInt(estInput?.value || '1', 10);
            if (pages <= 0) pages = 1;
            if (hiddenPageCount) hiddenPageCount.value = pages;
        }

        const colorRadio = document.querySelector('input[name="color_mode"]:checked');
        const isColored = colorRadio ? (colorRadio.value === 'colored') : true;

        const copiesInput = document.getElementById('copiesInput') || document.querySelector('input[name="copies"]');
        let rawCopies = parseInt(copiesInput ? copiesInput.value : 1, 10);
        if (isNaN(rawCopies) || rawCopies < 1) rawCopies = 1;
        if (rawCopies > 500) rawCopies = 500;
        const copies = rawCopies;

        const bindingRadio = document.querySelector('input[name="binding"]:checked');
        const bindingCost = parseFloat(bindingRadio ? (bindingRadio.dataset.cost || 0) : 0);

        const downPaymentPercent = <?= (float) ($printingSettings['down_payment_percent'] ?? 50.00) ?>;

        if (pages <= 0) {
            const elTotal = document.getElementById('printing-total-price');
            const elDown = document.getElementById('printing-down-payment');
            const elHelper = document.getElementById('printing-rate-helper');
            if (elTotal) elTotal.textContent = '₱0.00';
            if (elDown) elDown.textContent = '₱0.00';
            if (elHelper) elHelper.textContent = '';
            return;
        }

        // Global rates based on color mode only
        const basePerPage = isColored ? globalPriceColor : globalPriceBw;

        let total = ((pages * basePerPage) + bindingCost) * copies;
        let downPayment = total * (downPaymentPercent / 100.00);

        const elTotal = document.getElementById('printing-total-price');
        const elDown = document.getElementById('printing-down-payment');
        const elHelper = document.getElementById('printing-rate-helper');
        if (elTotal) elTotal.textContent = '₱' + total.toFixed(2);
        if (elDown) elDown.textContent = '₱' + downPayment.toFixed(2);
        if (elHelper) {
            let helper = `${pages} page${pages > 1 ? 's' : ''} × ₱${basePerPage.toFixed(2)} (${isColored ? 'Full Color' : 'B&W'})`;
            if (bindingCost > 0) helper += ` + ₱${bindingCost.toFixed(2)} binding`;
            if (copies > 1) helper += ` × ${copies} copies`;
            elHelper.textContent = helper;
        }

        updatePrintingPaymentUI();
    }

    function updatePrintingPaymentUI() {
        const fulfillmentVal = document.querySelector('input[name="fulfillment_method"]:checked')?.value || 'pickup';
        const pickupPayLabel = document.getElementById('payMethodPickupLabel');
        const codPayLabel = document.getElementById('payMethodCodLabel');
        const payPickupInput = document.getElementById('payMethodPickup');
        const payCodInput = document.getElementById('payMethodCod');
        const payGcashInput = document.getElementById('payMethodGcash');

        if (fulfillmentVal === 'delivery') {
            if (pickupPayLabel) pickupPayLabel.classList.add('hidden');
            if (codPayLabel) codPayLabel.classList.remove('hidden');
            if (payPickupInput && payPickupInput.checked && payCodInput) {
                payCodInput.checked = true;
            }
        } else {
            if (pickupPayLabel) pickupPayLabel.classList.remove('hidden');
            if (codPayLabel) codPayLabel.classList.add('hidden');
            if (payCodInput && payCodInput.checked && payPickupInput) {
                payPickupInput.checked = true;
            }
        }

        const payMethodVal = document.querySelector('input[name="payment_method"]:checked')?.value || 'gcash';
        const btn = document.getElementById('btnSubmitPrinting');
        const btnText = document.getElementById('submitBtnText');
        const subtext = document.getElementById('prTrustSubtext');
        const dpRow = document.getElementById('prDownPaymentRow');
        const dpPercent = <?= (float) ($printingSettings['down_payment_percent'] ?? 50.00) ?>;

        if (payMethodVal === 'gcash') {
            if (dpRow) dpRow.classList.remove('hidden');
            if (btn) btn.className = 'w-full py-3 bg-[#007DFE] hover:bg-[#006bd6] text-white rounded-xl font-button text-sm shadow-md hover:shadow-lg active:scale-95 transition-all flex items-center justify-center gap-2';
            if (btnText) btnText.textContent = 'Pay ' + Math.round(dpPercent) + '% Down Payment via GCash';
            if (subtext) subtext.textContent = 'Pay ' + Math.round(dpPercent) + '% now via GCash / PayMongo. Remaining balance paid on pickup or delivery.';
        } else if (payMethodVal === 'pickup') {
            if (dpRow) dpRow.classList.add('hidden');
            if (btn) btn.className = 'w-full py-3 bg-primary hover:bg-primary/90 text-on-primary rounded-xl font-button text-sm shadow-md hover:shadow-lg active:scale-95 transition-all flex items-center justify-center gap-2';
            if (btnText) btnText.textContent = 'Submit Printing Request (Pay at Counter)';
            if (subtext) subtext.textContent = 'No online payment required now. Pay total upon picking up at the store.';
        } else {
            if (dpRow) dpRow.classList.add('hidden');
            if (btn) btn.className = 'w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-button text-sm shadow-md hover:shadow-lg active:scale-95 transition-all flex items-center justify-center gap-2';
            if (btnText) btnText.textContent = 'Submit Printing Request (Cash on Delivery)';
            if (subtext) subtext.textContent = 'No online payment required now. Payment will be collected upon doorstep delivery.';
        }
    }

    document.querySelectorAll('input[name="payment_method"], input[name="fulfillment_method"]').forEach(r => {
        r.addEventListener('change', updatePrintingPaymentUI);
    });

    const copiesInput = document.getElementById('copiesInput');
    const btnMinus = document.getElementById('btnCopiesMinus');
    const btnPlus  = document.getElementById('btnCopiesPlus');

    if (btnMinus && copiesInput) {
        btnMinus.addEventListener('click', () => {
            let val = parseInt(copiesInput.value, 10) || 1;
            if (val > 1) {
                copiesInput.value = val - 1;
                updatePrintingPrice();
            }
        });
    }

    if (btnPlus && copiesInput) {
        btnPlus.addEventListener('click', () => {
            let val = parseInt(copiesInput.value, 10) || 1;
            if (val < 500) {
                copiesInput.value = val + 1;
                updatePrintingPrice();
            }
        });
    }

    if (copiesInput) {
        copiesInput.addEventListener('input', () => {
            let val = parseInt(copiesInput.value, 10);
            if (!isNaN(val)) {
                if (val < 1) copiesInput.value = 1;
                if (val > 500) copiesInput.value = 500;
            }
            updatePrintingPrice();
        });
        copiesInput.addEventListener('blur', () => {
            let val = parseInt(copiesInput.value, 10);
            if (isNaN(val) || val < 1) copiesInput.value = 1;
            if (val > 500) copiesInput.value = 500;
            updatePrintingPrice();
        });
    }

    document.querySelectorAll('input[name="color_mode"], select[name="paper_size"], input[name="copies"], input[name="binding"]').forEach(el => {
        el.addEventListener('change', updatePrintingPrice);
    });

    const docxPagesInput = document.getElementById('docxEstimatedPages');
    if (docxPagesInput) {
        docxPagesInput.addEventListener('input', function() {
            handleManualPageCount(this.value);
        });
        docxPagesInput.addEventListener('change', function() {
            handleManualPageCount(this.value);
        });
    }

    // Reference Photos Thumbnail Preview
    const refPhotosInput = document.getElementById('referencePhotosInput');
    if (refPhotosInput) {
        refPhotosInput.addEventListener('change', function() {
            const previewContainer = document.getElementById('refPhotosPreviewGrid');
            if (!previewContainer) return;
            previewContainer.innerHTML = '';
            if (this.files && this.files.length > 0) {
                Array.from(this.files).forEach((f, idx) => {
                    if (!f.type.startsWith('image/')) return;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const thumb = document.createElement('div');
                        thumb.className = 'relative group aspect-square rounded-xl overflow-hidden border border-outline-variant/40 bg-surface-container shadow-2xs';
                        thumb.innerHTML = `
                            <img src="${e.target.result}" alt="${f.name}" class="w-full h-full object-cover">
                            <span class="absolute bottom-0 inset-x-0 bg-black/70 text-white text-[9px] px-1 py-0.5 truncate text-center">${f.name}</span>
                        `;
                        previewContainer.appendChild(thumb);
                    };
                    reader.readAsDataURL(f);
                });
                previewContainer.classList.remove('hidden');
            } else {
                previewContainer.classList.add('hidden');
            }
        });
    }

    // Report Shop Modal Handlers
    const reportModal = document.getElementById('report-shop-modal');
    const btnOpenReport = document.getElementById('btn-open-report-modal');
    const btnCloseReport = document.getElementById('btn-close-report-modal');
    const btnCancelReport = document.getElementById('btn-cancel-report');

    if (btnOpenReport && reportModal) {
        btnOpenReport.addEventListener('click', () => reportModal.classList.remove('hidden'));
    }
    if (btnCloseReport && reportModal) {
        btnCloseReport.addEventListener('click', () => reportModal.classList.add('hidden'));
    }
    if (btnCancelReport && reportModal) {
        btnCancelReport.addEventListener('click', () => reportModal.classList.add('hidden'));
    }
    if (reportModal) {
        reportModal.addEventListener('click', (e) => {
            if (e.target === reportModal) reportModal.classList.add('hidden');
        });
    }
}
</script>

<?= $this->endSection() ?>
