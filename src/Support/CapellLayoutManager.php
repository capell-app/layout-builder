<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\LayoutBuilder\Models\Block;
use Illuminate\Support\Collection;

class CapellLayoutManager
{
    protected static array $containerBlocks = [];

    public static function getMigrations(): array
    {
        return CapellLayoutBuilderManager::getMigrations();
    }

    /**
     * Store blocks for a container
     */
    public static function storeContainerBlock(string $containerKey, string $blockKey, Block $block, int $occurrence = 1): void
    {
        if (! isset(static::$containerBlocks[$containerKey])) {
            static::$containerBlocks[$containerKey] = [];
        }

        if (! isset(static::$containerBlocks[$containerKey][$blockKey])) {
            static::$containerBlocks[$containerKey][$blockKey] = [];
        }

        static::$containerBlocks[$containerKey][$blockKey][$occurrence] = $block;
    }

    /**
     * Get a block for a container
     */
    public static function getContainerBlock(string $containerKey, string $blockKey, int $occurrence = 1): ?Block
    {
        return static::getStoredContainerBlock($containerKey, $blockKey, $occurrence)
            ?? Block::query()->with('type')->firstWhere('key', $blockKey);
    }

    public static function getStoredContainerBlock(string $containerKey, string $blockKey, int $occurrence = 1): ?Block
    {
        return static::$containerBlocks[$containerKey][$blockKey][$occurrence] ?? null;
    }

    public static function getContainerBlocks(?string $containerKey = null): Collection
    {
        $blocks = in_array($containerKey, [null, '', '0'], true)
            ? (static::$containerBlocks)
            : static::$containerBlocks[$containerKey] ?? [];

        return collect($blocks);
    }

    /**
     * Clear all stored blocks
     */
    public static function clearContainerBlocks(): void
    {
        static::$containerBlocks = [];
    }
}
