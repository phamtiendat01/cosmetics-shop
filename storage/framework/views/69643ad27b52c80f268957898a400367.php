<?php $__env->startSection('title','CosmeChain - Verifications'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?>
<div class="alert alert-success mb-3" data-auto-dismiss="3000"><?php echo e(session('ok')); ?></div>
<?php endif; ?>

<div class="toolbar">
    <div class="toolbar-title">Lịch sử Xác thực</div>
    <div class="toolbar-actions">
        <a href="<?php echo e(route('admin.blockchain.certificates')); ?>" class="btn btn-outline btn-sm">Certificates</a>
        <a href="<?php echo e(route('admin.blockchain.qr-codes')); ?>" class="btn btn-outline btn-sm">QR Codes</a>
        <a href="<?php echo e(route('admin.blockchain.statistics')); ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-chart-bar mr-1"></i>Statistics
        </a>
    </div>
</div>


<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
    <div class="card p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Tổng verifications</div>
                <div class="text-2xl font-bold text-slate-900"><?php echo e(number_format($stats['total'] ?? 0)); ?></div>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                <i class="fas fa-list text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="card p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Chính hãng</div>
                <div class="text-2xl font-bold text-green-600"><?php echo e(number_format($stats['authentic'] ?? 0)); ?></div>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="card p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Hàng giả</div>
                <div class="text-2xl font-bold text-red-600"><?php echo e(number_format($stats['fake'] ?? 0)); ?></div>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                <i class="fas fa-times-circle text-red-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="card p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Đáng nghi</div>
                <div class="text-2xl font-bold text-amber-600"><?php echo e(number_format($stats['suspicious'] ?? 0)); ?></div>
            </div>
            <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-amber-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>


<div class="card p-3 mb-3">
    <form method="get" class="grid md:grid-cols-3 gap-2 items-center">
        <input name="search" value="<?php echo e($filters['search'] ?? ''); ?>" class="form-control" placeholder="Tìm theo QR code, IP...">

        <select name="result" class="form-control">
            <option value="">Tất cả kết quả</option>
            <option value="authentic" <?php if(($filters['result'] ?? '') === 'authentic'): echo 'selected'; endif; ?>>Chính hãng</option>
            <option value="fake" <?php if(($filters['result'] ?? '') === 'fake'): echo 'selected'; endif; ?>>Hàng giả</option>
            <option value="suspicious" <?php if(($filters['result'] ?? '') === 'suspicious'): echo 'selected'; endif; ?>>Đáng nghi</option>
            <option value="not_found" <?php if(($filters['result'] ?? '') === 'not_found'): echo 'selected'; endif; ?>>Không tìm thấy</option>
        </select>

        <div class="flex gap-2">
            <button class="btn btn-soft btn-sm">Lọc</button>
            <a href="<?php echo e(route('admin.blockchain.verifications')); ?>" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>

<?php
$from = $verifications->total() ? (($verifications->currentPage()-1) * $verifications->perPage() + 1) : 0;
$to = $verifications->total() ? ($from + $verifications->count() - 1) : 0;
?>
<?php if($verifications->total() > 0): ?>
<div class="mb-2 text-sm text-slate-600">Hiển thị <?php echo e($from); ?>–<?php echo e($to); ?> / <?php echo e($verifications->total()); ?> verifications</div>
<?php endif; ?>

<div class="card table-wrap p-0">
    <table class="table-admin w-full">
        <thead>
            <tr>
                <th>ID</th>
                <th>QR Code</th>
                <th>Product</th>
                <th>Result</th>
                <th>Verifier IP</th>
                <th>User Agent</th>
                <th>Verified At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $verifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $verification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
            $qrCode = $verification->qrCode;
            $variant = $qrCode->productVariant ?? null;
            $product = $variant->product ?? null;
            ?>
            <tr>
                <td class="text-right pr-3"><?php echo e($verification->id); ?></td>
                <td>
                    <code class="text-xs font-mono break-all max-w-xs block"><?php echo e(Str::limit($verification->qr_code, 40)); ?></code>
                </td>
                <td>
                    <?php if($product): ?>
                    <div class="font-medium"><?php echo e($product->name); ?></div>
                    <div class="text-xs text-slate-500">SKU: <?php echo e($variant->sku ?? 'N/A'); ?></div>
                    <?php else: ?>
                    <span class="text-slate-400">N/A</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    $resultColors = [
                        'authentic' => ['bg-green-100', 'text-green-800', 'fa-check-circle'],
                        'fake' => ['bg-red-100', 'text-red-800', 'fa-times-circle'],
                        'suspicious' => ['bg-amber-100', 'text-amber-800', 'fa-exclamation-triangle'],
                        'not_found' => ['bg-gray-100', 'text-gray-800', 'fa-question-circle'],
                    ];
                    $resultLabels = [
                        'authentic' => 'Chính hãng',
                        'fake' => 'Hàng giả',
                        'suspicious' => 'Đáng nghi',
                        'not_found' => 'Không tìm thấy',
                    ];
                    $color = $resultColors[$verification->verification_result] ?? $resultColors['not_found'];
                    $label = $resultLabels[$verification->verification_result] ?? 'Unknown';
                    ?>
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium <?php echo e($color[0]); ?> <?php echo e($color[1]); ?>">
                        <i class="fas <?php echo e($color[2]); ?> mr-1"></i> <?php echo e($label); ?>

                    </span>
                </td>
                <td>
                    <code class="text-xs font-mono"><?php echo e($verification->verifier_ip ?? 'N/A'); ?></code>
                </td>
                <td>
                    <div class="text-xs text-slate-600 max-w-xs truncate" title="<?php echo e($verification->user_agent ?? 'N/A'); ?>">
                        <?php echo e(Str::limit($verification->user_agent ?? 'N/A', 50)); ?>

                    </div>
                </td>
                <td>
                    <div class="text-sm"><?php echo e($verification->created_at->format('d/m/Y')); ?></div>
                    <div class="text-xs text-slate-500"><?php echo e($verification->created_at->format('H:i:s')); ?></div>
                </td>
                <td>
                    <div class="flex gap-1">
                        <a href="<?php echo e(route('blockchain.verify.qr', $verification->qr_code)); ?>" target="_blank" class="btn btn-xs btn-soft" title="Verify lại">
                            <i class="fas fa-search"></i>
                        </a>
                        <?php if($verification->metadata): ?>
                        <button onclick="showMetadata(<?php echo e($verification->id); ?>)" class="btn btn-xs btn-soft" title="Xem metadata">
                            <i class="fas fa-info-circle"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="8" class="text-center py-8 text-slate-400">
                    <i class="fas fa-inbox text-4xl mb-2 block"></i>
                    <div>Không có verification nào</div>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if($verifications->hasPages()): ?>
<div class="mt-4">
    <?php echo e($verifications->links()); ?>

</div>
<?php endif; ?>


<div id="metadataModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Verification Metadata</h3>
            <button onclick="closeMetadataModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <pre id="metadataContent" class="bg-slate-50 p-4 rounded text-xs overflow-auto"></pre>
    </div>
</div>

<script>
const metadataStore = {};
<?php $__currentLoopData = $verifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php if($v->metadata): ?>
metadataStore[<?php echo e($v->id); ?>] = <?php echo json_encode($v->metadata, 15, 512) ?>;
<?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

function showMetadata(id) {
    const metadata = metadataStore[id] || {};
    document.getElementById('metadataContent').textContent = JSON.stringify(metadata, null, 2);
    document.getElementById('metadataModal').classList.remove('hidden');
}

function closeMetadataModal() {
    document.getElementById('metadataModal').classList.add('hidden');
}

// Close on outside click
document.getElementById('metadataModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeMetadataModal();
    }
});
</script>

<?php $__env->stopSection(); ?>


<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/blockchain/verifications.blade.php ENDPATH**/ ?>