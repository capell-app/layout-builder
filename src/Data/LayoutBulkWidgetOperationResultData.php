<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

final readonly class LayoutBulkWidgetOperationResultData
{
    /**
     * @param  array<string, mixed>  $containers
     * @param  list<string>  $changes
     * @param  list<string>  $warnings
     * @param  list<array<string, mixed>>  $assetMoves
     * @param  list<array<string, mixed>>  $assetRemovals
     * @param  list<array<string, mixed>>  $containerDiffs
     */
    public function __construct(
        public array $containers,
        public bool $changed = false,
        public bool $blocked = false,
        public ?string $skippedReason = null,
        public array $changes = [],
        public array $warnings = [],
        public array $assetMoves = [],
        public array $assetRemovals = [],
        public array $containerDiffs = [],
    ) {}
}
