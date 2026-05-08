<?php

declare(strict_types=1);

use Capell\AccessGate\Http\Controllers\AccessGateStatusController;
use Capell\AccessGate\Http\Controllers\ClaimAccessGateTokenController;
use Capell\AccessGate\Http\Controllers\LogoutAccessGateController;
use Capell\AccessGate\Http\Controllers\ShowAccessRequestController;
use Capell\AccessGate\Http\Controllers\StoreAccessRequestController;
use Illuminate\Support\Facades\Route;

$middleware = config('access-gate.middleware.default', ['web']);

Route::middleware(is_array($middleware) ? $middleware : ['web'])
    ->prefix((string) config('access-gate.route_prefix', 'access'))
    ->as('capell-access-gate.')
    ->group(function (): void {
        Route::get('/request/{area}', ShowAccessRequestController::class)
            ->name('request');

        Route::post('/request/{area}', StoreAccessRequestController::class)
            ->middleware('throttle:access-gate-request')
            ->name('request.store');

        Route::get('/claim/{token}', ClaimAccessGateTokenController::class)
            ->middleware('throttle:12,1')
            ->name('claim');

        Route::post('/logout/{area}', LogoutAccessGateController::class)
            ->name('logout');

        if ((bool) config('access-gate.status_endpoint_enabled', false)) {
            Route::get('/status/{area}', AccessGateStatusController::class)
                ->middleware('throttle:60,1')
                ->name('status');
        }
    });
