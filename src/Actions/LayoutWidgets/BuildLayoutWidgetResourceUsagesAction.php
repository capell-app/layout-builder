<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\LayoutWidgets;

use Capell\Core\Actions\Presentation\ResolvePresentationSettingsAction;
use Capell\LayoutBuilder\Data\Assets\LayoutWidgetResourceUsageData;
use Capell\LayoutBuilder\Enums\LayoutWidgetTarget;
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

            $presentation = ResolvePresentationSettingsAction::make()->fromWidgetBlockData($block, $definition->defaultPresentationSettings);

            foreach ($definition->resourceGroups as $resourceGroup) {
                $usages[] = new LayoutWidgetResourceUsageData(
                    widgetKey: $definition->key,
                    resourceGroup: $resourceGroup,
                    publicId: hash('xxh128', $definition->key . ':' . $resourceGroup . ':' . $index),
                    presentation: $presentation,
                );
            }
        }

        return $usages;
    }
}
