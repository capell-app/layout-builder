<?php

declare(strict_types=1);

?>

@php
    use Capell\Core\Enums\AssetComponentEnum;
    use Capell\Core\Facades\CapellCore;
    use Capell\Frontend\CapellFrontendManager;
    use Capell\Frontend\Facades\CapellFrontend;
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
    'maxWidth' => $widget->meta['max_width'] ?? ($widget->type->meta['max_width'] ?? false),
    'withChildCount' => $widget->meta['with_child_count'] ?? ($widget->type->meta['with_child_count'] ?? false),
    'withImage' => $widget->meta['with_image'] ?? ($widget->type->meta['with_image'] ?? true),
    'withParent' => $widget->meta['with_parent'] ?? ($widget->type->meta['with_parent'] ?? false),
    'withDate' => $widget->meta['with_date'] ?? ($widget->type->meta['with_date'] ?? true),
    'withSummary' => $widget->meta['with_summary'] ?? ($widget->type->meta['with_summary'] ?? true),
    'spacing' => $widget->meta['spacing'] ?? ($widget->type->meta['spacing'] ?? true),
    'columns' => $widget->meta['columns'] ?? ($widget->type->meta['columns'] ?? null),
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
            :color-scheme="$colorScheme"
            :presenter="$widget->type->meta['content_presenter'] ?? null"
            :title="$widget->translation->title"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
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
                    :component="CapellFrontend::getAsset($asset['asset_type'])->component"
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
    @endif
</x-capell-layout::widget.wrapper>

<?php
