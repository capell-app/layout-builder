<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class WidgetLayoutUsageData extends Data
{
    public function __construct(
        public readonly int $layouts = 0,
        public readonly bool $isComplete = false,
        public readonly bool $isAuthoritative = false,
    ) {}
}
