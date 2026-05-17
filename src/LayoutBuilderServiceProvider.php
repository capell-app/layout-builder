<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Frontend\Contracts\FrontendRuntimeManifestContributor;
use Capell\Frontend\Contracts\PublicLayoutGraphBuilder;
use Capell\LayoutBuilder\Console\Commands\BlockVisualRegressionCommand;
use Capell\LayoutBuilder\Console\Commands\InstallCommand;
use Capell\LayoutBuilder\Contracts\LayoutContentGroupContributor;
use Capell\LayoutBuilder\Contracts\LayoutSidebarElementContributor;
use Capell\LayoutBuilder\Contracts\PublicElementPayloadContributor;
use Capell\LayoutBuilder\Contracts\PublicElementPayloadResolver;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Policies\LayoutPresetPolicy;
use Capell\LayoutBuilder\Support\BlockPresentationPublicElementPayloadContributor;
use Capell\LayoutBuilder\Support\CapellLayoutBuilderManager;
use Capell\LayoutBuilder\Support\DefaultPublicElementPayloadResolver;
use Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar;
use Capell\LayoutBuilder\Support\LayoutBuilderCoreRegistrar;
use Capell\LayoutBuilder\Support\LayoutBuilderPublicLayoutGraphBuilder;
use Capell\LayoutBuilder\Support\LayoutBuilderRuntimeManifestContributor;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Illuminate\Support\Facades\Gate;
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
        $this->app->tag([], LayoutContentGroupContributor::TAG);
        $this->app->tag([], LayoutSidebarElementContributor::TAG);
        $this->app->scoped(LayoutLoader::class);
        $this->app->scoped(PublicElementPayloadResolver::class, DefaultPublicElementPayloadResolver::class);
        $this->app->scoped(PublicLayoutGraphBuilder::class, LayoutBuilderPublicLayoutGraphBuilder::class);
        $this->app->tag([BlockPresentationPublicElementPayloadContributor::class], PublicElementPayloadContributor::TAG);
        $this->app->tag([LayoutBuilderRuntimeManifestContributor::class], FrontendRuntimeManifestContributor::TAG);

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

        $this->app->make(LayoutBuilderCoreRegistrar::class)->register();

        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->app->make(LayoutBuilderAdminRegistrar::class)->register();
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(static::$packageName);
    }
}
