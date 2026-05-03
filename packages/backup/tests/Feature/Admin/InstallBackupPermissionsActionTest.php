<?php

declare(strict_types=1);

use Capell\Backup\Actions\InstallBackupPermissionsAction;
use Spatie\Permission\Models\Permission;

it('installs the full exchanger permission matrix', function (): void {
    InstallBackupPermissionsAction::run();

    $installed = Permission::query()
        ->whereIn('name', InstallBackupPermissionsAction::permissionNames())
        ->pluck('name')
        ->all();

    expect($installed)
        ->toEqualCanonicalizing(InstallBackupPermissionsAction::permissionNames());
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

    expect(InstallBackupPermissionsAction::permissionNames())
        ->toEqualCanonicalizing($expected);
});

it('is idempotent when invoked repeatedly', function (): void {
    InstallBackupPermissionsAction::run();
    InstallBackupPermissionsAction::run();

    $expected = count(InstallBackupPermissionsAction::permissionNames());
    $actual = Permission::query()
        ->whereIn('name', InstallBackupPermissionsAction::permissionNames())
        ->count();

    expect($actual)->toBe($expected);
});
