<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts\Assets;

use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Data\Assets\LayoutWidgetResourceUsageData;

interface LayoutWidgetResourceUsageContributor
{
    public const string TAG = 'capell.frontend.widget-resource-usage-contributor';

    /**
     * @return array<int, LayoutWidgetResourceUsageData>
     */
    public function usages(FrontendRenderContextData $context): array;
}
