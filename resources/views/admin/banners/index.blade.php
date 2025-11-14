@extends('admin.layouts.app')
@section('title','Banner')

@section('content')
@if(session('ok')) <div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div> @endif

<div class="toolbar">
    <div class="toolbar-title">Quản lý banner</div>
    <a href="{{ route('admin.banners.create') }}" class="btn btn-primary btn-sm">+ Thêm banner</a>
</div>

<div class="card p-3 mb-3">
    <form method="get" class="grid md:grid-cols-5 gap-2 items-center">
        <input class="form-control" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Tìm theo tiêu đề…">

        <select class="form-control" name="position">
            <option value="">Tất cả vị trí</option>
            @foreach($positions as $k => $v)
            <option value="{{ $k }}" @selected(($filters['position'] ?? '' )===$k)>{{ $v }}</option>
            @endforeach
        </select>

        <select class="form-control" name="device">
            <option value="">Tất cả thiết bị</option>
            @foreach($devices as $k => $v)
            <option value="{{ $k }}" @selected(($filters['device'] ?? '' )===$k)>{{ $v }}</option>
            @endforeach
        </select>

        <select class="form-control" name="status">
            <option value="">Tất cả trạng thái</option>
            <option value="active" @selected(($filters['status'] ?? '' )==='active' )>Đang bật</option>
            <option value="inactive" @selected(($filters['status'] ?? '' )==='inactive' )>Đang tắt</option>
        </select>

        <div class="flex items-center gap-2">
            <button class="btn btn-soft btn-sm">Lọc</button>
            <a href="{{ route('admin.banners.index') }}" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>

<div class="card table-wrap p-0">
    <table class="table-admin">
        <thead>
            <tr>
                <th>#</th>
                <th>Banner</th>
                <th>Vị trí / Thiết bị</th>
                <th>Hiệu lực</th>
                <th>Sắp xếp</th>
                <th>Trạng thái</th>
                <th class="col-actions">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($banners as $i => $b)
            <tr>
                <td>{{ ($banners->currentPage()-1)*$banners->perPage() + $i + 1 }}</td>
                <td class="col-name">
                    <div class="cell-thumb">
                        <img class="thumb"
                            src="{{ $b->image ? url($b->image) : 'https://placehold.co/80x80' }}" alt="">
                        <div>
                            <div class="font-medium">{{ $b->title }}</div>
                            @if($b->url)
                            <a href="{{ $b->url }}" target="_blank" class="text-xs link">Xem đích</a>
                            @endif
                        </div>
                    </div>
                </td>
                <td>
                    <div>{{ \App\Models\Banner::POSITIONS[$b->position] ?? $b->position }}</div>
                    <div class="text-xs text-slate-500">{{ \App\Models\Banner::DEVICES[$b->device] ?? $b->device }}</div>
                </td>
                <td class="text-sm">
                    {{ $b->starts_at?->format('d/m/Y H:i') ?? '—' }} → {{ $b->ends_at?->format('d/m/Y H:i') ?? '—' }}
                    <div class="text-xs {{ $b->is_running_now ? 'text-emerald-600':'text-rose-600' }}">
                        {{ $b->is_running_now ? 'Đang hiển thị' : 'Không trong hiệu lực' }}
                    </div>
                </td>
                <td>{{ $b->sort_order }}</td>
                <td>
                    <form method="post" action="{{ route('admin.banners.toggle',$b) }}">
                        @csrf @method('PATCH')
                        <button class="badge {{ $b->is_active ? 'badge-green' : 'badge-red' }}" title="Bật/Tắt nhanh">
                            {{ $b->is_active ? 'Bật' : 'Tắt' }}
                        </button>
                    </form>
                </td>
                <td class="col-actions">
                    <a class="btn btn-table btn-outline" href="{{ route('admin.banners.edit',$b) }}">Sửa</a>
                    <form method="post" action="{{ route('admin.banners.destroy',$b) }}" class="inline" onsubmit="return confirm('Xoá banner này?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-table btn-danger">Xoá</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="py-6 text-center text-slate-500">Chưa có banner.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="flex items-center justify-between mt-2">
    <div class="text-sm text-slate-600">
        @if($banners->total()>0)
        Hiển thị {{ ($banners->currentPage()-1)*$banners->perPage()+1 }} – {{ ($banners->currentPage()-1)*$banners->perPage()+$banners->count() }} / {{ $banners->total() }} banner
        @endif
    </div>
    <div class="pagination">{{ $banners->onEachSide(1)->links() }}</div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
        setTimeout(() => {
            el.classList.add('alert--hide');
            setTimeout(() => el.remove(), 350)
        }, +el.dataset.autoDismiss || 3000)
    });
</script>
@endpush
@endsection