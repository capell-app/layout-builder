<?php

declare(strict_types=1);

?>

@props(['containerKey', 'hasPageAssets', 'occurrence', 'assets', 'assetsCount', 'assetTypes', 'widget', 'widgetIndex'])
@php
    use Capell\Core\Facades\CapellCore;
    use Capell\Layout\Models\WidgetAsset;
    use Filament\Support\Enums\FontWeight;
    use Filament\Support\Enums\IconPosition;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Enums\Size;

    $removeAssetsAction = ($this->removeAssetsAction)([
        'containerKey' => $containerKey,
        'widgetIndex' => $widgetIndex,
    ]);
@endphp

<div
    class="layout-builder-widget-assets mx-4 mt-0.5 rounded-lg ring-1 ring-gray-950/10 dark:ring-white/20"
    x-show="! isCollapsed"
    x-cloak
>
    <div
        class="flex items-center justify-between rounded-t-lg border-b border-black/5 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-gray-800"
    >
        <span class="text-sm text-gray-500 dark:text-gray-400">
            {{ $hasPageAssets ? __('capell-admin::generic.page_widget_assets') : __('capell-admin::generic.widget_assets') }}
        </span>
        <div class="flex items-center gap-x-3">
            @if ($assetsCount > 1)
                <x-filament::link
                    color="warning"
                    :size="Size::Small"
                    weight="semibold"
                    x-on:click="toggleReorderingResources('{{ $containerKey }}', {{ $widgetIndex }})"
                    x-show="selectedRecords['{{ $containerKey }}'][{{ $widgetIndex }}].length === 0"
                >
                    @svg('heroicon-o-arrows-up-down', 'inline-block h-5 w-5 transition duration-75', [
                        'x-show' => "! isWidgetReorderingResources('{$containerKey}', {$widgetIndex})",
                    ])
                    @svg('heroicon-o-x-mark', 'inline-block h-5 w-5 transition duration-75', [
                        'x-show' => "isWidgetReorderingResources('{$containerKey}', {$widgetIndex})",
                        'x-cloak' => '',
                    ])
                    <span
                        x-text="
                            ! isWidgetReorderingResources('{{ $containerKey }}', {{ $widgetIndex }})
                                ? '{{ __('capell-admin::button.reorder') }}'
                                : '{{ __('capell-admin::button.cancel_reorder') }}'
                        "
                    ></span>
                </x-filament::link>
            @endif

            @if ($assetTypes)
                <div class="flex flex-wrap justify-end gap-1">
                    <x-filament::dropdown placement="bottom-end">
                        <x-slot name="trigger">
                            <x-filament::link
                                :iconSize="IconSize::Small"
                                :weight="FontWeight::Medium"
                                :size="Size::Small"
                                color="primary"
                                icon="heroicon-c-plus-circle"
                                :outlined="true"
                            >
                                {{ __('capell-admin::button.assets') }}
                            </x-filament::link>
                        </x-slot>
                        @foreach ($assetTypes as $assetType)
                            <x-filament::dropdown.list>
                                <x-filament::dropdown.header
                                    class="cursor-default font-semibold"
                                    color="gray"
                                    :icon="CapellCore::getAsset($assetType)->getIcon()"
                                >
                                    {{ __('capell-admin::button.add_asset_type', ['type' => CapellCore::getAsset($assetType)->getLabel()]) }}
                                </x-filament::dropdown.header>
                                {{ ($this->selectAssetAction)(['containerKey' => $containerKey, 'widgetIndex' => $widgetIndex, 'type' => $assetType, 'types' => $assetTypes]) }}
                                {{ ($this->addAssetAction)(['containerKey' => $containerKey, 'widgetIndex' => $widgetIndex, 'type' => $assetType, 'types' => $assetTypes]) }}
                            </x-filament::dropdown.list>
                        @endforeach
                    </x-filament::dropdown>
                </div>
            @endif
        </div>
    </div>

    <div
        class="flex w-full flex-grow flex-wrap items-center justify-between gap-4 border-b border-gray-100 px-4 py-3 lg:order-1 lg:w-auto dark:border-gray-700"
        x-show="{{ "selectedRecords['{$containerKey}'][{$widgetIndex}].length" }}"
        x-transition
    >
        <x-capell-admin::tables.selection-indicator
            class="flex-grow !bg-transparent !p-0"
            :all-selectable-records-count="$assetsCount"
            :page="1"
            :selected-records-property-name="'selectedRecords[\'' . $containerKey . '\'][' . $widgetIndex . ']'"
            :get-selected-records-count-action="'selectedRecords[\'' . $containerKey . '\'][' . $widgetIndex . '].length'"
            :select-all-records-action="'selectAllRecords(\'' . $containerKey . '\', ' . $widgetIndex . ')'"
            :deselect-all-records-action="'deselectAllRecords(\'' . $containerKey . '\', ' . $widgetIndex . ')'"
        />

        @if ($removeAssetsAction && $removeAssetsAction->isVisible())
            {{ $removeAssetsAction }}
        @endif
    </div>

    @if ($assets?->isNotEmpty())
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
                            "Resource not found for {$asset['asset_type']} {$asset['asset_id']} for widget {$widget->key} ({$occurrence}).",
                        );
                    }
                @endphp

                <x-capell-layout::layout-builder.asset
                    :$containerKey
                    :index="$loop->index"
                    :$occurrence
                    :asset="$widgetAsset->asset"
                    :asset-key="$widgetAsset->asset_type . '.' . $widgetAsset->asset_id"
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
            {{ $pagesWithAssets ? __('capell-layout::message.widget_has_page_assets', ['total' => $pagesWithAssets]) : __('capell-admin::message.widget_assets_empty') }}
        </div>
    @endif
</div>

<?php
