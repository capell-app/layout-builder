<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Providers;

use Capell\Admin\Actions\CreatedModelAction;
use Capell\Admin\Actions\DeletedModelAction;
use Capell\Admin\Data\AdminAssetData;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\BlockLibrary\Actions\RegisterContentBlockDefinitionProviderAction;
use Capell\BlockLibrary\Actions\RegisterDefaultBlockLibraryAction;
use Capell\BlockLibrary\Contracts\ContentBlockDefinitionProvider;
use Capell\BlockLibrary\Enums\AssetEnum;
use Capell\BlockLibrary\Enums\LayoutTypeEnum;
use Capell\BlockLibrary\Enums\ResourceEnum;
use Capell\BlockLibrary\Filament\Resources\BlockLibrary\ContentBlockResource;
use Capell\BlockLibrary\Models\ContentBlock;
use Capell\BlockLibrary\Support\ContentBlockRegistry;
use Capell\BlockLibrary\Support\LayoutBuilder\Livewire\ContentBlockAssets;
use Capell\Core\Data\AssetData;
use Capell\Core\Data\PageTypeData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Frontend\Contracts\AssetsRegistryInterface;
use Capell\Frontend\Data\FrontendAssetData;
use Capell\LayoutBuilder\Data\LayoutAssetBridgeData;
use Capell\LayoutBuilder\Livewire\Assets\Table\AbstractAssets;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\LayoutAssetBridgeRegistry;
use Capell\PublishingStudio\WorkspaceRegistry;
use Composer\InstalledVersions;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;

class BlockLibraryServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-block-library';

    public static string $packageName = 'capell-app/block-library';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasRoute('web')
            ->hasMigrations([
                'create_block_library_table',
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

    public function packageBooted(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        Relation::morphMap([
            'content_block' => ContentBlock::class,
        ], merge: true);
    }

    private function bootInstalledPackage(): self
    {
        return $this
            ->registerModels()
            ->registerBlockRegistry()
            ->registerRelationships()
            ->registerResources()
            ->registerConfigurators()
            ->registerTypes()
            ->registerAssets()
            ->registerEvents()
            ->registerBladeComponents()
            ->registerLivewireComponents()
            ->registerLayoutBuilderBridge()
            ->registerPublishingStudio();
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::getPackage(static::$packageName)->isInstalled();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            description: fn (): string => __('capell-block-library::package.description'),
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

    private function registerModels(): self
    {
        CapellCore::registerModels([
            ContentBlock::class,
        ]);

        return $this;
    }

    private function registerBlockRegistry(): self
    {
        $this->app->singleton(ContentBlockRegistry::class);

        $this->callAfterResolving(ContentBlockRegistry::class, function (ContentBlockRegistry $registry): void {
            RegisterDefaultBlockLibraryAction::run($registry);

            foreach ($this->app->tagged(ContentBlockDefinitionProvider::TAG) as $provider) {
                if (! $provider instanceof ContentBlockDefinitionProvider) {
                    continue;
                }

                RegisterContentBlockDefinitionProviderAction::run($registry, $provider);
            }
        });

        return $this;
    }

    private function registerResources(): self
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: ContentBlockResource::class,
            group: ResourceEnum::ContentBlock->name,
        ));

        return $this;
    }

    private function registerConfigurators(): self
    {
        foreach (resolve(ContentBlockRegistry::class)->all() as $definition) {
            CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::configurator(
                class: $definition->configurator,
                group: $definition->configuratorType->value,
                name: $definition->configurator::getKey(),
            ));
        }

        return $this;
    }

    private function registerTypes(): self
    {
        CapellCore::registerPageType(
            new PageTypeData(
                name: LayoutTypeEnum::ContentBlock->value,
                model: ContentBlock::class,
                label: LayoutTypeEnum::ContentBlock->getLabel(),
            ),
        );

        return $this;
    }

    private function registerAssets(): self
    {
        $contentBlockAsset = AssetEnum::ContentBlock;

        CapellCore::registerAsset(
            new AssetData(
                name: $contentBlockAsset->name,
                model: $contentBlockAsset->getModel(),
                icon: $contentBlockAsset->getIcon(),
                hasTranslations: $contentBlockAsset->hasTranslations(),
            ),
        );

        CapellAdmin::registerAsset(
            $contentBlockAsset,
            new AdminAssetData(
                formClass: $contentBlockAsset->getFormClass(),
                createAction: $contentBlockAsset->getCreateActionClass(),
                defaultDataAction: $contentBlockAsset->getDefaultDataActionClass(),
            ),
        );

        $this->callAfterResolving(AssetsRegistryInterface::class, function (AssetsRegistryInterface $assets) use ($contentBlockAsset): void {
            $assets->registerAsset(
                $contentBlockAsset,
                new FrontendAssetData(
                    component: $contentBlockAsset->getComponent(),
                ),
            );
        });

        return $this;
    }

    private function registerEvents(): self
    {
        ContentBlock::created(function (Model $model): void {
            CreatedModelAction::run($model);
        });

        ContentBlock::deleted(function (Model $model): void {
            DeletedModelAction::run($model);
        });

        return $this;
    }

    private function registerBladeComponents(): self
    {
        Blade::anonymousComponentNamespace('Capell\\BlockLibrary\\View\\Components');

        return $this;
    }

    private function registerLivewireComponents(): self
    {
        if (! class_exists(AbstractAssets::class)) {
            return $this;
        }

        if (! class_exists(ContentBlockAssets::class)) {
            return $this;
        }

        Livewire::component(
            'capell-block-library::assets.table.content-block-assets',
            ContentBlockAssets::class,
        );

        return $this;
    }

    private function registerLayoutBuilderBridge(): self
    {
        $bridgeRegistryClass = LayoutAssetBridgeRegistry::class;
        $bridgeDataClass = LayoutAssetBridgeData::class;

        if (! class_exists($bridgeRegistryClass) || ! class_exists($bridgeDataClass)) {
            return $this;
        }

        $this->callAfterResolving($bridgeRegistryClass, function (object $registry) use ($bridgeDataClass): void {
            $registry->register(new $bridgeDataClass(
                key: AssetEnum::ContentBlock->value,
                name: AssetEnum::ContentBlock->name,
                model: ContentBlock::class,
                icon: Heroicon::OutlinedClipboardDocumentList,
                color: 'info',
                label: __('capell-block-library::generic.content_block'),
                component: AssetEnum::ContentBlock->getComponent(),
                formClass: AssetEnum::ContentBlock->getFormClass(),
                createAction: AssetEnum::ContentBlock->getCreateActionClass(),
                defaultDataAction: AssetEnum::ContentBlock->getDefaultDataActionClass(),
                hasTranslations: true,
                livewireTable: ContentBlockAssets::class,
            ));
        });

        return $this;
    }

    private function registerRelationships(): self
    {
        $widgetAssetClass = WidgetAsset::class;

        if (! class_exists($widgetAssetClass)) {
            return $this
                ->registerSiteRelationships()
                ->registerTypeRelationships();
        }

        Page::resolveRelationUsing(
            'contentBlocks',
            fn (Page $model): HasManyThrough => $model->hasManyThrough(
                ContentBlock::class,
                $widgetAssetClass,
                'pageable_id',
                'id',
                'id',
                'asset_id',
            )
                ->where('widget_assets.pageable_type', $model->getMorphClass())
                ->where('widget_assets.asset_type', (new ContentBlock)->getMorphClass()),
        );

        return $this
            ->registerSiteRelationships()
            ->registerTypeRelationships();
    }

    private function registerSiteRelationships(): self
    {
        Site::resolveRelationUsing(
            'contentBlocks',
            fn (Site $model): HasMany => $model->hasMany(ContentBlock::class, 'site_id'),
        );

        return $this;
    }

    private function registerTypeRelationships(): self
    {
        Type::resolveRelationUsing(
            'contentBlocks',
            fn (Type $model): HasMany => $model->hasMany(ContentBlock::class, 'type_id'),
        );

        return $this;
    }

    private function registerPublishingStudio(): self
    {
        if (! class_exists(WorkspaceRegistry::class)) {
            return $this;
        }

        WorkspaceRegistry::register(ContentBlock::class);

        return $this;
    }
}
