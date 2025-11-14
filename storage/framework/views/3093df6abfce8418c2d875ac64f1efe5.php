
<?php $__env->startSection('title','Đổi trả / Hoàn tiền'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?>
<div class="alert alert-success mb-3" data-auto-dismiss="3000"><?php echo e(session('ok')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
<div class="alert alert-danger mb-3"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<div class="toolbar">
    <div class="toolbar-title">Đổi trả / Hoàn tiền</div>
</div>


<form method="get" class="card p-3 mb-3">
    <div class="flex items-end flex-wrap gap-3">
        <div>
            <label class="label">Order ID</label>
            <input type="text" name="order" value="<?php echo e(request('order')); ?>" class="form-control w-40" placeholder="VD: 211">
        </div>
        <div>
            <label class="label">Mã đơn</label>
            <input type="text" name="code" value="<?php echo e(request('code')); ?>" class="form-control w-56" placeholder="VD: CH-250909-XXXX">
        </div>
        <div class="ml-auto flex items-center gap-2">
            <button class="btn btn-outline btn-sm">Lọc</button>
            <?php if(request()->hasAny(['order','code'])): ?>
            <a href="<?php echo e(route('admin.order_returns.index')); ?>" class="btn btn-link btn-sm">Bỏ lọc</a>
            <?php endif; ?>
        </div>
    </div>
    <?php if(request()->hasAny(['order','code'])): ?>
    <div class="mt-2 text-xs text-slate-600">
        Đang lọc:
        <?php if(request('order')): ?> <span class="inline-flex items-center rounded-full border px-2 py-0.5 mr-1">Order ID: <?php echo e(request('order')); ?></span> <?php endif; ?>
        <?php if(request('code')): ?> <span class="inline-flex items-center rounded-full border px-2 py-0.5">Mã đơn: <?php echo e(request('code')); ?></span> <?php endif; ?>
    </div>
    <?php endif; ?>
</form>

<div class="card p-0 overflow-auto">
    <table class="table-admin text-sm">
        <thead>
            <tr>
                <th>#</th>
                <th>Đơn hàng</th>
                <th>Khách</th>
                <th>Trạng thái</th>
                <th class="text-right">Tạm tính</th>
                <th class="text-right">Chốt hoàn</th>
                <th>Thời gian</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $returns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr class="hover:bg-slate-50">
                <td><?php echo e($r->id); ?></td>
                <td>
                    <?php if($r->order): ?>
                    <div class="font-medium">#<?php echo e($r->order->code); ?></div>
                    <div class="text-xs text-slate-500">ID: <?php echo e($r->order_id); ?></div>
                    <?php else: ?>
                    <span class="text-slate-400">—</span>
                    <?php endif; ?>
                </td>
                <td class="whitespace-nowrap">
                    <?php echo e($r->order->customer_name ?? '—'); ?><br>
                    <span class="text-xs text-slate-500"><?php echo e($r->order->customer_phone ?? ''); ?></span>
                </td>
                <td>
                    <?php
                    $pill = match($r->status){
                    'requested' => 'bg-amber-50 text-amber-700 border border-amber-200',
                    'approved' => 'bg-violet-50 text-violet-700 border border-violet-200',
                    'in_transit' => 'bg-sky-50 text-sky-700 border border-sky-200',
                    'received' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                    'refunded' => 'bg-sky-50 text-sky-700 border border-sky-200',
                    'rejected','cancelled' => 'bg-rose-50 text-rose-700 border border-rose-200',
                    default => 'bg-slate-50 text-slate-700 border border-slate-200',
                    };
                    ?>
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs <?php echo e($pill); ?>"><?php echo e(strtoupper($r->status)); ?></span>
                </td>
                <td class="text-right"><?php echo e(number_format($r->expected_refund)); ?>₫</td>
                <td class="text-right"><?php echo e(number_format($r->final_refund)); ?>₫</td>
                <td class="whitespace-nowrap text-slate-600"><?php echo e(optional($r->created_at)->format('d/m/Y H:i')); ?></td>
                <td class="text-right">
                    <a href="<?php echo e(route('admin.order_returns.show', $r)); ?>" class="btn btn-outline btn-sm">Xem</a>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="8" class="text-center text-slate-500 py-6">Chưa có yêu cầu.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-3"><?php echo e($returns->withQueryString()->links()); ?></div>

<?php $__env->startPush('scripts'); ?>
<script>
    // Ẩn alert sau 3s
    document.querySelectorAll('[data-auto-dismiss]')?.forEach(el => {
        const ms = +el.getAttribute('data-auto-dismiss') || 3000;
        setTimeout(() => el.remove(), ms);
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/order_returns/index.blade.php ENDPATH**/ ?>