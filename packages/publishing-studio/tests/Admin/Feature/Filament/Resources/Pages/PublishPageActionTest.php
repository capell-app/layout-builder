<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Core\Models\Page;
use Capell\PublishingStudio\Actions\CopyOnWriteAction;
use Capell\PublishingStudio\Enums\WorkspaceApprovalActionEnum;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Filament\Resources\Pages\Actions\PublishPageAction;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Models\WorkspaceApproval;
use Capell\PublishingStudio\WorkspaceContext;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    Role::findOrCreate('super_admin');
    $adminUser = $this->createUser();
    $adminUser->assignRole('super_admin');
    $this->actingAs($adminUser);

    WorkspaceContext::clear();
});

afterEach(function (): void {
    WorkspaceContext::clear();
});

function draftInWorkspace(WorkspaceStatusEnum $status): Page
{
    $live = Page::factory()->withTranslations()->create();
    $workspace = Workspace::factory()->create(['status' => $status->value]);

    return (new CopyOnWriteAction)->cloneForEdit(
        $live->fresh()->fill(['name' => 'draft name']),
        $workspace,
    );
}

it('is hidden on a live page', function (): void {
    $page = Page::factory()->withTranslations()->create();

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->assertActionHidden('publish');
});

it('is visible on a draft with Open status', function (): void {
    $draft = draftInWorkspace(WorkspaceStatusEnum::Open);

    WorkspaceContext::set($draft->workspace);

    Livewire::test(EditPage::class, ['record' => $draft->getRouteKey()])
        ->assertActionVisible('publish');
});

it('is visible on a draft with Approved status', function (): void {
    $draft = draftInWorkspace(WorkspaceStatusEnum::Approved);

    WorkspaceContext::set($draft->workspace);

    Livewire::test(EditPage::class, ['record' => $draft->getRouteKey()])
        ->assertActionVisible('publish');
});

it('is hidden for users who can update the page but cannot publish the workspace', function (): void {
    Permission::findOrCreate('View:Page');
    Permission::findOrCreate('ViewAny:Page');
    Permission::findOrCreate('Update:Page');

    $user = $this->createUserWithPermission(['View:Page', 'ViewAny:Page', 'Update:Page']);
    $this->actingAs($user);

    $draft = draftInWorkspace(WorkspaceStatusEnum::Approved);

    Gate::define('update', fn (mixed $actor, Page $record): bool => true);

    $method = new ReflectionMethod(PublishPageAction::class, 'userCanPublish');

    expect($user->can('update', $draft))->toBeTrue()
        ->and($method->invoke(PublishPageAction::make(), $draft))->toBeFalse();
});

it('is disabled on a draft with InReview status', function (): void {
    $draft = draftInWorkspace(WorkspaceStatusEnum::InReview);

    WorkspaceContext::set($draft->workspace);

    Livewire::test(EditPage::class, ['record' => $draft->getRouteKey()])
        ->assertActionDisabled('publish');
});

it('publishes the workspace and returns user to live record', function (): void {
    $draft = draftInWorkspace(WorkspaceStatusEnum::Approved);
    $workspaceId = $draft->workspace_id;

    WorkspaceContext::set($draft->workspace);

    Livewire::test(EditPage::class, ['record' => $draft->getRouteKey()])
        ->callAction('publish')
        ->assertNotified();

    $workspace = Workspace::query()->find($workspaceId);

    expect($workspace?->status)->toBe(WorkspaceStatusEnum::Published);

    expect(
        Page::query()
            ->withoutGlobalScopes()
            ->where('id', $draft->getKey())
            ->where('workspace_id', 0)
            ->exists(),
    )->toBeTrue();
});

it('shows the Resubmit for review action when latest approval is ChangesRequested', function (): void {
    $draft = draftInWorkspace(WorkspaceStatusEnum::Open);

    WorkspaceApproval::factory()->create([
        'workspace_id' => $draft->workspace_id,
        'action' => WorkspaceApprovalActionEnum::ChangesRequested->value,
    ]);

    WorkspaceContext::set($draft->workspace);

    Livewire::test(EditPage::class, ['record' => $draft->getRouteKey()])
        ->assertActionVisible('resubmitForReview');
});
