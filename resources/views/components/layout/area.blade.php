@php
    use Capell\LayoutBuilder\Actions\ResolveLayoutAreaContainersAction;
    use Capell\LayoutBuilder\Models\Widget;
    use Capell\LayoutBuilder\Support\CapellLayoutManager;
    use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
    use Capell\LayoutBuilder\Support\LayoutWidgetData;
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
            $layoutWidgets = collect($container['widgets'] ?? $container ?? [])
                ->map(static fn (mixed $widgetData): array => LayoutWidgetData::normalize($widgetData))
                ->filter(static fn (array $widgetData): bool => LayoutWidgetData::key($widgetData) !== null)
                ->map(static fn (array $widgetData): ?Widget => CapellLayoutManager::getStoredContainerWidget(
                    (string) $containerKey,
                    (string) LayoutWidgetData::key($widgetData),
                    LayoutWidgetData::occurrence($widgetData),
                ))
                ->filter();

            if ($layoutWidgets->isEmpty()) {
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
