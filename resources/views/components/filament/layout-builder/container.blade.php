@props([
    'container',
    'containerKey',
    'containerBlocks',
])
@php
    use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;

    // Represent in two columns to ensure there's enough space
    $colspan = min(12, max(1, (int) ($container['meta']['colspan'] ?? 12)));

    $responsiveColspans = collect($container['meta']['responsive'] ?? [])
        ->mapWithKeys(fn (array $breakpointData, string $breakpoint): array => [
            $breakpoint => min(12, max(1, (int) ($breakpointData['colspan'] ?? $colspan))),
        ])
        ->all();

    $containerTitle = str($containerKey)->title();
    $containerArea = $this->layoutAreaForContainer($container);
    $containerAreaLabel = $this->layoutAreaLabel($containerArea);

    $containerWidgets = $container['widgets'] ?? $container['blocks'] ?? [];
    $blockCount = count($containerWidgets);
@endphp

<div
    id="layout-container-{{ $containerKey }}"
    tabindex="-1"
    x-data="{
        isCollapsed: false,
        id: {{ Js::from($containerKey) }},
        isResizing: false,
        isResizeHandleFocused: false,
        isResizeHandleHovered: false,
        baseColspan: {{ $colspan }},
        responsiveColspans: {{ Js::from($responsiveColspans) }},
        previewColspan: {{ $colspan }},
        resizeStartX: 0,
        resizeStartColspan: {{ $colspan }},
        activeResponsiveColspan() {
            if (shouldStackContainersForActiveBreakpoint()) {
                return 12
            }

            return this.responsiveColspans[activeBreakpoint] || this.baseColspan
        },
        activeResponsiveOverrideLabel() {
            if (
                shouldStackContainersForActiveBreakpoint() ||
                ! activeBreakpoint ||
                ! this.responsiveColspans[activeBreakpoint]
            ) {
                return ''
            }

            return String(
                {{ Js::from(__('capell-layout-builder::message.responsive_override_active', ['breakpoint' => '__breakpoint__'])) }},
            ).replace('__breakpoint__', activeBreakpoint)
        },
        syncPreviewColspanToBreakpoint() {
            if (this.isResizing) return

            this.previewColspan = this.activeResponsiveColspan()
        },
        rememberPreviewColspan() {
            if (activeBreakpoint) {
                this.responsiveColspans[activeBreakpoint] = this.previewColspan

                return
            }

            this.baseColspan = this.previewColspan
        },
        gridColumnStyle() {
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
            this.$dispatch('layout-builder-suppress-block-actions')
            this.isResizing = true
            this.isResizeHandleFocused = true
            this.resizeStartX = event.clientX
            this.resizeStartColspan = this.previewColspan
        },
        resize(event) {
            if (! this.isResizing) return

            const grid = this.$el.parentBlock
            const columnWidth = grid ? grid.getBoundingClientRect().width / 12 : 1
            const pointerDelta = event.clientX - this.resizeStartX
            const snapDeadZone = 3
            const columnDelta =
                Math.abs(pointerDelta) <= snapDeadZone
                    ? 0
                    : Math.sign(pointerDelta) *
                      Math.max(
                          1,
                          Math.ceil(
                              (Math.abs(pointerDelta) - snapDeadZone) / columnWidth,
                          ),
                      )

            this.previewColspan = Math.min(
                12,
                Math.max(1, this.resizeStartColspan + columnDelta),
            )
        },
        stopResize() {
            if (! this.isResizing) return

            this.isResizing = false
            this.rememberPreviewColspan()
            this.$dispatch('layout-builder-resize-container', {
                containerKey: this.id,
                colspan: this.previewColspan,
                breakpoint: activeBreakpoint,
            })
        },
        resizeWithKeyboard(delta) {
            this.previewColspan = Math.min(
                12,
                Math.max(1, this.previewColspan + delta),
            )

            this.rememberPreviewColspan()
            this.$dispatch('layout-builder-resize-container', {
                containerKey: this.id,
                colspan: this.previewColspan,
                breakpoint: activeBreakpoint,
            })
        },
        setColspan(colspan) {
            this.previewColspan = Math.min(12, Math.max(1, colspan))

            this.rememberPreviewColspan()
            this.$dispatch('layout-builder-resize-container', {
                containerKey: this.id,
                colspan: this.previewColspan,
                breakpoint: activeBreakpoint,
            })
        },
    }"
    wire:key="container-{{ $containerKey }}"
    x-sort:item="'{{ $containerKey }}'"
    x-on:pointermove.window="resize($event)"
    x-on:pointerup.window="stopResize"
    x-on:pointercancel.window="stopResize"
    x-effect="syncPreviewColspanToBreakpoint(activeBreakpoint)"
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
    x-bind:class="{
        'layout-container-selected': isSelectedContainer(id),
        'layout-container-reordering': mode === 'edit',
        'layout-container-resizing': isResizing,
    }"
