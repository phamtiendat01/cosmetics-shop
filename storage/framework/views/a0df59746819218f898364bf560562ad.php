<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['icon' => 'fa-regular fa-box-open', 'text' => 'Chưa có dữ liệu.']));

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

foreach (array_filter((['icon' => 'fa-regular fa-box-open', 'text' => 'Chưa có dữ liệu.']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="col-span-full text-center py-10">
    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-rose-50 text-brand-600 mb-2">
        <i class="<?php echo e($icon); ?>"></i>
    </div>
    <div class="text-sm text-ink/60"><?php echo e($text); ?></div>
</div><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/components/empty.blade.php ENDPATH**/ ?>