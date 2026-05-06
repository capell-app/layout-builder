<?php

declare(strict_types=1);

use Capell\PublishingStudio\Actions\SetWorkspaceSchedulerMetadataAction;
use Capell\PublishingStudio\Models\Workspace;
use Carbon\CarbonImmutable;

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('sets workspace scheduler metadata', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 09:00:00', 'UTC'));

    $workspace = Workspace::factory()->approved()->create();
    $unpublishAt = CarbonImmutable::parse('2026-05-10 17:00:00', 'UTC');
    $embargoUntil = CarbonImmutable::parse('2026-05-03 09:00:00', 'UTC');
    $reviewReminderAt = CarbonImmutable::parse('2026-05-02 12:00:00', 'UTC');

    $updated = SetWorkspaceSchedulerMetadataAction::run(
        $workspace,
        [
            'unpublish_at' => $unpublishAt,
            'embargo_until' => $embargoUntil,
            'review_reminder_at' => $reviewReminderAt,
        ],
    );

    expect($updated->unpublish_at?->equalTo($unpublishAt))->toBeTrue()
        ->and($updated->embargo_until?->equalTo($embargoUntil))->toBeTrue()
        ->and($updated->review_reminder_at?->equalTo($reviewReminderAt))->toBeTrue();
});

it('clears only the scheduler metadata fields passed as null', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 09:00:00', 'UTC'));

    $workspace = Workspace::factory()->approved()->create([
        'unpublish_at' => CarbonImmutable::parse('2026-05-10 17:00:00', 'UTC'),
        'embargo_until' => CarbonImmutable::parse('2026-05-03 09:00:00', 'UTC'),
        'review_reminder_at' => CarbonImmutable::parse('2026-05-02 12:00:00', 'UTC'),
    ]);

    $updated = SetWorkspaceSchedulerMetadataAction::run(
        $workspace,
        [
            'unpublish_at' => null,
            'embargo_until' => null,
        ],
    );

    expect($updated->unpublish_at)->toBeNull()
        ->and($updated->embargo_until)->toBeNull()
        ->and($updated->review_reminder_at?->equalTo(CarbonImmutable::parse('2026-05-02 12:00:00', 'UTC')))->toBeTrue();
});
