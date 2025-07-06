<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;
    use Capell\Frontend\Services\Loader\LayoutLoader;
    use Capell\Layout\Models\Widget;
    use Illuminate\Support\Collection;

    $layout = Frontend::getLayout();
    $theme = Frontend::getTheme();

    $previousColspan = null;
@endphp

@props([
    'containerClass' => null,
    'footer' => null,
    'header' => null,
    'mainClass' => null,
    'mainContainerClass' => null,
])
<div
    {{ $attributes->merge(['class' => 'flex flex-col min-h-screen bg-white dark:bg-gray-900']) }}
>
    <a
        class="sr-only"
        href="#main"
    >
        {{ __('capell-frontend::generic.skip_link') }}
    </a>

    @if ($header)
        {{ $header }}
    @elseif ($header === null && ! empty($theme['meta']['header']))
        @if (! empty($theme['meta']['header_file']))
            <x-dynamic-component :component="$theme['meta']['header_file']" />
        @else
            <x-capell::header.index />
        @endif
    @endif

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

            @foreach ($layout->containers as $containerKey => $container)
                @php
                    $widgets = collect($container['widgets'])
                        ->map(
                            fn ($widgetData): ?Widget => $layout->layoutWidgets->firstWhere(
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
                            fn (Widget $widget) => isset($widget->meta['type']) &&
                                $widget->meta['type'] === Widget::COMPONENT_SLOT,
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
                            $htmlClass .= ' '.$containerClass;
                        } elseif (! empty($containerClass[$containerKey])) {
                            $htmlClass .= ' '.$containerClass[$containerKey];
                        }
                    }
                @endphp

                <x-capell::layout.container
                    :$container
                    :$containerKey
                    :containerIndex="$loop->index"
                    :colspan="$colspan"
                    :column-start="$columnStart"
                    :htmlClass="$htmlClass"
                    :pageSlot="$hasSlotWidget ? $slot : null"
                    :previousColspan="$previousColspan"
                />

                @php
                    $previousColspan += $colspan;
                    if ($columnStart) {
                        $previousColspan += $columnStart - 1;
                    }
                    $previousColspan = $previousColspan >= 12 ? 0 : $previousColspan;
                @endphp
            @endforeach

            {{-- format-ignore-start --}}
            @if ($previousColspan && $previousColspan !== 12)
                    </div>
                </div>
            @endif
            {{-- format-ignore-end --}}

            @if ($slot && ! $slotRendered)
                {{ $slot }}
                @php
                    $slotRendered = true;
                @endphp
            @endif
        </div>
    </main>

    @if ($footer)
        {{ $footer }}
    @elseif ($footer === null && ! empty($theme['meta']['footer']))
        <x-dynamic-component
            :component="$theme['meta']['footer_file'] ?? 'capell::footer'"
        />
    @endif
</div>

<?php
