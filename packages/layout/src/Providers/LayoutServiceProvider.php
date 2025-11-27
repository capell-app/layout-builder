<?php

declare(strict_types=1);

namespace Capell\Layout\Providers;

use Capell\Admin\Actions\CreatedModelAction;
use Capell\Admin\Actions\DeletedModelAction;
use Capell\Admin\Data\AdminAssetData;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Enums\SchemaTypeEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Core\Data\AssetData;
use Capell\Core\Data\TypeData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Packages\AbstractPackageServiceProvider;
use Capell\Frontend\Data\FrontendAssetData;
use Capell\Frontend\Facades\CapellFrontend;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Layout\CapellLayoutManager;
use Capell\Layout\Commands\DemoCommand;
use Capell\Layout\Commands\InstallCommand;
use Capell\Layout\Commands\UpgradeCommand;
use Capell\Layout\Enums;
use Capell\Layout\Enums\AssetEnum;
use Capell\Layout\Enums\ComponentTypeEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Enums\ResourceEnum as LayoutResourceEnum;
use Capell\Layout\Filament\Resources\Layouts\LayoutResource;
use Capell\Layout\Filament\Resources\Layouts\Schemas\Extenders\LayoutSchemaExtender;
use Capell\Layout\Filament\Resources\Pages\Schemas\Extenders\PageSchemaExtender;
use Capell\Layout\Filament\Resources\Types\Schemas\Types\ContentTypeSchema;
use Capell\Layout\Filament\Resources\Types\Schemas\Types\WidgetTypeSchema;
use Capell\Layout\LayoutModelRegistrar;
use Capell\Layout\Listeners\AfterRecordSaved;
use Capell\Layout\Listeners\LayoutLoaded;
use Capell\Layout\Listeners\SiteTreeRebuilt;
use Capell\Layout\Listeners\TypeValidated;
use Capell\Layout\Models\Content;
use Composer\InstalledVersions;
use Exception;
use Filament\Facades\Filament;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use RuntimeException;
use Spatie\LaravelPackageTools\Package;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

class LayoutServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-layout';

    public static string $packageName = 'capell-app/layout';

    public static string $description = 'Managing content and widgets.';

    public function bootingPackage(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->registerAll()
            ->registerPublishCommands()
            ->registerLivewireComponents()
            ->registerBladeComponents();
    }

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasConfigFile()
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasCommands([
                DemoCommand::class,
                UpgradeCommand::class,
                InstallCommand::class,
            ]);
    }

    public function registeringPackage(): void
    {
        parent::registeringPackage();

        $this->registerPackageMetadata()
            ->registerResources();
    }

    protected function getPublishedDirectory(): string
    {
        $dir = $this->package->basePath('/../publishes/');

        throw_if(in_array($dir, ['', '0', false], true), RuntimeException::class, 'Publish directory not found.');

        return $dir;
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::getPackage(static::$packageName)->isInstalled();
    }

    private function registerAll(): self
    {
        return $this
            ->registerListeners()
            ->registerModels()
            ->registerRelationships()
            ->registerSchemas()
            ->registerManager()
            ->registerFilamentServing()
            ->registerTypes()
            ->registerComponents()
            ->registerAssets()
            ->registerSchemaExtenders()
            ->registerCloneableAndDraftableRelations()
            ->registerThemeViewPath()
            ->registerFilamentAssets();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            path: __DIR__,
            description: static::getDescription(),
            permissions: $this->getPackagePermissions(),
            installCommand: 'capell-layout:install',
            demoCommand: 'capell-layout:demo',
            upgradeCommand: 'capell-layout:upgrade',
            demoParams: ['author', 'sites'],
            requirements: [
                AdminServiceProvider::$packageName,
                FrontendServiceProvider::$packageName,
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

    private function getPackagePermissions(): array
    {
        return [
            'create_content',
            'reorder_content',
            'replicate_content',
            'restore_any_content',
            'restore_content',
            'update_content',
            'view_any_content',
            'view_content',
        ];
    }

    private function registerManager(): self
    {
        App::singleton(CapellLayoutManager::class, fn (): CapellLayoutManager => new CapellLayoutManager);

        return $this;
    }

    private function registerFilamentServing(): self
    {
        Filament::serving(function (): void {
            $this->registerEvents();
        });

        return $this;
    }

    private function registerResources(): self
    {
        CapellAdmin::registerResource(LayoutResourceEnum::Content->name, class: LayoutResourceEnum::Content->value);
        CapellAdmin::registerResource(LayoutResourceEnum::Widget->name, class: LayoutResourceEnum::Widget->value);
        CapellAdmin::registerResource(ResourceEnum::Layout, class: LayoutResource::class);

        return $this;
    }

    private function registerTypes(): self
    {
        foreach (LayoutTypeEnum::cases() as $type) {
            CapellCore::registerType(
                new TypeData(
                    name: $type->value,
                    model: $type->getModel(),
                    creatorClass: $type->getCreatorClass(),
                ),
            );
        }

        return $this;
    }

    private function registerComponents(): self
    {
        foreach (ComponentTypeEnum::cases() as $componentType) {
            /** @var class-string $enumClass */
            $enumClass = $componentType->value;
            CapellCore::registerComponents($componentType->name, $enumClass::cases());
        }

        return $this;
    }

    private function registerAssets(): self
    {
        $contentAsset = AssetEnum::Content;

        CapellCore::registerAsset(
            new AssetData(
                name: $contentAsset->name,
                model: $contentAsset->getModel(),
                icon: $contentAsset->getIcon(),
                hasTranslations: $contentAsset->hasTranslations(),
            ),
        );

        CapellAdmin::registerAsset(
            $contentAsset,
            new AdminAssetData(
                formClass: $contentAsset->getFormClass(),
                createAction: $contentAsset->getCreateActionClass(),
                defaultDataAction: $contentAsset->getDefaultDataActionClass(),
            ),
        );

        CapellFrontend::registerAsset($contentAsset, new FrontendAssetData(
            component: $contentAsset->getComponent(),
        ));

        return $this;
    }

    private function registerSchemaExtenders(): self
    {
        $this->registerSchemaExtender(SchemaExtenderEnum::Page->value, PageSchemaExtender::class);

        $this->registerSchemaExtender(SchemaExtenderEnum::Layout->value, LayoutSchemaExtender::class);

        return $this;
    }

    private function registerCloneableAndDraftableRelations(): self
    {
        CapellCore::addCloneableRelations('page', 'widgetAssets');
        CapellCore::addDraftableRelations('page', 'widgetAssets');

        return $this;
    }

    private function registerLivewireComponents(): self
    {
        foreach (config('capell-layout.livewire_components', []) as $name => $class) {
            Livewire::component($name, $class);
        }

        return $this;
    }

    private function registerBladeComponents(): self
    {
        foreach (config('capell-layout.blade_components') as $name => $component) {
            Blade::component($name, $component);
        }

        Blade::componentNamespace('Capell\\Layout\\View\\Components', 'capell-layout');
        Blade::anonymousComponentNamespace('Capell\\Layout\\View\\Components');

        return $this;
    }

    private function registerThemeViewPath(): self
    {
        $dir = $this->package->basePath('/../resources/views/capell/');

        throw_if(in_array($dir, ['', '0', false], true) || ! is_dir($dir), Exception::class, 'Theme view path not found: ' . $dir);

        app(Factory::class)->prependNamespace('capell', $dir);

        return $this;
    }

    private function registerFilamentAssets(): self
    {
        $publishDir = self::getPublishedDirectory();

        FilamentAsset::register(
            [
                Css::make('capell-layout-filament', $publishDir . '/build/admin/capell-layout-filament.css'),
                AlpineComponent::make('layout-builder', $publishDir . '/build/admin/layout-builder.js')
                    ->loadedOnRequest(),
            ],
            package: 'capell-layout',
        );

        return $this;
    }

    private function registerModels(): self
    {
        LayoutModelRegistrar::register();

        return $this;
    }

    private function registerListeners(): self
    {
        CapellCore::subscriberManager()->subscribe(AfterRecordSaved::class);
        CapellCore::subscriberManager()->subscribe(SiteTreeRebuilt::class);
        CapellCore::subscriberManager()->subscribe(TypeValidated::class);
        CapellCore::subscriberManager()->subscribe(LayoutLoaded::class);

        return $this;
    }

    private function registerEvents(): self
    {
        $createDeleteModels = [
            CapellCore::getModel(ModelEnum::Content->name),
        ];

        foreach ($createDeleteModels as $modelClass) {
            $modelClass::registerModelEvent('created', function (Model $model): void {
                CreatedModelAction::run($model);
            });

            $modelClass::registerModelEvent('deleted', function (Model $model): void {
                DeletedModelAction::run($model);
            });
        }

        return $this;
    }

    private function registerPublishCommands(): self
    {
        $vendorAssets = $this->package->basePath('/../publishes/build');
        $appAssets = public_path('vendor/' . $this->package->shortName());

        $this->publishes([$vendorAssets => $appAssets], $this->package->shortName() . '-assets');

        return $this;
    }

    private function registerSchemas(): self
    {
        foreach (Enums\SchemaTypeEnum::getAllSchemas() as $type => $schemas) {
            CapellAdmin::registerSchemas($type, $schemas, defaultSchemas: true);
        }

        CapellAdmin::registerSchema(SchemaTypeEnum::Type, ContentTypeSchema::class);
        CapellAdmin::registerSchema(SchemaTypeEnum::Type, WidgetTypeSchema::class);

        return $this;
    }

    private function registerSchemaExtender(string $tag, string $class): void
    {
        $this->app->singleton($class, fn (): object => new $class);

        $this->app->tag($class, $tag);
    }

    private function registerRelationships(): self
    {
        Page::resolveRelationUsing(
            'contents',
            fn (Page $model): HasManyThrough => $model->hasManyThrough(
                ModelEnum::Content->value,
                ModelEnum::WidgetAsset->value,
                'page_id',
                'id',
                'id',
                'asset_id',
            )
                ->where('widget_assets.asset_type', (new Content)->getMorphClass()),
        );

        Page::resolveRelationUsing(
            'widgetAssets',
            fn (Page $model): HasMany => $model->hasMany(ModelEnum::WidgetAsset->value, 'page_id'),
        );

        Page::resolveRelationUsing(
            'widgets',
            fn (Page $model): MorphToMany => $model->morphToMany(
                ModelEnum::Widget->value,
                'asset',
                'widget_assets',
                'asset_id',
                'widget_id',
            )
                ->wherePivot('asset_type', $model->getMorphClass()),
        );

        Site::resolveRelationUsing(
            'contents',
            fn (Site $model): HasMany => $model->hasMany(ModelEnum::Content->value, 'site_id'),
        );

        Type::resolveRelationUsing(
            'contents',
            fn (Type $model): HasMany => $model->hasMany(ModelEnum::Content->value, 'type_id'),
        );

        Type::resolveRelationUsing(
            'widgets',
            fn (Type $model) => $model->hasMany(ModelEnum::Widget->value, 'type_id'),
        );

        Layout::resolveRelationUsing(
            'layoutWidgets',
            fn (Layout $model): BelongsToJson => $model->belongsToJson(
                ModelEnum::Widget->value,
                'widgets',
                'key',
            ),
        );

        return $this;
    }
}
