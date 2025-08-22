@extends('layouts.app')
@section('title','Đơn hàng của tôi | Cosme House')

@section('content')
<section class="max-w-7xl mx-auto px-4 mt-6 grid md:grid-cols-12 gap-6">
    <aside class="md:col-span-3 space-y-2">
        <a class="block px-3 py-2 rounded-md hover:bg-rose-50" href="{{ route('dashboard') }}">Tổng quan</a>
        <a class="block px-3 py-2 rounded-md bg-white border border-rose-100" href="{{ route('account.orders') }}">Đơn hàng</a>
        <a class="block px-3 py-2 rounded-md hover:bg-rose-50" href="{{ route('account.addresses') }}">Sổ địa chỉ</a>
        <a class="block px-3 py-2 rounded-md hover:bg-rose-50" href="{{ route('account.wishlist') }}">Yêu thích</a>
        <a class="block px-3 py-2 rounded-md hover:bg-rose-50" href="{{ route('account.coupons') }}">Ví voucher</a>
    </aside>

    <main class="md:col-span-9">
        <div class="bg-white border border-rose-100 rounded-2xl p-4">
            <h1 class="text-lg font-bold mb-3">Đơn hàng của tôi</h1>

            {{-- Bộ lọc trạng thái đơn (UI) --}}
            <form method="get" class="flex flex-wrap gap-2 mb-3">
                <select name="status" class="border border-rose-200 rounded-md px-3 py-2 text-sm">
                    <option value="">Tất cả trạng thái</option>
                    @foreach(['pending'=>'Chờ xử lý','processing'=>'Đang xử lý','shipped'=>'Đã gửi','delivered'=>'Đã giao','cancelled'=>'Đã hủy'] as $k=>$v)
                    <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
                    @endforeach
                </select>
                <select name="payment_status" class="border border-rose-200 rounded-md px-3 py-2 text-sm">
                    <option value="">Thanh toán</option>
                    @foreach(['unpaid'=>'Chưa thanh toán','paid'=>'Đã thanh toán','refunded'=>'Hoàn tiền'] as $k=>$v)
                    <option value="{{ $k }}" @selected(request('payment_status')===$k)>{{ $v }}</option>
                    @endforeach
                </select>
                <button class="px-3 py-2 border border-rose-200 rounded-md">Lọc</button>
            </form>

            <div class="overflow-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-ink/60">
                        <tr class="border-b border-rose-100">
                            <th class="text-left py-2 pr-4">Mã đơn</th>
                            <th class="text-left py-2 pr-4">Ngày đặt</th>
                            <th class="text-left py-2 pr-4">Tổng tiền</th>
                            <th class="text-left py-2 pr-4">TT thanh toán</th>
                            <th class="text-left py-2 pr-4">Trạng thái</th>
                            <th class="text-left py-2">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $o)
                        <tr class="border-b border-rose-50">
                            <td class="py-2 pr-4 font-medium">#{{ $o->order_no ?? $o->id }}</td>
                            <td class="py-2 pr-4">{{ optional($o->placed_at ?? $o->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="py-2 pr-4 font-semibold">{{ number_format($o->grand_total) }}₫</td>
                            <td class="py-2 pr-4">
                                <span class="px-2 py-1 rounded-md text-xs {{ $o->payment_status==='paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                    {{ ucfirst($o->payment_status) }}
                                </span>
                            </td>
                            <td class="py-2 pr-4">
                                <span class="px-2 py-1 rounded-md text-xs bg-rose-50 text-rose-700">
                                    {{ ucfirst($o->status) }}
                                </span>
                            </td>
                            <td class="py-2">
                                <a href="{{ route('account.orders.show',$o->id) }}" class="text-brand-600 hover:underline">Xem</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6"><x-empty text="Bạn chưa có đơn hàng nào." /></td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $orders->onEachSide(1)->links('shared.pagination') }}
            </div>
        </div>
    </main>
</section>
@endsection