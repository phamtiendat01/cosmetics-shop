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
use App\Http\Controllers\BotController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SpinController;
use App\Http\Controllers\ShippingVoucherController; // ⟵ thêm dòng này
use App\Http\Controllers\PayOSCheckoutController;
use App\Http\Controllers\PayOSWebhookController;
use App\Http\Controllers\Api\ProductTryOnController;
use App\Http\Controllers\SkinTestController; // ⟵ thêm dòng này
use App\Http\Controllers\LiveChatController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\BlockchainVerificationController;


// Account controllers (user area)
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\WishlistController as AccountWishlistController;
use App\Http\Controllers\Account\CouponController as AccountCouponController;
use App\Http\Controllers\Account\ReviewController as AccountReviewController;
use App\Http\Controllers\Account\DashboardController as AccountDashboardController;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Account\SecurityController;
use App\Http\Controllers\Account\PointsController as AccountPointsController;
use App\Http\Controllers\Account\MysteryBoxController;
use App\Http\Controllers\Account\ShipVoucherController;
use App\Http\Controllers\Account\SkinProfileController; // ⟵ thêm dòng này
use App\Http\Controllers\Auth\FacebookAuthController;
use App\Http\Controllers\Account\ReturnController as AccountReturnController;
use App\Http\Controllers\Account\WalletController as AccountWalletController;
use App\Http\Controllers\Account\MembershipController as AccountMembershipController;



// Shop (public)
use App\Http\Controllers\Shop\CategoryController as ShopCategoryController;
use App\Http\Controllers\Shop\BrandController    as ShopBrandController;
use App\Http\Controllers\OrderReviewController as FrontOrderReviewController;
use App\Http\Controllers\Support\ChatController as SupportChatController;
use App\Http\Controllers\Support\MessageController;
use App\Http\Controllers\Admin\Support\ChatController as AdminSupportChatController;

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
    ShippingVoucherController as AdminShippingVoucherController,
    BannerController   as AdminBannerController,
    SettingController  as AdminSettingController,
    ReviewController   as AdminReviewController,
    AdminUserController,
    RoleController,
    ShippingCarrierController,
    ShippingZoneController,
    ShippingRateController,
    OrderReturnController,
    PulseController,
};

use Illuminate\Http\Request;
use App\Models\Order;

/*
|--------------------------------------------------------------------------
| PUBLIC (Storefront)
|--------------------------------------------------------------------------
*/

Route::get('/',        [HomeController::class, 'index'])->name('home');
Route::get('/shop',    [ShopController::class, 'index'])->name('shop.index');
Route::get('/sale',    [ShopController::class, 'sale'])->name('shop.sale');

// Danh mục / Sản phẩm / Thương hiệu
Route::get('/c/{slug}',     [ShopCategoryController::class, 'show'])->name('category.show');
Route::get('/p/{slug}',     [ProductController::class,     'show'])->name('product.show');
Route::get('/brand/{slug}', [ShopBrandController::class,   'show'])->name('brand.show');
// Blockchain Verification (Public - không cần auth)
Route::get('/verify', [\App\Http\Controllers\BlockchainVerificationController::class, 'show'])
    ->name('blockchain.verify');
Route::post('/verify', [\App\Http\Controllers\BlockchainVerificationController::class, 'verify'])
    ->name('blockchain.verify.submit');
Route::get('/verify/{qrCode}', [\App\Http\Controllers\BlockchainVerificationController::class, 'verify'])
    ->name('blockchain.verify.qr');
Route::get('/verify/hash/{hash}', [\App\Http\Controllers\BlockchainVerificationController::class, 'verifyByHash'])
    ->name('blockchain.verify.hash');

// QR Code Download (Public)
Route::get('/qr-code/{qrCode}/download', [\App\Http\Controllers\BlockchainQRCodeController::class, 'download'])
    ->name('blockchain.qr.download');
Route::get('/qr-code/{qrCode}/view', [\App\Http\Controllers\BlockchainQRCodeController::class, 'view'])
    ->name('blockchain.qr.view');
// Lưu đánh giá sản phẩm (từ trang chi tiết)
Route::post('/p/{product}/reviews', [ProductReviewController::class, 'store'])->name('reviews.store');

