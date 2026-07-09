@props ([
    'tree',
    'title' => __('capell-layout-builder::heading.layout_structure'),
    'canBrowseStarterLayouts' => false,
])

<div
    class="layout-builder-tree"
    data-layout-builder-surface="tree"
>
    <div class="layout-builder-tree-header">
        <div>
            <h2>{{ $title }}</h2>
            <p class="layout-builder-tree-counts">
                {{ trans_choice('capell-layout-builder::message.layout_tree_container_count', $tree->containerCount, ['count' => $tree->containerCount]) }}
                <span aria-hidden="true">·</span>
                {{ trans_choice('capell-layout-builder::message.layout_tree_widget_count', $tree->widgetCount, ['count' => $tree->widgetCount]) }}
            </p>
        </div>

        <div class="layout-builder-tree-header-actions">
            @if ($this->canEditLayout())
                @php
                    $addActionLabel = $this->selectedContainerKey
                        ? __('capell-layout-builder::button.add_widget_here')
                        : __('capell-layout-builder::button.add_container');
                @endphp

                <x-filament::dropdown
                    class="layout-builder-layout-actions-dropdown"
                    placement="bottom-end"
                    width="!w-auto"
                >
                    <x-slot name="trigger">
                        <x-filament::icon-button
                            color="gray"
                            icon="heroicon-o-plus"
                            size="sm"
                            :label="$addActionLabel"
                            :tooltip="$addActionLabel"
                        />
                    </x-slot>

                    <x-filament::dropdown.list>
                        @if ($this->selectedContainerKey)
                            {{ $this->addWidgetAction }}
                            {{ $this->addContainerAction }}
                        @else
                            {{ $this->addContainerAction }}
                            {{ $this->addWidgetAction }}
                        @endif

                        @if ($canBrowseStarterLayouts)
                            <x-filament::dropdown.list.item
                                icon="heroicon-o-sparkles"
                                x-on:click="
                                    $dispatch('open-modal', {
                                        id: 'capell-layout-builder-starter-layouts',
                                    })
                                "
                            >
                                {{ __('capell-layout-builder::button.browse_starter_layouts') }}
                            </x-filament::dropdown.list.item>
                        @endif
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            @endif

            <button
                type="button"
                class="layout-builder-tree-collapse-button"
                x-on:click="toggleTreeCollapsed()"
                title="{{ __('capell-layout-builder::button.structure') }}"
            >
                @svg ('heroicon-o-x-mark', 'h-4 w-4')
                <span class="sr-only">
                    {{ __('capell-layout-builder::button.structure') }}
                </span>
            </button>
        </div>
    </div>

    <label class="layout-builder-tree-search">
        <span class="sr-only">
            {{ __('capell-layout-builder::form.search_layout_tree') }}
        </span>
        @svg ('heroicon-o-magnifying-glass', 'h-4 w-4')
        <input
            x-ref="treeSearchInput"
            type="search"
            x-model.debounce.150ms="search"
            placeholder="{{ __('capell-layout-builder::form.search_layout_tree') }}"
        />
        <kbd
            class="layout-builder-tree-search-shortcut"
            x-show="!treeSearchActive()"
            aria-hidden="true"
        >
            /
        </kbd>
        <button
            type="button"
            class="layout-builder-tree-search-clear"
            x-show="treeSearchActive()"
            x-on:click="clearTreeSearch()"
            aria-label="{{ __('capell-layout-builder::button.clear_layout_tree_search') }}"
        >
            @svg ('heroicon-o-x-mark', 'h-4 w-4')
        </button>
    </label>

    <div
        class="layout-builder-tree-search-meta"
        x-show="treeSearchActive()"
    >
        <span x-text="treeSearchResultLabel()"></span>
    </div>

    <div class="layout-builder-tree-list">
        @foreach ($tree->containers as $container)
            @php
                $containerSearchText = collect([
                    $container->label,
                    $container->key,
                    $container->areaLabel,
                    trans_choice('capell-layout-builder::message.layout_tree_widget_count', $container->widgetCount, ['count' => $container->widgetCount]),
                ])->filter()->implode(' ');
            @endphp

            <section
                x-data="{ open: true }"
                x-show="containerMatches($el)"
                data-layout-builder-tree-container
                data-layout-builder-tree-item="{{ $container->key }}"
                data-layout-builder-tree-search="{{ $containerSearchText }}"
                data-layout-builder-tree-node="{{ $container->nodeId }}"
                x-bind:data-layout-builder-selected="
                    $el.dataset.layoutBuilderTreeNode === selectedNode
                        ? 'true'
                        : 'false'
                "
                class="layout-builder-tree-container"
                x-bind:aria-expanded="
                    treeContainerOpen(open, $el) ? 'true' : 'false'
                "
            >
                <button
                    type="button"
                    @class ([
                        'layout-builder-tree-row layout-builder-tree-row-container',
                        'layout-builder-tree-row-selected' => $container->isSelected,
                    ])
                    x-on:click="selectFromTree(@js($container->nodeId), () => $wire.selectContainer(@js($container->key)))"
                >
                    <span class="layout-builder-tree-row-icon">
                        @svg ('heroicon-o-rectangle-group', 'h-4 w-4')
                    </span>
                    <span class="layout-builder-tree-row-main">
                        <span>{{ $container->label }}</span>
                    </span>
                    @if ($container->widgetCount > 0)
                        <span
                            class="layout-builder-tree-badge"
                            title="{{ trans_choice('capell-layout-builder::message.layout_tree_widget_count', $container->widgetCount, ['count' => $container->widgetCount]) }}"
                        >
                            {{ $container->widgetCount }}
                        </span>
                    @endif

                    <span
                        class="layout-builder-tree-chevron"
                        x-on:click.stop="open = !open"
                        x-bind:class="{
                            'rotate-90': treeContainerOpen(
                                open,
                                $el.closest(
                                    '[data-layout-builder-tree-container]',
                                ),
                            ),
                        }"
                    >
                        @svg ('heroicon-o-chevron-right', 'h-4 w-4')
                    </span>
                </button>

                <div
                    x-show="
                        treeContainerOpen(
                            open,
                            $el.closest('[data-layout-builder-tree-container]'),
                        )
                    "
                    x-collapse
                    class="layout-builder-tree-widgets"
                >
                    @forelse ($container->widgets as $widget)
                        @php
                            $widgetSearchText = collect([
                                $widget->label,
                                $widget->typeLabel,
                                $widget->widgetKey,
                                trans_choice('capell-layout-builder::message.layout_tree_asset_count', $widget->assetCount, ['count' => $widget->assetCount]),
                                $widget->usesPageContent ? __('capell-layout-builder::generic.page_content_widget') : null,
                            ])->filter()->implode(' ');
                        @endphp

                        <button
                            type="button"
                            @class ([
                                'layout-builder-tree-row layout-builder-tree-row-widget',
                                'layout-builder-tree-row-selected' => $widget->isSelected,
                            ])
                            x-show="widgetMatches($el)"
                            data-layout-builder-tree-widget
                            data-layout-builder-tree-item="{{ $widget->nodeId }}"
                            data-layout-builder-tree-search="{{ $widgetSearchText }}"
                            data-layout-builder-tree-node="{{ $widget->nodeId }}"
                            x-bind:data-layout-builder-selected="
                                $el.dataset.layoutBuilderTreeNode ===
                                selectedNode
                                    ? 'true'
                                    : 'false'
                            "
                            x-on:click="selectFromTree(@js($widget->nodeId), () => $wire.selectWidget(@js($widget->containerKey), @js($widget->widgetIndex)))"
                        >
                            <span class="layout-builder-tree-row-icon">
                                @svg ($widget->icon ?: 'heroicon-o-cube', 'h-4 w-4')
                            </span>
                            <span class="layout-builder-tree-row-main">
                                <span>{{ $widget->label }}</span>
                                <small>
                                    {{ collect([$widget->typeLabel, $widget->usesPageContent ? __('capell-layout-builder::generic.page_content_widget') : null])->filter()->implode(' · ') }}
                                </small>
                            </span>
                            @if ($widget->assetCount > 0)
                                <span class="layout-builder-tree-badge">
                                    {{ $widget->assetCount }}
                                </span>
                            @endif
                        </button>
                    @empty
                        <div class="layout-builder-tree-empty">
                            {{ __('capell-layout-builder::message.container_empty') }}
                        </div>
                    @endforelse
                </div>
            </section>
        @endforeach

        <div
            class="layout-builder-tree-search-empty"
            x-show="treeSearchActive() && !hasTreeSearchResults()"
        >
            @svg ('heroicon-o-magnifying-glass', 'h-4 w-4')
            <span>
                {{ __('capell-layout-builder::message.layout_tree_search_empty') }}
            </span>
        </div>
    </div>
</div>
