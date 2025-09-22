{{-- resources/views/components/bot-widget.blade.php --}}
<div x-data="cosmeBotWidget()" x-init="init()"
    class="fixed z-[9999] select-none"
    :style="`left:${x}px; top:${y}px`"
    style="touch-action:none">

    {{-- Bong bóng mascot (kéo bằng bong bóng, click mới mở) --}}
    <button type="button"
        @pointerdown="startDrag($event,'bubble')"
        @click="onBubbleClick()"
        class="relative w-16 h-16 rounded-full shadow-card bg-white border border-rose-100 grid place-items-center hover:shadow-lg transition"
        aria-label="Mở CosmeBot">
        <span class="pulse-ring"></span>
        <svg class="w-9 h-9 bob" viewBox="0 0 64 64" fill="none" aria-hidden="true">
            <circle cx="32" cy="32" r="28" fill="#FFE8EE" stroke="#FF9DB1" stroke-width="2" />
            <circle cx="24" cy="28" r="4" fill="#222" />
            <circle cx="40" cy="28" r="4" fill="#222" />
            <path d="M22 40c3 3 7 5 10 5s7-2 10-5" stroke="#222" stroke-width="3" stroke-linecap="round" />
            <circle cx="18" cy="34" r="3" fill="#FF9DB1" opacity=".7" />
            <circle cx="46" cy="34" r="3" fill="#FF9DB1" opacity=".7" />
        </svg>
    </button>

    {{-- Panel chat (kéo bằng header) --}}
    <div x-show="open" x-transition
        class="absolute"
        :class="panelSide==='right' ? 'origin-bottom-right right-0' : 'origin-bottom-left left-0'"
        :style="`width:${panel.w}px; height:${panel.h}px; top:${panelTop}px`">

        <div class="bg-white border border-rose-100 rounded-2xl shadow-card overflow-hidden h-full flex flex-col">

            {{-- Header (drag handle) --}}
            <div class="px-4 py-3 bg-rose-50/60 border-b border-rose-100 flex items-center gap-2 cursor-grab active:cursor-grabbing"
                @pointerdown="startDrag($event,'header')" style="touch-action:none">
                <div class="w-7 h-7 rounded-full grid place-items-center bg-white border border-rose-100">
                    <svg class="w-5 h-5" viewBox="0 0 64 64" fill="none" aria-hidden="true">
                        <circle cx="32" cy="32" r="28" fill="#FFE8EE" stroke="#FF9DB1" stroke-width="2" />
                        <circle cx="24" cy="28" r="4" fill="#222" />
                        <circle cx="40" cy="28" r="4" fill="#222" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="font-semibold text-sm">CosmeBot</div>
                    <div class="text-[11px] text-ink/60">Tư vấn mỹ phẩm xinh xắn ✨</div>
                </div>
                <button class="ml-auto text-ink/60 hover:text-ink" @click="open=false" aria-label="Đóng">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- Content --}}
            <div id="botScroll" class="flex-1 overflow-y-auto px-3 py-3 space-y-2 text-sm bg-white">
                <template x-for="m in messages" :key="m.id">
                    <div :class="m.role==='user' ? 'text-right' : 'text-left'">
                        <div :class="m.role==='user'
                ? 'inline-block px-3 py-2 rounded-2xl bg-brand-600 text-white'
                : 'inline-block px-3 py-2 rounded-2xl bg-rose-50/70 border border-rose-100'">
                            <span x-html="m.html"></span>
                        </div>

                        {{-- Chips --}}
                        <div class="mt-1 flex gap-2 flex-wrap" x-show="m.suggestions && m.suggestions.length">
                            <template x-for="s in m.suggestions" :key="s">
                                <button class="px-2 py-1 text-xs rounded-full border border-rose-200 hover:bg-rose-50"
                                    @click="quick(s)" x-text="s"></button>
                            </template>
                        </div>

                        {{-- Product cards --}}
                        <div class="mt-2 grid grid-cols-2 gap-2" x-show="m.products && m.products.length">
                            <template x-for="p in m.products" :key="p.url">
                                <a :href="p.url" class="border border-rose-100 rounded-xl overflow-hidden hover:shadow-card bg-white js-card">
                                    <span class="shine"></span>
                                    <img :src="p.img" class="w-full h-24 object-contain bg-white" alt="">
                                    <div class="p-2">
                                        <div class="line-clamp-2 text-[12px] font-medium" x-text="p.name"></div>
                                        <div class="mt-1">
                                            <span class="text-[13px] font-bold text-rose-600" x-text="p.price"></span>
                                            <span class="text-[11px] text-ink/50 line-through" x-show="p.compare" x-text="p.compare"></span>
                                            <span class="ml-1 text-[11px] text-white bg-rose-600 px-1 rounded" x-show="p.discount" x-text="'-'+p.discount+'%'"></span>
                                        </div>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>

                <div x-show="typing" class="text-left">
                    <div class="inline-block px-3 py-2 rounded-2xl bg-rose-50/70 border border-rose-100">
                        <span class="inline-block w-2 h-2 rounded-full bg-ink/50 animate-pulse mr-1"></span>
                        <span class="inline-block w-2 h-2 rounded-full bg-ink/50 animate-pulse mr-1"></span>
                        <span class="inline-block w-2 h-2 rounded-full bg-ink/50 animate-pulse"></span>
                    </div>
                </div>
            </div>

            {{-- Input --}}
            <form @submit.prevent="send()" class="p-3 border-t border-rose-100 flex items-center gap-2 bg-white">
                @csrf
                <input type="text" x-model="input" placeholder="Hỏi CosmeBot điều gì cũng được nè…"
                    class="flex-1 px-3 py-2 rounded-full border border-rose-200 outline-none focus:ring-2 focus:ring-brand-300">
                <button class="w-10 h-10 rounded-full bg-brand-600 text-white grid place-items-center">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Styles nhỏ --}}
