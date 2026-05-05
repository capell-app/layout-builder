<?php

declare(strict_types=1);

use Capell\Migrator\Actions\InstallMigratorPermissionsAction;
use Spatie\Permission\Models\Permission;

it('installs the full migrator permission matrix', function (): void {
    InstallMigratorPermissionsAction::run();

    $installed = Permission::query()
        ->whereIn('name', InstallMigratorPermissionsAction::permissionNames())
        ->pluck('name')
        ->all();

    expect($installed)
        ->toEqualCanonicalizing(InstallMigratorPermissionsAction::permissionNames());
});

it('registers every permission listed in plan section 6.9', function (): void {
    $expected = [
        'page.export',
        'site.export',
        'page.import',
        'site.import',
        'page.import.update-shared-relations',
        'page.import.publish-live',
        'import-session.view',
        'import-session.cancel',
        'import-session.retry',
    ];

    expect(InstallMigratorPermissionsAction::permissionNames())
        ->toEqualCanonicalizing($expected);
});

it('is idempotent when invoked repeatedly', function (): void {
    InstallMigratorPermissionsAction::run();
    InstallMigratorPermissionsAction::run();

    $expected = count(InstallMigratorPermissionsAction::permissionNames());
    $actual = Permission::query()
        ->whereIn('name', InstallMigratorPermissionsAction::permissionNames())
        ->count();

    expect($actual)->toBe($expected);
});
