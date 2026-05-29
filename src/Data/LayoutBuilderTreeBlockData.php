<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutBuilderTreeBlockData extends Data
{
    public function __construct(
        public string $nodeId,
        public string $containerKey,
        public int $blockIndex,
        public string $label,
        public ?string $typeLabel,
        public ?string $icon,
        public int $assetCount,
        public bool $usesPageContent,
        public bool $isSelected,
    ) {}
}
