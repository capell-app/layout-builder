<?php

declare(strict_types=1);

use Capell\Api\Providers\ApiServiceProvider;
use Capell\Core\Facades\CapellCore;

it('registers the api package metadata', function (): void {
    $package = CapellCore::getPackage(ApiServiceProvider::$packageName);

    expect($package->name)->toBe(ApiServiceProvider::$packageName)
        ->and(ApiServiceProvider::$name)->toBe('capell-api')
        ->and(ApiServiceProvider::$packageName)->toBe('capell-app/api');
});
