@extends('admin.layouts.app')
@section('title', ($carrier->exists ? 'Sửa đơn vị: '.$carrier->name : 'Thêm đơn vị vận chuyển'))

@section('content')
@if(session('ok')) <div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div> @endif
@if($errors->any()) <div class="alert alert-danger mb-3">{{ $errors->first() }}</div> @endif

@include('admin.shipping._nav')

<div class="toolbar mb-2">
    <div class="toolbar-title">{{ $carrier->exists ? 'Sửa đơn vị' : 'Thêm đơn vị vận chuyển' }}</div>
    <a href="{{ route('admin.shipping.carriers.index') }}" class="btn btn-outline btn-sm">← Danh sách</a>
</div>

<div class="grid md:grid-cols-2 gap-3">
    <div class="card p-4">
        <form method="post"
            action="{{ $carrier->exists ? route('admin.shipping.carriers.update',$carrier) : route('admin.shipping.carriers.store') }}">
            @csrf @if($carrier->exists) @method('PUT') @endif

            <div class="mb-3">
                <label class="label">Tên đơn vị</label>
                <input name="name" class="form-control" value="{{ old('name',$carrier->name) }}" required>
            </div>

            <div class="mb-3">
                <label class="label">Mã code (ví dụ: ghn, ghtk, viettel, ems)</label>
                <input name="code" class="form-control" value="{{ old('code',$carrier->code) }}"
                    {{ $carrier->exists ? 'readonly' : '' }} required>
                <div class="text-xs text-slate-500 mt-1">Mã dùng map với biểu phí/API, không trùng nhau.</div>
            </div>

            <div class="mb-3">
                <label class="label">Logo (đường dẫn storage hoặc URL)</label>
                <input name="logo" class="form-control" value="{{ old('logo',$carrier->logo) }}">
                @if($carrier->logo)
                @php $src = $carrier->logo; if($src && !preg_match('/^https?:\/\//',$src)) $src = asset('storage/'.$src); @endphp
                <img src="{{ $src }}" alt="logo" class="h-10 mt-2">
                @endif
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="label">Thứ tự hiển thị</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order',$carrier->sort_order) }}">
                </div>
                <div class="flex items-end gap-4">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" class="form-check" name="supports_cod" value="1"
                            @checked(old('supports_cod',(bool)$carrier->supports_cod))>
                        <span>Hỗ trợ COD</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" class="form-check" name="enabled" value="1"
                            @checked(old('enabled',(bool)$carrier->enabled))>
                        <span>Bật</span>
                    </label>
                </div>
            </div>

            <div class="mb-4">
                <label class="label">Cấu hình (JSON tuỳ chọn)</label>
                @php
                $conf = $carrier->config;
                if (is_array($conf) || is_object($conf)) $conf = json_encode($conf, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
                @endphp
                <textarea name="config" rows="4" class="form-control"
                    placeholder='{"api_key":"...","mode":"sandbox"}'>{{ old('config',$conf) }}</textarea>
            </div>

            <div class="flex items-center gap-2">
                <button class="btn btn-primary">{{ $carrier->exists ? 'Lưu thay đổi' : 'Tạo mới' }}</button>
                <a class="btn btn-outline" href="{{ route('admin.shipping.carriers.index') }}">Huỷ</a>
            </div>
        </form>
    </div>

    <div class="card p-4">
        <div class="text-sm text-slate-600">
            <div class="font-semibold mb-2">Gợi ý cấu hình</div>
            <ul class="list-disc pl-5 space-y-1">
                <li><b>code</b> đề nghị: <code>ghn</code>, <code>ghtk</code>, <code>viettel</code>, <code>ems</code>.</li>
                <li><b>logo</b> có thể là URL ngoài hoặc đường dẫn đã lưu trong <code>storage/</code>.</li>
                <li><b>config</b> dùng để lưu khoá API, mode… dạng JSON.</li>
            </ul>
        </div>
    </div>
</div>
@endsection