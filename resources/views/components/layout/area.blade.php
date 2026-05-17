@php
    use Capell\LayoutBuilder\Actions\ResolveLayoutAreaContainersAction;
    use Capell\LayoutBuilder\Models\Element;
    use Capell\LayoutBuilder\Support\CapellLayoutManager;
    use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
    use Capell\LayoutBuilder\Support\LayoutElementData;
@endphp

@props([
    'area' => LayoutAreaRegistry::MAIN,
    'containerClass' => null,
    'layout' => null,
])

@php
    $previousColspan = null;
@endphp

@if ($layout?->containers)
    @foreach (ResolveLayoutAreaContainersAction::run($layout->containers, (string) $area) as $containerKey => $container)
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

            $colspan = (int) ($container['meta']['colspan'] ?? 12);
            $columnStart = (int) ($container['meta']['column_start'] ?? 0);
            $htmlClass = $container['meta']['html_class'] ?? '';

            if ($containerClass) {
                if (is_string($containerClass)) {
                    $htmlClass .= ' ' . $containerClass;
                } elseif (! empty($containerClass[$containerKey])) {
                    $htmlClass .= ' ' . $containerClass[$containerKey];
                }
            }
        @endphp

        @include('capell-layout-builder::components.layout.container', [
            'container' => $container,
            'containerKey' => (string) $containerKey,
            'layout' => $layout,
            'containerIndex' => $loop->index,
            'colspan' => $colspan,
            'columnStart' => $columnStart,
            'htmlClass' => $htmlClass,
            'pageSlot' => null,
            'previousColspan' => $previousColspan,
        ])

        @php
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
