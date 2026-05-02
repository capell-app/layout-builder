<?php

declare(strict_types=1);

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use Capell\Admin\Actions\SeedDefaultRolesAction;
use Capell\Tests\Fixtures\Models\User;
use Capell\Workspaces\Actions\InstallWorkspaceRolesAction;
use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Policies\WorkspacePolicy;
use Spatie\Permission\Models\Permission;

/**
 * Build a Shield-formatted permission name using the same config-driven logic
 * as the policy itself, so these tests stay correct under any Shield config.
 */
function workspacePermission(string $affix): string
{
    $permissions = Utils::getConfig()->permissions;

    return FilamentShield::defaultPermissionKeyBuilder(
        affix: $affix,
        separator: $permissions->separator,
        subject: 'Workspace',
        case: $permissions->case,
    );
}

beforeEach(function (): void {
    Permission::findOrCreate(workspacePermission('view_any'));
    Permission::findOrCreate(workspacePermission('view'));
    Permission::findOrCreate(workspacePermission('create'));
    Permission::findOrCreate(workspacePermission('update'));
    Permission::findOrCreate(workspacePermission('delete'));
    Permission::findOrCreate(InstallWorkspaceRolesAction::PERMISSION_SUBMIT);
    Permission::findOrCreate(InstallWorkspaceRolesAction::PERMISSION_APPROVE);
    Permission::findOrCreate(InstallWorkspaceRolesAction::PERMISSION_PUBLISH);
    SeedDefaultRolesAction::run();
});

// --- viewAny ---

it('super_admin can view any workspace', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $policy = new WorkspacePolicy;

    expect($policy->viewAny($user))->toBeTrue();
});

it('user with view_any permission can view any workspace', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(workspacePermission('view_any'));

    $policy = new WorkspacePolicy;

    expect($policy->viewAny($user))->toBeTrue();
});

it('user with only view permission passes viewAny via fallback', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(workspacePermission('view'));

    $policy = new WorkspacePolicy;

    // viewAny falls back to view permission so editors can reach the list.
    expect($policy->viewAny($user))->toBeTrue();
});

it('user without workspace permissions cannot view any workspace', function (): void {
    $user = User::factory()->create();

    $policy = new WorkspacePolicy;

    expect($policy->viewAny($user))->toBeFalse();
});

// --- create ---

it('user with create permission can create a workspace', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(workspacePermission('create'));

    $policy = new WorkspacePolicy;

    expect($policy->create($user))->toBeTrue();
});

// --- update ---

it('user with update permission can update an editable workspace', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(workspacePermission('update'));

    $workspace = Workspace::factory()->create(['status' => WorkspaceStatusEnum::Open]);

    $policy = new WorkspacePolicy;

    expect($policy->update($user, $workspace))->toBeTrue();
});

it('user with update permission cannot update a non-editable workspace', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(workspacePermission('update'));

    $workspace = Workspace::factory()->create(['status' => WorkspaceStatusEnum::InReview]);

    $policy = new WorkspacePolicy;

    expect($policy->update($user, $workspace))->toBeFalse();
});

// --- approve ---

it('user with approve permission can approve a workspace in review', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(InstallWorkspaceRolesAction::PERMISSION_APPROVE);

    $workspace = Workspace::factory()->create(['status' => WorkspaceStatusEnum::InReview]);

    $policy = new WorkspacePolicy;

    expect($policy->approve($user, $workspace))->toBeTrue();
});

it('user with approve permission cannot approve a workspace not in review', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(InstallWorkspaceRolesAction::PERMISSION_APPROVE);

    $workspace = Workspace::factory()->create(['status' => WorkspaceStatusEnum::Open]);

    $policy = new WorkspacePolicy;

    expect($policy->approve($user, $workspace))->toBeFalse();
});
