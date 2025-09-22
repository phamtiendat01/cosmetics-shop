@extends('layouts.app')
@section('title','Đặt lại mật khẩu')

@section('content')
<div class="max-w-md mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold mb-6">Đặt lại mật khẩu</h1>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email ?? old('email') }}">

        <div>
            <label class="block text-sm mb-1">Mật khẩu mới</label>
            <input name="password" type="password" required
                class="w-full border border-rose-200 rounded px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
            @error('password')<div class="text-sm text-rose-600 mt-1">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="block text-sm mb-1">Xác nhận mật khẩu</label>
            <input name="password_confirmation" type="password" required
                class="w-full border border-rose-200 rounded px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
        </div>

        <button class="w-full bg-brand-600 hover:bg-brand-700 text-white rounded px-4 py-2">Cập nhật mật khẩu</button>
        <div class="text-sm mt-2"><a class="text-brand-600 hover:underline" href="{{ route('login') }}">Quay lại đăng nhập</a></div>
    </form>
</div>
@endsection