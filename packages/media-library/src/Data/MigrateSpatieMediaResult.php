<?php

declare(strict_types=1);

namespace Capell\MediaLibrary\Data;

use Spatie\LaravelData\Data;

/**
 * Result DTO returned by MigrateSpatieMediaToCuratorAction.
 *
 * @property int $processed Total Spatie rows examined.
 * @property int $created Curator rows inserted (new).
 * @property int $skipped Rows whose Curator record already existed (idempotency).
 * @property int $ownersUpdated Owner rows whose FK column was populated.
 * @property array<int, string> $warnings Human-readable messages for unplaceable rows.
 */
final class MigrateSpatieMediaResult extends Data
{
    public function __construct(
        public readonly int $processed,
        public readonly int $created,
        public readonly int $skipped,
        public readonly int $ownersUpdated,
        /** @var array<int, string> */
        public readonly array $warnings,
    ) {}
}
