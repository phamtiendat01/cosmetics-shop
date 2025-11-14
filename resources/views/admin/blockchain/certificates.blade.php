@extends('admin.layouts.app')
@section('title','CosmeChain - Certificates')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif

<div class="toolbar">
    <div class="toolbar-title">Quản lý Blockchain Certificates</div>
    <div class="toolbar-actions">
        <a href="{{ route('admin.blockchain.qr-codes') }}" class="btn btn-outline btn-sm">QR Codes</a>
        <a href="{{ route('admin.blockchain.verifications') }}" class="btn btn-outline btn-sm">Verifications</a>
        <a href="{{ route('admin.blockchain.statistics') }}" class="btn btn-outline btn-sm">
            <i class="fas fa-chart-bar mr-1"></i>Statistics
        </a>
        <a href="{{ route('admin.blockchain.recall.create') }}" class="btn btn-warning btn-sm">
            <i class="fas fa-exclamation-triangle mr-1"></i>Product Recall
        </a>
    </div>
</div>

{{-- Stats Cards --}}
@php
$totalCerts = \App\Models\ProductBlockchainCertificate::count();
$mintedCount = \App\Models\ProductBlockchainCertificate::whereNotNull('minted_at')->count();
$ipfsCount = \App\Models\ProductBlockchainCertificate::whereNotNull('ipfs_hash')->count();
@endphp
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="card p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Tổng Certificates</div>
                <div class="text-2xl font-bold text-slate-900">{{ number_format($totalCerts) }}</div>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                <i class="fas fa-certificate text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="card p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Đã mint</div>
                <div class="text-2xl font-bold text-green-600">{{ number_format($mintedCount) }}</div>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="card p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Đã upload IPFS</div>
                <div class="text-2xl font-bold text-purple-600">{{ number_format($ipfsCount) }}</div>
            </div>
            <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                <i class="fas fa-cloud text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="card p-3 mb-3">
    <form method="get" class="flex gap-2 items-center">
        <input name="search" value="{{ $search ?? '' }}" class="form-control flex-1" placeholder="Tìm theo hash, SKU, tên sản phẩm...">
        <button class="btn btn-soft btn-sm">Tìm kiếm</button>
        <a href="{{ route('admin.blockchain.certificates') }}" class="btn btn-outline btn-sm">Reset</a>
    </form>
</div>

@php
$from = $certificates->total() ? (($certificates->currentPage()-1) * $certificates->perPage() + 1) : 0;
$to = $certificates->total() ? ($from + $certificates->count() - 1) : 0;
@endphp
@if($certificates->total() > 0)
<div class="mb-2 text-sm text-slate-600">Hiển thị {{ $from }}–{{ $to }} / {{ $certificates->total() }} certificates</div>
@endif

<div class="card table-wrap p-0">
    <table class="table-admin w-full">
        <thead>
            <tr>
                <th>ID</th>
                <th>Product</th>
                <th>Certificate Hash</th>
                <th>IPFS Hash</th>
                <th>Minted At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($certificates as $cert)
            @php
            $variant = $cert->productVariant;
            $product = $variant->product ?? null;
            @endphp
            <tr>
                <td class="text-right pr-3">{{ $cert->id }}</td>
                <td>
                    @if($product)
                    <div class="font-medium">{{ $product->name }}</div>
                    <div class="text-xs text-slate-500">SKU: {{ $variant->sku ?? 'N/A' }}</div>
                    @if($variant->name)
                    <div class="text-xs text-slate-400">{{ $variant->name }}</div>
                    @endif
                    @else
                    <span class="text-slate-400">N/A</span>
                    @endif
                </td>
                <td>
                    <code class="text-xs font-mono break-all">{{ Str::limit($cert->certificate_hash, 40) }}</code>
                </td>
                <td>
                    @if($cert->ipfs_hash)
                    <a href="{{ $cert->ipfs_url }}" target="_blank" class="text-blue-600 hover:underline text-xs">
                        {{ Str::limit($cert->ipfs_hash, 30) }}
                        <i class="fas fa-external-link-alt ml-1"></i>
                    </a>
                    @else
                    <span class="text-slate-400 text-xs">N/A</span>
                    @endif
                </td>
                <td>
                    @if($cert->minted_at)
                    <div class="text-sm">{{ $cert->minted_at->format('d/m/Y') }}</div>
                    <div class="text-xs text-slate-500">{{ $cert->minted_at->format('H:i') }}</div>
                    @else
                    <span class="text-slate-400 text-xs">Chưa mint</span>
                    @endif
                </td>
                <td>
                    <div class="flex gap-1">
                        @if($cert->ipfs_url)
                        <a href="{{ $cert->ipfs_url }}" target="_blank" class="btn btn-xs btn-soft" title="Xem trên IPFS">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                        @endif
                        <a href="{{ route('blockchain.verify.hash', $cert->certificate_hash) }}" target="_blank" class="btn btn-xs btn-soft" title="Verify">
                            <i class="fas fa-search"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-8 text-slate-400">
                    <i class="fas fa-inbox text-4xl mb-2 block"></i>
                    <div>Không có certificates nào</div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($certificates->hasPages())
<div class="mt-4">
    {{ $certificates->links() }}
</div>
@endif

@endsection

