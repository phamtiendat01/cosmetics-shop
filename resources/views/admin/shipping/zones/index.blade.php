@extends('admin.layouts.app')
@section('title','Khu vực / Tuyến')

@section('content')
@if(session('ok')) <div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div> @endif
@if($errors->any()) <div class="alert alert-danger mb-3">{{ $errors->first() }}</div> @endif

@include('admin.shipping._nav')

<div class="toolbar mb-2">
    <div class="toolbar-title">Khu vực / Tuyến</div>
    <div class="toolbar-actions">
        @if (Route::has('admin.shipping.zones.create'))
        <a href="{{ route('admin.shipping.zones.create') }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Thêm khu vực
        </a>
        @endif
    </div>
</div>

<div class="card p-3 mb-3">
    <form method="get" class="grid md:grid-cols-5 gap-2 items-center">
        <input name="keyword" value="{{ request('keyword','') }}" class="form-control" placeholder="Tên khu vực…">
        <select name="enabled" class="form-control">
            <option value="">Trạng thái</option>
            <option value="1" @selected(request('enabled')==='1' )>Đang bật</option>
            <option value="0" @selected(request('enabled')==='0' )>Đang tắt</option>
        </select>
        <div class="md:col-span-3 flex items-center gap-2">
            <button class="btn btn-soft btn-sm"><i class="fa-solid fa-filter"></i> Lọc</button>
            <a href="{{ route('admin.shipping.zones.index') }}" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>

@php
$total = (is_object($zones) && method_exists($zones,'total'))
? $zones->total()
: (is_countable($zones ?? []) ? count($zones) : 0);
@endphp
@if($total>0)
<div class="mb-2 text-sm text-slate-600">Có {{ $total }} khu vực</div>
@endif

<div class="card table-wrap p-0">
    <table class="table-admin w-full">
        <colgroup>
            <col>
            <col style="width:120px">
            <col style="width:160px">
            <col style="width:200px">
        </colgroup>
        <thead>
            <tr>
                <th>Tên khu vực</th>
                <th>Số tỉnh</th>
                <th>Trạng thái</th>
                <th class="text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($zones as $z)
            @php
            $codes = is_array($z->province_codes) ? $z->province_codes : (json_decode($z->province_codes,true) ?: []);
            @endphp
            <tr>
                <td>
                    <div class="font-medium">{{ $z->name }}</div>
                    <div class="text-xs text-slate-500">Mã tỉnh: {{ $codes ? implode(', ',$codes) : '—' }}</div>
                </td>
                <td>{{ count($codes) }}</td>
                <td>{!! $z->enabled ? '<span class="badge badge-green">Đang bật</span>' : '<span class="badge badge-amber">Đang tắt</span>' !!}</td>
                <td class="text-right">
                    <div class="actions">

                        <form class="inline" method="post" action="{{ route('admin.shipping.zones.toggle', $z) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="enabled" value="{{ $z->enabled ? 0 : 1 }}">
                            <button class="btn btn-soft btn-sm" title="Bật/Tắt">
                                <i class="fa-solid fa-toggle-{{ $z->enabled ? 'on' : 'off' }}"></i>
                            </button>
                        </form>

                        @if (Route::has('admin.shipping.zones.edit'))
                        <a href="{{ route('admin.shipping.zones.edit',$z) }}" class="btn btn-outline btn-sm">Sửa</a>
                        @endif

                        <form class="inline" method="post" action="{{ route('admin.shipping.zones.destroy',$z) }}"
                            onsubmit="return confirm('Xoá khu vực này?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Xoá</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="py-6 text-center text-slate-500">Chưa có khu vực.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(method_exists($zones,'links'))
<div class="pagination mt-3">{{ $zones->onEachSide(1)->links('pagination::tailwind') }}</div>
@endif
@endsection