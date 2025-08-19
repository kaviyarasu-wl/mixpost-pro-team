<?php

use Illuminate\Support\Facades\Route;
use Inovector\Mixpost\Http\Base\Controllers\Public\ManifestController;
use Inovector\Mixpost\Http\Base\Controllers\Public\PagesController;
use Inovector\Mixpost\Util;

Route::name('mixpost.')->group(function () {
    Route::get('manifest.json', ManifestController::class)->name('manifest');

    Route::prefix(Util::config('public_pages_prefix', ''))
        ->name('pages.')
        ->group(function () {
            Route::get('{slug?}', PagesController::class)->name('show');
        });
});
