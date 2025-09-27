<?php

declare(strict_types=1);

?>

@props([
    'containerKey',
    'containerWidget',
    'loop',
    'occurrence',
    'widget',
    'widgetIndex',
])
@php
    use Capell\Admin\Facades\CapellAdmin;
    use Capell\Layout\Enums\ResourceEnum;
    use Capell\Layout\Livewire\LayoutBuilder;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Enums\Size;
    use Illuminate\View\ComponentAttributeBag;

    /**
     * @var LayoutBuilder $this
     */
    $occurrence = $containerWidget['occurrence'] ?? 1;

    $type = $widget->admin['type'] ?? ($widget->type->admin['type'] ?? []);

    $assetTypes = ! empty($widget->admin['asset_types'])
        ? $widget->admin['asset_types']
        : ($widget->type->admin['asset_types'] ?? []);

    $widgetIcon = ! empty($widget->admin['icon'])
        ? $widget->admin['icon']
        : ($widget->type->admin['icon'] ?? null);

    $hasPageAssets = $this->hasPageAssets($containerKey, $widgetIndex);

    $editWidgetAction = ($this->editWidgetAction)(['containerKey' => $containerKey, 'widgetIndex' => $widgetIndex]);

    $editContainerWidgetAction = ($this->editContainerWidgetAction)([
        'containerKey' => $containerKey,
        'widgetIndex' => $widgetIndex,
    ]);

    $editWidgetTypeAction = ($this->editWidgetTypeAction)([
        'containerKey' => $containerKey,
        'widgetIndex' => $widgetIndex,
    ]);

    $convertPageAssetsAction = ($this->convertPageAssetsAction)([
        'containerKey' => $containerKey,
        'widgetIndex' => $widgetIndex,
    ]);

    $image = $widget->image ?: $widget->backgroundImage;
@endphp

<div
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
        $attributes->class(['layout-container-widget group last:rounded-b-lg'])->when(
            $assetTypes,
            fn (ComponentAttributeBag $attributeBag): ComponentAttributeBag => $attributeBag->merge([
                ':class' => "{ 'pb-4': ! isCollapsed }",
            ]),
        )
    }}
    wire:key="{{ "{$containerKey}.{$widgetIndex}" }}"
    x-sort:item="'{{ $containerKey . '.' . $widgetIndex }}'"
    x-init="
        $nextTick(() =>
            $dispatch('widget-collapsed-register', {
                id: id,
                containerKey: containerKey,
                isCollapsed: isCollapsed,
            }),
        )
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
    <div class="flex">
        <div
            class="group/widget layout-builder-widget-heading !lg:px-4 flex flex-1 items-center gap-4 px-4 py-3 group-[&:last-child]:rounded-b-lg"
            :class="{
                '!rounded-b-none' : ! isCollapsed,
                {{ $assetTypes ? "'cursor-pointer' : ! isReordering," : '' }}
                {{ $assetTypes ? "'hover:bg-gray-50 dark:hover:bg-white/5' : ! isReordering && isCollapsed," : '' }}
            }"
            @if ($assetTypes)
                x-on:click.self="! isReordering ? toggleCollapse() : null"
            @endif
        >
            <div
                class="flex grow items-center"
                @if ($assetTypes) x-on:click="! isReordering ? toggleCollapse() : null" @endif
            >
                <div class="mr-1 flex w-7 shrink-0 items-center gap-3">
                    <span
                        class="relative"
                        x-show="! isReordering"
                    >
                        <x-filament::icon
                            :class="'h-5 w-5' . ($assetTypes ? ' text-primary-600' : ' text-gray-400')"
                            :x-tooltip.raw="$widget->type?->name"
                            :icon="
                                $assetTypes
                                ? str_replace('heroicon-o-', 'heroicon-s-', $widgetIcon)
                                : $widgetIcon
                            "
                        />

                        @if ($assetTypes)
                            <x-filament::badge
                                :color="$hasPageAssets ? 'primary' : 'gray'"
                                :size="Size::ExtraSmall"
                                class="absolute -right-2 -top-2 inline-flex"
                            >
                                {{ $widget->assets?->count() ?? 0 }}
                            </x-filament::badge>
                        @endif
                    </span>

                    <div
                        wire:loading.class="pointer-events-none opacity-40"
                        x-cloak
                        x-show="isReordering"
                        x-sort:handle
                        x-transition:enter="transition duration-150 ease-in-out"
                    >
                        <x-filament::icon-button
                            class="layout-container-widget-handle"
                            color="primary"
                            icon="heroicon-o-arrows-up-down"
                            size="sm"
                        />
                    </div>
                </div>

                <span
                    @class([
                        'text-sm text-gray-600 dark:text-gray-100',
                        'group-hover/widget:text-primary-600 font-medium' => $assetTypes,
                    ])
                >
                    {{ $widget->name }}

                    @if (! empty($containerWidget['meta']['name']))
                        -
                        <b>{{ $containerWidget['meta']['name'] }}</b>
                    @endif
                </span>

                @if ($type)
                    <x-filament::badge
                        size="xs"
                        color="info"
                    >
                        {{ $type }}
                    </x-filament::badge>
                @endif

                @if ($image)
                    {{ $image->img('thumb')->lazy()->attributes(['class' => 'ml-auto max-h-12 max-w-12 object-contain']) }}
                @endif
            </div>

            <div
                class="ml-auto grid shrink flex-wrap items-center justify-end gap-x-4 gap-y-2 md:flex"
                x-show="! isReordering"
            >
                <div class="flex flex-wrap justify-end gap-3 md:order-2">
                    <div class="fi-btn-group flex items-center">
                        {{ $editWidgetAction }}

                        <x-filament::dropdown
                            class="fi-btn-group-dropdown"
                            placement="bottom-end"
                            teleport
                        >
                            <x-slot name="trigger">
                                <x-filament::button
                                    class="fi-btn-outlined"
                                    icon="heroicon-o-ellipsis-vertical"
                                    size="sm"
                                    color="gray"
                                    :label-sr-only="true"
                                />
                            </x-slot>

                            <x-filament::dropdown.list>
                                @if ($editContainerWidgetAction?->isVisible())
                                    {{ $editContainerWidgetAction }}
                                @endif

                                @if ($editWidgetTypeAction?->isVisible())
                                    {{ $editWidgetTypeAction }}
                                @endif

                                @if ($convertPageAssetsAction?->isVisible())
                                    {{ $convertPageAssetsAction }}
                                @endif

                                {{ ($this->duplicateWidgetAction)(['containerKey' => $containerKey, 'widgetIndex' => $widgetIndex]) }}

                                {{ ($this->removeWidgetAction)(['containerKey' => $containerKey, 'widgetIndex' => $widgetIndex]) }}

                                <x-filament::dropdown.list.item
                                    href="{{ CapellAdmin::getResource(ResourceEnum::Widget)::getUrl('edit', ['record' => $this->getContainerWidget($containerKey, $widgetIndex)]) }}"
                                    icon="heroicon-o-arrow-top-right-on-square"
                                    target="_blank"
                                    tag="a"
                                >
                                    {{ __('capell-admin::button.open_edit_widget') }}
                                </x-filament::dropdown.list.item>
                            </x-filament::dropdown.list>
                        </x-filament::dropdown>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($assetTypes)
        <x-capell-layout::layout-builder.assets
            :$containerKey
            :$hasPageAssets
            :$occurrence
            :$assetTypes
            :$widget
            :$widgetIndex
        />
    @endif
</div>

<?php
