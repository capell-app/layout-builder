<?php

declare(strict_types=1);

namespace Capell\Migrator\Services\Import;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Translates external field names onto the Capell page/type schema.
 */
final class FieldMapper
{
    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $mapping  external column => target path
     * @return array<string, mixed>
     */
    public function map(array $row, array $mapping = [], string $target = 'page'): array
    {
        $attributes = [];

        foreach ($row as $source => $value) {
            $targetPath = $mapping[$source] ?? $this->defaultTargetPath($source, $target);
            if ($targetPath === null) {
                continue;
            }

            Arr::set($attributes, $targetPath, $value);
        }

        return $attributes;
    }

    private function defaultTargetPath(string $source, string $target): ?string
    {
        $normalized = Str::of($source)->lower()->replace([' ', '-', ':'], '_')->toString();

        if ($target === 'type') {
            return match ($normalized) {
                'name', 'title' => 'name',
                'key', 'slug' => 'key',
                'group' => 'group',
                'status' => 'status',
                default => 'meta.imported.' . $normalized,
            };
        }

        return match ($normalized) {
            'name', 'title', 'post_title' => 'name',
            'content', 'body', 'post_content' => 'meta.content',
            'excerpt', 'post_excerpt' => 'meta.excerpt',
            'slug', 'post_name' => 'meta.slug',
            'status', 'post_status' => 'meta.status',
            'date', 'post_date' => 'visible_from',
            default => 'meta.imported.' . $normalized,
        };
    }
}