// Giỏ hàng (trang + API session)
Route::get('/cart', fn() => view('cart.index'))->name('cart.index');
// Try-on API
Route::get('/products/{product}/tryon/shades', [ProductTryOnController::class, 'shades']);
Route::get('/tryon/assets', [ProductTryOnController::class, 'assets']);
Route::post('/tryon/sessions', [ProductTryOnController::class, 'storeSession'])->middleware('throttle:60,1');
//
Route::get('/livechat', [LiveChatController::class, 'index'])->name('livechat.index');   // UI shell
Route::get('/livechat/{chat}', [LiveChatController::class, 'show'])->name('livechat.show'); // UI shell (open chatId)

// Chat bot
Route::get('/bot/tools', [BotController::class, 'getTools'])->name('bot.tools');
Route::post('/bot/chat', [BotController::class, 'chat'])->name('bot.chat');
Route::prefix('livechat')->as('livechat.')->middleware(['web', 'throttle:30,1'])->group(function () {
    Route::post('/start', [SupportChatController::class, 'start'])->name('start');  // ✅ sửa ở đây
    Route::get('/{chat}/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/{chat}/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/unread-count', [MessageController::class, 'unreadCount'])->middleware('auth')->name('unread');
});



// API giỏ hàng (SESSION JSON)
Route::prefix('cart')->as('cart.')->group(function () {
    Route::get('/count',  [CartController::class, 'count'])->name('count');
    Route::get('/json',     [CartController::class, 'index'])->name('json');
    Route::post('/',        [CartController::class, 'store'])->name('store');
    Route::patch('/{key}',  [CartController::class, 'update'])->name('update');
    Route::delete('/{key}', [CartController::class, 'destroy'])->name('destroy');
    Route::delete('/',      [CartController::class, 'clear'])->name('clear');
});

// API wishlist
Route::prefix('wishlist')->as('wishlist.')->group(function () {
    Route::get('/count',   [AccountWishlistController::class, 'count'])->name('count');
    Route::post('/toggle', [AccountWishlistController::class, 'toggle'])->name('toggle');
});

// Coupon
Route::post('/coupon/apply', [CouponController::class, 'apply'])->name('coupon.apply');
Route::delete('/coupon',     [CouponController::class, 'remove'])->name('coupon.remove');
Route::get('/coupon/mine',   [CouponController::class, 'mine'])->name('coupon.mine');

// Checkout
Route::get('/checkout',  [CheckoutController::class, 'show'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'place'])->name('checkout.place');

// Payment callbacks
Route::get('/payment/vnpay/return', [PaymentController::class, 'vnpayReturn'])->name('payment.vnpay.return');
Route::get('/payment/momo/return',  [PaymentController::class, 'momoReturn'])->name('payment.momo.return');
Route::post('/payment/momo/ipn',    [PaymentController::class, 'momoIpn'])->name('payment.momo.ipn');
Route::post('/payment/bank/webhook', [PaymentController::class, 'bankWebhook'])->name('payment.bank.webhook');
Route::get('/payment/vietqr/{order}',        [PaymentController::class, 'vietqrShow'])->name('payment.vietqr.show');
Route::get('/payment/vietqr/{order}/check',  [PaymentController::class, 'vietqrCheck'])->name('payment.vietqr.check');
// Payos
Route::get('/checkout/vietqr/{order}/payos', [PayOSCheckoutController::class, 'showQR'])
    ->name('checkout.vietqr.payos');

// Return/Cancel từ cổng payOS -> quay về chi tiết đơn
Route::get('/checkout/payos/return', [PayOSCheckoutController::class, 'return'])
    ->name('payos.return');
Route::get('/checkout/payos/cancel', [PayOSCheckoutController::class, 'cancel'])
    ->name('payos.cancel');

// Webhook (prod dùng ngrok / domain public)
Route::post('/payos/webhook', [PayOSWebhookController::class, 'handle'])
    ->name('payos.webhook');

// API để trang QR polling trạng thái thanh toán
Route::get('/orders/{order}/payment-status', [PayOSCheckoutController::class, 'status'])
    ->name('orders.payment.status');

// API: Poll trạng thái đơn cho VietQR
Route::get('/api/order-status', function (Request $r) {
    $code = $r->query('code');
    abort_if(!$code, 404);
    $o = Order::where('code', $code)->firstOrFail();
    return ['payment_status' => $o->payment_status, 'status' => $o->status];
})->name('api.order.status');

// OAuth với Google
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
    ->name('oauth.google.redirect');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('oauth.google.callback');
