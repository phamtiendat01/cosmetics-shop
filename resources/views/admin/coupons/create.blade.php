@extends('admin.layouts.app')
@section('title','Tạo mã giảm giá')

@section('content')
@if($errors->any())
<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

<form method="post" action="{{ route('admin.coupons.store') }}" class="space-y-3">
    @csrf

    <div class="card p-3">
        <div class="form-row">
            <div>
                <label class="label">Mã giảm giá <span class="text-rose-600">*</span></label>
                <input name="code" value="{{ old('code') }}" class="form-control" placeholder="VD: SUMMER20" required>
            </div>
            <div>
                <label class="label">Tên hiển thị</label>
                <input name="name" value="{{ old('name') }}" class="form-control" placeholder="VD: Khuyến mãi hè">
            </div>

            <div>
                <label class="label">Trạng thái</label>
                <select name="is_active" class="form-control">
                    <option value="1" @selected(old('is_active','1')==='1' )>Hoạt động</option>
                    <option value="0" @selected(old('is_active')==='0' )>Tạm tắt</option>
                </select>
            </div>

            <div>
                <label class="label">Loại giảm</label>
                <select name="type" class="form-control">
                    <option value="percent" @selected(old('type','percent')==='percent' )>% theo đơn</option>
                    <option value="fixed" @selected(old('type')==='fixed' )>Số tiền cố định</option>
                </select>
            </div>

            <div>
                <label class="label">Giá trị giảm <span class="text-rose-600">*</span></label>
                <input name="value" type="number" step="0.01" min="0" value="{{ old('value') }}" class="form-control" placeholder="VD: 20 (nếu %), hoặc 50000 (nếu cố định)" required>
            </div>

            <div>
                <label class="label">Giảm tối đa (nếu là %)</label>
                <input name="max_discount" type="number" step="0.01" min="0" value="{{ old('max_discount') }}" class="form-control" placeholder="VD: 100000">
            </div>

            <div>
                <label class="label">Điều kiện tối thiểu (đơn hàng)</label>
                <input name="min_order_value" type="number" step="0.01" min="0" value="{{ old('min_order_value') }}" class="form-control" placeholder="VD: 300000">
            </div>

            <div>
                <label class="label">Giới hạn tổng lượt dùng</label>
                <input name="usage_limit" type="number" min="0" value="{{ old('usage_limit') }}" class="form-control" placeholder="VD: 100">
            </div>

            <div>
                <label class="label">Giới hạn mỗi khách</label>
                <input name="per_customer_limit" type="number" min="0" value="{{ old('per_customer_limit') }}" class="form-control" placeholder="VD: 1">
            </div>

            <div>
                <label class="label">Bắt đầu áp dụng</label>
                <input name="start_at" id="startAt" value="{{ old('start_at') }}" class="form-control" placeholder="YYYY-MM-DD HH:mm">
            </div>

            <div>
                <label class="label">Kết thúc</label>
                <input name="end_at" id="endAt" value="{{ old('end_at') }}" class="form-control" placeholder="YYYY-MM-DD HH:mm">
            </div>

            <div>
                <label class="label">Áp dụng cho</label>
                <select name="applies_to" id="appliedTo" class="form-control">
                    <option value="order" @selected(old('applies_to','order')==='order' )>Toàn bộ đơn</option>
                    <option value="category" @selected(old('applies_to')==='category' )>Theo danh mục</option>
                    <option value="brand" @selected(old('applies_to')==='brand' )>Theo thương hiệu</option>
                    <option value="product" @selected(old('applies_to')==='product' )>Theo sản phẩm</option>
                </select>
            </div>

            <div class="md:col-span-2" id="appliesWrap" style="display:none">
                <label class="label">Chọn đối tượng áp dụng</label>
                <select id="appliesSelect" name="applies_to_ids[]" multiple class="form-control"></select>
                <div class="help mt-1">Gõ để tìm, có thể chọn nhiều.</div>
            </div>

            <div class="md:col-span-2">
                <label class="label">Mô tả (ghi chú nội bộ)</label>
                <textarea name="description" rows="3" class="form-control" placeholder="Ghi chú…">{{ old('description') }}</textarea>
            </div>
        </div>
    </div>

    <div class="flex justify-between">
        <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline">← Danh sách</a>
        <button class="btn btn-primary">Tạo mã</button>
    </div>
</form>

@push('scripts')
<script>
    // Datetime pickers
    if (window.flatpickr) {
        flatpickr('#startAt', {
            enableTime: true,
            dateFormat: 'Y-m-d H:i'
        });
        flatpickr('#endAt', {
            enableTime: true,
            dateFormat: 'Y-m-d H:i'
        });
    }

    // ============ TomSelect remote cho applies_to_ids ============
    const appliedTo = document.getElementById('appliedTo');
    const appliesSel = document.getElementById('appliesSelect');
    const wrap = document.getElementById('appliesWrap');

    let ts = new TomSelect(appliesSel, {
        valueField: 'value',
        labelField: 'text',
        searchField: 'text',
        plugins: ['remove_button'],
        maxOptions: 1000,
        loadThrottle: 250,
        load: function(query, callback) {
            const type = appliedTo.value;
            if (type === 'order') return callback();
            const url = new URL("{{ route('admin.coupons.targets') }}", window.location.origin);
            url.searchParams.set('type', type);
            url.searchParams.set('q', query || '');
            url.searchParams.set('page', 1);
            url.searchParams.set('per', 20);
            fetch(url).then(r => r.json()).then(json => callback(json.results || [])).catch(() => callback());
        },
        render: {
            option: (data, escape) => `<div>${escape(data.text)}</div>`,
            item: (data, escape) => `<div>${escape(data.text)}</div>`
        }
    });

    function toggleApplies() {
        const t = appliedTo.value;
        if (t === 'order') {
            wrap.style.display = 'none';
            ts.clear();
            ts.clearOptions();
        } else {
            wrap.style.display = '';
            ts.clear();
            ts.clearOptions();
        }
    }
    appliedTo.addEventListener('change', toggleApplies);
    // Lần đầu
    toggleApplies();
</script>
@endpush
@endsection