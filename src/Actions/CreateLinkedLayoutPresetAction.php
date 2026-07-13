<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Enums\LayoutPresetMode;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static LayoutPreset run(Layout $layout, Site $site, list<string> $containerKeys, string $name, string $category = 'general', list<string> $tags = [], ?string $description = null, ?string $themeKey = null, ?array<string, array<string, mixed>> $containers = null)
 */
final class CreateLinkedLayoutPresetAction
{
    use AsObject;

    /**
     * @param  list<string>  $containerKeys
     * @param  list<string>  $tags
     * @param  array<string, array<string, mixed>>|null  $containers
     */
    public function handle(
        Layout $layout,
        Site $site,
        array $containerKeys,
        string $name,
        string $category = 'general',
        array $tags = [],
        ?string $description = null,
        ?string $themeKey = null,
        ?array $containers = null,
    ): LayoutPreset {
        $siteKey = $site->getKey();
        throw_if($layout->site_id === null || ! is_numeric($siteKey) || (int) $layout->site_id !== (int) $siteKey, LogicException::class, 'Linked layout presets require a layout owned by the preset site.');

        $presetKey = Str::slug($name);
        throw_if($presetKey === '', InvalidArgumentException::class, 'Linked layout preset key must not be empty.');

        $layoutContainers = $containers ?? (is_array($layout->containers) ? $layout->containers : []);
        if (array_filter(array_keys($layoutContainers), static fn (mixed $key): bool => ! is_string($key)) !== []) {
            throw new InvalidArgumentException('Layout container keys must be strings.');
        }
        /** @var array<string, array<string, mixed>> $layoutContainers */
        $items = collect($containerKeys)
            ->unique()
            ->map(function (string $containerKey) use ($layoutContainers): array {
                $container = $layoutContainers[$containerKey] ?? null;
                throw_unless(is_array($container), InvalidArgumentException::class, sprintf('Layout container [%s] does not exist.', $containerKey));

                return [
                    'id' => (string) Str::uuid(),
                    'source_key' => $containerKey,
                    'container' => resolve(SaveLayoutPresetAction::class)->sanitizeLinkedPresetContainer($container),
                ];
            })
            ->values()
            ->all();

        throw_if($items === [], InvalidArgumentException::class, 'Select at least one container for a linked preset.');

        return DB::transaction(function () use ($site, $presetKey, $themeKey, $name, $category, $tags, $description, $items): LayoutPreset {
            $existingPreset = LayoutPreset::query()
                ->where('site_id', $site->getKey())
                ->where('key', $presetKey)
                ->lockForUpdate()
                ->first();

            throw_if($existingPreset instanceof LayoutPreset, LogicException::class, 'A layout preset with this key already exists for the site.');

            return LayoutPreset::query()->create([
                'site_id' => $site->getKey(),
                'theme_key' => $themeKey,
                'name' => $name,
                'key' => $presetKey,
                'category' => $category,
                'tags' => array_values(array_unique(array_filter($tags, static fn (string $tag): bool => trim($tag) !== ''))),
                'description' => $description,
                'scope' => 'container_linked',
                'mode' => LayoutPresetMode::Linked,
                'snapshot_version' => 1,
                'revision' => 1,
                'snapshot' => [
                    'items' => $items,
                ],
            ]);
        });
    }
}
