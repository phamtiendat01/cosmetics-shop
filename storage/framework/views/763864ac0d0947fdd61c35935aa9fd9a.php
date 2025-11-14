
<?php $__env->startSection('title','Đơn vị vận chuyển'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?> <div class="alert alert-success mb-3" data-auto-dismiss="3000"><?php echo e(session('ok')); ?></div> <?php endif; ?>
<?php if($errors->any()): ?> <div class="alert alert-danger mb-3"><?php echo e($errors->first()); ?></div> <?php endif; ?>


<?php echo $__env->make('admin.shipping._nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="toolbar mb-2">
    <div class="toolbar-title">Đơn vị vận chuyển</div>
    <div class="toolbar-actions">
        <?php if(Route::has('admin.shipping.carriers.create')): ?>
        <a href="<?php echo e(route('admin.shipping.carriers.create')); ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Thêm đơn vị
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card p-3 mb-3">
    <form method="get" class="grid md:grid-cols-5 gap-2 items-center">
        <input name="keyword" value="<?php echo e(request('keyword','')); ?>" class="form-control" placeholder="Tên / mã…">
        <select name="enabled" class="form-control">
            <option value="">Trạng thái</option>
            <option value="1" <?php if(request('enabled')==='1' ): echo 'selected'; endif; ?>>Đang bật</option>
            <option value="0" <?php if(request('enabled')==='0' ): echo 'selected'; endif; ?>>Đang tắt</option>
        </select>
        <div class="md:col-span-3 flex items-center gap-2">
            <button class="btn btn-soft btn-sm"><i class="fa-solid fa-filter"></i> Lọc</button>
            <a href="<?php echo e(route('admin.shipping.carriers.index')); ?>" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>

<?php $total = method_exists($carriers,'total') ? $carriers->total() : (is_countable($carriers ?? []) ? count($carriers) : 0); ?>

<?php if($total>0): ?>
<div class="mb-2 text-sm text-slate-600">Có <?php echo e($total); ?> đơn vị</div>
<?php endif; ?>

<div class="card table-wrap p-0">
    <table class="table-admin w-full">
        <colgroup>
            <col style="width:70px">
            <col>
            <col style="width:140px">
            <col style="width:120px">
            <col style="width:120px">
            <col style="width:180px">
        </colgroup>
        <thead>
            <tr>
                <th></th>
                <th>Tên đơn vị</th>
                <th>Mã</th>
                <th>COD</th>
                <th>Trạng thái</th>
                <th class="text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $carriers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td>
                    <?php if($c->logo_url): ?>
                    <img class="w-8 h-8 rounded object-cover" src="<?php echo e($c->logo_url); ?>" alt="">
                    <?php else: ?>
                    <span class="inline-flex w-8 h-8 items-center justify-center rounded bg-slate-100 text-slate-500">
                        <i class="fa-solid fa-truck"></i>
                    </span>
                    <?php endif; ?>

                </td>
                <td class="font-medium"><?php echo e($c->name); ?></td>
                <td><code class="text-xs"><?php echo e($c->code); ?></code></td>
                <td><?php echo $c->supports_cod ? '<span class="badge badge-green">Có</span>' : '<span class="badge badge-amber">Không</span>'; ?></td>
                <td><?php echo $c->enabled ? '<span class="badge badge-green">Đang bật</span>' : '<span class="badge badge-amber">Đang tắt</span>'; ?></td>
                <td class="text-right">
                    <div class="actions">
                        <form class="inline" method="post" action="<?php echo e(route('admin.shipping.carriers.toggle', $c)); ?>">
                            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                            <input type="hidden" name="enabled" value="<?php echo e($c->enabled ? 0 : 1); ?>">
                            <button class="btn btn-soft btn-sm" title="Bật/Tắt">
                                <i class="fa-solid fa-toggle-<?php echo e($c->enabled ? 'on' : 'off'); ?>"></i>
                            </button>
                        </form>
                        <?php if(Route::has('admin.shipping.carriers.edit')): ?>
                        <a href="<?php echo e(route('admin.shipping.carriers.edit',$c)); ?>" class="btn btn-outline btn-sm">Sửa</a>
                        <?php endif; ?>

                        <form class="inline" method="post" action="<?php echo e(route('admin.shipping.carriers.destroy',$c)); ?>"
                            onsubmit="return confirm('Xoá đơn vị này?')">
                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                            <button class="btn btn-danger btn-sm">Xoá</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="6" class="py-6 text-center text-slate-500">Chưa có đơn vị.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if(method_exists($carriers,'links')): ?>
<div class="pagination mt-3"><?php echo e($carriers->onEachSide(1)->links('pagination::tailwind')); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/shipping/carriers/index.blade.php ENDPATH**/ ?>