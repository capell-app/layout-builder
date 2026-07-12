<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutBuilderTreeWidgetData extends Data
{
    public function __construct(
        public string $nodeId,
        public string $containerKey,
        public int $widgetIndex,
        public ?string $widgetKey,
        public string $label,
        public ?string $typeLabel,
        public ?string $icon,
        public int $assetCount,
        public bool $usesPageContent,
        public bool $isSelected,
    ) {}
}
