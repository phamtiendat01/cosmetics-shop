
<style>
    @keyframes chat-ripple {
        0% {
            box-shadow: 0 0 0 0 rgba(244, 63, 94, .45), 0 0 0 6px rgba(244, 63, 94, .25), 0 0 0 12px rgba(244, 63, 94, .10)
        }

        70% {
            box-shadow: 0 0 0 6px rgba(244, 63, 94, .25), 0 0 0 12px rgba(244, 63, 94, .10), 0 0 0 24px rgba(244, 63, 94, 0)
        }

        100% {
            box-shadow: 0 0 0 0 rgba(244, 63, 94, 0), 0 0 0 0 rgba(244, 63, 94, 0), 0 0 0 0 rgba(244, 63, 94, 0)
        }
    }

    @keyframes msg-pop {
        0% {
            transform: scale(.96) translateY(4px);
            opacity: .0
        }

        100% {
            transform: scale(1) translateY(0);
            opacity: 1
        }
    }

    .livechat-msg {
        animation: msg-pop .16s cubic-bezier(.22, .61, .36, 1) both
    }

    .livechat-drag {
        cursor: grab
    }

    .livechat-drag:active {
        cursor: grabbing
    }

    .livechat-resizer::after {
        content: '';
        position: absolute;
        right: 6px;
        bottom: 6px;
        width: 14px;
        height: 14px;
        border-right: 3px solid rgba(15, 23, 42, .25);
        border-bottom: 3px solid rgba(15, 23, 42, .25);
        border-radius: 2px;
    }
</style>

<div x-data="liveChatWidget()" x-init="init()" class="fixed z-50 select-none"
    :style="`left:${pos.x}px; top:${pos.y}px;`">

    
    <button
        @pointerdown.prevent="startDragFromBubble($event)"
        @click.prevent="clickFromBubble()"
        class="group relative w-14 h-14 rounded-full
               shadow-[0_10px_30px_rgba(244,63,94,0.35)]
               bg-gradient-to-br from-rose-500 via-rose-600 to-pink-600
               flex items-center justify-center text-white transition-all duration-150 active:scale-95"
        :class="{'animate-[chat-ripple_2s_ease-out_infinite]': badge>0}"
        title="Live chat">
        <i class="fa-regular fa-message text-[22px] drop-shadow"></i>

        <span x-show="badge>0" x-transition
            class="absolute -top-1 -right-1 min-w-[20px] h-[20px] px-1 text-[11px] leading-[20px]
                     bg-red-600 text-white rounded-full text-center ring-2 ring-white">
            <span x-text="badge"></span>
        </span>
        <span class="pointer-events-none absolute inset-0 rounded-full opacity-0 group-hover:opacity-100 transition
                     bg-[radial-gradient(200px_circle_at_70%_30%,rgba(255,255,255,.35),transparent_40%)]"></span>
    </button>

    
    <div x-show="open" x-transition.opacity.scale.origin-bottom-right
        class="absolute right-0 bottom-16 sm:bottom-0 sm:right-16 max-w-[560px] max-h-[720px]" @pointerdown.stop>
        <div class="relative bg-white rounded-2xl shadow-2xl border border-rose-100 overflow-hidden w-full h-full"
            :style="`width:${size.w}px; height:${size.h}px;`">

            
            <div class="livechat-drag p-3 bg-gradient-to-r from-rose-600 to-pink-600 text-white flex items-center justify-between"
                @pointerdown="startDrag($event)">
                <div class="flex items-center gap-2">
                    <span class="inline-flex w-8 h-8 rounded-full bg-white/15 items-center justify-center">
                        <i class="fa-regular fa-message"></i>
                    </span>
                    <div>
                        <div class="font-semibold">COSME HOUSE | LIVE CHAT</div>
                        <div class="text-xs opacity-80">Chăm sóc khách hàng</div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="toggleFullscreen()" class="w-8 h-8 grid place-content-center rounded hover:bg-white/10">
                        <i class="fa-regular fa-square"></i>
                    </button>
                    <button @click="open=false" class="w-8 h-8 grid place-content-center rounded hover:bg-white/10">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            
            <div class="h-[calc(100%-56px-56px)] p-3 space-y-2 overflow-y-auto bg-white" x-ref="messages">
                <template x-for="m in messages" :key="m.id">
                    <div :class="m.sender_type==='customer' ? 'text-right' : 'text-left'">
                        <div class="livechat-msg inline-block max-w-[85%] px-3 py-2 rounded-2xl text-[15px] shadow-sm"
                            :class="m.sender_type==='customer' ? 'bg-rose-100 text-rose-900' : 'bg-slate-100 text-slate-900'">
                            <div x-html="renderMessage(m)"></div>
                        </div>
                    </div>
                </template>
            </div>

            
            <?php if(auth()->guard()->check()): ?>
            <form @submit.prevent="send()" class="p-2 border-t bg-white flex items-center gap-2">
                <input x-model="input" class="flex-1 border rounded-full px-4 py-2 text-sm focus:ring-2 focus:ring-rose-300 outline-none"
                    placeholder="Nhập tin nhắn…">
                <button class="px-4 py-2 rounded-full bg-rose-600 text-white text-sm hover:bg-rose-700 active:scale-95">Gửi</button>
            </form>
            <?php else: ?>
            <div class="p-3 border-t text-sm text-center bg-white">
                Vui lòng <a href="<?php echo e(route('login')); ?>" class="text-rose-600 underline">đăng nhập</a> để chat.
            </div>
            <?php endif; ?>

            
            <div class="livechat-resizer absolute right-0 bottom-0 w-6 h-6 cursor-se-resize"
                @pointerdown="startResize($event)"></div>
        </div>
    </div>
