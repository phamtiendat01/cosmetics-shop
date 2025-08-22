@extends('admin.layouts.app')
@section('title','Mã giảm giá')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif

{{-- Toolbar --}}
<div class="toolbar">
    <div class="toolbar-title">Quản lý mã giảm giá</div>
    <div class="toolbar-actions">
        <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Tạo mã
        </a>
    </div>
</div>

{{-- Tabs lọc nhanh --}}
@php $qs = request()->except('page','status'); $cur = request('status'); @endphp
<div class="card p-2 mb-3">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.coupons.index', $qs) }}"
            class="btn btn-ghost btn-sm {{ $cur===null || $cur==='' ? 'ring-1 ring-rose-200' : '' }}">Tất cả</a>
        <a href="{{ route('admin.coupons.index', array_merge($qs,['status'=>'ongoing'])) }}"
            class="btn btn-ghost btn-sm {{ $cur==='ongoing' ? 'ring-1 ring-rose-200' : '' }}">Đang diễn ra</a>
        <a href="{{ route('admin.coupons.index', array_merge($qs,['status'=>'expired'])) }}"
            class="btn btn-ghost btn-sm {{ $cur==='expired' ? 'ring-1 ring-rose-200' : '' }}">Hết hạn</a>
        <a href="{{ route('admin.coupons.index', array_merge($qs,['status'=>'active'])) }}"
            class="btn btn-ghost btn-sm {{ $cur==='active' ? 'ring-1 ring-rose-200' : '' }}">Đang bật</a>
        <a href="{{ route('admin.coupons.index', array_merge($qs,['status'=>'inactive'])) }}"
            class="btn btn-ghost btn-sm {{ $cur==='inactive' ? 'ring-1 ring-rose-200' : '' }}">Đang tắt</a>
    </div>
</div>

{{-- Bộ lọc chi tiết --}}
<div class="card p-3 mb-3">
    <form method="get" class="grid md:grid-cols-5 gap-2 items-center">
        <div class="md:col-span-2">
            <input class="form-control" name="keyword" value="{{ $filters['keyword'] ?? '' }}"
                placeholder="Tìm theo mã / tên…">
        </div>

        <select class="form-control" name="status">
            <option value="">Tất cả trạng thái</option>
            @foreach(['active'=>'Đang bật','inactive'=>'Đang tắt','ongoing'=>'Đang diễn ra','expired'=>'Hết hạn'] as $k=>$v)
            <option value="{{ $k }}" @selected(($filters['status']??'')===$k)>{{ $v }}</option>
            @endforeach
        </select>

        <select class="form-control" name="type">
            <option value="">Tất cả loại giảm</option>
            <option value="percent" @selected(request('type')==='percent' )>% theo đơn</option>
            <option value="fixed" @selected(request('type')==='fixed' )>Số tiền cố định</option>
        </select>

        <div class="flex items-center gap-2">
            <button class="btn btn-soft btn-sm"><i class="fa-solid fa-filter"></i> Lọc</button>
            <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>

