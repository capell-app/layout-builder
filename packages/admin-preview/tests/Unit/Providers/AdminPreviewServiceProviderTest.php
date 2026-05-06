<?php

declare(strict_types=1);

use Capell\AdminPreview\Providers\AdminPreviewServiceProvider;
use Capell\Core\Facades\CapellCore;

it('registers the package with Capell Core', function (): void {
    expect(CapellCore::getPackage(AdminPreviewServiceProvider::$packageName)->name)
        ->toBe('capell-app/admin-preview');
});
