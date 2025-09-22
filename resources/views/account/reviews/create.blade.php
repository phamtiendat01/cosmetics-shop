@extends('layouts.app')
@section('title','Đánh giá sản phẩm')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6">
    {{-- Thông báo / lỗi --}}
    @if (session('ok'))
    <div class="p-3 rounded-xl border bg-emerald-50 text-emerald-700 border-emerald-200 mb-4">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
    <div class="p-3 rounded-xl border bg-rose-50 text-rose-700 border-rose-200 mb-4">
        <ul class="list-disc list-inside text-sm">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    @php
    $thumb = $product->thumbnail ?? $product->image ?? null;
    if ($thumb && !\Illuminate\Support\Str::startsWith($thumb, ['http://','https://'])) {
    $thumb = asset(\Illuminate\Support\Str::startsWith($thumb, ['storage/','/storage/']) ? ltrim($thumb,'/') : 'storage/'.ltrim($thumb,'/'));
    }
    @endphp

    {{-- Header sản phẩm --}}
    <div class="bg-rose-50/60 border border-rose-200 rounded-2xl p-4 md:p-5 flex gap-4 items-start shadow-card">
        <img src="{{ $thumb ?: 'https://placehold.co/96' }}" class="w-20 h-20 md:w-24 md:h-24 object-contain bg-white border border-rose-100 rounded-xl shadow-sm" alt="">
        <div class="flex-1">
            <div class="text-xs md:text-sm text-ink/60">Mã đơn: <b>{{ $order->code }}</b></div>
            <h1 class="text-lg md:text-xl font-semibold mt-0.5">{{ $product->name }}</h1>
            @if($item->variant_name ?? false)
            <div class="mt-1 inline-flex rounded-full border px-2 py-0.5 text-xs text-ink/70">
                {{ $item->variant_name }}
            </div>
            @endif
        </div>
    </div>

    <form id="review-form" class="mt-6 space-y-6"
        method="post"
        action="{{ route('account.order-items.reviews.store', [$order, $item]) }}">
        @csrf

        {{-- ⭐ Rating: tương tác, hỗ trợ keyboard --}}
        <div>
            <label class="text-sm font-medium block mb-1">Đánh giá của bạn</label>
            <div id="rating" class="flex items-center gap-2 text-amber-500" role="radiogroup" aria-label="Chọn số sao">
                <input type="hidden" name="rating" id="rating-input" value="{{ (int)old('rating', 0) }}">
                @for($i=1;$i<=5;$i++)
                    <button type="button"
                    class="star w-8 h-8 md:w-9 md:h-9 transition transform hover:scale-110 focus:scale-110 outline-none"
                    data-value="{{ $i }}"
                    role="radio"
                    aria-label="{{ $i }} sao"
                    tabindex="0">
                    {{-- SVG star dùng currentColor, không phụ thuộc icon pack --}}
                    <svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full">
                        <path d="M12 .587l3.668 7.431 8.2 1.192-5.934 5.787 1.402 8.168L12 18.896l-7.336 3.869 1.402-8.168L.132 9.21l8.2-1.192L12 .587z" />
                    </svg>
                    </button>
                    @endfor
                    <span id="rating-text" class="ml-2 text-sm text-ink/60">Chọn 1–5 sao</span>
            </div>
            <p class="mt-1 text-xs text-ink/50">Mẹo: dùng phím ← → để tăng/giảm số sao.</p>
        </div>

        <div>
            <label class="text-sm font-medium">Tiêu đề (tuỳ chọn)</label>
            <input name="title" value="{{ old('title') }}"
                class="mt-1 w-full border border-rose-200 bg-white rounded-xl p-3 outline-none focus:ring-2 focus:ring-rose-400 shadow-sm">
        </div>

        <div>
            <label class="text-sm font-medium">Nội dung</label>
            <textarea id="review-content" name="content" rows="6" required minlength="10" maxlength="2000"
                class="mt-1 w-full border border-rose-200 bg-white rounded-xl p-3 outline-none focus:ring-2 focus:ring-rose-400 shadow-sm"
                placeholder="Chia sẻ trải nghiệm sau khi mua & sử dụng...">{{ old('content') }}</textarea>
            <div class="flex justify-end mt-1 text-xs text-ink/50">
                <span id="cc">0</span>/2000
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button class="px-5 py-2.5 rounded-xl text-white bg-gradient-to-r from-rose-600 to-pink-600
                     hover:shadow-lg hover:from-rose-500 hover:to-pink-500 transition">
                Gửi đánh giá
            </button>
            <a href="{{ route('account.orders.show', $order) }}"
                class="px-4 py-2 rounded-xl border border-rose-200 text-ink/70 hover:bg-rose-50 transition">
                Quay lại đơn hàng
            </a>
        </div>
    </form>
</div>

{{-- JS nhỏ cho rating + counter, không cần Alpine --}}
<script>
    (function() {
        const wrap = document.getElementById('rating');
        const input = document.getElementById('rating-input');
        const stars = Array.from(wrap.querySelectorAll('.star'));
        const text = document.getElementById('rating-text');
        const form = document.getElementById('review-form');
        const ta = document.getElementById('review-content');
        const cc = document.getElementById('cc');

        function paint(n) {
            stars.forEach((b, i) => b.classList.toggle('opacity-30', i + 1 > n));
            text.textContent = n ? (n + '/5') : 'Chọn 1–5 sao';
        }

        stars.forEach(btn => {
            const v = +btn.dataset.value;
            btn.addEventListener('click', () => {
                input.value = v;
                paint(v);
            });
            btn.addEventListener('mouseenter', () => paint(v));
            btn.addEventListener('mouseleave', () => paint(+input.value || 0));
            btn.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    input.value = v;
                    paint(v);
                }
                if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    input.value = Math.min(5, (+input.value || 0) + 1);
                    paint(+input.value);
                }
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    input.value = Math.max(1, (+input.value || 1) - 1);
                    paint(+input.value);
                }
            });
        });

        // Counter nội dung
        function updateCount() {
            cc.textContent = ta.value.length;
        }
        ta.addEventListener('input', updateCount);
        updateCount();

        // Validate: bắt buộc chọn sao
        form.addEventListener('submit', (e) => {
            if (!(+input.value > 0)) {
                e.preventDefault();
                wrap.classList.add('ring-2', 'ring-rose-400', 'rounded-lg');
                text.textContent = 'Vui lòng chọn số sao';
                setTimeout(() => wrap.classList.remove('ring-2', 'ring-rose-400'), 1200);
            }
        });

        // Vẽ trạng thái ban đầu
        paint(+input.value || 0);
    })();
</script>
@endsection