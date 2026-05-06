<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\PublishingStudio\Actions\Dashboard\BuildWorkspaceActivityAction;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Models\WorkspaceReviewAssignment;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

uses(CreatesAdminUser::class);

it("counts publishing-studio pending the user's approval", function (): void {
    $user = $this->createUser();
    $other = $this->createUser();

    $workspaceA = Workspace::factory()->inReview()->create();
    $workspaceB = Workspace::factory()->inReview()->create();
    $workspaceC = Workspace::factory()->inReview()->create();

    // 3 pending assignments for the user
    WorkspaceReviewAssignment::query()->create([
        'workspace_id' => $workspaceA->id,
        'reviewer_type' => $user->getMorphClass(),
        'reviewer_id' => $user->id,
        'decision' => null,
        'required_for' => 'publish',
    ]);
    WorkspaceReviewAssignment::query()->create([
        'workspace_id' => $workspaceB->id,
        'reviewer_type' => $user->getMorphClass(),
        'reviewer_id' => $user->id,
        'decision' => null,
        'required_for' => 'publish',
    ]);
    WorkspaceReviewAssignment::query()->create([
        'workspace_id' => $workspaceC->id,
        'reviewer_type' => $user->getMorphClass(),
        'reviewer_id' => $user->id,
        'decision' => null,
        'required_for' => 'publish',
    ]);

    // 2 already-decided — should not be counted
    $workspaceD = Workspace::factory()->approved()->create();
    WorkspaceReviewAssignment::query()->create([
        'workspace_id' => $workspaceD->id,
        'reviewer_type' => $user->getMorphClass(),
        'reviewer_id' => $user->id,
        'decision' => 'approved',
        'required_for' => 'publish',
    ]);

    $workspaceE = Workspace::factory()->inReview()->create();
    WorkspaceReviewAssignment::query()->create([
        'workspace_id' => $workspaceE->id,
        'reviewer_type' => $user->getMorphClass(),
        'reviewer_id' => $user->id,
        'decision' => 'rejected',
        'required_for' => 'publish',
    ]);

    // 1 assignment for a different user — should not be counted
    $workspaceF = Workspace::factory()->inReview()->create();
    WorkspaceReviewAssignment::query()->create([
        'workspace_id' => $workspaceF->id,
        'reviewer_type' => $other->getMorphClass(),
        'reviewer_id' => $other->id,
        'decision' => null,
        'required_for' => 'publish',
    ]);

    $result = BuildWorkspaceActivityAction::run($user);

    expect($result->pendingApprovalsCount)->toBe(3);
});

it('counts stuck publishing-studio created more than threshold days ago', function (): void {
    // 2 open publishing-studio created 10 days ago — stuck
    Workspace::factory()->open()->create()->forceFill([
        'created_at' => now()->subDays(10),
    ])->save();

    Workspace::factory()->open()->create()->forceFill([
        'created_at' => now()->subDays(10),
    ])->save();

    // 1 created yesterday — not stuck
    Workspace::factory()->open()->create()->forceFill([
        'created_at' => now()->subDay(),
    ])->save();

    $user = $this->createUser();
    $result = BuildWorkspaceActivityAction::run($user, 7);

    expect($result->stuckCount)->toBe(2);
});

it('excludes published and abandoned publishing-studio from stuck count', function (): void {
    // Published and abandoned publishing-studio created long ago — must not be counted as stuck
    Workspace::factory()->published()->create()->forceFill([
        'created_at' => now()->subDays(30),
    ])->save();

    Workspace::factory()->abandoned()->create()->forceFill([
        'created_at' => now()->subDays(30),
    ])->save();

    $user = $this->createUser();
    $result = BuildWorkspaceActivityAction::run($user, 7);

    expect($result->stuckCount)->toBe(0);
});

it('returns recent merges in descending published_at order', function (): void {
    $user = $this->createUser();

    // Seed 5 published publishing-studio at distinct times
    $workspace1 = Workspace::factory()->published()->create(['published_at' => now()->subHours(5)]);
    $workspace2 = Workspace::factory()->published()->create(['published_at' => now()->subHours(2)]);
    $workspace3 = Workspace::factory()->published()->create(['published_at' => now()->subHours(10)]);
    $workspace4 = Workspace::factory()->published()->create(['published_at' => now()->subHours(1)]);
    $workspace5 = Workspace::factory()->published()->create(['published_at' => now()->subHours(8)]);

    $result = BuildWorkspaceActivityAction::run($user, 7, 5);
    $ids = $result->recentMerges->toCollection()->pluck('workspaceId')->all();

    expect($ids)->toBe([
        $workspace4->id,
        $workspace2->id,
        $workspace1->id,
        $workspace5->id,
        $workspace3->id,
    ]);
});

it('limits recent merges to mergesLimit', function (): void {
    $user = $this->createUser();

    Workspace::factory()->published()->count(10)->create(['published_at' => now()]);

    $result = BuildWorkspaceActivityAction::run($user, 7, 3);

    expect($result->recentMerges->count())->toBe(3);
});

it('each merge data row includes actor name, page count, duration', function (): void {
    $actor = $this->createUser(['name' => 'Alice Editor']);

    $publishedAt = now();
    $createdAt = now()->subDays(4);

    $workspace = Workspace::factory()->published()->create([
        'published_at' => $publishedAt,
        'created_by' => $actor->id,
    ]);
    $workspace->forceFill(['created_at' => $createdAt])->save();

    // 3 pages in this workspace
    Page::factory()->count(3)->create(['workspace_id' => $workspace->id]);

    $user = $this->createUser();
    $result = BuildWorkspaceActivityAction::run($user, 7, 5);

    $merge = $result->recentMerges->toCollection()->first();

    expect($merge)->not->toBeNull()
        ->and($merge->actorName)->toBe('Alice Editor')
        ->and($merge->pageCount)->toBe(3)
        ->and($merge->durationOpenHours)->toBeGreaterThanOrEqual(4 * 24);
});
