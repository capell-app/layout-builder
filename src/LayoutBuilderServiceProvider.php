<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder;

use Capell\Core\Data\PageTypeData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\ContentGraph\ContentGraphRegistry;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Frontend\Contracts\FrontendRuntimeManifestContributor;
use Capell\Frontend\Contracts\PublicLayoutGraphBuilder;
use Capell\Frontend\Contracts\PublicWidgetAssetsRenderer;
use Capell\Frontend\Contracts\WidgetResourceUsageContributor;
use Capell\Frontend\Support\Routing\ReservedFrontendPathRegistry;
use Capell\FrontendAuthoring\Contracts\EditableRegionEditorSurface;
use Capell\FrontendAuthoring\Support\EditorSurfaceRegistry;
use Capell\LayoutBuilder\Actions\RepointWidgetAssetReferencesAction;
use Capell\LayoutBuilder\Console\Commands\InstallCommand;
use Capell\LayoutBuilder\Console\Commands\LayoutBulkChangeCommand;
use Capell\LayoutBuilder\Console\Commands\WidgetVisualRegressionCommand;
use Capell\LayoutBuilder\Contracts\LayoutContentGroupContributor;
use Capell\LayoutBuilder\Contracts\LayoutSidebarWidgetContributor;
use Capell\LayoutBuilder\Contracts\PublicWidgetPayloadContributor;
use Capell\LayoutBuilder\Contracts\PublicWidgetPayloadResolver;
use Capell\LayoutBuilder\Contracts\WidgetAssetReferenceRepointer;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Http\Controllers\PublicFragmentController;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Policies\LayoutPresetPolicy;
use Capell\LayoutBuilder\Support\CapellLayoutBuilderManager;
use Capell\LayoutBuilder\Support\ContentGraph\Extractors\LayoutWidgetContentGraphExtractor;
use Capell\LayoutBuilder\Support\ContentGraph\Extractors\WidgetAssetContentGraphExtractor;
use Capell\LayoutBuilder\Support\ContentGraph\Extractors\WidgetContentGraphExtractor;
use Capell\LayoutBuilder\Support\DefaultPublicWidgetPayloadResolver;
use Capell\LayoutBuilder\Support\FrontendAuthoring\LayoutBuilderEditableRegionContributor;
use Capell\LayoutBuilder\Support\FrontendAuthoring\LayoutBuilderEditorSurface;
use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
use Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar;
use Capell\LayoutBuilder\Support\LayoutBuilderCoreRegistrar;
use Capell\LayoutBuilder\Support\LayoutBuilderPublicLayoutGraphBuilder;
use Capell\LayoutBuilder\Support\LayoutBuilderPublicWidgetAssetsRenderer;
use Capell\LayoutBuilder\Support\LayoutBuilderRuntimeManifestContributor;
use Capell\LayoutBuilder\Support\LayoutModelRegistrar;
use Capell\LayoutBuilder\Support\LayoutWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Capell\LayoutBuilder\Support\WidgetPresentationPublicWidgetPayloadContributor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Override;
use Spatie\LaravelPackageTools\Package;

class LayoutBuilderServiceProvider extends AbstractPackageServiceProvider
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
        $this->app->tag([], LayoutContentGroupContributor::TAG);
        $this->app->tag([], LayoutSidebarWidgetContributor::TAG);
        $this->app->scoped(LayoutLoader::class);
        $this->app->scoped(PublicWidgetPayloadResolver::class, DefaultPublicWidgetPayloadResolver::class);
        $this->app->scoped(PublicWidgetAssetsRenderer::class, LayoutBuilderPublicWidgetAssetsRenderer::class);
        $this->app->scoped(WidgetAssetReferenceRepointer::class, RepointWidgetAssetReferencesAction::class);
        $this->app->scoped(PublicLayoutGraphBuilder::class, LayoutBuilderPublicLayoutGraphBuilder::class);
        $this->app->tag([WidgetPresentationPublicWidgetPayloadContributor::class], PublicWidgetPayloadContributor::TAG);
        $this->app->tag([LayoutBuilderRuntimeManifestContributor::class], FrontendRuntimeManifestContributor::TAG);
        $this->app->tag([LayoutWidgetResourceUsageContributor::class], WidgetResourceUsageContributor::TAG);
        $this->registerFrontendAuthoringIntegration();
        $this->app->tag([
            WidgetAssetContentGraphExtractor::class,
            WidgetContentGraphExtractor::class,
            LayoutWidgetContentGraphExtractor::class,
        ], ContentGraphRegistry::TAG);
        LayoutModelRegistrar::register();
        $this->registerPageTypes();

        $this->app->booting(function (): void {
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
        $this->reservePublicFragmentPath();
    }

    #[Override]
    protected function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(static::$packageName);
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
