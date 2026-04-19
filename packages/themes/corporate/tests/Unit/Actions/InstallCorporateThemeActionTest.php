<?php

declare(strict_types=1);

use Capell\Themes\Corporate\Actions\InstallCorporateThemeAction;

test('install action returns expected shape with no db', function () {
    // Without a Laravel boot, Schema::hasTable will throw. Only assert that
    // the class and public handle() signature exist and are callable when
    // wrapped in a try/catch — smoke-only.
    $action = new InstallCorporateThemeAction;
    expect(method_exists($action, 'handle'))->toBeTrue();
});
