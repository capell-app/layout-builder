<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\LayoutBuilder\Contracts\LayoutSidebarWidgetContributor;
use Capell\LayoutBuilder\Data\LayoutSidebarWidgetData;

final class LayoutBuilderResidualSidebarContributor implements LayoutSidebarWidgetContributor
{
    public function sidebarWidgets(): array
    {
        return [
            new LayoutSidebarWidgetData('sidebar-search', ['content'], ['compact' => true]),
            new LayoutSidebarWidgetData('missing-sidebar-widget'),
            new LayoutSidebarWidgetData('other-layout-only', ['landing']),
        ];
    }
}
