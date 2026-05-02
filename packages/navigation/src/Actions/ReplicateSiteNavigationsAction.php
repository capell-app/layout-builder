<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(Site $source, Site $replica, array $replacementPages = [])
 */
class ReplicateSiteNavigationsAction
{
    use AsObject;

    /**
     * @param  array<int|string, Pageable>  $replacementPages
     */
    public function handle(Site $source, Site $replica, array $replacementPages = []): void
    {
        $sourceNavigations = Navigation::query()
            ->where('site_id', $source->getKey())
            ->get();

        foreach ($sourceNavigations as $navigation) {
            $clone = $navigation->replicate();
            $clone->site()->associate($replica);
            $clone->items = $this->mapItems($navigation->getRawOriginal('items'), $replacementPages);
            $clone->save();
        }
    }

    /**
     * @param  array<int|string, Pageable>  $replacementPages
     */
    private function mapItems(?string $rawItems, array $replacementPages): array
    {
        if ($rawItems === null || $rawItems === '') {
            return [];
        }

        $decoded = json_decode($rawItems, true);

        if (! is_array($decoded)) {
            return [];
        }

        return $this->remapItemList($decoded, $replacementPages);
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @param  array<int|string, Pageable>  $replacementPages
     * @return array<int|string, mixed>
     */
    private function remapItemList(array $items, array $replacementPages): array
    {
        $result = [];

        foreach ($items as $key => $item) {
            if (! is_array($item)) {
                continue;
            }

            if (($item['type'] ?? null) === NavigationItemType::Page->value) {
                $sourcePageId = $item['data']['pageable_id'] ?? null;

                if ($sourcePageId !== null && isset($replacementPages[$sourcePageId])) {
                    $replacement = $replacementPages[$sourcePageId];
                    $item['data']['pageable_id'] = $replacement->getKey();
                    $item['data']['pageable_type'] = $replacement->getMorphClass();
                }
            }

            if (isset($item['children']) && is_array($item['children']) && $item['children'] !== []) {
                $item['children'] = $this->remapItemList($item['children'], $replacementPages);
            }

            $result[$key] = $item;
        }

        return $result;
    }
}
