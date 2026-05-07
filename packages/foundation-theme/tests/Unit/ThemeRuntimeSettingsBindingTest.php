<?php

declare(strict_types=1);

use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\ThemeStudio\Core\Contracts\ThemeRuntimeSettings;

it('reuses the theme runtime settings instance within a request', function (): void {
    $provider = new FoundationThemeServiceProvider(app());
    $method = new ReflectionMethod($provider, 'registerThemeRuntime');
    $method->invoke($provider);

    $first = app(ThemeRuntimeSettings::class);
    $second = app(ThemeRuntimeSettings::class);

    expect($second)->toBe($first);
});
