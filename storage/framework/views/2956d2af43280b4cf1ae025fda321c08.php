<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['name' => 'qty', 'min' => 1, 'max' => 99, 'value' => 1, 'class' => '']));

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

foreach (array_filter((['name' => 'qty', 'min' => 1, 'max' => 99, 'value' => 1, 'class' => '']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<div x-data="{v: <?php echo e((int)$value); ?>, min: <?php echo e((int)$min); ?>, max: <?php echo e((int)$max); ?> }"
    class="inline-flex items-stretch border border-rose-200 rounded-lg overflow-hidden <?php echo e($class); ?>">
    <button type="button" class="px-3 hover:bg-rose-50" @click="v=Math.max(min, v-1)"><i class="fa-solid fa-minus"></i></button>
    <input type="number" :value="v" @input="v = Math.max(min, Math.min(max, +$event.target.value||min))"
        class="w-14 text-center outline-none" name="<?php echo e($name); ?>" min="<?php echo e($min); ?>" max="<?php echo e($max); ?>">
    <button type="button" class="px-3 hover:bg-rose-50" @click="v=Math.min(max, v+1)"><i class="fa-solid fa-plus"></i></button>
</div><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/components/qty-stepper.blade.php ENDPATH**/ ?>