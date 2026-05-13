<?php

declare(strict_types=1);

use Capell\Api\Http\Controllers\ResolvePageController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')
    ->prefix('api')
    ->group(function (): void {
        Route::get('capell/pages/resolve', ResolvePageController::class)
            ->name('capell-api.pages.resolve');
    });
