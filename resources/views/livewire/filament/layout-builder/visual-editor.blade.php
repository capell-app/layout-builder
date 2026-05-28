@php
    use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
    use Illuminate\Support\Js;

    $tree = $this->layoutBuilderTree;
    $activePreviewBreakpoint = $this->activeBreakpoint ?? LayoutBreakpoint::Desktop;
    $breakpointWidths = collect(LayoutBreakpoint::cases())
        ->mapWithKeys(fn (LayoutBreakpoint $breakpoint): array => [$breakpoint->value => $breakpoint->maxCanvasWidth()])
        ->all();
@endphp

@once
    <script>
        window.capellLayoutBuilderVisualEditor = (config = {}) => ({
            treeOpen: false,
            inspectorOpen: false,
            search: '',
            selectedNode: config.selectedNode || null,
            activeBreakpoint: config.activeBreakpoint || 'desktop',
            breakpointWidths: config.breakpointWidths || {},
            previewSignature: config.previewSignature || '',
            init() {
                this.renderPreview()
            },
            shadowStyles() {
                return `
                    :host { all: initial; color: #111827; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
                    *, *::before, *::after { box-sizing: border-box; }
                    a, button, input, select, textarea, form { pointer-events: none !important; }
                    .clb-preview-page { min-height: 100%; background: #f8fafc; color: #111827; }
                    .clb-preview-header { display: flex; align-items: end; justify-content: space-between; gap: 1rem; padding: 2rem clamp(1rem, 3vw, 2.5rem); border-bottom: 1px solid #e5e7eb; background: #fff; }
                    .clb-preview-kicker { margin-bottom: .35rem; color: #64748b; font-size: .75rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
                    .clb-preview-header h1 { margin: 0; font-size: clamp(1.875rem, 4vw, 3.5rem); line-height: 1.05; font-weight: 750; letter-spacing: 0; }
                    .clb-preview-main { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 1rem; padding: clamp(1rem, 3vw, 2.5rem); }
                    .clb-preview-container { position: relative; grid-column: span var(--clb-preview-colspan) / span var(--clb-preview-colspan); min-width: 0; border: 1px solid rgba(148, 163, 184, .55); border-radius: .75rem; background: rgba(255,255,255,.7); padding: 1rem; transition: box-shadow .15s ease, outline-color .15s ease; }
                    .clb-preview-container-label { display: inline-flex; margin-bottom: .75rem; border-radius: 999px; background: #f1f5f9; padding: .25rem .625rem; color: #475569; font-size: .75rem; font-weight: 650; }
                    .clb-preview-blocks { display: grid; gap: .875rem; }
                    .clb-preview-block { position: relative; border-radius: .625rem; outline: 2px solid transparent; outline-offset: 3px; transition: outline-color .15s ease, box-shadow .15s ease; }
                    .clb-preview-widget, .layout-builder-block-preview { overflow: hidden; border: 1px solid #e5e7eb; border-radius: .625rem; background: #fff; box-shadow: 0 1px 2px rgba(15,23,42,.06); }
                    .clb-preview-widget-body { display: flex; gap: .875rem; padding: 1rem; }
                    .clb-preview-widget-icon { display: inline-flex; width: 2.25rem; height: 2.25rem; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: .5rem; background: #eff6ff; color: #2563eb; }
                    .clb-preview-widget-type { margin-bottom: .25rem; color: #64748b; font-size: .72rem; font-weight: 700; text-transform: uppercase; }
                    .clb-preview-widget h2, .layout-builder-block-preview h2 { margin: 0; font-size: 1rem; line-height: 1.35; font-weight: 700; letter-spacing: 0; }
                    .clb-preview-widget p { margin: .375rem 0 0; color: #475569; font-size: .875rem; line-height: 1.5; }
                    .layout-builder-block-preview { padding: 1rem; }
                    .layout-block-preview-actions, .layout-block-assets-toggle { display: none !important; }
                    .clb-preview-empty { border: 1px dashed #cbd5e1; border-radius: .625rem; padding: 1rem; color: #64748b; text-align: center; }
                    [data-clb-preview-node] { cursor: pointer; pointer-events: auto; }
                    [data-clb-preview-node]:hover { outline: 2px solid rgba(59, 130, 246, .55); outline-offset: 3px; }
                    [data-clb-preview-node].is-selected { outline: 3px solid #2563eb; outline-offset: 4px; box-shadow: 0 0 0 5px rgba(37, 99, 235, .12); }
                    @media (max-width: 720px) { .clb-preview-main { grid-template-columns: 1fr; } .clb-preview-container { grid-column: 1 / -1; } }
                `
            },
            renderPreview() {
                const host = this.$refs.previewHost

                if (!host) return

                const root =
                    host.shadowRoot || host.attachShadow({ mode: 'open' })
                const template = this.$refs.previewTemplate
                const html = template ? template.innerHTML : ''

                root.innerHTML = `<style>${this.shadowStyles()}</style>${html}`
                root.querySelectorAll('[data-clb-preview-node]').forEach(
                    (node) => {
                        if (node.dataset.clbPreviewNode === this.selectedNode) {
                            node.classList.add('is-selected')
                        }

                        node.addEventListener('click', (event) => {
                            event.preventDefault()
                            event.stopPropagation()
                            this.selectedNode = node.dataset.clbPreviewNode
                            this.$wire.selectPreviewNode(this.selectedNode)
                        })
                    },
                )
            },
            requestPreviewRefresh() {
                const token =
                    window.crypto?.randomUUID?.() ||
                    `${Date.now()}-${Math.random()}`
                let handled = false

                const consumePageState = (event) => {
                    if (event.detail?.token !== token) return

                    handled = true
                    window.removeEventListener(
                        'capell-layout-builder:page-state',
                        consumePageState,
                    )
                    this.$wire.refreshVisualPreview(event.detail?.state || {})
                }

                window.addEventListener(
                    'capell-layout-builder:page-state',
                    consumePageState,
                )
                window.dispatchEvent(
                    new CustomEvent(
                        'capell-layout-builder:request-page-state',
                        { detail: { token } },
                    ),
                )

                window.setTimeout(() => {
                    if (handled) return

                    window.removeEventListener(
                        'capell-layout-builder:page-state',
                        consumePageState,
                    )
                    this.$wire.refreshVisualPreview({})
                }, 75)
            },
            setActiveBreakpointPreview(breakpoint) {
                this.activeBreakpoint = breakpoint || 'desktop'
                this.$wire.setActiveBreakpoint(this.activeBreakpoint)
            },
            activeBreakpointMaxCanvasWidth() {
                return this.breakpointWidths[this.activeBreakpoint] || '100%'
            },
            activeBreakpointMinCanvasWidth() {
                return this.activeBreakpoint === 'desktop' ? '48rem' : '0'
            },
            shouldStackContainersForActiveBreakpoint() {
                return this.activeBreakpoint === 'mobile'
            },
            itemMatches(element) {
                const term = this.search.trim().toLowerCase()

                if (!term) return true

                return (element.dataset.layoutBuilderTreeSearch || '')
                    .toLowerCase()
                    .includes(term)
            },
            openTree() {
                this.treeOpen = true
                this.$nextTick(() => this.$refs.treeDrawer?.focus())
            },
            closeTree() {
                this.treeOpen = false
                this.$nextTick(() => this.$refs.treeToggle?.focus())
            },
            openInspector() {
                this.inspectorOpen = true
                this.$nextTick(() => this.$refs.inspectorDrawer?.focus())
            },
            closeInspector() {
                this.inspectorOpen = false
                this.$nextTick(() => this.$refs.inspectorToggle?.focus())
            },
            selectFromTree(node, callback) {
                this.selectedNode = node
                callback()

                if (window.matchMedia('(max-width: 1023px)').matches) {
                    this.treeOpen = false
                    this.inspectorOpen = true
                }
            },
        })
    </script>
