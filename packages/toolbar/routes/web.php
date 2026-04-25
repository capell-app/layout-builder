<?php

declare(strict_types=1);

use Capell\Toolbar\Http\Controllers\BeaconController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::name('capell-frontend.')
    ->middleware(['web'])
    ->group(function (): void {
        Route::post('beacon', BeaconController::class)
            ->middleware(['frontend.activity'])
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->name('beacon');
    });
