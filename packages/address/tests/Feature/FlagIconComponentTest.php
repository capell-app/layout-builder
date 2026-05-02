<?php

declare(strict_types=1);

use Capell\Address\Support\FlagIconRenderer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

require_once __DIR__ . '/../AddressTestCase.php';
require_once __DIR__ . '/../../src/Support/FlagIconRenderer.php';
require_once __DIR__ . '/../../src/View/Components/FlagIcon.php';

beforeEach(function (): void {
    File::deleteDirectory(public_path('vendor/blade-country-flags'));
    app()->singleton(FlagIconRenderer::class);
    View::addNamespace('capell-address', __DIR__ . '/../../resources/views');
    Blade::componentNamespace('Capell\\Address\\View\\Components', 'capell-address');
});

afterEach(function (): void {
    File::deleteDirectory(public_path('vendor/blade-country-flags'));
});

it('renders a published blade country flag asset', function (): void {
    File::ensureDirectoryExists(public_path('vendor/blade-country-flags'));
    File::put(public_path('vendor/blade-country-flags/4x3-fr.svg'), '<svg></svg>');

    $html = Blade::render('<x-capell-address::flag-icon flag="flag-4x3-fr" label="France" class="h-4" />');

    expect($html)
        ->toContain('src="http://localhost/vendor/blade-country-flags/4x3-fr.svg"')
        ->toContain('alt="France"')
        ->toContain('h-4')
        ->not->toContain('Missing flag');
});

it('falls back to a label when the published flag asset is missing', function (): void {
    $html = Blade::render('<x-capell-address::flag-icon flag="flag-4x3-gb-eng" label="England" />');

    expect($html)
        ->toContain('England')
        ->not->toContain('<img');
});
