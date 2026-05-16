<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Contracts\LayoutSidebarElementContributor;
use Capell\LayoutBuilder\Data\LayoutSidebarElementData;
use Capell\LayoutBuilder\Models\Element;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(Layout $layout)
 */
class ApplyLayoutSidebarElementContributionsAction
{
    use AsObject;

    public function handle(Layout $layout): void
    {
        $containers = $layout->getAttribute('containers');

        if (! is_array($containers)) {
            $containers = [];
        }

        if (! isset($containers['sidebar']) || ! is_array($containers['sidebar'])) {
            $containers['sidebar'] = $this->defaultSidebarContainer();
        }

        $sidebarElements = $containers['sidebar']['elements'] ?? [];
        $sidebarElements = is_array($sidebarElements) ? $sidebarElements : [];

        $sidebarElementKeys = $this->elementKeys($sidebarElements);

        foreach ($this->contributedElements($layout) as $sidebarElement) {
            if (in_array($sidebarElement->elementKey, $sidebarElementKeys, true)) {
                continue;
            }

            if (! Element::query()->where('key', $sidebarElement->elementKey)->exists()) {
                continue;
            }

            $sidebarElements[] = $sidebarElement->toLayoutElement();
            $sidebarElementKeys[] = $sidebarElement->elementKey;
        }

        $containers['sidebar']['elements'] = $sidebarElements;

        $layout->update([
            'containers' => $containers,
            'elements' => $this->elementKeys(
                collect($containers)
                    ->flatMap(fn (mixed $container): array => is_array($container) && is_array($container['elements'] ?? null) ? $container['elements'] : [])
                    ->all(),
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultSidebarContainer(): array
    {
        return [
            'meta' => [
                'colspan' => 3,
                'override_columns' => 1,
                'container' => 'full',
                'padding' => ['md'],
                'html_class' => 'sidebar-sticky space-y-8',
            ],
            'elements' => [],
        ];
    }

    /**
     * @return array<int, LayoutSidebarElementData>
     */
    private function contributedElements(Layout $layout): array
    {
        $layoutKey = (string) $layout->getAttribute('key');
        $elements = [];

        foreach (app()->tagged(LayoutSidebarElementContributor::TAG) as $contributor) {
            if (! $contributor instanceof LayoutSidebarElementContributor) {
                continue;
            }

            foreach ($contributor->sidebarElements() as $sidebarElement) {
                if (! $sidebarElement->appliesTo($layoutKey)) {
                    continue;
                }

                $elements[] = $sidebarElement;
            }
        }

        return $elements;
    }

    /**
     * @param  array<int, mixed>  $elements
     * @return array<int, string>
     */
    private function elementKeys(array $elements): array
    {
        return collect($elements)
            ->map(fn (mixed $element): ?string => is_array($element) ? ($element['element_key'] ?? null) : null)
            ->filter(fn (?string $elementKey): bool => is_string($elementKey) && $elementKey !== '')
            ->unique()
            ->values()
            ->all();
    }
}
