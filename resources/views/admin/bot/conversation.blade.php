@extends('admin.layouts.app')
@section('title', 'CosmeBot - Chi tiết hội thoại')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-comments text-rose-600"></i>
            Hội thoại #{{ $conversation->id }}
        </h1>
        <p class="text-slate-600 mt-1">
            @if($conversation->user)
            User: {{ $conversation->user->name }} ({{ $conversation->user->email }})
            @else
            Guest - Session: {{ $conversation->session_id }}
            @endif
        </p>
    </div>
    <a href="{{ route('admin.bot.conversations') }}" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition">
        <i class="fa-solid fa-arrow-left mr-2"></i> Quay lại
    </a>
</div>

{{-- Conversation Info --}}
<div class="bg-white border border-slate-200 rounded-xl p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <div class="text-sm text-slate-600">Trạng thái</div>
            <div class="font-semibold text-slate-900">
                @if($conversation->status === 'active')
                <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Đang hoạt động</span>
                @elseif($conversation->status === 'completed')
                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">Hoàn tất</span>
                @else
                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">Bỏ dở</span>
                @endif
            </div>
        </div>
        <div>
            <div class="text-sm text-slate-600">Số tin nhắn</div>
            <div class="font-semibold text-slate-900">{{ $conversation->messages->count() }}</div>
        </div>
        <div>
            <div class="text-sm text-slate-600">Bắt đầu</div>
            <div class="font-semibold text-slate-900">{{ $conversation->started_at->format('d/m/Y H:i') }}</div>
        </div>
        <div>
            <div class="text-sm text-slate-600">Cập nhật</div>
            <div class="font-semibold text-slate-900">{{ $conversation->updated_at->format('d/m/Y H:i') }}</div>
        </div>
    </div>
</div>

{{-- Messages --}}
<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b border-slate-200">
        <h3 class="text-lg font-semibold text-slate-900">Lịch sử tin nhắn</h3>
    </div>
    <div class="p-6 space-y-4 max-h-[600px] overflow-y-auto">
        @forelse($conversation->messages as $msg)
        <div class="flex gap-4 {{ $msg->isUser() ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[70%] {{ $msg->isUser() ? 'order-2' : 'order-1' }}">
                <div class="flex items-center gap-2 mb-1">
                    @if($msg->isUser())
                    <i class="fa-solid fa-user text-blue-600"></i>
                    <span class="text-xs font-semibold text-slate-700">User</span>
                    @else
                    <i class="fa-solid fa-robot text-rose-600"></i>
                    <span class="text-xs font-semibold text-slate-700">CosmeBot</span>
                    @endif
                    @if($msg->intent)
                    <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs">{{ $msg->intent }}</span>
                    @endif
                    @if($msg->confidence)
                    <span class="text-xs text-slate-500">({{ number_format($msg->confidence * 100, 0) }}%)</span>
                    @endif
                </div>
                <div class="p-3 rounded-lg {{ $msg->isUser() ? 'bg-blue-50 text-blue-900' : 'bg-rose-50 text-slate-900' }}">
                    <div class="text-sm whitespace-pre-wrap">{!! nl2br(e($msg->content)) !!}</div>
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

