@php
    use Capell\Core\Facades\CapellCore;
    use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
    use Illuminate\Support\Js;

    $tree = $this->layoutBuilderTree;
    $activePreviewBreakpoint = $this->activeBreakpoint ?? LayoutBreakpoint::Desktop;
    $breakpointWidths = collect(LayoutBreakpoint::cases())
        ->mapWithKeys(fn (LayoutBreakpoint $breakpoint): array => [$breakpoint->value => $breakpoint->maxCanvasWidth()])
        ->all();
    $previewContainerActions = [];
    $previewWidgetActions = [];

    foreach ($tree->containers as $containerPosition => $treeContainer) {
        $previewContainerActions[$treeContainer->nodeId] = [
            'type' => 'container',
            'containerKey' => $treeContainer->key,
            'label' => $treeContainer->label,
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
                'containerKey' => $treeWidget->containerKey,
                'widgetIndex' => $treeWidget->widgetIndex,
                'label' => $treeWidget->label,
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

@script
    <script data-navigate-once>
        window.capellLayoutBuilderVisualEditor = (config = {}) => ({
            treeOpen: false,
            compactPanels: false,
            actionLoading: false,
            search: '',
            selectedNode: config.selectedNode || null,
            activeBreakpoint: config.activeBreakpoint || 'desktop',
            breakpointWidths: config.breakpointWidths || {},
            previewWidgetActions: config.previewWidgetActions || {},
            previewContainerActions: config.previewContainerActions || {},
            actionLabels: config.actionLabels || {},
            previewSignature: config.previewSignature || '',
            init() {
                this.closePreviewMenusFromDocument = (event) => {
                    const host = this.$refs.previewHost

                    if (host && event.composedPath().includes(host)) return

                    this.closePreviewMenus()
                }
                document.addEventListener(
                    'click',
                    this.closePreviewMenusFromDocument,
                )
                this.syncPanelLayout()
                this.previewResizeObserver = new ResizeObserver(() =>
                    this.syncPanelLayout(),
                )
                this.previewResizeObserver.observe(this.$el)
                this.renderPreview()
            },
            destroy() {
                this.previewResizeObserver?.disconnect()
                document.removeEventListener(
                    'click',
                    this.closePreviewMenusFromDocument,
                )
            },
            shadowStyles() {
                return `
                    :host { all: initial; color: #111827; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
                    *, *::before, *::after { box-sizing: border-box; }
                    a, button, input, select, textarea, form { pointer-events: none !important; }
                    .clb-preview-page { min-height: 100%; background: #f8fafc; color: #111827; }
                    .clb-preview-header { display: flex; align-items: end; justify-content: space-between; gap: 1rem; padding: 1.5rem clamp(1rem, 2.5vw, 2rem); border-bottom: 1px solid #e5e7eb; background: #fff; }
                    .clb-preview-kicker { margin-bottom: .35rem; color: #64748b; font-size: .75rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
                    .clb-preview-header h1 { margin: 0; font-size: clamp(1.875rem, 4vw, 3.5rem); line-height: 1.05; font-weight: 750; letter-spacing: 0; }
                    .clb-preview-main { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 1rem; padding: clamp(.875rem, 2vw, 1.5rem); }
                    .clb-preview-container { position: relative; grid-column: span var(--clb-preview-colspan) / span var(--clb-preview-colspan); min-width: 0; border: 1px solid rgba(148, 163, 184, .55); border-radius: .75rem; background: rgba(255,255,255,.7); padding: 1rem; transition: box-shadow .15s ease, outline-color .15s ease; }
                    .clb-preview-container-label { display: inline-flex; margin-bottom: .75rem; border-radius: 999px; background: #f1f5f9; padding: .25rem .625rem; color: #475569; font-size: .75rem; font-weight: 650; }
                    .clb-preview-widgets { display: grid; gap: .875rem; }
                    .clb-preview-widget { position: relative; border-radius: .625rem; outline: 2px solid transparent; outline-offset: 3px; transition: outline-color .15s ease, box-shadow .15s ease; }
                    .clb-preview-widget, .layout-builder-widget-preview { overflow: hidden; border: 1px solid #e5e7eb; border-radius: .625rem; background: #fff; box-shadow: 0 1px 2px rgba(15,23,42,.06); }
                    .clb-preview-widget-body { display: flex; gap: .875rem; padding: 1rem; }
                    .clb-preview-widget-icon { display: inline-flex; width: 2.25rem; height: 2.25rem; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: .5rem; background: #eff6ff; color: #2563eb; }
                    .clb-preview-widget-type { margin-bottom: .25rem; color: #64748b; font-size: .72rem; font-weight: 700; text-transform: uppercase; }
                    .clb-preview-widget h2, .layout-builder-widget-preview h2 { margin: 0; font-size: 1rem; line-height: 1.35; font-weight: 700; letter-spacing: 0; }
                    .clb-preview-widget p { margin: .375rem 0 0; color: #475569; font-size: .875rem; line-height: 1.5; }
                    .layout-builder-widget-preview { padding: 1rem; }
                    .layout-widget-preview-actions, .layout-widget-assets-toggle { display: none !important; }
                    .clb-preview-empty { width: 100%; border: 1px dashed #cbd5e1; border-radius: .625rem; padding: 1rem; color: #64748b; text-align: center; }
                    .clb-preview-empty-page { grid-column: 1 / -1; }
                    [data-clb-preview-node] { cursor: pointer; pointer-events: auto; }
                    [data-clb-preview-node]:hover, [data-clb-preview-node]:focus-visible { outline: 2px solid rgba(59, 130, 246, .55); outline-offset: 3px; }
                    [data-clb-preview-node].is-selected { outline: 3px solid #2563eb; outline-offset: 4px; box-shadow: 0 0 0 5px rgba(37, 99, 235, .12); }
                    .clb-preview-actionbar { position: absolute; top: .5rem; right: .5rem; z-index: 20; display: inline-flex; align-items: center; gap: .25rem; border: 1px solid rgba(148, 163, 184, .38); border-radius: 999px; background: rgba(15, 23, 42, .86); padding: .25rem; opacity: 0; pointer-events: none; transform: translateY(-.125rem); transition: opacity .15s ease, transform .15s ease; box-shadow: 0 12px 30px rgba(15, 23, 42, .18); }
                    [data-clb-preview-node-type="widget"]:hover > .clb-preview-actionbar, [data-clb-preview-node-type="container"]:hover > .clb-preview-actionbar, [data-clb-preview-node].is-selected > .clb-preview-actionbar, .clb-preview-actionbar:focus-within { opacity: 1; pointer-events: auto; transform: translateY(0); }
                    .clb-preview-actionbar button { pointer-events: auto !important; }
                    .clb-preview-action-button { display: inline-flex; width: 1.75rem; height: 1.75rem; align-items: center; justify-content: center; border: 0; border-radius: 999px; background: transparent; color: #fff; cursor: pointer; padding: 0; transition: background-color .15s ease, color .15s ease; }
                    .clb-preview-action-button:hover, .clb-preview-action-button:focus-visible { background: rgba(255, 255, 255, .14); outline: none; }
                    .clb-preview-action-button-danger:hover, .clb-preview-action-button-danger:focus-visible { background: rgba(239, 68, 68, .18); color: #fecaca; }
                    .clb-preview-action-button svg { width: 1rem; height: 1rem; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
                    .clb-preview-more { position: relative; }
                    .clb-preview-menu { position: absolute; top: calc(100% + .5rem); right: 0; display: none; min-width: 15rem; border: 1px solid rgba(148, 163, 184, .25); border-radius: .625rem; background: #fff; padding: .35rem; color: #111827; box-shadow: 0 20px 45px rgba(15, 23, 42, .24); }
                    .clb-preview-menu.is-open { display: grid; gap: .125rem; }
                    .clb-preview-menu button { display: flex; width: 100%; align-items: center; justify-content: flex-start; border: 0; border-radius: .4rem; background: transparent; color: #111827; cursor: pointer; font: inherit; font-size: .8125rem; font-weight: 650; padding: .5rem .625rem; text-align: left; }
                    .clb-preview-menu button:hover, .clb-preview-menu button:focus-visible { background: #f3f4f6; outline: none; }
                    .clb-preview-menu-heading { margin: .25rem .625rem .125rem; color: #64748b; font-size: .6875rem; font-weight: 800; text-transform: uppercase; }
                    .clb-preview-insert { position: relative; z-index: 15; display: flex; min-height: 1rem; align-items: center; justify-content: center; opacity: 0; transition: opacity .15s ease; }
                    .clb-preview-insert::before { position: absolute; left: .25rem; right: .25rem; height: 1px; background: rgba(37, 99, 235, .45); content: ''; }
                    .clb-preview-insert:hover, .clb-preview-insert:focus-within { opacity: 1; }
                    .clb-preview-insert-button { position: relative; z-index: 1; display: inline-flex; width: 1.5rem; height: 1.5rem; align-items: center; justify-content: center; border: 1px solid rgba(37, 99, 235, .28); border-radius: 999px; background: #fff; color: #2563eb; cursor: pointer; pointer-events: auto !important; box-shadow: 0 4px 12px rgba(15, 23, 42, .12); }
                    .clb-preview-insert-button:hover, .clb-preview-insert-button:focus-visible { border-color: rgba(37, 99, 235, .5); outline: none; }
                    .clb-preview-container-insert { grid-column: 1 / -1; margin-block: -.5rem; }
                    .clb-preview-widgets > .clb-preview-insert { margin-block: -.4375rem; }
                    @media (max-width: 720px) { .clb-preview-main { grid-template-columns: 1fr; } .clb-preview-container { grid-column: 1 / -1; } }
                `
            },
            syncPanelLayout() {
                this.compactPanels = this.$el.offsetWidth <= 1152
            },
            renderPreview() {
                const host = this.$refs.previewHost

                if (!host) return

                const root =
                    host.shadowRoot || host.attachShadow({ mode: 'open' })
                const template = this.$refs.previewTemplate
                const html = template ? template.innerHTML : ''

                root.innerHTML = `<style>${this.shadowStyles()}</style>${html}`
                this.bindPreviewRootEvents(root)
                root.querySelectorAll('[data-clb-preview-node]').forEach(
                    (node) => {
                        if (node.dataset.clbPreviewNode === this.selectedNode) {
                            node.classList.add('is-selected')
                        }

                        if (node.dataset.clbPreviewNodeType === 'container') {
                            this.preparePreviewContainerNode(node)
                            this.attachContainerActions(node)
                            this.attachWidgetInsertControls(node)
                        }

                        if (node.dataset.clbPreviewNodeType === 'widget') {
                            this.preparePreviewWidgetNode(node)
                            this.attachWidgetActions(node)
                        }

                        node.addEventListener('click', (event) => {
                            const selectedTarget = event
                                .composedPath()
                                .find(
                                    (target) =>
                                        target instanceof HTMLElement &&
                                        target.dataset.clbPreviewNode,
                                )

                            if (selectedTarget !== node) {
                                return
                            }

                            event.preventDefault()
                            event.stopPropagation()
                            if (
                                node.dataset.clbPreviewNodeType === 'container'
                            ) {
                                this.openContainerEditor(
                                    node.dataset.clbPreviewNode,
                                )

                                return
                            }

                            this.openWidgetEditor(node.dataset.clbPreviewNode)
                        })
                    },
                )
                this.attachContainerInsertControls(root)
            },
            bindPreviewRootEvents(root) {
                if (root.clbPreviewEventsBound) return

                root.addEventListener('click', (event) => {
                    const clickedMenu = event
                        .composedPath()
                        .some(
                            (target) =>
                                target instanceof HTMLElement &&
                                target.closest('.clb-preview-more'),
                        )

                    if (!clickedMenu) {
                        this.closePreviewMenus(root)
                    }
                })

                root.addEventListener('keydown', (event) => {
                    if (event.key !== 'Escape') return

                    if (this.closePreviewMenus(root)) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                })

                root.clbPreviewEventsBound = true
            },
            icon(name) {
                const icons = {
                    edit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m16.9 4.6 2.5 2.5"/><path d="M14 7.5 5.5 16 5 19l3-.5 8.5-8.5"/></svg>',
                    copy: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="9" y="9" width="10" height="10" rx="2"/><path d="M5 15V7a2 2 0 0 1 2-2h8"/></svg>',
                    trash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M6 7l1 13h10l1-13"/><path d="M9 7V4h6v3"/></svg>',
                    plus: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14"/><path d="M5 12h14"/></svg>',
                    more: '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="5" r="1.5" fill="currentColor"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/><circle cx="12" cy="19" r="1.5" fill="currentColor"/></svg>',
                }

                return icons[name] || ''
            },
            preparePreviewContainerNode(node) {
                const action =
                    this.previewContainerActions[node.dataset.clbPreviewNode]
                const label = action?.label
                    ? `${this.actionLabels.editContainer}: ${action.label}`
                    : this.actionLabels.editContainer

                node.tabIndex = 0
                node.setAttribute('role', 'button')
                node.setAttribute('aria-label', label)
                node.addEventListener('keydown', (event) => {
                    if (!['Enter', ' '].includes(event.key)) return
                    if (event.target !== node) return

                    event.preventDefault()
                    event.stopPropagation()
                    this.openContainerEditor(node.dataset.clbPreviewNode)
                })
            },
            preparePreviewWidgetNode(node) {
                const action =
                    this.previewWidgetActions[node.dataset.clbPreviewNode]
                const label = action?.label
                    ? `${this.actionLabels.edit}: ${action.label}`
                    : this.actionLabels.edit

                node.tabIndex = 0
                node.setAttribute('role', 'button')
                node.setAttribute('aria-label', label)
                node.addEventListener('keydown', (event) => {
                    if (!['Enter', ' '].includes(event.key)) return
                    if (event.target !== node) return

                    event.preventDefault()
                    event.stopPropagation()
                    this.openWidgetEditor(node.dataset.clbPreviewNode)
                })
            },
            attachContainerActions(node) {
                const action =
                    this.previewContainerActions[node.dataset.clbPreviewNode]

                if (!action?.canEditLayout) return

                const toolbar = document.createElement('div')
                toolbar.className = 'clb-preview-actionbar'
                toolbar.innerHTML = this.containerActionsHtml(action)
                node.appendChild(toolbar)

                toolbar
                    .querySelectorAll('[data-clb-action]')
                    .forEach((button) => {
                        button.addEventListener('click', (event) => {
                            event.preventDefault()
                            event.stopPropagation()
                            this.closePreviewMenus()
                            this.runPreviewAction(
                                button.dataset.clbAction,
                                action,
                            )
                        })
                    })
            },
            attachWidgetActions(node) {
                const action =
                    this.previewWidgetActions[node.dataset.clbPreviewNode]

                if (!action) return

                const toolbar = document.createElement('div')
                toolbar.className = 'clb-preview-actionbar'
                toolbar.innerHTML = this.widgetActionsHtml(action)
                node.appendChild(toolbar)

                const menu = toolbar.querySelector('.clb-preview-menu')
                const menuToggle = toolbar.querySelector(
                    '[data-clb-menu-toggle]',
                )

                if (menu && menuToggle) {
                    const menuId = `clb-preview-menu-${node.dataset.clbPreviewNode}`

                    menu.id = menuId
                    menuToggle.setAttribute('aria-controls', menuId)
                }

                toolbar
                    .querySelectorAll('[data-clb-action]')
                    .forEach((button) => {
                        button.addEventListener('click', (event) => {
                            event.preventDefault()
                            event.stopPropagation()
                            this.closePreviewMenus()
                            this.runPreviewAction(
                                button.dataset.clbAction,
                                action,
                                {
                                    type: button.dataset.clbAssetType,
                                    types: action.assetTypes.map(
                                        (assetType) => assetType.type,
                                    ),
                                },
                            )
                        })
                    })

                toolbar
                    .querySelector('[data-clb-menu-toggle]')
                    ?.addEventListener('click', (event) => {
                        event.preventDefault()
                        event.stopPropagation()
                        this.togglePreviewMenu(toolbar)
                    })
            },
            attachWidgetInsertControls(containerNode) {
                const action =
                    this.previewContainerActions[
                        containerNode.dataset.clbPreviewNode
                    ]

                if (!action?.canEditLayout) return

                const widgets = containerNode.querySelector(
                    '.clb-preview-widgets',
                )

                if (!widgets) return

                const widgetNodes = Array.from(
                    widgets.querySelectorAll(':scope > .clb-preview-widget'),
                )

                widgetNodes.forEach((widgetNode, index) => {
                    widgets.insertBefore(
                        this.makeInsertControl(
                            this.actionLabels.addWidgetHere,
                            () =>
                                this.runPreviewAction('addWidget', {
                                    ...action,
                                    widgetIndex: 0,
                                    position: index,
                                }),
                        ),
                        widgetNode,
                    )
                })

                widgets.appendChild(
                    this.makeInsertControl(
                        this.actionLabels.addWidgetHere,
                        () =>
                            this.runPreviewAction('addWidget', {
                                ...action,
                                widgetIndex: 0,
                                position: widgetNodes.length,
                            }),
                    ),
                )
            },
            attachContainerInsertControls(root) {
                const main = root.querySelector('.clb-preview-main')

                if (!main) return

                const containerNodes = Array.from(
                    main.querySelectorAll(':scope > .clb-preview-container'),
                )

                containerNodes.forEach((containerNode, index) => {
                    main.insertBefore(
                        this.makeInsertControl(
                            this.actionLabels.addContainerHere,
                            () =>
                                this.runPreviewAction('addContainer', {
                                    type: 'container',
                                    containerKey: '',
                                    widgetIndex: 0,
                                    position: index,
                                }),
                            'clb-preview-container-insert',
                        ),
                        containerNode,
                    )
                })

                main.appendChild(
                    this.makeInsertControl(
                        this.actionLabels.addContainerHere,
                        () =>
                            this.runPreviewAction('addContainer', {
                                type: 'container',
                                containerKey: '',
                                widgetIndex: 0,
                                position: containerNodes.length,
                            }),
                        'clb-preview-container-insert',
                    ),
                )
            },
            makeInsertControl(label, callback, className = '') {
                const control = document.createElement('div')
                control.className = `clb-preview-insert ${className}`.trim()
                control.innerHTML = `<button type="button" class="clb-preview-insert-button" title="${this.escapeHtml(label)}" aria-label="${this.escapeHtml(label)}">${this.icon('plus')}</button>`
                control
                    .querySelector('button')
                    .addEventListener('click', (event) => {
                        event.preventDefault()
                        event.stopPropagation()
                        callback()
                    })

                return control
            },
            togglePreviewMenu(toolbar) {
                const menu = toolbar.querySelector('.clb-preview-menu')
                const menuToggle = toolbar.querySelector(
                    '[data-clb-menu-toggle]',
                )

                if (!menu || !menuToggle) return

                const shouldOpen = !menu.classList.contains('is-open')
                this.closePreviewMenus()

                if (!shouldOpen) return

                menu.classList.add('is-open')
                menuToggle.setAttribute('aria-expanded', 'true')
            },
            closePreviewMenus(root = this.$refs.previewHost?.shadowRoot) {
                if (!root) return false

                let closed = false

                root.querySelectorAll('.clb-preview-menu.is-open').forEach(
                    (menu) => {
                        menu.classList.remove('is-open')
                        closed = true
                    },
                )
                root.querySelectorAll('[data-clb-menu-toggle]').forEach(
                    (button) => button.setAttribute('aria-expanded', 'false'),
                )

                return closed
            },
            widgetActionsHtml(action) {
                const labels = this.actionLabels
                const moreItems = []

                if (action.hasLayoutSettings) {
                    moreItems.push(
                        this.menuButton(
                            'editLayoutWidget',
                            labels.widgetSettings,
                        ),
                    )
                }

                if (action.canTogglePageAssets) {
                    moreItems.push(
                        this.menuButton(
                            'togglePageAssets',
                            action.toggleAssetsLabel,
                        ),
                    )
                }

                if (action.assetTypes.length > 0) {
                    moreItems.push(
                        `<div class="clb-preview-menu-heading">${labels.assets}</div>`,
                    )
                    action.assetTypes.forEach((assetType) => {
                        if (action.assetTypes.length > 1) {
                            moreItems.push(
                                `<div class="clb-preview-menu-heading">${this.escapeHtml(assetType.label)}</div>`,
                            )
                        }

                        moreItems.push(
                            this.menuButton(
                                'selectAsset',
                                assetType.selectLabel,
                                assetType.type,
                            ),
                        )
                        moreItems.push(
                            this.menuButton(
                                'addAsset',
                                assetType.createLabel,
                                assetType.type,
                            ),
                        )
                    })
                }

                return `
                    <button type="button" class="clb-preview-action-button" data-clb-action="editWidget" title="${this.escapeHtml(labels.edit)}" aria-label="${this.escapeHtml(labels.edit)}">${this.icon('edit')}</button>
                    ${action.canEditLayout ? `<button type="button" class="clb-preview-action-button" data-clb-action="duplicateWidget" title="${this.escapeHtml(labels.duplicate)}" aria-label="${this.escapeHtml(labels.duplicate)}">${this.icon('copy')}</button>` : ''}
                    ${action.canEditLayout ? `<button type="button" class="clb-preview-action-button clb-preview-action-button-danger" data-clb-action="removeWidget" title="${this.escapeHtml(labels.remove)}" aria-label="${this.escapeHtml(labels.remove)}">${this.icon('trash')}</button>` : ''}
                    ${moreItems.length > 0 ? `<span class="clb-preview-more"><button type="button" class="clb-preview-action-button" data-clb-menu-toggle title="${this.escapeHtml(labels.controls)}" aria-label="${this.escapeHtml(labels.controls)}" aria-haspopup="menu" aria-expanded="false">${this.icon('more')}</button><div class="clb-preview-menu" role="menu">${moreItems.join('')}</div></span>` : ''}
                `
            },
            containerActionsHtml() {
                const labels = this.actionLabels

                return `
                    <button type="button" class="clb-preview-action-button" data-clb-action="editContainer" title="${this.escapeHtml(labels.editContainer)}" aria-label="${this.escapeHtml(labels.editContainer)}">${this.icon('edit')}</button>
                    <button type="button" class="clb-preview-action-button" data-clb-action="duplicateContainer" title="${this.escapeHtml(labels.duplicateContainer)}" aria-label="${this.escapeHtml(labels.duplicateContainer)}">${this.icon('copy')}</button>
                    <button type="button" class="clb-preview-action-button clb-preview-action-button-danger" data-clb-action="removeContainer" title="${this.escapeHtml(labels.removeContainer)}" aria-label="${this.escapeHtml(labels.removeContainer)}">${this.icon('trash')}</button>
                `
            },
            menuButton(action, label, assetType = '') {
                return `<button type="button" role="menuitem" data-clb-action="${this.escapeHtml(action)}" data-clb-asset-type="${this.escapeHtml(assetType)}">${this.escapeHtml(label)}</button>`
            },
            escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;')
            },
            markSelectedPreviewNode() {
                const root = this.$refs.previewHost?.shadowRoot

                if (!root) return

                root.querySelectorAll('[data-clb-preview-node]').forEach(
                    (node) => {
                        node.classList.toggle(
                            'is-selected',
                            node.dataset.clbPreviewNode === this.selectedNode,
                        )
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
            openWidgetEditor(node) {
                const action = this.previewWidgetActions[node]

                if (!action) {
                    this.selectNode(node, () =>
                        this.$wire.selectPreviewNode(node),
                    )

                    return
                }

                this.selectedNode = node
                this.markSelectedPreviewNode()
                this.runPreviewAction('editWidget', action)
            },
            openContainerEditor(node) {
                const action = this.previewContainerActions[node]

                if (!action) {
                    this.selectNode(node, () =>
                        this.$wire.selectPreviewNode(node),
                    )

                    return
                }

                this.selectedNode = node
                this.markSelectedPreviewNode()
                this.runPreviewAction('editContainer', action)
            },
            selectNode(node, callback) {
                this.selectedNode = node
                this.markSelectedPreviewNode()

                let result

                try {
                    result = callback()
                } catch (error) {
                    throw error
                }

                Promise.resolve(result).then(() =>
                    this.markSelectedPreviewNode(),
                )
            },
            selectFromTree(node, callback) {
                this.selectNode(node, callback)
            },
            runPreviewAction(actionName, action, extra = {}) {
                const args = {}

                if (action.containerKey !== undefined) {
                    args.containerKey = action.containerKey
                }

                if (Number.isInteger(action.widgetIndex)) {
                    args.widgetIndex = action.widgetIndex
                }

                if (Number.isInteger(action.position)) {
                    args.position = action.position
                }

                if (extra.type) {
                    args.type = extra.type
                }

                if (extra.types) {
                    args.types = extra.types
                }

                this.actionLoading = true

                return Promise.resolve(
                    this.$wire.mountAction(actionName, args),
                ).finally(() => {
                    this.actionLoading = false
                })
            },
        })
    </script>
@endscript

<section
    x-data="window.capellLayoutBuilderVisualEditor({
                selectedNode: {{ Js::from($this->selectedPreviewNodeHandle) }},
                activeBreakpoint: {{ Js::from($activePreviewBreakpoint->value) }},
                breakpointWidths: {{ Js::from($breakpointWidths) }},
                previewWidgetActions: {{ Js::from($previewWidgetActions) }},
                previewContainerActions: {{ Js::from($previewContainerActions) }},
                actionLabels:
                    {{
                    Js::from([
                        'addWidgetHere' => __('capell-layout-builder::button.add_widget_here'),
                        'addContainerHere' => __('capell-layout-builder::button.add_container_here'),
                        'assets' => __('capell-layout-builder::heading.assets'),
                        'widgetSettings' => __('capell-layout-builder::button.edit_layout_widget'),
                        'controls' => __('capell-layout-builder::button.controls'),
                        'duplicateContainer' => __('capell-layout-builder::button.duplicate_container'),
                        'duplicate' => __('capell-layout-builder::button.duplicate_widget'),
                        'edit' => __('capell-layout-builder::button.edit_widget'),
                        'editContainer' => __('capell-layout-builder::button.edit_container'),
                        'removeContainer' => __('capell-layout-builder::button.remove_container'),
                        'remove' => __('capell-layout-builder::button.remove_widget'),
                    ])
                }},
                previewSignature: {{ Js::from($this->visualPreviewSignature) }},
            })"
    x-on:keydown.escape.window="treeOpen ? closeTree() : null"
    @class([
        'layout-builder-visual-editor',
        'layout-builder-visual-editor-empty' => $tree->widgetCount === 0,
    ])
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
        </div>

        <div class="layout-builder-visual-actions">
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

            {{ $this->undoLayoutMutationAction }}
            {{ $this->redoLayoutMutationAction }}

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
        </div>
    </div>

    <div
        @class([
            'layout-builder-visual-grid',
            'layout-builder-visual-grid-empty' => $tree->widgetCount === 0,
        ])
    >
        <aside
            class="layout-builder-visual-panel layout-builder-visual-panel-tree"
        >
            @include('capell-layout-builder::livewire.filament.layout-builder.visual-tree', ['tree' => $tree])
        </aside>

        <div
            class="layout-builder-visual-canvas layout-builder-canvas-scroll"
            data-match-frontend-container-layout="{{ config('capell-layout-builder.preview.match_frontend_container_layout', true) ? 'true' : 'false' }}"
            x-bind:data-active-breakpoint="activeBreakpoint"
            x-bind:data-stack-containers="shouldStackContainersForActiveBreakpoint() ? 'true' : 'false'"
            x-bind:style="{
                '--layout-builder-preview-max-width': activeBreakpointMaxCanvasWidth(),
                '--layout-builder-preview-min-width': activeBreakpointMinCanvasWidth(),
            }"
        >
            @if ($this->saveLayoutAction->isVisible())
                <div class="layout-builder-preview-actions">
                    {{ $this->saveLayoutAction }}
                </div>
            @endif

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

            <template x-ref="previewTemplate">
                {!! $this->visualPreviewHtml !!}
            </template>

            <div
                wire:key="layout-builder-shadow-preview-{{ $this->visualPreviewSignature }}"
                x-ref="previewHost"
                x-init="$nextTick(() => renderPreview())"
                @class([
                    'layout-builder-shadow-preview',
                    'layout-builder-shadow-preview-empty' => $tree->widgetCount === 0,
                ])
            ></div>

            <div class="layout-builder-preview-status-row">
                <span
                    @class([
                        'layout-builder-preview-status',
                        'layout-builder-preview-status-stale' => $visualPreviewStatus === 'stale',
                        'layout-builder-preview-status-error' => $visualPreviewStatus === 'error',
                    ])
                >
                    {{ __('capell-layout-builder::message.preview_status_' . $visualPreviewStatus) }}
                </span>
            </div>
        </div>
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
    >
        @include('capell-layout-builder::livewire.filament.layout-builder.visual-tree', ['tree' => $tree])
    </aside>
</section>
