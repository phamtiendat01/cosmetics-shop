@extends('admin.layouts.app')
@section('title', 'CosmeBot - Chi tiết hội thoại')

@section('content')
<div class="toolbar">
    <div class="toolbar-title">Hội thoại #{{ $conversation->id }}</div>
    <div class="toolbar-actions">
        <a href="{{ route('admin.bot.conversations') }}" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Quay lại
        </a>
    </div>
</div>

{{-- Conversation Info --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.1s backwards;">
        <div class="text-xs text-slate-500 mb-1">Trạng thái</div>
        <div>
            @if($conversation->status === 'active')
            <span class="badge badge-green"><span class="badge-dot"></span>Đang hoạt động</span>
            @elseif($conversation->status === 'completed')
            <span class="badge"><span class="badge-dot"></span>Hoàn tất</span>
            @else
            <span class="badge badge-red"><span class="badge-dot"></span>Bỏ dở</span>
            @endif
        </div>
    </div>
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.2s backwards;">
        <div class="text-xs text-slate-500 mb-1">Số tin nhắn</div>
        <div class="text-2xl font-bold">{{ $conversation->messages->count() }}</div>
    </div>
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.3s backwards;">
        <div class="text-xs text-slate-500 mb-1">Bắt đầu</div>
        <div class="text-xs font-medium">{{ $conversation->started_at->format('d/m/Y H:i') }}</div>
    </div>
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.4s backwards;">
        <div class="text-xs text-slate-500 mb-1">Cập nhật</div>
        <div class="text-xs font-medium">{{ $conversation->updated_at->format('d/m/Y H:i') }}</div>
    </div>
</div>

<style>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

{{-- Messages --}}
<div class="card p-0">
    <div class="p-3 border-b bg-slate-50">
        <div class="font-semibold">Lịch sử tin nhắn</div>
    </div>
    <div class="p-4 space-y-3 max-h-[500px] overflow-y-auto">
        @forelse($conversation->messages as $index => $msg)
        <div class="flex gap-3 {{ $msg->isUser() ? 'justify-end' : 'justify-start' }}"
             style="animation: fadeInUp 0.3s ease-out {{ 0.1 + ($index * 0.05) }}s backwards;">
            <div class="max-w-[75%] {{ $msg->isUser() ? 'order-2' : 'order-1' }}">
                <div class="flex items-center gap-2 mb-1">
                    @if($msg->isUser())
                    <i class="fa-solid fa-user text-blue-600"></i>
                    <span class="text-xs font-semibold">User</span>
                    @else
                    <i class="fa-solid fa-robot text-rose-600"></i>
                    <span class="text-xs font-semibold">CosmeBot</span>
                    @endif
                    @if($msg->intent)
                    <span class="badge">{{ $msg->intent }}</span>
                    @endif
                    @if($msg->confidence)
                    <span class="text-xs text-slate-500">({{ number_format($msg->confidence * 100, 0) }}%)</span>
                    @endif
                </div>
                <div class="p-2.5 rounded {{ $msg->isUser() ? 'bg-slate-900 text-white' : 'bg-slate-50 border border-slate-200' }}">
                    <div class="text-xs whitespace-pre-wrap leading-relaxed">{!! nl2br(e($msg->content)) !!}</div>
                    @if($msg->tools_used && !empty($msg->tools_used))
                    <div class="mt-2 pt-2 border-t border-slate-200">
                        <div class="text-xs text-slate-600 mb-1">Tools used:</div>
                        <div class="flex flex-wrap gap-1">
                            @foreach(array_keys($msg->tools_used) as $tool)
                            <span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded text-xs">{{ $tool }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                <div class="text-xs text-slate-400 mt-1">{{ $msg->created_at->format('H:i:s') }}</div>
            </div>
        </div>
        @empty
        <div class="text-center text-slate-500 py-8">Chưa có tin nhắn nào.</div>
        @endforelse
    </div>
</div>
@endsection

