@extends('layouts.app')
@section('title','Hộp quà bí ẩn')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="preconnect" href="https://cdnjs.cloudflare.com">
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>

<style>
    /* ===== SCOPED: mystery-* (an toàn với layout khác) ===== */
    .mystery-stage {
        min-height: 76vh;
        background: radial-gradient(1000px 560px at 50% -12%, #fff, #fff 36%, #fff6f8 60%, #fde2e4 100%)
    }

    .mystery-wrap {
        max-width: 1050px;
        margin: 0 auto;
        padding: 28px 16px
    }

    .mystery-top {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-bottom: 18px
    }

    .mystery-top h1 {
        font-size: clamp(22px, 3vw, 30px);
        font-weight: 800;
        margin: 0
    }

    .mystery-hint {
        color: #6b7280
    }

    .mystery-badge {
        display: inline-flex;
        gap: .45rem;
        align-items: center;
        padding: .38rem .7rem;
        border-radius: 12px;
        background: #eef2ff;
        font-weight: 700;
        color: #374151;
        border: 1px solid #e5e7eb
    }

    /* 3×3 giữa trang */
    .mystery-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 28px;
        place-items: center;
        justify-content: center
    }

    .mystery-cell {
        width: 196px;
        height: 196px;
        display: grid;
        place-items: center;
        border-radius: 26px;
        border: 1px solid #ffd0da;
        background:
            radial-gradient(220px 120px at 30% 20%, #fff 0%, #fff7fb 35%, transparent 70%),
            linear-gradient(180deg, #fff9fb, #ffe8ee);
        box-shadow:
            inset 0 1px 0 #fff,
            0 18px 40px rgba(239, 68, 68, .16);
        transition: transform .18s ease, box-shadow .18s ease;
        cursor: pointer;
        perspective: 1200px;
    }

    .mystery-cell:hover {
        transform: translateY(-3px);
        box-shadow: 0 26px 54px rgba(17, 24, 39, .12)
    }

    @media (max-width:920px) {
        .mystery-cell {
            width: 176px;
            height: 176px
        }
    }

    @media (max-width:768px) {
        .mystery-grid {
            gap: 22px
        }

        .mystery-cell {
            width: 154px;
            height: 154px
        }
    }

    /* Modal glassmorphism */
    .mystery-modal {
        position: fixed;
        inset: 0;
        display: none;
        place-items: center;
        background: rgba(15, 23, 42, .35);
        backdrop-filter: blur(8px);
        z-index: 70
    }

    .mystery-modal.show {
        display: grid
    }

    .mystery-card {
        width: min(92vw, 560px);
        border-radius: 22px;
        padding: 22px;
        color: #0f172a;
        background: linear-gradient(180deg, #ffffff, #fbfbff);
        box-shadow: 0 30px 70px rgba(2, 6, 23, .28), inset 0 1px 0 rgba(255, 255, 255, .8), inset 0 -6px 18px rgba(2, 6, 23, .04);
        border: 1px solid rgba(148, 163, 184, .18);
    }

    .mystery-title {
        font-weight: 900;
        font-size: 28px;
        line-height: 1.2;
        margin: 6px 0 8px
    }

    .mystery-sub {
        color: #64748b;
        margin-bottom: 14px
    }

    .mystery-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 16px
    }

    .mbtn {
        appearance: none;
        padding: 10px 14px;
        border-radius: 12px;
        border: 0;
        font-weight: 800;
        cursor: pointer
    }

    .mbtn-red {
        background: linear-gradient(135deg, #ef4444, #f87171);
        color: #fff;
        box-shadow: 0 8px 18px rgba(239, 68, 68, .24)
    }

    .mbtn-green {
        background: linear-gradient(135deg, #059669, #10b981);
        color: #fff;
        box-shadow: 0 8px 18px rgba(16, 185, 129, .24)
    }

    .mbtn-blue {
        background: linear-gradient(135deg, #2563eb, #4f46e5);
        color: #fff;
        box-shadow: 0 8px 18px rgba(59, 130, 246, .24)
    }

    .mbtn-ghost {
        background: #fff;
        border: 1px solid #e2e8f0
    }

    .mystery-code {
        display: inline-block;
        padding: 8px 12px;
        border-radius: 12px;
        background: #f0fdf4;
        border: 1px dashed #10b981;
        font-family: ui-monospace, Menlo, Consolas
    }

    .mystery-sad {
        font-size: 52px;
        line-height: 1;
        margin-bottom: 8px
    }

    /* toast mini – scoped */
    .mystery-toast {
        position: fixed;
        top: 16px;
        right: 16px;
        z-index: 80;
        padding: 8px 12px;
        border-radius: 10px;
        color: #fff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .2)
    }
</style>

<div class="mystery-stage">
    <div class="mystery-wrap">
        <div class="mystery-top">
            <div>
                <h1>Hộp quà bí ẩn</h1>
                <div class="mystery-hint">Chọn 1 trong 9 bưu kiện để săn <b>mã vận chuyển</b> từ Admin.</div>
            </div>
            <div class="mystery-badge">Lượt còn lại: <span id="mystery-remain" class="ml-1">0</span></div>
        </div>

        <div class="mystery-grid">
            @for($i=1;$i<=9;$i++)
                <button class="mystery-cell" data-box="{{ $i }}" onclick="mysteryOpen({{ $i }})" disabled>
                <!-- SVG GIFT – contrast cao, nét, có halo -->
                <svg id="gift-{{ $i }}" class="mystery-gift" width="160" height="160" viewBox="0 0 200 200" fill="none">
                    <defs>
                        <linearGradient id="gFront" x1="0" y1="80" x2="0" y2="180">
                            <stop offset="0" stop-color="#FFF2F6" />
                            <stop offset=".55" stop-color="#FFD3DD" />
                            <stop offset="1" stop-color="#FFA6B8" />
                        </linearGradient>
                        <linearGradient id="gSide" x1="0" y1="80" x2="0" y2="180">
                            <stop offset="0" stop-color="#FF8FA6" />
                            <stop offset="1" stop-color="#F43F5E" />
                        </linearGradient>
                        <linearGradient id="gTop" x1="0" y1="50" x2="0" y2="95">
                            <stop offset="0" stop-color="#FFA6B8" />
                            <stop offset="1" stop-color="#F87171" />
                        </linearGradient>
                        <linearGradient id="gRibbon" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="#E11D48" />
                            <stop offset="1" stop-color="#FB7185" />
                        </linearGradient>
                        <radialGradient id="gGloss" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(84 25) rotate(0) scale(70 30)">
                            <stop stop-color="#fff" />
                            <stop offset=".55" stop-color="#ffffff80" />
                            <stop offset="1" stop-color="#ffffff00" />
                        </radialGradient>
                        <!-- soft outline để sắc nét trên nền sáng -->
                        <filter id="outline">
                            <feFlood flood-color="#e11d4833" result="c" />
                            <feComposite in="c" in2="SourceAlpha" operator="in" />
                            <feMorphology operator="dilate" radius="0.6" in="SourceAlpha" result="e" />
                            <feComposite in="c" in2="e" operator="in" />
                            <feMerge>
                                <feMergeNode />
                                <feMergeNode in="SourceGraphic" />
                            </feMerge>
                        </filter>
                        <!-- drop shadow lid/bow -->
                        <filter id="ds" x="-40%" y="-40%" width="180%" height="180%">
                            <feGaussianBlur in="SourceAlpha" stdDeviation="2.2" />
                            <feOffset dy="2" />
                            <feComponentTransfer>
                                <feFuncA type="linear" slope=".35" />
                            </feComponentTransfer>
                            <feMerge>
                                <feMergeNode />
                                <feMergeNode in="SourceGraphic" />
                            </feMerge>
                        </filter>
                    </defs>

                    <!-- Ambient halo sau hộp để tách khỏi nền trắng -->
                    <radialGradient id="halo" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(95 120) scale(90 60)">
                        <stop offset="0" stop-color="#ffc2d0" />
                        <stop offset="1" stop-color="#ffc2d000" />
                    </radialGradient>
                    <ellipse cx="95" cy="120" rx="85" ry="58" fill="url(#halo)" opacity=".35" />

                    <!-- Soft floor shadow -->
                    <ellipse cx="95" cy="185" rx="70" ry="16" fill="#000" opacity=".12" />

                    <!-- RIGHT SIDE -->
                    <polygon points="135,80 182,64 172,160 128,176" fill="url(#gSide)" filter="url(#outline)" />

                    <!-- FRONT -->
                    <polygon points="25,80 135,80 128,176 18,176" fill="url(#gFront)" filter="url(#outline)" />

                    <!-- RIBBONS -->
                    <rect x="77" y="84" width="18" height="92" rx="9" fill="url(#gRibbon)" filter="url(#outline)" />
                    <polygon points="25,122 135,122 133,138 21,138" fill="url(#gRibbon)" filter="url(#outline)" />

                    <!-- LID group (animate) -->
                    <g class="lid" style="transform-box: fill-box; transform-origin: 25% 60%;" filter="url(#ds)">
                        <polygon points="25,80 135,80 182,64 62,64" fill="url(#gTop)" />
                        <polygon points="25,80 135,80 133,92 23,92" fill="#FFB6C6" />
                        <ellipse cx="95" cy="72" rx="70" ry="24" fill="url(#gGloss)" />
                        <!-- bow -->
                        <path d="M90 58 C70 50, 60 47, 57 60 C55 66, 62 72, 73 74 C84 76, 89 70, 90 58 Z" fill="#FF9DB1" />
                        <path d="M100 58 C120 50, 130 47, 133 60 C135 66, 128 72, 117 74 C106 76, 101 70, 100 58 Z" fill="#FF9DB1" />
                    </g>
                </svg>
                </button>
                @endfor
        </div>
    </div>
</div>

<!-- Modal -->
<div id="mystery-modal" class="mystery-modal" aria-hidden="true">
    <div id="mystery-card" class="mystery-card" role="dialog" aria-modal="true"></div>
</div>

<script>
    const _csrf = document.querySelector('meta[name="csrf-token"]').content;
    const API = {
        config: `{{ route('mystery.config') }}`,
        play: `{{ route('mystery.play') }}`
    };
    let remain = 0,
        busy = false,
        lastLogId = null;

    document.addEventListener('DOMContentLoaded', async () => {
        const r = await fetch(API.config);
        const j = await r.json();
        if (j.ok) {
            remain = j.remaining || 0;
            document.getElementById('mystery-remain').textContent = remain;
            document.querySelectorAll('.mystery-cell').forEach(b => b.disabled = remain <= 0);
        }
    });

    const sleep = ms => new Promise(r => setTimeout(r, ms));

    function jiggle(el, ms = 1000) {
        const rep = Math.max(1, Math.floor(ms / 110));
        return gsap.fromTo(el, {
            rotationZ: 0,
            y: 0,
            scale: 1
        }, {
            rotationZ: 2,
            y: -2,
            scale: 1.02,
            yoyo: true,
            repeat: rep,
            duration: .055,
            transformOrigin: '50% 60%'
        });
    }

    function sparkles(svg) {
        for (let i = 0; i < 14; i++) {
            const s = document.createElement('div');
            s.style.position = 'absolute';
            s.style.width = '10px';
            s.style.height = '10px';
            const host = svg.closest('.mystery-cell');
            host.style.position = 'relative';
            s.style.left = '50%';
            s.style.top = '30%';
            s.style.transform = 'translate(-50%,-50%)';
            s.style.background = ['#fde68a', '#fca5a5', '#93c5fd', '#86efac', '#c7d2fe'][i % 5];
            s.style.borderRadius = '3px';
            s.style.boxShadow = '0 2px 4px rgba(0,0,0,.15)';
            host.appendChild(s);
            gsap.to(s, {
                x: (Math.random() - .5) * 170,
                y: -100 - Math.random() * 90,
                rotation: gsap.utils.random(-120, 120),
                scale: gsap.utils.random(.7, 1.3),
                opacity: 0,
                duration: gsap.utils.random(1, 1.4),
                ease: 'power2.out',
                onComplete: () => s.remove()
            });
        }
    }

    function confettiBurst() {
        confetti({
            particleCount: 180,
            spread: 70,
            origin: {
                y: 0.25
            }
        });
        confetti({
            particleCount: 140,
            angle: 60,
            spread: 55,
            origin: {
                x: 0
            }
        });
        confetti({
            particleCount: 140,
            angle: 120,
            spread: 55,
            origin: {
                x: 1
            }
        });
    }

    async function mysteryOpen(clickedId) {
        if (busy || remain <= 0) return;
        busy = true;
        document.querySelectorAll('.mystery-cell').forEach(b => b.disabled = true);

        // 1) Rung 1s (lụa)
        const clicked = document.getElementById(`gift-${clickedId}`);
        jiggle(clicked, 1000);

        // 2) Gọi server song song
        const req = fetch(API.play, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': _csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(r => r.json()).catch(() => ({
                ok: false,
                message: 'Lỗi mạng'
            }));
        const [j] = await Promise.all([req, sleep(1000)]);
        if (!j.ok) {
            toast('error', j.message || 'Không thể mở hộp');
            busy = false;
            document.querySelectorAll('.mystery-cell').forEach(b => b.disabled = remain <= 0);
            return;
        }

        remain = Math.max(0, remain - 1);
        document.getElementById('mystery-remain').textContent = remain;
        lastLogId = j.log_id || null;

        // 3) Mở nắp 1s (spring) + sparkles
        const actual = document.getElementById(`gift-${j.box_id}`);
        const lid = actual.querySelector('.lid');
        gsap.fromTo(lid, {
            rotate: 0,
            y: 0
        }, {
            rotate: -26,
            y: -16,
            transformOrigin: '25% 60%',
            duration: 1.0,
            ease: 'elastic.out(1,.55)'
        });
        sparkles(actual);

        // 4) Popup
        if (j.result_type === 'voucher') {
            confettiBurst();
            showWin(j.voucher_code || '');
        } else {
            showLose();
        }

        busy = false;
        document.querySelectorAll('.mystery-cell').forEach(b => b.disabled = remain <= 0);
    }

    /* ===== Modal helpers ===== */
    function showWin(code) {
        const m = document.getElementById('mystery-modal'),
            c = document.getElementById('mystery-card');
        c.innerHTML = `
    <div class="mystery-title">Chúc mừng! Trúng mã vận chuyển 🎉</div>
    <div class="mystery-sub">Áp mã ở bước thanh toán để giảm phí ship.</div>
    <div class="mystery-code">Mã: <b>${code}</b></div>
    <div class="mystery-actions">
      <button id="m-save" class="mbtn mbtn-red">Lưu mã</button>
      <button id="m-copy" class="mbtn mbtn-green">Copy</button>
      <a href="{{ route('cart.index') }}" class="mbtn mbtn-blue" style="text-decoration:none">Dùng ngay</a>
      <button id="m-close" class="mbtn mbtn-ghost">Đóng</button>
    </div>`;
        m.classList.add('show');
        gsap.fromTo(c, {
            y: 42,
            opacity: 0,
            scale: .96
        }, {
            y: 0,
            opacity: 1,
            scale: 1,
            duration: .45,
            ease: 'power3.out'
        });
        document.getElementById('m-copy').onclick = () => navigator.clipboard.writeText(code).then(() => toast('success', 'Đã copy mã'));
        document.getElementById('m-save').onclick = () => saveCode();
        document.getElementById('m-close').onclick = () => closeModal();
        m.onclick = e => {
            if (e.target === m) closeModal();
        };
    }

    function showLose() {
        const m = document.getElementById('mystery-modal'),
            c = document.getElementById('mystery-card');
        c.innerHTML = `
    <div class="mystery-sad">😢</div>
    <div class="mystery-title" style="margin-top:0">Hụt rồi!</div>
    <div class="mystery-sub">Đừng buồn nhé, thử lại lần sau.</div>
    <div class="mystery-actions"><button id="m-close2" class="mbtn mbtn-ghost">Đóng</button></div>`;
        m.classList.add('show');
        gsap.fromTo(c, {
            y: 42,
            opacity: 0,
            scale: .96
        }, {
            y: 0,
            opacity: 1,
            scale: 1,
            duration: .45,
            ease: 'power3.out'
        });
        document.getElementById('m-close2').onclick = () => closeModal();
        m.onclick = e => {
            if (e.target === m) closeModal();
        };
    }

    function closeModal() {
        const m = document.getElementById('mystery-modal'),
            c = document.getElementById('mystery-card');
        gsap.to(c, {
            y: 20,
            opacity: 0,
            scale: .98,
            duration: .25,
            ease: 'power2.out',
            onComplete: () => {
                m.classList.remove('show');
                c.innerHTML = '';
            }
        });
    }

    /* toast mini – scoped */
    function toast(type, msg) {
        const el = document.createElement('div');
        el.className = 'mystery-toast';
        el.textContent = msg || '';
        el.style.background = (type === 'success' ? 'linear-gradient(135deg,#059669,#10b981)' : 'linear-gradient(135deg,#dc2626,#ef4444)');
        document.body.appendChild(el);
        gsap.fromTo(el, {
            y: -12,
            opacity: 0
        }, {
            y: 0,
            opacity: 1,
            duration: .25
        });
        setTimeout(() => gsap.to(el, {
            y: -8,
            opacity: 0,
            duration: .25,
            onComplete: () => el.remove()
        }), 2200);
    }

    /* save -> /mystery/save/{log} */
    async function saveCode() {
        if (!lastLogId) {
            toast('error', 'Không tìm thấy phiên chơi để lưu');
            return;
        }
        const r = await fetch(`/mystery/save/${lastLogId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': _csrf,
                'Accept': 'application/json'
            }
        });
        const j = await r.json();
        j.ok ? toast('success', j.already ? 'Mã đã lưu trước đó' : 'Đã lưu vào ví của bạn') :
            toast('error', j.message || 'Không thể lưu mã');
    }
</script>
@endsection