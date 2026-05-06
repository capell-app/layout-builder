<?php

declare(strict_types=1);

use Capell\PublishingStudio\Http\Middleware\ResolveWorkspaceContext;
use Capell\PublishingStudio\Livewire\WorkspaceSwitcher;
use Capell\PublishingStudio\Models\Workspace;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Support\Facades\Session;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)->group('security', 'workspace');

beforeEach(function (): void {
    Session::forget(ResolveWorkspaceContext::SESSION_KEY);
});

it('rejects switchTo from a guest', function (): void {
    $workspace = Workspace::factory()->create();

    livewire(WorkspaceSwitcher::class)
        ->call('switchTo', $workspace->id)
        ->assertForbidden();

    expect(Session::get(ResolveWorkspaceContext::SESSION_KEY))->toBeNull();
});

it('rejects switchTo to a workspace the user has no permission to view', function (): void {
    test()->actingAsUser();

    $workspace = Workspace::factory()->create();

    livewire(WorkspaceSwitcher::class)
        ->call('switchTo', $workspace->id)
        ->assertForbidden();

    expect(Session::get(ResolveWorkspaceContext::SESSION_KEY))->toBeNull();
});

it('allows switchTo for a user granted view_workspace permission', function (): void {
    Permission::findOrCreate('View:Workspace');

    test()->actingAs(test()->createUserWithPermission('View:Workspace'));

    $workspace = Workspace::factory()->create();

    livewire(WorkspaceSwitcher::class)
        ->call('switchTo', $workspace->id);

    expect(Session::get(ResolveWorkspaceContext::SESSION_KEY))->toBe($workspace->id);
});

it('silently does nothing when switchTo targets a missing workspace id', function (): void {
    Permission::findOrCreate('View:Workspace');

    test()->actingAs(test()->createUserWithPermission('View:Workspace'));

    livewire(WorkspaceSwitcher::class)
        ->call('switchTo', 99999999);

    expect(Session::get(ResolveWorkspaceContext::SESSION_KEY))->toBeNull();
});
