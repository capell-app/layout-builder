@props([
    'color',
    'column',
    'widget',
    'widgetAsset',
])

@php
    use Capell\Core\Contracts\Pageable;

    $linkedPage = $widgetAsset->asset instanceof Pageable ? $widgetAsset->asset : $widgetAsset->asset->linkedPage;
    $image = $widgetAsset->media->first() ?: $widgetAsset->asset->image;
@endphp

<div
    @class([
        'widget-features-item flex items-start gap-x-4 pt-1',
        'lg:flex-row-reverse lg:text-right' => (int) $column === 1 && $widget->image,
    ])
>
    @if ($widgetAsset->asset->getMeta('icon', false))
        <div
            class="bg-gray flex h-14 w-14 shrink-0 items-center justify-center rounded-full p-3 dark:bg-gray-600"
        >
            @if ($linkedPage)
                <a href="{{ $linkedPage->pageUrl->full_url }}">
                    <x-capell::icon
                        :icon="$widgetAsset->asset->getMeta('icon')"
                        class="h-10 w-10 text-white"
                        loading="lazy"
                    />
                </a>
            @else
                <x-capell::icon
                    :icon="$widgetAsset->asset->getMeta('icon')"
                    class="h-10 w-10 text-white"
                    loading="lazy"
                />
            @endif
        </div>
    @elseif ($image)
        @if ($linkedPage)
            <a href="{{ $linkedPage->pageUrl->full_url }}">
                <x-capell::media
                    :media="$image"
                    :width="120"
                    :height="120"
                    :alt="$widgetAsset->asset->translation?->title"
                    fit="crop"
                    class="h-10 w-10 rounded-full object-cover object-center"
                    loading="lazy"
                />
            </a>
        @else
            <x-capell::media
                :media="$image"
                :width="120"
                :height="120"
                :alt="$widgetAsset->asset->translation?->title"
                fit="crop"
                class="h-10 w-10 rounded-full object-cover object-center"
                loading="lazy"
            />
        @endif
    @endif

    @if ($widgetAsset->asset->translation)
        <x-capell::content
            :compact="true"
            :content="$widgetAsset->asset->translation->content"
            :content-type="$widgetAsset->asset->type->content_structure"
            :color="$color"
            :title="$widgetAsset->asset->translation->title"
            :heading-tag="$widgetAsset->asset->getMeta('heading_size', 'h3')"
            :heading-weight="$widgetAsset->asset->getMeta('heading_weight', 'medium')"
            :text-align="$widgetAsset->asset->getMeta('align') ?? $widgetAsset->asset->type->getMeta('align') ?? ('text-left' . ((int) $column === 1 && $widget->image ? ' lg:text-right' : ''))"
            size="sm"
            class="prose-h3:mb-1 lg:prose-base lg:leading-snug"
        />
    @endif
</div>
