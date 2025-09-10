@extends('layouts.app')
@section('title','Đăng ký')

@section('content')
<div class="max-w-md mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold mb-6">Đăng ký</h1>

    <form method="POST" action="{{ url('/register') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm mb-1">Họ tên</label>
            <input name="name" value="{{ old('name') }}" required
                class="w-full border border-rose-200 rounded px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
            @error('name')<div class="text-sm text-rose-600 mt-1">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="block text-sm mb-1">Email</label>
            <input name="email" type="email" value="{{ old('email') }}" required
                class="w-full border border-rose-200 rounded px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
            @error('email')<div class="text-sm text-rose-600 mt-1">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="block text-sm mb-1">Mật khẩu</label>
            <input name="password" type="password" required
                class="w-full border border-rose-200 rounded px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
            @error('password')<div class="text-sm text-rose-600 mt-1">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="block text-sm mb-1">Xác nhận mật khẩu</label>
            <input name="password_confirmation" type="password" required
                class="w-full border border-rose-200 rounded px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
        </div>

        <button class="w-full bg-brand-600 hover:bg-brand-700 text-white rounded px-4 py-2">Tạo tài khoản</button>

        <div class="text-sm text-ink/70">
            Đã có tài khoản?
            <a href="{{ route('login') }}" class="text-brand-600 hover:underline">Đăng nhập</a>
        </div>
    </form>
</div>
@endsection