
<?php $__env->startSection('title','Khách hàng'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?>
<div class="alert alert-success mb-3" data-auto-dismiss="3000"><?php echo e(session('ok')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
<div class="alert alert-danger mb-3"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<div class="toolbar">
    <div class="toolbar-title">Quản lý khách hàng</div>
    <a href="<?php echo e(route('admin.customers.create')); ?>" class="btn btn-primary btn-sm">+ Thêm</a>
</div>

<div class="card p-3 mb-3">
    <form id="filterForm" method="get" class="grid md:grid-cols-6 gap-2 items-center">
        <input id="keywordInput" name="keyword" value="<?php echo e($filters['keyword'] ?? ''); ?>" class="form-control" placeholder="Tìm theo tên / email / ĐT (live)…">

        <select name="status" id="statusSelect" class="form-control">
            <option value="">Tất cả trạng thái</option>
            <option value="active" <?php if(($filters['status'] ?? '' )==='active' ): echo 'selected'; endif; ?>>Đang hoạt động</option>
            <option value="inactive" <?php if(($filters['status'] ?? '' )==='inactive' ): echo 'selected'; endif; ?>>Đã khoá</option>
        </select>

        <select name="verified" id="verifiedSelect" class="form-control">
            <option value="">Xác thực email</option>
            <option value="yes" <?php if(($filters['verified'] ?? '' )==='yes' ): echo 'selected'; endif; ?>>Đã xác thực</option>
            <option value="no" <?php if(($filters['verified'] ?? '' )==='no' ): echo 'selected'; endif; ?>>Chưa xác thực</option>
        </select>

        <input name="date_from" id="dateFrom" class="form-control" value="<?php echo e($filters['date_from'] ?? ''); ?>" placeholder="Từ ngày (đăng ký)">
        <input name="date_to" id="dateTo" class="form-control" value="<?php echo e($filters['date_to']   ?? ''); ?>" placeholder="Đến ngày">

        <select name="sort" class="form-control">
            <option value="">Sắp xếp</option>
            <option value="created_at" <?php if(($filters['sort'] ?? '' )==='created_at' ): echo 'selected'; endif; ?>>Mới đăng ký</option>
            <option value="orders" <?php if(($filters['sort'] ?? '' )==='orders' ): echo 'selected'; endif; ?>>Nhiều đơn</option>
            <option value="total_spent" <?php if(($filters['sort'] ?? '' )==='total_spent' ): echo 'selected'; endif; ?>>Chi tiêu cao</option>
        </select>

        <div class="flex items-center gap-2 md:col-span-6">
            <button class="btn btn-soft btn-sm">Lọc</button>
            <button type="button" id="resetBtn" class="btn btn-outline btn-sm">Reset</button>
        </div>
    </form>
</div>

<form method="post" action="<?php echo e(route('admin.customers.bulk')); ?>">
    <?php echo csrf_field(); ?>
    <div class="card table-wrap p-0">
        <table class="table-admin">
            <thead>
                <tr>
                    <th style="width:34px"><input type="checkbox" id="chkAll"></th>
                    <th>STT</th>
                    <th>Khách hàng</th>
                    <th>Email</th>
                    <th>Điện thoại</th>
                    <th>Đơn</th>
                    <th>Chi tiêu</th>
                    <th>Trạng thái</th>
                    <th>ĐK lúc</th>
                    <th class="col-actions">Thao tác</th>
                </tr>
            </thead>
            <tbody id="customerRows">
                <?php $__empty_1 = true; $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="cust-row" data-name="<?php echo e(Str::lower(($u->name??'').' '.($u->email??'').' '.($u->phone??''))); ?>">
                    <td><input type="checkbox" name="ids[]" value="<?php echo e($u->id); ?>"></td>
                    <td><?php echo e(($customers->currentPage()-1)*$customers->perPage() + $i + 1); ?></td>
                    <td class="font-medium">
                        <div class="cell-thumb">
                            <img
                                class="thumb"
                                src="<?php echo e($u->avatar_url); ?>"
                                alt="<?php echo e($u->name); ?>"
                                onerror="this.onerror=null;this.src='https://i.pravatar.cc/120?u=<?php echo e(urlencode($u->email ?? $u->id)); ?>'">
                            <div>
                                <div><?php echo e($u->name); ?></div>
                                <?php if($u->email_verified_at): ?>
                                <span class="badge badge-green"><span class="badge-dot"></span>Đã xác thực</span>
                                <?php else: ?>
                                <span class="badge"><span class="badge-dot"></span>Chưa xác thực</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="text-slate-700"><?php echo e($u->email); ?></td>
                    <td><?php echo e($u->phone ?? '—'); ?></td>
                    <td><?php echo e($u->orders_count); ?></td>
                    <td style="text-align:right"><?php echo e(number_format((float)$u->total_spent,0,',','.')); ?>₫</td>
                    <td>
                        <?php if($u->is_active): ?>
                        <span class="badge badge-green"><span class="badge-dot"></span>Hoạt động</span>
                        <?php else: ?>
                        <span class="badge badge-red"><span class="badge-dot"></span>Đã khoá</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo e($u->created_at?->format('d/m/Y')); ?></td>
                    <td class="col-actions">
                        <a class="btn btn-table btn-outline" href="<?php echo e(route('admin.customers.show',$u)); ?>">Xem</a>
                        <a class="btn btn-table btn-outline" href="<?php echo e(route('admin.customers.edit',$u)); ?>">Sửa</a>
                        <form method="post" action="<?php echo e(route('admin.customers.toggle',$u)); ?>" class="inline">
                            <?php echo csrf_field(); ?>
                            <button class="btn btn-table btn-outline" type="submit"><?php echo e($u->is_active?'Khoá':'Mở'); ?></button>
                        </form>
                        <button type="button" class="btn btn-table btn-danger" data-confirm-delete data-url="<?php echo e(route('admin.customers.destroy',$u)); ?>">Xoá</button>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="10" class="py-6 text-center text-slate-500">Chưa có khách hàng.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-2 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <select name="act" class="form-control" style="max-width:180px">
                <option value="activate">Mở khoá</option>
                <option value="deactivate">Khoá</option>
                <option value="delete">Xoá đã chọn</option>
            </select>
            <button class="btn btn-secondary btn-sm">Áp dụng</button>
        </div>
        <div class="pagination"><?php echo e($customers->links()); ?></div>
    </div>
</form>


<div id="confirmModal" class="modal hidden">
    <div class="modal-card">
        <div class="p-4 border-b">
            <div class="font-semibold">Xác nhận xoá</div>
            <div class="text-sm text-slate-500">Xoá khách hàng này? Thao tác không thể hoàn tác.</div>
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
    flatpickr("#dateFrom", {
        dateFormat: "Y-m-d"
    });
    flatpickr("#dateTo", {
        dateFormat: "Y-m-d"
    });

    const kw = document.getElementById('keywordInput');
    const rows = Array.from(document.querySelectorAll('.cust-row'));
    let timer;

    function doFilter() {
        const q = (kw.value || '').trim().toLowerCase();
        rows.forEach(tr => {
            tr.style.display = (!q || tr.dataset.name.includes(q)) ? '' : 'none';
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

    document.getElementById('resetBtn').addEventListener('click', () => {
        document.getElementById('filterForm').reset();
        kw.value = '';
        doFilter();
        document.getElementById('filterForm').submit();
    });

    const chkAll = document.getElementById('chkAll');
    chkAll.addEventListener('change', () => {
        document.querySelectorAll('input[name="ids[]"]').forEach(c => c.checked = chkAll.checked);
    });

    const modal = document.getElementById('confirmModal');
    const form = document.getElementById('confirmForm');
    document.querySelectorAll('[data-confirm-delete]').forEach(btn => {
        btn.addEventListener('click', () => {
            form.action = btn.dataset.url;
            modal.classList.remove('hidden');
        });
    });
    document.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', () => modal.classList.add('hidden')));
    modal.addEventListener('click', e => {
        if (e.target === modal) modal.classList.add('hidden');
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/customers/index.blade.php ENDPATH**/ ?>