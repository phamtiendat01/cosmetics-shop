@extends('admin.layouts.app')
@section('title','Chi tiết đánh giá')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

<div class="toolbar">
    <div class="toolbar-title">Chi tiết đánh giá #{{ $review->id }}</div>
    <div class="toolbar-actions flex items-center gap-2">
        {{-- Duyệt / Bỏ duyệt --}}
        @if(!$review->is_approved)
        <form method="post" action="{{ route('admin.reviews.approve',$review) }}" class="inline">
            @csrf @method('PATCH')
            <button class="btn btn-primary btn-sm">Duyệt</button>
        </form>
        @else
        <form method="post" action="{{ route('admin.reviews.unapprove',$review) }}" class="inline">
            @csrf @method('PATCH')
            <button class="btn btn-outline btn-sm">Bỏ duyệt</button>
        </form>
        @endif

        {{-- Xoá --}}
        <form method="post" action="{{ route('admin.reviews.destroy',$review) }}"
            onsubmit="return confirm('Xoá đánh giá này?')" class="inline">
            @csrf @method('DELETE')
            <button class="btn btn-danger btn-sm">Xoá</button>
        </form>
    </div>
</div>

<div class="grid md:grid-cols-3 gap-3">
    {{-- Nội dung đánh giá --}}
    <div class="md:col-span-2 card p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="text-amber-500 text-xl">
                @for($i=1;$i<=5;$i++)
                    <i class="fa-solid fa-star {{ $i <= ((int)$review->rating) ? '' : 'opacity-30' }}"></i>
                    @endfor
            </div>

            <div>
                @if($review->is_approved)
                <span class="badge badge-green">
                    <span class="badge-dot" style="background:#10b981"></span> Đã duyệt
                </span>
                @else
                <span class="badge badge-amber">
                    <span class="badge-dot" style="background:#f59e0b"></span> Chờ duyệt
                </span>
                @endif
                @if($review->verified_purchase)
                <span class="badge badge-soft ml-1">Đã mua</span>
                @endif
            </div>
        </div>

        <div class="mt-3">
            <div class="text-xs text-slate-500">{{ optional($review->created_at)->format('d/m/Y H:i') }}</div>
            <div class="mt-1 text-xl font-semibold">{{ $review->title ?: '—' }}</div>
            <div class="mt-3 whitespace-pre-line leading-relaxed">{{ $review->content }}</div>
        </div>

        {{-- (tuỳ chọn) admin reply nếu DB có cột admin_reply --}}
        @php $hasReply = \Illuminate\Support\Facades\Schema::hasColumn('reviews','admin_reply'); @endphp
        @if($hasReply)
        <div class="mt-5 pt-4 border-t">
            <div class="font-semibold mb-2">Trả lời của Admin</div>
            @if($review->admin_reply)
            <div class="p-3 rounded border bg-slate-50 whitespace-pre-line">{{ $review->admin_reply }}</div>
            @else
            <form method="post" action="{{ route('admin.reviews.reply',$review) }}" class="space-y-2">
                @csrf
                <textarea name="reply" rows="4" class="form-control" placeholder="Nhập nội dung trả lời…"></textarea>
                <button class="btn btn-soft btn-sm">Gửi trả lời</button>
            </form>
            @endif
        </div>
        @endif
    </div>

    {{-- Thông tin liên quan --}}
    <div class="card p-5">
        <div class="font-semibold mb-3">Thông tin</div>
        <div class="space-y-3 text-sm">
            <div class="flex items-center gap-3">
                @php
                $thumb = optional($review->product)->thumbnail;
                if ($thumb && !\Illuminate\Support\Str::startsWith($thumb,['http://','https://'])) {
                $thumb = asset(\Illuminate\Support\Str::startsWith($thumb,['storage/','/storage/']) ? ltrim($thumb,'/') : 'storage/'.ltrim($thumb,'/'));
                }
                @endphp
                <img src="{{ $thumb ?: 'https://placehold.co/64' }}" class="w-14 h-14 rounded border object-cover" alt="">
                <div>
                    <div class="text-slate-500">Sản phẩm</div>
                    @if($review->product)
                    <a class="text-blue-600 hover:underline" href="{{ route('product.show',$review->product->slug) }}" target="_blank">
                        {{ $review->product->name }}
                    </a>
                    @else
                    —
                    @endif
                </div>
            </div>

            <div>
                <div class="text-slate-500">Người dùng</div>
                <div>{{ optional($review->user)->name ?? 'Ẩn danh' }}</div>
            </div>

            @if($review->orderItem && $review->orderItem->order)
            <div>
                <div class="text-slate-500">Mã đơn</div>
                <div class="font-medium">{{ $review->orderItem->order->code }}</div>
            </div>
            @endif

            <div>
                <div class="text-slate-500">Trạng thái</div>
                <div>
                    @if($review->is_approved)
                    <span class="badge badge-green"><span class="badge-dot" style="background:#10b981"></span> Đã duyệt</span>
                    @else
                    <span class="badge badge-amber"><span class="badge-dot" style="background:#f59e0b"></span> Chờ duyệt</span>
                    @endif
                    @if($review->verified_purchase)
                    <span class="badge badge-soft ml-1">Đã mua</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection