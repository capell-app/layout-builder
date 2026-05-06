<?php

declare(strict_types=1);

namespace Capell\PublishingStudio;

use Capell\PublishingStudio\Checks\PublishCheckResult;
use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Structured result of a {@see Publisher::dryRun()} call. A dry run executes
 * the full publish flow inside a transaction that is always rolled back, so
 * the report reflects what *would* happen without changing any live data.
 */
final readonly class DryRunReport
{
    /**
     * @param  array<int, array{site_id: int, language_id: int, url: string}>  $collisions
     * @param  array<class-string<Model>, int>  $rowCounts
     * @param  array<int, PublishCheckResult>  $checkResults
     */
    public function __construct(
        public Workspace $workspace,
        public bool $wouldPublish,
        public ?RebaseReport $rebaseReport,
        public array $collisions,
        public array $rowCounts,
        public ?Throwable $failure = null,
        public array $checkResults = [],
    ) {}

    public function totalRows(): int
    {
        return array_sum($this->rowCounts);
    }

    public function hasCollisions(): bool
    {
        return $this->collisions !== [];
    }

    public function hasConflicts(): bool
    {
        return $this->rebaseReport instanceof RebaseReport && $this->rebaseReport->hasConflicts();
    }

    public function hasBlockingCheckErrors(): bool
    {
        foreach ($this->checkResults as $result) {
            if ($result->isError() && ! $result->isClean()) {
                return true;
            }
        }

        return false;
    }
}
