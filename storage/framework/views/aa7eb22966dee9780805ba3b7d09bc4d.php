
<?php $__env->startSection('title', $cat->name.' | Cosme House'); ?>

<?php $__env->startSection('content'); ?>
<section class="max-w-7xl mx-auto px-4 mt-6">
    
    <div class="text-sm text-ink/60 mb-3">
        <a href="<?php echo e(route('home')); ?>" class="hover:text-brand-600">Trang chủ</a> /
        <?php if($cat->parent): ?>
        <a href="<?php echo e(route('category.show',$cat->parent->slug)); ?>" class="hover:text-brand-600"><?php echo e($cat->parent->name); ?></a> /
        <?php endif; ?>
        <span class="text-ink"><?php echo e($cat->name); ?></span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <aside class="lg:col-span-3">
            <div class="sticky top-[84px]">
                <?php echo $__env->make('category.partials.filters-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </aside>

        
        <main class="lg:col-span-9">
            <div class="flex items-end justify-between gap-3">
                <h1 class="text-xl font-bold"><?php echo e($cat->name); ?></h1>
                <?php echo $__env->make('category.partials.sort', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 mt-4">
                <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php if (isset($component)) { $__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.product-card','data' => ['product' => $p]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('product-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['product' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($p)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a)): ?>
<?php $attributes = $__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a; ?>
<?php unset($__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a)): ?>
<?php $component = $__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a; ?>
<?php unset($__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a); ?>
<?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['text' => 'Chưa có sản phẩm.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['text' => 'Chưa có sản phẩm.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4f22a152e0729cd34293e65bd200d933)): ?>
<?php $attributes = $__attributesOriginal4f22a152e0729cd34293e65bd200d933; ?>
<?php unset($__attributesOriginal4f22a152e0729cd34293e65bd200d933); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4f22a152e0729cd34293e65bd200d933)): ?>
<?php $component = $__componentOriginal4f22a152e0729cd34293e65bd200d933; ?>
<?php unset($__componentOriginal4f22a152e0729cd34293e65bd200d933); ?>
<?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="mt-6">
                <?php echo e($products->onEachSide(1)->links('shared.pagination')); ?>

            </div>
        </main>
    </div>
</section>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/category/show.blade.php ENDPATH**/ ?>