@props([
    'containerKey',
    'containerWidget',
    'loop',
    'widget',
    'widgetIndex',
])
{{-- format-ignore-start --}}
@php
    use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
    use Illuminate\Support\HtmlString;
    use Illuminate\View\ComponentAttributeBag;

    /**
     * @var \Capell\Core\Models\Widget $widget
    */

    /**
     * @var LayoutBuilder $this
     */
    $occurrence = $containerWidget['occurrence'] ?? 1;

    $containerWidgetKey = "widget-{$containerKey}-{$widget->key}-{$occurrence}";
    $assetsPanelId = "{$containerWidgetKey}-assets";

    $assetTypes = $this->getWidgetAssetTypes($widget);

    $hasWidgetAssets = $widget->assets?->isNotEmpty() === true;

    $hasPageAssets = $this->hasPageAssets($containerKey, $widgetIndex);

    $previewData = $this->resolveAdminWidgetPreviewData($containerKey, $widgetIndex);

    $previewView = $this->resolveAdminWidgetPreviewView($previewData);

    $previewLabel = $previewData->title ?: $previewData->label;

    $editWidgetAction = ($this->editWidgetAction)(['containerKey' => $containerKey, 'widgetIndex' => $widgetIndex]);

    $moveWidgetUpAction = ($this->moveWidgetUpAction)([
        'containerKey' => $containerKey,
        'widgetIndex' => $widgetIndex,
    ]);

    $moveWidgetDownAction = ($this->moveWidgetDownAction)([
        'containerKey' => $containerKey,
        'widgetIndex' => $widgetIndex,
    ]);

    $moveWidgetToContainerAction = ($this->moveWidgetToContainerAction)([
        'containerKey' => $containerKey,
        'widgetIndex' => $widgetIndex,
    ]);

    $editLayoutWidgetAction = ($this->editLayoutWidgetAction)([
        'containerKey' => $containerKey,
        'widgetIndex' => $widgetIndex,
    ]);

    $togglePageAssetsAction = ($this->togglePageAssetsAction)([
        'containerKey' => $containerKey,
        'widgetIndex' => $widgetIndex,
    ]);

    $duplicateWidgetAction = ($this->duplicateWidgetAction)([
        'containerKey' => $containerKey,
        'widgetIndex' => $widgetIndex,
    ]);

    $removeWidgetAction = ($this->removeWidgetAction)([
        'containerKey' => $containerKey,
        'widgetIndex' => $widgetIndex,
    ]);

    $hasAssetControls = $assetTypes !== [];

    $hasWidgetControls =
        $moveWidgetUpAction?->isVisible()
        || $moveWidgetDownAction?->isVisible()
        || $moveWidgetToContainerAction?->isVisible()
        || $editLayoutWidgetAction?->isVisible()
        || $togglePageAssetsAction?->isVisible()
        || $duplicateWidgetAction?->isVisible()
        || $removeWidgetAction?->isVisible();
@endphp
{{-- format-ignore-end --}}
<div
    id="layout-widget-{{ $containerKey }}-{{ $widgetIndex }}"
    tabindex="-1"
    x-data="{
        isCollapsed: true,
        id: '{{ $widgetIndex }}',
        containerKey: '{{ $containerKey }}',
        notify() {
            this.$dispatch('widget-collapsed-changed', {
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
        $attributes->class(['layout-container-widget group group/widget last:rounded-b-lg'])->when(
            $assetTypes,
            fn (ComponentAttributeBag $attributeBag): ComponentAttributeBag => $attributeBag->merge([
                ':class' => "{ 'pb-4': ! isCollapsed }",
            ]),
        )
    }}
    wire:key="{{ "{$containerWidgetKey}" }}"
    x-sort:item="'{{ $containerKey . '.' . $widgetIndex }}'"
    x-bind:class="{
        'layout-container-widget-selected': isSelectedWidget(
            containerKey,
            {{ $widgetIndex }},
        ),
        'layout-container-widget-reordering': mode === 'edit',
    }"
    x-init="
        $dispatch('widget-collapsed-register', {
            id: id,
            containerKey: containerKey,
            isCollapsed: isCollapsed,
        })
    "
    x-on:collapse-widget.window="
        if ($event.detail.containerKey && $event.detail.containerKey !== containerKey)
            return
        if ($event.detail.id && $event.detail.id !== id) return
        isCollapsed = $event.detail.isCollapsed
        notify()
    "
    x-on:refresh-assets.window="
        $event.detail.containerKey === '{{ $containerKey }}' &&
        $event.detail.widgetIndex === {{ $widgetIndex }} &&
        isCollapsed === true
            ? ((isCollapsed = false), notify())
            : null
    "
