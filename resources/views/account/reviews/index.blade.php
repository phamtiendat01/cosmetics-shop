@extends('layouts.app')
@section('title','Đánh giá của tôi')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4">Đánh giá của tôi</h1>

    @if(session('success'))
    <div class="p-3 rounded-xl border bg-emerald-50 text-emerald-700 border-emerald-200 mb-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="p-3 rounded-xl border bg-rose-50 text-rose-700 border-rose-200 mb-4">{{ $errors->first() }}</div>
    @endif

    @if($reviews->isEmpty())
    <div class="p-6 bg-rose-50/60 border border-rose-200 rounded-2xl text-center text-ink/60">
        Bạn chưa viết đánh giá nào.
    </div>
    @else
    <div class="space-y-4">
        @foreach($reviews as $rv)
        <div class="p-4 bg-white border border-rose-100 rounded-2xl shadow-card">
            <div class="flex items-start gap-4">
                @php
                $thumb = optional($rv->product)->thumbnail;
                if ($thumb && !\Illuminate\Support\Str::startsWith($thumb, ['http://','https://'])) {
                $thumb = asset(\Illuminate\Support\Str::startsWith($thumb, ['storage/','/storage/']) ? ltrim($thumb,'/') : 'storage/'.ltrim($thumb,'/'));
                }
                @endphp

                <a href="{{ optional($rv->product) ? route('product.show', $rv->product->slug) : 'javascript:void(0)' }}">
                    <img src="{{ $thumb ?: 'https://placehold.co/72' }}" class="w-16 h-16 rounded border object-cover" alt="">
                </a>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-3">
                        <div class="truncate">
                            @if($rv->orderItem && $rv->orderItem->order)
                            <div class="text-xs text-ink/60 mb-0.5">
                                Mã đơn: <b>{{ $rv->orderItem->order->code }}</b>
                            </div>
                            @endif
                            <a class="font-medium hover:underline truncate" href="{{ optional($rv->product) ? route('product.show', $rv->product->slug) : '#' }}">
                                {{ optional($rv->product)->name ?? 'Sản phẩm' }}
                            </a>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <span class="inline-flex items-center gap-1 text-amber-500">
                                @for($s=1;$s<=5;$s++)
                                    <i class="fa-solid fa-star {{ $s <= ($rv->rating ?? 0) ? '' : 'opacity-30' }}"></i>
                                    @endfor
                            </span>
                            @if($rv->verified_purchase)
                            <span class="px-2 py-0.5 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded">Đã mua</span>
                            @endif
                            @if(!$rv->is_approved)
                            <span class="px-2 py-0.5 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded">Chờ duyệt</span>
                            @endif
                        </div>
                    </div>

                    @if($rv->title)
                    <div class="mt-2 font-semibold break-words">{{ $rv->title }}</div>
                    @endif
                    <div class="mt-1 text-sm text-ink/80 whitespace-pre-line break-words">{{ $rv->content }}</div>
                    <div class="mt-1 text-xs text-ink/50">
                        {{ optional($rv->created_at)->format('d/m/Y H:i') }}
                        @if(!empty($rv->edited_at)) · sửa {{ $rv->edited_at->diffForHumans() }} @endif
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <button
                            class="px-3 py-1.5 rounded-lg border text-ink/80 hover:bg-rose-50 js-edit"
                            data-action="{{ route('account.reviews.update', $rv) }}"
                            data-rating="{{ (int)$rv->rating }}"
                            data-title="{{ e($rv->title) }}"
                            data-content="{{ e($rv->content) }}">Sửa</button>

                        <form method="post" action="{{ route('account.reviews.destroy', $rv) }}"
                            onsubmit="return confirm('Xoá đánh giá này?')">
                            @csrf @method('DELETE')
                            <button class="px-3 py-1.5 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50">
                                Xoá
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $reviews->links() }}
    </div>
    @endif
</div>

{{-- Modal edit dùng lại cho tất cả --}}
<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/40" data-close></div>
    <div class="relative bg-white w-full max-w-lg mx-4 rounded-2xl shadow-xl border">
        <form id="editForm" method="post" class="p-5">
            @csrf @method('PATCH')

            <div class="flex items-center justify-between mb-3">
                <div class="text-lg font-semibold">Sửa đánh giá</div>
                <button type="button" class="text-ink/60 hover:text-ink" data-close>
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            {{-- Rating --}}
            <input type="hidden" id="editRating" name="rating" value="0">
            <div class="mb-3">
                <div class="text-sm font-medium mb-1">Số sao</div>
                <div id="ratingWrap" class="flex items-center gap-1.5 text-amber-500">
                    @for($i=1;$i<=5;$i++)
                        <button type="button" class="star w-8 h-8" data-value="{{ $i }}">
                        <svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full">
                            <path d="M12 .587l3.668 7.431 8.2 1.192-5.934 5.787 1.402 8.168L12 18.896l-7.336 3.869 1.402-8.168L.132 9.21l8.2-1.192L12 .587z" />
                        </svg>
                        </button>
                        @endfor
                        <span id="ratingText" class="ml-2 text-sm text-ink/60">Chọn 1–5</span>
                </div>
            </div>

            <div class="mb-3">
                <label class="text-sm font-medium">Tiêu đề (tuỳ chọn)</label>
                <input id="editTitle" name="title" class="mt-1 w-full border rounded-xl p-3 outline-none focus:ring-2 focus:ring-rose-400">
            </div>

            <div class="mb-3">
                <label class="text-sm font-medium">Nội dung</label>
                <textarea id="editContent" name="content" rows="6" required minlength="10" maxlength="2000"
                    class="mt-1 w-full border rounded-xl p-3 outline-none focus:ring-2 focus:ring-rose-400"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="button" class="px-4 py-2 rounded-xl border" data-close>Huỷ</button>
                <button class="px-5 py-2.5 rounded-xl text-white bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500">
                    Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        // Modal
        const modal = document.getElementById('editModal');
        const form = document.getElementById('editForm');
        const titleEl = document.getElementById('editTitle');
        const contentEl = document.getElementById('editContent');
        const ratingInput = document.getElementById('editRating');
        const stars = Array.from(document.querySelectorAll('#ratingWrap .star'));
        const ratingText = document.getElementById('ratingText');

        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.querySelectorAll('[data-close]').forEach(btn => btn.addEventListener('click', closeModal));

        function paint(n) {
            stars.forEach((b, i) => b.classList.toggle('opacity-30', i + 1 > n));
            ratingText.textContent = n ? (n + '/5') : 'Chọn 1–5';
        }
        stars.forEach(btn => {
            const v = +btn.dataset.value;
            btn.addEventListener('click', () => {
                ratingInput.value = v;
                paint(v);
            });
            btn.addEventListener('mouseenter', () => paint(v));
            btn.addEventListener('mouseleave', () => paint(+ratingInput.value || 0));
        });

        document.querySelectorAll('.js-edit').forEach(btn => {
            btn.addEventListener('click', () => {
                form.action = btn.dataset.action;
                ratingInput.value = parseInt(btn.dataset.rating || '0', 10);
                titleEl.value = btn.dataset.title || '';
                contentEl.value = btn.dataset.content || '';
                paint(+ratingInput.value || 0);
                openModal();
            });
        });

        // ESC to close
        window.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeModal();
        });
    })();
</script>
@endsection