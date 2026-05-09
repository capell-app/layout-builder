<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Support;

final class TranslationArray
{
    /**
     * @param  array<string, mixed>  $values
     * @return array<string, array{value: string|null, editable: bool, exists: bool}>
     */
    public static function flattenForEditor(array $values, string $prefix = ''): array
    {
        $entries = [];

        foreach ($values as $key => $value) {
            $entryKey = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $entries += self::flattenForEditor($value, $entryKey);

                continue;
            }

            $entries[$entryKey] = [
                'value' => is_scalar($value) ? (string) $value : null,
                'editable' => is_string($value),
                'exists' => true,
            ];
        }

        return $entries;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     */
    public static function flattenStrings(array $values, string $prefix = ''): array
    {
        $entries = [];

        foreach ($values as $key => $value) {
            $entryKey = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $entries += self::flattenStrings($value, $entryKey);

                continue;
            }

            if (is_string($value)) {
                $entries[$entryKey] = $value;
            }
        }

        return $entries;
    }

    /**
     * @param  array<string, string|null>  $values
     * @return array<string, mixed>
     */
    public static function unflattenStrings(array $values): array
    {
        $result = [];

        foreach ($values as $key => $value) {
            self::setNestedValue($result, $key, $value ?? '');
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function setNestedValue(array $values, string $key, string $value): array
    {
        $segments = explode('.', $key);
        $cursor = &$values;

        foreach ($segments as $segment) {
            if (! isset($cursor[$segment]) || ! is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }

            $cursor = &$cursor[$segment];
        }

        $cursor = $value;

        return $values;
    }
}
