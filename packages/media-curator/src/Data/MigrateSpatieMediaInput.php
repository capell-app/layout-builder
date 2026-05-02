<?php

declare(strict_types=1);

namespace Capell\MediaCurator\Data;

use Spatie\LaravelData\Data;

/**
 * Input DTO for the Spatie-to-Curator data migration action.
 *
 * @property bool $dryRun When true the action reports without writing.
 * @property array<int, string> $collections Spatie collection names to include (empty = all).
 * @property int $chunkSize Number of Spatie media rows processed per chunk.
 * @property string|null $ownerType Optional filter: FQCN of the owner model (null = all).
 */
final class MigrateSpatieMediaInput extends Data
{
    public function __construct(
        public readonly bool $dryRun = false,
        /** @var array<int, string> */
        public readonly array $collections = [],
        public readonly int $chunkSize = 200,
        public readonly ?string $ownerType = null,
    ) {}
}
