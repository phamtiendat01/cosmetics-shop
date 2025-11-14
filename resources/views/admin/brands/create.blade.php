@extends('admin.layouts.app')
@section('title','Thêm thương hiệu')

@section('content')
@if($errors->any())
<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

<form method="post" action="{{ route('admin.brands.store') }}" enctype="multipart/form-data" class="space-y-3">
    @csrf
    <div class="card p-3">
        <div class="form-row">
            <div>
                <label class="label">Tên thương hiệu</label>
                <input name="name" value="{{ old('name') }}" class="form-control" required>
            </div>
            <div>
                <label class="label">Slug (để trống sẽ tự tạo)</label>
                <input name="slug" value="{{ old('slug') }}" class="form-control">
            </div>
            <div>
                <label class="label">Website</label>
                <input name="website" value="{{ old('website') }}" class="form-control" placeholder="https://...">
            </div>
            <div>
                <label class="label">Thứ tự</label>
                <input type="number" name="sort_order" value="{{ old('sort_order',0) }}" class="form-control" min="0">
            </div>
            <div>
                <label class="label">Hiển thị</label>
                <select name="is_active" class="form-control">
                    <option value="1" @selected(old('is_active','1')==='1' )>Hiển thị</option>
                    <option value="0" @selected(old('is_active')==='0' )>Ẩn</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="label">Logo</label>
                <input type="file" name="logo" id="logoInput" class="form-control">
                <div class="help mt-1">Xem trước:</div>
                <img id="logoPreview" class="mt-1 w-20 h-20 rounded object-cover" src="https://placehold.co/80x80?text=Logo" alt="">
            </div>
        </div>
    </div>
    <div class="flex justify-between">
        <a href="{{ route('admin.brands.index') }}" class="btn btn-outline">← Danh sách</a>
        <button class="btn btn-primary">Lưu</button>
    </div>
</form>

@push('scripts')
<script>
    const input = document.getElementById('logoInput');
    const img = document.getElementById('logoPreview');
    input?.addEventListener('change', e => {
        const f = e.target.files?.[0];
        if (!f) return;
        const url = URL.createObjectURL(f);
        img.src = url;
    });
</script>
@endpush
@endsection