<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;

$theme = Frontend::theme();
?>

@php
    use Capell\Core\Enums\AssetComponentEnum;
    use Capell\Core\Facades\CapellCore;
    use Capell\Frontend\Contracts\AssetsRegistryInterface;
@endphp

@props([
    'color' => $widget->getMeta('color', 'dark'),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'total' => $widget->assets->count(),
    'widget',
    'widgetIndex',
    'withChildCount' => (bool) $widget->getMeta('with_child_count'),
    'withImage' => (bool) $widget->getMeta('with_image', true),
    'withParent' => (bool) $widget->getMeta('with_parent'),
    'withDate' => (bool) $widget->getMeta('with_date', true),
    'withSummary' => (bool) $widget->getMeta('with_summary', true),
    'spacing' => $widget->getMeta('spacing', true),
    'columns' => (int) $widget->getMeta('columns'),
])

@capellBuffer($extendedBackground, $color, $position)
    <div
        @class([
            '-z-1 absolute top-0 h-full w-1/2',
            match ($position) {
                'left' => 'left-0',
                'right' => 'right-0',
            },
            match ($color) {
                'danger' => 'bg-danger',
                'dark-gray' => 'bg-dark-gray',
                'gray' => 'bg-gray',
                'info' => 'bg-info',
                'light-gray' => 'bg-light-gray',
                'primary' => 'bg-primary',
                'secondary' => 'bg-secondary',
                'success' => 'bg-success',
                'warning' => 'bg-warning',
                'white' => 'bg-white',
            },
        ])
    ></div>
@endcapellBuffer

<x-capell-layout::widget.wrapper
    class="widget-assets-blocks relative"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
    container-class="space-y-6 md:space-y-10"
>
    @if ($widget->translation)
        <x-capell::content
            :compact="true"
            :content="$widget->translation->content"
            :content-type="$widget->type->content_structure"
            :color="$color"
            :divider="$widget->getMeta('content_divider')"
            :muted="in_array($containerKey, $theme->secondary_containers)"
            :title="$widget->translation->title"
            :text-align="$widget->getMeta('align')"
            :heading-style="$widget->getMeta('heading_style')"
        />
    @endif

    @if ($widget->assets->isNotEmpty())
        <div>
            @if ($color = $widget->assets->first()->asset->getMeta('color'))
                {{ $extendedBackground($color, 'left') }}
            @endif

            <div
                style="--columns: {{ $columns ?: $total }}"
                @class([
                    'grid md:grid-cols-[repeat(var(--columns),minmax(0,1fr))]',
                    'gap-x-8 gap-y-6 lg:gap-x-10 lg:gap-y-10' => $spacing && $spacing !== 'none',
                    'sm:grid-cols-2' => $total >= 2 && $columns === 0,
                    'md:grid-cols-2' => $total >= 2 && $columns !== 0 && $total <= $columns,
                    'lg:grid-cols-4' => $total >= 4 && $columns !== 0 && $total <= $columns,
                    '2xl:grid-cols-6' => $total >= 6 && $columns !== 0 && $total <= $columns,
                ])
            >
                @foreach ($widget->assets as $asset)
                    <x-dynamic-component
                        :component="app(AssetsRegistryInterface::class)->getAsset($asset['asset_type'])->component"
                        :component-item="$widget->getMeta('component_item', AssetComponentEnum::Card->value)"
                        :$container
                        :$containerKey
                        :$loop
                        :asset="$asset->asset"
                        :with-child-count="$withChildCount"
                        :with-date="$withDate"
                        :with-image="$withImage"
                        :with-parent="$withParent"
                        :with-summary="$withSummary"
                        class="widget-block-item"
                    />
                @endforeach
            </div>
            @if ($color = $widget->assets->last()->asset->getMeta('color'))
                {{ $extendedBackground($color, 'right') }}
            @endif
        </div>
    @endif
</x-capell-layout::widget.wrapper>

<?php
