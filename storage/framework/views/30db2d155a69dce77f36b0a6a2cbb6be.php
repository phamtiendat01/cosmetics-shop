<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title'=>'', 'open'=>false]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['title'=>'', 'open'=>false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<div x-data="{open: <?php echo e($open ? 'true':'false'); ?> }" class="border border-rose-100 rounded-xl overflow-hidden">
    <button type="button" class="w-full flex items-center justify-between px-3 py-2 bg-rose-50/40"
        @click="open=!open">
        <span class="text-sm font-medium"><?php echo e($title); ?></span>
        <i class="fa-solid" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
    </button>
    <div x-show="open" x-collapse>
        <div class="p-3">
            <?php echo e($slot); ?>

        </div>
    </div>
</div><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/components/accordion.blade.php ENDPATH**/ ?>