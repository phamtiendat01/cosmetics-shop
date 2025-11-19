@extends('admin.layouts.app')
@section('title', 'CosmeBot - Hội thoại')

@section('content')
<style>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif

<div class="toolbar">
    <div class="toolbar-title">Hội thoại</div>
    <div class="toolbar-actions"></div>
</div>

{{-- Filters --}}
<div class="card p-3 mb-3">
    <form method="GET" class="grid md:grid-cols-3 gap-2 items-end">
        <div>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Session ID, tên user..." class="form-control">
        </div>
        <div>
            <select name="status" class="form-control" id="statusSelect">
                <option value="">Tất cả trạng thái</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Hoàn tất</option>
                <option value="abandoned" {{ request('status') === 'abandoned' ? 'selected' : '' }}>Bỏ dở</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn btn-soft btn-sm">Lọc</button>
            <a href="{{ route('admin.bot.conversations') }}" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card table-wrap p-0">
    <table class="table-admin">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Session</th>
                <th>Số tin nhắn</th>
                <th>Trạng thái</th>
                <th>Cập nhật</th>
                <th class="col-actions">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($conversations as $index => $conv)
            <tr style="animation: fadeInUp 0.3s ease-out {{ 0.1 + ($index * 0.03) }}s backwards;">
                <td>#{{ $conv->id }}</td>
                <td>
                    @if($conv->user)
                    <div class="font-medium">{{ $conv->user->name }}</div>
                    <div class="text-xs text-slate-500">{{ $conv->user->email }}</div>
                    @else
                    <span class="text-slate-400">Guest</span>
                    @endif
                </td>
                <td>
                    <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">{{ Str::limit($conv->session_id, 20) }}</code>
                </td>
                <td>{{ $conv->messages_count ?? 0 }}</td>
                <td>
                    @if($conv->status === 'active')
                    <span class="badge badge-green"><span class="badge-dot"></span>Đang hoạt động</span>
                    @elseif($conv->status === 'completed')
                    <span class="badge"><span class="badge-dot"></span>Hoàn tất</span>
                    @else
                    <span class="badge badge-red"><span class="badge-dot"></span>Bỏ dở</span>
                    @endif
                </td>
                <td>{{ $conv->updated_at->format('d/m/Y H:i') }}</td>
                <td class="col-actions">
                    <a href="{{ route('admin.bot.conversation', $conv) }}" class="btn btn-table btn-outline">Xem</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="py-6 text-center text-slate-500">Chưa có hội thoại nào.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div class="pagination mt-3">
    {{ $conversations->onEachSide(1)->links('pagination::tailwind') }}
</div>
@endsection

