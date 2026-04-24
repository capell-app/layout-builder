<?php

declare(strict_types=1);

namespace Capell\MediaCurator;

use Capell\Core\Contracts\Media\MediaFieldFactory;
use Capell\MediaCurator\Filament\Components\CuratorMediaFieldFactory;
use Capell\MediaCurator\Models\CuratorMedia;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the Curator backend for Capell media:
 *   - capell.media.model  → CuratorMedia
 *   - capell.media.backend → 'curator'
 *   - MediaFieldFactory contract → CuratorMediaFieldFactory
 *
 * The data-migration command (MigrateSpatieToCuratorCommand) ships in
 * Phase 4 of the media-decoupling plan; its registration is guarded so
 * this provider boots cleanly while the class does not yet exist.
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
        if (! $this->app->runningInConsole()) {
            return;
        }

        $commandClass = 'Capell\\MediaCurator\\Console\\MigrateSpatieToCuratorCommand';

        if (class_exists($commandClass)) {
            $this->commands([$commandClass]);
        }
    }
}
