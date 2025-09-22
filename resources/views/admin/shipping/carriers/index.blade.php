@extends('admin.layouts.app')
@section('title','Đơn vị vận chuyển')

@section('content')
@if(session('ok')) <div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div> @endif
@if($errors->any()) <div class="alert alert-danger mb-3">{{ $errors->first() }}</div> @endif

{{-- Mini tabs (giữ nguyên logic của anh) --}}
@include('admin.shipping._nav')

<div class="toolbar mb-2">
    <div class="toolbar-title">Đơn vị vận chuyển</div>
    <div class="toolbar-actions">
        @if (Route::has('admin.shipping.carriers.create'))
        <a href="{{ route('admin.shipping.carriers.create') }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Thêm đơn vị
        </a>
        @endif
    </div>
</div>

<div class="card p-3 mb-3">
    <form method="get" class="grid md:grid-cols-5 gap-2 items-center">
        <input name="keyword" value="{{ request('keyword','') }}" class="form-control" placeholder="Tên / mã…">
        <select name="enabled" class="form-control">
            <option value="">Trạng thái</option>
            <option value="1" @selected(request('enabled')==='1' )>Đang bật</option>
            <option value="0" @selected(request('enabled')==='0' )>Đang tắt</option>
        </select>
        <div class="md:col-span-3 flex items-center gap-2">
            <button class="btn btn-soft btn-sm"><i class="fa-solid fa-filter"></i> Lọc</button>
            <a href="{{ route('admin.shipping.carriers.index') }}" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>

@php $total = method_exists($carriers,'total') ? $carriers->total() : (is_countable($carriers ?? []) ? count($carriers) : 0); @endphp

@if($total>0)
<div class="mb-2 text-sm text-slate-600">Có {{ $total }} đơn vị</div>
@endif

<div class="card table-wrap p-0">
    <table class="table-admin w-full">
        <colgroup>
            <col style="width:70px">
            <col>
            <col style="width:140px">
            <col style="width:120px">
            <col style="width:120px">
            <col style="width:180px">
        </colgroup>
        <thead>
            <tr>
                <th></th>
                <th>Tên đơn vị</th>
                <th>Mã</th>
                <th>COD</th>
                <th>Trạng thái</th>
                <th class="text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($carriers as $c)
            <tr>
                <td>
                    @if($c->logo_url)
                    <img class="w-8 h-8 rounded object-cover" src="{{ $c->logo_url }}" alt="">
                    @else
                    <span class="inline-flex w-8 h-8 items-center justify-center rounded bg-slate-100 text-slate-500">
                        <i class="fa-solid fa-truck"></i>
                    </span>
                    @endif

                </td>
                <td class="font-medium">{{ $c->name }}</td>
                <td><code class="text-xs">{{ $c->code }}</code></td>
                <td>{!! $c->supports_cod ? '<span class="badge badge-green">Có</span>' : '<span class="badge badge-amber">Không</span>' !!}</td>
                <td>{!! $c->enabled ? '<span class="badge badge-green">Đang bật</span>' : '<span class="badge badge-amber">Đang tắt</span>' !!}</td>
                <td class="text-right">
                    <div class="actions">
                        <form class="inline" method="post" action="{{ route('admin.shipping.carriers.toggle', $c) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="enabled" value="{{ $c->enabled ? 0 : 1 }}">
                            <button class="btn btn-soft btn-sm" title="Bật/Tắt">
                                <i class="fa-solid fa-toggle-{{ $c->enabled ? 'on' : 'off' }}"></i>
                            </button>
                        </form>
                        @if (Route::has('admin.shipping.carriers.edit'))
                        <a href="{{ route('admin.shipping.carriers.edit',$c) }}" class="btn btn-outline btn-sm">Sửa</a>
                        @endif

                        <form class="inline" method="post" action="{{ route('admin.shipping.carriers.destroy',$c) }}"
                            onsubmit="return confirm('Xoá đơn vị này?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Xoá</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="py-6 text-center text-slate-500">Chưa có đơn vị.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(method_exists($carriers,'links'))
<div class="pagination mt-3">{{ $carriers->onEachSide(1)->links('pagination::tailwind') }}</div>
@endif
@endsection