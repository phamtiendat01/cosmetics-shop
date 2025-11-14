@extends('admin.layouts.app')
@section('title','Đơn hàng')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

<div class="toolbar">
    <div class="toolbar-title">Quản lý đơn hàng</div>
    <div class="toolbar-actions">
        <a href="{{ request()->fullUrlWithQuery(['export'=>'csv']) }}" class="btn btn-outline btn-sm">Xuất CSV</a>
    </div>
</div>

{{-- Tabs trạng thái (giống sàn) --}}
<div class="mb-3 flex flex-wrap gap-2 text-sm">
    @php $st = $filters['status'] ?? ''; @endphp
    @php $tab = function($key,$text,$cnt) use($st){ $active = $st===$key ? 'btn-primary' : 'btn-outline'; $url = $key? request()->fullUrlWithQuery(['status'=>$key,'page'=>1]) : route('admin.orders.index'); return "<a class=\"btn btn-sm $active\" href=\"$url\">$text ($cnt)</a>"; }; @endphp
    {!! $tab('', 'Tất cả', $counts['all']) !!}
    {!! $tab('pending','Chờ xác nhận',$counts['pending']) !!}
    {!! $tab('confirmed','Đã xác nhận',$counts['confirmed']) !!}
    {!! $tab('processing','Đang xử lý',$counts['processing']) !!}
    {!! $tab('shipping','Đang giao',$counts['shipping']) !!}
    {!! $tab('completed','Hoàn tất',$counts['completed']) !!}
    {!! $tab('cancelled','Đã huỷ',$counts['cancelled']) !!}
    {!! $tab('refunded','Đã hoàn tiền',$counts['refunded']) !!}
</div>

