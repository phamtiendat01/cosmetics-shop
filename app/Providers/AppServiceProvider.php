<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use App\Models\Setting;
use App\Models\Category;
use App\Models\ProductReview;
use App\Models\Order;
use App\Observers\OrderObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 1) Đăng ký Observer NGAY khi boot app
        Order::observe(OrderObserver::class);

        // 2) Super-admin bypass
        Gate::before(function ($user, $ability) {
            return method_exists($user, 'hasRole') && $user->hasRole('super-admin') ? true : null;
        });

        // 3) Runtime settings
        if (Schema::hasTable('settings')) {
            Setting::syncRuntime();
        }
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // 4) View composer cho header
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

            $topCount   = 6;
            $megaTree   = $headerCats->take($topCount);
            $wishlistCount = (int) (is_array(session('wishlist')) ? count(session('wishlist')) : 0);
            $cartItems  = session('cart.items', []);
            $cartCount  = (int) collect($cartItems)->sum('qty');

            $view->with(compact('headerCats', 'megaTree', 'wishlistCount', 'cartCount'));

            $pendingReviewsCount = ProductReview::where('approved', false)->count();
            $view->with('pendingReviewsCount', $pendingReviewsCount);
        });
    }
}
