<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutMutationHistoryData extends Data
{
    /**
     * @param  array<int, array<string, mixed>>  $undoSnapshots
     * @param  array<int, array<string, mixed>>  $redoSnapshots
     */
    public function __construct(
        public array $undoSnapshots = [],
        public array $redoSnapshots = [],
    ) {}
}
