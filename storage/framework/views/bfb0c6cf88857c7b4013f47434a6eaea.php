<?php
$selectedBrands = (array) request()->input('brand_ids', []);
$minReq = (int) request('min', 0);
$maxReq = (int) request('max', 0);
$ratingReq = (int) request('rating', 0);
$inStock = request()->boolean('in_stock');
?>

<div class="space-y-3">
    
    <?php echo $__env->renderWhen(true, 'category.partials.selected-chips', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1])); ?>

    <form method="get" class="space-y-3">
        <?php if (isset($component)) { $__componentOriginalf37c7fa867bbb37ca7b59380c8fa1d1e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf37c7fa867bbb37ca7b59380c8fa1d1e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.accordion','data' => ['title' => 'Khoảng giá (₫)','open' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('accordion'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Khoảng giá (₫)','open' => true]); ?>
            <div class="flex items-center gap-2">
                <input type="number" name="min" value="<?php echo e($minReq); ?>" class="w-full px-3 py-2 border border-rose-200 rounded-md" placeholder="Từ">
                <span class="text-ink/40">—</span>
                <input type="number" name="max" value="<?php echo e($maxReq); ?>" class="w-full px-3 py-2 border border-rose-200 rounded-md" placeholder="Đến">
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf37c7fa867bbb37ca7b59380c8fa1d1e)): ?>
<?php $attributes = $__attributesOriginalf37c7fa867bbb37ca7b59380c8fa1d1e; ?>
<?php unset($__attributesOriginalf37c7fa867bbb37ca7b59380c8fa1d1e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf37c7fa867bbb37ca7b59380c8fa1d1e)): ?>
<?php $component = $__componentOriginalf37c7fa867bbb37ca7b59380c8fa1d1e; ?>
<?php unset($__componentOriginalf37c7fa867bbb37ca7b59380c8fa1d1e); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginalf37c7fa867bbb37ca7b59380c8fa1d1e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf37c7fa867bbb37ca7b59380c8fa1d1e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.accordion','data' => ['title' => 'Thương hiệu']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('accordion'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Thương hiệu']); ?>
            <div class="max-h-64 overflow-auto pr-1 space-y-1">
                <?php $__currentLoopData = \App\Models\Brand::orderBy('name')->get(['id','name']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="brand_ids[]" value="<?php echo e($b->id); ?>" <?php echo e(in_array($b->id, $selectedBrands) ? 'checked' : ''); ?>>
                    <span><?php echo e($b->name); ?></span>
                </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf37c7fa867bbb37ca7b59380c8fa1d1e)): ?>
<?php $attributes = $__attributesOriginalf37c7fa867bbb37ca7b59380c8fa1d1e; ?>
<?php unset($__attributesOriginalf37c7fa867bbb37ca7b59380c8fa1d1e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf37c7fa867bbb37ca7b59380c8fa1d1e)): ?>
<?php $component = $__componentOriginalf37c7fa867bbb37ca7b59380c8fa1d1e; ?>
<?php unset($__componentOriginalf37c7fa867bbb37ca7b59380c8fa1d1e); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginalf37c7fa867bbb37ca7b59380c8fa1d1e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf37c7fa867bbb37ca7b59380c8fa1d1e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.accordion','data' => ['title' => 'Đánh giá']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('accordion'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Đánh giá']); ?>
            <select name="rating" class="w-full px-3 py-2 border border-rose-200 rounded-md">
                <option value="">Bất kỳ</option>
                <option value="4" <?php if($ratingReq===4): echo 'selected'; endif; ?>>Từ 4★</option>
                <option value="3" <?php if($ratingReq===3): echo 'selected'; endif; ?>>Từ 3★</option>
                <option value="2" <?php if($ratingReq===2): echo 'selected'; endif; ?>>Từ 2★</option>
            </select>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf37c7fa867bbb37ca7b59380c8fa1d1e)): ?>
<?php $attributes = $__attributesOriginalf37c7fa867bbb37ca7b59380c8fa1d1e; ?>
<?php unset($__attributesOriginalf37c7fa867bbb37ca7b59380c8fa1d1e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf37c7fa867bbb37ca7b59380c8fa1d1e)): ?>
<?php $component = $__componentOriginalf37c7fa867bbb37ca7b59380c8fa1d1e; ?>
<?php unset($__componentOriginalf37c7fa867bbb37ca7b59380c8fa1d1e); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginalf37c7fa867bbb37ca7b59380c8fa1d1e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf37c7fa867bbb37ca7b59380c8fa1d1e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.accordion','data' => ['title' => 'Tình trạng']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('accordion'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Tình trạng']); ?>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="in_stock" value="1" <?php if($inStock): echo 'checked'; endif; ?>>
                <span>Chỉ hiển thị hàng còn</span>
            </label>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf37c7fa867bbb37ca7b59380c8fa1d1e)): ?>
<?php $attributes = $__attributesOriginalf37c7fa867bbb37ca7b59380c8fa1d1e; ?>
<?php unset($__attributesOriginalf37c7fa867bbb37ca7b59380c8fa1d1e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf37c7fa867bbb37ca7b59380c8fa1d1e)): ?>
<?php $component = $__componentOriginalf37c7fa867bbb37ca7b59380c8fa1d1e; ?>
<?php unset($__componentOriginalf37c7fa867bbb37ca7b59380c8fa1d1e); ?>
<?php endif; ?>

        
        <?php $__currentLoopData = request()->except(['min','max','brand_ids','rating','in_stock','page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($v); ?>">
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <button class="w-full px-3 py-2 bg-brand-600 text-white rounded-md">Áp dụng</button>
    </form>
</div><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/category/partials/filters-sidebar.blade.php ENDPATH**/ ?>