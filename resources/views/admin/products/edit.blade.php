@extends('admin.layouts.app')
@section('title','Sửa sản phẩm')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif

@if($errors->any())
<div class="alert alert-danger mb-3" data-auto-dismiss="3000">
    <b>Lỗi:</b>
    <ul class="list-disc pl-5 mt-1">
        @foreach($errors->all() as $msg)
        <li>{{ $msg }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="post" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="space-y-4">
    @csrf @method('PUT')

    <div class="card p-3">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="label">Tên sản phẩm</label>
                <input name="name" value="{{ old('name', $product->name) }}" class="form-control" required>
            </div>

            <div>
                <label class="label">Slug</label>
                <input name="slug" value="{{ old('slug', $product->slug) }}" class="form-control">
            </div>

            <div>
                <label class="label">Danh mục</label>
                <select name="category_id" id="catSelect" class="form-control">
                    <option value="">-- Chọn --</option>
                    @foreach($categoryGroups as $parentName => $children)
                    <optgroup label="{{ $parentName }}">
                        @foreach($children as $c)
                        <option value="{{ $c['id'] }}" @selected(old('category_id', $product->category_id) == $c['id'])>
                            {{ $c['name'] }}
                        </option>
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
                    <option value="{{ $b->id }}" @selected(old('brand_id', $product->brand_id) == $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label">Ảnh đại diện</label>
                <input type="file" name="image" class="form-control">
                <div class="help mt-1">Ảnh hiện tại:</div>
                <img class="mt-1 w-24 h-24 rounded object-cover"
                    src="{{ $product->image ? asset('storage/'.$product->image) : 'https://placehold.co/120x120?text=IMG' }}"
                    alt="thumb">
            </div>

            <div class="md:col-span-2">
                <label class="label">Mô tả</label>
                <textarea name="description" rows="4" class="form-control">{{ old('description', $product->description) }}</textarea>
            </div>
        </div>
    </div>

    {{-- ================== BIẾN THỂ & KHO ================== --}}
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
            @php
            $oldVars = old('variants'); // nếu submit lỗi sẽ có mảng này
            @endphp

            @if(is_array($oldVars))
            {{-- Render lại theo old() --}}
            @foreach($oldVars as $i => $v)
            <div class="variant-row">
                @if(!empty($v['id']))
                <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $v['id'] }}">
                @endif
                <input name="variants[{{ $i }}][name]" class="form-control" placeholder="VD: 30ml" value="{{ $v['name'] ?? '' }}">
                <input name="variants[{{ $i }}][sku]" class="form-control" placeholder="SKU" value="{{ $v['sku'] ?? '' }}">
                <input name="variants[{{ $i }}][price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá"
                    value="{{ $v['price'] ?? '' }}">
                <input name="variants[{{ $i }}][compare_at_price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá gốc"
                    value="{{ $v['compare_at_price'] ?? '' }}">
                <input name="variants[{{ $i }}][qty_in_stock]" class="form-control" type="number" min="0" placeholder="Tồn"
                    value="{{ $v['qty_in_stock'] ?? 0 }}">
                <div class="row-actions">
                    <input name="variants[{{ $i }}][low_stock_threshold]" class="form-control" type="number" min="0" placeholder="Cảnh báo"
                        value="{{ $v['low_stock_threshold'] ?? 0 }}">
                    <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)">X</button>
                </div>
            </div>
            @endforeach
            @else
            {{-- Render từ DB --}}
            @forelse($product->variants as $idx => $v)
            @php $inv = $v->inventory; @endphp
            <div class="variant-row">
                <input type="hidden" name="variants[{{ $idx }}][id]" value="{{ $v->id }}">
                <input name="variants[{{ $idx }}][name]" value="{{ $v->name }}" class="form-control" placeholder="VD: 30ml">
                <input name="variants[{{ $idx }}][sku]" value="{{ $v->sku }}" class="form-control" placeholder="SKU">
                <input name="variants[{{ $idx }}][price]" value="{{ $v->price }}" class="form-control" type="number" step="0.01" min="0" placeholder="Giá">
                <input name="variants[{{ $idx }}][compare_at_price]" value="{{ $v->compare_at_price }}" class="form-control" type="number" step="0.01" min="0" placeholder="Giá gốc">
                <input name="variants[{{ $idx }}][qty_in_stock]" value="{{ $inv->qty_in_stock ?? 0 }}" class="form-control" type="number" min="0" placeholder="Tồn">
                <div class="row-actions">
                    <input name="variants[{{ $idx }}][low_stock_threshold]" value="{{ $inv->low_stock_threshold ?? 0 }}" class="form-control" type="number" min="0" placeholder="Cảnh báo">
                    <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)">X</button>
                </div>
            </div>
            @empty
            {{-- Không có biến thể thì render 1 hàng rỗng để thêm mới --}}
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
            @endforelse
            @endif
        </div>
    </div>

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline">← Danh sách</a>
        <button type="submit" class="btn btn-primary !text-black">Lưu thay đổi</button>
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
        btn.closest('.variant-row')?.remove();
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