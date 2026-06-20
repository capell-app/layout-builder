<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Assets;

use Capell\Core\Enums\PresentationLoadingStrategy;
use Capell\Frontend\Contracts\FrontendAssetContributor;
use Capell\Frontend\Data\Assets\FrontendResourceGroupData;
use Capell\Frontend\Data\FrontendAssetContextData;
use Capell\Frontend\Data\FrontendAssetRequirementData;
use Capell\Frontend\Support\Assets\FrontendResourceRegistry;

class LayoutWidgetResourceAssetContributor implements FrontendAssetContributor
{
    public function __construct(
        private readonly FrontendResourceRegistry $resources,
    ) {}

    public function requirements(FrontendAssetContextData $context): array
    {
        $requirements = [];

        foreach ($context->widgetResourceUsages as $usage) {
            if (! $usage instanceof LayoutWidgetResourceUsageData) {
                continue;
            }

            $group = $this->resources->get($usage->resourceGroup);
            if (! $group instanceof FrontendResourceGroupData) {
                continue;
            }

            foreach ($group->resources as $resource) {
                $isLazy = $resource->loadingStrategy !== PresentationLoadingStrategy::Eager;
                $requirements[] = new FrontendAssetRequirementData(
                    handle: $resource->handle . ':' . $usage->publicId,
                    kind: $resource->kind,
                    source: $resource->source,
                    buildPath: $resource->buildPath,
                    defer: $resource->defer,
                    async: $resource->async,
                    condition: $isLazy ? $usage->publicId : null,
                    loadingStrategy: $resource->loadingStrategy,
                    module: $resource->module,
                );
            }
        }

        return $requirements;
    }
}
