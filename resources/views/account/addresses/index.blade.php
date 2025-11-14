@extends('layouts.app')
@section('title','Sổ địa chỉ')

@php
$addrStore = \Illuminate\Support\Facades\Route::has('account.addresses.store')
? route('account.addresses.store') : url('/account/addresses');
$addrBase = url('/account/addresses');
$destroyUrl = function($id){
return \Illuminate\Support\Facades\Route::has('account.addresses.destroy')
? route('account.addresses.destroy', $id)
: url('/account/addresses/'.$id);
};
$defaultUrl = function($id){
return \Illuminate\Support\Facades\Route::has('account.addresses.default')
? route('account.addresses.default', $id)
: url('/account/addresses/'.$id.'/default');
};
@endphp

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">

    {{-- Toast 3s (không dùng Alpine) --}}
    @if (session('status'))
    <div id="jsToast" class="fixed top-4 right-4 z-[999] bg-emerald-600 text-white px-4 py-2 rounded-lg shadow">
        {{ session('status') }}
    </div>
    @endif

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Sổ địa chỉ</h1>
        <button id="btnAdd"
            class="inline-flex items-center rounded-xl bg-brand-600 text-white px-4 py-2 text-sm hover:bg-brand-700 shadow">
            <i class="fa-solid fa-plus mr-2"></i> Thêm địa chỉ
        </button>
    </div>

    {{-- Cards --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 mt-6">
        @forelse($addresses as $a)
        @php $est = $estimates[$a->id] ?? ['km'=>null,'fee'=>null]; @endphp

        <div class="bg-white rounded-2xl border border-rose-100 shadow-card p-5 hover:shadow-lg hover:border-rose-200 transition">
            <div class="flex items-start justify-between">
                <div class="text-lg font-semibold">{{ $a->name }}</div>
                <div class="flex gap-2">
                    @if($a->is_default_shipping)
                    <span class="px-2 py-0.5 text-xs rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Mặc định giao</span>
                    @endif
                    @if($a->is_default_billing)
                    <span class="px-2 py-0.5 text-xs rounded-full bg-sky-50 text-sky-700 border border-sky-200">Mặc định thanh toán</span>
                    @endif
                </div>
            </div>

            <div class="mt-2 text-sm text-ink">
                <div class="font-medium">{{ $a->phone }}</div>
                <div>{{ $a->line1 }} {{ $a->line2 }}</div>
                <div>{{ $a->ward }}{{ $a->district ? ', '.$a->district : '' }}{{ $a->province ? ', '.$a->province : '' }}</div>
            </div>

            <div class="mt-3 text-xs text-ink/70">
                @if(is_null($est['km']))
                <div class="flex items-center gap-1"><i class="fa-regular fa-circle-question"></i> Chưa có toạ độ – sửa địa chỉ để ghim.</div>
                @else
                <div>Khoảng cách: <b>{{ number_format($est['km'],2) }} km</b></div>
                <div>Phí ship ước tính: <b>{{ ($est['fee'] ?? 0)==0 ? 'Miễn phí' : '₫'.number_format($est['fee']) }}</b></div>
                @endif
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3">
                <button type="button"
                    class="btn-edit rounded-lg border border-rose-200 px-3 py-2 text-sm hover:bg-rose-50"
                    data-update="{{ $addrBase.'/'.$a->id }}"
                    data-address='@json($a)'>
                    Sửa
                </button>

                <button type="button"
                    class="btn-delete rounded-lg border border-rose-200 px-3 py-2 text-sm hover:bg-rose-50"
                    data-action="{{ $destroyUrl($a->id) }}">
                    Xoá
                </button>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-3">
                @unless($a->is_default_shipping)
                <form method="POST" action="{{ $defaultUrl($a->id) }}">
                    @csrf
                    <input type="hidden" name="type" value="shipping">
                    <button class="w-full rounded-lg bg-ink text-white px-3 py-2 text-sm hover:bg-black/90">
                        Đặt mặc định giao
                    </button>
                </form>
                @endunless

                @unless($a->is_default_billing)
                <form method="POST" action="{{ $defaultUrl($a->id) }}">
                    @csrf
                    <input type="hidden" name="type" value="billing">
                    <button class="w-full rounded-lg border border-rose-200 px-3 py-2 text-sm hover:bg-rose-50">
                        Đặt mặc định thanh toán
                    </button>
                </form>
                @endunless
            </div>
        </div>
        @empty
        <div class="col-span-3">
            <div class="bg-white rounded-2xl border border-rose-100 shadow-card p-10 text-center">
                <div class="text-ink font-medium">Chưa có địa chỉ.</div>
            </div>
        </div>
        @endforelse
    </div>
</div>

{{-- ===== Modal THÊM/SỬA (JS thuần) ===== --}}
<div id="addrModal" class="fixed inset-0 z-[999] hidden items-center justify-center bg-black/40">
    <div class="w-[780px] max-w-[95vw] bg-white rounded-2xl shadow-card" role="dialog" aria-modal="true">
        <form id="addrForm" method="POST" action="{{ $addrStore }}" class="p-6 space-y-5">
            @csrf
            <input type="hidden" id="addrMethod" value=""> {{-- khi edit sẽ set name=_method & value=PATCH --}}

            <div class="flex items-center justify-between">
                <div id="addrTitle" class="text-lg font-semibold">Thêm địa chỉ</div>
                <button type="button" class="text-ink/60 hover:text-ink" data-close><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Tên người nhận<span class="text-red-500">*</span></label>
                    <input name="name" id="f_name" required
                        class="w-full rounded-md border border-rose-200 px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Số điện thoại<span class="text-red-500">*</span></label>
                    <input name="phone" id="f_phone" required
                        class="w-full rounded-md border border-rose-200 px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Địa chỉ<span class="text-red-500">*</span></label>
                    <input name="line1" id="f_line1"
                        class="w-full rounded-md border border-rose-200 px-3 py-2 outline-none focus:ring-2 focus:ring-brand-400">
                </div>

                {{-- Provinces API --}}
                <div>
                    <label class="block text-sm font-medium mb-1">Tỉnh/Thành<span class="text-red-500">*</span></label>
                    <select id="sel_prov" required class="w-full rounded-md border border-rose-200 px-3 py-2 outline-none">
                        <option value="">— Chọn Tỉnh/Thành —</option>
                    </select>
                    <input type="hidden" name="province" id="hid_prov_name">
                    <input type="hidden" name="province_code" id="hid_prov_code">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Quận/Huyện<span class="text-red-500">*</span></label>
                    <select id="sel_dist" required class="w-full rounded-md border border-rose-200 px-3 py-2 outline-none">
                        <option value="">— Chọn Quận/Huyện —</option>
                    </select>
                    <input type="hidden" name="district" id="hid_dist_name">
                    <input type="hidden" name="district_code" id="hid_dist_code">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Phường/Xã<span class="text-red-500">*</span></label>
                    <select id="sel_ward" required class="w-full rounded-md border border-rose-200 px-3 py-2 outline-none">
                        <option value="">— Chọn Phường/Xã —</option>
                    </select>
                    <input type="hidden" name="ward" id="hid_ward_name">
                    <input type="hidden" name="ward_code" id="hid_ward_code">
                </div>

                {{-- lat/lng ẨN --}}
                <input type="hidden" name="lat" id="hid_lat">
                <input type="hidden" name="lng" id="hid_lng">

                {{-- MAP --}}
                <div class="md:col-span-2">
                    <div id="addrMap" class="w-full h-64 rounded-xl border border-rose-200 overflow-hidden"></div>
                    <div class="text-xs text-ink/60 mt-1">Bản đồ tự di chuyển theo Tỉnh → Quận → Phường; có thể kéo pin để chỉnh.</div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="button" data-close
                    class="rounded-lg border border-rose-200 px-4 py-2 text-sm hover:bg-rose-50">Huỷ</button>
                <button class="rounded-lg bg-brand-600 text-white px-4 py-2 text-sm hover:bg-brand-700">Lưu</button>
            </div>
        </form>
    </div>
</div>

{{-- ===== Modal XÁC NHẬN XOÁ (JS thuần) ===== --}}
<div id="confirmModal" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-black/40">
    <div class="bg-white rounded-2xl shadow-card p-6 w-[420px] max-w-[95vw]">
        <div class="text-lg font-semibold">Xoá địa chỉ?</div>
        <div class="text-sm text-ink/70 mt-2">Hành động này không thể hoàn tác.</div>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" data-close
                class="rounded-lg border border-rose-200 px-4 py-2 text-sm hover:bg-rose-50">Huỷ</button>
            <form id="confirmForm" method="POST" action="#">
                @csrf @method('delete')
                <button class="rounded-lg bg-rose-600 text-white px-4 py-2 text-sm hover:bg-rose-700">Xoá</button>
            </form>
        </div>
    </div>
</div>

{{-- Leaflet --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

{{-- JS THUẦN: không phụ thuộc Alpine --}}
<script>
    (function() {
        // ===== Toast 3s
        const t = document.getElementById('jsToast');
        if (t) setTimeout(() => t.remove(), 3000);

        // ===== Helpers modals
        const modalAddr = document.getElementById('addrModal');
        const modalConfirm = document.getElementById('confirmModal');

        function show(el) {
            el.classList.remove('hidden');
            el.classList.add('flex');
        }

        function hide(el) {
            el.classList.add('hidden');
            el.classList.remove('flex');
        }

        // ===== Elements form
        const form = document.getElementById('addrForm');
        const methodEl = document.getElementById('addrMethod');
        const titleEl = document.getElementById('addrTitle');

        const f_name = document.getElementById('f_name');
        const f_phone = document.getElementById('f_phone');
        const f_line1 = document.getElementById('f_line1');

        const selProv = document.getElementById('sel_prov');
        const selDist = document.getElementById('sel_dist');
        const selWard = document.getElementById('sel_ward');

        const hidProvName = document.getElementById('hid_prov_name');
        const hidProvCode = document.getElementById('hid_prov_code');
        const hidDistName = document.getElementById('hid_dist_name');
        const hidDistCode = document.getElementById('hid_dist_code');
        const hidWardName = document.getElementById('hid_ward_name');
        const hidWardCode = document.getElementById('hid_ward_code');
        const hidLat = document.getElementById('hid_lat');
        const hidLng = document.getElementById('hid_lng');

        // ===== Map (Leaflet)
        let map = null,
            marker = null;

        function ensureMap() {
            if (!map) {
                map = L.map('addrMap', {
                    scrollWheelZoom: false
                }).setView([15.9, 105.8], 5);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);
            }
            setTimeout(() => map.invalidateSize(), 50);
        }

        function clearMarker() {
            if (marker) {
                map.removeLayer(marker);
                marker = null;
            }
        }

        function setMarker(lat, lng, zoom = 15) {
            hidLat.value = (+lat).toFixed(6);
            hidLng.value = (+lng).toFixed(6);
            if (marker) marker.setLatLng([lat, lng]);
            else {
                marker = L.marker([lat, lng], {
                    draggable: true
                }).addTo(map);
                marker.on('dragend', e => {
                    const p = e.target.getLatLng();
                    hidLat.value = p.lat.toFixed(6);
                    hidLng.value = p.lng.toFixed(6);
                });
            }
            map.setView([lat, lng], zoom);
        }

        // ===== Provinces API
        async function loadProvinces(selectCode = null) {
            selProv.innerHTML = '<option value="">— Chọn Tỉnh/Thành —</option>';
            const res = await fetch('https://provinces.open-api.vn/api/p/');
            const data = await res.json();
            for (const p of data) {
                const o = document.createElement('option');
                o.value = p.code;
                o.textContent = p.name;
                if (String(p.code) === String(selectCode)) o.selected = true;
                selProv.appendChild(o);
            }
            onProvinceChange();
        }
        async function onProvinceChange() {
            const code = selProv.value;
            hidProvCode.value = code;
            hidProvName.value = selProv.options[selProv.selectedIndex]?.text || '';
            // reset
            selDist.innerHTML = '<option value="">— Chọn Quận/Huyện —</option>';
            selWard.innerHTML = '<option value="">— Chọn Phường/Xã —</option>';
            hidDistCode.value = hidDistName.value = hidWardCode.value = hidWardName.value = '';
            hidLat.value = hidLng.value = '';
            clearMarker();

            if (!code) {
                map.setView([15.9, 105.8], 5);
                return;
            }
            const res = await fetch('https://provinces.open-api.vn/api/p/' + code + '?depth=2');
            const p = await res.json();
            (p.districts || []).forEach(d => {
                const o = document.createElement('option');
                o.value = d.code;
                o.textContent = d.name;
                selDist.appendChild(o);
            });
            geocode(['', '', '', hidProvName.value], 8);
        }
        async function onDistrictChange() {
            const code = selDist.value;
            hidDistCode.value = code;
            hidDistName.value = selDist.options[selDist.selectedIndex]?.text || '';
            selWard.innerHTML = '<option value="">— Chọn Phường/Xã —</option>';
            hidWardCode.value = hidWardName.value = '';
            hidLat.value = hidLng.value = '';
            clearMarker();

            if (!code) return;
            const res = await fetch('https://provinces.open-api.vn/api/d/' + code + '?depth=2');
            const d = await res.json();
            (d.wards || []).forEach(w => {
                const o = document.createElement('option');
                o.value = w.code;
                o.textContent = w.name;
                selWard.appendChild(o);
            });
            geocode(['', '', hidDistName.value, hidProvName.value], 12);
        }

        function onWardChange() {
            hidWardCode.value = selWard.value;
            hidWardName.value = selWard.options[selWard.selectedIndex]?.text || '';
            tryGeocode(true);
        }

        // ===== Geocode (Nominatim) + debounce
        let geoTimer = null;

        function tryGeocode(force = false) {
            clearTimeout(geoTimer);
            geoTimer = setTimeout(() => {
                const q = [f_line1.value, hidWardName.value, hidDistName.value, hidProvName.value, 'Việt Nam']
                    .filter(Boolean).join(', ');
                geocode(q, 15);
            }, force ? 60 : 500);
        }
        async function geocode(q, zoom = 15) {
            if (!q || String(q).trim() === '') return;
            try {
                const url = 'https://nominatim.openstreetmap.org/search?format=json&countrycodes=VN&limit=1&q=' + encodeURIComponent(q);
                const r = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    },
                    cache: 'no-store'
                });
                const d = await r.json();
                if (Array.isArray(d) && d[0]) {
                    const lat = +d[0].lat,
                        lng = +d[0].lon;
                    if (!Number.isNaN(lat) && !Number.isNaN(lng)) setMarker(lat, lng, zoom);
                }
            } catch (e) {}
        }

        // ===== Open Add
        function openCreate() {
            titleEl.textContent = 'Thêm địa chỉ';
            form.action = @json($addrStore);
            // _method trống -> bỏ name để không spoof
            methodEl.removeAttribute('name');
            methodEl.value = '';

            f_name.value = '';
            f_phone.value = '';
            f_line1.value = '';
            selProv.value = '';
            selDist.innerHTML = '<option value="">— Chọn Quận/Huyện —</option>';
            selWard.innerHTML = '<option value="">— Chọn Phường/Xã —</option>';
            hidProvName.value = hidProvCode.value = '';
            hidDistName.value = hidDistCode.value = '';
            hidWardName.value = hidWardCode.value = '';
            hidLat.value = hidLng.value = '';
            ensureMap();
            clearMarker();
            map.setView([15.9, 105.8], 5);
            loadProvinces(null);
            show(modalAddr);
        }

        // ===== Open Edit
        function openEdit(data, updateUrl) {
            titleEl.textContent = 'Sửa địa chỉ';
            form.action = updateUrl;
            methodEl.setAttribute('name', '_method');
            methodEl.value = 'PATCH';

            f_name.value = data.name || '';
            f_phone.value = data.phone || '';
            f_line1.value = data.line1 || '';

            ensureMap();
            clearMarker();
            hidLat.value = data.lat || '';
            hidLng.value = data.lng || '';
            if (hidLat.value && hidLng.value) setMarker(+hidLat.value, +hidLng.value, 15);
            else map.setView([15.9, 105.8], 5);

            // load selects theo code/names sẵn có
            loadProvinces(data.province_code || null).then(() => {
                if (data.province_code) {
                    selDist.innerHTML = '<option value="">— Chọn Quận/Huyện —</option>';
                    fetch('https://provinces.open-api.vn/api/p/' + data.province_code + '?depth=2')
                        .then(r => r.json()).then(p => {
                            (p.districts || []).forEach(d => {
                                const o = document.createElement('option');
                                o.value = d.code;
                                o.textContent = d.name;
                                if (String(d.code) === String(data.district_code)) o.selected = true;
                                selDist.appendChild(o);
                            });
                            hidProvCode.value = data.province_code || '';
                            hidProvName.value = data.province || '';
                            hidDistCode.value = data.district_code || '';
                            hidDistName.value = data.district || '';
                            // wards
                            if (data.district_code) {
                                selWard.innerHTML = '<option value="">— Chọn Phường/Xã —</option>';
                                fetch('https://provinces.open-api.vn/api/d/' + data.district_code + '?depth=2')
                                    .then(r => r.json()).then(d => {
                                        (d.wards || []).forEach(w => {
                                            const o = document.createElement('option');
                                            o.value = w.code;
                                            o.textContent = w.name;
                                            if (String(w.code) === String(data.ward_code)) o.selected = true;
                                            selWard.appendChild(o);
                                        });
                                        hidWardCode.value = data.ward_code || '';
                                        hidWardName.value = data.ward || '';
                                    });
                            }
                        });
                }
            });

            show(modalAddr);
        }

        // ===== Delete confirm
        const confirmForm = document.getElementById('confirmForm');

        function openConfirm(action) {
            confirmForm.setAttribute('action', action);
            show(modalConfirm);
        }

        // ===== Events (delegation)
        document.addEventListener('click', (e) => {
            // close modals
            if (e.target.matches('[data-close]') || e.target === modalAddr) hide(modalAddr);
            if (e.target.matches('[data-close]') || e.target === modalConfirm) hide(modalConfirm);

            // Add
            if (e.target.closest('#btnAdd')) openCreate();

            // Edit
            const btnEdit = e.target.closest('.btn-edit');
            if (btnEdit) {
                try {
                    const data = JSON.parse(btnEdit.getAttribute('data-address'));
                    const action = btnEdit.getAttribute('data-update');
                    openEdit(data, action);
                } catch (_) {}
            }

            // Delete
            const btnDel = e.target.closest('.btn-delete');
            if (btnDel) {
                openConfirm(btnDel.getAttribute('data-action'));
            }
        });

        // Province cascade + map jump
        selProv.addEventListener('change', onProvinceChange);
        selDist.addEventListener('change', onDistrictChange);
        selWard.addEventListener('change', onWardChange);

        // Address input => geocode debounce
        f_line1.addEventListener('input', () => tryGeocode(false));

        // Khởi tạo map lần đầu (để tránh giật)
        ensureMap();
    })();
</script>
@endsection