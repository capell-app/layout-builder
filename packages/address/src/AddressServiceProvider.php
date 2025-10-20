<?php

declare(strict_types=1);

namespace Capell\Address;

use Capell\Address\Commands\DemoCommand;
use Capell\Address\Commands\InstallCommand;
use Capell\Address\Enums\AddressSchemaEnum;
use Capell\Address\Enums\CountrySchemaEnum;
use Capell\Address\Enums\ModelEnum;
use Capell\Address\Enums\ResourceEnum;
use Capell\Address\Enums\SchemaTypeEnum;
use Capell\Address\Filament\Resources\Sites\Schemas\Extenders\SiteSchemaExtender;
use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Packages\AbstractPackageServiceProvider;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package;

class AddressServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-address';

    public static string $description = 'Address and country field components for forms.';

    public function bootingPackage(): void
    {
        Blade::componentNamespace('Capell\\Address\\View\\Components', 'capell-address');
        Blade::anonymousComponentNamespace('Capell\\Address\\View\\Components');
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

        $this->registerModels()
            ->registerRelationships()
            ->registerSchemas();

        CapellCore::registerPackage(
            self::$name,
            class: self::class,
            path: __DIR__,
            sort: 10,
            installCommand: true,
            demoCommand: true,
            demoParams: ['sites']
        );

        Relation::morphMap(
            collect(ModelEnum::cases())
                ->mapWithKeys(fn (ModelEnum $model): array => [Str::snake($model->name) => $model->value])
                ->all()
        );

        CapellAdmin::registerResource(ResourceEnum::Address->name, class: ResourceEnum::Address->value);
        CapellAdmin::registerResource(ResourceEnum::Country->name, class: ResourceEnum::Country->value);

        $this->registerSchemaExtender(SchemaExtenderEnum::Site->value, SiteSchemaExtender::class);
    }

    private function registerSchemaExtender(string $tag, string $class): void
    {
        $this->app->singleton($class, fn (): object => new $class);

        $this->app->tag($class, $tag);
    }

    private function registerRelationships(): self
    {
        Site::resolveRelationUsing(
            'address',
            fn (Site $model): BelongsTo => $model->belongsTo(Address::class, 'meta->address_id')
        );

        Site::resolveRelationUsing(
            'country',
            fn (Site $model): HasOneThrough => $model->hasOneThrough(
                Country::class,
                Address::class,
                'id',
                'id',
                'meta->address_id',
                'country_id'
            )
        );

        return $this;
    }

    private function registerModels(): self
    {
        CapellCore::registerModels(ModelEnum::cases());

        return $this;
    }

    private function registerSchemas(): self
    {
        CapellAdmin::registerSchemas(SchemaTypeEnum::Address->value, AddressSchemaEnum::cases());
        CapellAdmin::registerSchemas(SchemaTypeEnum::Country->value, CountrySchemaEnum::cases());

        return $this;
    }
}
