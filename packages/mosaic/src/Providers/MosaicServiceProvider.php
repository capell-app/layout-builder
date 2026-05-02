<?php

declare(strict_types=1);

namespace Capell\Mosaic\Providers;

use Capell\Admin\Actions\CreatedModelAction;
use Capell\Admin\Actions\DeletedModelAction;
use Capell\Admin\Data\AdminAssetData;
use Capell\Admin\Enums\ConfiguratorTypeEnum as AdminConfiguratorTypeEnum;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Contracts\Makers\MakerRegistryInterface;
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
use Capell\Frontend\Contracts\AssetsRegistryInterface;
use Capell\Frontend\Data\FrontendAssetData;
use Capell\Mosaic\Console\Commands\DemoCommand;
use Capell\Mosaic\Console\Commands\FakerCommand;
use Capell\Mosaic\Console\Commands\Hero\DemoCommand as HeroDemoCommand;
use Capell\Mosaic\Console\Commands\Hero\SetupCommand as HeroSetupCommand;
use Capell\Mosaic\Console\Commands\InstallCommand;
use Capell\Mosaic\Console\Commands\MakeWidgetCommand;
use Capell\Mosaic\Console\Commands\SetupCommand;
use Capell\Mosaic\Console\Commands\UpgradeCommand;
use Capell\Mosaic\Enums\AssetEnum;
use Capell\Mosaic\Enums\ComponentTypeEnum;
use Capell\Mosaic\Enums\ConfiguratorTypeEnum;
use Capell\Mosaic\Enums\LayoutTypeEnum;
use Capell\Mosaic\Enums\LivewireComponentsEnum;
use Capell\Mosaic\Enums\ResourceEnum as LayoutResourceEnum;
use Capell\Mosaic\Filament\Configurators\Types\ContentTypeConfigurator;
use Capell\Mosaic\Filament\Configurators\Types\WidgetTypeConfigurator;
use Capell\Mosaic\Filament\Extenders\Page\HeroPageSchemaExtender;
use Capell\Mosaic\Filament\Resources\Layouts\LayoutResource;
use Capell\Mosaic\Filament\Resources\Layouts\Schemas\Extenders\LayoutSchemaExtender;
use Capell\Mosaic\Filament\Resources\Pages\Schemas\Extenders\PageSchemaExtender;
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
use Capell\Mosaic\Support\LayoutAssetBridgeRegistry;
use Capell\Mosaic\Support\LayoutModelRegistrar;
use Capell\Mosaic\Support\LayoutPresets\LayoutPresetRegistry;
use Capell\Mosaic\Support\Makers\MosaicWidgetMaker;
use Capell\Workspaces\WorkspaceRegistry;
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

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasConfigFile()
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasCommands([
                DemoCommand::class,
                FakerCommand::class,
                HeroDemoCommand::class,
                HeroSetupCommand::class,
                InstallCommand::class,
                MakeWidgetCommand::class,
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
            ->registerLayoutAssetBridgeRegistry()
            ->registerLayoutPresetRegistry()
            ->registerPackageMetadata()
            ->registerBlazeComponents();

        $this->callAfterResolving(MakerRegistryInterface::class, function (MakerRegistryInterface $registry): void {
            $registry->register($this->app->make(MosaicWidgetMaker::class));
        });

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
            ->registerConfigurators()
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
            ->registerVendorAssets()
            ->registerWorkspaces();
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
        $layoutModel = Layout::class;

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
            version: $this->getVersion(),
            description: fn (): string => __('capell-mosaic::package.description'),
            setupCommand: 'capell:mosaic-setup',
        );

        $package = CapellCore::getPackage(static::$packageName);
        $package->installCommand = 'capell:mosaic-install';
        $package->demoCommand = 'capell:mosaic-demo';
        $package->demoParams = ['sites', 'user'];

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

    private function registerManager(): self
    {
        App::singleton(CapellLayoutManager::class, fn (): CapellLayoutManager => new CapellLayoutManager);

        return $this;
    }

    private function registerLayoutAssetBridgeRegistry(): self
    {
        App::singleton(LayoutAssetBridgeRegistry::class, fn (): LayoutAssetBridgeRegistry => new LayoutAssetBridgeRegistry);

        return $this;
    }

    private function registerLayoutPresetRegistry(): self
    {
        App::singleton(LayoutPresetRegistry::class, fn (): LayoutPresetRegistry => new LayoutPresetRegistry);

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
        $sectionAsset = AssetEnum::Section;

        CapellCore::registerAsset(
            new AssetData(
                name: $sectionAsset->name,
                model: $sectionAsset->getModel(),
                icon: $sectionAsset->getIcon(),
                hasTranslations: $sectionAsset->hasTranslations(),
            ),
        );

        CapellAdmin::registerAsset(
            $sectionAsset,
            new AdminAssetData(
                formClass: $sectionAsset->getFormClass(),
                createAction: $sectionAsset->getCreateActionClass(),
                defaultDataAction: $sectionAsset->getDefaultDataActionClass(),
            ),
        );

        // Defer frontend asset registration until the registry is resolved by FrontendServiceProvider
        $this->callAfterResolving(AssetsRegistryInterface::class, function (AssetsRegistryInterface $assets) use ($sectionAsset): void {
            $assets->registerAsset(
                $sectionAsset,
                new FrontendAssetData(
                    component: $sectionAsset->getComponent(),
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
                classNamespace: 'Capell\\Mosaic\\Livewire',
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
        Blade::componentNamespace('Capell\\Mosaic\\View\\Components', 'capell-mosaic');
        Blade::anonymousComponentNamespace('Capell\\Mosaic\\View\\Components');

        Blade::componentNamespace('Capell\\Mosaic\\View\\Components', 'capell-hero');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell-hero');

        return $this;
    }

    private function registerBlazeComponents(): self
    {
        RegisterBlazeOptimizedViewsAction::run(__DIR__ . '/../../resources/views/components');

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
            VendorAssetData::tailwindImport('swiper/css', static::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('swiper/css/autoplay', static::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('swiper/css/pagination', static::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('swiper/css/navigation', static::$packageName),
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

        CapellCore::registerVendorAsset(
            VendorAssetData::npmDependency('swiper', '^12.1.3', static::$packageName),
        );

        return $this;
    }

    private function registerModels(): self
    {
        LayoutModelRegistrar::register();

        return $this;
    }

    private function registerWorkspaces(): self
    {
        if (! class_exists(WorkspaceRegistry::class)) {
            return $this;
        }

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
            Section::class,
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

    private function registerConfigurators(): self
    {
        foreach (ConfiguratorTypeEnum::getAllConfigurators() as $type => $configurators) {
            CapellAdmin::registerConfigurators($type, $configurators, defaultConfigurators: true);
        }

        CapellAdmin::registerConfigurator(AdminConfiguratorTypeEnum::Type, ContentTypeConfigurator::class);
        CapellAdmin::registerConfigurator(AdminConfiguratorTypeEnum::Type, WidgetTypeConfigurator::class);

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
            'sections',
            fn (Page $model): HasManyThrough => $model->hasManyThrough(
                Section::class,
                WidgetAsset::class,
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
            fn (Page $model): MorphMany => $model->morphMany(WidgetAsset::class, 'pageable'),
        );

        Page::resolveRelationUsing(
            'widgets',
            fn (Page $model): MorphToMany => $model->morphToMany(
                Widget::class,
                'asset',
                'widget_assets',
                'asset_id',
                'widget_id',
            )
                ->wherePivot('asset_type', $model->getMorphClass()),
        );

        Site::resolveRelationUsing(
            'sections',
            fn (Site $model): HasMany => $model->hasMany(Section::class, 'site_id'),
        );

        Type::resolveRelationUsing(
            'sections',
            fn (Type $model): HasMany => $model->hasMany(Section::class, 'type_id'),
        );

        Type::resolveRelationUsing(
            'widgets',
            fn (Type $model) => $model->hasMany(Widget::class, 'type_id'),
        );

        Layout::resolveRelationUsing(
            'layoutWidgets',
            fn (Layout $model): BelongsToJson => $model->belongsToJson(
                Widget::class,
                'widgets',
                'key',
            ),
        );

        return $this;
    }
}
