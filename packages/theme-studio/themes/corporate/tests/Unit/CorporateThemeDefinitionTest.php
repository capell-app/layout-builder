<?php

declare(strict_types=1);

use Capell\ThemeStudio\Corporate\CorporateThemeServiceProvider;

it('defines the corporate premium renderer contract', function (): void {
    $definition = CorporateThemeServiceProvider::definition();

    expect($definition->package)->toBe('capell-app/theme-corporate')
        ->and($definition->includedSections)->toContain('hero', 'features', 'proof', 'cta')
        ->and($definition->presets)->toHaveCount(3)
        ->and($definition->tags)->toContain('Trust');
});
