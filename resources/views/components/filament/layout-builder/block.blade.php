@props([
    'containerKey',
    'containerBlock',
    'loop',
    'block',
    'blockIndex',
])
{{-- format-ignore-start --}}
@php
    use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
    use Illuminate\Support\HtmlString;
    use Illuminate\View\ComponentAttributeBag;

    /**
     * @var \Capell\LayoutBuilder\Models\Block $block
    */

    /**
     * @var LayoutBuilder $this
     */
    $occurrence = $containerBlock['occurrence'] ?? 1;

    $containerBlockKey = "block-{$containerKey}-{$block->key}-{$occurrence}";
    $assetsPanelId = "{$containerBlockKey}-assets";

    $assetTypes = $this->getBlockAssetTypes($block);

    $hasBlockAssets = $block->assets?->isNotEmpty() === true;

    $hasPageAssets = $this->hasPageAssets($containerKey, $blockIndex);

    $previewData = $this->resolveAdminBlockPreviewData($containerKey, $blockIndex);

    $previewView = $this->resolveAdminBlockPreviewView($previewData);

    $previewLabel = $previewData->title ?: $previewData->label;
    $interactionBadges = collect($containerBlock['meta']['interactions'] ?? [])
        ->filter(fn (mixed $interaction): bool => is_array($interaction) && filled($interaction['label'] ?? null))
        ->map(function (array $interaction): string {
            $label = $interaction['label'];
            $target = $interaction['target_type'] ?? $interaction['target']['target_type'] ?? 'widget';

            return $label . ' -> ' . str_replace('_', ' ', (string) $target);
        })
        ->values()
        ->all();

    $editBlockAction = ($this->editBlockAction)(['containerKey' => $containerKey, 'blockIndex' => $blockIndex]);

    $moveBlockUpAction = ($this->moveBlockUpAction)([
        'containerKey' => $containerKey,
        'blockIndex' => $blockIndex,
    ]);

    $moveBlockDownAction = ($this->moveBlockDownAction)([
        'containerKey' => $containerKey,
        'blockIndex' => $blockIndex,
    ]);

    $moveBlockToContainerAction = ($this->moveBlockToContainerAction)([
        'containerKey' => $containerKey,
        'blockIndex' => $blockIndex,
    ]);

    $editLayoutBlockAction = ($this->editLayoutBlockAction)([
        'containerKey' => $containerKey,
        'blockIndex' => $blockIndex,
    ]);

    $togglePageAssetsAction = ($this->togglePageAssetsAction)([
        'containerKey' => $containerKey,
        'blockIndex' => $blockIndex,
    ]);

    $duplicateBlockAction = ($this->duplicateBlockAction)([
        'containerKey' => $containerKey,
        'blockIndex' => $blockIndex,
    ]);

    $removeBlockAction = ($this->removeBlockAction)([
        'containerKey' => $containerKey,
        'blockIndex' => $blockIndex,
    ]);

    $hasAssetControls = $assetTypes !== [];

    $hasBlockControls =
        $moveBlockUpAction?->isVisible()
        || $moveBlockDownAction?->isVisible()
        || $moveBlockToContainerAction?->isVisible()
        || $editLayoutBlockAction?->isVisible()
        || $togglePageAssetsAction?->isVisible()
        || $duplicateBlockAction?->isVisible()
        || $removeBlockAction?->isVisible();
