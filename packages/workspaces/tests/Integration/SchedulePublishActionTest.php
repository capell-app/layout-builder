<?php

declare(strict_types=1);

use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Enums\WorkspaceTransitionEnum;
use Capell\Workspaces\Events\WorkspaceStateChanged;
use Capell\Workspaces\Exceptions\InvalidScheduleException;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\SchedulePublishAction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('schedules an approved workspace for a future timestamp', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 09:00:00', 'UTC'));

    $workspace = Workspace::factory()->approved()->create();
    $scheduledFor = CarbonImmutable::parse('2026-05-01 18:00:00', 'UTC');

    Event::fake([WorkspaceStateChanged::class]);

    $updated = (new SchedulePublishAction)->schedule($workspace, $scheduledFor);

    expect($updated->status)->toBe(WorkspaceStatusEnum::Scheduled)
        ->and($updated->publish_at?->equalTo($scheduledFor))->toBeTrue();

    Event::assertDispatched(WorkspaceStateChanged::class, fn (WorkspaceStateChanged $event): bool => $event->transition === WorkspaceTransitionEnum::Scheduled->value
        && $event->newStatus === WorkspaceStatusEnum::Scheduled);
});

it('rejects scheduling when the workspace is not approved', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 09:00:00', 'UTC'));

    $workspace = Workspace::factory()->open()->create();

    (new SchedulePublishAction)->schedule($workspace, CarbonImmutable::parse('2026-05-02 09:00:00', 'UTC'));
})->throws(InvalidScheduleException::class);

it('rejects a publish_at in the past', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 09:00:00', 'UTC'));

    $workspace = Workspace::factory()->approved()->create();

    (new SchedulePublishAction)->schedule($workspace, CarbonImmutable::parse('2026-04-30 09:00:00', 'UTC'));
})->throws(InvalidScheduleException::class);

it('allows rescheduling a workspace that is already scheduled', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 09:00:00', 'UTC'));

    $workspace = Workspace::factory()->scheduled(publishAt: '2026-05-01 18:00:00')->create();
    $rescheduledFor = CarbonImmutable::parse('2026-05-02 18:00:00', 'UTC');

    $updated = (new SchedulePublishAction)->schedule($workspace, $rescheduledFor);

    expect($updated->publish_at?->equalTo($rescheduledFor))->toBeTrue();
});

it('unschedules a scheduled workspace back to approved', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 09:00:00', 'UTC'));

    $workspace = Workspace::factory()->scheduled(publishAt: '2026-05-02 09:00:00')->create();

    Event::fake([WorkspaceStateChanged::class]);

    $updated = (new SchedulePublishAction)->unschedule($workspace);

    expect($updated->status)->toBe(WorkspaceStatusEnum::Approved)
        ->and($updated->publish_at)->toBeNull();

    Event::assertDispatched(WorkspaceStateChanged::class, fn (WorkspaceStateChanged $event): bool => $event->transition === WorkspaceTransitionEnum::Unscheduled->value);
});
