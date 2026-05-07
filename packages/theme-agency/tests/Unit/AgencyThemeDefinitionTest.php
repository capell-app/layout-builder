<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\ThemeStudio\Agency\AgencyThemeServiceProvider;
use Capell\ThemeStudio\Core\Data\BrandProfileData;
use Capell\ThemeStudio\Core\Data\NavigationData;
use Capell\ThemeStudio\Core\Data\ThemePageData;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;
use Illuminate\Support\Facades\View;

it('defines the agency premium renderer contract', function (): void {
    $definition = AgencyThemeServiceProvider::definition();

    expect($definition->package)->toBe('capell-app/theme-agency')
        ->and($definition->includedSections)->toContain('hero', 'features', 'proof', 'cta')
        ->and($definition->presets)->toHaveCount(3)
        ->and($definition->tags)->toContain('Expressive');
});

it('renders navigation from the agency package views', function (): void {
    View::addNamespace('capell-theme-agency', __DIR__ . '/../../resources/views');

    $provider = new AgencyThemeServiceProvider($this->app);
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

it('registers agency only when the theme package is installed', function (): void {
    CapellCore::clearPackages();

    $registry = new ThemeRegistry;
    $provider = new AgencyThemeServiceProvider($this->app);
    $provider->register();
    CapellCore::forcePackageInstalled(AgencyThemeServiceProvider::$packageName, false);
    $provider->boot($registry);

    expect($registry->has('agency'))->toBeFalse();

    CapellCore::forcePackageInstalled(AgencyThemeServiceProvider::$packageName);

    $provider->boot($registry);

    expect($registry->has('agency'))->toBeTrue()
        ->and($registry->definition('agency')->package)->toBe(AgencyThemeServiceProvider::$packageName);
});

it('renders the agency theme page wrapper', function (): void {
    CapellCore::clearPackages();
    CapellCore::forcePackageInstalled(AgencyThemeServiceProvider::$packageName);

    $registry = new ThemeRegistry;
    $provider = new AgencyThemeServiceProvider($this->app);
    $provider->register();
    $provider->boot($registry);

    $html = $registry->renderer('agency')->render(new ThemePageData(
        title: 'Birds',
        brand: new BrandProfileData,
        sections: [],
    ));

    expect($html)->toContain('data-capell-theme="agency"');
});
