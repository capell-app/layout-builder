<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\LayoutBuilder\Data\PublicLayoutContainerData;
use Capell\Core\LayoutBuilder\Data\PublicLayoutGraphData;
use Capell\Core\LayoutBuilder\Data\PublicLayoutWidgetData;
use Capell\Core\LayoutBuilder\Support\Loader\LayoutLoader;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Widget;
use Capell\LayoutBuilder\Contracts\PublicWidgetPayloadResolver;
use Lorisleiva\Actions\Concerns\AsObject;

class BuildPublicLayoutGraphAction
{
    use AsObject;

    /**
     * @param  array<int, string>  $containers
     */
    public function handle(Layout $layout, Page $page, Language $language, array $containers = [], bool $includeHtml = false): PublicLayoutGraphData
    {
        $layoutContainers = $layout->getAttribute('containers');
        $layoutContainers = is_array($layoutContainers) ? $layoutContainers : [];

        $selectedContainers = $this->selectedContainers($containers);
        $loader = resolve(LayoutLoader::class);

        $loader->preloadLayoutWidgets($layout, $language, $page, $selectedContainers);

        return new PublicLayoutGraphData(
            key: $layout->key,
            meta: [],
            containers: collect($layoutContainers)
                ->filter(fn (mixed $container, string|int $containerKey): bool => $this->shouldIncludeContainer((string) $containerKey, $selectedContainers))
                ->map(fn (mixed $container, string|int $containerKey): PublicLayoutContainerData => $this->containerData(
                    layout: $layout,
                    page: $page,
                    language: $language,
                    loader: $loader,
                    containerKey: (string) $containerKey,
                    container: is_array($container) ? $container : [],
                    includeHtml: $includeHtml,
                    selectedContainers: $selectedContainers,
                ))
                ->values()
                ->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $container
     * @param  array<int, string>|null  $selectedContainers
     */
    private function containerData(
        Layout $layout,
        Page $page,
        Language $language,
        LayoutLoader $loader,
        string $containerKey,
        array $container,
        bool $includeHtml,
        ?array $selectedContainers,
    ): PublicLayoutContainerData {
        $widgets = $container['widgets'] ?? [];
        $widgets = is_array($widgets) ? $widgets : [];

        return new PublicLayoutContainerData(
            key: $containerKey,
            meta: [],
            widgets: collect($widgets)
                ->map(fn (mixed $widgetData): ?PublicLayoutWidgetData => $this->widgetData(
                    layout: $layout,
                    page: $page,
                    language: $language,
                    loader: $loader,
                    containerKey: $containerKey,
                    widgetData: is_array($widgetData) ? $widgetData : [],
                    includeHtml: $includeHtml,
                    selectedContainers: $selectedContainers,
                ))
                ->filter()
                ->values()
                ->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $widgetData
     * @param  array<int, string>|null  $selectedContainers
     */
    private function widgetData(
        Layout $layout,
        Page $page,
        Language $language,
        LayoutLoader $loader,
        string $containerKey,
        array $widgetData,
        bool $includeHtml,
        ?array $selectedContainers,
    ): ?PublicLayoutWidgetData {
        $widgetKey = $widgetData['widget_key'] ?? null;
        if (! is_string($widgetKey) || $widgetKey === '') {
            return null;
        }

        $occurrence = (int) ($widgetData['occurrence'] ?? 1);
        $widget = $loader->getLayoutWidget($layout, $widgetKey, $language, $page, $containerKey, $occurrence, $selectedContainers);

        if (! $widget instanceof Widget) {
            return null;
        }

        $resolver = resolve(PublicWidgetPayloadResolver::class);

        return new PublicLayoutWidgetData(
            key: $widgetKey,
            occurrence: $occurrence,
            type: $widget->type?->key,
            data: $resolver->data($widget, $page, $language, $containerKey, $occurrence),
            html: $includeHtml ? $resolver->html($widget, $page, $language, $containerKey, $occurrence) : null,
        );
    }

    /**
     * @param  array<int, string>  $containers
     * @return array<int, string>|null
     */
    private function selectedContainers(array $containers): ?array
    {
        if ($containers === [] || in_array('*', $containers, true)) {
            return null;
        }

        return array_values(array_unique($containers));
    }

    /**
     * @param  array<int, string>|null  $selectedContainers
     */
    private function shouldIncludeContainer(string $containerKey, ?array $selectedContainers): bool
    {
        return $selectedContainers === null || in_array($containerKey, $selectedContainers, true);
    }
}
