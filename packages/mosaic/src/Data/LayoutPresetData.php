<?php

declare(strict_types=1);

namespace Capell\Mosaic\Data;

use Spatie\LaravelData\Data;

class LayoutPresetData extends Data
{
    /**
     * @param  array<int, string>  $containers
     * @param  array<int, string>  $sections
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public array $containers,
        public array $sections,
    ) {}
}
