<?php

declare(strict_types=1);

namespace Capell\Blog\Providers;

use Capell\Blog\Console\Commands\CreateBlogPagesCommand;
use Capell\Blog\Console\Commands\DemoCommand;
use Capell\Blog\Console\Commands\FakerCommand;
use Capell\Blog\Console\Commands\InstallCommand;
use Capell\Blog\Console\Commands\SetupCommand;
use Illuminate\Support\ServiceProvider;

final class ConsoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateBlogPagesCommand::class,
                DemoCommand::class,
                FakerCommand::class,
                InstallCommand::class,
                SetupCommand::class,
            ]);
        }
    }
}
