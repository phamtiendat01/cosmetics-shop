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
    <div class="flex items-center gap-2">
        <a class="btn btn-outline btn-sm" href="{{ route('admin.orders.index') }}">← Danh sách</a>
        @php
        // Kiểm tra xem order có QR codes không (kiểm tra trực tiếp từ database)
        $hasQRCodes = \App\Models\ProductQRCode::whereHas('orderItem', function($q) use ($order) {
            $q->where('order_id', $order->id);
        })->exists();
        @endphp
        @if($hasQRCodes)
        <a class="btn btn-primary btn-sm" href="{{ route('admin.orders.print-qr-codes', $order) }}" target="_blank">
            <i class="fas fa-print mr-1"></i> In QR Codes
        </a>
        @endif
        <a class="btn btn-outline btn-sm" href="{{ route('admin.order_returns.index', ['order' => $order->id]) }}">Đổi trả / Hoàn tiền</a>
    </div>
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
                    @php
                    // Logic lấy ảnh: snapshot thumbnail -> product thumbnail -> product image -> placeholder
                    $thumb = $it->thumbnail ?? null;
                    
                    if (!$thumb && $it->product) {
                        $thumb = $it->product->thumbnail ?? $it->product->image ?? null;
                    }

                    // Format URL
                    if ($thumb && !str_starts_with($thumb, 'http://') && !str_starts_with($thumb, 'https://')) {
                        $thumb = asset(str_starts_with($thumb, 'storage/') || str_starts_with($thumb, '/storage/')
                            ? ltrim($thumb, '/') 
                            : 'storage/' . ltrim($thumb, '/'));
                    } elseif (!$thumb) {
                        $thumb = 'https://placehold.co/60x60?text=IMG';
                    }
                    @endphp
                    <tr>
                        <td>
                            <div class="cell-thumb">
                                <img class="thumb" src="{{ $thumb }}" alt="{{ $it->product_name_snapshot }}">
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

        {{-- Timeline sự kiện --}}
        <div class="card p-3">
            <div class="font-semibold mb-2">Hoạt động</div>
            @forelse($order->events as $ev)
            <div class="text-sm py-1 border-t first:border-0">
                <span class="text-slate-500">{{ $ev->created_at->format('d/m/Y H:i') }}</span> —
                @switch($ev->type)
                @case('status_changed')
                Trạng thái:
                <b>{{ \App\Models\Order::STATUSES[$ev->old['status']] ?? $ev->old['status'] }}</b>
                →
                <b>{{ \App\Models\Order::STATUSES[$ev->new['status']] ?? $ev->new['status'] }}</b>
                @break
                @case('payment_changed')
                Thanh toán:
                <b>{{ \App\Models\Order::PAY_STATUSES[$ev->old['payment_status']] ?? $ev->old['payment_status'] }}</b>
                →
                <b>{{ \App\Models\Order::PAY_STATUSES[$ev->new['payment_status']] ?? $ev->new['payment_status'] }}</b>
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

        {{-- Thao tác nhanh --}}
        <div class="card p-3">
            <div class="font-semibold mb-2">Thao tác nhanh</div>
            @php
            $canCancel = in_array($order->status, ['pending','confirmed','processing'], true)
            && ( $order->payment_status !== 'paid' || strtoupper($order->payment_method) === 'COD' );
            @endphp
            @if($canCancel)
            <form method="POST" action="{{ route('admin.orders.cancel', ['admin_order' => $order->id]) }}"
                onsubmit="return confirm('Bạn có chắc muốn huỷ đơn này? Tồn kho sẽ được cộng lại.');">
                @csrf
                <button class="btn btn-outline btn-danger w-full">Huỷ đơn (COD/chưa thanh toán)</button>
            </form>
            @else
            <div class="text-sm text-slate-500">
                Huỷ đơn khả dụng khi trạng thái là <b>Chờ xác nhận / Đã xác nhận / Đang xử lý</b> và đơn <b>chưa thanh toán online</b>.
            </div>
            @endif
        </div>

        {{-- Đổi trả / Hoàn tiền --}}
        <div class="card p-3">
            <div class="text-sm text-slate-500">Đổi trả / Hoàn tiền</div>
            @php
            $retCount = $order->returns()->count();
            $latestReturn = $order->returns()->latest()->first();
            @endphp

            @if($retCount)
            <div class="mt-1 text-sm">Có <b>{{ $retCount }}</b> yêu cầu.</div>
            <div class="mt-2 flex gap-2">
                <a class="btn btn-outline btn-sm" href="{{ route('admin.order_returns.index', ['order' => $order->id]) }}">Danh sách</a>
                @if($latestReturn)
                <a class="btn btn-outline btn-sm" href="{{ route('admin.order_returns.show', $latestReturn) }}">Xem gần nhất #{{ $latestReturn->id }}</a>
                @endif
            </div>
            @else
            <div class="mt-1 text-sm text-slate-500">Chưa có yêu cầu trả hàng.</div>
            <a class="btn btn-outline btn-sm mt-2" href="{{ route('admin.order_returns.index', ['order' => $order->id]) }}">Tạo/Xem yêu cầu</a>
            @endif
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
    // Select đẹp (nếu có TomSelect)
    if (document.getElementById('statusSelect')) new TomSelect('#statusSelect', {
        create: false
    });
    if (document.getElementById('paySelect')) new TomSelect('#paySelect', {
        create: false
    });

    // Ẩn alert sau 3s
    document.querySelectorAll('[data-auto-dismiss]')?.forEach(el => {
        const ms = +el.getAttribute('data-auto-dismiss') || 3000;
        setTimeout(() => el.remove(), ms);
    });
</script>
@endpush
@endsection