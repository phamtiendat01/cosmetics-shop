
<?php $__env->startSection('title','Quản trị viên'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?>
<div class="alert alert-success mb-3" data-auto-dismiss="3000"><?php echo e(session('ok')); ?></div>
<?php endif; ?>
<?php if(session('err')): ?>
<div class="alert alert-danger mb-3" data-auto-dismiss="4000"><?php echo e(session('err')); ?></div>
<?php endif; ?>


<div class="toolbar">
    <div class="toolbar-title">Quản lý quản trị viên</div>
    <div class="toolbar-actions">
        <a href="<?php echo e(route('admin.users.create')); ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-user-plus"></i> Thêm quản trị viên
        </a>
    </div>
</div>


<?php $qs = request()->except('page','status'); $cur = request('status'); ?>
<div class="card p-2 mb-3">
    <div class="flex flex-wrap gap-2">
        <a href="<?php echo e(route('admin.users.index', $qs)); ?>"
            class="btn btn-ghost btn-sm <?php echo e($cur===null || $cur==='' ? 'ring-1 ring-rose-200' : ''); ?>">Tất cả</a>
        <a href="<?php echo e(route('admin.users.index', array_merge($qs,['status'=>'active']))); ?>"
            class="btn btn-ghost btn-sm <?php echo e($cur==='active' ? 'ring-1 ring-rose-200' : ''); ?>">Hoạt động</a>
        <a href="<?php echo e(route('admin.users.index', array_merge($qs,['status'=>'inactive']))); ?>"
            class="btn btn-ghost btn-sm <?php echo e($cur==='inactive' ? 'ring-1 ring-rose-200' : ''); ?>">Khoá</a>
    </div>
</div>


<div class="card p-3 mb-3">
    <form method="get" class="grid md:grid-cols-5 gap-2 items-center">
        <div class="md:col-span-2">
            <input class="form-control" name="q" value="<?php echo e(request('q')); ?>" placeholder="Tìm theo tên / email…">
        </div>

        <select class="form-control" name="role">
            <option value="">Tất cả vai trò</option>
            <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($r->name); ?>" <?php if(request('role')===$r->name): echo 'selected'; endif; ?>><?php echo e($r->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>

        <select class="form-control" name="status">
            <option value="">Tất cả trạng thái</option>
            <option value="active" <?php if(request('status')==='active' ): echo 'selected'; endif; ?>>Hoạt động</option>
            <option value="inactive" <?php if(request('status')==='inactive' ): echo 'selected'; endif; ?>>Khoá</option>
        </select>

        <div class="flex items-center gap-2">
            <button class="btn btn-soft btn-sm"><i class="fa-solid fa-filter"></i> Lọc</button>
            <a href="<?php echo e(route('admin.users.index')); ?>" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>


<div class="card table-wrap p-0">
    <table class="table-admin">
        <thead>
            <tr>
                <th style="width:56px">#</th>
                <th style="width:24%">Người dùng</th>
                <th>Email</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th class="col-actions">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e(($users->currentPage()-1)*$users->perPage() + $i + 1); ?></td>
                <td>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-700 grid place-content-center font-semibold">
                            <?php echo e(strtoupper(Str::substr($u->name,0,1))); ?>

                        </div>
                        <div>
                            <div class="font-semibold"><?php echo e($u->name); ?></div>
                            <div class="text-xs text-slate-500">ID: <?php echo e($u->id); ?></div>
                        </div>
                    </div>
                </td>
                <td><?php echo e($u->email); ?></td>
                <td>
                    <?php $__currentLoopData = $u->roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span class="badge"><?php echo e($r->name); ?></span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </td>
                <td>
                    <?php if($u->is_active): ?>
                    <span class="badge badge-green">Hoạt động</span>
                    <?php else: ?>
                    <span class="badge badge-red">Khoá</span>
                    <?php endif; ?>
                </td>
                <td class="col-actions">
                    <a class="btn btn-table btn-outline" href="<?php echo e(route('admin.users.edit',$u)); ?>">Sửa</a>
                    <form action="<?php echo e(route('admin.users.destroy',$u)); ?>" method="POST" class="inline"
                        onsubmit="return confirm('Xoá quản trị viên này?')">
                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                        <button class="btn btn-table btn-danger"
                            <?php if(($u->hasRole('super-admin') && \App\Models\User::role('super-admin')->count() <= 1) || auth()->id()===$u->id): echo 'disabled'; endif; ?>>
                                Xoá
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="6" class="py-6 text-center text-slate-500">Chưa có quản trị viên.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<div class="flex items-center justify-between mt-2">
    <div class="text-sm text-slate-600">
        <?php if($users->total()>0): ?>
        Hiển thị <?php echo e(($users->currentPage()-1)*$users->perPage()+1); ?>

        – <?php echo e(($users->currentPage()-1)*$users->perPage()+$users->count()); ?>

        / <?php echo e($users->total()); ?> tài khoản
        <?php endif; ?>
    </div>
    <div class="pagination">
        <?php echo e($users->onEachSide(1)->links()); ?>

    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
        const t = +el.getAttribute('data-auto-dismiss') || 3000;
        setTimeout(() => {
            el.classList.add('alert--hide');
            setTimeout(() => el.remove(), 350)
        }, t);
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/users/index.blade.php ENDPATH**/ ?>