@extends('admin.layouts.app')
@section('title','Thu hồi sản phẩm - CosmeChain')

@section('content')
<div class="toolbar">
    <div class="toolbar-title">Thu hồi sản phẩm (Product Recall)</div>
    <div class="toolbar-actions">
        <a href="{{ route('admin.blockchain.certificates') }}" class="btn btn-outline btn-sm">Quay lại</a>
    </div>
</div>

<div class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('admin.blockchain.recall.store') }}">
        @csrf

        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-2">
                Chọn sản phẩm <span class="text-red-500">*</span>
            </label>
            <select name="product_variant_id" id="variant-select" required
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                <option value="">-- Chọn sản phẩm --</option>
                @foreach($variants as $v)
                <option value="{{ $v->id }}" {{ $variant && $variant->id == $v->id ? 'selected' : '' }}>
                    {{ $v->product->name ?? 'N/A' }} - {{ $v->sku ?? 'N/A' }}
                    @if($v->product->brand)
                    ({{ $v->product->brand->name }})
                    @endif
                </option>
                @endforeach
            </select>
            @error('product_variant_id')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        @if($variant)
        <div class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="text-sm font-medium text-blue-900 mb-2">Thông tin sản phẩm:</div>
            <div class="text-sm text-blue-700 space-y-1">
                <div><strong>Tên:</strong> {{ $variant->product->name ?? 'N/A' }}</div>
                <div><strong>SKU:</strong> {{ $variant->sku ?? 'N/A' }}</div>
                @if($variant->blockchainCertificate)
                <div><strong>Certificate Hash:</strong> 
                    <code class="text-xs">{{ substr($variant->blockchainCertificate->certificate_hash, 0, 20) }}...</code>
                </div>
                <div><strong>Batch Number:</strong> 
                    {{ $variant->blockchainCertificate->metadata['batch_number'] ?? 'N/A' }}
                </div>
                @endif
            </div>
        </div>
        @endif

        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-2">
                Batch Number (tùy chọn)
            </label>
            <input type="text" name="batch_number" value="{{ old('batch_number') }}"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                placeholder="Nếu để trống sẽ dùng batch number từ certificate">
            @error('batch_number')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-2">
                Lý do thu hồi <span class="text-red-500">*</span>
            </label>
            <textarea name="reason" rows="4" required
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                placeholder="Ví dụ: Phát hiện lỗi sản xuất, vi phạm an toàn, thu hồi tự nguyện...">{{ old('reason') }}</textarea>
            @error('reason')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">
                Số lượng (tùy chọn)
            </label>
            <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            @error('quantity')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Ghi nhận thu hồi
            </button>
            <a href="{{ route('admin.blockchain.certificates') }}" class="btn btn-outline">
                Hủy
            </a>
        </div>
    </form>
</div>

<script>
document.getElementById('variant-select').addEventListener('change', function() {
    if (this.value) {
        window.location.href = '{{ route("admin.blockchain.recall.create") }}?variant_id=' + this.value;
    }
});
</script>

@endsection

