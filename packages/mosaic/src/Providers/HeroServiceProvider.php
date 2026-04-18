<?php

declare(strict_types=1);

namespace Capell\Mosaic\Providers;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Mosaic\Console\Commands\Hero\DemoCommand;
use Capell\Mosaic\Console\Commands\Hero\SetupCommand;
use Capell\Mosaic\Enums\Hero\ContentSchemaEnum;
use Capell\Mosaic\Enums\Hero\WidgetSchemaEnum;
use Capell\Mosaic\Enums\TypeSchemaEnum;
use Capell\Mosaic\Filament\Extenders\Page\HeroPageSchemaExtender;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;

class HeroServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-hero';

    public static string $packageName = 'capell-app/mosaic';

    public static string $description = 'Hero section component for layout builder.';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasViews(self::$name)
            ->hasCommands([
                DemoCommand::class,
                SetupCommand::class,
            ])
            ->hasTranslations();
    }

    public function registeringPackage(): void
    {
        $this
            ->registerSchemas()
            ->registerPackageMetadata()
            ->registerPackageAssets();

        $this->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this->bootInstalledPackage();
        });
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::getPackage(static::$packageName)->isInstalled();
    }

    private function bootInstalledPackage(): self
    {
        return $this
            ->registerSchemaExtenders()
            ->registerBladeComponents();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            sort: 10,
            description: static::getDescription(),
            setupCommand: 'capell:hero-setup',
            demoCommand: 'capell:hero-demo',
            demoParams: ['sites'],
            requirements: [
                AdminServiceProvider::$packageName,
                FrontendServiceProvider::$packageName,
                MosaicServiceProvider::$packageName,
            ],
            version: $this->getVersion(),
            url: 'https://capell.app',
        );

        return $this;
    }

    private function registerPackageAssets(): self
    {
        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', static::$packageName),
        );

        return $this;
    }

    private function getVersion(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return 'dev';
        }

        if (! InstalledVersions::isInstalled(static::$packageName)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion(static::$packageName) ?? 'dev';
    }

    private function registerSchemas(): self
    {
        CapellAdmin::registerSchema(TypeSchemaEnum::Content, ContentSchemaEnum::Hero);
        CapellAdmin::registerSchema(TypeSchemaEnum::Widget, WidgetSchemaEnum::Hero);

        return $this;
    }

    private function registerSchemaExtenders(): self
    {
        $this->registerSchemaExtender(HeroPageSchemaExtender::TAG, HeroPageSchemaExtender::class);

        return $this;
    }

    private function registerBladeComponents(): self
    {
        Blade::componentNamespace('Capell\\Mosaic\\View\\Components', 'capell-hero');
        Blade::anonymousComponentNamespace('Capell\\Mosaic\\View\\Components');

        return $this;
    }

    private function registerSchemaExtender(string $tag, string $class): void
    {
        $this->app->singleton($class, fn (): object => new $class);

        $this->app->tag($class, $tag);
    }
}
