@extends('admin.layouts.app')
@section('title','Khách hàng')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

<div class="toolbar">
    <div class="toolbar-title">Quản lý khách hàng</div>
    <a href="{{ route('admin.customers.create') }}" class="btn btn-primary btn-sm">+ Thêm</a>
</div>

<div class="card p-3 mb-3">
    <form id="filterForm" method="get" class="grid md:grid-cols-6 gap-2 items-center">
        <input id="keywordInput" name="keyword" value="{{ $filters['keyword'] ?? '' }}" class="form-control" placeholder="Tìm theo tên / email / ĐT (live)…">

        <select name="status" id="statusSelect" class="form-control">
            <option value="">Tất cả trạng thái</option>
            <option value="active" @selected(($filters['status'] ?? '' )==='active' )>Đang hoạt động</option>
            <option value="inactive" @selected(($filters['status'] ?? '' )==='inactive' )>Đã khoá</option>
        </select>

        <select name="verified" id="verifiedSelect" class="form-control">
            <option value="">Xác thực email</option>
            <option value="yes" @selected(($filters['verified'] ?? '' )==='yes' )>Đã xác thực</option>
            <option value="no" @selected(($filters['verified'] ?? '' )==='no' )>Chưa xác thực</option>
        </select>

        <input name="date_from" id="dateFrom" class="form-control" value="{{ $filters['date_from'] ?? '' }}" placeholder="Từ ngày (đăng ký)">
        <input name="date_to" id="dateTo" class="form-control" value="{{ $filters['date_to']   ?? '' }}" placeholder="Đến ngày">

        <select name="sort" class="form-control">
            <option value="">Sắp xếp</option>
            <option value="created_at" @selected(($filters['sort'] ?? '' )==='created_at' )>Mới đăng ký</option>
            <option value="orders" @selected(($filters['sort'] ?? '' )==='orders' )>Nhiều đơn</option>
            <option value="total_spent" @selected(($filters['sort'] ?? '' )==='total_spent' )>Chi tiêu cao</option>
        </select>

        <div class="flex items-center gap-2 md:col-span-6">
            <button class="btn btn-soft btn-sm">Lọc</button>
            <button type="button" id="resetBtn" class="btn btn-outline btn-sm">Reset</button>
        </div>
    </form>
</div>

<form method="post" action="{{ route('admin.customers.bulk') }}">
    @csrf
    <div class="card table-wrap p-0">
        <table class="table-admin">
            <thead>
                <tr>
                    <th style="width:34px"><input type="checkbox" id="chkAll"></th>
                    <th>STT</th>
                    <th>Khách hàng</th>
                    <th>Email</th>
                    <th>Điện thoại</th>
                    <th>Đơn</th>
                    <th>Chi tiêu</th>
                    <th>Trạng thái</th>
                    <th>ĐK lúc</th>
                    <th class="col-actions">Thao tác</th>
                </tr>
            </thead>
            <tbody id="customerRows">
                @forelse($customers as $i => $u)
                <tr class="cust-row" data-name="{{ Str::lower(($u->name??'').' '.($u->email??'').' '.($u->phone??'')) }}">
                    <td><input type="checkbox" name="ids[]" value="{{ $u->id }}"></td>
                    <td>{{ ($customers->currentPage()-1)*$customers->perPage() + $i + 1 }}</td>
                    <td class="font-medium">
                        <div class="cell-thumb">
                            <img
                                class="thumb"
                                src="{{ $u->avatar_url }}"
                                alt="{{ $u->name }}"
                                onerror="this.onerror=null;this.src='https://i.pravatar.cc/120?u={{ urlencode($u->email ?? $u->id) }}'">
                            <div>
                                <div>{{ $u->name }}</div>
                                @if($u->email_verified_at)
                                <span class="badge badge-green"><span class="badge-dot"></span>Đã xác thực</span>
                                @else
                                <span class="badge"><span class="badge-dot"></span>Chưa xác thực</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="text-slate-700">{{ $u->email }}</td>
                    <td>{{ $u->phone ?? '—' }}</td>
                    <td>{{ $u->orders_count }}</td>
                    <td style="text-align:right">{{ number_format((float)$u->total_spent,0,',','.') }}₫</td>
                    <td>
                        @if($u->is_active)
                        <span class="badge badge-green"><span class="badge-dot"></span>Hoạt động</span>
                        @else
                        <span class="badge badge-red"><span class="badge-dot"></span>Đã khoá</span>
                        @endif
                    </td>
                    <td>{{ $u->created_at?->format('d/m/Y') }}</td>
                    <td class="col-actions">
                        <a class="btn btn-table btn-outline" href="{{ route('admin.customers.show',$u) }}">Xem</a>
                        <a class="btn btn-table btn-outline" href="{{ route('admin.customers.edit',$u) }}">Sửa</a>
                        <form method="post" action="{{ route('admin.customers.toggle',$u) }}" class="inline">
                            @csrf
                            <button class="btn btn-table btn-outline" type="submit">{{ $u->is_active?'Khoá':'Mở' }}</button>
                        </form>
                        <button type="button" class="btn btn-table btn-danger" data-confirm-delete data-url="{{ route('admin.customers.destroy',$u) }}">Xoá</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="py-6 text-center text-slate-500">Chưa có khách hàng.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-2 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <select name="act" class="form-control" style="max-width:180px">
                <option value="activate">Mở khoá</option>
                <option value="deactivate">Khoá</option>
                <option value="delete">Xoá đã chọn</option>
            </select>
            <button class="btn btn-secondary btn-sm">Áp dụng</button>
        </div>
        <div class="pagination">{{ $customers->links() }}</div>
    </div>
</form>

{{-- Modal xác nhận xoá --}}
<div id="confirmModal" class="modal hidden">
    <div class="modal-card">
        <div class="p-4 border-b">
            <div class="font-semibold">Xác nhận xoá</div>
            <div class="text-sm text-slate-500">Xoá khách hàng này? Thao tác không thể hoàn tác.</div>
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
    flatpickr("#dateFrom", {
        dateFormat: "Y-m-d"
    });
    flatpickr("#dateTo", {
        dateFormat: "Y-m-d"
    });

    const kw = document.getElementById('keywordInput');
    const rows = Array.from(document.querySelectorAll('.cust-row'));
    let timer;

    function doFilter() {
        const q = (kw.value || '').trim().toLowerCase();
        rows.forEach(tr => {
            tr.style.display = (!q || tr.dataset.name.includes(q)) ? '' : 'none';
        });
    }
    kw.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(doFilter, 180);
    });
    kw.addEventListener('keydown', e => {
        if (e.key === 'Enter') e.preventDefault();
    });
    doFilter();

    document.getElementById('resetBtn').addEventListener('click', () => {
        document.getElementById('filterForm').reset();
        kw.value = '';
        doFilter();
        document.getElementById('filterForm').submit();
    });

    const chkAll = document.getElementById('chkAll');
    chkAll.addEventListener('change', () => {
        document.querySelectorAll('input[name="ids[]"]').forEach(c => c.checked = chkAll.checked);
    });

    const modal = document.getElementById('confirmModal');
    const form = document.getElementById('confirmForm');
    document.querySelectorAll('[data-confirm-delete]').forEach(btn => {
        btn.addEventListener('click', () => {
            form.action = btn.dataset.url;
            modal.classList.remove('hidden');
        });
    });
    document.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', () => modal.classList.add('hidden')));
    modal.addEventListener('click', e => {
        if (e.target === modal) modal.classList.add('hidden');
    });
</script>
@endpush
@endsection