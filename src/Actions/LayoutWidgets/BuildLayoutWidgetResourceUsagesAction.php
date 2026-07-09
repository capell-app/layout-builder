<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\LayoutWidgets;

use Capell\Core\Actions\Presentation\ResolvePresentationSettingsAction;
use Capell\Core\Enums\PresentationLoadingStrategy;
use Capell\LayoutBuilder\Data\Assets\LayoutWidgetResourceUsageData;
use Capell\LayoutBuilder\Enums\LayoutWidgetTarget;
use Capell\LayoutBuilder\Support\LayoutBuilderLayoutWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Support\LayoutWidgets\LayoutWidgetRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class BuildLayoutWidgetResourceUsagesAction
{
    use AsObject;

    /**
     * @param  array<int|string, mixed>  $content
     * @return array<int, LayoutWidgetResourceUsageData>
     */
    public function handle(array $content, LayoutWidgetTarget $target): array
    {
        $usages = [];

        foreach (array_values($content) as $index => $block) {
            if (! is_array($block)) {
                continue;
            }

            if (! is_string($block['type'] ?? null)) {
                continue;
            }

            $definition = resolve(LayoutWidgetRegistry::class)->definition($block['type'], $target);
            if ($definition === null) {
                continue;
            }

            if ($definition->resourceGroups === []) {
                continue;
            }

            foreach ($definition->resourceGroups as $resourceGroup) {
                $loadingStrategy = $definition->resourceGroupLoadingStrategies[$resourceGroup]
                    ?? $definition->defaultLoadingStrategy;
                $defaultPresentationSettings = $definition->defaultPresentationSettings;

                if ($loadingStrategy instanceof PresentationLoadingStrategy) {
                    $defaultPresentationSettings['loading_strategy'] = $loadingStrategy->value;
                }

                $usages[] = new LayoutWidgetResourceUsageData(
                    widgetKey: $definition->key,
                    resourceGroup: $resourceGroup,
                    publicId: LayoutBuilderLayoutWidgetResourceUsageContributor::resourceGroupPublicId($resourceGroup),
                    presentation: ResolvePresentationSettingsAction::make()->fromWidgetBlockData(
                        $block,
                        $defaultPresentationSettings,
                    ),
                );
            }
        }

        return $usages;
    }
}
