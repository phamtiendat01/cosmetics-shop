@extends('layouts.app')
@section('title','Hồ sơ cá nhân')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold">Hồ sơ cá nhân</h1>
    <p class="text-ink/60 mt-1">Cập nhật thông tin tài khoản và ảnh đại diện.</p>

    {{-- Flash --}}
    @if (session('status'))
    <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        {{ session('status') }}
    </div>
    @endif

    {{-- Errors --}}
    @if ($errors->any())
    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <div class="font-medium mb-1">Vui lòng kiểm tra lại:</div>
        <ul class="list-disc ml-5 space-y-0.5">
            @foreach ($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @php
    // Chuẩn hóa giá trị dob cho input[type=date]
    $dobVal = old('dob');
    if ($dobVal === null) {
    $raw = $user->dob;
    $dobVal = $raw instanceof \Illuminate\Support\Carbon
    ? $raw->format('Y-m-d')
    : (is_string($raw) ? $raw : '');
    }
    @endphp

    <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="bg-white border border-rose-100 rounded-xl shadow-card p-5">
            <div class="grid grid-cols-1 md:grid-cols-[160px,1fr] gap-6 items-start">
                {{-- Avatar --}}
                <div x-data="{preview: '{{ $avatar ?? '' }}' }" class="space-y-3">
                    <div class="w-36 h-36 rounded-full bg-rose-50 border border-rose-100 overflow-hidden flex items-center justify-center">
                        <img x-show="preview" :src="preview" class="w-full h-full object-cover" alt="">
                        <div x-show="!preview" class="text-ink/40 text-sm">Chưa có ảnh</div>
                    </div>
                    <label class="inline-flex items-center px-3 py-2 rounded-md border text-sm hover:bg-rose-50 cursor-pointer">
                        <input type="file" name="avatar" class="hidden" accept="image/*"
                            @change="if($event.target.files[0]){ preview = URL.createObjectURL($event.target.files[0]) }">
                        <i class="fa-regular fa-image mr-2"></i> Chọn ảnh
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="remove_avatar" value="1" class="rounded border-rose-300">
                        <span>Xóa ảnh hiện tại</span>
                    </label>
                    <div class="text-xs text-ink/60">JPG/PNG/WebP, tối đa 2MB. Ảnh vuông sẽ hiển thị đẹp hơn.</div>
                </div>

                {{-- Basic fields --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Họ tên <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                            class="w-full rounded-md border border-rose-200 bg-white px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                            class="w-full rounded-md border border-rose-200 bg-white px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Số điện thoại</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                            class="w-full rounded-md border border-rose-200 bg-white px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Giới tính</label>
                        <select name="gender"
                            class="w-full rounded-md border border-rose-200 bg-white px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
                            <option value="">— Chọn —</option>
                            <option value="male" @selected(old('gender', $user->gender)==='male')>Nam</option>
                            <option value="female" @selected(old('gender', $user->gender)==='female')>Nữ</option>
                            <option value="other" @selected(old('gender', $user->gender)==='other')>Khác</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Ngày sinh</label>
                        <input type="date" name="dob" value="{{ $dobVal }}"
                            class="w-full rounded-md border border-rose-200 bg-white px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button class="inline-flex items-center rounded-md bg-brand-600 text-white px-4 py-2 text-sm hover:bg-brand-700">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Lưu thay đổi
                </button>
                <a href="{{ route('account.dashboard') }}" class="inline-flex items-center rounded-md border px-4 py-2 text-sm hover:bg-rose-50">
                    Quay lại tổng quan
                </a>
            </div>
        </div>
    </form>

    {{-- Gợi ý an toàn --}}
    <div class="mt-6 text-xs text-ink/60">
        Mẹo: Nếu đổi email, có thể cần xác minh lại email tùy cấu hình dự án.
    </div>
</div>
@endsection