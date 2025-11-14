@extends('layouts.app')
@section('title', 'Thanh toán VietQR')

@section('content')
<style>
    /* ——— hiệu ứng nhẹ nhàng ——— */
    .hover-lift {
        transition: transform .25s ease, box-shadow .25s ease;
    }

    .hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(2, 6, 23, .08);
    }

    .shine {
        position: relative;
        overflow: hidden;
    }

    .shine:after {
        content: "";
        position: absolute;
        inset: -100% -60%;
        background: linear-gradient(120deg, transparent, rgba(255, 255, 255, .35), transparent);
        transform: translateX(-100%);
        animation: shine 3s infinite;
    }

    @keyframes shine {
        to {
            transform: translateX(100%);
        }
    }

    .pulse-dot {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, .6);
        animation: pulse 1.6s infinite;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, .6);
        }

        70% {
            box-shadow: 0 0 0 14px rgba(16, 185, 129, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
        }
    }

    /* vòng tròn tiến độ quanh QR (conic-gradient) */
    .qr-ring {
        position: absolute;
        inset: -10px;
        border-radius: 18px;
        filter: drop-shadow(0 8px 18px rgba(2, 6, 23, .12));
    }

    /* ripple copy buttons */
    .ripple {
        position: relative;
        overflow: hidden;
    }

    .ripple::after {
        content: "";
        position: absolute;
        width: 8px;
        height: 8px;
        border-radius: 9999px;
        background: rgba(2, 6, 23, .15);
        transform: scale(1);
        opacity: 0;
        transition: all .55s;
    }

    .ripple:active::after {
        transform: scale(18);
        opacity: 1;
    }
</style>

