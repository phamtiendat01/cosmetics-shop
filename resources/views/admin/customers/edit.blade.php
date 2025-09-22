@extends('admin.layouts.app')
@section('title','Sửa khách hàng')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

<form method="post" action="{{ route('admin.customers.update',$customer) }}" class="space-y-3">
    @csrf @method('PUT')

    <div class="card p-3">
        <div class="form-row">
            <div>
                <label class="label">Họ tên</label>
                <input name="name" value="{{ old('name',$customer->name) }}" class="form-control" required>
            </div>
            <div>
                <label class="label">Email</label>
                <input name="email" value="{{ old('email',$customer->email) }}" class="form-control" type="email" required>
            </div>
            <div>
                <label class="label">Điện thoại</label>
                <input name="phone" value="{{ old('phone',$customer->phone) }}" class="form-control">
            </div>
            <div>
                <label class="label">Giới tính</label>
                <select name="gender" class="form-control">
                    <option value="">—</option>
                    <option value="male" @selected(old('gender',$customer->gender)==='male')>Nam</option>
                    <option value="female" @selected(old('gender',$customer->gender)==='female')>Nữ</option>
                    <option value="other" @selected(old('gender',$customer->gender)==='other')>Khác</option>
                </select>
            </div>

            <div>
                <label class="label">Ngày sinh</label>
                <input name="dob" id="dob" value="{{ old('dob',$customer->dob?->format('Y-m-d')) }}" class="form-control" placeholder="YYYY-MM-DD">
            </div>
            <div>
                <label class="label">Trạng thái</label>
                <select name="is_active" class="form-control">
                    <option value="1" @selected(old('is_active',(string)$customer->is_active)==='1')>Hoạt động</option>
                    <option value="0" @selected(old('is_active',(string)$customer->is_active)==='0')>Khoá</option>
                </select>
            </div>
            <div>
                <label class="label">Đổi mật khẩu (để trống nếu không đổi)</label>
                <input name="password" type="password" class="form-control">
            </div>
            <div>
                <label class="label">Nhập lại mật khẩu</label>
                <input name="password_confirmation" type="password" class="form-control">
            </div>

            {{-- ========== ĐỊA CHỈ (có API tỉnh/ huyện/ phường) ========== --}}
            @php
            // Lấy địa chỉ cũ hoặc mặc định của khách
            $addr = old('shipping_address', $customer->default_shipping_address ?? []);
            // Prefill theo schema mới (code + name) hoặc fallback theo tên cũ
            $pCode = $addr['province_code'] ?? '';
            $dCode = $addr['district_code'] ?? '';
            $wCode = $addr['ward_code'] ?? '';
            $pName = $addr['province_name'] ?? ($addr['province'] ?? '');
            $dName = $addr['district_name'] ?? ($addr['district'] ?? '');
            $wName = $addr['ward_name'] ?? ($addr['city'] ?? '');
            @endphp

            <div class="md:col-span-2">
                <label class="label">Địa chỉ giao hàng mặc định</label>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    {{-- Dòng địa chỉ chi tiết --}}
                    <input name="shipping_address[line1]"
                        value="{{ $addr['line1'] ?? '' }}"
                        class="form-control md:col-span-2"
                        placeholder="Số nhà, đường / thôn, ấp">

                    {{-- Combobox Tỉnh/TP - Quận/Huyện - Phường/Xã (API) --}}
                    <select id="shippingProvince" class="form-control" title="Tỉnh/Thành phố" disabled>
                        <option value="">{{ $pName ?: 'Tỉnh/TP' }}</option>
                    </select>
                    <select id="shippingDistrict" class="form-control" title="Quận/Huyện" disabled>
                        <option value="">{{ $dName ?: 'Quận/Huyện' }}</option>
                    </select>
                    <select id="shippingWard" class="form-control" title="Phường/Xã" disabled>
                        <option value="">{{ $wName ?: 'Phường/Xã' }}</option>
                    </select>

                    {{-- Mã bưu chính --}}
                    <input name="shipping_address[postal]"
                        value="{{ $addr['postal'] ?? '' }}"
                        class="form-control"
                        placeholder="Mã bưu chính">

                    {{-- Hidden: lưu code + name để server nhận đúng --}}
                    <input type="hidden" name="shipping_address[province_code]" id="shipProvinceCode" value="{{ $pCode }}">
                    <input type="hidden" name="shipping_address[province_name]" id="shipProvinceName" value="{{ $pName }}">
                    <input type="hidden" name="shipping_address[district_code]" id="shipDistrictCode" value="{{ $dCode }}">
                    <input type="hidden" name="shipping_address[district_name]" id="shipDistrictName" value="{{ $dName }}">
                    <input type="hidden" name="shipping_address[ward_code]" id="shipWardCode" value="{{ $wCode }}">
                    <input type="hidden" name="shipping_address[ward_name]" id="shipWardName" value="{{ $wName }}">
                </div>

                <div class="text-xs text-slate-500 mt-1">
                    Gợi ý: nếu đang hiển thị tên tỉnh/huyện/phường cũ, hãy chọn lại để cập nhật theo chuẩn hành chính mới (sẽ lưu cả <i>code</i>).
                </div>
            </div>
            {{-- /ĐỊA CHỈ --}}
        </div>

        <div class="divider"></div>
        <div class="text-xs text-slate-500">
            Đơn hàng: <b>{{ $customer->orders_count }}</b> —
            Tổng chi tiêu: <b>{{ number_format($stats['total_spent'] ?? 0,0,',','.') }}₫</b> —
            Đơn gần nhất: <b>{{ $stats['last_order'] ? \Carbon\Carbon::parse($stats['last_order'])->format('d/m/Y H:i') : '—' }}</b>
        </div>
    </div>

    <div class="flex justify-between">
        <a href="{{ route('admin.customers.index') }}" class="btn btn-outline">← Danh sách</a>
        <div class="flex gap-2">
            <button class="btn btn-primary">Lưu thay đổi</button>
            <button type="button" class="btn btn-danger" data-confirm-delete data-url="{{ route('admin.customers.destroy',$customer) }}">Xoá</button>
        </div>
    </div>
