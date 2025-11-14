<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['items' => []]));

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

foreach (array_filter((['items' => []]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
$defaults = [
['icon'=>'fa-solid fa-truck-fast', 'text'=>'Freeship đơn từ 499K'],
['icon'=>'fa-solid fa-rotate-left', 'text'=>'Đổi trả 7 ngày'],
['icon'=>'fa-solid fa-shield-heart', 'text'=>'Hàng chính hãng'],
];
$items = count($items) ? $items : $defaults;
?>

<ul class="grid sm:grid-cols-3 gap-2">
    <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <li class="flex items-center gap-2 px-3 py-2 rounded-lg border border-rose-100 bg-rose-50/40">
        <i class="<?php echo e($it['icon']); ?> text-brand-600"></i>
        <span class="text-sm"><?php echo e($it['text']); ?></span>
    </li>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</ul><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/components/badge-list.blade.php ENDPATH**/ ?>