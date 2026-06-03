<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Support\Collection;

class CapellLayoutManager
{
    /**
     * @var array<array-key, mixed>
     */
    protected static array $containerWidgets = [];

    /**
     * @return array<array-key, mixed>
     */
    public static function getMigrations(): array
    {
        return CapellLayoutBuilderManager::getMigrations();
    }

    /**
     * Store widgets for a container.
     */
    public static function storeContainerWidget(string $containerKey, string $widgetKey, Widget $widget, int $occurrence = 1): void
    {
        if (! isset(static::$containerWidgets[$containerKey])) {
            static::$containerWidgets[$containerKey] = [];
        }

        if (! isset(static::$containerWidgets[$containerKey][$widgetKey])) {
            static::$containerWidgets[$containerKey][$widgetKey] = [];
        }

        static::$containerWidgets[$containerKey][$widgetKey][$occurrence] = $widget;
    }

    /**
     * Get a widget for a container.
     */
    public static function getContainerWidget(string $containerKey, string $widgetKey, int $occurrence = 1): ?Widget
    {
        return static::getStoredContainerWidget($containerKey, $widgetKey, $occurrence)
            ?? Widget::query()->with('type')->firstWhere('key', $widgetKey);
    }

    public static function getStoredContainerWidget(string $containerKey, string $widgetKey, int $occurrence = 1): ?Widget
    {
        return static::$containerWidgets[$containerKey][$widgetKey][$occurrence] ?? null;
    }

    /**
     * @return Collection<array-key, mixed>
     */
    public static function getContainerWidgets(?string $containerKey = null): Collection
    {
        $widgets = in_array($containerKey, [null, '', '0'], true)
            ? (static::$containerWidgets)
            : static::$containerWidgets[$containerKey] ?? [];

        return new Collection(is_array($widgets) ? $widgets : []);
    }

    /**
     * Clear all stored widgets.
     */
    public static function clearContainerWidgets(): void
    {
        static::$containerWidgets = [];
    }
}
