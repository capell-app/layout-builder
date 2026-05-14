<?php

declare(strict_types=1);

use Capell\Api\Http\Controllers\ResolvePageController;
use Illuminate\Support\Facades\Route;

$configuredMiddleware = static function (mixed $middleware): array {
    if ($middleware === null || $middleware === false || $middleware === '') {
        return [];
    }

    if (is_string($middleware)) {
        return [$middleware];
    }

    if (! is_array($middleware)) {
        return [];
    }

    return array_values(array_filter(
        $middleware,
        static fn (mixed $middlewareName): bool => is_string($middlewareName) && $middlewareName !== '',
    ));
};

$apiMiddleware = [
    ...$configuredMiddleware(config('capell-api.middleware', ['api'])),
    ...$configuredMiddleware(config('capell-api.public_pages.auth_middleware')),
    ...$configuredMiddleware(config('capell-api.public_pages.rate_limit_middleware')),
    ...$configuredMiddleware(config('capell-api.public_pages.middleware', [])),
];

Route::middleware($apiMiddleware)
    ->prefix('api')
    ->group(function (): void {
        Route::get('capell/pages/resolve', ResolvePageController::class)
            ->name('capell-api.pages.resolve');

        Route::get('capell/v1/pages/resolve', ResolvePageController::class)
            ->name('capell-api.v1.pages.resolve');
    });
