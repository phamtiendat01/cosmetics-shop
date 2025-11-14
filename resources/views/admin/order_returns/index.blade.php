@extends('admin.layouts.app')
@section('title','Đổi trả / Hoàn tiền')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

<div class="toolbar">
    <div class="toolbar-title">Đổi trả / Hoàn tiền</div>
</div>

{{-- BỘ LỌC --}}
<form method="get" class="card p-3 mb-3">
    <div class="flex items-end flex-wrap gap-3">
        <div>
            <label class="label">Order ID</label>
            <input type="text" name="order" value="{{ request('order') }}" class="form-control w-40" placeholder="VD: 211">
        </div>
        <div>
            <label class="label">Mã đơn</label>
            <input type="text" name="code" value="{{ request('code') }}" class="form-control w-56" placeholder="VD: CH-250909-XXXX">
        </div>
        <div class="ml-auto flex items-center gap-2">
            <button class="btn btn-outline btn-sm">Lọc</button>
            @if(request()->hasAny(['order','code']))
            <a href="{{ route('admin.order_returns.index') }}" class="btn btn-link btn-sm">Bỏ lọc</a>
            @endif
        </div>
    </div>
    @if(request()->hasAny(['order','code']))
    <div class="mt-2 text-xs text-slate-600">
        Đang lọc:
        @if(request('order')) <span class="inline-flex items-center rounded-full border px-2 py-0.5 mr-1">Order ID: {{ request('order') }}</span> @endif
        @if(request('code')) <span class="inline-flex items-center rounded-full border px-2 py-0.5">Mã đơn: {{ request('code') }}</span> @endif
    </div>
    @endif
</form>

<div class="card p-0 overflow-auto">
    <table class="table-admin text-sm">
        <thead>
            <tr>
                <th>#</th>
                <th>Đơn hàng</th>
                <th>Khách</th>
                <th>Trạng thái</th>
                <th class="text-right">Tạm tính</th>
                <th class="text-right">Chốt hoàn</th>
                <th>Thời gian</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($returns as $r)
            <tr class="hover:bg-slate-50">
                <td>{{ $r->id }}</td>
                <td>
                    @if($r->order)
                    <div class="font-medium">#{{ $r->order->code }}</div>
                    <div class="text-xs text-slate-500">ID: {{ $r->order_id }}</div>
                    @else
                    <span class="text-slate-400">—</span>
                    @endif
                </td>
                <td class="whitespace-nowrap">
                    {{ $r->order->customer_name ?? '—' }}<br>
                    <span class="text-xs text-slate-500">{{ $r->order->customer_phone ?? '' }}</span>
                </td>
                <td>
                    @php
                    $pill = match($r->status){
                    'requested' => 'bg-amber-50 text-amber-700 border border-amber-200',
                    'approved' => 'bg-violet-50 text-violet-700 border border-violet-200',
                    'in_transit' => 'bg-sky-50 text-sky-700 border border-sky-200',
                    'received' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                    'refunded' => 'bg-sky-50 text-sky-700 border border-sky-200',
                    'rejected','cancelled' => 'bg-rose-50 text-rose-700 border border-rose-200',
                    default => 'bg-slate-50 text-slate-700 border border-slate-200',
                    };
                    @endphp
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs {{ $pill }}">{{ strtoupper($r->status) }}</span>
                </td>
                <td class="text-right">{{ number_format($r->expected_refund) }}₫</td>
                <td class="text-right">{{ number_format($r->final_refund) }}₫</td>
                <td class="whitespace-nowrap text-slate-600">{{ optional($r->created_at)->format('d/m/Y H:i') }}</td>
                <td class="text-right">
                    <a href="{{ route('admin.order_returns.show', $r) }}" class="btn btn-outline btn-sm">Xem</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-slate-500 py-6">Chưa có yêu cầu.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">{{ $returns->withQueryString()->links() }}</div>

@push('scripts')
<script>
    // Ẩn alert sau 3s
    document.querySelectorAll('[data-auto-dismiss]')?.forEach(el => {
        const ms = +el.getAttribute('data-auto-dismiss') || 3000;
        setTimeout(() => el.remove(), ms);
    });
</script>
@endpush
@endsection