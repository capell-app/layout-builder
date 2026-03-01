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
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Frontend\Contracts\AssetsRegistryInterface;
use Capell\Frontend\Data\FrontendAssetData;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Frontend\Support\Blocks\BlockRegistry;
use Capell\Layout\Console\Commands\DemoCommand;
use Capell\Layout\Console\Commands\InstallCommand;
use Capell\Layout\Console\Commands\SetupCommand;
use Capell\Layout\Enums\AssetEnum;
use Capell\Layout\Enums\ComponentTypeEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Enums\ResourceEnum as LayoutResourceEnum;
use Capell\Layout\Enums\TypeSchemaEnum;
use Capell\Layout\Filament\Resources\Layouts\LayoutResource;
use Capell\Layout\Filament\Resources\Layouts\Schemas\Extenders\LayoutSchemaExtender;
use Capell\Layout\Filament\Resources\Pages\Schemas\Extenders\PageSchemaExtender;
use Capell\Layout\Filament\Resources\Types\Schemas\Types\ContentTypeSchema;
use Capell\Layout\Filament\Resources\Types\Schemas\Types\WidgetTypeSchema;
use Capell\Layout\Listeners\AfterRecordSaved;
use Capell\Layout\Listeners\LayoutLoaded;
use Capell\Layout\Listeners\LayoutSavingListener;
use Capell\Layout\Listeners\SiteTreeRebuilt;
use Capell\Layout\Listeners\TypeValidated;
use Capell\Layout\Models\Content;
use Capell\Layout\Support\CapellLayoutManager;
use Capell\Layout\Support\Interceptors\Layouts\DefaultLayoutInterceptor;
use Capell\Layout\Support\Interceptors\Layouts\HomeLayoutInterceptor;
use Capell\Layout\Support\Interceptors\Layouts\ResultsLayoutInterceptor;
use Capell\Layout\Support\Interceptors\Themes\DefaultThemeInterceptor;
use Capell\Layout\Support\LayoutModelRegistrar;
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

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasConfigFile()
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasCommands([
                DemoCommand::class,
                InstallCommand::class,
                SetupCommand::class,
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
            ->registerCloneableAndDraftableRelations()
            ->registerThemeViewPath()
            ->registerFilamentAssets()
            ->registerPublishCommands()
            ->registerLivewireComponents()
            ->registerBladeComponents();
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

        CapellCore::registerModelInterceptor($layoutModel, LayoutEnum::Default, DefaultLayoutInterceptor::class);
        CapellCore::registerModelInterceptor($layoutModel, LayoutEnum::Home, HomeLayoutInterceptor::class);
        CapellCore::registerModelInterceptor($layoutModel, LayoutEnum::Results, ResultsLayoutInterceptor::class);

        CapellCore::registerModelInterceptor(
            CapellCore::getModel(\Capell\Core\Enums\ModelEnum::Theme),
            key: 'default',
            interceptorClass: DefaultThemeInterceptor::class,
        );

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
            demoCommand: 'capell:layout-demo',
            demoParams: ['user', 'sites'],
            requirements: [
                AdminServiceProvider::$packageName,
                FrontendServiceProvider::$packageName,
            ],
            version: $this->getVersion(),
            url: 'https://capell.app',
            tailwindSources: [
                'resources/views/**/*.blade.php',
                '../../../laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
            ],
            tailwindImports: [
                'tippy.js/dist/tippy.css',
                'resources/css/capell-layout.css',
            ],
            tailwindPlugins: ['@tailwindcss/typography'],
            npmDependencies: [
                'tippy.js' => '^6.3.7',
            ],
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
        if ($this->isLivewireV3()) {
            $registry = resolve(BlockRegistry::class);
            foreach ($registry->allLivewire() as $name => $component) {
                Livewire::component($name, $component);
            }
        } else {
            Livewire::addNamespace(
                namespace: 'capell-layout',
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
        Blade::componentNamespace('Capell\\Layout\\View\\Components', 'capell-layout');
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
        $this->publishes([
            $this->package->basePath('/../publishes/build') => public_path('vendor/capell-layout'),
        ], 'capell-layout-assets');

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
