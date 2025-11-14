@extends('layouts.app')
@section('title','Đăng ký')

@section('content')
<div class="max-w-md mx-auto px-4 py-12" x-data="{ showPass:false, showPass2:false }">
    <div class="text-center mb-8">
        <h1 class="text-2xl font-semibold text-slate-900">Tạo tài khoản</h1>
        <p class="text-sm text-slate-500 mt-1">Tham gia Cosme House để nhận ưu đãi thành viên.</p>
    </div>

    <div class="rounded-2xl border border-rose-100 bg-white shadow-sm">
        <div class="p-6 space-y-5">
            <form method="POST" action="{{ url('/register') }}" class="space-y-4">
                @csrf

                {{-- Name --}}
                <div>
                    <label class="block text-sm text-slate-700 mb-1">Họ tên</label>
                    <div class="relative">
                        <i class="fa-solid fa-user absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="name"
                            value="{{ old('name') }}"
                            required
                            autocomplete="name"
                            placeholder="Nguyễn Văn A"
                            class="w-full pl-10 pr-3 py-2 rounded-xl border border-rose-200 focus:outline-none focus:ring-2 focus:ring-rose-300">
                    </div>
                    @error('name')<div class="text-xs text-rose-600 mt-1">{{ $message }}</div>@enderror
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-sm text-slate-700 mb-1">Email</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="email"
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
                    <label class="block text-sm text-slate-700 mb-1">Mật khẩu</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input :type="showPass ? 'text' : 'password'"
                            name="password"
                            required
                            autocomplete="new-password"
                            placeholder="Tối thiểu 8 ký tự"
                            class="w-full pl-10 pr-10 py-2 rounded-xl border border-rose-200 focus:outline-none focus:ring-2 focus:ring-rose-300">
                        <button type="button"
                            @click="showPass = !showPass"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <i :class="showPass ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                        </button>
                    </div>
                    @error('password')<div class="text-xs text-rose-600 mt-1">{{ $message }}</div>@enderror
                </div>

                {{-- Confirm Password --}}
                <div>
                    <label class="block text-sm text-slate-700 mb-1">Xác nhận mật khẩu</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input :type="showPass2 ? 'text' : 'password'"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="Nhập lại mật khẩu"
                            class="w-full pl-10 pr-10 py-2 rounded-xl border border-rose-200 focus:outline-none focus:ring-2 focus:ring-rose-300">
                        <button type="button"
                            @click="showPass2 = !showPass2"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <i :class="showPass2 ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <button
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl
                           bg-rose-600 text-white hover:bg-rose-700 transition active:scale-[.99]">
                    <i class="fa-solid fa-user-plus"></i> Tạo tài khoản
                </button>
            </form>
        </div>
    </div>

    <div class="text-center text-sm text-slate-500 mt-4">
        Đã có tài khoản?
        <a href="{{ route('login') }}" class="text-rose-600 hover:underline">Đăng nhập</a>
    </div>
</div>
@endsection