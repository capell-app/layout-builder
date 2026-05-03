<?php

declare(strict_types=1);

namespace Capell\Navigation\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class NavigationRenderData extends Data
{
    /**
     * @param  Collection<int, NavigationItemRenderData>  $items
     */
    public function __construct(
        public ?int $navigationId,
        public string $navigationKey,
        public ?string $navigationName,
        public string $listComponent,
        public Collection $items,
    ) {}

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    public function isNotEmpty(): bool
    {
        return $this->items->isNotEmpty();
    }
}
