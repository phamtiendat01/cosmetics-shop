
<?php $__env->startSection('title','Mã vận chuyển'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?>
<div class="alert alert-success mb-3" data-auto-dismiss="3000"><?php echo e(session('ok')); ?></div>
<?php endif; ?>


<div class="toolbar">
    <div class="toolbar-title">Quản lý mã vận chuyển</div>
    <div class="toolbar-actions">
        <a href="<?php echo e(route('admin.shipvouchers.create')); ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Tạo mã
        </a>
    </div>
</div>

<?php
$qs = request()->except('page','status');
$cur = request('status');
?>


<div class="card p-2 mb-3">
    <div class="flex flex-wrap gap-2">

        <?php $active = ($cur===null || $cur===''); $count = (int)($counts['all'] ?? 0); ?>
        <a href="<?php echo e(route('admin.shipvouchers.index', $qs)); ?>"
            class="btn btn-ghost btn-sm <?php echo e($active ? 'bg-rose-600 text-white hover:bg-rose-600' : ''); ?>">
            Tất cả
            <span class="ml-1 text-xs rounded-full px-1.5 <?php echo e($active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'); ?>">
                <?php echo e($count); ?>

            </span>
        </a>

        <?php $active = ($cur==='running'); $count = (int)($counts['running'] ?? 0); ?>
        <a href="<?php echo e(route('admin.shipvouchers.index', array_merge($qs,['status'=>'running']))); ?>"
            class="btn btn-ghost btn-sm <?php echo e($active ? 'bg-rose-600 text-white hover:bg-rose-600' : ''); ?>">
            Đang diễn ra
            <span class="ml-1 text-xs rounded-full px-1.5 <?php echo e($active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'); ?>">
                <?php echo e($count); ?>

            </span>
        </a>

        <?php $active = ($cur==='expired'); $count = (int)($counts['expired'] ?? 0); ?>
        <a href="<?php echo e(route('admin.shipvouchers.index', array_merge($qs,['status'=>'expired']))); ?>"
            class="btn btn-ghost btn-sm <?php echo e($active ? 'bg-rose-600 text-white hover:bg-rose-600' : ''); ?>">
            Hết hạn
            <span class="ml-1 text-xs rounded-full px-1.5 <?php echo e($active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'); ?>">
                <?php echo e($count); ?>

            </span>
        </a>

        <?php $active = ($cur==='active'); $count = (int)($counts['active'] ?? 0); ?>
        <a href="<?php echo e(route('admin.shipvouchers.index', array_merge($qs,['status'=>'active']))); ?>"
            class="btn btn-ghost btn-sm <?php echo e($active ? 'bg-rose-600 text-white hover:bg-rose-600' : ''); ?>">
            Đang bật
            <span class="ml-1 text-xs rounded-full px-1.5 <?php echo e($active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'); ?>">
                <?php echo e($count); ?>

            </span>
        </a>

        <?php $active = ($cur==='inactive'); $count = (int)($counts['inactive'] ?? 0); ?>
        <a href="<?php echo e(route('admin.shipvouchers.index', array_merge($qs,['status'=>'inactive']))); ?>"
            class="btn btn-ghost btn-sm <?php echo e($active ? 'bg-rose-600 text-white hover:bg-rose-600' : ''); ?>">
            Đang tắt
            <span class="ml-1 text-xs rounded-full px-1.5 <?php echo e($active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'); ?>">
                <?php echo e($count); ?>

            </span>
        </a>
    </div>
</div>


<div class="card p-3 mb-3">
    <form method="get" class="grid md:grid-cols-5 gap-2 items-center">
        <div class="md:col-span-2">
            <input class="form-control" name="q" value="<?php echo e(request('q')); ?>" placeholder="Tìm theo mã / tên…">
        </div>

        <select class="form-control" name="status">
            <option value="">Tất cả trạng thái</option>
            <option value="active" <?php if(request('status')==='active' ): echo 'selected'; endif; ?>>Đang bật</option>
            <option value="inactive" <?php if(request('status')==='inactive' ): echo 'selected'; endif; ?>>Đang tắt</option>
            <option value="running" <?php if(request('status')==='running' ): echo 'selected'; endif; ?>>Đang diễn ra</option>
            <option value="expired" <?php if(request('status')==='expired' ): echo 'selected'; endif; ?>>Hết hạn</option>
        </select>

        <select class="form-control" disabled>
            <option selected>Loại mã: Mã vận chuyển</option>
        </select>

        <div class="flex items-center gap-2">
            <button class="btn btn-soft btn-sm"><i class="fa-solid fa-filter"></i> Lọc</button>
            <a href="<?php echo e(route('admin.shipvouchers.index')); ?>" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>


<div class="card table-wrap p-0">
    <table class="table-admin">
        <thead>
            <tr>
                <th style="width:56px">#</th>
                <th style="width:22%">Mã / Tên</th>
                <th>Giảm</th>
                <th>Giới hạn</th>
                <th>Áp dụng</th>
                <th>Thời gian</th>
                <th>Trạng thái</th>
                <th class="col-actions">Thao tác</th>
            </tr>
        </thead>

        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
            $now = now();

            if ($v->isRunning()) {
            $timeBadge = 'Đang diễn ra';
            $timeCls = 'badge-green';
            } elseif ($v->end_at && $v->end_at < $now) {
                $timeBadge='Hết hạn' ;
                $timeCls='badge-red' ;
                } else {
                $timeBadge='Chưa bắt đầu' ;
                $timeCls='badge-amber' ;
                }

                if ($v->discount_type === 'percent') {
                $giam = rtrim(rtrim(number_format($v->amount, 2, '.', ''), '0'), '.') . '%';
                if ($v->max_discount) {
                $giam .= ' (tối đa ' . number_format($v->max_discount) . '₫)';
                }
                } else {
                $giam = number_format($v->amount) . '₫';
                }

                $startTxt = $v->start_at ? $v->start_at->format('d/m/Y H:i') : '—';
                $endTxt = $v->end_at ? $v->end_at->format('d/m/Y H:i') : '—';
                ?>

                <tr>
                    <td><?php echo e(($items->currentPage()-1)*$items->perPage() + $i + 1); ?></td>

                    <td>
                        <div class="font-semibold"><?php echo e($v->code); ?></div>
                        <div class="text-xs text-slate-500 line-clamp-1"><?php echo e($v->title); ?></div>
                    </td>

                    <td><?php echo e($giam); ?></td>

                    <td class="text-xs">
                        Dùng:
                        <span class="badge">
                            <?php echo e((int)($usageCounts[$v->id] ?? 0)); ?> / <?php echo e($v->usage_limit ?? '∞'); ?>

                        </span><br>
                        Mỗi user: <?php echo e($v->per_user_limit ?? '∞'); ?>

                    </td>

                    <td class="text-xs">
                        <?php if($v->min_order): ?> Đơn từ <?php echo e(number_format($v->min_order)); ?>₫<br><?php endif; ?>
                        <?php if($v->carriers): ?> Hãng: <?php echo e(implode(', ', (array)$v->carriers)); ?> <?php endif; ?>
                    </td>

                    <td class="text-xs"><?php echo e($startTxt); ?> → <?php echo e($endTxt); ?></td>

                    
                    <td>
                        <div class="flex items-center gap-1 flex-wrap">
                            <span class="badge <?php echo e($v->is_active ? 'badge-green' : 'badge-red'); ?>">
                                <?php echo e($v->is_active ? 'Bật' : 'Tắt'); ?>

                            </span>
                            <span class="badge <?php echo e($timeCls); ?>"><?php echo e($timeBadge); ?></span>
                        </div>
                    </td>

                    
                    <td class="col-actions">
                        <a class="btn btn-table btn-outline" href="<?php echo e(route('admin.shipvouchers.edit',$v)); ?>">Sửa</a>

                        <form action="<?php echo e(route('admin.shipvouchers.toggle',$v)); ?>" method="post" class="inline">
                            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                            <label class="sv-switch" title="Bật/Tắt mã">
                                <input type="checkbox" <?php echo e($v->is_active ? 'checked' : ''); ?> onchange="this.form.submit()">
                                <span class="sv-slider"></span>
                            </label>
                        </form>

                        <form class="inline" action="<?php echo e(route('admin.shipvouchers.destroy',$v)); ?>"
                            method="post" onsubmit="return confirm('Xoá mã <?php echo e($v->code); ?>?')">
                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                            <button class="btn btn-table btn-danger">Xoá</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="8" class="py-6 text-center text-slate-500">Chưa có mã.</td>
                </tr>
                <?php endif; ?>
        </tbody>
    </table>
</div>


<div class="flex items-center justify-between mt-2">
    <div class="text-sm text-slate-600">
        <?php if($items->total() > 0): ?>
        Hiển thị <?php echo e(($items->currentPage()-1)*$items->perPage()+1); ?>

        – <?php echo e(($items->currentPage()-1)*$items->perPage()+$items->count()); ?>

        / <?php echo e($items->total()); ?> mã
        <?php endif; ?>
    </div>
    <div class="pagination"><?php echo e($items->onEachSide(1)->links()); ?></div>
</div>


<style>
    .sv-switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 22px;
        vertical-align: middle
    }

    .sv-switch input {
        display: none
    }

    .sv-slider {
        position: absolute;
        inset: 0;
        background: #e5e7eb;
        border-radius: 999px;
        transition: .2s
    }

    .sv-slider:before {
        content: "";
        position: absolute;
        width: 18px;
        height: 18px;
        left: 2px;
        top: 2px;
        background: #fff;
        border-radius: 999px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .2);
        transition: .2s
    }

    .sv-switch input:checked+.sv-slider {
        background: #10b981
    }

    .sv-switch input:checked+.sv-slider:before {
        transform: translateX(18px)
    }
</style>


<script>
    document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
        const t = +el.getAttribute('data-auto-dismiss') || 3000;
        setTimeout(() => {
            el.classList.add('alert--hide');
            setTimeout(() => el.remove(), 350);
        }, t);
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/shipvouchers/index.blade.php ENDPATH**/ ?>