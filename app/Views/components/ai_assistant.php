<?php
    $csrfToken = csrf_hash();
    $csrfName  = csrf_token();
    $defaultGreeting = 'Hello! I am your Blax AI shopping and delivery assistant. How can I help you find products, calculate printing orders, or track shipments across Polomolok today?';
    $greeting = $greeting ?? $defaultGreeting;
?>

<!-- AI Assistant Floating Action Button -->
<button id="ai-toggle-btn" type="button" aria-label="Open Blax AI Assistant" class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 group z-40 flex items-center gap-2 p-1.5 bg-gradient-to-tr from-blue-600 via-indigo-600 to-blue-500 text-white rounded-full shadow-[0_8px_30px_rgb(37,99,235,0.4)] hover:shadow-[0_12px_36px_rgb(37,99,235,0.5)] hover:scale-105 active:scale-95 transition-all duration-300">
    <div class="relative w-12 h-12 sm:w-14 sm:h-14 rounded-full flex items-center justify-center">
        <span class="material-symbols-outlined text-[26px] sm:text-[30px] group-hover:rotate-12 transition-transform duration-300">auto_awesome</span>
        <!-- Online Pulse Dot -->
        <span class="absolute top-1 right-1 flex h-3 w-3">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500 border-2 border-white dark:border-slate-900"></span>
        </span>
    </div>
    <span class="hidden sm:inline-block pr-4 pl-1 font-bold text-xs tracking-wide">Ask Blax AI</span>
</button>

