<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Support\LayoutBlockData;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Lorisleiva\Actions\Concerns\AsObject;

final class SaveLayoutPresetAction
{
    use AsObject;

    /** @var list<string> */
    private const array UNSAFE_PRESET_KEYS = [
        'admin_schema',
        'admin_url',
        'adminSchema',
        'authoring',
        'data_model_id',
        'data_model_type',
        'diagnostics',
        'edit_url',
        'editUrl',
        'editor_selectors',
        'editorSelectors',
        'editor_url',
        'editorUrl',
        'field_path',
        'fieldPath',
        'model_id',
        'modelId',
        'package',
        'package_name',
        'permissions',
        'preview_view',
        'public_view',
        'schema',
        'signed_url',
        'signed_admin_url',
        'signed_editor_url',
        'signedAdminUrl',
        'signedEditorUrl',
        'signedUrl',
    ];

    /**
     * @param  array<array-key, mixed>|null  $containers
     */
    public function handle(
        Layout $layout,
        Site $site,
        string $name,
        ?string $key = null,
        string $category = 'general',
        ?string $themeKey = null,
        ?array $containers = null,
        bool $includeStarterContent = false,
        bool $replaceExisting = false,
    ): LayoutPreset {
        throw_if($layout->site_id !== null && $layout->site_id !== (int) $site->getKey(), LogicException::class, 'Layout presets can only be saved for the layout site.');

        $presetKey = $key !== null && trim($key) !== '' ? Str::slug($key) : Str::slug($name);

        throw_if($presetKey === '', InvalidArgumentException::class, 'Layout preset key must not be empty.');

        return DB::transaction(function () use ($site, $presetKey, $themeKey, $name, $category, $layout, $containers, $includeStarterContent, $replaceExisting): LayoutPreset {
            $identity = [
                'site_id' => $site->getKey(),
                'key' => $presetKey,
            ];

            $values = [
                'theme_key' => $themeKey,
                'name' => $name,
                'category' => $category,
                'scope' => $includeStarterContent ? 'starter_content' : 'layout_only',
                'snapshot' => [
                    'containers' => $this->snapshotContainers($layout, $includeStarterContent, $containers),
                    'includeStarterContent' => $includeStarterContent,
                ],
            ];

            $existingPreset = LayoutPreset::query()
                ->where($identity)
                ->lockForUpdate()
                ->first();

            if ($existingPreset instanceof LayoutPreset) {
                throw_unless($replaceExisting, LogicException::class, 'A layout preset with this key already exists for the site.');

                $existingPreset->fill($values);
                $existingPreset->save();

                return $existingPreset->refresh();
            }

            try {
                return LayoutPreset::query()->create([...$identity, ...$values]);
            } catch (QueryException $queryException) {
                $conflictingPreset = LayoutPreset::query()
                    ->where($identity)
                    ->lockForUpdate()
                    ->first();

                throw_unless($conflictingPreset instanceof LayoutPreset, $queryException);

                if (! $replaceExisting) {
                    throw new LogicException('A layout preset with this key already exists for the site.', $queryException->getCode(), previous: $queryException);
                }

                $conflictingPreset->fill($values);
                $conflictingPreset->save();

                return $conflictingPreset->refresh();
            }
        });
    }

    /**
     * @param  array<array-key, mixed>  $containers
     * @return array<array-key, mixed>
     */
    public function sanitizePresetContainers(array $containers): array
    {
        return $this->scrubUnsafePresetData($containers);
    }

    /**
     * @param  array<array-key, mixed>|null  $containers
     * @return array<array-key, mixed>
     */
    private function snapshotContainers(Layout $layout, bool $includeStarterContent, ?array $containers = null): array
    {
        $containers ??= is_array($layout->containers) ? $layout->containers : [];

        return collect($containers)
            ->map(function (mixed $container) use ($includeStarterContent): array {
                $container = is_array($container) ? $container : [];
                $blocks = is_array($container['widgets'] ?? null) ? $container['widgets'] : [];

                $container = $this->scrubUnsafePresetData($container);
                $container['widgets'] = array_map(
                    fn (array $block): array => $this->snapshotBlock($block, $includeStarterContent),
                    LayoutBlockData::normalizeMany($blocks),
                );

                return $container;
            })
            ->all();
    }

