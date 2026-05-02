<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\FilamentPeek\Providers\FilamentPeekServiceProvider;

it('registers the package with Capell Core', function (): void {
    expect(CapellCore::getPackage(FilamentPeekServiceProvider::$packageName)->name)
        ->toBe('capell-app/filament-peek');
});
