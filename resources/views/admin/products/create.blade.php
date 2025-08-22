@extends('admin.layouts.app')
@section('title','Thêm sản phẩm')

@section('content')
@if($errors->any())
<div class="alert alert-danger mb-3">
    <b>Lỗi:</b> {{ $errors->first() }}
</div>
@endif

<div class="toolbar">
    <div class="toolbar-title">Thêm sản phẩm</div>
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline btn-sm">Quay lại</a>
</div>

<form method="post" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="space-y-4">
    @csrf

    <div class="card p-3">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="label">Tên sản phẩm</label>
                <input name="name" value="{{ old('name') }}" class="form-control" required>
            </div>
            <div>
                <label class="label">Slug (để trống sẽ tự tạo)</label>
                <input name="slug" value="{{ old('slug') }}" class="form-control">
            </div>

            <div>
                <label class="label">Danh mục</label>
                <select name="category_id" id="catSelect" class="form-control">
                    <option value="">-- Chọn --</option>
                    @foreach($categoryGroups as $parentName => $children)
                    <optgroup label="{{ $parentName }}">
                        @foreach($children as $c)
                        <option value="{{ $c['id'] }}" @selected(old('category_id')==$c['id'])>{{ $c['name'] }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
                <div class="help mt-1 text-xs text-slate-500">Chỉ liệt kê danh mục <b>con</b>.</div>
            </div>

            <div>
                <label class="label">Thương hiệu</label>
                <select name="brand_id" id="brandSelect" class="form-control">
                    <option value="">-- Chọn --</option>
                    @foreach($brands as $b)
                    <option value="{{ $b->id }}" @selected(old('brand_id')==$b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label">Ảnh đại diện</label>
                <input type="file" name="image" class="form-control">
                <div class="help mt-1">JPG/PNG ≤ 2MB</div>
            </div>

            <div class="md:col-span-2">
                <label class="label">Mô tả</label>
                <textarea name="description" rows="4" class="form-control">{{ old('description') }}</textarea>
            </div>
        </div>
    </div>

    {{-- ============ BIẾN THỂ & KHO ============ --}}
    <div class="card p-3">
        <div class="toolbar mb-2">
            <div class="font-semibold text-sm">Biến thể & Giá</div>
            <button type="button" onclick="addVariantRow()" class="btn btn-outline btn-sm">+ Thêm biến thể</button>
        </div>

        <div class="variants-header">
            <div>Tên biến thể</div>
            <div>SKU</div>
            <div>Giá</div>
            <div>Giá gốc</div>
            <div>Tồn kho</div>
            <div>Cảnh báo</div>
        </div>

        <div id="variantList" class="space-y-2">
            {{-- Nếu old() rỗng, render 1 dòng mặc định --}}
            @php $oldVars = old('variants', []); @endphp
            @if(empty($oldVars))
            <div class="variant-row">
                <input name="variants[0][name]" class="form-control" placeholder="VD: 30ml">
                <input name="variants[0][sku]" class="form-control" placeholder="SKU">
                <input name="variants[0][price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá">
                <input name="variants[0][compare_at_price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá gốc">
                <input name="variants[0][qty_in_stock]" class="form-control" type="number" min="0" placeholder="Tồn">
                <div class="row-actions">
                    <input name="variants[0][low_stock_threshold]" class="form-control" type="number" min="0" placeholder="Cảnh báo">
                    <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)">X</button>
                </div>
            </div>
            @else
            @foreach($oldVars as $i => $v)
            <div class="variant-row">
                <input name="variants[{{ $i }}][name]" class="form-control" placeholder="VD: 30ml" value="{{ $v['name'] ?? '' }}">
                <input name="variants[{{ $i }}][sku]" class="form-control" placeholder="SKU" value="{{ $v['sku'] ?? '' }}">
                <input name="variants[{{ $i }}][price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá" value="{{ $v['price'] ?? '' }}">
                <input name="variants[{{ $i }}][compare_at_price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá gốc" value="{{ $v['compare_at_price'] ?? '' }}">
                <input name="variants[{{ $i }}][qty_in_stock]" class="form-control" type="number" min="0" placeholder="Tồn" value="{{ $v['qty_in_stock'] ?? 0 }}">
                <div class="row-actions">
                    <input name="variants[{{ $i }}][low_stock_threshold]" class="form-control" type="number" min="0" placeholder="Cảnh báo" value="{{ $v['low_stock_threshold'] ?? 0 }}">
                    <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)">X</button>
                </div>
            </div>
            @endforeach
            @endif
        </div>

        @error('variants')<div class="text-rose-600 text-sm mt-2">{{ $message }}</div>@enderror
    </div>

    <div class="flex items-center justify-end">
        <button class="btn btn-primary">Lưu</button>
    </div>
</form>

@push('scripts')
<script>
    if (document.getElementById('catSelect')) new TomSelect('#catSelect', {
        create: false,
        maxOptions: 500
    });
    if (document.getElementById('brandSelect')) new TomSelect('#brandSelect', {
        create: false,
        maxOptions: 500
    });

    function removeRow(btn) {
        const row = btn.closest('.variant-row');
        row?.remove();
    }

    function addVariantRow() {
        const list = document.getElementById('variantList');
        const idx = list.querySelectorAll('.variant-row').length;
        const row = document.createElement('div');
        row.className = 'variant-row';
        row.innerHTML = `
      <input name="variants[${idx}][name]"  class="form-control" placeholder="VD: 30ml">
      <input name="variants[${idx}][sku]"   class="form-control" placeholder="SKU">
      <input name="variants[${idx}][price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá">
      <input name="variants[${idx}][compare_at_price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá gốc">
      <input name="variants[${idx}][qty_in_stock]" class="form-control" type="number" min="0" placeholder="Tồn">
      <div class="row-actions">
        <input name="variants[${idx}][low_stock_threshold]" class="form-control" type="number" min="0" placeholder="Cảnh báo">
        <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)">X</button>
      </div>`;
        list.appendChild(row);
    }
</script>
@endpush
@endsection