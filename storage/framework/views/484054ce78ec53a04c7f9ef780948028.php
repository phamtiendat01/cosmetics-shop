
<?php $__env->startSection('title','Đánh giá'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?>
<div class="alert alert-success mb-3" data-auto-dismiss="3000"><?php echo e(session('ok')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
<div class="alert alert-danger mb-3"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<div class="toolbar">
    <div class="toolbar-title">Quản lý đánh giá sản phẩm</div>
</div>

<?php
// Chuẩn hoá filters theo controller mới (q, product, rating, state)
$filters = $filters ?? [];
$state = (string)($filters['state'] ?? '');
$q = (string)($filters['q'] ?? '');
$product = (string)($filters['product'] ?? '');
$rating = (string)($filters['rating'] ?? '');
$counts = $counts ?? ['all'=>0,'approved'=>0,'pending'=>0];

$tab = function(string $key, string $text, int $cnt) use ($state) {
$active = $state === $key ? 'btn-primary' : 'btn-outline';
$url = $key !== '' ? request()->fullUrlWithQuery(['state' => $key, 'page' => 1])
: route('admin.reviews.index');
return "<a class=\"btn btn-sm $active\" href=\"$url\">$text ($cnt)</a>";
};
?>

<div class="mb-3 flex flex-wrap gap-2 text-sm">
    <?php echo $tab('', 'Tất cả', $counts['all'] ?? 0); ?>

    <?php echo $tab('approved', 'Đã duyệt',$counts['approved'] ?? 0); ?>

    <?php echo $tab('pending', 'Chờ duyệt',$counts['pending'] ?? 0); ?>

</div>

<div class="card p-3 mb-3">
    <form id="filterForm" method="get" class="grid md:grid-cols-5 gap-2 items-center">
        <input name="q" value="<?php echo e($q); ?>" class="form-control search" placeholder="Tìm theo tiêu đề / nội dung…">
        <input name="product" value="<?php echo e($product); ?>" class="form-control" placeholder="Tên sản phẩm…">

        <select name="rating" class="form-control">
            <option value="">Sao (tất cả)</option>
            <?php for($i=5;$i>=1;$i--): ?>
            <option value="<?php echo e($i); ?>" <?php if($rating===$i.''): echo 'selected'; endif; ?>><?php echo e($i); ?> sao</option>
            <?php endfor; ?>
        </select>

        <select name="state" class="form-control">
            <option value="">Trạng thái</option>
            <option value="approved" <?php if($state==='approved' ): echo 'selected'; endif; ?>>Đã duyệt</option>
            <option value="pending" <?php if($state==='pending' ): echo 'selected'; endif; ?>>Chờ duyệt</option>
        </select>

        <div class="flex items-center gap-2">
            <button class="btn btn-soft btn-sm">Lọc</button>
            <a href="<?php echo e(route('admin.reviews.index')); ?>" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>

<form id="bulkForm" method="post" class="card p-3 mb-3 hidden">
    <?php echo csrf_field(); ?>
    <input type="hidden" id="bulkMethod" name="_method" value="POST">
    <div class="flex items-center gap-2">
        <span class="text-sm text-slate-600" id="bulkCount">0 đã chọn</span>

        
        <button id="btnBulkApprove"
            formaction="<?php echo e(route('admin.reviews.bulk-approve')); ?>"
            formmethod="POST"
            class="btn btn-primary btn-sm">
            Duyệt đã chọn
        </button>

        
        <button id="btnBulkDestroy"
            formaction="<?php echo e(route('admin.reviews.bulk-destroy')); ?>"
            formmethod="POST"
            class="btn btn-danger btn-sm">
            Xoá đã chọn
        </button>
    </div>
    <div id="bulkInputs"></div>
</form>

<?php
$from = $reviews->total() ? (($reviews->currentPage()-1) * $reviews->perPage() + 1) : 0;
$to = $reviews->total() ? ($from + $reviews->count() - 1) : 0;
?>
<?php if($reviews->total() > 0): ?>
<div class="mb-2 text-sm text-slate-600">
    Hiển thị <?php echo e($from); ?>–<?php echo e($to); ?> / <?php echo e($reviews->total()); ?> đánh giá
</div>
<?php endif; ?>

<div class="card table-wrap p-0">
    <table class="table-admin w-full">
        
        <colgroup>
            <col style="width:36px">
            <col> 
            <col style="width:260px"> 
            <col style="width:180px"> 
            <col style="width:120px"> 
            <col style="width:140px"> 
            <col style="width:150px"> 
            <col style="width:180px"> 
        </colgroup>
        <thead>
            <tr>
                <th><input type="checkbox" id="chkAll"></th>
                <th>Đánh giá</th>
                <th>Sản phẩm</th>
                <th>Người dùng</th>
                <th>Sao</th>
                <th>Trạng thái</th>
                <th>Thời gian</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $reviews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><input type="checkbox" class="row-chk" value="<?php echo e($r->id); ?>"></td>
                <td>
                    <div class="font-medium"><?php echo e($r->title ?: '—'); ?></div>
                    <div class="text-sm text-slate-600 line-clamp-2"><?php echo e($r->content); ?></div>
                </td>
                <td>
                    <?php if($r->product): ?>
                    <a class="text-blue-600 hover:underline"
                        href="<?php echo e(route('product.show', $r->product->slug)); ?>" target="_blank">
                        <?php echo e($r->product->name); ?>

                    </a>
                    <?php else: ?>
                    —
                    <?php endif; ?>
                </td>
                <td><?php echo e(optional($r->user)->name ?? 'Ẩn danh'); ?></td>
                <td>
                    <div class="text-amber-500">
                        <?php for($i=1;$i<=5;$i++): ?>
                            <i class="fa-solid fa-star <?php echo e($i <= ((int)$r->rating) ? '' : 'opacity-30'); ?>"></i>
                            <?php endfor; ?>
                    </div>
                </td>
                <td>
                    <?php if($r->is_approved): ?>
                    <span class="badge badge-green">
                        <span class="badge-dot" style="background:#10b981"></span> Đã duyệt
                    </span>
                    <?php else: ?>
                    <span class="badge badge-amber">
                        <span class="badge-dot" style="background:#f59e0b"></span> Chờ duyệt
                    </span>
                    <?php endif; ?>
                </td>
                <td class="text-sm text-slate-600"><?php echo e(optional($r->created_at)->format('d/m/Y H:i')); ?></td>
                <td class="text-right">
                    <div class="actions">
                        <?php if(!$r->is_approved): ?>
                        <form method="post" action="<?php echo e(route('admin.reviews.approve',$r)); ?>" class="inline">
                            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                            <button class="btn btn-soft btn-sm">Duyệt</button>
                        </form>
                        <?php else: ?>
                        <form method="post" action="<?php echo e(route('admin.reviews.unapprove',$r)); ?>" class="inline">
                            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                            <button class="btn btn-outline btn-sm">Bỏ duyệt</button>
                        </form>
                        <?php endif; ?>

                        <a href="<?php echo e(route('admin.reviews.show',$r)); ?>" class="btn btn-ghost btn-sm">Chi tiết</a>

                        <form method="post" action="<?php echo e(route('admin.reviews.destroy',$r)); ?>" class="inline"
                            onsubmit="return confirm('Xoá đánh giá này?')">
                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                            <button class="btn btn-danger btn-sm">Xoá</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="8" class="py-6 text-center text-slate-500">Chưa có đánh giá.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="pagination mt-3">
    <?php echo e($reviews->onEachSide(1)->links('pagination::tailwind')); ?>

</div>

<?php $__env->startPush('scripts'); ?>
<script>
    (function() {
        const chkAll = document.getElementById('chkAll');
        const checks = Array.from(document.querySelectorAll('.row-chk'));
        const bulk = document.getElementById('bulkForm');
        const bulkCnt = document.getElementById('bulkCount');
        const bulkInputs = document.getElementById('bulkInputs');
        const bulkMethod = document.getElementById('bulkMethod');
        const btnApprove = document.getElementById('btnBulkApprove');
        const btnDestroy = document.getElementById('btnBulkDestroy');

        function refresh() {
            const ids = checks.filter(c => c.checked).map(c => c.value);
            bulk.classList.toggle('hidden', ids.length === 0);
            bulkCnt.textContent = ids.length + ' đã chọn';
            bulkInputs.innerHTML = ids.map(id => `<input type="hidden" name="ids[]" value="${id}">`).join('');
        }
        chkAll?.addEventListener('change', () => {
            checks.forEach(c => c.checked = chkAll.checked);
            refresh();
        });
        checks.forEach(c => c.addEventListener('change', refresh));

        btnApprove?.addEventListener('click', () => {
            bulkMethod.value = 'POST';
        });
        btnDestroy?.addEventListener('click', (e) => {
            if (!confirm('Xoá các đánh giá đã chọn?')) {
                e.preventDefault();
                return;
            }
            bulkMethod.value = 'DELETE';
        });
    })();
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/reviews/index.blade.php ENDPATH**/ ?>