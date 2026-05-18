<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\LayoutPreviews;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Models\Block;
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

        $blockKeys = $this->blockKeys($containers);
        $blocks = $this->blocksByKey($blockKeys);

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
    private function blockKeys(array $containers): array
    {
        $blockKeys = [];

        foreach ($containers as $container) {
            if (! is_array($container)) {
                continue;
            }

            foreach (LayoutBlockData::normalizeMany($container['blocks'] ?? []) as $block) {
                $blockKey = LayoutBlockData::key($block);
                if ($blockKey === null) {
                    continue;
                }

                $blockKeys[] = $blockKey;
            }
        }

        return array_values(array_unique($blockKeys));
    }

    /**
     * @param  array<int, string>  $blockKeys
     * @return array<string, Block>
     */
    private function blocksByKey(array $blockKeys): array
    {
        if ($blockKeys === []) {
            return [];
        }

        /** @var EloquentCollection<int, Block> $blocks */
        $blocks = Block::query()
            ->with('type')
            ->whereIn('key', $blockKeys)
            ->get();

        return $blocks->keyBy('key')->all();
    }

    /**
     * @param  array<string, mixed>  $containers
     * @param  array<string, Block>  $blocks
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
                'blocks' => $this->normalizeBlocks($container['blocks'] ?? [], $blocks),
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
     * @param  array<string, Block>  $blocks
     * @return array<int, array<string, mixed>>
     */
    private function normalizeBlocks(mixed $containerBlocks, array $blocks): array
    {
        $normalizedBlocks = [];

        foreach (LayoutBlockData::normalizeMany($containerBlocks) as $containerBlock) {
            $blockKey = LayoutBlockData::key($containerBlock);
            if ($blockKey === null) {
                continue;
            }

            $block = $blocks[$blockKey] ?? null;

            $normalizedBlocks[] = [
                'key' => $blockKey,
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
