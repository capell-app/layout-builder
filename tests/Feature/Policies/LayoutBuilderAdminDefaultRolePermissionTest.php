<?php

declare(strict_types=1);

use Capell\Admin\Actions\Shield\ResolveDefaultRolePermissionsAction;
use Spatie\Permission\Models\Permission;

it('adds layout builder permissions to admin default role resolution when installed', function (): void {
    foreach ([
        'EditContent:Layout',
        'EditLayout:Layout',
        'Update:Layout',
    ] as $permission) {
        Permission::findOrCreate($permission);
    }

    expect(ResolveDefaultRolePermissionsAction::run('admin', 'web'))->toContain(
        'EditContent:Layout',
        'EditLayout:Layout',
        'Update:Layout',
    );
});

it('adds content-first layout permission to editor default role resolution when installed', function (): void {
    Permission::findOrCreate('EditContent:Layout');
    Permission::findOrCreate('EditLayout:Layout');

    expect(ResolveDefaultRolePermissionsAction::run('editor', 'web'))->toContain('EditContent:Layout')
        ->not->toContain('EditLayout:Layout');
});
