@extends('admin.layouts.app')
@section('title','Mã vận chuyển')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif

{{-- Toolbar --}}
<div class="toolbar">
    <div class="toolbar-title">Quản lý mã vận chuyển</div>
    <div class="toolbar-actions">
        <a href="{{ route('admin.shipvouchers.create') }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Tạo mã
        </a>
    </div>
</div>

@php
$qs = request()->except('page','status');
$cur = request('status');
@endphp

{{-- Tabs lọc nhanh --}}
<div class="card p-2 mb-3">
    <div class="flex flex-wrap gap-2">

        @php $active = ($cur===null || $cur===''); $count = (int)($counts['all'] ?? 0); @endphp
        <a href="{{ route('admin.shipvouchers.index', $qs) }}"
            class="btn btn-ghost btn-sm {{ $active ? 'bg-rose-600 text-white hover:bg-rose-600' : '' }}">
            Tất cả
            <span class="ml-1 text-xs rounded-full px-1.5 {{ $active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">
                {{ $count }}
            </span>
        </a>

        @php $active = ($cur==='running'); $count = (int)($counts['running'] ?? 0); @endphp
        <a href="{{ route('admin.shipvouchers.index', array_merge($qs,['status'=>'running'])) }}"
            class="btn btn-ghost btn-sm {{ $active ? 'bg-rose-600 text-white hover:bg-rose-600' : '' }}">
            Đang diễn ra
            <span class="ml-1 text-xs rounded-full px-1.5 {{ $active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">
                {{ $count }}
            </span>
        </a>

        @php $active = ($cur==='expired'); $count = (int)($counts['expired'] ?? 0); @endphp
        <a href="{{ route('admin.shipvouchers.index', array_merge($qs,['status'=>'expired'])) }}"
            class="btn btn-ghost btn-sm {{ $active ? 'bg-rose-600 text-white hover:bg-rose-600' : '' }}">
            Hết hạn
            <span class="ml-1 text-xs rounded-full px-1.5 {{ $active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">
                {{ $count }}
            </span>
        </a>

        @php $active = ($cur==='active'); $count = (int)($counts['active'] ?? 0); @endphp
        <a href="{{ route('admin.shipvouchers.index', array_merge($qs,['status'=>'active'])) }}"
            class="btn btn-ghost btn-sm {{ $active ? 'bg-rose-600 text-white hover:bg-rose-600' : '' }}">
            Đang bật
            <span class="ml-1 text-xs rounded-full px-1.5 {{ $active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">
                {{ $count }}
            </span>
        </a>

        @php $active = ($cur==='inactive'); $count = (int)($counts['inactive'] ?? 0); @endphp
        <a href="{{ route('admin.shipvouchers.index', array_merge($qs,['status'=>'inactive'])) }}"
            class="btn btn-ghost btn-sm {{ $active ? 'bg-rose-600 text-white hover:bg-rose-600' : '' }}">
            Đang tắt
            <span class="ml-1 text-xs rounded-full px-1.5 {{ $active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">
                {{ $count }}
            </span>
        </a>
    </div>
</div>

{{-- Bộ lọc chi tiết --}}
<div class="card p-3 mb-3">
    <form method="get" class="grid md:grid-cols-5 gap-2 items-center">
        <div class="md:col-span-2">
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Tìm theo mã / tên…">
        </div>

        <select class="form-control" name="status">
            <option value="">Tất cả trạng thái</option>
            <option value="active" @selected(request('status')==='active' )>Đang bật</option>
            <option value="inactive" @selected(request('status')==='inactive' )>Đang tắt</option>
            <option value="running" @selected(request('status')==='running' )>Đang diễn ra</option>
            <option value="expired" @selected(request('status')==='expired' )>Hết hạn</option>
        </select>

        <select class="form-control" disabled>
            <option selected>Loại mã: Mã vận chuyển</option>
        </select>

        <div class="flex items-center gap-2">
            <button class="btn btn-soft btn-sm"><i class="fa-solid fa-filter"></i> Lọc</button>
            <a href="{{ route('admin.shipvouchers.index') }}" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>

