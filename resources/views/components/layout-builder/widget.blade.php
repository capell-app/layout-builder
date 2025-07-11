<?php

declare(strict_types=1);

?>

@props([
    'containerKey',
    'containerWidget',
    'loop',
    'occurrence',
    'assets',
    'assetsCount',
    'widget',
    'widgetIndex',
])
@php
    use Capell\Admin\Facades\CapellAdmin;
    use Capell\Core\Facades\CapellCore;
    use Capell\Core\Models\Type;
    use Capell\Layout\Enums\LayoutResourceEnum;
    use Capell\Layout\Models\Content;
    use Filament\Support\Enums\ActionSize;
    use Filament\Support\Enums\FontWeight;
    use Filament\Support\Enums\IconSize;
    use Illuminate\Support\HtmlString;
    use Illuminate\View\ComponentAttributeBag;

    $occurrence = $containerWidget['occurrence'] ?? 1;

    $type = $widget->admin['type'] ?? ($widget->type->admin['type'] ?? []);

    $assetTypes = ! empty($widget->admin['asset_types'])
        ? $widget->admin['asset_types']
        : ($widget->type->admin['asset_types'] ?? []);

    $widgetIcon = ! empty($widget->admin['icon'])
        ? $widget->admin['icon']
        : ($widget->type->admin['icon'] ?? 'heroicon-o-document-text');

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
        toggleCollapse() {
            this.isCollapsed = ! this.isCollapsed
        },
    }"
    {{
        $attributes->class(['layout-container-widget group last:rounded-b-lg bg-white dark:bg-gray-900'])->when(
            $assetTypes,
            fn (ComponentAttributeBag $attributeBag): ComponentAttributeBag => $attributeBag->merge([
                ':class' => "{ 'pb-4': ! isCollapsed }",
            ]),
        )
    }}
    wire:key="{{ "$containerKey.$widgetIndex" }}"
    x-sort:item="'{{ $containerKey.'.'.$widgetIndex }}'"
    x-on:collapse-widget.window="isCollapsed = $event.detail.isCollapsed"
    x-on:refresh-assets.window="
        $event.detail.containerKey === '{{ $containerKey }}' &&
        $event.detail.widgetIndex === {{ $widgetIndex }} &&
        isCollapsed === true
            ? (isCollapsed = false)
            : null
    "
>
    <div class="flex">
        <div
            class="group/widget layout-builder-widget-heading !lg:px-4 flex flex-1 items-center gap-4 px-4 py-3 group-[&:last-child]:rounded-b-lg"
            @if ($assetTypes)
                :class="!isReordering ? 'cursor-pointer' : ''"
                x-on:click.self="! isReordering ? toggleCollapse() : null"
            @endif
        >
            <div
                class="flex grow items-center"
                @if ($assetTypes) x-on:click="! isReordering ? toggleCollapse() : null" @endif
            >
                <div class="mr-1 flex w-7 items-center gap-3">
                    <span
                        class="relative"
                        x-show="! isReordering"
                    >
                        <x-filament::icon
                            :class="'h-5 w-5'.($assetTypes ? ' text-primary-600' : ' text-gray-400')"
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
                                :size="ActionSize::ExtraSmall"
                                class="absolute -right-2 -top-2 inline-flex"
                            >
                                {{ $assetsCount }}
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
                    <x-curator-glider
                        class="ml-auto max-h-12 object-contain"
                        format="webp"
                        view="capell-admin::components.media.glider"
                        :media="$image"
                        :width="80"
                        :height="80"
                        fit="fit"
                        loading="lazy"
                    />
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
                                    href="{{ CapellAdmin::getResource(LayoutResourceEnum::Widget->name)::getUrl('edit', ['record' => $this->getContainerWidget($containerKey, $widgetIndex)]) }}"
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

                @if ($assetTypes)
                    <div class="flex flex-wrap justify-end gap-1 md:order-1">
                        <x-filament::dropdown placement="bottom-end">
                            <x-slot name="trigger">
                                <x-filament::link
                                    :iconSize="IconSize::Small"
                                    :weight="FontWeight::Medium"
                                    size="xs"
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
                                        {{ CapellCore::getAsset($assetType)->getLabel() }}
                                    </x-filament::dropdown.header>
                                    {{ ($this->addAssetAction)(['containerKey' => $containerKey, 'widgetIndex' => $widgetIndex, 'type' => $assetType, 'types' => $assetTypes]) }}
                                    {{ ($this->selectAssetAction)(['containerKey' => $containerKey, 'widgetIndex' => $widgetIndex, 'type' => $assetType, 'types' => $assetTypes]) }}
                                </x-filament::dropdown.list>
                            @endforeach
                        </x-filament::dropdown>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($assetTypes)
        <x-capell-layout::layout-builder.assets
            :$containerKey
            :$hasPageAssets
            :$occurrence
            :$assets
            :$assetsCount
            :$widget
            :$widgetIndex
        />
    @endif
</div>

<?php
