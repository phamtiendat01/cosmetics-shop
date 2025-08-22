@extends('admin.layouts.app')
@section('title','Biểu phí vận chuyển')

@section('content')
@include('admin.shipping._nav')

@if(session('ok')) <div class="alert alert-success mb-4" data-auto-dismiss="2200">{{ session('ok') }}</div> @endif
@if($errors->any())
<div class="mb-3 rounded border border-red-200 bg-red-50 text-red-700 p-3">
    <ul class="list-disc pl-5 text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

{{-- Bộ lọc + Thêm --}}
<div class="rounded-xl border border-slate-200 bg-white p-4 mb-4">
    <form class="grid md:grid-cols-4 gap-3">
        <div>
            <label class="text-xs text-slate-500">Đơn vị</label>
            <select name="carrier_id" class="w-full rounded border-slate-300">
                <option value="">Tất cả</option>
                @foreach($carriers as $c)
                <option value="{{ $c->id }}" @selected(request('carrier_id')==$c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs text-slate-500">Khu vực</label>
            <select name="zone_id" class="w-full rounded border-slate-300">
                <option value="">Tất cả</option>
                @foreach($zones as $z)
                <option value="{{ $z->id }}" @selected(request('zone_id')==$z->id)>{{ $z->name }}</option>
                @endforeach
                <option value="0" @selected(request('zone_id')==='0' )>Toàn quốc</option>
            </select>
        </div>
        <div class="md:self-end">
            <button class="px-3 py-2 rounded bg-slate-100 hover:bg-slate-200">Lọc</button>
        </div>
        <div class="md:self-end text-right">
            <button type="button" data-modal-target="rateCreateModal" data-modal-toggle="rateCreateModal"
                class="px-3 py-2 rounded bg-rose-600 text-white hover:bg-rose-700">
                <i class="fa-solid fa-plus mr-2"></i> Thêm biểu phí
            </button>
        </div>
    </form>
</div>

{{-- Bảng --}}
<div class="rounded-xl border border-slate-200 bg-white overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50">
            <tr class="text-left">
                <th class="px-4 py-2">Đơn vị</th>
                <th class="px-4 py-2">Khu vực</th>
                <th class="px-4 py-2">Gói</th>
                <th class="px-4 py-2">Điều kiện</th>
                <th class="px-4 py-2">Công thức</th>
                <th class="px-4 py-2">ETD</th>
                <th class="px-4 py-2 w-28">Trạng thái</th>
                <th class="px-4 py-2 w-44 text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rates as $r)
            <tr class="border-t border-slate-100">
                <td class="px-4 py-2">{{ $r->carrier->name }}</td>
                <td class="px-4 py-2">{{ $r->zone?->name ?? 'Toàn quốc' }}</td>
                <td class="px-4 py-2">{{ $r->name ?: 'Chuẩn' }}</td>
                <td class="px-4 py-2">
                    <div class="text-xs text-slate-600 space-y-1">
                        @if(!is_null($r->min_weight) || !is_null($r->max_weight))
                        <div>Cân: {{ $r->min_weight ?? 0 }}–{{ $r->max_weight ?? '∞' }} kg</div>
                        @endif
                        @if(!is_null($r->min_total) || !is_null($r->max_total))
                        <div>Đơn: {{ number_format($r->min_total ?? 0) }}–{{ $r->max_total?number_format($r->max_total):'∞' }} đ</div>
                        @endif
                    </div>
                </td>
                <td class="px-4 py-2">{{ number_format($r->base_fee) }}đ + {{ number_format($r->per_kg_fee) }}đ/kg (từ >1kg)</td>
                <td class="px-4 py-2">{{ $r->etd_min_days && $r->etd_max_days ? $r->etd_min_days.'-'.$r->etd_max_days.' ngày' : '—' }}</td>
                <td class="px-4 py-2">
                    @if($r->enabled)
                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Bật</span>
                    @else
                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-50 text-slate-600 border border-slate-200">Tắt</span>
                    @endif
                </td>
                <td class="px-4 py-2 text-right">
                    {{-- Toggle nhanh --}}
                    <form method="POST" action="{{ route('admin.shipping.rates.update',$r) }}" class="inline">
                        @csrf @method('PUT')
                        <input type="hidden" name="carrier_id" value="{{ $r->carrier_id }}">
                        <input type="hidden" name="zone_id" value="{{ $r->zone_id }}">
                        <input type="hidden" name="name" value="{{ $r->name }}">
                        <input type="hidden" name="min_weight" value="{{ $r->min_weight }}">
                        <input type="hidden" name="max_weight" value="{{ $r->max_weight }}">
                        <input type="hidden" name="min_total" value="{{ $r->min_total }}">
                        <input type="hidden" name="max_total" value="{{ $r->max_total }}">
                        <input type="hidden" name="base_fee" value="{{ $r->base_fee }}">
                        <input type="hidden" name="per_kg_fee" value="{{ $r->per_kg_fee }}">
                        <input type="hidden" name="etd_min_days" value="{{ $r->etd_min_days }}">
                        <input type="hidden" name="etd_max_days" value="{{ $r->etd_max_days }}">
                        <input type="hidden" name="enabled" value="{{ $r->enabled?0:1 }}">
                        <button class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-xs">Bật/Tắt</button>
                    </form>

                    {{-- Sửa (modal) --}}
                    <button
                        class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-xs"
                        data-modal-target="rateEditModal" data-modal-toggle="rateEditModal"
                        data-id="{{ $r->id }}"
                        data-carrier="{{ $r->carrier_id }}"
                        data-zone="{{ $r->zone_id ?? '' }}"
                        data-name="{{ $r->name }}"
                        data-minw="{{ $r->min_weight }}"
                        data-maxw="{{ $r->max_weight }}"
                        data-mint="{{ $r->min_total }}"
                        data-maxt="{{ $r->max_total }}"
                        data-base="{{ $r->base_fee }}"
                        data-perkg="{{ $r->per_kg_fee }}"
                        data-etdmin="{{ $r->etd_min_days }}"
                        data-etdmax="{{ $r->etd_max_days }}"
                        data-enabled="{{ $r->enabled?1:0 }}">Sửa</button>

                    <form class="inline" method="POST" action="{{ route('admin.shipping.rates.destroy',$r) }}" onsubmit="return confirm('Xóa biểu phí này?')">
                        @csrf @method('DELETE')
                        <button class="px-2 py-1 rounded bg-red-50 hover:bg-red-100 text-red-600 text-xs">Xóa</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-6 text-center text-slate-500">Chưa có biểu phí.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $rates->withQueryString()->links() }}</div>

{{-- Modal: Tạo --}}
<div id="rateCreateModal" tabindex="-1" aria-hidden="true" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-4xl rounded-2xl border border-slate-200 bg-white shadow-lg">
            <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                <div class="font-semibold">Thêm biểu phí</div>
                <button data-modal-hide="rateCreateModal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.shipping.rates.store') }}" class="p-5 grid md:grid-cols-6 gap-3">
                @csrf
                <div class="md:col-span-2">
                    <label class="text-sm font-medium">Đơn vị</label>
                    <select name="carrier_id" class="w-full rounded border-slate-300" required>
                        @foreach($carriers as $c) <option value="{{ $c->id }}">{{ $c->name }}</option> @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium">Khu vực</label>
                    <select name="zone_id" class="w-full rounded border-slate-300">
                        <option value="">Toàn quốc</option>
                        @foreach($zones as $z) <option value="{{ $z->id }}">{{ $z->name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium">Tên gói</label>
                    <input name="name" class="w-full rounded border-slate-300" placeholder="Chuẩn/Nhanh">
                </div>
                <div class="md:col-span-1 flex items-end">
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="enabled" value="1" checked> Bật</label>
                </div>

                <div>
                    <label class="text-sm font-medium">Min cân (kg)</label>
                    <input type="number" step="0.001" min="0" name="min_weight" class="w-full rounded border-slate-300">
                </div>
                <div>
                    <label class="text-sm font-medium">Max cân (kg)</label>
                    <input type="number" step="0.001" min="0" name="max_weight" class="w-full rounded border-slate-300">
                </div>
                <div>
                    <label class="text-sm font-medium">Min đơn (VND)</label>
                    <input type="number" min="0" name="min_total" class="w-full rounded border-slate-300">
                </div>
                <div>
                    <label class="text-sm font-medium">Max đơn (VND)</label>
                    <input type="number" min="0" name="max_total" class="w-full rounded border-slate-300">
                </div>

                <div>
                    <label class="text-sm font-medium">Phí cơ bản</label>
                    <input type="number" min="0" name="base_fee" class="w-full rounded border-slate-300" required>
                </div>
                <div>
                    <label class="text-sm font-medium">+ mỗi kg</label>
                    <input type="number" min="0" name="per_kg_fee" class="w-full rounded border-slate-300" required>
                </div>
                <div>
                    <label class="text-sm font-medium">ETD min (ngày)</label>
                    <input type="number" min="1" name="etd_min_days" class="w-full rounded border-slate-300">
                </div>
                <div>
                    <label class="text-sm font-medium">ETD max (ngày)</label>
                    <input type="number" min="1" name="etd_max_days" class="w-full rounded border-slate-300">
                </div>

                <div class="md:col-span-6 flex justify-end">
                    <button class="px-4 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700"><i class="fa-solid fa-floppy-disk mr-2"></i>Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Sửa --}}
<div id="rateEditModal" tabindex="-1" aria-hidden="true" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-4xl rounded-2xl border border-slate-200 bg-white shadow-lg">
            <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                <div class="font-semibold">Sửa biểu phí</div>
                <button data-modal-hide="rateEditModal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="rateEditForm" method="POST" class="p-5 grid md:grid-cols-6 gap-3">
                @csrf @method('PUT')
                <div class="md:col-span-2">
                    <label class="text-sm font-medium">Đơn vị</label>
                    <select name="carrier_id" class="w-full rounded border-slate-300" required>
                        @foreach($carriers as $c) <option value="{{ $c->id }}">{{ $c->name }}</option> @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium">Khu vực</label>
                    <select name="zone_id" class="w-full rounded border-slate-300">
                        <option value="">Toàn quốc</option>
                        @foreach($zones as $z) <option value="{{ $z->id }}">{{ $z->name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium">Tên gói</label>
                    <input name="name" class="w-full rounded border-slate-300">
                </div>
                <div class="md:col-span-1 flex items-end">
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="enabled" value="1"> Bật</label>
                </div>

                <div>
                    <label class="text-sm font-medium">Min cân (kg)</label>
                    <input type="number" step="0.001" min="0" name="min_weight" class="w-full rounded border-slate-300">
                </div>
                <div>
                    <label class="text-sm font-medium">Max cân (kg)</label>
                    <input type="number" step="0.001" min="0" name="max_weight" class="w-full rounded border-slate-300">
                </div>
                <div>
                    <label class="text-sm font-medium">Min đơn (VND)</label>
                    <input type="number" min="0" name="min_total" class="w-full rounded border-slate-300">
                </div>
                <div>
                    <label class="text-sm font-medium">Max đơn (VND)</label>
                    <input type="number" min="0" name="max_total" class="w-full rounded border-slate-300">
                </div>

                <div>
                    <label class="text-sm font-medium">Phí cơ bản</label>
                    <input type="number" min="0" name="base_fee" class="w-full rounded border-slate-300" required>
                </div>
                <div>
                    <label class="text-sm font-medium">+ mỗi kg</label>
                    <input type="number" min="0" name="per_kg_fee" class="w-full rounded border-slate-300" required>
                </div>
                <div>
                    <label class="text-sm font-medium">ETD min</label>
                    <input type="number" min="1" name="etd_min_days" class="w-full rounded border-slate-300">
                </div>
                <div>
                    <label class="text-sm font-medium">ETD max</label>
                    <input type="number" min="1" name="etd_max_days" class="w-full rounded border-slate-300">
                </div>

                <div class="md:col-span-6 flex justify-end">
                    <button class="px-4 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700"><i class="fa-solid fa-floppy-disk mr-2"></i>Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Điền dữ liệu vào modal Edit
    document.querySelectorAll('[data-modal-target="rateEditModal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const f = document.getElementById('rateEditForm');
            f.action = "{{ route('admin.shipping.rates.update', ':id') }}".replace(':id', btn.dataset.id);

            f.querySelector('[name="carrier_id"]').value = btn.dataset.carrier || '';
            f.querySelector('[name="zone_id"]').value = btn.dataset.zone || '';
            f.querySelector('[name="name"]').value = btn.dataset.name || '';

            f.querySelector('[name="min_weight"]').value = btn.dataset.minw || '';
            f.querySelector('[name="max_weight"]').value = btn.dataset.maxw || '';
            f.querySelector('[name="min_total"]').value = btn.dataset.mint || '';
            f.querySelector('[name="max_total"]').value = btn.dataset.maxt || '';

            f.querySelector('[name="base_fee"]').value = btn.dataset.base || 0;
            f.querySelector('[name="per_kg_fee"]').value = btn.dataset.perkg || 0;
            f.querySelector('[name="etd_min_days"]').value = btn.dataset.etdmin || '';
            f.querySelector('[name="etd_max_days"]').value = btn.dataset.etdmax || '';
            f.querySelector('[name="enabled"]').checked = (btn.dataset.enabled === '1');
        });
    });
</script>
@endpush
@endsection