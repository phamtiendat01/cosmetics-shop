@extends('admin.layouts.app')
@section('title','Sửa mã giảm giá')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

<form method="post" action="{{ route('admin.coupons.update',$coupon) }}" class="space-y-3">
    @csrf @method('PUT')

    <div class="card p-3">
        <div class="form-row">
            <div>
                <label class="label">Mã giảm giá <span class="text-rose-600">*</span></label>
                <input name="code" value="{{ old('code',$coupon->code) }}" class="form-control" required>
            </div>

            <div>
                <label class="label">Tên hiển thị</label>
                <input name="name" value="{{ old('name',$coupon->name) }}" class="form-control">
            </div>

            <div>
                <label class="label">Trạng thái</label>
                <select name="is_active" class="form-control">
                    <option value="1" @selected(old('is_active',(string)$coupon->is_active)==='1')>Hoạt động</option>
                    <option value="0" @selected(old('is_active',(string)$coupon->is_active)==='0')>Tạm tắt</option>
                </select>
            </div>

            <div>
                <label class="label">Loại giảm</label>
                <select name="type" class="form-control">
                    <option value="percent" @selected(old('type',$coupon->discount_type)==='percent')>% theo đơn</option>
                    <option value="fixed" @selected(old('type',$coupon->discount_type)==='fixed')>Số tiền cố định</option>
                </select>
            </div>

            <div>
                <label class="label">Giá trị giảm <span class="text-rose-600">*</span></label>
                {{-- để text + inputmode tránh lỗi dấu phẩy/chấm trên trình duyệt --}}
                <input name="value" type="text" inputmode="decimal"
                    value="{{ old('value',$coupon->discount_value) }}" class="form-control" required>
                <div class="help mt-1">Ví dụ: <b>10</b> (nếu là %), <b>50000</b> (nếu là số tiền).</div>
            </div>

            <div>
                <label class="label">Giảm tối đa (nếu là %)</label>
                <input name="max_discount" type="text" inputmode="numeric"
                    value="{{ old('max_discount',$coupon->max_discount) }}" class="form-control">
            </div>

            <div>
                <label class="label">Điều kiện tối thiểu (đơn hàng)</label>
                <input name="min_order_value" type="text" inputmode="numeric"
                    value="{{ old('min_order_value',$coupon->min_order_total) }}" class="form-control">
            </div>

            <div>
                <label class="label">Giới hạn tổng lượt dùng</label>
                <input name="usage_limit" type="number" min="0"
                    value="{{ old('usage_limit',$coupon->usage_limit) }}" class="form-control">
            </div>

            <div>
                <label class="label">Giới hạn mỗi khách</label>
                <input name="per_customer_limit" type="number" min="0"
                    value="{{ old('per_customer_limit',$coupon->usage_limit_per_user) }}" class="form-control">
            </div>

            <div>
                <label class="label">Bắt đầu áp dụng</label>
                <input name="start_at" id="startAt"
                    value="{{ old('start_at', optional($coupon->starts_at)->format('Y-m-d H:i')) }}"
                    class="form-control" placeholder="YYYY-MM-DD HH:mm">
            </div>

            <div>
                <label class="label">Kết thúc</label>
                <input name="end_at" id="endAt"
                    value="{{ old('end_at', optional($coupon->ends_at)->format('Y-m-d H:i')) }}"
                    class="form-control" placeholder="YYYY-MM-DD HH:mm">
            </div>

            <div>
                <label class="label">Áp dụng cho</label>
                <select name="applies_to" id="appliedTo" class="form-control">
                    @php $appliesToOld = old('applies_to', $coupon->applied_to); @endphp
                    <option value="order" @selected($appliesToOld==='order' )>Toàn bộ đơn</option>
                    <option value="category" @selected($appliesToOld==='category' )>Theo danh mục</option>
                    <option value="brand" @selected($appliesToOld==='brand' )>Theo thương hiệu</option>
                    <option value="product" @selected($appliesToOld==='product' )>Theo sản phẩm</option>
                </select>
            </div>

            <div class="md:col-span-2" id="appliesWrap" style="display:none">
                <label class="label">Chọn đối tượng áp dụng</label>
                <select id="appliesSelect" name="applies_to_ids[]" multiple class="form-control"></select>
                <div class="help mt-1">Gõ để tìm, có thể chọn nhiều.</div>
            </div>

            <div class="md:col-span-2">
                <label class="label">Mô tả (ghi chú nội bộ)</label>
                <textarea name="description" rows="3" class="form-control">{{ old('description',$coupon->description) }}</textarea>
            </div>
        </div>
    </div>

    <div class="flex justify-between">
        <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline">← Danh sách</a>
        <div class="flex gap-2">
            <button class="btn btn-primary">Lưu thay đổi</button>
            <button type="button" class="btn btn-danger" data-confirm-delete data-url="{{ route('admin.coupons.destroy',$coupon) }}">Xoá</button>
        </div>
    </div>
</form>

{{-- Modal xác nhận xoá --}}
<div id="confirmModal" class="modal hidden">
    <div class="modal-card">
        <div class="p-4 border-b">
            <div class="font-semibold">Xác nhận xoá</div>
            <div class="text-sm text-slate-500">Xoá mã giảm giá này? Không thể hoàn tác.</div>
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

    // Auto dismiss alerts
    document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
        const t = +el.getAttribute('data-auto-dismiss') || 3000;
        setTimeout(() => {
            el.classList.add('alert--hide');
            setTimeout(() => el.remove(), 350);
        }, t);
    });

    // Modal xoá
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

    // ======= TomSelect remote cho applies_to_ids =======
    const appliedTo = document.getElementById('appliedTo');
    const appliesSel = document.getElementById('appliesSelect');
    const wrap = document.getElementById('appliesWrap');

    const preselected = @json($preselected);

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
            if (query) {
                url.searchParams.set('q', query);
                url.searchParams.set('page', 1);
                url.searchParams.set('per', 20);
            } else {
                return callback();
            }
            fetch(url).then(r => r.json()).then(json => callback(json.results || [])).catch(() => callback());
        },
        render: {
            option: (data, escape) => `<div>${escape(data.text)}</div>`,
            item: (data, escape) => `<div>${escape(data.text)}</div>`
        }
    });

    function preloadSelected() {
        const t = appliedTo.value;
        if (t === 'order' || !preselected.length) return;
        const url = new URL("{{ route('admin.coupons.targets') }}", window.location.origin);
        url.searchParams.set('type', t);
        preselected.forEach(id => url.searchParams.append('ids[]', id));
        fetch(url).then(r => r.json()).then(json => {
            ts.addOptions(json.results || []);
            ts.setValue(preselected, true);
        });
    }

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
            preloadSelected();
        }
    }

    appliedTo.addEventListener('change', toggleApplies);
    toggleApplies();
</script>
@endpush
@endsection