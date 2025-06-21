<?php

declare(strict_types=1);

?>

@props(['containerKey', 'hasPageAssets', 'occurrence', 'assets', 'assetsCount', 'widget', 'widgetIndex'])
@php
    use Capell\Layout\Models\WidgetAsset;

    $removeAssetsAction = ($this->removeAssetsAction)([
        'containerKey' => $containerKey,
        'widgetIndex' => $widgetIndex,
    ]);
@endphp

<div
    class="layout-builder-widget-assets mx-4 mt-0.5 rounded-lg ring-1 ring-gray-950/5 dark:ring-gray-700"
    x-show="! isCollapsed"
    x-cloak
>
    @if ($assets?->isNotEmpty())
        <div
            class="flex items-center justify-between border-b border-black/5 bg-gray-50 px-4 py-2 dark:border-white/10 dark:bg-gray-800"
        >
            <span
                class="text-sm font-light tracking-tight text-gray-500 dark:text-gray-400"
            >
                {{ $hasPageAssets ? __('capell-admin::generic.page_widget_resources') : __('capell-admin::generic.widget_resources') }}
            </span>
            <div>
                @if ($assetsCount > 1)
                    <x-filament::link
                        color="gray"
                        size="xl"
                        weight="semibold"
                        x-on:click="toggleReorderingResources('{{ $containerKey }}', {{ $widgetIndex }})"
                        x-show="selectedRecords['{{ $containerKey }}'][{{ $widgetIndex }}].length === 0"
                    >
                        @svg('heroicon-o-arrows-up-down', 'inline-block h-5 w-5 text-gray-400 transition duration-75 dark:text-gray-500', [
                            'x-show' => "! isWidgetReorderingResources('{$containerKey}', $widgetIndex)",
                        ])
                        @svg('heroicon-o-x-mark', 'inline-block h-5 w-5 text-gray-400 transition duration-75 dark:text-gray-500', [
                            'x-show' => "isWidgetReorderingResources('{$containerKey}', $widgetIndex)",
                            'x-cloak' => '',
                        ])
                        <span
                            x-bind:class="
                                isWidgetReorderingResources('{{ $containerKey }}', {{ $widgetIndex }})
                                    ? 'text-primary-600 dark:text-primary-500'
                                    : 'text-gray-500 dark:text-gray-400'
                            "
                            x-text="
                                ! isWidgetReorderingResources('{{ $containerKey }}', {{ $widgetIndex }})
                                    ? '{{ __('capell-admin::button.reorder') }}'
                                    : '{{ __('capell-admin::button.cancel_reorder') }}'
                            "
                        ></span>
                    </x-filament::link>
                @endif
            </div>
        </div>

        <div
            class="flex w-full flex-grow flex-wrap items-center justify-between gap-4 px-4 py-3 lg:order-1 lg:w-auto"
            x-show="{{ "selectedRecords['$containerKey'][$widgetIndex].length" }}"
            x-transition
        >
            <x-filament-tables::selection.indicator
                class="flex-grow !bg-transparent !p-0"
                :all-selectable-records-count="$assetsCount"
                :selectedRecordsPropertyName="'selectedRecords[\''.$containerKey.'\']['.$widgetIndex.']'"
                :x-bind:hidden="
                    '! selectedRecords[\''.$containerKey.
                    '\']['.$widgetIndex.
                    '].length'
                "
                :selectAllRecordsAction="'selectAllRecords(\''.$containerKey.'\', '.$widgetIndex.')'"
                :deselectAllRecordsAction="'deselectAllRecords(\''.$containerKey.'\', '.$widgetIndex.')'"
            />

            @if ($removeAssetsAction && $removeAssetsAction->isVisible())
                {{ $removeAssetsAction }}
            @endif
        </div>

        <div
            class="divide-y divide-black/5 dark:divide-white/10"
            x-sort="
                $wire.reorderAssets(
                    '{{ $containerKey }}',
                    {{ $widgetIndex }},
                    $item,
                    $position,
                )
            "
        >
            @foreach ($assets as $asset)
                @php
                    /** @var Capell\Layout\Models\Widget $widget */
                    $widgetAsset = $widget->assets
                        ->where('asset_type', $asset['asset_type'])
                        ->where('asset_id', $asset['asset_id'])
                        ->first();

                    if (! $widgetAsset) {
                        throw new Exception(
                            "Resource not found for {$asset['asset_type']} {$asset['asset_id']} for widget $widget->key ($occurrence).",
                        );
                    }
                @endphp

                <x-capell-layout::layout-builder.widget.asset
                    :$containerKey
                    :index="$loop->index"
                    :$occurrence
                    :asset="$widgetAsset->asset"
                    :asset-key="$widgetAsset->asset_type.'.'.$widgetAsset->asset_id"
                    :asset-type="$widgetAsset->asset_type"
                    :$widget
                    :$widgetIndex
                />
            @endforeach
        </div>
    @else
        <div
            class="py-3 text-center font-light tracking-tight text-gray-600 dark:text-gray-100"
        >
            @php($pagesWithAssets = WidgetAsset::totalWidgetPages($widget))
            {{ $pagesWithAssets ? __('capell-layout::message.widget_has_page_assets', ['total' => $pagesWithAssets]) : __('capell-admin::message.widget_resources_empty') }}
        </div>
    @endif
</div>

<?php
