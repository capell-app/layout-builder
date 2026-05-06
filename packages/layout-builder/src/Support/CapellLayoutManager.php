<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Support\Collection;

class CapellLayoutManager
{
    protected static array $containerWidgets = [];

    public static function getMigrations(): array
    {
        return [
            'create_sections_table',
            'create_widgets_table',
            'create_widget_assets_table',
            'add_container_widgets_to_layouts_table',
        ];
    }

    /**
     * Store widgets for a container
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
     * Get a widget for a container
     */
    public static function getContainerWidget(string $containerKey, string $widgetKey, int $occurrence = 1): ?Widget
    {
        return static::$containerWidgets[$containerKey][$widgetKey][$occurrence]
            ?? Widget::query()->with('type')->firstWhere('key', $widgetKey);
    }

    public static function getContainerWidgets(?string $containerKey = null): Collection
    {
        $widgets = in_array($containerKey, [null, '', '0'], true)
            ? (static::$containerWidgets)
            : static::$containerWidgets[$containerKey] ?? [];

        return collect($widgets);
    }

    /**
     * Clear all stored widgets
     */
    public static function clearContainerWidgets(): void
    {
        static::$containerWidgets = [];
    }
}
