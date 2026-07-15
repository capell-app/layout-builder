<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Fragments\LayoutBuilderFragmentUrlResolver;
use Illuminate\Support\Facades\File;

it('does not reintroduce the superseded global fragment builder', function (): void {
    $legacyContract = implode('', ['Deferred', 'Fragment', 'Reference', 'Builder']);
    $legacyClass = 'Capell\\Frontend\\Contracts\\' . $legacyContract;
    $source = dirname(__DIR__, 3) . '/src';

    expect(interface_exists($legacyClass))->toBeFalse()
        ->and(class_exists(LayoutBuilderFragmentUrlResolver::class))->toBeTrue();

    foreach (File::allFiles($source) as $file) {
        expect(File::get($file->getPathname()))
            ->not->toContain($legacyContract, $file->getPathname());
    }
});
