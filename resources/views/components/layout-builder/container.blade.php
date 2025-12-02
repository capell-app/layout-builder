<?php

declare(strict_types=1);

?>

@props([
'container',
'containerKey',
'containerWidgets',
])
@php
    // Represent in two columns to ensure there's enough space
                $colspan = (int) ($container['meta']['colspan'] ?? 12);

                $containerTitle = str($containerKey)->title();

                $widgetHasResourceTypes = ! empty(
                    array_filter($containerWidgets, fn ($widget) => ! empty($widget->type->admin['asset_types']))
                );
@endphp

<div
    x-data="{
        isCollapsed: false,
        id: '{{ $containerKey }}',
        notify() {
            this.$dispatch('container-collapsed-changed', {
                id: this.id,
                isCollapsed: this.isCollapsed,
            })
        },
        toggleCollapse() {
            this.isCollapsed = ! this.isCollapsed
            this.notify()
        },
    }"
    wire:key="container-{{ $containerKey }}"
    x-sort:item="'{{ $containerKey }}'"
    x-init="
        $dispatch('container-collapsed-register', {
            id: id,
            isCollapsed: isCollapsed,
        })
    "
    x-on:collapse-container.window="
        if ($event.detail.id && $event.detail.id !== id) return
        isCollapsed = $event.detail.isCollapsed
        notify()
    "
    @class([
    'layout-container col-span-12',
    'md:col-span-6' => $colspan < 12,
    ])
>
    <div
        class="rounded-lg bg-white ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10"
    >
        <div
            class="layout-container-header group/container flex min-h-11 cursor-pointer items-center gap-x-4 gap-y-2 rounded-lg border-b border-gray-100 bg-gray-50 px-4 hover:bg-gray-50 dark:border-white/5 dark:bg-gray-800 dark:hover:bg-white/5"
            :class="{ '!rounded-b-none': !isCollapsed }"
            x-on:click.self="toggleCollapse"
        >
            <div class="pointer-events-none flex grow items-center gap-x-3">
                @if (count($this->containers) > 1)
                    <div
                        wire:loading.class="pointer-events-none opacity-40"
                        x-sort:handle
                        x-show="isReordering"
                    >
                        <x-filament::icon-button
                            class="layout-container-handle pointer-events-auto"
                            icon="heroicon-o-arrows-up-down"
                            color="primary"
                            size="sm"
                        />
                    </div>
                @endif

                <span
                    class="group-hover/container:text-primary-600 pointer-events-auto break-words text-sm leading-6 text-gray-500 dark:text-gray-100"
                    x-on:click.self="toggleCollapse"
                >
                    {{ __('capell-admin::generic.container_name', ['name' => $containerTitle]) }}
                </span>
            </div>

            <div class="flex items-center gap-3">
                <div
                    class="flex justify-end gap-2"
                    x-show="! isReordering"
                >
                    <x-filament::link
                        class="whitespace-nowrap"
                        color="gray"
                        icon="heroicon-m-plus"
                        iconSize="sm"
                        size="xs"
                        tag="button"
                        weight="normal"
                        x-on:click="collapseAllContainerWidgets(id, false)"
                        x-cloak
                        x-show="isAllWidgetsCollapsed(id) !== false"
                        x-tooltip.raw="{{ __('capell-layout::button.expand_all') }}"
                    >
                        {{ __('capell-layout::button.expand') }}
                    </x-filament::link>
                    <x-filament::link
                        class="whitespace-nowrap"
                        color="gray"
                        icon="heroicon-o-minus"
                        iconSize="sm"
                        size="xs"
                        tag="button"
                        weight="normal"
                        x-on:click="collapseAllContainerWidgets(id, true)"
                        x-cloak
                        x-show="isAllWidgetsCollapsed(id) !== true"
                        x-tooltip.raw="{{ __('capell-layout::button.collapse_all') }}"
                    >
                        {{ __('capell-layout::button.collapse') }}
                    </x-filament::link>
                </div>

                <x-filament::dropdown
                    placement="bottom-end"
                    width="!w-auto"
                    teleport
                >
                    <x-slot name="trigger">
                        <x-filament::icon-button
                            icon="heroicon-o-ellipsis-vertical"
                            size="sm"
                            color="gray"
                        />
                    </x-slot>

                    <x-filament::dropdown.list>
                        {{ ($this->editContainerAction)(['containerKey' => $containerKey]) }}

                        {{ ($this->removeContainerAction)(['containerKey' => $containerKey]) }}
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            </div>
        </div>

        <div
            x-show="! isCollapsed"
            class="layout-container-widgets min-h-[52px] divide-y divide-gray-100 rounded-b-lg dark:divide-gray-800"
            x-sort="$wire.reorderWidgets('{{ $containerKey }}', $item, $position)"
            x-sort:group="widgets"
        >
            @foreach ($container['widgets'] as $widgetIndex => $containerWidget)
                <x-capell-layout::layout-builder.widget
                    :$containerKey
                    :$containerWidget
                    :$loop
                    :widget="$containerWidgets[$widgetIndex]"
                    :$widgetIndex
                />
            @endforeach
        </div>
        <style>
            .layout-container-widgets:empty::before {
                content: '{{ __('capell-admin::generic.drag_and_drop_widgets_here') }}';
                text-align: center;
                display: block;
                height: 100%;
                padding: 20px;
                font-style: italic;
                color: #757575;
                font-size: 0.9em;
            }
        </style>
    </div>
</div>

<?php
