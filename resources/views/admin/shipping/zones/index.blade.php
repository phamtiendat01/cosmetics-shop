@extends('admin.layouts.app')
@section('title','Khu vực / Tuyến')

@section('content')
@include('admin.shipping._nav')
@php use App\Support\VNProvinces; @endphp

@if(session('ok')) <div class="alert alert-success mb-4" data-auto-dismiss="2200">{{ session('ok') }}</div> @endif
@if($errors->any())
<div class="mb-3 rounded border border-red-200 bg-red-50 text-red-700 p-3">
    <ul class="list-disc pl-5 text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="flex items-center justify-between mb-4">
    <h1 class="text-lg font-semibold">Khu vực / Tuyến</h1>
    <button data-modal-target="zoneCreateModal" data-modal-toggle="zoneCreateModal"
        class="px-3 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700">
        <i class="fa-solid fa-plus mr-2"></i> Thêm khu vực
    </button>
</div>

<div class="rounded-xl border border-slate-200 bg-white overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50">
            <tr class="text-left">
                <th class="px-4 py-2">Tên</th>
                <th class="px-4 py-2 w-40">Số tỉnh</th>
                <th class="px-4 py-2 w-32">Trạng thái</th>
                <th class="px-4 py-2 w-48 text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($zones as $z)
            <tr class="border-t border-slate-100">
                <td class="px-4 py-2">{{ $z->name }}</td>
                <td class="px-4 py-2">{{ count($z->province_codes ?? []) }}</td>
                <td class="px-4 py-2">{{ $z->enabled ? 'Bật' : 'Tắt' }}</td>
                <td class="px-4 py-2 text-right">
                    <button
                        class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-xs"
                        data-modal-target="zoneEditModal" data-modal-toggle="zoneEditModal"
                        data-id="{{ $z->id }}"
                        data-name="{{ $z->name }}"
                        data-enabled="{{ $z->enabled?1:0 }}"
                        data-provinces='@json($z->province_codes ?? [])'>Sửa</button>

                    <form class="inline" method="POST" action="{{ route('admin.shipping.zones.destroy',$z) }}" onsubmit="return confirm('Xóa khu vực này?')">
                        @csrf @method('DELETE')
                        <button class="px-2 py-1 rounded bg-red-50 hover:bg-red-100 text-red-600 text-xs">Xóa</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-4 py-6 text-center text-slate-500">Chưa có khu vực.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $zones->links() }}</div>

{{-- Modal: Tạo --}}
<div id="zoneCreateModal" tabindex="-1" aria-hidden="true" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-3xl rounded-2xl border border-slate-200 bg-white shadow-lg">
            <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                <div class="font-semibold">Thêm khu vực</div>
                <button data-modal-hide="zoneCreateModal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.shipping.zones.store') }}" class="p-5 grid md:grid-cols-2 gap-4">
                @csrf
                <div class="md:col-span-2">
                    <label class="text-sm font-medium">Tên khu vực</label>
                    <input name="name" class="w-full rounded border-slate-300" required>
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium">Tỉnh/Thành áp dụng</label>
                    <select name="province_codes[]" id="zoneCreateProvinces" multiple class="w-full rounded border-slate-300">
                        @foreach(VNProvinces::LIST as $code=>$name)
                        <option value="{{ $code }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-500 mt-1">Để trống = áp dụng toàn quốc.</p>
                </div>
                <div class="md:col-span-2 flex items-center gap-6">
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="enabled" value="1" checked> Bật</label>
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button class="px-4 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700"><i class="fa-solid fa-floppy-disk mr-2"></i>Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Sửa --}}
<div id="zoneEditModal" tabindex="-1" aria-hidden="true" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-3xl rounded-2xl border border-slate-200 bg-white shadow-lg">
            <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                <div class="font-semibold">Sửa khu vực</div>
                <button data-modal-hide="zoneEditModal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="zoneEditForm" method="POST" class="p-5 grid md:grid-cols-2 gap-4">
                @csrf @method('PUT')
                <div class="md:col-span-2">
                    <label class="text-sm font-medium">Tên khu vực</label>
                    <input name="name" class="w-full rounded border-slate-300" required>
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium">Tỉnh/Thành áp dụng</label>
                    <select name="province_codes[]" id="zoneEditProvinces" multiple class="w-full rounded border-slate-300">
                        @foreach(VNProvinces::LIST as $code=>$name)
                        <option value="{{ $code }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2 flex items-center gap-6">
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="enabled" value="1"> Bật</label>
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button class="px-4 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700"><i class="fa-solid fa-floppy-disk mr-2"></i>Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // TomSelect cho modal
    let zoneCreateTS, zoneEditTS;
    document.addEventListener('DOMContentLoaded', () => {
        zoneCreateTS = new TomSelect('#zoneCreateProvinces', {
            plugins: ['remove_button'],
            create: false,
            sortField: {
                field: 'text'
            }
        });
        zoneEditTS = new TomSelect('#zoneEditProvinces', {
            plugins: ['remove_button'],
            create: false,
            sortField: {
                field: 'text'
            }
        });
    });

    // Điền dữ liệu Edit
    document.querySelectorAll('[data-modal-target="zoneEditModal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const form = document.getElementById('zoneEditForm');
            form.action = "{{ route('admin.shipping.zones.update', ':id') }}".replace(':id', id);
            form.querySelector('[name="name"]').value = btn.dataset.name || '';
            form.querySelector('[name="enabled"]').checked = (btn.dataset.enabled === '1');

            // provinces
            try {
                const arr = JSON.parse(btn.dataset.provinces || '[]');
                zoneEditTS.clear();
                zoneEditTS.addItems(arr);
            } catch (e) {
                zoneEditTS.clear();
            }
        });
    });
</script>
@endpush
@endsection