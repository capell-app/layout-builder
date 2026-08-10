@php
    use Capell\Core\Facades\CapellCore;
    use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
    use Illuminate\Support\Js;

    $tree = $this->layoutBuilderTree;
    $activePreviewBreakpoint = $this->activeBreakpoint ?? LayoutBreakpoint::Desktop;
    $breakpointWidths = collect(LayoutBreakpoint::cases())
        ->mapWithKeys(fn (LayoutBreakpoint $breakpoint): array => [$breakpoint->value => $breakpoint->maxCanvasWidth()])
        ->all();
    $editorPageLabel = (string) (data_get($this->page, 'title') ?: data_get($this->page, 'name') ?: $this->layout->name);
    $previewContainerActions = [];
    $previewWidgetActions = [];

    foreach ($tree->containers as $containerPosition => $treeContainer) {
        $container = $this->containers[$treeContainer->key] ?? [];
        $containerMeta = is_array($container['meta'] ?? null) ? $container['meta'] : [];
        $containerColspan = min(12, max(1, (int) ($containerMeta['colspan'] ?? 12)));
        $containerArea = $this->layoutAreaForContainer(is_array($container) ? $container : []);

        $containerColspanLabel = match ($containerColspan) {
            12 => __('capell-layout-builder::generic.full_width'),
            9 => __('capell-layout-builder::generic.three_quarters'),
            8 => __('capell-layout-builder::generic.two_thirds'),
            6 => __('capell-layout-builder::generic.half_width'),
            4 => __('capell-layout-builder::generic.third_width'),
            3 => __('capell-layout-builder::generic.quarter_width'),
            default => __('capell-layout-builder::message.container_columns', ['columns' => $containerColspan]),
        };

        $previewContainerActions[$treeContainer->nodeId] = [
            'type' => 'container',
            'containerKey' => $treeContainer->key,
            'label' => $treeContainer->label,
            'area' => $containerArea,
            'areaLabel' => $this->layoutAreaLabel($containerArea),
            'widgetCount' => $treeContainer->widgetCount,
            'widgetCountLabel' => trans_choice('capell-layout-builder::message.layout_tree_widget_count', $treeContainer->widgetCount, ['count' => $treeContainer->widgetCount]),
            'colspan' => $containerColspan,
            'colspanLabel' => __('capell-layout-builder::message.container_colspan_value', ['columns' => $containerColspan, 'label' => $containerColspanLabel]),
            'position' => $containerPosition,
            'canEditLayout' => $this->canEditLayout(),
        ];

        foreach ($treeContainer->widgets as $treeWidget) {
            $widget = $this->getContainerWidget($treeWidget->containerKey, $treeWidget->widgetIndex);
            $hasPageAssets = $this->hasPageAssets($treeWidget->containerKey, $treeWidget->widgetIndex);
            $assetTypes = collect($this->getWidgetAssetTypes($widget))
                ->map(static fn (string $assetType): array => [
                    'type' => $assetType,
                    'label' => CapellCore::getAsset($assetType)->getLabel(),
                    'selectLabel' => __('capell-layout-builder::button.select_asset_type', ['asset' => CapellCore::getAsset($assetType)->getLabel()]),
                    'createLabel' => __('capell-layout-builder::button.add_new_asset_type', ['asset' => CapellCore::getAsset($assetType)->getLabel()]),
                ])
                ->values()
                ->all();

            $previewWidgetActions[$treeWidget->nodeId] = [
                'type' => 'widget',
                'nodeId' => $treeWidget->nodeId,
                'containerKey' => $treeWidget->containerKey,
                'widgetIndex' => $treeWidget->widgetIndex,
                'label' => $treeWidget->label,
                'containerLabel' => $treeContainer->label,
                'areaLabel' => $previewContainerActions[$treeContainer->nodeId]['areaLabel'],
                'typeLabel' => $treeWidget->typeLabel,
                'assetCount' => $treeWidget->assetCount,
                'assetCountLabel' => trans_choice('capell-layout-builder::message.layout_tree_asset_count', $treeWidget->assetCount, ['count' => $treeWidget->assetCount]),
                'usesPageContent' => $treeWidget->usesPageContent,
                'assetTypes' => $assetTypes,
                'canEditContent' => $this->canEditContent(),
                'canEditLayout' => $this->canEditLayout(),
                'hasLayoutSettings' => (bool) $this->getContainerWidgetConfigurator($treeWidget->containerKey, $treeWidget->widgetIndex),
                'canTogglePageAssets' => $this->inPageContext() && $assetTypes !== [],
                'toggleAssetsLabel' => __(
                    $hasPageAssets
                        ? 'capell-layout-builder::button.convert_widget_assets'
                        : 'capell-layout-builder::button.convert_page_assets',
                ),
            ];
        }
    }
@endphp

