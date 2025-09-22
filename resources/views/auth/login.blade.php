@extends('layouts.app')
@section('title','Đăng nhập')

@section('content')
<div class="max-w-md mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold mb-6">Đăng nhập</h1>

    @if (session('status'))
    <div class="mb-4 rounded border border-emerald-200 bg-emerald-50 text-emerald-800 px-3 py-2">
        {{ session('status') }}
    </div>
    @endif

    <form method="POST" action="{{ url('/login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm mb-1">Email</label>
            <input name="email" type="email" value="{{ old('email') }}" required autofocus
                class="w-full border border-rose-200 rounded px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
            @error('email')<div class="text-sm text-rose-600 mt-1">{{ $message }}</div>@enderror
        </div>

        <div>
            <label class="block text-sm mb-1">Mật khẩu</label>
            <input name="password" type="password" required
                class="w-full border border-rose-200 rounded px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
            @error('password')<div class="text-sm text-rose-600 mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" value="1" class="rounded border-rose-300">
                Ghi nhớ đăng nhập
            </label>
            <a href="{{ route('password.request') }}" class="text-sm text-brand-600 hover:underline">Quên mật khẩu?</a>
        </div>

        <button class="w-full bg-brand-600 hover:bg-brand-700 text-white rounded px-4 py-2">Đăng nhập</button>

        <div class="text-sm text-ink/70">
            Chưa có tài khoản?
            <a href="{{ route('register') }}" class="text-brand-600 hover:underline">Đăng ký</a>
        </div>
    </form>
</div>
@endsection