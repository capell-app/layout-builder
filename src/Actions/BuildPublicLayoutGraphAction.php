<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Contracts\PublicElementPayloadResolver;
use Capell\LayoutBuilder\Data\PublicLayoutContainerData;
use Capell\LayoutBuilder\Data\PublicLayoutElementData;
use Capell\LayoutBuilder\Data\PublicLayoutGraphData;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Lorisleiva\Actions\Concerns\AsObject;

class BuildPublicLayoutGraphAction
{
    use AsObject;

    /**
     * @param  array<int, string>  $containers
     */
    public function handle(Layout $layout, Page $page, Language $language, array $containers = [], bool $includeHtml = false): PublicLayoutGraphData
    {
        $layoutContainers = $layout->getAttribute('containers');
        $layoutContainers = is_array($layoutContainers) ? $layoutContainers : [];

        $selectedContainers = $this->selectedContainers($containers);
        $loader = resolve(LayoutLoader::class);

        $loader->preloadLayoutElements($layout, $language, $page, $selectedContainers);

        return new PublicLayoutGraphData(
            key: $layout->key,
            meta: [],
            containers: collect($layoutContainers)
                ->filter(fn (mixed $container, string|int $containerKey): bool => $this->shouldIncludeContainer((string) $containerKey, $selectedContainers))
                ->map(fn (mixed $container, string|int $containerKey): PublicLayoutContainerData => $this->containerData(
                    layout: $layout,
                    page: $page,
                    language: $language,
                    loader: $loader,
                    containerKey: (string) $containerKey,
                    container: is_array($container) ? $container : [],
                    includeHtml: $includeHtml,
                    selectedContainers: $selectedContainers,
                ))
                ->values()
                ->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $container
     * @param  array<int, string>|null  $selectedContainers
     */
    private function containerData(
        Layout $layout,
        Page $page,
        Language $language,
        LayoutLoader $loader,
        string $containerKey,
        array $container,
        bool $includeHtml,
        ?array $selectedContainers,
    ): PublicLayoutContainerData {
        $elements = $container['elements'] ?? [];
        $elements = is_array($elements) ? $elements : [];

        return new PublicLayoutContainerData(
            key: $containerKey,
            meta: [],
            elements: collect($elements)
                ->map(fn (mixed $elementData): ?PublicLayoutElementData => $this->elementData(
                    layout: $layout,
                    page: $page,
                    language: $language,
                    loader: $loader,
                    containerKey: $containerKey,
                    elementData: is_array($elementData) ? $elementData : [],
                    includeHtml: $includeHtml,
                    selectedContainers: $selectedContainers,
                ))
                ->filter()
                ->values()
                ->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $elementData
     * @param  array<int, string>|null  $selectedContainers
     */
    private function elementData(
        Layout $layout,
        Page $page,
        Language $language,
        LayoutLoader $loader,
        string $containerKey,
        array $elementData,
        bool $includeHtml,
        ?array $selectedContainers,
    ): ?PublicLayoutElementData {
        $elementKey = $elementData['element_key'] ?? null;
        if (! is_string($elementKey) || $elementKey === '') {
            return null;
        }

        $occurrence = (int) ($elementData['occurrence'] ?? 1);
        $element = CapellLayoutManager::getStoredContainerElement($containerKey, $elementKey, $occurrence)
            ?? $loader->getLayoutElement($layout, $elementKey, $language, $page, $containerKey, $occurrence, $selectedContainers);

        if (! $element instanceof Element) {
            return null;
        }

        $resolver = resolve(PublicElementPayloadResolver::class);

        return new PublicLayoutElementData(
            key: $elementKey,
            occurrence: $occurrence,
            type: $element->type?->key,
            data: $resolver->data($element, $page, $language, $containerKey, $occurrence),
            html: $includeHtml ? $resolver->html($element, $page, $language, $containerKey, $occurrence) : null,
        );
    }

    /**
     * @param  array<int, string>  $containers
     * @return array<int, string>|null
     */
    private function selectedContainers(array $containers): ?array
    {
        if ($containers === [] || in_array('*', $containers, true)) {
            return null;
        }

        return array_values(array_unique($containers));
    }

    /**
     * @param  array<int, string>|null  $selectedContainers
     */
    private function shouldIncludeContainer(string $containerKey, ?array $selectedContainers): bool
    {
        return $selectedContainers === null || in_array($containerKey, $selectedContainers, true);
    }
}
