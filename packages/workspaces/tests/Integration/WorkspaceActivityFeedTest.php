<?php

declare(strict_types=1);

use Capell\Workspaces\Activity\WorkspaceActivityFeed;
use Capell\Workspaces\Models\Workspace;
use Spatie\Activitylog\Models\Activity;

it('returns recent workspace activity rows ordered newest first', function (): void {
    $firstWorkspace = Workspace::factory()->create(['name' => 'first']);
    $secondWorkspace = Workspace::factory()->create(['name' => 'second']);

    Activity::query()->create([
        'log_name' => 'workspace',
        'description' => 'submitted',
        'subject_type' => (new Workspace)->getMorphClass(),
        'subject_id' => $firstWorkspace->id,
        'event' => 'submitted',
    ]);

    Activity::query()->create([
        'log_name' => 'workspace',
        'description' => 'approved',
        'subject_type' => (new Workspace)->getMorphClass(),
        'subject_id' => $secondWorkspace->id,
        'event' => 'approved',
    ]);

    $feed = (new WorkspaceActivityFeed)->recent()
        ->whereIn('description', ['submitted', 'approved'])
        ->values();

    expect($feed)->toHaveCount(2)
        ->and($feed->first()->description)->toBe('approved')
        ->and($feed->first()->workspaceName)->toBe('second')
        ->and($feed->last()->workspaceName)->toBe('first');
});

it('honours the limit parameter', function (): void {
    $workspace = Workspace::factory()->create();

    for ($index = 0; $index < 5; $index++) {
        Activity::query()->create([
            'log_name' => 'workspace',
            'description' => 'event-' . $index,
            'subject_type' => (new Workspace)->getMorphClass(),
            'subject_id' => $workspace->id,
        ]);
    }

    $feed = (new WorkspaceActivityFeed)->recent(limit: 3);

    expect($feed)->toHaveCount(3);
});
