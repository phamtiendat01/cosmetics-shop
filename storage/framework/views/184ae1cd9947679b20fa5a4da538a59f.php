
<?php $__env->startSection('title','Danh mục'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?>
<div class="alert alert-success mb-3" data-auto-dismiss="3000"><?php echo e(session('ok')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
<div class="alert alert-danger mb-3"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<div class="toolbar">
    <div class="toolbar-title">Quản lý danh mục</div>
    <a href="<?php echo e(route('admin.categories.create')); ?>" class="btn btn-primary btn-sm">+ Thêm</a>
</div>


<?php $v = $filters['view'] ?? 'all'; ?>
<div class="mb-3 flex flex-wrap gap-2 text-sm">
    <a class="btn btn-sm <?php echo e($v==='all'?'btn-primary':'btn-outline'); ?>"
        href="<?php echo e(route('admin.categories.index', array_merge(request()->except('page'), ['view'=>'all']))); ?>">
        Tất cả (<?php echo e($stats['all'] ?? 0); ?>)
    </a>
    <a class="btn btn-sm <?php echo e($v==='parents'?'btn-primary':'btn-outline'); ?>"
        href="<?php echo e(route('admin.categories.index', array_merge(request()->except('page'), ['view'=>'parents']))); ?>">
        Chỉ danh mục cha (<?php echo e($stats['parents'] ?? 0); ?>)
    </a>
    <a class="btn btn-sm <?php echo e($v==='children'?'btn-primary':'btn-outline'); ?>"
        href="<?php echo e(route('admin.categories.index', array_merge(request()->except('page'), ['view'=>'children']))); ?>">
        Chỉ danh mục con (<?php echo e($stats['children'] ?? 0); ?>)
    </a>
</div>


<div class="card p-3 mb-3">
    <form id="filterForm" method="get" class="grid md:grid-cols-4 gap-2 items-center">
        <input name="keyword" value="<?php echo e($filters['keyword'] ?? ''); ?>" class="form-control" placeholder="Tìm theo tên hoặc slug…">

        <select name="parent_id" id="parentSelect" class="form-control">
            <option value="">Tất cả danh mục cha</option>
            <?php $__currentLoopData = $parentsOpts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $text): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($id); ?>" <?php if(($filters['parent_id'] ?? '' )==$id): echo 'selected'; endif; ?>><?php echo e($text); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>

        <select name="status" id="statusSelect" class="form-control">
            <option value="">Tất cả trạng thái</option>
            <option value="active" <?php if(($filters['status'] ?? '' )==='active' ): echo 'selected'; endif; ?>>Đang hiển thị</option>
            <option value="inactive" <?php if(($filters['status'] ?? '' )==='inactive' ): echo 'selected'; endif; ?>>Đang ẩn</option>
        </select>

        <input type="hidden" name="view" value="<?php echo e($filters['view'] ?? 'all'); ?>">
        <div class="flex gap-2 items-center">
            <button class="btn btn-soft btn-sm">Lọc</button>
            <a class="btn btn-outline btn-sm" href="<?php echo e(route('admin.categories.index', ['view' => $filters['view'] ?? 'all'])); ?>">Reset</a>
        </div>
    </form>
</div>

<?php if($mode === 'tree'): ?>

<div class="card table-wrap p-0">
    <table class="table-admin">
        <thead>
            <tr>
                <th style="width:34px"></th>
                <th>Danh mục</th>
                <th>Slug</th>
                <th>SP</th>
                <th>Con</th>
                <th>Thứ tự</th>
                <th>Trạng thái</th>
                <th class="col-actions">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $parents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            
            <tr class="bg-slate-50">
                <td>
                    <?php if($p->children_count>0): ?>
                    <button type="button" class="btn btn-table btn-outline" data-toggle="#child-<?php echo e($p->id); ?>">▸</button>
                    <?php endif; ?>
                </td>
                <td class="font-semibold"><?php echo e($p->name); ?></td>
                <td class="text-slate-500"><?php echo e($p->slug); ?></td>
                <td><?php echo e($p->products_count); ?></td>
                <td><?php echo e($p->children_count); ?></td>
                <td><?php echo e($p->sort_order); ?></td>
                <td>
                    <?php if($p->is_active): ?>
                    <span class="badge badge-green"><span class="badge-dot"></span>Hiển thị</span>
                    <?php else: ?>
                    <span class="badge badge-red"><span class="badge-dot"></span>Ẩn</span>
                    <?php endif; ?>
                </td>
                <td class="col-actions">
                    <a class="btn btn-table btn-outline" href="<?php echo e(route('admin.categories.edit',$p)); ?>">Sửa</a>
                    <form method="post" action="<?php echo e(route('admin.categories.toggle',$p)); ?>" class="inline"><?php echo csrf_field(); ?>
                        <button class="btn btn-table btn-outline" type="submit"><?php echo e($p->is_active?'Ẩn':'Hiện'); ?></button>
                    </form>
                    <button type="button" class="btn btn-table btn-danger" data-confirm-delete data-url="<?php echo e(route('admin.categories.destroy',$p)); ?>">Xoá</button>
                </td>
            </tr>

            
            <?php if($p->children->count()): ?>
            <tr id="child-<?php echo e($p->id); ?>" class="hidden">
                <td></td>
                <td colspan="7" class="p-0">
                    <table class="table-admin inner">
                        <thead>
                            <tr>
                                <th>Tên</th>
                                <th>Slug</th>
                                <th>SP</th>
                                <th>Con</th>
                                <th>Thứ tự</th>
                                <th>Trạng thái</th>
                                <th class="col-actions">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $p->children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($c->name); ?></td>
                                <td class="text-slate-500"><?php echo e($c->slug); ?></td>
                                <td><?php echo e($c->products_count); ?></td>
                                <td><?php echo e($c->children_count); ?></td>
                                <td><?php echo e($c->sort_order); ?></td>
                                <td>
                                    <?php if($c->is_active): ?>
                                    <span class="badge badge-green"><span class="badge-dot"></span>Hiển thị</span>
                                    <?php else: ?>
                                    <span class="badge badge-red"><span class="badge-dot"></span>Ẩn</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-actions">
                                    <a class="btn btn-table btn-outline" href="<?php echo e(route('admin.categories.edit',$c)); ?>">Sửa</a>
                                    <form method="post" action="<?php echo e(route('admin.categories.toggle',$c)); ?>" class="inline"><?php echo csrf_field(); ?>
                                        <button class="btn btn-table btn-outline" type="submit"><?php echo e($c->is_active?'Ẩn':'Hiện'); ?></button>
                                    </form>
                                    <button type="button" class="btn btn-table btn-danger" data-confirm-delete data-url="<?php echo e(route('admin.categories.destroy',$c)); ?>">Xoá</button>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="8" class="py-6 text-center text-slate-500">Chưa có danh mục.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php else: ?>

<?php
$from = $categories->total() ? (($categories->currentPage()-1) * $categories->perPage() + 1) : 0;
$to = $categories->total() ? ($from + $categories->count() - 1) : 0;
?>
<?php if($categories->total() > 0): ?>
<div class="mb-2 text-sm text-slate-600">Hiển thị <?php echo e($from); ?>–<?php echo e($to); ?> / <?php echo e($categories->total()); ?> danh mục</div>
<?php endif; ?>

<form method="post" action="<?php echo e(route('admin.categories.bulk')); ?>">
    <?php echo csrf_field(); ?>
    <div class="card table-wrap p-0">
        <table class="table-admin" id="catTable">
            <thead>
                <tr>
                    <th style="width:34px"><input type="checkbox" id="chkAll"></th>
                    <th>STT</th>
                    <th>Tên</th>
                    <th>Slug</th>
                    <th>Danh mục cha</th>
                    <th>SP</th>
                    <th>Con</th>
                    <th>Thứ tự</th>
                    <th>Trạng thái</th>
                    <th class="col-actions">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><input type="checkbox" name="ids[]" value="<?php echo e($c->id); ?>"></td>
                    <td><?php echo e(($categories->currentPage()-1)*$categories->perPage() + $i + 1); ?></td>
                    <td class="font-medium">
                        <?php if($c->parent_id): ?> <span class="text-slate-400">—</span> <?php endif; ?> <?php echo e($c->name); ?>

                    </td>
                    <td class="text-slate-500"><?php echo e($c->slug); ?></td>
                    <td><?php echo e($c->parent?->name ?? '—'); ?></td>
                    <td><?php echo e($c->products_count); ?></td>
                    <td><?php echo e($c->children_count); ?></td>
                    <td><?php echo e($c->sort_order); ?></td>
                    <td>
                        <?php if($c->is_active): ?>
                        <span class="badge badge-green"><span class="badge-dot"></span>Hiển thị</span>
                        <?php else: ?>
                        <span class="badge badge-red"><span class="badge-dot"></span>Ẩn</span>
                        <?php endif; ?>
                    </td>
                    <td class="col-actions">
                        <a class="btn btn-table btn-outline" href="<?php echo e(route('admin.categories.edit',$c)); ?>">Sửa</a>
                        <form method="post" action="<?php echo e(route('admin.categories.toggle',$c)); ?>" class="inline"><?php echo csrf_field(); ?>
                            <button class="btn btn-table btn-outline" type="submit"><?php echo e($c->is_active?'Ẩn':'Hiện'); ?></button>
                        </form>
                        <button type="button" class="btn btn-table btn-danger" data-confirm-delete data-url="<?php echo e(route('admin.categories.destroy',$c)); ?>">Xoá</button>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="10" class="py-6 text-center text-slate-500">Chưa có danh mục.</td>
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
        <div class="pagination"><?php echo e($categories->onEachSide(1)->links('pagination::tailwind')); ?></div>
    </div>
</form>
<?php endif; ?>


<div id="confirmModal" class="modal hidden">
    <div class="modal-card">
        <div class="p-4 border-b">
            <div class="font-semibold">Xác nhận xoá</div>
            <div class="text-sm text-slate-500">Bạn chắc chắn muốn xoá danh mục này?</div>
        </div>
        <div class="p-4 flex justify-end gap-2">
            <button class="btn btn-outline btn-sm" data-close-modal>Huỷ</button>
            <form id="confirmForm" method="post"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button class="btn btn-danger btn-sm">Xoá</button>
            </form>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    // TomSelect
    if (document.getElementById('parentSelect')) new TomSelect('#parentSelect', {
        create: false,
        maxOptions: 1000
    });
    if (document.getElementById('statusSelect')) new TomSelect('#statusSelect', {
        create: false
    });

    // Toggle tree rows
    document.querySelectorAll('[data-toggle]')?.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.querySelector(btn.dataset.toggle);
            if (!target) return;
            target.classList.toggle('hidden');
            btn.textContent = target.classList.contains('hidden') ? '▸' : '▾';
        });
    });

    // Modal confirm delete
    const modal = document.getElementById('confirmModal');
    const form = document.getElementById('confirmForm');
    document.querySelectorAll('[data-confirm-delete]').forEach(btn => {
        btn.addEventListener('click', () => {
            form.action = btn.dataset.url;
            modal.classList.remove('hidden');
        });
    });
    document.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', () => modal.classList.add('hidden')));
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.classList.add('hidden');
    });

    // Check all (list mode)
    const chkAll = document.getElementById('chkAll');
    chkAll?.addEventListener('change', () => {
        document.querySelectorAll('input[name="ids[]"]').forEach(c => c.checked = chkAll.checked);
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?> 
<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/categories/index.blade.php ENDPATH**/ ?>