<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;

use App\Models\Setting;
use App\Models\Category;
use App\Models\ProductReview;
use App\Models\Order;

use App\Observers\OrderObserver;

use App\Contracts\SkinAnalyzer as SkinAnalyzerContract; // interface
use App\Services\SkinAnalyzerAPI;
use App\Services\SimpleSkinAnalyzerService;

use App\Services\Loyalty\TierService; // <- ĐÚNG namespace

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind driver phân tích da (simple hoặc Gemini API) vào IoC
        $this->app->bind(SkinAnalyzerContract::class, function () {
            $driver = config('skin.driver', 'simple');

            if ($driver === 'api') {
                return new SkinAnalyzerAPI(
                    fallback: new SimpleSkinAnalyzerService()
                );
            }

            return new SimpleSkinAnalyzerService();
        });
    }

    public function boot(): void
    {
        // 1) Đăng ký Observer
        Order::observe(OrderObserver::class);

        // 2) Super-admin bypass
        Gate::before(function ($user, $ability) {
            return method_exists($user, 'hasRole') && $user->hasRole('super-admin') ? true : null;
        });

        // 3) Runtime settings + HTTPS production
        if (Schema::hasTable('settings')) {
            Setting::syncRuntime();
        }
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // 4) View composer cho header/menu (categories, wishlist, cart, pending reviews)
        View::composer('*', function ($view) {
            $headerCats = Cache::remember('headerCats:withChildren:v1', 3600, function () {
                return Category::query()
                    ->select('id', 'name', 'slug')
                    ->whereNull('parent_id')->where('is_active', 1)
                    ->orderByRaw('COALESCE(sort_order,999999), name')
                    ->with(['children' => function ($q) {
                        $q->select('id', 'name', 'slug', 'parent_id')
                            ->where('is_active', 1)
                            ->orderByRaw('COALESCE(sort_order,999999), name')
                            ->with(['children' => function ($qq) {
                                $qq->select('id', 'name', 'slug', 'parent_id')
                                    ->where('is_active', 1)
                                    ->orderByRaw('COALESCE(sort_order,999999), name');
                            }]);
                    }])->get();
            });

            $topCount       = 6;
            $megaTree       = $headerCats->take($topCount);
            $wishlistCount  = (int) (is_array(session('wishlist')) ? count(session('wishlist')) : 0);
            $cartItems      = session('cart.items', []);
            $cartCount      = (int) collect($cartItems)->sum('qty');

            $view->with(compact('headerCats', 'megaTree', 'wishlistCount', 'cartCount'));

            // Nếu bạn chỉ cần cho admin, có thể wrap bằng điều kiện isAdmin
            $pendingReviewsCount = ProductReview::where('approved', false)->count();
            $view->with('pendingReviewsCount', $pendingReviewsCount);
        });

        // 5) Composer tiêm tier vào mọi layout/account/component
        View::composer('*', function ($view) {
            $tierCode = 'member';
            $tierName = 'Member';

            if (Auth::check()) {
                $u = Auth::user();

                // Ưu tiên đọc quan hệ đã có
                $u = $u->fresh(['memberTier.tier']);   // load luôn tier để tránh N+1
                $ut = $u->memberTier;

                // Nếu chưa có bản ghi hạng -> evaluate 1 lần rồi fresh lại
                if (!$ut || !$ut->tier) {
                    try {
                        app(TierService::class)->evaluate($u);
                        $u  = $u->fresh(['memberTier.tier']);
                        $ut = $u->memberTier;
                    } catch (\Throwable $e) {
                        // fallback Member nếu có lỗi nhẹ, không làm nổ giao diện
                    }
                }

                if ($ut && $ut->tier) {
                    $tierCode = $ut->tier->code ?? 'member';
                    $tierName = $ut->tier->name ?? 'Member';
                }
            }

            $view->with(compact('tierCode', 'tierName'));
        });
    }
}
