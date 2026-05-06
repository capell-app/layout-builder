<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Providers;

use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Events\PackageInstalled;
use Capell\Core\Events\PackageUninstalled;
use Capell\Core\Events\ThemeColorsUpdated;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\FoundationTheme\Console\Commands\GenerateTailwindAssetsCommand;
use Capell\FoundationTheme\Enums\FoundationThemeAssetEnum;
use Capell\FoundationTheme\Filament\Settings\FoundationThemeSettingsSchema;
use Capell\FoundationTheme\Listeners\RegenerateTailwindAssetsOnThemeColorsUpdated;
use Capell\FoundationTheme\Listeners\RunTailwindAssetsOnPackageChange;
use Capell\FoundationTheme\Settings\FoundationThemeSettings;
use Capell\FoundationTheme\Support\Blade\BladeDirectives;
use Capell\FoundationTheme\Support\Interceptors\Themes\FoundationThemeInterceptor;
use Capell\FoundationTheme\Support\Media\CapellUrlGenerator;
use Capell\FoundationTheme\Support\Tailwind\TailwindAssetsGenerator;
use Capell\FoundationTheme\View\Components\Media\Svg;
use Capell\Frontend\Contracts\AssetsRegistryInterface;
use Capell\Frontend\Data\FrontendAssetData;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;

final class FoundationThemeServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-foundation-theme';

    public static string $packageName = 'capell-app/foundation-theme';

    public static PackageTypeEnum $type = PackageTypeEnum::Theme;

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile()
            ->hasViews('capell')
            ->hasCommands([GenerateTailwindAssetsCommand::class]);
    }

    public function packageBooted(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->registerAssets();
        $this->registerBladeDirectives();
        $this->registerBlazeComponents();
        $this->registerTailwindEventListeners();
        $this->registerVendorNpmDependencies();
        $this->registerVendorCssJsAssets();
        $this->registerMediaUrlGenerator();
        $this->registerBladeComponents();
        $this->registerMediaBladeComponents();
        $this->registerModelInterceptors();
        $this->registerSettingsSchemas();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton('capell.tailwind.generator', fn (): TailwindAssetsGenerator => new TailwindAssetsGenerator(
            $this->app->make(Filesystem::class),
        ));
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(self::$packageName);
    }

    private function registerAssets(): void
    {
        if (! $this->app->bound(AssetsRegistryInterface::class)) {
            return;
        }

        $registry = resolve(AssetsRegistryInterface::class);

        foreach (FoundationThemeAssetEnum::cases() as $asset) {
            $registry->registerAsset(
                $asset->getAsset(),
                new FrontendAssetData(component: $asset->getComponent()),
            );
        }
    }

    private function registerBladeDirectives(): void
    {
        BladeDirectives::register();
    }

    private function registerBlazeComponents(): void
    {
        RegisterBlazeOptimizedViewsAction::run(__DIR__ . '/../../resources/views/components');
    }

    private function registerTailwindEventListeners(): void
    {
        Event::listen(ThemeColorsUpdated::class, [RegenerateTailwindAssetsOnThemeColorsUpdated::class, 'handle']);
        Event::listen(PackageInstalled::class, [RunTailwindAssetsOnPackageChange::class, 'handleInstalled']);
        Event::listen(PackageUninstalled::class, [RunTailwindAssetsOnPackageChange::class, 'handleUninstalled']);
    }

    private function registerMediaUrlGenerator(): void
    {
        config(['media-library.url_generator' => CapellUrlGenerator::class]);
    }

    private function registerMediaBladeComponents(): void
    {
        Blade::component('capell::media.svg', Svg::class);
    }

    private function registerBladeComponents(): void
    {
        Blade::anonymousComponentPath(__DIR__ . '/../../resources/views/components', 'capell');
    }

    private function registerSettingsSchemas(): void
    {
        $registry = resolve(SettingsSchemaRegistry::class);
        $registry->registerSettingsClass('foundation_theme', FoundationThemeSettings::class);
        $registry->register('foundation_theme', FoundationThemeSettingsSchema::class);
    }

    private function registerModelInterceptors(): void
    {
        CapellCore::registerModelInterceptor(Theme::class, interceptorClass: FoundationThemeInterceptor::class);
    }

    private function registerVendorNpmDependencies(): void
    {
        $npmDependencies = config('capell-foundation-theme.npm_dependencies', []);

        if (! is_array($npmDependencies)) {
            return;
        }

        foreach ($npmDependencies as $package => $version) {
            if (! is_string($package)) {
                continue;
            }

            if ($package === '') {
                continue;
            }

            if (! is_string($version)) {
                continue;
            }

            if ($version === '') {
                continue;
            }

            CapellCore::registerVendorAsset(
                VendorAssetData::npmDependency($package, $version, self::$packageName),
            );
        }
    }

    private function registerVendorCssJsAssets(): void
    {
        CapellCore::registerVendorAsset(
            VendorAssetData::buildAsset(
                path: 'vendor/capell-frontend',
                file: 'resources/js/capell-frontend.js',
                packageName: self::$packageName,
            ),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('resources/css/foundation-theme.css', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', self::$packageName),
        );
    }
}
