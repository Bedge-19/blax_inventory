<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<meta name="csrf-token" content="<?= csrf_hash() ?>">
<meta name="csrf-header" content="<?= csrf_header() ?>">

<title><?= esc($title ?? 'Blax') ?></title>

<!-- Early Theme Switcher Initializer (Defaults to Light Mode, Prevents FOUC) -->
<script>
(function() {
    window.updateThemeToggleIcons = function() {
        const isDark = document.documentElement.classList.contains('dark');
        document.querySelectorAll('.theme-toggle-btn').forEach(function(btn) {
            const icon = btn.querySelector('.theme-toggle-icon, .material-symbols-outlined');
            const text = btn.querySelector('.theme-toggle-text, .theme-mode-label');
            if (icon) {
                icon.textContent = isDark ? 'light_mode' : 'dark_mode';
            }
            if (text) {
                text.textContent = isDark ? 'Light Mode' : 'Dark Mode';
            }
            btn.setAttribute('title', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
            btn.setAttribute('aria-label', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
        });
    };

    window.setBlaxTheme = function(theme) {
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
            try { localStorage.setItem('blax_theme', 'dark'); } catch(e) {}
        } else {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
            try { localStorage.setItem('blax_theme', 'light'); } catch(e) {}
        }
        window.updateThemeToggleIcons();
        window.dispatchEvent(new CustomEvent('blax:theme-changed', { detail: { theme: theme } }));
    };

    window.toggleBlaxTheme = function() {
        const isDark = document.documentElement.classList.contains('dark');
        window.setBlaxTheme(isDark ? 'light' : 'dark');
    };

    try {
        const savedTheme = localStorage.getItem('blax_theme');
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
        } else {
            document.documentElement.classList.add('light');
            document.documentElement.classList.remove('dark');
        }
    } catch(e) {}
})();
</script>

<script>
window.BASE_URL = '<?= rtrim(base_url(), '/') ?>/';
(function() {
    window.getCsrfToken = function() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    };
    window.getCsrfHeader = function() {
        const meta = document.querySelector('meta[name="csrf-header"]');
        return meta ? meta.getAttribute('content') : 'X-CSRF-TOKEN';
    };

    const originalFetch = window.fetch;
    window.fetch = function(input, init) {
        init = init || {};
        const method = (init.method || 'GET').toUpperCase();
        if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
            const token = window.getCsrfToken();
            const header = window.getCsrfHeader();
            if (token) {
                if (!init.headers) {
                    init.headers = {};
                }
                if (init.headers instanceof Headers) {
                    if (!init.headers.has(header)) {
                        init.headers.set(header, token);
                    }
                } else if (Array.isArray(init.headers)) {
                    if (!init.headers.some(([k]) => k.toLowerCase() === header.toLowerCase())) {
                        init.headers.push([header, token]);
                    }
                } else {
                    if (!init.headers[header]) {
                        init.headers[header] = token;
                    }
                }
            }
        }
        return originalFetch.call(this, input, init);
    };

    window.showToast = function(message, type = 'success', duration = 3500) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed top-16 sm:top-5 right-0 sm:right-5 z-[9999] flex flex-col gap-2 pointer-events-none max-w-sm w-full px-3 sm:px-4 transition-all';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = 'pointer-events-auto bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl rounded-2xl p-3 flex items-center gap-3 transform translate-y-[-10px] opacity-0 transition-all duration-300 ease-out';
        
        let iconName = 'check_circle';
        let iconColor = 'text-green-600';
        if (type === 'error') {
            iconName = 'error';
            iconColor = 'text-red-600';
        } else if (type === 'warning') {
            iconName = 'warning';
            iconColor = 'text-amber-600';
        } else if (type === 'info') {
            iconName = 'info';
            iconColor = 'text-blue-600';
        }

        toast.innerHTML = `
            <span class="material-symbols-outlined shrink-0 ${iconColor} text-xl">${iconName}</span>
            <div class="flex-1 text-xs font-semibold leading-tight">${message}</div>
            <button type="button" class="shrink-0 p-1 text-outline hover:text-on-surface rounded-full transition-colors" aria-label="Close notification">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        `;

        const closeBtn = toast.querySelector('button');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => dismissToast(toast));
        }

        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.remove('translate-y-[-10px]', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
        });

        const timer = setTimeout(() => dismissToast(toast), duration);

        function dismissToast(el) {
            clearTimeout(timer);
            el.classList.remove('translate-y-0', 'opacity-100');
            el.classList.add('translate-y-[-10px]', 'opacity-0');
            setTimeout(() => {
                if (el.parentNode) el.parentNode.removeChild(el);
            }, 300);
        }
    };
})();
</script>

