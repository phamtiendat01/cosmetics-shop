@extends('admin.layouts.app')
@section('title','Thêm khách hàng')

@section('content')
@if($errors->any())
<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

<form method="post" action="{{ route('admin.customers.store') }}" class="space-y-3">
    @csrf

    <div class="card p-3">
        <div class="form-row">
            <div>
                <label class="label">Họ tên</label>
                <input name="name" value="{{ old('name') }}" class="form-control" required>
            </div>
            <div>
                <label class="label">Email</label>
                <input name="email" value="{{ old('email') }}" class="form-control" type="email" required>
            </div>

            <div>
                <label class="label">Điện thoại</label>
                <input name="phone" value="{{ old('phone') }}" class="form-control">
            </div>
            <div>
                <label class="label">Giới tính</label>
                <select name="gender" class="form-control">
                    <option value="">—</option>
                    <option value="male" @selected(old('gender')==='male' )>Nam</option>
                    <option value="female" @selected(old('gender')==='female' )>Nữ</option>
                    <option value="other" @selected(old('gender')==='other' )>Khác</option>
                </select>
            </div>

            <div>
                <label class="label">Ngày sinh</label>
                <input name="dob" id="dob" value="{{ old('dob') }}" class="form-control" placeholder="YYYY-MM-DD">
            </div>
            <div>
                <label class="label">Trạng thái</label>
                <select name="is_active" class="form-control">
                    <option value="1" @selected(old('is_active','1')==='1' )>Hoạt động</option>
                    <option value="0" @selected(old('is_active')==='0' )>Khoá</option>
                </select>
            </div>

            <div>
                <label class="label">Mật khẩu</label>
                <input name="password" type="password" class="form-control" required>
            </div>
            <div>
                <label class="label">Nhập lại mật khẩu</label>
                <input name="password_confirmation" type="password" class="form-control" required>
            </div>

            {{-- ===== Địa chỉ giao hàng mặc định (có API tỉnh / quận / phường) ===== --}}
            <div class="md:col-span-2">
                <label class="label">Địa chỉ giao hàng mặc định (tuỳ chọn)</label>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    {{-- Dòng địa chỉ chi tiết --}}
                    <input name="shipping_address[line1]"
                        value="{{ old('shipping_address.line1') }}"
                        class="form-control md:col-span-2"
                        placeholder="Số nhà, đường / thôn, ấp">

                    {{-- Combobox Tỉnh/TP - Quận/Huyện - Phường/Xã --}}
                    <select id="shippingProvince" class="form-control" title="Tỉnh/Thành phố" disabled>
                        <option value="">Tỉnh/TP</option>
                    </select>
                    <select id="shippingDistrict" class="form-control" title="Quận/Huyện" disabled>
                        <option value="">Quận/Huyện</option>
                    </select>
                    <select id="shippingWard" class="form-control" title="Phường/Xã" disabled>
                        <option value="">Phường/Xã</option>
                    </select>

                    {{-- Mã bưu chính (nếu có) --}}
                    <input name="shipping_address[postal]"
                        value="{{ old('shipping_address.postal') }}"
                        class="form-control"
                        placeholder="Mã bưu chính">

                    {{-- Hidden: lưu code + tên để server nhận đúng --}}
                    <input type="hidden" name="shipping_address[province_code]" id="shipProvinceCode" value="{{ old('shipping_address.province_code') }}">
                    <input type="hidden" name="shipping_address[province_name]" id="shipProvinceName" value="{{ old('shipping_address.province_name') }}">
                    <input type="hidden" name="shipping_address[district_code]" id="shipDistrictCode" value="{{ old('shipping_address.district_code') }}">
                    <input type="hidden" name="shipping_address[district_name]" id="shipDistrictName" value="{{ old('shipping_address.district_name') }}">
                    <input type="hidden" name="shipping_address[ward_code]" id="shipWardCode" value="{{ old('shipping_address.ward_code') }}">
                    <input type="hidden" name="shipping_address[ward_name]" id="shipWardName" value="{{ old('shipping_address.ward_name') }}">
                </div>
            </div>
            {{-- /Địa chỉ --}}
        </div>
    </div>

    <div class="flex justify-between">
        <a href="{{ route('admin.customers.index') }}" class="btn btn-outline">← Danh sách</a>
        <button class="btn btn-primary">Lưu</button>
    </div>
</form>

@push('scripts')
<script>
    // Datepicker
    if (window.flatpickr) {
        flatpickr("#dob", {
            dateFormat: "Y-m-d"
        });
    }

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
            p: "{{ old('shipping_address.province_code') }}",
            d: "{{ old('shipping_address.district_code') }}",
            w: "{{ old('shipping_address.ward_code') }}"
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
            sel.appendChild(opt('', label));
        };

        // Load provinces
        fetch(API + '/p/')
            .then(r => r.json())
            .then(list => {
                first($p, 'Tỉnh/TP');
                list.forEach(x => $p.appendChild(opt(x.code, x.name, preset.p && preset.p == x.code)));
                $p.disabled = false;
                if (preset.p) loadDistricts(preset.p, true);
            });

        function loadDistricts(pCode, prefill = false) {
            $d.disabled = true;
            $w.disabled = true;
            first($d, 'Quận/Huyện');
            first($w, 'Phường/Xã');
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
            first($w, 'Phường/Xã');
            if (!dCode) return;
            fetch(`${API}/d/${dCode}?depth=2`)
                .then(r => r.json())
                .then(d => {
                    d.wards.forEach(x => $w.appendChild(opt(x.code, x.name, prefill && preset.w == x.code)));
                    $w.disabled = false;
                });
        }

        // Update hidden fields (code + name)
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
            // reset ward
            $wCode.value = '';
            $wName.value = '';
            loadWards($d.value);
        });

        $w.addEventListener('change', () => {
            syncHidden($w, $wCode, $wName);
        });

        // Prefill hidden theo old() (nếu có)
        if (preset.p) {
            $pCode.value = preset.p;
            // tên sẽ được điền khi fetch xong và chọn option tương ứng
        }
        if (preset.d) $dCode.value = preset.d;
        if (preset.w) $wCode.value = preset.w;
    })();
</script>
@endpush
@endsection