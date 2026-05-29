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
    protected static array $containerBlocks = [];

    /**
     * @return array<array-key, mixed>
     */
    public static function getMigrations(): array
    {
        return CapellLayoutBuilderManager::getMigrations();
    }

    /**
     * Store blocks for a container
     */
    public static function storeContainerBlock(string $containerKey, string $widgetKey, Widget $block, int $occurrence = 1): void
    {
        if (! isset(static::$containerBlocks[$containerKey])) {
            static::$containerBlocks[$containerKey] = [];
        }

        if (! isset(static::$containerBlocks[$containerKey][$widgetKey])) {
            static::$containerBlocks[$containerKey][$widgetKey] = [];
        }

        static::$containerBlocks[$containerKey][$widgetKey][$occurrence] = $block;
    }

    /**
     * Get a block for a container
     */
    public static function getContainerBlock(string $containerKey, string $widgetKey, int $occurrence = 1): ?Widget
    {
        return static::getStoredContainerBlock($containerKey, $widgetKey, $occurrence)
            ?? Widget::query()->with('type')->firstWhere('key', $widgetKey);
    }

    public static function getStoredContainerBlock(string $containerKey, string $widgetKey, int $occurrence = 1): ?Widget
    {
        return static::$containerBlocks[$containerKey][$widgetKey][$occurrence] ?? null;
    }

    /**
     * @return Collection<array-key, mixed>
     */
    public static function getContainerBlocks(?string $containerKey = null): Collection
    {
        $blocks = in_array($containerKey, [null, '', '0'], true)
            ? (static::$containerBlocks)
            : static::$containerBlocks[$containerKey] ?? [];

        return new Collection(is_array($blocks) ? $blocks : []);
    }

    /**
     * Clear all stored blocks
     */
    public static function clearContainerBlocks(): void
    {
        static::$containerBlocks = [];
    }
}
