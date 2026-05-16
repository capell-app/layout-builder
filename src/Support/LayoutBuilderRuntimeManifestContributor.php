<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Models\Layout;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Contracts\FrontendRuntimeManifestContributor;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Capell\LayoutBuilder\Models\Element;
use Illuminate\Database\Eloquent\Model;

final class LayoutBuilderRuntimeManifestContributor implements FrontendRuntimeManifestContributor
{
    public function contribute(FrontendContextReader $context, FrontendRuntimeManifestData $manifest): void
    {
        if ($manifest->renderingStrategy !== RenderingStrategyEnum::BladeOnly) {
            return;
        }

        $layout = $context->layout();
        $elementKeys = $this->layoutElementKeys($layout);

        if ($elementKeys === []) {
            return;
        }

        $manifest->usesAlpine = true;
        $manifest->modules['layout-builder'] = true;

        if (! $this->layoutUsesLivewireElements($elementKeys)) {
            return;
        }

        $manifest->usesLivewire = true;
        $manifest->usesIslands = true;
    }

    /**
     * @return list<string>
     */
    private function layoutElementKeys(?Layout $layout): array
    {
        if (! $layout instanceof Layout) {
            return [];
        }

        $elementKeys = collect((array) $layout->getAttribute('elements'));
        $containers = $layout->containers;

        if (is_array($containers)) {
            foreach ($containers as $container) {
                if (! is_array($container)) {
                    continue;
                }

                $elements = $container['elements'] ?? [];

                if (! is_array($elements)) {
                    continue;
                }

                $elementKeys = $elementKeys->merge(collect($elements)->map(
                    fn (mixed $element): mixed => is_array($element) ? ($element['element_key'] ?? $element['key'] ?? null) : $element,
                ));
            }
        }

        return $elementKeys
            ->filter(fn (mixed $elementKey): bool => is_string($elementKey) || is_numeric($elementKey))
            ->map(fn (mixed $elementKey): string => (string) $elementKey)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $elementKeys
     */
    private function layoutUsesLivewireElements(array $elementKeys): bool
    {
        return Element::query()
            ->with('type')
            ->whereIn('key', $elementKeys)
            ->get()
            ->contains(fn (Model $element): bool => method_exists($element, 'getMetaComponentType') && $element->getMetaComponentType() === 'livewire');
    }
}
