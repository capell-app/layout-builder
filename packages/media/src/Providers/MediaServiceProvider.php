<?php

declare(strict_types=1);

namespace Capell\Media\Providers;

use Capell\Core\Contracts\ModelMediaExtender as ModelMediaExtenderContract;
use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Media\Extenders\ModelMediaExtender;
use Composer\InstalledVersions;
use Spatie\LaravelPackageTools\Package;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

class MediaServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-media';

    public static string $packageName = 'capell-app/media';

    public static PackageTypeEnum $type = PackageTypeEnum::Plugin;

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasTranslations();
    }

    public function registeringPackage(): void
    {
        $this->app->register(MediaLibraryServiceProvider::class);
        $this->registerPackageMetadata();
    }

    public function bootingPackage(): void
    {
        $this->app->tag([ModelMediaExtender::class], ModelMediaExtenderContract::TAG);
    }

    private function registerPackageMetadata(): void
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            permissions: [],
        );
    }

    private function getVersion(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return 'dev';
        }

        if (! InstalledVersions::isInstalled(static::$packageName)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion(static::$packageName) ?? 'dev';
    }
}
