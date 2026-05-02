<?php

declare(strict_types=1);

namespace Capell\Campaigns\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

final class FrontendServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::anonymousComponentNamespace('Capell\\Campaigns\\View\\Components');
    }
}
