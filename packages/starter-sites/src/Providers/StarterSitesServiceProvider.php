<?php

declare(strict_types=1);

namespace Capell\StarterSites\Providers;

use Capell\Admin\Support\Extensions\ExtensionsPageActionRegistry;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\StarterSites\Console\Commands\AdminDemoCommand;
use Capell\StarterSites\Console\Commands\DemoCommand;
use Capell\StarterSites\Console\Commands\FullDemoCommand;
use Capell\StarterSites\Support\Extensions\StarterSitesActionSchemaRegistry;
use Capell\StarterSites\Support\Extensions\StarterSitesDemoActionSchema;
use Capell\StarterSites\Support\Extensions\StarterSitesExtensionsPageActions;
use Composer\InstalledVersions;
use Spatie\LaravelPackageTools\Package;

final class StarterSitesServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-starter-sites';

    public static string $packageName = 'capell-app/starter-sites';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasConfigFile('capell-starter-sites')
            ->hasTranslations()
            ->hasCommands([
                DemoCommand::class,
                AdminDemoCommand::class,
                FullDemoCommand::class,
            ]);
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(StarterSitesActionSchemaRegistry::class);

        if (class_exists(ExtensionsPageActionRegistry::class)) {
            resolve(StarterSitesExtensionsPageActions::class)->register(resolve(ExtensionsPageActionRegistry::class));
        }

        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            description: fn (): string => 'Example site content and assets for Capell installs.',
            setupCommand: 'capell:starter-sites-full-demo',
        );

        $package = CapellCore::getPackage(self::$packageName);
        $package->setupParams = ['url', 'user', 'languages', 'sites', 'force'];
        $package->demoCommand = 'capell:starter-sites-full-demo';
        $package->demoParams = ['url', 'user', 'languages', 'sites', 'force'];

        resolve(StarterSitesActionSchemaRegistry::class)
            ->register('demo', fn (): array => resolve(StarterSitesDemoActionSchema::class)->schema());
    }

    private function getVersion(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return 'dev';
        }

        if (! InstalledVersions::isInstalled(self::$packageName)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion(self::$packageName) ?? 'dev';
    }
}
