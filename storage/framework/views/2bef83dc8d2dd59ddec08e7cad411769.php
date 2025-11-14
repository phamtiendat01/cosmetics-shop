
<nav class="mb-5">
    <div id="shipTabs"
        class="relative inline-flex gap-2 rounded-2xl border border-slate-200 bg-white p-1">
        
        <span id="shipTabIndicator"
            class="pointer-events-none absolute top-1 left-1 z-0 h-[34px] rounded-xl bg-rose-50 ring-1 ring-rose-200 shadow-sm
                 transition-all duration-300 ease-out"></span>

        <a href="<?php echo e(route('admin.shipping.carriers.index')); ?>"
            class="ship-tab relative z-[1] overflow-hidden px-3 py-2 rounded-xl flex items-center gap-2 text-sm
              transition-[color,transform] hover:scale-[1.03]
              <?php echo e(request()->routeIs('admin.shipping.carriers.*') ? 'is-active text-rose-700' : ''); ?>">
            <i class="fa-solid fa-truck"></i> <span>Đơn vị</span>
        </a>

        <a href="<?php echo e(route('admin.shipping.zones.index')); ?>"
            class="ship-tab relative z-[1] overflow-hidden px-3 py-2 rounded-xl flex items-center gap-2 text-sm
              transition-[color,transform] hover:scale-[1.03]
              <?php echo e(request()->routeIs('admin.shipping.zones.*') ? 'is-active text-rose-700' : ''); ?>">
            <i class="fa-solid fa-location-dot"></i> <span>Khu vực / Tuyến</span>
        </a>

        <a href="<?php echo e(route('admin.shipping.rates.index')); ?>"
            class="ship-tab relative z-[1] overflow-hidden px-3 py-2 rounded-xl flex items-center gap-2 text-sm
              transition-[color,transform] hover:scale-[1.03]
              <?php echo e(request()->routeIs('admin.shipping.rates.*') ? 'is-active text-rose-700' : ''); ?>">
            <i class="fa-solid fa-scale-balanced"></i> <span>Biểu phí</span>
        </a>
    </div>
</nav>

<?php if (! $__env->hasRenderedOnce('67b0fac2-875f-4aa9-9fe8-533ca9260da1')): $__env->markAsRenderedOnce('67b0fac2-875f-4aa9-9fe8-533ca9260da1'); ?>
<?php $__env->startPush('scripts'); ?>
<style>
    /* Không dùng nền xám cho tab – để indicator luôn nổi */
    .ship-tab {
        background-color: transparent !important;
    }

    .ship-tab:hover {
        background-color: transparent !important;
    }

    /* Ripple (gợn sóng) */
    .ship-tab .ripple {
        position: absolute;
        border-radius: 9999px;
        background: rgba(244, 63, 94, .15);
        transform: scale(0);
        animation: ship-ripple .6s ease-out forwards;
        pointer-events: none;
    }

    @keyframes ship-ripple {
        to {
            transform: scale(2.6);
            opacity: 0;
        }
    }
</style>

<script>
    (() => {
        const wrap = document.getElementById('shipTabs');
        if (!wrap) return;

        const indicator = document.getElementById('shipTabIndicator');
        const tabs = [...wrap.querySelectorAll('.ship-tab')];

        function moveIndicator(el, {
            animate = true
        } = {}) {
            const r = el.getBoundingClientRect();
            const rw = wrap.getBoundingClientRect();
            indicator.classList.toggle('transition-none', !animate);
            indicator.style.width = r.width + 'px';
            indicator.style.transform = `translateX(${r.left - rw.left}px)`;
        }

        const active = tabs.find(t => t.classList.contains('is-active')) || tabs[0];

        // Nhớ tab trước khi chuyển trang -> animate mượt khi load trang mới
        tabs.forEach((t, i) => t.addEventListener('click', () => {
            try {
                sessionStorage.setItem('shipTabPrevIndex', i);
            } catch (e) {}
        }));

        const prevIdx = parseInt(sessionStorage.getItem('shipTabPrevIndex') ?? '-1', 10);
        if (!isNaN(prevIdx) && prevIdx >= 0 && tabs[prevIdx]) {
            moveIndicator(tabs[prevIdx], {
                animate: false
            });
            requestAnimationFrame(() => moveIndicator(active, {
                animate: true
            }));
        } else {
            moveIndicator(active, {
                animate: true
            });
        }

        // Ripple + preview indicator khi hover
        tabs.forEach(t => {
            t.addEventListener('mouseenter', (e) => {
                const d = Math.max(t.clientWidth, t.clientHeight);
                const circle = document.createElement('span');
                circle.className = 'ripple';
                circle.style.width = circle.style.height = d + 'px';
                const rect = t.getBoundingClientRect();
                circle.style.left = (e.clientX - rect.left - d / 2) + 'px';
                circle.style.top = (e.clientY - rect.top - d / 2) + 'px';
                t.appendChild(circle);
                setTimeout(() => circle.remove(), 600);
                moveIndicator(t);
            });
            t.addEventListener('mouseleave', () => moveIndicator(active));
        });
    })();
</script>
<?php $__env->stopPush(); ?>
<?php endif; ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/shipping/_nav.blade.php ENDPATH**/ ?>