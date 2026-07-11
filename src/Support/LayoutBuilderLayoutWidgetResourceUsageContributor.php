<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Actions\Presentation\ResolvePresentationSettingsAction;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Contracts\Assets\LayoutWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Data\Assets\LayoutWidgetResourceUsageData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;

class LayoutBuilderLayoutWidgetResourceUsageContributor implements LayoutWidgetResourceUsageContributor
{
    public static function publicId(string $widgetKey, string $resourceGroup, string $containerKey, int $occurrence): string
    {
        return self::resourceGroupPublicId($resourceGroup);
    }

    public static function resourceGroupPublicId(string $resourceGroup): string
    {
        return hash('xxh128', $resourceGroup);
    }

    /**
     * @return array<int, LayoutWidgetResourceUsageData>
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

            $stringKeyedContainer = array_filter(
                $container,
                static fn (int|string $key): bool => is_string($key),
                ARRAY_FILTER_USE_KEY,
            );

            foreach (LayoutWidgetData::fromContainer($stringKeyedContainer) as $widgetData) {
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

                $type = $widget->blueprint;
                $typeMeta = $type instanceof Blueprint && is_array($type->meta) ? $type->meta : [];

                $presentation = ResolvePresentationSettingsAction::make()->handle(
                    instanceSettings: is_array($widgetMeta['presentation'] ?? null) ? $widgetMeta['presentation'] : [],
                    typeDefaults: is_array($typeMeta['presentation'] ?? null) ? $typeMeta['presentation'] : [],
                );

                foreach ($resourceGroups as $resourceGroup) {
                    $usages[] = new LayoutWidgetResourceUsageData(
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
        $type = $widget->blueprint;
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
