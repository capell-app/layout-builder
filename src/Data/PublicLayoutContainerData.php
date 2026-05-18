<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

class PublicLayoutContainerData extends Data
{
    /**
     * @param  array<string, mixed>  $meta
     * @param  array<int, PublicLayoutBlockData>  $blocks
     */
    public function __construct(
        public string $key,
        public array $meta,
        public array $blocks,
    ) {}
}
