{{-- resources/views/admin/order_returns/show.blade.php --}}
@extends('admin.layouts.app')
@section('title','Return #'.$return->id.' · Đơn '.$return->order->code)

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

@php
// Stepper nho nhỏ cho vòng đời trả hàng
$steps = ['requested'=>'Yêu cầu','approved'=>'Duyệt','received'=>'Kho nhận','refunded'=>'Hoàn tiền'];
$idx = array_search($return->status, array_keys($steps)) ?? 0;
$pill = match($return->status){
'requested' => 'bg-amber-50 text-amber-700 border border-amber-200',
'approved' => 'bg-violet-50 text-violet-700 border border-violet-200',
'received' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
'refunded' => 'bg-sky-50 text-sky-700 border border-sky-200',
'rejected','cancelled' => 'bg-rose-50 text-rose-700 border border-rose-200',
default => 'bg-slate-50 text-slate-700 border border-slate-200',
};
@endphp

<div class="toolbar">
    <div class="toolbar-title">Yêu cầu trả hàng #{{ $return->id }} — Đơn #{{ $return->order->code }}</div>
    <div class="flex items-center gap-2">
        <a class="btn btn-outline btn-sm" href="{{ route('admin.order_returns.index') }}">← Danh sách</a>
        @if(Route::has('admin.orders.show'))
        <a class="btn btn-outline btn-sm" href="{{ route('admin.orders.show', ['admin_order'=>$return->order_id]) }}">Xem đơn</a>
        @endif
    </div>
</div>

{{-- Stepper --}}
<div class="card p-4 mb-3">
    <div class="flex items-center gap-4">
        @php $i=0; @endphp
        @foreach($steps as $key=>$label)
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full flex items-center justify-center
              {{ $i <= $idx ? 'text-white shadow bg-gradient-to-br from-rose-600 to-pink-600' : 'text-rose-300 border border-rose-200 bg-white' }}">
                <span class="text-xs font-semibold">{{ $i+1 }}</span>
            </div>
            <div class="text-xs font-medium {{ $i <= $idx ? 'text-ink' : 'text-ink/40' }}">{{ $label }}</div>
        </div>
        @if(!$loop->last)
        <div class="flex-1 h-1 rounded-full {{ $i < $idx ? 'bg-gradient-to-r from-rose-500 to-pink-500' : 'bg-rose-100' }}"></div>
        @endif
        @php $i++; @endphp
        @endforeach
    </div>
    <div class="mt-3 text-sm">
        Trạng thái:
        <span class="inline-flex rounded-full px-2 py-0.5 text-xs {{ $pill }}">{{ strtoupper($return->status) }}</span>
    </div>
</div>

<div class="grid md:grid-cols-3 gap-3">
    <div class="md:col-span-2 space-y-3">
        {{-- Items trả --}}
        <div class="card p-0 overflow-auto">
            <table class="table-admin text-sm">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th class="text-center">SL trả</th>
                        <th class="text-center">SL nhận</th>
                        <th class="text-center">Tình trạng</th>
                        <th class="text-right">Hoàn (đ)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($return->items as $rit)
                    <tr>
                        <td>
                            <div class="font-medium">{{ $rit->orderItem->product_name_snapshot }}</div>
                            <div class="text-xs text-slate-500">{{ $rit->orderItem->variant_name_snapshot }}</div>
                        </td>
                        <td class="text-center">{{ $rit->qty }}</td>
                        <td class="text-center">{{ $rit->approved_qty ?? '—' }}</td>
                        <td class="text-center">{{ $rit->condition ?? '—' }}</td>
                        <td class="text-right font-semibold">{{ number_format($rit->line_refund) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Tổng kết --}}
        <div class="card p-3 text-sm space-y-1">
            <div>Tạm tính: <b>{{ number_format($return->expected_refund) }}đ</b></div>
            <div>Chốt hoàn: <b>{{ number_format($return->final_refund) }}đ</b></div>
            @if($return->reason)
            <div>Lý do KH: <span class="text-slate-700">{{ $return->reason }}</span></div>
            @endif
            <div>Thời gian tạo: <span class="text-slate-600">{{ optional($return->created_at)->format('d/m/Y H:i') }}</span></div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="space-y-3">
        @if($return->status==='requested')
        <div class="card p-3">
            <div class="font-semibold mb-2">Duyệt yêu cầu</div>
            <button class="btn btn-primary w-full" data-open="#modal-approve">Duyệt</button>
        </div>
        @endif

        @if(in_array($return->status,['approved','requested']))
        <div class="card p-3">
            <div class="font-semibold mb-2">Kho nhận — chốt SL & tình trạng</div>
            <button class="btn btn-primary w-full" data-open="#modal-receive">Xác nhận nhận hàng</button>
        </div>
        @endif

        @if(in_array($return->status,['approved','received']))
        <div class="card p-3">
            <div class="font-semibold mb-2">Hoàn tiền</div>
            <button class="btn btn-primary w-full" data-open="#modal-refund">Thực hiện hoàn</button>
            <div class="text-xs text-slate-500 mt-2">Phương thức gốc: {{ $return->order->payment_method ?? 'COD' }}</div>
        </div>
        @endif
    </div>
