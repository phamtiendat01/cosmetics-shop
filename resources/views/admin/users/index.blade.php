@extends('admin.layouts.app')
@section('title','Quản trị viên')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif
@if(session('err'))
<div class="alert alert-danger mb-3" data-auto-dismiss="4000">{{ session('err') }}</div>
@endif

{{-- Toolbar --}}
<div class="toolbar">
    <div class="toolbar-title">Quản lý quản trị viên</div>
    <div class="toolbar-actions">
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-user-plus"></i> Thêm quản trị viên
        </a>
    </div>
</div>

{{-- Tabs lọc nhanh --}}
@php $qs = request()->except('page','status'); $cur = request('status'); @endphp
<div class="card p-2 mb-3">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.users.index', $qs) }}"
            class="btn btn-ghost btn-sm {{ $cur===null || $cur==='' ? 'ring-1 ring-rose-200' : '' }}">Tất cả</a>
        <a href="{{ route('admin.users.index', array_merge($qs,['status'=>'active'])) }}"
            class="btn btn-ghost btn-sm {{ $cur==='active' ? 'ring-1 ring-rose-200' : '' }}">Hoạt động</a>
        <a href="{{ route('admin.users.index', array_merge($qs,['status'=>'inactive'])) }}"
            class="btn btn-ghost btn-sm {{ $cur==='inactive' ? 'ring-1 ring-rose-200' : '' }}">Khoá</a>
    </div>
</div>

{{-- Bộ lọc chi tiết --}}
<div class="card p-3 mb-3">
    <form method="get" class="grid md:grid-cols-5 gap-2 items-center">
        <div class="md:col-span-2">
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Tìm theo tên / email…">
        </div>

        <select class="form-control" name="role">
            <option value="">Tất cả vai trò</option>
            @foreach($roles as $r)
            <option value="{{ $r->name }}" @selected(request('role')===$r->name)>{{ $r->name }}</option>
            @endforeach
        </select>

        <select class="form-control" name="status">
            <option value="">Tất cả trạng thái</option>
            <option value="active" @selected(request('status')==='active' )>Hoạt động</option>
            <option value="inactive" @selected(request('status')==='inactive' )>Khoá</option>
        </select>

        <div class="flex items-center gap-2">
            <button class="btn btn-soft btn-sm"><i class="fa-solid fa-filter"></i> Lọc</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>

{{-- Bảng --}}
<div class="card table-wrap p-0">
    <table class="table-admin">
        <thead>
            <tr>
                <th style="width:56px">#</th>
                <th style="width:24%">Người dùng</th>
                <th>Email</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th class="col-actions">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $i => $u)
            <tr>
                <td>{{ ($users->currentPage()-1)*$users->perPage() + $i + 1 }}</td>
                <td>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-700 grid place-content-center font-semibold">
                            {{ strtoupper(Str::substr($u->name,0,1)) }}
                        </div>
                        <div>
                            <div class="font-semibold">{{ $u->name }}</div>
                            <div class="text-xs text-slate-500">ID: {{ $u->id }}</div>
                        </div>
                    </div>
                </td>
                <td>{{ $u->email }}</td>
                <td>
                    @foreach($u->roles as $r)
                    <span class="badge">{{ $r->name }}</span>
                    @endforeach
                </td>
                <td>
                    @if($u->is_active)
                    <span class="badge badge-green">Hoạt động</span>
                    @else
                    <span class="badge badge-red">Khoá</span>
                    @endif
                </td>
                <td class="col-actions">
                    <a class="btn btn-table btn-outline" href="{{ route('admin.users.edit',$u) }}">Sửa</a>
                    <form action="{{ route('admin.users.destroy',$u) }}" method="POST" class="inline"
                        onsubmit="return confirm('Xoá quản trị viên này?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-table btn-danger"
                            @disabled(($u->hasRole('super-admin') && \App\Models\User::role('super-admin')->count() <= 1) || auth()->id()===$u->id)>
                                Xoá
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="py-6 text-center text-slate-500">Chưa có quản trị viên.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination + summary --}}
<div class="flex items-center justify-between mt-2">
    <div class="text-sm text-slate-600">
        @if($users->total()>0)
        Hiển thị {{ ($users->currentPage()-1)*$users->perPage()+1 }}
        – {{ ($users->currentPage()-1)*$users->perPage()+$users->count() }}
        / {{ $users->total() }} tài khoản
        @endif
    </div>
    <div class="pagination">
        {{ $users->onEachSide(1)->links() }}
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
        const t = +el.getAttribute('data-auto-dismiss') || 3000;
        setTimeout(() => {
            el.classList.add('alert--hide');
            setTimeout(() => el.remove(), 350)
        }, t);
    });
</script>
@endpush
@endsection