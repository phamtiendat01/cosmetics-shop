<?php

use Illuminate\Support\Facades\Route;

// Public (User) controllers
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\CheckoutController;

// Shop (public) – tách namespace để tránh trùng Admin*
use App\Http\Controllers\Shop\CategoryController as ShopCategoryController;
use App\Http\Controllers\Shop\BrandController    as ShopBrandController;

// Admin controllers
use App\Http\Controllers\Admin\{
    DashboardController,
    HomepageController,
    ProductController as AdminProductController,
    OrderController   as AdminOrderController,
    CategoryController as AdminCategoryController,
    BrandController    as AdminBrandController,
    CustomerController as AdminCustomerController,
    CouponController   as AdminCouponController,
    BannerController   as AdminBannerController,
    SettingController  as AdminSettingController,
    AdminUserController,
    RoleController,
    ShippingCarrierController,
    ShippingZoneController,
    ShippingRateController,
};

/*
|--------------------------------------------------------------------------
| PUBLIC (Storefront)
|--------------------------------------------------------------------------
*/
// Trang chủ + Shop + Sale
Route::get('/',        [HomeController::class, 'index'])->name('home');
Route::get('/shop',    [ShopController::class, 'index'])->name('shop.index');
Route::get('/sale',    [ShopController::class, 'sale'])->name('shop.sale'); // tạo action sale() hoặc tạm return view

// Danh mục / Sản phẩm / Thương hiệu
Route::get('/c/{slug}', [ShopCategoryController::class, 'show'])->name('category.show');
Route::get('/p/{slug}', [ProductController::class,     'show'])->name('product.show');
Route::get('/brand/{slug}', [ShopBrandController::class, 'show'])->name('brand.show');
// Giỏ hàng (trang + API session)
Route::get('/cart', fn() => view('cart.index'))->name('cart.index');

// API giỏ hàng (SESSION JSON)
Route::prefix('cart')->as('cart.')->group(function () {
    Route::get('/json',     [CartController::class, 'index'])->name('json');   // đổi từ '/' -> '/json'
    Route::post('/',        [CartController::class, 'store'])->name('store');  // giữ nguyên POST /cart
    Route::patch('/{key}',  [CartController::class, 'update'])->name('update');
    Route::delete('/{key}', [CartController::class, 'destroy'])->name('destroy');
    Route::delete('/',      [CartController::class, 'clear'])->name('clear');
});


// Coupon
Route::post('/coupon/apply', [CouponController::class, 'apply'])->name('coupon.apply');
Route::delete('/coupon',     [CouponController::class, 'remove'])->name('coupon.remove');

// Checkout (trang + API preview + place)
Route::get('/checkout',           [CheckoutController::class, 'index'])->name('checkout.index');    // trang thanh toán
Route::get('/checkout/preview',   [CheckoutController::class, 'preview'])->name('checkout.preview'); // JSON tổng tiền
Route::post('/checkout',          [CheckoutController::class, 'place'])->name('checkout.place');

