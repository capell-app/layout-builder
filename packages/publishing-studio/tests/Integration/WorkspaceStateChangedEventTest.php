<?php

declare(strict_types=1);

use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Enums\WorkspaceTransitionEnum;
use Capell\PublishingStudio\Events\WorkspaceStateChanged;
use Capell\PublishingStudio\Models\Workspace;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake([WorkspaceStateChanged::class]);
});

it('dispatches WorkspaceStateChanged on submit', function (): void {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->open()->create();

    $workspace->submitForApproval($user, 'Please review');

    Event::assertDispatched(
        WorkspaceStateChanged::class,
        fn (WorkspaceStateChanged $event): bool => $event->workspace->is($workspace)
            && $event->previousStatus === WorkspaceStatusEnum::Open
            && $event->newStatus === WorkspaceStatusEnum::InReview
            && $event->transition === WorkspaceTransitionEnum::Submitted->value
            && $event->notes === 'Please review',
    );
});

it('dispatches WorkspaceStateChanged on approve once threshold is met', function (): void {
    $firstApprover = User::factory()->create();
    $secondApprover = User::factory()->create();
    $workspace = Workspace::factory()->inReview()->create();

    $workspace->approve($firstApprover, level: 1);
    $workspace->approve($secondApprover, level: 2);

    Event::assertDispatched(
        WorkspaceStateChanged::class,
        fn (WorkspaceStateChanged $event): bool => $event->transition === WorkspaceTransitionEnum::Approved->value
            && $event->newStatus === WorkspaceStatusEnum::Approved,
    );

    Event::assertDispatchedTimes(WorkspaceStateChanged::class, 1);
});

it('dispatches WorkspaceStateChanged on reject', function (): void {
    $reviewer = User::factory()->create();
    $workspace = Workspace::factory()->inReview()->create();

    $workspace->reject($reviewer, level: 1, notes: 'Needs more work');

    Event::assertDispatched(
        WorkspaceStateChanged::class,
        fn (WorkspaceStateChanged $event): bool => $event->transition === WorkspaceTransitionEnum::Rejected->value
            && $event->newStatus === WorkspaceStatusEnum::Open
            && $event->notes === 'Needs more work',
    );
});

it('dispatches WorkspaceStateChanged on abandon', function (): void {
    $workspace = Workspace::factory()->open()->create();

    $workspace->markAbandoned();

    Event::assertDispatched(
        WorkspaceStateChanged::class,
        fn (WorkspaceStateChanged $event): bool => $event->transition === WorkspaceTransitionEnum::Abandoned->value
            && $event->newStatus === WorkspaceStatusEnum::Abandoned
            && ! $event->actor instanceof Authenticatable,
    );
});
