@extends('layouts.guest')

@section('title','Đăng ký | Cosme House')
@section('heading','Tạo tài khoản')
@section('subheading','Gia nhập hội skincare sành điệu ✨')

@section('content')
<form action="{{ route('register') }}" method="POST" class="space-y-4">
    @csrf
    <div>
        <label class="block text-sm mb-1">Họ tên</label>
        <input type="text" name="name" value="{{ old('name') }}"
            class="w-full px-3 py-2 border border-rose-200 rounded-md outline-none focus:ring-2 focus:ring-brand-400"
            required>
        @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email') }}"
            class="w-full px-3 py-2 border border-rose-200 rounded-md outline-none focus:ring-2 focus:ring-brand-400"
            required>
        @error('email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="block text-sm mb-1">Mật khẩu</label>
            <input type="password" name="password"
                class="w-full px-3 py-2 border border-rose-200 rounded-md outline-none focus:ring-2 focus:ring-brand-400"
                required autocomplete="new-password">
            @error('password') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm mb-1">Nhập lại mật khẩu</label>
            <input type="password" name="password_confirmation"
                class="w-full px-3 py-2 border border-rose-200 rounded-md outline-none focus:ring-2 focus:ring-brand-400"
                required autocomplete="new-password">
        </div>
    </div>

    <button class="w-full py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-md font-medium">
        Đăng ký
    </button>
</form>
@endsection

@section('alt-action')
Đã có tài khoản?
<a class="text-brand-600 hover:underline font-medium" href="{{ route('login') }}">Đăng nhập</a>
@endsection