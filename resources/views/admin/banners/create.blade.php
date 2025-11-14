@extends('admin.layouts.app')
@section('title','Thêm banner')

@section('content')
@if($errors->any()) <div class="alert alert-danger mb-3">{{ $errors->first() }}</div> @endif

<form method="post" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="space-y-3">
    @csrf
    <div class="card p-3">
        <div class="form-row-3">
            <div>
                <label class="label">Tiêu đề</label>
                <input name="title" value="{{ old('title') }}" class="form-control" required>
            </div>
            <div>
                <label class="label">Vị trí</label>
                <select name="position" class="form-control">
                    @foreach($positions as $k=>$v)
                    <option value="{{ $k }}" @selected(old('position')===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Thiết bị</label>
                <select name="device" class="form-control">
                    @foreach($devices as $k=>$v)
                    <option value="{{ $k }}" @selected(old('device','all')===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label">Ảnh (JPG/PNG ≤ 4MB)</label>
                <input type="file" name="image" class="form-control" required>
            </div>
            <div>
                <label class="label">Ảnh Mobile (tuỳ chọn)</label>
                <input type="file" name="mobile_image" class="form-control">
            </div>
            <div>
                <label class="label">Liên kết khi bấm</label>
                <input name="url" class="form-control" value="{{ old('url') }}" placeholder="https://...">
                <div class="help">Để trống nếu chỉ hiển thị hình.</div>
            </div>

            <div>
                <label class="label">Mở tab mới?</label>
                <select name="open_in_new_tab" class="form-control">
                    <option value="0" @selected(old('open_in_new_tab','0')==='0' )>Không</option>
                    <option value="1" @selected(old('open_in_new_tab')==='1' )>Có</option>
                </select>
            </div>
            <div>
                <label class="label">Thứ tự</label>
                <input name="sort_order" type="number" min="0" class="form-control" value="{{ old('sort_order',0) }}">
            </div>
            <div>
                <label class="label">Trạng thái</label>
                <select name="is_active" class="form-control">
                    <option value="1" @selected(old('is_active','1')==='1' )>Bật</option>
                    <option value="0" @selected(old('is_active')==='0' )>Tắt</option>
                </select>
            </div>

            <div>
                <label class="label">Bắt đầu</label>
                <input name="starts_at" id="startsAt" value="{{ old('starts_at') }}" class="form-control" placeholder="YYYY-MM-DD HH:mm">
            </div>
            <div>
                <label class="label">Kết thúc</label>
                <input name="ends_at" id="endsAt" value="{{ old('ends_at') }}" class="form-control" placeholder="YYYY-MM-DD HH:mm">
            </div>
        </div>
    </div>

    <div class="flex justify-between">
        <a href="{{ route('admin.banners.index') }}" class="btn btn-outline">← Danh sách</a>
        <button class="btn btn-primary">Lưu</button>
    </div>
</form>

@push('scripts')
<script>
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