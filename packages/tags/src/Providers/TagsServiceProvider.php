<?php

declare(strict_types=1);

namespace Capell\Tags\Providers;

use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Tags\Models\Tag;
use Capell\Tags\Models\Taggable;
use Capell\Tags\Support\TagModelRegistrar;
use Capell\Workspaces\WorkspaceRegistry;
use Composer\InstalledVersions;
use Spatie\LaravelPackageTools\Package;

class TagsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-tags';

    public static string $packageName = 'capell-app/tags';

    public static PackageTypeEnum $type = PackageTypeEnum::Plugin;

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasMigrations(['alter_tags_table'])
            ->hasTranslations();
    }

    public function registeringPackage(): void
    {
        TagModelRegistrar::register();
        $this->registerWorkspaces();
        $this->registerPackageMetadata();
    }

    private function registerWorkspaces(): void
    {
        if (class_exists(WorkspaceRegistry::class)) {
            WorkspaceRegistry::register(Tag::class);
            WorkspaceRegistry::register(Taggable::class);
        }
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
