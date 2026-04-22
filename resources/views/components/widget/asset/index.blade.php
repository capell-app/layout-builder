<?php

declare(strict_types=1);

?>

@php
    use Capell\Core\Enums\AssetComponentEnum;
    use Capell\Core\Facades\CapellCore;
    use Capell\Frontend\Contracts\AssetsRegistryInterface;
    use Capell\Frontend\Facades\Frontend;

    $theme = Frontend::theme();
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
    'maxWidth' => $widget->getMeta('max_width'),
    'withChildCount' => (bool) $widget->getMeta('with_child_count'),
    'withImage' => (bool) $widget->getMeta('with_image', true),
    'withParent' => (bool) $widget->getMeta('with_parent'),
    'withDate' => (bool) $widget->getMeta('with_date'),
    'withSummary' => (bool) $widget->getMeta('with_summary'),
    'spacing' => $widget->getMeta('spacing', true),
    'columns' => (int) $widget->getMeta('columns'),
    'headingSize' => $widget->getMeta('heading_size'),
    'imagePosition' => $widget->getMeta('image_position', 'left'),
])
<x-capell-mosaic::widget.wrapper
    class="widget-assets widget-assets-grid"
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
        <div
            style="--columns: {{ $columns ?: $total }}"
            @if ($maxWidth && ! in_array($maxWidth, ['none', 'sm', 'md', 'lg', 'xl'], true)) style="--max-max-width: {{ $maxWidth }};" @endif
            @class([
                'grid md:grid-cols-[repeat(var(--columns),minmax(0,1fr))]',
                'mx-auto' => $maxWidth,
                $maxWidth ? match ($maxWidth) {
                    'none' => 'max-w-none',
                    'sm' => 'max-w-sm',
                    'md' => 'max-w-md',
                    'lg' => 'max-w-lg',
                    'xl' => 'max-w-xl',
                    '2xl' => 'max-w-2xl',
                    '3xl' => 'max-w-3xl',
                    default => 'max-w-[var(--max-max-width)]',
                } : '',
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
                    :heading-size="$headingSize"
                    :image-position="$imagePosition"
                    :with-parent="$withParent"
                    :with-summary="$withSummary"
                    class="widget-asset"
                />
            @endforeach
        </div>
    @endif
</x-capell-mosaic::widget.wrapper>

<?php
