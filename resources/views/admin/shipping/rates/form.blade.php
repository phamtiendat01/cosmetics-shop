@extends('admin.layouts.app')
@section('title', $rate->exists ? 'Sửa biểu phí' : 'Thêm biểu phí')

@section('content')
@if(session('ok')) <div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div> @endif
@if($errors->any()) <div class="alert alert-danger mb-3">{{ $errors->first() }}</div> @endif

@include('admin.shipping._nav')

<div class="toolbar mb-2">
    <div class="toolbar-title">{{ $rate->exists ? 'Sửa biểu phí' : 'Thêm biểu phí' }}</div>
    <a href="{{ route('admin.shipping.rates.index') }}" class="btn btn-outline btn-sm">← Danh sách</a>
</div>

<div class="grid md:grid-cols-2 gap-3">
    <div class="card p-4">
        <form method="post"
            action="{{ $rate->exists ? route('admin.shipping.rates.update',$rate) : route('admin.shipping.rates.store') }}">
            @csrf @if($rate->exists) @method('PUT') @endif

            <div class="grid md:grid-cols-2 gap-3">
                <div>
                    <label class="label">Đơn vị</label>
                    <select name="carrier_id" class="form-control" required>
                        <option value="">— Chọn đơn vị —</option>
                        @foreach($carrierOptions as $id=>$name)
                        <option value="{{ $id }}" @selected(old('carrier_id',$rate->carrier_id)==$id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Khu vực / Tuyến</label>
                    <select name="zone_id" class="form-control" required>
                        <option value="">— Chọn khu vực —</option>
                        @foreach($zoneOptions as $id=>$name)
                        <option value="{{ $id }}" @selected(old('zone_id',$rate->zone_id)==$id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <label class="label">Tên biểu phí</label>
                <input name="name" class="form-control" value="{{ old('name',$rate->name) }}" required>
            </div>

            <div class="grid md:grid-cols-2 gap-3 mt-3">
                <div>
                    <label class="label">Khối lượng tối thiểu (kg)</label>
                    <input type="number" name="min_weight" class="form-control"
                        value="{{ old('min_weight',$rate->min_weight) }}" min="0" step="1">
                </div>
                <div>
                    <label class="label">Khối lượng tối đa (kg)</label>
                    <input type="number" name="max_weight" class="form-control"
                        value="{{ old('max_weight',$rate->max_weight) }}" min="0" step="1">
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-3 mt-3">
                <div>
                    <label class="label">Áp dụng từ giá trị đơn (₫)</label>
                    <input type="number" name="min_total" class="form-control"
                        value="{{ old('min_total',$rate->min_total) }}" min="0" step="1000" placeholder="VD: 500000">
                </div>
                <div>
                    <label class="label">Tối đa giá trị đơn (₫)</label>
                    <input type="number" name="max_total" class="form-control"
                        value="{{ old('max_total',$rate->max_total) }}" min="0" step="1000">
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-3 mt-3">
                <div>
                    <label class="label">Phí 1kg đầu (₫)</label>
                    <input type="number" name="base_fee" class="form-control"
                        value="{{ old('base_fee',$rate->base_fee) }}" min="0" step="1000" required>
                </div>
                <div>
                    <label class="label">Phí mỗi kg thêm (₫)</label>
                    <input type="number" name="per_kg_fee" class="form-control"
                        value="{{ old('per_kg_fee',$rate->per_kg_fee) }}" min="0" step="1000" required>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-3 mt-3">
                <div>
                    <label class="label">ETD tối thiểu (ngày)</label>
                    <input type="number" name="etd_min_days" class="form-control"
                        value="{{ old('etd_min_days',$rate->etd_min_days) }}" min="0" step="1">
                </div>
                <div>
                    <label class="label">ETD tối đa (ngày)</label>
                    <input type="number" name="etd_max_days" class="form-control"
                        value="{{ old('etd_max_days',$rate->etd_max_days) }}" min="0" step="1">
                </div>
            </div>

            <div class="mt-3">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" class="form-check" name="enabled" value="1"
                        @checked(old('enabled', (bool)$rate->enabled))>
                    <span>Bật biểu phí này</span>
                </label>
            </div>

            <div class="mt-4 flex items-center gap-2">
                <button class="btn btn-primary">{{ $rate->exists ? 'Lưu thay đổi' : 'Tạo mới' }}</button>
                <a class="btn btn-outline" href="{{ route('admin.shipping.rates.index') }}">Huỷ</a>
            </div>
        </form>
    </div>

    <div class="card p-4">
        <div class="text-sm text-slate-600">
            <div class="font-semibold mb-2">Gợi ý & Quy ước</div>
            <ul class="list-disc pl-5 space-y-1">
                <li>Bỏ trống <b>min/max weight</b> hay <b>min/max total</b> nếu không giới hạn.</li>
                <li>Phí được tính: <code>base_fee</code> cho 1kg đầu + <code>per_kg_fee</code> × (kg làm tròn lên - 1).</li>
                <li>ETD là khoảng ngày giao dự kiến, để hiển thị cho CSKH.</li>
            </ul>
        </div>
    </div>
</div>
@endsection