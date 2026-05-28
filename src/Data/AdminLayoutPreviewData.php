<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class AdminLayoutPreviewData extends Data
{
    /**
     * @param  array<string, array{type: string, containerKey: string, blockIndex?: int}>  $nodeMap
     */
    public function __construct(
        public string $html,
        public string $signature,
        public array $nodeMap,
    ) {}
}
