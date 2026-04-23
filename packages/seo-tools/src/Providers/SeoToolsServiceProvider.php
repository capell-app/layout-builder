<?php

declare(strict_types=1);

namespace Capell\SeoTools\Providers;

use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\SeoTools\Contracts\Schemas\SearchMetaDataSectionExtenderResolverInterface;
use Capell\SeoTools\Support\Schemas\SearchMetaDataSectionExtenderResolver;
use Composer\InstalledVersions;
use Spatie\LaravelPackageTools\Package;

class SeoToolsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-seo-tools';

    public static string $packageName = 'capell-app/seo-tools';

    public static PackageTypeEnum $type = PackageTypeEnum::Plugin;

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasTranslations()
            ->hasViews();
    }

    public function registeringPackage(): void
    {
        $this->registerPackageMetadata();
        $this->registerExtenderResolvers();
    }

    private function registerExtenderResolvers(): void
    {
        $this->app->singleton(
            SearchMetaDataSectionExtenderResolverInterface::class,
            fn (): SearchMetaDataSectionExtenderResolver => new SearchMetaDataSectionExtenderResolver,
        );
    }

    /**
     * Discover migrations in database/migrations as filenames (no extension).
     *
     * Kept for future use when core SEO primitives with migrations land here.
     *
     * @return array<int, string>
     */
    protected function discoveredMigrations(): array
    {
        return $this->discoverMigrations();
    }

    /**
     * @return array<int, string>
     */
    private function discoverMigrations(): array
    {
        $directory = realpath(__DIR__ . '/../../database/migrations');

        if ($directory === false) {
            return [];
        }

        $files = glob($directory . '/*.php') ?: [];

        return array_map(
            static fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
            $files,
        );
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