@endphp
{{-- format-ignore-end --}}
<div
    id="layout-block-{{ $containerKey }}-{{ $blockIndex }}"
    tabindex="-1"
    x-data="{
        isCollapsed: true,
        id: '{{ $blockIndex }}',
        containerKey: '{{ $containerKey }}',
        notify() {
            this.$dispatch('block-collapsed-changed', {
                id: this.id,
                containerKey: this.containerKey,
                isCollapsed: this.isCollapsed,
            })
        },
        toggleCollapse() {
            this.isCollapsed = ! this.isCollapsed
            this.notify()
        },
    }"
    {{
        $attributes->class(['layout-container-block group group/block last:rounded-b-lg'])->when(
            $assetTypes,
            fn (ComponentAttributeBag $attributeBag): ComponentAttributeBag => $attributeBag->merge([
                ':class' => "{ 'pb-4': ! isCollapsed }",
            ]),
        )
    }}
    wire:key="{{ "{$containerBlockKey}" }}"
    x-sort:item="'{{ $containerKey . '.' . $blockIndex }}'"
    x-bind:class="{
        'layout-container-block-selected': isSelectedBlock(
            containerKey,
            {{ $blockIndex }},
        ),
        'layout-container-block-reordering': mode === 'edit',
    }"
    x-init="
        $dispatch('block-collapsed-register', {
            id: id,
            containerKey: containerKey,
            isCollapsed: isCollapsed,
        })
    "
    x-on:collapse-block.window="
        if ($event.detail.containerKey && $event.detail.containerKey !== containerKey)
            return
        if ($event.detail.id && $event.detail.id !== id) return
        isCollapsed = $event.detail.isCollapsed
        notify()
    "
    x-on:refresh-assets.window="
        $event.detail.containerKey === '{{ $containerKey }}' &&
        $event.detail.blockIndex === {{ $blockIndex }} &&
        isCollapsed === true
            ? ((isCollapsed = false), notify())
            : null
    "
