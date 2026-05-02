<?php

declare(strict_types=1);

use Capell\Toolbar\Http\Controllers\BeaconController;
use Capell\Toolbar\Http\Requests\BeaconRequest;
use Capell\Toolbar\Providers\ToolbarServiceProvider;

it('BeaconController exists under Capell\Toolbar namespace', function (): void {
    expect(class_exists(BeaconController::class))->toBeTrue();
});

it('BeaconRequest exists under Capell\Toolbar namespace', function (): void {
    expect(class_exists(BeaconRequest::class))->toBeTrue();
});

it('ToolbarServiceProvider exists under Capell\Toolbar namespace', function (): void {
    expect(class_exists(ToolbarServiceProvider::class))->toBeTrue();
});

it('Capell\Frontend\Http\Controllers\BeaconController no longer exists', function (): void {
    expect(class_exists('Capell\Frontend\Http\Controllers\BeaconController'))->toBeFalse();
});

it('Capell\Frontend\Http\Requests\BeaconRequest no longer exists', function (): void {
    expect(class_exists('Capell\Frontend\Http\Requests\BeaconRequest'))->toBeFalse();
});
