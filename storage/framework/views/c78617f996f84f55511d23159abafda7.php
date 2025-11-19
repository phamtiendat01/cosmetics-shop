
<?php $__env->startSection('title','Vòng quay may mắn'); ?>

<?php $__env->startSection('content'); ?>
<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

<link rel="preconnect" href="https://cdnjs.cloudflare.com">
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/2.1.3/TweenMax.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/zarocknz/javascript-winwheel/Winwheel.min.js"></script>

<style>
    .btn-primary {
        background: linear-gradient(90deg, #ff3d67, #ff5965);
        color: #fff
    }

    .btn-primary:hover {
        filter: brightness(.95)
    }

    .panel {
        backdrop-filter: saturate(140%) blur(8px);
        background: linear-gradient(180deg, rgba(255, 255, 255, .85), rgba(255, 255, 255, .65));
    }

    #glow {
        background:
            radial-gradient(closest-side, rgba(255, 255, 255, .25), rgba(255, 255, 255, 0) 60%),
            conic-gradient(from 0deg, rgba(251, 113, 133, .25), rgba(244, 63, 94, .15), rgba(251, 113, 133, .25));
        mix-blend-mode: screen;
    }

    #wheelWrap {
        transform-origin: center top
    }

    /* Toast */
    #toastHost {
        position: fixed;
        top: 24px;
        right: 24px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 8px
    }

    .toast {
        min-width: 260px;
        max-width: 360px;
        padding: 12px 14px;
        border-radius: 14px;
        background: #111827;
        color: #fff;
        box-shadow: 0 12px 30px rgba(0, 0, 0, .18);
        display: flex;
        align-items: flex-start;
        gap: 10px;
        opacity: 0;
        transform: translateY(-10px)
    }

    .toast.success {
        background: linear-gradient(135deg, #059669, #10b981)
    }

    .toast.error {
        background: linear-gradient(135deg, #dc2626, #ef4444)
    }

    .toast .ttl {
        font-weight: 700
    }

    .toast .msg {
        font-size: 14px;
        opacity: .95
    }
</style>

<div id="toastHost"></div>

<div class="min-h-[70vh] bg-gradient-to-br from-rose-50 via-white to-pink-50">
    <div class="max-w-6xl mx-auto px-4 py-10">
        <div class="grid lg:grid-cols-5 gap-10">
            <div class="lg:col-span-3">
                <div id="wheelWrap" class="relative mx-auto max-w-[520px] w-full">
                    <svg id="pointer" class="absolute -top-1 left-1/2 -translate-x-1/2 z-20" width="64" height="64" viewBox="0 0 64 64" aria-hidden="true">
                        <defs>
                            <filter id="pGlow" x="-50%" y="-50%" width="200%" height="200%">
                                <feDropShadow dx="0" dy="2" stdDeviation="2" flood-color="#fb7185" flood-opacity=".9" />
                            </filter>
                        </defs>
                        <circle cx="32" cy="20" r="16" fill="#fff" />
                        <path id="pointerArrow" d="M32 46 L44 24 H20 Z" fill="#e11d48" filter="url(#pGlow)" />
                        <circle cx="32" cy="20" r="4" fill="#e11d48" />
                    </svg>

                    <div class="rounded-full ring-8 ring-white shadow-[0_12px_60px_rgba(0,0,0,.12)] overflow-hidden relative">
                        <div id="glow" class="absolute inset-0 pointer-events-none opacity-0 transition-opacity duration-300"></div>
                        <canvas id="wheelCanvas" width="520" height="520" class="w-full h-auto"></canvas>
                    </div>

                    <div class="text-center mt-6">
                        <button id="spinBtn" class="px-7 py-3 rounded-full font-semibold shadow btn-primary disabled:opacity-40 disabled:cursor-not-allowed">
                            Quay
                        </button>
                        <p class="text-sm text-gray-600 mt-2">Còn <b id="remain">0</b> / <b id="max">3</b> lượt hôm nay</p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                <div class="panel p-6 rounded-2xl border">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-xl">🎯</span>
                        <h2 class="text-lg font-bold">Phần thưởng</h2>
                    </div>
                    <ul id="prizeList" class="space-y-2 text-[15px]"></ul>
                </div>

                <div id="resultCard" class="hidden p-6 bg-white rounded-2xl border shadow-sm">
                    <div id="resultTitle" class="text-lg font-bold"></div>
                    <p id="resultDesc" class="text-gray-600 mt-1"></p>

                    <div id="couponBox" class="hidden mt-4">
                        <div class="flex items-center gap-2">
                            <code id="couponCode" class="px-3 py-2 bg-rose-50 text-rose-700 rounded border border-rose-200 font-semibold"></code>
                            <button id="copyBtn" class="px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm">Copy</button>
                            <button id="saveBtn" class="px-3 py-2 rounded-lg btn-primary text-sm">Lưu mã</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <canvas id="confetti" class="fixed inset-0 pointer-events-none z-50" width="0" height="0" style="display:none"></canvas>
    </div>
</div>

<script>
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const API = {
        config: `<?php echo e(route('spin.config')); ?>`,
        make: `<?php echo e(route('spin.make')); ?>`,
        save: `<?php echo e(route('spin.save')); ?>`
    };
    const palette = ['#fde68a', '#93c5fd', '#86efac', '#fca5a5', '#c7d2fe', '#a5f3fc'];
    const ANIM_DURATION = 4.8,
        ANIM_SPINS = 9,
        PINS_FACTOR = 1;

    let slices = [],
        wheel = null,
        remain = 0,
        lastLog = null,
        pending = null,
        savedLogs = new Set();

    const $remain = document.getElementById('remain');
    const $max = document.getElementById('max');
    const $spin = document.getElementById('spinBtn');
    const $list = document.getElementById('prizeList');
    const $card = document.getElementById('resultCard');
    const $title = document.getElementById('resultTitle');
    const $desc = document.getElementById('resultDesc');
    const $box = document.getElementById('couponBox');
    const $code = document.getElementById('couponCode');
    const wrap = document.getElementById('wheelWrap');
    const glow = document.getElementById('glow');
    const pointer = document.getElementById('pointerArrow');
    const $saveBtn = document.getElementById('saveBtn');

    boot();

    async function boot() {
        const r = await fetch(API.config, {
            headers: {
                Accept: 'application/json'
            }
        });
        const j = await r.json();
        if (!j.ok) {
            toast('error', 'Lỗi', 'Không tải được cấu hình');
            return;
        }
        slices = j.slices;
        remain = j.remaining;
        $remain.textContent = remain;
        $max.textContent = j.max_per_day ?? 3;
        renderList();
        buildWheel();
        if (remain > 0) TweenMax.fromTo($spin, 1.4, {
            scale: 1
        }, {
            scale: 1.06,
            repeat: -1,
            yoyo: true,
            ease: Sine.easeInOut
        });
    }

    function makeAnimation() {
        return {
            type: 'spinToStop',
            duration: ANIM_DURATION,
            spins: ANIM_SPINS,
            easing: 'Power3.easeOut',
            callbackSound: pinTick,
            soundTrigger: 'pin',
            callbackAfter: onAfterTick,
            callbackFinished: onSpinFinished
        };
    }

    function buildWheel() {
        const segs = slices.map((s, i) => ({
            fillStyle: palette[i % palette.length],
            text: s.label,
            textFontFamily: 'Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI"',
            textFillStyle: '#111827',
            textFontSize: 16
        }));
        wheel = new Winwheel({
            canvasId: 'wheelCanvas',
            numSegments: segs.length,
            segments: segs,
            outerRadius: 240,
            innerRadius: 0,
            textAlignment: 'outer',
            textOrientation: 'horizontal',
            textMargin: 16,
            rotationAngle: (wheel?.rotationAngle || 0) % 360,
            responsive: true,
            lineWidth: 2,
            strokeStyle: '#ffffff',
            pins: {
                number: segs.length * PINS_FACTOR,
                outerRadius: 3,
                responsive: true
            },
            animation: makeAnimation()
        });
    }

    function resetForNextSpin() {
        if (wheel?.animation?.isSpinning) wheel.stopAnimation(false);
        wheel.rotationAngle %= 360;
        wheel.animation = makeAnimation();
        wheel.draw();
    }

    function renderList() {
        $list.innerHTML = '';
        slices.forEach((s, i) => {
            const li = document.createElement('li');
            li.innerHTML = `<span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold mr-2" style="background:${palette[i%palette.length]}22;color:#333">${i+1}</span>${s.label}${s.type==='none'?' <span class="text-xs text-gray-400">(trượt)</span>':''}`;
            $list.appendChild(li);
        });
    }

    $spin.addEventListener('click', async () => {
        if (remain <= 0 || !wheel || wheel.animation.isSpinning) return;
        $spin.disabled = true;

        const r = await fetch(API.make, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                Accept: 'application/json'
            },
            body: JSON.stringify({})
        });
        const j = await r.json();
        if (!j.ok) {
            $spin.disabled = false;
            toast('error', 'Lỗi', j.message || 'Không quay được');
            return;
        }

        lastLog = j.log_id;
        savedLogs.delete(lastLog); // reset trạng thái "đã lưu" cho lượt mới
        const idx = slices.findIndex(s => s.id === j.wheel_slice_id);
        pending = {
            idx,
            code: j.coupon_code
        };
        const seg = idx + 1,
            stopAt = wheel.getRandomForSegment(seg);

        resetForNextSpin();
        wheel.animation.stopAngle = stopAt;

        TweenMax.killTweensOf([wrap, pointer]);
        glow.style.opacity = '1';
        TweenMax.fromTo(wrap, .35, {
            scale: 1
        }, {
            scale: 1.03,
            ease: Power2.easeOut
        });
        TweenMax.fromTo(pointer, .09, {
            rotation: 0
        }, {
            rotation: 8,
            yoyo: true,
            repeat: 8,
            ease: Sine.easeInOut
        });
        wheel.startAnimation();
    });

    function pinTick() {}

    function onAfterTick() {
        glow.style.opacity = '0.9';
    }

    function onSpinFinished() {
        TweenMax.to(wrap, .8, {
            scale: 1,
            ease: Elastic.easeOut.config(1, 0.5)
        });
        TweenMax.to(pointer, .2, {
            rotation: 0,
            ease: Power2.easeOut
        });
        glow.style.opacity = '0';
        remain = Math.max(0, remain - 1);
        $remain.textContent = remain;
        $spin.disabled = (remain <= 0);
        const idx = pending?.idx ?? -1,
            code = pending?.code ?? null;
        pending = null;
        if (idx < 0) return;
        const s = slices[idx];
        $card.classList.remove('hidden');
        if (s.type === 'coupon' && code) {
            $title.textContent = 'Chúc mừng!';
            $desc.textContent = s.label;
            $box.classList.remove('hidden');
            $code.textContent = code;
            $saveBtn.disabled = false;
            confetti();
        } else {
            $title.textContent = 'Hụt rồi 😅';
            $desc.textContent = '';
            $box.classList.add('hidden');
        }
    }

    document.getElementById('copyBtn').addEventListener('click', () => {
        navigator.clipboard.writeText($code.textContent.trim());
        toast('success', 'Đã copy', 'Mã đã được sao chép');
    });

    document.getElementById('saveBtn').addEventListener('click', async () => {
        if (!lastLog) return;
        if (savedLogs.has(lastLog)) {
            toast('success', 'Đã lưu', 'Mã này bạn đã lưu');
            return;
        }
        $saveBtn.disabled = true;
        const r = await fetch(API.save, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                Accept: 'application/json'
            },
            body: JSON.stringify({
                log_id: lastLog
            })
        });
        const j = await r.json().catch(() => ({
            ok: false,
            message: 'Lỗi kết nối'
        }));
        if (j.ok) {
            savedLogs.add(lastLog);
            toast('success', j.already ? 'Đã lưu' : 'Thành công', j.already ? 'Mã đã có trong tài khoản' : 'Đã lưu mã vào tài khoản');
        } else {
            $saveBtn.disabled = false;
            toast('error', 'Không lưu được', j.message || 'Vui lòng thử lại');
        }
    });

    /* Toast 3s tự tắt */
    function toast(type = 'success', title = 'Thông báo', msg = '') {
        const host = document.getElementById('toastHost');
        const el = document.createElement('div');
        el.className = `toast ${type}`;
        el.innerHTML = `<div><div class="ttl">${title}</div><div class="msg">${msg}</div></div>`;
        host.appendChild(el);
        TweenMax.to(el, .25, {
            autoAlpha: 1,
            y: 0,
            ease: Power2.out
        });
        setTimeout(() => {
            TweenMax.to(el, .25, {
                autoAlpha: 0,
                y: -10,
                ease: Power2.in,
                onComplete: () => el.remove()
            });
        }, 3000);
    }

    /* confetti nhỏ */
    function confetti() {
        const cv = document.getElementById('confetti'),
            ctx = cv.getContext('2d');
        cv.width = innerWidth;
        cv.height = innerHeight;
        cv.style.display = 'block';
        const colors = palette,
            pcs = Array.from({
                length: 160
            }).map(() => ({
                x: Math.random() * cv.width,
                y: -10,
                r: Math.random() * 5 + 2,
                v: Math.random() * 2 + 2,
                c: colors[Math.floor(Math.random() * colors.length)]
            }));
        let t = 0;
        (function loop() {
            ctx.clearRect(0, 0, cv.width, cv.height);
            pcs.forEach(p => {
                p.y += p.v;
                p.x += Math.sin((p.y + p.r) * .02);
                ctx.fillStyle = p.c;
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fill();
            });
            if (++t < 90) requestAnimationFrame(loop);
            else cv.style.display = 'none';
        })();
    }
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/spin/index.blade.php ENDPATH**/ ?>