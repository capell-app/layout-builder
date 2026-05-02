<?php

declare(strict_types=1);

use Capell\ThemeStudio\Saas\SaasThemeServiceProvider;

it('defines the saas premium renderer contract', function (): void {
    $definition = SaasThemeServiceProvider::definition();

    expect($definition->package)->toBe('capell-app/theme-saas')
        ->and($definition->includedSections)->toContain('hero', 'features', 'proof', 'cta')
        ->and($definition->presets)->toHaveCount(3)
        ->and($definition->tags)->toContain('Conversion');
});
