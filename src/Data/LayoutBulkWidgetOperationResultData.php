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
     */
    public function __construct(
        public array $containers,
        public bool $changed = false,
        public bool $blocked = false,
        public ?string $skippedReason = null,
        public array $changes = [],
        public array $warnings = [],
        public array $assetMoves = [],
    ) {}
}
