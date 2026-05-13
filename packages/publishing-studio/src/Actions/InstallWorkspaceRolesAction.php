<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions;

use Capell\PublishingStudio\Enums\PublishingStudioPermission;
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

    public const ROLE_EDITOR = 'workspace_editor';

    public const ROLE_REVIEWER = 'workspace_reviewer';

    public const ROLE_RELEASE_MANAGER = 'workspace_release_manager';

    public const PERMISSION_SUBMIT = 'submit_workspace_for_approval';

    public const PERMISSION_APPROVE = 'approve_workspace';

    public const PERMISSION_PUBLISH = 'publish_workspace';

    public const PERMISSION_ROLLBACK = 'rollback_workspace';

    public const PERMISSION_PUBLISH_OUTSIDE_WINDOW = 'publish_outside_release_window';

    public function handle(?string $guardName = null): void
    {
        $guard = $guardName ?? config('auth.defaults.guard', 'web');

        EnsurePublishingStudioPermissionsAction::run($guard);

        $submitPermission = $this->permission(PublishingStudioPermission::SubmitWorkspaceForApproval, $guard);
        $approvePermission = $this->permission(PublishingStudioPermission::ApproveWorkspace, $guard);
        $publishPermission = $this->permission(PublishingStudioPermission::PublishWorkspace, $guard);
        $rollbackPermission = $this->permission(PublishingStudioPermission::RollbackWorkspace, $guard);
        $bypassWindowPermission = $this->permission(PublishingStudioPermission::PublishOutsideReleaseWindow, $guard);
        $workflowPagePermission = $this->permission(PublishingStudioPermission::ViewPublishingWorkflowPage, $guard);
        $scheduledPagePermission = $this->permission(PublishingStudioPermission::ViewScheduledPublishingPage, $guard);
        $staleDraftsPagePermission = $this->permission(PublishingStudioPermission::ViewStaleDraftsPage, $guard);

        $editorRole = $this->role(self::ROLE_EDITOR, $guard);
        $reviewerRole = $this->role(self::ROLE_REVIEWER, $guard);
        $releaseManagerRole = $this->role(self::ROLE_RELEASE_MANAGER, $guard);

        $editorRole->givePermissionTo([
            $submitPermission,
            $workflowPagePermission,
            $staleDraftsPagePermission,
        ]);

        $reviewerRole->givePermissionTo([
            $submitPermission,
            $approvePermission,
            $workflowPagePermission,
            $staleDraftsPagePermission,
        ]);

        $releaseManagerRole->givePermissionTo([
            $submitPermission,
            $approvePermission,
            $publishPermission,
            $rollbackPermission,
            $bypassWindowPermission,
            $workflowPagePermission,
            $scheduledPagePermission,
            $staleDraftsPagePermission,
        ]);
    }

    private function permission(PublishingStudioPermission $permission, string $guardName): Permission
    {
        /** @var Permission $model */
        $model = Permission::query()
            ->where('name', $permission->value)
            ->where('guard_name', $guardName)
            ->firstOrFail();

        return $model;
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
