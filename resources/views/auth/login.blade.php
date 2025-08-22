@extends('layouts.guest')

@section('title','Đăng nhập | Cosme House')
@section('heading','Đăng nhập')
@section('subheading','Chào mừng bạn quay lại 💖')

@section('content')
<form action="{{ route('login') }}" method="POST" class="space-y-4">
    @csrf
    <div>
        <label class="block text-sm mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email') }}"
            class="w-full px-3 py-2 border border-rose-200 rounded-md outline-none focus:ring-2 focus:ring-brand-400"
            required autocomplete="email" autofocus>
        @error('email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm mb-1">Mật khẩu</label>
        <input type="password" name="password"
            class="w-full px-3 py-2 border border-rose-200 rounded-md outline-none focus:ring-2 focus:ring-brand-400"
            required autocomplete="current-password">
        @error('password') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center justify-between text-sm">
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="remember"> Ghi nhớ đăng nhập
        </label>
        <a class="text-brand-600 hover:underline" href="{{ route('password.request') }}">Quên mật khẩu?</a>
    </div>

    <button class="w-full py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-md font-medium">
        Đăng nhập
    </button>
</form>
@endsection

@section('alt-action')
Chưa có tài khoản?
<a class="text-brand-600 hover:underline font-medium" href="{{ route('register') }}">Đăng ký</a>
@endsection