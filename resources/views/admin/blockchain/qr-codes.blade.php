@extends('admin.layouts.app')
@section('title','CosmeChain - QR Codes')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif

<div class="toolbar">
    <div class="toolbar-title">Quản lý QR Codes</div>
    <div class="toolbar-actions">
        <a href="{{ route('admin.blockchain.certificates') }}" class="btn btn-outline btn-sm">Certificates</a>
        <a href="{{ route('admin.blockchain.verifications') }}" class="btn btn-outline btn-sm">Verifications</a>
        <a href="{{ route('admin.blockchain.statistics') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-chart-bar mr-1"></i>Statistics
        </a>
    </div>
</div>

{{-- Stats Cards --}}
@php
$totalQRCodes = \App\Models\ProductQRCode::count();
$verifiedCount = \App\Models\ProductQRCode::where('is_verified', true)->count();
$flaggedCount = \App\Models\ProductQRCode::where('is_flagged', true)->count();
@endphp
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="card p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Tổng QR Codes</div>
                <div class="text-2xl font-bold text-slate-900">{{ number_format($totalQRCodes) }}</div>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                <i class="fas fa-qrcode text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="card p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Đã xác thực</div>
                <div class="text-2xl font-bold text-green-600">{{ number_format($verifiedCount) }}</div>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="card p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Đã đánh dấu</div>
                <div class="text-2xl font-bold text-red-600">{{ number_format($flaggedCount) }}</div>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                <i class="fas fa-flag text-red-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card p-3 mb-3">
    <form method="get" class="grid md:grid-cols-4 gap-2 items-center">
        <input name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Tìm theo QR code, order code...">

        <select name="verified" class="form-control">
            <option value="">Tất cả trạng thái</option>
            <option value="1" @selected(($filters['verified'] ?? '') === '1')>Đã xác thực</option>
            <option value="0" @selected(($filters['verified'] ?? '') === '0')>Chưa xác thực</option>
        </select>

        <select name="flagged" class="form-control">
            <option value="">Tất cả</option>
            <option value="1" @selected(($filters['flagged'] ?? '') === '1')>Đã đánh dấu</option>
            <option value="0" @selected(($filters['flagged'] ?? '') === '0')>Bình thường</option>
        </select>

        <div class="flex gap-2">
            <button class="btn btn-soft btn-sm">Lọc</button>
            <a href="{{ route('admin.blockchain.qr-codes') }}" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>

@php
$from = $qrCodes->total() ? (($qrCodes->currentPage()-1) * $qrCodes->perPage() + 1) : 0;
$to = $qrCodes->total() ? ($from + $qrCodes->count() - 1) : 0;
@endphp
@if($qrCodes->total() > 0)
<div class="mb-2 text-sm text-slate-600">Hiển thị {{ $from }}–{{ $to }} / {{ $qrCodes->total() }} QR codes</div>
@endif

<div class="card table-wrap p-0">
    <table class="table-admin w-full">
        <thead>
            <tr>
                <th>ID</th>
                <th>QR Code</th>
                <th>Product</th>
                <th>Order</th>
                <th>Status</th>
                <th>Verifications</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($qrCodes as $qr)
            @php
            $variant = $qr->productVariant ?? null;
            $product = $variant->product ?? null;
            $orderItem = $qr->orderItem ?? null;
            $order = $orderItem->order ?? null;
            @endphp
            <tr>
                <td class="text-right pr-3">{{ $qr->id }}</td>
                <td>
                    <code class="text-xs font-mono break-all max-w-xs block">{{ Str::limit($qr->qr_code, 50) }}</code>
                    @if($qr->qr_image_url)
                    <a href="{{ $qr->qr_image_url }}" target="_blank" class="text-blue-600 hover:underline text-xs mt-1 inline-block">
                        <i class="fas fa-image"></i> Xem ảnh
                    </a>
                    @endif
                </td>
                <td>
                    @if($product)
                    <div class="font-medium">{{ $product->name }}</div>
                    <div class="text-xs text-slate-500">SKU: {{ $variant->sku ?? 'N/A' }}</div>
                    @else
                    <span class="text-slate-400">N/A</span>
                    @endif
                </td>
                <td>
                    @if($order)
                    <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:underline">
                        {{ $order->code }}
                    </a>
                    <div class="text-xs text-slate-500">{{ $order->status }}</div>
                    @else
                    <span class="text-slate-400">N/A</span>
                    @endif
                </td>
                <td>
                    @if($qr->is_verified)
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i> Verified
                    </span>
                    @else
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">
                        <i class="fas fa-clock mr-1"></i> Pending
                    </span>
                    @endif
                    @if($qr->is_flagged)
                    <div class="mt-1">
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-flag mr-1"></i> Flagged
                        </span>
                    </div>
                    @if($qr->flag_reason)
                    <div class="text-xs text-red-600 mt-1">{{ Str::limit($qr->flag_reason, 30) }}</div>
                    @endif
                    @endif
                </td>
                <td>
                    <div class="text-sm font-medium">{{ $qr->verification_count ?? 0 }}</div>
                    @if($qr->verified_at)
                    <div class="text-xs text-slate-500">Lần cuối: {{ $qr->verified_at->format('d/m/Y H:i') }}</div>
                    @endif
                </td>
                <td>
                    <div class="text-sm">{{ $qr->created_at->format('d/m/Y') }}</div>
                    <div class="text-xs text-slate-500">{{ $qr->created_at->format('H:i') }}</div>
                </td>
                <td>
                    <div class="flex gap-1">
                        <a href="{{ route('blockchain.verify.qr', $qr->qr_code) }}" target="_blank" class="btn btn-xs btn-soft" title="Verify">
                            <i class="fas fa-search"></i>
                        </a>
                        @if($qr->qr_image_url)
                        <a href="{{ $qr->qr_image_url }}" target="_blank" download class="btn btn-xs btn-soft" title="Download">
                            <i class="fas fa-download"></i>
                        </a>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center py-8 text-slate-400">
                    <i class="fas fa-inbox text-4xl mb-2 block"></i>
                    <div>Không có QR codes nào</div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($qrCodes->hasPages())
<div class="mt-4">
    {{ $qrCodes->links() }}
</div>
@endif

@endsection

