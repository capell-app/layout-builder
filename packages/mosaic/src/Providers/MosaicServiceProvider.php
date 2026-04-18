<?php

declare(strict_types=1);

namespace Capell\Mosaic\Providers;

use Capell\Admin\Actions\CreatedModelAction;
use Capell\Admin\Actions\DeletedModelAction;
use Capell\Admin\Data\AdminAssetData;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Enums\SchemaTypeEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Core\Data\AssetData;
use Capell\Core\Data\PageTypeData;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Workspaces\WorkspaceRegistry;
use Capell\Frontend\Contracts\AssetsRegistryInterface;
use Capell\Frontend\Data\FrontendAssetData;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Mosaic\Console\Commands\DemoCommand;
use Capell\Mosaic\Console\Commands\HeroDemoCommand;
use Capell\Mosaic\Console\Commands\HeroSetupCommand;
use Capell\Mosaic\Console\Commands\InstallCommand;
use Capell\Mosaic\Console\Commands\SetupCommand;
use Capell\Mosaic\Console\Commands\UpgradeCommand;
use Capell\Mosaic\Enums\AssetEnum;
use Capell\Mosaic\Enums\ComponentTypeEnum;
use Capell\Mosaic\Enums\LayoutTypeEnum;
use Capell\Mosaic\Enums\LivewireComponentsEnum;
use Capell\Mosaic\Enums\ModelEnum;
use Capell\Mosaic\Enums\ResourceEnum as LayoutResourceEnum;
use Capell\Mosaic\Enums\TypeSchemaEnum;
use Capell\Mosaic\Filament\Resources\Layouts\LayoutResource;
use Capell\Mosaic\Filament\Resources\Layouts\Schemas\Extenders\LayoutSchemaExtender;
use Capell\Mosaic\Filament\Resources\Pages\Schemas\Extenders\HeroPageSchemaExtender;
use Capell\Mosaic\Filament\Resources\Pages\Schemas\Extenders\PageSchemaExtender;
use Capell\Mosaic\Filament\Resources\Types\Schemas\Types\ContentTypeSchema;
use Capell\Mosaic\Filament\Resources\Types\Schemas\Types\WidgetTypeSchema;
use Capell\Mosaic\Listeners\AfterRecordSaved;
use Capell\Mosaic\Listeners\LayoutLoaded;
use Capell\Mosaic\Listeners\LayoutSavingListener;
use Capell\Mosaic\Listeners\SiteTreeRebuilt;
use Capell\Mosaic\Listeners\TypeValidated;
use Capell\Mosaic\Models\Section;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Models\WidgetAsset;
use Capell\Mosaic\Support\CapellLayoutManager;
use Capell\Mosaic\Support\Interceptors\Layouts\DefaultLayoutInterceptor;
use Capell\Mosaic\Support\Interceptors\Layouts\HomeLayoutInterceptor;
use Capell\Mosaic\Support\Interceptors\Layouts\ResultsLayoutInterceptor;
use Capell\Mosaic\Support\LayoutModelRegistrar;
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
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use RuntimeException;
use Spatie\LaravelPackageTools\Package;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

class MosaicServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-mosaic';

    public static string $packageName = 'capell-app/mosaic';

    public static string $description = 'Managing content and widgets.';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasConfigFile()
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasCommands([
                DemoCommand::class,
                HeroDemoCommand::class,
                HeroSetupCommand::class,
                InstallCommand::class,
                SetupCommand::class,
                UpgradeCommand::class,
            ]);
    }

    public function registeringPackage(): void
    {
        $this
            ->registerResources()
            ->registerModels()
            ->registerModelFillableAndCasts()
            ->registerRelationships()
            ->registerPackageMetadata();

        $this->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this->bootInstalledPackage();
        });
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

    private function bootInstalledPackage(): self
    {
        return $this
            ->registerListeners()
            ->registerSchemas()
            ->registerManager()
            ->registerFilamentServing()
            ->registerTypes()
            ->registerComponents()
            ->registerModelEvents()
            ->registerModelInterceptors()
            ->registerAssets()
            ->registerSchemaExtenders()
            ->registerCloneableRelations()
            ->registerThemeViewPath()
            ->registerFilamentAssets()
            ->registerPublishCommands()
            ->registerLivewireComponents()
            ->registerBladeComponents()
            ->registerVendorAssets();
    }

    private function registerModelEvents(): self
    {
        Layout::saving(resolve(LayoutSavingListener::class));

        return $this;
    }

    private function registerModelFillableAndCasts(): self
    {
        Layout::addFillable(['containers', 'widgets']);

        Layout::addCasts([
            'containers' => 'array',
            'widgets' => 'array',
        ]);

        return $this;
    }

    private function registerModelInterceptors(): self
    {
        $layoutModel = CapellCore::getModel(\Capell\Core\Enums\ModelEnum::Layout);

        CapellCore::registerModelInterceptor($layoutModel, DefaultLayoutInterceptor::class, LayoutEnum::Default);
        CapellCore::registerModelInterceptor($layoutModel, HomeLayoutInterceptor::class, LayoutEnum::Home);
        CapellCore::registerModelInterceptor($layoutModel, ResultsLayoutInterceptor::class, LayoutEnum::Results);

        return $this;
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            description: static::getDescription(),
            permissions: $this->getPackagePermissions(),
            installCommand: 'capell:layout-install',
            setupCommand: 'capell:layout-setup',
            upgradeCommand: 'capell:layout-upgrade',
            demoCommand: 'capell:layout-demo',
            demoParams: ['user', 'sites'],
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
        CapellAdmin::registerResource(LayoutResourceEnum::Section->name, class: LayoutResourceEnum::Section->value);
        CapellAdmin::registerResource(LayoutResourceEnum::Widget->name, class: LayoutResourceEnum::Widget->value);
        CapellAdmin::registerResource(ResourceEnum::Layout, class: LayoutResource::class);

        return $this;
    }

    private function registerTypes(): self
    {
        foreach (LayoutTypeEnum::cases() as $type) {
            CapellCore::registerPageType(
                new PageTypeData(
                    name: $type->value,
                    model: $type->getModel(),
                    // TODO when this is translated this causes Livewire error: Exception: Property type not supported in Livewire for property: [{}]
                    label: $type->getLabel(),
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
        $contentAsset = AssetEnum::Section;

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

        // Defer frontend asset registration until the registry is resolved by FrontendServiceProvider
        $this->callAfterResolving(AssetsRegistryInterface::class, function (AssetsRegistryInterface $assets) use ($contentAsset): void {
            $assets->registerAsset(
                $contentAsset,
                new FrontendAssetData(
                    component: $contentAsset->getComponent(),
                ),
            );
        });

        return $this;
    }

    private function registerSchemaExtenders(): self
    {
        $this->registerSchemaExtender(SchemaExtenderEnum::Page->value, PageSchemaExtender::class);

        $this->registerSchemaExtender(SchemaExtenderEnum::Page->value, HeroPageSchemaExtender::class);

        $this->registerSchemaExtender(SchemaExtenderEnum::Layout->value, LayoutSchemaExtender::class);

        return $this;
    }

    private function registerCloneableRelations(): self
    {
        CapellCore::addCloneableRelations('page', 'widgetAssets');

        return $this;
    }

    private function registerLivewireComponents(): self
    {
        if ($this->isLivewireV3()) {
            foreach (LivewireComponentsEnum::getComponents() as $name => $component) {
                if (! $component) {
                    continue;
                }

                Livewire::component($name, $component);
            }
        } else {
            Livewire::addNamespace(
                namespace: 'capell-mosaic',
                classNamespace: 'Capell\\Layout\\Livewire',
                classPath: __DIR__ . '/../Livewire',
                classViewPath: __DIR__ . '/../../resources/views/livewire',
            );
        }

        return $this;
    }

    private function isLivewireV3(): bool
    {
        $version = InstalledVersions::getVersion('livewire/livewire');

        return version_compare($version, '4.0.0', '<');
    }

    private function registerBladeComponents(): self
    {
        Blade::componentNamespace('Capell\\Layout\\View\\Components', 'capell-mosaic');
        Blade::anonymousComponentNamespace('Capell\\Layout\\View\\Components');

        return $this;
    }

    private function registerThemeViewPath(): self
    {
        $dir = $this->package->basePath('/../resources/views/capell/');

        throw_if(in_array($dir, ['', '0', false], true) || ! is_dir($dir), Exception::class, 'Theme view path not found: ' . $dir);

        resolve(Factory::class)->prependNamespace('capell', $dir);

        return $this;
    }

    private function registerFilamentAssets(): self
    {
        $publishDir = self::getPublishedDirectory();

        FilamentAsset::register(
            [
                Css::make('capell-mosaic-filament', $publishDir . '/build/admin/capell-mosaic-filament.css'),
                AlpineComponent::make('layout-builder', $publishDir . '/build/admin/layout-builder.js')
                    ->loadedOnRequest(),
            ],
            package: 'capell-mosaic',
        );

        return $this;
    }

    private function registerVendorAssets(): self
    {
        CapellCore::registerVendorAsset(
            VendorAssetData::buildAsset(
                path: 'vendor/capell-mosaic/frontend',
                file: 'resources/js/capell-mosaic.js',
                packageName: self::$packageName,
            ),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', static::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('tippy.js/dist/tippy.css', static::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('resources/css/capell-mosaic.css', static::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindPlugin('@tailwindcss/typography', static::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::npmDependency('tippy.js', '^6.3.7', static::$packageName),
        );

        return $this;
    }

    private function registerModels(): self
    {
        LayoutModelRegistrar::register();

        WorkspaceRegistry::register(Section::class);
        WorkspaceRegistry::register(Widget::class);
        WorkspaceRegistry::register(WidgetAsset::class);

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
            CapellCore::getModel(ModelEnum::Section->name),
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
        $this->publishes([
            $this->package->basePath('/../publishes/build') => public_path('vendor/capell-mosaic'),
        ], 'capell-mosaic-assets');

        return $this;
    }

    private function registerSchemas(): self
    {
        foreach (TypeSchemaEnum::getAllSchemas() as $type => $schemas) {
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
                ModelEnum::Section->value,
                ModelEnum::WidgetAsset->value,
                'pageable_id',
                'id',
                'id',
                'asset_id',
            )
                ->where('widget_assets.pageable_type', $model->getMorphClass())
                ->where('widget_assets.asset_type', (new Section)->getMorphClass()),
        );

        Page::resolveRelationUsing(
            'widgetAssets',
            fn (Page $model): MorphMany => $model->morphMany(ModelEnum::WidgetAsset->value, 'pageable'),
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
            fn (Site $model): HasMany => $model->hasMany(ModelEnum::Section->value, 'site_id'),
        );

        Type::resolveRelationUsing(
            'contents',
            fn (Type $model): HasMany => $model->hasMany(ModelEnum::Section->value, 'type_id'),
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
