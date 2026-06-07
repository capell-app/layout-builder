<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\ContentInventory;

use Capell\LayoutBuilder\Contracts\LayoutContentGroupContributor;
use Capell\LayoutBuilder\Data\LayoutContentGroupData;
use Capell\LayoutBuilder\Data\LayoutContentInventoryContextData;
use Capell\LayoutBuilder\Data\LayoutContentItemData;

final readonly class LayoutContentInventoryGrouper
{
    public function __construct(
        private LayoutContentInventoryItemFactory $itemFactory,
    ) {}

    /**
     * @param  array<string, LayoutContentGroupData>  $groups
     * @param  array<int, LayoutContentGroupContributor>  $contributors
     * @return array<string, LayoutContentGroupData>
     */
    public function appendItemToOwnershipGroup(
        array $groups,
        LayoutContentItemData $item,
        array $contributors,
        LayoutContentInventoryContextData $context,
    ): array {
        if (! isset($groups[$item->ownershipGroupKey])) {
            $group = new LayoutContentGroupData(
                key: $item->ownershipGroupKey,
                label: $item->ownershipGroupLabel,
                summary: $this->itemFactory->ownershipGroupSummary($item->ownershipGroupKey),
                items: [],
                order: count($groups),
            );

            foreach ($contributors as $contributor) {
                $group = $contributor->group($group, $context);
            }

            $groups[$item->ownershipGroupKey] = $group;
        }

        $groups[$item->ownershipGroupKey]->items[] = $item;

        return $groups;
    }
}
