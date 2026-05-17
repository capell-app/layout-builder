@props([
    'containerKey',
    'containerElement',
    'loop',
    'element',
    'elementIndex',
])
{{-- format-ignore-start --}}
@php
    use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
    use Illuminate\Support\HtmlString;
    use Illuminate\View\ComponentAttributeBag;

    /**
     * @var \Capell\LayoutBuilder\Models\Element $element
    */

    /**
     * @var LayoutBuilder $this
     */
    $occurrence = $containerElement['occurrence'] ?? 1;

    $containerElementKey = "element-{$containerKey}-{$element->key}-{$occurrence}";
    $assetsPanelId = "{$containerElementKey}-assets";

    $assetTypes = $this->getElementAssetTypes($element);

    $hasElementAssets = $element->assets?->isNotEmpty() === true;

    $hasPageAssets = $this->hasPageAssets($containerKey, $elementIndex);

    $previewData = $this->resolveAdminElementPreviewData($containerKey, $elementIndex);

    $previewView = $this->resolveAdminElementPreviewView($previewData);

    $previewLabel = $previewData->title ?: $previewData->label;

    $editElementAction = ($this->editElementAction)(['containerKey' => $containerKey, 'elementIndex' => $elementIndex]);

    $moveElementUpAction = ($this->moveElementUpAction)([
        'containerKey' => $containerKey,
        'elementIndex' => $elementIndex,
    ]);

    $moveElementDownAction = ($this->moveElementDownAction)([
        'containerKey' => $containerKey,
        'elementIndex' => $elementIndex,
    ]);

    $moveElementToContainerAction = ($this->moveElementToContainerAction)([
        'containerKey' => $containerKey,
        'elementIndex' => $elementIndex,
    ]);

    $editLayoutElementAction = ($this->editLayoutElementAction)([
        'containerKey' => $containerKey,
        'elementIndex' => $elementIndex,
    ]);

    $togglePageAssetsAction = ($this->togglePageAssetsAction)([
        'containerKey' => $containerKey,
        'elementIndex' => $elementIndex,
    ]);

    $duplicateElementAction = ($this->duplicateElementAction)([
        'containerKey' => $containerKey,
        'elementIndex' => $elementIndex,
    ]);

    $removeElementAction = ($this->removeElementAction)([
        'containerKey' => $containerKey,
        'elementIndex' => $elementIndex,
    ]);

    $hasAssetControls = $assetTypes !== [];

    $hasElementControls =
        $moveElementUpAction?->isVisible()
        || $moveElementDownAction?->isVisible()
        || $moveElementToContainerAction?->isVisible()
        || $editLayoutElementAction?->isVisible()
        || $togglePageAssetsAction?->isVisible()
        || $duplicateElementAction?->isVisible()
        || $removeElementAction?->isVisible();
@endphp
{{-- format-ignore-end --}}
<div
    id="layout-element-{{ $containerKey }}-{{ $elementIndex }}"
    tabindex="-1"
    x-data="{
        isCollapsed: true,
        id: '{{ $elementIndex }}',
        containerKey: '{{ $containerKey }}',
        notify() {
            this.$dispatch('element-collapsed-changed', {
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
        $attributes->class(['layout-container-element group group/element last:rounded-b-lg'])->when(
            $assetTypes,
            fn (ComponentAttributeBag $attributeBag): ComponentAttributeBag => $attributeBag->merge([
                ':class' => "{ 'pb-4': ! isCollapsed }",
            ]),
        )
    }}
    wire:key="{{ "{$containerElementKey}" }}"
    x-sort:item="'{{ $containerKey . '.' . $elementIndex }}'"
    x-bind:class="{
        'layout-container-element-selected': isSelectedElement(
            containerKey,
            {{ $elementIndex }},
        ),
        'layout-container-element-reordering': mode === 'edit',
    }"
    x-init="
        $dispatch('element-collapsed-register', {
            id: id,
            containerKey: containerKey,
            isCollapsed: isCollapsed,
        })
    "
    x-on:collapse-element.window="
        if ($event.detail.containerKey && $event.detail.containerKey !== containerKey)
            return
        if ($event.detail.id && $event.detail.id !== id) return
        isCollapsed = $event.detail.isCollapsed
        notify()
    "
    x-on:refresh-assets.window="
        $event.detail.containerKey === '{{ $containerKey }}' &&
        $event.detail.elementIndex === {{ $elementIndex }} &&
        isCollapsed === true
            ? ((isCollapsed = false), notify())
            : null
    "
