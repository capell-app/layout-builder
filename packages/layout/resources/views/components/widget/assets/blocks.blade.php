<?php

declare(strict_types=1);

?>

@php
    use Capell\Core\Enums\AssetComponentEnum;
                use Capell\Core\Facades\CapellCore;
@endphp

@props([
'colorScheme' => $widget->meta['color_scheme'] ?? 'dark',
'container',
'containerKey',
'containerWidth' => null,
'loop',
'total' => $widget->assets->isNotEmpty() ? $widget->assets->count() : 1,
'widget',
'widgetIndex',
'withChildCount' => $widget->meta['with_child_count'] ?? ($widget->type->meta['with_child_count'] ?? false),
'withImage' => $widget->meta['with_image'] ?? ($widget->type->meta['with_image'] ?? true),
'withParent' => $widget->meta['with_parent'] ?? ($widget->type->meta['with_parent'] ?? false),
'withDate' => $widget->meta['with_date'] ?? ($widget->type->meta['with_date'] ?? true),
'withSummary' => $widget->meta['with_summary'] ?? ($widget->type->meta['with_summary'] ?? true),
'spacing' => $widget->meta['spacing'] ?? ($widget->type->meta['spacing'] ?? true),
'columns' => $widget->meta['columns'] ?? ($widget->type->meta['columns'] ?? null),
])

@capture($extendedBackground, $color, $position)
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
@endcapture

<x-capell-layout::widget.wrapper
    class="widget-blocks relative"
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
            :color-scheme="$colorScheme"
            :title="$widget->translation->title"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
        />
    @endif

    @if ($widget->assets->isNotEmpty())
        <div>
            @if ($color = $widget->assets->first()->asset->meta['color'] ?? null)
                {{ $extendedBackground($color, 'left') }}
            @endif

            <div
                @if ($columns === 0) style="--columns: {{ $widget->assets->count() }};" @endif
                @class([
                'grid',
                'gap-x-8 gap-y-6 lg:gap-x-10 lg:gap-y-10' => $spacing && $spacing !== 'none',
                'sm:grid-cols-2' => $columns === 0 && $widget->assets->count() > 2,
                'md:grid-cols-[repeat(var(--columns),minmax(0,1fr))]' => $columns === 0,
                'md:grid-cols-[repeat($columns,minmax(0,1fr))]' => $columns && $columns !== 0,
                'md:grid-cols-2' => $total >= 2 && (! $columns && $columns !== 0),
                'lg:grid-cols-3' => $total >= 3 && (! $columns && $columns !== 0),
                '2xl:grid-cols-4' => $total > 7 && (! $columns && $columns !== 0),
                ])
            >
                @foreach ($widget->assets as $asset)
                    <x-dynamic-component
                        :component="app(\Capell\Frontend\Contracts\AssetsRegistryInterface::class)->getAsset($asset['asset_type'])->component"
                        :component-item="$widget->meta['component_item'] ?? AssetComponentEnum::Card->value"
                        :$container
                        :$containerKey
                        :$loop
                        :asset="$asset->asset"
                        :with-child-count="$withChildCount"
                        :with-date="$withDate"
                        :with-image="$withImage"
                        :with-parent="$withParent"
                        :with-summary="$withSummary"
                    />
                @endforeach
            </div>
            @if ($color = $widget->assets->last()->asset->meta['color'] ?? null)
                {{ $extendedBackground($color, 'right') }}
            @endif
        </div>
    @endif
</x-capell-layout::widget.wrapper>

<?php
