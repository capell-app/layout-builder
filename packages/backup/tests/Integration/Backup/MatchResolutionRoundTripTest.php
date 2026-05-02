<?php

declare(strict_types=1);

use Capell\Backup\Jobs\ExecuteImportPlanJob;
use Capell\Backup\Models\ImportSession;
use Capell\Backup\Services\Import\ResolutionMap;
use Capell\Backup\Services\Import\Resolvers\MatchResolution;

it('round-trips reason and alternatives through ResolutionMap::toArray and hydration', function (): void {
    $alternative = new MatchResolution(
        localId: 2,
        strategy: 'fingerprint',
        confidence: 0.7,
        reason: 'schema fingerprint match',
    );

    $primary = new MatchResolution(
        localId: 1,
        strategy: 'key',
        confidence: 1.0,
        reason: 'exact key match on "home"',
        alternatives: [$alternative],
    );

    $map = new ResolutionMap(
        resolved: ['layout:99' => $primary],
        unresolved: ['type:50'],
    );

    $encoded = $map->toArray();

    expect($encoded['resolved']['layout:99']['reason'])->toBe('exact key match on "home"')
        ->and($encoded['resolved']['layout:99']['alternatives'])->toHaveCount(1)
        ->and($encoded['resolved']['layout:99']['alternatives'][0]['strategy'])->toBe('fingerprint');

    $session = new ImportSession;
    $session->resolution_map = $encoded;

    $job = new ExecuteImportPlanJob(importSessionId: 0);
    $hydrator = new ReflectionMethod(ExecuteImportPlanJob::class, 'hydrateResolutionMap');
    /** @var ResolutionMap $hydrated */
    $hydrated = $hydrator->invoke($job, $session);

    expect($hydrated->resolved['layout:99']->reason)->toBe('exact key match on "home"')
        ->and($hydrated->resolved['layout:99']->alternatives)->toHaveCount(1)
        ->and($hydrated->resolved['layout:99']->alternatives[0]->localId)->toBe(2)
        ->and($hydrated->resolved['layout:99']->alternatives[0]->strategy)->toBe('fingerprint')
        ->and($hydrated->unresolved)->toBe(['type:50']);
});
