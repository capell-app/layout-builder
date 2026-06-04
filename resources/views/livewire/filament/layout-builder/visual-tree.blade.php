@props([
    'tree',
])

<div
    class="layout-builder-tree"
    role="tree"
>
    <div class="layout-builder-tree-header">
        <div>
            <h2>
                {{ __('capell-layout-builder::heading.layout_structure') }}
            </h2>
            <div class="layout-builder-tree-counts">
                <span>
                    {{ trans_choice('capell-layout-builder::message.layout_tree_container_count', $tree->containerCount, ['count' => $tree->containerCount]) }}
                </span>
                <span>
                    {{ trans_choice('capell-layout-builder::message.layout_tree_widget_count', $tree->widgetCount, ['count' => $tree->widgetCount]) }}
                </span>
            </div>
        </div>

        <div class="layout-builder-tree-header-actions">
            <button
                type="button"
                class="layout-builder-tree-collapse-button"
                x-on:click="toggleTreeCollapsed()"
                title="{{ __('capell-layout-builder::button.collapse') }}"
            >
                @svg('heroicon-o-chevron-left', 'h-4 w-4')
                <span class="sr-only">
                    {{ __('capell-layout-builder::button.collapse') }}
                </span>
            </button>

            @if ($this->canEditLayout())
                <x-filament::dropdown
                    placement="bottom-end"
                    width="!w-auto"
                >
                    <x-slot name="trigger">
                        <x-filament::icon-button
                            color="gray"
                            icon="heroicon-o-plus"
                            size="sm"
                            :label="__('capell-layout-builder::button.layout_actions')"
                        />
                    </x-slot>

                    <x-filament::dropdown.list>
                        {{ $this->addContainerAction }}
                        {{ $this->addWidgetAction }}
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            @endif
        </div>
    </div>

    <label class="layout-builder-tree-search">
        <span class="sr-only">
            {{ __('capell-layout-builder::form.search_layout_tree') }}
        </span>
        @svg('heroicon-o-magnifying-glass', 'h-4 w-4')
        <input
            type="search"
            x-model.debounce.150ms="search"
            placeholder="{{ __('capell-layout-builder::form.search_layout_tree') }}"
        />
    </label>

    <div class="layout-builder-tree-list">
        @foreach ($tree->containers as $container)
            <section
                x-data="{ open: true }"
                x-show="itemMatches($el)"
                data-layout-builder-tree-search="{{ $container->label }} {{ $container->areaLabel }} {{ collect($container->widgets)->pluck('label')->implode(' ') }}"
                data-layout-builder-tree-node="{{ $container->nodeId }}"
                class="layout-builder-tree-container"
                role="treeitem"
                x-bind:aria-expanded="open ? 'true' : 'false'"
            >
                <button
                    type="button"
                    @class([
                        'layout-builder-tree-row layout-builder-tree-row-container',
                        'layout-builder-tree-row-selected' => $container->isSelected,
                    ])
                    x-on:click="selectFromTree(@js($container->nodeId), () => $wire.selectContainer(@js($container->key)))"
                >
                    <span class="layout-builder-tree-row-icon">
                        @svg('heroicon-o-rectangle-group', 'h-4 w-4')
                    </span>
                    <span class="layout-builder-tree-row-main">
                        <span>{{ $container->label }}</span>
                        <small>
                            {{ trans_choice('capell-layout-builder::message.layout_tree_widget_count', $container->widgetCount, ['count' => $container->widgetCount]) }}
                        </small>
                    </span>
                    <span
                        class="layout-builder-tree-chevron"
                        x-on:click.stop="open = ! open"
                        x-bind:class="{ 'rotate-90': open }"
                    >
                        @svg('heroicon-o-chevron-right', 'h-4 w-4')
                    </span>
                </button>

                <div
                    x-show="open"
                    x-collapse
                    class="layout-builder-tree-widgets"
                    role="group"
                >
                    @forelse ($container->widgets as $widget)
                        <button
                            type="button"
                            @class([
                                'layout-builder-tree-row layout-builder-tree-row-widget',
                                'layout-builder-tree-row-selected' => $widget->isSelected,
                            ])
                            data-layout-builder-tree-node="{{ $widget->nodeId }}"
                            x-on:click="selectFromTree(@js($widget->nodeId), () => $wire.selectWidget(@js($widget->containerKey), @js($widget->widgetIndex)))"
                        >
                            <span class="layout-builder-tree-row-icon">
                                @svg($widget->icon ?: 'heroicon-o-cube', 'h-4 w-4')
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
    </div>
</div>
