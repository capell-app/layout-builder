@php
    use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
    use Illuminate\Support\Js;
@endphp

<section
    x-data="window.capellLayoutBuilderVisualEditor({
                selectedNode: {{ Js::from($this->selectedPreviewNodeHandle) }},
                activeBreakpoint: {{ Js::from($activePreviewBreakpoint->value) }},
                breakpointWidths: {{ Js::from($breakpointWidths) }},
                previewWidgetActions: {{ Js::from($previewWidgetActions) }},
                previewContainerActions: {{ Js::from($previewContainerActions) }},
                previewStatus: {{ Js::from($this->visualPreviewStatus) }},
                actionLabels:
                    {{
                        Js::from([
                            'addWidgetHere' => __('capell-layout-builder::button.add_widget_here'),
                            'addContainerHere' => __('capell-layout-builder::button.add_container_here'),
                            'area' => __('capell-layout-builder::form.area'),
                            'assets' => __('capell-layout-builder::heading.assets'),
                            'appearance' => __('capell-layout-builder::generic.appearance'),
                            'canvas' => __('capell-layout-builder::generic.canvas'),
                            'container' => __('capell-layout-builder::button.container'),
                            'content' => __('capell-layout-builder::generic.content'),
                            'widgetSettings' => __('capell-layout-builder::button.edit_layout_widget'),
                            'controls' => __('capell-layout-builder::button.controls'),
                            'duplicateContainer' => __('capell-layout-builder::button.duplicate_container'),
                            'duplicate' => __('capell-layout-builder::button.duplicate_widget'),
                            'edit' => __('capell-layout-builder::button.edit_widget'),
                            'editContainer' => __('capell-layout-builder::button.edit_container'),
                            'layout' => __('capell-layout-builder::generic.layout'),
                            'layoutMode' => __('capell-layout-builder::button.advanced_layout'),
                            'page' => __('capell-layout-builder::generic.page'),
                            'placement' => __('capell-layout-builder::generic.placement'),
                            'properties' => __('capell-layout-builder::generic.properties'),
                            'removeContainer' => __('capell-layout-builder::button.remove_container'),
                            'remove' => __('capell-layout-builder::button.remove_widget'),
                            'treeSearchResult' => __('capell-layout-builder::message.layout_tree_search_result'),
                            'treeSearchResults' => __('capell-layout-builder::message.layout_tree_search_results'),
                            'type' => __('capell-layout-builder::generic.type'),
                            'unsavedNavigationWarning' => __('capell-layout-builder::message.layout_unsaved_navigation_warning'),
                            'widget' => __('capell-layout-builder::button.widget'),
                            'widgets' => __('capell-layout-builder::generic.widgets'),
                            'width' => __('capell-layout-builder::generic.width'),
                        ])
                    }},
                previewSignature: {{ Js::from($this->visualPreviewSignature) }},
            })"
    x-on:keydown.escape.window.prevent="handleEscape()"
    x-on:keydown.window="handleGlobalShortcut($event)"
    x-bind:data-tree-collapsed="treeCollapsed ? 'true' : 'false'"
    x-bind:data-inspector-open="selectedPreviewAction() ? 'true' : 'false'"
    data-layout-builder-surface="visual-editor"
    @class([
        'layout-builder-visual-editor',
        'layout-builder-visual-editor-empty' => $tree->widgetCount === 0,
    ])
