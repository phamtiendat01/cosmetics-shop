{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','Cosme House')</title>

    {{-- Tailwind --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fff1f5',
                            100: '#ffe4e9',
                            200: '#fecdd3',
                            300: '#fda4af',
                            400: '#fb7185',
                            500: '#f43f5e',
                            600: '#e11d48',
                            700: '#be123c',
                            800: '#9f1239',
                            900: '#881337'
                        },
                        ink: {
                            DEFAULT: '#111827',
                            soft: '#6b7280'
                        }
                    },
                    boxShadow: {
                        header: '0 2px 20px rgba(17,24,39,0.07)',
                        card: '0 8px 28px rgba(17,24,39,0.06)'
                    }
                }
            }
        }
    </script>

    {{-- Icons + Alpine --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none
        }

        .wave-card {
            position: relative;
            will-change: transform;
            transition: transform .18s ease, box-shadow .18s ease
        }

        .wave-card .shine {
            position: absolute;
            inset: 0;
            border-radius: 1rem;
            pointer-events: none;
            opacity: 0;
            transition: opacity .2s ease;
            mix-blend-mode: overlay;
            background: radial-gradient(300px circle at var(--mx, -100px) var(--my, -100px), rgba(255, 255, 255, .35), rgba(255, 255, 255, 0) 40%)
        }

        .wave-card:hover .shine {
            opacity: 1
        }

        html,
        body {
            max-width: 100vw
        }

        @supports (overflow: clip) {

            html,
            body {
                overflow-x: clip
            }
        }

        @supports not (overflow: clip) {

            html,
            body {
                overflow-x: hidden
            }
        }
    </style>

    {{-- Hover shine (nhẹ) --}}
    <script>
        (() => {
            if (window.__shineBound) return;
            window.__shineBound = true;
            let raf = 0;
            document.addEventListener('pointermove', (e) => {
                if (raf) return;
                raf = requestAnimationFrame(() => {
                    raf = 0;
                    const card = e.target.closest('.js-card');
                    if (!card) return;
                    const r = card.getBoundingClientRect();
                    card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
                    card.style.setProperty('--my', (e.clientY - r.top) + 'px');
                });
            }, {
                passive: true
            });
        })();
    </script>
</head>

<body class="bg-rose-50/40 text-ink overflow-x-clip">
    @php
    $headerCats = $headerCats ?? collect();
    $wishlistCount = (int)($wishlistCount ?? 0);
    $cartCount = (int)($cartCount ?? 0);

    // Helper: link theo tên route + fallback (tránh phải dùng FQCN dài)
    $link = function (string $name, string $fallback = '#') {
    return \Illuminate\Support\Facades\Route::has($name) ? route($name) : $fallback;
    };
    @endphp

    {{-- Top notice --}}
    <div class="w-full bg-ink text-white text-sm">
        <div class="max-w-7xl mx-auto px-4 py-2 flex items-center justify-between">
            <span>🎁 Miễn phí vận chuyển đơn từ 499K • Tích điểm thành viên</span>
            <a href="tel:19001234" class="opacity-80 hover:opacity-100"><i class="fa-solid fa-phone"></i> Hotline: 1900 1234</a>
        </div>
    </div>

    {{-- HEADER --}}
    <header id="siteHeader" class="sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-rose-100 overflow-visible">
        <div class="max-w-7xl mx-auto px-4 py-3 grid grid-cols-12 gap-4 items-center">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="col-span-12 sm:col-span-2 flex items-center gap-2 font-bold text-xl">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-brand-500 text-white">C</span>
                <span class="hidden sm:block">Cosme House</span>
            </a>

            {{-- Search --}}
            <form class="col-span-12 sm:col-span-6 order-last sm:order-none" action="{{ route('shop.index') }}" method="get">
                <div class="flex rounded-full border border-rose-200 bg-white overflow-hidden focus-within:ring-2 focus-within:ring-brand-400">
                    <input class="flex-1 px-4 py-2.5 outline-none text-sm" name="q" value="{{ request('q') }}" placeholder="Tìm sản phẩm, thương hiệu, vấn đề da…">
                    <button class="px-4 bg-brand-500 text-white text-sm font-medium"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </form>

            {{-- Actions (4 cột) --}}
            <div class="col-span-12 sm:col-span-3 flex items-center justify-start sm:justify-end gap-3">
                {{-- Account dropdown --}}
                <div class="relative z-[200]" x-data="{open:false}">
                    @auth
                    @php
                    $u = auth()->user();
                    $avatar = $u->avatar ?? null;
                    if ($avatar && !\Illuminate\Support\Str::startsWith($avatar, ['http','/storage'])) {
                    $avatar = asset('storage/'.$avatar);
                    }
                    @endphp

                    <button type="button" @click="open=!open"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-full hover:bg-rose-50 border border-rose-200">
                        @if($avatar)
                        <img src="{{ $avatar }}" class="w-8 h-8 rounded-full object-cover" alt="">
                        @else
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-brand-500 text-white">
                            {{ strtoupper(\Illuminate\Support\Str::substr($u->name ?? 'U',0,1)) }}
                        </span>
                        @endif
                        <span class="hidden md:block text-sm font-medium max-w-[150px] truncate">{{ $u->name }}</span>
                        <i class="fa-solid fa-chevron-down text-xs text-ink/60"></i>
                    </button>

                    <div x-show="open" x-transition.opacity x-cloak
                        @click.outside="open=false" @keydown.escape.window="open=false"
                        class="absolute right-0 mt-2 w-[320px] bg-white border border-rose-100 rounded-xl shadow-card py-2">
                        <div class="px-4 pb-2 text-sm">
                            <div class="text-ink/60">Xin chào,</div>
                            <div class="font-medium text-ink truncate">{{ $u->name }}</div>
                            <div class="text-xs text-ink/50 truncate">{{ $u->email }}</div>
                        </div>
                        <div class="my-2 border-t border-rose-100"></div>

                        <div class="px-2 py-1">
                            {{-- Tổng quan --}}
                            <a href="{{ $link('account.dashboard', $link('account.orders.index')) }}"
                                class="flex items-center gap-2 px-2 py-2 rounded hover:bg-rose-50">
                                <i class="fa-regular fa-user"></i><span>Tổng quan tài khoản</span>
                            </a>
                            {{-- Đơn hàng --}}
                            <a href="{{ $link('account.orders.index') }}"
                                class="flex items-center gap-2 px-2 py-2 rounded hover:bg-rose-50">
                                <i class="fa-solid fa-receipt"></i><span>Đơn hàng của tôi</span>
                            </a>
                            {{-- Yêu thích --}}
                            <a href="{{ $link('account.wishlist') }}"
                                class="flex items-center gap-2 px-2 py-2 rounded hover:bg-rose-50">
                                <i class="fa-regular fa-heart"></i><span>Yêu thích</span>
                            </a>
                            {{-- Giỏ hàng --}}
                            <a href="{{ $link('cart.index', url('/cart')) }}"
                                class="flex items-center gap-2 px-2 py-2 rounded hover:bg-rose-50">
                                <i class="fa-solid fa-bag-shopping"></i><span>Giỏ hàng</span>
                            </a>
                        </div>

                        <div class="my-2 border-t border-rose-100"></div>
                        <div class="px-2 py-1">
                            <a href="{{ $link('account.profile') }}" class="flex items-center gap-2 px-2 py-2 rounded hover:bg-rose-50">
                                <i class="fa-regular fa-id-card"></i><span>Hồ sơ cá nhân</span>
                            </a>
                            <a href="{{ $link('account.addresses.index') }}" class="flex items-center gap-2 px-2 py-2 rounded hover:bg-rose-50">
                                <i class="fa-regular fa-map"></i><span>Sổ địa chỉ</span>
                            </a>
                            <a href="{{ $link('account.coupons') }}" class="flex items-center gap-2 px-2 py-2 rounded hover:bg-rose-50">
                                <i class="fa-solid fa-ticket"></i><span>Mã giảm giá</span>
                            </a>

                            <a href="{{ $link('account.shipvouchers.index', url('/account/ship-vouchers')) }}"
                                class="flex items-center gap-2 px-2 py-2 rounded hover:bg-rose-50">
                                <i class="fa-solid fa-truck-fast"></i><span>Mã vận chuyển</span>
                            </a>

                            <a href="{{ $link('account.reviews') }}" class="flex items-center gap-2 px-2 py-2 rounded hover:bg-rose-50">
                                <i class="fa-regular fa-comment-dots"></i><span>Đánh giá của tôi</span>
                            </a>
                        </div>


                        <div class="my-2 border-t border-rose-100"></div>
                        <div class="px-2 py-1">
                            <a href="{{ $link('account.security') }}" class="flex items-center gap-2 px-2 py-2 rounded hover:bg-rose-50">
                                <i class="fa-solid fa-shield-halved"></i><span>Bảo mật & đăng nhập</span>
                            </a>
                            <div class="my-2 border-t border-rose-100"></div>
                            <div class="px-2 py-1">
                                {{-- Ưu đãi & tích điểm --}}
                                <a href="{{ $link('spin.index', url('/spin')) }}"
                                    class="flex items-center gap-2 px-2 py-2 rounded hover:bg-rose-50">
                                    <i class="fa-solid fa-dice"></i><span>Vòng quay may mắn</span>
                                </a>
                                <a href="{{ $link('game.mystery', url('/game/mystery')) }}"
                                    class="flex items-center gap-2 px-2 py-2 rounded hover:bg-rose-50">
                                    <i class="fa-solid fa-gift"></i><span>Hộp quà bí ẩn</span>
                                </a>

                                <a href="{{ $link('account.points.index', url('/account/points')) }}"
                                    class="flex items-center gap-2 px-2 py-2 rounded hover:bg-rose-50">
                                    <i class="fa-solid fa-coins"></i><span>Xu tích điểm</span>
                                </a>

                            </div>
                            @hasanyrole('super-admin|admin|staff')
                            <a href="{{ $link('admin.dashboard', '#') }}"
                                class="flex items-center gap-2 px-2 py-2 rounded hover:bg-rose-50 text-rose-700 font-medium">
                                <i class="fa-solid fa-gauge"></i><span>Vào trang Admin</span>
                            </a>
                            @endhasanyrole

                            @if (\Illuminate\Support\Facades\Route::has('logout'))
                            <form method="POST" action="{{ route('logout') }}">@csrf
                                <button type="submit" class="w-full text-left flex items-center gap-2 px-2 py-2 rounded hover:bg-rose-50">
                                    <i class="fa-solid fa-arrow-right-from-bracket"></i><span>Đăng xuất</span>
                                </button>
                            </form>
                            @else
                            <form method="POST" action="/logout">@csrf
                                <button type="submit" class="w-full text-left flex items-center gap-2 px-2 py-2 rounded hover:bg-rose-50">
                                    <i class="fa-solid fa-arrow-right-from-bracket"></i><span>Đăng xuất</span>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @else
                    {{-- Guest --}}
                    <button type="button" @click="open=!open"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-full hover:bg-rose-50 border border-rose-200">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-brand-500 text-white"><i class="fa-regular fa-user"></i></span>
                        <span class="hidden md:block text-sm font-medium">Tài khoản</span>
                        <i class="fa-solid fa-chevron-down text-xs text-ink/60"></i>
                    </button>
                    <div x-show="open" x-transition.opacity x-cloak
                        @click.outside="open=false" @keydown.escape.window="open=false"
                        class="absolute right-0 mt-2 w-64 bg-white border border-rose-100 rounded-xl shadow-card py-2">
                        <a href="{{ route('login') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-rose-50">
                            <i class="fa-solid fa-right-to-bracket"></i> Đăng nhập
                        </a>
                        <a href="{{ route('register') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-rose-50">
                            <i class="fa-regular fa-id-card"></i> Đăng ký
                        </a>
                        <div class="my-2 border-t border-rose-100"></div>
                        <a href="{{ $link('cart.index', url('/cart')) }}"
                            class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-rose-50">
                            <i class="fa-solid fa-bag-shopping"></i> Giỏ hàng
                        </a>
                    </div>
                    @endauth
                </div>

                {{-- Wishlist (badge bằng JS thuần) --}}
                <a id="jsWishlistIcon" href="{{ route('account.wishlist') }}" class="relative inline-flex items-center shrink-0">
                    <i class="fa-regular fa-heart text-lg"></i>
                    <span id="jsWishlistCount"
                        class="absolute -top-2 -right-2 min-w-[18px] h-[18px] px-1 text-[11px] leading-[18px] bg-red-500 text-white rounded-full text-center {{ $wishlistCount>0 ? '' : 'hidden' }}">
                        {{ $wishlistCount }}
                    </span>
                </a>

                {{-- Cart --}}
                <a id="jsCartIcon" href="{{ $link('cart.index', url('/cart')) }}" class="relative hover:text-brand-600 shrink-0">
                    <i class="fa-solid fa-bag-shopping text-lg"></i>
                    <span id="jsCartCount"
                        class="absolute -top-2 -right-2 min-w-[18px] h-[18px] px-1 text-[11px] leading-[18px] bg-brand-500 text-white rounded-full text-center {{ $cartCount>0 ? '' : 'hidden' }}">
                        {{ $cartCount }}
                    </span>
                </a>
            </div>
        </div>

        {{-- NAV: Mega-menu (không tràn, giữ dropdown con) --}}
        <nav class="border-t border-rose-100">
            <div class="max-w-7xl mx-auto px-4 flex items-center gap-4">
                {{-- (Giữ) Nút “Danh mục” flyout nếu bạn đang dùng --}}
                <div class="flex-shrink-0">
                    @include('components.header.category-flyout')
                </div>

                @php
                // CẮT CỨNG 6 danh mục cha để chắc chắn không tràn
                $topCats = ($megaTree->count() ? $megaTree : $headerCats)->take(6);
                @endphp

                {{-- Không dùng overflow-x ở wrapper để tránh clip dropdown --}}
                <div class="flex-1 min-w-0">
                    @include('components.header.mega-menu', ['tree' => $topCats])
                </div>

                {{-- Nút 🔥 Sale cố định bên phải --}}
                <a href="{{ route('shop.sale') }}"
                    class="flex-shrink-0 py-3 text-rose-600 font-semibold whitespace-nowrap {{ request()->routeIs('shop.sale') ? 'underline' : '' }}">
                    🔥 Sale
                </a>
            </div>
        </nav>



    </header>

    <main>@yield('content')</main>

    {{-- FOOTER --}}
    <footer class="mt-16 border-t border-rose-100 bg-white">
        <div class="max-w-7xl mx-auto px-4 py-12 grid grid-cols-2 sm:grid-cols-4 gap-8 text-sm">
            <div>
                <h4 class="font-semibold mb-3">Về Cosme House</h4>
                <ul class="space-y-2 text-ink/80">
                    <li><a href="#" class="hover:text-brand-600">Giới thiệu</a></li>
                    <li><a href="#" class="hover:text-brand-600">Chính sách bảo mật</a></li>
                    <li><a href="#" class="hover:text-brand-600">Điều khoản</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-3">Hỗ trợ</h4>
                <ul class="space-y-2 text-ink/80">
                    <li><a href="#" class="hover:text-brand-600">Chính sách giao hàng</a></li>
                    <li><a href="#" class="hover:text-brand-600">Đổi trả & hoàn tiền</a></li>
                    <li><a href="#" class="hover:text-brand-600">Hướng dẫn mua hàng</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-3">Liên hệ</h4>
                <p class="text-ink/80">Hotline: 1900 1234</p>
                <p class="text-ink/80">Email: support@cosme.house</p>
                <div class="flex gap-3 mt-3 text-lg">
                    <a href="#" class="hover:text-brand-600"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" class="hover:text-brand-600"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="hover:text-brand-600"><i class="fa-brands fa-tiktok"></i></a>
                </div>
            </div>
            <div>
                <h4 class="font-semibold mb-3">Đăng ký nhận tin</h4>
                <form class="flex gap-2">
                    <input class="flex-1 px-3 py-2 border border-rose-200 rounded-md outline-none focus:ring-2 focus:ring-brand-400" placeholder="Email của bạn">
                    <button class="px-3 py-2 bg-brand-600 text-white rounded-md">Đăng ký</button>
                </form>
            </div>
        </div>
        <div class="border-t border-rose-100 py-4 text-center text-xs text-ink/60">© {{ date('Y') }} Cosme House</div>
    </footer>

    {{-- Header shadow --}}
    <script>
        const header = document.getElementById('siteHeader');
        addEventListener('scroll', () => {
            header.style.boxShadow = window.scrollY > 12 ? 'var(--tw-shadow,0 2px 20px rgba(17,24,39,0.07))' : 'none';
        });
    </script>

    {{-- Alpine store: chỉ giữ cart --}}
    <script>
        document.addEventListener('alpine:init', function() {
            Alpine.store('cart', {
                open: false,
                count: @json((int)($cartCount ?? 0))
            });
        });
    </script>


    {{-- Endpoints --}}
    <script>
        window.R = Object.assign(window.R || {}, {
            wishlistToggle: "{{ route('wishlist.toggle') }}",
            wishlistCount: "{{ route('wishlist.count') }}",
            cartJson: "{{ route('cart.json') }}",
            cartStore: "{{ route('cart.store') }}",
            cartBase: "{{ url('/cart') }}"
        });
    </script>

    {{-- Wishlist badge + sync --}}
    <script>
        function setWishlistCount(n) {
            n = Number(n) || 0;
            const b = document.getElementById('jsWishlistCount');
            if (!b) return;
            b.textContent = n;
            b.classList.toggle('hidden', n <= 0);
        }
        fetch(window.R.wishlistCount, {
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(d => {
                if (d?.ok) setWishlistCount(d.count ?? 0);
            })
            .catch(() => {});
    </script>

    {{-- Heart fly + toggle --}}
    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        (() => {
            if (document.getElementById('flyHeartCSS')) return;
            const s = document.createElement('style');
            s.id = 'flyHeartCSS';
            s.textContent = '.fly-heart{position:fixed;left:0;top:0;pointer-events:none;z-index:99999;will-change:transform,opacity;filter:drop-shadow(0 8px 16px rgba(244,63,94,.35))}.fly-heart .dot{width:32px;height:32px;border-radius:9999px;background:#fff;display:grid;place-items:center;border:1px solid rgba(244,63,94,.3)}.fly-heart i{color:#e11d48;font-size:18px}';
            document.head.appendChild(s);
        })();

        function flyHeart(originEl, targetEl) {
            if (!originEl || !targetEl) return;
            const ob = originEl.getBoundingClientRect(),
                tb = targetEl.getBoundingClientRect();
            const n = document.createElement('div');
            n.className = 'fly-heart';
            n.innerHTML = '<div class="dot"><i class="fa-solid fa-heart"></i></div>';
            document.body.appendChild(n);
            const start = {
                    x: ob.left + ob.width / 2 - 16,
                    y: ob.top + ob.height / 2 - 16
                },
                mid = {
                    x: (ob.left + tb.left) / 2 - 16,
                    y: (ob.top + tb.top) / 2 - 120
                },
                end = {
                    x: tb.left + tb.width / 2 - 10,
                    y: tb.top + tb.height / 2 - 10
                };
            n.animate([{
                    transform: `translate(${start.x}px,${start.y}px) scale(1)`,
                    opacity: 1
                },
                {
                    transform: `translate(${mid.x}px,${mid.y}px) scale(.9)`,
                    opacity: .9
                },
                {
                    transform: `translate(${end.x}px,${end.y}px) scale(.2)`,
                    opacity: .2
                }
            ], {
                duration: 650,
                easing: 'cubic-bezier(.22,.61,.36,1)'
            }).onfinish = () => n.remove();
            document.getElementById('jsWishlistIcon')?.animate([{
                transform: 'scale(1)'
            }, {
                transform: 'scale(1.15)'
            }, {
                transform: 'scale(1)'
            }], {
                duration: 300,
                easing: 'ease-out'
            });
        }

        window.addEventListener('wishlist:toggle', async (e) => {
            const id = e.detail?.id;
            if (!id) return;
            if (e.detail?.added === true) flyHeart(e.target, document.getElementById('jsWishlistIcon'));
            const res = await fetch(window.R.wishlistToggle, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    product_id: id
                })
            });
            const data = await res.json().catch(() => null);
            if (!data?.ok) return;
            setWishlistCount(data.count ?? 0);
            if (data.action === 'added' && e.detail?.added !== true) flyHeart(e.target, document.getElementById('jsWishlistIcon'));
            localStorage.setItem('wishlist-sync', JSON.stringify({
                ts: Date.now(),
                count: data.count ?? 0
            }));
        });

        window.addEventListener('storage', (ev) => {
            if (ev.key !== 'wishlist-sync' || !ev.newValue) return;
            try {
                setWishlistCount(JSON.parse(ev.newValue).count ?? 0);
            } catch {}
        });
    </script>

    @stack('scripts')

    {{-- Cart Drawer + Quick View --}}
    <x-cart-drawer />
    <x-quick-view-modal />

    @include('shared.toast')
    <x-bot-widget />

    {{-- Bridge mở bot từ nút "Tư vấn" --}}
    <script>
        window.addEventListener('bot:open', (e) => {
            if (window.Bot && typeof window.Bot.open === 'function') {
                window.Bot.open(e.detail?.prompt || '');
            } else {
                window.__botPending = e.detail?.prompt || '';
            }
        });
    </script>

    {{-- Bridge thêm vào giỏ từ mọi nơi --}}
    <script>
        window.addEventListener('cart:add', async (e) => {
            const {
                product_id,
                variant_id = null,
                qty = 1,
                origin = null
            } = e.detail || {};
            if (!product_id) return;
            try {
                if (origin && typeof flyToCart === 'function') flyToCart(origin);
            } catch (_) {}
            const res = await fetch(window.R.cartStore, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    product_id,
                    variant_id,
                    qty
                })
            });
            const data = await res.json().catch(() => null);
            if (data?.ok) {
                const cnt = Number(data.count || 0);
                const b = document.getElementById('jsCartCount');
                if (b) {
                    b.textContent = cnt;
                    b.classList.toggle('hidden', cnt <= 0);
                }
                Alpine.store('cart').open = true;
                document.getElementById('jsCartDrawer')?.dispatchEvent(new CustomEvent('cart:refresh'));
            }
        });
    </script>

</body>

</html>