<?php

declare(strict_types=1);

namespace Capell\Migrator\Actions;

use Capell\Migrator\Data\RelationResolveRow;
use Capell\Migrator\Services\Import\ResolutionMap;
use Capell\Migrator\Services\Import\Resolvers\MatchResolution;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Shape the ResolutionMap into rows for the wizard "Resolve relations"
 * step (§6.3b). One row per shared-relation ref, grouped by kind.
 *
 * The row exposes the top match (if any) plus lower-confidence
 * alternatives so the UI can surface them as a secondary picker.
 */
class BuildRelationResolveRowsAction
{
    use AsAction;

    /**
     * Map ref-prefix (e.g. "layout") to the relation-registry group key
     * used by resolvers (e.g. "layouts").
     *
     * @var array<string, string>
     */
    private const PREFIX_TO_GROUP = [
        'layout' => RelationResolveRow::GROUP_LAYOUTS,
        'type' => RelationResolveRow::GROUP_TYPES,
        'site' => RelationResolveRow::GROUP_SITES,
        'media' => RelationResolveRow::GROUP_MEDIA,
    ];

    /**
     * @return list<RelationResolveRow>
     */
    public function handle(ResolutionMap $resolutionMap): array
    {
        $rows = [];

        foreach ($resolutionMap->resolved as $ref => $resolution) {
            $group = $this->groupForRef($ref);

            $alternatives = array_map(
                static fn (MatchResolution $alternative): array => [
                    'local_id' => $alternative->localId,
                    'strategy' => $alternative->strategy,
                    'confidence' => $alternative->confidence,
                    'reason' => $alternative->reason,
                ],
                $resolution->alternatives,
            );

            $rows[] = new RelationResolveRow(
                group: $group,
                ref: $ref,
                topMatch: [
                    'local_id' => $resolution->localId,
                    'strategy' => $resolution->strategy,
                    'confidence' => $resolution->confidence,
                    'reason' => $resolution->reason,
                ],
                alternatives: $alternatives,
                warnings: $resolution->warnings,
                suggestedAction: RelationResolveRow::ACTION_USE_EXISTING,
            );
        }

        foreach ($resolutionMap->unresolved as $ref) {
            $rows[] = new RelationResolveRow(
                group: $this->groupForRef($ref),
                ref: $ref,
                topMatch: null,
                alternatives: [],
                warnings: [],
                suggestedAction: RelationResolveRow::ACTION_CREATE_NEW,
            );
        }

        usort(
            $rows,
            static fn (RelationResolveRow $left, RelationResolveRow $right): int => [$left->group, $left->ref] <=> [$right->group, $right->ref],
        );

        return $rows;
    }

    /**
     * Derives the registry group from a ref string like "layout:7".
     * Unknown prefixes fall through as-is so the UI still renders them
     * under a literal heading rather than dropping rows silently.
     */
    private function groupForRef(string $ref): string
    {
        $colonPosition = strpos($ref, ':');
        $prefix = $colonPosition === false ? $ref : substr($ref, 0, $colonPosition);

        return self::PREFIX_TO_GROUP[$prefix] ?? $prefix;
    }
}
