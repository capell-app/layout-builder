<?php

declare(strict_types=1);

use Capell\Themes\Agency\Actions\InstallAgencyThemeAction;
use Capell\Themes\Agency\Actions\SeedAgencyLayoutsAction;

test('install action class exists and is callable', function () {
    $action = new InstallAgencyThemeAction;
    expect(method_exists($action, 'handle'))->toBeTrue();
});

test('seed layouts action returns empty array when Mosaic is not installed', function () {
    $action = new SeedAgencyLayoutsAction;
    // Mosaic is not installed in the package test harness, so handle() should
    // no-op and return an empty array — which is safe to assert without DB.
    expect($action->handle())->toBeArray()->toBeEmpty();
});

test('seed layouts action exposes home, work and contact definitions', function () {
    $action = new SeedAgencyLayoutsAction;
    $layouts = $action->layouts();

    expect($layouts)->toHaveKeys(['home', 'work', 'contact']);
    expect($layouts['home']['widgets'])->toBeArray()->not->toBeEmpty();
});
