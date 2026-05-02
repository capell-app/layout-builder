<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(Pageable $page, Navigation $navigation, ?string $label = null)
 */
class AddPageToNavigationAction
{
    use AsObject;

    public function handle(Pageable $page, Navigation $navigation, ?string $label = null): void
    {
        if ($this->pageExistsInNavigation($navigation, $page)) {
            return;
        }

        $items = $navigation->items ?? [];

        $items[(string) Str::uuid()] = [
            'label' => $label,
            'type' => NavigationItemType::Page->value,
            'data' => [
                'site_id' => $page->site_id,
                'pageable_id' => (int) $page->getKey(),
                'pageable_type' => $page->getMorphClass(),
            ],
            'children' => [],
        ];

        $navigation->update(['items' => $items]);
    }

    private function pageExistsInNavigation(Navigation $navigation, Pageable $page): bool
    {
        return $navigation->items
            ->toCollection()
            ->contains(
                fn (NavigationItemData $item): bool => $item->type === NavigationItemType::Page
                    && $item->data['pageable_type'] === $page->getMorphClass()
                    && (int) $item->data['pageable_id'] === $page->getKey(),
            );
    }
}
