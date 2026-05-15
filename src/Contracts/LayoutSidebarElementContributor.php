<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts;

use Capell\LayoutBuilder\Data\LayoutSidebarElementData;

interface LayoutSidebarElementContributor
{
    public const TAG = 'capell.layout-builder.sidebar-element-contributor';

    /**
     * @return array<int, LayoutSidebarElementData>
     */
    public function sidebarElements(): array;
}
