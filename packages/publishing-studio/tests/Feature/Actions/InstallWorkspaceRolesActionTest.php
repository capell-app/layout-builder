<?php

declare(strict_types=1);

use Capell\PublishingStudio\Actions\InstallWorkspaceRolesAction;
use Capell\PublishingStudio\Enums\PublishingStudioPermission;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('installs the three workspace roles with the expected permission tiers', function (): void {
    InstallWorkspaceRolesAction::run();

    $editor = Role::query()->where('name', InstallWorkspaceRolesAction::ROLE_EDITOR)->firstOrFail();
    $reviewer = Role::query()->where('name', InstallWorkspaceRolesAction::ROLE_REVIEWER)->firstOrFail();
    $releaseManager = Role::query()->where('name', InstallWorkspaceRolesAction::ROLE_RELEASE_MANAGER)->firstOrFail();

    expect($editor->permissions->pluck('name')->all())
        ->toEqualCanonicalizing([InstallWorkspaceRolesAction::PERMISSION_SUBMIT])
        ->and($reviewer->permissions->pluck('name')->all())
        ->toEqualCanonicalizing([
            InstallWorkspaceRolesAction::PERMISSION_SUBMIT,
            InstallWorkspaceRolesAction::PERMISSION_APPROVE,
        ])
        ->and($releaseManager->permissions->pluck('name')->all())
        ->toEqualCanonicalizing([
            InstallWorkspaceRolesAction::PERMISSION_SUBMIT,
            InstallWorkspaceRolesAction::PERMISSION_APPROVE,
            InstallWorkspaceRolesAction::PERMISSION_PUBLISH,
            InstallWorkspaceRolesAction::PERMISSION_ROLLBACK,
            InstallWorkspaceRolesAction::PERMISSION_PUBLISH_OUTSIDE_WINDOW,
        ]);
});

it('grants rollback_workspace only to the release manager role', function (): void {
    InstallWorkspaceRolesAction::run();

    $editor = Role::query()->where('name', InstallWorkspaceRolesAction::ROLE_EDITOR)->firstOrFail();
    $reviewer = Role::query()->where('name', InstallWorkspaceRolesAction::ROLE_REVIEWER)->firstOrFail();
    $releaseManager = Role::query()->where('name', InstallWorkspaceRolesAction::ROLE_RELEASE_MANAGER)->firstOrFail();

    expect(Permission::query()->where('name', InstallWorkspaceRolesAction::PERMISSION_ROLLBACK)->exists())->toBeTrue()
        ->and($editor->hasPermissionTo(InstallWorkspaceRolesAction::PERMISSION_ROLLBACK))->toBeFalse()
        ->and($reviewer->hasPermissionTo(InstallWorkspaceRolesAction::PERMISSION_ROLLBACK))->toBeFalse()
        ->and($releaseManager->hasPermissionTo(InstallWorkspaceRolesAction::PERMISSION_ROLLBACK))->toBeTrue();
});

it('is idempotent when invoked repeatedly — no duplicate roles, permissions, or attachments', function (): void {
    InstallWorkspaceRolesAction::run();
    InstallWorkspaceRolesAction::run();

    $roleNames = [
        InstallWorkspaceRolesAction::ROLE_EDITOR,
        InstallWorkspaceRolesAction::ROLE_REVIEWER,
        InstallWorkspaceRolesAction::ROLE_RELEASE_MANAGER,
    ];

    $permissionNames = PublishingStudioPermission::names();

    expect(Role::query()->whereIn('name', $roleNames)->count())->toBe(3)
        ->and(Permission::query()->whereIn('name', $permissionNames)->count())->toBe(count($permissionNames));

    $editor = Role::query()->where('name', InstallWorkspaceRolesAction::ROLE_EDITOR)->firstOrFail();
    $reviewer = Role::query()->where('name', InstallWorkspaceRolesAction::ROLE_REVIEWER)->firstOrFail();
    $releaseManager = Role::query()->where('name', InstallWorkspaceRolesAction::ROLE_RELEASE_MANAGER)->firstOrFail();

    // Pivot rows are deduplicated by (role_id, permission_id) — running the
    // action a second time must not double-attach permissions to roles.
    expect($editor->permissions()->count())->toBe(1)
        ->and($reviewer->permissions()->count())->toBe(2)
        ->and($releaseManager->permissions()->count())->toBe(5);
});

it('inserts every publishing-studio permission enum case', function (): void {
    InstallWorkspaceRolesAction::run();

    $installed = Permission::query()
        ->whereIn('name', PublishingStudioPermission::names())
        ->pluck('name')
        ->all();

    expect($installed)->toEqualCanonicalizing(PublishingStudioPermission::names());
});

it('keeps manifest permissions traceable to the enum', function (): void {
    $manifestJson = file_get_contents(dirname(__DIR__, 3) . '/capell.json');

    $manifest = json_decode(
        $manifestJson !== false ? $manifestJson : '{}',
        true,
    );

    expect($manifest['permissions'] ?? [])->toEqualCanonicalizing(PublishingStudioPermission::names());
});

it('uses the configured guard name when none is provided', function (): void {
    $expectedGuard = config('auth.defaults.guard', 'web');

    InstallWorkspaceRolesAction::run();

    $editor = Role::query()->where('name', InstallWorkspaceRolesAction::ROLE_EDITOR)->firstOrFail();
    $submitPermission = Permission::query()->where('name', InstallWorkspaceRolesAction::PERMISSION_SUBMIT)->firstOrFail();

    expect($editor->guard_name)->toBe($expectedGuard)
        ->and($submitPermission->guard_name)->toBe($expectedGuard);
});

it('honours an explicit guard name override', function (): void {
    InstallWorkspaceRolesAction::run('api');

    $apiEditor = Role::query()
        ->where('name', InstallWorkspaceRolesAction::ROLE_EDITOR)
        ->where('guard_name', 'api')
        ->first();

    $apiSubmit = Permission::query()
        ->where('name', InstallWorkspaceRolesAction::PERMISSION_SUBMIT)
        ->where('guard_name', 'api')
        ->first();

    expect($apiEditor)->not->toBeNull()
        ->and($apiSubmit)->not->toBeNull();
});
