@extends('admin.layouts.app')
@section('title','Đơn vị vận chuyển')

@section('content')
@include('admin.shipping._nav')

@if(session('ok'))
<div class="alert alert-success mb-4" data-auto-dismiss="2200">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="mb-3 rounded border border-red-200 bg-red-50 text-red-700 p-3">
    <ul class="list-disc pl-5 text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="flex items-center justify-between mb-4">
    <h1 class="text-lg font-semibold">Đơn vị vận chuyển</h1>
    <div class="flex items-center gap-2">
        <form class="hidden md:block">
            <input name="search" value="{{ request('search') }}" placeholder="Tìm tên / mã"
                class="rounded-lg border-slate-300 w-64" />
        </form>
        <button data-modal-target="carrierCreateModal" data-modal-toggle="carrierCreateModal"
            class="px-3 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700">
            <i class="fa-solid fa-plus mr-2"></i> Thêm đơn vị
        </button>
    </div>
</div>

@if($q->count())
<div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
    @foreach($q as $c)
    <div class="rounded-xl border border-slate-200 bg-white p-4 flex gap-3 items-center">
        <img src="{{ $c->logo ?: 'https://placehold.co/120x48?text=LOGO' }}" class="w-28 h-12 object-contain bg-white rounded">
        <div class="flex-1">
            <div class="flex items-center gap-2">
                <div class="font-medium">{{ $c->name }}</div>
                <span class="text-xs text-slate-500">({{ $c->code }})</span>
                @if($c->enabled)
                <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Bật</span>
                @else
                <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-50 text-slate-600 border border-slate-200">Tắt</span>
                @endif
            </div>
            <div class="text-xs text-slate-500 mt-1">COD: {{ $c->supports_cod ? 'Có' : 'Không' }}</div>
        </div>

        <div class="flex items-center gap-2">
            {{-- Toggle Bật/Tắt (submit nhanh) --}}
            <form method="POST" action="{{ route('admin.shipping.carriers.update',$c) }}">
                @csrf @method('PUT')
                <input type="hidden" name="name" value="{{ $c->name }}">
                <input type="hidden" name="code" value="{{ $c->code }}">
                <input type="hidden" name="logo" value="{{ $c->logo }}">
                <input type="hidden" name="supports_cod" value="{{ $c->supports_cod?1:0 }}">
                <input type="hidden" name="sort_order" value="{{ $c->sort_order }}">
                <input type="hidden" name="enabled" value="{{ $c->enabled?0:1 }}">
                <button class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-xs" title="Bật/Tắt">Bật/Tắt</button>
            </form>

            {{-- Sửa (modal) --}}
            <button
                class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-xs"
                data-modal-target="carrierEditModal" data-modal-toggle="carrierEditModal"
                data-id="{{ $c->id }}"
                data-name="{{ $c->name }}"
                data-code="{{ $c->code }}"
                data-logo="{{ $c->logo }}"
                data-cod="{{ $c->supports_cod ? 1 : 0 }}"
                data-enabled="{{ $c->enabled ? 1 : 0 }}"
                data-sort="{{ $c->sort_order }}">Sửa</button>

            {{-- Xóa --}}
            <form method="POST" action="{{ route('admin.shipping.carriers.destroy',$c) }}" onsubmit="return confirm('Xóa đơn vị vận chuyển này?')">
                @csrf @method('DELETE')
                <button class="px-2 py-1 rounded bg-red-50 hover:bg-red-100 text-red-600 text-xs">Xóa</button>
            </form>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-4">{{ $q->withQueryString()->links() }}</div>
@else
<div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
    Chưa có đơn vị nào. Bấm <button data-modal-target="carrierCreateModal" data-modal-toggle="carrierCreateModal" class="text-rose-600 hover:underline">Thêm đơn vị</button>.
</div>
@endif

{{-- Modal: Tạo --}}
<div id="carrierCreateModal" tabindex="-1" aria-hidden="true" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-lg">
            <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                <div class="font-semibold">Thêm đơn vị vận chuyển</div>
                <button data-modal-hide="carrierCreateModal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.shipping.carriers.store') }}" class="p-5 grid md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label class="text-sm font-medium">Tên</label>
                    <input name="name" class="w-full rounded border-slate-300" required>
                </div>
                <div>
                    <label class="text-sm font-medium">Mã (code)</label>
                    <input name="code" class="w-full rounded border-slate-300" required>
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium">Logo (URL)</label>
                    <input name="logo" class="w-full rounded border-slate-300">
                </div>
                <div class="md:col-span-2 flex items-center gap-6">
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="supports_cod" value="1" checked> Hỗ trợ COD</label>
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="enabled" value="1" checked> Bật</label>
                    <div class="ml-auto">
                        <label class="text-sm mr-2">Thứ tự</label>
                        <input type="number" name="sort_order" min="0" value="0" class="w-24 rounded border-slate-300">
                    </div>
                </div>
                <div class="md:col-span-2 flex justify-end pt-2">
                    <button class="px-4 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700"><i class="fa-solid fa-floppy-disk mr-2"></i>Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Sửa --}}
<div id="carrierEditModal" tabindex="-1" aria-hidden="true" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-lg">
            <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                <div class="font-semibold">Sửa đơn vị vận chuyển</div>
                <button data-modal-hide="carrierEditModal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="carrierEditForm" method="POST" class="p-5 grid md:grid-cols-2 gap-4">
                @csrf @method('PUT')
                <div>
                    <label class="text-sm font-medium">Tên</label>
                    <input name="name" class="w-full rounded border-slate-300" required>
                </div>
                <div>
                    <label class="text-sm font-medium">Mã (code)</label>
                    <input name="code" class="w-full rounded border-slate-300" required>
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium">Logo (URL)</label>
                    <input name="logo" class="w-full rounded border-slate-300">
                </div>
                <div class="md:col-span-2 flex items-center gap-6">
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="supports_cod" value="1"> Hỗ trợ COD</label>
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="enabled" value="1"> Bật</label>
                    <div class="ml-auto">
                        <label class="text-sm mr-2">Thứ tự</label>
                        <input type="number" name="sort_order" min="0" class="w-24 rounded border-slate-300">
                    </div>
                </div>
                <div class="md:col-span-2 flex justify-end pt-2">
                    <button class="px-4 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700"><i class="fa-solid fa-floppy-disk mr-2"></i>Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Điền dữ liệu vào modal edit
    document.querySelectorAll('[data-modal-target="carrierEditModal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const form = document.getElementById('carrierEditForm');
            form.action = "{{ route('admin.shipping.carriers.update', ':id') }}".replace(':id', id);
            form.querySelector('[name="name"]').value = btn.dataset.name || '';
            form.querySelector('[name="code"]').value = btn.dataset.code || '';
            form.querySelector('[name="logo"]').value = btn.dataset.logo || '';
            form.querySelector('[name="supports_cod"]').checked = btn.dataset.cod === '1';
            form.querySelector('[name="enabled"]').checked = btn.dataset.enabled === '1';
            form.querySelector('[name="sort_order"]').value = btn.dataset.sort || 0;
        });
    });
</script>
@endpush
@endsection