

<div x-data="cosmeBotWidget()" x-init="init()"
    class="fixed z-[9999] select-none"
    :style="`left:${x}px; top:${y}px`"
    style="touch-action:none">

    
    <button type="button"
        @pointerdown="startDrag($event,'bubble')"
        @click="onBubbleClick()"
        class="relative w-16 h-16 rounded-full grid place-items-center transition-all duration-200 hover:scale-105 active:scale-95"
        aria-label="Mở CosmeBot">
        
        <div class="absolute inset-0 rounded-full bg-gradient-to-br from-rose-400/40 via-pink-400/30 to-rose-500/40 blur-xl animate-pulse"></div>

        
        <div class="absolute inset-0 rounded-full bg-gradient-to-br from-rose-500/90 to-pink-600/90 shadow-[0_4px_20px_rgba(244,63,94,0.25)]"></div>

        
        <div class="relative w-14 h-14 rounded-full bg-white/95 shadow-inner grid place-items-center">
            
            <svg class="w-10 h-10" viewBox="0 0 64 64" fill="none" aria-hidden="true">
                
                <circle cx="32" cy="32" r="30" fill="url(#smileGradient)" />
                <defs>
                    <linearGradient id="smileGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#f43f5e;stop-opacity:0.95" />
                        <stop offset="100%" style="stop-color:#ec4899;stop-opacity:0.95" />
                    </linearGradient>
                </defs>
                
                <circle cx="24" cy="28" r="4" fill="white" />
                
                <circle cx="40" cy="28" r="4" fill="white" />
                
                <path d="M22 40 Q32 48 42 40" stroke="white" stroke-width="3" stroke-linecap="round" fill="none" />
            </svg>
        </div>

        
        <span x-show="unreadCount > 0" x-transition
            class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1.5 text-[11px] font-bold leading-5 bg-red-500 text-white rounded-full text-center ring-2 ring-white flex items-center justify-center z-10">
            <span x-text="unreadCount"></span>
        </span>
    </button>

    
    <div x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="absolute"
        :class="panelSide==='right' ? 'origin-bottom-right right-0' : 'origin-bottom-left left-0'"
        :style="`width:${panel.w}px; height:${panel.h}px; top:${panelTop}px`">

        <div class="bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden h-full flex flex-col">

            
            <div class="px-4 py-3 bg-white border-b border-slate-200 flex items-center gap-3 cursor-grab active:cursor-grabbing"
                @pointerdown="startDrag($event,'header')" style="touch-action:none">
                <div class="relative w-9 h-9 rounded-full grid place-items-center">
                    
                    <div class="absolute inset-0 rounded-full bg-gradient-to-br from-rose-400/30 via-pink-400/20 to-rose-500/30 blur-md"></div>
                    
                    <div class="absolute inset-0 rounded-full bg-gradient-to-br from-rose-500/85 to-pink-600/85"></div>
                    
                    <div class="relative w-8 h-8 rounded-full bg-white/95 grid place-items-center">
                        
                        <svg class="w-6 h-6" viewBox="0 0 64 64" fill="none" aria-hidden="true">
                            <circle cx="32" cy="32" r="30" fill="url(#headerGradient)" />
                            <defs>
                                <linearGradient id="headerGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#f43f5e;stop-opacity:0.95" />
                                    <stop offset="100%" style="stop-color:#ec4899;stop-opacity:0.95" />
                                </linearGradient>
                            </defs>
                            <circle cx="24" cy="28" r="3.5" fill="white" />
                            <circle cx="40" cy="28" r="3.5" fill="white" />
                            <path d="M22 40 Q32 47 42 40" stroke="white" stroke-width="2.5" stroke-linecap="round" fill="none" />
                        </svg>
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-bold text-sm text-slate-900 flex items-center gap-2">
                        <span>CosmeBot</span>
                        <span x-show="isTyping" class="inline-flex items-center gap-1 text-xs font-normal text-slate-500">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            <span>Đang trả lời...</span>
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-600">Tư vấn mỹ phẩm thông minh ✨</div>
                </div>
                <button class="w-8 h-8 grid place-items-center rounded-lg hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition"
                    @click="open=false" aria-label="Đóng">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            
            <div id="botScroll"
                class="flex-1 overflow-y-auto px-4 py-4 space-y-3 text-sm bg-slate-50"
                style="scroll-behavior: smooth;">

                
                <div x-show="messages.length === 0" x-transition
                    class="py-4 space-y-4">
                    <div class="text-center">
                        <div class="inline-block px-4 py-3 rounded-xl bg-white border border-slate-200 shadow-sm">
                            <div class="font-semibold text-slate-900 mb-1 text-base">👋 Chào bạn!</div>
                            <div class="text-xs text-slate-600">Mình là CosmeBot, sẵn sàng tư vấn mỹ phẩm cho bạn nè ✨</div>
                        </div>
                    </div>

                    
                    <div x-show="Object.keys(quickQuestions).length > 0"
                        class="space-y-4"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0">

                        
                        <div class="flex items-center gap-3 px-2">
                            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-slate-200 to-slate-200"></div>
                            <div class="text-xs font-bold text-slate-700 uppercase tracking-wider whitespace-nowrap">Câu hỏi thường gặp</div>
                            <div class="h-px flex-1 bg-gradient-to-r from-slate-200 via-slate-200 to-transparent"></div>
                        </div>

                        
                        <div class="space-y-4 max-h-[400px] overflow-y-auto pr-1" style="scrollbar-width: thin; scrollbar-color: rgba(244, 63, 94, 0.3) transparent;">
                            <template x-for="(category, catName) in quickQuestions" :key="catName">
                                <div class="space-y-2.5 px-2"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 translate-x-2"
                                    x-transition:enter-end="opacity-100 translate-x-0">
                                    
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="text-sm font-semibold text-slate-700" x-text="getCategoryLabel(catName)"></div>
                                        <div class="h-px flex-1 bg-slate-200"></div>
                                        <span class="text-xs text-slate-500 font-normal" x-text="category.length + ' câu hỏi'"></span>
                                    </div>

                                    
                                    <div class="space-y-2">
                                        <template x-for="item in category" :key="item.id">
                                            <button
                                                @click="selectQuestion(item)"
                                                class="w-full px-4 py-3 text-left text-sm font-medium rounded-lg border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 transition-all duration-200 active:scale-[0.98] shadow-sm hover:shadow-md flex items-center gap-3 group"
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0 translate-y-1"
                                                x-transition:enter-end="opacity-100 translate-y-0">
                                                
                                                <div class="relative z-10 flex-shrink-0 w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center group-hover:bg-slate-200 transition-all">
                                                    <span x-show="item.icon" class="text-lg" x-text="item.icon"></span>
                                                    <span x-show="!item.icon" class="text-base">💬</span>
                                                </div>

                                                
                                                <span x-text="item.question" class="relative z-10 flex-1 group-hover:text-slate-900 font-semibold leading-tight"></span>

                                                
                                                <i class="fa-solid fa-chevron-right text-xs text-slate-400 group-hover:text-slate-600 relative z-10 transition-transform group-hover:translate-x-1 flex-shrink-0"></i>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                
                <template x-for="m in messages" :key="m.id">
                    <div :class="m.role==='user' ? 'flex justify-end' : 'flex justify-start'"
                         :data-msg-id="m.id">
                        <div class="max-w-[85%]">
                            
                            <div x-show="m.role==='user'"
                                class="inline-block px-4 py-2.5 rounded-lg rounded-tr-sm bg-rose-600 text-white shadow-sm"
                                style="animation: msgSlideIn 0.2s ease-out;">
                                <span x-html="m.html" class="text-sm leading-relaxed"></span>
                            </div>

                            
                            <div x-show="m.role==='bot'"
                                class="inline-block px-4 py-3 rounded-lg rounded-tl-sm bg-white border border-slate-200 shadow-sm hover:shadow-md transition-shadow duration-200"
                                style="animation: msgSlideIn 0.3s ease-out;">
                                <div class="flex items-start gap-2.5">
                                    <div class="relative w-7 h-7 rounded-full flex-shrink-0 grid place-items-center mt-0.5">
                                        
                                        <div class="absolute inset-0 rounded-full bg-gradient-to-br from-rose-400/30 via-pink-400/20 to-rose-500/30 blur-sm"></div>
                                        
                                        <div class="absolute inset-0 rounded-full bg-gradient-to-br from-rose-500/85 to-pink-600/85"></div>
                                        
                                        <div class="relative w-6 h-6 rounded-full bg-white/95 grid place-items-center">
                                            <svg class="w-4 h-4" viewBox="0 0 64 64" fill="none">
                                                <circle cx="32" cy="32" r="30" fill="url(#msgGradient)" />
                                                <defs>
                                                    <linearGradient id="msgGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                        <stop offset="0%" style="stop-color:#f43f5e;stop-opacity:0.95" />
                                                        <stop offset="100%" style="stop-color:#ec4899;stop-opacity:0.95" />
                                                    </linearGradient>
                                                </defs>
                                                <circle cx="24" cy="28" r="3" fill="white" />
                                                <circle cx="40" cy="28" r="3" fill="white" />
                                                <path d="M22 40 Q32 46 42 40" stroke="white" stroke-width="2" stroke-linecap="round" fill="none" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div x-html="m.html" class="text-sm text-slate-800 leading-relaxed block prose prose-sm max-w-none">
                                            <style>
                                                .prose strong { font-weight: 600; color: #e11d48; }
                                                .prose em { font-style: italic; color: #9f1239; }
                                                .prose ul, .prose ol { margin: 0.5rem 0; padding-left: 1.25rem; }
                                                .prose li { margin: 0.25rem 0; }
                                            </style>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            
                            <div class="mt-3 flex gap-2 flex-wrap"
                                x-show="m.suggestions && m.suggestions.length && m.role==='bot'"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0">
                                <template x-for="s in m.suggestions" :key="s">
                                    <button
                                        class="px-4 py-2.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 transition-all duration-200 active:scale-95 shadow-sm hover:shadow-md flex items-center gap-2"
                                        @click="quick(s)">
                                        <span x-show="s === '/reset'" class="text-sm">🔄</span>
                                        <span x-show="s === 'Tư vấn mỹ phẩm'" class="text-sm">💄</span>
                                        <span x-text="s === '/reset' ? 'Reset' : s"></span>
                                    </button>
                                </template>
                            </div>

                            
                            <div class="mt-3 grid grid-cols-2 gap-2.5"
                                x-show="m.products && m.products.length && m.role==='bot'"
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0">
                                <template x-for="p in m.products" :key="p.url">
                                    <a :href="p.url" target="_blank"
                                        class="group border border-rose-100 rounded-xl overflow-hidden hover:shadow-xl bg-white transition-all duration-300 hover:border-rose-400 hover:-translate-y-1 relative js-card">
                                        <span class="shine"></span>

                                        
                                        <div class="relative w-full h-32 bg-gradient-to-br from-rose-50 via-pink-50 to-rose-50 overflow-hidden">
                                            <img :src="p.image || '/images/placeholder.png'"
                                                x-on:error="$event.target.src='/images/placeholder.png'"
                                                loading="lazy"
                                                decoding="async"
                                                class="w-full h-full object-contain p-2.5 group-hover:scale-110 transition-transform duration-300"
                                                :alt="p.name">

                                            
                                            <span x-show="p.discount"
                                                class="absolute top-1.5 right-1.5 px-2 py-1 text-[10px] font-bold bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-full shadow-md">
                                                <span x-text="'-'+p.discount+'%'"></span>
                                            </span>

                                            
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                        </div>

                                        
                                        <div class="p-3 bg-white">
                                            <div class="line-clamp-2 text-[12px] font-semibold text-slate-800 mb-2 min-h-[2.5rem] leading-tight group-hover:text-rose-600 transition-colors duration-200"
                                                x-text="p.name"></div>
                                            <div class="flex items-baseline gap-1.5">
                                                <span class="text-[15px] font-bold text-rose-600"
                                                    x-text="formatVND(p.price_min)"></span>
                                                <span x-show="p.compare_at"
                                                    class="text-[11px] text-slate-400 line-through"
                                                    x-text="formatVND(p.compare_at)"></span>
                                            </div>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                
                <div x-show="isTyping" class="flex justify-start" x-transition>
                    <div class="max-w-[85%]">
                        <div class="inline-block px-4 py-2.5 rounded-2xl rounded-tl-sm bg-white border border-rose-100 shadow-sm">
                            <div class="flex items-center gap-1.5">
                                <div class="w-6 h-6 rounded-full bg-rose-100 flex-shrink-0 grid place-items-center">
                                    <svg class="w-4 h-4 text-rose-600" viewBox="0 0 64 64" fill="none">
                                        <circle cx="32" cy="32" r="28" fill="currentColor" opacity="0.2" />
                                        <circle cx="24" cy="28" r="3" fill="currentColor" />
                                        <circle cx="40" cy="28" r="3" fill="currentColor" />
                                    </svg>
                                </div>
                                <div class="flex gap-1">
                                    <span class="typing-dot w-2 h-2 rounded-full bg-rose-400"></span>
                                    <span class="typing-dot w-2 h-2 rounded-full bg-rose-400"></span>
                                    <span class="typing-dot w-2 h-2 rounded-full bg-rose-400"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div x-show="messages.length > 0 && Object.keys(quickQuestions).length > 0"
                    class="mt-4 pt-4 border-t border-slate-200"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0">
                    <button @click="showQuickQuestions = !showQuickQuestions"
                        class="w-full px-3 py-2 text-xs font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition flex items-center justify-center gap-2">
                        <i class="fa-solid" :class="showQuickQuestions ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        <span x-text="showQuickQuestions ? 'Ẩn câu hỏi thường gặp' : 'Xem câu hỏi thường gặp'"></span>
                    </button>

                    <div x-show="showQuickQuestions"
                        class="mt-3 space-y-3"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 max-h-0"
                        x-transition:enter-end="opacity-100 max-h-[500px]"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 max-h-[500px]"
                        x-transition:leave-end="opacity-0 max-h-0">
                        <template x-for="(category, catName) in quickQuestions" :key="catName">
                            <div class="space-y-2">
                                <div class="text-xs font-semibold text-slate-600 px-2" x-text="getCategoryLabel(catName)"></div>
                                <div class="grid grid-cols-1 gap-1.5 px-2">
                                    <template x-for="item in category" :key="item.id">
                                        <button
                                            @click="selectQuestion(item)"
                                            class="w-full px-3 py-2 text-xs font-medium rounded-lg border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 transition-all duration-200 active:scale-[0.98] shadow-sm hover:shadow-md flex items-center gap-2 group">
                                            <span x-show="item.icon" class="text-base" x-text="item.icon"></span>
                                            <span x-text="item.question" class="flex-1 text-left group-hover:text-slate-900"></span>
                                            <i class="fa-solid fa-chevron-right text-xs text-slate-400 group-hover:text-slate-600"></i>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            
            <form @submit.prevent="send()"
                class="p-3 border-t border-slate-200 bg-white flex items-center gap-2.5">
                <?php echo csrf_field(); ?>
                <div class="flex-1 relative">
                    <input type="text"
                        x-model="input"
                        x-ref="inputField"
                        placeholder="Nhập tin nhắn của bạn..."
                        class="w-full px-4 py-2.5 pr-11 rounded-lg border border-slate-300 outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all text-sm bg-white shadow-sm hover:shadow-md placeholder:text-slate-400"
                        @keydown.enter.prevent="send()">
                    <button type="button"
                        @click="toggleEmoji()"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 w-8 h-8 grid place-items-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-all duration-200">
                        <i class="fa-regular fa-face-smile text-sm"></i>
                    </button>
                </div>
                <button type="submit"
                    :disabled="!input.trim() || isTyping"
                    class="w-10 h-10 rounded-lg bg-rose-600 text-white grid place-items-center shadow-sm hover:shadow-md hover:bg-rose-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed active:scale-95">
                    <i class="fa-solid fa-paper-plane text-sm"></i>
                </button>
            </form>
        </div>
    </div>
</div>


<style>
    @keyframes bob {
        0%, 100% { transform: translateY(0) }
        50% { transform: translateY(-3px) }
    }
    .bob { animation: bob 2.6s ease-in-out infinite; }

    .pulse-ring {
        position: absolute;
        inset: -8px;
        border-radius: 9999px;
        pointer-events: none;
        background: radial-gradient(circle, rgba(244, 63, 94, 0.3) 0%, rgba(244, 63, 94, 0.1) 50%, transparent 70%);
        animation: pulse 2.5s ease-out infinite;
    }
    @keyframes pulse {
        0% { transform: scale(0.9); opacity: 0.8; }
        50% { transform: scale(1.1); opacity: 0.5; }
        100% { transform: scale(1.3); opacity: 0; }
    }

    @keyframes msgSlideIn {
        0% {
            transform: translateY(4px) scale(0.96);
            opacity: 0;
        }
        100% {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    }

    /* Shine effect cho product cards */
    .js-card {
        position: relative;
        overflow: hidden;
    }
    .js-card .shine {
        position: absolute;
        inset: 0;
        border-radius: 0.75rem;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.3s ease;
        mix-blend-mode: overlay;
        background: radial-gradient(300px circle at var(--mx, -100px) var(--my, -100px),
            rgba(255, 255, 255, 0.4), rgba(255, 255, 255, 0) 40%);
    }
    .js-card:hover .shine {
        opacity: 1;
    }

    /* Smooth scroll */
    #botScroll {
        scrollbar-width: thin;
        scrollbar-color: rgba(244, 63, 94, 0.3) transparent;
    }
    #botScroll::-webkit-scrollbar {
        width: 6px;
    }
    #botScroll::-webkit-scrollbar-track {
        background: transparent;
    }
    #botScroll::-webkit-scrollbar-thumb {
        background: rgba(244, 63, 94, 0.3);
        border-radius: 3px;
    }
    #botScroll::-webkit-scrollbar-thumb:hover {
        background: rgba(244, 63, 94, 0.5);
    }
