
<?php $__env->startSection('title','Đơn hàng'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?>
<div class="alert alert-success mb-3" data-auto-dismiss="3000"><?php echo e(session('ok')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
<div class="alert alert-danger mb-3"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<div class="toolbar">
    <div class="toolbar-title">Quản lý đơn hàng</div>
    <div class="toolbar-actions">
        <a href="<?php echo e(request()->fullUrlWithQuery(['export'=>'csv'])); ?>" class="btn btn-outline btn-sm">Xuất CSV</a>
    </div>
</div>


<div class="mb-3 flex flex-wrap gap-2 text-sm">
    <?php $st = $filters['status'] ?? ''; ?>
    <?php $tab = function($key,$text,$cnt) use($st){ $active = $st===$key ? 'btn-primary' : 'btn-outline'; $url = $key? request()->fullUrlWithQuery(['status'=>$key,'page'=>1]) : route('admin.orders.index'); return "<a class=\"btn btn-sm $active\" href=\"$url\">$text ($cnt)</a>"; }; ?>
    <?php echo $tab('', 'Tất cả', $counts['all']); ?>

    <?php echo $tab('pending','Chờ xác nhận',$counts['pending']); ?>

    <?php echo $tab('confirmed','Đã xác nhận',$counts['confirmed']); ?>

    <?php echo $tab('processing','Đang xử lý',$counts['processing']); ?>

    <?php echo $tab('shipping','Đang giao',$counts['shipping']); ?>

    <?php echo $tab('completed','Hoàn tất',$counts['completed']); ?>

    <?php echo $tab('cancelled','Đã huỷ',$counts['cancelled']); ?>

    <?php echo $tab('refunded','Đã hoàn tiền',$counts['refunded']); ?>

</div>


<div class="card p-3 mb-3">
    <form method="get" class="grid md:grid-cols-5 gap-2 items-center">
        <input name="keyword" value="<?php echo e($filters['keyword'] ?? ''); ?>" class="form-control" placeholder="Mã đơn / Tên / SĐT / Email">
        <select name="status" class="form-control" id="statusSelect">
            <option value="">Trạng thái</option>
            <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($k); ?>" <?php if(($filters['status']??'')===$k): echo 'selected'; endif; ?>><?php echo e($v); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <select name="payment_status" class="form-control" id="paySelect">
            <option value="">Thanh toán</option>
            <?php $__currentLoopData = $payOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($k); ?>" <?php if(($filters['payment_status']??'')===$k): echo 'selected'; endif; ?>><?php echo e($v); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <select name="sort" class="form-control" id="sortSelect">
            <option value="newest" <?php if(($filters['sort']??'newest')==='newest' ): echo 'selected'; endif; ?>>Mới nhất</option>
            <option value="total_desc" <?php if(($filters['sort']??'')==='total_desc' ): echo 'selected'; endif; ?>>Tổng cao → thấp</option>
            <option value="total_asc" <?php if(($filters['sort']??'')==='total_asc' ): echo 'selected'; endif; ?>>Tổng thấp → cao</option>
        </select>
        <div class="flex items-center gap-2">
            <button class="btn btn-soft btn-sm">Lọc</button>
            <a href="<?php echo e(route('admin.orders.index')); ?>" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>


<form id="bulkForm" method="post" action="<?php echo e(route('admin.orders.bulk')); ?>" class="hidden card p-3 mb-3">
    <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
    <input type="hidden" name="ids[]" id="bulkIds">
    <div class="flex items-center gap-2">
        <span class="text-sm text-slate-600" id="bulkCount">0 đã chọn</span>
        <select name="action" class="form-control" style="max-width:200px">
            <option value="set_status">Đặt trạng thái</option>
            <option value="set_payment">Đặt thanh toán</option>
        </select>
        <select name="status" class="form-control" style="max-width:220px">
            <option value="">— Trạng thái —</option>
            <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>"><?php echo e($v); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <select name="payment_status" class="form-control" style="max-width:220px">
            <option value="">— Thanh toán —</option>
            <?php $__currentLoopData = $payOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>"><?php echo e($v); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button class="btn btn-primary btn-sm">Áp dụng</button>
    </div>
</form>

<?php
$from = $orders->total() ? (($orders->currentPage()-1) * $orders->perPage() + 1) : 0;
$to = $orders->total() ? ($from + $orders->count() - 1) : 0;
?>
<?php if($orders->total() > 0): ?>
<div class="mb-2 text-sm text-slate-600">Hiển thị <?php echo e($from); ?>–<?php echo e($to); ?> / <?php echo e($orders->total()); ?> đơn</div>
<?php endif; ?>

<div class="card table-wrap p-0">
    <table class="table-admin w-full" id="orderTable">
        <colgroup>
            <col style="width:36px">
            <col style="width:130px">
            <col>
            <col style="width:170px">
            <col style="width:150px">
            <col style="width:150px">
            <col style="width:160px">
        </colgroup>
        <thead>
            <tr>
                <th><input type="checkbox" id="checkAll"></th>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>Thanh toán</th>
                <th>Trạng thái</th>
                <th>Tổng</th>
                <th>Đặt lúc</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><input type="checkbox" class="rowCheck" value="<?php echo e($o->id); ?>"></td>
                <td>
                    
                    <a class="link font-semibold" href="<?php echo e(route('admin.orders.show', ['admin_order' => $o->id])); ?>">#<?php echo e($o->code); ?></a>
                    <div class="text-xs text-slate-500"><?php echo e($o->items_count); ?> SP</div>
                </td>
                <td class="whitespace-nowrap">
                    <div class="font-medium"><?php echo e($o->customer_name); ?></div>
                    <div class="text-xs text-slate-500">
                        <?php echo e($o->customer_phone); ?><?php echo e($o->customer_email ? ' · '.$o->customer_email : ''); ?>

                    </div>

                    <?php if(($o->pending_returns_count ?? 0) > 0): ?>
                    <div class="mt-1">
                        <span class="inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50
                               text-rose-700 text-[11px] font-medium px-2 py-0.5">
                            <i class="fa-solid fa-rotate-left"></i>
                            Trả hàng: <?php echo e($o->pending_returns_count); ?>

                        </span>
                    </div>
                    <?php endif; ?>
                </td>
                <td><span class="badge <?php echo e($o->payment_status_badge); ?>"><?php echo e($o->payment_status_label); ?></span>
                    <div class="text-xs text-slate-500 mt-1"><?php echo e($o->payment_method); ?></div>
                </td>
                <td><span class="badge <?php echo e($o->status_badge); ?>"><?php echo e($o->status_label); ?></span></td>
                <td class="font-semibold"><?php echo e(number_format($o->grand_total,0)); ?>₫</td>
                <td><?php echo e(optional($o->placed_at)->format('d/m/Y H:i') ?? '-'); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="7" class="py-6 text-center text-slate-500">Chưa có đơn hàng.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="pagination mt-3">
    <?php echo e($orders->onEachSide(1)->links('pagination::tailwind')); ?>

</div>

<?php $__env->startPush('scripts'); ?>
<script>
    if (document.getElementById('statusSelect')) new TomSelect('#statusSelect', {
        create: false
    });
    if (document.getElementById('paySelect')) new TomSelect('#paySelect', {
        create: false
    });
    if (document.getElementById('sortSelect')) new TomSelect('#sortSelect', {
        create: false
    });

    // Bulk
    const bulkBar = document.getElementById('bulkForm');
    const bulkIds = document.getElementById('bulkIds');
    const bulkCount = document.getElementById('bulkCount');
    const rowChecks = Array.from(document.querySelectorAll('.rowCheck'));
    const checkAll = document.getElementById('checkAll');

    function refreshBulk() {
        const ids = rowChecks.filter(c => c.checked).map(c => c.value);
        bulkIds.value = '';
        document.querySelectorAll('#bulkForm input[name="ids[]"]').forEach(n => n.remove());
        ids.forEach(id => {
            const i = document.createElement('input');
            i.type = 'hidden';
            i.name = 'ids[]';
            i.value = id;
            bulkBar.appendChild(i);
        });
        bulkCount.textContent = ids.length + ' đã chọn';
        bulkBar.classList.toggle('hidden', ids.length === 0);
    }
    rowChecks.forEach(c => c.addEventListener('change', refreshBulk));
    checkAll?.addEventListener('change', () => {
        rowChecks.forEach(c => c.checked = checkAll.checked);
        refreshBulk();
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/orders/index.blade.php ENDPATH**/ ?>