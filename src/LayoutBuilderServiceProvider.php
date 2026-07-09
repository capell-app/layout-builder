<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder;

use Capell\Admin\Contracts\Widgets\ContentWidgetStateProcessor;
use Capell\Core\Data\PageTypeData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\ContentGraph\ContentGraphRegistry;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Frontend\Contracts\FrontendAssetContributor;
use Capell\Frontend\Contracts\FrontendRuntimeManifestContributor;
use Capell\Frontend\Contracts\PublicLayoutGraphBuilder;
use Capell\Frontend\Support\Routing\ReservedFrontendPathRegistry;
use Capell\FrontendAuthoring\Contracts\EditableRegionEditorSurface;
use Capell\FrontendAuthoring\Support\EditorSurfaceRegistry;
use Capell\LayoutBuilder\Actions\RepointWidgetAssetReferencesAction;
use Capell\LayoutBuilder\Console\Commands\InstallCommand;
use Capell\LayoutBuilder\Console\Commands\LayoutBulkChangeCommand;
use Capell\LayoutBuilder\Console\Commands\WidgetVisualRegressionCommand;
use Capell\LayoutBuilder\Contracts\Assets\LayoutWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Contracts\Assets\PublicLayoutWidgetAssetsRenderer;
use Capell\LayoutBuilder\Contracts\LayoutContentGroupContributor;
use Capell\LayoutBuilder\Contracts\LayoutSidebarWidgetContributor;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetPayloadContributor;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetPayloadResolver;
use Capell\LayoutBuilder\Contracts\WidgetAssetReferenceRepointer;
use Capell\LayoutBuilder\Data\LayoutWidgets\LayoutWidgetDefinitionData;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Enums\LayoutWidgetTarget;
use Capell\LayoutBuilder\Http\Controllers\LazyLayoutWidgetController;
use Capell\LayoutBuilder\Http\Controllers\PublicFragmentController;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Policies\LayoutPresetPolicy;
use Capell\LayoutBuilder\Support\Assets\LayoutWidgetResourceAssetContributor;
use Capell\LayoutBuilder\Support\Assets\PageContentLayoutWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Support\CapellLayoutBuilderManager;
use Capell\LayoutBuilder\Support\ContentGraph\Extractors\LayoutWidgetContentGraphExtractor;
use Capell\LayoutBuilder\Support\ContentGraph\Extractors\WidgetAssetContentGraphExtractor;
use Capell\LayoutBuilder\Support\ContentGraph\Extractors\WidgetContentGraphExtractor;
use Capell\LayoutBuilder\Support\DefaultPublicLayoutWidgetPayloadResolver;
use Capell\LayoutBuilder\Support\FrontendAuthoring\LayoutBuilderEditableRegionContributor;
use Capell\LayoutBuilder\Support\FrontendAuthoring\LayoutBuilderEditorSurface;
use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
use Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar;
use Capell\LayoutBuilder\Support\LayoutBuilderCoreRegistrar;
use Capell\LayoutBuilder\Support\LayoutBuilderLayoutWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Support\LayoutBuilderPublicLayoutGraphBuilder;
use Capell\LayoutBuilder\Support\LayoutBuilderPublicWidgetAssetsRenderer;
use Capell\LayoutBuilder\Support\LayoutBuilderRuntimeManifestContributor;
use Capell\LayoutBuilder\Support\LayoutModelRegistrar;
use Capell\LayoutBuilder\Support\LayoutWidgets\LayoutWidgetRegistry;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionContentStateProcessor;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionDefinitionAdapter;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistrar;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionViewResolver;
use Capell\LayoutBuilder\Support\WidgetPresentationPublicLayoutWidgetPayloadContributor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Override;
use Spatie\LaravelPackageTools\Package;

final class LayoutBuilderServiceProvider extends AbstractPackageServiceProvider
{
    private const string EDITABLE_REGION_EDITOR_SURFACE = EditableRegionEditorSurface::class;

    private const string EDITOR_SURFACE_REGISTRY = EditorSurfaceRegistry::class;

    public static string $name = 'capell-layout-builder';

    public static string $packageName = 'capell-app/layout-builder';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-layout-builder')
            ->hasMigrations(CapellLayoutBuilderManager::getMigrations())
            ->hasTranslations()
            ->hasViews(self::$name);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(LayoutAreaRegistry::class, fn (): LayoutAreaRegistry => new LayoutAreaRegistry);
        $this->app->singleton(LayoutWidgetRegistry::class, fn (): LayoutWidgetRegistry => new LayoutWidgetRegistry);
        $this->app->singleton(WidgetExtensionDefinitionAdapter::class);
        $this->app->singleton(WidgetExtensionRegistrar::class);
        $this->app->singleton(WidgetExtensionViewResolver::class);
        $this->app->singleton(WidgetExtensionContentStateProcessor::class);
        $this->app->singleton(
            WidgetExtensionRegistry::class,
            fn (): WidgetExtensionRegistry => new WidgetExtensionRegistry(
                $this->app->make(WidgetExtensionDefinitionAdapter::class),
            ),
        );
        $this->registerDefaultLayoutWidgets();
        $this->app->tag([], LayoutContentGroupContributor::TAG);
        $this->app->tag([WidgetExtensionContentStateProcessor::class], ContentWidgetStateProcessor::TAG);
        $this->app->tag([], LayoutSidebarWidgetContributor::TAG);
        $this->app->scoped(LayoutLoader::class);
        $this->app->scoped(LayoutWidgetResourceAssetContributor::class);
        $this->app->scoped(PageContentLayoutWidgetResourceUsageContributor::class);
        $this->app->scoped(PublicLayoutWidgetPayloadResolver::class, DefaultPublicLayoutWidgetPayloadResolver::class);
        $this->app->scoped(PublicLayoutWidgetAssetsRenderer::class, LayoutBuilderPublicWidgetAssetsRenderer::class);
        $this->app->scoped(WidgetAssetReferenceRepointer::class, RepointWidgetAssetReferencesAction::class);
        $this->app->scoped(PublicLayoutGraphBuilder::class, LayoutBuilderPublicLayoutGraphBuilder::class);
        $this->app->tag([WidgetPresentationPublicLayoutWidgetPayloadContributor::class], PublicLayoutWidgetPayloadContributor::TAG);
        $this->app->tag([LayoutBuilderRuntimeManifestContributor::class], FrontendRuntimeManifestContributor::TAG);
        $this->app->tag([LayoutWidgetResourceAssetContributor::class], FrontendAssetContributor::TAG);
        $this->app->tag([LayoutBuilderLayoutWidgetResourceUsageContributor::class, PageContentLayoutWidgetResourceUsageContributor::class], LayoutWidgetResourceUsageContributor::TAG);
        $this->registerFrontendAuthoringIntegration();
        $this->app->tag([
            WidgetAssetContentGraphExtractor::class,
            WidgetContentGraphExtractor::class,
            LayoutWidgetContentGraphExtractor::class,
        ], ContentGraphRegistry::TAG);
        LayoutModelRegistrar::register();
        $this->registerPageTypes();

