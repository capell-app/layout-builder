<?php

declare(strict_types=1);

use Capell\MigrationAssistant\Actions\BuildRelationResolveRowsAction;
use Capell\MigrationAssistant\Data\RelationResolveRow;
use Capell\MigrationAssistant\Services\Import\ResolutionMap;
use Capell\MigrationAssistant\Services\Import\Resolvers\MatchResolution;

it('builds sorted relation rows for resolved and unresolved references', function (): void {
    $resolutionMap = new ResolutionMap(
        resolved: [
            'site:3' => new MatchResolution(
                localId: 103,
                strategy: 'key',
                confidence: 0.92,
                reason: 'Matched by site key.',
                alternatives: [
                    new MatchResolution(localId: 203, strategy: 'fingerprint', confidence: 0.68, reason: 'Similar domain.'),
                ],
                warnings: ['Domain differs from export.'],
            ),
            'media:2' => new MatchResolution(localId: 202, strategy: 'checksum'),
        ],
        unresolved: ['layout:1', 'custom:9'],
    );

    $rows = BuildRelationResolveRowsAction::run($resolutionMap);

    expect(array_map(static fn (RelationResolveRow $row): string => $row->ref, $rows))
        ->toBe(['custom:9', 'layout:1', 'media:2', 'site:3']);

    $mediaRow = $rows[2];
    expect($mediaRow->group)->toBe(RelationResolveRow::GROUP_MEDIA)
        ->and($mediaRow->topMatch)->toBe([
            'local_id' => 202,
            'strategy' => 'checksum',
            'confidence' => 1.0,
            'reason' => '',
        ])
        ->and($mediaRow->suggestedAction)->toBe(RelationResolveRow::ACTION_USE_EXISTING);

    $siteRow = $rows[3];
    expect($siteRow->group)->toBe(RelationResolveRow::GROUP_SITES)
        ->and($siteRow->alternatives)->toBe([
            [
                'local_id' => 203,
                'strategy' => 'fingerprint',
                'confidence' => 0.68,
                'reason' => 'Similar domain.',
            ],
        ])
        ->and($siteRow->warnings)->toBe(['Domain differs from export.']);
});

it('marks unresolved relation rows for creation while preserving unknown groups', function (): void {
    $resolutionMap = new ResolutionMap(
        resolved: [],
        unresolved: ['type:4', 'bespoke_without_colon'],
    );

    $rows = BuildRelationResolveRowsAction::run($resolutionMap);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]->group)->toBe('bespoke_without_colon')
        ->and($rows[0]->topMatch)->toBeNull()
        ->and($rows[0]->suggestedAction)->toBe(RelationResolveRow::ACTION_CREATE_NEW)
        ->and($rows[1]->group)->toBe(RelationResolveRow::GROUP_TYPES)
        ->and($rows[1]->topMatch)->toBeNull()
        ->and($rows[1]->alternatives)->toBe([])
        ->and($rows[1]->suggestedAction)->toBe(RelationResolveRow::ACTION_CREATE_NEW);
});