{{-- Bộ lọc + sort --}}
<div class="card p-3 mb-3">
    <form method="get" class="grid md:grid-cols-5 gap-2 items-center">
        <input name="keyword" value="{{ $filters['keyword'] ?? '' }}" class="form-control" placeholder="Mã đơn / Tên / SĐT / Email">
        <select name="status" class="form-control" id="statusSelect">
            <option value="">Trạng thái</option>
            @foreach($statusOptions as $k=>$v)
            <option value="{{ $k }}" @selected(($filters['status']??'')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <select name="payment_status" class="form-control" id="paySelect">
            <option value="">Thanh toán</option>
            @foreach($payOptions as $k=>$v)
            <option value="{{ $k }}" @selected(($filters['payment_status']??'')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <select name="sort" class="form-control" id="sortSelect">
            <option value="newest" @selected(($filters['sort']??'newest')==='newest' )>Mới nhất</option>
            <option value="total_desc" @selected(($filters['sort']??'')==='total_desc' )>Tổng cao → thấp</option>
            <option value="total_asc" @selected(($filters['sort']??'')==='total_asc' )>Tổng thấp → cao</option>
        </select>
        <div class="flex items-center gap-2">
            <button class="btn btn-soft btn-sm">Lọc</button>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>

{{-- Bulk actions bar (ẩn/hiện khi chọn) --}}
<form id="bulkForm" method="post" action="{{ route('admin.orders.bulk') }}" class="hidden card p-3 mb-3">
    @csrf @method('PATCH')
    <input type="hidden" name="ids[]" id="bulkIds">
    <div class="flex items-center gap-2">
        <span class="text-sm text-slate-600" id="bulkCount">0 đã chọn</span>
        <select name="action" class="form-control" style="max-width:200px">
            <option value="set_status">Đặt trạng thái</option>
            <option value="set_payment">Đặt thanh toán</option>
        </select>
        <select name="status" class="form-control" style="max-width:220px">
            <option value="">— Trạng thái —</option>
            @foreach($statusOptions as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
        </select>
        <select name="payment_status" class="form-control" style="max-width:220px">
            <option value="">— Thanh toán —</option>
            @foreach($payOptions as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
        </select>
        <button class="btn btn-primary btn-sm">Áp dụng</button>
    </div>
</form>

@php
$from = $orders->total() ? (($orders->currentPage()-1) * $orders->perPage() + 1) : 0;
$to = $orders->total() ? ($from + $orders->count() - 1) : 0;
@endphp
@if($orders->total() > 0)
<div class="mb-2 text-sm text-slate-600">Hiển thị {{ $from }}–{{ $to }} / {{ $orders->total() }} đơn</div>
@endif

<div class="card table-wrap p-0">
    <table class="table-admin w-full" id="orderTable">
        <colgroup>
            <col style="width:36px">
            <col style="width:130px">
            <col>
            <col style="width:170px">
            <col style="width:150px">
            <col style="width:150px">
            <col style="width:160px">
        </colgroup>
        <thead>
            <tr>
                <th><input type="checkbox" id="checkAll"></th>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>Thanh toán</th>
                <th>Trạng thái</th>
                <th>Tổng</th>
                <th>Đặt lúc</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $o)
            <tr>
                <td><input type="checkbox" class="rowCheck" value="{{ $o->id }}"></td>
                <td>
                    {{-- ĐỔI DÒNG NÀY --}}
                    <a class="link font-semibold" href="{{ route('admin.orders.show', ['admin_order' => $o->id]) }}">#{{ $o->code }}</a>
                    <div class="text-xs text-slate-500">{{ $o->items_count }} SP</div>
                </td>
                <td class="whitespace-nowrap">
                    <div class="font-medium">{{ $o->customer_name }}</div>
                    <div class="text-xs text-slate-500">
                        {{ $o->customer_phone }}{{ $o->customer_email ? ' · '.$o->customer_email : '' }}
                    </div>

                    @if(($o->pending_returns_count ?? 0) > 0)
                    <div class="mt-1">
                        <span class="inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50
                               text-rose-700 text-[11px] font-medium px-2 py-0.5">
                            <i class="fa-solid fa-rotate-left"></i>
                            Trả hàng: {{ $o->pending_returns_count }}
                        </span>
                    </div>
                    @endif
                </td>
                <td><span class="badge {{ $o->payment_status_badge }}">{{ $o->payment_status_label }}</span>
                    <div class="text-xs text-slate-500 mt-1">{{ $o->payment_method }}</div>
                </td>
                <td><span class="badge {{ $o->status_badge }}">{{ $o->status_label }}</span></td>
                <td class="font-semibold">{{ number_format($o->grand_total,0) }}₫</td>
                <td>{{ optional($o->placed_at)->format('d/m/Y H:i') ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="py-6 text-center text-slate-500">Chưa có đơn hàng.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination mt-3">
    {{ $orders->onEachSide(1)->links('pagination::tailwind') }}
</div>

@push('scripts')
<script>
    if (document.getElementById('statusSelect')) new TomSelect('#statusSelect', {
        create: false
    });
    if (document.getElementById('paySelect')) new TomSelect('#paySelect', {
        create: false
    });
    if (document.getElementById('sortSelect')) new TomSelect('#sortSelect', {
        create: false
    });

    // Bulk
    const bulkBar = document.getElementById('bulkForm');
    const bulkIds = document.getElementById('bulkIds');
    const bulkCount = document.getElementById('bulkCount');
    const rowChecks = Array.from(document.querySelectorAll('.rowCheck'));
    const checkAll = document.getElementById('checkAll');

    function refreshBulk() {
        const ids = rowChecks.filter(c => c.checked).map(c => c.value);
        bulkIds.value = '';
        document.querySelectorAll('#bulkForm input[name="ids[]"]').forEach(n => n.remove());
        ids.forEach(id => {
            const i = document.createElement('input');
            i.type = 'hidden';
            i.name = 'ids[]';
            i.value = id;
            bulkBar.appendChild(i);
        });
        bulkCount.textContent = ids.length + ' đã chọn';
        bulkBar.classList.toggle('hidden', ids.length === 0);
    }
    rowChecks.forEach(c => c.addEventListener('change', refreshBulk));
    checkAll?.addEventListener('change', () => {
        rowChecks.forEach(c => c.checked = checkAll.checked);
        refreshBulk();
    });
</script>
@endpush
@endsection