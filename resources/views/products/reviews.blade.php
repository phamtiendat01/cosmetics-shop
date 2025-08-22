@props(['product'])

@php
$reviews = $product->reviews ?? collect(); // Controller nên eager load: ->with('reviews')
@endphp

<div class="space-y-4">
    @forelse($reviews as $rv)
    <div class="p-4 border border-rose-100 rounded-xl">
        <div class="flex items-center justify-between">
            <div class="font-medium">{{ $rv->user_name ?? 'Ẩn danh' }}</div>
            <div class="text-amber-500">
                @for($i=1;$i<=5;$i++)
                    <i class="fa-solid fa-star {{ $i <= ($rv->rating ?? 0) ? '' : 'opacity-30' }}"></i>
                    @endfor
            </div>
        </div>
        <div class="mt-2 text-sm text-ink/80 whitespace-pre-line">{{ $rv->content }}</div>
        <div class="mt-1 text-xs text-ink/50">{{ optional($rv->created_at)->format('d/m/Y H:i') }}</div>
    </div>
    @empty
    <x-empty icon="fa-regular fa-comment-dots" text="Chưa có đánh giá nào." />
    @endforelse

    {{-- Form (UI demo) --}}
    <form class="mt-4 grid gap-2">
        <div class="text-sm font-medium">Viết đánh giá của bạn</div>
        <textarea class="border border-rose-200 rounded-md p-2 outline-none focus:ring-2 focus:ring-brand-400"
            rows="3" placeholder="Chia sẻ trải nghiệm..."></textarea>
        <button class="self-start px-4 py-2 bg-brand-600 text-white rounded-md">Gửi đánh giá</button>
    </form>
</div>