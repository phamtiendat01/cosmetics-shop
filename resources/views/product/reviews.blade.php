@php
use App\Models\Review;

// viewer hiện tại (để chính chủ vẫn thấy review của mình)
$viewerId = auth()->id();

// Lấy review từ bảng `reviews` theo product_id.
// - Hiển thị tất cả review đã duyệt
// - Nếu user đang đăng nhập, cho hiển thị thêm các review do user đó viết
$reviews = Review::where('product_id', $product->id)
->where(function ($q) use ($viewerId) {
$q->where('is_approved', 1);
if ($viewerId) {
$q->orWhere('user_id', $viewerId);
}
})
->with('user:id,name')
->latest()
->get();
@endphp

<div class="space-y-4">
    @forelse($reviews as $rv)
    <div class="p-4 border border-rose-100 rounded-xl bg-white">
        <div class="flex items-center justify-between gap-2">
            <div class="font-medium">
                {{ optional($rv->user)->name ?: 'Ẩn danh' }}
                @if($rv->verified_purchase)
                <span class="ml-2 px-2 py-0.5 text-emerald-700 bg-emerald-50 border border-emerald-200 rounded text-xs">
                    Đã mua
                </span>
                @endif
                @if(!$rv->is_approved && auth()->id() === $rv->user_id)
                <span class="ml-1 px-2 py-0.5 text-amber-700 bg-amber-50 border border-amber-200 rounded text-xs">
                    Chờ duyệt
                </span>
                @endif
            </div>
            <div class="text-amber-500">
                @for($i=1;$i<=5;$i++)
                    <i class="fa-solid fa-star {{ $i <= (int)$rv->rating ? '' : 'opacity-30' }}"></i>
                    @endfor
            </div>
        </div>

        @if($rv->title)
        <div class="mt-1 font-semibold">{{ $rv->title }}</div>
        @endif
        <div class="mt-1 text-sm text-ink/80 whitespace-pre-line">{{ $rv->content }}</div>
        <div class="mt-1 text-xs text-ink/50">{{ optional($rv->created_at)->format('d/m/Y H:i') }}</div>
    </div>
    @empty
    <div class="py-10 text-center text-ink/60">
        <div class="mx-auto w-10 h-10 rounded-full bg-rose-50 flex items-center justify-center mb-2">
            <i class="fa-regular fa-comment-dots text-rose-500"></i>
        </div>
        Chưa có đánh giá nào.
    </div>
    @endforelse

    <div class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-md text-sm">
        Muốn viết đánh giá? Vào
        <a class="underline font-medium" href="{{ route('account.orders.index') }}">Đơn hàng của bạn</a>,
        mở chi tiết đơn <b>đã thanh toán</b> rồi bấm <b>Đánh giá</b> tại từng sản phẩm.
    </div>
</div>