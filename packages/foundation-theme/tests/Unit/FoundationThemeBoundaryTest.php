<?php

declare(strict_types=1);

it('owns the opinionated public body behavior', function (): void {
    $body = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/app/body.blade.php');

    expect($body)->toContain('font-sans')
        ->and($body)->toContain('dark:bg-gray-950')
        ->and($body)->toContain('showLightbox');
});

it('owns the opinionated content prose and divider behavior', function (): void {
    $content = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/content.blade.php');
    $themeCss = file_get_contents(dirname(__DIR__, 2) . '/resources/css/theme/theme.css');

    expect($content)->toContain('content-component prose')
        ->and($content)->toContain('prose-invert')
        ->and($content)->toContain('prose-muted')
        ->and($content)->toContain('var(--color-divider)')
        ->and($themeCss)->toContain('.prose-muted')
        ->and($themeCss)->toContain('.prose-compact');
});

it('owns the token-backed link utilities', function (): void {
    $themeCss = file_get_contents(dirname(__DIR__, 2) . '/resources/css/theme/theme.css');

    expect($themeCss)->toContain('.text-brand')
        ->and($themeCss)->toContain('--color-brand')
        ->and($themeCss)->toContain('--color-link');
});

it('owns the foundation frontend javascript runtime', function (): void {
    $entrypoint = file_get_contents(dirname(__DIR__, 2) . '/resources/js/capell-frontend.js');
    $config = file_get_contents(dirname(__DIR__, 2) . '/config/capell-foundation-theme.php');

    expect($entrypoint)->toContain('@ryangjchandler/alpine-tooltip')
        ->and($entrypoint)->toContain('@awcodes/alpine-floating-ui')
        ->and($entrypoint)->toContain('./utilities/lightbox')
        ->and($config)->toContain('@ryangjchandler/alpine-tooltip')
        ->and($config)->toContain('@awcodes/alpine-floating-ui');
});

it('loads layout builder javascript only when the frontend layout uses widgets', function (): void {
    $provider = file_get_contents(dirname(__DIR__, 2) . '/src/Providers/FoundationThemeServiceProvider.php');

    expect($provider)->toContain('VendorAssetConditionRegistry')
        ->and($provider)->toContain('LAYOUT_BUILDER_ASSETS_CONDITION')
        ->and($provider)->toContain('currentLayoutHasWidgets')
        ->and($provider)->toContain('condition: self::LAYOUT_BUILDER_ASSETS_CONDITION');
});

it('owns the default body content and layout component files', function (): void {
    $layout = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/layout/index.blade.php');

    expect(file_exists(dirname(__DIR__, 2) . '/resources/views/components/app/body.blade.php'))->toBeTrue()
        ->and(file_exists(dirname(__DIR__, 2) . '/resources/views/components/content.blade.php'))->toBeTrue()
        ->and(file_exists(dirname(__DIR__, 2) . '/resources/views/components/layout/index.blade.php'))->toBeTrue()
        ->and($layout)->toContain('<x-capell::header.index />')
        ->and($layout)->toContain("\$theme['meta']['footer_file'] ?? 'capell::footer'");
});

it('keeps runtime asset registrations behind the installed package guard', function (): void {
    $provider = file_get_contents(dirname(__DIR__, 2) . '/src/Providers/FoundationThemeServiceProvider.php');

    $guardPosition = strpos($provider, 'if (! $this->isPackageInstalled())');
    $assetRegistrationPosition = strpos($provider, '$this->registerVendorCssJsAssets();');

    expect($guardPosition)->not->toBeFalse()
        ->and($assetRegistrationPosition)->not->toBeFalse()
        ->and($guardPosition)->toBeLessThan($assetRegistrationPosition);
});

it('registers foundation chrome components for admin selection', function (): void {
    $provider = file_get_contents(dirname(__DIR__, 2) . '/src/Providers/FoundationThemeServiceProvider.php');

    expect($provider)->toContain("registerHeader('capell::header.index'")
        ->and($provider)->toContain("registerFooter('capell::footer'");
});

it('does not rebuild tailwind assets for runtime theme color changes', function (): void {
    $provider = file_get_contents(dirname(__DIR__, 2) . '/src/Providers/FoundationThemeServiceProvider.php');
    $command = file_get_contents(dirname(__DIR__, 2) . '/src/Console/Commands/GenerateTailwindAssetsCommand.php');
    $generator = file_get_contents(dirname(__DIR__, 2) . '/src/Support/Tailwind/TailwindAssetsGenerator.php');
    $tokens = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/app/head/tokens.blade.php');

    expect($provider)->not->toContain('ThemeColorsUpdated')
        ->and($command)->not->toContain('--theme-key')
        ->and($generator)->toContain('DefaultColorEnum::getKeyValues()')
        ->and($tokens)->toContain('->merge($theme->colors)');
});

it('keeps foundation header menu controls screen reader accurate', function (): void {
    $header = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/header/index.blade.php');

    expect($header)->toContain('aria-controls="main-menu"')
        ->and($header)->toContain('x-bind:aria-expanded="isMenuOpen.toString()"')
        ->and($header)->toContain('x-text=')
        ->and($header)->toContain("isMenuOpen\n                                ? '{{ __('capell-frontend::generic.close_menu') }}'")
        ->and($header)->toContain(": '{{ __('capell-frontend::generic.open_menu') }}'")
        ->and($header)->not->toContain("\$refs.toggleMenu.setAttribute('aria-expanded', 'true')");
});
