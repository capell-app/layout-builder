<?php

declare(strict_types=1);

?>

{{-- format-ignore-start --}}
@php
    use Capell\Core\Contracts\Pageable;
    use Capell\Core\Enums\AssetComponentEnum;
    use Capell\Core\Models\Page;
    use Capell\Frontend\Facades\Frontend;
    use Capell\Mosaic\Models\WidgetAsset;

    $site = Frontend::site();
    $theme = Frontend::theme();
@endphp
{{-- format-ignore-end --}}

@props([
    'columns' => $container['meta']['override_columns'] ?? $widget->getMeta('columns', 3),
    'container',
    'containerKey',
    'containerWidth' => null,
    'showPageContent' => $widgetData['meta']['show_page_content'] ?? false,
    'showPageTitle' => $widgetData['meta']['show_page_title'] ?? false,
    'index',
    'loop',
    'size' => $widget->getMeta('size'),
    'widget',
])
<x-capell-mosaic::widget.wrapper
    class="widget-section-grid widget-accordion space-y-6"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    @if ($widget->translation)
        <x-capell::content
            class="mb-4"
            :compact="true"
            :content="$widget->translation->content"
            :content-type="$widget->type->content_structure"
            :divider="$widget->getMeta('content_divider')"
            :muted="in_array($containerKey, $theme->secondary_containers)"
            :title="$widget->translation->title"
            :text-align="$widget->getMeta('align')"
            :heading-style="$widget->getMeta('heading_style')"
        />
    @endif

    @if ($widget->assets->isNotEmpty())
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
                {{-- format-ignore-start --}}
                @php
                    /** @var WidgetAsset $widgetAsset */

                    $image = $widgetAsset->media->first() ?: $widgetAsset->asset->image;

                    $linkedPage = $widgetAsset->asset instanceof Pageable
                        ? $widgetAsset->asset
                        : $widgetAsset->asset->linkedPage;

                    $actions = $widgetAsset->asset->getMeta('actions', []);
                @endphp
                {{-- format-ignore-end --}}
                <section
                    class="widget-accordion-item flex flex-col gap-1 bg-gray-50 py-3 first:rounded-t-lg last:rounded-b-lg dark:bg-white/5"
                >
                    <button
                        type="button"
                        x-on:click="
                            isActive({{ $loop->iteration }})
                                ? (selected = null)
                                : (selected = {{ $loop->iteration }})
                        "
                        class="hover:text-primary focus:text-primary group flex cursor-pointer items-center"
                    >
                        <div class="ml-2 flex w-10 justify-center">
                            @svg('heroicon-o-chevron-right', [
                                'class' => 'text-link group-hover:text-primary group-focus:text-primary h-6 w-6',
                                ':class' => "{ 'rotate-90': isActive(" . $loop->iteration . "), 'rotate-0': !isActive(" . $loop->iteration . ') }',
                            ])
                        </div>
                        <div class="font-medium">
                            {!! $widgetAsset->asset->translation?->title !!}
                        </div>
                    </button>

                    <div
                        x-bind:style="
                            isActive({{ $loop->iteration }})
                                ? 'max-height: ' + $el.scrollHeight + 'px'
                                : ''
                        "
                        class="relative max-h-0 overflow-hidden transition-all duration-700"
                    >
                        <div class="ml-4 px-1 pr-4 pt-1">
                            <div class="flex gap-6">
                                @if ($widgetAsset->asset->translation)
                                    <x-capell::content
                                        :compact="true"
                                        :content="$widgetAsset->asset->translation->content"
                                        :content-type="$widgetAsset->asset->type->content_structure"
                                    />
                                @endif

                                @if ($image)
                                    @capellBuffer($mediaContent)
                                        <x-capell::media
                                            :media="$image"
                                            :width="120"
                                            :height="120"
                                            :alt="$widgetAsset->asset->translation->title"
                                            fit="crop"
                                            class="h-10 w-10 rounded-full object-cover object-center"
                                            loading="lazy"
                                        />
                                    @endcapellBuffer

                                    @if ($linkedPage)
                                        <a
                                            href="{{ $linkedPage->pageUrl->full_url }}"
                                            wire:navigate
                                            class="shrink-0"
                                        >
                                            {{ $mediaContent() }}
                                        </a>
                                    @else
                                        {{ $mediaContent() }}
                                    @endif
                                @endif
                            </div>

                            @if ($actions || $linkedPage)
                                <x-capell-mosaic::actions
                                    :$actions
                                    class="mt-4"
                                >
                                    @if ($linkedPage)
                                        <x-capell::button
                                            :url="$linkedPage->pageUrl->full_url"
                                            color="default"
                                            icon="heroicon-o-chevron-right"
                                        >
                                            {{ $widgetAsset->asset->translation?->link_text }}
                                        </x-capell::button>
                                    @endif
                                </x-capell-mosaic::actions>
                            @endif
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-capell-mosaic::widget.wrapper>

<?php
