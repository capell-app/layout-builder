<?php

declare(strict_types=1);

use Capell\ThemeStudio\Agency\AgencyThemeServiceProvider;

it('defines the agency premium renderer contract', function (): void {
    $definition = AgencyThemeServiceProvider::definition();

    expect($definition->package)->toBe('capell-app/theme-agency')
        ->and($definition->includedSections)->toContain('hero', 'features', 'proof', 'cta')
        ->and($definition->presets)->toHaveCount(3)
        ->and($definition->tags)->toContain('Expressive');
});
