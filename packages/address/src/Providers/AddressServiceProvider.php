<?php

declare(strict_types=1);

namespace Capell\Address\Providers;

use Capell\Address\AddressModelRegistrar;
use Capell\Address\Commands\DemoCommand;
use Capell\Address\Commands\InstallCommand;
use Capell\Address\Enums\ResourceEnum;
use Capell\Address\Enums\SchemaTypeEnum;
use Capell\Address\Filament\Resources\Sites\Schemas\Extenders\SiteSchemaExtender;
use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Packages\AbstractPackageServiceProvider;
use Composer\InstalledVersions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;

class AddressServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-address';

    public static string $packageName = 'capell-app/address';

    public static string $description = 'Address and country field components for forms.';

    public function bootingPackage(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->registerAll();
    }

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasCommands([
                DemoCommand::class,
                InstallCommand::class,
            ])
            ->hasTranslations();
    }

    public function registeringPackage(): void
    {
        parent::registeringPackage();

        $this->registerPackageMetadata()
            ->registerResources();
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::getPackage(static::$packageName)->isInstalled();
    }

    private function registerAll(): self
    {
        return $this
            ->registerModels()
            ->registerRelationships()
            ->registerSchemas()
            ->registerSchemaExtenders()
            ->registerBladeComponents();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            path: __DIR__,
            sort: 10,
            description: static::getDescription(),
            installCommand: 'capell-address:install',
            demoCommand: 'capell-address:demo',
            demoParams: ['sites'],
            requirements: [
                AdminServiceProvider::$packageName,
            ],
            version: $this->getVersion(),
            url: 'https://capell.app',
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

    private function registerSchemas(): self
    {
        foreach (SchemaTypeEnum::getAllSchemas() as $type => $schemas) {
            CapellAdmin::registerSchemas($type, $schemas, defaultSchemas: true);
        }

        return $this;
    }

    private function registerResources(): self
    {
        CapellAdmin::registerResource(ResourceEnum::Address->name, class: ResourceEnum::Address->value);
        CapellAdmin::registerResource(ResourceEnum::Country->name, class: ResourceEnum::Country->value);

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
