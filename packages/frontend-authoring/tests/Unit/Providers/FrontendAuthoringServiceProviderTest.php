<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\FrontendAuthoring\Providers\FrontendAuthoringServiceProvider;

it('registers authoring package metadata for install workflows', function (): void {
    $package = CapellCore::getPackage(FrontendAuthoringServiceProvider::$packageName);

    expect($package->name)->toBe('capell-app/frontend-authoring')
        ->and($package->serviceProviderClass)->toBe(FrontendAuthoringServiceProvider::class)
        ->and($package->path)->toBe(realpath(__DIR__ . '/../../../'))
        ->and($package->getDescription())->toBe('Frontend authoring bridge and in-page editing for Capell frontend');
});
