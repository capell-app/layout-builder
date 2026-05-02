<?php

declare(strict_types=1);

namespace Capell\DefaultTheme\Providers;

use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Theme;
use Capell\DefaultTheme\Support\Blade\BladeDirectives;
use Capell\DefaultTheme\Support\Interceptors\Themes\DefaultThemeInterceptor;
use Illuminate\Support\ServiceProvider;

class DefaultThemeServiceProvider extends ServiceProvider
{
    private static string $packageName = 'capell-app/default-theme';

    public function boot(): void
    {
        $themeModel = Theme::class;
        CapellCore::registerModelInterceptor($themeModel, interceptorClass: DefaultThemeInterceptor::class);

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell-default-theme');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell');
        RegisterBlazeOptimizedViewsAction::run(__DIR__ . '/../../resources/views/components');

        BladeDirectives::register();

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('resources/css/default-theme.css', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', self::$packageName),
        );
    }

    public function register(): void {}
}
