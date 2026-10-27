<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Capell\LayoutBuilder\Models\Widget;

/** A layout placement with its widget loaded for the current public render. */
final readonly class LoadedLayoutWidgetData
{
    public function __construct(
        public string $containerKey,
        public string $key,
        public int $occurrence,
        public Widget $widget,
    ) {}
}
