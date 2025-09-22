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
                        <option value="{{ $c['id'] }}" @selected(old('category_id', $product->category_id)==$c['id'])>{{ $c['name'] }}</option>
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
                    <option value="{{ $b->id }}" @selected(old('brand_id',$product->brand_id)==$b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label">Ảnh đại diện</label>
                <input type="file" name="thumbnail" class="form-control">
                <div class="help mt-1">Ảnh hiện tại:</div>
                <img class="mt-1 w-24 h-24 rounded object-cover"
                    src="{{ $product->thumbnail ? asset('storage/'.$product->thumbnail) : 'https://placehold.co/120x120?text=IMG' }}"
                    alt="thumb">
            </div>

            <div class="md:col-span-2">
                <label class="label">Mô tả ngắn</label>
                <textarea name="short_desc" rows="3" class="form-control">{{ old('short_desc', $product->short_desc) }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label class="label">Mô tả chi tiết</label>
                <div class="editor-card">
                    <textarea id="long_desc" name="long_desc" rows="12" class="form-control">{{ old('long_desc', $product->long_desc) }}</textarea>
                </div>
                <div class="help mt-1 text-xs text-slate-500">
                    Soạn thảo có định dạng. Dùng <b>Heading 2</b> cho mục lớn, <b>Heading 3</b> cho mục con.
                </div>
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
            @php $oldVars = old('variants'); @endphp

            {{-- ====== old input (trả về từ validate) ====== --}}
            @if(is_array($oldVars))
            @foreach($oldVars as $i => $v)
            <div class="variant-row">
                @if(!empty($v['id']))
                <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $v['id'] }}">
                @endif

                <input name="variants[{{ $i }}][name]" class="form-control" placeholder="VD: 30ml" value="{{ $v['name'] ?? '' }}">
                <input name="variants[{{ $i }}][sku]" class="form-control" placeholder="SKU" value="{{ $v['sku'] ?? '' }}">
                <input name="variants[{{ $i }}][price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá" value="{{ $v['price'] ?? '' }}">
                <input name="variants[{{ $i }}][compare_at_price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá gốc" value="{{ $v['compare_at_price'] ?? '' }}">

                {{-- ✅ Tồn kho chỉ hiển thị --}}
                <div class="inline-flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-20 h-9 rounded border bg-gray-50 select-none">
                        {{ $v['qty_in_stock'] ?? 0 }}
                    </span>
                    @if(!empty($v['id']))
                    <button type="button" class="btn btn-soft btn-sm" data-open="#inv-modal-{{ $v['id'] }}">Điều chỉnh</button>
                    @endif
                </div>

                <div class="row-actions">
                    <input name="variants[{{ $i }}][low_stock_threshold]" class="form-control" type="number" min="0" placeholder="Cảnh báo" value="{{ $v['low_stock_threshold'] ?? 0 }}">
                    <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)">X</button>
                </div>
            </div>
            @endforeach

            {{-- ====== edit bình thường ====== --}}
            @else
            @forelse($product->variants as $idx => $v)
            @php $inv = $v->inventory; @endphp
            <div class="variant-row">
                <input type="hidden" name="variants[{{ $idx }}][id]" value="{{ $v->id }}">
                <input name="variants[{{ $idx }}][name]" value="{{ $v->name }}" class="form-control" placeholder="VD: 30ml">
                <input name="variants[{{ $idx }}][sku]" value="{{ $v->sku }}" class="form-control" placeholder="SKU">
                <input name="variants[{{ $idx }}][price]" value="{{ $v->price }}" class="form-control" type="number" step="0.01" min="0" placeholder="Giá">
                <input name="variants[{{ $idx }}][compare_at_price]" value="{{ $v->compare_at_price }}" class="form-control" type="number" step="0.01" min="0" placeholder="Giá gốc">

                {{-- ✅ Tồn kho chỉ hiển thị + nút Điều chỉnh --}}
                <div class="inline-flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-20 h-9 rounded border bg-gray-50 select-none">
                        {{ $inv->qty_in_stock ?? 0 }}
                    </span>
                    <button type="button" class="btn btn-soft btn-sm" data-open="#inv-modal-{{ $v->id }}">Điều chỉnh</button>
                </div>

                <div class="row-actions">
                    <input name="variants[{{ $idx }}][low_stock_threshold]" value="{{ $inv->low_stock_threshold ?? 0 }}" class="form-control" type="number" min="0" placeholder="Cảnh báo">
                    <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)">X</button>
                </div>
            </div>

            {{-- ===== PUSH modal ra ngoài form để tránh "form lồng form" ===== --}}
            @push('modals')
            <div id="inv-modal-{{ $v->id }}" class="modal hidden js-inv-modal" aria-hidden="true">
                <div class="modal-card max-w-[560px] w-[92vw] p-0 overflow-hidden rounded-2xl" role="dialog" aria-labelledby="inv-title-{{ $v->id }}">
                    {{-- Header --}}
                    <div class="px-6 py-4 border-b bg-white flex items-start justify-between">
                        <div>
                            <div id="inv-title-{{ $v->id }}" class="text-base font-semibold">
                                Điều chỉnh kho — {{ $v->name }} <a class="text-brand-600 hover:underline">({{ $v->sku }})</a>
                            </div>
                            <div class="mt-1 text-xs text-slate-600 space-x-2">
                                <span>Hiện có</span>
                                <span class="badge">{{ (int)($inv->qty_in_stock ?? 0) }}</span>
                                <span>→ Sau lưu</span>
                                <span class="badge badge-live js-stock-result">{{ (int)($inv->qty_in_stock ?? 0) }}</span>
                                <span class="hidden js-stock-current" data-cur="{{ (int)($inv->qty_in_stock ?? 0) }}"></span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm !px-2" title="Đóng" data-close="#inv-modal-{{ $v->id }}">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    {{-- Form ĐỘC LẬP (không nằm trong form edit) --}}
                    <form method="POST" action="{{ route('admin.variants.inventory.adjust', $v) }}" class="p-6 space-y-5 bg-white">
                        @csrf
                        <input type="hidden" name="mode" class="js-mode-input" value="delta">

                        {{-- Tabs chế độ --}}
                        <div class="grid grid-cols-2 bg-slate-50 p-1 rounded-lg border border-slate-200">
                            <button type="button" class="tab-btn is-active js-mode" data-mode="delta">
                                <i class="fa-solid fa-plus-minus"></i> Cộng/Trừ
                            </button>
                            <button type="button" class="tab-btn js-mode" data-mode="set">
                                <i class="fa-regular fa-pen-to-square"></i> Đặt bằng
                            </button>
                        </div>

                        <div class="grid md:grid-cols-2 gap-5">
                            {{-- Cộng/Trừ --}}
                            <div class="js-delta-wrap">
                                <label class="text-xs text-slate-500">+ / − số lượng</label>
                                <input class="form-control js-delta" name="delta" type="number" step="1" inputmode="numeric" placeholder="+100 (nhập) hoặc -5 (hỏng)">
                                <div class="chips mt-2">
                                    <button type="button" class="chip js-quick" data-val="+10">+10</button>
                                    <button type="button" class="chip js-quick" data-val="+50">+50</button>
                                    <button type="button" class="chip js-quick" data-val="+100">+100</button>
                                    <button type="button" class="chip js-quick" data-val="-1">-1</button>
                                    <button type="button" class="chip js-quick" data-val="-5">-5</button>
                                </div>
                            </div>

                            {{-- Đặt bằng --}}
                            <div class="js-set-wrap opacity-50 pointer-events-none">
                                <label class="text-xs text-slate-500">Đặt tồn kho bằng</label>
                                <input class="form-control js-set" name="qty" type="number" min="0" placeholder="{{ (int)($inv->qty_in_stock ?? 0) }}">
                                <div class="text-xs text-slate-400 mt-1">Nhập giá trị tuyệt đối muốn đặt.</div>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-slate-500">Lý do</label>
                                <select name="reason" class="form-control">
                                    <option value="">-- Chọn lý do --</option>
                                    <option value="restock">Nhập hàng</option>
                                    <option value="damage">Hỏng/lỗi</option>
                                    <option value="stock_take">Kiểm kho</option>
                                    <option value="manual-set">Đặt bằng (thủ công)</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-slate-500">Ghi chú</label>
                                <input name="note" class="form-control" placeholder="Ví dụ: nhập lô 09/2025">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="btn btn-outline btn-sm" data-close="#inv-modal-{{ $v->id }}">Hủy</button>
                            <button class="btn btn-primary btn-sm">Lưu</button>
                        </div>
                    </form>
                </div>
            </div>
            @endpush
            {{-- ===== /PUSH modal ===== --}}
            @empty
            {{-- Không có biến thể --}}
            <div class="variant-row">
                <input name="variants[0][name]" class="form-control" placeholder="VD: 30ml">
                <input name="variants[0][sku]" class="form-control" placeholder="SKU">
                <input name="variants[0][price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá">
                <input name="variants[0][compare_at_price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá gốc">
                <div class="inline-flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-20 h-9 rounded border bg-gray-50 select-none">0</span>
                </div>
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

