@php $active = $active ?? ''; @endphp
<a class="block px-3 py-2 rounded-md {{ $active==='dashboard' ? 'bg-white border border-rose-100' : 'hover:bg-rose-50' }}" href="{{ route('dashboard') }}">Tổng quan</a>
<a class="block px-3 py-2 rounded-md {{ $active==='orders' ? 'bg-white border border-rose-100' : 'hover:bg-rose-50' }}" href="{{ route('account.orders') }}">Đơn hàng</a>
<a class="block px-3 py-2 rounded-md {{ $active==='addresses' ? 'bg-white border border-rose-100' : 'hover:bg-rose-50' }}" href="{{ route('account.addresses') }}">Sổ địa chỉ</a>
<a class="block px-3 py-2 rounded-md {{ $active==='wishlist' ? 'bg-white border border-rose-100' : 'hover:bg-rose-50' }}" href="{{ route('account.wishlist') }}">Yêu thích</a>
<a class="block px-3 py-2 rounded-md {{ $active==='coupons' ? 'bg-white border border-rose-100' : 'hover:bg-rose-50' }}" href="{{ route('account.coupons') }}">Ví voucher</a>