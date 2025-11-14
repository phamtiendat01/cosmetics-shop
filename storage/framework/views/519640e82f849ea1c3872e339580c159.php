
<?php
// Nhận dữ liệu từ include
$roots = $tree ?? collect();
$roots = $roots instanceof \Illuminate\Support\Collection
? $roots->values()->take(6)
: collect($roots)->values()->take(6);
?>

<ul class="flex items-center gap-6 whitespace-nowrap">
    <?php $__currentLoopData = $roots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    
    <li class="relative group pb-2">
        <a href="<?php echo e(route('category.show', $cat->slug)); ?>"
            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-rose-50">
            <?php echo e($cat->name); ?>

            <?php if(!empty($cat->children) && count($cat->children)): ?>
            <i class="fa-solid fa-chevron-down text-xs opacity-60"></i>
            <?php endif; ?>
        </a>

        
        <?php if(!empty($cat->children) && count($cat->children)): ?>
        <div
            class="absolute left-0 top-full w-[min(960px,calc(100vw-2rem))] bg-white border border-rose-100
                 rounded-2xl shadow-[0_20px_60px_rgba(0,0,0,.08)] p-5
                 opacity-0 invisible translate-y-1 transition
                 group-hover:opacity-100 group-hover:visible group-hover:translate-y-0
                 z-[80]">
            <div class="grid grid-cols-3 gap-6">
                <?php $__currentLoopData = $cat->children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div>
                    <a href="<?php echo e(route('category.show', $child->slug)); ?>"
                        class="font-medium hover:text-rose-600"><?php echo e($child->name); ?></a>

                    <?php if(!empty($child->children) && count($child->children)): ?>
                    <ul class="mt-2 space-y-1">
                        <?php $__currentLoopData = $child->children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $gchild): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li>
                            <a href="<?php echo e(route('category.show', $gchild->slug)); ?>"
                                class="text-sm text-gray-600 hover:text-gray-900"><?php echo e($gchild->name); ?></a>
                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>
    </li>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</ul><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/components/header/mega-menu.blade.php ENDPATH**/ ?>