</form>

{{-- Modal xác nhận xoá --}}
<div id="confirmModal" class="modal hidden">
    <div class="modal-card">
        <div class="p-4 border-b">
            <div class="font-semibold">Xác nhận xoá</div>
            <div class="text-sm text-slate-500">Xoá khách hàng này? Không thể hoàn tác.</div>
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
    // Datepicker
    if (window.flatpickr) {
        flatpickr("#dob", {
            dateFormat: "Y-m-d"
        });
    }

    // Modal XOÁ
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

    // ===== Provinces Open API cho địa chỉ =====
    (function() {
        const API = 'https://provinces.open-api.vn/api';

        const $p = document.getElementById('shippingProvince');
        const $d = document.getElementById('shippingDistrict');
        const $w = document.getElementById('shippingWard');

        const $pCode = document.getElementById('shipProvinceCode');
        const $pName = document.getElementById('shipProvinceName');
        const $dCode = document.getElementById('shipDistrictCode');
        const $dName = document.getElementById('shipDistrictName');
        const $wCode = document.getElementById('shipWardCode');
        const $wName = document.getElementById('shipWardName');

        const preset = {
            p: "{{ $pCode }}",
            d: "{{ $dCode }}",
            w: "{{ $wCode }}"
        };
        const presetName = {
            p: "{{ $pName }}",
            d: "{{ $dName }}",
            w: "{{ $wName }}"
        };

        const opt = (val, text, selected = false) => {
            const o = document.createElement('option');
            o.value = val ?? '';
            o.textContent = text ?? '';
            if (selected) o.selected = true;
            return o;
        };
        const first = (sel, label) => {
            sel.innerHTML = '';
            sel.appendChild(opt('', label || '—'));
        };

        // đặt sẵn name hidden từ dữ liệu cũ (để không mất dữ liệu nếu chưa chọn lại)
        if (presetName.p) $pName.value = presetName.p;
        if (presetName.d) $dName.value = presetName.d;
        if (presetName.w) $wName.value = presetName.w;

        // Load provinces
        fetch(API + '/p/')
            .then(r => r.json())
            .then(list => {
                first($p, presetName.p || 'Tỉnh/TP');
                list.forEach(x => $p.appendChild(opt(x.code, x.name, preset.p && preset.p == x.code)));
                $p.disabled = false;
                if (preset.p) loadDistricts(preset.p, true);
            });

        function loadDistricts(pCode, prefill = false) {
            $d.disabled = true;
            $w.disabled = true;
            first($d, presetName.d || 'Quận/Huyện');
            first($w, presetName.w || 'Phường/Xã');
            if (!pCode) return;
            fetch(`${API}/p/${pCode}?depth=2`)
                .then(r => r.json())
                .then(p => {
                    p.districts.forEach(x => $d.appendChild(opt(x.code, x.name, prefill && preset.d == x.code)));
                    $d.disabled = false;
                    if (prefill && preset.d) loadWards(preset.d, true);
                });
        }

        function loadWards(dCode, prefill = false) {
            $w.disabled = true;
            first($w, presetName.w || 'Phường/Xã');
            if (!dCode) return;
            fetch(`${API}/d/${dCode}?depth=2`)
                .then(r => r.json())
                .then(d => {
                    d.wards.forEach(x => $w.appendChild(opt(x.code, x.name, prefill && preset.w == x.code)));
                    $w.disabled = false;
                });
        }

        // Sync hidden code + name
        function syncHidden(sel, codeEl, nameEl) {
            const op = sel.options[sel.selectedIndex];
            codeEl.value = sel.value || '';
            nameEl.value = op ? op.textContent : '';
        }

        // Events
        $p.addEventListener('change', () => {
            syncHidden($p, $pCode, $pName);
            // reset dưới
            $dCode.value = '';
            $dName.value = '';
            $wCode.value = '';
            $wName.value = '';
            loadDistricts($p.value);
        });
        $d.addEventListener('change', () => {
            syncHidden($d, $dCode, $dName);
            $wCode.value = '';
            $wName.value = '';
            loadWards($d.value);
        });
        $w.addEventListener('change', () => {
            syncHidden($w, $wCode, $wName);
        });
    })();
</script>
@endpush
@endsection