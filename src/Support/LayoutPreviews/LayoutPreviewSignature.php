<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\LayoutPreviews;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutBlockData;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

final class LayoutPreviewSignature
{
    public function forLayout(Layout $layout): string
    {
        return hash('sha256', json_encode(
            $this->payload($layout),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(Layout $layout): array
    {
        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        $widgetKeys = $this->widgetKeys($containers);
        $blocks = $this->blocksByKey($widgetKeys);

        return [
            'layout' => [
                'id' => $layout->getKey(),
                'key' => $layout->getAttribute('key'),
            ],
            'containers' => $this->normalizeContainers($containers, $blocks),
        ];
    }

    /**
     * @param  array<string, mixed>  $containers
     * @return array<int, string>
     */
    private function widgetKeys(array $containers): array
    {
        $widgetKeys = [];

        foreach ($containers as $container) {
            if (! is_array($container)) {
                continue;
            }

            foreach (LayoutBlockData::fromContainer($container) as $block) {
                $widgetKey = LayoutBlockData::key($block);
                if ($widgetKey === null) {
                    continue;
                }

                $widgetKeys[] = $widgetKey;
            }
        }

        return array_values(array_unique($widgetKeys));
    }

    /**
     * @param  array<int, string>  $widgetKeys
     * @return array<string, Widget>
     */
    private function blocksByKey(array $widgetKeys): array
    {
        if ($widgetKeys === []) {
            return [];
        }

        /** @var EloquentCollection<int, Widget> $blocks */
        $blocks = Widget::query()
            ->with('type')
            ->whereIn('key', $widgetKeys)
            ->get();

        return $blocks->keyBy('key')->all();
    }

    /**
     * @param  array<string, mixed>  $containers
     * @param  array<string, Widget>  $blocks
     * @return array<int, array<string, mixed>>
     */
    private function normalizeContainers(array $containers, array $blocks): array
    {
        $normalizedContainers = [];

        foreach ($containers as $containerKey => $container) {
            if (! is_array($container)) {
                continue;
            }

            $normalizedContainers[] = [
                'key' => $containerKey,
                'colspan' => $this->colspan($container),
                'widgets' => $this->normalizeBlocks($container['widgets'] ?? [], $blocks),
            ];
        }

        return $normalizedContainers;
    }

    /**
     * @param  array<string, mixed>  $container
     */
    private function colspan(array $container): int
    {
        $colspan = (int) ($container['meta']['colspan'] ?? 12);

        return min(12, max(1, $colspan));
    }

    /**
     * @param  array<string, Widget>  $blocks
     * @return array<int, array<string, mixed>>
     */
    private function normalizeBlocks(mixed $containerBlocks, array $blocks): array
    {
        $normalizedBlocks = [];

        foreach (LayoutBlockData::normalizeMany($containerBlocks) as $containerBlock) {
            $widgetKey = LayoutBlockData::key($containerBlock);
            if ($widgetKey === null) {
                continue;
            }

            $block = $blocks[$widgetKey] ?? null;

            $normalizedBlocks[] = [
                'key' => $widgetKey,
                'occurrence' => LayoutBlockData::occurrence($containerBlock),
                'name' => $block?->name,
                'icon' => $block?->admin['icon'] ?? $block?->type?->admin['icon'] ?? null,
                'type_name' => $block?->type?->name,
                'type_icon' => $block?->type?->admin['icon'] ?? null,
                'meta_name' => $containerBlock['meta']['name'] ?? null,
            ];
        }

        return $normalizedBlocks;
    }
}
