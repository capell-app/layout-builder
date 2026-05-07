<?php

declare(strict_types=1);

namespace Capell\DemoKit\Providers;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\DemoKit\Console\Commands\AdminDemoCommand;
use Capell\DemoKit\Console\Commands\DemoCommand;
use Capell\DemoKit\Console\Commands\FullDemoCommand;
use Capell\DemoKit\Filament\Pages\DemoKitPage;
use Composer\InstalledVersions;
use Spatie\LaravelPackageTools\Package;

final class DemoKitServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-demo-kit';

    public static string $packageName = 'capell-app/demo-kit';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasConfigFile('capell-demo-kit')
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasCommands([
                DemoCommand::class,
                AdminDemoCommand::class,
                FullDemoCommand::class,
            ]);
    }

    public function registeringPackage(): void
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            description: fn (): string => 'Demo content and media kit for Capell',
            setupCommand: 'capell:demo-kit-full-demo',
        );

        $package = CapellCore::getPackage(self::$packageName);
        $package->setupParams = ['url', 'user', 'languages', 'sites', 'force'];
        $package->demoCommand = 'capell:demo-kit-full-demo';
        $package->demoParams = ['url', 'user', 'languages', 'sites', 'force'];

        $this->registerAdminPanelExtensions();
    }

    private function registerAdminPanelExtensions(): void
    {
        if (! class_exists(CapellAdmin::class)) {
            return;
        }

        CapellAdmin::registerExtensionPage(self::$packageName, DemoKitPage::class);
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
