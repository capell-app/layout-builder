<?php

declare(strict_types=1);

it('keeps wildcard package sources in the screenshot workbench Tailwind input', function (): void {
    $script = file_get_contents(dirname(__DIR__, 4) . '/scripts/screenshots/build-filament-theme-css.mjs');

    expect($script)->toBeString()
        ->toMatch("/repoRoot\\s*,\\s*'packages'\\s*,\\s*'\\*'\\s*,\\s*'resources'\\s*,\\s*'views'\\s*,\\s*'\\*\\*'\\s*,\\s*'\\*\\.blade\\.php'/")
        ->toContain('function source(path)')
        ->not->toContain('function optionalSource(path)')
        ->not->toContain('existsSync(path)');
});
