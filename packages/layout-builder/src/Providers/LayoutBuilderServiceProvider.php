<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Providers;

use BackedEnum;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\ConfiguratorTypeEnum as AdminConfiguratorTypeEnum;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\AIOrchestrator\Support\AIOrchestratorModuleRegistry;
use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Contracts\Makers\MakerRegistryInterface;
use Capell\Core\Data\PageTypeData;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Enums\AssetComponentEnum;
use Capell\Core\Enums\AssetEnum as CoreAssetEnum;
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Type;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Frontend\Contracts\AssetsRegistryInterface;
use Capell\Frontend\Contracts\FrontendComponentRegistryInterface;
use Capell\Frontend\Data\FrontendAssetData;
use Capell\LayoutBuilder\Actions\InvalidateTypeLayoutPreviewImagesAction;
use Capell\LayoutBuilder\AIOrchestrator\LayoutBuilderAIOrchestratorModule;
use Capell\LayoutBuilder\Console\Commands\DemoCommand;
use Capell\LayoutBuilder\Console\Commands\FakerCommand;
use Capell\LayoutBuilder\Console\Commands\Hero\DemoCommand as HeroDemoCommand;
use Capell\LayoutBuilder\Console\Commands\Hero\SetupCommand as HeroSetupCommand;
use Capell\LayoutBuilder\Console\Commands\InstallCommand;
use Capell\LayoutBuilder\Console\Commands\MakeWidgetCommand;
use Capell\LayoutBuilder\Console\Commands\SetupCommand;
use Capell\LayoutBuilder\Console\Commands\UpgradeCommand;
use Capell\LayoutBuilder\Enums\ComponentTypeEnum;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\FrontendComponentKeyEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Enums\LivewireComponentsEnum;
use Capell\LayoutBuilder\Enums\ResourceEnum as LayoutResourceEnum;
use Capell\LayoutBuilder\Filament\Configurators\Types\WidgetTypeConfigurator;
use Capell\LayoutBuilder\Filament\Extenders\Page\HeroPageSchemaExtender;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Layouts\Schemas\Extenders\LayoutSchemaExtender;
use Capell\LayoutBuilder\Filament\Resources\Pages\Schemas\Extenders\PageSchemaExtender;
use Capell\LayoutBuilder\Listeners\AfterRecordSaved;
use Capell\LayoutBuilder\Listeners\LayoutLoaded;
use Capell\LayoutBuilder\Listeners\LayoutSavingListener;
use Capell\LayoutBuilder\Listeners\SiteTreeRebuilt;
use Capell\LayoutBuilder\Listeners\TypeValidated;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Support\Interceptors\Layouts\DefaultLayoutInterceptor;
use Capell\LayoutBuilder\Support\Interceptors\Layouts\HomeLayoutInterceptor;
use Capell\LayoutBuilder\Support\Interceptors\Layouts\ResultsLayoutInterceptor;
use Capell\LayoutBuilder\Support\LayoutAssetBridgeRegistry;
use Capell\LayoutBuilder\Support\LayoutModelRegistrar;
use Capell\LayoutBuilder\Support\LayoutPresets\LayoutPresetRegistry;
use Capell\LayoutBuilder\Support\Makers\LayoutBuilderWidgetMaker;
use Capell\LayoutBuilder\View\Components\Widget\Page\Children as PageChildrenComponent;
use Capell\LayoutBuilder\View\Components\Widget\Page\Content as PageContentComponent;
use Capell\LayoutBuilder\View\Components\Widget\Page\Latest as PageLatestComponent;
use Capell\LayoutBuilder\View\Components\Widget\Page\Siblings as PageSiblingsComponent;
use Capell\PublishingStudio\WorkspaceRegistry;
use Composer\InstalledVersions;
use Exception;
use Filament\Facades\Filament;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use RuntimeException;
use Spatie\LaravelPackageTools\Package;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

class LayoutBuilderServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-layout-builder';

    public static string $packageName = 'capell-app/layout-builder';

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
        $this->registerPackageMetadata();

        $this->app->booted(function (): void {
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
        $this->callAfterResolving(MakerRegistryInterface::class, function (MakerRegistryInterface $registry): void {
            $registry->register($this->app->make(LayoutBuilderWidgetMaker::class));
        });

        return $this
            ->registerModels()
            ->registerModelFillableAndCasts()
            ->registerRelationships()
            ->registerLayoutAssetBridgeRegistry()
            ->registerLayoutPresetRegistry()
            ->registerAIOrchestratorModule()
            ->registerResources()
            ->registerListeners()
            ->registerConfigurators()
            ->registerManager()
            ->registerFilamentServing()
            ->registerTypes()
            ->registerComponents()
            ->registerModelEvents()
            ->registerModelInterceptors()
            ->registerSchemaExtenders()
            ->registerCloneableRelations()
            ->registerThemeViewPath()
            ->registerFrontendComponents()
            ->registerFilamentAssets()
            ->registerFrontendAssets()
            ->registerPublishCommands()
            ->registerLivewireComponents()
            ->registerBladeComponents()
            ->registerBlazeComponents()
            ->registerVendorAssets()
            ->registerPublishingStudio();
    }

    private function registerModelEvents(): self
    {
        Layout::saving(resolve(LayoutSavingListener::class));
        Type::updated(function (Type $type): void {
            if (! $this->isWidgetType($type)) {
                return;
            }

            if (! $type->wasChanged(['name', 'admin'])) {
                return;
            }

            InvalidateTypeLayoutPreviewImagesAction::run($type);
        });

        return $this;
    }

    private function isWidgetType(Type $type): bool
    {
        $rawType = $type->getRawOriginal('type');

        return $rawType === LayoutTypeEnum::Widget->value;
    }

    private function registerFrontendAssets(): self
    {
        $this->callAfterResolving(AssetsRegistryInterface::class, function (AssetsRegistryInterface $assets): void {
            $assets->registerAsset(
                CoreAssetEnum::Page,
                new FrontendAssetData(
                    component: AssetComponentEnum::Page->value,
                ),
            );
        });

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
            description: fn (): string => __('capell-layout-builder::package.description'),
            setupCommand: 'capell:layout-builder-setup',
        );

        $package = CapellCore::getPackage(static::$packageName);
        $package->installCommand = 'capell:layout-builder-install';
        $package->demoCommand = 'capell:layout-builder-demo';
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

    private function registerAIOrchestratorModule(): self
    {
        if (! class_exists(AIOrchestratorModuleRegistry::class)) {
            return $this;
        }

        if (! CapellCore::isPackageInstalled('capell-app/ai-orchestrator')) {
            return $this;
        }

        $this->app->afterResolving(
            AIOrchestratorModuleRegistry::class,
            function (AIOrchestratorModuleRegistry $registry): void {
                $registry->register(new LayoutBuilderAIOrchestratorModule);
            },
        );

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
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: LayoutResourceEnum::Widget->value,
            group: LayoutResourceEnum::Widget->name,
        ));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: LayoutResource::class,
            group: ResourceEnum::Layout->name,
        ));

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

        CapellCore::registerComponents(ComponentTypeEnum::Asset, FrontendComponentKeyEnum::cases());

        return $this;
    }

    private function registerFrontendComponents(): self
    {
        $this->callAfterResolving(FrontendComponentRegistryInterface::class, function (FrontendComponentRegistryInterface $registry): void {
            $registry
                ->register(
                    key: FrontendComponentKeyEnum::SectionBlock->value,
                    component: 'capell-layout-builder::section.block',
                    aliases: [
                        'capell-content-sections::section.block',
                        'capell-layout-builder::section.block',
                    ],
                    props: [
                        'asset',
                        'class',
                        'color',
                        'icon',
                        'image',
                        'linkText',
                        'loop',
                        'meta',
                        'size',
                        'summary',
                        'tags',
                        'title',
                        'url',
                    ],
                )
                ->register(
                    key: FrontendComponentKeyEnum::SectionTeamMember->value,
                    component: 'capell-layout-builder::section.team-member',
                    aliases: [
                        'capell-content-sections::section.team-member',
                        'capell-layout-builder::section.team-member',
                    ],
                    props: [
                        'asset',
                        'class',
                        'color',
                        'icon',
                        'image',
                        'linkText',
                        'loop',
                        'meta',
                        'size',
                        'summary',
                        'title',
                        'url',
                    ],
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
                namespace: 'capell-layout-builder',
                classNamespace: 'Capell\\LayoutBuilder\\Livewire',
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
        Blade::componentNamespace('Capell\\LayoutBuilder\\View\\Components', 'capell-layout-builder');
        Blade::anonymousComponentNamespace('Capell\\LayoutBuilder\\View\\Components');
        Blade::component('capell-layout-builder::components.widget.page.breadcrumbs', 'capell-layout-builder-widget-page-breadcrumbs');
        Blade::component(PageContentComponent::class, 'capell-layout-builder-widget-page-content');
        Blade::component('capell-layout-builder::components.widget.slot', 'capell-layout-builder-widget-slot');
        Blade::component(PageChildrenComponent::class, 'capell-layout-builder::widget.page.children');
        Blade::component(PageContentComponent::class, 'capell-layout-builder::widget.page.content');
        Blade::component(PageLatestComponent::class, 'capell-layout-builder::widget.page.latest');
        Blade::component(PageSiblingsComponent::class, 'capell-layout-builder::widget.page.siblings');

        Blade::componentNamespace('Capell\\LayoutBuilder\\View\\Components', 'capell-hero');
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
                Css::make('capell-layout-builder-filament', $publishDir . '/build/admin/capell-layout-builder-filament.css'),
                AlpineComponent::make('layout-builder', $publishDir . '/build/admin/layout-builder.js')
                    ->loadedOnRequest(),
            ],
            package: 'capell-layout-builder',
        );

        return $this;
    }

    private function registerVendorAssets(): self
    {
        CapellCore::registerVendorAsset(
            VendorAssetData::buildAsset(
                path: 'vendor/capell-layout-builder/frontend',
                file: 'resources/js/capell-layout-builder.js',
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
            VendorAssetData::tailwindImport('resources/css/capell-layout-builder.css', static::$packageName),
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

    private function registerPublishingStudio(): self
    {
        if (! class_exists(WorkspaceRegistry::class)) {
            return $this;
        }

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
        return $this;
    }

    private function registerPublishCommands(): self
    {
        $this->publishes([
            $this->package->basePath('/../publishes/build') => public_path('vendor/capell-layout-builder'),
        ], 'capell-layout-builder-assets');

        return $this;
    }

    private function registerConfigurators(): self
    {
        foreach (ConfiguratorTypeEnum::getAllConfigurators() as $type => $configurators) {
            foreach ($configurators as $configurator) {
                $configuratorClass = $configurator instanceof BackedEnum ? $configurator->value : $configurator;

                CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::configurator(
                    class: $configuratorClass,
                    group: $type,
                    name: $configuratorClass::getKey(),
                ));
            }
        }

        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::configurator(
            class: WidgetTypeConfigurator::class,
            group: AdminConfiguratorTypeEnum::Type->value,
            name: WidgetTypeConfigurator::getKey(),
        ));

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

        Type::resolveRelationUsing(
            'widgets',
            fn (Type $model): HasMany => $model->hasMany(Widget::class, 'type_id'),
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
