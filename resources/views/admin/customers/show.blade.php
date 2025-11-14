@extends('admin.layouts.app')
@section('title','Hồ sơ khách hàng')

@section('content')
<div class="toolbar">
    <div class="toolbar-title">Hồ sơ khách hàng</div>
    <a href="{{ route('admin.customers.edit',$customer) }}" class="btn btn-primary btn-sm">Sửa</a>
</div>

<div class="grid md:grid-cols-3 gap-3">
    <div class="card p-3">
        <div class="cell-thumb">
            <img
                class="thumb"
                src="{{ $customer->avatar_url }}"
                alt="{{ $customer->name }}"
                onerror="this.onerror=null;this.src='https://i.pravatar.cc/120?u={{ urlencode($customer->email ?? $customer->id) }}'">
            <div>
                <div class="font-semibold">{{ $customer->name }}</div>
                <div class="text-sm text-slate-600">{{ $customer->email }}</div>
                <div class="text-sm">{{ $customer->phone ?? '—' }}</div>
            </div>
        </div>
        <div class="divider"></div>
        <div class="text-sm">
            Trạng thái:
            @if($customer->is_active)
            <span class="badge badge-green"><span class="badge-dot"></span>Hoạt động</span>
            @else
            <span class="badge badge-red"><span class="badge-dot"></span>Khoá</span>
            @endif
            <br>
            Xác thực email: {{ $customer->email_verified_at ? 'Đã xác thực' : 'Chưa' }}<br>
            Ngày sinh: {{ $customer->dob?->format('d/m/Y') ?? '—' }}<br>
            Địa chỉ mặc định: {{ $customer->default_shipping_address['line1'] ?? '—' }},
            {{ $customer->default_shipping_address['district'] ?? '' }}
            {{ $customer->default_shipping_address['city'] ?? '' }}
        </div>
    </div>

    <div class="card p-3 md:col-span-2">
        <div class="font-semibold mb-2">Đơn hàng gần đây</div>
        <div class="table-wrap">
            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Ngày đặt</th>
                        <th>SL SP</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $o)
                    <tr>
                        <td>{{ $o->code }}</td>
                        <td>{{ $o->placed_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $o->items()->sum('qty') }}</td>
                        <td style="text-align:right">{{ number_format($o->grand_total,0,',','.') }}₫</td>
                        <td>{{ ucfirst($o->status) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-slate-500">Chưa có đơn.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $orders->links() }}</div>
    </div>
</div>
@endsection