<style>
    @keyframes bob {

        0%,
        100% {
            transform: translateY(0)
        }

        50% {
            transform: translateY(-3px)
        }
    }

    .bob {
        animation: bob 2.6s ease-in-out infinite;
    }

    .pulse-ring {
        position: absolute;
        inset: 0;
        border-radius: 9999px;
        pointer-events: none;
    }

    .pulse-ring::after {
        content: "";
        position: absolute;
        inset: -6px;
        border: 2px solid rgba(244, 63, 94, .35);
        border-radius: 9999px;
        animation: pulse 2.4s ease-out infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(.8);
            opacity: .7
        }

        100% {
            transform: scale(1.25);
            opacity: 0
        }
    }

    /* Hiệu ứng vệt sáng cho card (tuỳ chọn) */
    .js-card {
        position: relative;
    }

    .js-card .shine {
        position: absolute;
        inset: 0;
        border-radius: 1rem;
        pointer-events: none;
        opacity: 0;
        transition: opacity .2s ease;
        mix-blend-mode: overlay;
        background: radial-gradient(300px circle at var(--mx, -100px) var(--my, -100px), rgba(255, 255, 255, .35), rgba(255, 255, 255, 0) 40%);
    }

    .js-card:hover .shine {
        opacity: 1;
    }
</style>

<script>
    // Hiệu ứng vệt sáng cho card (nhẹ, không ảnh hưởng drag)
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
        }, {
            passive: true
        });
    })();
</script>

<script>
    function cosmeBotWidget() {
        return {
            open: false,
            input: '',
            typing: false,
            messages: [],
            id: 1,

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
            bubble: {
                w: 64,
                h: 64
            },
            panel: {
                w: 380,
                h: 460
            },
            panelTop: -396,
            openBelow: false,

            init() {

                window.Bot = {
                    open: (p = '') => {
                        this.open = true;
                        if (p) this.input = p;
                        this.$nextTick(() => this.scrollBottom());
                    },
                    close: () => {
                        this.open = false;
                    }
                };
                const saved = JSON.parse(localStorage.getItem('cosmebot.pos') || '{}');
                this.x = (saved.x ?? (window.innerWidth - this.bubble.w - 16));
                this.y = (saved.y ?? (window.innerHeight - this.bubble.h - 16));
                this.updateSide();
                this.updatePlacement();

                this._onMove = (e) => {
                    if (!this.dragging) return;
                    const p = this._pt(e);
                    const nx = p.x - this.ox,
                        ny = p.y - this.oy;

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
                            this.clamp(true); // chỉ clamp vị trí khi đang kéo
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
                    this.clamp(false); // chốt & tính hướng mở
                    localStorage.setItem('cosmebot.pos', JSON.stringify({
                        x: this.x,
                        y: this.y
                    }));
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
                document.addEventListener('pointermove', this._onMove, {
                    passive: false
                });
                document.addEventListener('pointerup', this._onUp, {
                    passive: true
                });
            },

            _pt(e) {
                return {
                    x: e.clientX,
                    y: e.clientY
                };
            },

            // clampPosOnly=true => không tính updatePlacement khi đang kéo
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

            // CHỈ mở khi click thật; nếu vừa kéo thì bỏ qua click
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
                this.$nextTick(() => this.scrollBottom());
                this.clamp(false);
            },

            scrollBottom() {
                this.$nextTick(() => {
                    const el = document.getElementById('botScroll');
                    if (el) el.scrollTop = el.scrollHeight;
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

            async send() {
                const text = this.input.trim();
                if (!text) return;
                this.push('user', text.replace(/</g, '&lt;'));
                this.input = '';
                this.typing = true;
                try {
                    const res = await fetch("{{ route('bot.chat') }}", {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            message: text
                        })
                    });
                    const data = await res.json();
                    this.push('bot', (data.reply || '').replace(/\n/g, '<br>'), {
                        suggestions: data.suggestions || [],
                        products: data.products || []
                    });
                } catch (e) {
                    this.push('bot', 'Ôi mạng hơi chậm rồi 😢 thử lại giúp tớ nhé!');
                } finally {
                    this.typing = false;
                }
            }
        }
    }
</script>