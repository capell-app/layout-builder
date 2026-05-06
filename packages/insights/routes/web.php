<?php

declare(strict_types=1);

use Capell\Insights\Http\Controllers\InsightsBeaconController;
use Capell\Insights\Http\Controllers\InsightsConsentController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

$routePrefix = trim(config('capell-insights.route_prefix', 'capell/insights'), '/');

Route::prefix($routePrefix)
    ->middleware(['web'])
    ->group(function (): void {
        Route::post('events', InsightsBeaconController::class)
            ->middleware(['throttle:60,1'])
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->name('capell-insights.events');

        Route::post('consent', InsightsConsentController::class)
            ->middleware(['throttle:60,1'])
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->name('capell-insights.consent');
    });
