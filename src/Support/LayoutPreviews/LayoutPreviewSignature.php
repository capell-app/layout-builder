<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\LayoutPreviews;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Models\Element;
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

        $elementKeys = $this->elementKeys($containers);
        $elements = $this->elementsByKey($elementKeys);

        return [
            'layout' => [
                'id' => $layout->getKey(),
                'key' => $layout->getAttribute('key'),
            ],
            'containers' => $this->normalizeContainers($containers, $elements),
        ];
    }

    /**
     * @param  array<string, mixed>  $containers
     * @return array<int, string>
     */
    private function elementKeys(array $containers): array
    {
        $elementKeys = [];

        foreach ($containers as $container) {
            if (! is_array($container)) {
                continue;
            }

            $elements = $container['elements'] ?? [];

            if (! is_array($elements)) {
                continue;
            }

            foreach ($elements as $element) {
                if (! is_array($element)) {
                    continue;
                }

                if (! isset($element['element_key'])) {
                    continue;
                }

                $elementKeys[] = (string) $element['element_key'];
            }
        }

        return array_values(array_unique($elementKeys));
    }

    /**
     * @param  array<int, string>  $elementKeys
     * @return array<string, Element>
     */
    private function elementsByKey(array $elementKeys): array
    {
        if ($elementKeys === []) {
            return [];
        }

        /** @var EloquentCollection<int, Element> $elements */
        $elements = Element::query()
            ->with('type')
            ->whereIn('key', $elementKeys)
            ->get();

        return $elements->keyBy('key')->all();
    }

    /**
     * @param  array<string, mixed>  $containers
     * @param  array<string, Element>  $elements
     * @return array<int, array<string, mixed>>
     */
    private function normalizeContainers(array $containers, array $elements): array
    {
        $normalizedContainers = [];

        foreach ($containers as $containerKey => $container) {
            if (! is_array($container)) {
                continue;
            }

            $normalizedContainers[] = [
                'key' => $containerKey,
                'colspan' => $this->colspan($container),
                'elements' => $this->normalizeElements($container['elements'] ?? [], $elements),
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
     * @param  array<string, Element>  $elements
     * @return array<int, array<string, mixed>>
     */
    private function normalizeElements(mixed $containerElements, array $elements): array
    {
        if (! is_array($containerElements)) {
            return [];
        }

        $normalizedElements = [];

        foreach ($containerElements as $containerElement) {
            if (! is_array($containerElement)) {
                continue;
            }

            $elementKey = (string) ($containerElement['element_key'] ?? '');
            $element = $elements[$elementKey] ?? null;

            $normalizedElements[] = [
                'key' => $elementKey,
                'occurrence' => (int) ($containerElement['occurrence'] ?? 1),
                'name' => $element?->name,
                'icon' => $element?->admin['icon'] ?? $element?->type?->admin['icon'] ?? null,
                'type_name' => $element?->type?->name,
                'type_icon' => $element?->type?->admin['icon'] ?? null,
                'meta_name' => $containerElement['meta']['name'] ?? null,
            ];
        }

        return $normalizedElements;
    }
}
