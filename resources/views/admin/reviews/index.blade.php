@extends('admin.layouts.app')
@section('title','Đánh giá')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
@endif

<div class="toolbar">
    <div class="toolbar-title">Quản lý đánh giá sản phẩm</div>
</div>

@php
// Chuẩn hoá filters theo controller mới (q, product, rating, state)
$filters = $filters ?? [];
$state = (string)($filters['state'] ?? '');
$q = (string)($filters['q'] ?? '');
$product = (string)($filters['product'] ?? '');
$rating = (string)($filters['rating'] ?? '');
$counts = $counts ?? ['all'=>0,'approved'=>0,'pending'=>0];

$tab = function(string $key, string $text, int $cnt) use ($state) {
$active = $state === $key ? 'btn-primary' : 'btn-outline';
$url = $key !== '' ? request()->fullUrlWithQuery(['state' => $key, 'page' => 1])
: route('admin.reviews.index');
return "<a class=\"btn btn-sm $active\" href=\"$url\">$text ($cnt)</a>";
};
@endphp

<div class="mb-3 flex flex-wrap gap-2 text-sm">
    {!! $tab('', 'Tất cả', $counts['all'] ?? 0) !!}
    {!! $tab('approved', 'Đã duyệt',$counts['approved'] ?? 0) !!}
    {!! $tab('pending', 'Chờ duyệt',$counts['pending'] ?? 0) !!}
</div>

<div class="card p-3 mb-3">
    <form id="filterForm" method="get" class="grid md:grid-cols-5 gap-2 items-center">
        <input name="q" value="{{ $q }}" class="form-control search" placeholder="Tìm theo tiêu đề / nội dung…">
        <input name="product" value="{{ $product }}" class="form-control" placeholder="Tên sản phẩm…">

        <select name="rating" class="form-control">
            <option value="">Sao (tất cả)</option>
            @for($i=5;$i>=1;$i--)
            <option value="{{ $i }}" @selected($rating===$i.'')>{{ $i }} sao</option>
            @endfor
        </select>

        <select name="state" class="form-control">
            <option value="">Trạng thái</option>
            <option value="approved" @selected($state==='approved' )>Đã duyệt</option>
            <option value="pending" @selected($state==='pending' )>Chờ duyệt</option>
        </select>

        <div class="flex items-center gap-2">
            <button class="btn btn-soft btn-sm">Lọc</button>
            <a href="{{ route('admin.reviews.index') }}" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>

<form id="bulkForm" method="post" class="card p-3 mb-3 hidden">
    @csrf
    <input type="hidden" id="bulkMethod" name="_method" value="POST">
    <div class="flex items-center gap-2">
        <span class="text-sm text-slate-600" id="bulkCount">0 đã chọn</span>

        {{-- bulk approve = POST --}}
        <button id="btnBulkApprove"
            formaction="{{ route('admin.reviews.bulk-approve') }}"
            formmethod="POST"
            class="btn btn-primary btn-sm">
            Duyệt đã chọn
        </button>

        {{-- bulk destroy = DELETE --}}
        <button id="btnBulkDestroy"
            formaction="{{ route('admin.reviews.bulk-destroy') }}"
            formmethod="POST"
            class="btn btn-danger btn-sm">
            Xoá đã chọn
        </button>
    </div>
    <div id="bulkInputs"></div>
</form>

@php
$from = $reviews->total() ? (($reviews->currentPage()-1) * $reviews->perPage() + 1) : 0;
$to = $reviews->total() ? ($from + $reviews->count() - 1) : 0;
@endphp
@if($reviews->total() > 0)
<div class="mb-2 text-sm text-slate-600">
    Hiển thị {{ $from }}–{{ $to }} / {{ $reviews->total() }} đánh giá
</div>
@endif

