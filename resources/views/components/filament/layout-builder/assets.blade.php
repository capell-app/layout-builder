@props(['containerKey', 'hasPageAssets', 'occurrence', 'panelId' => null, 'block', 'blockIndex'])
@php
    use Capell\Core\Facades\CapellCore;
    use Capell\LayoutBuilder\Models\WidgetAsset;
    use Filament\Support\Enums\FontWeight;
    use Filament\Support\Enums\IconPosition;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Enums\Size;

    $assetsCount = $block->assets?->count() ?? 0;

    $removeAssetsAction = ($this->removeAssetsAction)([
        'containerKey' => $containerKey,
        'blockIndex' => $blockIndex,
    ]);
@endphp

<div
    @if ($panelId) id="{{ $panelId }}" @endif
    class="layout-builder-block-assets shadow-xs mx-4 mt-0.5 rounded ring-1 ring-gray-950/5 dark:ring-white/10"
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
                {{ $hasPageAssets ? __('capell-layout-builder::generic.block_asset_page') : __('capell-layout-builder::generic.block_assets') }}
            </span>
            -
            {{ $hasPageAssets ? __('capell-layout-builder::generic.block_assets_page_info') : __('capell-layout-builder::generic.block_assets_info') }}
        </span>
        <div class="flex items-center gap-x-3">
            @if ($assetsCount > 1)
                <x-filament::link
                    color="gray"
                    :size="Size::ExtraSmall"
                    tag="button"
                    type="button"
                    class="cursor-pointer"
                    x-on:click="toggleReorderingResources('{{ $containerKey }}', {{ $blockIndex }})"
                    x-bind:aria-pressed="isBlockReorderingResources('{{ $containerKey }}', {{ $blockIndex }}).toString()"
                >
                    @svg('heroicon-o-arrows-up-down', 'inline-block h-4 w-4 transition duration-75', [
                        'x-show' => "! isBlockReorderingResources('{$containerKey}', {$blockIndex})",
                    ])
                    @svg('heroicon-o-check', 'inline-block h-4 w-4 transition duration-75', [
                        'x-show' => "isBlockReorderingResources('{$containerKey}', {$blockIndex})",
                        'x-cloak' => '',
                    ])
                    <span
                        x-text="
                            ! isBlockReorderingResources('{{ $containerKey }}', {{ $blockIndex }})
                                ? '{{ __('capell-layout-builder::button.reorder') }}'
                                : '{{ __('capell-layout-builder::button.cancel_reorder') }}'
                        "
                    ></span>
                </x-filament::link>
            @endif
        </div>
    </div>

    <div
        class="flex w-full flex-grow flex-wrap items-center justify-between gap-4 border-b border-gray-100 px-4 py-3 lg:order-1 lg:w-auto dark:border-gray-700"
        x-show="{{ "selectedRecords['{$containerKey}'][{$blockIndex}].length" }}"
        x-transition
    >
        <x-capell-admin::tables.selection-indicator
            class="flex-grow !bg-transparent !p-0"
            :all-selectable-records-count="$assetsCount"
            :page="1"
            :selected-records-property-name="'selectedRecords[\'' . $containerKey . '\'][' . $blockIndex . ']'"
            :get-selected-records-count-action="'selectedRecords[\'' . $containerKey . '\'][' . $blockIndex . '].length'"
            :select-all-records-action="'selectAllRecords(\'' . $containerKey . '\', ' . $blockIndex . ')'"
            :deselect-all-records-action="'deselectAllRecords(\'' . $containerKey . '\', ' . $blockIndex . ')'"
        />

        @if ($removeAssetsAction && $removeAssetsAction->isVisible())
            {{ $removeAssetsAction }}
        @endif
    </div>

    @if ($block->assets?->isNotEmpty())
        <div
            class="divide-y divide-black/5 dark:divide-white/10"
            x-sort="
                $wire.reorderAssets(
                    '{{ $containerKey }}',
                    {{ $blockIndex }},
                    $item,
                    $position,
                )
            "
            x-sort:config="{
                animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches
                    ? 0
                    : 180,
            }"
        >
            @foreach ($block->assets as $blockAsset)
                <x-capell-layout-builder::filament.layout-builder.asset
                    :$containerKey
                    :index="$loop->index"
                    :$occurrence
                    :$blockAsset
                    :$block
                    :$blockIndex
                />
            @endforeach
        </div>
    @else
        <div
            class="py-3 text-center font-light tracking-tight text-gray-600 dark:text-gray-100"
        >
            {{ $block->page_assets_count ? __('capell-layout-builder::message.block_has_page_assets', ['total' => $block->page_assets_count]) : __('capell-layout-builder::message.block_assets_empty') }}
        </div>
    @endif
</div>