<!-- Preconnect for Google Fonts to accelerate typography paint -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="<?= base_url('css/tailwind.css?v=1.0.2') ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<script src="<?= base_url('js/blax-ui.js?v=1.0.2') ?>" defer></script>

<style>
        body { font-family: 'Inter', sans-serif; scroll-behavior: smooth; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .fill-icon { font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24; }
        .glass-panel { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(229, 231, 235, 0.5); }
        .glass-card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(229, 231, 235, 0.5); }
        .glassmorphism { backdrop-filter: blur(16px); background-color: rgba(255, 255, 255, 0.8); border: 1px solid rgba(255, 255, 255, 0.3); }
        .card-elevated { background-color: #ffffff; border: 1px solid rgba(226, 232, 240, 0.9); box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04); }
        .card-elevated:hover { border-color: rgba(37, 99, 235, 0.4); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08); }
        .bg-pattern { background-color: #f7f9fb; background-image: radial-gradient(at 15% 20%, rgba(37,99,235,0.08) 0px, transparent 50%), radial-gradient(at 85% 15%, rgba(0,116,166,0.06) 0px, transparent 50%), radial-gradient(at 70% 90%, rgba(0,74,198,0.07) 0px, transparent 50%); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(115, 118, 134, 0.3); border-radius: 10px; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .metric-card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .hover-lift:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .soft-shadow { box-shadow: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05); }
        .active-nav-link { background-color: #d5e0f8; color: #586377; font-weight: 600; transform: translateX(4px); }
        .badge { background-color: #004ac6; color: #ffffff; font-size: 11px; line-height: 1; padding: 3px 6px; border-radius: 9999px; font-weight: 600; }
        .sidebar-item:hover { background-color: #e6e8ea; }
        .order-table-row:hover { background-color: #eceef0; }
        .delay-150ms { transition-delay: 150ms; }
        
        /* Dark Mode — Inline Critical Overrides (prevents FOUC) */
        html.dark { color-scheme: dark; }
        html.dark body { background-color: #0b0f19 !important; color: #e2e8f0 !important; }
        html.dark .glass-panel, html.dark .glass-card, html.dark .glassmorphism { background-color: rgba(21, 31, 50, 0.85) !important; border-color: rgba(51, 65, 85, 0.5) !important; }
        html.dark .card-elevated { background-color: #151f32 !important; border-color: rgba(51, 65, 85, 0.6) !important; box-shadow: 0 4px 20px -2px rgba(0,0,0,0.5) !important; }
        html.dark .card-elevated:hover { border-color: rgba(59, 130, 246, 0.5) !important; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.6) !important; }
        html.dark .bg-pattern { background-color: #0b0f19 !important; background-image: radial-gradient(at 15% 20%, rgba(59,130,246,0.12) 0px, transparent 50%), radial-gradient(at 85% 15%, rgba(14,165,233,0.10) 0px, transparent 50%), radial-gradient(at 70% 90%, rgba(37,99,235,0.10) 0px, transparent 50%) !important; }
        
        /* Semantic Surface Backgrounds */
        html.dark .bg-background                { background-color: #0b0f19 !important; }
        html.dark .bg-surface                   { background-color: #111827 !important; }
        html.dark .bg-surface-container-lowest  { background-color: #151f32 !important; }
        html.dark .bg-surface-container-low     { background-color: #1e293b !important; }
        html.dark .bg-surface-container         { background-color: #222f46 !important; }
        html.dark .bg-surface-container-high    { background-color: #293854 !important; }
        html.dark .bg-surface-container-highest { background-color: #324466 !important; }
        html.dark .bg-surface-variant           { background-color: #222f46 !important; }
        html.dark .bg-surface-dim               { background-color: #0f172a !important; }
        html.dark .bg-surface-bright            { background-color: #1e293b !important; }

        /* Opacity variants for semantic surfaces */
        html.dark .bg-surface-container-lowest\/95 { background-color: rgba(21, 31, 50, 0.95) !important; }
        html.dark .bg-surface-container-lowest\/90 { background-color: rgba(21, 31, 50, 0.90) !important; }
        html.dark .bg-surface-container-lowest\/80 { background-color: rgba(21, 31, 50, 0.80) !important; }
        html.dark .bg-surface-container-low\/90    { background-color: rgba(30, 41, 59, 0.90) !important; }
        html.dark .bg-surface-container-low\/80    { background-color: rgba(30, 41, 59, 0.80) !important; }
        html.dark .bg-surface-container-low\/70    { background-color: rgba(30, 41, 59, 0.70) !important; }
        html.dark .bg-surface-container-low\/60    { background-color: rgba(30, 41, 59, 0.60) !important; }
        html.dark .bg-surface-container-low\/50    { background-color: rgba(30, 41, 59, 0.50) !important; }
        html.dark .bg-surface-container-low\/40    { background-color: rgba(30, 41, 59, 0.40) !important; }

        /* Semantic Text Colors */
        html.dark .text-on-surface             { color: #f1f5f9 !important; }
        html.dark .text-on-surface-variant     { color: #94a3b8 !important; }
        html.dark .text-outline                { color: #64748b !important; }
        html.dark .text-outline-variant        { color: #475569 !important; }
        html.dark .text-label-sm               { color: #94a3b8 !important; }
        html.dark .text-on-surface-variant\/70 { color: rgba(148, 163, 184, 0.7) !important; }
        html.dark .text-on-surface-variant\/60 { color: rgba(148, 163, 184, 0.6) !important; }

        /* Semantic Borders & Dividers */
        html.dark .border-outline-variant,
        html.dark .border-outline-variant\/10,
        html.dark .border-outline-variant\/15,
        html.dark .border-outline-variant\/20,
        html.dark .border-outline-variant\/25,
        html.dark .border-outline-variant\/30,
        html.dark .border-outline-variant\/40,
        html.dark .border-outline-variant\/50,
        html.dark .border-outline-variant\/60 {
            border-color: rgba(51, 65, 85, 0.6) !important;
        }
        html.dark .border-surface-container-lowest { border-color: #151f32 !important; }
        html.dark .divide-outline-variant\/10 > :not([hidden]) ~ :not([hidden]),
        html.dark .divide-outline-variant\/15 > :not([hidden]) ~ :not([hidden]),
        html.dark .divide-outline-variant\/20 > :not([hidden]) ~ :not([hidden]),
        html.dark .divide-outline-variant\/30 > :not([hidden]) ~ :not([hidden]) {
            border-color: rgba(51, 65, 85, 0.45) !important;
        }

        /* Critical text remaps to prevent dark-on-dark flash */
        html.dark .text-slate-900 { color: #f1f5f9 !important; }
        html.dark .text-slate-800 { color: #e2e8f0 !important; }
        html.dark .text-slate-700 { color: #cbd5e1 !important; }
        html.dark .text-slate-600, html.dark .text-slate-500 { color: #94a3b8 !important; }
        html.dark .text-gray-900 { color: #f9fafb !important; }
        html.dark .text-gray-800 { color: #e5e7eb !important; }
        html.dark .text-gray-700 { color: #d1d5db !important; }

        /* Critical bg remaps */
        html.dark .bg-white { background-color: #151f32 !important; }
        html.dark .bg-slate-50 { background-color: #111827 !important; }
        html.dark .bg-slate-100 { background-color: #1e293b !important; }
        html.dark .bg-white\/90 { background-color: rgba(21,31,50,0.90) !important; }
        html.dark .bg-white\/95 { background-color: rgba(21,31,50,0.95) !important; }

        /* Critical border remaps */
        html.dark .border-slate-200, html.dark .border-slate-200\/80, html.dark .border-slate-200\/90 { border-color: rgba(51,65,85,0.5) !important; }
        html.dark .border-slate-100 { border-color: rgba(51,65,85,0.3) !important; }

        /* Gradient remaps */
        html.dark .from-white { --tw-gradient-from: #151f32 !important; }
        html.dark .via-white, html.dark .via-white\/90, html.dark .via-white\/95 { --tw-gradient-via: #151f32 !important; }

        /* Form Controls */
        html.dark select, html.dark input:not([type="checkbox"]):not([type="radio"]), html.dark textarea {
            background-color: #151f32 !important;
            color: #f1f5f9 !important;
            border-color: rgba(51, 65, 85, 0.6) !important;
        }

        /* Responsive utilities */
        .mobile-nav-open { overflow: hidden; }
        .drawer-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 45; opacity: 0; transition: opacity 0.3s ease; pointer-events: none; }
        .drawer-backdrop.active { opacity: 1; pointer-events: auto; }
        .mobile-drawer { position: fixed; top: 0; right: 0; bottom: 0; width: 300px; max-width: 85vw; background: #ffffff; z-index: 50; transform: translateX(100%); transition: transform 0.3s ease; box-shadow: -4px 0 24px rgba(0,0,0,0.12); overflow-y: auto; }
        html.dark .mobile-drawer { background: #111827; border-left: 1px solid rgba(51, 65, 85, 0.5); }
        .mobile-drawer.open { transform: translateX(0); }
        .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 35; opacity: 0; transition: opacity 0.3s ease; pointer-events: none; }
        .sidebar-overlay.active { opacity: 1; pointer-events: auto; }
        @media (max-width: 767px) {
            .responsive-table { display: block; overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .responsive-table table { min-width: 640px; }
            .responsive-table th, .responsive-table td { padding-left: 12px; padding-right: 12px; white-space: nowrap; }
        }
        .mobile-profile-tabs { display: flex; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; -ms-overflow-style: none; gap: 4px; padding: 4px; }
        .mobile-profile-tabs::-webkit-scrollbar { display: none; }

        /* Table responsive wrapper - mobile horizontal scroll */
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table-responsive table { min-width: 640px; }

        /* Responsive max-width container (capped at 1200px and centered) */
        .site-container { width: 100%; max-width: 1200px; margin-left: auto; margin-right: auto; padding-left: 1rem; padding-right: 1rem; }
        @media (min-width: 640px) { .site-container { padding-left: 1.5rem; padding-right: 1.5rem; } }
        @media (min-width: 1024px) { .site-container { padding-left: 2rem; padding-right: 2rem; } }

        /* Dropdown tap helpers */
        .dropdown-menu { opacity: 0; visibility: hidden; pointer-events: none; transition: opacity 0.15s ease, visibility 0.15s ease, transform 0.15s ease; }
        .dropdown-menu.open { opacity: 1 !important; visibility: visible !important; pointer-events: auto !important; }
        @media (hover: hover) and (pointer: fine) {
            .dropdown-toggle:hover + .dropdown-menu,
            .dropdown-menu:hover { opacity: 1; visibility: visible; pointer-events: auto; }
        }
        .profile-dropdown-toggle { display: inline-flex; }
    </style>