<?php

declare(strict_types=1);

namespace Capell\MediaLibrary;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Contracts\Media\MediaFieldFactory;
use Capell\Core\Facades\CapellCore;
use Capell\MediaLibrary\Console\MigrateSpatieToCuratorCommand;
use Capell\MediaLibrary\Filament\Components\CuratorMediaFieldFactory;
use Capell\MediaLibrary\Filament\Pages\MediaHealthPage;
use Capell\MediaLibrary\Models\CuratorMedia;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the Curator backend for Capell media:
 *   - capell.media.model  → CuratorMedia
 *   - capell.media.backend → 'curator'
 *   - MediaFieldFactory contract → CuratorMediaFieldFactory
 *   - MigrateSpatieToCuratorCommand (console only)
 */
final class MediaLibraryServiceProvider extends ServiceProvider
{
    public static string $packageName = 'capell-app/media-library';

    public function register(): void
    {
        CapellCore::registerPackage(
            self::$packageName,
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            description: fn (): string => __('capell-media-library::package.description'),
        );

        $this->app->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this->registerInstalledPackage();
        });
    }

    public function boot(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        if ($this->app->runningInConsole()) {
            $this->commands([MigrateSpatieToCuratorCommand::class]);
        }
    }

    private function registerInstalledPackage(): void
    {
        config()->set('capell.media.backend', 'curator');
        config()->set('capell.media.model', CuratorMedia::class);
        CapellCore::registerModels([CuratorMedia::class]);

        $this->app->bind(MediaFieldFactory::class, CuratorMediaFieldFactory::class);

        if (class_exists(CapellAdmin::class)) {
            CapellAdmin::registerExtensionPage(self::$packageName, MediaHealthPage::class);
        }
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(self::$packageName);
    }
}
