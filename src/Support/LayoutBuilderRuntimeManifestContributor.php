<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Models\Layout;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Contracts\FrontendRuntimeManifestContributor;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Capell\LayoutBuilder\Models\Widget;
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
        $widgetKeys = $this->layoutWidgetKeys($layout);

        if ($widgetKeys === []) {
            return;
        }

        $manifest->usesAlpine = true;
        $manifest->modules['layout-builder'] = true;

        if (! $this->layoutUsesLivewireBlocks($widgetKeys)) {
            return;
        }

        $manifest->usesLivewire = true;
        $manifest->usesIslands = true;
    }

    /**
     * @return list<string>
     */
    private function layoutWidgetKeys(?Layout $layout): array
    {
        if (! $layout instanceof Layout) {
            return [];
        }

        $widgetKeys = collect();
        $containers = $layout->containers;

        if (is_array($containers)) {
            foreach ($containers as $container) {
                if (! is_array($container)) {
                    continue;
                }

                $blocks = $container['widgets'] ?? [];

                if (! is_array($blocks)) {
                    continue;
                }

                $widgetKeys = $widgetKeys->merge(collect($blocks)->map(
                    fn (mixed $block): mixed => is_array($block) ? ($block['widget_key'] ?? $block['key'] ?? null) : $block,
                ));
            }
        }

        return array_values($widgetKeys
            ->filter(fn (mixed $widgetKey): bool => is_string($widgetKey) || is_numeric($widgetKey))
            ->map(fn (mixed $widgetKey): string => (string) $widgetKey)
            ->unique()
            ->values()
            ->all());
    }

    /**
     * @param  list<string>  $widgetKeys
     */
    private function layoutUsesLivewireBlocks(array $widgetKeys): bool
    {
        return Widget::query()
            ->with('type')
            ->whereIn('key', $widgetKeys)
            ->whereHas('type', fn (Builder $query): Builder => $query->enabled()->accessible())
            ->enabled()
            ->publishedDate()
            ->get()
            ->contains(fn (Model $block): bool => $block->getMetaComponentType() === 'livewire');
    }
}
