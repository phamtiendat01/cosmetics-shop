@extends('admin.layouts.app')
@section('title','Sửa danh mục')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

<form method="post" action="{{ route('admin.categories.update',$category) }}" class="space-y-3">
    @csrf @method('PUT')
    <div class="card p-3">
        <div class="form-row">
            <div>
                <label class="label">Tên danh mục</label>
                <input name="name" value="{{ old('name',$category->name) }}" class="form-control" required>
            </div>
            <div>
                <label class="label">Slug</label>
                <input name="slug" value="{{ old('slug',$category->slug) }}" class="form-control">
            </div>
            <div>
                <label class="label">Danh mục cha</label>
                <select name="parent_id" id="parentSelect" class="form-control">
                    <option value="">— Không —</option>
                    @foreach($parents as $id => $text)
                    <option value="{{ $id }}" @selected(old('parent_id',$category->parent_id)==$id)>{{ $text }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Thứ tự</label>
                <input type="number" name="sort_order" value="{{ old('sort_order',$category->sort_order) }}" class="form-control" min="0">
            </div>
            <div>
                <label class="label">Hiển thị</label>
                <select name="is_active" class="form-control">
                    <option value="1" @selected(old('is_active',(string)$category->is_active)==='1')>Hiển thị</option>
                    <option value="0" @selected(old('is_active',(string)$category->is_active)==='0')>Ẩn</option>
                </select>
            </div>
        </div>
        <div class="divider"></div>
        <div class="text-xs text-slate-500">
            Tạo lúc: {{ $category->created_at?->format('d/m/Y H:i') ?? '—' }} —
            Cập nhật: {{ $category->updated_at?->format('d/m/Y H:i') ?? '—' }}
        </div>
    </div>

    <div class="flex justify-between">
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline">← Danh sách</a>
        <div class="flex gap-2">
            <button class="btn btn-primary">Lưu thay đổi</button>
            <button type="button" class="btn btn-danger" data-confirm-delete data-url="{{ route('admin.categories.destroy',$category) }}">Xoá</button>
        </div>
    </div>
</form>

{{-- Modal xác nhận xoá --}}
<div id="confirmModal" class="modal hidden">
    <div class="modal-card">
        <div class="p-4 border-b">
            <div class="font-semibold">Xác nhận xoá</div>
            <div class="text-sm text-slate-500">Bạn chắc chắn muốn xoá danh mục này?</div>
        </div>
        <div class="p-4 flex justify-end gap-2">
            <button class="btn btn-outline btn-sm" data-close-modal>Huỷ</button>
            <form id="confirmForm" method="post">
                @csrf @method('DELETE')
                <button class="btn btn-danger btn-sm">Xoá</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    new TomSelect('#parentSelect', {
        create: false,
        maxOptions: 1000
    });
    // modal
    const modal = document.getElementById('confirmModal');
    const form = document.getElementById('confirmForm');
    document.querySelectorAll('[data-confirm-delete]').forEach(btn => {
        btn.addEventListener('click', () => {
            form.action = btn.dataset.url;
            modal.classList.remove('hidden');
        });
    });
    document.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', () => modal.classList.add('hidden')));
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.classList.add('hidden');
    });
</script>
@endpush
@endsection