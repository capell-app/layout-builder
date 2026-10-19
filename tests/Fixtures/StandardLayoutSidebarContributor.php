<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\Core\Enums\LayoutEnum;
use Capell\LayoutBuilder\Contracts\LayoutSidebarWidgetContributor;
use Capell\LayoutBuilder\Data\LayoutSidebarWidgetData;
use Override;

final class StandardLayoutSidebarContributor implements LayoutSidebarWidgetContributor
{
    #[Override]
    public function sidebarWidgets(): array
    {
        return [new LayoutSidebarWidgetData('standard-sidebar', [LayoutEnum::Default->value, LayoutEnum::Results->value])];
    }
}
