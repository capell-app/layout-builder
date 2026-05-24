<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Models\Layout;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Contracts\FrontendRuntimeManifestContributor;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Capell\LayoutBuilder\Models\Block;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class LayoutBuilderRuntimeManifestContributor implements FrontendRuntimeManifestContributor
{
    public function contribute(FrontendContextReader $context, FrontendRuntimeManifestData $manifest): void
    {
        if ($manifest->renderingStrategy !== RenderingStrategyEnum::BladeOnly) {
            return;
        }

        $layout = $context->layout();
        $blockKeys = $this->layoutBlockKeys($layout);

        if ($blockKeys === []) {
            return;
        }

        $manifest->usesAlpine = true;
        $manifest->modules['layout-builder'] = true;

        if (! $this->layoutUsesLivewireBlocks($blockKeys)) {
            return;
        }

        $manifest->usesLivewire = true;
        $manifest->usesIslands = true;
    }

    /**
     * @return list<string>
     */
    private function layoutBlockKeys(?Layout $layout): array
    {
        if (! $layout instanceof Layout) {
            return [];
        }

        $attributes = $layout->getAttributes();

        $blockKeys = collect(array_key_exists('blocks', $attributes) ? (array) $layout->getAttribute('blocks') : []);
        $containers = array_key_exists('containers', $attributes) ? $layout->getAttribute('containers') : null;

        if (is_array($containers)) {
            foreach ($containers as $container) {
                if (! is_array($container)) {
                    continue;
                }

                $blocks = $container['blocks'] ?? [];

                if (! is_array($blocks)) {
                    continue;
                }

                $blockKeys = $blockKeys->merge(collect($blocks)->map(
                    fn (mixed $block): mixed => is_array($block) ? ($block['block_key'] ?? $block['key'] ?? null) : $block,
                ));
            }
        }

        return $blockKeys
            ->filter(fn (mixed $blockKey): bool => is_string($blockKey) || is_numeric($blockKey))
            ->map(fn (mixed $blockKey): string => (string) $blockKey)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $blockKeys
     */
    private function layoutUsesLivewireBlocks(array $blockKeys): bool
    {
        return Block::query()
            ->with('type')
            ->whereIn('key', $blockKeys)
            ->whereHas('type', fn (Builder $query): Builder => $query->enabled()->accessible())
            ->enabled()
            ->publishedDate()
            ->get()
            ->contains(fn (Model $block): bool => $block->getMetaComponentType() === 'livewire');
    }
}
