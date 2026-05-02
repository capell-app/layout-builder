<?php

declare(strict_types=1);

use Capell\Address\Support\FlagIconRenderer;
use Illuminate\Support\Facades\File;

require_once __DIR__ . '/../AddressTestCase.php';
require_once __DIR__ . '/../../src/Support/FlagIconRenderer.php';

beforeEach(function (): void {
    File::deleteDirectory(public_path('vendor/blade-country-flags'));
    app()->singleton(FlagIconRenderer::class);
});

afterEach(function (): void {
    File::deleteDirectory(public_path('vendor/blade-country-flags'));
});

it('resolves a published blade country flag asset from an iso code', function (): void {
    File::ensureDirectoryExists(public_path('vendor/blade-country-flags'));
    File::put(public_path('vendor/blade-country-flags/4x3-fr.svg'), '<svg></svg>');

    expect(resolve(FlagIconRenderer::class)->assetPath('fr'))->toBe('vendor/blade-country-flags/4x3-fr.svg');
});

it('renders a published country flag through the admin renderer contract', function (): void {
    if (! interface_exists(Capell\Admin\Contracts\Support\FlagIconRenderer::class)) {
        $this->markTestSkipped('The installed admin package does not expose the flag renderer contract.');
    }

    app()->singleton(Capell\Admin\Contracts\Support\FlagIconRenderer::class, FlagIconRenderer::class);
    File::ensureDirectoryExists(public_path('vendor/blade-country-flags'));
    File::put(public_path('vendor/blade-country-flags/4x3-fr.svg'), '<svg></svg>');

    $html = resolve(Capell\Admin\Contracts\Support\FlagIconRenderer::class)
        ->render('flag-4x3-fr', 'France', attributes: ['class' => 'h-4'])
        ->toHtml();

    expect($html)
        ->toContain('src="http://localhost/vendor/blade-country-flags/4x3-fr.svg"')
        ->toContain('alt="France"')
        ->toContain('h-4');
});

it('resolves a published blade country flag asset from a blade icon name', function (): void {
    File::ensureDirectoryExists(public_path('vendor/blade-country-flags'));
    File::put(public_path('vendor/blade-country-flags/4x3-gb-eng.svg'), '<svg></svg>');

    expect(resolve(FlagIconRenderer::class)->assetPath('flag-4x3-gb-eng'))->toBe('vendor/blade-country-flags/4x3-gb-eng.svg');
});

it('falls back to a readable label for missing or invalid assets', function (): void {
    $renderer = resolve(FlagIconRenderer::class);

    expect($renderer->assetPath('flag-4x3-fr'))->toBeNull()
        ->and($renderer->fallbackLabel('flag-4x3-fr', 'France'))->toBe('France')
        ->and($renderer->fallbackLabel('../fr'))->toBe('FR');
});
