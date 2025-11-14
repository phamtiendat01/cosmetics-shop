@extends('layouts.app')
@section('title','Bảo mật & đăng nhập')
@php use Illuminate\Support\Str; @endphp

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6">

    {{-- Toast 3s --}}
    @if (session('status'))
    <div x-data="{show:true}" x-init="setTimeout(()=>show=false,3000)" x-show="show"
        class="fixed top-4 right-4 z-[999] bg-emerald-600 text-white px-4 py-2 rounded-lg shadow">
        {{ session('status') }}
    </div>
    @endif

    <h1 class="text-2xl font-bold mb-5">Bảo mật & đăng nhập</h1>

    <div class="grid md:grid-cols-2 gap-5">

        {{-- Đổi mật khẩu --}}
        <div class="bg-white rounded-2xl border border-rose-100 shadow-card p-5">
            <div class="font-semibold mb-3">Đổi mật khẩu</div>
            <form method="POST" action="{{ route('account.security.password') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm mb-1">Mật khẩu hiện tại</label>
                    <input name="current_password" type="password" required
                        class="w-full rounded-md border border-rose-200 px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
                    @error('current_password')<div class="text-sm text-rose-600 mt-1">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="block text-sm mb-1">Mật khẩu mới</label>
                    <input name="password" type="password" required
                        class="w-full rounded-md border border-rose-200 px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
                    @error('password')<div class="text-sm text-rose-600 mt-1">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="block text-sm mb-1">Xác nhận mật khẩu</label>
                    <input name="password_confirmation" type="password" required
                        class="w-full rounded-md border border-rose-200 px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
                </div>
                <button class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700">Cập nhật mật khẩu</button>
            </form>
            <p class="text-xs text-ink/60 mt-2">Sau khi đổi, các thiết bị khác sẽ bị đăng xuất.</p>
        </div>

        {{-- Đổi email đăng nhập --}}
        <div class="bg-white rounded-2xl border border-rose-100 shadow-card p-5">
            <div class="font-semibold mb-3">Đổi email đăng nhập</div>
            <form method="POST" action="{{ route('account.security.email') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm mb-1">Email mới</label>
                    <input name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required
                        class="w-full rounded-md border border-rose-200 px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
                    @error('email')<div class="text-sm text-rose-600 mt-1">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="block text-sm mb-1">Mật khẩu hiện tại</label>
                    <input name="current_password" type="password" required
                        class="w-full rounded-md border border-rose-200 px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
                    @error('current_password')<div class="text-sm text-rose-600 mt-1">{{ $message }}</div>@enderror
                </div>
                <button class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700">Cập nhật email</button>
            </form>
        </div>
    </div>

    {{-- Phiên đăng nhập & thiết bị --}}
    <div class="bg-white rounded-2xl border border-rose-100 shadow-card p-5 mt-5">
        <div class="flex items-center justify-between mb-3">
            <div>
                <div class="font-semibold">Phiên đăng nhập & thiết bị</div>
                <div class="text-sm text-ink/70">Quản lý các thiết bị đang hoạt động.</div>
            </div>
        </div>

        @if($sessions->isNotEmpty())
        <div class="space-y-3">
            @foreach($sessions as $s)
            <div class="flex items-center justify-between border rounded-lg px-3 py-2">
                <div class="text-sm">
                    <div class="font-medium">
                        {{ $s->ua ? Str::limit($s->ua, 80) : 'Trình duyệt' }}
                        @if($s->is_current) <span class="text-emerald-700">• Thiết bị hiện tại</span>@endif
                    </div>
                    <div class="text-ink/70">IP: {{ $s->ip ?? 'N/A' }} • Hoạt động: {{ $s->last->diffForHumans() }}</div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-sm text-ink/70">
            Không có danh sách phiên. Để hiển thị, bật <code class="bg-rose-50 px-1 rounded">SESSION_DRIVER=database</code> rồi chạy:
            <code class="bg-rose-50 px-1 rounded">php artisan session:table && php artisan migrate</code>.
        </div>
        @endif

        <form method="POST" action="{{ route('account.security.sessions.logout-others') }}" class="mt-4 max-w-sm space-y-2">
            @csrf
            <label class="text-sm">Nhập mật khẩu hiện tại để đăng xuất các thiết bị khác</label>
            <input type="password" name="current_password"
                class="w-full border border-rose-200 rounded px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400" required>
            @error('current_password')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
            <button class="px-3 py-2 bg-rose-600 text-white rounded hover:bg-rose-700">Đăng xuất thiết bị khác</button>
        </form>
    </div>
</div>
@endsection