{{-- Bảng --}}
<div class="card table-wrap p-0">
    <table class="table-admin">
        <thead>
            <tr>
                <th style="width:56px">#</th>
                <th style="width:22%">Mã / Tên</th>
                <th>Giảm</th>
                <th>Giới hạn</th>
                <th>Áp dụng</th>
                <th>Thời gian</th>
                <th>Trạng thái</th>
                <th class="col-actions">Thao tác</th>
            </tr>
        </thead>

        <tbody>
            @forelse($items as $i => $v)
            @php
            $now = now();

            if ($v->isRunning()) {
            $timeBadge = 'Đang diễn ra';
            $timeCls = 'badge-green';
            } elseif ($v->end_at && $v->end_at < $now) {
                $timeBadge='Hết hạn' ;
                $timeCls='badge-red' ;
                } else {
                $timeBadge='Chưa bắt đầu' ;
                $timeCls='badge-amber' ;
                }

                if ($v->discount_type === 'percent') {
                $giam = rtrim(rtrim(number_format($v->amount, 2, '.', ''), '0'), '.') . '%';
                if ($v->max_discount) {
                $giam .= ' (tối đa ' . number_format($v->max_discount) . '₫)';
                }
                } else {
                $giam = number_format($v->amount) . '₫';
                }

                $startTxt = $v->start_at ? $v->start_at->format('d/m/Y H:i') : '—';
                $endTxt = $v->end_at ? $v->end_at->format('d/m/Y H:i') : '—';
                @endphp

                <tr>
                    <td>{{ ($items->currentPage()-1)*$items->perPage() + $i + 1 }}</td>

                    <td>
                        <div class="font-semibold">{{ $v->code }}</div>
                        <div class="text-xs text-slate-500 line-clamp-1">{{ $v->title }}</div>
                    </td>

                    <td>{{ $giam }}</td>

                    <td class="text-xs">
                        Dùng:
                        <span class="badge">
                            {{ (int)($usageCounts[$v->id] ?? 0) }} / {{ $v->usage_limit ?? '∞' }}
                        </span><br>
                        Mỗi user: {{ $v->per_user_limit ?? '∞' }}
                    </td>

                    <td class="text-xs">
                        @if($v->min_order) Đơn từ {{ number_format($v->min_order) }}₫<br>@endif
                        @if($v->carriers) Hãng: {{ implode(', ', (array)$v->carriers) }} @endif
                    </td>

                    <td class="text-xs">{{ $startTxt }} → {{ $endTxt }}</td>

                    {{-- Cột Trạng thái: bật/tắt + tiến trình thời gian --}}
                    <td>
                        <div class="flex items-center gap-1 flex-wrap">
                            <span class="badge {{ $v->is_active ? 'badge-green' : 'badge-red' }}">
                                {{ $v->is_active ? 'Bật' : 'Tắt' }}
                            </span>
                            <span class="badge {{ $timeCls }}">{{ $timeBadge }}</span>
                        </div>
                    </td>

                    {{-- Cột thao tác: Sửa / Công tắc bật-tắt / Xoá --}}
                    <td class="col-actions">
                        <a class="btn btn-table btn-outline" href="{{ route('admin.shipvouchers.edit',$v) }}">Sửa</a>

                        <form action="{{ route('admin.shipvouchers.toggle',$v) }}" method="post" class="inline">
                            @csrf @method('PATCH')
                            <label class="sv-switch" title="Bật/Tắt mã">
                                <input type="checkbox" {{ $v->is_active ? 'checked' : '' }} onchange="this.form.submit()">
                                <span class="sv-slider"></span>
                            </label>
                        </form>

                        <form class="inline" action="{{ route('admin.shipvouchers.destroy',$v) }}"
                            method="post" onsubmit="return confirm('Xoá mã {{ $v->code }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-table btn-danger">Xoá</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-6 text-center text-slate-500">Chưa có mã.</td>
                </tr>
                @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination + summary --}}
<div class="flex items-center justify-between mt-2">
    <div class="text-sm text-slate-600">
        @if($items->total() > 0)
        Hiển thị {{ ($items->currentPage()-1)*$items->perPage()+1 }}
        – {{ ($items->currentPage()-1)*$items->perPage()+$items->count() }}
        / {{ $items->total() }} mã
        @endif
    </div>
    <div class="pagination">{{ $items->onEachSide(1)->links() }}</div>
</div>

{{-- Styles: công tắc bật/tắt --}}
<style>
    .sv-switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 22px;
        vertical-align: middle
    }

    .sv-switch input {
        display: none
    }

    .sv-slider {
        position: absolute;
        inset: 0;
        background: #e5e7eb;
        border-radius: 999px;
        transition: .2s
    }

    .sv-slider:before {
        content: "";
        position: absolute;
        width: 18px;
        height: 18px;
        left: 2px;
        top: 2px;
        background: #fff;
        border-radius: 999px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .2);
        transition: .2s
    }

    .sv-switch input:checked+.sv-slider {
        background: #10b981
    }

    .sv-switch input:checked+.sv-slider:before {
        transform: translateX(18px)
    }
</style>

{{-- Scripts: auto-dismiss alert --}}
<script>
    document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
        const t = +el.getAttribute('data-auto-dismiss') || 3000;
        setTimeout(() => {
            el.classList.add('alert--hide');
            setTimeout(() => el.remove(), 350);
        }, t);
    });
</script>
@endsection