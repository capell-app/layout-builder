<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Actions\Dashboard\BuildMyWorkQueueAction;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Models\WorkspaceReviewAssignment;

uses(CreatesAdminUser::class);

it('includes draft pages from workspaces the user owns', function (): void {
    $owner = $this->createUser();

    $workspace = Workspace::factory()->open()->create(['created_by' => $owner->id]);
    $page = Page::factory()->create(['workspace_id' => $workspace->id]);

    $result = BuildMyWorkQueueAction::run($owner);
    $ids = $result->items->toCollection()->pluck('pageId');

    expect($ids)->toContain($page->id);
});

it("excludes draft pages from other users' workspaces", function (): void {
    $owner = $this->createUser();
    $other = $this->createUser();

    $workspace = Workspace::factory()->open()->create(['created_by' => $other->id]);
    Page::factory()->create(['workspace_id' => $workspace->id]);

    $result = BuildMyWorkQueueAction::run($owner);

    expect($result->items->count())->toBe(0);
});

it("includes pages awaiting the user's approval", function (): void {
    $reviewer = $this->createUser();

    $workspace = Workspace::factory()->inReview()->create();
    $page = Page::factory()->create(['workspace_id' => $workspace->id]);

    WorkspaceReviewAssignment::factory()->create([
        'workspace_id' => $workspace->id,
        'reviewer_type' => $reviewer->getMorphClass(),
        'reviewer_id' => $reviewer->id,
        'decision' => null,
    ]);

    $result = BuildMyWorkQueueAction::run($reviewer);
    $ids = $result->items->toCollection()->pluck('pageId');

    expect($ids)->toContain($page->id);
});

it('includes scheduled pages within the window', function (): void {
    $owner = $this->createUser();

    $workspace = Workspace::factory()->scheduled(now()->addDays(3))->create(['created_by' => $owner->id]);
    $page = Page::factory()->create(['workspace_id' => $workspace->id]);

    $result = BuildMyWorkQueueAction::run($owner, 15, 7);
    $ids = $result->items->toCollection()->pluck('pageId');

    expect($ids)->toContain($page->id);
});

it('excludes scheduled pages outside the window', function (): void {
    $owner = $this->createUser();

    $workspace = Workspace::factory()->scheduled(now()->addDays(30))->create(['created_by' => $owner->id]);
    Page::factory()->create(['workspace_id' => $workspace->id]);

    $result = BuildMyWorkQueueAction::run($owner, 15, 7);

    expect($result->items->count())->toBe(0);
});

it('respects the limit parameter', function (): void {
    $owner = $this->createUser();

    $workspace = Workspace::factory()->open()->create(['created_by' => $owner->id]);
    Page::factory()->count(10)->create(['workspace_id' => $workspace->id]);

    $result = BuildMyWorkQueueAction::run($owner, 3);

    expect($result->items->count())->toBe(3);
});
