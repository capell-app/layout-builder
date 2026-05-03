<?php

declare(strict_types=1);

namespace Capell\Navigation\Data;

use Capell\Navigation\Enums\NavigationItemType;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class NavigationItemRenderData extends Data
{
    /**
     * @param  Collection<int, NavigationItemRenderData>  $children
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public ?string $label,
        public NavigationItemType $type,
        public ?string $url,
        public bool $active,
        public Collection $children,
        public array $data = [],
        public ?string $target = null,
        public ?string $icon = null,
        public ?string $activeIcon = null,
        public ?string $class = null,
        public ?string $component = null,
        public ?string $componentItem = null,
        public bool $hideLabel = false,
    ) {}
}
