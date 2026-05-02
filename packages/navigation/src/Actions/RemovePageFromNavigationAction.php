<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(Pageable $page, Navigation $navigation)
 */
class RemovePageFromNavigationAction
{
    use AsObject;

    public function handle(Pageable $page, Navigation $navigation): void
    {
        $items = collect($navigation->items);
        [$updatedItems, $removedPage] = $this->removePageFromItems($items, $page);

        if ($removedPage) {
            $navigation->update(['items' => $updatedItems->all()]);
        }
    }

    private function isPageItem(array $item, Pageable $page): bool
    {
        return ($item['type'] ?? null) === NavigationItemType::Page->value
            && isset($item['data']['pageable_type'], $item['data']['pageable_id'])
            && (string) $item['data']['pageable_type'] === $page->getMorphClass()
            && (int) $item['data']['pageable_id'] === $page->getKey();
    }

    /**
     * @return array{0: Collection, 1: bool}
     */
    private function removePageFromItems(Collection $items, Pageable $page): array
    {
        $result = collect();
        $removedPage = false;

        foreach ($items as $item) {
            if ($item instanceof NavigationItemData) {
                $item = $item->toArray();
            }

            if (! is_array($item)) {
                continue;
            }

            if ($this->isPageItem($item, $page)) {
                $removedPage = true;

                continue;
            }

            if (isset($item['children']) && is_array($item['children']) && $item['children'] !== []) {
                [$children, $removedNestedPage] = $this->removePageFromItems(collect($item['children']), $page);
                $item['children'] = $children->all();
                $removedPage = $removedPage || $removedNestedPage;
            }

            $result->push($item);
        }

        return [$result, $removedPage];
    }
}
