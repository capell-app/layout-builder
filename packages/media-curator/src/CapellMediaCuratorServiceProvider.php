<?php

declare(strict_types=1);

namespace Capell\MediaCurator;

use Capell\Core\Contracts\Media\MediaFieldFactory;
use Capell\MediaCurator\Console\MigrateSpatieToCuratorCommand;
use Capell\MediaCurator\Filament\Components\CuratorMediaFieldFactory;
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
    public function register(): void
    {
        config()->set('capell.media.backend', 'curator');
        config()->set('capell.media.model', CuratorMedia::class);

        $this->app->bind(MediaFieldFactory::class, CuratorMediaFieldFactory::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([MigrateSpatieToCuratorCommand::class]);
        }
    }
}
