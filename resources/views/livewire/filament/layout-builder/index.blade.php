{{-- format-ignore-start --}}
@php
    use Capell\Admin\Enums\AlertTypeEnum;
    use Capell\Admin\Enums\ResourceEnum;
    use Capell\Admin\Facades\CapellAdmin;
    use Filament\Support\Enums\Size;
    use Filament\Support\Facades\FilamentAsset;

    $changeLayoutAction = $this->changeLayoutAction;
    $duplicateLayoutAction = $this->duplicateLayoutAction;
    $widgetPalette = $this->widgetPalette;
@endphp
{{-- format-ignore-end --}}
<div>
    <div
        x-load
        x-load-src="{{
            FilamentAsset::getAlpineComponentSrc(
                'layout-builder',
                'capell-mosaic',
            )
        }}"
        x-data="layoutBuilderComponent"
        x-on:expand-all-containers.window="expandAll"
        x-on:collapse-all-containers.window="collapseAll"
    >
        <div x-data="{ isWidgetPaletteOpen: false }">
            <div
                class="mb-4 flex flex-wrap justify-between gap-4 pl-1 pr-4 sm:flex-nowrap lg:justify-end"
            >
                <div class="grow">
                    <div class="text-lg font-semibold">
                        {{ __('capell-mosaic::heading.layout_record', ['name' => $this->layout->name]) }}
                    </div>

                    <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        @svg('heroicon-o-information-circle', 'inline-block h-6 w-6')

                        <span class="text-gray-800 dark:text-gray-300">
                            {!!
                                trans_choice(
                                    'capell-mosaic::message.layout_count_on_pages',
                                    $this->layoutPagesCount,
                                    [
                                        'count' => $this->layoutPagesCount,
                                        'url' => CapellAdmin::getResource(ResourceEnum::Page)::getUrl(
                                            'index',
                                            ['filters' => ['layout_id' => ['value' => $this->layout->id]]],
                                        ),
                                    ],
                                )
                            !!}
                        </span>

                        @if ($duplicateLayoutAction->isVisible())
                            <span class="font-medium">
                                {!! __('capell-admin::generic.copy_page_layout', ['link' => $duplicateLayoutAction->link()->size(Size::Small)->toHtml()]) !!}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="ml-auto mt-auto space-y-4 text-right">
                    @if ($this->page || $changeLayoutAction->isVisible())
                        <div
                            class="flex flex-wrap items-center justify-end gap-4"
                        >
                            <div class="fi-btn-group flex items-center">
                                {{ $this->changeLayoutAction }}

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
                                        @if ($duplicateLayoutAction->isVisible())
                                            {{ $duplicateLayoutAction->grouped() }}
                                        @endif

                                        <x-filament::dropdown.list.item
                                            href="{{ CapellAdmin::getResource(ResourceEnum::Layout)::getUrl('edit', ['record' => $this->layout->id]) }}"
                                            icon="heroicon-o-arrow-top-right-on-square"
                                            target="_blank"
                                            tag="a"
                                        >
                                            {{ __('capell-mosaic::button.open_edit_layout') }}
                                        </x-filament::dropdown.list.item>
                                    </x-filament::dropdown.list>
                                </x-filament::dropdown>
                            </div>
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <x-filament::button
                            icon="heroicon-o-squares-2x2"
                            size="sm"
                            color="primary"
                            x-on:click="isWidgetPaletteOpen = true"
                        >
                            {{ __('capell-mosaic::button.open_widget_palette') }}
                        </x-filament::button>

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
                                x-on:click="$dispatch('expand-all-containers')"
                                x-show="isContainersAllCollapsed !== false"
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
                                x-on:click="$dispatch('collapse-all-containers')"
                                x-show="isContainersAllCollapsed !== true"
                                x-tooltip.raw="{{ __('capell-mosaic::button.collapse_all') }}"
                            >
                                {{ __('capell-mosaic::button.collapse') }}
                            </x-filament::link>
                        </div>
                    </div>
                </div>
            </div>

            @if ($this->layoutModified)
                <x-filament::callout
                    icon="heroicon-o-exclamation-triangle"
                    color="info"
                    class="mb-5"
                >
                    <x-slot name="heading">
                        {{ __('capell-mosaic::message.layout_unsaved') }}
                    </x-slot>

                    @if ($this->saveLayoutAction->isVisible())
                        <x-slot name="controls">
                            {{ $this->saveLayoutAction }}
                        </x-slot>
                    @endif
                </x-filament::callout>
            @endif

            <div
                class="fixed inset-0 z-30 bg-gray-950/50 xl:hidden"
                x-show="isWidgetPaletteOpen"
                x-transition.opacity
                x-on:click="isWidgetPaletteOpen = false"
                x-cloak
            ></div>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="space-y-5">
                    @if ($containers)
                        <div
                            class="layout-containers mb-4 grid grid-cols-1 gap-4 xl:grid-cols-12 xl:gap-6"
                            x-sort="$wire.reorderContainers($item, $position)"
                            x-sort:config="{
                                animation: 180,
                                easing: 'cubic-bezier(0.2, 0, 0, 1)',
                                forceFallback: true,
                                fallbackClass: 'layout-sort-fallback',
                                ghostClass: 'layout-sort-ghost',
                                chosenClass: 'layout-sort-chosen',
                                dragClass: 'layout-sort-drag',
                            }"
                        >
                            @foreach ($containers as $containerKey => $container)
                                <div
                                    class="layout-container-insert-zone col-span-12 -my-1 flex items-center gap-3 px-2"
                                >
                                    <span
                                        class="border-primary-500/45 h-px flex-1 border-t border-dashed"
                                    ></span>

                                    <div
                                        class="border-primary-500/45 text-primary-600 hover:border-primary-500 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-500/10 rounded-full border bg-white text-xs font-medium shadow-sm transition dark:bg-gray-900"
                                    >
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 px-2 py-0.5"
                                            wire:click="insertContainerAtPosition({{ $loop->index }})"
                                        >
                                            @svg('heroicon-m-plus', 'h-3.5 w-3.5')
                                            <span>
                                                {{ __('capell-mosaic::button.container') }}
                                            </span>
                                        </button>
                                        <span class="sr-only">
                                            {{ __('capell-mosaic::message.insert_container_here') }}
                                        </span>
                                    </div>

                                    <span
                                        class="border-primary-500/45 h-px flex-1 border-t border-dashed"
                                    ></span>
                                </div>

                                <x-capell-mosaic::filament.layout-builder.container
                                    :$container
                                    :$containerKey
                                    :containerWidgets="$this->containerWidgets[$containerKey] ?? []"
                                />

                                @if ($loop->last)
                                    <div
                                        class="layout-container-insert-zone col-span-12 -my-1 flex items-center gap-3 px-2"
                                    >
                                        <span
                                            class="border-primary-500/45 h-px flex-1 border-t border-dashed"
                                        ></span>

                                        <div
                                            class="border-primary-500/45 text-primary-600 hover:border-primary-500 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-500/10 rounded-full border bg-white text-xs font-medium shadow-sm transition dark:bg-gray-900"
                                        >
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-1 px-2 py-0.5"
                                                wire:click="insertContainerAtPosition({{ $loop->iteration }})"
                                            >
                                                @svg('heroicon-m-plus', 'h-3.5 w-3.5')
                                                <span>
                                                    {{ __('capell-mosaic::button.container') }}
                                                </span>
                                            </button>
                                            <span class="sr-only">
                                                {{ __('capell-mosaic::message.insert_container_here') }}
                                            </span>
                                        </div>

                                        <span
                                            class="border-primary-500/45 h-px flex-1 border-t border-dashed"
                                        ></span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div
                            class="layout-empty rounded-xl border border-gray-200 p-6 px-3 text-center text-base text-gray-600 dark:border-gray-700 dark:text-gray-100"
                        >
                            {{ __('capell-mosaic::message.layout_empty') }}
                        </div>
                    @endif
                </div>

                <aside
                    class="fixed bottom-0 right-0 top-0 z-40 flex w-[min(26rem,calc(100vw-2rem))] flex-col bg-white p-4 shadow-2xl ring-1 ring-gray-950/10 transition-transform duration-200 xl:sticky xl:top-6 xl:z-auto xl:max-h-[calc(100vh-3rem)] xl:w-auto xl:translate-x-0 xl:rounded-lg xl:p-3 xl:shadow-none dark:bg-gray-900 dark:ring-white/10"
                    x-bind:class="isWidgetPaletteOpen ? 'translate-x-0' : 'translate-x-full xl:translate-x-0'"
                    wire:key="widget-palette"
                    x-on:keydown.escape.window="isWidgetPaletteOpen = false"
                    x-cloak
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <h2
                                class="text-sm font-semibold text-gray-950 dark:text-white"
                            >
                                {{ __('capell-mosaic::form.widget_palette') }}
                            </h2>

                            <span
                                class="text-xs text-gray-500 dark:text-gray-400"
                            >
                                {{ __('capell-mosaic::message.widget_palette_count', ['count' => $widgetPalette->total()]) }}
                            </span>
                        </div>

                        <x-filament::icon-button
                            class="xl:hidden"
                            icon="heroicon-o-x-mark"
                            color="gray"
                            size="sm"
                            x-on:click="isWidgetPaletteOpen = false"
                            :label="__('capell-admin::button.close')"
                        />
                    </div>

                    <x-filament::input.wrapper
                        class="mb-3"
                        prefix-icon="heroicon-m-magnifying-glass"
                    >
                        <x-filament::input
                            type="search"
                            wire:model.live.debounce.300ms="widgetPaletteSearch"
                            :placeholder="__('capell-mosaic::form.search_widgets')"
                        />
                    </x-filament::input.wrapper>

                    <div
                        class="grid min-h-0 flex-1 auto-rows-fr grid-cols-2 gap-2 overflow-y-auto pr-1 sm:grid-cols-3 xl:grid-cols-2"
                        x-sort
                        x-sort:group="widgets"
                        x-sort:config="{
                            group: {
                                name: 'widgets',
                                pull: 'clone',
                                put: false,
                            },
                            sort: false,
                        }"
                    >
                        @forelse ($widgetPalette as $widget)
                            <div
                                class="hover:border-primary-500 hover:bg-primary-50/60 dark:hover:bg-primary-500/10 group flex aspect-square cursor-grab flex-col justify-between rounded-lg border border-gray-200 bg-gray-50 p-3 transition active:cursor-grabbing dark:border-white/10 dark:bg-white/5"
                                draggable="true"
                                x-sort:item="'palette.{{ $widget->getKey() }}'"
                                x-on:dragstart="
                                    $event.dataTransfer.effectAllowed = 'copy'
                                    $event.dataTransfer.setData(
                                        'application/x-capell-widget-id',
                                        '{{ $widget->getKey() }}',
                                    )
                                "
                                wire:key="widget-palette-{{ $widget->getKey() }}"
                            >
                                <div
                                    class="flex items-start justify-between gap-2"
                                >
                                    @svg('heroicon-o-cube-transparent', 'group-hover:text-primary-500 h-5 w-5 shrink-0 text-gray-400 transition')

                                    <span
                                        class="rounded-full bg-white px-1.5 py-0.5 text-[10px] font-medium text-gray-500 ring-1 ring-gray-950/10 dark:bg-gray-900 dark:text-gray-400 dark:ring-white/10"
                                    >
                                        {{ $widget->getKey() }}
                                    </span>
                                </div>

                                <div class="min-w-0">
                                    <div
                                        class="line-clamp-2 text-sm font-semibold leading-5 text-gray-950 dark:text-white"
                                    >
                                        {{ $widget->name }}
                                    </div>

                                    <div
                                        class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {{ $widget->key }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div
                                class="col-span-full rounded-md border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400"
                            >
                                {{ __('capell-mosaic::message.widget_palette_empty') }}
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-3 flex items-center justify-between gap-2">
                        <x-filament::button
                            color="gray"
                            size="xs"
                            icon="heroicon-o-chevron-left"
                            wire:click="previousWidgetPalettePage"
                            :disabled="$widgetPalette->onFirstPage()"
                        >
                            {{ __('capell-mosaic::button.previous') }}
                        </x-filament::button>

                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('capell-mosaic::message.widget_palette_page', ['current' => $widgetPalette->currentPage(), 'last' => $widgetPalette->lastPage()]) }}
                        </span>

                        <x-filament::button
                            color="gray"
                            size="xs"
                            icon="heroicon-o-chevron-right"
                            icon-position="after"
                            wire:click="nextWidgetPalettePage"
                            :disabled="! $widgetPalette->hasMorePages()"
                        >
                            {{ __('capell-mosaic::button.next') }}
                        </x-filament::button>
                    </div>
                </aside>
            </div>

            <div class="mt-6 flex items-center justify-center gap-4">
                {{ $this->addContainerAction }}

                <x-filament::link
                    color="gray"
                    :size="Size::Small"
                    x-on:click="toggleReordering"
                    x-bind:class="isReordering ? '!text-primary-600' : ''"
                >
                    @svg('heroicon-o-arrows-up-down',
                        'inline-block h-4 w-4 text-gray-400 transition duration-75 dark:text-gray-500',
                        ['x-show' => '! isReordering'])
                    @svg('heroicon-o-x-mark',
                        'fi-btn-icon inline-block h-4 w-4 text-gray-400 transition duration-75 dark:text-gray-500',
                        [
                            'x-show' => 'isReordering',
                            'x-cloak' => '',
                        ])
                    <span
                        x-text="
                            ! isReordering
                                ? '{{ __('capell-mosaic::button.reorder') }}'
                                : '{{ __('capell-mosaic::button.cancel_reorder') }}'
                        "
                    ></span>
                </x-filament::link>
            </div>
        </div>
    </div>

    <x-filament-actions::modals />

    <style>
        .layout-sort-ghost {
            opacity: 0.35;
        }

        .layout-sort-chosen {
            outline: 1px solid rgba(var(--primary-500), 0.7);
            outline-offset: 2px;
        }

        .layout-sort-drag,
        .layout-sort-fallback {
            transform: rotate(0.25deg);
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.24);
        }
    </style>
</div>
