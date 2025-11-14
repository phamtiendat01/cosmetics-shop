@extends('layouts.app')
@section('title','Đăng nhập')

@section('content')
<div class="max-w-md mx-auto px-4 py-12" x-data="{ showPass:false }">
    <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-50 text-rose-700 text-xs font-medium">
            <i class="fa-solid fa-shield-heart"></i> Bảo mật & riêng tư
        </div>
        <h1 class="mt-3 text-2xl font-semibold text-slate-900">Đăng nhập tài khoản</h1>
        <p class="text-sm text-slate-500 mt-1">Tiếp tục để quản lý đơn hàng và ưu đãi dành riêng.</p>
    </div>

    <div class="rounded-2xl border border-rose-100 bg-white shadow-sm">
        <div class="p-6 space-y-5">
            @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 px-3 py-2 text-sm">
                {{ session('status') }}
            </div>
            @endif

            <form method="POST" action="{{ url('/login') }}" class="space-y-4">
                @csrf

                {{-- Email --}}
                <div>
                    <label class="block text-sm text-slate-700 mb-1">Email</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                            placeholder="you@example.com"
                            class="w-full pl-10 pr-3 py-2 rounded-xl border border-rose-200 focus:outline-none focus:ring-2 focus:ring-rose-300">
                    </div>
                    @error('email')<div class="text-xs text-rose-600 mt-1">{{ $message }}</div>@enderror
                </div>

                {{-- Password --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm text-slate-700">Mật khẩu</label>
                        <a href="{{ route('password.request') }}" class="text-xs text-rose-600 hover:underline">Quên mật khẩu?</a>
                    </div>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input
                            :type="showPass ? 'text' : 'password'"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Nhập mật khẩu"
                            class="w-full pl-10 pr-10 py-2 rounded-xl border border-rose-200 focus:outline-none focus:ring-2 focus:ring-rose-300">
                        <button type="button"
                            @click="showPass = !showPass"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <i :class="showPass ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                        </button>
                    </div>
                    @error('password')<div class="text-xs text-rose-600 mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" value="1" class="rounded border-rose-300">
                        Ghi nhớ đăng nhập
                    </label>
                </div>

                <button
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl
                           bg-rose-600 text-white hover:bg-rose-700 transition active:scale-[.99]">
                    <i class="fa-solid fa-right-to-bracket"></i> Đăng nhập
                </button>
            </form>

            <div class="relative text-center my-1">
                <span class="px-3 text-xs text-slate-400 bg-white relative z-[1]">hoặc</span>
                <div class="absolute inset-x-0 top-1/2 -translate-y-1/2 h-px bg-slate-200"></div>
            </div>

            <div class="space-y-3">
                <a href="{{ route('oauth.google.redirect') }}"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-rose-200 bg-white hover:bg-rose-50 text-rose-700">
                    <i class="fa-brands fa-google"></i> Đăng nhập với Google
                </a>
                <a href="{{ route('auth.facebook.redirect') }}"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700">
                    <i class="fa-brands fa-facebook text-[#1877F2]"></i> Đăng nhập với Facebook
                </a>
            </div>
        </div>
    </div>

    <div class="text-center text-sm text-slate-500 mt-4">
        Chưa có tài khoản?
        <a href="{{ route('register') }}" class="text-rose-600 hover:underline">Đăng ký</a>
    </div>
</div>
@endsection