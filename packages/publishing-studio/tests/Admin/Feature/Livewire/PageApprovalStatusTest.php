<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\PublishingStudio\Actions\CopyOnWriteAction;
use Capell\PublishingStudio\Enums\WorkspaceApprovalActionEnum;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Livewire\PageApprovalStatus;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Models\WorkspaceApproval;
use Livewire\Livewire;

it('renders nothing for a live page', function (): void {
    $page = Page::factory()->create()->fresh();

    Livewire::test(PageApprovalStatus::class, ['record' => $page])
        ->assertDontSee('Waiting on review')
        ->assertDontSee('Changes requested');
});

it('renders nothing when workspace status is Open', function (): void {
    $live = Page::factory()->create();
    $workspace = Workspace::factory()->create(['status' => WorkspaceStatusEnum::Open]);
    $draft = (new CopyOnWriteAction)->cloneForEdit($live->fresh()->fill(['name' => 'x']), $workspace);

    Livewire::test(PageApprovalStatus::class, ['record' => $draft->fresh()])
        ->assertDontSee('Waiting on review');
});

it('shows Waiting on review title when status is InReview', function (): void {
    $live = Page::factory()->create();
    $workspace = Workspace::factory()->create(['status' => WorkspaceStatusEnum::InReview]);
    $draft = (new CopyOnWriteAction)->cloneForEdit($live->fresh()->fill(['name' => 'x']), $workspace);

    Livewire::test(PageApprovalStatus::class, ['record' => $draft->fresh()])
        ->assertSee('Waiting on review');
});

it('shows Changes requested title when latest approval action is ChangesRequested', function (): void {
    $live = Page::factory()->create();
    $workspace = Workspace::factory()->create(['status' => WorkspaceStatusEnum::Open]);
    $draft = (new CopyOnWriteAction)->cloneForEdit($live->fresh()->fill(['name' => 'x']), $workspace);

    WorkspaceApproval::factory()->create([
        'workspace_id' => $workspace->id,
        'action' => WorkspaceApprovalActionEnum::ChangesRequested,
    ]);

    Livewire::test(PageApprovalStatus::class, ['record' => $draft->fresh()])
        ->assertSee('Changes requested');
});