        $this->app->booting(function (): void {
            // Resolve after every provider has registered so definitions declared
            // before or after Layout Builder are adapted into WidgetDiscovery.
            $this->app->make(WidgetExtensionRegistry::class);

            if (! $this->isPackageInstalled()) {
                return;
            }

            $this->app->make(LayoutBuilderAdminRegistrar::class)->registerResources();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                WidgetVisualRegressionCommand::class,
                InstallCommand::class,
                LayoutBulkChangeCommand::class,
            ]);
        }
    }

    public function packageBooted(): void
    {
        Gate::policy(LayoutPreset::class, LayoutPresetPolicy::class);

        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->app->make(LayoutBuilderCoreRegistrar::class)->register();
        $this->app->make(LayoutBuilderAdminRegistrar::class)->register();
        $this->registerPublicFragmentRoute();
        $this->registerLazyLayoutWidgetRoute();
        $this->reservePublicFragmentPath();
        $this->reserveLazyLayoutWidgetPath();
    }

    #[Override]
    protected function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(self::$packageName);
    }

    private function registerDefaultLayoutWidgets(): void
    {
        /** @var LayoutWidgetRegistry $registry */
        $registry = $this->app->make(LayoutWidgetRegistry::class);

        $registry->register('content', LayoutWidgetTarget::FrontendBlade, 'capell-layout-builder::layout-widgets.content');
        $registry->register('image', LayoutWidgetTarget::FrontendBlade, 'capell-layout-builder::layout-widgets.image');
        $registry->register('title', LayoutWidgetTarget::FrontendBlade, 'capell-layout-builder::layout-widgets.title');
        $registry->registerDefinition(LayoutWidgetDefinitionData::frontendInertia('content', 'Capell/Widgets/Content'));
        $registry->registerDefinition(LayoutWidgetDefinitionData::frontendInertia('image', 'Capell/Widgets/Image'));
        $registry->registerDefinition(LayoutWidgetDefinitionData::frontendInertia('title', 'Capell/Widgets/Title'));
    }

    private function registerPublicFragmentRoute(): void
    {
        Route::middleware('web')
            ->name('capell-layout-builder.fragments.')
            ->prefix('_fragments')
            ->group(function (): void {
                Route::get('{reference}', PublicFragmentController::class)
                    ->where('reference', '.*')
                    ->name('show');
            });
    }

    private function reservePublicFragmentPath(): void
    {
        if (! class_exists(ReservedFrontendPathRegistry::class) || ! $this->app->bound(ReservedFrontendPathRegistry::class)) {
            return;
        }

        $this->app->make(ReservedFrontendPathRegistry::class)->reservePrefix('_fragments');
    }

    private function registerLazyLayoutWidgetRoute(): void
    {
        Route::middleware('web')
            ->name('capell-layout-builder.layout-widgets.')
            ->group(function (): void {
                Route::get('/_capell/layout-widgets/{reference}', LazyLayoutWidgetController::class)
                    ->where('reference', '.*')
                    ->name('show');
            });
    }

    private function reserveLazyLayoutWidgetPath(): void
    {
        if (! class_exists(ReservedFrontendPathRegistry::class) || ! $this->app->bound(ReservedFrontendPathRegistry::class)) {
            return;
        }

        $this->app->make(ReservedFrontendPathRegistry::class)->reservePrefix('_capell/layout-widgets');
    }

    private function registerFrontendAuthoringIntegration(): void
    {
        if (! interface_exists(self::EDITABLE_REGION_EDITOR_SURFACE)) {
            return;
        }

        $this->app->tag([LayoutBuilderEditableRegionContributor::class], 'capell-frontend-authoring:editable-regions');

        $registryClass = self::EDITOR_SURFACE_REGISTRY;

        if (! class_exists($registryClass)) {
            return;
        }

        $registerSurface = function (object $registry): void {
            if (method_exists($registry, 'register')) {
                $registry->register($this->app->make(LayoutBuilderEditorSurface::class));
            }
        };

        $this->app->afterResolving($registryClass, $registerSurface);

        if ($this->app->resolved($registryClass)) {
            $registerSurface($this->app->make($registryClass));
        }
    }

    private function registerPageTypes(): void
    {
        foreach (LayoutTypeEnum::cases() as $type) {
            CapellCore::registerPageType(
                new PageTypeData(
                    name: $type->value,
                    model: $type->getModel(),
                    label: $type->getLabel(),
                ),
            );
        }
    }
}
