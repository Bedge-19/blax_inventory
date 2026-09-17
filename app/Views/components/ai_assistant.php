<?php
    $csrfToken = csrf_hash();
    $csrfName = csrf_token();
    $greeting = $greeting ?? 'Hello! How can I help you discover great products on RHK General Merchandise today?';
?>

<!-- AI Assistant Floating Toggle Button -->
<button id="ai-toggle-btn" aria-label="AI Assistant" class="fixed bottom-4 right-4 sm:bottom-8 sm:right-8 w-16 h-16 bg-[#2563eb] text-white rounded-full shadow-xl hover:scale-110 transition-transform flex items-center justify-center z-40 active:scale-95">

    <span class="material-symbols-outlined text-3xl fill-icon">smart_toy</span>

</button>

<!-- AI Assistant Floating Widget -->
<div id="ai-modal" class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 pointer-events-none hidden justify-end items-end p-0">

    <div class="glass-panel w-[calc(100vw_-_32px)] sm:w-[380px] h-[500px] max-h-[calc(100vh_-_80px)] rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.2)] flex flex-col pointer-events-auto transition-all duration-300 border border-outline-variant/30">

        <div class="px-md py-sm border-b border-outline-variant/30 flex justify-between items-center bg-surface-container-lowest/60 rounded-t-2xl">

            <div class="flex items-center space-x-md">

                <div class="w-10 h-10 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center">

                    <span class="material-symbols-outlined text-title-lg fill-icon">smart_toy</span>

                </div>

                <div>

                    <h2 class="text-title-lg font-title-lg text-on-surface m-0">Blax Assistant</h2>
                    <p class="text-label-sm font-label-sm text-on-surface-variant m-0">Powered by AI</p>

                </div>

            </div>

            <button id="ai-close-btn" class="p-2 text-on-surface-variant hover:bg-surface-container rounded-full transition-colors">

                <span class="material-symbols-outlined">close</span>

            </button>

        </div>

        <!-- FAQ & Quick Help Bar -->
        <div class="px-md py-1.5 bg-surface-container/60 border-b border-outline-variant/20 flex items-center justify-between text-xs">
            <button type="button" id="toggle-ai-faq" class="text-primary font-semibold flex items-center gap-1 hover:underline">
                <span class="material-symbols-outlined text-[15px]">help_outline</span>
                <span>FAQ & Suggested Prompts</span>
                <span id="faq-chevron" class="material-symbols-outlined text-[14px]">expand_more</span>
            </button>
            <span class="text-[10px] text-outline">Tap prompt to ask</span>
        </div>

        <!-- Collapsible FAQ Drawer -->
        <div id="ai-faq-drawer" class="hidden bg-surface-container-low p-3 border-b border-outline-variant/30 space-y-1.5 text-xs max-h-44 overflow-y-auto">
            <button type="button" class="faq-prompt-btn w-full text-left p-2 rounded-lg bg-surface-container hover:bg-primary/10 hover:text-primary transition-colors flex items-center gap-2" data-prompt="How do I search or ask about a product?">
                <span class="material-symbols-outlined text-[15px] text-primary">search</span>
                <span class="font-medium">How do I ask about a product?</span>
            </button>
            <button type="button" class="faq-prompt-btn w-full text-left p-2 rounded-lg bg-surface-container hover:bg-primary/10 hover:text-primary transition-colors flex items-center gap-2" data-prompt="How do I track my order and get my store pickup QR code?">
                <span class="material-symbols-outlined text-[15px] text-primary">qr_code_2</span>
                <span class="font-medium">How do I track orders or use pickup QR?</span>
            </button>
            <button type="button" class="faq-prompt-btn w-full text-left p-2 rounded-lg bg-surface-container hover:bg-primary/10 hover:text-primary transition-colors flex items-center gap-2" data-prompt="How does document printing and downpayment work?">
                <span class="material-symbols-outlined text-[15px] text-primary">print</span>
                <span class="font-medium">How do PDF printing services work?</span>
            </button>
            <button type="button" class="faq-prompt-btn w-full text-left p-2 rounded-lg bg-surface-container hover:bg-primary/10 hover:text-primary transition-colors flex items-center gap-2" data-prompt="How do I report a suspicious or fraudulent shop?">
                <span class="material-symbols-outlined text-[15px] text-primary">flag</span>
                <span class="font-medium">How do I report a store or fake item?</span>
            </button>
        </div>

        <!-- Chat History -->
        <div id="chat-history" class="flex-1 overflow-y-auto p-lg space-y-md custom-scrollbar bg-surface-bright/40 text-sm">

            <div class="flex justify-start">

                <div class="bg-surface-container text-on-surface px-md py-sm rounded-2xl rounded-tl-sm max-w-[85%] shadow-sm">
                    <?= esc($greeting) ?>
                </div>

            </div>

        </div>

        <!-- Input Area -->
        <div class="p-md border-t border-outline-variant/30 bg-surface-container-lowest rounded-b-2xl">

            <form id="ai-chat-form" class="flex items-center space-x-sm bg-surface-container-low rounded-xl border border-outline-variant/50 p-xs focus-within:border-primary transition-all">

                <?= csrf_field() ?>

                <input type="hidden" name="csrf_name" value="<?= esc($csrfName) ?>">
                <input type="hidden" name="csrf_token" value="<?= esc($csrfToken) ?>">

                <input id="ai-input" class="w-full bg-transparent border-none focus:ring-0 text-body-md font-body-md text-on-surface placeholder-on-surface-variant/60 py-2 px-3 text-sm" placeholder="Ask me anything..." type="text" autocomplete="off">

                <button type="button" id="ai-mic-btn" title="Voice input" class="p-2 text-on-surface-variant hover:text-primary rounded-lg transition-colors">
                    <span class="material-symbols-outlined" style="font-size:22px">mic</span>
                </button>

                <button type="submit" class="p-2 bg-primary text-on-primary rounded-lg hover:bg-primary-container transition-colors shadow-sm">
                    <span class="material-symbols-outlined">send</span>
                </button>

            </form>

            <p class="text-[10px] text-on-surface-variant/70 mt-xs px-xs">AI responses may occasionally be inaccurate.</p>

        </div>

    </div>

