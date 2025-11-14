<form method="get" class="flex items-center gap-2">
    <?php $__currentLoopData = request()->except('sort'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($v); ?>">
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <select name="sort" class="form-control" onchange="this.form.submit()">
        <option value="">Mới nhất</option>
        <option value="price_asc" <?php if(request('sort')==='price_asc' ): echo 'selected'; endif; ?>>Giá ↑</option>
        <option value="price_desc" <?php if(request('sort')==='price_desc' ): echo 'selected'; endif; ?>>Giá ↓</option>
    </select>
</form><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/category/partials/sort.blade.php ENDPATH**/ ?>