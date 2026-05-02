<?php

declare(strict_types=1);

namespace Capell\MediaCurator;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Contracts\Media\MediaFieldFactory;
use Capell\Core\Facades\CapellCore;
use Capell\MediaCurator\Console\MigrateSpatieToCuratorCommand;
use Capell\MediaCurator\Filament\Components\CuratorMediaFieldFactory;
use Capell\MediaCurator\Filament\Pages\MediaHealthPage;
use Capell\MediaCurator\Models\CuratorMedia;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the Curator backend for Capell media:
 *   - capell.media.model  → CuratorMedia
 *   - capell.media.backend → 'curator'
 *   - MediaFieldFactory contract → CuratorMediaFieldFactory
 *   - MigrateSpatieToCuratorCommand (console only)
 */
final class CapellMediaCuratorServiceProvider extends ServiceProvider
{
    public static string $packageName = 'capell-app/media-curator';

    public function register(): void
    {
        config()->set('capell.media.backend', 'curator');
        config()->set('capell.media.model', CuratorMedia::class);

        CapellCore::registerPackage(
            self::$packageName,
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            description: fn (): string => __('capell-media-curator::package.description'),
        );

        CapellCore::registerModels([CuratorMedia::class]);

        $this->app->bind(MediaFieldFactory::class, CuratorMediaFieldFactory::class);

        if (class_exists(CapellAdmin::class)) {
            CapellAdmin::registerPage(MediaHealthPage::class);
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([MigrateSpatieToCuratorCommand::class]);
        }
    }
}
