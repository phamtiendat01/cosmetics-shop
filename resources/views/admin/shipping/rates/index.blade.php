@extends('admin.layouts.app')
@section('title','Biểu phí')

@section('content')
@if(session('ok')) <div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div> @endif
@if($errors->any()) <div class="alert alert-danger mb-3">{{ $errors->first() }}</div> @endif

@include('admin.shipping._nav')

<div class="toolbar mb-2">
    <div class="toolbar-title">Biểu phí</div>
    <div class="toolbar-actions">
        @if (Route::has('admin.shipping.rates.create'))
        <a href="{{ route('admin.shipping.rates.create') }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Thêm biểu phí
        </a>
        @endif
    </div>
</div>

<div class="card p-3 mb-3">
    <form method="get" class="grid md:grid-cols-6 gap-2 items-center">
        {{-- Carrier select nếu controller có truyền $carrierOptions, nếu không fallback text --}}
        @if(isset($carrierOptions) && count($carrierOptions))
        <select name="carrier_id" class="form-control">
            <option value="">Đơn vị</option>
            @foreach($carrierOptions as $id=>$name)
            <option value="{{ $id }}" @selected((string)request('carrier_id')===(string)$id)>{{ $name }}</option>
            @endforeach
        </select>
        @else
        <input name="carrier_id" value="{{ request('carrier_id','') }}" class="form-control" placeholder="Đơn vị (ID)">
        @endif

        {{-- Zone select nếu controller có truyền $zoneOptions, nếu không fallback text --}}
        @if(isset($zoneOptions) && count($zoneOptions))
        <select name="zone_id" class="form-control">
            <option value="">Khu vực</option>
            @foreach($zoneOptions as $id=>$name)
            <option value="{{ $id }}" @selected((string)request('zone_id')===(string)$id)>{{ $name }}</option>
            @endforeach
        </select>
        @else
        <input name="zone_id" value="{{ request('zone_id','') }}" class="form-control" placeholder="Khu vực (ID)">
        @endif

        <input name="keyword" value="{{ request('keyword','') }}" class="form-control md:col-span-2" placeholder="Tên biểu phí…">
        <select name="enabled" class="form-control">
            <option value="">Trạng thái</option>
            <option value="1" @selected(request('enabled')==='1' )>Đang bật</option>
            <option value="0" @selected(request('enabled')==='0' )>Đang tắt</option>
        </select>
        <div class="flex items-center gap-2">
            <button class="btn btn-soft btn-sm"><i class="fa-solid fa-filter"></i> Lọc</button>
            <a href="{{ route('admin.shipping.rates.index') }}" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>

@php
$total = (is_object($rates) && method_exists($rates,'total'))
? $rates->total()
: (is_countable($rates ?? []) ? count($rates) : 0);
@endphp
@if($total>0)
<div class="mb-2 text-sm text-slate-600">Có {{ $total }} biểu phí</div>
@endif

<div class="card table-wrap p-0">
    <table class="table-admin w-full">
        <colgroup>
            <col>
            <col style="width:170px">
            <col style="width:150px">
            <col style="width:150px">
            <col style="width:120px">
            <col style="width:160px">
            <col style="width:180px">
        </colgroup>
        <thead>
            <tr>
                <th>Tên biểu phí</th>
                <th>Đơn vị</th>
                <th>Khu vực</th>
                <th>Khối lượng</th>
                <th>ETD</th>
                <th>Phí</th>
                <th class="text-right">Trạng thái / Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rates as $r)
            <tr>
                <td class="font-medium">
                    {{ $r->name }}
                    @if($r->min_total || $r->max_total)
                    <div class="text-xs text-emerald-600">
                        Áp dụng theo giá trị đơn:
                        @if($r->min_total) từ {{ number_format($r->min_total,0) }}₫ @endif
                        @if($r->max_total) đến {{ number_format($r->max_total,0) }}₫ @endif
                    </div>
                    @endif
                </td>
                <td>{{ $r->carrier->name ?? ('#'.$r->carrier_id) }}</td>
                <td>{{ $r->zone->name ?? ('#'.$r->zone_id) }}</td>
                <td class="text-sm">
                    @if($r->min_weight && $r->max_weight)
                    {{ $r->min_weight }}–{{ $r->max_weight }} kg
                    @elseif($r->min_weight && !$r->max_weight)
                    ≥ {{ $r->min_weight }} kg
                    @elseif(!$r->min_weight && $r->max_weight)
                    ≤ {{ $r->max_weight }} kg
                    @else
                    —
                    @endif
                </td>
                <td class="text-sm">
                    @if($r->etd_min_days || $r->etd_max_days)
                    {{ $r->etd_min_days }}–{{ $r->etd_max_days }} ngày
                    @else — @endif
                </td>
                <td class="text-sm">
                    <div>1kg đầu: <b>{{ number_format($r->base_fee,0) }}₫</b></div>
                    <div class="text-xs text-slate-500">+ {{ number_format($r->per_kg_fee,0) }}₫ / kg thêm</div>
                </td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-2">
                        <span class="badge {{ $r->enabled ? 'badge-green' : 'badge-amber' }}">{{ $r->enabled ? 'Đang bật' : 'Đang tắt' }}</span>

                        <form class="inline" method="post" action="{{ route('admin.shipping.rates.toggle', $r) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="enabled" value="{{ $r->enabled ? 0 : 1 }}">
                            <button class="btn btn-soft btn-sm" title="Bật/Tắt">
                                <i class="fa-solid fa-toggle-{{ $r->enabled ? 'on' : 'off' }}"></i>
                            </button>
                        </form>


                        @if (Route::has('admin.shipping.rates.edit'))
                        <a href="{{ route('admin.shipping.rates.edit',$r) }}" class="btn btn-outline btn-sm">Sửa</a>
                        @endif

                        <form class="inline" method="post" action="{{ route('admin.shipping.rates.destroy',$r) }}"
                            onsubmit="return confirm('Xoá biểu phí này?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Xoá</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="py-6 text-center text-slate-500">Chưa có biểu phí.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(method_exists($rates,'links'))
<div class="pagination mt-3">{{ $rates->onEachSide(1)->links('pagination::tailwind') }}</div>
@endif
@endsection