>
    <section
        class="hover:border-primary-400/60 hover:bg-primary-50/25 focus-within:border-primary-500/70 focus-within:bg-primary-50/30 dark:hover:border-primary-400/50 dark:hover:bg-primary-500/5 dark:focus-within:border-primary-400/70 dark:focus-within:bg-primary-500/10 relative mt-4 rounded-xl border border-dashed border-gray-400 bg-gray-400/5 px-3 pb-3 pt-0 transition-colors lg:px-4 dark:border-gray-700 dark:bg-white/[0.04]"
        x-bind:class="
            isSelectedContainer(id)
                ? 'border-primary-500 bg-primary-50/35 shadow-sm ring-2 ring-primary-500/15 dark:border-primary-400/80 dark:bg-primary-500/10 dark:ring-primary-400/20'
                : ''
        "
    >
        <div
            class="layout-container-rail -mt-4 mb-1 flex flex-wrap items-start gap-x-3 gap-y-2"
        >
            <div
                class="layout-container-primary flex min-w-0 flex-1 flex-wrap items-center gap-x-2.5 gap-y-1.5"
            >
                <div
                    class="layout-container-title-group flex min-w-0 items-center rounded-full bg-white text-sm text-gray-600 shadow-sm dark:bg-gray-950 dark:text-gray-300"
                >
                    <button
                        type="button"
                        class="layout-container-title-button hover:text-primary-600 dark:hover:text-primary-400 flex min-w-0 items-center py-1 pl-3 pr-1 text-left transition"
                        x-on:click="selectContainer(id)"
                    >
                        <span
                            class="layout-container-title whitespace-nowrap text-xs font-medium text-gray-600 dark:text-gray-200"
                        >
                            {{ __('capell-admin::generic.container_name', ['name' => $containerTitle]) }}
                        </span>

                        @if ($containerArea !== LayoutAreaRegistry::MAIN)
                            <span
                                class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-[0.6875rem] font-medium text-gray-500 dark:bg-white/10 dark:text-gray-300"
                            >
                                {{ $containerAreaLabel }}
                            </span>
                        @endif

                        <span
                            class="mx-2 text-gray-300 dark:text-gray-600"
                            x-show="activeResponsiveOverrideLabel()"
                            x-cloak
                        >
                            -
                        </span>
                        <span
                            class="text-primary-600 dark:text-primary-400 whitespace-nowrap text-xs"
                            x-show="activeResponsiveOverrideLabel()"
                            x-text="activeResponsiveOverrideLabel()"
                            x-cloak
                        ></span>
                    </button>

                    <div class="layout-container-title-tool-slot">
                        <x-filament::dropdown
                            placement="bottom-end"
                            width="!w-auto"
                        >
                            <x-slot name="trigger">
                                <x-filament::icon-button
                                    class="layout-container-title-tools"
                                    icon="heroicon-o-adjustments-horizontal"
                                    color="gray"
                                    :label="__('capell-layout-builder::button.container_actions', ['container' => $containerTitle])"
                                />
                            </x-slot>

                            <x-filament::dropdown.list>
                                {{ ($this->editContainerAction)(['containerKey' => $containerKey]) }}

                                {{ ($this->duplicateContainerAction)(['containerKey' => $containerKey]) }}

                                @if (($this->moveContainerUpAction)(['containerKey' => $containerKey])?->isVisible())
                                    {{ ($this->moveContainerUpAction)(['containerKey' => $containerKey]) }}
                                @endif

                                @if (($this->moveContainerDownAction)(['containerKey' => $containerKey])?->isVisible())
                                    {{ ($this->moveContainerDownAction)(['containerKey' => $containerKey]) }}
                                @endif

                                {{ ($this->removeContainerAction)(['containerKey' => $containerKey]) }}
                            </x-filament::dropdown.list>
                        </x-filament::dropdown>
                    </div>

                    @if (count($this->containers) > 1)
                        <div
                            class="layout-container-handle-reveal layout-container-title-handle hidden md:flex"
                            wire:loading.class="pointer-events-none opacity-40"
                        >
                            <button
                                type="button"
                                class="layout-container-title-drag-handle m-0 cursor-grab active:cursor-grabbing"
                                tabindex="-1"
                                aria-hidden="true"
                                x-sort:handle
                                x-tooltip.raw="{{ __('capell-layout-builder::button.move_container') }}"
                                aria-label="{{ __('capell-layout-builder::button.move_container') }}"
                            >
                                <x-capell-layout-builder::filament.layout-builder.drag-handle-icon
                                    class="h-4 w-2.5"
                                />
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <div
                class="layout-container-controls ml-auto flex shrink-0 flex-wrap items-center justify-end gap-0.5 rounded-lg bg-white p-1 opacity-100 shadow-sm ring-1 ring-gray-950/10 transition-opacity md:opacity-0 md:group-focus-within/container:opacity-100 md:group-hover/container:opacity-100 dark:bg-gray-950 dark:ring-white/10"
            >
                <x-filament::icon-button
                    class="layout-container-collapse-button"
                    icon="heroicon-o-chevron-right"
                    size="sm"
                    color="gray"
                    :label="__('capell-layout-builder::button.collapse')"
                    x-on:click="toggleCollapse"
                    x-bind:aria-expanded="(! isCollapsed).toString()"
                    x-bind:class="! isCollapsed ? 'rotate-90' : ''"
                />
            </div>
        </div>

        <div
            class="layout-container-mobile-width-control"
            x-show="! isCollapsed && mode === 'edit' && ! shouldStackContainersForActiveBreakpoint()"
            x-cloak
        >
            <button
                type="button"
                class="layout-container-width-stepper-button"
                x-on:click="resizeWithKeyboard(-1)"
                x-bind:disabled="previewColspan <= 1"
                aria-label="{{ __('capell-layout-builder::message.decrease_container_width', ['container' => $containerTitle]) }}"
            >
                @svg('heroicon-o-minus', 'h-4 w-4')
            </button>

            <span
                class="layout-container-width-stepper-value"
                aria-live="polite"
                x-text="
                    String(
                        {{ Js::from(__('capell-layout-builder::message.container_colspan_value', ['columns' => '__columns__'])) }},
                    ).replace('__columns__', previewColspan)
                "
            ></span>

            <button
                type="button"
                class="layout-container-width-stepper-button"
                x-on:click="resizeWithKeyboard(1)"
                x-bind:disabled="previewColspan >= 12"
                aria-label="{{ __('capell-layout-builder::message.increase_container_width', ['container' => $containerTitle]) }}"
            >
                @svg('heroicon-o-plus', 'h-4 w-4')
            </button>
        </div>

        <button
            type="button"
            class="layout-container-resize-handle absolute right-0 top-1/2 z-10 hidden -translate-y-1/2 translate-x-1/2 cursor-col-resize items-center justify-center md:flex"
            role="slider"
            aria-orientation="horizontal"
            aria-label="{{ __('capell-layout-builder::message.resize_container_width', ['container' => $containerTitle]) }}"
            aria-valuemin="1"
            aria-valuemax="12"
            x-bind:aria-valuenow="previewColspan"
            x-bind:aria-valuetext="
                String(
                    {{ Js::from(__('capell-layout-builder::message.container_colspan_value', ['columns' => '__columns__'])) }},
                ).replace('__columns__', previewColspan)
            "
            x-show="! isCollapsed && ! shouldStackContainersForActiveBreakpoint()"
            x-on:pointerenter="isResizeHandleHovered = true"
            x-on:pointerleave="isResizeHandleHovered = false"
            x-on:pointerdown.stop.prevent="startResize($event)"
            x-on:focus="isResizeHandleFocused = true"
            x-on:blur="isResizeHandleFocused = false"
            x-on:keydown.arrow-left.stop.prevent="resizeWithKeyboard(-1)"
            x-on:keydown.arrow-down.stop.prevent="resizeWithKeyboard(-1)"
            x-on:keydown.arrow-right.stop.prevent="resizeWithKeyboard(1)"
            x-on:keydown.arrow-up.stop.prevent="resizeWithKeyboard(1)"
            x-on:keydown.home.stop.prevent="setColspan(1)"
            x-on:keydown.end.stop.prevent="setColspan(12)"
        >
            <span class="pointer-events-none" aria-hidden="true">
                <svg
                    class="h-3.5 w-3.5"
                    fill="currentColor"
                    viewBox="0 0 16 16"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <circle cx="5" cy="5" r="1.5" />
                    <circle cx="11" cy="5" r="1.5" />
                    <circle cx="5" cy="11" r="1.5" />
                    <circle cx="11" cy="11" r="1.5" />
                </svg>
            </span>
            <span
                class="layout-container-resize-value pointer-events-none absolute"
                x-show="isResizeHandleHovered || isResizeHandleFocused || isResizing"
                x-transition.opacity.duration.100ms
                x-cloak
                aria-hidden="true"
                x-text="previewColspan"
            ></span>
            <span class="sr-only">
                {{ __('capell-layout-builder::message.drag_to_resize_container') }}
            </span>
        </button>

        <div
            x-show="! isCollapsed"
            class="layout-container-blocks"
            x-sort="reorderBlock('{{ $containerKey }}', $item, $position)"
            x-sort:group="blocks"
            x-sort:config="{
                animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches
                    ? 0
                    : 160,
                easing: 'cubic-bezier(0.2, 0, 0, 1)',
                forceFallback: true,
                fallbackClass: 'layout-sort-fallback',
                ghostClass: 'layout-sort-ghost',
                chosenClass: 'layout-sort-chosen',
                dragClass: 'layout-sort-drag',
            }"
        >
            @foreach ($containerWidgets as $blockIndex => $containerBlock)
                <div
                    class="layout-container-block-drop-zone group flex min-h-8 items-center px-3 transition"
                    x-show="shouldShowInsertTargets()"
                    x-transition.opacity
                    x-cloak
                >
                    <div
                        class="text-primary-600 dark:text-primary-400 flex w-full items-center gap-2 text-xs font-medium opacity-100 transition group-focus-within:opacity-100 group-hover:opacity-100 sm:opacity-40"
                    >
                        <span
                            class="border-primary-500/50 h-px flex-1 border-t border-dashed"
                        ></span>
                        <span class="layout-container-block-insert-action">
                            <button
                                type="button"
                                class="fi-btn fi-size-sm fi-btn-color-gray fi-color-gray fi-btn-outlined focus-visible:ring-primary-500 inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                                x-on:click="$wire.mountAction('addBlock', { containerKey: @js($containerKey), position: @js($blockIndex) })"
                            >
                                @svg('heroicon-m-plus', 'h-3.5 w-3.5')
                                <span>
                                    {{ __('capell-layout-builder::button.add_block_here') }}
                                </span>
                            </button>
                        </span>
                        <span
                            class="border-primary-500/50 h-px flex-1 border-t border-dashed"
                        ></span>
                    </div>
                </div>

                @if (isset($containerBlocks[$blockIndex]))
                    <x-capell-layout-builder::filament.layout-builder.block
                        :$containerKey
                        :$containerBlock
                        :$loop
                        :block="$containerBlocks[$blockIndex]"
                        :$blockIndex
                    />
                @else
                    <div
                        class="layout-container-block border-b border-dashed border-rose-200 bg-rose-50/70 px-4 py-3 text-sm text-rose-700 last:rounded-b-lg dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200"
                    >
                        {{ __('capell-admin::message.unknown_block', ['block' => $containerBlock['block_key'] ?? __('capell-admin::generic.unknown')]) }}
                    </div>
                @endif
            @endforeach

            <div
                class="layout-container-block-drop-zone group flex min-h-8 items-center px-3 transition"
                x-show="shouldShowInsertTargets()"
                x-transition.opacity
                x-cloak
            >
                <div
                    class="text-primary-600 dark:text-primary-400 flex w-full items-center gap-2 text-xs font-medium opacity-100 transition group-focus-within:opacity-100 group-hover:opacity-100 sm:opacity-40"
                >
                    <span
                        class="border-primary-500/50 h-px flex-1 border-t border-dashed"
                    ></span>
                    <span class="layout-container-block-insert-action">
                        <button
                            type="button"
                            class="fi-btn fi-size-sm fi-btn-color-gray fi-color-gray fi-btn-outlined focus-visible:ring-primary-500 inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                            x-on:click="$wire.mountAction('addBlock', { containerKey: @js($containerKey), position: @js(count($containerWidgets)) })"
                        >
                            @svg('heroicon-m-plus', 'h-3.5 w-3.5')
                            <span>
                                {{ __('capell-layout-builder::button.add_block_here') }}
                            </span>
                        </button>
                    </span>
                    <span
                        class="border-primary-500/50 h-px flex-1 border-t border-dashed"
                    ></span>
                </div>
            </div>
        </div>
    </section>
</div>
