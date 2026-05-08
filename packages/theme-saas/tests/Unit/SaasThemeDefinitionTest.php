<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\ThemeStudio\Data\BrandProfileData;
use Capell\Core\ThemeStudio\Data\NavigationData;
use Capell\Core\ThemeStudio\Data\ThemePageData;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\ThemeStudio\Saas\SaasThemeServiceProvider;
use Illuminate\Support\Facades\View;

it('defines the saas premium renderer contract', function (): void {
    $definition = SaasThemeServiceProvider::definition();

    expect($definition->package)->toBe('capell-app/theme-saas')
        ->and($definition->includedSections)->toContain('hero', 'features', 'proof', 'cta')
        ->and($definition->presets)->toHaveCount(3)
        ->and($definition->tags)->toContain('Conversion');
});

it('renders navigation from the saas package views', function (): void {
    View::addNamespace('capell-theme-saas', __DIR__ . '/../../resources/views');

    $provider = new SaasThemeServiceProvider($this->app);
    $method = new ReflectionMethod($provider, 'sectionRenderers');

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

it('registers saas only when the theme package is installed', function (): void {
    CapellCore::clearPackages();

    $registry = new ThemeRegistry;
    $provider = new SaasThemeServiceProvider($this->app);
    $provider->register();
    CapellCore::forcePackageInstalled(SaasThemeServiceProvider::$packageName, false);
    $provider->boot($registry);

    expect($registry->has('saas'))->toBeFalse();

    CapellCore::forcePackageInstalled(SaasThemeServiceProvider::$packageName);

    $provider->boot($registry);

    expect($registry->has('saas'))->toBeTrue()
        ->and($registry->definition('saas')->package)->toBe(SaasThemeServiceProvider::$packageName);
});

it('renders the saas theme page wrapper', function (): void {
    CapellCore::clearPackages();
    CapellCore::forcePackageInstalled(SaasThemeServiceProvider::$packageName);

    $registry = new ThemeRegistry;
    $provider = new SaasThemeServiceProvider($this->app);
    $provider->register();
    $provider->boot($registry);

    $html = $registry->renderer('saas')->render(new ThemePageData(
        title: 'Birds',
        brand: new BrandProfileData,
        sections: [],
    ));

    expect($html)->toContain('data-capell-theme="saas"');
});