>
    <div class="relative py-2">
        <div
            class="layout-widget-sort-overlay absolute inset-1 z-20 cursor-grab rounded-xl active:cursor-grabbing"
            x-sort:handle
            x-show="mode === 'edit'"
            x-cloak
            x-on:click.stop
        ></div>

        @php
            ob_start();
        @endphp

        <div
            class="layout-widget-header-actions relative z-30 flex shrink-0 flex-wrap items-center justify-end gap-2"
            x-on:click.stop
        >
            <x-filament::icon-button
                :label="__('capell-layout-builder::button.edit_widget') . ': ' . $previewLabel"
                color="gray"
                icon="heroicon-o-pencil"
                size="sm"
                wire:click="{{ $editWidgetAction->getLivewireClickHandler() }}"
            />

            @if ($hasAssetControls)
                <x-filament::dropdown
                    class="fi-btn-group-dropdown layout-widget-asset-actions"
                    placement="bottom-end"
                    data-layout-widget-asset-actions="true"
                >
                    <x-slot name="trigger">
                        <x-filament::icon-button
                            :label="__('capell-layout-builder::button.widget_asset_actions', ['widget' => $previewLabel])"
                            color="gray"
                            icon="heroicon-o-squares-plus"
                            size="sm"
                        />
                    </x-slot>

                    <x-filament::dropdown.list>
                        @foreach ($assetTypes as $assetType)
                            {{ ($this->selectAssetAction)(['containerKey' => $containerKey, 'widgetIndex' => $widgetIndex, 'type' => $assetType, 'types' => $assetTypes]) }}
                            {{ ($this->addAssetAction)(['containerKey' => $containerKey, 'widgetIndex' => $widgetIndex, 'type' => $assetType, 'types' => $assetTypes]) }}
                        @endforeach
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            @endif

            @if ($hasWidgetControls)
                <x-filament::dropdown
                    class="fi-btn-group-dropdown layout-widget-tools-actions"
                    placement="bottom-end"
                    data-layout-widget-tools-actions="true"
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
                        @if ($moveWidgetUpAction?->isVisible())
                            {{ $moveWidgetUpAction }}
                        @endif

                        @if ($moveWidgetDownAction?->isVisible())
                            {{ $moveWidgetDownAction }}
                        @endif

                        @if ($moveWidgetToContainerAction?->isVisible())
                            {{ $moveWidgetToContainerAction }}
                        @endif

                        @if ($editLayoutWidgetAction?->isVisible())
                            {{ $editLayoutWidgetAction }}
                        @endif

                        @if ($togglePageAssetsAction?->isVisible())
                            {{ $togglePageAssetsAction }}
                        @endif

                        @if ($duplicateWidgetAction?->isVisible())
                            {{ $duplicateWidgetAction }}
                        @endif

                        @if ($removeWidgetAction?->isVisible())
                            {{ $removeWidgetAction }}
                        @endif
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            @endif
        </div>
        @php
            $widgetActionsHtml = ob_get_clean();
            $widgetActions = trim($widgetActionsHtml) === '' ? null : new HtmlString($widgetActionsHtml);
        @endphp

        @php
            ob_start();
        @endphp

        @if ($hasWidgetAssets)
            <x-filament::icon-button
                class="layout-widget-assets-toggle"
                :label="__('capell-layout-builder::button.show_widget_assets')"
                color="gray"
                icon="heroicon-o-folder-open"
                size="sm"
                x-on:click.stop="toggleCollapse()"
                x-bind:aria-expanded="(! isCollapsed).toString()"
                x-bind:class="! isCollapsed ? 'layout-widget-assets-toggle-open' : ''"
                aria-controls="{{ $assetsPanelId }}"
                data-layout-widget-assets-toggle="true"
            />
        @endif

        @php
            $assetsToggleActionHtml = ob_get_clean();
            $assetsToggleAction = trim($assetsToggleActionHtml) === '' ? null : new HtmlString($assetsToggleActionHtml);
        @endphp

        <div class="layout-widget-frame relative flex items-stretch">
            <button
                type="button"
                class="layout-widget-drag-handle z-30 m-0 hidden cursor-grab items-center justify-center active:cursor-grabbing md:flex"
                tabindex="-1"
                aria-hidden="true"
                x-sort:handle
                x-on:click.stop
            >
                <x-capell-layout-builder::filament.layout-builder.drag-handle-icon
                    class="h-7 w-2.5"
                />
                <span class="sr-only">
                    {{ __('capell-layout-builder::button.move_widget', ['widget' => $previewLabel]) }}
                </span>
            </button>

            <div
                @class([
                    'layout-widget-preview-shell group/widget min-w-0 flex-1 transition focus-visible:outline-none',
                ])
                role="button"
                tabindex="0"
                aria-label="{{ __('capell-layout-builder::button.select_widget', ['widget' => $previewLabel]) }}"
                x-on:click.capture="
                    if (shouldSuppressWidgetActions()) {
                        $event.preventDefault()
                        $event.stopImmediatePropagation()
                    }
                "
                x-on:click="selectWidget(containerKey, {{ $widgetIndex }})"
                x-on:keydown.enter.prevent="selectWidget(containerKey, {{ $widgetIndex }})"
                x-on:keydown.space.prevent="selectWidget(containerKey, {{ $widgetIndex }})"
            >
                @include($previewView, [
                    'containerKey' => $containerKey,
                    'containerWidget' => $containerWidget,
                    'previewData' => $previewData,
                    'assetsToggleAction' => $assetsToggleAction,
                    'widgetActions' => $widgetActions,
                    'widget' => $widget,
                    'widgetIndex' => $widgetIndex,
                ])
            </div>
        </div>

        <div
            class="layout-widget-mobile-handle absolute bottom-2 left-0 top-2 z-30 md:hidden"
            x-show="mode === 'edit'"
            x-cloak
            x-sort:handle
            x-on:click.stop
        >
            <button
                type="button"
                class="layout-container-widget-handle inline-flex h-8 w-8 cursor-grab items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-500/10 focus-visible:bg-gray-500/10 active:cursor-grabbing dark:text-gray-300"
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
            :$widget
            :$widgetIndex
        />
    @endif
</div>
