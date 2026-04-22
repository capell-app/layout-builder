<?php

declare(strict_types=1);

?>

{{-- format-ignore-start --}}
@php
    use Capell\Admin\Enums\AlertTypeEnum;
    use Capell\Admin\Enums\ResourceEnum;
    use Capell\Admin\Facades\CapellAdmin;
    use Filament\Support\Enums\Size;
    use Filament\Support\Facades\FilamentAsset;

    $changeLayoutAction = $this->changeLayoutAction;
    $duplicateLayoutAction = $this->duplicateLayoutAction;
    $addWidgetAction = $this->addWidgetAction;
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

        <div class="space-y-5">
            @if ($containers)
                <div
                    class="layout-containers mb-4 grid grid-cols-12 gap-4 lg:gap-6"
                    x-sort="$wire.reorderContainers($item, $position)"
                    x-sort:config="{ forceFallback: true, fallbackClass: 'sortable-fallback' }"
                >
                    @foreach ($containers as $containerKey => $container)
                        <x-capell-mosaic::filament.layout-builder.container
                            :$container
                            :$containerKey
                            :containerWidgets="$this->containerWidgets[$containerKey] ?? []"
                        />
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

        <div class="mt-6 flex items-center justify-center gap-4">
            @if ($addWidgetAction->isVisible())
                {{ $addWidgetAction }}
            @endif

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

    <x-filament-actions::modals />
</div>

<?php
