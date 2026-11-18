<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Manifest\LayoutBuilderPermissionsContribution;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class GrantLayoutBuilderDefaultRolePermissionsAction
{
    use AsFake;
    use AsObject;

    public function handle(): void
    {
        $configuredGuard = config('auth.defaults.guard', 'web');
        $guard = is_string($configuredGuard) ? $configuredGuard : 'web';
        $roleName = config('capell.roles.editor', 'editor');
        if (! is_string($roleName)) {
            return;
        }

        $role = Role::query()->where('name', $roleName)->where('guard_name', $guard)->first();
        if (! $role instanceof Role) {
            return;
        }

        foreach (LayoutBuilderPermissionsContribution::permissionKeys() as $permission) {
            Permission::findOrCreate($permission, $guard);
        }

        $role->givePermissionTo(LayoutBuilderPermissionsContribution::defaultsForRole('editor'));

        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
