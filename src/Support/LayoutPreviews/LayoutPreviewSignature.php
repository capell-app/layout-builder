<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\LayoutPreviews;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

final class LayoutPreviewSignature
{
    public function forLayout(Layout $layout): string
    {
        return hash('sha256', json_encode(
            $this->payload($layout),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(Layout $layout): array
    {
        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        $widgetKeys = $this->widgetKeys($containers);
        $widgets = $this->widgetsByKey($widgetKeys);

        return [
            'layout' => [
                'id' => $layout->getKey(),
                'key' => $layout->getAttribute('key'),
            ],
            'containers' => $this->normalizeContainers($containers, $widgets),
        ];
    }

    /**
     * @param  array<string, mixed>  $containers
     * @return array<int, string>
     */
    private function widgetKeys(array $containers): array
    {
        $widgetKeys = [];

        foreach ($containers as $container) {
            if (! is_array($container)) {
                continue;
            }

            foreach (LayoutWidgetData::fromContainer($container) as $widget) {
                $widgetKey = LayoutWidgetData::key($widget);
                if ($widgetKey === null) {
                    continue;
                }

                $widgetKeys[] = $widgetKey;
            }
        }

        return array_values(array_unique($widgetKeys));
    }

    /**
     * @param  array<int, string>  $widgetKeys
     * @return array<string, Widget>
     */
    private function widgetsByKey(array $widgetKeys): array
    {
        if ($widgetKeys === []) {
            return [];
        }

        /** @var EloquentCollection<int, Widget> $widgets */
        $widgets = Widget::query()
            ->with('blueprint')
            ->whereIn('key', $widgetKeys)
            ->get();

        return $widgets->keyBy('key')->all();
    }

    /**
     * @param  array<string, mixed>  $containers
     * @param  array<string, Widget>  $widgets
     * @return array<int, array<string, mixed>>
     */
    private function normalizeContainers(array $containers, array $widgets): array
    {
        $normalizedContainers = [];

        foreach ($containers as $containerKey => $container) {
            if (! is_array($container)) {
                continue;
            }

            $normalizedContainers[] = [
                'key' => $containerKey,
                'colspan' => $this->colspan($container),
                'widgets' => $this->normalizeWidgets($container['widgets'] ?? [], $widgets),
            ];
        }

        return $normalizedContainers;
    }

    /**
     * @param  array<string, mixed>  $container
     */
    private function colspan(array $container): int
    {
        $colspan = (int) ($container['meta']['colspan'] ?? 12);

        return min(12, max(1, $colspan));
    }

    /**
     * @param  array<string, Widget>  $widgets
     * @return array<int, array<string, mixed>>
     */
    private function normalizeWidgets(mixed $containerWidgets, array $widgets): array
    {
        $normalizedWidgets = [];

        foreach (LayoutWidgetData::normalizeMany($containerWidgets) as $containerWidget) {
            $widgetKey = LayoutWidgetData::key($containerWidget);
            if ($widgetKey === null) {
                continue;
            }

            $widget = $widgets[$widgetKey] ?? null;

            $normalizedWidgets[] = [
                'key' => $widgetKey,
                'occurrence' => LayoutWidgetData::occurrence($containerWidget),
                'name' => $widget?->name,
                'icon' => $widget?->admin['icon'] ?? $widget?->blueprint?->admin['icon'] ?? null,
                'type_name' => $widget?->blueprint?->name,
                'type_icon' => $widget?->blueprint?->admin['icon'] ?? null,
                'meta_name' => $containerWidget['meta']['name'] ?? null,
            ];
        }

        return $normalizedWidgets;
    }
}