{{-- Render toàn bộ modal đã push, Ở NGOÀI form để tránh lồng form --}}
@stack('modals')
@endsection

@push('styles')
<style>
    /* ✨ Glass editor card */
    .editor-card {
        border-radius: 16px;
        overflow: hidden;
        background: rgba(255, 255, 255, .96);
        backdrop-filter: saturate(140%) blur(6px);
        border: 1px solid #eef2f7;
        box-shadow: 0 12px 34px rgba(2, 6, 23, .06)
    }

    .editor-card .ck-editor__top {
        background: #fff;
        border-bottom: 1px solid #f1f5f9 !important;
        position: sticky;
        top: 0;
        z-index: 20
    }

    .editor-card .ck-toolbar {
        border: 0 !important;
        box-shadow: none !important
    }

    .editor-card .ck-editor__editable {
        min-height: 360px;
        padding: 18px 20px !important;
        border: 0 !important;
        box-shadow: none !important;
        font-size: 15px;
        line-height: 1.75;
        color: #0f172a
    }

    .editor-card .ck-editor__editable.ck-focused {
        box-shadow: 0 0 0 3px rgba(244, 63, 94, .16) !important;
        outline: 1px solid #fb7185 !important
    }

    /* Badge nhỏ (Hiện có / Sau lưu) */
    .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 9999px;
        background: #e2e8f0;
        color: #0f172a;
        font-weight: 700;
        font-size: 12px
    }

    .badge-live {
        background: #dcfce7;
        color: #166534
    }

    /* Tab button cho 2 chế độ */
    .tab-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 12px;
        border-radius: 10px;
        font-weight: 700;
        color: #334155
    }

    .tab-btn.is-active {
        background: #111827;
        color: #fff
    }

    /* Quick chips */
    .chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px
    }

    .chip {
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        background: #fff;
        color: #111827
    }

    .chip:hover {
        background: #111827;
        color: #fff
    }