{{-- Bảng --}}
<div class="card table-wrap p-0">
    <table class="table-admin">
        <thead>
            <tr>
                <th style="width:56px">#</th>
                <th style="width:22%">Mã / Tên</th>
                <th>Loại giảm</th>
                <th>Phạm vi</th>
                <th>Thời gian</th>
                <th>Đã dùng / Giới hạn</th>
                <th>Trạng thái</th>
                <th class="col-actions">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($coupons as $i => $c)
            @php
            $now = now();
            $inTime = (!$c->starts_at || $c->starts_at <= $now) && (!$c->ends_at || $c->ends_at >= $now);
                $timeBadge = $inTime ? 'Đang diễn ra' : ($c->ends_at && $c->ends_at < $now ? 'Hết hạn' : 'Chưa bắt đầu' );
                    $timeCls=$inTime ? 'badge-green' : ($c->ends_at && $c->ends_at < $now ? 'badge-red' : 'badge-amber' );
                        $scope=['order'=>'Toàn đơn','category'=>'Theo danh mục','brand'=>'Theo thương hiệu','product'=>'Theo sản phẩm'][$c->applied_to] ?? $c->applied_to;
                        @endphp
                        <tr>
                            <td>{{ ($coupons->currentPage()-1)*$coupons->perPage() + $i + 1 }}</td>
                            <td>
                                <div class="font-semibold">{{ $c->code }}</div>
                                <div class="text-xs text-slate-500 line-clamp-1">{{ $c->name }}</div>
                            </td>
                            <td>
                                @if($c->discount_type==='percent')
                                {{ rtrim(rtrim(number_format($c->discount_value,2,'.',''), '0'),'.') }}%
                                @if($c->max_discount)
                                <span class="text-xs text-slate-500">(tối đa {{ number_format($c->max_discount) }}₫)</span>
                                @endif
                                @else
                                {{ number_format($c->discount_value) }}₫
                                @endif
                            </td>
                            <td>
                                @switch($c->applied_to)
                                @case('order') <i class="fa-solid fa-receipt mr-1 text-slate-400"></i> @break
                                @case('category') <i class="fa-solid fa-list mr-1 text-slate-400"></i> @break
                                @case('brand') <i class="fa-solid fa-copyright mr-1 text-slate-400"></i>@break
                                @case('product') <i class="fa-solid fa-box mr-1 text-slate-400"></i> @break
                                @endswitch
                                {{ $scope }}
                            </td>
                            <td class="text-xs">
                                {{ $c->starts_at ? $c->starts_at->format('d/m/Y H:i') : '—' }} → {{ $c->ends_at ? $c->ends_at->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td>
                                <span class="badge">{{ $c->redemptions_count }} / {{ $c->usage_limit ?? '∞' }}</span>
                            </td>
                            <td>
                                <div class="flex items-center gap-1 flex-wrap">
                                    <span class="badge {{ $c->is_active ? 'badge-green':'badge-red' }}">{{ $c->is_active ? 'Bật' : 'Tắt' }}</span>
                                    <span class="badge {{ $timeCls }}">{{ $timeBadge }}</span>
                                </div>
                            </td>
                            <td class="col-actions">
                                <a class="btn btn-table btn-outline" href="{{ route('admin.coupons.edit',$c) }}">Sửa</a>
                                <button type="button" class="btn btn-table btn-danger"
                                    data-id="{{ $c->id }}" data-code="{{ $c->code }}"
                                    onclick="openDeleteModal(this)">Xoá</button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-6 text-center text-slate-500">Chưa có mã.</td>
                        </tr>
                        @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination + summary --}}
<div class="flex items-center justify-between mt-2">
    <div class="text-sm text-slate-600">
        @if($coupons->total()>0)
        Hiển thị {{ ($coupons->currentPage()-1)*$coupons->perPage()+1 }}
        – {{ ($coupons->currentPage()-1)*$coupons->perPage()+$coupons->count() }}
        / {{ $coupons->total() }} mã
        @endif
    </div>
    <div class="pagination">
        {{ $coupons->onEachSide(1)->links() }}
    </div>
</div>

{{-- Modal xoá đẹp --}}
<div id="deleteModal" class="modal hidden">
    <div class="modal-card p-5">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full bg-rose-50 text-rose-600 grid place-content-center">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="flex-1">
                <div class="font-semibold text-base">Xoá mã giảm giá?</div>
                <div class="text-sm text-slate-600 mt-1">
                    Bạn sắp xoá mã <span id="delCode" class="font-semibold"></span>. Thao tác này không thể hoàn tác.
                </div>
                <form id="deleteForm" method="post" class="mt-4 flex items-center gap-2">
                    @csrf @method('DELETE')
                    <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Huỷ</button>
                    <button class="btn btn-danger">Xoá</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-dismiss alert
    document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
        const t = +el.getAttribute('data-auto-dismiss') || 3000;
        setTimeout(() => {
            el.classList.add('alert--hide');
            setTimeout(() => el.remove(), 350);
        }, t);
    });

    // Modal xoá
    const modal = document.getElementById('deleteModal');
    const delCode = document.getElementById('delCode');
    const delForm = document.getElementById('deleteForm');

    function openDeleteModal(btn) {
        const id = btn.dataset.id,
            code = btn.dataset.code;
        delCode.textContent = code;
        delForm.action = "{{ route('admin.coupons.destroy', ':id') }}".replace(':id', id);
        modal.classList.remove('hidden');
    }

    function closeDeleteModal() {
        modal.classList.add('hidden');
    }
    window.openDeleteModal = openDeleteModal;
    window.closeDeleteModal = closeDeleteModal;
</script>
@endpush
@endsection