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
        $this->app->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this->registerMediaBackend();
            $this->registerInstalledPackage();
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole() && $this->isPackageInstalled()) {
            $this->commands([MigrateSpatieToCuratorCommand::class]);
        }
    }

    private function registerInstalledPackage(): void
    {
        CapellCore::registerModels([CuratorMedia::class]);

        if (class_exists(CapellAdmin::class)) {
            CapellAdmin::registerExtensionPage(self::$packageName, MediaHealthPage::class);
        }
    }

    private function registerMediaBackend(): void
    {
        config()->set('capell.media.backend', 'curator');
        config()->set('capell.media.model', CuratorMedia::class);

        $this->app->bind(MediaFieldFactory::class, CuratorMediaFieldFactory::class);
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(self::$packageName);
    }
}
