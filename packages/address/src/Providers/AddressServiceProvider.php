<?php

declare(strict_types=1);

namespace Capell\Address\Providers;

use Capell\Address\Console\Commands\DemoCommand;
use Capell\Address\Console\Commands\FakerCommand;
use Capell\Address\Console\Commands\InstallCommand;
use Capell\Address\Enums\ConfiguratorTypeEnum;
use Capell\Address\Enums\ResourceEnum;
use Capell\Address\Filament\Configurators\Languages\DefaultLanguageConfigurator;
use Capell\Address\Filament\Resources\Sites\Schemas\Extenders\SiteSchemaExtender;
use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Capell\Address\Support\AddressModelRegistrar;
use Capell\Address\Support\FlagIconRenderer;
use Capell\Address\Support\Language\FlagsService;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\ConfiguratorTypeEnum as AdminConfiguratorTypeEnum;
use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Composer\InstalledVersions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;

class AddressServiceProvider extends AbstractPackageServiceProvider
{
    private const ADMIN_FLAG_ICON_RENDERER_CONTRACT = \Capell\Admin\Contracts\Support\FlagIconRenderer::class;

    public static string $name = 'capell-address';

    public static string $packageName = 'capell-app/address';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasViews(self::$name)
            ->hasCommands([
                DemoCommand::class,
                FakerCommand::class,
                InstallCommand::class,
            ])
            ->hasTranslations();
    }

    public function registeringPackage(): void
    {
        $this->registerPackageMetadata();

        $this->app->booted(function (): void {
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
            ->registerModels()
            ->registerRelationships()
            ->registerPackageAssets()
            ->registerSupportServices()
            ->registerResources()
            ->registerConfigurators()
            ->registerLanguageConfigurator()
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
            version: $this->getVersion(),
            description: fn (): string => __('capell-address::package.description'),
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

    private function registerSupportServices(): self
    {
        $this->app->singleton(FlagIconRenderer::class);
        $this->app->singleton(FlagsService::class);

        if (interface_exists(self::ADMIN_FLAG_ICON_RENDERER_CONTRACT)) {
            $this->app->singleton(self::ADMIN_FLAG_ICON_RENDERER_CONTRACT, FlagIconRenderer::class);
        }

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

    private function registerSchemaExtender(string $tag, string $class): void
    {
        $this->app->singleton($class, fn (): object => new $class);
        $this->app->tag($class, $tag);
    }

    private function registerModels(): self
    {
        AddressModelRegistrar::register();

        return $this;
    }

    private function registerConfigurators(): self
    {
        foreach (ConfiguratorTypeEnum::getAllConfigurators() as $type => $configurators) {
            foreach ($configurators as $configuratorClass) {
                CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::configurator(
                    class: $configuratorClass,
                    group: $type,
                    name: $configuratorClass::getKey(),
                ));
            }
        }

        return $this;
    }

    private function registerLanguageConfigurator(): self
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::configurator(
            class: DefaultLanguageConfigurator::class,
            group: AdminConfiguratorTypeEnum::Language->value,
            name: DefaultLanguageConfigurator::getKey(),
        ));

        return $this;
    }

    private function registerResources(): self
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: ResourceEnum::Address->value,
            group: ResourceEnum::Address->name,
        ));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: ResourceEnum::Country->value,
            group: ResourceEnum::Country->name,
        ));

        return $this;
    }

    private function registerSchemaExtenders(): self
    {
        $this->registerSchemaExtender(SchemaExtenderEnum::Site->value, SiteSchemaExtender::class);

        return $this;
    }

    private function registerBladeComponents(): self
    {
        Blade::componentNamespace('Capell\\Address\\View\\Components', 'capell-address');
        Blade::anonymousComponentNamespace('Capell\\Address\\View\\Components');

        return $this;
    }

    private function registerRelationships(): self
    {
        Site::resolveRelationUsing(
            'address',
            fn (Site $model): BelongsTo => $model->belongsTo(Address::class, 'meta->address_id'),
        );

        Site::resolveRelationUsing(
            'country',
            fn (Site $model): HasOneThrough => $model->hasOneThrough(
                Country::class,
                Address::class,
                'id',
                'id',
                'meta->address_id',
                'country_id',
            ),
        );

        return $this;
    }
}
