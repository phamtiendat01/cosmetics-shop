<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
'title' => null,
'subtitle' => null,
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
'title' => null,
'subtitle' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div <?php echo e($attributes->class('bg-white rounded-xl border border-rose-100 shadow-sm overflow-hidden')); ?>>
    <?php if(isset($header) || $title || $subtitle): ?>
    <div class="px-4 py-3 border-b border-rose-100 flex items-center justify-between">
        <div>
            <?php if($title): ?>
            <h3 class="font-semibold text-ink"><?php echo e($title); ?></h3>
            <?php endif; ?>
            <?php if($subtitle): ?>
            <p class="text-sm text-ink/60"><?php echo e($subtitle); ?></p>
            <?php endif; ?>
        </div>
        
        <?php echo e($header ?? ''); ?>

    </div>
    <?php endif; ?>

    <div class="p-4">
        <?php echo e($slot); ?>

    </div>

    <?php if(isset($footer)): ?>
    <div class="px-4 py-3 border-t border-rose-100 bg-rose-50/30">
        <?php echo e($footer); ?>

    </div>
    <?php endif; ?>
</div><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/components/card.blade.php ENDPATH**/ ?>