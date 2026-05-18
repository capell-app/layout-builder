<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts;

use Capell\LayoutBuilder\Data\LayoutSidebarBlockData;

interface LayoutSidebarBlockContributor
{
    public const string TAG = 'capell.layout-builder.sidebar-block-contributor';

    /**
     * @return array<int, LayoutSidebarBlockData>
     */
    public function sidebarBlocks(): array;
}
