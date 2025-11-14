@extends('layouts.app')
@section('title','Quét da AI — Cosme House')

@push('styles')
<style>
    .wand {
        display: inline-grid;
        place-items: center;
        width: 84px;
        height: 84px;
        border-radius: 9999px;
        border: 4px solid #fecdd3;
        border-top-color: #f43f5e;
        animation: spin 1s linear infinite
    }

    .wand i {
        font-size: 26px;
        color: #f43f5e;
        animation: pulse .9s ease-in-out infinite
    }

    @keyframes spin {
        to {
            transform: rotate(360deg)
        }
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1)
        }

        50% {
            transform: scale(1.15)
        }
    }
</style>
@endpush

@section('content')
<div x-data="skinTestPage()" x-init="init()" class="min-h-[70vh]">

    {{-- Header --}}
    <section class="max-w-7xl mx-auto px-4 pt-6 pb-2">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-brand-100 text-brand-700 text-xs">
                    <i class="fa-solid fa-bolt"></i> Beta · Miễn phí
                </div>
                <h1 class="text-[22px] font-bold mt-2">AI Skin Scan & Routine Builder</h1>
                <div class="text-[13px] text-ink/70">Tải 1–3 ảnh selfie, hệ thống phân tích dầu/khô/đỏ/mụn và gợi ý <b>routine 4 bước</b>.</div>
            </div>
            <a href="{{ route('skintest.camera') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-brand-600 text-white hover:bg-brand-700 shadow">
                <i class="fa-solid fa-camera"></i> Mở camera (chụp tối đa 3 ảnh)
            </a>
        </div>
    </section>

    {{-- WIZARD --}}
    <section class="max-w-7xl mx-auto px-4 pb-14">
        <div class="mx-auto max-w-4xl rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 overflow-hidden">

            {{-- Step bar --}}
            <div class="px-4 sm:px-6 py-3 border-b bg-gradient-to-r from-white to-rose-50">
                <div class="flex items-center gap-3">
                    <template x-for="(s,i) in steps" :key="i">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full grid place-items-center text-xs font-semibold"
                                :class="currentStep>=i ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-500'">
                                <span x-text="i+1"></span>
                            </div>
                            <div class="text-[12px] text-gray-600" x-text="s"></div>
                            <div class="w-10 h-px bg-gray-200 last:hidden" x-show="i<steps.length-1"></div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- STEP 0 --}}
            <div x-show="currentStep===0" x-transition.opacity class="p-5 sm:p-7 space-y-5">
                <ul class="grid sm:grid-cols-3 gap-3 text-[13px]">
                    <li class="flex items-center gap-2 p-3 rounded-xl bg-gray-50"><i class="fa-regular fa-sun text-brand-500"></i>Ánh sáng tự nhiên</li>
                    <li class="flex items-center gap-2 p-3 rounded-xl bg-gray-50"><i class="fa-solid fa-face-smile text-brand-500"></i>Không filter/trang điểm đậm</li>
                    <li class="flex items-center gap-2 p-3 rounded-xl bg-gray-50"><i class="fa-solid fa-user text-brand-500"></i>Lộ rõ toàn mặt</li>
                </ul>
                <label class="flex items-start gap-3 cursor-pointer text-[14px]">
                    <input type="checkbox" class="mt-1 accent-brand-600" x-model="consent">
                    <span>Tôi đồng ý cho phép phân tích ảnh theo <a href="{{ url('/privacy') }}" class="text-brand-600 hover:underline">Chính sách riêng tư</a>.</span>
                </label>
                <div class="flex items-center gap-4">
                    <button x-on:click="start()" :disabled="!consent"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand-600 text-white hover:bg-brand-700 shadow disabled:opacity-50">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Bắt đầu
                    </button>
                    <a href="{{ route('skintest.camera') }}" class="text-sm text-gray-600 hover:text-gray-800"><i class="fa-solid fa-camera"></i> Mở camera</a>
                </div>
            </div>

            {{-- STEP 1: UPLOAD --}}
            <div x-show="currentStep===1" x-transition.opacity class="p-5 sm:p-7">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <div id="dropzone"
                            x-on:dragover.prevent="drag=true" x-on:dragleave.prevent="drag=false" x-on:drop.prevent="onDrop($event)"
                            :class="drag ? 'ring-2 ring-brand-400 bg-brand-50/50' : 'ring-1 ring-gray-200'"
                            class="rounded-xl p-6 border-dashed text-center transition-all">
                            <div class="space-y-2">
                                <i class="fa-solid fa-cloud-arrow-up text-3xl text-brand-500"></i>
                                <div class="font-medium">Kéo & thả ảnh vào đây</div>
                                <div class="text-xs text-gray-500">JPG/PNG ≤ 4MB/ảnh, tối đa 3 ảnh</div>
                            </div>
                            <div class="mt-4">
                                <label class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-gray-900 text-white cursor-pointer">
                                    <i class="fa-solid fa-file-arrow-up"></i> Chọn ảnh
                                    <input type="file" multiple accept="image/*" class="hidden" x-on:change="onFileInput($event)">
                                </label>
                                <a href="{{ route('skintest.camera') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-brand-600 text-white ml-2">
                                    <i class="fa-solid fa-camera"></i> Mở camera
                                </a>
                            </div>
                        </div>
                        <template x-if="errors.length">
                            <div class="mt-3 text-sm text-red-600" x-html="errors.join('<br>')"></div>
                        </template>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600 mb-2">Ảnh đã chọn (tối đa 3):</div>
                        <div class="grid grid-cols-3 gap-3">
                            <template x-for="(p,idx) in previews" :key="idx">
                                <div class="relative group aspect-square rounded-lg overflow-hidden ring-1 ring-gray-200 bg-gray-50">
                                    <img :src="p.url" class="w-full h-full object-cover">
                                    <button class="absolute top-2 right-2 bg-white/90 rounded-full p-1 shadow" x-on:click="remove(idx)" title="Xoá">
                                        <i class="fa-solid fa-xmark text-gray-700"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <div class="pt-3 flex items-center justify-between">
                            <button class="px-4 py-2 rounded-full border border-gray-300 bg-white hover:bg-gray-50" x-on:click="back()">
                                <i class="fa-solid fa-arrow-left-long"></i> Quay lại
                            </button>
                            <button class="px-4 py-2 rounded-full bg-brand-600 text-white hover:bg-brand-700 shadow"
                                :class="previews.length===0 && 'opacity-50 pointer-events-none'" x-on:click="toReview()">
                                Tiếp tục <i class="fa-solid fa-arrow-right-long"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 2: REVIEW --}}
            <div x-show="currentStep===2" x-transition.opacity class="p-5 sm:p-7 space-y-6">
                <div class="grid grid-cols-3 gap-3">
                    <template x-for="p in previews" :key="p.url">
                        <div class="aspect-square overflow-hidden rounded-xl ring-1 ring-gray-200">
                            <img :src="p.url" class="w-full h-full object-cover">
                        </div>
                    </template>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="text-sm text-gray-600">Ngân sách:</div>
                    <template x-for="tier in budgetTiers" :key="tier.key">
                        <button x-on:click="budget = tier.value" class="px-3 py-1.5 rounded-full border"
                            :class="budget===tier.value ? 'bg-brand-600 text-white border-brand-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                            <span x-text="tier.label"></span>
                        </button>
                    </template>
                </div>
                <div class="flex items-center justify-between">
                    <button class="px-4 py-2 rounded-full border border-gray-300 bg-white hover:bg-gray-50" x-on:click="back()">
                        <i class="fa-solid fa-arrow-left-long"></i> Quay lại
                    </button>
                    <button class="px-4 py-2 rounded-full bg-brand-600 text-white hover:bg-brand-700 shadow" x-on:click="submit()">
                        Phân tích <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </button>
                </div>
            </div>

            {{-- STEP 3 --}}
            <div x-show="currentStep===3" class="p-2"></div>

            {{-- STEP 4: RESULT --}}
            <div x-show="currentStep===4" x-transition.opacity class="p-5 sm:p-7 space-y-8">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-gray-500 text-sm">Kết quả của bạn</div>
                        <div class="text-xl font-semibold">
                            <span class="inline-flex items-center gap-2">
                                <i class="fa-regular fa-face-smile-beam text-brand-600"></i>
                                <span x-text="prettySkinType(result.dominant_skin_type)"></span>
                            </span>
                        </div>
                    </div>
                    <button class="px-4 py-2 rounded-full border border-gray-300 bg-white hover:bg-gray-50" x-on:click="restart()">
                        <i class="fa-solid fa-rotate"></i> Quét lại
                    </button>
                </div>

                <div class="grid sm:grid-cols-4 gap-3">
                    <template x-for="m in metricsView" :key="m.key">
                        <div class="p-4 rounded-xl ring-1 ring-gray-200 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="font-medium text-gray-700" x-text="m.label"></div>
                                <div class="text-sm text-gray-500" x-text="Math.round((result.metrics?.[m.key]||0)*100)+'%'"></div>
                            </div>
                            <div class="mt-2 w-full h-2 rounded-full bg-gray-200 overflow-hidden">
                                <div class="h-2 bg-brand-500 rounded-full transition-all"
                                    :style="`width:${Math.round((result.metrics?.[m.key]||0)*100)}%`"></div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="space-y-6">
                    <template x-for="step in (Array.isArray(result.routine) ? result.routine : (result.routine?.routine || []))" :key="step.step">
                        <div class="p-4 sm:p-5 rounded-2xl ring-1 ring-gray-200 bg-white shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <div class="text-lg font-semibold">
                                    <i class="fa-solid fa-check-double text-brand-600"></i>
                                    <span class="ml-2" x-text="step.step"></span>
                                </div>
                                <div class="text-sm text-gray-500" x-text="step.reason"></div>
                            </div>
                            <div class="grid sm:grid-cols-3 gap-3">
                                <template x-for="p in (step.products || [])" :key="p.slug || p.id || p.name">
                                    <a :href="p.url || ('/product/'+(p.slug||''))"
                                        class="group p-3 rounded-xl ring-1 ring-gray-200 hover:ring-brand-300 bg-gray-50 hover:bg-white transition block">
                                        <div class="aspect-square rounded-lg overflow-hidden bg-white">
                                            <img :src="resolveImg(p)"
                                                x-on:error="$event.target.src='https://placehold.co/600x600?text=Cosme'"
                                                class="w-full h-full object-contain group-hover:scale-105 transition">
                                        </div>
                                        <div class="mt-2 font-medium text-gray-800 truncate" x-text="p.name || p.title"></div>
                                        <div class="text-brand-600 font-semibold" x-text="formatVND(p.price_min || p.price)"></div>
                                        <div class="text-xs text-gray-500 mt-1" x-show="!p.slug">Gợi ý tiêu chí – chưa có sản phẩm phù hợp</div>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button class="px-4 py-2 rounded-full bg-brand-600 text-white hover:bg-brand-700 shadow" x-on:click="addAllToCart()">
                        <i class="fa-solid fa-cart-plus"></i> Thêm tất cả vào giỏ
                    </button>
                    <button class="px-4 py-2 rounded-full border border-gray-300 bg-white hover:bg-gray-50" x-on:click="saveProfile()">
                        <i class="fa-regular fa-floppy-disk"></i> Lưu hồ sơ làn da
                    </button>
                    <button class="px-4 py-2 rounded-full text-gray-700 hover:bg-gray-100" @click="chatExpert()">
                        <i class="fa-regular fa-comments"></i> Chat với chuyên gia
                    </button>
                </div>

                <div class="text-xs text-gray-400">* Thông tin tham khảo, không thay thế tư vấn y khoa.</div>
            </div>
        </div>
    </section>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition.opacity
        class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-gray-900 text-white px-4 py-2 rounded-full shadow-2xl z-50">
        <span x-text="toast.message"></span>
    </div>

    {{-- Overlay --}}
    <div x-show="currentStep===3" x-transition.opacity
        class="fixed inset-0 z-[999] bg-black/60 backdrop-blur-sm grid place-items-center">
        <div class="flex flex-col items-center gap-3 select-none">
            <div class="wand"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
            <div class="text-white/90 font-medium">Đang phân tích ảnh…</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Bootstrap route/CSRF cho JS -->