>
    <div class="relative py-2">
        <div
            class="layout-element-sort-overlay absolute inset-1 z-20 cursor-grab rounded-xl active:cursor-grabbing"
            x-sort:handle
            x-show="mode === 'edit'"
            x-cloak
            x-on:click.stop
        ></div>

        @php
            ob_start();
        @endphp

        <div
            class="layout-element-header-actions relative z-30 flex shrink-0 flex-wrap items-center justify-end gap-2"
            x-on:click.stop
        >
            <x-filament::icon-button
                :label="__('capell-layout-builder::button.edit_element') . ': ' . $previewLabel"
                color="gray"
                icon="heroicon-o-pencil"
                size="sm"
                wire:click="{{ $editElementAction->getLivewireClickHandler() }}"
            />

            @if ($hasAssetControls)
                <x-filament::dropdown
                    class="fi-btn-group-dropdown layout-element-asset-actions"
                    placement="bottom-end"
                    data-layout-element-asset-actions="true"
                >
                    <x-slot name="trigger">
                        <x-filament::icon-button
                            :label="__('capell-layout-builder::button.element_asset_actions', ['element' => $previewLabel])"
                            color="gray"
                            icon="heroicon-o-squares-plus"
                            size="sm"
                        />
                    </x-slot>

                    <x-filament::dropdown.list>
                        @foreach ($assetTypes as $assetType)
                            {{ ($this->selectAssetAction)(['containerKey' => $containerKey, 'elementIndex' => $elementIndex, 'type' => $assetType, 'types' => $assetTypes]) }}
                            {{ ($this->addAssetAction)(['containerKey' => $containerKey, 'elementIndex' => $elementIndex, 'type' => $assetType, 'types' => $assetTypes]) }}
                        @endforeach
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            @endif

            @if ($hasElementControls)
                <x-filament::dropdown
                    class="fi-btn-group-dropdown layout-element-tools-actions"
                    placement="bottom-end"
                    data-layout-element-tools-actions="true"
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
                        @if ($moveElementUpAction?->isVisible())
                            {{ $moveElementUpAction }}
                        @endif

                        @if ($moveElementDownAction?->isVisible())
                            {{ $moveElementDownAction }}
                        @endif

                        @if ($moveElementToContainerAction?->isVisible())
                            {{ $moveElementToContainerAction }}
                        @endif

                        @if ($editLayoutElementAction?->isVisible())
                            {{ $editLayoutElementAction }}
                        @endif

                        @if ($togglePageAssetsAction?->isVisible())
                            {{ $togglePageAssetsAction }}
                        @endif

                        @if ($duplicateElementAction?->isVisible())
                            {{ $duplicateElementAction }}
                        @endif

                        @if ($removeElementAction?->isVisible())
                            {{ $removeElementAction }}
                        @endif
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            @endif
        </div>
        @php
            $elementActionsHtml = ob_get_clean();
            $elementActions = trim($elementActionsHtml) === '' ? null : new HtmlString($elementActionsHtml);
        @endphp

        @php
            ob_start();
        @endphp

        @if ($hasElementAssets)
            <x-filament::icon-button
                class="layout-element-assets-toggle"
                :label="__('capell-layout-builder::button.show_element_assets')"
                color="gray"
                icon="heroicon-o-folder-open"
                size="sm"
                x-on:click.stop="toggleCollapse()"
                x-bind:aria-expanded="(! isCollapsed).toString()"
                x-bind:class="! isCollapsed ? 'layout-element-assets-toggle-open' : ''"
                aria-controls="{{ $assetsPanelId }}"
                data-layout-element-assets-toggle="true"
            />
        @endif

        @php
            $assetsToggleActionHtml = ob_get_clean();
            $assetsToggleAction = trim($assetsToggleActionHtml) === '' ? null : new HtmlString($assetsToggleActionHtml);
        @endphp

        <div class="layout-element-frame relative flex items-stretch">
            <button
                type="button"
                class="layout-element-drag-handle z-30 m-0 hidden cursor-grab items-center justify-center active:cursor-grabbing md:flex"
                tabindex="-1"
                aria-hidden="true"
                x-sort:handle
                x-on:click.stop
            >
                <x-capell-layout-builder::filament.layout-builder.drag-handle-icon
                    class="h-7 w-2.5"
                />
                <span class="sr-only">
                    {{ __('capell-layout-builder::button.move_element', ['element' => $previewLabel]) }}
                </span>
            </button>

            <div
                @class([
                    'layout-element-preview-shell group/element min-w-0 flex-1 transition focus-visible:outline-none',
                ])
                role="button"
                tabindex="0"
                aria-label="{{ __('capell-layout-builder::button.select_element', ['element' => $previewLabel]) }}"
                x-on:click.capture="
                    if (shouldSuppressElementActions()) {
                        $event.preventDefault()
                        $event.stopImmediatePropagation()
                    }
                "
                x-on:click="selectElement(containerKey, {{ $elementIndex }})"
                x-on:keydown.enter.prevent="selectElement(containerKey, {{ $elementIndex }})"
                x-on:keydown.space.prevent="selectElement(containerKey, {{ $elementIndex }})"
            >
                @include($previewView, [
                    'containerKey' => $containerKey,
                    'containerElement' => $containerElement,
                    'previewData' => $previewData,
                    'assetsToggleAction' => $assetsToggleAction,
                    'elementActions' => $elementActions,
                    'element' => $element,
                    'elementIndex' => $elementIndex,
                ])
            </div>
        </div>

        <div
            class="layout-element-mobile-handle absolute bottom-2 left-0 top-2 z-30 md:hidden"
            x-show="mode === 'edit'"
            x-cloak
            x-sort:handle
            x-on:click.stop
        >
            <button
                type="button"
                class="layout-container-element-handle inline-flex h-8 w-8 cursor-grab items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-500/10 focus-visible:bg-gray-500/10 active:cursor-grabbing dark:text-gray-300"
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
            :$element
            :$elementIndex
        />
    @endif
</div>