{{-- format-ignore-start --}}
@script
    <script data-navigate-once>
        window.capellLayoutBuilderVisualEditor = (config = {}) => ({
            treeOpen: false,
            treeCollapsed: false,
            compactPanels: false,
            treeReturnFocus: null,
            inspectorReturnFocus: null,
            actionLoading: false,
            search: '',
            selectedNode: config.selectedNode || null,
            activeBreakpoint: config.activeBreakpoint || 'desktop',
            breakpointWidths: config.breakpointWidths || {},
            previewWidgetActions: config.previewWidgetActions || {},
            previewContainerActions: config.previewContainerActions || {},
            actionLabels: config.actionLabels || {},
            previewStatus: config.previewStatus || 'stale',
            previewSignature: config.previewSignature || '',
            init() {
                this.beforeUnloadHandler = (event) => {
                    if (!this.hasUnsavedLayoutChanges()) return;

                    event.preventDefault();
                    event.returnValue = '';
                };
                this.livewireNavigateHandler = (event) => {
                    if (!this.hasUnsavedLayoutChanges()) return;
                    if (window.confirm(this.actionLabels.unsavedNavigationWarning)) return;

                    event.preventDefault();
                };
                window.addEventListener('beforeunload', this.beforeUnloadHandler);
                document.addEventListener('livewire:navigate', this.livewireNavigateHandler);
                this.syncPanelLayout();
                this.previewResizeObserver = new ResizeObserver(() => this.syncPanelLayout());
                this.previewResizeObserver.observe(this.$el);
                this.renderPreview();
                this.previewStatus = this.$wire.visualPreviewStatus || this.previewStatus;
                this.applyPreviewBreakpoint();
            },
            destroy() {
                this.previewResizeObserver?.disconnect();
                window.removeEventListener('beforeunload', this.beforeUnloadHandler);
                document.removeEventListener('livewire:navigate', this.livewireNavigateHandler);
            },
            shadowStyles() {
                return `
                    :host { all: initial; color: #111827; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
                    *, *::before, *::after { box-sizing: border-box; }
                    a, button, input, select, textarea, form { pointer-events: none !important; }
                    .clb-preview-page { min-height: 100%; container-type: inline-size; background: #fff; color: #111827; }
                    .clb-preview-main { display: grid; gap: 1rem; padding: 1.125rem; }
                    .clb-preview-content-layout { display: grid; gap: 1rem; align-items: start; }
                    .clb-preview-content-layout-with-sidebar { grid-template-columns: minmax(0, 1fr) minmax(13.5rem, 18rem); }
                    .clb-preview-region { display: grid; min-width: 0; gap: .75rem; align-self: start; border-radius: .25rem; background: #f5f7f4; padding: .75rem; box-shadow: inset 0 0 0 1px rgba(82,97,91,.14); }
                    .clb-preview-region-main { background: transparent; padding: 0; box-shadow: none; }
                    .clb-preview-region-sidebar { background: #f8fafc; }
                    .clb-preview-region-area { background: #f8fafc; }
                    .clb-preview-region-label { color: #64748b; font-size: .6875rem; font-weight: 800; letter-spacing: 0; text-transform: uppercase; }
                    .clb-preview-region-main > .clb-preview-region-label { display: none; }
                    .clb-preview-container-list { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: .875rem; }
                    .clb-preview-region-sidebar .clb-preview-container-list { grid-template-columns: minmax(0, 1fr); }
                    .clb-preview-container { position: relative; grid-column: span var(--clb-preview-colspan) / span var(--clb-preview-colspan); min-width: 0; border-radius: .125rem; background: #fff; padding: .875rem; box-shadow: inset 0 0 0 1px rgba(82,97,91,.12); transition: background-color .15s ease, box-shadow .15s ease, outline-color .15s ease; }
                    .clb-preview-region-sidebar .clb-preview-container { grid-column: 1 / -1; }
                    .clb-preview-container:hover { background: #fff; box-shadow: inset 0 0 0 1px rgba(8,119,101,.34), 0 10px 22px rgba(15,23,42,.08); }
                    :host([data-active-breakpoint="tablet"]) .clb-preview-container { grid-column: span var(--clb-preview-tablet-colspan, var(--clb-preview-colspan)) / span var(--clb-preview-tablet-colspan, var(--clb-preview-colspan)); }
                    :host([data-active-breakpoint="tablet"]) .clb-preview-content-layout-with-sidebar { grid-template-columns: minmax(0, 1fr); }
                    :host([data-active-breakpoint="mobile"]) .clb-preview-content-layout-with-sidebar { grid-template-columns: minmax(0, 1fr); }
                    :host([data-active-breakpoint="mobile"]) .clb-preview-container { grid-column: 1 / -1; }
                    .clb-preview-container-label { display: inline-flex; margin-bottom: .625rem; border-radius: .125rem; background: #087765; padding: .1875rem .5rem; color: #f8fffb; font-size: .625rem; font-weight: 800; letter-spacing: 0; text-transform: uppercase; }
                    .clb-preview-widgets { display: grid; gap: .75rem; }
                    .clb-preview-widget { position: relative; overflow: hidden; border-radius: .125rem; outline: 2px solid transparent; outline-offset: 0; background: #fff; box-shadow: none; transition: outline-color .15s ease, box-shadow .15s ease, transform .15s ease; }
                    .layout-builder-widget-preview { overflow: hidden; border-radius: .25rem; background: transparent; box-shadow: none; }
                    .clb-preview-widget:hover { box-shadow: inset 0 0 0 1px rgba(8,119,101,.34), 0 10px 22px rgba(15,23,42,.08); transform: translateY(-1px); }
                    .clb-preview-widget-body { display: flex; gap: .75rem; padding: .875rem; }
                    .clb-preview-widget-icon { display: inline-flex; width: 2rem; height: 2rem; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: 999px; background: #eff6ff; color: #2563eb; }
                    .clb-preview-widget-type { margin-bottom: .25rem; color: #64748b; font-size: .72rem; font-weight: 700; letter-spacing: 0; text-transform: uppercase; }
                    .clb-preview-widget h2, .layout-builder-widget-preview h2 { margin: 0; font-size: 1rem; line-height: 1.35; font-weight: 700; letter-spacing: 0; }
                    .clb-preview-widget p { margin: .375rem 0 0; color: #475569; font-size: .875rem; line-height: 1.5; }
                    .layout-builder-widget-preview { padding: .75rem; }
                    .layout-widget-preview-actions, .layout-widget-assets-toggle { display: none !important; }
                    .clb-preview-node-affordance { position: absolute; top: .625rem; right: .625rem; z-index: 2; display: inline-flex; width: 1.5rem; height: 1.5rem; align-items: center; justify-content: center; border-radius: 999px; background: rgba(15,23,42,.08); color: #475569; opacity: .72; pointer-events: none; transition: background-color .15s ease, color .15s ease, opacity .15s ease, transform .15s ease; }
                    .clb-preview-node-affordance svg { width: .875rem; height: .875rem; }
                    [data-clb-preview-node]:hover > .clb-preview-node-affordance,
                    [data-clb-preview-node]:focus-visible > .clb-preview-node-affordance,
                    [data-clb-preview-node].is-selected > .clb-preview-node-affordance { background: rgba(8,119,101,.14); color: #087765; opacity: 1; transform: scale(1.05); }
                    .clb-preview-empty { width: 100%; border-radius: .25rem; background: rgba(255,255,255,.7); padding: .75rem; color: #71717a; text-align: center; }
                    .clb-preview-empty-page { grid-column: 1 / -1; }
                    [data-clb-preview-node] { cursor: pointer; pointer-events: auto; }
                    [data-clb-preview-node]:hover, [data-clb-preview-node]:focus-visible { outline: 1px solid rgba(8,119,101,.42); outline-offset: 2px; }
                    [data-clb-preview-node].is-selected { border-radius: 0 !important; outline: 2px solid #087765; outline-offset: 0; box-shadow: 0 8px 18px rgba(15,23,42,.06); }
                    [data-clb-preview-node].is-selected :where(.layout-builder-widget-preview) { border-radius: 0 !important; }
                    @keyframes clb-preview-spin { to { transform: rotate(360deg); } }
                    .clb-preview-insert { position: relative; z-index: 15; display: flex; min-height: 1rem; align-items: center; justify-content: center; opacity: 0; transition: opacity .15s ease; }
                    .clb-preview-insert::before { position: absolute; left: .25rem; right: .25rem; height: 1px; background: rgba(8, 119, 101, .45); content: ''; }
                    .clb-preview-insert:hover, .clb-preview-insert:focus-within { opacity: 1; }
                    .clb-preview-insert-button { position: relative; z-index: 1; display: inline-flex; width: 1.5rem; height: 1.5rem; align-items: center; justify-content: center; border: 1px solid rgba(8, 119, 101, .34); border-radius: 999px; background: #fff; color: #087765; cursor: pointer; pointer-events: auto !important; box-shadow: 0 4px 12px rgba(15, 23, 42, .12); }
                    .clb-preview-insert-button:hover, .clb-preview-insert-button:focus-visible { border-color: rgba(8, 119, 101, .58); outline: none; }
                    .clb-preview-insert-button:disabled { cursor: wait; opacity: .82; }
                    .clb-preview-insert-button.is-loading { color: transparent; }
                    .clb-preview-insert-button.is-loading::after { position: absolute; inset: .38rem; border: 2px solid rgba(37, 99, 235, .25); border-top-color: #2563eb; border-radius: 999px; content: ''; animation: clb-preview-spin .65s linear infinite; }
                    .clb-preview-container-insert { grid-column: 1 / -1; margin-block: -.375rem; }
                    .clb-preview-widgets > .clb-preview-insert { margin-block: -.4375rem; }
                    @container(max-width: 58rem) { .clb-preview-content-layout-with-sidebar { grid-template-columns: minmax(0, 1fr); } }
                    @media (max-width: 720px) { .clb-preview-content-layout-with-sidebar { grid-template-columns: minmax(0, 1fr); } .clb-preview-container { grid-column: 1 / -1; } }
                    :host-context(.dark) { color: #e2e8f0; }
                    :host-context(.dark) .clb-preview-page { background: #111827; color: #f8fafc; }
                    :host-context(.dark) .clb-preview-region { background: #1f2937; box-shadow: none; }
                    :host-context(.dark) .clb-preview-region-main { background: transparent; }
                    :host-context(.dark) .clb-preview-region-sidebar,
                    :host-context(.dark) .clb-preview-region-area { background: #1e293b; }
                    :host-context(.dark) .clb-preview-region-label { color: #94a3b8; }
                    :host-context(.dark) .clb-preview-container { background: #111827; box-shadow: inset 0 0 0 1px rgba(148,163,184,.2); }
                    :host-context(.dark) .clb-preview-container:hover { background: #1e293b; box-shadow: inset 0 0 0 1px rgba(94,234,212,.52), 0 10px 22px rgba(0,0,0,.28); }
                    :host-context(.dark) .clb-preview-container-label { background: #0f766e; color: #ccfbf1; }
                    :host-context(.dark) .clb-preview-widget { background: #1e293b; box-shadow: none; }
                    :host-context(.dark) .clb-preview-widget:hover { box-shadow: inset 0 0 0 1px rgba(94,234,212,.52), 0 10px 22px rgba(0,0,0,.28); }
                    :host-context(.dark) .clb-preview-widget-icon { background: #1e3a8a; color: #bfdbfe; }
                    :host-context(.dark) .clb-preview-widget-type { color: #94a3b8; }
                    :host-context(.dark) .clb-preview-widget p { color: #cbd5e1; }
                    :host-context(.dark) a { color: #7dd3fc; text-decoration-color: rgba(125,211,252,.56); }
                    :host-context(.dark) a:hover,
                    :host-context(.dark) a:focus-visible { color: #bae6fd; text-decoration-color: #bae6fd; }
                    :host-context(.dark) .clb-preview-empty { background: rgba(255,255,255,.06); color: #cbd5e1; }
                    :host-context(.dark) .clb-preview-node-affordance { background: rgba(255,255,255,.1); color: #cbd5e1; }
                    :host-context(.dark) [data-clb-preview-node]:hover > .clb-preview-node-affordance,
                    :host-context(.dark) [data-clb-preview-node]:focus-visible > .clb-preview-node-affordance,
                    :host-context(.dark) [data-clb-preview-node].is-selected > .clb-preview-node-affordance { background: rgba(94,234,212,.18); color: #5eead4; }
                    :host-context(.dark) [data-clb-preview-node]:hover,
                    :host-context(.dark) [data-clb-preview-node]:focus-visible { outline-color: rgba(94,234,212,.62); }
                    :host-context(.dark) [data-clb-preview-node].is-selected { outline-color: #5eead4; box-shadow: 0 8px 18px rgba(0,0,0,.28); }
                    :host-context(.dark) .clb-preview-insert::before { background: rgba(94,234,212,.48); }
                    :host-context(.dark) .clb-preview-insert-button { border-color: rgba(94,234,212,.46); background: #1e293b; color: #5eead4; box-shadow: 0 4px 12px rgba(0,0,0,.28); }
                `;
            },
            syncPanelLayout() {
                this.compactPanels = this.$el.offsetWidth <= 1152;
            },
            renderPreview() {
                this.syncPreviewPayload();

                const host = this.$refs.previewHost;

                if (!host) return;

                const root = host.shadowRoot || host.attachShadow({ mode: 'open' });
                const template = this.previewTemplateElement();
                const html = template ? template.innerHTML : '';

                host.dataset.activeBreakpoint = this.activeBreakpoint;
                root.innerHTML = `<style>${this.shadowStyles()}</style>${html}`;
                root.querySelectorAll('[data-clb-preview-node]').forEach((node) => {
                    if (node.dataset.clbPreviewNode === this.selectedNode) {
                        node.classList.add('is-selected');
                    }

                    if (node.dataset.clbPreviewNodeType === 'container') {
                        this.preparePreviewContainerNode(node);
                        this.attachWidgetInsertControls(node);
                    }

                    if (node.dataset.clbPreviewNodeType === 'widget') {
                        this.preparePreviewWidgetNode(node);
                    }

                    node.addEventListener('click', (event) => {
                        const selectedTarget = event
                            .composedPath()
                            .find((target) => target instanceof HTMLElement && target.dataset.clbPreviewNode);

                        if (selectedTarget !== node) {
                            return;
                        }

                        event.preventDefault();
                        event.stopPropagation();
                        this.selectPreviewNode(node.dataset.clbPreviewNode, node);
                    });
                });
                this.attachContainerInsertControls(root);
            },
            syncPreviewPayload() {
                this.previewWidgetActions = this.parsePreviewPayload(
                    this.previewPayloadElement('previewWidgetActionsPayload'),
                    this.previewWidgetActions,
                );
                this.previewContainerActions = this.parsePreviewPayload(
                    this.previewPayloadElement('previewContainerActionsPayload'),
                    this.previewContainerActions,
                );
            },
            previewTemplateElement() {
                return this.$el.querySelector('[x-ref="previewTemplate"]') || this.$refs.previewTemplate;
            },
            previewPayloadElement(reference) {
                return this.$el.querySelector(`[x-ref="${reference}"]`) || this.$refs[reference];
            },
            parsePreviewPayload(element, fallback) {
                if (!element?.textContent) return fallback;

                try {
                    return JSON.parse(element.textContent);
                } catch (error) {
                    return fallback;
                }
            },
            icon(name) {
                if (name === 'plus') {
                    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14"/><path d="M5 12h14"/></svg>';
                }

                if (name === 'click') {
                    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M8.25 4.5v8.25l2.25-2.25 1.5 1.5 1.5-1.5 1.5 1.5 1.5-1.5v5.25a4.5 4.5 0 0 1-4.5 4.5h-1.125a4.5 4.5 0 0 1-4.5-4.5V9.75a2.25 2.25 0 0 1 2.25-2.25v-3Z"/><path d="M8.25 12.75 6 10.5"/></svg>';
                }

                return '';
            },
            addPreviewNodeAffordance(node) {
                if (node.querySelector(':scope > .clb-preview-node-affordance')) return;

                const affordance = document.createElement('span');
                affordance.className = 'clb-preview-node-affordance';
                affordance.dataset.layoutBuilderAffordance = 'clickable';
                affordance.setAttribute('aria-hidden', 'true');
                affordance.innerHTML = this.icon('click');
                node.append(affordance);
            },
            preparePreviewContainerNode(node) {
                const action = this.previewContainerActions[node.dataset.clbPreviewNode];
                const label = action?.label
                    ? `${this.actionLabels.editContainer}: ${action.label}`
                    : this.actionLabels.editContainer;

                node.tabIndex = 0;
                node.setAttribute('role', 'button');
                node.setAttribute('aria-label', label);
                this.addPreviewNodeAffordance(node);
                node.addEventListener('keydown', (event) => {
                    if (!['Enter', ' '].includes(event.key)) return;
                    if (event.target !== node) return;

                    event.preventDefault();
                    event.stopPropagation();
                    this.selectPreviewNode(node.dataset.clbPreviewNode, node);
                });
            },
            preparePreviewWidgetNode(node) {
                const action = this.previewWidgetActions[node.dataset.clbPreviewNode];
                const label = action?.label ? `${this.actionLabels.edit}: ${action.label}` : this.actionLabels.edit;

                node.tabIndex = 0;
                node.setAttribute('role', 'button');
                node.setAttribute('aria-label', label);
                this.addPreviewNodeAffordance(node);
                node.addEventListener('keydown', (event) => {
                    if (!['Enter', ' '].includes(event.key)) return;
                    if (event.target !== node) return;

                    event.preventDefault();
                    event.stopPropagation();
                    this.selectPreviewNode(node.dataset.clbPreviewNode, node);
                });
            },
            attachWidgetInsertControls(containerNode) {
                const action = this.previewContainerActions[containerNode.dataset.clbPreviewNode];

                if (!action?.canEditLayout) return;

                const widgets = containerNode.querySelector('.clb-preview-widgets');

                if (!widgets) return;

                const widgetNodes = Array.from(widgets.querySelectorAll(':scope > .clb-preview-widget'));

                widgetNodes.forEach((widgetNode, index) => {
                    widgets.insertBefore(
                        this.makeInsertControl(this.actionLabels.addWidgetHere, (trigger) =>
                            this.runPreviewAction(
                                'addWidget',
                                {
                                    ...action,
                                    widgetIndex: 0,
                                    position: index,
                                },
                                {},
                                trigger,
                            ),
                        ),
                        widgetNode,
                    );
                });

                widgets.appendChild(
                    this.makeInsertControl(this.actionLabels.addWidgetHere, (trigger) =>
                        this.runPreviewAction(
                            'addWidget',
                            {
                                ...action,
                                widgetIndex: 0,
                                position: widgetNodes.length,
                            },
                            {},
                            trigger,
                        ),
                    ),
                );
            },
            attachContainerInsertControls(root) {
                root.querySelectorAll('[data-clb-preview-container-list]').forEach((containerList) => {
                    const containerNodes = Array.from(containerList.querySelectorAll(':scope > .clb-preview-container'));

                    containerNodes.forEach((containerNode) => {
                        const position = Number.parseInt(containerNode.dataset.clbPreviewContainerPosition || '0', 10);

                        containerList.insertBefore(
                            this.makeInsertControl(
                                this.actionLabels.addContainerHere,
                                (trigger) =>
                                    this.runPreviewAction(
                                        'addContainer',
                                        {
                                            type: 'container',
                                            containerKey: '',
                                            widgetIndex: 0,
                                            position,
                                        },
                                        {},
                                        trigger,
                                    ),
                                'clb-preview-container-insert',
                            ),
                            containerNode,
                        );
                    });

                    const lastPosition =
                        Math.max(
                            -1,
                            ...containerNodes.map((containerNode) =>
                                Number.parseInt(containerNode.dataset.clbPreviewContainerPosition || '0', 10),
                            ),
                        ) + 1;

                    containerList.appendChild(
                        this.makeInsertControl(
                            this.actionLabels.addContainerHere,
                            (trigger) =>
                                this.runPreviewAction(
                                    'addContainer',
                                    {
                                        type: 'container',
                                        containerKey: '',
                                        widgetIndex: 0,
                                        position: lastPosition,
                                    },
                                    {},
                                    trigger,
                                ),
                            'clb-preview-container-insert',
                        ),
                    );
                });
            },
            makeInsertControl(label, callback, className = '') {
                const control = document.createElement('div');
                control.className = `clb-preview-insert ${className}`.trim();
                const actionName = className.includes('clb-preview-container-insert') ? 'add-container' : 'add-widget';
                control.innerHTML = `<button type="button" class="clb-preview-insert-button" data-layout-builder-action="${actionName}" title="${this.escapeHtml(label)}" aria-label="${this.escapeHtml(label)}">${this.icon('plus')}</button>`;
                control.querySelector('button').addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    callback(event.currentTarget);
                });

                return control;
            },
            escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            },
            markSelectedPreviewNode() {
                const root = this.$refs.previewHost?.shadowRoot;

                if (!root) return;

                root.querySelectorAll('[data-clb-preview-node]').forEach((node) => {
                    node.classList.toggle('is-selected', node.dataset.clbPreviewNode === this.selectedNode);
                });
            },
            markSelectedTreeNode() {
                this.$el.querySelectorAll('[data-layout-builder-tree-node]').forEach((node) => {
                    const row = node.matches('.layout-builder-tree-row')
                        ? node
                        : node.querySelector(':scope > .layout-builder-tree-row');

                    row?.classList.toggle(
                        'layout-builder-tree-row-selected',
                        node.dataset.layoutBuilderTreeNode === this.selectedNode,
                    );
                });
            },
            setActiveBreakpointPreview(breakpoint) {
                this.activeBreakpoint = breakpoint || 'desktop';
                this.applyPreviewBreakpoint();
            },
            applyPreviewBreakpoint() {
                const maxWidth = this.activeBreakpointMaxCanvasWidth();
                const minWidth = this.activeBreakpointMinCanvasWidth();
                const canvas = this.$refs.previewCanvas;
                const host = this.$refs.previewHost;

                canvas?.style.setProperty('--layout-builder-preview-max-width', maxWidth);
                canvas?.style.setProperty('--layout-builder-preview-min-width', minWidth);

                if (!host) return;

                host.dataset.activeBreakpoint = this.activeBreakpoint;
                host.style.maxWidth = maxWidth;
                host.style.minWidth = minWidth;
            },
            activeBreakpointMaxCanvasWidth() {
                return this.breakpointWidths[this.activeBreakpoint] || '100%';
            },
            activeBreakpointMinCanvasWidth() {
                return '0';
            },
            shouldStackContainersForActiveBreakpoint() {
                return this.activeBreakpoint !== 'desktop';
            },
            normalizedTreeSearch() {
                return this.search.trim().toLowerCase();
            },
            treeSearchActive() {
                return this.normalizedTreeSearch() !== '';
            },
            treeItemMatches(element) {
                const term = this.normalizedTreeSearch();

                if (!term) return true;

                return (element.dataset.layoutBuilderTreeSearch || '').toLowerCase().includes(term);
            },
            containerHasMatchingChild(element) {
                if (!this.treeSearchActive()) return false;

                return [...element.querySelectorAll('[data-layout-builder-tree-widget]')].some((widget) =>
                    this.treeItemMatches(widget),
                );
            },
            containerMatches(element) {
                if (!this.treeSearchActive()) return true;

                return this.treeItemMatches(element) || this.containerHasMatchingChild(element);
            },
            widgetMatches(element) {
                return !this.treeSearchActive() || this.treeItemMatches(element);
            },
            treeSearchScope() {
                return this.$el.closest('.layout-builder-visual-editor') || this.$el;
            },
            treeSearchResultCount() {
                if (!this.treeSearchActive()) return 0;

                const scope = this.treeSearchScope();
                const nodes = new Set();

                [...scope.querySelectorAll('[data-layout-builder-tree-container]')]
                    .filter((container) => this.treeItemMatches(container))
                    .forEach((container) => nodes.add(container.dataset.layoutBuilderTreeNode));
                [...scope.querySelectorAll('[data-layout-builder-tree-widget]')]
                    .filter((widget) => this.treeItemMatches(widget))
                    .forEach((widget) => nodes.add(widget.dataset.layoutBuilderTreeNode));

                return nodes.size;
            },
            treeSearchResultLabel() {
                const count = this.treeSearchResultCount();

                return count === 1
                    ? this.actionLabels.treeSearchResult
                    : (this.actionLabels.treeSearchResults || '').replace(':count', count);
            },
            hasTreeSearchResults() {
                return !this.treeSearchActive() || this.treeSearchResultCount() > 0;
            },
            clearTreeSearch() {
                this.search = '';
                this.$nextTick(() => this.$refs.treeSearchInput?.focus());
            },
            treeContainerOpen(open, element) {
                return this.treeSearchActive() ? this.containerMatches(element) : open;
            },
            openTree(trigger = null) {
                this.treeReturnFocus = trigger instanceof HTMLElement ? trigger : document.activeElement;
                this.treeOpen = true;
                this.$nextTick(() => this.$refs.treeDrawer?.focus());
            },
            closeTree(restoreFocus = true) {
                this.treeOpen = false;
                if (!restoreFocus) return;

                this.$nextTick(() => {
                    const target = this.treeReturnFocus?.isConnected
                        ? this.treeReturnFocus
                        : this.$refs.treeToggle;
                    target?.focus();
                });
            },
            handleEscape() {
                if (this.treeOpen) {
                    this.closeTree();

                    return;
                }

                if (this.selectedNode) {
                    this.clearSelectedPreviewNode();

                    return;
                }
            },
            hasUnsavedLayoutChanges() {
                return Boolean(this.$wire.layoutModified);
            },
            returnToContentEditor() {
                this.$wire.$call('showContentEditor');
            },
            refreshPreview(trigger = null) {
                this.previewStatus = 'refreshing';
                this.markPreviewActionLoading(trigger, true);

                return Promise.resolve(this.$wire.$call('refreshVisualPreview'))
                    .then(() => this.afterLivewirePreviewMutation())
                    .then(() => {
                        this.previewStatus = this.$wire.visualPreviewStatus || 'current';
                    })
                    .catch((error) => {
                        this.previewStatus = 'error';
                        throw error;
                    })
                    .finally(() => this.markPreviewActionLoading(trigger, false));
            },
            handleGlobalShortcut(event) {
                if (!event || event.defaultPrevented || event.isComposing) return;

                const modifier = event.metaKey || event.ctrlKey;
                const key = (event.key || '').toLowerCase();

                if (modifier && key === 's') {
                    event.preventDefault();
                    if (this.$wire.layoutModified) {
                        this.$wire.$call('saveLayout', true);
                    }
                    return;
                }

                if (modifier && (key === 'y' || (event.shiftKey && key === 'z'))) {
                    event.preventDefault();
                    this.$wire.$call('redoLayoutMutation');
                    return;
                }

                if (modifier && !event.shiftKey && key === 'z') {
                    event.preventDefault();
                    this.$wire.$call('undoLayoutMutation');
                    return;
                }

                if (modifier || event.altKey) return;
                if (this.isEditableTarget(event.target)) return;

                if (key === '/') {
                    const input = this.$refs.treeSearchInput;
                    if (!input) return;
                    event.preventDefault();
                    if (this.treeCollapsed) this.treeCollapsed = false;
                    if (this.compactPanels && !this.treeOpen) this.openTree(this.$refs.treeToggle);
                    this.$nextTick(() => {
                        input.focus();
                        input.select?.();
                    });
                    return;
                }

                const breakpointByKey = {
                    1: 'desktop',
                    2: 'tablet',
                    3: 'mobile',
                };
                if (breakpointByKey[key]) {
                    event.preventDefault();
                    this.setActiveBreakpointPreview(breakpointByKey[key]);
                }
            },
            isEditableTarget(target) {
                if (!(target instanceof HTMLElement)) return false;
                if (target.isContentEditable) return true;
                const tag = target.tagName;
                return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';
            },
            toggleTreeCollapsed() {
                this.treeCollapsed = !this.treeCollapsed;
            },
            selectPreviewNode(node, trigger = null) {
                this.inspectorReturnFocus = trigger instanceof HTMLElement ? trigger : document.activeElement;
                this.selectedNode = node;
                this.markSelectedPreviewNode();
                this.markSelectedTreeNode();
                this.scrollSelectedTreeNodeIntoView();

                if (this.compactPanels) {
                    this.$nextTick(() => this.$refs.inspectorPanel?.focus());
                }
            },
            clearSelectedPreviewNode() {
                this.selectedNode = null;
                this.markSelectedPreviewNode();
                this.markSelectedTreeNode();
                this.$nextTick(() => {
                    const target = this.inspectorReturnFocus?.isConnected
                        ? this.inspectorReturnFocus
                        : this.$refs.treeToggle;
                    target?.focus();
                });
            },
            openWidgetEditor(node) {
                const action = this.previewWidgetActions[node];

                if (!action) {
                    this.selectNode(node, () => this.$wire.selectPreviewNode(node));

                    return;
                }

                this.selectedNode = node;
                this.markSelectedPreviewNode();
                this.runPreviewAction('editWidget', action);
            },
            openContainerEditor(node) {
                const action = this.previewContainerActions[node];

                if (!action) {
                    this.selectNode(node, () => this.$wire.selectPreviewNode(node));

                    return;
                }

                this.selectedNode = node;
                this.markSelectedPreviewNode();
                this.runPreviewAction('editContainer', action);
            },
            selectNode(node, callback) {
                this.selectedNode = node;
                this.markSelectedPreviewNode();
                this.markSelectedTreeNode();

                let result;

                try {
                    result = callback();
                } catch (error) {
                    throw error;
                }

                Promise.resolve(result).then(() => {
                    this.markSelectedPreviewNode();
                    this.markSelectedTreeNode();
                    this.scrollSelectedTreeNodeIntoView();
                    if (this.compactPanels) {
                        this.$nextTick(() => this.$refs.inspectorPanel?.focus());
                    }
                });
            },
            selectFromTree(node, callback) {
                this.inspectorReturnFocus = this.$refs.treeToggle;
                this.selectNode(node, callback);

                if (this.compactPanels) this.closeTree(false);
            },
            scrollSelectedTreeNodeIntoView() {
                if (!this.selectedNode) return;

                const panel = this.$refs.treePanel;
                const node = panel?.querySelector(`[data-layout-builder-tree-node="${this.selectedNode}"]`);

                if (!panel || !node) return;

                const panelRect = panel.getBoundingClientRect();
                const nodeRect = node.getBoundingClientRect();

                if (nodeRect.top < panelRect.top) {
                    panel.scrollTop -= panelRect.top - nodeRect.top + 12;
                } else if (nodeRect.bottom > panelRect.bottom) {
                    panel.scrollTop += nodeRect.bottom - panelRect.bottom + 12;
                }
            },
            runPreviewAction(actionName, action, extra = {}, trigger = null) {
                const args = {};

                if (action.containerKey !== undefined) {
                    args.containerKey = action.containerKey;
                }

                if (Number.isInteger(action.widgetIndex)) {
                    args.widgetIndex = action.widgetIndex;
                }

                if (Number.isInteger(action.position)) {
                    args.position = action.position;
                }

                if (extra.type) {
                    args.type = extra.type;
                }

                if (extra.types) {
                    args.types = extra.types;
                }

                return this.dispatchPreviewAction(actionName, args, trigger);
            },
            dispatchPreviewAction(actionName, args, trigger = null) {
                this.actionLoading = true;
                this.markPreviewActionLoading(trigger, true);

                const directActions = {
                    duplicateContainer: async () => {
                        await this.$wire.$call('duplicateContainer', args.containerKey);
                        await this.$wire.$call('refreshVisualPreview');
                    },
                    removeContainer: async () => {
                        await this.$wire.$call('removeContainer', args.containerKey);
                        await this.$wire.$call('refreshVisualPreview');
                    },
                    duplicateWidget: async () => {
                        await this.$wire.$call('duplicateWidget', args.containerKey, args.widgetIndex);
                        await this.$wire.$call('refreshVisualPreview');
                    },
                    removeWidget: async () => {
                        await this.$wire.$call('removeWidget', args.containerKey, args.widgetIndex);
                        await this.$wire.$call('refreshVisualPreview');
                    },
                };

                if (directActions[actionName]) {
                    return Promise.resolve(directActions[actionName]())
                        .then(() => this.afterLivewirePreviewMutation())
                        .finally(() => {
                            this.markPreviewActionLoading(trigger, false);
                            this.actionLoading = false;
                        });
                }

                return Promise.resolve(this.$wire.mountAction(actionName, args)).finally(() => {
                    this.markPreviewActionLoading(trigger, false);
                    this.actionLoading = false;
                });
            },
            afterLivewirePreviewMutation() {
                return new Promise((resolve) => {
                    this.$nextTick(() => {
                        window.requestAnimationFrame(() => {
                            window.setTimeout(() => {
                                this.renderPreview();
                                this.syncSelectedPreviewNode();
                                this.markSelectedPreviewNode();
                                this.markSelectedTreeNode();
                                this.scrollSelectedTreeNodeIntoView();
                                resolve();
                            }, 50);
                        });
                    });
                });
            },
            markPreviewActionLoading(trigger, loading) {
                if (!(trigger instanceof HTMLElement)) return;

                trigger.classList.toggle('is-loading', loading);
                trigger.toggleAttribute('disabled', loading);
                trigger.setAttribute('aria-busy', loading ? 'true' : 'false');
            },
            selectedPreviewAction() {
                return this.previewWidgetActions[this.selectedNode] || this.previewContainerActions[this.selectedNode] || null;
            },
            syncSelectedPreviewNode() {
                if (this.selectedPreviewAction()) return;

                this.selectedNode =
                    Object.keys(this.previewContainerActions)[0] || Object.keys(this.previewWidgetActions)[0] || null;
            },
            selectedPreviewLabel() {
                return this.selectedPreviewAction()?.label || this.actionLabels.layout || '';
            },
            selectedPreviewKind() {
                return this.actionLabels[this.selectedPreviewAction()?.type] || this.actionLabels.layout;
            },
            selectedPreviewMetaRows() {
                const action = this.selectedPreviewAction();

                if (!action) return [];

                if (action.type === 'container') {
                    return [
                        {
                            label: this.actionLabels.area,
                            value: action.areaLabel || this.actionLabels.layout,
                        },
                        {
                            label: this.actionLabels.widgets,
                            value: action.widgetCountLabel || '',
                        },
                        {
                            label: this.actionLabels.width,
                            value: action.colspanLabel || '',
                        },
                    ].filter((row) => row.value);
                }

                return [
                    {
                        label: this.actionLabels.type,
                        value: action.typeLabel || this.actionLabels.widget,
                    },
                    {
                        label: this.actionLabels.placement,
                        value: [action.areaLabel, action.containerLabel].filter(Boolean).join(' / '),
                    },
                    {
                        label: this.actionLabels.assets,
                        value: action.assetCountLabel || '',
                    },
                ].filter((row) => row.value);
            },
            selectedPreviewAssetType() {
                const action = this.selectedPreviewAction();
                const assetTypes = Array.isArray(action?.assetTypes) ? action.assetTypes : [];

                return assetTypes[0]?.type || '';
            },
            selectedPreviewAssetTypes() {
                const action = this.selectedPreviewAction();
                const assetTypes = Array.isArray(action?.assetTypes) ? action.assetTypes : [];

                return assetTypes.map((assetType) => assetType.type);
            },
            selectedPreviewAssetTypeDescriptors() {
                const action = this.selectedPreviewAction();

                return Array.isArray(action?.assetTypes) ? action.assetTypes : [];
            },
            selectedPreviewHasWidgetControls() {
                const action = this.selectedPreviewAction();

                if (!action || action.type !== 'widget') return false;

                return Boolean(
                    action.hasLayoutSettings ||
                    action.canTogglePageAssets ||
                    this.selectedPreviewAssetTypeDescriptors().length > 0,
                );
            },
            runSelectedPreviewAction(actionName, trigger = null, typeOverride = null) {
                const action = this.selectedPreviewAction();

                if (!action) return;

                this.runPreviewAction(
                    actionName,
                    action,
                    {
                        type: typeOverride || this.selectedPreviewAssetType(),
                        types: this.selectedPreviewAssetTypes(),
                    },
                    trigger,
                );
            },
        });
    </script>
@endscript
{{-- format-ignore-end --}}

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
