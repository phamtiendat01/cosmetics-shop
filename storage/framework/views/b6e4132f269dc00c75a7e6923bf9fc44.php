
<?php $__env->startSection('title', 'CosmeBot - Analytics'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
        <i class="fa-solid fa-chart-bar text-rose-600"></i>
        Analytics
    </h1>
    <p class="text-slate-600 mt-1">Phân tích hiệu suất chatbot</p>
</div>


<div class="bg-white border border-slate-200 rounded-xl p-4 mb-6">
    <form method="GET" class="flex gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Từ ngày</label>
            <input type="date" name="date_from" value="<?php echo e($dateFrom); ?>"
                class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Đến ngày</label>
            <input type="date" name="date_to" value="<?php echo e($dateTo); ?>"
                class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition">
            <i class="fa-solid fa-filter mr-2"></i> Lọc
        </button>
    </form>
</div>


<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="text-3xl font-bold text-rose-600 mb-1"><?php echo e(number_format($stats['total_interactions'])); ?></div>
        <div class="text-sm text-slate-600">Tổng tương tác</div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="text-3xl font-bold text-blue-600 mb-1"><?php echo e(number_format($stats['intent_detections'])); ?></div>
        <div class="text-sm text-slate-600">Intent detections</div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="text-3xl font-bold text-green-600 mb-1"><?php echo e(number_format($stats['tool_calls'])); ?></div>
        <div class="text-sm text-slate-600">Tool calls</div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="text-3xl font-bold text-purple-600 mb-1"><?php echo e(number_format($stats['avg_latency'], 0)); ?>ms</div>
        <div class="text-sm text-slate-600">Avg latency</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <h3 class="text-lg font-semibold mb-4">Intent Performance</h3>
        <div class="space-y-3">
            <?php $__empty_1 = true; $__currentLoopData = $intentStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                <div>
                    <div class="font-medium text-slate-900"><?php echo e($stat->intent); ?></div>
                    <div class="text-xs text-slate-500">
                        <?php echo e($stat->count); ?> lần • 
                        Confidence: <?php echo e(number_format($stat->avg_confidence * 100, 1)); ?>%
                    </div>
                </div>
                <div class="text-rose-600 font-semibold"><?php echo e($stat->count); ?></div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="text-slate-500 text-sm">Chưa có dữ liệu</p>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <h3 class="text-lg font-semibold mb-4">Tool Usage</h3>
        <div class="space-y-3">
            <?php $__empty_1 = true; $__currentLoopData = $toolStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                <div>
                    <div class="font-medium text-slate-900"><?php echo e($stat->tool ?? 'N/A'); ?></div>
                    <div class="text-xs text-slate-500"><?php echo e($stat->count); ?> lần sử dụng</div>
                </div>
                <div class="text-blue-600 font-semibold"><?php echo e($stat->count); ?></div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="text-slate-500 text-sm">Chưa có dữ liệu</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/bot/analytics.blade.php ENDPATH**/ ?>