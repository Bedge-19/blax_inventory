<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<meta name="csrf-token" content="<?= csrf_hash() ?>">
<meta name="csrf-header" content="<?= csrf_header() ?>">

<title><?= esc($title ?? 'Blax') ?></title>

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
            container.className = 'fixed top-5 right-5 z-[9999] flex flex-col gap-2 pointer-events-none max-w-sm w-full px-4';
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

<link rel="stylesheet" href="<?= base_url('css/tailwind.css') ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<script src="<?= base_url('js/blax-ui.js') ?>" defer></script>

<style>
        body { font-family: 'Inter', sans-serif; scroll-behavior: smooth; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .fill-icon { font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24; }
        .glass-panel { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(229, 231, 235, 0.5); }
        .glass-card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(229, 231, 235, 0.5); }
        .glassmorphism { backdrop-filter: blur(16px); background-color: rgba(255, 255, 255, 0.8); border: 1px solid rgba(255, 255, 255, 0.3); }
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
        /* Responsive utilities */
        .mobile-nav-open { overflow: hidden; }
        .drawer-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 45; opacity: 0; transition: opacity 0.3s ease; pointer-events: none; }
        .drawer-backdrop.active { opacity: 1; pointer-events: auto; }
        .mobile-drawer { position: fixed; top: 0; right: 0; bottom: 0; width: 300px; max-width: 85vw; background: #ffffff; z-index: 50; transform: translateX(100%); transition: transform 0.3s ease; box-shadow: -4px 0 24px rgba(0,0,0,0.12); overflow-y: auto; }
        .mobile-drawer.open { transform: translateX(0); }
        .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 35; opacity: 0; transition: opacity 0.3s ease; pointer-events: none; }
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
        .dropdown-menu { opacity: 0; visibility: hidden; transition: opacity 0.15s, visibility 0.15s; }
        .dropdown-menu.open { opacity: 1; visibility: visible; }
        @media (hover: hover) and (pointer: fine) {
            .dropdown-toggle:hover + .dropdown-menu,
            .dropdown-menu:hover { opacity: 1; visibility: visible; }
        }
        .profile-dropdown-toggle { display: inline-flex; }
    </style>