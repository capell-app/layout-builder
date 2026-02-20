@props([
'layout',
'containerClass' => null,
'mainClass' => null,
'mainContainerClass' => null,
'pageSlot' => null,
'page',
'theme' => [],
])
<main
    id="main"
    @class([
    'relative z-0 flex min-h-full flex-1 flex-col overflow-x-hidden lg:!min-h-0',
    $theme['meta']['main_class'] ?? '',
    $mainClass ?? '',
    ])
>
    <div
        @class([
        'grow',
        $mainContainerClass => (bool) $mainContainerClass,
        ])
    >
        @php
            $previousColspan = null;
                                    $slotRendered = false;
        @endphp

        @if ($layout->containers)
            @foreach ($layout->containers as $containerKey => $container)
                {{-- format-ignore-start --}}
                @php
                    $widgets = collect($container['widgets'])
                        ->map(
                            fn ($widgetData): ?\Capell\Layout\Models\Widget => $layout->layoutWidgets->firstWhere(
                                'key',
                                $widgetData['widget_key'],
                            ),
                        )
                        ->filter();

                    if ($widgets->isEmpty()) {
                        continue;
                    }

                    if (! $slotRendered) {
                        $hasSlotWidget = $widgets->contains(
                            fn (\Capell\Layout\Models\Widget $widget) => isset($widget->meta['type']) &&
                                $widget->meta['type'] === \Capell\Layout\Models\Widget::COMPONENT_SLOT,
                        );

                        if ($hasSlotWidget) {
                            $slotRendered = true;
                        }
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
                <x-capell-layout::layout.container
                    :$container
                    :$containerKey
                    :$layout
                    :containerIndex="$loop->index"
                    :colspan="$colspan"
                    :column-start="$columnStart"
                    :htmlClass="$htmlClass"
                    :pageSlot="$hasSlotWidget ? $pageSlot : null"
                    :previousColspan="$previousColspan"
                />

                @php
                    $previousColspan += $colspan;
                    if ($columnStart) {
                        $previousColspan += $columnStart - 1;
                    }
                    $previousColspan = $previousColspan >= 12 ? 0 : $previousColspan;
                @endphp
                {{-- format-ignore-end --}}
            @endforeach
        @endif

        {{-- format-ignore-start --}}
        @if ($previousColspan && $previousColspan !== 12)
    </div>
    </div>
    @endif
    {{-- format-ignore-end --}}

        @if ($pageSlot && ! $slotRendered)
            {{ $pageSlot }}
            @php
                $slotRendered = true;
            @endphp
        @endif
    </div>
</main>
