<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutChangeData extends Data
{
    public function __construct(
        public string $type,
        public string $label,
        public ?string $containerKey,
        public ?int $blockIndex,
    ) {}
}
