<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\ThemeStudio\Core\Actions\ResolveThemeRuntimeAction;
use Capell\ThemeStudio\Core\Adapters\CapellFrontendThemePageAdapter;
use Capell\ThemeStudio\Core\Contracts\ThemePageAdapter;
use Capell\ThemeStudio\Core\Contracts\ThemeRuntimeSettings;
use Capell\ThemeStudio\Core\Http\Middleware\ResolveThemePreviewContext;
use Capell\ThemeStudio\Core\Preview\ThemePreviewContext;
use Capell\ThemeStudio\Core\Preview\ThemePreviewSigner;
use Capell\ThemeStudio\Core\Settings\ThemeStudioSettings;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

final class ThemeStudioCoreServiceProvider extends ServiceProvider
{
    private bool $previewMiddlewareRegistered = false;

    public function register(): void
    {
        $this->app->singleton(ThemeRegistry::class);
        $this->app->bind(ThemePageAdapter::class, CapellFrontendThemePageAdapter::class);
        $this->app->bind(ThemeRuntimeSettings::class, ThemeStudioSettings::class);

        $this->app->singleton(
            ThemePreviewSigner::class,
            fn (): ThemePreviewSigner => new ThemePreviewSigner(config('app.key', 'theme-studio')),
        );

        $this->app->singleton(ThemePreviewContext::class, fn (): ThemePreviewContext => ThemePreviewContext::none());

        $this->app->afterResolving(
            SettingsSchemaRegistry::class,
            function (SettingsSchemaRegistry $registry): void {
                $registry->registerSettingsClass(ThemeStudioSettings::group(), ThemeStudioSettings::class);
            },
        );

        CapellCore::registerPackage(
            name: 'capell-app/theme-studio-core',
            path: realpath(__DIR__ . '/..'),
            version: CapellCore::getInstalledPrettyVersion('capell-app/theme-studio-core'),
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../database/settings/create_theme_studio_settings.php' => database_path('settings/create_theme_studio_settings.php'),
            ], 'capell-theme-studio-core-settings');
        }

        $this->app->afterResolving(Router::class, function (Router $router): void {
            $this->registerPreviewMiddleware($router);
        });

        if ($this->app->resolved(Router::class)) {
            $this->registerPreviewMiddleware($this->app->make(Router::class));
        }

        $this->registerTokenRenderHook();
    }

    private function registerPreviewMiddleware(Router $router): void
    {
        if ($this->previewMiddlewareRegistered) {
            return;
        }

        $router->pushMiddlewareToGroup('web', ResolveThemePreviewContext::class);
        $this->previewMiddlewareRegistered = true;
    }

    private function registerTokenRenderHook(): void
    {
        if (! class_exists(RenderHookRegistry::class)
            || ! class_exists(RenderHookLocation::class)) {
            return;
        }

        $this->app->afterResolving(
            RenderHookRegistry::class,
            function (RenderHookRegistry $registry, Application $app): void {
                $registry->register(
                    RenderHookLocation::HeadClose,
                    function () use ($app): string {
                        if (! $app->bound(ThemeRuntimeSettings::class)) {
                            return '';
                        }

                        $settings = $app->make(ThemeRuntimeSettings::class);
                        $runtime = ResolveThemeRuntimeAction::run(
                            activeTheme: $settings->activeTheme(),
                            activePreset: $settings->activePreset(),
                            brand: $settings->brandProfile(),
                            themeOverrides: $settings->themeOverrides(),
                        );

                        if ($runtime->tokenCssPath === null) {
                            return '';
                        }

                        return '<link rel="stylesheet" href="' . e(asset('vendor/capell-theme-studio/tokens/' . basename((string) $runtime->tokenCssPath))) . '">';
                    },
                );
            },
        );
    }
}
