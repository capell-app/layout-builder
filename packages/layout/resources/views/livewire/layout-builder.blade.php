<?php

declare(strict_types=1);

?>

@php
    use Capell\Admin\Enums\AlertTypeEnum;
    use Capell\Admin\Enums\ResourceEnum;
    use Capell\Admin\Facades\CapellAdmin;
    use Capell\Layout\Livewire\LayoutBuilder;
    use Filament\Support\Enums\Size;

    $changeLayoutAction = $this->changeLayoutAction;
    $duplicateLayoutAction = $this->duplicateLayoutAction;
    $addWidgetAction = $this->addWidgetAction;
@endphp

<div>
    <div
        x-load
        x-load-src="{{
            Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc(
                'layout-builder',
                'capell-layout'
            )
        }}"
        x-data="layoutBuilderComponent"
        x-on:expand-all-containers.window="expandAll"
        x-on:collapse-all-containers.window="collapseAll"
    >
        <div
            class="mb-4 flex flex-wrap justify-between gap-4 pl-1 pr-4 sm:flex-nowrap lg:justify-end"
        >
            <div class="flex-grow">
                <div class="text-lg font-semibold">
                    {{ __('capell-admin::heading.layout_record', ['name' => $this->layout->name]) }}
                </div>

                <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    @svg('heroicon-o-information-circle', 'inline-block h-6 w-6')

                    <span class="text-gray-800 dark:text-gray-300">
                        {!!
                            trans_choice('capell-admin::message.layout_count_on_pages', $this->layout->pages_count, [
                                'count' => $this->layout->pages_count,
                                'url' => CapellAdmin::getResource(ResourceEnum::Page)::getUrl('index', [
                                    'tableFilters' => ['layout_id' => ['value' => $this->layout->id]],
                                ]),
                            ])
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
                @if ($this->page_id || $changeLayoutAction->isVisible())
                    <div class="flex flex-wrap items-center justify-end gap-4">
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
                                        href="{{ CapellAdmin::getResource(ResourceEnum::Layout)::getUrl('edit', ['record' => $this->layout_id]) }}"
                                        icon="heroicon-o-arrow-top-right-on-square"
                                        target="_blank"
                                        tag="a"
                                    >
                                        {{ __('capell-admin::button.open_edit_layout') }}
                                    </x-filament::dropdown.list.item>
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>
                        </div>
                    </div>
                @endif

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
                        x-tooltip.raw="{{ __('capell-admin::button.expand_all') }}"
                    >
                        {{ __('capell-admin::button.expand') }}
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
                        x-tooltip.raw="{{ __('capell-admin::button.collapse_all') }}"
                    >
                        {{ __('capell-admin::button.collapse') }}
                    </x-filament::link>
                </div>
            </div>
        </div>

        @if ($this->layoutModified)
            <x-capell-admin::alert
                class="mb-5"
                :color="AlertTypeEnum::Warning->value"
                :actions="[$this->saveLayoutAction]"
                icon="heroicon-o-exclamation-triangle"
            >
                <x-slot:description>
                    {{ __('capell-admin::message.layout_unsaved') }}
                </x-slot>
            </x-capell-admin::alert>
        @endif

        <div class="space-y-5">
            @if ($containers)
                <div
                    class="layout-containers mb-4 grid grid-cols-12 gap-4 lg:gap-6"
                    x-sort="$wire.reorderContainers($item, $position)"
                    x-sort:config="{ forceFallback: true, fallbackClass: 'sortable-fallback' }"
                >
                    @foreach ($containers as $containerKey => $container)
                        <x-capell-layout::layout-builder.container
                            :$container
                            :$containerKey
                            :containerWidgets="$this->containerWidgets[$containerKey] ?? []"
                        />
                    @endforeach
                </div>
            @else
                <div
                    class="px-3 text-center text-sm font-semibold text-gray-600 dark:text-gray-100"
                >
                    {{ __('capell-admin::message.layout_empty') }}
                </div>
            @endif
        </div>

        <div class="mt-6 flex items-center justify-center gap-4">
            @if ($addWidgetAction->isVisible())
                {{ $addWidgetAction }}
            @endif

            {{ $this->addContainerAction }}

            <x-filament::button
                color="gray"
                :size="Size::Small"
                outlined
                x-on:click="toggleReordering"
                x-bind:class="isReordering ? '!bg-primary-500/5 !ring-primary-600 !text-primary-600' : ''"
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
                            ? '{{ __('capell-admin::button.reorder') }}'
                            : '{{ __('capell-admin::button.cancel_reorder') }}'
                    "
                ></span>
            </x-filament::button>
        </div>
    </div>

    <x-filament-actions::modals />
</div>

<?php
