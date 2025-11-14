
<?php $__env->startSection('title', ($product->name ?? 'Sản phẩm').' | Cosme House'); ?>

<?php $__env->startSection('content'); ?>
<?php
// Ảnh chính + gallery
$main = $product->image
? asset('storage/'.$product->image)
: ($product->thumbnail ? asset('storage/'.$product->thumbnail) : null);

$images = collect($product->gallery ?? [])
->map(fn($p) => str_starts_with($p,'http') ? $p : asset('storage/'.$p));
if ($main && $images->isEmpty()) $images = collect([$main]);

// Giá min từ variants (hoặc fallback)
$min = $product->variants->min('price');
$minCmp = $product->variants->min('compare_at_price');
$minForPrompt = $min ?? ($product->price ?? 0);

// Tóm tắt ngắn: ưu tiên short_desc; nếu trống -> rút gọn từ long_desc
$descBrief = trim($product->short_desc ?? '') !== ''
? trim(preg_replace('/\s+/', ' ', $product->short_desc))
: \Illuminate\Support\Str::limit(strip_tags($product->long_desc ?? ''), 160);

// HTML mô tả chi tiết cho tab: CHỈ dùng long_desc
$longHtml = $product->long_desc ?: null;

// Prompt cho chatbot
$consultPrompt = "Tư vấn cho sản phẩm: {$product->name}.\n"
. "• Thương hiệu: ".(optional($product->brand)->name ?? 'N/A')."\n"
. "• Danh mục: ".(optional($product->category)->name ?? 'N/A')."\n"
. "• Giá từ: ".number_format($minForPrompt)."đ\n"
. "• Mô tả ngắn: ".$descBrief."\n"
. "Hãy gợi ý công dụng, ai nên dùng/không nên dùng, cách sử dụng, và combo đi kèm.";
?>

