<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

final class DeveloperToolsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-developer-tools';

    public static string $packageName = 'capell-app/developer-tools';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasTranslations()
            ->hasViews(self::$name);
    }

    public function registeringPackage(): void
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            description: fn (): string => __('capell-developer-tools::package.description'),
        );
    }
}
