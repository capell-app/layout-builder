<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

final class LayoutBlockData
{
    /**
     * @return array<string, mixed>
     */
    public static function normalize(mixed $block): array
    {
        if (is_array($block)) {
            return $block;
        }

        if (is_string($block) && $block !== '') {
            return ['block_key' => $block];
        }

        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeMany(mixed $blocks): array
    {
        if (! is_array($blocks)) {
            return [];
        }

        return collect($blocks)
            ->map(static fn (mixed $block): array => self::normalize($block))
            ->filter(static fn (array $block): bool => self::key($block) !== null)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $block
     */
    public static function key(array $block): ?string
    {
        $blockKey = $block['block_key'] ?? null;

        return is_string($blockKey) && $blockKey !== '' ? $blockKey : null;
    }

    /**
     * @param  array<string, mixed>  $block
     */
    public static function occurrence(array $block): int
    {
        $occurrence = $block['occurrence'] ?? 1;

        return is_numeric($occurrence) ? max(1, (int) $occurrence) : 1;
    }
}
