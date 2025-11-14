{{-- resources/views/account/returns/create.blade.php --}}
@extends('layouts.app')
@section('title','Yêu cầu trả hàng')

@section('content')
@php
use Illuminate\Support\Str;

// Cửa sổ trả hàng (mặc định 14 ngày)
$windowDays = (int) (config('orders.return_window_days', env('RETURN_WINDOW_DAYS', 14)));
$events = $order->events ?? collect();
$completedAt = null;

if (!empty($order->completed_at)) {
try { $completedAt = \Carbon\Carbon::parse($order->completed_at); } catch (\Throwable $e) {}
}
if (!$completedAt && $events->count()) {
foreach ($events->sortByDesc('created_at') as $ev) {
if (($ev->type ?? '') !== 'status_changed') continue;
$new = data_get($ev,'new.status');
if (in_array(Str::snake((string)$new), ['completed','hoan_tat'], true)) {
$completedAt = \Carbon\Carbon::parse($ev->created_at);
break;
}
}
}
$withinWindow = $completedAt ? now()->lte($completedAt->copy()->addDays($windowDays)) : false;
$walletBalance = (int) optional(auth()->user()->wallet)->balance;
@endphp

<div class="max-w-5xl mx-auto px-4 py-6">

    {{-- Flash / error --}}
    @if(session('ok'))
    <div class="mb-3 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 shadow-sm" data-auto-dismiss="3000">
        <b>Thành công:</b> {{ session('ok') }}
    </div>
    @endif
    @if($errors->any())
    <div class="mb-3 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 shadow-sm">
        <b>Lỗi:</b> {{ $errors->first() }}
    </div>
    @endif

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Yêu cầu trả hàng — Đơn #{{ $order->code }}</h1>
            <div class="text-sm text-ink/60 mt-1">Đặt lúc {{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
        </div>
        <a href="{{ route('account.orders.show', $order) }}" class="btn btn-outline">← Quay lại đơn</a>
    </div>

    {{-- Chính sách + hạn cuối --}}
    <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900 text-sm">
        <div class="font-semibold mb-1">Chính sách trả hàng</div>
        <ul class="list-disc pl-5 space-y-1">
            <li>Áp dụng khi đơn ở <b>Đang giao</b> hoặc <b>Hoàn tất</b>.</li>
            <li>Thời hạn yêu cầu: trong vòng <b>{{ $windowDays }}</b> ngày kể từ khi hoàn tất.</li>
            @if($completedAt)
            <li>Hạn cuối: <b>{{ $completedAt->copy()->addDays($windowDays)->format('d/m/Y H:i') }}</b></li>
            @endif
        </ul>
        @unless($withinWindow)
        <div class="mt-2 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 p-3">
            Đơn đã quá hạn {{ $windowDays }} ngày kể từ khi hoàn tất nên không thể tạo yêu cầu.
        </div>
        @endunless
    </div>

    {{-- Form --}}
    <form id="return-form" method="POST" action="{{ route('account.returns.store', $order) }}" class="mt-6 grid lg:grid-cols-3 gap-6">
        @csrf
        {{-- ép hoàn về ví --}}
        <input type="hidden" name="refund_method" value="wallet">

        {{-- Cột trái: Lý do + Sản phẩm --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- Lý do --}}
            <div class="bg-white border border-rose-100 rounded-2xl shadow-sm p-5">
                <div class="text-sm font-semibold mb-3">Lý do trả hàng</div>
                <div class="grid sm:grid-cols-2 gap-2 mb-3">
                    @php $commonReasons = ['Sản phẩm không phù hợp','Thiếu/nhầm hàng','Lỗi seal/tem','Hư hỏng do vận chuyển','Khác']; @endphp
                    @foreach($commonReasons as $reason)
                    <label class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 cursor-pointer hover:bg-rose-50">
                        <input type="radio" name="reason_choice" value="{{ $reason }}" class="accent-rose-600">
                        <span class="text-sm">{{ $reason }}</span>
                    </label>
                    @endforeach
                </div>
                <input id="reasonInput" name="reason" class="form-control w-full" placeholder="Ghi rõ thêm (tuỳ chọn)">
                <div class="text-xs text-ink/50 mt-1">Bạn có thể chọn nhanh lý do ở trên hoặc tự nhập bên dưới.</div>
            </div>

            {{-- Danh sách sản phẩm --}}
            <div class="bg-white border border-rose-100 rounded-2xl shadow-sm overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-rose-50/60 text-ink/70">
                        <tr>
                            <th class="px-4 py-3 text-left  font-medium">Sản phẩm</th>
                            <th class="px-4 py-3 text-center font-medium">Đã mua</th>
                            <th class="px-4 py-3 text-right font-medium">Đơn giá</th>
                            <th class="px-4 py-3 text-center font-medium">Trả</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-100">
                        @foreach($order->items as $it)
                        @php
                        // Resolve variant snapshot
                        $variant = $it->variant;
                        if (is_string($variant)) {
                        $decoded = json_decode($variant, true);
                        if (json_last_error() === JSON_ERROR_NONE) $variant = $decoded;
                        }
                        if (!is_array($variant)) $variant = [];
                        $variantLabel = $it->variant_name ?? $it->variant_name_snapshot ?? ($variant['name'] ?? ($variant['title'] ?? null));

                        // Resolve thumbnail
                        $thumb = $it->thumbnail
                        ?? ($variant['image'] ?? $variant['thumbnail'] ?? null)
                        ?? optional($it->product)->thumbnail
                        ?? optional($it->product)->image;
                        if ($thumb && !Str::startsWith($thumb, ['http://','https://'])) {
                        $thumb = asset(Str::startsWith($thumb, ['storage/','/storage/']) ? ltrim($thumb,'/') : 'storage/'.ltrim($thumb,'/'));
                        }

                        // Unit price fallback
                        $unit = (float)($it->unit_price ?? $it->price ?? 0);
                        if (!$unit && isset($variant['price'])) $unit = (float)$variant['price'];
                        if (!$unit && optional($it->product)->price) $unit = (float)$it->product->price;
                        @endphp
                        <tr class="hover:bg-rose-50/40">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($thumb)
                                    <img src="{{ $thumb }}" class="w-12 h-12 rounded object-cover border" alt="">
                                    @else
                                    <div class="w-12 h-12 rounded border flex items-center justify-center text-ink/40">IMG</div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="font-medium truncate">{{ $it->product_name_snapshot ?? optional($it->product)->name ?? 'Sản phẩm' }}</div>
                                        @if($variantLabel)
                                        <div class="text-xs text-ink/60">{{ $variantLabel }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">{{ $it->qty }}</td>
                            <td class="px-4 py-3 text-right">₫{{ number_format($unit) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <button class="qty-btn w-8 h-8 rounded border hover:bg-rose-50" type="button" data-act="dec">−</button>
                                    <input type="hidden" name="items[{{$loop->index}}][order_item_id]" value="{{ $it->id }}">
                                    <input class="qty-input w-16 border rounded px-2 py-1 text-center"
                                        type="number" min="0" max="{{ $it->qty }}"
                                        name="items[{{$loop->index}}][qty]"
                                        value="{{ old('items.'.$loop->index.'.qty', 0) }}"
                                        data-price="{{ $unit }}">
                                    <button class="qty-btn w-8 h-8 rounded border hover:bg-rose-50" type="button" data-act="inc">＋</button>
                                </div>
                                <div class="text-[11px] text-ink/50 mt-1">Tối đa: {{ $it->qty }}</div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Cột phải: Tóm tắt & Hoàn về ví --}}
        <aside class="space-y-4">
            <div class="bg-white border border-rose-100 rounded-2xl shadow-sm p-5">
                <div class="text-sm font-semibold mb-2">Tóm tắt</div>
                <div class="text-sm flex justify-between"><span>Số dòng trả</span><b id="sum-lines">0</b></div>
                <div class="text-sm flex justify-between"><span>Tổng SL</span><b id="sum-qty">0</b></div>
                <div class="border-t my-2"></div>
                <div class="flex justify-between items-center">
                    <span class="font-semibold">Ước tính hoàn</span>
                    <span id="sum-amount" class="text-lg font-semibold">₫0</span>
                </div>
            </div>

            {{-- ✅ Chỉ hoàn về Ví Cosme --}}
            <div class="bg-white border border-rose-100 rounded-2xl shadow-sm p-5">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-600 to-pink-500 text-white grid place-content-center shadow">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-semibold">Hoàn tiền về Ví Cosme</div>
                        <div class="text-sm text-ink/70 mt-1">
                            Tiền hoàn sẽ tự động cộng vào <b>Ví Cosme</b> của bạn sau khi yêu cầu được duyệt và đánh dấu <b>Đã hoàn tiền</b>.
                        </div>
                        <div class="mt-2 text-xs text-ink/50">
                            Số dư ví hiện tại: <b>₫{{ number_format($walletBalance) }}</b>.
                        </div>
                    </div>
                </div>
                {{-- Không có input chọn – đã có hidden input refund_method=wallet ở trên --}}
            </div>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('account.orders.show',$order) }}" class="btn btn-outline">Hủy</a>
                <button id="btn-open-confirm" type="button"
                    class="btn btn-primary {{ $withinWindow ? '' : 'opacity-50 cursor-not-allowed' }}"
                    {{ $withinWindow ? '' : 'disabled' }}>
                    Gửi yêu cầu
                </button>
            </div>
        </aside>
    </form>
</div>

{{-- Modal xác nhận --}}
<div id="confirm-modal" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" data-close></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-2xl bg-white border shadow-2xl">
            <div class="p-5">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center
                                bg-gradient-to-br from-rose-600 to-pink-600 text-white shadow">
                        <i class="fa-solid fa-rotate-left"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold">Xác nhận gửi yêu cầu trả hàng?</h3>
                        <p class="text-sm text-ink/70 mt-1">
                            Bạn sắp gửi yêu cầu trả <b id="cf-qty">0</b> sản phẩm, ước tính hoàn <b id="cf-amount">₫0</b> (sẽ cộng vào Ví Cosme).
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-4">
                    <button type="button" class="btn btn-outline" data-close>Để sau</button>
                    <button id="btn-submit" type="button"
                        class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm text-white
                                   bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 shadow">
                        <span class="spinner hidden h-4 w-4 border-2 border-white/60 border-t-transparent rounded-full animate-spin"></span>
                        <span class="label">Xác nhận</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- JS --}}
