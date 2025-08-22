<?php

use Illuminate\Support\Facades\Route;

// Public (User)
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\AuthController;

// Admin
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

    // RBAC + Shipping
    AdminUserController,
    RoleController,
    ShippingCarrierController,
    ShippingZoneController,
    ShippingRateController,
};

/*
|--------------------------------------------------------------------------
| PUBLIC (User)
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/p/{slug}', [ProductController::class, 'show'])->name('product.show');

// Shop listing (+ Category + Sale)
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/c/{slug}', [ShopController::class, 'byCategory'])->name('category.show');
Route::get('/sale', [ShopController::class, 'sale'])->name('shop.sale');

// Auth — guest
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Auth — authenticated
Route::middleware('auth')->group(function () {
    // dashboard người dùng (sau login/register)
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    // logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| ADMIN (auth + role + permission)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super-admin|admin|staff'])
    ->prefix('admin')->as('admin.')
    ->group(function () {
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])
            ->middleware('permission:view dashboard')
            ->name('dashboard');

        // Homepage (CMS)
        Route::get('homepage', [HomepageController::class, 'index'])
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
            ->middleware('permission:manage orders')
            ->name('orders.bulk');

        // Categories
        Route::resource('categories', AdminCategoryController::class)
            ->middleware('permission:manage categories');
        Route::post('categories/{category}/toggle', [AdminCategoryController::class, 'toggle'])
            ->middleware('permission:manage categories')->name('categories.toggle');
        Route::post('categories/bulk', [AdminCategoryController::class, 'bulk'])
            ->middleware('permission:manage categories')->name('categories.bulk');

        // Brands
        Route::resource('brands', AdminBrandController::class)
            ->middleware('permission:manage brands');
        Route::post('brands/{brand}/toggle', [AdminBrandController::class, 'toggle'])
            ->middleware('permission:manage brands')->name('brands.toggle');
        Route::post('brands/bulk', [AdminBrandController::class, 'bulk'])
            ->middleware('permission:manage brands')->name('brands.bulk');

        // Customers (đã tách bạch với admin; controller lọc bỏ super-admin/admin/staff)
        Route::resource('customers', AdminCustomerController::class)
            ->middleware('permission:manage customers');
        Route::post('customers/{customer}/toggle', [AdminCustomerController::class, 'toggle'])
            ->middleware('permission:manage customers')->name('customers.toggle');
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
        Route::get('settings', [AdminSettingController::class, 'index'])
            ->middleware('permission:manage settings')->name('settings.index');
        Route::post('settings', [AdminSettingController::class, 'store'])
            ->middleware('permission:manage settings')
            ->name('settings.store');


        // RBAC: Quản trị viên + Vai trò & Quyền
        Route::resource('users', AdminUserController::class)
            ->middleware('permission:manage roles');
        Route::resource('roles', RoleController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('permission:manage roles');

        // Vận chuyển
        Route::prefix('shipping')->as('shipping.')
            ->middleware('permission:manage shipping')
            ->group(function () {
                Route::resource('carriers', ShippingCarrierController::class);
                Route::resource('zones',    ShippingZoneController::class);
                Route::resource('rates',    ShippingRateController::class)
                    ->only(['index', 'store', 'update', 'destroy']);
            });
    });
