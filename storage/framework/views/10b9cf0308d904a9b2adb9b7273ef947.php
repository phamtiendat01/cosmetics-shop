<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
'min' => null, // giá min
'compare' => null, // giá gốc (min) nếu có
'class' => '',
]));

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

foreach (array_filter(([
'min' => null, // giá min
'compare' => null, // giá gốc (min) nếu có
'class' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
$sale = ($min && $compare && $compare > $min) ? round(100*(1-$min/$compare)) : null;
?>

<div class="<?php echo e($class); ?>">
    <?php if($sale): ?>
    <div class="flex items-baseline gap-2">
        <span class="text-2xl text-brand-600 font-semibold"><?php echo e(number_format($min)); ?>₫</span>
        <span class="text-sm line-through text-ink/50"><?php echo e(number_format($compare)); ?>₫</span>
        <span class="text-xs px-2 py-1 rounded-full bg-rose-600 text-white">-<?php echo e($sale); ?>%</span>
    </div>
    <?php elseif($min): ?>
    <div class="text-2xl text-brand-600 font-semibold"><?php echo e(number_format($min)); ?>₫</div>
    <?php else: ?>
    <div class="text-ink/50">Đang cập nhật</div>
    <?php endif; ?>
</div><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/components/price-block.blade.php ENDPATH**/ ?>