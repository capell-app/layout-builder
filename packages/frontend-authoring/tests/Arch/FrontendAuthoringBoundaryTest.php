<?php

declare(strict_types=1);

use Capell\FrontendAuthoring\Http\Controllers\BeaconController;
use Capell\FrontendAuthoring\Http\Requests\BeaconRequest;
use Capell\FrontendAuthoring\Providers\FrontendAuthoringServiceProvider;

it('BeaconController exists under Capell\FrontendAuthoring namespace', function (): void {
    expect(class_exists(BeaconController::class))->toBeTrue();
});

it('BeaconRequest exists under Capell\FrontendAuthoring namespace', function (): void {
    expect(class_exists(BeaconRequest::class))->toBeTrue();
});

it('FrontendAuthoringServiceProvider exists under Capell\FrontendAuthoring namespace', function (): void {
    expect(class_exists(FrontendAuthoringServiceProvider::class))->toBeTrue();
});

it('Capell\Frontend\Http\Controllers\BeaconController no longer exists', function (): void {
    expect(class_exists('Capell\Frontend\Http\Controllers\BeaconController'))->toBeFalse();
});

it('Capell\Frontend\Http\Requests\BeaconRequest no longer exists', function (): void {
    expect(class_exists('Capell\Frontend\Http\Requests\BeaconRequest'))->toBeFalse();
});
