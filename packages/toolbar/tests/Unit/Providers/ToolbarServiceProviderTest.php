<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Toolbar\Providers\ToolbarServiceProvider;

it('registers toolbar package metadata for install workflows', function (): void {
    $package = CapellCore::getPackage(ToolbarServiceProvider::$packageName);

    expect($package->name)->toBe('capell-app/frontend-toolbar')
        ->and($package->serviceProviderClass)->toBe(ToolbarServiceProvider::class)
        ->and($package->path)->toBe(realpath(__DIR__ . '/../../../'))
        ->and($package->getDescription())->toBe('Admin toolbar and beacon for Capell frontend');
});
