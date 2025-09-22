@extends('admin.layouts.app')
@section('title','Đơn #'.$order->code)

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

<div class="toolbar">
    <div class="toolbar-title">Đơn hàng #{{ $order->code }}</div>
    <a class="btn btn-outline btn-sm" href="{{ route('admin.orders.index') }}">← Danh sách</a>
</div>

<div class="grid md:grid-cols-3 gap-3">
    <div class="md:col-span-2 space-y-3">
        <div class="card p-0">
            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Đơn giá</th>
                        <th>SL</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $it)
                    <tr>
                        <td>
                            <div class="cell-thumb">
                                <img class="thumb" src="{{ $it->product?->image ? asset('storage/'.$it->product->image) : 'https://placehold.co/60x60?text=IMG' }}">
                                <div class="min-w-0">
                                    <div class="font-medium truncate">{{ $it->product_name_snapshot }}</div>
                                    <div class="text-xs text-slate-500">{{ $it->variant_name_snapshot }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ number_format($it->unit_price,0) }}₫</td>
                        <td>{{ $it->qty }}</td>
                        <td class="font-semibold">{{ number_format($it->line_total,0) }}₫</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Timeline sự kiện (giống sàn lớn) --}}
        <div class="card p-3">
            <div class="font-semibold mb-2">Hoạt động</div>
            @forelse($order->events as $ev)
            <div class="text-sm py-1 border-t first:border-0">
                <span class="text-slate-500">{{ $ev->created_at->format('d/m/Y H:i') }}</span> —
                @switch($ev->type)
                @case('status_changed')
                Trạng thái: <b>{{ \App\Models\Order::STATUSES[$ev->old['status']] ?? $ev->old['status'] }}</b> → <b>{{ \App\Models\Order::STATUSES[$ev->new['status']] ?? $ev->new['status'] }}</b>
                @break
                @case('payment_changed')
                Thanh toán: <b>{{ \App\Models\Order::PAY_STATUSES[$ev->old['payment_status']] ?? $ev->old['payment_status'] }}</b> → <b>{{ \App\Models\Order::PAY_STATUSES[$ev->new['payment_status']] ?? $ev->new['payment_status'] }}</b>
                @break
                @case('tracking_updated')
                Cập nhật mã vận đơn: <b>{{ $ev->new['tracking_no'] ?? '' }}</b>
                @break
                @case('note_added')
                Ghi chú: {{ $ev->new['notes'] ?? '' }}
                @break
                @default
                {{ $ev->type }}
                @endswitch
            </div>
            @empty
            <div class="text-sm text-slate-500">Chưa có hoạt động.</div>
            @endforelse
        </div>
    </div>

    <div class="space-y-3">
        <div class="card p-3">
            <div class="text-sm text-slate-500">Khách hàng</div>
            <div class="mt-1">
                <div class="font-medium">{{ $order->customer_name }}</div>
                <div class="text-sm">{{ $order->customer_phone }} {{ $order->customer_email ? '· '.$order->customer_email : '' }}</div>
                <div class="text-sm text-slate-600 mt-1">{{ $order->address_text ?: '—' }}</div>
            </div>
        </div>

        <div class="card p-3">
            <div class="text-sm text-slate-500">Giao hàng</div>
            <div class="mt-1 space-y-1 text-sm">
                <div>Phương thức: <b>{{ $order->shipping_method ?: '—' }}</b></div>
                <div>Mã vận đơn: <b>{{ $order->tracking_no ?: '—' }}</b></div>
            </div>
        </div>

        <div class="card p-3">
            <div class="text-sm text-slate-500">Thanh toán & Trạng thái</div>

            <form method="post" action="{{ route('admin.orders.update', ['admin_order' => $order->id]) }}" class="space-y-2">
                @csrf @method('PUT')
                <div>
                    <label class="label">Trạng thái đơn</label>
                    <select name="status" class="form-control" id="statusSelect">
                        @foreach($statusOptions as $k=>$v)
                        <option value="{{ $k }}" @selected($order->status===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Trạng thái thanh toán</label>
                    <select name="payment_status" class="form-control" id="paySelect">
                        @foreach($payOptions as $k=>$v)
                        <option value="{{ $k }}" @selected($order->payment_status===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Mã vận đơn</label>
                    <input name="tracking_no" value="{{ old('tracking_no',$order->tracking_no) }}" class="form-control">
                </div>
                <div>
                    <label class="label">ĐVVC</label>
                    <input name="shipping_method" value="{{ old('shipping_method',$order->shipping_method) }}" class="form-control">
                </div>
                <div>
                    <label class="label">Ghi chú nội bộ</label>
                    <textarea name="notes" rows="2" class="form-control" placeholder="Thêm note">{{ old('notes',$order->notes) }}</textarea>
                </div>
                <div class="flex justify-end">
                    <button class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>

        <div class="card p-3">
            <div class="text-sm text-slate-500">Tổng kết</div>
            <div class="mt-2 text-sm space-y-1">
                <div class="flex justify-between"><span>Tạm tính</span><b>{{ number_format($order->subtotal,0) }}₫</b></div>
                <div class="flex justify-between"><span>Giảm giá</span><b>-{{ number_format($order->discount_total,0) }}₫</b></div>
                <div class="flex justify-between"><span>Phí vận chuyển</span><b>{{ number_format($order->shipping_fee,0) }}₫</b></div>
                <div class="flex justify-between"><span>Thuế</span><b>{{ number_format($order->tax_total,0) }}₫</b></div>
                <div class="divider"></div>
                <div class="flex justify-between text-base"><span>Tổng thanh toán</span><b>{{ number_format($order->grand_total,0) }}₫</b></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    if (document.getElementById('statusSelect')) new TomSelect('#statusSelect', {
        create: false
    });
    if (document.getElementById('paySelect')) new TomSelect('#paySelect', {
        create: false
    });
</script>
@endpush
@endsection