<script>
    window.SK = {
        start: @json(route('skintest.start')),
        uploadTmpl: @json(route('skintest.upload', ['skinTest' => '__ID__'])),
        submitTmpl: @json(route('skintest.submit', ['skinTest' => '__ID__'])),
        showTmpl: @json(route('skintest.show', ['skinTest' => '__ID__'])),
        // ✅ dùng đúng tên route có dấu gạch ngang:
        saveProfile: @json(route('account.skin_profile.store')),
        viewProfile: @json(route('account.skin_profile')),
        csrf: document.querySelector('meta[name=csrf-token]')?.content
    };
</script>
<script>
    window.LIVECHAT = {
        start: @json(route('livechat.start')),
        msgTmpl: @json(route('livechat.messages.store', ['chat' => '__ID__'])),
        uiBase: @json(url('/livechat')),
        @php
        try {
            $uiTmpl = route('livechat.messages.index', ['chat' => '__ID__']);
        } catch (\Throwable $e) {
            $uiTmpl = null;
        }
        @endphp
        uiTmpl: @json($uiTmpl),
        csrf: document.querySelector('meta[name=csrf-token]')?.content
    };
</script>

</script>

@verbatim
<script>
    window.skinTestPage = function() {
        return {
            async chatExpert() {
                try {
                    if (!this.result || !this.result.metrics) {
                        this.showToast?.('Chưa có dữ liệu soi để gửi cho chuyên gia.');
                        return;
                    }

                    // 1) Lấy/khởi tạo chatId (tái dùng nếu có)
                    let chatId = localStorage.getItem('livechat_id');
                    if (!chatId) {
                        const res = await fetch(LIVECHAT.start, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': LIVECHAT.csrf
                            },
                            credentials: 'same-origin'
                        });
                        if (!res.ok) {
                            this.showToast('Vui lòng đăng nhập để chat.');
                            window.location.href = LIVECHAT.uiBase || '/livechat';
                            return;
                        }
                        const d = await res.json();
                        chatId = d.id ?? d.chat_id ?? d?.data?.id;
                        if (chatId) localStorage.setItem('livechat_id', chatId);
                    }

                    // 2) Lấy danh sách ảnh đã upload từ API show (nếu trả về)
                    let photos = [];
                    try {
                        const showRes = await fetch(window.SK.showTmpl.replace('__ID__', this.testId), {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (showRes.ok) {
                            const data = await showRes.json();
                            // mong đợi server trả về mảng URL tuyệt đối/ tương đối ở data.photos
                            photos = (Array.isArray(data?.photos) ? data.photos : []).slice(0, 4).map(u => {
                                if (!u) return null;
                                if (/^https?:\/\//i.test(u)) return u;
                                return location.origin + (u.startsWith('/') ? u : '/' + u);
                            }).filter(Boolean);
                        }
                    } catch (e) {
                        /* silent */
                    }

                    // 3) Soạn payload "đẹp" để widget render thành card
                    const pct = v => Math.round((v || 0) * 100);
                    const payload = {
                        kind: 'ai_scan',
                        test_id: this.testId,
                        profile_url: window.SK.viewProfile,
                        type: this.result?.dominant_skin_type || null,
                        metrics: {
                            oiliness: pct(this.result?.metrics?.oiliness),
                            dryness: pct(this.result?.metrics?.dryness),
                            redness: pct(this.result?.metrics?.redness),
                            acne: pct(this.result?.metrics?.acne_score),
                        },
                        created_at: new Date().toISOString(),
                        photos, // mảng URL ảnh
                    };

                    // 4) Đóng gói vào body với tiền tố đặc biệt để widget nhận diện
                    const body = '::ai_scan::' + btoa(unescape(encodeURIComponent(JSON.stringify(payload))));

                    // 5) Gửi tin
                    const msgUrl = LIVECHAT.msgTmpl.replace('__ID__', chatId);
                    await fetch(msgUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': LIVECHAT.csrf,
                            'Content-Type': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            body
                        }) // <-- API của bạn dùng key "body"
                    });

                    // 6) Mở widget & nạp lại messages
                    if (window.$livechat) {
                        window.$livechat.open = true;
                        window.$livechat.fetchMessages?.();
                    } else {
                        window.location.href = (LIVECHAT.uiBase || '/livechat');
                    }
                } catch (e) {
                    console.error(e);
                    this.showToast?.('Không mở được chat. Thử lại nhé.');
                }
            },

            // ===== BRIDGE CAMERA =====
            dataUrlToFile(dataUrl, filename) {
                const [meta, b64] = dataUrl.split(',');
                const mime = /data:(.*?);base64/.exec(meta)?.[1] || 'image/jpeg';
                const bin = atob(b64),
                    len = bin.length,
                    u8 = new Uint8Array(len);
                for (let i = 0; i < len; i++) u8[i] = bin.charCodeAt(i);
                return new File([u8], filename, {
                    type: mime
                });
            },
            addDataUrl(u) {
                if (this.files.length >= 3) return;
                const f = this.dataUrlToFile(u, `camera_${Date.now()}.jpg`);
                this.files.push(f);
                this.previews.push({
                    url: URL.createObjectURL(f)
                });
            },

            // ===== STATE =====
            steps: ['Bắt đầu', 'Ảnh', 'Xem lại', 'Phân tích', 'Kết quả'],
            currentStep: 0,
            consent: false,
            testId: null,
            publicToken: null,
            drag: false,
            previews: [],
            files: [],
            errors: [],
            budget: null,
            budgetTiers: [{
                    key: 'low',
                    label: 'Tiết kiệm',
                    value: 300000
                },
                {
                    key: 'mid',
                    label: 'Cân bằng',
                    value: 700000
                },
                {
                    key: 'hi',
                    label: 'Cao cấp',
                    value: 1500000
                },
            ],
            result: {
                metrics: {},
                routine: null,
                dominant_skin_type: null
            },
            toast: {
                show: false,
                message: ''
            },
            pollTimer: null,
            _processingStart: 0,

            metricsView: [{
                    key: 'oiliness',
                    label: 'Oiliness'
                },
                {
                    key: 'dryness',
                    label: 'Dryness'
                },
                {
                    key: 'redness',
                    label: 'Redness'
                },
                {
                    key: 'acne_score',
                    label: 'Acne'
                },
            ],

            // ===== INIT =====
            async init() {
                try {
                    const saved = JSON.parse(localStorage.getItem('skin_photos') || '[]');
                    if (Array.isArray(saved) && saved.length) {
                        saved.slice(0, 3).forEach(u => this.addDataUrl(u));
                        localStorage.removeItem('skin_photos');
                        this.currentStep = 1;
                        await this.ensureTestId();
                    }
                } catch {}
            },

            // ===== UTIL =====
            showToast(m) {
                this.toast.message = m;
                this.toast.show = true;
                setTimeout(() => this.toast.show = false, 2200);
            },
            back() {
                this.currentStep = Math.max(0, this.currentStep - 1);
            },
            restart() {
                this.currentStep = 0;
                this.testId = null;
                this.publicToken = null;
                this.previews = [];
                this.files = [];
                this.errors = [];
                this.result = {
                    metrics: {},
                    routine: null,
                    dominant_skin_type: null
                };
                if (this.pollTimer) {
                    clearInterval(this.pollTimer);
                    this.pollTimer = null;
                }
            },
            prettySkinType(v) {
                return ({
                    oily: 'Da thiên dầu',
                    dry: 'Da khô',
                    combination: 'Da hỗn hợp',
                    sensitive: 'Da nhạy cảm'
                })[v] || 'Đang xác định';
            },
            formatVND(n) {
                if (n == null) return '';
                return new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND',
                    maximumFractionDigits: 0
                }).format(n);
            },
            resolveImg(p) {
                const u = (p.image || p.thumbnail || '').trim();
                if (!u) return 'https://placehold.co/600x600?text=Cosme';
                if (/^https?:\/\//i.test(u) || u.startsWith('//')) return u;
                if (u.startsWith('/')) return u;
                if (u.startsWith('storage/')) return `${location.origin}/${u}`;
                return `${location.origin}/storage/${u}`;
            },

            // ===== BẢO ĐẢM testId =====
            async ensureTestId() {
                if (this.testId) return true;
                try {
                    this.consent = true;
                    const res = await fetch(window.SK.start, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.SK.csrf,
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                        },
                        body: new URLSearchParams({
                            consent: 1,
                            policy_version: 'v1'
                        })
                    });
                    if (!res.ok) throw await res.text();
                    const d = await res.json();
                    this.testId = d.id;
                    this.publicToken = d.public_token;
                    return true;
                } catch (e) {
                    console.error(e);
                    this.showToast('Không khởi tạo được phiên kiểm tra.');
                    return false;
                }
            },

            // ===== STEP 0 =====
            async start() {
                if (!this.consent) {
                    this.showToast('Vui lòng chấp nhận điều khoản');
                    return;
                }
                if (await this.ensureTestId()) {
                    this.currentStep = 1;
                    this.showToast('Bắt đầu – hãy tải/chụp 1–3 ảnh');
                }
            },

            // ===== STEP 1 =====
            onDrop(e) {
                this.drag = false;
                this.pushFiles(Array.from(e.dataTransfer.files || []));
            },
            onFileInput(e) {
                const fl = Array.from(e.target.files || []);
                this.pushFiles(fl);
                e.target.value = '';
            },
            pushFiles(fl) {
                const max = 3 - this.files.length;
                const picked = fl.slice(0, max);
                this.errors = [];
                picked.forEach(f => {
                    if (!/^image\//.test(f.type)) {
                        this.errors.push('Tệp không phải ảnh.');
                        return;
                    }
                    if (f.size > 4 * 1024 * 1024) {
                        this.errors.push('Ảnh vượt 4MB.');
                        return;
                    }
                    this.files.push(f);
                    this.previews.push({
                        url: URL.createObjectURL(f)
                    });
                });
            },
            remove(i) {
                this.previews.splice(i, 1);
                this.files.splice(i, 1);
            },
            toReview() {
                if (this.files.length === 0) {
                    this.showToast('Chưa có ảnh nào.');
                    return;
                }
                this.currentStep = 2;
            },

            // ===== STEP 2 → SUBMIT =====
            async submit() {
                if (!(await this.ensureTestId())) return;

                // 1) Upload
                try {
                    const fd = new FormData();
                    this.files.forEach(f => fd.append('photos[]', f));
                    fd.append('_method', 'PUT');
                    const urlUpload = window.SK.uploadTmpl.replace('__ID__', this.testId);
                    const r = await fetch(urlUpload, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.SK.csrf
                        },
                        body: fd
                    });
                    if (!r.ok) {
                        console.error('upload failed', await r.text());
                        throw new Error('upload_failed');
                    }
                } catch (e) {
                    this.showToast('Upload thất bại.');
                    console.error(e);
                    return;
                }

                // 2) Submit + overlay
                try {
                    const body = new URLSearchParams();
                    if (this.budget) body.set('budget', this.budget);
                    const urlSubmit = window.SK.submitTmpl.replace('__ID__', this.testId);
                    this.currentStep = 3;
                    this._processingStart = Date.now();

                    const sb = await fetch(urlSubmit, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': window.SK.csrf,
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                        },
                        body
                    });
                    if (!sb.ok) {
                        console.error('submit failed', await sb.text());
                        throw new Error('submit_failed');
                    }
                    const d = await sb.json();

                    const finish = (payload) => {
                        const delay = Math.max(0, 2000 - (Date.now() - this._processingStart));
                        setTimeout(() => this.onCompleted(payload), delay);
                    };

                    if (d?.status === 'completed' && d?.payload) {
                        finish({
                            dominant_skin_type: d.payload.dominant_skin_type,
                            metrics: d.payload.metrics,
                            recommendation_json: d.payload.recommendation_json
                        });
                    } else {
                        if (this.pollTimer) clearInterval(this.pollTimer);
                        this.pollTimer = setInterval(async () => {
                            try {
                                const urlShow = window.SK.showTmpl.replace('__ID__', this.testId);
                                const r = await fetch(urlShow, {
                                    headers: {
                                        'Accept': 'application/json'
                                    }
                                });
                                const x = await r.json();
                                if (x.status === 'completed') {
                                    finish({
                                        dominant_skin_type: x.dominant_skin_type,
                                        metrics: x.metrics,
                                        recommendation_json: x.routine
                                    });
                                    clearInterval(this.pollTimer);
                                    this.pollTimer = null;
                                } else if (x.status === 'failed') {
                                    this.onFailed();
                                }
                            } catch (e) {
                                console.error(e);
                            }
                        }, 1200);
                    }
                } catch (e) {
                    this.showToast('Không gửi được yêu cầu phân tích.');
                    console.error(e);
                }
            },

            onCompleted(payload) {
                this.result = {
                    dominant_skin_type: payload.dominant_skin_type,
                    metrics: payload.metrics || payload.metrics_json || {},
                    routine: Array.isArray(payload.recommendation_json) ? payload.recommendation_json : (payload.recommendation_json?.routine || payload.routine || [])
                };
                this.currentStep = 4;
            },
            onFailed() {
                if (this.pollTimer) {
                    clearInterval(this.pollTimer);
                    this.pollTimer = null;
                }
                this.currentStep = 2;
                this.showToast('Phân tích thất bại.');
            },

            // ===== STUB =====
            addAllToCart() {
                this.showToast('Đã thêm đề xuất vào giỏ (demo).');
            },
            saveProfile() {
                if (!this.result || !this.result.metrics) {
                    this.showToast('Chưa có kết quả để lưu.');
                    return;
                }
                (async () => {
                    try {
                        const r = await fetch(window.SK.saveProfile, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': window.SK.csrf
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                skin_test_id: this.testId,
                                dominant_skin_type: this.result.dominant_skin_type,
                                metrics: this.result.metrics
                            })
                        });
                        if (r.status === 401) {
                            this.showToast('Vui lòng đăng nhập để lưu hồ sơ.');
                            return;
                        }
                        if (!r.ok) throw await r.text();
                        this.showToast('Đã lưu hồ sơ làn da.');
                        setTimeout(() => window.location.href = window.SK.viewProfile, 600);
                    } catch (e) {
                        console.error(e);
                        this.showToast('Lưu thất bại. Kiểm tra lại cấu hình server.');
                    }
                })();
            }
        }
    }
</script>
@endverbatim
@endpush