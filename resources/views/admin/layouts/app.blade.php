<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title','Admin — Cosme House')</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- CSS custom -->
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <!-- Flowbite -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css" />
    <script defer src="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- TomSelect -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <!-- PWA-ish icons (nếu cần) -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('android-chrome-512x512.png') }}">

    <style>
        html {
            scroll-behavior: smooth;
        }

        :root {
            --ease-smooth: cubic-bezier(.22, .61, .36, 1);
        }

        /* Nav hover mượt */
        .nav-smooth {
            transition: transform .18s var(--ease-smooth), background-color .18s var(--ease-smooth), box-shadow .18s var(--ease-smooth);
        }

        .nav-smooth i {
            transition: transform .18s var(--ease-smooth), color .18s var(--ease-smooth);
        }

        .nav-smooth:hover {
            transform: translateX(4px);
        }

        .nav-smooth:hover i {
            transform: scale(1.1);
        }

        /* Button thu gọn kiểu “float” */
        .btn-float {
            transition: transform .22s var(--ease-smooth), box-shadow .22s var(--ease-smooth), background-color .22s var(--ease-smooth);
        }

        .btn-float:hover {
            transform: translateY(-2px) scale(1.08);
            box-shadow: 0 8px 20px rgba(2, 6, 23, .08);
        }

        .btn-float:active {
            transform: translateY(0) scale(.96);
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-900">
    <div id="layout" class="min-h-screen grid grid-cols-[240px_1fr] transition-[grid-template-columns] duration-200">

        {{-- Sidebar --}}
        <aside id="sidebar" class="relative bg-white border-r border-slate-200 p-4">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 font-semibold text-lg mb-2">
                <span class="w-8 h-8 rounded-lg bg-rose-600 text-white grid place-content-center">C</span>
                <span class="sidebar-label">Admin Cosme</span>
            </a>

            <nav class="mt-4 space-y-1 text-sm" id="sideNav">
                {{-- TỔNG QUAN --}}
                @can('view dashboard')
                <div class="px-3 pt-2 pb-1 text-[11px] uppercase tracking-wider text-slate-400 sidebar-section">Tổng quan</div>
                <a class="nav-item nav-smooth flex items-center px-3 py-2 rounded hover:bg-slate-100 {{ request()->routeIs('admin.dashboard') ? 'bg-slate-100 font-medium' : '' }}"
                    href="{{ route('admin.dashboard') }}">
                    <i class="fa-solid fa-chart-line mr-2"></i> <span class="sidebar-label">Tổng quan</span>
                </a>
                @endcan

                {{-- BÁN HÀNG --}}
                @canany(['manage orders','manage customers'])
                <div class="px-3 pt-3 pb-1 text-[11px] uppercase tracking-wider text-slate-400 sidebar-section">Bán hàng</div>

                @can('manage orders')
                <a class="nav-item nav-smooth flex items-center px-3 py-2 rounded hover:bg-slate-100 {{ request()->routeIs('admin.orders.*') ? 'bg-slate-100 font-medium' : '' }}"
                    href="{{ route('admin.orders.index') }}">
                    <i class="fa-solid fa-receipt mr-2"></i> <span class="sidebar-label">Đơn hàng</span>
                </a>
                @endcan

                @can('manage customers')
                <a class="nav-item nav-smooth flex items-center px-3 py-2 rounded hover:bg-slate-100 {{ request()->routeIs('admin.customers.*') ? 'bg-slate-100 font-medium' : '' }}"
                    href="{{ route('admin.customers.index') }}">
                    <i class="fa-solid fa-user-group mr-2"></i> <span class="sidebar-label">Khách hàng</span>
                </a>
                @endcan
                @endcanany

                {{-- CATALOG --}}
                @canany(['manage products','manage categories','manage brands'])
                <div class="px-3 pt-3 pb-1 text-[11px] uppercase tracking-wider text-slate-400 sidebar-section">Catalog</div>

                @can('manage products')
                <a class="nav-item nav-smooth flex items-center px-3 py-2 rounded hover:bg-slate-100 {{ request()->routeIs('admin.products.*') ? 'bg-slate-100 font-medium' : '' }}"
                    href="{{ route('admin.products.index') }}">
                    <i class="fa-solid fa-box mr-2"></i> <span class="sidebar-label">Sản phẩm</span>
                </a>
                @endcan

                @can('manage categories')
                <a class="nav-item nav-smooth flex items-center px-3 py-2 rounded hover:bg-slate-100 {{ request()->routeIs('admin.categories.*') ? 'bg-slate-100 font-medium' : '' }}"
                    href="{{ route('admin.categories.index') }}">
                    <i class="fa-solid fa-list mr-2"></i> <span class="sidebar-label">Danh mục</span>
                </a>
                @endcan

                @can('manage brands')
                <a class="nav-item nav-smooth flex items-center px-3 py-2 rounded hover:bg-slate-100 {{ request()->routeIs('admin.brands.*') ? 'bg-slate-100 font-medium' : '' }}"
                    href="{{ route('admin.brands.index') }}">
                    <i class="fa-solid fa-copyright mr-2"></i> <span class="sidebar-label">Thương hiệu</span>
                </a>
                @endcan
                @endcanany

                {{-- TIẾP THỊ --}}
                @can('manage coupons')
                <div class="px-3 pt-3 pb-1 text-[11px] uppercase tracking-wider text-slate-400 sidebar-section">Tiếp thị</div>
                <a class="nav-item nav-smooth flex items-center px-3 py-2 rounded hover:bg-slate-100 {{ request()->routeIs('admin.coupons.*') ? 'bg-slate-100 font-medium' : '' }}"
                    href="{{ route('admin.coupons.index') }}">
                    <i class="fa-solid fa-ticket mr-2"></i> <span class="sidebar-label">Mã giảm giá</span>
                </a>
                @endcan

                {{-- NỘI DUNG --}}
                @can('manage banners')
                <div class="px-3 pt-3 pb-1 text-[11px] uppercase tracking-wider text-slate-400 sidebar-section">Nội dung</div>
                <a class="nav-item nav-smooth flex items-center px-3 py-2 rounded hover:bg-slate-100 {{ request()->routeIs('admin.banners.*') ? 'bg-slate-100 font-medium' : '' }}"
                    href="{{ route('admin.banners.index') }}">
                    <i class="fa-solid fa-image mr-2"></i> <span class="sidebar-label">Banner</span>
                </a>
                @endcan

                {{-- VẬN CHUYỂN --}}
                @can('manage shipping')
                <div class="px-3 pt-3 pb-1 text-[11px] uppercase tracking-wider text-slate-400 sidebar-section">Vận chuyển</div>
                <a class="nav-item nav-smooth flex items-center px-3 py-2 rounded hover:bg-slate-100 {{ request()->routeIs('admin.shipping.carriers.*') ? 'bg-slate-100 font-medium' : '' }}"
                    href="{{ route('admin.shipping.carriers.index') }}">
                    <i class="fa-solid fa-truck mr-2"></i> <span class="sidebar-label">Đơn vị vận chuyển</span>
                </a>
                @endcan

                {{-- HỆ THỐNG --}}
                @canany(['manage settings','manage roles'])
                <div class="px-3 pt-3 pb-1 text-[11px] uppercase tracking-wider text-slate-400 sidebar-section">Hệ thống</div>

                @can('manage settings')
                <a class="nav-item nav-smooth flex items-center px-3 py-2 rounded hover:bg-slate-100 {{ request()->routeIs('admin.settings.*') ? 'bg-slate-100 font-medium' : '' }}"
                    href="{{ route('admin.settings.index') }}">
                    <i class="fa-solid fa-gear mr-2"></i> <span class="sidebar-label">Cài đặt</span>
                </a>
                @endcan

                @can('manage roles')
                <a class="nav-item nav-smooth flex items-center px-3 py-2 rounded hover:bg-slate-100 {{ request()->routeIs('admin.users.*') ? 'bg-slate-100 font-medium' : '' }}"
                    href="{{ route('admin.users.index') }}">
                    <i class="fa-solid fa-user-shield mr-2"></i> <span class="sidebar-label">Quản trị viên</span>
                </a>
                <a class="nav-item nav-smooth flex items-center px-3 py-2 rounded hover:bg-slate-100 {{ request()->routeIs('admin.roles.*') ? 'bg-slate-100 font-medium' : '' }}"
                    href="{{ route('admin.roles.index') }}">
                    <i class="fa-solid fa-key mr-2"></i> <span class="sidebar-label">Vai trò & Quyền</span>
                </a>
                @endcan
                @endcanany

                {{-- Nút thu gọn dưới "Vai trò & Quyền" --}}
                <div class="pt-4 px-3">
                    <div class="flex justify-center">
                        <button id="sidebarToggle" type="button"
                            class="btn-float w-10 h-10 rounded-full bg-slate-100 hover:bg-slate-200 border border-slate-200
                                       grid place-content-center text-slate-700 shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-300"
                            aria-label="Thu gọn / Mở rộng sidebar" title="Thu gọn / Mở rộng">
                            <i id="sidebarToggleIcon" class="fa-solid fa-angle-left text-lg"></i>
                        </button>
                    </div>
                </div>
            </nav>
        </aside>

        {{-- Main --}}
        <div>
            <header class="bg-white border-b border-slate-200 p-4 flex items-center justify-between">
                <div class="font-semibold">@yield('title','Tổng quan')</div>
                <div class="text-sm text-slate-600">👤 Admin</div>
            </header>
            <main class="p-6">@yield('content')</main>
        </div>
    </div>

    <script>
        // Ẩn alert tự động
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.alert[data-auto-dismiss]').forEach(el => {
                const ms = parseInt(el.dataset.autoDismiss || '3000', 10);
                setTimeout(() => {
                    el.classList.add('alert--hide');
                    setTimeout(() => el.remove(), 400);
                }, ms);
            });
        });

        // Thu gọn / mở rộng sidebar + đổi hướng icon
        (function() {
            const layout = document.getElementById('layout');
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('sidebarToggle');
            const icon = document.getElementById('sidebarToggleIcon');
            const labels = sidebar.querySelectorAll('.sidebar-label');
            const sections = sidebar.querySelectorAll('.sidebar-section');
            const navItems = sidebar.querySelectorAll('.nav-item');
            const icons = sidebar.querySelectorAll('.nav-item i');

            function apply(collapsed) {
                layout.classList.toggle('grid-cols-[240px_1fr]', !collapsed);
                layout.classList.toggle('grid-cols-[64px_1fr]', collapsed);

                labels.forEach(el => el.classList.toggle('hidden', collapsed));
                sections.forEach(el => el.classList.toggle('hidden', collapsed));

                navItems.forEach(a => a.classList.toggle('justify-center', collapsed));
                icons.forEach(i => {
                    i.classList.toggle('mr-2', !collapsed);
                    i.classList.toggle('mr-0', collapsed);
                });

                icon.classList.toggle('fa-angle-left', !collapsed);
                icon.classList.toggle('fa-angle-right', collapsed);

                try {
                    localStorage.setItem('admin.sidebarCollapsed', collapsed ? '1' : '0');
                } catch (e) {}
            }

            let collapsed = (localStorage.getItem('admin.sidebarCollapsed') === '1');
            apply(collapsed);

            if (toggle) {
                toggle.addEventListener('click', () => {
                    collapsed = !collapsed;
                    apply(collapsed);
                });
            }
        })();
    </script>

    @stack('scripts')
</body>

</html>