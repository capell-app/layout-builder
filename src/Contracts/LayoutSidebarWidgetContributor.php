<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts;

use Capell\LayoutBuilder\Data\LayoutSidebarWidgetData;

interface LayoutSidebarWidgetContributor
{
    public const string TAG = 'capell.layout-builder.sidebar-widget-contributor';

    /**
     * @return array<int, LayoutSidebarWidgetData>
     */
    public function sidebarWidgets(): array;
}
