<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

class LayoutPlanData extends Data
{
    /**
     * @param  array<int, string>  $containers
     * @param  array<int, string>  $sections
     */
    public function __construct(
        public string $prompt,
        public string $presetKey,
        public array $containers,
        public array $sections,
        public bool $reusesExistingBlocks = true,
    ) {}
}
