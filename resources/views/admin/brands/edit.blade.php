@extends('admin.layouts.app')
@section('title','Sửa thương hiệu')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

<form method="post" action="{{ route('admin.brands.update',$brand) }}" enctype="multipart/form-data" class="space-y-3">
    @csrf @method('PUT')
    <div class="card p-3">
        <div class="form-row">
            <div>
                <label class="label">Tên thương hiệu</label>
                <input name="name" value="{{ old('name',$brand->name) }}" class="form-control" required>
            </div>
            <div>
                <label class="label">Slug</label>
                <input name="slug" value="{{ old('slug',$brand->slug) }}" class="form-control">
            </div>
            <div>
                <label class="label">Website</label>
                <input name="website" value={{ old('website',$brand->website) ? '"'.old('website',$brand->website).'"' : '""' }} class="form-control" placeholder="https://...">
            </div>
            <div>
                <label class="label">Thứ tự</label>
                <input type="number" name="sort_order" value="{{ old('sort_order',$brand->sort_order) }}" class="form-control" min="0">
            </div>
            <div>
                <label class="label">Hiển thị</label>
                <select name="is_active" class="form-control">
                    <option value="1" @selected(old('is_active',(string)$brand->is_active)==='1')>Hiển thị</option>
                    <option value="0" @selected(old('is_active',(string)$brand->is_active)==='0')>Ẩn</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="label">Logo</label>
                <input type="file" name="logo" id="logoInput" class="form-control">
                <div class="help mt-1">Logo hiện tại / xem trước:</div>
                <img id="logoPreview" class="mt-1 w-20 h-20 rounded object-cover"
                    src="{{ $brand->logo ? asset('storage/'.$brand->logo) : 'https://placehold.co/80x80?text=Logo' }}" alt="">
            </div>
        </div>
        <div class="divider"></div>
        <div class="text-xs text-slate-500">
            Tạo lúc: {{ $brand->created_at?->format('d/m/Y H:i') ?? '—' }} —
            Cập nhật: {{ $brand->updated_at?->format('d/m/Y H:i') ?? '—' }}
        </div>
    </div>

    <div class="flex justify-between">
        <a href="{{ route('admin.brands.index') }}" class="btn btn-outline">← Danh sách</a>
        <div class="flex gap-2">
            <button class="btn btn-primary">Lưu thay đổi</button>
            <button type="button" class="btn btn-danger" data-confirm-delete data-url="{{ route('admin.brands.destroy',$brand) }}">Xoá</button>
        </div>
    </div>
</form>

{{-- Modal xác nhận xoá --}}
<div id="confirmModal" class="modal hidden">
    <div class="modal-card">
        <div class="p-4 border-b">
            <div class="font-semibold">Xác nhận xoá</div>
            <div class="text-sm text-slate-500">Bạn chắc chắn muốn xoá thương hiệu này?</div>
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
    const input = document.getElementById('logoInput');
    const img = document.getElementById('logoPreview');
    input?.addEventListener('change', e => {
        const f = e.target.files?.[0];
        if (!f) return;
        const url = URL.createObjectURL(f);
        img.src = url;
    });

    // modal confirm
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