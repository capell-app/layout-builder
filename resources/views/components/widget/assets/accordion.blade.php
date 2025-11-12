<?php

declare(strict_types=1);

?>

@php
    use Capell\Core\Enums\AssetComponentEnum;
    use Capell\Frontend\Facades\FrontendLoader;

    $site = FrontendLoader::getSite();
@endphp

@props([
    'columns' => $container['meta']['override_columns'] ?? ($widget->meta['columns'] ?? 3),
    'container',
    'containerKey',
    'containerWidth' => null,
    'showPageContent' => $widgetData['meta']['show_page_content'] ?? false,
    'showPageTitle' => $widgetData['meta']['show_page_title'] ?? false,
    'index',
    'loop',
    'size' => $widget->meta['size'] ?? null,
    'spacing' => $widget->meta['spacing'] ?? 'lg',
    'widget',
])
<x-capell-layout::widget.wrapper
    class="widget-content-grid widget-content-accordion space-y-6"
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
            :presenter="$widget->type->meta['content_presenter'] ?? null"
            :title="$widget->translation->title"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
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
                @php($linkedPageUrl = $widgetAsset->asset->linkedPage ? $widgetAsset->asset->linkedPage->pageUrl?->full_url : '')
                <section
                    class="flex flex-col gap-1 bg-gray-50 py-3 first:rounded-t-lg last:rounded-b-lg dark:bg-white/5"
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
                                        :presenter="$widgetAsset->asset->type->meta['content_presenter'] ?? null"
                                    />
                                @endif

                                @if ($widgetAsset->asset->image)
                                    <a href="{{ $linkedPageUrl }}">
                                        <x-capell::media
                                            :media="$widgetAsset->asset->image"
                                            :width="120"
                                            :height="120"
                                            fit="crop"
                                            class="h-10 w-10 rounded-full object-cover object-center"
                                            loading="lazy"
                                        />
                                    </a>
                                @endif
                            </div>

                            @if (! empty($widgetAsset->asset->meta['actions']) || $linkedPageUrl)
                                <x-capell::actions
                                    :actions="$widgetAsset->asset->meta['actions'] ?? []"
                                    class="mt-4"
                                >
                                    @if ($linkedPageUrl)
                                        <x-capell::button
                                            :url="$linkedPageUrl"
                                            color="default"
                                            icon="heroicon-o-chevron-right"
                                        >
                                            {{ $item->translation?->link_text }}
                                        </x-capell::button>
                                    @endif
                                </x-capell::actions>
                            @endif
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-capell-layout::widget.wrapper>

<?php
