@extends('layouts.app')
@section('title','Quên mật khẩu')

@section('content')
<div class="max-w-md mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold mb-6">Quên mật khẩu</h1>

    @if (session('status'))
    <div class="mb-4 rounded border border-emerald-200 bg-emerald-50 text-emerald-800 px-3 py-2">
        {{ session('status') }}
    </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm mb-1">Email</label>
            <input name="email" type="email" value="{{ old('email') }}" required
                class="w-full border border-rose-200 rounded px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
            @error('email')<div class="text-sm text-rose-600 mt-1">{{ $message }}</div>@enderror
        </div>
        <button class="w-full bg-brand-600 hover:bg-brand-700 text-white rounded px-4 py-2">Gửi liên kết đặt lại</button>
        <div class="text-sm mt-2"><a class="text-brand-600 hover:underline" href="{{ route('login') }}">Quay lại đăng nhập</a></div>
    </form>
</div>
@endsection