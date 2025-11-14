<?php if($paginator->hasPages()): ?>
<nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-center mt-6">
    <ul class="inline-flex items-center gap-1 text-sm">
        
        <?php if($paginator->onFirstPage()): ?>
        <li class="px-3 py-1.5 rounded-md text-ink/40 border border-rose-100 cursor-not-allowed">
            <i class="fa-solid fa-angle-left"></i>
        </li>
        <?php else: ?>
        <li>
            <a href="<?php echo e($paginator->previousPageUrl()); ?>" rel="prev"
                class="px-3 py-1.5 rounded-md border border-rose-200 hover:bg-rose-50">
                <i class="fa-solid fa-angle-left"></i>
            </a>
        </li>
        <?php endif; ?>

        
        <?php $__currentLoopData = $elements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $element): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if(is_string($element)): ?>
        <li class="px-3 py-1.5 text-ink/40"><?php echo e($element); ?></li>
        <?php endif; ?>

        <?php if(is_array($element)): ?>
        <?php $__currentLoopData = $element; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if($page == $paginator->currentPage()): ?>
        <li class="px-3 py-1.5 rounded-md bg-brand-600 text-white font-semibold"><?php echo e($page); ?></li>
        <?php else: ?>
        <li>
            <a href="<?php echo e($url); ?>"
                class="px-3 py-1.5 rounded-md border border-rose-200 hover:bg-rose-50"><?php echo e($page); ?></a>
        </li>
        <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        
        <?php if($paginator->hasMorePages()): ?>
        <li>
            <a href="<?php echo e($paginator->nextPageUrl()); ?>" rel="next"
                class="px-3 py-1.5 rounded-md border border-rose-200 hover:bg-rose-50">
                <i class="fa-solid fa-angle-right"></i>
            </a>
        </li>
        <?php else: ?>
        <li class="px-3 py-1.5 rounded-md text-ink/40 border border-rose-100 cursor-not-allowed">
            <i class="fa-solid fa-angle-right"></i>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/shared/pagination.blade.php ENDPATH**/ ?>