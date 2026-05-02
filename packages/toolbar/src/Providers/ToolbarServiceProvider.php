<?php

declare(strict_types=1);

namespace Capell\Toolbar\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Toolbar\Http\Middleware\PassThroughActivityMiddleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ToolbarServiceProvider extends ServiceProvider
{
    public static string $packageName = 'capell-app/frontend-toolbar';

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'capell-frontend-toolbar');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell');
        $this->registerFallbackMiddlewareAliases();
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/capell-frontend-toolbar.php', 'capell-frontend-toolbar');
        $this->registerPackageMetadata();
    }

    private function registerFallbackMiddlewareAliases(): void
    {
        if (array_key_exists('frontend.activity', Route::getMiddleware())) {
            return;
        }

        Route::aliasMiddleware('frontend.activity', PassThroughActivityMiddleware::class);
    }

    private function registerPackageMetadata(): void
    {
        CapellCore::registerPackage(
            static::$packageName,
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(static::$packageName),
            description: 'Admin toolbar and beacon for Capell frontend',
        );
    }
}
