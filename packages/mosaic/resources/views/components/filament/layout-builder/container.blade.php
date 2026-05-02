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
        id: {{ Js::from($containerKey) }},
        isResizing: false,
        previewColspan: {{ min(12, max(1, $colspan)) }},
        resizeStartX: 0,
        resizeStartColspan: {{ min(12, max(1, $colspan)) }},
        viewportWidth: window.innerWidth,
        gridColumnStyle() {
            if (this.viewportWidth < 1280) {
                return ''
            }

            return (
                'grid-column: span ' +
                this.previewColspan +
                ' / span ' +
                this.previewColspan
            )
        },
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
        startResize(event) {
            this.isResizing = true
            this.resizeStartX = event.clientX
            this.resizeStartColspan = this.previewColspan
        },
        resize(event) {
            if (! this.isResizing) return

            const grid = this.$el.parentElement
            const columnWidth = grid ? grid.getBoundingClientRect().width / 12 : 1
            const columnDelta = Math.round(
                (event.clientX - this.resizeStartX) / columnWidth,
            )

            this.previewColspan = Math.min(
                12,
                Math.max(1, this.resizeStartColspan + columnDelta),
            )
        },
        stopResize() {
            if (! this.isResizing) return

            this.isResizing = false
            this.$wire.resizeContainer(this.id, this.previewColspan)
        },
    }"
    wire:key="container-{{ $containerKey }}"
    x-sort:item="'{{ $containerKey }}'"
    x-on:pointermove.window="resize($event)"
    x-on:pointerup.window="stopResize"
    x-on:resize.window="viewportWidth = window.innerWidth"
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
        'layout-container group/container relative col-span-12 transition-[grid-column] duration-150 ease-out',
    ])
    x-bind:style="gridColumnStyle()"
>
    <div
        class="rounded-lg bg-white ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10"
    >
        <div
            class="layout-container-header group/container flex min-h-11 cursor-pointer items-center gap-x-4 gap-y-2 rounded-t-lg border-b border-gray-100 bg-gray-50 px-4 hover:bg-gray-50 dark:border-white/5 dark:bg-gray-800 dark:hover:bg-white/5"
            :class="{ 'rounded-b-lg': isCollapsed }"
            x-on:click.self="toggleCollapse"
        >
            <div class="pointer-events-none flex grow items-center gap-x-3">
                @if (count($this->containers) > 1)
                    <div
                        wire:loading.class="pointer-events-none opacity-40"
                        x-sort:handle
                        x-show="isReordering"
                        x-cloak
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
                    x-cloak
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
                        x-tooltip.raw="{{ __('capell-mosaic::button.expand_all') }}"
                    >
                        {{ __('capell-mosaic::button.expand') }}
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
                        x-tooltip.raw="{{ __('capell-mosaic::button.collapse_all') }}"
                    >
                        {{ __('capell-mosaic::button.collapse') }}
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

                        {{ ($this->duplicateContainerAction)(['containerKey' => $containerKey]) }}

                        {{ ($this->removeContainerAction)(['containerKey' => $containerKey]) }}
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            </div>
        </div>

        <button
            type="button"
            class="border-primary-500/50 bg-primary-500/20 absolute bottom-4 right-0 top-12 z-10 hidden w-3 cursor-col-resize rounded-l-md border opacity-0 transition group-hover/container:opacity-100 xl:block"
            title="{{ __('capell-mosaic::message.drag_to_resize_container') }}"
            x-show="! isCollapsed && ! isReordering"
            x-on:pointerdown.stop.prevent="startResize($event)"
        >
            <span class="sr-only">
                {{ __('capell-mosaic::message.drag_to_resize_container') }}
            </span>
        </button>

        <div
            x-show="! isCollapsed"
            class="layout-container-widgets min-h-[52px] rounded-b-lg"
            x-sort="$wire.reorderWidgets('{{ $containerKey }}', $item, $position)"
            x-sort:group="widgets"
            x-sort:config="{
                animation: 160,
                easing: 'cubic-bezier(0.2, 0, 0, 1)',
                forceFallback: true,
                fallbackClass: 'layout-sort-fallback',
                ghostClass: 'layout-sort-ghost',
                chosenClass: 'layout-sort-chosen',
                dragClass: 'layout-sort-drag',
            }"
            x-on:dragover.prevent="$event.dataTransfer.dropEffect = 'copy'"
            x-on:drop.prevent="
                const widgetId = Number(
                    $event.dataTransfer.getData('application/x-capell-widget-id'),
                )
                if (widgetId)
                    $wire.addPaletteWidgetToContainer(widgetId, {{ Js::from($containerKey) }})
            "
        >
            @foreach ($container['widgets'] as $widgetIndex => $containerWidget)
                <div
                    class="layout-container-widget-drop-zone group flex min-h-8 items-center px-3 transition"
                    x-on:drop.stop.prevent="
                        const widgetId = Number(
                            $event.dataTransfer.getData('application/x-capell-widget-id'),
                        )
                        if (widgetId)
                            $wire.addPaletteWidgetToContainer(
                                widgetId,
                                {{ Js::from($containerKey) }},
                                {{ $widgetIndex }},
                            )
                    "
                >
                    <div
                        class="text-primary-600 dark:text-primary-400 pointer-events-none flex w-full items-center gap-2 text-xs font-medium opacity-55 transition group-hover:opacity-100"
                    >
                        <span
                            class="border-primary-500/60 h-px flex-1 border-t border-dashed"
                        ></span>
                        <span
                            class="border-primary-500/60 bg-primary-50 dark:bg-primary-500/10 rounded-full border px-2 py-0.5"
                        >
                            +
                            {{ __('capell-mosaic::message.insert_widget_here') }}
                        </span>
                        <span
                            class="border-primary-500/60 h-px flex-1 border-t border-dashed"
                        ></span>
                    </div>
                </div>

                <x-capell-mosaic::filament.layout-builder.widget
                    :$containerKey
                    :$containerWidget
                    :$loop
                    :widget="$containerWidgets[$widgetIndex]"
                    :$widgetIndex
                />
            @endforeach

            <div
                class="layout-container-widget-drop-zone group flex min-h-8 items-center px-3 transition"
                x-on:drop.stop.prevent="
                    const widgetId = Number(
                        $event.dataTransfer.getData('application/x-capell-widget-id'),
                    )
                    if (widgetId)
                        $wire.addPaletteWidgetToContainer(
                            widgetId,
                            {{ Js::from($containerKey) }},
                            {{ count($container['widgets']) }},
                        )
                "
            >
                <div
                    class="text-primary-600 dark:text-primary-400 pointer-events-none flex w-full items-center gap-2 text-xs font-medium opacity-55 transition group-hover:opacity-100"
                >
                    <span
                        class="border-primary-500/60 h-px flex-1 border-t border-dashed"
                    ></span>
                    <span
                        class="border-primary-500/60 bg-primary-50 dark:bg-primary-500/10 rounded-full border px-2 py-0.5"
                    >
                        + {{ __('capell-mosaic::message.insert_widget_here') }}
                    </span>
                    <span
                        class="border-primary-500/60 h-px flex-1 border-t border-dashed"
                    ></span>
                </div>
            </div>
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
