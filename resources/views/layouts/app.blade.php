<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title','Cosme House')</title>

    {{-- Tailwind CDN --}}
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
    </style>

    @stack('styles')
</head>

<body class="bg-rose-50/40 text-ink">
    @php
    use Illuminate\Support\Str;
    $headerCats = $headerCats ?? collect();
    $wishlistCount = (int)($wishlistCount ?? 0);
    $cartCount = (int)($cartCount ?? 0);
    @endphp

    {{-- Top notice --}}
    <div class="w-full bg-ink text-white text-sm">
        <div class="max-w-7xl mx-auto px-4 py-2 flex items-center justify-between">
            <span>🎁 Miễn phí vận chuyển đơn từ 499K • Tích điểm thành viên</span>
            <a href="tel:19001234" class="opacity-80 hover:opacity-100">
                <i class="fa-solid fa-phone"></i> Hotline: 1900 1234
            </a>
        </div>
    </div>

    {{-- HEADER --}}
    <header id="siteHeader" class="sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-rose-100">
        <div class="max-w-7xl mx-auto px-4 py-3 grid grid-cols-12 gap-4 items-center">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="col-span-12 sm:col-span-2 flex items-center gap-2 font-bold text-xl">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-brand-500 text-white">C</span>
                <span class="hidden sm:block">Cosme House</span>
            </a>

            {{-- Search --}}
            <form class="col-span-12 sm:col-span-7 order-last sm:order-none" action="{{ route('shop.index') }}" method="get">
                <div class="flex rounded-full border border-rose-200 bg-white overflow-hidden focus-within:ring-2 focus-within:ring-brand-400">
                    <input class="flex-1 px-4 py-2.5 outline-none text-sm" name="q" value="{{ request('q') }}"
                        placeholder="Tìm sản phẩm, thương hiệu, vấn đề da…">
                    <button class="px-4 bg-brand-500 text-white text-sm font-medium">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
            </form>

            {{-- Actions --}}
            <div class="col-span-12 sm:col-span-3 flex items-center justify-end gap-4">
                {{-- Account dropdown --}}
                <div class="relative group">
                    @auth
                    @php $u = auth()->user(); @endphp
                    <button type="button"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-full hover:bg-rose-50 border border-rose-200">
                        @if(!empty($u->avatar))
                        <img src="{{ $u->avatar }}" class="w-8 h-8 rounded-full object-cover" alt="">
                        @else
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-brand-500 text-white">
                            {{ strtoupper(Str::substr($u->name ?? 'U',0,1)) }}
                        </span>
                        @endif
                        <span class="hidden md:block text-sm font-medium max-w-[150px] truncate">{{ $u->name }}</span>
                        <i class="fa-solid fa-chevron-down text-xs text-ink/60"></i>
                    </button>

                    <div class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition
                        absolute right-0 mt-2 w-72 bg-white border border-rose-100 rounded-xl shadow-card py-2">
                        <div class="px-4 pb-2 text-sm">
                            <div class="text-ink/60">Xin chào,</div>
                            <div class="font-medium text-ink truncate">{{ $u->name }}</div>
                        </div>
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-rose-50">
                            <i class="fa-regular fa-user"></i> Trang cá nhân
                        </a>
                        <a href="{{ route('account.orders') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-rose-50">
                            <i class="fa-solid fa-receipt"></i> Đơn hàng của tôi
                        </a>
                        @hasanyrole('super-admin|admin|staff')
                        <div class="my-2 border-t border-rose-100"></div>
                        <a href="{{ route('admin.dashboard') }}"
                            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">
                            <i class="fa-solid fa-shield-halved"></i> Vào trang Admin
                        </a>
                        @endhasanyrole
                        <div class="my-2 border-t border-rose-100"></div>
                        <form action="{{ route('logout') }}" method="POST">@csrf
                            <button class="w-full text-left flex items-center gap-2 px-4 py-2 text-sm hover:bg-rose-50">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất
                            </button>
                        </form>
                    </div>
                    @else
                    <button type="button"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-full hover:bg-rose-50 border border-rose-200">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-brand-500 text-white">
                            <i class="fa-regular fa-user"></i>
                        </span>
                        <span class="hidden md:block text-sm font-medium">Tài khoản</span>
                        <i class="fa-solid fa-chevron-down text-xs text-ink/60"></i>
                    </button>
                    <div class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition
                        absolute right-0 mt-2 w-64 bg-white border border-rose-100 rounded-xl shadow-card py-2">
                        <a href="{{ route('login') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-rose-50">
                            <i class="fa-regular fa-right-to-bracket"></i> Đăng nhập
                        </a>
                        <a href="{{ route('register') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-rose-50">
                            <i class="fa-regular fa-id-card"></i> Đăng ký
                        </a>
                    </div>
                    @endauth
                </div>

                {{-- Wishlist --}}
                <a href="{{ route('account.wishlist') }}" class="relative hover:text-brand-600">
                    <i class="fa-regular fa-heart text-lg"></i>
                    @if($wishlistCount > 0)
                    <span class="absolute -top-2 -right-2 text-[11px] bg-brand-500 text-white rounded-full px-1.5">
                        {{ $wishlistCount }}
                    </span>
                    @endif
                </a>

                {{-- Cart (có fallback nếu thiếu route để không nổ 500) --}}
                <a href="{{ \Illuminate\Support\Facades\Route::has('cart.index') ? route('cart.index') : url('/cart') }}"
                    class="relative hover:text-brand-600"
                    @click.prevent="$store.cart.open = true">
                    <i class="fa-solid fa-bag-shopping text-lg"></i>
                    @if($cartCount > 0)
                    <span class="absolute -top-2 -right-2 text-[11px] bg-brand-500 text-white rounded-full px-1.5">
                        {{ $cartCount }}
                    </span>
                    @endif
                </a>
            </div>
        </div>

        {{-- NAV: danh mục + Sale --}}
        <nav class="border-t border-rose-100">
            <div class="max-w-7xl mx-auto px-4 flex items-center gap-6 overflow-x-auto">
                @include('components.header.category-flyout')

                @foreach($headerCats as $cat)
                <a class="py-3 hover:text-brand-600 whitespace-nowrap {{ request()->is('c/'.$cat->slug.'*') ? 'text-brand-600 font-semibold' : '' }}"
                    href="{{ route('category.show', $cat->slug) }}">
                    {{ $cat->name }}
                </a>
                @endforeach

                <a class="py-3 text-rose-600 font-semibold whitespace-nowrap {{ request()->routeIs('shop.sale') ? 'underline' : '' }}"
                    href="{{ route('shop.sale') }}">🔥 Sale</a>
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
                    <input class="flex-1 px-3 py-2 border border-rose-200 rounded-md outline-none focus:ring-2 focus:ring-brand-400"
                        placeholder="Email của bạn">
                    <button class="px-3 py-2 bg-brand-600 text-white rounded-md">Đăng ký</button>
                </form>
            </div>
        </div>
        <div class="border-t border-rose-100 py-4 text-center text-xs text-ink/60">
            © {{ date('Y') }} Cosme House
        </div>
    </footer>

    {{-- Header shadow --}}
    <script>
        const header = document.getElementById('siteHeader');
        addEventListener('scroll', () => {
            header.style.boxShadow = window.scrollY > 12 ? 'var(--tw-shadow,0 2px 20px rgba(17,24,39,0.07))' : 'none';
        });
    </script>

    @stack('scripts')

    {{-- Cart Drawer + Quick View (component của bạn) --}}
    <x-cart-drawer />
    <x-quick-view-modal />

    {{-- Alpine stores (gộp 1 chỗ, không ghi đè) --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('cart', {
                open: false,
                count: {
                    {
                        $cartCount
                    }
                },
            });

            // Quick-View store
            Alpine.store('qv', {
                open: false,
                data: {},
                show(payload) {
                    const sale = payload.min && payload.compare && payload.compare > payload.min ?
                        Math.round(100 * (1 - payload.min / payload.compare)) : null;
                    this.data = {
                        id: payload.id,
                        name: payload.name,
                        image: payload.image,
                        url: payload.url,
                        short: payload.short || '',
                        price_fmt: payload.min ? new Intl.NumberFormat('vi-VN').format(payload.min) + '₫' : null,
                        compare_fmt: payload.compare ? new Intl.NumberFormat('vi-VN').format(payload.compare) + '₫' : null,
                        sale,
                        variants: (payload.variants || []).map((v, i) => ({
                            ...v,
                            price_fmt: new Intl.NumberFormat('vi-VN').format(v.price) + '₫',
                            _first: i === 0
                        }))
                    };
                    this.open = true;
                }
            });
        });

        // Lắng nghe event add-to-cart → gọi API của bạn, rồi mở drawer
        document.addEventListener('cart:add', async (e) => {
            const payload = e.detail || {};
            try {
                // TODO: gọi endpoint thật, ví dụ:
                // await fetch('{{ url('/cart') }}', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body: JSON.stringify({variant_id: payload.variant_id, qty: payload.qty || 1})});
                Alpine.store('cart').count++;
                Alpine.store('cart').open = true;
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        type: 'success',
                        text: 'Đã thêm vào giỏ hàng!'
                    }
                }));
            } catch (err) {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        type: 'error',
                        text: 'Thêm giỏ thất bại'
                    }
                }));
            }
        });
    </script>

    @include('shared.toast')
</body>

</html>