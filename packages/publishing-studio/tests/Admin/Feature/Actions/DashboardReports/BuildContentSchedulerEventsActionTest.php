<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\PublishingStudio\Actions\DashboardReports\BuildContentSchedulerEventsAction;
use Capell\PublishingStudio\Data\SchedulerEventData;
use Capell\PublishingStudio\Enums\SchedulerEventTypeEnum;
use Capell\PublishingStudio\Models\Workspace;
use Carbon\CarbonImmutable;

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

test('returns calendar-ready scheduler events for pages and publishing-studio', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 09:00:00', 'UTC'));

    Page::factory()->create([
        'name' => 'Spring launch page',
        'visible_from' => CarbonImmutable::parse('2026-05-02 10:00:00', 'UTC'),
    ]);
    Page::factory()->create([
        'name' => 'Campaign landing page',
        'visible_from' => CarbonImmutable::parse('2026-04-01 10:00:00', 'UTC'),
        'visible_until' => CarbonImmutable::parse('2026-05-03 17:00:00', 'UTC'),
    ]);
    Workspace::factory()->scheduled('2026-05-04 09:00:00')->create([
        'name' => 'Summer campaign',
        'unpublish_at' => CarbonImmutable::parse('2026-05-20 17:00:00', 'UTC'),
        'embargo_until' => CarbonImmutable::parse('2026-05-03 09:00:00', 'UTC'),
        'review_reminder_at' => CarbonImmutable::parse('2026-05-02 12:00:00', 'UTC'),
    ]);

    $events = BuildContentSchedulerEventsAction::run();

    expect($events)->toHaveCount(6)
        ->and($events->pluck('eventType')->map(fn (SchedulerEventTypeEnum $eventType): string => $eventType->value)->all())
        ->toBe([
            SchedulerEventTypeEnum::Publish->value,
            SchedulerEventTypeEnum::ReviewReminder->value,
            SchedulerEventTypeEnum::Embargo->value,
            SchedulerEventTypeEnum::Unpublish->value,
            SchedulerEventTypeEnum::Publish->value,
            SchedulerEventTypeEnum::Unpublish->value,
        ])
        ->and($events->first(fn (SchedulerEventData $event): bool => $event->title === 'Summer campaign' && $event->eventType === SchedulerEventTypeEnum::Unpublish)?->description)
        ->toContain('does not automatically unpublish')
        ->and($events->first()->title)->toBe('Spring launch page');
});

test('filters scheduler events by type and source', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 09:00:00', 'UTC'));

    Page::factory()->create([
        'name' => 'Page publish',
        'visible_from' => CarbonImmutable::parse('2026-05-02 10:00:00', 'UTC'),
    ]);
    Workspace::factory()->scheduled('2026-05-04 09:00:00')->create([
        'name' => 'Workspace publish',
    ]);

    $events = BuildContentSchedulerEventsAction::run(
        eventType: SchedulerEventTypeEnum::Publish,
        sourceType: 'workspace',
    );

    expect($events)->toHaveCount(1)
        ->and($events->first()->sourceType)->toBe('workspace')
        ->and($events->first()->title)->toBe('Workspace publish');
});