<!-- AI Assistant Modal Window -->
<div id="ai-modal" class="fixed inset-0 z-50 pointer-events-none hidden flex-col justify-end sm:justify-end sm:items-end p-0 sm:p-0 sm:inset-auto sm:bottom-6 sm:right-6">

    <!-- Mobile Backdrop Scrim -->
    <div id="ai-backdrop" class="fixed inset-0 bg-black/40 backdrop-blur-xs sm:hidden pointer-events-auto -z-10 transition-opacity"></div>

    <div class="glass-panel w-full sm:w-[420px] h-[92dvh] sm:h-[600px] max-h-[92dvh] sm:max-h-[660px] rounded-t-3xl sm:rounded-3xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.3)] flex flex-col pointer-events-auto transition-all duration-300 border-t sm:border border-outline-variant/30 bg-surface-container-lowest/98 backdrop-blur-xl overflow-hidden animate-in slide-in-from-bottom-6 sm:zoom-in-95 duration-200">

        <!-- Mobile Drawer Drag Handle -->
        <div class="w-12 h-1 rounded-full bg-outline-variant/50 mx-auto mt-2.5 mb-1 sm:hidden shrink-0"></div>

        <!-- Modern Header -->
        <div class="px-md py-sm bg-gradient-to-r from-surface-container-lowest via-surface-container-low to-surface-container-lowest border-b border-outline-variant/20 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-sm">
                <div class="relative">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-xs">
                        <span class="material-symbols-outlined text-[20px]">smart_toy</span>
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-emerald-500 border-2 border-surface-container-lowest rounded-full"></span>
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <h3 class="text-sm font-black text-on-surface tracking-tight m-0">Blax AI Assistant</h3>
                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black uppercase tracking-wider bg-primary/10 text-primary border border-primary/20">AI</span>
                    </div>
                    <p class="text-[11px] text-on-surface-variant flex items-center gap-1 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                        Polomolok Shopping &amp; Delivery Guide
                    </p>
                </div>
            </div>

            <!-- Header Controls -->
            <div class="flex items-center gap-1">
                <button type="button" id="ai-reset-btn" title="Clear conversation" class="p-1.5 text-on-surface-variant hover:text-on-surface hover:bg-surface-container rounded-xl transition-all">
                    <span class="material-symbols-outlined text-[18px]">restart_alt</span>
                </button>
                <button type="button" id="ai-close-btn" title="Close assistant" class="p-1.5 text-on-surface-variant hover:text-on-surface hover:bg-surface-container rounded-xl transition-all">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>
        </div>

        <!-- Chat History Container -->
        <div id="chat-history" class="flex-1 overflow-y-auto p-md space-y-md custom-scrollbar bg-surface/30 text-xs sm:text-sm">

            <!-- Initial Hero Greeting -->
            <div class="flex items-start gap-2 max-w-[92%]">
                <div class="w-7 h-7 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 mt-1">
                    <span class="material-symbols-outlined text-[16px]">smart_toy</span>
                </div>
                <div class="space-y-sm">
                    <div class="bg-surface-container-low text-on-surface p-3.5 rounded-2xl rounded-tl-xs border border-outline-variant/20 shadow-xs leading-relaxed">
                        <?= esc($greeting) ?>
                    </div>
                    <span class="text-[10px] text-outline font-medium block px-1">Just now</span>
                </div>
            </div>

            <!-- Starter Prompt Suggestions -->
            <div id="starter-prompts-wrap" class="space-y-xs pt-xs">
                <p class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70 px-1">Suggested inquiries</p>
                <div class="grid grid-cols-1 gap-1.5">
                    <button type="button" class="faq-prompt-btn w-full text-left p-2.5 rounded-xl bg-surface-container-lowest hover:bg-primary/10 hover:border-primary/30 border border-outline-variant/20 transition-all text-xs font-semibold text-on-surface flex items-center justify-between group shadow-xs" data-prompt="What school, office, and merchandise products are available?">
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-primary">search</span>
                            <span>Find school &amp; office supplies</span>
                        </span>
                        <span class="material-symbols-outlined text-[14px] text-outline group-hover:text-primary group-hover:translate-x-0.5 transition-all">arrow_forward</span>
                    </button>
                    <button type="button" class="faq-prompt-btn w-full text-left p-2.5 rounded-xl bg-surface-container-lowest hover:bg-primary/10 hover:border-primary/30 border border-outline-variant/20 transition-all text-xs font-semibold text-on-surface flex items-center justify-between group shadow-xs" data-prompt="How do custom PDF printing requests and downpayments work?">
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-tertiary">print</span>
                            <span>How do document printing orders work?</span>
                        </span>
                        <span class="material-symbols-outlined text-[14px] text-outline group-hover:text-primary group-hover:translate-x-0.5 transition-all">arrow_forward</span>
                    </button>
                    <button type="button" class="faq-prompt-btn w-full text-left p-2.5 rounded-xl bg-surface-container-lowest hover:bg-primary/10 hover:border-primary/30 border border-outline-variant/20 transition-all text-xs font-semibold text-on-surface flex items-center justify-between group shadow-xs" data-prompt="How do I track my delivery or use store pickup QR code?">
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-sky-600">two_wheeler</span>
                            <span>Track live delivery or pickup QR code</span>
                        </span>
                        <span class="material-symbols-outlined text-[14px] text-outline group-hover:text-primary group-hover:translate-x-0.5 transition-all">arrow_forward</span>
                    </button>
                    <button type="button" class="faq-prompt-btn w-full text-left p-2.5 rounded-xl bg-surface-container-lowest hover:bg-primary/10 hover:border-primary/30 border border-outline-variant/20 transition-all text-xs font-semibold text-on-surface flex items-center justify-between group shadow-xs" data-prompt="Show verified shops and print partners in Polomolok">
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-indigo-600">storefront</span>
                            <span>Browse verified Polomolok shops</span>
                        </span>
                        <span class="material-symbols-outlined text-[14px] text-outline group-hover:text-primary group-hover:translate-x-0.5 transition-all">arrow_forward</span>
                    </button>
                </div>
            </div>

        </div>

        <!-- Quick Prompt Chips Floating Bar (Visible when conversation is active) -->
        <div id="quick-chips-bar" class="hidden px-md py-1.5 bg-surface-container-low/60 border-t border-outline-variant/20 overflow-x-auto no-scrollbar whitespace-nowrap gap-1.5 shrink-0">
            <button type="button" class="faq-prompt-btn px-2.5 py-1 rounded-full bg-surface-container-lowest hover:bg-primary/10 text-on-surface text-[11px] font-semibold border border-outline-variant/30 shrink-0 inline-flex items-center gap-1" data-prompt="Show popular products">
                <span class="material-symbols-outlined text-[13px] text-primary">local_fire_department</span> Popular items
            </button>
            <button type="button" class="faq-prompt-btn px-2.5 py-1 rounded-full bg-surface-container-lowest hover:bg-primary/10 text-on-surface text-[11px] font-semibold border border-outline-variant/30 shrink-0 inline-flex items-center gap-1" data-prompt="How fast is doorstep delivery in Polomolok?">
                <span class="material-symbols-outlined text-[13px] text-sky-600">speed</span> Delivery speed
            </button>
            <button type="button" class="faq-prompt-btn px-2.5 py-1 rounded-full bg-surface-container-lowest hover:bg-primary/10 text-on-surface text-[11px] font-semibold border border-outline-variant/30 shrink-0 inline-flex items-center gap-1" data-prompt="What are the accepted payment methods?">
                <span class="material-symbols-outlined text-[13px] text-emerald-600">payments</span> GCash &amp; COD
            </button>
        </div>

        <!-- Modern Input Dock -->
        <div class="p-sm sm:p-md bg-surface-container-lowest border-t border-outline-variant/20 rounded-b-none sm:rounded-b-3xl shrink-0 pb-4 sm:pb-md">
            <form id="ai-chat-form" class="relative flex items-center gap-1 bg-surface-container-low/70 rounded-2xl border border-outline-variant/40 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/15 transition-all p-1">
                <?= csrf_field() ?>
                <input type="hidden" name="csrf_name" value="<?= esc($csrfName) ?>">
                <input type="hidden" name="csrf_token" value="<?= esc($csrfToken) ?>">

                <input id="ai-input" class="w-full bg-transparent border-none focus:outline-none focus:ring-0 text-on-surface placeholder-on-surface-variant/50 py-2.5 pl-3 pr-2 text-xs sm:text-sm font-medium" placeholder="Ask about products, orders, printing..." type="text" autocomplete="off">

                <!-- Voice Input Button -->
                <button type="button" id="ai-mic-btn" title="Voice search" class="p-2 text-on-surface-variant hover:text-primary rounded-xl transition-all shrink-0">
                    <span class="material-symbols-outlined text-[20px]">mic</span>
                </button>

                <!-- Send Button -->
                <button type="submit" id="ai-send-btn" class="p-2.5 bg-primary text-on-primary rounded-xl hover:bg-primary/90 active:scale-95 transition-all shadow-xs shrink-0 flex items-center justify-center disabled:opacity-40 disabled:pointer-events-none">
                    <span class="material-symbols-outlined text-[18px]">send</span>
                </button>
            </form>

            <div class="flex items-center justify-between px-1 mt-1 text-[10px] text-on-surface-variant/60">
                <span>Press Enter to send</span>
                <span class="flex items-center gap-0.5">
                    <span class="material-symbols-outlined text-[11px]">bolt</span>
                    Blax Semantic Engine
                </span>
            </div>
        </div>

    </div>

