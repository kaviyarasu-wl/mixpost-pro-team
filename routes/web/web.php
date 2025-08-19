<?php

use Illuminate\Support\Facades\Route;
use Inovector\Mixpost\Http\Base\Middleware\HandleInertiaRequests;
use Inovector\Mixpost\Mixpost;
use Inovector\Mixpost\Util;

$route = Route::middleware(array_merge(Mixpost::getWebAppMiddlewares(), Mixpost::getGlobalMiddlewares()));

if (Util::corePath()) {
    $route->prefix(Util::corePath());
}

$route->name('mixpost.')
    ->group(function () {
        // Auth routes
        Route::middleware(HandleInertiaRequests::class)->group(function () {
            require __DIR__ . '/includes/auth.php';
        });

        // Dashboard routes
        Route::middleware(array_merge(
            Mixpost::getWebDashboardMiddlewares(),
            [HandleInertiaRequests::class]
        ))->group(function () {
            require __DIR__ . '/includes/main.php';

            require __DIR__ . '/includes/admin.php';

            require __DIR__ . '/includes/workspace.php';
        });
    });

require __DIR__ . '/includes/callback.php';

require __DIR__ . '/includes/public.php';
