<?php

declare(strict_types=1);

?>

@props(['containerKey', 'hasPageAssets', 'occurrence', 'widget', 'widgetIndex'])
@php
    use Capell\Core\Facades\CapellCore;
        use Capell\Layout\Models\WidgetAsset;
        use Filament\Support\Enums\FontWeight;
        use Filament\Support\Enums\IconPosition;
        use Filament\Support\Enums\IconSize;
        use Filament\Support\Enums\Size;

        $assetsCount = $widget->assets?->count() ?? 0;

        $removeAssetsAction = ($this->removeAssetsAction)([
            'containerKey' => $containerKey,
            'widgetIndex' => $widgetIndex,
        ]);
@endphp

<div
    class="layout-builder-widget-assets shadow-xs mx-4 mt-0.5 rounded ring-1 ring-gray-950/5 dark:ring-white/10"
    x-show="! isCollapsed"
    x-cloak
>
    <div
        class="flex items-center justify-between rounded-t border-b border-black/5 bg-gray-50 px-4 py-2.5 dark:border-white/10 dark:bg-gray-800"
    >
        <span
            @class([
            'text-xs font-medium',
            'text-warning-600 dark:text-warning-400' => $hasPageAssets,
            'text-gray-500 dark:text-gray-400' => ! $hasPageAssets,
            ])
        >
            <span class="font-semi-bold">
                {{ $hasPageAssets ? __('capell-admin::generic.widget_asset_page') : __('capell-admin::generic.widget_assets') }}
            </span>
            -
            {{ $hasPageAssets ? __('capell-admin::generic.widget_assets_page_info') : __('capell-admin::generic.widget_assets_info') }}
        </span>
        <div class="flex items-center gap-x-3">
            @if ($assetsCount > 1)
                <x-filament::link
                    color="gray"
                    :size="Size::ExtraSmall"
                    class="cursor-pointer"
                    x-on:click="toggleReorderingResources('{{ $containerKey }}', {{ $widgetIndex }})"
                >
                    @svg('heroicon-o-arrows-up-down', 'inline-block h-4 w-4 transition duration-75', [
                    'x-show' => "! isWidgetReorderingResources('{$containerKey}', {$widgetIndex})",
                    ])
                    @svg('heroicon-o-check', 'inline-block h-4 w-4 transition duration-75', [
                    'x-show' => "isWidgetReorderingResources('{$containerKey}', {$widgetIndex})",
                    'x-cloak' => '',
                    ])
                    <span
                        x-text="
                            ! isWidgetReorderingResources('{{ $containerKey }}', {{ $widgetIndex }})
                                ? '{{ __('capell-layout::button.reorder') }}'
                                : '{{ __('capell-layout::button.cancel_reorder') }}'
                        "
                    ></span>
                </x-filament::link>
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

    @if ($widget->assets?->isNotEmpty())
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
            @foreach ($widget->assets as $widgetAsset)
                <x-capell-layout::layout-builder.asset
                    :$containerKey
                    :index="$loop->index"
                    :$occurrence
                    :$widgetAsset
                    :$widget
                    :$widgetIndex
                />
            @endforeach
        </div>
    @else
        <div
            class="py-3 text-center font-light tracking-tight text-gray-600 dark:text-gray-100"
        >
            {{ $widget->page_assets_count ? __('capell-layout::message.widget_has_page_assets', ['total' => $widget->page_assets_count]) : __('capell-layout::message.widget_assets_empty') }}
        </div>
    @endif
</div>

<?php
