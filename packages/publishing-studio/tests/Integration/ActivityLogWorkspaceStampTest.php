<?php

declare(strict_types=1);

use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\WorkspaceContext;
use Spatie\Activitylog\Models\Activity;

afterEach(function (): void {
    WorkspaceContext::clear();
});

it('stamps the active workspace id on activitylog entries', function (): void {
    $workspace = Workspace::factory()->open()->create();

    WorkspaceContext::set($workspace);

    $workspace->name = 'Renamed inside workspace context';
    $workspace->save();

    $activity = Activity::query()->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties?->get('workspace_id'))->toBe($workspace->id);
});

it('does not stamp workspace id when no workspace is active', function (): void {
    $workspace = Workspace::factory()->open()->create();

    WorkspaceContext::clear();

    $workspace->name = 'Renamed without workspace';
    $workspace->save();

    $activity = Activity::query()->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties?->get('workspace_id'))->toBeNull();
});
