<?php

declare(strict_types=1);

use Capell\MigrationAssistant\Actions\InstallMigrationAssistantPermissionsAction;
use Capell\MigrationAssistant\Enums\MigrationAssistantPermission;
use Spatie\Permission\Models\Permission;

it('installs the full migration-assistant permission matrix', function (): void {
    InstallMigrationAssistantPermissionsAction::run();

    $installed = Permission::query()
        ->whereIn('name', InstallMigrationAssistantPermissionsAction::permissionNames())
        ->pluck('name')
        ->all();

    expect($installed)
        ->toEqualCanonicalizing(InstallMigrationAssistantPermissionsAction::permissionNames());
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

    expect(InstallMigrationAssistantPermissionsAction::permissionNames())
        ->toEqualCanonicalizing($expected);
});

it('uses the migration-assistant permission enum as the source of truth', function (): void {
    expect(InstallMigrationAssistantPermissionsAction::permissionNames())
        ->toEqualCanonicalizing(MigrationAssistantPermission::names());
});

it('keeps manifest permissions traceable to the enum', function (): void {
    $manifestJson = file_get_contents(dirname(__DIR__, 3) . '/capell.json');

    $manifest = json_decode(
        $manifestJson !== false ? $manifestJson : '{}',
        true,
    );

    expect($manifest['permissions'] ?? [])->toEqualCanonicalizing(MigrationAssistantPermission::names());
});

it('is idempotent when invoked repeatedly', function (): void {
    InstallMigrationAssistantPermissionsAction::run();
    InstallMigrationAssistantPermissionsAction::run();

    $expected = count(InstallMigrationAssistantPermissionsAction::permissionNames());
    $actual = Permission::query()
        ->whereIn('name', InstallMigrationAssistantPermissionsAction::permissionNames())
        ->count();

    expect($actual)->toBe($expected);
});