</div>

{{-- ======= MODALS ======= --}}
{{-- Approve --}}
<div id="modal-approve" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/50" data-close></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-2xl bg-white border shadow-xl">
            <div class="p-5">
                <h3 class="text-base font-semibold mb-1">Duyệt yêu cầu trả hàng?</h3>
                <p class="text-sm text-ink/70 mb-4">Sau khi duyệt, kho có thể xác nhận số lượng thực nhận.</p>
                <form method="POST" action="{{ route('admin.order_returns.approve',$return) }}">
                    @csrf
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn btn-outline" data-close>Để sau</button>
                        <button class="btn btn-primary">Duyệt</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Receive --}}
<div id="modal-receive" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/50" data-close></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-xl rounded-2xl bg-white border shadow-xl">
            <div class="p-5">
                <h3 class="text-base font-semibold mb-2">Kho nhận — chốt SL & tình trạng</h3>
                <form method="POST" action="{{ route('admin.order_returns.receive',$return) }}" class="space-y-2">
                    @csrf
                    @foreach($return->items as $rit)
                    <div class="grid grid-cols-3 items-center gap-2 text-sm">
                        <div class="truncate">{{ $rit->orderItem->product_name_snapshot }}</div>
                        <input type="hidden" name="items[{{$loop->index}}][id]" value="{{ $rit->id }}">
                        <input name="items[{{$loop->index}}][approved_qty]" type="number" min="0" max="{{ $rit->qty }}" class="form-control" placeholder="SL nhận">
                        <select name="items[{{$loop->index}}][condition]" class="form-control">
                            <option value="resell">Bán lại</option>
                            <option value="damaged">Hư hỏng</option>
                        </select>
                    </div>
                    @endforeach
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn btn-outline" data-close>Hủy</button>
                        <button class="btn btn-primary">Lưu xác nhận</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Refund --}}
<div id="modal-refund" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/50" data-close></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-2xl bg-white border shadow-xl">
            <div class="p-5">
                <h3 class="text-base font-semibold mb-2">Thực hiện hoàn tiền</h3>
                <form method="POST" action="{{ route('admin.order_returns.refund',$return) }}">
                    @csrf
                    <label class="label">Số tiền (đ)</label>
                    <input name="amount" class="form-control" value="{{ max($return->final_refund,0) }}">
                    <div class="text-xs text-slate-500 mt-2">Phương thức gốc: {{ $return->order->payment_method ?? 'COD' }}</div>
                    <div class="flex justify-end gap-2 mt-3">
                        <button type="button" class="btn btn-outline" data-close>Hủy</button>
                        <button class="btn btn-primary">Hoàn tiền</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // auto dismiss alerts
    document.querySelectorAll('[data-auto-dismiss]')?.forEach(el => {
        const ms = +el.getAttribute('data-auto-dismiss') || 3000;
        setTimeout(() => el.remove(), ms);
    });

    // modal open/close
    const openers = document.querySelectorAll('[data-open]');
    openers.forEach(btn => {
        btn.addEventListener('click', () => {
            const sel = btn.getAttribute('data-open');
            document.querySelector(sel)?.classList.remove('hidden');
        });
    });
    document.addEventListener('click', (e) => {
        if (e.target?.hasAttribute?.('data-close')) {
            e.target.closest('.fixed.inset-0')?.classList.add('hidden');
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.fixed.inset-0').forEach(m => m.classList.add('hidden'));
        }
    });
</script>
@endpush
@endsection