@endonce

<section
    x-data="window.capellLayoutBuilderVisualEditor({
                selectedNode: {{ Js::from($this->selectedPreviewNodeHandle) }},
                activeBreakpoint: {{ Js::from($activePreviewBreakpoint->value) }},
                breakpointWidths: {{ Js::from($breakpointWidths) }},
                previewSignature: {{ Js::from($this->visualPreviewSignature) }},
            })"
    x-on:keydown.escape.window="treeOpen ? closeTree() : inspectorOpen ? closeInspector() : null"
    class="layout-builder-visual-editor"
>
    <div class="layout-builder-visual-toolbar">
        <div class="layout-builder-visual-toolbar-start">
            <button
                x-ref="treeToggle"
                type="button"
                class="layout-builder-panel-toggle"
                x-on:click="openTree()"
            >
                @svg('heroicon-o-bars-3-bottom-left', 'h-5 w-5')
                <span>
                    {{ __('capell-layout-builder::button.structure') }}
                </span>
            </button>

            <button
                x-ref="inspectorToggle"
                type="button"
                class="layout-builder-panel-toggle"
                x-on:click="openInspector()"
            >
                @svg('heroicon-o-adjustments-horizontal', 'h-5 w-5')
                <span>
                    {{ __('capell-layout-builder::button.inspector') }}
                </span>
            </button>
        </div>

        <div class="layout-builder-visual-status">
            <div
                class="layout-builder-breakpoint-controls"
                aria-label="{{ __('capell-layout-builder::button.preview_breakpoint') }}"
            >
                @foreach (LayoutBreakpoint::cases() as $breakpoint)
                    <button
                        type="button"
                        class="layout-builder-breakpoint-button"
                        x-on:click="setActiveBreakpointPreview(@js($breakpoint->value))"
                        x-bind:aria-pressed="activeBreakpoint === @js($breakpoint->value)"
                    >
                        @svg(match ($breakpoint) {
                            LayoutBreakpoint::Desktop => 'heroicon-o-computer-desktop',
                            LayoutBreakpoint::Tablet => 'heroicon-o-device-tablet',
                            LayoutBreakpoint::Mobile => 'heroicon-o-device-phone-mobile',
                        }, 'h-4 w-4')
                        <span class="sr-only">
                            {{ __('capell-layout-builder::button.' . $breakpoint->value) }}
                        </span>
                    </button>
                @endforeach
            </div>

            <span
                @class([
                    'layout-builder-preview-status',
                    'layout-builder-preview-status-stale' => $visualPreviewStatus === 'stale',
                    'layout-builder-preview-status-error' => $visualPreviewStatus === 'error',
                ])
            >
                {{ __('capell-layout-builder::message.preview_status_' . $visualPreviewStatus) }}
            </span>

            <x-filament::button
                color="gray"
                icon="heroicon-o-arrow-path"
                size="sm"
                type="button"
                x-on:click="requestPreviewRefresh()"
                wire:loading.attr="disabled"
                wire:target="refreshVisualPreview"
            >
                {{ __('capell-layout-builder::button.refresh_preview') }}
            </x-filament::button>

            {{ $this->undoLayoutMutationAction }}
            {{ $this->redoLayoutMutationAction }}
            {{ $this->saveLayoutAction }}
        </div>
    </div>

    <div class="layout-builder-visual-grid">
        <aside
            class="layout-builder-visual-panel layout-builder-visual-panel-tree"
        >
            @include('capell-layout-builder::livewire.filament.layout-builder.visual-tree', ['tree' => $tree])
        </aside>

        <div
            class="layout-builder-visual-canvas layout-builder-canvas-scroll"
            data-match-frontend-container-layout="{{ config('capell-layout-builder.preview.match_frontend_container_layout', true) ? 'true' : 'false' }}"
            x-bind:data-stack-containers="shouldStackContainersForActiveBreakpoint() ? 'true' : 'false'"
        >
            <template x-ref="previewTemplate">
                {!! $this->visualPreviewHtml !!}
            </template>

            <div
                wire:key="layout-builder-shadow-preview-{{ $this->visualPreviewSignature }}"
                x-ref="previewHost"
                x-init="$nextTick(() => renderPreview())"
                class="layout-builder-shadow-preview"
                x-bind:style="{
                    maxWidth: activeBreakpointMaxCanvasWidth(),
                    minWidth: activeBreakpointMinCanvasWidth(),
                }"
            ></div>
        </div>

        <aside
            class="layout-builder-visual-panel layout-builder-visual-panel-inspector"
        >
            @include('capell-layout-builder::livewire.filament.layout-builder.visual-inspector')
        </aside>
    </div>

    <div
        x-show="treeOpen || inspectorOpen"
        x-cloak
        class="layout-builder-drawer-backdrop"
        x-on:click="treeOpen ? closeTree() : closeInspector()"
    ></div>

    <aside
        x-ref="treeDrawer"
        x-show="treeOpen"
        x-cloak
        x-transition
        tabindex="-1"
        class="layout-builder-responsive-drawer layout-builder-responsive-drawer-left"
    >
        @include('capell-layout-builder::livewire.filament.layout-builder.visual-tree', ['tree' => $tree])
    </aside>

    <aside
        x-ref="inspectorDrawer"
        x-show="inspectorOpen"
        x-cloak
        x-transition
        tabindex="-1"
        class="layout-builder-responsive-drawer layout-builder-responsive-drawer-right"
    >
        @include('capell-layout-builder::livewire.filament.layout-builder.visual-inspector')
    </aside>
</section>
