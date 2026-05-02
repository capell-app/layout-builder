<?php

declare(strict_types=1);

namespace Capell\Assistant\Providers;

use Capell\Assistant\Support\AssistantModuleRegistry;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Composer\InstalledVersions;
use Spatie\LaravelPackageTools\Package;

class AssistantServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-assistant';

    public static string $packageName = 'capell-app/assistant';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name);
    }

    public function registeringPackage(): void
    {
        $this
            ->registerPackageMetadata();

        $this->app->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this->registerServices();
        });
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(static::$packageName);
    }

    private function registerServices(): self
    {
        $this->app->singleton(AssistantModuleRegistry::class);

        return $this;
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            description: fn (): string => 'Capell Assistant orchestration package.',
        );

        return $this;
    }

    private function getVersion(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return '0.0.0';
        }

        return CapellCore::getInstalledPrettyVersion(static::$packageName) ?? '0.0.0';
    }
}
