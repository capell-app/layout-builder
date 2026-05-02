<?php

declare(strict_types=1);

use Capell\SiteSearch\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::name('capell-frontend.')
    ->middleware(['web', 'frontend.resolve'])
    ->group(function (): void {
        Route::get(config('capell-site-search.route_path', 'search'), SearchController::class)
            ->name('search');
    });
