<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutMutationNavigationData extends Data
{
    public function __construct(
        public readonly ?LayoutBuilderStateData $state,
        public readonly LayoutMutationHistoryData $history,
    ) {}

    public function changed(): bool
    {
        return $this->state instanceof LayoutBuilderStateData;
    }
}
