<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts;

use Capell\LayoutBuilder\Data\LayoutContentGroupData;
use Capell\LayoutBuilder\Data\LayoutContentInventoryContextData;
use Capell\LayoutBuilder\Data\LayoutContentItemData;

interface LayoutContentGroupContributor
{
    public const string TAG = 'capell.layout_builder.content_group_contributor';

    public function priority(): int;

    public function group(LayoutContentGroupData $group, LayoutContentInventoryContextData $context): LayoutContentGroupData;

    public function item(LayoutContentItemData $item, LayoutContentInventoryContextData $context): LayoutContentItemData;

    /**
     * @return array<int, string>
     */
    public function eagerLoads(): array;

    /**
     * @return array<int, string>
     */
    public function cacheDependencies(): array;
}
