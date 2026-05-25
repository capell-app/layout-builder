<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\ContentGraph\ContentGraphRegistry;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Frontend\Contracts\FrontendRuntimeManifestContributor;
use Capell\Frontend\Contracts\PublicLayoutGraphBuilder;
use Capell\Frontend\Contracts\WidgetResourceUsageContributor;
use Capell\Frontend\Support\Routing\ReservedFrontendPathRegistry;
use Capell\LayoutBuilder\Console\Commands\BlockVisualRegressionCommand;
use Capell\LayoutBuilder\Console\Commands\InstallCommand;
use Capell\LayoutBuilder\Contracts\LayoutContentGroupContributor;
use Capell\LayoutBuilder\Contracts\LayoutSidebarBlockContributor;
use Capell\LayoutBuilder\Contracts\PublicBlockPayloadContributor;
use Capell\LayoutBuilder\Contracts\PublicBlockPayloadResolver;
use Capell\LayoutBuilder\Http\Controllers\PublicFragmentController;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Policies\LayoutPresetPolicy;
use Capell\LayoutBuilder\Support\BlockPresentationPublicBlockPayloadContributor;
use Capell\LayoutBuilder\Support\CapellLayoutBuilderManager;
use Capell\LayoutBuilder\Support\ContentGraph\Extractors\BlockAssetContentGraphExtractor;
use Capell\LayoutBuilder\Support\ContentGraph\Extractors\BlockContentGraphExtractor;
use Capell\LayoutBuilder\Support\ContentGraph\Extractors\LayoutBlockContentGraphExtractor;
use Capell\LayoutBuilder\Support\DefaultPublicBlockPayloadResolver;
use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
use Capell\LayoutBuilder\Support\LayoutBlockWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar;
use Capell\LayoutBuilder\Support\LayoutBuilderCoreRegistrar;
use Capell\LayoutBuilder\Support\LayoutBuilderPublicLayoutGraphBuilder;
use Capell\LayoutBuilder\Support\LayoutBuilderRuntimeManifestContributor;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Override;
use Spatie\LaravelPackageTools\Package;

class LayoutBuilderServiceProvider extends AbstractPackageServiceProvider
{
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
        $this->app->tag([], LayoutSidebarBlockContributor::TAG);
        $this->app->scoped(LayoutLoader::class);
        $this->app->scoped(PublicBlockPayloadResolver::class, DefaultPublicBlockPayloadResolver::class);
        $this->app->scoped(PublicLayoutGraphBuilder::class, LayoutBuilderPublicLayoutGraphBuilder::class);
        $this->app->tag([BlockPresentationPublicBlockPayloadContributor::class], PublicBlockPayloadContributor::TAG);
        $this->app->tag([LayoutBuilderRuntimeManifestContributor::class], FrontendRuntimeManifestContributor::TAG);
        $this->app->tag([LayoutBlockWidgetResourceUsageContributor::class], WidgetResourceUsageContributor::TAG);
        $this->app->tag([
            BlockAssetContentGraphExtractor::class,
            BlockContentGraphExtractor::class,
            LayoutBlockContentGraphExtractor::class,
        ], ContentGraphRegistry::TAG);

        if ($this->app->runningInConsole()) {
            $this->commands([
                BlockVisualRegressionCommand::class,
                InstallCommand::class,
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
            ->prefix('_capell/fragments')
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

        $this->app->make(ReservedFrontendPathRegistry::class)->reservePrefix('_capell/fragments');
    }
}