<div class="card table-wrap p-0">
    <table class="table-admin w-full">
        {{-- Cập nhật đúng số cột --}}
        <colgroup>
            <col style="width:36px">
            <col> {{-- Đánh giá --}}
            <col style="width:260px"> {{-- Sản phẩm --}}
            <col style="width:180px"> {{-- Người dùng --}}
            <col style="width:120px"> {{-- Sao --}}
            <col style="width:140px"> {{-- Trạng thái --}}
            <col style="width:150px"> {{-- Thời gian --}}
            <col style="width:180px"> {{-- Actions --}}
        </colgroup>
        <thead>
            <tr>
                <th><input type="checkbox" id="chkAll"></th>
                <th>Đánh giá</th>
                <th>Sản phẩm</th>
                <th>Người dùng</th>
                <th>Sao</th>
                <th>Trạng thái</th>
                <th>Thời gian</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($reviews as $r)
            <tr>
                <td><input type="checkbox" class="row-chk" value="{{ $r->id }}"></td>
                <td>
                    <div class="font-medium">{{ $r->title ?: '—' }}</div>
                    <div class="text-sm text-slate-600 line-clamp-2">{{ $r->content }}</div>
                </td>
                <td>
                    @if($r->product)
                    <a class="text-blue-600 hover:underline"
                        href="{{ route('product.show', $r->product->slug) }}" target="_blank">
                        {{ $r->product->name }}
                    </a>
                    @else
                    —
                    @endif
                </td>
                <td>{{ optional($r->user)->name ?? 'Ẩn danh' }}</td>
                <td>
                    <div class="text-amber-500">
                        @for($i=1;$i<=5;$i++)
                            <i class="fa-solid fa-star {{ $i <= ((int)$r->rating) ? '' : 'opacity-30' }}"></i>
                            @endfor
                    </div>
                </td>
                <td>
                    @if($r->is_approved)
                    <span class="badge badge-green">
                        <span class="badge-dot" style="background:#10b981"></span> Đã duyệt
                    </span>
                    @else
                    <span class="badge badge-amber">
                        <span class="badge-dot" style="background:#f59e0b"></span> Chờ duyệt
                    </span>
                    @endif
                </td>
                <td class="text-sm text-slate-600">{{ optional($r->created_at)->format('d/m/Y H:i') }}</td>
                <td class="text-right">
                    <div class="actions">
                        @if(!$r->is_approved)
                        <form method="post" action="{{ route('admin.reviews.approve',$r) }}" class="inline">
                            @csrf @method('PATCH')
                            <button class="btn btn-soft btn-sm">Duyệt</button>
                        </form>
                        @else
                        <form method="post" action="{{ route('admin.reviews.unapprove',$r) }}" class="inline">
                            @csrf @method('PATCH')
                            <button class="btn btn-outline btn-sm">Bỏ duyệt</button>
                        </form>
                        @endif

                        <a href="{{ route('admin.reviews.show',$r) }}" class="btn btn-ghost btn-sm">Chi tiết</a>

                        <form method="post" action="{{ route('admin.reviews.destroy',$r) }}" class="inline"
                            onsubmit="return confirm('Xoá đánh giá này?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Xoá</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="py-6 text-center text-slate-500">Chưa có đánh giá.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination mt-3">
    {{ $reviews->onEachSide(1)->links('pagination::tailwind') }}
</div>

@push('scripts')
<script>
    (function() {
        const chkAll = document.getElementById('chkAll');
        const checks = Array.from(document.querySelectorAll('.row-chk'));
        const bulk = document.getElementById('bulkForm');
        const bulkCnt = document.getElementById('bulkCount');
        const bulkInputs = document.getElementById('bulkInputs');
        const bulkMethod = document.getElementById('bulkMethod');
        const btnApprove = document.getElementById('btnBulkApprove');
        const btnDestroy = document.getElementById('btnBulkDestroy');

        function refresh() {
            const ids = checks.filter(c => c.checked).map(c => c.value);
            bulk.classList.toggle('hidden', ids.length === 0);
            bulkCnt.textContent = ids.length + ' đã chọn';
            bulkInputs.innerHTML = ids.map(id => `<input type="hidden" name="ids[]" value="${id}">`).join('');
        }
        chkAll?.addEventListener('change', () => {
            checks.forEach(c => c.checked = chkAll.checked);
            refresh();
        });
        checks.forEach(c => c.addEventListener('change', refresh));

        btnApprove?.addEventListener('click', () => {
            bulkMethod.value = 'POST';
        });
        btnDestroy?.addEventListener('click', (e) => {
            if (!confirm('Xoá các đánh giá đã chọn?')) {
                e.preventDefault();
                return;
            }
            bulkMethod.value = 'DELETE';
        });
    })();
</script>
@endpush
@endsection