<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\LayoutBuilder\Models\Element;
use Illuminate\Support\Collection;

class CapellLayoutManager
{
    protected static array $containerElements = [];

    public static function getMigrations(): array
    {
        return CapellLayoutBuilderManager::getMigrations();
    }

    /**
     * Store elements for a container
     */
    public static function storeContainerElement(string $containerKey, string $elementKey, Element $element, int $occurrence = 1): void
    {
        if (! isset(static::$containerElements[$containerKey])) {
            static::$containerElements[$containerKey] = [];
        }

        if (! isset(static::$containerElements[$containerKey][$elementKey])) {
            static::$containerElements[$containerKey][$elementKey] = [];
        }

        static::$containerElements[$containerKey][$elementKey][$occurrence] = $element;
    }

    /**
     * Get a element for a container
     */
    public static function getContainerElement(string $containerKey, string $elementKey, int $occurrence = 1): ?Element
    {
        return static::getStoredContainerElement($containerKey, $elementKey, $occurrence)
            ?? Element::query()->with('type')->firstWhere('key', $elementKey);
    }

    public static function getContainerWidget(string $containerKey, string $widgetKey, int $occurrence = 1): ?Element
    {
        return static::getContainerElement($containerKey, $widgetKey, $occurrence);
    }

    public static function getStoredContainerElement(string $containerKey, string $elementKey, int $occurrence = 1): ?Element
    {
        return static::$containerElements[$containerKey][$elementKey][$occurrence] ?? null;
    }

    public static function getContainerElements(?string $containerKey = null): Collection
    {
        $elements = in_array($containerKey, [null, '', '0'], true)
            ? (static::$containerElements)
            : static::$containerElements[$containerKey] ?? [];

        return collect($elements);
    }

    /**
     * Clear all stored elements
     */
    public static function clearContainerElements(): void
    {
        static::$containerElements = [];
    }
}