<div class="container mx-auto max-w-6xl px-4 py-6">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Thanh toán qua VietQR</h1>
            <p class="text-slate-500 mt-1">Quét mã để chuyển khoản nhanh. Sau khi nhận tiền, trang sẽ tự chuyển về chi tiết đơn.</p>
        </div>
    </div>

    {{-- Root cho JS --}}
    <div id="vietqr-root"
        data-order-id="{{ $order->id }}"
        data-deadline-ts="{{ $deadlineTs ?? '' }}"
        data-check-url="{{ $checkUrl }}"
        data-redirect-url="{{ $redirectUrl }}"
        data-ttl-min="{{ $ttlMin ?? 15 }}"></div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Cột trái: QR với vòng tiến độ --}}
        <div class="bg-white rounded-2xl border shadow-sm p-4 shine">
            <div class="flex items-center justify-between">
                <div class="font-medium">Quét mã VietQR</div>
                <div class="text-sm text-slate-500">{{ $bank ?? 'Ngân hàng' }}</div>
            </div>

            <div class="relative mt-4 w-full max-w-md mx-auto">
                <div id="qrRing" class="qr-ring" style="background:conic-gradient(#10b981 0%, #e5e7eb 0%);"></div>
                <img src="{{ $qr_url }}" alt="Mã VietQR"
                    class="relative z-[1] w-full rounded-xl border hover-lift select-none">
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button id="copyAcc" class="ripple inline-flex items-center gap-2 px-3 py-2 rounded-lg border bg-slate-50 hover:bg-slate-100 text-sm"
                    data-copy="{{ $account }}">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <rect x="9" y="9" width="13" height="13" rx="2" />
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                    </svg>
                    Sao chép STK: <b>{{ $account }}</b>
                </button>

                <button id="copyRef" class="ripple inline-flex items-center gap-2 px-3 py-2 rounded-lg border bg-slate-50 hover:bg-slate-100 text-sm"
                    data-copy="{{ $ref }}">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M21 15v4a2 2 0 0 1-2 2h-7" />
                        <path d="M3 9V5a2 2 0 0 1 2-2h7" />
                        <rect x="7" y="7" width="10" height="10" rx="2" />
                    </svg>
                    Sao chép nội dung: <b>{{ $ref }}</b>
                </button>

                <a href="{{ $qr_url }}" download="vietqr-{{ $order->id }}.jpg"
                    class="ripple inline-flex items-center gap-2 px-3 py-2 rounded-lg border bg-slate-50 hover:bg-slate-100 text-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M12 3v12" />
                        <path d="M8 11l4 4 4-4" />
                        <path d="M20 21H4" />
                    </svg>
                    Tải ảnh QR
                </a>
            </div>
        </div>

        {{-- Cột phải: Thông tin + Trạng thái/tiến độ --}}
        <div class="space-y-4">
            <div class="bg-white rounded-2xl border shadow-sm p-4">
                <div class="text-sm text-slate-500">Thông tin thanh toán</div>
                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="rounded-xl border p-3 bg-slate-50">
                        <div class="text-xs text-slate-500">Số tài khoản</div>
                        <div class="font-semibold">{{ $account }}</div>
                    </div>
                    <div class="rounded-xl border p-3 bg-slate-50">
                        <div class="text-xs text-slate-500">Tên người nhận</div>
                        <div class="font-medium">{{ config('vietqr.name') }}</div>
                    </div>
                    <div class="rounded-xl border p-3 bg-slate-50">
                        <div class="text-xs text-slate-500">Nội dung</div>
                        <div class="font-medium">{{ $ref }}</div>
                    </div>
                    <div class="rounded-xl border p-3 bg-slate-50">
                        <div class="text-xs text-slate-500">Số tiền</div>
                        <div class="font-semibold">{{ number_format($amount) }} đ</div>
                    </div>
                </div>
            </div>

            {{-- Trạng thái & tự động cập nhật (đã move sang phải) --}}
            <div class="bg-white rounded-2xl border shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span id="pollDot" class="w-2.5 h-2.5 rounded-full bg-emerald-500 pulse-dot"></span>
                        <div class="text-sm text-slate-600">Tự động kiểm tra giao dịch</div>
                    </div>
                    <button id="forcePing" class="ripple text-xs px-3 py-1.5 rounded-lg border bg-slate-50 hover:bg-slate-100">
                        Tôi đã chuyển
                    </button>
                </div>

                <div id="statusBox" class="mt-3 text-sm text-slate-700"></div>

                <div class="mt-3">
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div id="progress" class="h-2 bg-emerald-500 w-0"></div>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
                        <div id="countdown"></div>
                        <div id="tickText" class="opacity-70">Đang cập nhật…</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border shadow-sm p-4">
                <div class="text-sm text-slate-500">Hướng dẫn nhanh</div>
                <ol class="mt-3 list-decimal list-inside space-y-1 text-sm">
                    <li>Mở app ngân hàng → <b>Quét QR</b>.</li>
                    <li>Kiểm tra <b>số tiền</b> và <b>nội dung</b> đúng <b>{{ $ref }}</b>.</li>
                    <li>Xác nhận chuyển. Trang sẽ tự nhận & chuyển tiếp.</li>
                </ol>
                <details class="mt-3">
                    <summary class="cursor-pointer text-sm text-slate-600">Gặp sự cố?</summary>
                    <ul class="mt-2 text-sm text-slate-600 list-disc list-inside">
                        <li>Nếu không hiện mã, bấm <b>Tải ảnh QR</b> và quét từ thư viện.</li>
                        <li>Nếu đã chuyển nhưng chưa nhận, đợi 1–2 phút hoặc bấm <b>Tôi đã chuyển</b>.</li>
                        <li>Luôn ghi đúng nội dung <b>{{ $ref }}</b> để hệ thống khớp lệnh.</li>
                    </ul>
                </details>
            </div>
        </div>
    </div>
</div>

{{-- Toast + overlay --}}
<div id="toast" class="hidden fixed left-1/2 -translate-x-1/2 bottom-6 bg-slate-900 text-white text-sm px-4 py-2 rounded-xl shadow-xl">
    <span id="toastMsg">Đã nhận tiền. Đang chuyển đến chi tiết đơn…</span>
