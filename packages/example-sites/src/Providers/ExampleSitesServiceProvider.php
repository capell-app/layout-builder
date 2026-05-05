<?php

declare(strict_types=1);

namespace Capell\ExampleSites\Providers;

use Capell\Admin\Support\Extensions\ExtensionsPageActionRegistry;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\ExampleSites\Console\Commands\AdminDemoCommand;
use Capell\ExampleSites\Console\Commands\DemoCommand;
use Capell\ExampleSites\Support\Extensions\ExampleSitesExtensionsPageActions;
use Composer\InstalledVersions;
use Spatie\LaravelPackageTools\Package;

final class ExampleSitesServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-example-sites';

    public static string $packageName = 'capell-app/example-sites';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasConfigFile('capell-example-sites')
            ->hasCommands([
                DemoCommand::class,
                AdminDemoCommand::class,
            ]);
    }

    public function registeringPackage(): void
    {
        if (class_exists(ExtensionsPageActionRegistry::class)) {
            resolve(ExampleSitesExtensionsPageActions::class)->register(resolve(ExtensionsPageActionRegistry::class));
        }

        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            description: fn (): string => 'Example site content and assets for Capell installs.',
            setupCommand: 'capell:demo',
        );

        $package = CapellCore::getPackage(self::$packageName);
        $package->setupParams = ['url', 'user', 'languages', 'sites', 'force'];
        $package->demoCommand = 'capell:demo';
        $package->demoParams = ['url', 'user', 'languages', 'sites', 'force'];
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
