@extends('admin.layouts.app')
@section('title', ($zone->exists ? 'Sửa khu vực' : 'Thêm khu vực'))

@section('content')
@if(session('ok')) <div class="alert alert-success" data-auto-dismiss="3000">{{ session('ok') }}</div> @endif
@if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif

@include('admin.shipping._nav') {{-- dùng nav gốc của bạn --}}

<div class="card p-4 max-w-2xl">
    <form method="post" action="{{ $zone->exists ? route('admin.shipping.zones.update',$zone) : route('admin.shipping.zones.store') }}">
        @csrf
        @if($zone->exists) @method('PUT') @endif

        <div class="mb-3">
            <label class="label">Tên khu vực / tuyến</label>
            <input name="name" class="form-control" value="{{ old('name',$zone->name) }}" required>
        </div>

        <div class="mb-3">
            <label class="label">
                Mã tỉnh (GSO) – mảng JSON
                <span class="text-xs text-slate-500">(ví dụ: ["01","79"])</span>
            </label>
            @php
            $prov = $zone->province_codes;
            if (is_array($prov)) $prov = json_encode($prov, JSON_UNESCAPED_UNICODE);
            if (!is_string($prov) || $prov==='') $prov = '[]';
            @endphp
            <textarea name="province_codes" rows="3" class="form-control" placeholder='["01","79"]'>{{ old('province_codes', $prov) }}</textarea>
        </div>

        <div class="mb-4">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="enabled" value="1" class="form-check"
                    @checked(old('enabled', (bool)$zone->enabled))>
                <span>Bật</span>
            </label>
        </div>

        <div class="flex items-center gap-2">
            <button class="btn btn-primary">{{ $zone->exists ? 'Lưu thay đổi' : 'Tạo mới' }}</button>
            <a class="btn btn-outline" href="{{ route('admin.shipping.zones.index') }}">← Danh sách</a>
        </div>
    </form>
</div>
@endsection