<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Core\Models\Page;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Actions\CopyOnWriteAction;
use Capell\Workspaces\Enums\WorkspaceApprovalActionEnum;
use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Models\WorkspaceApproval;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    Role::findOrCreate('super_admin');
    $adminUser = $this->createUser();
    $adminUser->assignRole('super_admin');
    $this->actingAs($adminUser);
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

    Livewire::test(EditPage::class, ['record' => $draft->getRouteKey()])
        ->assertActionVisible('publish');
});

it('is visible on a draft with Approved status', function (): void {
    $draft = draftInWorkspace(WorkspaceStatusEnum::Approved);

    Livewire::test(EditPage::class, ['record' => $draft->getRouteKey()])
        ->assertActionVisible('publish');
});

it('is disabled on a draft with InReview status', function (): void {
    $draft = draftInWorkspace(WorkspaceStatusEnum::InReview);

    Livewire::test(EditPage::class, ['record' => $draft->getRouteKey()])
        ->assertActionDisabled('publish');
});

it('publishes the workspace and returns user to live record', function (): void {
    $draft = draftInWorkspace(WorkspaceStatusEnum::Approved);
    $workspaceId = $draft->workspace_id;

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

    Livewire::test(EditPage::class, ['record' => $draft->getRouteKey()])
        ->assertActionVisible('resubmitForReview');
});
