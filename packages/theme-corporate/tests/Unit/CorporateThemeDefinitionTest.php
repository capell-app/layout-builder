<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\ThemeStudio\Core\Data\NavigationData;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;
use Capell\ThemeStudio\Corporate\CorporateThemeServiceProvider;
use Illuminate\Support\Facades\View;

it('defines the corporate premium renderer contract', function (): void {
    $definition = CorporateThemeServiceProvider::definition();

    expect($definition->package)->toBe('capell-app/theme-corporate')
        ->and($definition->includedSections)->toContain('hero', 'features', 'proof', 'cta')
        ->and($definition->presets)->toHaveCount(3)
        ->and($definition->tags)->toContain('Trust');
});

it('renders navigation from the corporate package views', function (): void {
    View::addNamespace('capell-theme-corporate', __DIR__ . '/../../resources/views');

    $provider = new CorporateThemeServiceProvider($this->app);
    $method = new ReflectionMethod($provider, 'sectionRenderers');
    $method->setAccessible(true);

    $renderer = $method->invoke($provider)['navigation'] ?? null;

    expect($renderer)->not->toBeNull();

    $html = $renderer->render(new NavigationData(
        brandName: 'Capell',
        items: [['label' => 'Home', 'url' => '/']],
    ));

    expect($html)
        ->toContain('Capell')
        ->toContain('Home');
});

it('registers corporate only when the theme package is installed', function (): void {
    CapellCore::clearPackages();

    $registry = new ThemeRegistry;
    $provider = new CorporateThemeServiceProvider($this->app);
    $provider->register();
    CapellCore::forcePackageInstalled(CorporateThemeServiceProvider::$packageName, false);
    $provider->boot($registry);

    expect($registry->has('corporate'))->toBeFalse();

    CapellCore::forcePackageInstalled(CorporateThemeServiceProvider::$packageName);

    $provider->boot($registry);

    expect($registry->has('corporate'))->toBeTrue()
        ->and($registry->definition('corporate')->package)->toBe(CorporateThemeServiceProvider::$packageName);
});