</style>

<script>
    // Shine effect cho product cards
    (function() {
        if (window.__shineBound) return;
        window.__shineBound = true;
        let raf = 0;
        document.addEventListener('pointermove', (e) => {
            if (raf) return;
            raf = requestAnimationFrame(() => {
                raf = 0;
                const card = e.target.closest('.js-card');
                if (!card) return;
                const r = card.getBoundingClientRect();
                card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
                card.style.setProperty('--my', (e.clientY - r.top) + 'px');
            });
        }, { passive: true });
    })();
</script>

<script>
    function cosmeBotWidget() {
        return {
            open: false,
            input: '',
            isTyping: false,
            messages: [],
            id: 1,
            unreadCount: 0,
            quickQuestions: {}, // Questions từ tools, grouped by category
            showQuickQuestions: false, // Toggle để hiển thị/ẩn questions khi có messages

            // Drag state
            x: 0,
            y: 0,
            dragging: false,
            dragOrigin: 'bubble',
            startCX: 0,
            startCY: 0,
            ox: 0,
            oy: 0,
            clickGuard: false,
            dragThreshold: 6,
            raf: 0,

            panelSide: 'right',
            bubble: { w: 64, h: 64 },
            panel: { w: 400, h: 600 },
            panelTop: -536,
            openBelow: false,

            async init() {
                window.Bot = {
                    open: (p = '') => {
                        this.open = true;
                        if (p) this.input = p;
                        this.$nextTick(() => {
                            this.scrollBottom();
                            this.$refs.inputField?.focus();
                        });
                    },
                    close: () => { this.open = false; }
                };

                // Load quick questions từ tools
                await this.loadQuickQuestions();

                const saved = JSON.parse(localStorage.getItem('cosmebot.pos') || '{}');
                this.x = (saved.x ?? (window.innerWidth - this.bubble.w - 16));
                this.y = (saved.y ?? (window.innerHeight - this.bubble.h - 16));
                this.updateSide();
                this.updatePlacement();

                this._onMove = (e) => {
                    if (!this.dragging) return;
                    const p = this._pt(e);
                    const nx = p.x - this.ox, ny = p.y - this.oy;

                    if (!this.clickGuard) {
                        const dx = Math.abs(e.clientX - this.startCX);
                        const dy = Math.abs(e.clientY - this.startCY);
                        if (dx > this.dragThreshold || dy > this.dragThreshold) this.clickGuard = true;
                    }

                    if (!this.raf) {
                        this.raf = requestAnimationFrame(() => {
                            this.raf = 0;
                            this.x = nx;
                            this.y = ny;
                            this.clamp(true);
                        });
                    }
                };

                this._onUp = (e) => {
                    this.dragging = false;
                    try {
                        e.currentTarget?.releasePointerCapture?.(e.pointerId);
                    } catch (_) {}
                    document.removeEventListener('pointermove', this._onMove);
                    document.removeEventListener('pointerup', this._onUp);
                    this.clamp(false);
                    localStorage.setItem('cosmebot.pos', JSON.stringify({ x: this.x, y: this.y }));
                };

                window.addEventListener('resize', () => this.clamp(false));
            },

            startDrag(e, origin = 'bubble') {
                e.preventDefault();
                this.dragOrigin = origin;
                this.dragging = true;
                this.clickGuard = false;
                this.startCX = e.clientX;
                this.startCY = e.clientY;

                const p = this._pt(e);
                this.ox = p.x - this.x;
                this.oy = p.y - this.y;

                try {
                    e.currentTarget?.setPointerCapture?.(e.pointerId);
                } catch (_) {}
                document.addEventListener('pointermove', this._onMove, { passive: false });
                document.addEventListener('pointerup', this._onUp, { passive: true });
            },

            _pt(e) {
                return { x: e.clientX, y: e.clientY };
            },

            clamp(clampPosOnly = false) {
                const margin = 8;
                const W = this.open ? Math.max(this.panel.w, this.bubble.w) : this.bubble.w;
                const H = this.open ? (this.openBelow ? this.panel.h + this.bubble.h : this.panel.h) : this.bubble.h;
                const maxX = window.innerWidth - W - margin;
                const maxY = window.innerHeight - H - margin;
                this.x = Math.max(margin, Math.min(this.x, maxX));
                this.y = Math.max(margin, Math.min(this.y, maxY));
                this.updateSide();
                if (!clampPosOnly) this.updatePlacement();
            },

            updateSide() {
                this.panelSide = (this.x > window.innerWidth / 2) ? 'right' : 'left';
            },

            updatePlacement() {
                const spaceAbove = this.y;
                const spaceBelow = window.innerHeight - (this.y + this.bubble.h);
                this.openBelow = (spaceBelow >= this.panel.h + 12) || (spaceBelow >= spaceAbove);
                this.panelTop = this.openBelow ? (this.bubble.h + 8) : -(this.panel.h + 8);
            },

            onBubbleClick() {
                if (this.clickGuard) {
                    this.clickGuard = false;
                    return;
                }
                this.toggle();
            },

            toggle() {
                this.open = !this.open;
                this.updatePlacement();
                this.$nextTick(() => {
                    this.scrollBottom();
                    if (this.open) {
                        this.$refs.inputField?.focus();
                        this.unreadCount = 0;
                    }
                });
                this.clamp(false);
            },

            scrollBottom() {
                this.$nextTick(() => {
                    const el = document.getElementById('botScroll');
                    if (el) {
                        el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
                    }
                });
            },

            quick(text) {
                this.input = text;
                this.send();
            },

            push(role, html, extra = {}) {
                this.messages.push({
                    id: this.id++,
                    role,
                    html,
                    ...extra
                });
                this.scrollBottom();
            },

            formatVND(n) {
                try {
                    return new Intl.NumberFormat('vi-VN').format(Number(n || 0)) + '₫';
                } catch {
                    return (n || 0) + '₫';
                }
            },

            normalizeProducts(list) {
                if (!Array.isArray(list)) return [];
                return list.map(p => ({
                    url: p.url,
                    image: p.image || p.img || '/images/placeholder.png',
                    name: p.name,
                    price_min: typeof p.price_min === 'number' ? p.price_min : parseInt(String(p.price || '').replace(/\D/g, '')) || 0,
                    compare_at: typeof p.compare_at === 'number' ? p.compare_at : (p.compare ? parseInt(String(p.compare).replace(/\D/g, '')) || null : null),
                    discount: p.discount ?? null
                }));
            },

            async loadQuickQuestions() {
                try {
                    const res = await fetch("<?php echo e(route('bot.tools')); ?>", {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
                        }
                    });
                    if (!res.ok) {
                        throw new Error('Failed to fetch tools');
                    }
                    const data = await res.json();
                    this.quickQuestions = data.tools || {};
                    console.log('Loaded quick questions:', this.quickQuestions);
                } catch (e) {
                    console.error('Failed to load quick questions:', e);
                    this.quickQuestions = {};
                }
            },

            getCategoryLabel(catName) {
                const labels = {
                    'shipping': '🚚 Vận chuyển',
                    'return': '🔄 Đổi trả',
                    'product': '💄 Sản phẩm',
                    'payment': '💳 Thanh toán',
                    'general': '❓ Chung'
                };
                return labels[catName] || catName;
            },

            async selectQuestion(item) {
                // Hiển thị câu hỏi user đã chọn
                this.push('user', this.escapeHtml(item.question));
                this.isTyping = true;

                try {
                    const res = await fetch("<?php echo e(route('bot.chat')); ?>", {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ tool_id: item.id })
                    });

                    const data = await res.json();
                    const formattedReply = this.formatMarkdown(data.reply || '');

                    this.push('bot', formattedReply, {
                        suggestions: data.suggestions || [],
                        products: this.normalizeProducts(data.products || [])
                    });

                } catch (e) {
                    console.error('Bot error:', e);
                    this.push('bot', 'Ôi mạng hơi chậm rồi 😢 thử lại giúp tớ nhé!');
                } finally {
                    this.isTyping = false;
                    this.$nextTick(() => {
                        this.scrollBottom();
                        this.$refs.inputField?.focus();
                    });
                }
            },

            async send() {
                const text = this.input.trim();
                if (!text || this.isTyping) return;

                this.push('user', this.escapeHtml(text));
                this.input = '';
                this.isTyping = true;

                try {
                    const res = await fetch("<?php echo e(route('bot.chat')); ?>", {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ message: text })
                    });

                    const data = await res.json();

                    // Format reply với markdown
                    const formattedReply = this.formatMarkdown(data.reply || '');

                    this.push('bot', formattedReply, {
                        suggestions: data.suggestions || [],
                        products: this.normalizeProducts(data.products || [])
                    });

                } catch (e) {
                    console.error('Bot error:', e);
                    this.push('bot', 'Ôi mạng hơi chậm rồi 😢 thử lại giúp tớ nhé!');
                } finally {
                    this.isTyping = false;
                    this.$nextTick(() => {
                        this.$refs.inputField?.focus();
                    });
                }
            },

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            },

            formatMarkdown(text) {
                // Simple markdown to HTML
                text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
                text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');
                text = text.replace(/\n/g, '<br>');
                return text;
            },

            toggleEmoji() {
                // TODO: Implement emoji picker
                alert('Emoji picker sẽ được thêm sau! 😊');
            }
        }
    }

    // GSAP Animations - Modern 2025 animations
    (function() {
        if (typeof gsap === 'undefined') return;

        // Initialize animations when widget is ready
        document.addEventListener('DOMContentLoaded', () => {
            // Watch for new messages and animate them
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1 && node.hasAttribute && node.hasAttribute('data-msg-id')) {
                            const msgId = node.getAttribute('data-msg-id');
                            gsap.fromTo(node,
                                { opacity: 0, y: 20, scale: 0.9 },
                                { 
                                    opacity: 1, 
                                    y: 0, 
                                    scale: 1, 
                                    duration: 0.3, 
                                    ease: "back.out(1.7)" 
                                }
                            );
                        }
                    });
                });
            });

            // Observe bot scroll container
            const botScroll = document.getElementById('botScroll');
            if (botScroll) {
                observer.observe(botScroll, { childList: true, subtree: true });
            }

            // Product cards hover animation
            const initProductCards = () => {
                const cards = document.querySelectorAll('.js-card:not([data-gsap-initialized])');
                cards.forEach(card => {
                    card.setAttribute('data-gsap-initialized', 'true');
                    
                    card.addEventListener('mouseenter', () => {
                        gsap.to(card, { 
                            scale: 1.02, 
                            duration: 0.2, 
                            ease: "power2.out" 
                        });
                    });
                    
                    card.addEventListener('mouseleave', () => {
                        gsap.to(card, { 
                            scale: 1, 
                            duration: 0.2, 
                            ease: "power2.out" 
                        });
                    });
                });
            };

            // Re-init product cards when new products are added
            const productObserver = new MutationObserver(() => {
                initProductCards();
            });

            if (botScroll) {
                productObserver.observe(botScroll, { childList: true, subtree: true });
                initProductCards();
            }

            // Typing indicator animation - Watch for typing state changes
            const initTypingAnimation = () => {
                const typingDots = document.querySelectorAll('.typing-dot:not(.gsap-animated)');
                if (typingDots.length) {
                    typingDots.forEach(dot => dot.classList.add('gsap-animated'));
                    gsap.to(typingDots, {
                        y: -8,
                        duration: 0.4,
                        stagger: 0.1,
                        repeat: -1,
                        yoyo: true,
                        ease: "power2.inOut"
                    });
                }
            };
            
            // Watch for typing indicator appearance
            const typingObserver = new MutationObserver(() => {
                initTypingAnimation();
            });
            
            if (botScroll) {
                typingObserver.observe(botScroll, { childList: true, subtree: true });
                initTypingAnimation();
            }
        });

        // Panel open/close animation - Better integration with Alpine.js
        // Wait for Alpine to be ready
        if (typeof Alpine !== 'undefined') {
            document.addEventListener('alpine:init', () => {
                // Use Alpine's $watch instead of MutationObserver for better compatibility
                setTimeout(() => {
                    const widget = document.querySelector('[x-data*="cosmeBotWidget"]');
                    if (widget && widget._x_dataStack) {
                        const widgetData = widget._x_dataStack[0];
                        if (widgetData && typeof widgetData.$watch === 'function') {
                            widgetData.$watch('open', (isOpen) => {
                                if (isOpen) {
                                    const panel = widget.querySelector('[x-show="open"]');
                                    if (panel) {
                                        gsap.fromTo(panel,
                                            { opacity: 0, scale: 0.95, y: 10 },
                                            { opacity: 1, scale: 1, y: 0, duration: 0.3, ease: "power2.out" }
                                        );
                                    }
                                }
                            });
                        }
                    }
                }, 100);
            });
        }
    })();
</script>
<?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/components/bot-widget.blade.php ENDPATH**/ ?>