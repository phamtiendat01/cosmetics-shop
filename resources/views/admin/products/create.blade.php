@extends('admin.layouts.app')
@section('title','Thêm sản phẩm')

@section('content')
@if($errors->any())
<div class="alert alert-danger mb-3"><b>Lỗi:</b> {{ $errors->first() }}</div>
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
                <input type="file" name="thumbnail" class="form-control">
                <div class="help mt-1">JPG/PNG ≤ 2MB</div>
            </div>

            <div class="md:col-span-2">
                <label class="label">Mô tả ngắn</label>
                <textarea name="short_desc" rows="3" class="form-control">{{ old('short_desc') }}</textarea>
                <div class="help mt-1 text-xs text-slate-500">Hiển thị ngắn gọn ở đầu trang sản phẩm.</div>
            </div>

            <div class="md:col-span-2">
                <label class="label">Mô tả chi tiết</label>

                {{-- ✨ Card "glass" cho editor --}}
                <div class="editor-card">
                    <textarea id="long_desc" name="long_desc" rows="12" class="form-control">{{ old('long_desc') }}</textarea>
                </div>

                <div class="help mt-1 text-xs text-slate-500">
                    Gợi ý: dùng <b>Heading 2</b> cho mục lớn, <b>Heading 3</b> cho mục con; gạch đầu dòng để nội dung gọn gàng.
                </div>
            </div>

        </div>
    </div>

    {{-- ================== THÔNG TIN CHO BOT ================== --}}
    <div class="card p-3">
        <div class="toolbar mb-4">
            <div class="font-semibold text-sm flex items-center gap-2">
                <i class="fa-solid fa-robot text-rose-600"></i>
                Thông tin cho Chatbot (CosmeBot)
            </div>
            <div class="text-xs text-slate-500">Các thông tin này giúp bot tư vấn chính xác hơn cho khách hàng</div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            {{-- Loại da phù hợp --}}
            <div>
                <label class="label">Loại da phù hợp <span class="text-red-500">*</span></label>
                <div class="space-y-2">
                    @php
                    $skinTypes = [
                        'oily' => 'Da dầu',
                        'dry' => 'Da khô',
                        'combination' => 'Da hỗn hợp',
                        'sensitive' => 'Da nhạy cảm',
                        'normal' => 'Da thường',
                    ];
                    $oldSkinTypes = old('skin_types', []);
                    @endphp
                    @foreach($skinTypes as $key => $label)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="skin_types[]" value="{{ $key }}" 
                            {{ in_array($key, $oldSkinTypes) ? 'checked' : '' }}
                            class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                        <span class="text-sm">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Vấn đề da --}}
            <div>
                <label class="label">Vấn đề da giải quyết</label>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-slate-200 rounded-lg p-3">
                    @php
                    $concerns = [
                        'acne' => 'Mụn',
                        'blackheads' => 'Đầu đen',
                        'dark_spots' => 'Thâm',
                        'melasma' => 'Nám',
                        'freckles' => 'Tàn nhang',
                        'pores' => 'Lỗ chân lông',
                        'aging' => 'Lão hóa',
                        'hydration' => 'Dưỡng ẩm',
                        'sunburn' => 'Cháy nắng',
                    ];
                    $oldConcerns = old('concerns', []);
                    @endphp
                    @foreach($concerns as $key => $label)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="concerns[]" value="{{ $key }}" 
                            {{ in_array($key, $oldConcerns) ? 'checked' : '' }}
                            class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                        <span class="text-sm">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Thành phần chính --}}
            <div>
                <label class="label">Thành phần chính</label>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-slate-200 rounded-lg p-3">
                    @php
                    $ingredients = [
                        'hyaluronic_acid' => 'Hyaluronic Acid',
                        'niacinamide' => 'Niacinamide',
                        'retinol' => 'Retinol',
                        'vitamin_c' => 'Vitamin C',
                        'salicylic_acid' => 'Salicylic Acid',
                        'glycolic_acid' => 'Glycolic Acid',
                        'peptides' => 'Peptides',
                        'ceramides' => 'Ceramides',
                        'snail_mucin' => 'Snail Mucin',
                        'centella' => 'Centella Asiatica',
                        'tea_tree' => 'Tea Tree Oil',
                        'aloe_vera' => 'Aloe Vera',
                    ];
                    $oldIngredients = old('ingredients', []);
                    @endphp
                    @foreach($ingredients as $key => $label)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="ingredients[]" value="{{ $key }}" 
                            {{ in_array($key, $oldIngredients) ? 'checked' : '' }}
                            class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                        <span class="text-sm">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Công dụng --}}
            <div>
                <label class="label">Công dụng chính</label>
                <textarea name="benefits" rows="4" class="form-control" 
                    placeholder="VD: Dưỡng ẩm sâu, làm mờ thâm, se khít lỗ chân lông...">{{ old('benefits') }}</textarea>
            </div>

            {{-- Hướng dẫn sử dụng --}}
            <div>
                <label class="label">Hướng dẫn sử dụng</label>
                <textarea name="usage_instructions" rows="4" class="form-control" 
                    placeholder="VD: Thoa đều lên mặt sau khi làm sạch, dùng buổi tối...">{{ old('usage_instructions') }}</textarea>
            </div>

            {{-- Độ tuổi --}}
            <div>
                <label class="label">Độ tuổi phù hợp</label>
                <select name="age_range" class="form-control">
                    <option value="">-- Chọn --</option>
                    <option value="teen" {{ old('age_range') === 'teen' ? 'selected' : '' }}>Thiếu niên (13-18)</option>
                    <option value="adult" {{ old('age_range') === 'adult' ? 'selected' : '' }}>Người trẻ (19-35)</option>
                    <option value="mature" {{ old('age_range') === 'mature' ? 'selected' : '' }}>Trung niên (36+)</option>
                    <option value="all" {{ old('age_range') === 'all' ? 'selected' : '' }}>Mọi độ tuổi</option>
                </select>
            </div>

            {{-- Giới tính --}}
            <div>
                <label class="label">Giới tính</label>
                <select name="gender" class="form-control">
                    <option value="unisex" {{ old('gender', 'unisex') === 'unisex' ? 'selected' : '' }}>Unisex (Cả nam và nữ)</option>
                    <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Nữ</option>
                    <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Nam</option>
                </select>
            </div>

            {{-- Loại sản phẩm --}}
            <div>
                <label class="label">Loại sản phẩm</label>
                <select name="product_type" class="form-control">
                    <option value="">-- Chọn --</option>
                    <option value="serum" {{ old('product_type') === 'serum' ? 'selected' : '' }}>Serum</option>
                    <option value="cream" {{ old('product_type') === 'cream' ? 'selected' : '' }}>Kem</option>
                    <option value="toner" {{ old('product_type') === 'toner' ? 'selected' : '' }}>Toner</option>
                    <option value="cleanser" {{ old('product_type') === 'cleanser' ? 'selected' : '' }}>Sữa rửa mặt</option>
                    <option value="moisturizer" {{ old('product_type') === 'moisturizer' ? 'selected' : '' }}>Kem dưỡng ẩm</option>
                    <option value="sunscreen" {{ old('product_type') === 'sunscreen' ? 'selected' : '' }}>Kem chống nắng</option>
                    <option value="mask" {{ old('product_type') === 'mask' ? 'selected' : '' }}>Mặt nạ</option>
                    <option value="essence" {{ old('product_type') === 'essence' ? 'selected' : '' }}>Essence</option>
                    <option value="eye_cream" {{ old('product_type') === 'eye_cream' ? 'selected' : '' }}>Kem mắt</option>
                    <option value="other" {{ old('product_type') === 'other' ? 'selected' : '' }}>Khác</option>
                </select>
            </div>

            {{-- Kết cấu --}}
            <div>
                <label class="label">Kết cấu</label>
                <select name="texture" class="form-control">
                    <option value="">-- Chọn --</option>
                    <option value="gel" {{ old('texture') === 'gel' ? 'selected' : '' }}>Gel</option>
                    <option value="cream" {{ old('texture') === 'cream' ? 'selected' : '' }}>Cream</option>
                    <option value="liquid" {{ old('texture') === 'liquid' ? 'selected' : '' }}>Liquid</option>
                    <option value="foam" {{ old('texture') === 'foam' ? 'selected' : '' }}>Foam</option>
                    <option value="oil" {{ old('texture') === 'oil' ? 'selected' : '' }}>Oil</option>
                    <option value="balm" {{ old('texture') === 'balm' ? 'selected' : '' }}>Balm</option>
                    <option value="powder" {{ old('texture') === 'powder' ? 'selected' : '' }}>Powder</option>
                    <option value="spray" {{ old('texture') === 'spray' ? 'selected' : '' }}>Spray</option>
                </select>
            </div>

            {{-- SPF --}}
            <div>
                <label class="label">Chỉ số SPF (chỉ cho kem chống nắng)</label>
                <input type="number" name="spf" min="0" max="100" class="form-control" 
                    placeholder="VD: 30, 50" value="{{ old('spf') }}">
            </div>

            {{-- Đặc điểm --}}
            <div class="md:col-span-2">
                <label class="label">Đặc điểm</label>
                <div class="grid grid-cols-3 gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="fragrance_free" value="1" 
                            {{ old('fragrance_free') ? 'checked' : '' }}
                            class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                        <span class="text-sm">Không mùi</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="cruelty_free" value="1" 
                            {{ old('cruelty_free') ? 'checked' : '' }}
                            class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                        <span class="text-sm">Không test trên động vật</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="vegan" value="1" 
                            {{ old('vegan') ? 'checked' : '' }}
                            class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                        <span class="text-sm">Thuần chay</span>
                    </label>
                </div>
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
@endsection

