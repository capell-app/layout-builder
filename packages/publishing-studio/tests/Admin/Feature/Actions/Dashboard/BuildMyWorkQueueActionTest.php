<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Dashboard\MyWorkQueueDataProvider;
use Capell\Core\Models\Page;
use Capell\PublishingStudio\Actions\Dashboard\BuildMyWorkQueueAction;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Models\WorkspaceReviewAssignment;
use Capell\PublishingStudio\Support\Dashboard\WorkspaceMyWorkQueueDataProvider;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

uses(CreatesAdminUser::class);

it('includes draft pages from publishing-studio the user owns', function (): void {
    $owner = $this->createUser();

    $workspace = Workspace::factory()->open()->create(['created_by' => $owner->id]);
    $page = Page::factory()->create(['workspace_id' => $workspace->id]);

    $result = BuildMyWorkQueueAction::run($owner);
    $ids = $result->items->toCollection()->pluck('pageId');

    expect($ids)->toContain($page->id);
});

it("excludes draft pages from other users' publishing-studio", function (): void {
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

it('binds the workspace queue provider to the admin dashboard contract', function (): void {
    expect(resolve(MyWorkQueueDataProvider::class))
        ->toBeInstanceOf(WorkspaceMyWorkQueueDataProvider::class);
});

it('builds my work queue data through the admin dashboard contract', function (): void {
    $owner = $this->createUser();

    $workspace = Workspace::factory()->open()->create(['created_by' => $owner->id]);
    $page = Page::factory()->create(['workspace_id' => $workspace->id]);

    $result = resolve(MyWorkQueueDataProvider::class)->build($owner, 5);
    $ids = $result->items->toCollection()->pluck('pageId');

    expect($ids)->toContain($page->id);
});
