<?php

declare(strict_types=1);

namespace Capell\DefaultTheme\Providers;

use Capell\Core\Data\VendorAssetData;
use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Events\PackageInstalled;
use Capell\Core\Events\PackageUninstalled;
use Capell\Core\Events\ThemeColorsUpdated;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\DefaultTheme\Support\Tailwind\TailwindAssetsGenerator;
use Capell\Frontend\Contracts\AssetsRegistryInterface;
use Capell\Frontend\Data\FrontendAssetData;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use theme\src\Console\Commands\GenerateTailwindAssetsCommand;
use theme\src\Enums\DefaultThemeAssetEnum;
use theme\src\Filament\Settings\DefaultThemeSettingsSchema;
use theme\src\Listeners\RegenerateTailwindAssetsOnThemeColorsUpdated;
use theme\src\Listeners\RunTailwindAssetsOnPackageChange;
use theme\src\Settings\DefaultThemeSettings;
use theme\src\Support\Blade\BladeDirectives;
use theme\src\Support\Media\CapellUrlGenerator;
use theme\src\View\Components\Media\Svg;

final class DefaultThemeServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-default-theme';

    public static string $packageName = 'capell-app/default-theme';

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
        $this->registerAssets();
        $this->registerBladeDirectives();
        $this->registerTailwindEventListeners();
        $this->registerVendorNpmDependencies();
        $this->registerVendorCssJsAssets();
        $this->registerMediaUrlGenerator();
        $this->registerMediaBladeComponents();
        $this->registerSettingsSchemas();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton('capell.tailwind.generator', fn (): TailwindAssetsGenerator => new TailwindAssetsGenerator(
            $this->app->make(Filesystem::class),
        ));
    }

    private function registerAssets(): void
    {
        if (! $this->app->bound(AssetsRegistryInterface::class)) {
            return;
        }

        $registry = resolve(AssetsRegistryInterface::class);

        foreach (DefaultThemeAssetEnum::cases() as $asset) {
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

    private function registerSettingsSchemas(): void
    {
        $registry = resolve(SettingsSchemaRegistry::class);
        $registry->registerSettingsClass('default_theme', DefaultThemeSettings::class);
        $registry->register('default_theme', DefaultThemeSettingsSchema::class);
    }

    private function registerVendorNpmDependencies(): void
    {
        $npmDependencies = config('capell-default-theme.npm_dependencies', []);

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
            VendorAssetData::tailwindImport('resources/css/capell-frontend.css', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', self::$packageName),
        );
    }
}
