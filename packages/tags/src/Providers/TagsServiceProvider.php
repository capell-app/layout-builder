<?php

declare(strict_types=1);

namespace Capell\Tags\Providers;

use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Tags\Console\Commands\InstallCommand;
use Capell\Tags\Models\Tag;
use Capell\Tags\Support\TagModelRegistrar;
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
            ->hasCommands([
                InstallCommand::class,
            ])
            ->hasTranslations();
    }

    public function registeringPackage(): void
    {
        $this->repairLegacyTagModelConfig();
        TagModelRegistrar::register();
        $this->registerPackageMetadata();

        $this->booted(function (): void {
            $this->registerPublishCommands();
        });
    }

    private function repairLegacyTagModelConfig(): void
    {
        $configuredModel = config('tags.tag_model');

        if ($configuredModel === Tag::class) {
            return;
        }

        if (is_string($configuredModel) && class_exists($configuredModel)) {
            return;
        }

        config(['tags.tag_model' => Tag::class]);
    }

    private function registerPackageMetadata(): void
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            description: fn (): string => __('capell-tags::package.description'),
        );
    }

    private function registerPublishCommands(): self
    {
        $this->publishes([
            $this->package->basePath('/../publishes/config/') => config_path(),
        ], 'capell-tags-config');

        return $this;
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
