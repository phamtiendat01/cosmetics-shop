<?php
/**
* Biến $headerCats phải là danh sách danh mục CHA có children,
* ví dụ mỗi item: { id, name, slug, children: [ {id,name,slug}, ... ] }
*/
$parents = $headerCats ?? collect();
?>

<div x-data="catFlyout()" class="relative">
    <!-- Nút mở flyout -->
    <button type="button"
        @mouseenter="open()"
        @click="toggle()"
        class="flex items-center gap-2 py-3 px-3 rounded-lg hover:bg-rose-50">
        <i class="fa-solid fa-bars-staggered"></i>
        <span class="font-medium">Danh mục</span>
        <i class="fa-solid fa-chevron-down text-xs text-ink/60"></i>
    </button>

    <!-- Flyout -->
    <div x-show="isOpen"
        x-transition.opacity
        @mouseleave="close()"
        @keydown.escape.window="close()"
        class="absolute left-0 top-full mt-2 w-[720px] bg-white border border-rose-100 rounded-2xl shadow-card overflow-hidden z-[300]"
        x-cloak>
        <div class="grid grid-cols-12">
            <!-- Cột trái: danh mục cha -->
            <div class="col-span-5 max-h-[360px] overflow-auto bg-rose-50/40">
                <?php $__currentLoopData = $parents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <button type="button"
                    @mouseenter="active=<?php echo e($idx); ?>"
                    :class="active===<?php echo e($idx); ?> ? 'bg-white text-brand-700' : 'hover:bg-white/70'"
                    class="w-full text-left px-4 py-3 border-b border-rose-100 flex items-center justify-between">
                    <span class="truncate"><?php echo e($p->name); ?></span>
                    <i class="fa-solid fa-chevron-right text-xs opacity-60"></i>
                </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <!-- Cột phải: con của danh mục đang active -->
            <div class="col-span-7 p-4">
                <?php $__currentLoopData = $parents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div x-show="active===<?php echo e($idx); ?>" x-transition.opacity>
                    <?php if(($p->children ?? collect())->count()): ?>
                    <div class="grid grid-cols-2 gap-2">
                        <?php $__currentLoopData = $p->children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('category.show', $c->slug)); ?>"
                            class="px-3 py-2 rounded-lg border border-rose-100 hover:border-brand-500 hover:bg-rose-50/60">
                            <?php echo e($c->name); ?>

                        </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <?php else: ?>
                    <div class="text-sm text-ink/60">Chưa có danh mục con.</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    function catFlyout() {
        return {
            isOpen: false,
            active: 0,
            open() {
                this.isOpen = true
            },
            close() {
                this.isOpen = false
            },
            toggle() {
                this.isOpen = !this.isOpen
            }
        }
    }
</script>
<?php $__env->stopPush(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/components/header/category-flyout.blade.php ENDPATH**/ ?>