</div>

<script>
    function liveChatWidget() {
        const MIN_W = 320,
            MIN_H = 360,
            MAX_W = () => Math.min(window.innerWidth - 24, 560),
            MAX_H = () => Math.min(window.innerHeight - 24, 720);

        return {
            /* ===== UI ===== */
            open: false,
            pos: {
                x: 0,
                y: 0
            },
            size: {
                w: 360,
                h: 520
            },
            drag: {
                active: false,
                startX: 0,
                startY: 0,
                baseX: 0,
                baseY: 0,
                mode: 'move'
            },
            bubbleDrag: {
                moving: false,
                moved: false,
                sx: 0,
                sy: 0
            },

            /* ===== Data ===== */
            chatId: null,
            publicToken: null,
            messages: [],
            input: '',
            badge: 0,
            fullscreen: false,

            async init() {
                // neo mặc định
                this.pos.x = window.innerWidth - 88;
                this.pos.y = window.innerHeight - 88;

                <?php if(auth()->guard()->check()): ?>
                try {
                    const r = await fetch(<?php echo json_encode(route('livechat.unread'), 15, 512) ?>, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const d = await r.json();
                    this.badge = Number(d?.count || 0);
                } catch (_) {}
                <?php endif; ?>

                window.$livechat = this; // để trang khác có thể mở
                window.addEventListener('livechat:prefill', (e) => this.prefillAndOpen(e.detail));

                window.addEventListener('resize', () => {
                    this.size.w = Math.min(this.size.w, MAX_W());
                    this.size.h = Math.min(this.size.h, MAX_H());
                    this.pos.x = Math.min(Math.max(8, this.pos.x), window.innerWidth - 72);
                    this.pos.y = Math.min(Math.max(8, this.pos.y), window.innerHeight - 72);
                }, {
                    passive: true
                });
            },

            /* ===== Drag panel header ===== */
            startDrag(e) {
                this.drag = {
                    active: true,
                    startX: e.clientX,
                    startY: e.clientY,
                    baseX: this.pos.x,
                    baseY: this.pos.y,
                    mode: 'move'
                };
                const mm = (ev) => {
                    if (!this.drag.active) return;
                    const dx = ev.clientX - this.drag.startX,
                        dy = ev.clientY - this.drag.startY;
                    this.pos.x = Math.min(Math.max(8, this.drag.baseX + dx), window.innerWidth - 72);
                    this.pos.y = Math.min(Math.max(8, this.drag.baseY + dy), window.innerHeight - 72);
                };
                const up = () => {
                    this.drag.active = false;
                    window.removeEventListener('pointermove', mm);
                    window.removeEventListener('pointerup', up);
                };
                window.addEventListener('pointermove', mm);
                window.addEventListener('pointerup', up, {
                    once: true
                });
            },

            /* ===== Drag from bubble ===== */
            startDragFromBubble(e) {
                this.bubbleDrag = {
                    moving: true,
                    moved: false,
                    sx: e.clientX,
                    sy: e.clientY
                };
                const mm = (ev) => {
                    if (!this.bubbleDrag.moving) return;
                    const dx = ev.clientX - this.bubbleDrag.sx,
                        dy = ev.clientY - this.bubbleDrag.sy;
                    if (Math.abs(dx) > 3 || Math.abs(dy) > 3) this.bubbleDrag.moved = true;
                    this.pos.x = Math.min(Math.max(8, this.pos.x + dx), window.innerWidth - 72);
                    this.pos.y = Math.min(Math.max(8, this.pos.y + dy), window.innerHeight - 72);
                    this.bubbleDrag.sx = ev.clientX;
                    this.bubbleDrag.sy = ev.clientY;
                };
                const up = () => {
                    this.bubbleDrag.moving = false;
                    window.removeEventListener('pointermove', mm);
                    window.removeEventListener('pointerup', up);
                };
                window.addEventListener('pointermove', mm);
                window.addEventListener('pointerup', up, {
                    once: true
                });
            },
            clickFromBubble() {
                if (this.bubbleDrag.moved) {
                    this.bubbleDrag.moved = false;
                    return;
                }
                this.toggle();
            },

            /* ===== Resize panel ===== */
            startResize(e) {
                this.drag = {
                    active: true,
                    startX: e.clientX,
                    startY: e.clientY,
                    baseW: this.size.w,
                    baseH: this.size.h,
                    mode: 'resize'
                };
                const mm = (ev) => {
                    if (!this.drag.active) return;
                    const dw = ev.clientX - this.drag.startX,
                        dh = ev.clientY - this.drag.startY;
                    this.size.w = Math.min(Math.max(MIN_W, this.drag.baseW + dw), MAX_W());
                    this.size.h = Math.min(Math.max(MIN_H, this.drag.baseH + dh), MAX_H());
                };
                const up = () => {
                    this.drag.active = false;
                    window.removeEventListener('pointermove', mm);
                    window.removeEventListener('pointerup', up);
                };
                window.addEventListener('pointermove', mm);
                window.addEventListener('pointerup', up, {
                    once: true
                });
            },

            toggleFullscreen() {
                this.fullscreen = !this.fullscreen;
                if (this.fullscreen) {
                    this.size = {
                        w: Math.min(MAX_W(), window.innerWidth - 24),
                        h: Math.min(MAX_H(), window.innerHeight - 24)
                    };
                    this.pos = {
                        x: 12,
                        y: 12
                    };
                } else {
                    this.size = {
                        w: 360,
                        h: 520
                    };
                    this.pos = {
                        x: window.innerWidth - 88,
                        y: window.innerHeight - 88
                    };
                }
            },

            /* ===== Flow ===== */
            async toggle() {
                this.open = !this.open;
                if (this.open && !this.chatId) await this.startChat();
                if (this.open) await this.fetchMessages();
            },
            async startChat() {
                const r = await fetch(<?php echo json_encode(route('livechat.start'), 15, 512) ?>, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const d = await r.json();
                this.chatId = d.chat_id ?? d.id;
                this.publicToken = d.public_token ?? d.token;
                try {
                    localStorage.setItem('livechat_id', this.chatId);
                } catch (_) {}
                if (window.Echo) {
                    <?php if(auth()->guard()->check()): ?> window.Echo.private(`chat.${this.chatId}`).listen('.message.sent', (e) => this.onReceive(e));
                    <?php else: ?> window.Echo.channel(`public-chat.${this.publicToken}`).listen('.message.sent', (e) => this.onReceive(e));
                    <?php endif; ?>
                }
            },
            async fetchMessages() {
                if (!this.chatId) return;
                const r = await fetch(`/livechat/${this.chatId}/messages`);
                this.messages = await r.json();
                this.badge = 0;
                this.$nextTick(() => {
                    this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
                });
            },
            onReceive(e) {
                this.messages.push(e);
                if (!this.open && e?.sender_type === 'staff') this.badge = (this.badge || 0) + 1;
                this.$nextTick(() => {
                    this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
                });
            },

            // đảm bảo có phiên
            async ensureChat() {
                if (this.chatId) return true;
                await this.startChat();
                return !!this.chatId;
            },

            // gửi text thuần (không đụng ô input)
            async sendRaw(text) {
                if (!text?.trim() || !this.chatId) return;
                await fetch(`/livechat/${this.chatId}/messages`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        body: text
                    })
                });
            },

            // tạo tóm tắt (fallback nếu trang khác bắn event prefill)
            buildSummary(data = {}) {
                const map = {
                    oily: 'Da thiên dầu',
                    dry: 'Da khô',
                    combination: 'Da hỗn hợp',
                    sensitive: 'Da nhạy cảm'
                };
                const t = map[(data.skinType || '').toString()] || 'Đang xác định';
                const m = data.metrics || {};
                const pct = v => (Math.round((+v || 0) * 100)) + '%';
                const lines = [
                    '[AI Skin Scan]',
                    `• Loại da: ${t}`,
                    `• Chỉ số: Oil ${pct(m.oiliness)} · Dry ${pct(m.dryness)} · Red ${pct(m.redness)} · Acne ${pct(m.acne_score)}`,
                    `• ID lần soi: #${data.testId ?? '—'}`,
                ];
                if (data.profileUrl) lines.push(`• Hồ sơ: ${data.profileUrl}`);
                const photos = (data.photos || []).slice(0, 3);
                if (photos.length) {
                    lines.push('• Ảnh:');
                    photos.forEach((u, i) => lines.push(`   ${i+1}) ${u}`));
                }
                return lines.join('\n');
            },

            async prefillAndOpen(payload) {
                if (!(await this.ensureChat())) return;
                const text = this.buildSummary(payload);
                await this.sendRaw(text);
                this.open = true;
                await this.fetchMessages();
                this.$nextTick(() => {
                    this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
                });
            },

            /* ===== Helpers render ===== */
            escapeHtml(s = '') {
                return String(s).replace(/[&<>"']/g, m => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [m]));
            },
            linkifyImages(text) {
                const html = this.escapeHtml(text)
                    .replace(/(https?:\/\/[^\s'"]+\.(?:png|jpe?g|webp|gif)(?:\?[^\s'"]*)?)/ig,
                        u => `<a href="${u}" target="_blank" rel="noopener"><img src="${u}" alt="" class="mt-1 max-w-full rounded-lg border"></a>`)
                    .replace(/\n/g, '<br>');
                return html;
            },
            renderAiScanCard(d) {
                const typeMap = {
                    oily: 'Da thiên dầu',
                    dry: 'Da khô',
                    combination: 'Da hỗn hợp',
                    sensitive: 'Da nhạy cảm'
                };
                const t = typeMap[(d?.type || '').toString()] || 'Đang xác định';
                const m = d?.metrics || {};
                const row = (k, l) => `<div class="flex items-center justify-between"><span class="text-slate-500">${l}</span><span class="font-semibold">${(m[k]??0)}%</span></div>`;
                const photos = (d?.photos || []).slice(0, 4).map(u => `
                <a href="${u}" target="_blank" rel="noopener" class="block">
                    <img src="${u}" alt="" class="w-full h-28 object-cover rounded-lg border">
                </a>`).join('');
                return `
            <div class="-mx-2 -my-1 p-2">
              <div class="rounded-xl border bg-white">
                <div class="px-3 py-2 border-b bg-rose-50 text-rose-800 font-semibold">
                  AI Skin Scan • <span class="inline-block px-2 py-0.5 rounded-full bg-rose-100 border border-rose-200">${t}</span>
                </div>
                <div class="p-3">
                  <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1 text-sm">
                      ${row('oiliness','Oiliness')}
                      ${row('dryness','Dryness')}
                      ${row('redness','Redness')}
                      ${row('acne','Acne')}
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                      ${photos || '<div class="col-span-2 text-sm text-slate-500">Chưa có ảnh.</div>'}
                    </div>
                  </div>
                  <div class="mt-3 text-xs text-slate-500">
                    ID lần soi: #${this.escapeHtml(d?.test_id)} •
                    <a class="text-rose-600 underline" target="_blank" rel="noopener" href="${this.escapeHtml(d?.profile_url || '/account/skin-profile')}">Xem hồ sơ</a>
                  </div>
                </div>
              </div>
            </div>`;
            },
            renderMessage(m) {
                const raw = String(m?.body || '');
                const prefix = '::ai_scan::';
                if (raw.startsWith(prefix)) {
                    try {
                        const json = JSON.parse(decodeURIComponent(escape(atob(raw.slice(prefix.length)))));
                        return this.renderAiScanCard(json);
                    } catch (e) {
                        /* nếu lỗi parse → rơi về text thường */
                    }
                }
                return this.linkifyImages(raw);
            },

            /* ===== Send from input ===== */
            async send() {
                if (!this.input.trim() || !this.chatId) return;
                const r = await fetch(`/livechat/${this.chatId}/messages`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        body: this.input
                    })
                });
                if (r.ok) {
                    this.input = '';
                    await this.fetchMessages();
                }
            }
        }
    }
</script><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/components/livechat/widget.blade.php ENDPATH**/ ?>