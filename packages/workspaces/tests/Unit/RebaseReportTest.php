<?php

declare(strict_types=1);

use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\RebaseReport;

function makeWorkspaceForReport(?int $baseVersionId): Workspace
{
    $workspace = new Workspace;
    $workspace->forceFill([
        'id' => 1,
        'base_version_id' => $baseVersionId,
    ]);

    return $workspace;
}

it('reports no conflicts when the map is empty', function (): void {
    $report = new RebaseReport(makeWorkspaceForReport(1), 1, []);

    expect($report->hasConflicts())->toBeFalse()
        ->and($report->conflictCount())->toBe(0)
        ->and($report->conflicts())->toBe([]);
});

it('addConflict accumulates uuids without duplicates', function (): void {
    $report = new RebaseReport(makeWorkspaceForReport(1), 2, []);

    $report->addConflict('App\\Models\\Page', 'uuid-one');
    $report->addConflict('App\\Models\\Page', 'uuid-two');
    $report->addConflict('App\\Models\\Page', 'uuid-one');
    $report->addConflict('App\\Models\\Navigation', 'nav-uuid');

    expect($report->hasConflicts())->toBeTrue()
        ->and($report->conflictCount())->toBe(3)
        ->and($report->conflicts())->toBe([
            'App\\Models\\Page' => ['uuid-one', 'uuid-two'],
            'App\\Models\\Navigation' => ['nav-uuid'],
        ]);
});

it('is stale when the workspace base is behind the live version', function (): void {
    $report = new RebaseReport(makeWorkspaceForReport(1), 5, []);

    expect($report->isStale())->toBeTrue();
});

it('is not stale when the workspace is already on the live version', function (): void {
    $report = new RebaseReport(makeWorkspaceForReport(5), 5, []);

    expect($report->isStale())->toBeFalse();
});

it('is not stale when there is no live version yet', function (): void {
    $report = new RebaseReport(makeWorkspaceForReport(3), null, []);

    expect($report->isStale())->toBeFalse();
});

it('is not stale when the workspace has no base version', function (): void {
    $report = new RebaseReport(makeWorkspaceForReport(null), 2, []);

    expect($report->isStale())->toBeFalse();
});
