<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;

$theme = Frontend::theme();
?>

@php
    use Capell\Core\Enums\AssetComponentEnum;
    use Capell\Core\Facades\CapellCore;
@endphp

@props([
    'color' => $widget->getMeta('color', 'dark'),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'total' => $widget->assets->isNotEmpty() ? $widget->assets->count() : 1,
    'widget',
    'widgetIndex',
    'maxWidth' => (bool) $widget->getMeta('max_width'),
    'withChildCount' => (bool) $widget->getMeta('with_child_count'),
    'withImage' => (bool) $widget->getMeta('with_image', true),
    'withParent' => (bool) $widget->getMeta('with_parent'),
    'withDate' => (bool) $widget->getMeta('with_date', true),
    'withSummary' => (bool) $widget->getMeta('with_summary', true),
    'spacing' => (bool) $widget->getMeta('spacing', true),
    'columns' => $widget->getMeta('columns'),
])
<x-capell-layout::widget.wrapper
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
            :muted="in_array($containerKey, $theme->secondary_containers)"
            :title="$widget->translation->title"
            :text-align="$widget->getMeta('align')"
            :heading-style="$widget->getMeta('heading_style')"
        />
    @endif

    @if ($widget->assets->isNotEmpty())
        <div
            @if ($columns)
                style="--columns: {{ $columns === 0 ? $widget->assets->count() : $columns }};"
            @endif
            @if ($maxWidth && ! in_array($maxWidth, ['none', 'sm', 'md', 'lg', 'xl'], true)) style="--max-max-width: {{ $maxWidth }};" @endif
            @class([
                'grid',
                'mx-auto' => $maxWidth,
                $maxWidth ? match ($maxWidth) {
                    'none' => 'max-w-none',
                    'sm' => 'max-w-sm',
                    'md' => 'max-w-md',
                    'lg' => 'max-w-lg',
                    'xl' => 'max-w-xl',
                    '2xl' => 'max-w-2xl',
                    '3xl' => 'max-w-3xl',
                    'max-w-[var(--max-max-width)]' => true,
                } : '',
                'gap-x-8 gap-y-6 lg:gap-x-10 lg:gap-y-10' => $spacing && $spacing !== 'none',
                'md:grid-cols-[repeat(var(--columns),minmax(0,1fr))]' => $columns,
                'md:grid-cols-2' => $total >= 2 && (! $columns && $columns !== 0),
                'lg:grid-cols-3' => $total >= 3 && (! $columns && $columns !== 0),
                '2xl:grid-cols-4' => $total > 7 && (! $columns && $columns !== 0),
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
                    class="widget-asset"
                />
            @endforeach
        </div>
    @endif
</x-capell-layout::widget.wrapper>

<?php
