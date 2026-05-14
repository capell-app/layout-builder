<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Support\LayoutBuilderPermissionRegistrar;
use Spatie\Permission\Models\Permission;

it('contributes content and layout permissions for default roles', function (): void {
    expect(LayoutBuilderPermissionRegistrar::permissionsForRole('editor'))->toBe([
        'ViewAny:Layout',
        'View:Layout',
        'EditContent:Layout',
    ])
        ->and(LayoutBuilderPermissionRegistrar::permissionsForRole('admin'))->toContain(
            'EditContent:Layout',
            'EditLayout:Layout',
            'Update:Layout',
            'Delete:Layout',
        )
        ->and(LayoutBuilderPermissionRegistrar::permissionsForRole('unknown'))->toBe([]);
});

it('filters role permissions to permissions that exist for the guard', function (): void {
    Permission::findOrCreate('EditContent:Layout');
    Permission::findOrCreate('EditLayout:Layout');

    expect(LayoutBuilderPermissionRegistrar::existingPermissionsForRole('admin', 'web'))->toBe([
        'EditContent:Layout',
        'EditLayout:Layout',
    ]);
});
