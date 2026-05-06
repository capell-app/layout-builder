<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Actions;

use Capell\MigrationAssistant\Data\ImportValidationSummary;
use Capell\MigrationAssistant\Data\PageReviewRow;
use Capell\MigrationAssistant\Data\RelationResolveRow;
use Capell\MigrationAssistant\Services\Import\PackageReadResult;
use Capell\MigrationAssistant\Services\Import\ResolutionMap;
use Illuminate\Support\Facades\Date;
use JsonException;

/**
 * Re-runs the resolver outputs against the user's decisions to produce a
 * dry-run summary for the H2.1 wizard Validate step (§6.4). The summary is
 * stored on `import_sessions.validation_results` and drives the final
 * confirmation gate before dispatch.
 *
 * Pure — no DB writes. Collisions for pages are derived from the already
 * computed {@see PageReviewRow} set so no extra queries fire here.
 */
final readonly class BuildImportValidationSummaryAction
{
    /**
     * Alternatives below this confidence trigger a warning so the user
     * knows the resolver wasn't sure about the top match either.
     */
    /** @var float */
    private const LOW_CONFIDENCE_THRESHOLD = 0.5;

    public function __construct(private BuildPageReviewRows $buildPageReviewRows = new BuildPageReviewRows) {}

    /**
     * @param  array<string, array{action: string, target_id?: int|string|null, notes?: string}>  $pageDecisions
     * @param  array<string, array{action: string, target_id?: int|string|null, notes?: string}>  $relationDecisions
     */
    public function run(
        PackageReadResult $package,
        ResolutionMap $map,
        array $pageDecisions,
        array $relationDecisions,
    ): ImportValidationSummary {
        $reviewRows = $this->buildPageReviewRows->run($package, $map);

        [$pagesBuckets, $pageBlockers, $pageWarnings] = $this->summarizePages($reviewRows, $pageDecisions);
        [$relationsBuckets, $relationBlockers, $relationWarnings] = $this->summarizeRelations($map, $relationDecisions);
        $mediaBuckets = $this->summarizeMedia($package, $map);

        $warnings = array_values(array_filter(
            array_merge($pageWarnings, $relationWarnings),
            static fn (string $entry): bool => $entry !== '',
        ));

        return new ImportValidationSummary(
            pages: $pagesBuckets,
            relations: $relationsBuckets,
            media: $mediaBuckets,
            blockingErrors: array_values(array_merge($pageBlockers, $relationBlockers)),
            warnings: $warnings,
            generatedAt: Date::now()->toIso8601String(),
        );
    }

    /**
     * @param  list<PageReviewRow>  $reviewRows
     * @param  array<string, array{action: string, target_id?: int|string|null, notes?: string}>  $pageDecisions
     * @return array{0: array{create: int, update: int, skip: int}, 1: list<string>, 2: list<string>}
     */
    private function summarizePages(array $reviewRows, array $pageDecisions): array
    {
        $buckets = ['create' => 0, 'update' => 0, 'skip' => 0];
        $blockingErrors = [];
        $warnings = [];

        foreach ($reviewRows as $row) {
            $decision = $pageDecisions[$row->uuid] ?? ['action' => $row->suggestedAction];
            $action = is_string($decision['action'] ?? null)
                ? $decision['action']
                : $row->suggestedAction;

            if (isset($buckets[$action])) {
                $buckets[$action]++;
            }

            if ($action === PageReviewRow::ACTION_CREATE
                && $row->collisionState === PageReviewRow::COLLISION_URL_WORKSPACE) {
                $blockingErrors[] = sprintf(
                    'Page %s cannot be created — its URL is already claimed by another workspace.',
                    $row->title ?? $row->uuid,
                );
            }

            if ($action === PageReviewRow::ACTION_CREATE
                && $row->collisionState === PageReviewRow::COLLISION_URL_LIVE) {
                $warnings[] = sprintf(
                    'Page %s is set to "create" but its URL already exists on a live page.',
                    $row->title ?? $row->uuid,
                );
            }

        }

        return [$buckets, $blockingErrors, $warnings];
    }

    /**
     * @param  array<string, array{action: string, target_id?: int|string|null, notes?: string}>  $relationDecisions
     * @return array{0: array{match: int, create: int, clone: int, update: int, skip: int}, 1: list<string>, 2: list<string>}
     */
    private function summarizeRelations(ResolutionMap $map, array $relationDecisions): array
    {
        $buckets = ['match' => 0, 'create' => 0, 'clone' => 0, 'update' => 0, 'skip' => 0];
        $blockingErrors = [];
        $warnings = [];

        foreach ($map->resolved as $ref => $resolution) {
            $decision = $relationDecisions[$ref] ?? ['action' => RelationResolveRow::ACTION_USE_EXISTING];
            $action = is_string($decision['action'] ?? null)
                ? $decision['action']
                : RelationResolveRow::ACTION_USE_EXISTING;

            switch ($action) {
                case RelationResolveRow::ACTION_USE_EXISTING:
                    $buckets['match']++;
                    break;
                case RelationResolveRow::ACTION_CREATE_NEW:
                    $buckets['create']++;
                    break;
                case RelationResolveRow::ACTION_CLONE_IMPORTED:
                    $buckets['clone']++;
                    break;
                case RelationResolveRow::ACTION_UPDATE_EXISTING:
                    $buckets['update']++;
                    $targetId = $decision['target_id'] ?? null;
                    if ($targetId === null || $targetId === '') {
                        $blockingErrors[] = sprintf(
                            'Relation %s is set to "update existing" but has no target id.',
                            $ref,
                        );
                    }

                    break;
                case RelationResolveRow::ACTION_SKIP:
                    $buckets['skip']++;
                    break;
            }

            foreach ($resolution->alternatives as $alternative) {
                if ($alternative->confidence < self::LOW_CONFIDENCE_THRESHOLD) {
                    $warnings[] = sprintf(
                        'Relation %s has a low-confidence alternative (#%s at %.0f%%).',
                        $ref,
                        (string) $alternative->localId,
                        $alternative->confidence * 100,
                    );
                }
            }
        }

        foreach ($map->unresolved as $ref) {
            $decision = $relationDecisions[$ref] ?? null;
            $action = is_array($decision) && is_string($decision['action'] ?? null)
                ? $decision['action']
                : null;

            $allowed = [
                RelationResolveRow::ACTION_CREATE_NEW,
                RelationResolveRow::ACTION_CLONE_IMPORTED,
                RelationResolveRow::ACTION_SKIP,
            ];

            if ($action === null || ! in_array($action, $allowed, true)) {
                $blockingErrors[] = sprintf(
                    'Relation %s is unresolved — pick create/clone/skip before dispatching.',
                    $ref,
                );

                continue;
            }

            switch ($action) {
                case RelationResolveRow::ACTION_CREATE_NEW:
                    $buckets['create']++;
                    break;
                case RelationResolveRow::ACTION_CLONE_IMPORTED:
                    $buckets['clone']++;
                    break;
                case RelationResolveRow::ACTION_SKIP:
                    $buckets['skip']++;
                    break;
            }
        }

        return [$buckets, $blockingErrors, $warnings];
    }

    /**
     * @return array{import: int, reuse: int}
     */
    private function summarizeMedia(PackageReadResult $package, ResolutionMap $map): array
    {
        $buckets = ['import' => 0, 'reuse' => 0];

        foreach ($package->payload as $entryPath => $contents) {
            if (! str_starts_with($entryPath, 'relations/media/')) {
                continue;
            }

            if (! str_ends_with($entryPath, '.json')) {
                continue;
            }

            try {
                /** @var array<string, mixed> $descriptor */
                $descriptor = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                continue;
            }

            $ref = is_string($descriptor['ref'] ?? null) ? $descriptor['ref'] : null;
            if ($ref === null) {
                $buckets['import']++;

                continue;
            }

            if (isset($map->resolved[$ref])) {
                $buckets['reuse']++;

                continue;
            }

            $buckets['import']++;
        }

        return $buckets;
    }
}
