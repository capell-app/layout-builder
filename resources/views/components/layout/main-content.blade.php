@php
    use Capell\Frontend\Data\MainContentRenderHookData;
    use Capell\LayoutBuilder\Actions\WidgetIsSlotAction;
    use Capell\LayoutBuilder\Actions\ResolveLayoutAreaContainersAction;
    use Capell\LayoutBuilder\Models\Widget;
    use Capell\LayoutBuilder\Support\CapellLayoutManager;
    use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
    use Capell\LayoutBuilder\Support\LayoutWidgetData;

    /** @var MainContentRenderHookData $context */
    $previousColspan = null;
@endphp

@if ($context->layout?->containers)
    @foreach (ResolveLayoutAreaContainersAction::run($context->layout->containers, LayoutAreaRegistry::MAIN) as $containerKey => $container)
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

            $context->pageContentWidgetRendered = $context->pageContentWidgetRendered || collect($container['widgets'] ?? $container ?? [])
                ->map(static fn (mixed $widgetData): array => LayoutWidgetData::normalize($widgetData))
                ->contains(static fn (array $widgetData): bool => LayoutWidgetData::key($widgetData) === 'page-content');

            $hasSlotWidget = ! $context->slotRendered && $layoutWidgets->contains(
                static fn (Widget $layoutWidget): bool => WidgetIsSlotAction::run($layoutWidget),
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
            'pageSlot' => $hasSlotWidget ? $context->pageSlot : null,
            'previousColspan' => $previousColspan,
        ])

        @php
            if ($hasSlotWidget && $context->pageSlot) {
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
