@extends('admin.layouts.app')
@section('title','Thêm danh mục')

@section('content')
@if($errors->any())
<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

<form method="post" action="{{ route('admin.categories.store') }}" class="space-y-3">
    @csrf
    <div class="card p-3">
        <div class="form-row">
            <div>
                <label class="label">Tên danh mục</label>
                <input name="name" value="{{ old('name') }}" class="form-control" required>
            </div>
            <div>
                <label class="label">Slug (để trống sẽ tự tạo)</label>
                <input name="slug" value="{{ old('slug') }}" class="form-control">
            </div>
            <div>
                <label class="label">Danh mục cha</label>
                <select name="parent_id" id="parentSelect" class="form-control">
                    <option value="">— Không —</option>
                    @foreach($parents as $id => $text)
                    <option value="{{ $id }}" @selected(old('parent_id')==$id)>{{ $text }}</option>
                    @endforeach
                </select>
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
        </div>
    </div>
    <div class="flex justify-between">
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline">← Danh sách</a>
        <button class="btn btn-primary">Lưu</button>
    </div>
</form>

@push('scripts')
<script>
    new TomSelect('#parentSelect', {
        create: false,
        maxOptions: 1000
    });
</script>
@endpush
@endsection