</div>

<script>
(function () {
    const toggleBtn = document.getElementById('ai-toggle-btn');
    const closeBtn = document.getElementById('ai-close-btn');
    const modal = document.getElementById('ai-modal');
    const form = document.getElementById('ai-chat-form');
    const input = document.getElementById('ai-input');
    const history = document.getElementById('chat-history');
    const micBtn = document.getElementById('ai-mic-btn');
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
            micBtn.classList.toggle('text-on-surface-variant', !active);
        }

        micBtn.addEventListener('click', () => {
            if (listening) {
                recognition.stop();
            } else {
                finalTranscript = '';
                input.value = '';
                input.placeholder = 'Listening...';
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
        };

        recognition.onend = () => {
            setMicState(false);
            input.placeholder = 'Ask me anything...';
            if (input.value.trim()) {
                setTimeout(() => form.requestSubmit(), 150);
            }
        };

        recognition.onerror = () => {
            setMicState(false);
            input.placeholder = 'Ask me anything...';
        };
    } else if (micBtn) {
        micBtn.style.display = 'none';
    }

    toggleBtn.addEventListener('click', () => {
        modal.classList.toggle('hidden');
        modal.classList.toggle('flex');
    });
    closeBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });

    function escapeHtml(text) {
        return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }

    const PRODUCT_URL = '<?= base_url('product/') ?>';
    const FALLBACK_IMG = 'https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=300&q=80';

    function peso(value) {
        const n = parseFloat(value);
        if (isNaN(n)) return '';
        return '\u20B1' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function resolveImgUrl(url) {
        if (!url) return FALLBACK_IMG;
        if (url.startsWith('http://') || url.startsWith('https://')) return url;
        return '<?= base_url() ?>/' + url.replace(/^\/+/, '');
    }

    // Product card rendered inside the chat. Every value comes from the
    // server's whitelisted product DTO - nothing is generated client-side.
    function productCard(p) {
        const inStock = Number(p.stock_quantity) > 0;
        const url = PRODUCT_URL + encodeURIComponent(p.id);
        // Image goes in an escaped src attribute (not a CSS url(), which would
        // let a single quote in a seller-supplied URL break out of the context).
        const img = escapeHtml(resolveImgUrl(p.image_url));
        const stockBadge = inStock
            ? '<span class="text-[10px] font-semibold text-green-700 bg-green-100 px-2 py-[2px] rounded-full">In stock</span>'
            : '<span class="text-[10px] font-semibold text-white bg-error px-2 py-[2px] rounded-full">Out of stock</span>';

        return `
        <div class="bg-white rounded-xl border border-outline-variant/30 overflow-hidden shadow-sm hover:shadow-md transition-all flex flex-col">
            <a href="${url}" class="block h-24 bg-surface-container overflow-hidden">
                <img src="${img}" alt="${escapeHtml(p.name)}" loading="lazy" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='${FALLBACK_IMG}';">
            </a>
            <div class="p-2 flex flex-col flex-grow gap-1">
                <div class="font-semibold text-[11px] leading-tight line-clamp-2 text-on-surface">${escapeHtml(p.name)}</div>
                ${p.shop_name ? `<div class="text-[9px] text-primary uppercase tracking-tight line-clamp-1">${escapeHtml(p.shop_name)}</div>` : ''}
                <div class="flex items-center justify-between mt-auto pt-1">
                    <span class="text-primary font-bold text-xs">${peso(p.price)}</span>
                    ${stockBadge}
                </div>
                <a href="${url}" class="mt-1 block text-center bg-primary text-on-primary text-[10px] font-semibold py-1.5 rounded-lg hover:bg-primary-container transition-colors">View Product</a>
            </div>
        </div>`;
    }

    function appendAssistant(text, products) {
        const wrap = document.createElement('div');
        wrap.className = 'flex justify-start flex-col gap-2';

        let html = `<div class="bg-surface-container text-on-surface px-md py-sm rounded-2xl rounded-tl-sm max-w-[90%] shadow-sm">${escapeHtml(text)}</div>`;

        if (Array.isArray(products) && products.length > 0) {
            html += '<div class="grid grid-cols-2 gap-2 w-full">' + products.map(productCard).join('') + '</div>';
        }

        wrap.innerHTML = html;
        history.appendChild(wrap);
        history.scrollTop = history.scrollHeight;
    }

    function appendTyping() {
        const el = document.createElement('div');
        el.className = 'flex justify-start';
        el.innerHTML = `<div class="bg-surface-container px-md py-sm rounded-2xl rounded-tl-sm shadow-sm flex items-center gap-1" aria-label="Assistant is typing">
            <span class="w-1.5 h-1.5 bg-on-surface-variant/60 rounded-full animate-bounce" style="animation-delay:0ms"></span>
            <span class="w-1.5 h-1.5 bg-on-surface-variant/60 rounded-full animate-bounce" style="animation-delay:150ms"></span>
            <span class="w-1.5 h-1.5 bg-on-surface-variant/60 rounded-full animate-bounce" style="animation-delay:300ms"></span>
        </div>`;
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
        const userMsg = document.createElement('div');
        userMsg.className = 'flex justify-end';
        userMsg.innerHTML = `<div class="bg-primary text-on-primary px-md py-sm rounded-2xl rounded-tr-sm max-w-[85%] shadow-sm">${escapeHtml(query)}</div>`;
        history.appendChild(userMsg);
        input.value = '';
        history.scrollTop = history.scrollHeight;

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
                const errMsg = (data && data.message) ? data.message : 'Sorry, I had trouble replying just now. Please try again.';
                appendAssistant(errMsg, []);
            } else {
                appendAssistant(data.reply || 'Here is what I found for you:', data.products);
            }
        } catch (err) {
            if (typing.parentNode) history.removeChild(typing);
            appendAssistant('I could not reach the assistant. Please check your connection and try again.', []);
        } finally {
            busy = false;
            input.focus();
        }
    });

    // FAQ Drawer Toggle & Prompt Dispatch
    const faqToggleBtn = document.getElementById('toggle-ai-faq');
    const faqDrawer = document.getElementById('ai-faq-drawer');
    const faqChevron = document.getElementById('faq-chevron');

    if (faqToggleBtn && faqDrawer) {
        faqToggleBtn.addEventListener('click', () => {
            faqDrawer.classList.toggle('hidden');
            if (faqChevron) faqChevron.classList.toggle('rotate-180');
        });
    }

    document.querySelectorAll('.faq-prompt-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const prompt = btn.dataset.prompt;
            if (prompt && input) {
                input.value = prompt;
                if (faqDrawer) faqDrawer.classList.add('hidden');
                if (faqChevron) faqChevron.classList.remove('rotate-180');
                form.dispatchEvent(new Event('submit', { cancelable: true }));
            }
        });
    });
})();
</script>