<script>
    (() => {
        // Đổ nhanh lý do → input
        document.querySelectorAll('input[name="reason_choice"]').forEach(r => {
            r.addEventListener('change', () => {
                const box = document.getElementById('reasonInput');
                if (r.checked) box.value = r.value;
            });
        });

        const fmt = n => n.toLocaleString('vi-VN');

        // Tính tổng
        const qtyInputs = Array.from(document.querySelectorAll('.qty-input'));
        const sumLines = document.getElementById('sum-lines');
        const sumQty = document.getElementById('sum-qty');
        const sumAmount = document.getElementById('sum-amount');

        function recalc() {
            let lines = 0,
                qty = 0,
                amount = 0;
            qtyInputs.forEach(i => {
                const v = Math.max(0, Math.min(+i.value || 0, +i.max || 0));
                if (v > 0) lines++;
                qty += v;
                amount += v * (+i.dataset.price || 0);
                i.value = v;
            });
            sumLines.textContent = lines;
            sumQty.textContent = qty;
            sumAmount.textContent = '₫' + fmt(Math.round(amount));
            return {
                qty,
                amount
            };
        }
        recalc();

        document.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = btn.closest('td').querySelector('.qty-input');
                const max = +input.max || 0;
                let val = +input.value || 0;
                val = btn.dataset.act === 'inc' ? Math.min(val + 1, max) : Math.max(val - 1, 0);
                input.value = val;
                recalc();
            });
        });
        qtyInputs.forEach(i => i.addEventListener('input', recalc));

        const withinWindow = @json($withinWindow);

        // Modal
        const openBtn = document.getElementById('btn-open-confirm');
        const modal = document.getElementById('confirm-modal');
        const closeModal = () => modal.classList.add('hidden');

        openBtn?.addEventListener('click', () => {
            if (!withinWindow) {
                alert('Đơn đã quá hạn thời gian trả hàng.');
                return;
            }
            const {
                qty,
                amount
            } = recalc();
            if (qty <= 0) {
                alert('Vui lòng chọn số lượng cần trả.');
                return;
            }
            document.getElementById('cf-qty').textContent = qty;
            document.getElementById('cf-amount').textContent = '₫' + fmt(Math.round(amount));
            modal.classList.remove('hidden');
        });

        document.addEventListener('click', e => {
            if (e.target.hasAttribute('data-close')) closeModal();
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeModal();
        });

        // Submit UX
        const submitBtn = document.getElementById('btn-submit');
        const form = document.getElementById('return-form');
        submitBtn?.addEventListener('click', () => {
            const {
                qty
            } = recalc();
            if (qty <= 0) return;
            submitBtn.setAttribute('disabled', 'disabled');
            submitBtn.querySelector('.spinner')?.classList.remove('hidden');
            submitBtn.querySelector('.label').textContent = 'Đang gửi...';
            form.submit();
        });

        // Tự ẩn flash
        document.querySelectorAll('[data-auto-dismiss]')?.forEach(el => {
            const ms = +el.getAttribute('data-auto-dismiss') || 3000;
            setTimeout(() => el.remove(), ms);
        });
    })();
</script>
@endsection