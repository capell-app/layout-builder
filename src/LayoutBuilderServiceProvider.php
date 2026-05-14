<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder;

use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\LayoutBuilder\Console\Commands\InstallCommand;
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
            ->hasTranslations()
            ->hasViews(self::$name);
    }

    public function packageRegistered(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
