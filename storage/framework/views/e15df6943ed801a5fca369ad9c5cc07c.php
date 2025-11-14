
<?php $__env->startSection('title','Vai trò & Quyền'); ?>

<?php $__env->startSection('content'); ?>
<?php
// Chọn role hiện tại theo ?role=ID, nếu không có lấy cái đầu tiên
$currentRole = $roles->firstWhere('id', (int)request('role')) ?? $roles->first();
?>


<?php if(session('ok')): ?>
<div class="alert mb-4 rounded-md border border-green-200 bg-green-50 text-green-700 px-4 py-3" data-auto-dismiss="3000">
    <?php echo e(session('ok')); ?>

</div>
<?php endif; ?>
<?php if(session('err')): ?>
<div class="alert mb-4 rounded-md border border-red-200 bg-red-50 text-red-700 px-4 py-3">
    <?php echo e(session('err')); ?>

</div>
<?php endif; ?>
<?php if($errors->any()): ?>
<div class="alert mb-4 rounded-md border border-red-200 bg-red-50 text-red-700 px-4 py-3">
    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div>• <?php echo e($e); ?></div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-12 gap-6">
    
    <aside class="col-span-12 lg:col-span-4">
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                <h2 class="font-semibold">Vai trò</h2>
                <button id="btnOpenCreateRole" class="text-sm px-3 py-1.5 rounded-md bg-rose-600 text-white hover:bg-rose-700">
                    + Thêm
                </button>
            </div>

            
            <div id="createRoleForm" class="hidden px-4 py-3 border-b border-slate-200 bg-slate-50">
                <form action="<?php echo e(route('admin.roles.store')); ?>" method="POST" class="flex items-center gap-2">
                    <?php echo csrf_field(); ?>
                    <input name="name" required placeholder="Tên vai trò (vd: staff)"
                        class="flex-1 px-3 py-2 text-sm border border-slate-300 rounded-md outline-none focus:ring-2 focus:ring-rose-300">
                    <button class="px-3 py-2 text-sm rounded-md border">Huỷ</button>
                    <button class="px-3 py-2 text-sm rounded-md bg-rose-600 text-white hover:bg-rose-700">Tạo</button>
                </form>
                <div class="mt-2 text-xs text-slate-500">Gợi ý: <code>super-admin</code>, <code>admin</code>, <code>staff</code></div>
            </div>

            
            <ul class="max-h-[520px] overflow-y-auto divide-y divide-slate-100">
                <?php $__empty_1 = true; $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                $isActive = $currentRole && $currentRole->id === $r->id;
                $isProtected = in_array($r->name, ['super-admin']);
                $usersCount = method_exists($r, 'users') ? $r->users()->count() : null;
                ?>
                <li class="hover:bg-slate-50">
                    <div class="flex items-center justify-between">
                        <a href="<?php echo e(route('admin.roles.index', ['role' => $r->id])); ?>"
                            class="block flex-1 px-4 py-3 <?php echo e($isActive ? 'bg-rose-50/50 font-medium' : ''); ?>">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-rose-100 text-rose-700 text-xs uppercase">
                                    <?php echo e(strtoupper(substr($r->name,0,2))); ?>

                                </span>
                                <div>
                                    <div class="text-sm"><?php echo e($r->name); ?></div>
                                    <div class="text-xs text-slate-500">
                                        <?php echo e($r->permissions->count()); ?> quyền
                                        <?php if(!is_null($usersCount)): ?> • <?php echo e($usersCount); ?> người dùng
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>

                        
                        <div class="px-3">
                            <form action="<?php echo e(route('admin.roles.destroy', $r)); ?>" method="POST"
                                onsubmit="return confirm('Xoá vai trò <?php echo e($r->name); ?>?')">
                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                <button class="p-2 text-slate-500 hover:text-rose-600"
                                    <?php echo e($isProtected ? 'disabled' : ''); ?>

                                    title="<?php echo e($isProtected ? 'Vai trò hệ thống, không thể xoá' : 'Xoá vai trò'); ?>">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <li class="px-4 py-6 text-sm text-slate-500">Chưa có vai trò nào.</li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="mt-3 text-xs text-slate-500">
            * <b>Gợi ý chuẩn sàn:</b> Chỉ “super-admin” mới được toàn quyền (đã cấu hình bypass qua <code>Gate::before</code>).
        </div>
    </aside>

    
    <section class="col-span-12 lg:col-span-8">
        <div class="bg-white border border-slate-200 rounded-xl">
            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="font-semibold">Quyền của vai trò</h2>
                    <?php if($currentRole): ?>
                    <div class="text-sm text-slate-600">
                        Đang chỉnh: <span class="font-medium text-rose-700"><?php echo e($currentRole->name); ?></span>
                        <?php if(in_array($currentRole->name, ['super-admin'])): ?>
                        <span class="ml-2 text-xs px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Super-admin: có toàn bộ quyền
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" id="btnCheckAll"
                        class="px-3 py-1.5 text-sm rounded-md border hover:bg-slate-50">
                        Chọn tất cả
                    </button>
                    <button type="button" id="btnUncheckAll"
                        class="px-3 py-1.5 text-sm rounded-md border hover:bg-slate-50">
                        Bỏ chọn
                    </button>
                </div>
            </div>

            <?php if($currentRole): ?>
            <form action="<?php echo e(route('admin.roles.update', $currentRole)); ?>" method="POST" class="p-4">
                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php $__empty_1 = true; $__currentLoopData = $perms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        <div class="px-4 py-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <div class="text-sm font-medium capitalize">
                                <?php echo e($group === '' ? 'Khác' : str_replace('-', ' ', $group)); ?>

                            </div>
                            <div class="flex items-center gap-2 text-xs">
                                <button type="button" class="text-rose-700 hover:underline group-check"
                                    data-action="check" data-target="grp-<?php echo e(\Illuminate\Support\Str::slug($group)); ?>">
                                    Chọn nhóm
                                </button>
                                <span class="text-slate-300">•</span>
                                <button type="button" class="hover:underline text-slate-600 group-check"
                                    data-action="uncheck" data-target="grp-<?php echo e(\Illuminate\Support\Str::slug($group)); ?>">
                                    Bỏ nhóm
                                </button>
                            </div>
                        </div>
                        <div class="p-3 grid grid-cols-1 gap-2" id="grp-<?php echo e(\Illuminate\Support\Str::slug($group)); ?>">
                            <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $checked = $currentRole->hasPermissionTo($perm->name); ?>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="permissions[]" value="<?php echo e($perm->name); ?>"
                                    class="perm-checkbox rounded border-slate-300"
                                    <?php echo e($checked ? 'checked' : ''); ?>>
                                <span class="select-none"><?php echo e($perm->name); ?></span>
                            </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="text-sm text-slate-500 px-2">Chưa có permission nào.</div>
                    <?php endif; ?>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <button class="px-4 py-2 rounded-md bg-rose-600 text-white hover:bg-rose-700">
                        Lưu thay đổi
                    </button>
                    <a href="<?php echo e(route('admin.roles.index', ['role' => $currentRole->id])); ?>"
                        class="px-4 py-2 rounded-md border hover:bg-slate-50">Hoàn tác</a>
                </div>
            </form>
            <?php else: ?>
            <div class="p-6 text-sm text-slate-600">
                Vui lòng tạo ít nhất một vai trò để cấu hình quyền.
            </div>
            <?php endif; ?>
        </div>

        
        <div class="mt-4 text-xs text-slate-500 leading-relaxed">
            <p class="mb-1">• Mô hình <b>RBAC</b>: người dùng ⇢ gán <b>vai trò</b>; vai trò ⇢ sở hữu <b>quyền</b>.</p>
            <p class="mb-1">• Nên giới hạn số vai trò (3–5 loại) để dễ vận hành: <code>super-admin</code>, <code>admin</code>, <code>staff</code>.</p>
            <p>• Bảo vệ: Không xoá/giáng cấp <b>Super Admin cuối cùng</b>; Staff thường không được “Phân quyền”.</p>
        </div>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    // Toggle form tạo vai trò
    (function() {
        const btnOpen = document.getElementById('btnOpenCreateRole');
        const box = document.getElementById('createRoleForm');
        if (btnOpen && box) {
            btnOpen.addEventListener('click', () => box.classList.toggle('hidden'));
            // nút "Huỷ" là button nằm trong form tạo
            const cancelBtn = box.querySelector('button[type="button"], button:not([type])');
            if (cancelBtn) cancelBtn.addEventListener('click', (e) => {
                e.preventDefault();
                box.classList.add('hidden');
            });
        }
    })();

    // Chọn/Bỏ chọn tất cả permission
    const checkAllBtn = document.getElementById('btnCheckAll');
    const uncheckAllBtn = document.getElementById('btnUncheckAll');
    const allCheckboxes = () => document.querySelectorAll('input.perm-checkbox');

    if (checkAllBtn) {
        checkAllBtn.addEventListener('click', () => {
            allCheckboxes().forEach(cb => cb.checked = true);
        });
    }
    if (uncheckAllBtn) {
        uncheckAllBtn.addEventListener('click', () => {
            allCheckboxes().forEach(cb => cb.checked = false);
        });
    }

    // Chọn/Bỏ theo từng nhóm
    document.querySelectorAll('.group-check').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.target);
            const action = btn.dataset.action;
            if (!target) return;
            target.querySelectorAll('input.perm-checkbox').forEach(cb => {
                cb.checked = action === 'check';
            });
        });
    });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/roles/index.blade.php ENDPATH**/ ?>