>
    <div
        class="layout-builder-visual-toolbar"
        data-layout-builder-surface="toolbar"
    >
        <div class="layout-builder-visual-toolbar-start">
            <button
                type="button"
                class="layout-builder-panel-toggle"
                title="{{ __('capell-layout-builder::heading.layout_structure') }}"
                x-ref="treeToggle"
                x-on:click="compactPanels ? openTree($event.currentTarget) : toggleTreeCollapsed()"
                x-bind:aria-pressed="! treeCollapsed"
            >
                @svg('heroicon-o-bars-3-bottom-left', 'h-4 w-4')
                <span class="sr-only">
                    {{ __('capell-layout-builder::heading.layout_structure') }}
                </span>
            </button>

            @if ($this->layoutModified)
                <span
                    class="layout-builder-editor-status layout-builder-editor-status-unsaved"
                >
                    @svg('heroicon-o-exclamation-circle', 'h-3.5 w-3.5')
                    {{ __('capell-layout-builder::message.layout_unsaved') }}
                </span>
            @endif
        </div>

        <div class="layout-builder-visual-actions">
            <div
                class="layout-builder-editor-mode-toggle"
                role="group"
                aria-label="{{ __('capell-layout-builder::button.edit_mode') }}"
            >
                <button
                    type="button"
                    class="layout-builder-editor-mode-button"
                    x-on:click="returnToContentEditor()"
                >
                    {{ __('capell-layout-builder::generic.content') }}
                </button>
                <button
                    type="button"
                    class="layout-builder-editor-mode-button layout-builder-editor-mode-button-active"
                    aria-pressed="true"
                >
                    {{ __('capell-layout-builder::button.advanced_layout') }}
                </button>
            </div>

            <div
                x-show="actionLoading"
                x-cloak
                class="layout-builder-action-inline-loading"
            >
                @svg('heroicon-o-arrow-path', 'h-4 w-4 animate-spin')
                <span>
                    {{ __('capell-layout-builder::message.editor_loading') }}
                </span>
            </div>

            <div
                class="layout-builder-breakpoint-controls layout-builder-command-group"
                aria-label="{{ __('capell-layout-builder::button.preview_breakpoint') }}"
                data-layout-builder-surface="breakpoint-controls"
            >
                <div class="layout-builder-breakpoint-segment">
                    @foreach (LayoutBreakpoint::cases() as $shortcutIndex => $breakpoint)
                        @php
                            $breakpointLabel = __('capell-layout-builder::button.' . $breakpoint->value);
                            $shortcutKey = (string) ($shortcutIndex + 1);
                        @endphp

                        <button
                            type="button"
                            class="layout-builder-breakpoint-button"
                            data-layout-builder-action="preview-{{ $breakpoint->value }}"
                            x-on:click="setActiveBreakpointPreview(@js($breakpoint->value))"
                            x-bind:aria-pressed="activeBreakpoint === @js($breakpoint->value)"
                            title="{{ $breakpointLabel }} - {{ $shortcutKey }}"
                        >
                            @svg(match ($breakpoint) {
                                LayoutBreakpoint::Desktop => 'heroicon-o-computer-desktop',
                                LayoutBreakpoint::Tablet => 'heroicon-o-device-tablet',
                                LayoutBreakpoint::Mobile => 'heroicon-o-device-phone-mobile',
                            }, 'h-4 w-4')
                            <span class="sr-only">
                                {{ $breakpointLabel }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div
                class="layout-builder-toolbar-divider"
                aria-hidden="true"
            ></div>

            <div
                class="layout-builder-history-actions"
                data-layout-builder-surface="history-actions"
            >
                {{ $this->undoLayoutMutationAction }} {{ $this->redoLayoutMutationAction }}
            </div>

            <div class="layout-builder-command-save">
                @if ($this->saveLayoutAction->isVisible())
                    {{ $this->saveLayoutAction }}
                @endif
            </div>
        </div>
    </div>

    <div
        @class([
        'layout-builder-visual-grid',
        'layout-builder-visual-grid-empty' => $tree->widgetCount === 0,
    ])
    >
        <aside
            x-ref="treePanel"
            class="layout-builder-visual-panel layout-builder-visual-panel-tree"
        >
            @include('capell-layout-builder::livewire.filament.layout-builder.visual-tree', ['tree' => $tree, 'canBrowseStarterLayouts' => $canBrowseStarterLayouts ?? false])
        </aside>

        <div
            class="layout-builder-visual-canvas layout-builder-canvas-scroll"
            x-ref="previewCanvas"
            data-layout-builder-surface="preview-canvas"
            data-match-frontend-container-layout="{{ config('capell-layout-builder.preview.match_frontend_container_layout', true) ? 'true' : 'false' }}"
            data-layout-empty="{{ $tree->containerCount === 0 ? 'true' : 'false' }}"
            x-bind:data-active-breakpoint="activeBreakpoint"
            x-bind:data-layout-builder-breakpoint="activeBreakpoint"
            x-bind:data-stack-containers="shouldStackContainersForActiveBreakpoint() ? 'true' : 'false'"
            x-bind:style="{
                '--layout-builder-preview-max-width': activeBreakpointMaxCanvasWidth(),
                '--layout-builder-preview-min-width': activeBreakpointMinCanvasWidth(),
            }"
        >
            <div
                class="layout-builder-preview-status-overlay"
                x-show="previewStatus !== 'current'"
                x-cloak
                x-bind:data-preview-status="previewStatus"
            >
                <span>
                    @svg('heroicon-o-exclamation-triangle', 'h-4 w-4')
                    <span
                        x-text="
                            previewStatus === 'error'
                                ? @js(__('capell-layout-builder::message.preview_status_error'))
                                : previewStatus === 'refreshing'
                                    ? @js(__('capell-layout-builder::message.preview_status_refreshing'))
                                    : @js(__('capell-layout-builder::message.preview_status_stale'))
                        "
                    ></span>
                </span>
                <button
                    type="button"
                    x-show="previewStatus !== 'refreshing'"
                    x-on:click="refreshPreview($event.currentTarget)"
                >
                    @svg('heroicon-o-arrow-path', 'h-4 w-4')
                    {{ __('capell-layout-builder::button.refresh_preview') }}
                </button>
            </div>

            @if ($tree->containerCount === 0 && $this->canEditLayout())
                <div class="layout-builder-canvas-empty-state">
                    <span
                        class="layout-builder-canvas-empty-icon"
                        aria-hidden="true"
                    >
                        @svg('heroicon-o-rectangle-stack', 'h-7 w-7')
                    </span>
                    <h3 class="layout-builder-canvas-empty-heading">
                        {{ __('capell-layout-builder::message.layout_canvas_empty_heading') }}
                    </h3>
                    <p class="layout-builder-canvas-empty-description">
                        {{ __('capell-layout-builder::message.layout_canvas_empty_description') }}
                    </p>
                    <div class="layout-builder-canvas-empty-actions">
                        @if ($canBrowseStarterLayouts ?? false)
                            <x-filament::button
                                color="primary"
                                icon="heroicon-o-sparkles"
                                size="sm"
                                x-on:click="
                                    $dispatch('open-modal', {
                                        id: 'capell-layout-builder-starter-layouts',
                                    })
                                "
                            >
                                {{ __('capell-layout-builder::button.browse_starter_layouts') }}
                            </x-filament::button>
                        @endif

                        {{ $this->addContainerAction }}
                    </div>
                </div>
            @endif

            <script
                type="application/json"
                x-ref="previewWidgetActionsPayload"
            >
                {!! json_encode($previewWidgetActions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
            </script>

            <script
                type="application/json"
                x-ref="previewContainerActionsPayload"
            >
                {!! json_encode($previewContainerActions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
            </script>

            <div
                hidden
                aria-hidden="true"
                x-ref="previewTemplate"
                wire:key="layout-builder-preview-template-{{ $this->visualPreviewSignature }}"
            >
                {!! $this->visualPreviewHtml() !!}
            </div>

            <div
                wire:key="layout-builder-shadow-preview-{{ $this->visualPreviewSignature }}"
                x-ref="previewHost"
                x-init="$nextTick(() => renderPreview())"
                @class([
                    'layout-builder-shadow-preview',
                    'layout-builder-shadow-preview-empty' => $tree->widgetCount === 0,
                ])
            ></div>
        </div>

        <div
            x-show="compactPanels && selectedPreviewAction()"
            x-cloak
            class="layout-builder-drawer-backdrop"
            data-layout-builder-surface="inspector-backdrop"
            x-on:click="clearSelectedPreviewNode()"
        ></div>

        <aside
            x-ref="inspectorPanel"
            x-show="selectedPreviewAction()"
            x-cloak
            x-transition
            tabindex="-1"
            class="layout-builder-inspector-panel"
            data-layout-builder-surface="inspector"
            aria-live="polite"
            x-bind:role="compactPanels ? 'dialog' : null"
            x-bind:aria-modal="compactPanels ? 'true' : null"
            x-bind:aria-labelledby="compactPanels ? 'layout-builder-inspector-title' : null"
            x-trap.noscroll="compactPanels && Boolean(selectedPreviewAction())"
        >
            <template x-if="selectedPreviewAction()">
                <div class="layout-builder-inspector-stack">
                    <div class="layout-builder-inspector-header">
                        <div>
                            <h3
                                id="layout-builder-inspector-title"
                                x-text="selectedPreviewLabel()"
                            ></h3>
                        </div>

                        <div class="layout-builder-inspector-header-actions">
                            <span x-text="selectedPreviewKind()"></span>
                            <button
                                type="button"
                                class="layout-builder-inspector-close"
                                x-on:click="clearSelectedPreviewNode()"
                                title="{{ __('capell-layout-builder::button.close') }}"
                                aria-label="{{ __('capell-layout-builder::button.close') }}"
                            >
                                @svg('heroicon-o-x-mark', 'h-4 w-4')
                            </button>
                        </div>
                    </div>

                    <dl class="layout-builder-inspector-card">
                        <template
                            x-for="row in selectedPreviewMetaRows()"
                            :key="row.label"
                        >
                            <div class="layout-builder-inspector-field">
                                <dt x-text="row.label"></dt>
                                <dd x-text="row.value"></dd>
                            </div>
                        </template>
                    </dl>

                    <div class="layout-builder-inspector-card">
                        <div class="layout-builder-inspector-actions-grid">
                            <button
                                type="button"
                                class="layout-builder-inspector-action"
                                x-bind:data-layout-builder-action="
                                    selectedPreviewAction()?.type === 'container' ? 'edit-container' : 'edit-widget'
                                "
                                x-on:click="
                                    selectedPreviewAction()?.type === 'container'
                                        ? openContainerEditor(selectedNode)
                                        : openWidgetEditor(selectedNode)
                                "
                            >
                                @svg('heroicon-o-pencil-square', 'h-4 w-4')
                                <span>
                                    {{ __('capell-layout-builder::button.edit') }}
                                </span>
                            </button>

                            <button
                                type="button"
                                class="layout-builder-inspector-action layout-builder-inspector-action-secondary"
                                x-bind:disabled="! selectedPreviewAction()?.canEditLayout"
                                x-on:click="
                                    runSelectedPreviewAction(
                                        selectedPreviewAction()?.type === 'container'
                                            ? 'duplicateContainer'
                                            : 'duplicateWidget',
                                        $event.currentTarget,
                                    )
                                "
                            >
                                @svg('heroicon-o-square-2-stack', 'h-4 w-4')
                                <span
                                    x-text="
                                        selectedPreviewAction()?.type === 'container'
                                            ? actionLabels.duplicateContainer
                                            : actionLabels.duplicate
                                    "
                                ></span>
                            </button>

                            <button
                                type="button"
                                class="layout-builder-inspector-action layout-builder-inspector-action-danger"
                                x-bind:disabled="! selectedPreviewAction()?.canEditLayout"
                                x-on:click="
                                    runSelectedPreviewAction(
                                        selectedPreviewAction()?.type === 'container'
                                            ? 'removeContainer'
                                            : 'removeWidget',
                                        $event.currentTarget,
                                    )
                                "
                            >
                                @svg('heroicon-o-trash', 'h-4 w-4')
                                <span>
                                    {{ __('capell-layout-builder::button.remove') }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <div
                        class="layout-builder-inspector-card layout-builder-inspector-widget-controls"
                        x-show="selectedPreviewHasWidgetControls()"
                        x-cloak
                    >
                        <h4>
                            {{ __('capell-layout-builder::button.controls') }}
                        </h4>

                        <div class="layout-builder-inspector-stack-buttons">
                            <button
                                type="button"
                                class="layout-builder-inspector-row-button"
                                data-layout-builder-action="edit-layout-widget"
                                x-show="selectedPreviewAction()?.hasLayoutSettings"
                                x-on:click="runSelectedPreviewAction('editLayoutWidget', $event.currentTarget)"
                            >
                                @svg('heroicon-o-cog-6-tooth', 'h-4 w-4')
                                <span>
                                    {{ __('capell-layout-builder::button.edit_layout_widget') }}
                                </span>
                            </button>

                            <button
                                type="button"
                                class="layout-builder-inspector-row-button"
                                x-show="selectedPreviewAction()?.canTogglePageAssets"
                                x-on:click="runSelectedPreviewAction('togglePageAssets', $event.currentTarget)"
                            >
                                @svg('heroicon-o-arrows-right-left', 'h-4 w-4')
                                <span
                                    x-text="selectedPreviewAction()?.toggleAssetsLabel"
                                ></span>
                            </button>

                            <template
                                x-for="assetType in selectedPreviewAssetTypeDescriptors()"
                                :key="assetType.type"
                            >
                                <div class="layout-builder-inspector-asset-row">
                                    <p
                                        class="layout-builder-inspector-asset-row-label"
                                        x-show="selectedPreviewAssetTypeDescriptors().length > 1"
                                        x-text="assetType.label"
                                    ></p>

                                    <div
                                        class="layout-builder-inspector-asset-row-actions"
                                    >
                                        <button
                                            type="button"
                                            class="layout-builder-inspector-row-button"
                                            x-on:click="
                                                runSelectedPreviewAction(
                                                    'selectAsset',
                                                    $event.currentTarget,
                                                    assetType.type,
                                                )
                                            "
                                        >
                                            @svg('heroicon-o-magnifying-glass', 'h-4 w-4')
                                            <span
                                                x-text="assetType.selectLabel"
                                            ></span>
                                        </button>

                                        <button
                                            type="button"
                                            class="layout-builder-inspector-row-button layout-builder-inspector-row-button-secondary"
                                            x-on:click="
                                                runSelectedPreviewAction(
                                                    'addAsset',
                                                    $event.currentTarget,
                                                    assetType.type,
                                                )
                                            "
                                        >
                                            @svg('heroicon-o-plus-circle', 'h-4 w-4')
                                            <span
                                                x-text="assetType.createLabel"
                                            ></span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </aside>
    </div>

    <div
        x-show="treeOpen"
        x-cloak
        class="layout-builder-drawer-backdrop"
        x-on:click="closeTree()"
    ></div>

    <aside
        x-ref="treeDrawer"
        x-show="treeOpen"
        x-cloak
        x-transition
        tabindex="-1"
        class="layout-builder-responsive-drawer layout-builder-responsive-drawer-left"
        data-layout-builder-surface="structure-drawer"
        x-bind:role="compactPanels ? 'dialog' : null"
        x-bind:aria-modal="compactPanels ? 'true' : null"
        x-bind:aria-label="compactPanels ? @js(__('capell-layout-builder::heading.layout_structure')) : null"
        x-trap.noscroll="compactPanels && treeOpen"
    >
        @include('capell-layout-builder::livewire.filament.layout-builder.visual-tree', ['tree' => $tree, 'canBrowseStarterLayouts' => $canBrowseStarterLayouts ?? false])
    </aside>
</section>