<section class="max-w-7xl mx-auto px-4 mt-6">
    
    <div class="text-sm text-ink/60 mb-3">
        <a href="<?php echo e(route('home')); ?>" class="hover:text-brand-600">Trang chủ</a> /
        <?php if($product->category): ?>
        <a href="<?php echo e(route('category.show',$product->category->slug)); ?>" class="hover:text-brand-600"><?php echo e($product->category->name); ?></a> /
        <?php endif; ?>
        <span class="text-ink"><?php echo e($product->name); ?></span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        
        <div class="md:col-span-5" id="jsPdpGallery">
            <?php if (isset($component)) { $__componentOriginal3e02afc58783df8e9f8209bbd7eced29 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3e02afc58783df8e9f8209bbd7eced29 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.product-gallery','data' => ['images' => $images,'main' => $main]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('product-gallery'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['images' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($images),'main' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($main)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3e02afc58783df8e9f8209bbd7eced29)): ?>
<?php $attributes = $__attributesOriginal3e02afc58783df8e9f8209bbd7eced29; ?>
<?php unset($__attributesOriginal3e02afc58783df8e9f8209bbd7eced29); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3e02afc58783df8e9f8209bbd7eced29)): ?>
<?php $component = $__componentOriginal3e02afc58783df8e9f8209bbd7eced29; ?>
<?php unset($__componentOriginal3e02afc58783df8e9f8209bbd7eced29); ?>
<?php endif; ?>
        </div>

        
        <div class="md:col-span-7">
            <div class="md:sticky md:top-[92px] space-y-4">
                <h1 class="text-2xl font-bold"><?php echo e($product->name); ?></h1>

                
                <div class="text-sm text-ink/60 flex items-center gap-2">
                    <?php if($product->brand): ?>
                    <a class="hover:text-brand-600" href="<?php echo e(route('brand.show',$product->brand->slug)); ?>"><?php echo e($product->brand->name); ?></a><span>•</span>
                    <?php endif; ?>
                    <?php if($product->category): ?>
                    <a class="hover:text-brand-600" href="<?php echo e(route('category.show',$product->category->slug)); ?>"><?php echo e($product->category->name); ?></a>
                    <?php endif; ?>
                </div>

                
                <?php if (isset($component)) { $__componentOriginal1694116fa6ac1a37a76aa3ef3a849480 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1694116fa6ac1a37a76aa3ef3a849480 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.price-block','data' => ['min' => $min,'compare' => $minCmp]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('price-block'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['min' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($min),'compare' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($minCmp)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1694116fa6ac1a37a76aa3ef3a849480)): ?>
<?php $attributes = $__attributesOriginal1694116fa6ac1a37a76aa3ef3a849480; ?>
<?php unset($__attributesOriginal1694116fa6ac1a37a76aa3ef3a849480); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1694116fa6ac1a37a76aa3ef3a849480)): ?>
<?php $component = $__componentOriginal1694116fa6ac1a37a76aa3ef3a849480; ?>
<?php unset($__componentOriginal1694116fa6ac1a37a76aa3ef3a849480); ?>
<?php endif; ?>

                
                <?php if($product->variants->count()): ?>
                <div class="mt-2">
                    <div class="text-sm font-medium mb-2">Phiên bản</div>
                    <div class="flex flex-wrap gap-2">
                        <?php $__currentLoopData = $product->variants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="variant_id" class="peer sr-only"
                                value="<?php echo e($v->id); ?>" <?php echo e($loop->first ? 'checked' : ''); ?>>
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full border border-rose-200 bg-white text-sm
                                                 peer-checked:bg-brand-600 peer-checked:text-white">
                                <?php echo e($v->name); ?> — <?php echo e(number_format($v->price)); ?>₫
                            </span>
                        </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <?php endif; ?>

                
                <div class="flex items-center gap-3">
                    
                    <div class="flex items-center rounded-xl border border-rose-200 overflow-hidden">
                        <button type="button" id="qtyDec"
                            class="w-10 h-10 grid place-items-center text-xl text-ink/70 hover:bg-rose-50">−</button>
                        <input id="qtyInput" name="qty" value="1" inputmode="numeric"
                            class="h-10 w-14 text-center outline-none border-x border-rose-100"
                            oninput="this.value=this.value.replace(/[^0-9]/g,'')" />
                        <button type="button" id="qtyInc"
                            class="w-10 h-10 grid place-items-center text-xl text-ink/70 hover:bg-rose-50">+</button>
                    </div>

                    <button id="btnAddToCart"
                        data-product-id="<?php echo e((int) $product->id); ?>"
                        class="px-5 py-3 bg-brand-600 text-white rounded-xl hover:bg-brand-700">
                        Thêm vào giỏ
                    </button>

                    <button id="btnConsult"
                        class="px-5 py-3 border border-rose-200 rounded-xl hover:bg-rose-50">
                        Tư vấn
                    </button>
                </div>

                <?php if (isset($component)) { $__componentOriginal0394c09a561618b128e4d6b695379c4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0394c09a561618b128e4d6b695379c4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge-list','data' => ['class' => 'mt-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-1']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0394c09a561618b128e4d6b695379c4a)): ?>
<?php $attributes = $__attributesOriginal0394c09a561618b128e4d6b695379c4a; ?>
<?php unset($__attributesOriginal0394c09a561618b128e4d6b695379c4a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0394c09a561618b128e4d6b695379c4a)): ?>
<?php $component = $__componentOriginal0394c09a561618b128e4d6b695379c4a; ?>
<?php unset($__componentOriginal0394c09a561618b128e4d6b695379c4a); ?>
<?php endif; ?>

                
                <?php if($descBrief): ?>
                <div class="text-sm text-ink/80 mt-2"><?php echo e($descBrief); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
    <div id="desc" class="mt-10 bg-white border border-rose-100 rounded-2xl p-4">
        <div x-data="{tab:'desc'}">
            <div class="flex gap-4 border-b border-rose-100">
                <button class="pb-3 font-medium"
                    :class="tab==='desc' ? 'text-brand-600 border-b-2 border-brand-600' : 'text-ink/60'"
                    @click="tab='desc'">Mô tả</button>

                <button class="pb-3 font-medium"
                    :class="tab==='reviews' ? 'text-brand-600 border-b-2 border-brand-600' : 'text-ink/60'"
                    @click="tab='reviews'">Đánh giá</button>
            </div>

            <div class="pt-4" x-show="tab==='desc'">
                <?php if($longHtml): ?>
                
                <div class="prose max-w-none"><?php echo $longHtml; ?></div>
                <?php else: ?>
                <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['text' => 'Chưa có mô tả chi tiết.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['text' => 'Chưa có mô tả chi tiết.']); ?>
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

            <div class="pt-4" x-show="tab==='reviews'">
                <?php echo $__env->make('product.reviews', ['product' => $product], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    </div>

    
    <?php if(isset($related) && $related->count()): ?>
    <div class="mt-10">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold">Khách cũng mua</h2>
            <a href="<?php echo e(route('shop.index')); ?>" class="text-sm text-brand-600 hover:underline">Xem thêm</a>
        </div>
        <?php if (isset($component)) { $__componentOriginal1bce25776c2c78b9ebf743508ea436bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1bce25776c2c78b9ebf743508ea436bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.related-carousel','data' => ['products' => $related]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('related-carousel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['products' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($related)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1bce25776c2c78b9ebf743508ea436bc)): ?>
<?php $attributes = $__attributesOriginal1bce25776c2c78b9ebf743508ea436bc; ?>
<?php unset($__attributesOriginal1bce25776c2c78b9ebf743508ea436bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1bce25776c2c78b9ebf743508ea436bc)): ?>
<?php $component = $__componentOriginal1bce25776c2c78b9ebf743508ea436bc; ?>
<?php unset($__componentOriginal1bce25776c2c78b9ebf743508ea436bc); ?>
<?php endif; ?>
    </div>
    <?php endif; ?>
</section>


<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const CART_ENDPOINT = "<?php echo e(route('cart.store')); ?>";
    const CONSULT_PROMPT = <?php echo json_encode($consultPrompt, 15, 512) ?>;

    // Qty stepper
    (function() {
        const q = document.getElementById('qtyInput');
        document.getElementById('qtyDec').addEventListener('click', () => {
            const v = parseInt(q.value || '1', 10) || 1;
            q.value = Math.max(1, v - 1);
        });
        document.getElementById('qtyInc').addEventListener('click', () => {
            const v = parseInt(q.value || '1', 10) || 1;
            q.value = v + 1;
        });
    })();

    // Tư vấn → mở bot (fallback nếu widget chưa init)
    document.getElementById('btnConsult').addEventListener('click', () => {
        if (window.Bot?.open) window.Bot.open(CONSULT_PROMPT);
        else window.dispatchEvent(new CustomEvent('bot:open', {
            detail: {
                prompt: CONSULT_PROMPT
            }
        }));
    });

    // Hiệu ứng bay vào giỏ
    (() => {
        if (document.getElementById('flyCartCSS')) return;
        const s = document.createElement('style');
        s.id = 'flyCartCSS';
        s.textContent = `
          .fly-cart{position:fixed;left:0;top:0;pointer-events:none;z-index:99999;will-change:transform,opacity;
                    filter:drop-shadow(0 8px 16px rgba(0,0,0,.15))}
          .fly-cart .dot{width:56px;height:56px;border-radius:9999px;background:#fff;display:grid;place-items:center;
                         border:1px solid rgba(16,24,39,.12)}
          .fly-cart i{font-size:26px;color:#e11d48}
        `;
        document.head.appendChild(s);
    })();

    function flyToCart(originEl = document.getElementById('btnAddToCart')) {
        const target = document.getElementById('jsCartIcon');
        if (!target) return;

        const ob = (originEl || document.querySelector('#jsPdpGallery img'))?.getBoundingClientRect();
        const tb = target.getBoundingClientRect();
        if (!ob || !tb) return;

        const DOT = 56,
            HALF = DOT / 2;
        const n = document.createElement('div');
        n.className = 'fly-cart';
        n.innerHTML = `<div class="dot"><i class="fa-solid fa-bag-shopping"></i></div>`;
        document.body.appendChild(n);

        const start = {
            x: ob.left + ob.width / 2 - HALF,
            y: ob.top + ob.height / 2 - HALF
        };
        const mid = {
            x: (ob.left + tb.left) / 2 - HALF,
            y: (ob.top + tb.top) / 2 - 200
        };
        const end = {
            x: tb.left + tb.width / 2 - (DOT * 0.35),
            y: tb.top + tb.height / 2 - (DOT * 0.35)
        };

        n.animate([{
                transform: `translate(${start.x}px,${start.y}px) scale(1)`,
                opacity: 1
            },
            {
                transform: `translate(${mid.x}px,${mid.y}px) scale(.95)`,
                opacity: .9
            },
            {
                transform: `translate(${end.x}px,${end.y}px) scale(.30)`,
                opacity: .2
            }
        ], {
            duration: 1600,
            easing: 'cubic-bezier(.22,.61,.36,1)'
        }).onfinish = () => n.remove();

        target.animate([{
            transform: 'scale(1)'
        }, {
            transform: 'scale(1.15)'
        }, {
            transform: 'scale(1)'
        }], {
            duration: 360,
            easing: 'ease-out'
        });
    }

    // Badge giỏ ở header
    function setCartCount(n) {
        n = Number(n) || 0;
        const el = document.getElementById('jsCartCount');
        if (!el) return;
        el.textContent = n;
        el.classList.toggle('hidden', n <= 0);
    }

    // Thêm vào giỏ (fetch + fly + đồng bộ)
    document.getElementById('btnAddToCart').addEventListener('click', async () => {
        const pid = Number(document.getElementById('btnAddToCart').dataset.productId || 0);
        const vid = (document.querySelector('input[name=variant_id]:checked') || {}).value || null;
        const qty = parseInt((document.getElementById('qtyInput') || {}).value || 1, 10);
        if (!pid || qty < 1) return;

        flyToCart();

        try {
            const res = await fetch(CART_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    product_id: pid,
                    variant_id: vid,
                    qty
                })
            });
            const data = await res.json().catch(() => null);

            if (data?.ok) {
                if (typeof data.count === 'number') {
                    setCartCount(data.count);
                } else {
                    const cur = Number((document.getElementById('jsCartCount')?.textContent) || 0);
                    setCartCount(cur + qty);
                }
                localStorage.setItem('cart-sync', JSON.stringify({
                    ts: Date.now(),
                    count: Number(document.getElementById('jsCartCount')?.textContent || 0)
                }));
            }
        } catch (e) {
            /* ignore */
        }
    });

    // Đồng bộ giữa các tab
    window.addEventListener('storage', (ev) => {
        if (ev.key === 'cart-sync' && ev.newValue) {
            try {
                setCartCount(JSON.parse(ev.newValue).count ?? 0);
            } catch {}
        }
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/product/show.blade.php ENDPATH**/ ?>