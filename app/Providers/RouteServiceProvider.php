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

        // ✅ BIND {order} nhận được CẢ id LẪN code (chặn 404 do sai kiểu param)
        Route::bind('order', fn($v) => \App\Models\Order::where('id', $v)->orWhere('code', $v)->firstOrFail());


        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Throttle cho login: 5 lần/phút theo email|IP
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));
            $key   = $email . '|' . $request->ip();

            return [
                Limit::perMinute(5)->by($key),
            ];
        });
    }
}
