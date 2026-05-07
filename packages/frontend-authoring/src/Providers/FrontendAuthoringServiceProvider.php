<?php

declare(strict_types=1);

namespace Capell\FrontendAuthoring\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\FrontendAuthoring\Http\Middleware\PassThroughActivityMiddleware;
use Capell\FrontendAuthoring\Livewire\EditRegionField;
use Capell\FrontendAuthoring\Support\EditableRegionRegistry;
use Capell\FrontendAuthoring\Support\EditableRegionSigner;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class FrontendAuthoringServiceProvider extends ServiceProvider
{
    public static string $packageName = 'capell-app/frontend-authoring';

    public function boot(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'capell-frontend-authoring');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell');
        $this->registerFallbackMiddlewareAliases();
        Livewire::component('capell-frontend-authoring::edit-region-field', EditRegionField::class);
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/capell-frontend-authoring.php', 'capell-frontend-authoring');
        $this->app->singleton(EditableRegionRegistry::class, fn (): EditableRegionRegistry => new EditableRegionRegistry);
        $this->app->singleton(EditableRegionSigner::class, fn (): EditableRegionSigner => new EditableRegionSigner);
        $this->registerPackageMetadata();
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(static::$packageName);
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
            description: 'Frontend Authoring replaces the old frontend toolbar package. It keeps the beacon route and adds cache-safe in-page editing for rendered frontend pages.',
        );
    }
}
