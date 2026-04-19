<?php

declare(strict_types=1);

use Capell\Themes\Saas\Actions\InstallSaasThemeAction;
use Capell\Themes\Saas\Actions\SeedSaasLayoutsAction;

test('install action exposes handle() method', function () {
    $action = new InstallSaasThemeAction;
    expect(method_exists($action, 'handle'))->toBeTrue();
});

test('seed layouts action returns empty array when Mosaic is not installed', function () {
    $action = new SeedSaasLayoutsAction;
    expect($action->handle())->toBe([]);
});

test('seed layouts action defines home, pricing and features layouts', function () {
    $action = new SeedSaasLayoutsAction;
    $layouts = $action->layouts();

    expect(array_keys($layouts))->toContain('home', 'pricing', 'features');
    foreach ($layouts as $def) {
        expect($def['name'])->toBeString()
            ->and($def['widgets'])->toBeArray()->not->toBeEmpty();
    }
});
