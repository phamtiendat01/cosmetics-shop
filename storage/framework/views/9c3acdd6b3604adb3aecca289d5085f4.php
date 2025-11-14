
<?php $__env->startSection('title','Cosme House'); ?>

<?php $__env->startSection('content'); ?>
<?php
// Ưu tiên slides từ $banners; nếu không có dùng $heroSlides (fallback)
$slides = collect($banners ?? [])->map(function ($b) {
return [
'image' => $b->image ?? null,
'mobile_image' => $b->mobile_image ?? null,
'url' => $b->url ?: '#',
'title' => $b->title ?? '',
];
})->values()->all();

if (empty($slides)) {
$slides = $heroSlides ?? [
['image'=>null,'url'=>'#','title'=>'Banner 1'],
['image'=>null,'url'=>'#','title'=>'Banner 2'],
['image'=>null,'url'=>'#','title'=>'Banner 3'],
];
}
?>

<section class="max-w-7xl mx-auto px-4 mt-6 space-y-10">

    
    <div x-data="heroCarousel(<?php echo e(json_encode($slides)); ?>)"
        x-init="init()"
        @mouseenter="pause()" @mouseleave="play()"
        @keydown.left.prevent="prev()" @keydown.right.prevent="next()"
        tabindex="0"
        class="relative group rounded-2xl overflow-hidden border border-rose-100 focus:outline-none">

        <div class="relative w-full h-0 pb-[36%] sm:pb-[28%]">
            <template x-for="(s,idx) in items" :key="idx">
                <a :href="s.url || '#'" class="absolute inset-0" x-show="i===idx" x-transition.opacity>
                    <picture>
                        <source media="(max-width: 640px)" :srcset="toUrl(s.mobile_image || s.image)">
                        <img :src="toUrl(s.image) || 'https://placehold.co/1600x576?text=Hero+Slide'"
                            :alt="s.title || ''"
                            class="w-full h-full object-cover">
                    </picture>
                </a>
            </template>
        </div>

        
        <div class="absolute bottom-3 left-0 right-0 flex items-center justify-center gap-2 z-20">
            <template x-for="k in items.length" :key="k">
                <button @click="go(k-1)" class="w-2.5 h-2.5 rounded-full transition"
                    :class="i===k-1 ? 'bg-white' : 'bg-white/50'"></button>
            </template>
        </div>

        
        <button @click="prev"
            class="absolute left-3 top-1/2 -translate-y-1/2 grid place-items-center w-10 h-10 rounded-full
                   bg-white/90 shadow hover:bg-white transition z-20
                   opacity-0 group-hover:opacity-100 sm:opacity-0 sm:group-hover:opacity-100">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button @click="next"
            class="absolute right-3 top-1/2 -translate-y-1/2 grid place-items-center w-10 h-10 rounded-full
                   bg-white/90 shadow hover:bg-white transition z-20
                   opacity-0 group-hover:opacity-100 sm:opacity-0 sm:group-hover:opacity-100">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>

    
    <div class="bg-white border border-rose-100 rounded-2xl p-3 overflow-hidden">
        <div class="flex items-center gap-4 overflow-x-auto snap-x snap-mandatory no-scrollbar"
            id="brandWave" data-wave=".js-brand">
            <?php $__empty_1 = true; $__currentLoopData = ($topBrands ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
            $logo = $b->logo
            ? (\Illuminate\Support\Str::startsWith($b->logo,'http') ? $b->logo : asset('storage/'.$b->logo))
            : null;
            ?>
            <a href="<?php echo e(route('brand.show',$b->slug)); ?>"
                class="js-brand min-w-[140px] snap-start shrink-0 flex items-center gap-3 px-3 py-2
                          border border-rose-100 rounded-xl bg-white hover:shadow-card transition-transform duration-200">
                <?php if($logo): ?>
                <img src="<?php echo e($logo); ?>" alt="<?php echo e($b->name); ?>" class="w-10 h-10 object-contain">
                <?php else: ?>
                <div class="w-10 h-10 rounded bg-rose-50 grid place-items-center">
                    <?php echo e(strtoupper(substr($b->name,0,1))); ?>

                </div>
                <?php endif; ?>
                <span class="text-sm font-medium"><?php echo e($b->name); ?></span>
            </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <?php for($i=0;$i<8;$i++): ?>
                <div class="min-w-[140px] snap-start shrink-0 h-[56px] rounded-xl bg-rose-50/60 border border-rose-100">
        </div>
        <?php endfor; ?>
        <?php endif; ?>
    </div>
    </div>

    
    <?php if(($flashSale ?? collect())->count()): ?>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold flex items-center gap-2">
                <span class="text-amber-500"><i class="fa-solid fa-bolt"></i></span> Flash Sale
            </h2>
            <div x-data="countdown(<?php echo e(now()->copy()->addHours(6)->timestamp); ?>)"
                x-init="start()"
                class="px-3 py-1 rounded-lg bg-rose-50 text-brand-600 text-sm">
                Kết thúc sau: <span x-text="hhmmss"></span>
            </div>
        </div>

        <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory no-scrollbar pb-2"
            id="flashWave" data-wave=".js-fs">
            <?php $__currentLoopData = $flashSale; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="js-fs min-w-[180px] max-w-[180px] snap-start">
                <?php if (isset($component)) { $__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.product-card','data' => ['product' => $p]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('product-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['product' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($p)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a)): ?>
<?php $attributes = $__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a; ?>
<?php unset($__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a)): ?>
<?php $component = $__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a; ?>
<?php unset($__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a); ?>
<?php endif; ?>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
    <?php endif; ?>

    
    <?php if(($suggested ?? collect())->count()): ?>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">Gợi ý hôm nay</h2>
            <a href="<?php echo e(route('shop.index')); ?>" class="text-sm text-brand-600 hover:underline">Xem tất cả</a>
        </div>
        <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory no-scrollbar pb-2"
            id="suggestWave" data-wave=".js-sg">
            <?php $__currentLoopData = $suggested; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="js-sg min-w-[180px] max-w-[180px] snap-start">
                <?php if (isset($component)) { $__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.product-card','data' => ['product' => $p]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('product-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['product' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($p)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a)): ?>
