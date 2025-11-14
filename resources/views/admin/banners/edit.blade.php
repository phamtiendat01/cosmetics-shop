@extends('admin.layouts.app')
@section('title','Sửa banner')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

{{-- ========== FORM UPDATE (KHÔNG LỒNG FORM KHÁC) ========== --}}
<form id="bannerUpdateForm"
    method="post"
    action="{{ route('admin.banners.update', $banner) }}"
    enctype="multipart/form-data"
    class="space-y-3">
    @csrf
    @method('PUT')

    <div class="card p-3">
        <div class="form-row-3">
            {{-- Tiêu đề --}}
            <div>
                <label class="label">Tiêu đề</label>
                <input name="title"
                    value="{{ old('title', $banner->title) }}"
                    class="form-control"
                    required>
            </div>

            {{-- Vị trí --}}
            <div>
                <label class="label">Vị trí</label>
                <select name="position" class="form-control">
                    @foreach($positions as $k => $v)
                    <option value="{{ $k }}" @selected(old('position', $banner->position) === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Thiết bị --}}
            <div>
                <label class="label">Thiết bị</label>
                <select name="device" class="form-control">
                    @foreach($devices as $k => $v)
                    <option value="{{ $k }}" @selected(old('device', $banner->device) === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Ảnh desktop --}}
            <div>
                <label class="label">Ảnh</label>
                <input type="file" name="image" class="form-control">
                <div class="help mt-1">Ảnh hiện tại:</div>
                <img class="mt-1 w-28 h-16 object-cover rounded"
                    src="{{ $banner->image ? url($banner->image) : 'https://placehold.co/120x64?text=—' }}"
                    alt="">
                <div class="help mt-1 text-xs text-slate-500">
                    Gợi ý: 1600×576px (tối ưu desktop).
                </div>
            </div>

            {{-- Ảnh mobile --}}
            <div>
                <label class="label">Ảnh Mobile</label>
                <input type="file" name="mobile_image" class="form-control">
                <div class="help mt-1">Hiện tại:</div>
                <img class="mt-1 w-28 h-16 object-cover rounded"
                    src="{{ $banner->mobile_image ? url($banner->mobile_image) : 'https://placehold.co/120x64?text=—' }}"
                    alt="">
                <div class="help mt-1 text-xs text-slate-500">
                    Gợi ý: 750×600px (tối ưu mobile).
                </div>
            </div>

            {{-- Link đích --}}
            <div>
                <label class="label">Liên kết</label>
                <input name="url"
                    class="form-control"
                    value="{{ old('url', $banner->url) }}"
                    placeholder="https://...">
            </div>

            {{-- Mở tab mới --}}
            <div>
                <label class="label">Mở tab mới?</label>
                <select name="open_in_new_tab" class="form-control">
                    <option value="0" @selected((int) old('open_in_new_tab', (int) $banner->open_in_new_tab) === 0)>Không</option>
                    <option value="1" @selected((int) old('open_in_new_tab', (int) $banner->open_in_new_tab) === 1)>Có</option>
                </select>
            </div>

            {{-- Thứ tự --}}
            <div>
                <label class="label">Thứ tự</label>
                <input name="sort_order"
                    type="number"
                    min="0"
                    class="form-control"
                    value="{{ old('sort_order', $banner->sort_order) }}">
            </div>

            {{-- Trạng thái --}}
            <div>
                <label class="label">Trạng thái</label>
                <select name="is_active" class="form-control">
                    <option value="1" @selected((int) old('is_active', (int) $banner->is_active) === 1)>Bật</option>
                    <option value="0" @selected((int) old('is_active', (int) $banner->is_active) === 0)>Tắt</option>
                </select>
            </div>

            {{-- Bắt đầu --}}
            <div>
                <label class="label">Bắt đầu</label>
                <input name="starts_at"
                    id="startsAt"
                    value="{{ old('starts_at', optional($banner->starts_at)->format('Y-m-d H:i')) }}"
                    class="form-control"
                    placeholder="YYYY-MM-DD HH:mm">
            </div>

            {{-- Kết thúc --}}
            <div>
                <label class="label">Kết thúc</label>
                <input name="ends_at"
                    id="endsAt"
                    value="{{ old('ends_at', optional($banner->ends_at)->format('Y-m-d H:i')) }}"
                    class="form-control"
                    placeholder="YYYY-MM-DD HH:mm">
            </div>
        </div>
    </div>
</form>

{{-- ========== HÀNG ACTION: KHÔNG LỒNG FORM ========== --}}
<div class="flex justify-between mt-3">
    <a href="{{ route('admin.banners.index') }}" class="btn btn-outline">← Danh sách</a>

    <div class="flex gap-2">
        {{-- Nút submit cho form cập nhật ở trên --}}
        <button form="bannerUpdateForm" class="btn btn-primary">Lưu thay đổi</button>

        {{-- Form XOÁ tách riêng, không lồng trong form cập nhật --}}
        <form method="post"
            action="{{ route('admin.banners.destroy', $banner) }}"
            onsubmit="return confirm('Xoá banner này?')">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger">Xoá</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Tự ẩn alert
    document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
        setTimeout(() => {
            el.classList.add('alert--hide');
            setTimeout(() => el.remove(), 350);
        }, +el.dataset.autoDismiss || 3000);
    });

    // Datetime picker (nếu đã nạp flatpickr ở layout)
    if (window.flatpickr) {
        flatpickr('#startsAt', {
            enableTime: true,
            dateFormat: 'Y-m-d H:i'
        });
        flatpickr('#endsAt', {
            enableTime: true,
            dateFormat: 'Y-m-d H:i'
        });
    }
</script>
@endpush
@endsection