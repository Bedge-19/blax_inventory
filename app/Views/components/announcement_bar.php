<?php
    $sc = $cms ?? $siteContents ?? [];
    $active = (string) ($sc['announcement_active']['text_value'] ?? '0');
    $isActive = in_array(trim($active), ['1', 'true', 'yes', 'on'], true);
    $text = trim((string) ($sc['announcement_text']['text_value'] ?? ''));

    if (!$isActive || $text === '') {
        return;
    }

    $barId = 'blax-global-announcement-bar';
    $storageKey = 'blax_announcement_dismissed_' . substr(md5($text), 0, 12);
?>
<aside id="<?= $barId ?>" aria-label="Store Announcement" class="hidden bg-gradient-to-r from-primary via-primary-container to-primary text-on-primary text-xs py-2 px-4 relative z-40 transition-all duration-300 shadow-xs">
    <div class="max-w-container-max mx-auto flex items-center justify-between gap-3">
        <div class="flex-1 flex items-center justify-center gap-2 text-center font-medium">
            <span class="material-symbols-outlined text-[16px] text-amber-300 shrink-0" aria-hidden="true">campaign</span>
            <span class="line-clamp-1 sm:line-clamp-none tracking-wide"><?= esc($text) ?></span>
        </div>
        <button type="button" onclick="dismissGlobalAnnouncement()" class="shrink-0 text-on-primary/80 hover:text-on-primary p-0.5 rounded hover:bg-white/10 transition-colors focus:outline-none focus:ring-1 focus:ring-white/40" title="Dismiss announcement" aria-label="Dismiss announcement">
            <span class="material-symbols-outlined text-[16px]">close</span>
        </button>
    </div>
</aside>
<script>
(function() {
    try {
        const key = '<?= $storageKey ?>';
        if (!localStorage.getItem(key)) {
            const bar = document.getElementById('<?= $barId ?>');
            if (bar) bar.classList.remove('hidden');
        }
    } catch(e) {}
})();

function dismissGlobalAnnouncement() {
    try {
        localStorage.setItem('<?= $storageKey ?>', '1');
    } catch(e) {}
    const bar = document.getElementById('<?= $barId ?>');
    if (bar) {
        bar.style.opacity = '0';
        bar.style.maxHeight = '0';
        bar.style.paddingTop = '0';
        bar.style.paddingBottom = '0';
        setTimeout(() => bar.remove(), 300);
    }
}
</script>
