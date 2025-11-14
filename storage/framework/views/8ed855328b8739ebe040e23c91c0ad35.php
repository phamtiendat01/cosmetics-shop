<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
'images' => [],
'main' => null,
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
'images' => [],
'main' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
$imgs = collect($images)->filter()->values();
if ($imgs->isEmpty() && $main) $imgs = collect([$main]);
if ($imgs->isEmpty()) $imgs = collect(['https://placehold.co/800x800?text=IMG']);
?>

<div x-data="pdpGallery(<?php echo \Illuminate\Support\Js::from($imgs)->toHtml() ?>)" class="space-y-3" id="jsPdpGallery">
    
    <div class="relative rounded-2xl overflow-hidden border border-rose-100 bg-white md:max-w-[560px] mx-auto">
        <img
            :src="imgs[i]"
            class="w-full aspect-square object-contain select-none"
            @mousemove="zoom && move($event)"
            @mouseenter="zoom = true"
            @mouseleave="zoom = false"
            draggable="false" />
        
        <div
            x-show="zoom"
            class="hidden md:block absolute right-3 top-3 w-56 h-56 rounded-xl border border-rose-100 bg-white shadow-card overflow-hidden"
            :style="`background-image:url(${imgs[i]}); background-repeat:no-repeat; background-size:${zsize}% ${zsize}%; background-position:${zx}% ${zy}%;`"></div>
    </div>

    
    <div class="flex gap-2 overflow-x-auto no-scrollbar">
        <?php $__currentLoopData = $imgs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <button type="button"
            class="shrink-0 w-16 h-16 rounded-lg overflow-hidden border"
            :class="i === <?php echo e($k); ?> ? 'border-brand-600' : 'border-rose-100'"
            @click="set(<?php echo e($k); ?>)">
            <img src="<?php echo e($u); ?>" class="w-full h-full object-cover" loading="lazy">
        </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>

<script>
    // Alpine component cho gallery + kính lúp
    function pdpGallery(imgs) {
        return {
            imgs,
            i: 0,
            zoom: false,
            zx: 50,
            zy: 50,
            zsize: 200, // % phóng to cho kính lúp
            set(i) {
                this.i = i;
            },
            move(e) {
                const box = e.currentTarget.getBoundingClientRect();
                this.zx = Math.min(100, Math.max(0, ((e.clientX - box.left) / box.width) * 100));
                this.zy = Math.min(100, Math.max(0, ((e.clientY - box.top) / box.height) * 100));
            }
        }
    }
</script><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/components/product-gallery.blade.php ENDPATH**/ ?>