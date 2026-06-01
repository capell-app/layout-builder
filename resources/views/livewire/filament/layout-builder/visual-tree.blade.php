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
            <p>
                {{ trans_choice('capell-layout-builder::message.layout_tree_summary', $tree->blockCount, ['containers' => $tree->containerCount, 'blocks' => $tree->blockCount]) }}
            </p>
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
                data-layout-builder-tree-search="{{ $container->label }} {{ $container->areaLabel }} {{ collect($container->blocks)->pluck('label')->implode(' ') }}"
                class="layout-builder-tree-container"
                role="treeitem"
                aria-expanded="true"
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
                            {{ trans_choice('capell-layout-builder::message.layout_tree_block_count', $container->blockCount, ['count' => $container->blockCount]) }}
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
                    class="layout-builder-tree-blocks"
                    role="group"
                >
                    @forelse ($container->blocks as $block)
                        <button
                            type="button"
                            @class([
                                'layout-builder-tree-row layout-builder-tree-row-block',
                                'layout-builder-tree-row-selected' => $block->isSelected,
                            ])
                            x-on:click="selectFromTree(@js($block->nodeId), () => $wire.selectBlock(@js($block->containerKey), @js($block->blockIndex)))"
                        >
                            <span class="layout-builder-tree-row-icon">
                                @svg($block->icon ?: 'heroicon-o-cube', 'h-4 w-4')
                            </span>
                            <span class="layout-builder-tree-row-main">
                                <span>{{ $block->label }}</span>
                                <small>
                                    {{ collect([$block->typeLabel, $block->usesPageContent ? __('capell-layout-builder::generic.page_content_block') : null])->filter()->implode(' · ') }}
                                </small>
                            </span>
                            @if ($block->assetCount > 0)
                                <span class="layout-builder-tree-badge">
                                    {{ $block->assetCount }}
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

    @if ($this->canEditLayout())
        <div class="layout-builder-tree-footer">
            {{ $this->addContainerAction }}
            {{ $this->addBlockAction }}
        </div>
    @endif
</div>