<?php $attributes = $__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a; ?>
<?php unset($__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a)): ?>
<?php $component = $__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a; ?>
<?php unset($__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a); ?>
<?php endif; ?>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
    <?php endif; ?>

    
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold">Sản phẩm mới</h2>
            <a href="<?php echo e(route('shop.index')); ?>" class="text-sm text-brand-600 hover:underline">Xem tất cả</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <?php $__empty_1 = true; $__currentLoopData = ($newProducts ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php if (isset($component)) { $__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.product-card','data' => ['product' => $p]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('product-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['product' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($p)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a)): ?>
<?php $attributes = $__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a; ?>
<?php unset($__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a)): ?>
<?php $component = $__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a; ?>
<?php unset($__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a); ?>
<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['text' => 'Chưa có sản phẩm.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['text' => 'Chưa có sản phẩm.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4f22a152e0729cd34293e65bd200d933)): ?>
<?php $attributes = $__attributesOriginal4f22a152e0729cd34293e65bd200d933; ?>
<?php unset($__attributesOriginal4f22a152e0729cd34293e65bd200d933); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4f22a152e0729cd34293e65bd200d933)): ?>
<?php $component = $__componentOriginal4f22a152e0729cd34293e65bd200d933; ?>
<?php unset($__componentOriginal4f22a152e0729cd34293e65bd200d933); ?>
<?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php ($__justLoggedIn = \Illuminate\Support\Facades\Session::has('just_logged_in')); ?>
    <script>
        window.__JUST_LOGGED_IN__ = <?php echo json_encode($__justLoggedIn, 15, 512) ?>;
    </script>

    <?php echo $__env->make('components.promo-modal', [
    'onlyWhenJustLoggedIn' => true,
    'posters' => [
    ['img'=>asset('images/promo/poster1.png'),'title'=>'SALE 50% – Skincare Hot','desc'=>'Giảm sâu cho bộ sưu tập chăm da bán chạy nhất.','cta'=>'Mua ngay','href'=>route('shop.sale')],
    ['img'=>asset('images/promo/poster2.png'),'title'=>'MUA 2 TẶNG 1 – Makeup','desc'=>'Săn deal son/phấn/cọ, số lượng có hạn.','cta'=>'Khám phá','href'=>route('shop.sale')],
    ['img'=>asset('images/promo/poster3.png'),'title'=>'Quay là trúng!','desc'=>'Thử vận may – nhận mã giảm giá tức thì.','cta'=>'Chơi ngay','href'=>route('spin.index')],
    ],
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

</section>


<?php $__env->startPush('scripts'); ?>
<script>
    /* ===== Hero carousel ===== */
    document.addEventListener('alpine:init', () => {
        Alpine.data('heroCarousel', (items) => ({
            items: Array.isArray(items) ? items : [],
            i: 0,
            timer: null,
            interval: 4500,

            toUrl(p) {
                if (!p) return '';
                p = String(p).trim().replace(/\\/g, '/');
                // đã là http(s) hoặc /storage
                if (/^https?:\/\//i.test(p)) return p;
                if (p.startsWith('/storage/')) return p;

                // chuẩn hoá đường dẫn lưu trong DB
                if (p.startsWith('public/')) p = p.replace(/^public\//, 'storage/');
                if (p.startsWith('storage/')) return '<?php echo e(url(' / ')); ?>/' + p;

                // còn lại: coi như trong storage/
                return '<?php echo e(url(' / ')); ?>/' + ('storage/' + p.replace(/^\/+/, ''));
            },

            init() {
                if (this.items.length > 1) this.play();
            },
            play() {
                this.pause();
                this.timer = setInterval(() => this.next(), this.interval);
            },
            pause() {
                if (this.timer) clearInterval(this.timer);
            },
            next() {
                this.i = (this.i + 1) % this.items.length;
            },
            prev() {
                this.i = (this.i - 1 + this.items.length) % this.items.length;
            },
            go(k) {
                this.i = k;
            }
        }));
    });

    /* ===== Countdown cho Flash Sale ===== */
    function countdown(targetTs) {
        return {
            hhmmss: '00:00:00',
            target: targetTs * 1000,
            timer: null,
            start() {
                const tick = () => {
                    const remain = this.target - Date.now();
                    if (remain <= 0) {
                        this.hhmmss = '00:00:00';
                        clearInterval(this.timer);
                        return;
                    }
                    const h = String(Math.floor(remain / 3600000)).padStart(2, '0');
                    const m = String(Math.floor(remain % 3600000 / 60000)).padStart(2, '0');
                    const s = String(Math.floor(remain % 60000 / 1000)).padStart(2, '0');
                    this.hhmmss = `${h}:${m}:${s}`;
                };
                tick();
                this.timer = setInterval(tick, 1000);
            }
        }
    }

    /* ===== Hiệu ứng "lướt sóng" – chỉ item hover + 2 hàng xóm ===== */
    function wave(group) {
        const selector = group.dataset.wave || '.card';
        const items = [...group.querySelectorAll(selector)];
        const shift = (el, dy) => el.style.transform = `translateY(${dy}px)`;
        const reset = (el) => el.style.transform = '';

        items.forEach((el, idx) => {
            el.addEventListener('mouseenter', () => {
                items.forEach(reset);
                shift(el, -8);
                if (items[idx - 1]) shift(items[idx - 1], -4);
                if (items[idx + 1]) shift(items[idx + 1], -4);
            });
            el.addEventListener('mouseleave', () => items.forEach(reset));
        });
    }

    /* Kích hoạt wave cho 3 cụm: Brand, Flash Sale, Gợi ý hôm nay */
    ['brandWave', 'flashWave', 'suggestWave'].forEach(id => {
        const el = document.getElementById(id);
        if (el) wave(el);
    });
</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/home/index.blade.php ENDPATH**/ ?>