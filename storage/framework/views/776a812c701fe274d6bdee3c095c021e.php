<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
'onlyWhenJustLoggedIn' => true,
'posters' => [],
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
'onlyWhenJustLoggedIn' => true,
'posters' => [],
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
if (empty($posters)) {
$posters = [
[
'img' => asset('images/promo/poster1.png'),
'title' => 'BIG SALE • Skincare',
'desc' => 'Ưu đãi sâu cho bộ sản phẩm chăm da bán chạy nhất.',
'cta' => 'Mua ngay',
'href' => route('shop.sale'),
],
[
'img' => asset('images/promo/poster2.png'),
'title' => 'MUA 2 TẶNG 1 • Makeup',
'desc' => 'Săn deal son/phấn/cọ – số lượng có hạn.',
'cta' => 'Khám phá',
'href' => route('shop.sale'),
],
[
'img' => asset('images/promo/poster3.png'),
'title' => 'Quay là trúng!',
'desc' => 'Thử vận may – nhận mã giảm giá tức thì.',
'cta' => 'Chơi ngay',
'href' => route('spin.index'),
],
];
}
?>

<div id="promoModal"
    class="fixed inset-0 z-[9999] hidden"
    aria-hidden="true" role="dialog" aria-modal="true">

    
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

    
    <div class="relative mx-auto mt-[6vh] w-[min(1000px,calc(100vw-2rem))] max-h-[88vh]">
        <div class="relative rounded-3xl bg-white/80 shadow-[0_40px_120px_rgba(17,24,39,.25)] ring-1 ring-white/70
                    overflow-hidden backdrop-blur-xl">

            
            <button id="promoClose"
                class="absolute right-3 top-3 z-20 grid h-10 w-10 place-items-center rounded-full
                           bg-white/95 hover:bg-white shadow ring-1 ring-black/5"
                aria-label="Đóng">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-800" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>

            
            <div class="relative">
                <div id="promoTrack"
                    class="flex w-full transition-transform duration-500 ease-out"
                    style="transform: translateX(0%);">
                    <?php $__currentLoopData = $posters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="relative shrink-0 w-full">
                        
                        <div class="relative aspect-[16/8] sm:aspect-[16/7] bg-slate-100">
                            <img src="<?php echo e($p['img']); ?>" alt="Poster"
                                class="absolute inset-0 h-full w-full object-cover" />
                            
                            <div class="absolute inset-0 bg-gradient-to-tr from-black/60 via-black/20 to-black/0"></div>

                            
                            <div class="absolute inset-x-0 bottom-0 p-6 sm:p-10">
                                <div class="max-w-[720px]">
                                    <div class="inline-flex items-center gap-2 rounded-full bg-white/90 px-3 py-1.5
                                                    text-[13px] font-semibold text-rose-700 shadow-sm ring-1 ring-black/5">
                                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-rose-600"></span>
                                        Cosme House ưu đãi
                                    </div>

                                    <h3 class="mt-3 text-3xl sm:text-5xl font-extrabold tracking-tight text-white drop-shadow">
                                        <?php echo e($p['title']); ?>

                                    </h3>
                                    <p class="mt-2 text-white/90 text-sm sm:text-base">
                                        <?php echo e($p['desc']); ?>

                                    </p>

                                    <div class="mt-4 flex items-center gap-3">
                                        <a href="<?php echo e($p['href']); ?>"
                                            class="inline-flex items-center gap-2 rounded-2xl px-5 py-2.5
                                                      font-semibold text-white
                                                      bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700
                                                      shadow ring-1 ring-white/10">
                                            <?php echo e($p['cta']); ?>

                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                            </svg>
                                        </a>

                                        
                                        <button class="promoCloseEach inline-flex items-center gap-2 rounded-2xl px-4 py-2
                                                           bg-white/90 hover:bg-white text-slate-800 font-medium shadow
                                                           ring-1 ring-black/5"
                                            aria-label="Đóng poster này">
                                            Bỏ qua
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                
                <button id="promoPrev"
                    class="absolute left-3 top-1/2 -translate-y-1/2 grid h-11 w-11 place-items-center rounded-full
                               bg-white/90 hover:bg-white shadow ring-1 ring-black/5"
                    aria-label="Trước">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.707 15.707a1 1 0 01-1.414 0l-5-5a1 1 0 010-1.414l5-5a1 1 0 111.414 1.414L8.414 10l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                <button id="promoNext"
                    class="absolute right-3 top-1/2 -translate-y-1/2 grid h-11 w-11 place-items-center rounded-full
                               bg-white/90 hover:bg-white shadow ring-1 ring-black/5"
                    aria-label="Sau">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 4.293a1 1 0 011.414 0L14 9.586a1 1 0 010 1.414L8.707 16.293a1 1 0 01-1.414-1.414L11.586 10 7.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>

                
                <div id="promoDots" class="absolute bottom-3 left-0 right-0 flex justify-center gap-2">
                    <?php for($i = 0; $i < count($posters); $i++): ?>
                        <button class="h-2.5 w-2.5 rounded-full bg-white/50 ring-1 ring-black/10"
                        data-idx="<?php echo e($i); ?>" aria-label="Slide <?php echo e($i+1); ?>"></button>
                        <?php endfor; ?>
                </div>

                
                <div class="absolute left-0 right-0 bottom-0 h-[3px] bg-white/20">
                    <div id="promoProgress" class="h-full w-0 bg-gradient-to-r from-rose-500 to-rose-600"></div>
                </div>
            </div>

            
            <div class="flex items-center justify-between p-3 text-sm text-slate-600 bg-white/80">
                <label class="inline-flex items-center gap-2">
                    <input id="promoDontShow" type="checkbox" class="h-4 w-4 rounded border-slate-300">
                    Không hiện lại hôm nay
                </label>
                <a href="<?php echo e(route('shop.sale')); ?>" class="underline decoration-rose-500/60 underline-offset-4 hover:no-underline">
                    Xem tất cả ưu đãi
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        const onlyWhenLogin = <?php echo json_encode($onlyWhenJustLoggedIn, 15, 512) ?>;
        const params = new URLSearchParams(location.search);
        const force = params.has('promo') || params.get('show_promo') === '1';
        const todayKey = 'promoModal:shown:' + (new Date()).toISOString().slice(0, 10);

        function shouldShow() {
            if (force) return true;
            const justLoggedIn = !!window.__JUST_LOGGED_IN__;
            if (onlyWhenLogin) return justLoggedIn;
            if (localStorage.getItem(todayKey)) return false;
            return true;
        }

        const root = document.getElementById('promoModal');
        const track = document.getElementById('promoTrack');
        const prev = document.getElementById('promoPrev');
        const next = document.getElementById('promoNext');
        const close = document.getElementById('promoClose');
        const dots = document.getElementById('promoDots');
        const bar = document.getElementById('promoProgress');
        const dont = document.getElementById('promoDontShow');

        const slides = track ? track.children.length : 0;
        let idx = 0;
        let timer = 0;
        const interval = 5000; // 5s auto slide

        function setProgress(pct) {
            if (!bar) return;
            bar.style.width = (pct * 100) + '%';
        }

        function update() {
            if (!track) return;
            track.style.transform = 'translateX(' + (-idx * 100) + '%)';
            if (dots) {
                const children = dots.children;
                for (let i = 0; i < children.length; i++) {
                    const d = children[i];
                    d.style.opacity = (i === idx ? '1' : '.6');
                    d.style.background = (i === idx ? '#ffffff' : 'rgba(255,255,255,.5)');
                }
            }
            setProgress(0);
        }

        function go(k) {
            idx = (k + slides) % slides;
            update();
            restart();
        }

        function prevSlide() {
            go(idx - 1);
        }

        function nextSlide() {
            go(idx + 1);
        }

        function openM() {
            if (!root) return;
            root.classList.remove('hidden');
            document.documentElement.classList.add('overflow-hidden');
        }

        function closeM() {
            if (!root) return;
            root.classList.add('hidden');
            document.documentElement.classList.remove('overflow-hidden');
            if (dont && dont.checked) localStorage.setItem(todayKey, '1');
            stop();
        }

        function tick(startTs) {
            const elapsed = performance.now() - startTs;
            const pct = Math.min(1, elapsed / interval);
            setProgress(pct);
            if (pct >= 1) {
                nextSlide();
                return;
            }
            timer = requestAnimationFrame(() => tick(startTs));
        }

        function restart() {
            stop();
            timer = requestAnimationFrame(() => tick(performance.now()));
        }

        function stop() {
            if (timer) cancelAnimationFrame(timer);
            timer = 0;
            setProgress(0);
        }

        if (root && shouldShow()) {
            openM();
            update();
            restart();
        }

        // events
        if (prev) prev.addEventListener('click', prevSlide);
        if (next) next.addEventListener('click', nextSlide);
        if (close) close.addEventListener('click', closeM);
        if (root) root.addEventListener('click', e => {
            if (e.target === root) closeM();
        });
        if (dots)[...dots.children].forEach(btn => btn.addEventListener('click', () => go(+btn.dataset.idx)));
        // pause on hover
        if (track) {
            track.addEventListener('mouseenter', stop);
            track.addEventListener('mouseleave', restart);
        }
        // esc
        window.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeM();
        });
    })();
</script><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/components/promo-modal.blade.php ENDPATH**/ ?>