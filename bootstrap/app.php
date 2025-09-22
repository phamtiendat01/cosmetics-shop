<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// THÊM 2 DÒNG use NÀY
use App\Console\Commands\PointsConfirmDue;
use App\Console\Commands\PointsExpire;
use App\Console\Commands\PointsBackfill;
use App\Console\Commands\CouponRedemptionsBackfill;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    // THÊM KHỐI NÀY
    ->withCommands([
        PointsBackfill::class,
        PointsConfirmDue::class,
        PointsExpire::class,
        CouponRedemptionsBackfill::class,

    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
