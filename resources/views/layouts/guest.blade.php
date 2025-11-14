<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title','Cosme House')</title>

    {{-- Tailwind (giữ đúng palette như app) --}}
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

    {{-- Icons + Alpine (nếu trang guest cần dropdown đơn giản) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('styles')
</head>

<body class="bg-rose-50/40 text-ink min-h-dvh flex flex-col">
    {{-- Top notice (giống app, có thể ẩn nếu không cần) --}}
    <div class="w-full bg-ink text-white text-sm">
        <div class="max-w-md sm:max-w-lg md:max-w-2xl lg:max-w-3xl mx-auto px-4 py-2 flex items-center justify-between">
            <span>🎁 Miễn phí vận chuyển đơn từ 499K • Tích điểm thành viên</span>
            <a href="tel:19001234" class="opacity-80 hover:opacity-100">
                <i class="fa-solid fa-phone"></i> 1900 1234
            </a>
        </div>
    </div>

    {{-- HEADER rút gọn --}}
    <header class="bg-white/90 backdrop-blur border-b border-rose-100 shadow-header">
        <div class="max-w-md sm:max-w-lg md:max-w-2xl lg:max-w-3xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-xl">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-brand-500 text-white">C</span>
                <span class="hidden sm:block">Cosme House</span>
            </a>

            {{-- Link nhanh sang trang chủ / shop --}}
            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('home') }}" class="hover:text-brand-600 hidden sm:inline">Trang chủ</a>
                <a href="{{ route('shop.index') }}" class="hover:text-brand-600">Cửa hàng</a>
            </div>
        </div>
    </header>

    {{-- NỘI DUNG TRANG GUEST --}}
    <main class="flex-1">
        <div class="max-w-md sm:max-w-lg md:max-w-2xl lg:max-w-3xl mx-auto px-4 py-10">
            {{-- Card trung tâm để form đăng nhập/đăng ký --}}
            <div class="bg-white border border-rose-100 rounded-2xl shadow-card p-6 sm:p-8">
                {{-- Tiêu đề trang con (nếu có) --}}
                @hasSection('heading')
                <h1 class="text-xl font-semibold mb-1">@yield('heading')</h1>
                @hasSection('subheading')
                <p class="text-sm text-ink/60 mb-4">@yield('subheading')</p>
                @endif
                @endif

                {{-- Nội dung chính --}}
                @yield('content')
            </div>

            {{-- Link chuyển đổi giữa login/register --}}
            @hasSection('alt-action')
            <div class="mt-4 text-center text-sm text-ink/80">
                @yield('alt-action')
            </div>
            @endif
        </div>
    </main>

    {{-- FOOTER gọn --}}
    <footer class="mt-auto border-t border-rose-100 bg-white">
        <div class="max-w-md sm:max-w-lg md:max-w-2xl lg:max-w-3xl mx-auto px-4 py-8 grid grid-cols-2 gap-6 text-xs sm:text-sm">
            <div>
                <h4 class="font-semibold mb-2">Về Cosme House</h4>
                <ul class="space-y-1 sm:space-y-2 text-ink/80">
                    <li><a href="#" class="hover:text-brand-600">Giới thiệu</a></li>
                    <li><a href="#" class="hover:text-brand-600">Chính sách bảo mật</a></li>
                    <li><a href="#" class="hover:text-brand-600">Điều khoản</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-2">Liên hệ</h4>
                <p class="text-ink/80">Hotline: 1900 1234</p>
                <p class="text-ink/80">Email: support@cosme.house</p>
            </div>
        </div>
        <div class="border-t border-rose-100 py-3 text-center text-xs text-ink/60">
            © {{ date('Y') }} Cosme House
        </div>
    </footer>

    @stack('scripts')
</body>

</html>