</div>
<div id="redirectOverlay" class="hidden fixed inset-0 bg-black/60 items-center justify-center z-50">
    <div class="opacity-0 scale-95 transition rounded-xl bg-white px-6 py-4 shadow-2xl">
        <div class="flex items-center gap-2">
            <span class="inline-flex w-6 h-6 rounded-full bg-emerald-500 text-white items-center justify-center">✓</span>
            <span class="text-slate-700">Đang chuyển đến chi tiết đơn…</span>
        </div>
    </div>
</div>

<script>
    (function() {
        const root = document.getElementById('vietqr-root');
        if (!root) return;

        const orderId = Number(root.dataset.orderId || 0);
        const deadline = Number(root.dataset.deadlineTs || 0);
        const checkUrl = root.dataset.checkUrl;
        const redirectUrl = root.dataset.redirectUrl;
        const ttlMin = Number(root.dataset.ttlMin || 15);

        const DUR = {
            poll: 1500,
            toastShow: 1600,
            beforeRedirect: 3500,
            overlayStay: 1500
        };

        const statusBox = document.getElementById('statusBox');
        const countdown = document.getElementById('countdown');
        const progress = document.getElementById('progress');
        const toast = document.getElementById('toast');
        const overlay = document.getElementById('redirectOverlay');
        const qrRing = document.getElementById('qrRing');
        const pollDot = document.getElementById('pollDot');
        const tickText = document.getElementById('tickText');
        const forcePing = document.getElementById('forcePing');

        const START = deadline ? (deadline - ttlMin * 60 * 1000) : Date.now();

        function setPendingUI() {
            if (!statusBox) return;
            statusBox.className = 'mt-3 p-3 rounded-md border text-sm flex items-center gap-2 bg-yellow-50 border-yellow-200 text-yellow-800';
            statusBox.innerHTML = `
      <span class="inline-flex w-4 h-4 rounded-full border-2 border-yellow-500 border-t-transparent animate-spin"></span>
      <span>Đang chờ chuyển khoản...</span>`;
        }

        function setSuccessUI() {
            if (!statusBox) return;
            statusBox.className = 'mt-3 p-3 rounded-md border text-sm flex items-center gap-2 bg-emerald-50 border-emerald-200 text-emerald-800';
            statusBox.innerHTML = `
      <span class="inline-flex w-5 h-5 rounded-full bg-emerald-500 text-white items-center justify-center">✓</span>
      <span>Đã nhận tiền</span>`;
            if (pollDot) {
                pollDot.classList.remove('pulse-dot');
                pollDot.classList.add('bg-emerald-600');
            }
            if (tickText) tickText.textContent = 'Đã xác nhận thanh toán';
        }

        function showToast(msg) {
            const msgEl = document.getElementById('toastMsg');
            if (msgEl) msgEl.textContent = msg || 'Đã nhận tiền. Đang chuyển đến chi tiết đơn…';
            toast.classList.remove('hidden');
            toast.animate([{
                transform: 'translateY(12px)',
                opacity: 0
            }, {
                transform: 'translateY(0)',
                opacity: 1
            }], {
                duration: 220,
                easing: 'ease-out',
                fill: 'forwards'
            });
            setTimeout(() => {
                toast.animate([{
                    opacity: 1
                }, {
                    opacity: 0
                }], {
                    duration: 200,
                    easing: 'ease-in',
                    fill: 'forwards'
                });
                setTimeout(() => toast.classList.add('hidden'), 220);
            }, DUR.toastShow);
        }

        function goRedirect(url) {
            overlay.classList.remove('hidden');
            const card = overlay.firstElementChild;
            requestAnimationFrame(() => {
                overlay.classList.add('flex');
                card.classList.remove('opacity-0', 'scale-95');
                card.classList.add('opacity-100', 'scale-100');
            });
            setTimeout(() => {
                window.location.href = url || redirectUrl;
            }, DUR.overlayStay);
        }

        // progress (bar + ring)
        function applyProgress(pct) {
            if (progress) progress.style.width = pct + '%';
            if (qrRing) {
                qrRing.style.background = `conic-gradient(#10b981 ${pct}%, #e5e7eb ${pct}%)`;
            }
        }

        function tickTimer() {
            if (!deadline) return;
            const now = Date.now();
            const diff = deadline - now;
            const total = deadline - START;
            const passed = Math.max(0, Math.min(total, now - START));
            const pct = total ? Math.round((passed / total) * 100) : 0;
            applyProgress(pct);

            if (diff <= 0) {
                countdown.textContent = 'Hết thời gian chờ (' + ttlMin + ' phút).';
                if (statusBox) {
                    statusBox.className = 'mt-3 p-3 rounded-md border text-sm bg-rose-50 border-rose-200 text-rose-700';
                    statusBox.textContent = 'Hết thời gian chờ thanh toán. Vui lòng tạo lại đơn hoặc thử lại.';
                }
                return;
            }
            const m = Math.floor(diff / 60000),
                s = Math.floor((diff % 60000) / 1000);
            countdown.textContent = `Hết hạn sau: ${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
            setTimeout(tickTimer, 1000);
        }

        let pinging = false;
        async function ping(isManual = false) {
            if (deadline && Date.now() > deadline) return;
            if (pinging) return;
            pinging = true;
            if (tickText) tickText.textContent = isManual ? 'Đang kiểm tra...' : 'Đang cập nhật…';
            try {
                const r = await fetch(checkUrl, {
                    cache: 'no-store',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const j = await r.json();
                if (j.status === 'paid') {

                    confetti();
                    setSuccessUI();
                    showToast('Đã nhận tiền. Đang chuyển đến chi tiết đơn…');
                    setTimeout(() => goRedirect(j.redirect), DUR.beforeRedirect);
                    return;
                }
            } catch (e) {} finally {
                pinging = false;
            }
            setTimeout(ping, DUR.poll);
        }

        // confetti mini
        function confetti() {
            for (let i = 0; i < 22; i++) {
                const p = document.createElement('span');
                const size = 6 + Math.random() * 6;
                p.style.cssText = `position:fixed;z-index:60;top:50%;left:50%;width:${size}px;height:${size}px;border-radius:2px;background:hsl(${Math.random()*360},90%,60%);pointer-events:none;`;
                document.body.appendChild(p);
                const x = (Math.random() - 0.5) * 360,
                    y = (Math.random() - 0.8) * 400,
                    r = (Math.random() - 0.5) * 540;
                p.animate([{
                    transform: 'translate(0,0) rotate(0deg)',
                    opacity: 1
                }, {
                    transform: `translate(${x}px,${y}px) rotate(${r}deg)`,
                    opacity: 0
                }], {
                    duration: 900 + Math.random() * 600,
                    easing: 'cubic-bezier(.2,.8,.2,1)',
                    fill: 'forwards'
                }).onfinish = () => p.remove();
            }
        }

        // copy buttons
        function bindCopy(btnId) {
            const el = document.getElementById(btnId);
            if (!el) return;
            el.addEventListener('click', async (e) => {
                const txt = el.getAttribute('data-copy') || '';
                el.style.setProperty('--x', (e.offsetX || 8) + 'px');
                el.style.setProperty('--y', (e.offsetY || 8) + 'px');
                try {
                    await navigator.clipboard.writeText(txt);
                    showToast('Đã sao chép: ' + txt);
                } catch (_) {}
            });
        }
        bindCopy('copyAcc');
        bindCopy('copyRef');

        if (forcePing) forcePing.addEventListener('click', () => ping(true));

        setPendingUI();
        tickTimer();
        ping();
    })();
</script>
@endsection