<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Idempotently install the three default workspace roles and attach the
 * matching custom permissions registered in
 * {@see config('filament-shield.custom_permissions')}.
 *
 * Intended to be called from an application's DatabaseSeeder after Shield's
 * own `shield:generate` has run, or from a dedicated ops console command.
 * Running it repeatedly is safe: existing roles / permissions are looked up
 * via `firstOrCreate` and permission attachments are additive.
 *
 * Tiers:
 *   - workspace_editor: can open and edit publishing-studio, submit for approval.
 *   - workspace_reviewer: above, plus approve / reject a submitted workspace.
 *   - workspace_release_manager: above, plus publish an approved workspace
 *     onto live.
 *
 * Apps wanting a different tiering (e.g. combining reviewer + release into a
 * single senior role) should compose their own seeder rather than fight with
 * this default — the permission names are the stable public contract.
 */
class InstallWorkspaceRolesAction
{
    use AsAction;

    /** @var string */
    public const ROLE_EDITOR = 'workspace_editor';

    /** @var string */
    public const ROLE_REVIEWER = 'workspace_reviewer';

    /** @var string */
    public const ROLE_RELEASE_MANAGER = 'workspace_release_manager';

    /** @var string */
    public const PERMISSION_SUBMIT = 'submit_workspace_for_approval';

    /** @var string */
    public const PERMISSION_APPROVE = 'approve_workspace';

    /** @var string */
    public const PERMISSION_PUBLISH = 'publish_workspace';

    /** @var string */
    public const PERMISSION_ROLLBACK = 'rollback_workspace';

    /** @var string */
    public const PERMISSION_PUBLISH_OUTSIDE_WINDOW = 'publish_outside_release_window';

    public function handle(?string $guardName = null): void
    {
        $guard = $guardName ?? config('auth.defaults.guard', 'web');

        $submitPermission = $this->permission(self::PERMISSION_SUBMIT, $guard);
        $approvePermission = $this->permission(self::PERMISSION_APPROVE, $guard);
        $publishPermission = $this->permission(self::PERMISSION_PUBLISH, $guard);
        $rollbackPermission = $this->permission(self::PERMISSION_ROLLBACK, $guard);
        $bypassWindowPermission = $this->permission(self::PERMISSION_PUBLISH_OUTSIDE_WINDOW, $guard);

        $editorRole = $this->role(self::ROLE_EDITOR, $guard);
        $reviewerRole = $this->role(self::ROLE_REVIEWER, $guard);
        $releaseManagerRole = $this->role(self::ROLE_RELEASE_MANAGER, $guard);

        $editorRole->givePermissionTo($submitPermission);

        $reviewerRole->givePermissionTo([
            $submitPermission,
            $approvePermission,
        ]);

        $releaseManagerRole->givePermissionTo([
            $submitPermission,
            $approvePermission,
            $publishPermission,
            $rollbackPermission,
            $bypassWindowPermission,
        ]);
    }

    private function permission(string $name, string $guardName): Permission
    {
        /** @var Permission $permission */
        $permission = Permission::query()->firstOrCreate([
            'name' => $name,
            'guard_name' => $guardName,
        ]);

        return $permission;
    }

    private function role(string $name, string $guardName): Role
    {
        /** @var Role $role */
        $role = Role::query()->firstOrCreate([
            'name' => $name,
            'guard_name' => $guardName,
        ]);

        return $role;
    }
}
