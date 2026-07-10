<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\WidgetSnapshots;

use Capell\Core\Data\Interactions\InteractionTargetData;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Contracts\WidgetInteractionLocatorResolver;
use Capell\Frontend\Data\PublicPageRenderData;

final readonly class PrebuiltWidgetInteractionLocatorResolver implements WidgetInteractionLocatorResolver
{
    public function __construct(private FrontendContextReader $context) {}

    public function resolve(InteractionTargetData $target): ?string
    {
        $capell = $target->widgetData['__capell'] ?? null;
        $instanceId = is_array($capell) ? ($capell['instance_id'] ?? null) : null;
        $renderData = $this->context->getFrontendData('publicPageRenderData');

        return is_string($instanceId) && $renderData instanceof PublicPageRenderData
            ? $renderData->widgetInteractionLocator($instanceId)
            : null;
    }
}
