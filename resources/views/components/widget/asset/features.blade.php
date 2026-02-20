<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;

$theme = Frontend::theme();
?>

@php
    use Capell\Core\Enums\AssetComponentEnum;
    use Capell\Core\Facades\CapellCore;
    use Capell\Core\Models\Page;
@endphp

@props([
    'color' => $widget->meta['color'] ?? 'dark',
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
])

@capture($assetBlock, $widgetAsset, $column)
    @php
        $linkedPage = $widgetAsset->asset instanceof Page ? $widgetAsset->asset : $widgetAsset->asset->linkedPage;
    @endphp

    <div
        @class([
            'widget-features-item flex items-start gap-x-4 pt-1',
            'lg:flex-row-reverse lg:text-right' => $column === 1 && $widget->image,
        ])
    >
        @if ($widgetAsset->asset->meta['icon'] ?? false)
            <div
                class="bg-gray flex h-14 w-14 shrink-0 items-center justify-center rounded-full p-3 dark:bg-gray-600"
            >
                @capture($iconContent)
                    <x-capell::icon
                        :icon="$widgetAsset->asset->meta['icon']"
                        class="h-10 w-10 text-white"
                        loading="lazy"
                    />
                @endcapture

                @if ($linkedPage)
                    <a href="{{ $linkedPage->pageUrl->full_url }}">
                        {{ $iconContent() }}
                    </a>
                @else
                    {{ $iconContent() }}
                @endif
            </div>
        @elseif ($image = $widgetAsset->media->first() ?: $widgetAsset->asset->image)
            @capture($imageBlock)
                <x-capell::media
                    :media="$image"
                    :width="120"
                    :height="120"
                    :alt="$widgetAsset->asset->translation?->title"
                    fit="crop"
                    class="h-10 w-10 rounded-full object-cover object-center"
                    loading="lazy"
                />
            @endcapture

            @if ($linkedPage)
                <a href="{{ $linkedPage->pageUrl->full_url }}">
                    {{ $imageBlock() }}
                </a>
            @else
                {{ $imageBlock() }}
            @endif
        @endif
        @if ($widgetAsset->asset->translation)
            <x-capell::content
                :compact="true"
                :content="$widgetAsset->asset->translation->content"
                :content-type="$widgetAsset->asset->type->content_structure"
                :color="$color"
                :title="$widgetAsset->asset->translation->title"
                :heading-tag="$widgetAsset->asset->meta['heading_size'] ?? 'h3'"
                :heading-weight="$widgetAsset->asset->meta['heading_weight'] ?? 'medium'"
                :text-align="$widgetAsset->asset->meta['align'] ?? $widgetAsset->asset->type->meta['align'] ?? ('text-left' . ($column === 1 && $widget->image ? ' lg:text-right' : ''))"
                size="sm"
                class="prose-h3:mb-1 lg:prose-base lg:leading-snug"
            />
        @endif
    </div>
@endcapture

<x-capell-layout::widget.wrapper
    class="widget-assets widget-assets-features"
    :$container
    :$containerKey
    :$containerWidth
    container-class="space-y-6 md:space-y-10"
    :index="$loop->index"
    :$widget
>
    @if ($widget->translation)
        <x-capell::content
            :compact="true"
            :content="$widget->translation->content"
            :content-type="$widget->type->content_structure"
            :color="$color"
            :muted="in_array($containerKey, $theme->secondary_containers)"
            :title="$widget->translation->title"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
            :heading-style="($widget->meta['heading_style'] ?? null) ?: $widget->type->meta['heading_style'] ?? null"
            align="center"
        />
    @endif

    @if ($widget->assets->isNotEmpty())
        <div
            @class([
                'grid grid-cols-1 items-start gap-x-10 gap-y-6 md:grid-cols-2',
                'lg:grid-cols-3' => $widget->image,
            ])
        >
            @if ($widget->image)
                <div
                    class="flex min-h-full justify-center md:col-span-2 lg:order-2 lg:col-span-1"
                >
                    <x-capell::media
                        :media="$widget->image"
                        format="webp"
                        size="xl"
                        fit="fit"
                        loading="lazy"
                        class="object-cover"
                    />
                </div>
            @endif

            <div
                class="grid space-y-6 md:min-h-full md:auto-rows-fr lg:order-1 lg:space-y-8"
            >
                @foreach ($widget->assets->slice(0, ceil($widget->assets->count() / 2)) as $widgetAsset)
                    {{ $assetBlock($widgetAsset, 1) }}
                @endforeach
            </div>

            <div
                class="grid space-y-6 md:min-h-full md:auto-rows-fr lg:order-3 lg:space-y-8"
            >
                @foreach ($widget->assets->slice(ceil($widget->assets->count() / 2)) as $widgetAsset)
                    {{ $assetBlock($widgetAsset, 2) }}
                @endforeach
            </div>
        </div>
    @endif
</x-capell-layout::widget.wrapper>

<?php
