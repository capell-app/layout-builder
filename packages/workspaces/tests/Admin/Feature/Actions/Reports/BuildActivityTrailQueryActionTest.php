<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Workspaces\Actions\Reports\BuildActivityTrailQueryAction;
use Spatie\Activitylog\Models\Activity;

describe('BuildActivityTrailQueryAction', function (): void {
    it('returns activity records for editorial models only', function (): void {
        // Arrange — create models without triggering auto-logging via the LogsActivity trait
        $page = activity()->withoutLogs(fn (): Page => Page::factory()->create());
        $site = activity()->withoutLogs(fn (): Site => Site::factory()->create());

        // Create activity logs for editorial models with explicit event values
        activity()->causedBy(auth()->user())->performedOn($page)->event('created')->log('created');
        activity()->causedBy(auth()->user())->performedOn($site)->event('updated')->log('updated');

        // Act
        $query = BuildActivityTrailQueryAction::run();
        $activities = $query->get();

        // Assert — morph map stores aliases, not full class names
        expect($activities)->toHaveCount(2);
        expect($activities->pluck('subject_type')->unique())->toContain(
            (new Page)->getMorphClass(),
            (new Site)->getMorphClass(),
        );
    });

    it('filters by last 30 days by default', function (): void {
        // Arrange
        $page = Page::factory()->create();
        activity()->causedBy(auth()->user())->performedOn($page)->log('created');

        // Create an old activity record (>30 days)
        activity()->performedOn($page)->log('old-event');
        Activity::query()->latest()->first()?->forceFill(['created_at' => now()->subDays(31)])->saveQuietly();

        // Act
        $query = BuildActivityTrailQueryAction::run();
        $activities = $query->get();

        // Assert - should only have the recent one
        expect($activities)->toHaveCount(1);
    });

    it('excludes system events', function (): void {
        // Arrange — create page without auto-logging to control the event set precisely
        $page = activity()->withoutLogs(fn (): Page => Page::factory()->create());

        // Create editorial events with explicit event values
        activity()->causedBy(auth()->user())->performedOn($page)->event('created')->log('created');
        activity()->causedBy(auth()->user())->performedOn($page)->event('updated')->log('updated');

        // Create a system event (not in the allowed list)
        activity()->causedBy(auth()->user())->performedOn($page)->event('viewed')->log('viewed');

        // Act
        $query = BuildActivityTrailQueryAction::run();
        $activities = $query->get();

        // Assert - should only have created and updated
        expect($activities)->toHaveCount(2);
        expect($activities->pluck('event')->unique())->toContain('created', 'updated')
            ->and($activities->pluck('event'))->not->toContain('viewed');
    });
});
