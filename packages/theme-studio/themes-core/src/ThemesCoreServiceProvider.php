<?php

declare(strict_types=1);

namespace Capell\Themes\Core;

use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Facades\CapellCore;
use Capell\Themes\Core\Console\GeneratePreviewTokenCommand;
use Capell\Themes\Core\Console\GenerateSitemapCommand;
use Illuminate\Console\Application as ArtisanApplication;
use Illuminate\Support\ServiceProvider;

final class ThemesCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        CapellCore::registerPackage(
            name: 'capell-app/themes-core',
            path: realpath(__DIR__ . '/..'),
            version: CapellCore::getInstalledPrettyVersion('capell-app/themes-core'),
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'capell-themes-core');

        RegisterBlazeOptimizedViewsAction::run(__DIR__ . '/../resources/views/components');

        if ($this->app->runningInConsole()) {
            ArtisanApplication::starting(static function (ArtisanApplication $artisan): void {
                $artisan->resolveCommands([
                    GeneratePreviewTokenCommand::class,
                    GenerateSitemapCommand::class,
                ]);
            });
        }
    }
}