// OAuth với Facebook
Route::get('auth/facebook/redirect', [FacebookAuthController::class, 'redirect'])
    ->name('auth.facebook.redirect');

Route::get('auth/facebook/callback', [FacebookAuthController::class, 'callback'])
    ->name('auth.facebook.callback');
/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login',     [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',    [AuthController::class, 'login'])->middleware('throttle:login');

    Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/forgot-password',        [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password',       [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password',        [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/spin', 'spin.index')->name('spin.index');
    Route::get('/spin/config', [SpinController::class, 'config'])->name('spin.config');
    Route::post('/spin',       [SpinController::class, 'spin'])->name('spin.make');
    Route::post('/spin/save',  [SpinController::class, 'save'])->name('spin.save');
    Route::get('/game/spin', fn() => redirect()->route('spin.index'))->name('game.spin');
    // Mystery Box
    Route::get('/game/mystery', [MysteryBoxController::class, 'index'])->name('game.mystery');
    Route::get('/mystery/config', [MysteryBoxController::class, 'config'])->name('mystery.config');
    Route::post('/mystery/play',  [MysteryBoxController::class, 'play'])->name('mystery.play');
    Route::post('/mystery/save/{log}', [MysteryBoxController::class, 'save'])->name('mystery.save'); // ⟵ thêm
    // Checkout: Áp mã vận chuyển
    Route::get('/ship-vouchers', fn() => redirect()->route('account.shipvouchers.index'))
        ->name('shipvouchers.index.redirect');
    Route::post('/ship/apply', [ShippingVoucherController::class, 'apply'])->name('ship.apply');
    Route::delete('/ship',     [ShippingVoucherController::class, 'remove'])->name('ship.remove');

    // >>> Thêm API trả về các mã ship của user (JSON) <<<
    Route::get('/ship/mine',   [ShippingVoucherController::class, 'mine'])->name('ship.mine');
});

Route::middleware('auth')->get('/shipping/quote', function (Request $r) {
    $r->validate(['address_id' => 'required|integer']);
    $addr = \App\Models\UserAddress::where('user_id', auth()->id())
        ->where('id', $r->integer('address_id'))
        ->firstOrFail();

    $items = session('cart.items', []);
    $subtotal = 0;
    foreach ($items as $it) {
        $subtotal += (int)($it['price'] ?? 0) * (int)($it['qty'] ?? 1);
    }

    $q = \App\Services\Shipping\DistanceEstimator::estimateFee($addr->lat, $addr->lng, (int)$subtotal);
    session(['cart.shipping_fee' => (int)($q['fee'] ?? 0)]);

    return response()->json(['ok' => true, 'fee' => (int)($q['fee'] ?? 0), 'km' => $q['km'] ?? null]);
})->name('shipping.quote');
// Trang giao diện (view)
Route::get('/skin-test', fn() => view('skin_test.index'))
    ->name('skintest.index');
Route::get('/skin-test/camera', fn() => view('skin_test.camera'))->name('skintest.camera');


// API cho Skin Test
Route::middleware(['throttle:skintest'])
    ->prefix('skintest')
    ->name('skintest.')
    ->group(function () {
        Route::post('start', [SkinTestController::class, 'start'])->name('start');

        // CHO PHÉP CẢ PUT lẫn POST (để upload FormData dễ)
        Route::match(['put', 'post'], '{skinTest}/photos', [SkinTestController::class, 'upload'])->name('upload');

        Route::post('{skinTest}/submit', [SkinTestController::class, 'submit'])->name('submit');
        Route::get('{skinTest}', [SkinTestController::class, 'show'])->name('show');
    });