>
    <div class="relative py-2">
        <div
            class="layout-block-sort-overlay absolute inset-1 z-20 cursor-grab rounded-xl active:cursor-grabbing"
            x-sort:handle
            x-show="mode === 'edit'"
            x-cloak
            x-on:click.stop
        ></div>

        @php
            ob_start();
        @endphp

        <div
            class="layout-block-header-actions relative z-30 flex shrink-0 flex-wrap items-center justify-end gap-2"
            x-on:click.stop
        >
            <x-filament::icon-button
                :label="__('capell-layout-builder::button.edit_block') . ': ' . $previewLabel"
                color="gray"
                icon="heroicon-o-pencil"
                size="sm"
                wire:click="{{ $editBlockAction->getLivewireClickHandler() }}"
            />

            @if ($hasAssetControls)
                <x-filament::dropdown
                    class="fi-btn-group-dropdown layout-block-asset-actions"
                    placement="bottom-end"
                    data-layout-block-asset-actions="true"
                >
                    <x-slot name="trigger">
                        <x-filament::icon-button
                            :label="__('capell-layout-builder::button.block_asset_actions', ['block' => $previewLabel])"
                            color="gray"
                            icon="heroicon-o-squares-plus"
                            size="sm"
                        />
                    </x-slot>

                    <x-filament::dropdown.list>
                        @foreach ($assetTypes as $assetType)
                            {{ ($this->selectAssetAction)(['containerKey' => $containerKey, 'blockIndex' => $blockIndex, 'type' => $assetType, 'types' => $assetTypes]) }}
                            {{ ($this->addAssetAction)(['containerKey' => $containerKey, 'blockIndex' => $blockIndex, 'type' => $assetType, 'types' => $assetTypes]) }}
                        @endforeach
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            @endif

            @if ($hasBlockControls)
                <x-filament::dropdown
                    class="fi-btn-group-dropdown layout-block-tools-actions"
                    placement="bottom-end"
                    data-layout-block-tools-actions="true"
                >
                    <x-slot name="trigger">
                        <x-filament::icon-button
                            :label="__('capell-layout-builder::button.controls')"
                            color="gray"
                            icon="heroicon-o-adjustments-horizontal"
                            size="sm"
                        />
                    </x-slot>

                    <x-filament::dropdown.list>
                        @if ($moveBlockUpAction?->isVisible())
                            {{ $moveBlockUpAction }}
                        @endif

                        @if ($moveBlockDownAction?->isVisible())
                            {{ $moveBlockDownAction }}
                        @endif

                        @if ($moveBlockToContainerAction?->isVisible())
                            {{ $moveBlockToContainerAction }}
                        @endif

                        @if ($editLayoutBlockAction?->isVisible())
                            {{ $editLayoutBlockAction }}
                        @endif

                        @if ($togglePageAssetsAction?->isVisible())
                            {{ $togglePageAssetsAction }}
                        @endif

                        @if ($duplicateBlockAction?->isVisible())
                            {{ $duplicateBlockAction }}
                        @endif

                        @if ($removeBlockAction?->isVisible())
                            {{ $removeBlockAction }}
                        @endif
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            @endif
        </div>
        @php
            $blockActionsHtml = ob_get_clean();
            $blockActions = trim($blockActionsHtml) === '' ? null : new HtmlString($blockActionsHtml);
        @endphp

        @php
            ob_start();
        @endphp

        @if ($hasBlockAssets)
            <x-filament::icon-button
                class="layout-block-assets-toggle"
                :label="__('capell-layout-builder::button.show_block_assets')"
                color="gray"
                icon="heroicon-o-folder-open"
                size="sm"
                x-on:click.stop="toggleCollapse()"
                x-bind:aria-expanded="(! isCollapsed).toString()"
                x-bind:class="! isCollapsed ? 'layout-block-assets-toggle-open' : ''"
                aria-controls="{{ $assetsPanelId }}"
                data-layout-block-assets-toggle="true"
            />
        @endif

        @php
            $assetsToggleActionHtml = ob_get_clean();
            $assetsToggleAction = trim($assetsToggleActionHtml) === '' ? null : new HtmlString($assetsToggleActionHtml);
        @endphp

        <div class="layout-block-frame relative flex items-stretch">
            <button
                type="button"
                class="layout-block-drag-handle z-30 m-0 hidden cursor-grab items-center justify-center active:cursor-grabbing md:flex"
                tabindex="-1"
                aria-hidden="true"
                x-sort:handle
                x-on:click.stop
            >
                <x-capell-layout-builder::filament.layout-builder.drag-handle-icon
                    class="h-7 w-2.5"
                />
                <span class="sr-only">
                    {{ __('capell-layout-builder::button.move_block', ['block' => $previewLabel]) }}
                </span>
            </button>

            <div
                @class([
                    'layout-block-preview-shell group/block min-w-0 flex-1 transition focus-visible:outline-none',
                ])
                role="button"
                tabindex="0"
                aria-label="{{ __('capell-layout-builder::button.select_block', ['block' => $previewLabel]) }}"
                x-on:click.capture="
                    if (shouldSuppressBlockActions()) {
                        $event.preventDefault()
                        $event.stopImmediatePropagation()
                    }
                "
                x-on:click="selectBlock(containerKey, {{ $blockIndex }})"
                x-on:keydown.enter.prevent="selectBlock(containerKey, {{ $blockIndex }})"
                x-on:keydown.space.prevent="selectBlock(containerKey, {{ $blockIndex }})"
            >
                @include($previewView, [
                    'containerKey' => $containerKey,
                    'containerBlock' => $containerBlock,
                    'previewData' => $previewData,
                    'assetsToggleAction' => $assetsToggleAction,
                    'blockActions' => $blockActions,
                    'block' => $block,
                    'blockIndex' => $blockIndex,
                ])

                @if ($interactionBadges !== [])
                    <div class="flex flex-wrap gap-1.5 px-4 pb-3">
                        @foreach ($interactionBadges as $interactionBadge)
                            <span
                                class="bg-primary-50 text-primary-700 ring-primary-600/15 dark:bg-primary-500/10 dark:text-primary-300 dark:ring-primary-400/20 inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium ring-1"
                            >
                                <x-filament::icon
                                    icon="heroicon-o-bolt"
                                    class="h-3.5 w-3.5"
                                />
                                {{ $interactionBadge }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div
            class="layout-block-mobile-handle absolute bottom-2 left-0 top-2 z-30 md:hidden"
            x-show="mode === 'edit'"
            x-cloak
            x-sort:handle
            x-on:click.stop
        >
            <button
                type="button"
                class="layout-container-block-handle inline-flex h-8 w-8 cursor-grab items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-500/10 focus-visible:bg-gray-500/10 active:cursor-grabbing dark:text-gray-300"
                tabindex="-1"
                aria-hidden="true"
            >
                <x-capell-layout-builder::filament.layout-builder.drag-handle-icon
                    class="h-4 w-4"
                />
            </button>
        </div>
    </div>

    @if ($assetTypes)
        <x-capell-layout-builder::filament.layout-builder.assets
            :$containerKey
            :$hasPageAssets
            :$occurrence
            :panelId="$assetsPanelId"
            :$assetTypes
            :$block
            :$blockIndex
        />
    @endif
</div>
