
<?php $__env->startSection('title','Thương hiệu'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?>
<div class="alert alert-success mb-3" data-auto-dismiss="3000"><?php echo e(session('ok')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
<div class="alert alert-danger mb-3"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<div class="toolbar">
    <div class="toolbar-title">Quản lý thương hiệu</div>
    <a href="<?php echo e(route('admin.brands.create')); ?>" class="btn btn-primary btn-sm">+ Thêm</a>
</div>


<?php $st = $filters['status'] ?? ''; ?>
<?php
$tab = function($key,$text,$cnt) use($st){
$active = $st===$key ? 'btn-primary' : 'btn-outline';
$url = $key ? request()->fullUrlWithQuery(['status'=>$key,'page'=>1]) : route('admin.brands.index');
return "<a class=\"btn btn-sm $active\" href=\"$url\">$text ($cnt)</a>";
};
?>
<div class="mb-3 flex flex-wrap gap-2 text-sm">
    <?php echo $tab('', 'Tất cả', $counts['all'] ?? 0); ?>

    <?php echo $tab('active', 'Đang hiển thị', $counts['active']?? 0); ?>

    <?php echo $tab('inactive','Đang ẩn', $counts['inactive']?? 0); ?>

    <?php echo $tab('has', 'Đang có sản phẩm',$counts['has'] ?? 0); ?>

    <?php echo $tab('empty', 'Chưa có sản phẩm',$counts['empty'] ?? 0); ?>

</div>

<div class="card p-3 mb-3">
    <form id="filterForm" method="get" class="grid md:grid-cols-4 gap-2 items-center">
        <input id="keywordInput" name="keyword" value="<?php echo e($filters['keyword'] ?? ''); ?>" class="form-control" placeholder="Tìm theo tên/slug (live)…">
        <div class="flex gap-2 items-center">
            <button class="btn btn-soft btn-sm" id="submitFilterBtn">Lọc</button>
            <a href="<?php echo e(route('admin.brands.index')); ?>" id="resetBtn" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>


<form method="post" action="<?php echo e(route('admin.brands.bulk')); ?>">
    <?php echo csrf_field(); ?>
    <div class="card table-wrap p-0">
        <table class="table-admin" id="brandTable">
            <colgroup>
                <col style="width:34px">
                <col style="width:64px">
                <col style="width:80px">
                <col style="width:200px"> 
                <col style="width:220px">
                <col style="width:80px">
                <col style="width:80px">
                <col style="width:120px">
                <col style="width:52px"> 
            </colgroup>
            <thead>
                <tr>
                    <th><input type="checkbox" id="chkAll"></th>
                    <th>ID</th>
                    <th>Logo</th>
                    <th>Tên</th>
                    <th>Website</th>
                    <th>SP</th>
                    <th>Thứ tự</th>
                    <th>Trạng thái</th>
                    <th class="text-center"> </th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="brand-row" data-name="<?php echo e(Str::lower($b->name.' '.$b->slug)); ?>">
                    <td><input type="checkbox" name="ids[]" value="<?php echo e($b->id); ?>"></td>
                    <td><?php echo e($b->id); ?></td>
                    <td>
                        <img src="<?php echo e($b->logo ? asset('storage/'.$b->logo) : 'https://placehold.co/60x60?text=Logo'); ?>"
                            class="w-12 h-12 rounded object-cover" alt="logo">
                    </td>
                    <td class="font-medium">
                        <a href="<?php echo e(route('admin.brands.edit',$b)); ?>" class="link"><?php echo e($b->name); ?></a>
                        <div class="text-[10px] text-slate-400 truncate">slug: <?php echo e($b->slug); ?></div>
                    </td>
                    <td>
                        <?php if($b->website): ?>
                        <a href="<?php echo e($b->website); ?>" target="_blank" class="link truncate block max-w-[210px]"><?php echo e(parse_url($b->website,PHP_URL_HOST) ?? 'Mở'); ?></a>
                        <?php else: ?>
                        —
                        <?php endif; ?>
                    </td>
                    <td><?php echo e($b->products_count); ?></td>
                    <td><?php echo e($b->sort_order); ?></td>
                    <td>
                        <?php if($b->is_active): ?>
                        <span class="badge badge-green"><span class="badge-dot"></span>Hiển thị</span>
                        <?php else: ?>
                        <span class="badge badge-red"><span class="badge-dot"></span>Ẩn</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        
                        <button type="button"
                            class="btn btn-outline btn-xs !p-1 js-toggle"
                            title="<?php echo e($b->is_active ? 'Ẩn' : 'Hiện'); ?>"
                            data-url="<?php echo e(route('admin.brands.toggle',$b)); ?>">
                            <i class="fa-solid <?php echo e($b->is_active ? 'fa-eye-slash' : 'fa-eye'); ?> text-[12px]"></i>
                        </button>

                        
                        <button type="button"
                            class="btn btn-danger btn-xs !p-1"
                            title="Xoá"
                            data-confirm-delete
                            data-url="<?php echo e(route('admin.brands.destroy',$b)); ?>">
                            <i class="fa-solid fa-trash text-[12px]"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="9" class="py-6 text-center text-slate-500">Chưa có thương hiệu.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-2 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <select name="act" class="form-control" style="max-width:180px">
                <option value="activate">Bật hiển thị</option>
                <option value="deactivate">Tắt hiển thị</option>
                <option value="delete">Xoá đã chọn</option>
            </select>
            <button class="btn btn-secondary btn-sm">Áp dụng</button>
        </div>
        <div class="pagination"><?php echo e($brands->links()); ?></div>
    </div>
</form>


<form id="toggleForm" method="post" class="hidden"><?php echo csrf_field(); ?></form>


<div id="confirmModal" class="modal hidden">
    <div class="modal-card">
        <div class="p-4 border-b">
            <div class="font-semibold">Xác nhận xoá</div>
            <div class="text-sm text-slate-500">Bạn chắc chắn muốn xoá thương hiệu này?</div>
        </div>
        <div class="p-4 flex justify-end gap-2">
            <button class="btn btn-outline btn-sm" data-close-modal>Huỷ</button>
            <form id="confirmForm" method="post">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button class="btn btn-danger btn-sm">Xoá</button>
            </form>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    // Live search trong trang
    const kw = document.getElementById('keywordInput');
    const rows = Array.from(document.querySelectorAll('.brand-row'));
    let timer;

    function doFilter() {
        const q = (kw.value || '').trim().toLowerCase();
        rows.forEach(tr => {
            const hit = !q || tr.dataset.name.includes(q);
            tr.style.display = hit ? '' : 'none';
        });
    }
    kw.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(doFilter, 180);
    });
    kw.addEventListener('keydown', e => {
        if (e.key === 'Enter') e.preventDefault();
    });
    doFilter();

    // Check all
    document.getElementById('chkAll').addEventListener('change', (e) => {
        document.querySelectorAll('input[name="ids[]"]').forEach(c => c.checked = e.target.checked);
    });

    // Modal confirm delete
    const modal = document.getElementById('confirmModal');
    const formDel = document.getElementById('confirmForm');
    document.querySelectorAll('[data-confirm-delete]').forEach(btn => {
        btn.addEventListener('click', () => {
            formDel.action = btn.dataset.url;
            modal.classList.remove('hidden');
        });
    });
    document.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', () => modal.classList.add('hidden')));
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.classList.add('hidden');
    });

    // Toggle dùng form ẩn (để không bị nested form)
    const toggleForm = document.getElementById('toggleForm');
    document.querySelectorAll('.js-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            toggleForm.action = btn.dataset.url;
            toggleForm.submit();
        });
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/brands/index.blade.php ENDPATH**/ ?>