{{-- ================== CSS làm đẹp editor ================== --}}
@push('styles')
<style>
    .editor-card {
        border-radius: 16px;
        overflow: hidden;
        background: rgba(255, 255, 255, .96);
        backdrop-filter: saturate(140%) blur(6px);
        border: 1px solid #eef2f7;
        box-shadow: 0 12px 34px rgba(2, 6, 23, .06);
    }

    /* Toolbar dính + viền nhẹ */
    .editor-card .ck-editor__top {
        background: linear-gradient(180deg, #fff, #fff);
        border-bottom: 1px solid #f1f5f9 !important;
        position: sticky;
        top: 0;
        z-index: 20;
    }

    .editor-card .ck-toolbar {
        border: 0 !important;
        box-shadow: none !important
    }

    /* Vùng soạn thảo */
    .editor-card .ck-editor__editable {
        min-height: 360px;
        padding: 18px 20px !important;
        border: 0 !important;
        box-shadow: none !important;
        font-size: 15px;
        line-height: 1.75;
        color: #0f172a;
    }

    .editor-card .ck-editor__editable.ck-focused {
        box-shadow: 0 0 0 3px rgba(244, 63, 94, .16) !important;
        outline: 1px solid #fb7185 !important;
    }

    /* Nội dung đẹp */
    .editor-card .ck.ck-content h2 {
        font-size: 1.125rem;
        margin: .75rem 0 .5rem;
        font-weight: 800;
        color: #0f172a;
    }

    .editor-card .ck.ck-content h3 {
        font-size: 1.025rem;
        margin: .6rem 0 .4rem;
        font-weight: 700;
        color: #111827;
    }

    .editor-card .ck.ck-content p {
        margin: .5rem 0;
    }

    .editor-card .ck.ck-content ul,
    .editor-card .ck.ck-content ol {
        padding-left: 1.2rem;
    }

    .editor-card .ck.ck-content li {
        margin: .25rem 0;
    }

    .editor-card .ck.ck-content img {
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
    }

    .editor-card .ck-button {
        border-radius: 10px;
    }
</style>
@endpush

{{-- ================== Scripts ================== --}}
@push('scripts')
@verbatim
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
@endverbatim

{{-- CKEditor --}}
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
    ClassicEditor.create(document.querySelector('#long_desc'), {
        placeholder: 'Viết mô tả chi tiết… (H2 cho mục lớn, H3 cho mục con, gạch đầu dòng để rõ ràng) ✨',
        toolbar: [
            'heading', 'bold', 'italic', 'link', 'bulletedList', 'numberedList',
            'blockQuote', 'insertTable', 'undo', 'redo'
        ],
        heading: {
            options: [{
                    model: 'paragraph',
                    title: 'Đoạn văn',
                    class: 'ck-heading_paragraph'
                },
                {
                    model: 'heading2',
                    view: 'h2',
                    title: 'Tiêu đề (H2)',
                    class: 'ck-heading_heading2'
                },
                {
                    model: 'heading3',
                    view: 'h3',
                    title: 'Tiêu đề nhỏ (H3)',
                    class: 'ck-heading_heading3'
                }
            ]
        },
        list: {
            properties: {
                styles: true,
                startIndex: true,
                reversed: true
            }
        },
        table: {
            contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
        }
    }).catch(console.error);
</script>
@endpush