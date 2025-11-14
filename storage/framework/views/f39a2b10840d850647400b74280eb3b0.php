<?php $__env->startSection('title','CosmeChain - Certificates'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?>
<div class="alert alert-success mb-3" data-auto-dismiss="3000"><?php echo e(session('ok')); ?></div>
<?php endif; ?>

<div class="toolbar">
    <div class="toolbar-title">Quản lý Blockchain Certificates</div>
    <div class="toolbar-actions">
        <a href="<?php echo e(route('admin.blockchain.qr-codes')); ?>" class="btn btn-outline btn-sm">QR Codes</a>
        <a href="<?php echo e(route('admin.blockchain.verifications')); ?>" class="btn btn-outline btn-sm">Verifications</a>
        <a href="<?php echo e(route('admin.blockchain.statistics')); ?>" class="btn btn-outline btn-sm">
            <i class="fas fa-chart-bar mr-1"></i>Statistics
        </a>
        <a href="<?php echo e(route('admin.blockchain.recall.create')); ?>" class="btn btn-warning btn-sm">
            <i class="fas fa-exclamation-triangle mr-1"></i>Product Recall
        </a>
    </div>
</div>


<?php
$totalCerts = \App\Models\ProductBlockchainCertificate::count();
$mintedCount = \App\Models\ProductBlockchainCertificate::whereNotNull('minted_at')->count();
$ipfsCount = \App\Models\ProductBlockchainCertificate::whereNotNull('ipfs_hash')->count();
?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="card p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Tổng Certificates</div>
                <div class="text-2xl font-bold text-slate-900"><?php echo e(number_format($totalCerts)); ?></div>
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
                <div class="text-2xl font-bold text-green-600"><?php echo e(number_format($mintedCount)); ?></div>
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
                <div class="text-2xl font-bold text-purple-600"><?php echo e(number_format($ipfsCount)); ?></div>
            </div>
            <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                <i class="fas fa-cloud text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="card p-3 mb-3">
    <form method="get" class="flex gap-2 items-center">
        <input name="search" value="<?php echo e($search ?? ''); ?>" class="form-control flex-1" placeholder="Tìm theo hash, SKU, tên sản phẩm...">
        <button class="btn btn-soft btn-sm">Tìm kiếm</button>
        <a href="<?php echo e(route('admin.blockchain.certificates')); ?>" class="btn btn-outline btn-sm">Reset</a>
    </form>
</div>

<?php
$from = $certificates->total() ? (($certificates->currentPage()-1) * $certificates->perPage() + 1) : 0;
$to = $certificates->total() ? ($from + $certificates->count() - 1) : 0;
?>
<?php if($certificates->total() > 0): ?>
<div class="mb-2 text-sm text-slate-600">Hiển thị <?php echo e($from); ?>–<?php echo e($to); ?> / <?php echo e($certificates->total()); ?> certificates</div>
<?php endif; ?>

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
            <?php $__empty_1 = true; $__currentLoopData = $certificates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
            $variant = $cert->productVariant;
            $product = $variant->product ?? null;
            ?>
            <tr>
                <td class="text-right pr-3"><?php echo e($cert->id); ?></td>
                <td>
                    <?php if($product): ?>
                    <div class="font-medium"><?php echo e($product->name); ?></div>
                    <div class="text-xs text-slate-500">SKU: <?php echo e($variant->sku ?? 'N/A'); ?></div>
                    <?php if($variant->name): ?>
                    <div class="text-xs text-slate-400"><?php echo e($variant->name); ?></div>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="text-slate-400">N/A</span>
                    <?php endif; ?>
                </td>
                <td>
                    <code class="text-xs font-mono break-all"><?php echo e(Str::limit($cert->certificate_hash, 40)); ?></code>
                </td>
                <td>
                    <?php if($cert->ipfs_hash): ?>
                    <a href="<?php echo e($cert->ipfs_url); ?>" target="_blank" class="text-blue-600 hover:underline text-xs">
                        <?php echo e(Str::limit($cert->ipfs_hash, 30)); ?>

                        <i class="fas fa-external-link-alt ml-1"></i>
                    </a>
                    <?php else: ?>
                    <span class="text-slate-400 text-xs">N/A</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($cert->minted_at): ?>
                    <div class="text-sm"><?php echo e($cert->minted_at->format('d/m/Y')); ?></div>
                    <div class="text-xs text-slate-500"><?php echo e($cert->minted_at->format('H:i')); ?></div>
                    <?php else: ?>
                    <span class="text-slate-400 text-xs">Chưa mint</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="flex gap-1">
                        <?php if($cert->ipfs_url): ?>
                        <a href="<?php echo e($cert->ipfs_url); ?>" target="_blank" class="btn btn-xs btn-soft" title="Xem trên IPFS">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                        <?php endif; ?>
                        <a href="<?php echo e(route('blockchain.verify.hash', $cert->certificate_hash)); ?>" target="_blank" class="btn btn-xs btn-soft" title="Verify">
                            <i class="fas fa-search"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="6" class="text-center py-8 text-slate-400">
                    <i class="fas fa-inbox text-4xl mb-2 block"></i>
                    <div>Không có certificates nào</div>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if($certificates->hasPages()): ?>
<div class="mt-4">
    <?php echo e($certificates->links()); ?>

</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>


<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/blockchain/certificates.blade.php ENDPATH**/ ?>