    /**
     * @param  array<array-key, mixed>  $block
     * @return array<array-key, mixed>
     */
    private function snapshotBlock(array $block, bool $includeStarterContent): array
    {
        $snapshot = array_intersect_key($block, array_flip(['widget_key', 'occurrence']));
        $snapshot['occurrence'] = LayoutBlockData::occurrence($block);

        $meta = is_array($block['meta'] ?? null) ? $block['meta'] : [];
        $snapshot['meta'] = $this->safeBlockMeta($meta, $includeStarterContent);

        if (! $includeStarterContent) {
            return $snapshot;
        }

        foreach (['content', 'items', 'cta', 'media', 'image', 'images'] as $key) {
            if (array_key_exists($key, $block)) {
                $snapshot[$key] = $this->scrubUnsafePresetData($block[$key]);
            }
        }

        return $snapshot;
    }

    /**
     * @param  array<array-key, mixed>  $meta
     * @return array<array-key, mixed>
     */
    private function safeBlockMeta(array $meta, bool $includeStarterContent): array
    {
        $safeMeta = array_intersect_key($meta, array_flip(['widget_key', 'block_variant']));
        $settings = is_array($meta['block_settings'] ?? null) ? $meta['block_settings'] : [];
        $safeSettings = array_intersect_key($settings, array_flip([
            'spacing',
            'background',
            'media_position',
            'cards_per_row',
            'show_cta',
            'heading_width',
            'anchor_id',
        ]));

        if ($safeSettings !== []) {
            $safeMeta['block_settings'] = $safeSettings;
        }

        if ($includeStarterContent && is_array($meta['content'] ?? null)) {
            $safeMeta['content'] = $this->scrubUnsafePresetData($meta['content']);
        }

        return $this->scrubUnsafePresetData($safeMeta);
    }

    private function scrubUnsafePresetData(mixed $value): mixed
    {
        if (is_string($value) && $this->containsUnsafePresetToken($value)) {
            return '';
        }

        if (! is_array($value)) {
            return $value;
        }

        $value = array_filter(
            $value,
            fn (mixed $item, mixed $key): bool => ! is_string($key) || ! $this->isUnsafePresetKey($key),
            ARRAY_FILTER_USE_BOTH,
        );

        return array_map($this->scrubUnsafePresetData(...), $value);
    }

    private function isUnsafePresetKey(string $key): bool
    {
        $normalizedKey = Str::of($key)
            ->snake()
            ->replace('-', '_')
            ->lower()
            ->value();

        return in_array($normalizedKey, self::UNSAFE_PRESET_KEYS, true)
            || str_contains($normalizedKey, 'authoring')
            || str_contains($normalizedKey, 'editor')
            || str_contains($normalizedKey, 'field_path')
            || str_contains($normalizedKey, 'model_id')
            || str_contains($normalizedKey, 'permission')
            || str_contains($normalizedKey, 'signed')
            || str_starts_with($normalizedKey, 'admin_')
            || str_starts_with($normalizedKey, 'data_model_');
    }

    private function containsUnsafePresetToken(string $value): bool
    {
        $normalizedValue = Str::of($value)
            ->lower()
            ->replace(['-', ' '], '_')
            ->value();

        $urlLike = str_starts_with($normalizedValue, 'http://')
            || str_starts_with($normalizedValue, 'https://')
            || str_starts_with($normalizedValue, '/')
            || str_contains($normalizedValue, '?')
            || str_contains($normalizedValue, '=');

        if (! $urlLike) {
            return false;
        }

        return str_contains($normalizedValue, 'signed')
            || str_contains($normalizedValue, 'editor')
            || str_contains($normalizedValue, '/admin')
            || str_contains($normalizedValue, 'admin/')
            || str_contains($normalizedValue, 'signature=');
    }
}
