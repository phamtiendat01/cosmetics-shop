
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Cosme House'); ?></title>

    
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

    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    
    
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
    
    
    <script src="https://cdn.jsdelivr.net/npm/intersection-observer@0.12.2/intersection-observer.min.js"></script>

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
    <?php
$headerCats = $headerCats ?? collect();
$wishlistCount = (int) ($wishlistCount ?? 0);
$cartCount = (int) ($cartCount ?? 0);

// Helper: link theo tên route + fallback (tránh phải dùng FQCN dài)
$link = function (string $name, string $fallback = '#') {
    return \Illuminate\Support\Facades\Route::has($name) ? route($name) : $fallback;
};
    ?>

    
    <div class="w-full bg-ink text-white text-sm">
        <div class="max-w-7xl mx-auto px-4 py-2 flex items-center justify-between">
            <span>🎁 Miễn phí vận chuyển đơn từ 499K • Tích điểm thành viên</span>
            <a href="tel:19001234" class="opacity-80 hover:opacity-100"><i class="fa-solid fa-phone"></i> Hotline: 1900 1234</a>
        </div>
    </div>

    
    <header id="siteHeader" class="sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-rose-100 overflow-visible">
        <div class="max-w-7xl mx-auto px-4 py-3 grid grid-cols-12 gap-4 items-center">
            
            <a href="<?php echo e(route('home')); ?>" class="col-span-12 sm:col-span-2 flex items-center gap-2 font-bold text-xl">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-brand-500 text-white">C</span>
                <span class="hidden sm:block">Cosme House</span>
            </a>

            
            <form class="col-span-12 sm:col-span-6 order-last sm:order-none" action="<?php echo e(route('shop.index')); ?>" method="get">
                <div class="flex rounded-full border border-rose-200 bg-white overflow-hidden focus-within:ring-2 focus-within:ring-brand-400">
                    <input class="flex-1 px-4 py-2.5 outline-none text-sm" name="q" value="<?php echo e(request('q')); ?>" placeholder="Tìm sản phẩm, thương hiệu, vấn đề da…">
                    <button class="px-4 bg-brand-500 text-white text-sm font-medium"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </form>

            
            <div class="col-span-12 sm:col-span-3 flex items-center justify-start sm:justify-end gap-3">
                
                <div class="relative z-[200]" x-data="{open:false}">
                    <?php if(auth()->guard()->check()): ?>
                                        <?php
                        $u = auth()->user();
                        $avatar = $u->avatar ?? null;
                        if ($avatar && !\Illuminate\Support\Str::startsWith($avatar, ['http', '/storage'])) {
                            $avatar = asset('storage/' . $avatar);
                        }
                                        ?>

                                        <button type="button" @click="open=!open"
                                            class="flex items-center gap-2 px-3 py-1.5 rounded-full hover:bg-rose-50 border border-rose-200">
                                            <?php if($avatar): ?>
                                            <img src="<?php echo e($avatar); ?>" class="w-8 h-8 rounded-full object-cover" alt="">
                                            <?php else: ?>
                                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-brand-500 text-white">
                                                <?php echo e(strtoupper(\Illuminate\Support\Str::substr($u->name ?? 'U', 0, 1))); ?>

                                            </span>
                                            <?php endif; ?>
                                            <span class="hidden md:block text-sm font-medium max-w-[150px] truncate"><?php echo e($u->name); ?></span>
                                            <i class="fa-solid fa-chevron-down text-xs text-ink/60"></i>
                                        </button>

                                      <div x-show="open" x-transition.opacity x-cloak
     @click.outside="open=false" @keydown.escape.window="open=false"
     class="absolute right-0 mt-2 w-[740px] max-w-[calc(100vw-1rem)] bg-white border border-rose-100 rounded-xl shadow-card p-3">

    
    <div class="px-2 pb-2 text-sm">
        <div class="text-ink/60">Xin chào,</div>
        <div class="font-medium text-ink truncate"><?php echo e($u->name); ?></div>
        <div class="text-xs text-ink/50 truncate"><?php echo e($u->email); ?></div>
    </div>

    <div class="my-3 border-t border-rose-100"></div>

    
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-1 max-h-[70vh] overflow-y-auto no-scrollbar">

        
        <div class="col-span-2 lg:col-span-3 px-2 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-wider text-ink/50">
            Tổng quan & Mua sắm
        </div>

        
        <a href="<?php echo e($link('account.dashboard', $link('account.orders.index'))); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
            <i class="fa-regular fa-user"></i><span>Tổng quan tài khoản</span>
        </a>

        
        <a href="<?php echo e($link('account.orders.index')); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
            <i class="fa-solid fa-receipt"></i><span>Đơn hàng của tôi</span>
        </a>

        
        <a href="<?php echo e($link('account.wishlist')); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
            <i class="fa-regular fa-heart"></i><span>Yêu thích</span>
        </a>

        
        <a href="<?php echo e($link('cart.index', url('/cart'))); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
            <i class="fa-solid fa-bag-shopping"></i><span>Giỏ hàng</span>
        </a>

        
        <?php
            $walletBalance = auth()->check() ? optional(auth()->user()->wallet)->balance : null;
        ?>
        <a href="<?php echo e($link('account.wallet.show', url('/account/wallet'))); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
            <i class="fa-solid fa-wallet"></i>
            <span>Ví Cosme</span>
            <?php if(!is_null($walletBalance)): ?>
                <span class="ml-auto inline-flex items-center rounded-full bg-rose-600 text-white text-[11px] px-2 py-0.5">
                    <?php echo e('₫'.number_format($walletBalance)); ?>

                </span>
            <?php endif; ?>
        </a>

        
        <?php
            $href = \Illuminate\Support\Facades\Route::has('account.membership.show')
                ? route('account.membership.show')
                : url('/account/membership');
            $badgeMap = [
                'platinum' => 'bg-zinc-900',
                'gold'     => 'bg-amber-500',
                'silver'   => 'bg-slate-500',
                'member'   => 'bg-rose-500',
            ];
            $badgeClass = $badgeMap[$tierCode ?? 'member'] ?? 'bg-rose-500';
        ?>
        <a href="<?php echo e($href); ?>" class="group flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
            <i class="fa-solid fa-medal text-rose-500 group-hover:scale-110 transition"></i>
            <span>Hạng thành viên</span>
            <span class="ml-auto inline-flex items-center gap-1 rounded-full text-white text-[11px] px-2 py-0.5 <?php echo e($badgeClass); ?> ring-1 ring-black/5 shadow-sm">
                <i class="fa-solid fa-medal text-[10px] opacity-90"></i> <?php echo e($tierName ?? 'Member'); ?>

            </span>
        </a>

        <div class="col-span-2 lg:col-span-3 my-2 h-px bg-rose-100"></div>

        
        <div class="col-span-2 lg:col-span-3 px-2 pt-1 pb-1 text-[11px] font-semibold uppercase tracking-wider text-ink/50">
            Hồ sơ & Địa chỉ
        </div>

        <a href="<?php echo e($link('account.profile')); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
            <i class="fa-regular fa-id-card"></i><span>Hồ sơ cá nhân</span>
        </a>

        <a href="<?php echo e(route('account.skin_profile')); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
            <i class="fa-regular fa-face-smile-beam"></i><span>Hồ sơ làn da</span>
        </a>

        <a href="<?php echo e($link('account.addresses.index')); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
            <i class="fa-regular fa-map"></i><span>Sổ địa chỉ</span>
        </a>

        <a href="<?php echo e($link('account.coupons')); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
            <i class="fa-solid fa-ticket"></i><span>Mã giảm giá</span>
        </a>

        <a href="<?php echo e($link('account.shipvouchers.index', url('/account/ship-vouchers'))); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
            <i class="fa-solid fa-truck-fast"></i><span>Mã vận chuyển</span>
        </a>

        <a href="<?php echo e($link('account.reviews')); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
            <i class="fa-regular fa-comment-dots"></i><span>Đánh giá của tôi</span>
        </a>

        <div class="col-span-2 lg:col-span-3 my-2 h-px bg-rose-100"></div>

        
        <div class="col-span-2 lg:col-span-3 px-2 pt-1 pb-1 text-[11px] font-semibold uppercase tracking-wider text-ink/50">
            Ưu đãi & Tích điểm
        </div>

        <a href="<?php echo e($link('spin.index', url('/spin'))); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
            <i class="fa-solid fa-dice"></i><span>Vòng quay may mắn</span>
        </a>

        <a href="<?php echo e($link('game.mystery', url('/game/mystery'))); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
            <i class="fa-solid fa-gift"></i><span>Hộp quà bí ẩn</span>
        </a>

        <a href="<?php echo e($link('account.points.index', url('/account/points'))); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
            <i class="fa-solid fa-coins"></i><span>Xu tích điểm</span>
        </a>

        <div class="col-span-2 lg:col-span-3 my-2 h-px bg-rose-100"></div>

        
        <div class="col-span-2 lg:col-span-3 px-2 pt-1 pb-1 text-[11px] font-semibold uppercase tracking-wider text-ink/50">
            Bảo mật & Hệ thống
        </div>

        <a href="<?php echo e(route('blockchain.verify')); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
            <i class="fa-solid fa-qrcode"></i><span>Xác thực CosmeChain</span>
        </a>

        <a href="<?php echo e($link('account.security')); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
            <i class="fa-solid fa-shield-halved"></i><span>Bảo mật & đăng nhập</span>
        </a>

        <?php if (\Illuminate\Support\Facades\Blade::check('hasanyrole', 'super-admin|admin|staff')): ?>
        <a href="<?php echo e($link('admin.dashboard', '#')); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50 text-rose-700 font-medium">
            <i class="fa-solid fa-gauge"></i><span>Vào trang Admin</span>
        </a>
        <?php endif; ?>

        
        <div class="col-span-2 lg:col-span-3">
            <?php if(\Illuminate\Support\Facades\Route::has('logout')): ?>
                <form method="POST" action="<?php echo e(route('logout')); ?>"><?php echo csrf_field(); ?>
                    <button type="submit"
                        class="w-full text-left flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i><span>Đăng xuất</span>
                    </button>
                </form>
            <?php else: ?>
                <form method="POST" action="/logout"><?php echo csrf_field(); ?>
                    <button type="submit"
                        class="w-full text-left flex items-center gap-2 px-3 py-2 rounded hover:bg-rose-50">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i><span>Đăng xuất</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

                    <?php else: ?>
                    
                    <button type="button" @click="open=!open"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-full hover:bg-rose-50 border border-rose-200">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-brand-500 text-white"><i class="fa-regular fa-user"></i></span>
                        <span class="hidden md:block text-sm font-medium">Tài khoản</span>
                        <i class="fa-solid fa-chevron-down text-xs text-ink/60"></i>
                    </button>
                    <div x-show="open" x-transition.opacity x-cloak
                        @click.outside="open=false" @keydown.escape.window="open=false"
                        class="absolute right-0 mt-2 w-64 bg-white border border-rose-100 rounded-xl shadow-card py-2">
                        <a href="<?php echo e(route('login')); ?>" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-rose-50">
                            <i class="fa-solid fa-right-to-bracket"></i> Đăng nhập
                        </a>
                        <a href="<?php echo e(route('register')); ?>" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-rose-50">
                            <i class="fa-regular fa-id-card"></i> Đăng ký
                        </a>
                        <div class="my-2 border-t border-rose-100"></div>
                        <a href="<?php echo e($link('cart.index', url('/cart'))); ?>"
                            class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-rose-50">
                            <i class="fa-solid fa-bag-shopping"></i> Giỏ hàng
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                
                <a id="jsWishlistIcon" href="<?php echo e(route('account.wishlist')); ?>" class="relative inline-flex items-center shrink-0">
                    <i class="fa-regular fa-heart text-lg"></i>
                    <span id="jsWishlistCount"
                        class="absolute -top-2 -right-2 min-w-[18px] h-[18px] px-1 text-[11px] leading-[18px] bg-red-500 text-white rounded-full text-center <?php echo e($wishlistCount > 0 ? '' : 'hidden'); ?>">
                        <?php echo e($wishlistCount); ?>

                    </span>
                </a>
                
                <a id="jsSkinAI"
                    href="<?php echo e($link('skintest.index', url('/skin-test'))); ?>"
                    class="relative inline-flex items-center shrink-0 hover:text-brand-600"
                    aria-label="Quét da AI">
                    <i class="fa-solid fa-camera text-lg"></i>
                    
                    <span class="absolute -top-2 -right-3 text-[10px] px-1.5 h-[16px] leading-[16px]
                 rounded-full bg-brand-500 text-white">NEW</span>
                </a>
                
                <a id="jsCartIcon" href="<?php echo e($link('cart.index', url('/cart'))); ?>" class="relative hover:text-brand-600 shrink-0">
                    <i class="fa-solid fa-bag-shopping text-lg"></i>
                    <span id="jsCartCount"
                        class="absolute -top-2 -right-2 min-w-[18px] h-[18px] px-1 text-[11px] leading-[18px] bg-brand-500 text-white rounded-full text-center <?php echo e($cartCount > 0 ? '' : 'hidden'); ?>">
                        <?php echo e($cartCount); ?>

                    </span>
                </a>
            </div>
        </div>

        
        <nav class="border-t border-rose-100">
            <div class="max-w-7xl mx-auto px-4 flex items-center gap-4">
                
                <div class="flex-shrink-0">
                    <?php echo $__env->make('components.header.category-flyout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>

                <?php
// CẮT CỨNG 6 danh mục cha để chắc chắn không tràn
$topCats = ($megaTree->count() ? $megaTree : $headerCats)->take(6);
                ?>

                
                <div class="flex-1 min-w-0">
                    <?php echo $__env->make('components.header.mega-menu', ['tree' => $topCats], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>

                
                <a href="<?php echo e(route('shop.sale')); ?>"
                    class="flex-shrink-0 py-3 text-rose-600 font-semibold whitespace-nowrap <?php echo e(request()->routeIs('shop.sale') ? 'underline' : ''); ?>">
                    🔥 Sale
                </a>
            </div>
        </nav>



    </header>

    <main><?php echo $__env->yieldContent('content'); ?></main>

    
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
                <h4 class="font-semibold mb-3 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full border-2 border-rose-500/30 bg-gradient-to-br from-rose-50 to-pink-50 flex items-center justify-center">
                        <i class="fa-solid fa-headset text-rose-600 text-sm"></i>
                    </div>
                    <span>Thông tin liên hệ</span>
                </h4>
                <div class="space-y-2 text-ink/80">
                    <p class="flex items-center gap-2">
                        <i class="fa-solid fa-phone text-rose-600 text-xs"></i>
                        <span>Hotline: <a href="tel:19001234" class="hover:text-brand-600 font-medium">1900 1234</a> (8:00 - 22:00)</span>
                    </p>
                    <p class="flex items-center gap-2">
                        <i class="fa-solid fa-envelope text-rose-600 text-xs"></i>
                        <span>Email: <a href="mailto:support@cosme.house" class="hover:text-brand-600">support@cosme.house</a></span>
                    </p>
                </div>
                <div class="flex gap-3 mt-4 text-lg">
                    <a href="#" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-rose-100 flex items-center justify-center text-slate-600 hover:text-rose-600 transition"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-rose-100 flex items-center justify-center text-slate-600 hover:text-rose-600 transition"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-rose-100 flex items-center justify-center text-slate-600 hover:text-rose-600 transition"><i class="fa-brands fa-tiktok"></i></a>
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
        <div class="border-t border-rose-100 py-4 text-center text-xs text-ink/60">© <?php echo e(date('Y')); ?> Cosme House</div>
    </footer>

    
    <script>
        const header = document.getElementById('siteHeader');
        addEventListener('scroll', () => {
            header.style.boxShadow = window.scrollY > 12 ? 'var(--tw-shadow,0 2px 20px rgba(17,24,39,0.07))' : 'none';
        });
    </script>

    
    <script>
        document.addEventListener('alpine:init', function() {
            Alpine.store('cart', {
                open: false,
                count: <?php echo json_encode((int) ($cartCount ?? 0), 15, 512) ?>
            });
        });
    </script>


    
    <script>
        window.R = Object.assign(window.R || {}, {
            wishlistToggle: "<?php echo e(route('wishlist.toggle')); ?>",
            wishlistCount: "<?php echo e(route('wishlist.count')); ?>",
            cartJson: "<?php echo e(route('cart.json')); ?>",
            cartStore: "<?php echo e(route('cart.store')); ?>",
            cartBase: "<?php echo e(url('/cart')); ?>"
        });
    </script>

    
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

    <?php echo $__env->yieldPushContent('scripts'); ?>

    
    <?php if (isset($component)) { $__componentOriginalea42f4dd66f806b609eae8726a731031 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalea42f4dd66f806b609eae8726a731031 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.cart-drawer','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('cart-drawer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalea42f4dd66f806b609eae8726a731031)): ?>
<?php $attributes = $__attributesOriginalea42f4dd66f806b609eae8726a731031; ?>
<?php unset($__attributesOriginalea42f4dd66f806b609eae8726a731031); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalea42f4dd66f806b609eae8726a731031)): ?>
<?php $component = $__componentOriginalea42f4dd66f806b609eae8726a731031; ?>
<?php unset($__componentOriginalea42f4dd66f806b609eae8726a731031); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginalf7ffef54e17a12bddd902775db84cdc9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf7ffef54e17a12bddd902775db84cdc9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.quick-view-modal','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('quick-view-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf7ffef54e17a12bddd902775db84cdc9)): ?>
<?php $attributes = $__attributesOriginalf7ffef54e17a12bddd902775db84cdc9; ?>
<?php unset($__attributesOriginalf7ffef54e17a12bddd902775db84cdc9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf7ffef54e17a12bddd902775db84cdc9)): ?>
<?php $component = $__componentOriginalf7ffef54e17a12bddd902775db84cdc9; ?>
<?php unset($__componentOriginalf7ffef54e17a12bddd902775db84cdc9); ?>
<?php endif; ?>

    <?php echo $__env->make('shared.toast', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php if (isset($component)) { $__componentOriginalc070435beb60fd46d701c3dd1b5f41f5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc070435beb60fd46d701c3dd1b5f41f5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.bot-widget','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('bot-widget'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc070435beb60fd46d701c3dd1b5f41f5)): ?>
<?php $attributes = $__attributesOriginalc070435beb60fd46d701c3dd1b5f41f5; ?>
<?php unset($__attributesOriginalc070435beb60fd46d701c3dd1b5f41f5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc070435beb60fd46d701c3dd1b5f41f5)): ?>
<?php $component = $__componentOriginalc070435beb60fd46d701c3dd1b5f41f5; ?>
<?php unset($__componentOriginalc070435beb60fd46d701c3dd1b5f41f5); ?>
<?php endif; ?>

    
    <script>
        window.addEventListener('bot:open', (e) => {
            if (window.Bot && typeof window.Bot.open === 'function') {
                window.Bot.open(e.detail?.prompt || '');
            } else {
                window.__botPending = e.detail?.prompt || '';
            }
        });
    </script>

    
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
    <script>
        async function updateCartBadge() {
            try {
                const COUNT_URL = <?php echo json_encode(route('cart.count'), 15, 512) ?>; // <- an toàn dấu nháy
                const res = await fetch(COUNT_URL, {
                    credentials: 'same-origin'
                });
                const data = await res.json();
                const el = document.querySelector('#jsCartCount');
                if (el) {
                    el.textContent = data.count || 0;
                    el.classList.toggle('hidden', (data.count || 0) <= 0);
                }
                if (window.Alpine && Alpine.store('cart')) {
                    Alpine.store('cart').count = data.count || 0;
                }
            } catch (e) {}
        }
        document.addEventListener('DOMContentLoaded', updateCartBadge);
        document.addEventListener('bot:reply', updateCartBadge);
    </script>

    
    <?php if (isset($component)) { $__componentOriginal8008093895f86869991f557646a6cfb6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8008093895f86869991f557646a6cfb6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livechat.widget','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('livechat.widget'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8008093895f86869991f557646a6cfb6)): ?>
<?php $attributes = $__attributesOriginal8008093895f86869991f557646a6cfb6; ?>
<?php unset($__attributesOriginal8008093895f86869991f557646a6cfb6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8008093895f86869991f557646a6cfb6)): ?>
<?php $component = $__componentOriginal8008093895f86869991f557646a6cfb6; ?>
<?php unset($__componentOriginal8008093895f86869991f557646a6cfb6); ?>
<?php endif; ?>
    
    <?php echo $__env->make('partials.echo', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>

</html>
<?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/layouts/app.blade.php ENDPATH**/ ?>