</style>
@endpush

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
          <div class="inline-flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-20 h-9 rounded border bg-gray-50 select-none">0</span>
          </div>
          <div class="row-actions">
            <input name="variants[${idx}][low_stock_threshold]" class="form-control" type="number" min="0" placeholder="Cảnh báo">
            <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)">X</button>
          </div>`;
        list.appendChild(row);
    }

    // Toggle modal mở/đóng (dùng chung)
    document.addEventListener('click', function(e) {
        const openBtn = e.target.closest('[data-open]');
        const closeBtn = e.target.closest('[data-close]');
        if (openBtn) {
            const sel = openBtn.getAttribute('data-open');
            const m = document.querySelector(sel);
            if (m) {
                m.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        }
        if (closeBtn) {
            const sel = closeBtn.getAttribute('data-close');
            const m = document.querySelector(sel);
            if (m) {
                m.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }
    });
    document.querySelectorAll('.modal').forEach(m => {
        m.addEventListener('click', e => {
            if (e.target === m) {
                m.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });
    });

    // ===== Logic UI cho popup điều chỉnh kho =====
    document.querySelectorAll('.js-inv-modal').forEach(function(modal) {
        const curEl = modal.querySelector('.js-stock-current');
        const resEl = modal.querySelector('.js-stock-result');
        const modeInput = modal.querySelector('.js-mode-input');
        const btns = modal.querySelectorAll('.js-mode');
        const deltaWrap = modal.querySelector('.js-delta-wrap');
        const setWrap = modal.querySelector('.js-set-wrap');
        const deltaEl = modal.querySelector('.js-delta');
        const setEl = modal.querySelector('.js-set');
        const cur = Number(curEl?.dataset.cur || 0);

        function clamp(n) {
            return Math.max(0, n | 0);
        }

        function setMode(mode) {
            modeInput.value = mode;
            btns.forEach(b => b.classList.toggle('is-active', b.dataset.mode === mode));
            if (mode === 'delta') {
                deltaWrap.classList.remove('opacity-50', 'pointer-events-none');
                setWrap.classList.add('opacity-50', 'pointer-events-none');
            } else {
                setWrap.classList.remove('opacity-50', 'pointer-events-none');
                deltaWrap.classList.add('opacity-50', 'pointer-events-none');
            }
            updatePreview();
        }

        function updatePreview() {
            const mode = modeInput.value;
            const delta = Number(deltaEl?.value || 0);
            const set = Number(setEl?.value || 0);
            const next = mode === 'delta' ? clamp(cur + (isNaN(delta) ? 0 : delta)) : clamp(isNaN(set) ? cur : set);
            resEl.textContent = next;
        }

        btns.forEach(b => b.addEventListener('click', () => setMode(b.dataset.mode)));
        deltaEl && deltaEl.addEventListener('input', updatePreview);
        setEl && setEl.addEventListener('input', updatePreview);

        modal.querySelectorAll('.js-quick').forEach(ch => {
            ch.addEventListener('click', () => {
                const v = Number(ch.dataset.val || 0);
                deltaEl.value = (Number(deltaEl.value || 0) + v) || v;
                setMode('delta'); // về chế độ cộng/trừ khi bấm chip
            });
        });

        // init
        setMode('delta');
    });

    // Tự ẩn alert
    document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
        setTimeout(() => {
            el.classList.add('alert--hide');
            setTimeout(() => el.remove(), 350)
        }, +el.dataset.autoDismiss || 3000);
    });
</script>
@endverbatim
@endpush

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
    ClassicEditor.create(document.querySelector('#long_desc'), {
        placeholder: 'Viết mô tả chi tiết… (H2 cho mục lớn, H3 cho mục con, gạch đầu dòng để rõ ràng) ✨',
        toolbar: ['heading', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'undo', 'redo'],
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