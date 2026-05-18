@php
    use Capell\Frontend\Data\MainContentRenderHookData;
    use Capell\LayoutBuilder\Actions\ElementIsSlotAction;
    use Capell\LayoutBuilder\Actions\ResolveLayoutAreaContainersAction;
    use Capell\LayoutBuilder\Models\Element;
    use Capell\LayoutBuilder\Support\CapellLayoutManager;
    use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
    use Capell\LayoutBuilder\Support\LayoutElementData;

    /** @var MainContentRenderHookData $context */
    $previousColspan = null;
@endphp

@if ($context->layout?->containers)
    @foreach (ResolveLayoutAreaContainersAction::run($context->layout->containers, LayoutAreaRegistry::MAIN) as $containerKey => $container)
        @php
            $layoutElements = collect($container['elements'] ?? [])
                ->map(static fn (mixed $elementData): array => LayoutElementData::normalize($elementData))
                ->filter(static fn (array $elementData): bool => LayoutElementData::key($elementData) !== null)
                ->map(static fn (array $elementData): ?Element => CapellLayoutManager::getStoredContainerElement(
                    (string) $containerKey,
                    (string) LayoutElementData::key($elementData),
                    LayoutElementData::occurrence($elementData),
                ))
                ->filter();

            if ($layoutElements->isEmpty()) {
                continue;
            }

            $context->pageContentElementRendered = $context->pageContentElementRendered || collect($container['elements'] ?? [])
                ->map(static fn (mixed $elementData): array => LayoutElementData::normalize($elementData))
                ->contains(static fn (array $elementData): bool => LayoutElementData::key($elementData) === 'page-content');

            $hasSlotElement = ! $context->slotRendered && $layoutElements->contains(
                static fn (Element $layoutElement): bool => ElementIsSlotAction::run($layoutElement),
            );

            $colspan = (int) ($container['meta']['colspan'] ?? 12);
            $columnStart = (int) ($container['meta']['column_start'] ?? 0);
            $htmlClass = $container['meta']['html_class'] ?? '';

            if ($context->containerClass) {
                if (is_string($context->containerClass)) {
                    $htmlClass .= ' ' . $context->containerClass;
                } elseif (! empty($context->containerClass[$containerKey])) {
                    $htmlClass .= ' ' . $context->containerClass[$containerKey];
                }
            }
        @endphp

        @include('capell-layout-builder::components.layout.container', [
            'container' => $container,
            'containerKey' => (string) $containerKey,
            'layout' => $context->layout,
            'page' => $context->page,
            'containerIndex' => $loop->index,
            'colspan' => $colspan,
            'columnStart' => $columnStart,
            'htmlClass' => $htmlClass,
            'pageSlot' => $hasSlotElement ? $context->pageSlot : null,
            'previousColspan' => $previousColspan,
        ])

        @php
            if ($hasSlotElement && $context->pageSlot) {
                $context->slotRendered = true;
            }

            $previousColspan += $colspan;
            if ($columnStart) {
                $previousColspan += $columnStart - 1;
            }
            $previousColspan = $previousColspan >= 12 ? 0 : $previousColspan;
        @endphp
    @endforeach
@endif

@if ($previousColspan && $previousColspan !== 12)
    </div>
    </div>
@endif
