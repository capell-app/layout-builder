<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

final class LayoutWidgetData
{
    /**
     * @return array<string, mixed>
     */
    public static function normalize(mixed $widget): array
    {
        if (is_array($widget)) {
            return $widget;
        }

        if (is_string($widget) && $widget !== '') {
            return ['widget_key' => $widget];
        }

        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeMany(mixed $widgets): array
    {
        if (! is_array($widgets)) {
            return [];
        }

        return collect($widgets)
            ->map(static fn (mixed $widget): array => self::normalize($widget))
            ->filter(static fn (array $widget): bool => self::key($widget) !== null)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $container
     * @return array<int, array<string, mixed>>
     */
    public static function fromContainer(array $container): array
    {
        return self::normalizeMany($container['widgets'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $widget
     */
    public static function key(array $widget): ?string
    {
        $widgetKey = $widget['widget_key'] ?? null;

        return is_string($widgetKey) && $widgetKey !== '' ? $widgetKey : null;
    }

    /**
     * @param  array<string, mixed>  $widget
     */
    public static function occurrence(array $widget): int
    {
        $occurrence = $widget['occurrence'] ?? 1;

        return is_numeric($occurrence) ? max(1, (int) $occurrence) : 1;
    }
}
