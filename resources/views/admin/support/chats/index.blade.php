@extends('admin.layouts.app')
@section('title','Live chat')

@section('content')
<script>
    window.__SEED__ = @json($seed ?? []);
</script>

<div class="h-screen grid grid-cols-[260px_1fr] gap-4 p-3 overflow-hidden"
    x-data="window.supportAdmin({ seed: window.__SEED__ })" x-init="init()">

    <!-- Sidebar -->
    <aside class="bg-white border border-slate-200 rounded-lg p-3 flex flex-col h-full overflow-hidden">
        <div class="flex items-center justify-between mb-2 flex-shrink-0">
            <h4 class="text-sm font-semibold">Chats</h4>
            <span class="text-xs text-slate-500">Live</span>
        </div>

        <div id="admin-chat-list" class="flex-1 overflow-y-auto pr-1">
            <template x-for="c in chats" :key="c.id">
                <div
                    class="relative flex items-center gap-3 px-2 py-2 rounded-md hover:bg-slate-50 cursor-pointer transition"
                    :class="selected && selected.id === c.id ? 'bg-rose-50 border-l-4 border-rose-500 pl-2' : ''"
                    @click="openChat(c)">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-white text-sm bg-gradient-to-br from-pink-400 to-rose-500">
                        <span x-text="(displayName(c)||'#'+c.id).slice(0,2).toUpperCase()"></span>
                    </div>

                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-slate-800 truncate" x-text="displayName(c)"></div>
                        <div class="text-xs text-slate-500 truncate" x-text="'#' + c.id + ' · ' + (c.status ?? 'open')"></div>
                    </div>

                    <span
                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-rose-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full shadow"
                        x-show="c.unread && c.unread>0"
                        x-text="c.unread>99? '99+': c.unread"></span>
                </div>
            </template>
        </div>
    </aside>

    <!-- Chat panel -->
    <section class="bg-white border border-slate-200 rounded-lg p-4 flex flex-col h-full overflow-hidden">
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 mb-3 flex-shrink-0">
            <div>
                <div class="text-base font-semibold text-slate-800"
                    x-text="selected ? (selected.customer_name ?? ('#'+selected.id+' — Khách')) : 'Chọn phòng để xem'"></div>
                <div class="text-xs text-slate-500"
                    x-text="selected ? ('Chat ID: ' + selected.id + (selected.status ? ' · ' + selected.status : '')) : ''"></div>
            </div>
            <div class="text-sm text-slate-500">Agent: <strong>Admin</strong></div>
        </div>

        <!-- Messages -->
        <div id="admin-chat-messages"
            class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50 rounded-md border border-dashed border-slate-200"
            x-show="selected" x-cloak>
            <template x-for="m in messages" :key="m.id">
                <div class="w-full flex" :class="m.sender_type === 'customer' ? 'justify-start' : 'justify-end'">
                    <div
                        class="max-w-[70%] break-words px-3 py-2 text-sm rounded-2xl"
                        :class="m.sender_type === 'customer'
                    ? 'bg-white text-slate-900 border border-slate-100 rounded-bl-md'
                    : 'bg-gradient-to-br from-pink-400 to-rose-500 text-white rounded-br-md'">
                        <!-- render HTML thay vì text để hiện card + ảnh -->
                        <div x-html="renderMessage(m)"></div>
                        <span class="block text-xs text-slate-300 mt-1 text-right" x-text="formatTime(m.created_at)"></span>
                    </div>
                </div>
            </template>
        </div>

        <!-- Input -->
        <div x-show="selected" x-cloak class="mt-3 flex-shrink-0">
            <div class="flex items-end gap-3">
                <textarea
                    x-model="draft"
                    placeholder="Type a message..."
                    aria-label="Message"
                    @keydown.enter.prevent="send()"
                    class="flex-1 resize-none min-h-[44px] max-h-[140px] rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300"></textarea>

                <button
                    type="button"
                    class="w-11 h-11 rounded-full flex items-center justify-center bg-gradient-to-b from-pink-500 to-rose-500 text-white shadow-md hover:opacity-95"
                    @click="send()"
                    title="Send">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="transform rotate-45">
                        <path d="M22 2L11 13"></path>
                        <path d="M22 2L15 22L11 13L2 9L22 2Z" />
                    </svg>
                </button>
            </div>
            <div class="text-xs text-slate-400 mt-2">Nhấn Enter để gửi — Shift+Enter để xuống dòng.</div>
        </div>

        <div x-show="!selected" x-cloak class="text-sm text-slate-400 mt-4 flex-shrink-0">
            Chọn một cuộc trò chuyện từ bên trái để bắt đầu. Tin nhắn sẽ hiện realtime khi có sự kiện.
        </div>
    </section>
</div>

