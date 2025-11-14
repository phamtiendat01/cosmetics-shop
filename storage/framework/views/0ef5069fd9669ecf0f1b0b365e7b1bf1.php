
<?php $__env->startSection('title','Sản phẩm'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?>
<div class="alert alert-success mb-3" data-auto-dismiss="3000"><?php echo e(session('ok')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
<div class="alert alert-danger mb-3"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<div class="toolbar">
    <div class="toolbar-title">Quản lý sản phẩm</div>
    <div class="toolbar-actions">
        <a href="<?php echo e(route('admin.products.create')); ?>" class="btn btn-primary btn-sm">+ Thêm</a>
    </div>
</div>

<?php $st = $filters['status'] ?? ''; ?>
<?php
$tab = function($key,$text,$cnt) use($st){
$active = $st===$key ? 'btn-primary' : 'btn-outline';
$url = $key ? request()->fullUrlWithQuery(['status'=>$key,'page'=>1]) : route('admin.products.index');
return "<a class=\"btn btn-sm $active\" href=\"$url\">$text ($cnt)</a>";
};
?>
<div class="mb-3 flex flex-wrap gap-2 text-sm">
    <?php echo $tab('', 'Tất cả', $counts['all'] ?? 0); ?>

    <?php echo $tab('active', 'Đang hiển thị', $counts['active'] ?? 0); ?>

    <?php echo $tab('inactive', 'Đang ẩn', $counts['inactive'] ?? 0); ?>

    <?php echo $tab('low', 'Sắp hết hàng', $counts['low'] ?? 0); ?>

    <?php echo $tab('out', 'Hết hàng', $counts['out'] ?? 0); ?>

    <?php echo $tab('novariant', 'Không biến thể',$counts['novariant'] ?? 0); ?>

</div>

<div class="card p-3 mb-3">
    <form id="filterForm" method="get" class="grid md:grid-cols-5 gap-2 items-center">
        <input name="keyword" value="<?php echo e($filters['keyword'] ?? ''); ?>" class="form-control search" placeholder="Tìm nhanh theo sản phẩm…">

        <select name="category_id" id="filterCat" class="form-control">
            <option value="">Tất cả danh mục</option>
            <?php $__currentLoopData = $categoryGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $parentName => $children): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <optgroup label="<?php echo e($parentName); ?>">
                <?php $__currentLoopData = $children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($c['id']); ?>" <?php if(($filters['category_id'] ?? '' )==$c['id']): echo 'selected'; endif; ?>><?php echo e($c['name']); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </optgroup>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>

        <select name="brand_id" id="filterBrand" class="form-control">
            <option value="">Tất cả thương hiệu</option>
            <?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($b->id); ?>" <?php if(($filters['brand_id'] ?? '' )==$b->id): echo 'selected'; endif; ?>><?php echo e($b->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>

        <select name="sort" id="sortSelect" class="form-control">
            <option value="newest" <?php if(($filters['sort']??'newest')==='newest' ): echo 'selected'; endif; ?>>Mới nhất</option>
            <option value="price_asc" <?php if(($filters['sort']??'')==='price_asc' ): echo 'selected'; endif; ?>>Giá thấp → cao</option>
            <option value="price_desc" <?php if(($filters['sort']??'')==='price_desc' ): echo 'selected'; endif; ?>>Giá cao → thấp</option>
            <option value="stock_desc" <?php if(($filters['sort']??'')==='stock_desc' ): echo 'selected'; endif; ?>>Tồn kho nhiều → ít</option>
        </select>

        <div class="flex items-center gap-2">
            <button class="btn btn-soft btn-sm">Lọc</button>
            <a href="<?php echo e(route('admin.products.index')); ?>" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>

<?php
$from = $products->total() ? (($products->currentPage()-1) * $products->perPage() + 1) : 0;
$to = $products->total() ? ($from + $products->count() - 1) : 0;
?>
<?php if($products->total() > 0): ?>
<div class="mb-2 text-sm text-slate-600">Hiển thị <?php echo e($from); ?>–<?php echo e($to); ?> / <?php echo e($products->total()); ?> sản phẩm</div>
<?php endif; ?>

<div class="card table-wrap p-0">
    <table class="table-admin w-full" id="productTable">
        <colgroup>
            <col style="width:80px">
            <col style="width:80px">
            <col style="width:400px">
            <col style="width:180px">
            <col style="width:140px">
            <col style="width:88px">
            <col style="width:300px">
            <col style="width:140px">
            <col>
        </colgroup>

        <thead>
            <tr>
                <th class="text-right pr-3">ID</th>
                <th>Ảnh</th>
                <th>Sản phẩm</th>
                <th>Danh mục</th>
                <th>Thương hiệu</th>
                <th class="text-right pr-3">Kho</th>
                <th>Giá</th>
                <th>Ngày tạo</th>
                <th class="col-actions text-center"> </th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
            // fallback cho dữ liệu cũ còn trường image
            $thumbPath = $p->thumbnail ?: $p->image;
            $thumbUrl = $thumbPath ? asset('storage/'.$thumbPath) : 'https://placehold.co/64x64?text=IMG';
            $thumbModal= $thumbPath ? asset('storage/'.$thumbPath) : 'https://placehold.co/80x80?text=IMG';
            ?>
            <tr>
                <td class="text-right pr-3"><?php echo e($p->id); ?></td>

                <td>
                    <img src="<?php echo e($thumbUrl); ?>" alt="thumb" class="w-12 h-12 rounded object-cover">
                </td>

                <td>
                    <a class="link font-semibold" href="<?php echo e(route('admin.products.edit',$p)); ?>"><?php echo e($p->name); ?></a>
                    <?php if(($p->is_active ?? 1) == 0): ?>
                    <span class="ml-1 text-xs text-slate-500">(đang ẩn)</span>
                    <?php endif; ?>
                    <div class="text-[10px] text-slate-400 truncate">slug: <?php echo e($p->slug); ?></div>
                </td>

                <td class="truncate"><?php echo e($p->category?->name ?? '-'); ?></td>
                <td class="truncate"><?php echo e($p->brand?->name ?? '-'); ?></td>

                <td class="text-right pr-3"> <?php echo e(number_format($p->stock ?? 0)); ?></td>

                <td>
                    <?php if($p->min_price && $p->max_price && $p->min_price != $p->max_price): ?>
                    <?php echo e(number_format($p->min_price)); ?>₫ – <?php echo e(number_format($p->max_price)); ?>₫
                    <?php elseif($p->min_price): ?>
                    <?php echo e(number_format($p->min_price)); ?>₫
                    <?php else: ?>
                    -
                    <?php endif; ?>
                </td>

                <td><?php echo e($p->created_at?->format('d/m/Y H:i')); ?></td>

                <td class="text-center">
                    <button type="button"
                        class="btn btn-danger btn-sm !px-2 !py-1 js-open-delete"
                        title="Xoá"
                        data-url="<?php echo e(route('admin.products.destroy',$p)); ?>"
                        data-name="<?php echo e($p->name); ?>"
                        data-thumb="<?php echo e($thumbModal); ?>">
                        <i class="fa-solid fa-trash text-[12px]"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="9" class="py-6 text-center text-slate-500">Chưa có sản phẩm.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="pagination mt-3">
    <?php echo e($products->onEachSide(1)->links('pagination::tailwind')); ?>

</div>


<div id="deleteModal" class="modal hidden" aria-hidden="true">
    <div class="modal-card p-4">
        <div class="flex items-start gap-3">
            <img id="delThumb" src="https://placehold.co/80x80?text=IMG" class="w-16 h-16 rounded object-cover" alt="">
            <div class="min-w-0">
                <div class="text-base font-semibold">Xoá sản phẩm?</div>
                <div class="text-sm text-slate-600 mt-1">
                    Bạn sắp xoá <b id="delName">Sản phẩm</b>. Thao tác này không thể hoàn tác.
                </div>
            </div>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <button type="button" class="btn btn-outline btn-sm" id="cancelDelBtn">Huỷ</button>
            <button type="button" class="btn btn-danger btn-sm" id="confirmDelBtn">Xoá</button>
        </div>
    </div>
</div>
<form id="deleteForm" method="post" class="hidden">
    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
</form>

<?php $__env->startPush('scripts'); ?>
<script>
    if (document.getElementById('filterCat')) new TomSelect('#filterCat', {
        create: false,
        maxOptions: 800
    });
    if (document.getElementById('filterBrand')) new TomSelect('#filterBrand', {
        create: false,
        maxOptions: 800
    });
    if (document.getElementById('sortSelect')) new TomSelect('#sortSelect', {
        create: false
    });

    // Modal xoá
    const modal = document.getElementById('deleteModal');
    const delName = document.getElementById('delName');
    const delThumb = document.getElementById('delThumb');
    const delForm = document.getElementById('deleteForm');

    function openModal(url, name, thumb) {
        delName.textContent = name;
        delThumb.src = thumb || delThumb.src;
        delForm.action = url;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
    document.querySelectorAll('.js-open-delete').forEach(btn => {
        btn.addEventListener('click', () => openModal(btn.dataset.url, btn.dataset.name, btn.dataset.thumb));
    });
    document.getElementById('cancelDelBtn').addEventListener('click', closeModal);
    document.getElementById('confirmDelBtn').addEventListener('click', () => delForm.submit());
    modal.addEventListener('click', e => {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });

    // Tự ẩn alert
    document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
        setTimeout(() => {
            el.classList.add('alert--hide');
            setTimeout(() => el.remove(), 350)
        }, +el.dataset.autoDismiss || 3000)
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/products/index.blade.php ENDPATH**/ ?>