<?php

declare(strict_types=1);

use Capell\FrontendAuthoring\Http\Controllers\BeaconController;
use Capell\FrontendAuthoring\Http\Controllers\EditRegionController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::name('capell-frontend.')
    ->middleware(['web'])
    ->group(function (): void {
        Route::post('beacon', BeaconController::class)
            ->middleware(['frontend.activity', 'throttle:60,1'])
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->name('beacon');

        Route::get('authoring/regions/{payload}', EditRegionController::class)
            ->middleware(['auth', 'signed'])
            ->name('authoring.edit');
    });
