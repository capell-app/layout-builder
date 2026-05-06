<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\PublishingStudio\Actions\CopyOnWriteAction;
use Capell\PublishingStudio\Actions\DeletePageDraftAction;
use Capell\PublishingStudio\Enums\WorkspaceKindEnum;
use Capell\PublishingStudio\Models\Workspace;

function makeLiveAndDraft(WorkspaceKindEnum $kind = WorkspaceKindEnum::Manual): array
{
    $live = Page::factory()->create();
    $workspace = Workspace::factory()->create(['kind' => $kind]);
    $draft = (new CopyOnWriteAction)->cloneForEdit(
        $live->fresh()->fill(['name' => 'updated']),
        $workspace,
    );

    return [$live->fresh(), $workspace->fresh(), $draft->fresh()];
}

it('throws when passed a live row', function (): void {
    $live = Page::factory()->create()->fresh();

    DeletePageDraftAction::run($live);
})->throws(LogicException::class);

it('deletes the draft row', function (): void {
    [, , $draft] = makeLiveAndDraft();

    DeletePageDraftAction::run($draft);

    $remaining = Page::query()->withoutGlobalScopes()
        ->where('id', $draft->id)
        ->first();

    expect($remaining)->toBeNull();
});

it('clears shadow on every live row owned by the workspace when it becomes empty', function (): void {
    [$liveA, $workspace, $draftA] = makeLiveAndDraft();

    $liveB = Page::factory()->create();
    (new CopyOnWriteAction)->cloneForEdit(
        $liveB->fresh()->fill(['name' => 'b-updated']),
        $workspace,
    );

    expect($liveA->fresh()->shadowed_by_workspace_id)->toBe($workspace->id)
        ->and($liveB->fresh()->shadowed_by_workspace_id)->toBe($workspace->id);

    $secondDraft = Page::query()->withoutGlobalScopes()
        ->where('workspace_id', $workspace->id)
        ->where('id', '!=', $draftA->id)
        ->firstOrFail();

    DeletePageDraftAction::run($draftA);
    DeletePageDraftAction::run($secondDraft);

    expect($liveA->fresh()->shadowed_by_workspace_id)->toBe(0)
        ->and($liveB->fresh()->shadowed_by_workspace_id)->toBe(0);
});

it('keeps live rows shadowed while other drafts remain in the workspace', function (): void {
    [$liveA, $workspace, $draftA] = makeLiveAndDraft();

    $liveB = Page::factory()->create();
    (new CopyOnWriteAction)->cloneForEdit(
        $liveB->fresh()->fill(['name' => 'b-updated']),
        $workspace,
    );

    DeletePageDraftAction::run($draftA);

    expect($liveA->fresh()->shadowed_by_workspace_id)->toBe($workspace->id)
        ->and($liveB->fresh()->shadowed_by_workspace_id)->toBe($workspace->id);
});

it('removes an empty SinglePageDraft workspace after the last draft is deleted', function (): void {
    [, $workspace, $draft] = makeLiveAndDraft(WorkspaceKindEnum::SinglePageDraft);

    DeletePageDraftAction::run($draft);

    expect(Workspace::query()->find($workspace->id))->toBeNull();
});

it('keeps a Manual workspace even when empty after delete', function (): void {
    [, $workspace, $draft] = makeLiveAndDraft(WorkspaceKindEnum::Manual);

    DeletePageDraftAction::run($draft);

    expect(Workspace::query()->find($workspace->id))->not->toBeNull();
});
