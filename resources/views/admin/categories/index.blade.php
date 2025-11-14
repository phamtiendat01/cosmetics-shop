@extends('admin.layouts.app')
@section('title','Danh mục')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

<div class="toolbar">
    <div class="toolbar-title">Quản lý danh mục</div>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">+ Thêm</a>
</div>

{{-- Tabs giống đơn hàng --}}
@php $v = $filters['view'] ?? 'all'; @endphp
<div class="mb-3 flex flex-wrap gap-2 text-sm">
    <a class="btn btn-sm {{ $v==='all'?'btn-primary':'btn-outline' }}"
        href="{{ route('admin.categories.index', array_merge(request()->except('page'), ['view'=>'all'])) }}">
        Tất cả ({{ $stats['all'] ?? 0 }})
    </a>
    <a class="btn btn-sm {{ $v==='parents'?'btn-primary':'btn-outline' }}"
        href="{{ route('admin.categories.index', array_merge(request()->except('page'), ['view'=>'parents'])) }}">
        Chỉ danh mục cha ({{ $stats['parents'] ?? 0 }})
    </a>
    <a class="btn btn-sm {{ $v==='children'?'btn-primary':'btn-outline' }}"
        href="{{ route('admin.categories.index', array_merge(request()->except('page'), ['view'=>'children'])) }}">
        Chỉ danh mục con ({{ $stats['children'] ?? 0 }})
    </a>
</div>

{{-- Bộ lọc --}}
<div class="card p-3 mb-3">
    <form id="filterForm" method="get" class="grid md:grid-cols-4 gap-2 items-center">
        <input name="keyword" value="{{ $filters['keyword'] ?? '' }}" class="form-control" placeholder="Tìm theo tên hoặc slug…">

        <select name="parent_id" id="parentSelect" class="form-control">
            <option value="">Tất cả danh mục cha</option>
            @foreach($parentsOpts as $id => $text)
            <option value="{{ $id }}" @selected(($filters['parent_id'] ?? '' )==$id)>{{ $text }}</option>
            @endforeach
        </select>

        <select name="status" id="statusSelect" class="form-control">
            <option value="">Tất cả trạng thái</option>
            <option value="active" @selected(($filters['status'] ?? '' )==='active' )>Đang hiển thị</option>
            <option value="inactive" @selected(($filters['status'] ?? '' )==='inactive' )>Đang ẩn</option>
        </select>

        <input type="hidden" name="view" value="{{ $filters['view'] ?? 'all' }}">
        <div class="flex gap-2 items-center">
            <button class="btn btn-soft btn-sm">Lọc</button>
            <a class="btn btn-outline btn-sm" href="{{ route('admin.categories.index', ['view' => $filters['view'] ?? 'all']) }}">Reset</a>
        </div>
    </form>
</div>

@if($mode === 'tree')
{{-- =================== TREE MODE: CHA -> xổ CON =================== --}}
<div class="card table-wrap p-0">
    <table class="table-admin">
        <thead>
            <tr>
                <th style="width:34px"></th>
                <th>Danh mục</th>
                <th>Slug</th>
                <th>SP</th>
                <th>Con</th>
                <th>Thứ tự</th>
                <th>Trạng thái</th>
                <th class="col-actions">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($parents as $p)
            {{-- Row CHA --}}
            <tr class="bg-slate-50">
                <td>
                    @if($p->children_count>0)
                    <button type="button" class="btn btn-table btn-outline" data-toggle="#child-{{ $p->id }}">▸</button>
                    @endif
                </td>
                <td class="font-semibold">{{ $p->name }}</td>
                <td class="text-slate-500">{{ $p->slug }}</td>
                <td>{{ $p->products_count }}</td>
                <td>{{ $p->children_count }}</td>
                <td>{{ $p->sort_order }}</td>
                <td>
                    @if($p->is_active)
                    <span class="badge badge-green"><span class="badge-dot"></span>Hiển thị</span>
                    @else
                    <span class="badge badge-red"><span class="badge-dot"></span>Ẩn</span>
                    @endif
                </td>
                <td class="col-actions">
                    <a class="btn btn-table btn-outline" href="{{ route('admin.categories.edit',$p) }}">Sửa</a>
                    <form method="post" action="{{ route('admin.categories.toggle',$p) }}" class="inline">@csrf
                        <button class="btn btn-table btn-outline" type="submit">{{ $p->is_active?'Ẩn':'Hiện' }}</button>
                    </form>
                    <button type="button" class="btn btn-table btn-danger" data-confirm-delete data-url="{{ route('admin.categories.destroy',$p) }}">Xoá</button>
                </td>
            </tr>

            {{-- Bảng CON của cha --}}
            @if($p->children->count())
            <tr id="child-{{ $p->id }}" class="hidden">
                <td></td>
                <td colspan="7" class="p-0">
                    <table class="table-admin inner">
                        <thead>
                            <tr>
                                <th>Tên</th>
                                <th>Slug</th>
                                <th>SP</th>
                                <th>Con</th>
                                <th>Thứ tự</th>
                                <th>Trạng thái</th>
                                <th class="col-actions">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($p->children as $c)
                            <tr>
                                <td>{{ $c->name }}</td>
                                <td class="text-slate-500">{{ $c->slug }}</td>
                                <td>{{ $c->products_count }}</td>
                                <td>{{ $c->children_count }}</td>
                                <td>{{ $c->sort_order }}</td>
                                <td>
                                    @if($c->is_active)
                                    <span class="badge badge-green"><span class="badge-dot"></span>Hiển thị</span>
                                    @else
                                    <span class="badge badge-red"><span class="badge-dot"></span>Ẩn</span>
                                    @endif
                                </td>
                                <td class="col-actions">
                                    <a class="btn btn-table btn-outline" href="{{ route('admin.categories.edit',$c) }}">Sửa</a>
                                    <form method="post" action="{{ route('admin.categories.toggle',$c) }}" class="inline">@csrf
                                        <button class="btn btn-table btn-outline" type="submit">{{ $c->is_active?'Ẩn':'Hiện' }}</button>
                                    </form>
                                    <button type="button" class="btn btn-table btn-danger" data-confirm-delete data-url="{{ route('admin.categories.destroy',$c) }}">Xoá</button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
            @endif
            @empty
            <tr>
                <td colspan="8" class="py-6 text-center text-slate-500">Chưa có danh mục.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@else
{{-- =================== LIST MODE: bảng phẳng + phân trang =================== --}}
@php
$from = $categories->total() ? (($categories->currentPage()-1) * $categories->perPage() + 1) : 0;
$to = $categories->total() ? ($from + $categories->count() - 1) : 0;
@endphp
@if($categories->total() > 0)
<div class="mb-2 text-sm text-slate-600">Hiển thị {{ $from }}–{{ $to }} / {{ $categories->total() }} danh mục</div>
@endif