<script>
    window.supportAdmin = function({
        seed = []
    }) {
        return {
            chats: seed,
            selected: null,
            messages: [],
            draft: '',
            chans: {},
            isSending: false,

            init() {
                if (this.chats.length) this.openChat(this.chats[0]);
                this.chats.forEach(c => this.ensureSub(c.id));
            },
            displayName(c) {
                return c.customer_name ? c.customer_name : ('#' + c.id + ' — Khách');
            },
            addOrUpdateChat(item) {
                const idx = this.chats.findIndex(x => x.id === item.id);
                if (idx > -1) this.chats.splice(idx, 1, item);
                else this.chats.unshift(item);
                this.ensureSub(item.id);
            },
            async openChat(c) {
                this.selected = c;
                this.setUnread(c.id, 0);
                this.ensureSub(c.id);
                await this.fetchMessages(c.id);
                this.scrollDown();
            },
            async fetchMessages(chatId) {
                try {
                    const res = await fetch(`/livechat/${chatId}/messages`, {
                        credentials: 'include',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    this.messages = res.ok ? await res.json() : [];
                    // đánh dấu đã đọc tới cuối (nếu có route server)
                    const last = this.messages[this.messages.length - 1];
                    if (last) this.markRead(chatId, last.id);
                } catch {
                    this.messages = [];
                }
            },
            async send() {
                if (!this.selected) return;
                const body = (this.draft || '').trim();
                if (!body || this.isSending) return;
                this.isSending = true;
                const tmp = {
                    id: 'tmp_' + Date.now(),
                    body,
                    sender_type: 'staff',
                    created_at: new Date().toISOString()
                };
                this.messages.push(tmp);
                this.draft = '';
                try {
                    await fetch(`/livechat/${this.selected.id}/messages`, {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            body
                        })
                    });
                } catch (e) {
                    console.warn(e);
                } finally {
                    setTimeout(() => this.isSending = false, 200);
                }
                this.scrollDown();
            },
            ensureSub(chatId) {
                if (!window.Echo || this.chans[chatId]) return;
                try {
                    const ch = window.Echo.private('chat.' + chatId);
                    ch.listen('.message.sent', p => this.onReceive(chatId, p));
                    this.chans[chatId] = ch;
                } catch (e) {
                    console.warn('sub failed', e);
                }
            },
            onReceive(chatId, payload) {
                if (this.selected && this.selected.id === chatId) {
                    if (payload.sender_type === 'staff') return;
                    this.messages.push(payload);
                    this.scrollDown();
                    this.markRead(chatId, payload.id);
                } else {
                    const i = this.chats.findIndex(x => x.id === chatId);
                    if (i > -1) this.chats[i].unread = (this.chats[i].unread || 0) + 1;
                }
            },
            setUnread(chatId, val) {
                const i = this.chats.findIndex(x => x.id === chatId);
                if (i > -1) this.chats[i].unread = val;
            },
            async markRead(chatId, lastId) {
                // nếu chưa có route /livechat/{chat}/read thì call này sẽ fail silently (không ảnh hưởng)
                try {
                    await fetch(`/livechat/${chatId}/read`, {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            last_id: lastId
                        })
                    });
                } catch (_) {}
            },
            formatTime(iso) {
                try {
                    return new Date(iso).toLocaleString();
                } catch {
                    return iso ?? '';
                }
            },
            scrollDown() {
                setTimeout(() => {
                    const el = document.querySelector('#admin-chat-messages');
                    if (el) el.scrollTop = el.scrollHeight;
                }, 80);
            },

            /* ---------- RENDER HELPERS (AI card + ảnh) ---------- */
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
                return this.escapeHtml(text)
                    .replace(/(https?:\/\/[^\s'"]+\.(?:png|jpe?g|webp|gif)(?:\?[^\s'"]*)?)/ig,
                        u => `<a href="${u}" target="_blank" rel="noopener"><img src="${u}" class="mt-1 max-w-full rounded-lg border" loading="lazy"></a>`)
                    .replace(/\n/g, '<br>');
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
                const pct = k => (Math.round(((m[k] ?? 0) * 100))) + '%';
                const photos = (d?.photos || []).slice(0, 4).map(u =>
                    `<a href="${u}" target="_blank" rel="noopener"><img src="${u}" class="w-full h-28 object-cover rounded-lg border" loading="lazy"></a>`
                ).join('');
                return `
          <div class="-mx-2 -my-1 p-2">
            <div class="rounded-xl border bg-white">
              <div class="px-3 py-2 border-b bg-rose-50 text-rose-800 font-semibold">
                AI Skin Scan • <span class="inline-block px-2 py-0.5 rounded-full bg-rose-100 border border-rose-200">${this.escapeHtml(t)}</span>
              </div>
              <div class="p-3">
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1 text-sm">
                    <div class="flex items-center justify-between"><span class="text-slate-500">Oiliness</span><span class="font-semibold">${pct('oiliness')}</span></div>
                    <div class="flex items-center justify-between"><span class="text-slate-500">Dryness</span><span class="font-semibold">${pct('dryness')}</span></div>
                    <div class="flex items-center justify-between"><span class="text-slate-500">Redness</span><span class="font-semibold">${pct('redness')}</span></div>
                    <div class="flex items-center justify-between"><span class="text-slate-500">Acne</span><span class="font-semibold">${pct('acne_score') || pct('acne')}</span></div>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    ${photos || '<div class="col-span-2 text-sm text-slate-500">Chưa có ảnh.</div>'}
                  </div>
                </div>
                <div class="mt-3 text-xs text-slate-500">
                  ID lần soi: #${this.escapeHtml(d?.test_id ?? '')}
                  • <a class="text-rose-600 underline" target="_blank" rel="noopener" href="${this.escapeHtml(d?.profile_url || '/account/skin-profile')}">Xem hồ sơ</a>
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
                        /* nếu lỗi thì rơi xuống text thường */
                    }
                }
                return this.linkifyImages(raw);
            }
        };
    };
</script>
@endsection