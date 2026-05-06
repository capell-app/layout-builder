<?php

declare(strict_types=1);

namespace Capell\PublishingStudio;

use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Database\Eloquent\Model;

/**
 * Structured result of a {@see Rebaser::analyse()} call. Collects the set of
 * records whose live copy has drifted from what this workspace forked at.
 */
final class RebaseReport
{
    /**
     * @param  array<class-string<Model>, array<int, string>>  $conflicts
     *                                                                     Map of model class => list of uuids that conflict.
     */
    public function __construct(
        public readonly Workspace $workspace,
        public readonly ?int $currentLiveVersionId,
        private array $conflicts,
    ) {}

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function addConflict(string $modelClass, string $uuid): void
    {
        $this->conflicts[$modelClass] ??= [];
        if (! in_array($uuid, $this->conflicts[$modelClass], true)) {
            $this->conflicts[$modelClass][] = $uuid;
        }
    }

    /**
     * @return array<class-string<Model>, array<int, string>>
     */
    public function conflicts(): array
    {
        return $this->conflicts;
    }

    public function hasConflicts(): bool
    {
        return $this->conflicts !== [];
    }

    public function isStale(): bool
    {
        return $this->currentLiveVersionId !== null
            && $this->workspace->base_version_id !== null
            && $this->workspace->base_version_id < $this->currentLiveVersionId;
    }

    public function conflictCount(): int
    {
        return array_sum(array_map(count(...), $this->conflicts));
    }
}
