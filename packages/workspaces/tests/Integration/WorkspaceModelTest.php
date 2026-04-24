<?php

declare(strict_types=1);

use Capell\Tests\Fixtures\Models\User;
use Capell\Workspaces\Enums\WorkspaceApprovalActionEnum;
use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Models\Version;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Models\WorkspaceApproval;
use Capell\Workspaces\WorkspaceContext;
use Illuminate\Support\Str;

afterEach(function (): void {
    WorkspaceContext::clear();
});

it('generates a slug and adopts the live base version on creation', function (): void {
    $workspace = Workspace::query()->create([
        'name' => 'Spring Relaunch',
        'description' => 'Coordinated rollout for spring.',
    ]);

    expect($workspace->slug)->toStartWith('spring-relaunch-')
        ->and(strlen($workspace->slug))->toBeGreaterThan(strlen('spring-relaunch-'))
        ->and($workspace->base_version_id)->toBe(Version::liveId())
        ->and($workspace->status)->toBe(WorkspaceStatusEnum::Open)
        ->and($workspace->uuid)->not->toBeEmpty();
});

it('submitForApproval records a Submitted approval and flips status to InReview', function (): void {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->open()->create();

    $workspace->submitForApproval($user, 'Ready for QA review');

    $workspace->refresh();

    expect($workspace->status)->toBe(WorkspaceStatusEnum::InReview)
        ->and($workspace->submitted_at)->not->toBeNull()
        ->and($workspace->approvals()->count())->toBe(1);

    $submission = $workspace->approvals()->first();

    expect($submission)->toBeInstanceOf(WorkspaceApproval::class)
        ->and($submission->action)->toBe(WorkspaceApprovalActionEnum::Submitted)
        ->and($submission->level)->toBe(1)
        ->and($submission->notes)->toBe('Ready for QA review')
        ->and($submission->actionable_id)->toBe($user->id);
});

it('approve at level 1 does not yet flip status when two levels are required', function (): void {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->inReview()->create();

    $workspace->approve($user, level: 1, notes: 'Content looks good');

    $workspace->refresh();

    expect($workspace->status)->toBe(WorkspaceStatusEnum::InReview)
        ->and($workspace->approved_at)->toBeNull()
        ->and($workspace->approvals()->where('action', WorkspaceApprovalActionEnum::Approved->value)->count())
        ->toBe(1);
});

it('approve at level 2 flips the status to Approved and stamps approved_at', function (): void {
    $firstApprover = User::factory()->create();
    $secondApprover = User::factory()->create();
    $workspace = Workspace::factory()->inReview()->create();

    $workspace->approve($firstApprover, level: 1);
    $workspace->approve($secondApprover, level: 2);

    $workspace->refresh();

    expect($workspace->status)->toBe(WorkspaceStatusEnum::Approved)
        ->and($workspace->approved_at)->not->toBeNull()
        ->and($workspace->approvals()->count())->toBe(2);
});

it('honours a custom required_approval_levels setting', function (): void {
    $workspace = Workspace::factory()
        ->inReview()
        ->create(['settings' => ['required_approval_levels' => 1]]);

    $approver = User::factory()->create();

    $workspace->approve($approver, level: 1);

    $workspace->refresh();

    expect($workspace->status)->toBe(WorkspaceStatusEnum::Approved);
});

it('reject returns the workspace to Open and clears submitted_at', function (): void {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->inReview()->create();

    $workspace->reject($user, level: 1, notes: 'Copy needs updating');

    $workspace->refresh();

    expect($workspace->status)->toBe(WorkspaceStatusEnum::Open)
        ->and($workspace->submitted_at)->toBeNull()
        ->and($workspace->approvals()->where('action', WorkspaceApprovalActionEnum::Rejected->value)->count())
        ->toBe(1);
});

it('markAbandoned moves the workspace to the Abandoned status', function (): void {
    $workspace = Workspace::factory()->open()->create();

    $workspace->markAbandoned();

    $workspace->refresh();

    expect($workspace->status)->toBe(WorkspaceStatusEnum::Abandoned);
});

it('isStale is true when the base version is older than the live version', function (): void {
    $newerLive = Version::query()->create([
        'uuid' => (string) Str::uuid(),
        'number' => Version::query()->max('number') + 1,
        'name' => 'New Live',
        'is_live' => false,
        'manifest' => [],
        'published_at' => now(),
    ]);

    Version::query()->where('id', '!=', $newerLive->id)->update(['is_live' => false]);
    $newerLive->is_live = true;
    $newerLive->save();

    $workspace = Workspace::factory()->create([
        'base_version_id' => 1,
    ]);

    expect($workspace->isStale())->toBeTrue();
});

it('isStale is false when the workspace base equals the live version', function (): void {
    $workspace = Workspace::factory()->create([
        'base_version_id' => Version::liveId(),
    ]);

    expect($workspace->isStale())->toBeFalse();
});

it('runInContext sets and restores the current workspace context', function (): void {
    $workspace = Workspace::factory()->create();

    expect(WorkspaceContext::current())->toBeNull();

    $observedInsideCallback = $workspace->runInContext(
        fn (): ?Workspace => WorkspaceContext::current(),
    );

    expect($observedInsideCallback?->id)->toBe($workspace->id)
        ->and(WorkspaceContext::current())->toBeNull();
});

it('restores context when runInContext callback throws', function (): void {
    $workspace = Workspace::factory()->create();

    try {
        $workspace->runInContext(function (): void {
            throw new RuntimeException('inside');
        });
    } catch (RuntimeException) {
        // swallowed — we only care about context cleanup
    }

    expect(WorkspaceContext::current())->toBeNull();
});

it('scopes filter by status', function (): void {
    Workspace::factory()->open()->create();
    Workspace::factory()->inReview()->create();
    Workspace::factory()->approved()->create();
    Workspace::factory()->published()->create();

    expect(Workspace::query()->open()->count())->toBe(1)
        ->and(Workspace::query()->inReview()->count())->toBe(1)
        ->and(Workspace::query()->approved()->count())->toBe(1)
        ->and(Workspace::query()->published()->count())->toBe(1)
        ->and(Workspace::query()->active()->count())->toBe(3);
});
