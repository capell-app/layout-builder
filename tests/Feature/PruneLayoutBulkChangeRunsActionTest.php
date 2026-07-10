<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\PruneLayoutBulkChangeRunsAction;
use Capell\LayoutBuilder\Enums\LayoutBulkChangeRunStatus;
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;

it('prunes terminal bulk-change runs without deleting active work', function (): void {
    $stale = LayoutBulkChangeRun::query()->create([
        'status' => LayoutBulkChangeRunStatus::Applied,
        'criteria' => [],
        'operation' => [],
    ]);
    $stale->forceFill(['updated_at' => now()->subDays(91)])->saveQuietly();
    $active = LayoutBulkChangeRun::query()->create([
        'status' => LayoutBulkChangeRunStatus::Applying,
        'criteria' => [],
        'operation' => [],
    ]);
    $active->forceFill(['updated_at' => now()->subDays(91)])->saveQuietly();

    expect(PruneLayoutBulkChangeRunsAction::run(90))->toBe(1)
        ->and(LayoutBulkChangeRun::query()->find($stale->getKey()))->toBeNull()
        ->and(LayoutBulkChangeRun::query()->find($active->getKey()))->not->toBeNull();
});
