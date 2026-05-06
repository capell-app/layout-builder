<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Data\Imports;

use Spatie\LaravelData\Data;

final class PageImportDecisionData extends Data
{
    /**
     * @param  list<array<string, mixed>>  $reviewRows
     * @param  array<string, array{action: string, notes?: string}>  $pageDecisions
     * @param  list<array<string, mixed>>  $resolveRows
     * @param  array<string, array{action: string, target_id?: int|string|null, notes?: string}>  $relationDecisions
     */
    public function __construct(
        public readonly ?int $sessionId,
        public readonly array $reviewRows,
        public readonly array $pageDecisions,
        public readonly array $resolveRows,
        public readonly array $relationDecisions,
        public readonly bool $canUpdateSharedRelations,
    ) {}

    public function shouldSkipResolveStep(): bool
    {
        if ($this->resolveRows === []) {
            return true;
        }

        foreach ($this->resolveRows as $row) {
            if (($row['top_match'] ?? null) === null) {
                return false;
            }

            $alternatives = $row['alternatives'] ?? [];
            if (is_array($alternatives) && $alternatives !== []) {
                return false;
            }
        }

        return true;
    }
}
