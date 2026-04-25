<?php

declare(strict_types=1);

namespace Capell\Toolbar\Providers;

use Illuminate\Support\ServiceProvider;

class ToolbarServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'capell-frontend-toolbar');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/capell-frontend-toolbar.php', 'capell-frontend-toolbar');
    }
}
