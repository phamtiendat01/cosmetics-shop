{{-- resources/views/components/cart-drawer.blade.php --}}
<div id="jsCartDrawer"
    x-data="cartDrawer()"
    x-init="init()"
    x-show="$store.cart.open"
    x-cloak
    class="fixed inset-0 z-[1100]">

    {{-- Overlay --}}
    <div class="absolute inset-0 bg-black/30" @click="$store.cart.open=false"></div>

    {{-- Panel --}}
    <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-card
              border-l border-rose-100 flex flex-col"
        x-transition:enter="transition transform ease-out duration-200"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition transform ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full">

        {{-- Header --}}
        <div class="px-4 py-3 border-b border-rose-100 flex items-center justify-between">
            <div class="text-lg font-semibold">Giỏ hàng</div>
            <button class="text-ink/60 hover:text-ink" @click="$store.cart.open=false">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto" x-ref="body">
            <template x-if="!items.length">
                <div class="p-6 text-ink/60">Giỏ hàng trống.</div>
            </template>

            <template x-for="it in items" :key="it.key">
                <div class="p-4 border-b border-rose-100 flex gap-3">
                    <a :href="it.url" class="w-20 h-20 rounded-lg bg-rose-50/40 border border-rose-100 overflow-hidden grid place-items-center">
                        <img :src="it.img" class="max-h-20 object-contain" alt="">
                    </a>
                    <div class="flex-1 min-w-0">
                        <a :href="it.url" class="line-clamp-2 font-medium hover:text-brand-600" x-text="it.name"></a>
                        <div class="text-xs text-ink/60 mt-0.5" x-show="it.variant" x-text="it.variant"></div>

                        <div class="mt-2 flex items-center justify-between">
                            <div>
                                <div class="font-semibold text-rose-600" x-text="money(it.price)"></div>
                                <div class="text-xs text-ink/50 line-through" x-show="it.compare" x-text="money(it.compare)"></div>
                            </div>

                            {{-- Stepper --}}
                            <div class="flex items-center border border-rose-200 rounded-lg overflow-hidden">
                                <button class="w-8 h-8 grid place-items-center" @click="dec(it)">−</button>
                                <input class="w-10 h-8 text-center border-x border-rose-100 outline-none"
                                    :value="it.qty"
                                    @input="onInputQty($event, it)">
                                <button class="w-8 h-8 grid place-items-center" @click="inc(it)">+</button>
                            </div>
                        </div>
                    </div>

                    <button class="self-start text-ink/60 hover:text-red-600" title="Xóa" @click="remove(it)">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div class="border-t border-rose-100 p-4 space-y-3">
            <div class="flex items-center justify-between">
                <div class="text-sm text-ink/60">Tạm tính</div>
                <div class="text-lg font-bold text-rose-600" x-text="money(subtotal)"></div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('checkout.index') }}" class="flex-1 px-4 py-3 bg-brand-600 text-white rounded-xl text-center hover:bg-brand-700">
                    Thanh toán
                </a>
                <button class="px-4 py-3 border border-rose-200 rounded-xl hover:bg-rose-50"
                    @click="$store.cart.open=false">Mua tiếp</button>
            </div>
            <button class="w-full text-xs text-ink/60 hover:text-red-600" @click="clearAll()">Xóa toàn bộ</button>
        </div>
    </div>
</div>

<script>
    function cartDrawer() {
        return {
            items: [],
            subtotal: 0,

            init() {
                // mở drawer thì refresh
                this.$watch('$store.cart.open', (v) => {
                    if (v) this.refresh();
                });
                // allow external trigger
                this.$el.addEventListener('cart:refresh', () => this.refresh(), {
                    passive: true
                });
            },

            money(n) {
                n = Number(n) || 0;
                return n.toLocaleString('vi-VN') + '₫';
            },

            async refresh() {
                try {
                    const r = await fetch("{{ route('cart.json') }}", {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const d = await r.json();
                    if (d?.ok) {
                        this.items = d.items || [];
                        this.subtotal = d.subtotal || 0;
                        // update badge
                        const cnt = Number(d.count || 0);
                        const el = document.getElementById('jsCartCount');
                        if (el) {
                            el.textContent = cnt;
                            el.classList.toggle('hidden', cnt <= 0);
                        }
                    }
                    this.$nextTick(() => {
                        const b = this.$refs.body;
                        if (b) b.scrollTop = 0;
                    });
                } catch (_) {}
            },

            async inc(it) {
                await this.updateQty(it, it.qty + 1);
            },
            async dec(it) {
                await this.updateQty(it, Math.max(1, it.qty - 1));
            },
            onInputQty(ev, it) {
                const v = parseInt(ev.target.value.replace(/[^0-9]/g, ''), 10) || 1;
                this.updateQty(it, v);
            },

            async updateQty(it, qty) {
                try {
                    const r = await fetch(`{{ url('/cart') }}/${encodeURIComponent(it.key)}`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            qty
                        })
                    });
                    const d = await r.json();
                    if (d?.ok) {
                        this.refresh();
                    }
                } catch (_) {}
            },

            async remove(it) {
                try {
                    const r = await fetch(`{{ url('/cart') }}/${encodeURIComponent(it.key)}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    const d = await r.json();
                    if (d?.ok) {
                        this.refresh();
                    }
                } catch (_) {}
            },

            async clearAll() {
                if (!confirm('Xóa toàn bộ sản phẩm trong giỏ?')) return;
                try {
                    const r = await fetch(`{{ url('/cart') }}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    const d = await r.json();
                    if (d?.ok) {
                        this.refresh();
                    }
                } catch (_) {}
            }
        }
    }
</script>