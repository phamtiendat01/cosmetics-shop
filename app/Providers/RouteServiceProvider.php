<?php

namespace App\Providers;

use App\Models\Order;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/';

    public function boot(): void
    {
        $this->configureRateLimiting();

        // ✅ BIND {order} nhận được CẢ id LẪN code
        Route::bind('order', function ($value) {
            return Order::where('id', $value)
                ->orWhere('code', $value)
                ->firstOrFail();
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Khai báo tất cả rate limiters tại đây
     */
    protected function configureRateLimiting(): void
    {
        // Throttle cho login: 5 lần/phút theo email|IP
        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));
            $key   = 'login|' . $email . '|' . $request->ip();
            return Limit::perMinute(5)->by($key);
        });

        // Throttle cho nhóm /skintest
        RateLimiter::for('skintest', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(30)->by('skintest|user:' . $request->user()->id)
                : Limit::perMinute(20)->by('skintest|ip:' . $request->ip());
        });
    }
}
