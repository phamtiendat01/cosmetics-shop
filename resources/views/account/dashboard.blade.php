@extends('layouts.app')
@section('title','Tài khoản | Cosme House')

@section('content')
<section class="max-w-7xl mx-auto px-4 mt-6 grid md:grid-cols-12 gap-6">
    <aside class="md:col-span-3 space-y-2">
        <a class="block px-3 py-2 rounded-md bg-white border border-rose-100" href="{{ route('dashboard') }}">Tổng quan</a>
        <a class="block px-3 py-2 rounded-md hover:bg-rose-50" href="{{ route('account.orders') }}">Đơn hàng</a>
        <a class="block px-3 py-2 rounded-md hover:bg-rose-50" href="{{ route('account.addresses') }}">Sổ địa chỉ</a>
        <a class="block px-3 py-2 rounded-md hover:bg-rose-50" href="{{ route('account.wishlist') }}">Yêu thích</a>
        <a class="block px-3 py-2 rounded-md hover:bg-rose-50" href="{{ route('account.coupons') }}">Ví voucher</a>
    </aside>

    <main class="md:col-span-9">
        <div class="bg-white border border-rose-100 rounded-2xl p-4">
            <h1 class="text-lg font-bold">Xin chào, {{ auth()->user()->name ?? 'Bạn' }}</h1>
            <p class="text-sm text-ink/60 mt-1">Quản lý thông tin tài khoản và đơn hàng của bạn tại đây.</p>

            <div class="grid sm:grid-cols-3 gap-3 mt-4">
                <div class="p-4 border border-rose-100 rounded-xl">
                    <div class="text-ink/60 text-sm">Đơn hàng</div>
                    <div class="text-2xl font-bold">{{ $stats['orders'] ?? 0 }}</div>
                </div>
                <div class="p-4 border border-rose-100 rounded-xl">
                    <div class="text-ink/60 text-sm">Yêu thích</div>
                    <div class="text-2xl font-bold">{{ $stats['wishlist'] ?? 0 }}</div>
                </div>
                <div class="p-4 border border-rose-100 rounded-xl">
                    <div class="text-ink/60 text-sm">Voucher</div>
                    <div class="text-2xl font-bold">{{ $stats['coupons'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </main>
</section>
@endsection