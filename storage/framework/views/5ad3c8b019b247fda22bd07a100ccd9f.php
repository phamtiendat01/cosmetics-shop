
<?php $__env->startSection('title','Thu hồi sản phẩm - CosmeChain'); ?>

<?php $__env->startSection('content'); ?>
<div class="toolbar">
    <div class="toolbar-title">Thu hồi sản phẩm (Product Recall)</div>
    <div class="toolbar-actions">
        <a href="<?php echo e(route('admin.blockchain.certificates')); ?>" class="btn btn-outline btn-sm">Quay lại</a>
    </div>
</div>

<div class="card p-6 max-w-2xl">
    <form method="POST" action="<?php echo e(route('admin.blockchain.recall.store')); ?>">
        <?php echo csrf_field(); ?>

        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-2">
                Chọn sản phẩm <span class="text-red-500">*</span>
            </label>
            <select name="product_variant_id" id="variant-select" required
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                <option value="">-- Chọn sản phẩm --</option>
                <?php $__currentLoopData = $variants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($v->id); ?>" <?php echo e($variant && $variant->id == $v->id ? 'selected' : ''); ?>>
                    <?php echo e($v->product->name ?? 'N/A'); ?> - <?php echo e($v->sku ?? 'N/A'); ?>

                    <?php if($v->product->brand): ?>
                    (<?php echo e($v->product->brand->name); ?>)
                    <?php endif; ?>
                </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php $__errorArgs = ['product_variant_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <?php if($variant): ?>
        <div class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="text-sm font-medium text-blue-900 mb-2">Thông tin sản phẩm:</div>
            <div class="text-sm text-blue-700 space-y-1">
                <div><strong>Tên:</strong> <?php echo e($variant->product->name ?? 'N/A'); ?></div>
                <div><strong>SKU:</strong> <?php echo e($variant->sku ?? 'N/A'); ?></div>
                <?php if($variant->blockchainCertificate): ?>
                <div><strong>Certificate Hash:</strong> 
                    <code class="text-xs"><?php echo e(substr($variant->blockchainCertificate->certificate_hash, 0, 20)); ?>...</code>
                </div>
                <div><strong>Batch Number:</strong> 
                    <?php echo e($variant->blockchainCertificate->metadata['batch_number'] ?? 'N/A'); ?>

                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-2">
                Batch Number (tùy chọn)
            </label>
            <input type="text" name="batch_number" value="<?php echo e(old('batch_number')); ?>"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                placeholder="Nếu để trống sẽ dùng batch number từ certificate">
            <?php $__errorArgs = ['batch_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-2">
                Lý do thu hồi <span class="text-red-500">*</span>
            </label>
            <textarea name="reason" rows="4" required
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                placeholder="Ví dụ: Phát hiện lỗi sản xuất, vi phạm an toàn, thu hồi tự nguyện..."><?php echo e(old('reason')); ?></textarea>
            <?php $__errorArgs = ['reason'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">
                Số lượng (tùy chọn)
            </label>
            <input type="number" name="quantity" value="<?php echo e(old('quantity', 1)); ?>" min="1"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            <?php $__errorArgs = ['quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Ghi nhận thu hồi
            </button>
            <a href="<?php echo e(route('admin.blockchain.certificates')); ?>" class="btn btn-outline">
                Hủy
            </a>
        </div>
    </form>
</div>

<script>
document.getElementById('variant-select').addEventListener('change', function() {
    if (this.value) {
        window.location.href = '<?php echo e(route("admin.blockchain.recall.create")); ?>?variant_id=' + this.value;
    }
});
</script>

<?php $__env->stopSection(); ?>


<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/blockchain/recall/create.blade.php ENDPATH**/ ?>