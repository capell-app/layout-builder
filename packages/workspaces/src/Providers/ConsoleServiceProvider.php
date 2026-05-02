<?php

declare(strict_types=1);

namespace Capell\Workspaces\Providers;

use Capell\Workspaces\Console\Commands\InstallCommand;
use Capell\Workspaces\Console\Commands\LoadTestWorkspacesCommand;
use Capell\Workspaces\Console\Commands\PruneAbandonedWorkspacesCommand;
use Capell\Workspaces\PublishScheduledWorkspacesJob;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                LoadTestWorkspacesCommand::class,
                PruneAbandonedWorkspacesCommand::class,
            ]);

            $this->registerSchedule();
        }
    }

    private function registerSchedule(): void
    {
        if (! config('capell.workspaces.scheduled_publish_enabled', true)) {
            return;
        }

        Schedule::job(new PublishScheduledWorkspacesJob)
            ->everyMinute()
            ->withoutOverlapping()
            ->name('capell-workspaces-scheduled-publish')
            ->onOneServer();

        if (! config('capell.workspaces.prune_schedule_enabled', false)) {
            return;
        }

        $cron = config('capell.workspaces.prune_schedule_cron', '15 3 * * *');

        Schedule::command('capell:workspaces:prune')
            ->cron($cron)
            ->withoutOverlapping()
            ->name('capell-workspaces-prune')
            ->onOneServer();
    }
}
