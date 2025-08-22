@extends('layouts.guest')

@section('title','Quên mật khẩu | Cosme House')
@section('heading','Quên mật khẩu')
@section('subheading','Nhập email để nhận liên kết đặt lại mật khẩu.')

@section('content')
<form action="{{ route('password.email') }}" method="POST" class="space-y-4">
    @csrf
    <div>
        <label class="block text-sm mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email') }}"
            class="w-full px-3 py-2 border border-rose-200 rounded-md outline-none focus:ring-2 focus:ring-brand-400"
            required>
        @error('email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <button class="w-full py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-md font-medium">
        Gửi liên kết đặt lại
    </button>
</form>
@endsection

@section('alt-action')
Nhớ mật khẩu rồi?
<a class="text-brand-600 hover:underline font-medium" href="{{ route('login') }}">Đăng nhập</a>
@endsection