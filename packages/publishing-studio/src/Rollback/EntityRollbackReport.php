<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Rollback;

use Spatie\LaravelData\Data;

/**
 * Summary of what {@see EntityRollbackAction} did (or would do, in preview
 * mode). `restoredId` is the row id now live after the rollback. `replacedId`
 * is the id of the row that was live before, if any.
 */
class EntityRollbackReport extends Data
{
    public function __construct(
        public string $modelClass,
        public string $entityUuid,
        public int $targetVersionId,
        public ?int $restoredId = null,
        public ?int $replacedId = null,
        public bool $noOp = false,
    ) {}
}
