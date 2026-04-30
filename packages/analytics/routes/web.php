<?php

declare(strict_types=1);

use Capell\Analytics\Http\Controllers\AnalyticsBeaconController;
use Capell\Analytics\Http\Controllers\AnalyticsConsentController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

$routePrefix = trim(config('capell-analytics.route_prefix', 'capell/analytics'), '/');

Route::prefix($routePrefix)
    ->middleware(['web'])
    ->group(function (): void {
        Route::post('events', AnalyticsBeaconController::class)
            ->middleware(['throttle:60,1'])
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->name('capell-analytics.events');

        Route::post('consent', AnalyticsConsentController::class)
            ->middleware(['throttle:60,1'])
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->name('capell-analytics.consent');
    });
