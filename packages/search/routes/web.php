<?php

declare(strict_types=1);

use Capell\Search\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::name('capell-frontend.')
    ->middleware(['web', 'frontend.resolve'])
    ->group(function (): void {
        Route::get(config('capell-search.route_path', 'search'), SearchController::class)
            ->name('search');
    });