<form method="post" action="{{ route('admin.categories.bulk') }}">
    @csrf
    <div class="card table-wrap p-0">
        <table class="table-admin" id="catTable">
            <thead>
                <tr>
                    <th style="width:34px"><input type="checkbox" id="chkAll"></th>
                    <th>STT</th>
                    <th>Tên</th>
                    <th>Slug</th>
                    <th>Danh mục cha</th>
                    <th>SP</th>
                    <th>Con</th>
                    <th>Thứ tự</th>
                    <th>Trạng thái</th>
                    <th class="col-actions">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $i => $c)
                <tr>
                    <td><input type="checkbox" name="ids[]" value="{{ $c->id }}"></td>
                    <td>{{ ($categories->currentPage()-1)*$categories->perPage() + $i + 1 }}</td>
                    <td class="font-medium">
                        @if($c->parent_id) <span class="text-slate-400">—</span> @endif {{ $c->name }}
                    </td>
                    <td class="text-slate-500">{{ $c->slug }}</td>
                    <td>{{ $c->parent?->name ?? '—' }}</td>
                    <td>{{ $c->products_count }}</td>
                    <td>{{ $c->children_count }}</td>
                    <td>{{ $c->sort_order }}</td>
                    <td>
                        @if($c->is_active)
                        <span class="badge badge-green"><span class="badge-dot"></span>Hiển thị</span>
                        @else
                        <span class="badge badge-red"><span class="badge-dot"></span>Ẩn</span>
                        @endif
                    </td>
                    <td class="col-actions">
                        <a class="btn btn-table btn-outline" href="{{ route('admin.categories.edit',$c) }}">Sửa</a>
                        <form method="post" action="{{ route('admin.categories.toggle',$c) }}" class="inline">@csrf
                            <button class="btn btn-table btn-outline" type="submit">{{ $c->is_active?'Ẩn':'Hiện' }}</button>
                        </form>
                        <button type="button" class="btn btn-table btn-danger" data-confirm-delete data-url="{{ route('admin.categories.destroy',$c) }}">Xoá</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="py-6 text-center text-slate-500">Chưa có danh mục.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-2 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <select name="act" class="form-control" style="max-width:180px">
                <option value="activate">Bật hiển thị</option>
                <option value="deactivate">Tắt hiển thị</option>
                <option value="delete">Xoá đã chọn</option>
            </select>
            <button class="btn btn-secondary btn-sm">Áp dụng</button>
        </div>
        <div class="pagination">{{ $categories->onEachSide(1)->links('pagination::tailwind') }}</div>
    </div>
</form>
@endif

{{-- Modal xác nhận xoá (dùng chung) --}}
<div id="confirmModal" class="modal hidden">
    <div class="modal-card">
        <div class="p-4 border-b">
            <div class="font-semibold">Xác nhận xoá</div>
            <div class="text-sm text-slate-500">Bạn chắc chắn muốn xoá danh mục này?</div>
        </div>
        <div class="p-4 flex justify-end gap-2">
            <button class="btn btn-outline btn-sm" data-close-modal>Huỷ</button>
            <form id="confirmForm" method="post">@csrf @method('DELETE')
                <button class="btn btn-danger btn-sm">Xoá</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // TomSelect
    if (document.getElementById('parentSelect')) new TomSelect('#parentSelect', {
        create: false,
        maxOptions: 1000
    });
    if (document.getElementById('statusSelect')) new TomSelect('#statusSelect', {
        create: false
    });

    // Toggle tree rows
    document.querySelectorAll('[data-toggle]')?.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.querySelector(btn.dataset.toggle);
            if (!target) return;
            target.classList.toggle('hidden');
            btn.textContent = target.classList.contains('hidden') ? '▸' : '▾';
        });
    });

    // Modal confirm delete
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

    // Check all (list mode)
    const chkAll = document.getElementById('chkAll');
    chkAll?.addEventListener('change', () => {
        document.querySelectorAll('input[name="ids[]"]').forEach(c => c.checked = chkAll.checked);
    });
</script>
@endpush
@endsection 