
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
'label' => '',
'name' => '',
'type' => 'text',
'value' => '',
'placeholder' => '',
'required' => false,
'min' => null,
'max' => null,
'step' => null,
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
'label' => '',
'name' => '',
'type' => 'text',
'value' => '',
'placeholder' => '',
'required' => false,
'min' => null,
'max' => null,
'step' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
// a[b][c] -> a.b.c để @error hoạt động
$dotName = str_replace(['[',']'], ['.',''], $name);
?>

<div>
    <label class="block text-sm font-medium mb-1">
        <?php echo e($label); ?> <?php if($required): ?><span class="text-red-600">*</span><?php endif; ?>
    </label>

    <input
        type="<?php echo e($type); ?>"
        name="<?php echo e($name); ?>"
        value="<?php echo e(old($dotName, $value)); ?>"
        placeholder="<?php echo e($placeholder); ?>"
        <?php if($required): ?> required <?php endif; ?>
        <?php if(!is_null($min)): ?> min="<?php echo e($min); ?>" <?php endif; ?>
        <?php if(!is_null($max)): ?> max="<?php echo e($max); ?>" <?php endif; ?>
        <?php if(!is_null($step)): ?> step="<?php echo e($step); ?>" <?php endif; ?>
        <?php echo e($attributes->merge(['class' => 'w-full rounded border-slate-300'])); ?>>

    <?php $__errorArgs = [$dotName];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
    <p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p>
    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/components/setting/input.blade.php ENDPATH**/ ?>