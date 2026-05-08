<?php

declare(strict_types=1);

use Capell\Core\ThemeStudio\Contracts\ThemeRuntimeSettings;

it('reuses the theme runtime settings instance within a request', function (): void {
    $first = resolve(ThemeRuntimeSettings::class);
    $second = resolve(ThemeRuntimeSettings::class);

    expect($second)->toBe($first);
});
