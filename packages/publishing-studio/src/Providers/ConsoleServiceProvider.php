<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\PublishingStudio\Console\Commands\InstallCommand;
use Capell\PublishingStudio\Console\Commands\LoadTestPublishingStudioCommand;
use Capell\PublishingStudio\Console\Commands\PruneAbandonedPublishingStudioCommand;
use Capell\PublishingStudio\PublishScheduledPublishingStudioJob;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->commands([
            InstallCommand::class,
            LoadTestPublishingStudioCommand::class,
            PruneAbandonedPublishingStudioCommand::class,
        ]);

        if ($this->app->runningInConsole()) {
            $this->registerSchedule();
        }
    }

    private function registerSchedule(): void
    {
        if (! CapellCore::isPackageInstalled(PublishingStudioServiceProvider::$packageName)) {
            return;
        }

        if (config('capell.publishing-studio.scheduled_publish_enabled', true) !== true) {
            return;
        }

        Schedule::job(new PublishScheduledPublishingStudioJob)
            ->everyMinute()
            ->withoutOverlapping()
            ->name('capell-publishing-studio-scheduled-publish')
            ->onOneServer();

        if (config('capell.publishing-studio.prune_schedule_enabled', false) !== true) {
            return;
        }

        $cron = config('capell.publishing-studio.prune_schedule_cron', '15 3 * * *');

        Schedule::command('capell:publishing-studio:prune')
            ->cron($cron)
            ->withoutOverlapping()
            ->name('capell-publishing-studio-prune')
            ->onOneServer();
    }
}
