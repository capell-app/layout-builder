<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Actions\Presentation\ResolvePresentationSettingsAction;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Frontend\Contracts\WidgetResourceUsageContributor;
use Capell\Frontend\Data\Assets\WidgetResourceUsageData;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;

class LayoutWidgetResourceUsageContributor implements WidgetResourceUsageContributor
{
    public static function publicId(string $widgetKey, string $resourceGroup, string $containerKey, int $occurrence): string
    {
        return hash('xxh128', implode(':', [$widgetKey, $resourceGroup, $containerKey, $occurrence]));
    }

    /**
     * @return array<int, WidgetResourceUsageData>
     */
    public function usages(FrontendRenderContextData $context): array
    {
        if (! $context->layout instanceof Layout || ! $context->language instanceof Language || ! $context->page instanceof Pageable) {
            return [];
        }

        $containers = $context->layout->getAttribute('containers');
        if (! is_array($containers) || $containers === []) {
            return [];
        }

        $loader = resolve(LayoutLoader::class);
        $loader->preloadLayoutWidgets($context->layout, $context->language, $context->page);

        $usages = [];

        foreach ($containers as $containerKey => $container) {
            if (! is_array($container)) {
                continue;
            }

            foreach (LayoutWidgetData::fromContainer($container) as $widgetData) {
                $widgetKey = LayoutWidgetData::key($widgetData);
                if ($widgetKey === null) {
                    continue;
                }

                $occurrence = LayoutWidgetData::occurrence($widgetData);
                $widget = $loader->getLayoutWidget(
                    $context->layout,
                    $widgetKey,
                    $context->language,
                    $context->page,
                    (string) $containerKey,
                    $occurrence,
                );

                if (! $widget instanceof Widget) {
                    continue;
                }

                $resourceGroups = $this->resourceGroups($widget, $widgetData);
                if ($resourceGroups === []) {
                    continue;
                }

                $widgetMeta = is_array($widgetData['meta'] ?? null) ? $widgetData['meta'] : [];

                $type = $widget->type;
                $typeMeta = $type instanceof Blueprint && is_array($type->meta) ? $type->meta : [];

                $presentation = ResolvePresentationSettingsAction::run(
                    instanceSettings: is_array($widgetMeta['presentation'] ?? null) ? $widgetMeta['presentation'] : [],
                    typeDefaults: is_array($typeMeta['presentation'] ?? null) ? $typeMeta['presentation'] : [],
                );

                foreach ($resourceGroups as $resourceGroup) {
                    $usages[] = new WidgetResourceUsageData(
                        widgetKey: $widgetKey,
                        resourceGroup: $resourceGroup,
                        publicId: self::publicId($widgetKey, $resourceGroup, (string) $containerKey, $occurrence),
                        presentation: $presentation,
                    );
                }
            }
        }

        return $usages;
    }

    /**
     * @param  array<string, mixed>  $widgetData
     * @return array<int, string>
     */
    private function resourceGroups(Widget $widget, array $widgetData): array
    {
        $widgetMeta = is_array($widgetData['meta'] ?? null) ? $widgetData['meta'] : [];

        $instanceGroups = is_array($widgetMeta['resource_groups'] ?? null)
            ? $widgetMeta['resource_groups']
            : [];
        $type = $widget->type;
        $typeMeta = $type instanceof Blueprint && is_array($type->meta) ? $type->meta : [];

        $typeGroups = is_array($typeMeta['resource_groups'] ?? null)
            ? $typeMeta['resource_groups']
            : [];

        return collect([...$typeGroups, ...$instanceGroups])
            ->filter(fn (mixed $resourceGroup): bool => is_string($resourceGroup) && $resourceGroup !== '')
            ->unique()
            ->values()
            ->all();
    }
}
