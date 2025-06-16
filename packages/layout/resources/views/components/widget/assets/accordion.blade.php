<?php

declare(strict_types=1);

?>

@php
    use Capell\Core\Enums\ResourceComponentEnum;
    use Capell\Frontend\Facades\Frontend;
@endphp

@props([
    'columns' => $container['meta']['override_columns'] ?? ($widget->meta['columns'] ?? 3),
    'componentItem' => ($widget->meta['component_item'] ?? ResourceComponentEnum::Card->value),
    'container',
    'containerKey',
    'hideContent' => $widgetData['meta']['hide_content'] ?? false,
    'index',
    'loop',
    'size' => $widget->meta['size'] ?? null,
    'spacing' => $widget->meta['spacing'] ?? 'lg',
    'site' => Frontend::getSite(),
    'theme' => Frontend::getTheme(),
    'widget',
])
<x-capell::widget.wrapper
    class="widget-content-grid widget-content-accordion space-y-6"
    :$containerKey
    :$container
    :index="$loop->index"
    :$widget
>
    @if ($widget->translation)
        <x-capell::content
            class="mb-4"
            :compact="true"
            :$containerKey
            :content="$widget->translation->content"
            :contents="$widget->translation->content ? null : $widget->translation->contents"
            :title="$widget->translation->title"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
        />
    @endif

    @if ($widget->assets)
        <div
            x-data="{
                selected: 0,
                isActive(index) {
                    return this.selected === index
                },
            }"
            class="flex w-full flex-col divide-y divide-gray-200 rounded-lg border border-gray-200 dark:divide-gray-600 dark:border-gray-600"
        >
            @foreach ($widget->assets as $widgetAsset)
                @php($linked_page_url = $widgetAsset->asset->linkedPage ? app('capell-frontend')->pageUrl($widgetAsset->asset->linkedPage->pageUrl->url, $site->siteDomain->url) : '')
                <section class="flex flex-col gap-1 py-4">
                    <button
                        type="button"
                        x-on:click="
                            isActive({{ $loop->iteration }})
                                ? (selected = null)
                                : (selected = {{ $loop->iteration }})
                        "
                        class="hover:text-primary focus:text-primary group flex cursor-pointer items-center"
                    >
                        <div class="ml-2 mr-1 flex w-10 justify-center">
                            @svg('heroicon-o-chevron-right', [
                                'class' => 'text-link group-hover:text-primary group-focus:text-primary h-6 w-6',
                                ':class' => "{ 'rotate-90': isActive(".$loop->iteration."), 'rotate-0': !isActive(".$loop->iteration.') }',
                            ])
                        </div>
                        <div class="text-lg font-medium">
                            {!! $widgetAsset->asset->translation->title !!}
                        </div>
                    </button>

                    <div
                        x-bind:style="
                            isActive({{ $loop->iteration }})
                                ? 'max-height: ' + $el.scrollHeight + 'px'
                                : ''
                        "
                        class="relative ml-10 max-h-0 overflow-hidden px-1 pr-4 transition-all duration-700"
                    >
                        <div class="flex gap-6">
                            @if ($widgetAsset->asset->translation)
                                <x-capell::content
                                    :compact="true"
                                    :content="$widgetAsset->asset->translation->content"
                                    :contents="$widgetAsset->asset->translation->contents"
                                />
                            @endif

                            @if ($widgetAsset->asset->image)
                                <a href="{{ $linked_page_url }}">
                                    @if ($widgetAsset->asset->image->preview->hasCuration('thumbnail'))
                                        <x-curator-curation
                                            curation="thumbnail"
                                            :media="$widgetAsset->asset->image->preview"
                                            :width="120"
                                            :height="120"
                                            fit="crop"
                                            format="webp"
                                            class="h-10 w-10 rounded-full object-cover object-center"
                                            loading="lazy"
                                        />
                                    @else
                                        <x-curator-glider
                                            :media="$widgetAsset->asset->image->preview"
                                            :width="120"
                                            :height="120"
                                            fit="crop"
                                            format="webp"
                                            class="h-10 w-10 rounded-full object-cover object-center"
                                            loading="lazy"
                                        />
                                    @endif
                                </a>
                            @endif
                        </div>

                        @if ($widgetAsset->asset->translation->actions || $linked_page_url)
                            <x-capell::actions
                                :actions="$widgetAsset->asset->translation->actions"
                                class="mt-4"
                            >
                                @if ($linked_page_url && ! empty($item->translation->meta['link_text']))
                                    <x-capell::button
                                        :url="$linked_page_url"
                                        color="default"
                                        icon="heroicon-o-chevron-right"
                                    >
                                        {{ $item->translation->meta['link_text'] }}
                                    </x-capell::button>
                                @endif
                            </x-capell::actions>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-capell::widget.wrapper>

<?php
