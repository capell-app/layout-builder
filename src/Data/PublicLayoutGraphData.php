<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

class PublicLayoutGraphData extends Data
{
    /**
     * @param  array<string, mixed>  $meta
     * @param  array<int, PublicLayoutContainerData>  $containers
     */
    public function __construct(
        public string $key,
        public array $meta,
        public array $containers,
    ) {}
}
