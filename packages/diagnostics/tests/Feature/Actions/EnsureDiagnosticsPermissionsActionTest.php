<?php

declare(strict_types=1);

use Capell\Diagnostics\Actions\EnsureDiagnosticsPermissionsAction;
use Capell\Diagnostics\Enums\DiagnosticsPermission;
use Spatie\Permission\Models\Permission;

it('inserts every diagnostics permission enum case', function (): void {
    EnsureDiagnosticsPermissionsAction::run();

    $installed = Permission::query()
        ->whereIn('name', DiagnosticsPermission::names())
        ->pluck('name')
        ->all();

    expect($installed)->toEqualCanonicalizing(DiagnosticsPermission::names());
});

it('is idempotent when invoked repeatedly', function (): void {
    EnsureDiagnosticsPermissionsAction::run();
    EnsureDiagnosticsPermissionsAction::run();

    expect(
        Permission::query()
            ->whereIn('name', DiagnosticsPermission::names())
            ->count(),
    )->toBe(count(DiagnosticsPermission::names()));
});

it('keeps manifest permissions traceable to the enum', function (): void {
    $manifestJson = file_get_contents(dirname(__DIR__, 3) . '/capell.json');

    $manifest = json_decode(
        $manifestJson !== false ? $manifestJson : '{}',
        true,
    );

    expect($manifest['permissions'] ?? [])->toEqualCanonicalizing(DiagnosticsPermission::names());
});