/*
|--------------------------------------------------------------------------
| AUTH (Login/Register/Password Reset)
|--------------------------------------------------------------------------
*/
// Guest only
Route::middleware('guest')->group(function () {
    Route::get('/login',     [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',    [AuthController::class, 'login']);
    Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // Quên mật khẩu (đồng bộ với view bạn đã gọi route('password.request')/('password.email')…)
    Route::get('/forgot-password',        [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password',       [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password',        [AuthController::class, 'resetPassword'])->name('password.update');
});

// Authenticated
Route::middleware('auth')->group(function () {
    // dashboard người dùng (sau login/register)
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // Account area (khớp cây thư mục account/* và các route đã dùng trong view)
    Route::prefix('account')->as('account.')->group(function () {
        // Nếu chưa có controller, bạn có thể tạm Route::view() và đổ dữ liệu sau
        Route::get('orders',    [\App\Http\Controllers\Account\OrderController::class,    'index'])->name('orders');
        Route::get('addresses', [\App\Http\Controllers\Account\AddressController::class,  'index'])->name('addresses');
        Route::get('wishlist',  [\App\Http\Controllers\Account\WishlistController::class, 'index'])->name('wishlist');
        Route::get('coupons',   [\App\Http\Controllers\Account\CouponController::class,   'index'])->name('coupons');
    });

    // logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| ADMIN (auth + role + permission)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super-admin|admin|staff'])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])
            ->middleware('permission:view dashboard')
            ->name('dashboard');

        // Homepage (CMS)
        Route::get('homepage',  [HomepageController::class, 'index'])
            ->middleware('permission:manage homepage')->name('homepage.index');
        Route::post('homepage', [HomepageController::class, 'store'])
            ->middleware('permission:manage homepage')->name('homepage.store');

        // Products
        Route::resource('products', AdminProductController::class)
            ->middleware('permission:manage products');

        // Orders
        Route::resource('orders', AdminOrderController::class)
            ->only(['index', 'show', 'update'])
            ->middleware('permission:manage orders');
        Route::patch('orders/bulk', [AdminOrderController::class, 'bulk'])
            ->middleware('permission:manage orders')->name('orders.bulk');

        // Categories
        Route::resource('categories', AdminCategoryController::class)
            ->middleware('permission:manage categories');
        Route::patch('categories/{category}/toggle', [AdminCategoryController::class, 'toggle'])
            ->middleware('permission:manage categories')->name('categories.toggle'); // PATCH cho “chuẩn REST”
        Route::post('categories/bulk', [AdminCategoryController::class, 'bulk'])
            ->middleware('permission:manage categories')->name('categories.bulk');

        // Brands
        Route::resource('brands', AdminBrandController::class)
            ->middleware('permission:manage brands');
        Route::patch('brands/{brand}/toggle', [AdminBrandController::class, 'toggle'])
            ->middleware('permission:manage brands')->name('brands.toggle'); // PATCH cho “chuẩn REST”
        Route::post('brands/bulk', [AdminBrandController::class, 'bulk'])
            ->middleware('permission:manage brands')->name('brands.bulk');

        // Customers
        Route::resource('customers', AdminCustomerController::class)
            ->middleware('permission:manage customers');
        Route::patch('customers/{customer}/toggle', [AdminCustomerController::class, 'toggle'])
            ->middleware('permission:manage customers')->name('customers.toggle'); // PATCH
        Route::post('customers/bulk', [AdminCustomerController::class, 'bulk'])
            ->middleware('permission:manage customers')->name('customers.bulk');

        // Coupons
        Route::get('coupons/targets', [AdminCouponController::class, 'targets'])
            ->middleware('permission:manage coupons')->name('coupons.targets');
        Route::resource('coupons', AdminCouponController::class)
            ->middleware('permission:manage coupons');

        // Banners
        Route::resource('banners', AdminBannerController::class)
            ->middleware('permission:manage banners');
        Route::patch('banners/{banner}/toggle', [AdminBannerController::class, 'toggle'])
            ->middleware('permission:manage banners')->name('banners.toggle');

        // Settings
        Route::get('settings',  [AdminSettingController::class, 'index'])
            ->middleware('permission:manage settings')->name('settings.index');
        Route::post('settings', [AdminSettingController::class, 'store'])
            ->middleware('permission:manage settings')->name('settings.store');

        // RBAC
        Route::resource('users', AdminUserController::class)
            ->middleware('permission:manage roles');
        Route::resource('roles', RoleController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('permission:manage roles');

        // Shipping
        Route::prefix('shipping')->as('shipping.')
            ->middleware('permission:manage shipping')
            ->group(function () {
                Route::resource('carriers', ShippingCarrierController::class);
                Route::resource('zones',    ShippingZoneController::class);
                Route::resource('rates',    ShippingRateController::class)
                    ->only(['index', 'store', 'update', 'destroy']);
            });
    });
