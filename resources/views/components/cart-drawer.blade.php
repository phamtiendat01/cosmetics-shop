@php
$items = collect(session('cart', []));
$subtotal = $items->sum(fn($i)=> ($i['price'] ?? 0) * ($i['qty'] ?? 0));
@endphp

<div x-data x-show="$store.cart.open" x-transition.opacity
    class="fixed inset-0 z-[70] bg-black/40" style="display:none"
    @keydown.escape.window="$store.cart.open=false" @click.self="$store.cart.open=false">
    <aside x-show="$store.cart.open" x-transition
        class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-card border-l border-rose-100 p-4 flex flex-col">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold">Giỏ hàng</h3>
            <button class="w-9 h-9 grid place-items-center rounded-full hover:bg-rose-50" @click="$store.cart.open=false">
                <i class="fa-regular fa-xmark"></i>
            </button>
        </div>

        <div class="mt-3 flex-1 overflow-auto space-y-3" id="cartItems">
            @forelse($items as $key=>$it)
            <div class="flex gap-3 border border-rose-100 rounded-xl p-2" data-key="{{ $key }}">
                <img src="{{ $it['image'] ?? 'https://placehold.co/96x96?text=IMG' }}" class="w-16 h-16 rounded-lg object-cover">
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium line-clamp-2">{{ $it['name'] ?? 'Sản phẩm' }}</div>
                    <div class="text-xs text-ink/60">{{ $it['variant_name'] ?? '' }}</div>
                    <div class="mt-1 text-sm font-semibold text-brand-600">{{ number_format($it['price'] ?? 0) }}₫</div>
                    <div class="mt-2 flex items-center gap-2 text-sm">
                        <x-qty-stepper :value="$it['qty'] ?? 1" :min="1" :max="99" :name="'qty_'.$key" />
                        <button class="text-rose-600 hover:underline" @click="$dispatch('cart:remove',{key:'{{ $key }}'})">Xoá</button>
                    </div>
                </div>
            </div>
            @empty
            <x-empty text="Giỏ hàng trống." />
            @endforelse
        </div>

        <div class="pt-3 border-t border-rose-100">
            <div class="flex items-center justify-between text-sm">
                <span>Tạm tính</span>
                <span id="cartSubtotal" class="font-semibold">{{ number_format($subtotal) }}₫</span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <a href="{{ route('cart.index') }}" class="px-3 py-2 border border-rose-200 rounded-md text-center">Xem giỏ hàng</a>
                <a href="{{ route('checkout.index') }}" class="px-3 py-2 bg-brand-600 text-white rounded-md text-center">Thanh toán</a>
            </div>
        </div>
    </aside>
</div>

<script>
    // Hook UI: remove / update qty (stub; gọi API thật theo endpoint của bạn)
    document.addEventListener('cart:remove', async (e) => {
        const key = e.detail.key;
        // TODO: fetch DELETE /cart/{key}
        const node = document.querySelector(`[data-key="${key}"]`);
        if (node) node.remove();
        window.dispatchEvent(new CustomEvent('toast', {
            detail: {
                type: 'success',
                text: 'Đã xoá khỏi giỏ'
            }
        }));
    });
</script>