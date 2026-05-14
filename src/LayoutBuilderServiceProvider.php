<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\LayoutBuilder\Console\Commands\InstallCommand;
use Capell\LayoutBuilder\Contracts\LayoutContentGroupContributor;
use Capell\LayoutBuilder\Contracts\LayoutSidebarWidgetContributor;
use Capell\LayoutBuilder\Contracts\PublicWidgetPayloadContributor;
use Capell\LayoutBuilder\Contracts\PublicWidgetPayloadResolver;
use Capell\LayoutBuilder\Support\CapellLayoutBuilderManager;
use Capell\LayoutBuilder\Support\DefaultPublicWidgetPayloadResolver;
use Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar;
use Capell\LayoutBuilder\Support\LayoutBuilderCoreRegistrar;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
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
        $this->app->tag([], LayoutSidebarWidgetContributor::TAG);
        $this->app->scoped(LayoutLoader::class);
        $this->app->bind(PublicWidgetPayloadResolver::class, DefaultPublicWidgetPayloadResolver::class);
        $this->app->tag([], PublicWidgetPayloadContributor::TAG);

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }

    public function packageBooted(): void
    {
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
