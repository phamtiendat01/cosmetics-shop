<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;   // 👈 thêm dòng này
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;
use App\Models\Category;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return method_exists($user, 'hasRole') && $user->hasRole('super-admin') ? true : null;
        });
        if (Schema::hasTable('settings')) {
            Setting::syncRuntime();
        }
        // Chia sẻ dữ liệu cho layouts.app (header)
        View::composer('layouts.app', function ($view) {
            // 8 danh mục cha hiển thị trên thanh nav
            $headerCats = Cache::remember('header_cats', 3600, function () {
                return Category::active()
                    ->whereNull('parent_id')
                    ->orderByRaw('COALESCE(sort_order,999999), name')
                    ->take(8)
                    ->get(['id', 'name', 'slug']);
            });

            // Cây cha -> con cho flyout “Danh mục”
            $megaTree = Cache::remember('mega_tree', 3600, function () {
                return Category::active()
                    ->whereNull('parent_id')
                    ->orderByRaw('COALESCE(sort_order,999999), name')
                    ->with(['children' => function ($q) {
                        $q->active()->orderByRaw('COALESCE(sort_order,999999), name')
                            ->select('id', 'name', 'slug', 'parent_id');
                    }])
                    ->get(['id', 'name', 'slug']);
            });

            // Badge số lượng yêu thích & giỏ hàng (tạm thời lấy nhanh)
            $wishlistCount = auth()->check() ? (int) (method_exists(auth()->user(), 'wishlist') ? auth()->user()->wishlist()->count() : 0) : 0;
            $cartCount     = (int) (is_array(session('cart')) ? collect(session('cart'))->sum('qty') : 0);

            $view->with(compact('headerCats', 'megaTree', 'wishlistCount', 'cartCount'));
        });
    }
}
