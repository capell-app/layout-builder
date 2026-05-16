<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

final class LayoutElementData
{
    /**
     * @return array<string, mixed>
     */
    public static function normalize(mixed $element): array
    {
        if (is_array($element)) {
            return $element;
        }

        if (is_string($element) && $element !== '') {
            return ['element_key' => $element];
        }

        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeMany(mixed $elements): array
    {
        if (! is_array($elements)) {
            return [];
        }

        return collect($elements)
            ->map(static fn (mixed $element): array => self::normalize($element))
            ->filter(static fn (array $element): bool => self::key($element) !== null)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $element
     */
    public static function key(array $element): ?string
    {
        $elementKey = $element['element_key'] ?? null;

        return is_string($elementKey) && $elementKey !== '' ? $elementKey : null;
    }

    /**
     * @param  array<string, mixed>  $element
     */
    public static function occurrence(array $element): int
    {
        $occurrence = $element['occurrence'] ?? 1;

        return is_numeric($occurrence) ? max(1, (int) $occurrence) : 1;
    }
}
