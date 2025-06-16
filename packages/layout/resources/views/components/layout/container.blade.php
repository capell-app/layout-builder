<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;
    use Capell\Frontend\Services\Loader\LayoutLoader;
@endphp

@props([
    'colspan' => 12,
    'container',
    'containerKey',
    'containerIndex',
    'layout',
    'spacing' => $container['meta']['spacing'] ?? null,
    'padding' => $container['meta']['padding'] ?? [],
    'margin' => $container['meta']['margin'] ?? [],
    'htmlClass' => '',
    'previousColspan' => null,
    'pageSlot' => null,
])
@php
    if (! empty($container['meta']['html_class'])) {
        $htmlClass .= ' '.$container['meta']['html_class'];
    }

    if ($containerKey === 'sidebar') {
        $htmlClass .= ' sidebar sidebar-sticky space-y-10 pt-10 pb-20';
    }
@endphp

@if ($colspan === 12 && $previousColspan && $previousColspan !== 12)
    {{-- format-ignore-start --}}</div>
</div>{{-- format-ignore-end --}}
@endif

@if ($colspan !== 12)
    @if (! $previousColspan || $previousColspan === 12)
        {{-- format-ignore-start --}}
        <div class="container flex">
            <div class="flex w-full flex-col gap-x-12 lg:grid lg:grid-cols-12 xl:gap-x-16">
                {{-- format-ignore-end --}}
    @endif

    {{-- format-ignore-start --}}
                <div @class([
        'lg:col-span-1' => $colspan === 1,
        'lg:col-span-2' => $colspan === 2,
        'lg:col-span-3' => $colspan === 3,
        'lg:col-span-4' => $colspan === 4,
        'lg:col-span-5' => $colspan === 5,
        'lg:col-span-6' => $colspan === 6,
        'lg:col-span-7' => $colspan === 7,
        'lg:col-span-8' => $colspan === 8,
        'lg:col-span-9' => $colspan === 9,
        'lg:col-span-10' => $colspan === 10,
        'lg:col-span-11' => $colspan === 11,
        'lg:col-span-12' => $colspan === 12,
    ])>
                    @if (!empty($container['meta']['container_inner']))
                        <div class="container-inner relative">
                            @endif
                            {{-- format-ignore-end --}}
@endif

<div
    @class([
        'layout-container',
        $htmlClass => (bool) $htmlClass,
        'space-y-4' => $spacing === 'tight',
        'space-y-2' => $spacing === 'normal',
        'space-y-10' => $spacing === 'loose',
        'py-4' => in_array('sm', $padding, true),
        'pt-4' => in_array('t-sm', $padding, true),
        'pb-4' => in_array('b-sm', $padding, true),
        'py-8' => in_array('md', $padding, true),
        'pt-8' => in_array('t-md', $padding, true),
        'pb-8' => in_array('b-md', $padding, true),
        'py-10' => in_array('lg', $padding, true),
        'pt-10' => in_array('t-lg', $padding, true),
        'pb-10' => in_array('b-lg', $padding, true),
        'pt-20' => in_array('t-xl', $padding, true),
        'pb-20' => in_array('b-xl', $padding, true),
        'my-4' => in_array('sm', $margin, true),
        'mt-4' => in_array('t-sm', $margin, true),
        'mb-4' => in_array('b-sm', $margin, true),
        'my-6 lg:my-10' => in_array('md', $margin, true),
        'mt-6' => in_array('t-md', $margin, true),
        'mb-6' => in_array('b-md', $margin, true),
        'my-10' => in_array('lg', $margin, true),
        'mt-10' => in_array('t-lg', $margin, true),
        'mb-10' => in_array('b-lg', $margin, true),
        'm-20' => in_array('xl', $margin, true),
        'mt-20' => in_array('t-xl', $margin, true),
        'mb-20' => in_array('b-xl', $margin, true),
    ])
>
    @foreach ($container['widgets'] as $widgetIndex => $widgetData)
        @php
            $widget = Frontend::getContainerWidget(
                $containerKey,
                $widgetData['widget_key'],
                $widgetData['occurrence'] ?? 1
            );

            $type = $widget->getMetaComponentType();

            $component = $widget->getComponent();

            if (! $component) {
                continue;
            }
        @endphp

        {!! config('app.debug') ? "<!-- {$widget->key} Widget ({$widget->id}) - {$component} -->" : '' !!}

        <x-capell::layout.widget
            :$component
            :$container
            :containerColspan="$colspan"
            :$containerKey
            :$containerIndex
            :$loop
            :$type
            :$widget
            :$widgetIndex
            :$widgetData
            :pageSlot="$pageSlot"
        />
    @endforeach
</div>

@if ($colspan !== 12)
    {{-- format-ignore-start --}}
                                @if (!empty($container['meta']['container_inner']))
                        </div>
                    @endif
                </div>
                {{-- format-ignore-end --}}
@endif

@if ($previousColspan && $previousColspan !== 12)
    {{-- format-ignore-start --}}
            </div>
        </div>
        {{-- format-ignore-end --}}
@endif

<?php
