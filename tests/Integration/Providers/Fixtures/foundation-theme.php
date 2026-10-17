<?php

declare(strict_types=1);

use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Illuminate\Foundation\Application;

return static function (Application $app): void {
    $app->instance('installed-runtime.scenario', 'foundation-theme');
    // The isolated harness loads required companions; this theme is an optional fixture.
    $app->register(FoundationThemeServiceProvider::class);
};
