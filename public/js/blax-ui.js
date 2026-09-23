/**
 * Blax Inventory / Marketplace - Shared UI JavaScript Bundle
 * 
 * Provides centralized, defensive handling for:
 * 1. CSRF header injection for AJAX/fetch requests
 * 2. Toast notifications & Web Audio API synthesized chimes
 * 3. Navigation drawer & sidebar toggles (Customer, Tenant, Admin)
 * 4. Dropdown menus (Profile, Notifications, Catalog sort)
 * 5. Global AJAX Add-to-Cart with badge synchronization
 */

(function () {
    'use strict';

    // ── 1. CSRF & Fetch Interceptor ───────────────────────────────────
    window.getCsrfToken = function () {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    };

    window.getCsrfHeader = function () {
        const meta = document.querySelector('meta[name="csrf-header"]');
        return meta ? meta.getAttribute('content') : 'X-CSRF-TOKEN';
    };

    if (window.fetch) {
        const originalFetch = window.fetch;
        window.fetch = function (input, init) {
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
    }

    // ── 2. Web Audio Synthesized Chimes ───────────────────────────────
    function playChime(type) {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            if (ctx.state === 'suspended') {
                ctx.resume().catch(() => {});
            }

            const now = ctx.currentTime;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';

            if (type === 'error') {
                osc.frequency.setValueAtTime(300, now);
                osc.frequency.exponentialRampToValueAtTime(180, now + 0.25);
            } else if (type === 'order' || type === 'delivery') {
                osc.frequency.setValueAtTime(587.33, now); // D5
                osc.frequency.setValueAtTime(880.00, now + 0.12); // A5
            } else {
                osc.frequency.setValueAtTime(523.25, now); // C5
                osc.frequency.setValueAtTime(659.25, now + 0.10); // E5
            }

            gain.gain.setValueAtTime(0.08, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start(now);
            osc.stop(now + 0.36);
        } catch (e) {
            // Audio error silently caught
        }
    }

    // ── 3. Universal Toast Notification ───────────────────────────────
    window.showToast = function (opts) {
        try {
            if (typeof opts === 'string') {
                opts = { message: opts, type: 'info' };
            }
            opts = opts || {};
            const type = opts.type || 'info';
            const title = opts.title || (type === 'success' ? 'Success' : (type === 'error' ? 'Notice' : 'Notification'));
            const message = opts.message || '';
            const url = opts.url || '';
            const icon = opts.icon || (
                type === 'success' ? 'check_circle' :
                type === 'error' ? 'error' :
                type === 'warning' ? 'warning' :
                type === 'order' ? 'shopping_bag' :
                type === 'delivery' ? 'two_wheeler' :
                type === 'printing' ? 'print' : 'notifications'
            );

            let container = document.getElementById('blax-toast-container') 
                || document.getElementById('toast-container')
                || document.getElementById('blax-tenant-toast-container')
                || document.getElementById('blax-admin-toast-container');

            if (!container) {
                container = document.createElement('div');
                container.id = 'blax-toast-container';
                container.className = 'fixed top-4 right-4 z-[9999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-3 sm:px-0';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = 'pointer-events-auto bg-surface-container-lowest/95 backdrop-blur-md border border-outline-variant/40 rounded-2xl shadow-xl p-3.5 flex items-start gap-3 transform translate-y-[-10px] opacity-0 transition-all duration-300 ease-out';

            const colorMap = {
                success: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                error: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
                warning: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                order: 'bg-primary/10 text-primary border-primary/20',
                delivery: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
                printing: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                info: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'
            };
            const badgeStyle = colorMap[type] || colorMap.info;

            let actionHtml = '';
            if (url) {
                actionHtml = `
                    <a href="${url}" class="inline-flex items-center gap-1 text-[11px] font-bold text-primary hover:underline mt-1.5">
                        <span>View Details</span>
                        <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                    </a>
                `;
            }

            toast.innerHTML = `
                <div class="w-8 h-8 rounded-xl ${badgeStyle} border flex items-center justify-center shrink-0 shadow-2xs mt-0.5">
                    <span class="material-symbols-outlined text-[18px]">${icon}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-xs font-bold text-on-surface leading-tight">${title}</h4>
                    <p class="text-xs text-on-surface-variant mt-0.5 leading-snug break-words">${message}</p>
                    ${actionHtml}
                </div>
                <button type="button" class="text-on-surface-variant/60 hover:text-on-surface p-1 -mr-1 rounded-lg transition-colors" aria-label="Close notification">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </button>
            `;

            const closeBtn = toast.querySelector('button');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    toast.classList.add('opacity-0', 'translate-x-full');
                    setTimeout(() => toast.remove(), 350);
                });
            }

            container.appendChild(toast);
            playChime(type);

            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-[-10px]', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
            });

            const duration = opts.duration || 6000;
            const autoDismiss = setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-x-full');
                setTimeout(() => toast.remove(), 350);
            }, duration);

            toast.addEventListener('mouseenter', () => clearTimeout(autoDismiss));
        } catch (e) {
            // Toast fallback
        }
    };

    // ── 4. Safe UI Interactivity Setup (DOM Ready) ─────────────────────
    function initBlaxUI() {
        // A. Mobile Navigation Drawer
        try {
            const menuToggle = document.getElementById('mobile-menu-toggle');
            const menuClose = document.getElementById('mobile-menu-close');
            const drawer = document.getElementById('mobile-drawer');
            const backdrop = document.getElementById('mobile-drawer-backdrop');

            if (menuToggle && drawer && backdrop) {
                const openDrawer = function () {
                    drawer.classList.add('open');
                    backdrop.classList.add('active');
                    document.body.classList.add('mobile-nav-open');
                };

                const closeDrawer = function () {
                    drawer.classList.remove('open');
                    backdrop.classList.remove('active');
                    document.body.classList.remove('mobile-nav-open');
                };

                menuToggle.addEventListener('click', openDrawer);
                if (menuClose) menuClose.addEventListener('click', closeDrawer);
                backdrop.addEventListener('click', closeDrawer);

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && drawer.classList.contains('open')) {
                        closeDrawer();
                    }
                });
            }
        } catch (e) {}

        // B. Tenant & Admin Sidebar Toggles
        try {
            // Tenant
            const tenantToggle = document.getElementById('sidebar-toggle') || document.getElementById('tenant-sidebar-toggle');
            const tenantClose = document.getElementById('tenant-sidebar-close');
            const tenantSidebar = document.getElementById('tenant-sidebar');
            const tenantOverlay = document.getElementById('tenant-sidebar-overlay');

            if (tenantSidebar && tenantOverlay) {
                const openTenantSidebar = function () {
                    tenantSidebar.classList.remove('hidden');
                    tenantSidebar.classList.add('flex', 'z-50');
                    tenantOverlay.classList.add('active');
                    document.body.classList.add('mobile-nav-open');
                };
                const closeTenantSidebar = function () {
                    tenantSidebar.classList.add('hidden');
                    tenantSidebar.classList.remove('flex', 'z-50');
                    tenantOverlay.classList.remove('active');
                    document.body.classList.remove('mobile-nav-open');
                };

                if (tenantToggle) tenantToggle.addEventListener('click', openTenantSidebar);
                if (tenantClose) tenantClose.addEventListener('click', closeTenantSidebar);
                tenantOverlay.addEventListener('click', closeTenantSidebar);
            }

            // Admin
            const adminToggle = document.getElementById('admin-sidebar-toggle');
            const adminClose = document.getElementById('admin-sidebar-close');
            const adminSidebar = document.getElementById('admin-sidebar');
            const adminOverlay = document.getElementById('admin-sidebar-overlay');

            if (adminSidebar && adminOverlay) {
                const openAdminSidebar = function () {
                    adminSidebar.classList.remove('hidden');
                    adminSidebar.classList.add('flex', 'z-50');
                    adminOverlay.classList.add('active');
                    document.body.classList.add('mobile-nav-open');
                };
                const closeAdminSidebar = function () {
                    adminSidebar.classList.add('hidden');
                    adminSidebar.classList.remove('flex', 'z-50');
                    adminOverlay.classList.remove('active');
                    document.body.classList.remove('mobile-nav-open');
                };

                if (adminToggle) adminToggle.addEventListener('click', openAdminSidebar);
                if (adminClose) adminClose.addEventListener('click', closeAdminSidebar);
                adminOverlay.addEventListener('click', closeAdminSidebar);
            }
        } catch (e) {}

        // C. Notification Dropdowns
        try {
            // Customer notification dropdown
            const notifToggle = document.getElementById('notif-dropdown-toggle') || document.getElementById('notif-toggle');
            const notifDropdown = document.getElementById('notif-dropdown') || document.getElementById('notif-panel');
            if (notifToggle && notifDropdown) {
                notifToggle.addEventListener('click', function (e) {
                    e.stopPropagation();
                    notifDropdown.classList.toggle('hidden');
                });
                notifDropdown.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
                document.addEventListener('click', function () {
                    notifDropdown.classList.add('hidden');
                });
            }

            // Admin notification panel
            const adminNotifToggle = document.getElementById('admin-notif-toggle');
            const adminNotifPanel = document.getElementById('admin-notif-panel');
            if (adminNotifToggle && adminNotifPanel) {
                adminNotifToggle.addEventListener('click', function (e) {
                    e.stopPropagation();
                    adminNotifPanel.classList.toggle('hidden');
                });
                adminNotifPanel.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
                document.addEventListener('click', function () {
                    adminNotifPanel.classList.add('hidden');
                });
            }
        } catch (e) {}

        // D. Profile Dropdowns (Customer & Tenant)
        try {
            const customerProfileToggle = document.getElementById('profile-dropdown-toggle');
            const customerProfileDropdown = document.getElementById('profile-dropdown');
            if (customerProfileToggle && customerProfileDropdown) {
                let isOpen = false;
                const setOpen = function (open) {
                    isOpen = open;
                    if (open) {
                        customerProfileDropdown.classList.add('open');
                    } else {
                        customerProfileDropdown.classList.remove('open');
                    }
                    customerProfileToggle.setAttribute('aria-expanded', open);
                    customerProfileToggle.classList.toggle('ring-2', open);
                    customerProfileToggle.classList.toggle('ring-primary', open);
                };

                customerProfileToggle.addEventListener('click', function (e) {
                    e.stopPropagation();
                    setOpen(!isOpen);
                });
                document.addEventListener('click', function () {
                    setOpen(false);
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') setOpen(false);
                });
            }

            const tenantProfileToggle = document.getElementById('tenant-profile-toggle');
            const tenantProfileDropdown = document.getElementById('tenant-profile-dropdown');
            if (tenantProfileToggle && tenantProfileDropdown) {
                let isTenantOpen = false;
                const setTenantOpen = function (open) {
                    isTenantOpen = open;
                    if (open) {
                        tenantProfileDropdown.classList.add('open');
                    } else {
                        tenantProfileDropdown.classList.remove('open');
                    }
                    tenantProfileToggle.setAttribute('aria-expanded', open);
                };

                tenantProfileToggle.addEventListener('click', function (e) {
                    e.stopPropagation();
                    setTenantOpen(!isTenantOpen);
                });
                document.addEventListener('click', function () {
                    setTenantOpen(false);
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') setTenantOpen(false);
                });
            }
        } catch (e) {}

        // E. Catalog Sort Menu
        try {
            const sortBtn = document.getElementById('sort-menu-btn');
            const sortMenu = document.getElementById('sort-menu');
            const sortContainer = document.getElementById('sort-dropdown-container');

            window.toggleSortMenu = function (e) {
                if (e && e.stopPropagation) e.stopPropagation();
                if (sortMenu) sortMenu.classList.toggle('hidden');
            };

            if (sortBtn && sortMenu) {
                sortBtn.addEventListener('click', function (e) {
                    window.toggleSortMenu(e);
                });
            }

            document.addEventListener('click', function (e) {
                if (sortContainer && sortMenu && !sortContainer.contains(e.target)) {
                    sortMenu.classList.add('hidden');
                }
            });
        } catch (e) {}

        // F. Global AJAX Add to Cart & Badge Sync
        try {
            const cartBadge = document.getElementById('cart-count-badge');
            const mobileCartBadge = document.getElementById('mobile-bottom-cart-badge');

            document.body.addEventListener('submit', async function (e) {
                const form = e.target.closest('form[action*="cart/add"]');
                if (!form) return;

                // Do not intercept if submission is for Buy Now
                const submitter = e.submitter;
                if (submitter) {
                    const formaction = submitter.getAttribute('formaction');
                    if (formaction && !formaction.includes('cart/add')) return;
                    if (submitter.id === 'buy-now-btn' || submitter.classList.contains('buy-now-btn')) return;
                }

                e.preventDefault();

                const btn = form.querySelector('button[type="submit"]');
                if (!btn || btn.dataset.loading) return;
                btn.dataset.loading = '1';

                const icon = btn.querySelector('.material-symbols-outlined');
                const origIcon = icon ? icon.textContent.trim() : '';
                const origClass = btn.className;

                const fd = new FormData(form);
                fd.append('X-Requested-With', 'XMLHttpRequest');

                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd
                    });
                    const data = await res.json();

                    if (data && data.status === 'success') {
                        btn.className = btn.className
                            .replace(/bg-\S+/g, '')
                            .replace(/text-on-primary\b/g, '')
                            .trim() + ' bg-green-500 text-white scale-105';
                        if (icon) icon.textContent = 'check';

                        if (cartBadge) {
                            const cur = parseInt(cartBadge.textContent, 10) || 0;
                            cartBadge.textContent = cur + 1;
                            cartBadge.classList.remove('hidden');
                        }
                        if (mobileCartBadge) {
                            const cur = parseInt(mobileCartBadge.textContent, 10) || 0;
                            mobileCartBadge.textContent = cur + 1;
                            mobileCartBadge.classList.remove('hidden');
                        }

                        if (window.showToast) {
                            window.showToast({
                                type: 'success',
                                title: 'Added to Cart',
                                message: 'Item was added to your shopping cart.'
                            });
                        }

                        setTimeout(() => {
                            btn.className = origClass;
                            if (icon) icon.textContent = origIcon;
                            delete btn.dataset.loading;
                        }, 1500);
                    } else {
                        const errMsg = (data && data.message) ? data.message : 'Could not add to cart.';
                        btn.className = btn.className.replace(/bg-\S+/g, '').trim() + ' bg-red-500 text-white';
                        if (icon) icon.textContent = 'error';

                        if (window.showToast) {
                            window.showToast({
                                type: 'error',
                                title: 'Notice',
                                message: errMsg
                            });
                        }

                        setTimeout(() => {
                            btn.className = origClass;
                            if (icon) icon.textContent = origIcon;
                            delete btn.dataset.loading;
                        }, 1500);
                    }
                } catch (err) {
                    btn.className = origClass;
                    delete btn.dataset.loading;
                }
            });
        } catch (e) {}
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBlaxUI);
    } else {
        initBlaxUI();
    }
})();
