<?php

declare(strict_types=1);

namespace Capell\Navigation\Data;

use Capell\Core\Contracts\Pageable;
use Capell\Navigation\Enums\NavigationItemType;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class NavigationItemData extends Data
{
    /**
     * @param  array<string, mixed>  $data
     * @param  DataCollection<int, NavigationItemData>|null  $children
     */
    public function __construct(
        public ?string $label = null,
        public NavigationItemType $type = NavigationItemType::Link,
        public array $data = [],
        #[DataCollectionOf(self::class)]
        public ?DataCollection $children = null,
        public ?bool $active = null,
        public bool $is_visible = true,
    ) {
        $this->children ??= new DataCollection(self::class, []);
    }

    public static function fromPages(Collection $pages): DataCollection
    {
        return self::collect(self::mapPages($pages), DataCollection::class);
    }

    private static function mapPages(Collection $pages): Collection
    {
        return $pages->map(fn (Pageable $page): self => new NavigationItemData(
            label: $page->translation->label,
            type: NavigationItemType::Page,
            data: [
                'url' => $page->pageUrl->full_url,
                'pageable_id' => $page->getKey(),
                'pageable_type' => $page->getMorphClass(),
            ],
            children: self::fromPages($page->children),
        ));
    }
}