</div>

<script>
(function () {
    const toggleBtn = document.getElementById('ai-toggle-btn');
    const closeBtn  = document.getElementById('ai-close-btn');
    const backdrop  = document.getElementById('ai-backdrop');
    const resetBtn  = document.getElementById('ai-reset-btn');
    const modal     = document.getElementById('ai-modal');
    const form      = document.getElementById('ai-chat-form');
    const input     = document.getElementById('ai-input');
    const history   = document.getElementById('chat-history');
    const micBtn    = document.getElementById('ai-mic-btn');
    const sendBtn   = document.getElementById('ai-send-btn');
    const starterPrompts = document.getElementById('starter-prompts-wrap');
    const quickChips = document.getElementById('quick-chips-bar');

    if (!toggleBtn || !modal) return;

    // --- Voice Recognition ---
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition && micBtn) {
        const recognition = new SpeechRecognition();
        recognition.lang = 'en-US';
        recognition.continuous = false;
        recognition.interimResults = true;
        let listening = false;
        let finalTranscript = '';

        function setMicState(active) {
            listening = active;
            micBtn.querySelector('span').textContent = active ? 'mic_off' : 'mic';
            micBtn.classList.toggle('text-red-500', active);
            micBtn.classList.toggle('animate-pulse', active);
            micBtn.classList.toggle('bg-red-500/10', active);
            micBtn.classList.toggle('text-on-surface-variant', !active);
        }

        micBtn.addEventListener('click', () => {
            if (listening) {
                recognition.stop();
            } else {
                finalTranscript = '';
                input.value = '';
                input.placeholder = 'Listening to your voice...';
                try { recognition.start(); } catch(err) {}
            }
        });

        recognition.onstart = () => setMicState(true);
        recognition.onresult = (e) => {
            let interim = '';
            for (let i = 0; i < e.results.length; i++) {
                if (e.results[i].isFinal) {
                    finalTranscript += e.results[i][0].transcript;
                } else {
                    interim += e.results[i][0].transcript;
                }
            }
            input.value = finalTranscript + interim;
            updateSendButtonState();
        };

        recognition.onend = () => {
            setMicState(false);
            input.placeholder = 'Ask about products, orders, printing...';
            if (input.value.trim()) {
                setTimeout(() => form.requestSubmit(), 150);
            }
        };

        recognition.onerror = () => {
            setMicState(false);
            input.placeholder = 'Ask about products, orders, printing...';
        };
    } else if (micBtn) {
        micBtn.style.display = 'none';
    }

    // Modal open/close handling
    toggleBtn.addEventListener('click', () => {
        modal.classList.toggle('hidden');
        modal.classList.toggle('flex');
        if (!modal.classList.contains('hidden')) {
            setTimeout(() => input && input.focus(), 100);
        }
    });

    closeBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });

    if (backdrop) {
        backdrop.addEventListener('click', () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    }

    // Reset conversation
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            if (confirm('Start a new conversation?')) {
                window.location.reload();
            }
        });
    }

    // Send button active state toggle
    function updateSendButtonState() {
        if (!sendBtn || !input) return;
        const hasText = input.value.trim().length > 0;
        sendBtn.disabled = !hasText;
    }
    input.addEventListener('input', updateSendButtonState);
    updateSendButtonState();

    function escapeHtml(text) {
        return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }

    function getFormattedTime() {
        const now = new Date();
        return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    const PRODUCT_URL = '<?= base_url('product/') ?>';
    const FALLBACK_IMG = 'https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=300&q=80';

    function peso(value) {
        const n = parseFloat(value);
        if (isNaN(n)) return '';
        return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function resolveImgUrl(url) {
        if (!url) return FALLBACK_IMG;
        if (url.startsWith('http://') || url.startsWith('https://')) return url;
        return '<?= base_url() ?>/' + url.replace(/^\/+/, '');
    }

    // Product card inside chat
    function productCard(p) {
        const inStock = Number(p.stock_quantity) > 0;
        const url = PRODUCT_URL + encodeURIComponent(p.id);
        const img = escapeHtml(resolveImgUrl(p.image_url));
        const stockBadge = inStock
            ? '<span class="text-[9px] font-bold text-emerald-700 bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-400 px-1.5 py-0.5 rounded-md">In Stock</span>'
            : '<span class="text-[9px] font-bold text-red-700 bg-red-100 dark:bg-red-950/50 dark:text-red-400 px-1.5 py-0.5 rounded-md">Out of Stock</span>';

        return `
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 overflow-hidden shadow-xs hover:shadow-md transition-all flex flex-col group">
            <a href="${url}" class="block h-24 bg-surface-container overflow-hidden relative">
                <img src="${img}" alt="${escapeHtml(p.name)}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" onerror="this.onerror=null;this.src='${FALLBACK_IMG}';">
                <div class="absolute top-1.5 right-1.5">${stockBadge}</div>
            </a>
            <div class="p-2 flex flex-col flex-grow justify-between gap-1">
                <div>
                    <a href="${url}" class="font-bold text-[11px] leading-snug line-clamp-2 text-on-surface hover:text-primary transition-colors">${escapeHtml(p.name)}</a>
                    ${p.shop_name ? `<div class="text-[9px] text-primary font-semibold uppercase tracking-tight flex items-center gap-0.5 mt-0.5 truncate"><span class="material-symbols-outlined text-[10px]">store</span> ${escapeHtml(p.shop_name)}</div>` : ''}
                </div>
                <div class="pt-1 border-t border-outline-variant/15 flex items-center justify-between mt-auto">
                    <span class="text-primary font-black text-xs">${peso(p.price)}</span>
                    <a href="${url}" class="px-2 py-1 rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-on-primary font-bold text-[10px] transition-all">View</a>
                </div>
            </div>
        </div>`;
    }

    function formatMessageText(text) {
        if (!text) return '';
        let safe = escapeHtml(text);
        safe = safe.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        safe = safe.replace(/\n\n/g, '<br><br>').replace(/\n/g, '<br>');
        return safe;
    }

    function appendAssistant(text, products, actions) {
        if (starterPrompts) starterPrompts.classList.add('hidden');
        if (quickChips) quickChips.classList.remove('hidden');

        const wrap = document.createElement('div');
        wrap.className = 'flex items-start gap-2 max-w-[95%] animate-in fade-in slide-in-from-bottom-2 duration-200';

        let productsHtml = '';
        if (Array.isArray(products) && products.length > 0) {
            productsHtml = '<div class="grid grid-cols-2 gap-2 w-full pt-2">' + products.map(productCard).join('') + '</div>';
        }

        let actionsHtml = '';
        if (Array.isArray(actions) && actions.length > 0) {
            actionsHtml = '<div class="pt-2 flex flex-wrap gap-1.5">' + actions.map(act => `
                <a href="${escapeHtml(act.url)}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary text-on-primary text-xs font-bold hover:bg-primary/90 transition-all shadow-xs">
                    ${act.icon ? `<span class="material-symbols-outlined text-[16px]">${escapeHtml(act.icon)}</span>` : ''}
                    <span>${escapeHtml(act.label)}</span>
                    <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </a>
            `).join('') + '</div>';
        }

        wrap.innerHTML = `
            <div class="w-7 h-7 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 mt-1">
                <span class="material-symbols-outlined text-[16px]">smart_toy</span>
            </div>
            <div class="space-y-sm flex-1">
                <div class="bg-surface-container-low text-on-surface p-3.5 rounded-2xl rounded-tl-xs border border-outline-variant/20 shadow-xs leading-relaxed">
                    ${formatMessageText(text)}
                    ${actionsHtml}
                    ${productsHtml}
                </div>
                <span class="text-[10px] text-outline font-medium block px-1">${getFormattedTime()}</span>
            </div>
        `;

        history.appendChild(wrap);
        history.scrollTop = history.scrollHeight;
    }

    function appendUserMessage(text) {
        if (starterPrompts) starterPrompts.classList.add('hidden');
        if (quickChips) quickChips.classList.remove('hidden');

        const wrap = document.createElement('div');
        wrap.className = 'flex flex-col items-end gap-1 ml-auto max-w-[85%] animate-in fade-in slide-in-from-bottom-2 duration-200';

        wrap.innerHTML = `
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-3.5 rounded-2xl rounded-tr-xs shadow-xs font-medium leading-relaxed">
                ${escapeHtml(text)}
            </div>
            <span class="text-[10px] text-outline font-medium px-1">${getFormattedTime()}</span>
        `;

        history.appendChild(wrap);
        history.scrollTop = history.scrollHeight;
    }

    function appendTyping() {
        const el = document.createElement('div');
        el.className = 'flex items-start gap-2 max-w-[85%] animate-in fade-in duration-150';
        el.innerHTML = `
            <div class="w-7 h-7 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 mt-1">
                <span class="material-symbols-outlined text-[16px]">smart_toy</span>
            </div>
            <div class="bg-surface-container-low text-on-surface px-3.5 py-2.5 rounded-2xl rounded-tl-xs border border-outline-variant/20 shadow-xs flex items-center gap-2">
                <div class="flex items-center gap-1">
                    <span class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce" style="animation-delay:0ms"></span>
                    <span class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce" style="animation-delay:150ms"></span>
                    <span class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce" style="animation-delay:300ms"></span>
                </div>
                <span class="text-[11px] text-on-surface-variant font-medium">Blax AI is searching...</span>
            </div>
        `;
        history.appendChild(el);
        history.scrollTop = history.scrollHeight;
        return el;
    }

    let busy = false;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (busy) return;

        const query = input.value.trim();
        if (!query) return;

        busy = true;
        appendUserMessage(query);
        input.value = '';
        updateSendButtonState();

        const typing = appendTyping();

        try {
            const fd = new FormData();
            fd.append('message', query);
            fd.append('<?= esc($csrfName) ?>', '<?= esc($csrfToken) ?>');
            const response = await fetch('<?= base_url('ai-assistant/chat') ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            });

            let data = null;
            try { data = await response.json(); } catch (parseErr) { data = null; }

            if (typing.parentNode) history.removeChild(typing);

            if (!response.ok || !data || data.status === 'error') {
                const errMsg = (data && data.message) ? data.message : 'I had trouble checking that just now. Could you please rephrase your request?';
                appendAssistant(errMsg, [], []);
            } else {
                appendAssistant(data.reply || 'Here is what I found for you in Polomolok:', data.products, data.actions);
            }
        } catch (err) {
            if (typing.parentNode) history.removeChild(typing);
            appendAssistant('I could not connect to the assistant service. Please check your internet connection and try again.', [], []);
        } finally {
            busy = false;
            input.focus();
        }
    });

    // Prompt chip listeners
    document.body.addEventListener('click', (e) => {
        const btn = e.target.closest('.faq-prompt-btn');
        if (btn) {
            const prompt = btn.dataset.prompt;
            if (prompt && input) {
                input.value = prompt;
                updateSendButtonState();
                form.dispatchEvent(new Event('submit', { cancelable: true }));
            }
        }
    });
})();
</script>