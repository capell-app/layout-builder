<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Navigation\Providers\NavigationServiceProvider;

it('registers navigation package metadata for install workflows', function (): void {
    $package = CapellCore::getPackage(NavigationServiceProvider::$packageName);

    expect($package->name)->toBe('capell-app/navigation')
        ->and($package->serviceProviderClass)->toBe(NavigationServiceProvider::class)
        ->and($package->path)->toBe(realpath(__DIR__ . '/../../../'))
        ->and($package->getDescription())->toBe('Navigation for Capell')
        ->and($package->getSetupCommand())->toBe('capell:navigation-setup')
        ->and($package->getSetupParams())->toBe(['sites']);
});
