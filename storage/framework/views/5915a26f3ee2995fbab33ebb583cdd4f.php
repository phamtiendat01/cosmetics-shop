
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['name','checked'=>false,'label'=>null,'right'=>false]));

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

foreach (array_filter((['name','checked'=>false,'label'=>null,'right'=>false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="flex items-center <?php echo e($right ? 'justify-end' : ''); ?>">
    <input type="hidden" name="<?php echo e($name); ?>" value="0">
    <label class="relative inline-flex items-center cursor-pointer">
        <input type="checkbox" class="sr-only peer" name="<?php echo e($name); ?>" value="1" <?php if($checked): echo 'checked'; endif; ?>>
        <div class="relative w-11 h-6 bg-slate-200 rounded-full peer-focus:outline-none
                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all
                peer-checked:after:translate-x-full peer-checked:bg-rose-600"></div>
        <?php if($label): ?><span class="ml-3 text-sm text-slate-700"><?php echo e($label); ?></span><?php endif; ?>
    </label>
</div><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/components/switch.blade.php ENDPATH**/ ?>