Route::middleware('auth')->group(function () {
    // Alias tương thích cho code cũ gọi route('dashboard')
    Route::get('/dashboard', fn() => redirect()->route('account.dashboard'))->name('dashboard');

    // ========= ACCOUNT (USER AREA) =========
    // Dùng scopeBindings để tự ràng buộc {item} thuộc {order}
    Route::prefix('account')->as('account.')->scopeBindings()->group(function () {
        // Dashboard
        Route::get('/', [AccountDashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard', fn() => redirect()->route('account.dashboard'));

        // Points
        Route::get('points', [AccountPointsController::class, 'index'])->name('points.index');
        Route::post('points/redeem', [AccountPointsController::class, 'redeem'])->name('points.redeem');

        // Orders
        Route::get('orders',            [AccountOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}',    [AccountOrderController::class, 'show'])
            ->whereNumber('order')->name('orders.show');
        Route::post('/orders/{order}/cancel', [AccountOrderController::class, 'cancel'])
            ->name('orders.cancel');
        Route::get('/orders/{order}/returns/create', [AccountReturnController::class, 'create'])
            ->name('returns.create');
        Route::post('/orders/{order}/returns', [AccountReturnController::class, 'store'])
            ->name('returns.store');
        // Wallet (xem lịch sử giao dịch, rút tiền)
        Route::get('wallet', [AccountWalletController::class, 'show'])
            ->name('wallet.show');
        // Membership
        Route::get('membership', [AccountMembershipController::class, 'show'])
            ->name('membership.show');
        // ⭐ Viết đánh giá cho từng item trong đơn (đặt ở account – không đặt ở admin)
        Route::get(
            'orders/{order}/items/{item}/review',
            [FrontOrderReviewController::class, 'create']
        )->whereNumber('order')->whereNumber('item')
            ->name('order-items.reviews.create');

        Route::post(
            'orders/{order}/items/{item}/review',
            [FrontOrderReviewController::class, 'store']
        )->whereNumber('order')->whereNumber('item')
            ->name('order-items.reviews.store');

        // Profile
        Route::get('profile',  [ProfileController::class, 'index'])->name('profile');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        // Hồ sơ làn da
        Route::get('skin-profile',  [\App\Http\Controllers\Account\SkinProfileController::class, 'show'])
            ->name('skin_profile');               // account.skin_profile

        Route::post('skin-profile', [\App\Http\Controllers\Account\SkinProfileController::class, 'store'])
            ->name('skin_profile.store');


        // Sổ địa chỉ
        Route::get('addresses',            [AddressController::class, 'index'])->name('addresses.index');
        Route::post('addresses',           [AddressController::class, 'store'])->name('addresses.store');
        Route::patch('addresses/{id}',     [AddressController::class, 'update'])->name('addresses.update');
        Route::delete('addresses/{id}',    [AddressController::class, 'destroy'])->name('addresses.destroy');
        Route::post('addresses/{id}/default', [AddressController::class, 'makeDefault'])->name('addresses.default');

        // Coupons
        Route::get('coupons', [AccountCouponController::class, 'index'])->name('coupons');

        // Mã vận chuyển (ví user)
        Route::get('ship-vouchers', [\App\Http\Controllers\Account\ShipVoucherController::class, 'index'])
            ->name('shipvouchers.index');

        // My reviews list (sửa/xoá review cá nhân)
        Route::get('reviews',             [AccountReviewController::class, 'index'])->name('reviews');
        Route::patch('reviews/{review}',  [AccountReviewController::class, 'update'])->name('reviews.update');
        Route::delete('reviews/{review}', [AccountReviewController::class, 'destroy'])->name('reviews.destroy');

        // Wishlist
        Route::get('wishlist', [AccountWishlistController::class, 'index'])->name('wishlist');

        // Security
        Route::get('security', [SecurityController::class, 'index'])->name('security');
        Route::post('security/password', [SecurityController::class, 'updatePassword'])->name('security.password');
        Route::post('security/email',    [SecurityController::class, 'updateEmail'])->name('security.email');
        Route::post('security/sessions/logout-others', [SecurityController::class, 'logoutOthers'])->name('security.sessions.logout-others');
    });

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

        // Eager-binding cho orders ở admin
        Route::bind('admin_order', function ($value) {
            return Order::query()
                ->with(['items.product', 'user'])
                ->findOrFail((int) $value);
        });

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])
            ->middleware('permission:view dashboard')
            ->name('dashboard');

        // Inventory
        Route::post(
            '/variants/{variant}/inventory/adjust',
            [\App\Http\Controllers\Admin\InventoryController::class, 'adjust']
        )->name('variants.inventory.adjust');

        // Homepage (CMS)
        Route::get('homepage',  [HomepageController::class, 'index'])
            ->middleware('permission:manage homepage')->name('homepage.index');
        Route::post('homepage', [HomepageController::class, 'store'])
            ->middleware('permission:manage homepage')->name('homepage.store');

        // Products
        Route::resource('products', AdminProductController::class)
            ->middleware('permission:manage products');

        // CosmeBot Management
        Route::prefix('bot')->as('bot.')
            ->middleware('permission:manage products')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\BotController::class, 'index'])
                    ->name('index');
                Route::get('intents', [\App\Http\Controllers\Admin\BotController::class, 'intents'])
                    ->name('intents');
                Route::post('intents', [\App\Http\Controllers\Admin\BotController::class, 'intentStore'])
                    ->name('intents.store');
                Route::get('tools', [\App\Http\Controllers\Admin\BotController::class, 'tools'])
                    ->name('tools');
                Route::post('tools', [\App\Http\Controllers\Admin\BotController::class, 'toolStore'])
                    ->name('tools.store');
                Route::get('conversations', [\App\Http\Controllers\Admin\BotController::class, 'conversations'])
                    ->name('conversations');
                Route::get('conversations/{conversation}', [\App\Http\Controllers\Admin\BotController::class, 'conversation'])
                    ->name('conversation');
                Route::get('analytics', [\App\Http\Controllers\Admin\BotController::class, 'analytics'])
                    ->name('analytics');
            });

        // Blockchain Management
        Route::prefix('blockchain')->as('blockchain.')
            ->middleware('permission:manage products')
            ->group(function () {
                Route::get('certificates', [\App\Http\Controllers\Admin\BlockchainController::class, 'certificates'])
                    ->name('certificates');
                Route::get('qr-codes', [\App\Http\Controllers\Admin\BlockchainController::class, 'qrCodes'])
                    ->name('qr-codes');
                Route::get('verifications', [\App\Http\Controllers\Admin\BlockchainController::class, 'verifications'])
                    ->name('verifications');
                Route::get('statistics', [\App\Http\Controllers\Admin\BlockchainController::class, 'statistics'])
                    ->name('statistics');

                // Product Recall
                Route::get('recall', [\App\Http\Controllers\Admin\BlockchainRecallController::class, 'create'])
                    ->name('recall.create');
                Route::post('recall', [\App\Http\Controllers\Admin\BlockchainRecallController::class, 'store'])
                    ->name('recall.store');
            });

        // Orders (admin)
        Route::resource('orders', AdminOrderController::class)
            ->only(['index', 'show', 'update'])
            ->parameters(['orders' => 'admin_order'])
            ->middleware('permission:manage orders');
        Route::get('orders/{admin_order}/print-qr-codes', [AdminOrderController::class, 'printQRCodes'])
            ->name('orders.print-qr-codes')
            ->middleware('permission:manage orders');
        Route::patch('orders/bulk', [AdminOrderController::class, 'bulk'])
            ->middleware('permission:manage orders')->name('orders.bulk');
        Route::post('orders/{admin_order}/cancel', [AdminOrderController::class, 'cancel'])
            ->middleware('permission:manage orders')
            ->name('orders.cancel');
        Route::get('order-returns', [OrderReturnController::class, 'index'])->name('order_returns.index');
        Route::get('order-returns/{return}', [OrderReturnController::class, 'show'])->name('order_returns.show');
        Route::post('order-returns/{return}/approve', [OrderReturnController::class, 'approve'])->name('order_returns.approve');
        Route::post('order-returns/{return}/receive', [OrderReturnController::class, 'receive'])->name('order_returns.receive'); // kho xác nhận SL
        Route::post('order-returns/{return}/refund', [OrderReturnController::class, 'refund'])->name('order_returns.refund');   // thực hiện hoàn tiền
        // Live chat console cho staff
        Route::get('support/chats', [AdminSupportChatController::class, 'index'])
            ->name('support.chats.index');
        Route::get('support', fn() => redirect()->route('admin.support.chats.index'))
            ->name('support.index');
        // Categories
        Route::resource('categories', AdminCategoryController::class)
            ->middleware('permission:manage categories');
        Route::match(['patch', 'post'], 'categories/{category}/toggle', [AdminCategoryController::class, 'toggle'])
            ->middleware('permission:manage categories')->name('categories.toggle');
        Route::post('categories/bulk', [AdminCategoryController::class, 'bulk'])
            ->middleware('permission:manage categories')->name('categories.bulk');

        // Brands
        Route::resource('brands', AdminBrandController::class)
            ->middleware('permission:manage brands');
        Route::match(['patch', 'post'], 'brands/{brand}/toggle', [AdminBrandController::class, 'toggle'])
            ->middleware('permission:manage brands')->name('brands.toggle');
        Route::post('brands/bulk', [AdminBrandController::class, 'bulk'])
            ->middleware('permission:manage brands')->name('brands.bulk');

        // Customers
        Route::resource('customers', \App\Http\Controllers\Admin\CustomerController::class)
            ->middleware('permission:manage customers');
        Route::match(['patch', 'post'], 'customers/{customer}/toggle', [\App\Http\Controllers\Admin\CustomerController::class, 'toggle'])
            ->middleware('permission:manage customers')->name('customers.toggle');
        Route::post('customers/bulk', [\App\Http\Controllers\Admin\CustomerController::class, 'bulk'])
            ->middleware('permission:manage customers')->name('customers.bulk');

        // Coupons
        Route::get('coupons/targets', [AdminCouponController::class, 'targets'])
            ->middleware('permission:manage coupons')->name('coupons.targets');
        Route::resource('coupons', AdminCouponController::class)
            ->middleware('permission:manage coupons');
        Route::match(
            ['patch', 'post'],
            'coupons/{coupon}/toggle',
            [\App\Http\Controllers\Admin\CouponController::class, 'toggle']
        )->name('coupons.toggle')->middleware('permission:manage coupons');

        // Shipping Vouchers (Mã vận chuyển)
        Route::resource('shipvouchers', AdminShippingVoucherController::class)
            ->parameters(['shipvouchers' => 'shipvoucher'])
            ->except(['show'])
            ->middleware('permission:manage shipping vouchers');

        Route::patch('shipvouchers/{shipvoucher}/toggle', [AdminShippingVoucherController::class, 'toggle'])
            ->name('shipvouchers.toggle')
            ->middleware('permission:manage shipping vouchers');



        // Banners
        Route::resource('banners', AdminBannerController::class)
            ->middleware('permission:manage banners');
        Route::match(['patch', 'post'], 'banners/{banner}/toggle', [AdminBannerController::class, 'toggle'])
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

        // Reviews (moderation)
        Route::prefix('reviews')->as('reviews.')
            ->middleware('permission:manage reviews')
            ->group(function () {
                Route::get('/',           [AdminReviewController::class, 'index'])->name('index');
                Route::get('{review}',    [AdminReviewController::class, 'show'])->name('show')->whereNumber('review');
                Route::delete('{review}', [AdminReviewController::class, 'destroy'])->name('destroy')->whereNumber('review');

                Route::patch('{review}/approve',   [AdminReviewController::class, 'approve'])->name('approve')->whereNumber('review');
                Route::patch('{review}/unapprove', [AdminReviewController::class, 'unapprove'])->name('unapprove')->whereNumber('review');

                Route::post('{review}/reply', [AdminReviewController::class, 'reply'])->name('reply')->whereNumber('review');

                Route::post('bulk-approve',   [AdminReviewController::class, 'bulkApprove'])->name('bulk-approve');
                Route::delete('bulk-destroy', [AdminReviewController::class, 'bulkDestroy'])->name('bulk-destroy');
            });

        // Points (legacy redirect)
        Route::get('/points', fn() => redirect()->route('account.points.index'))->name('points.legacy');
        // Pulse (dashboard widgets)
        Route::get('/pulse/counts', [PulseController::class, 'counts'])->name('pulse.counts');
        // Shipping
        Route::prefix('shipping')->as('shipping.')
            ->middleware('permission:manage shipping')
            ->group(function () {
                Route::resource('carriers', \App\Http\Controllers\Admin\ShippingCarrierController::class);
                Route::resource('zones',    \App\Http\Controllers\Admin\ShippingZoneController::class);
                Route::resource('rates',    \App\Http\Controllers\Admin\ShippingRateController::class)
                    ->except(['show']); // để có create/edit cho biểu phí

                // ✅ Toggle chỉ đổi enabled, không validate name/code
                Route::match(['patch', 'post'], 'carriers/{carrier}/toggle', [\App\Http\Controllers\Admin\ShippingCarrierController::class, 'toggle'])->name('carriers.toggle');
                Route::match(['patch', 'post'], 'zones/{zone}/toggle',       [\App\Http\Controllers\Admin\ShippingZoneController::class,   'toggle'])->name('zones.toggle');
                Route::match(['patch', 'post'], 'rates/{rate}/toggle',       [\App\Http\Controllers\Admin\ShippingRateController::class,   'toggle'])->name('rates.toggle');
            });
    });
