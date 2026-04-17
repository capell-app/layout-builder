/* global Alpine */
import sort from '@alpinejs/sort'

Alpine.plugin(sort)

export default function layoutBuilderComponent() {
    return {
        isReordering: false,

        isReorderingResources: [],

        isLoading: false,

        isContainersAllCollapsed: null, // true = all collapsed, false = all expanded, null = mixed

        collapsedContainers: new Map(), // id => isCollapsed

        collapsedWidgets: {}, // containerKey => { id => isCollapsed }

        selectedRecords: this.$wire.$entangle('selectedRecords'),

        init() {
            this.$wire.on('layout-builder-reset', () => {
                this.isReordering = false
                this.isReorderingResources = []
            })

            window.addEventListener('keydown', (e) => {
                if (e.key !== 'Escape') {
                    return
                }
                this.isReordering = false
                this.isReorderingResources = []
            })

            const handleContainerCollapsed = (e) => {
                this.collapsedContainers.set(
                    e.detail.id,
                    !!e.detail.isCollapsed,
                )
                this.updateIsAllContainersCollapsed()
            }

            window.addEventListener(
                'container-collapsed-register',
                handleContainerCollapsed,
            )
            window.addEventListener(
                'container-collapsed-changed',
                handleContainerCollapsed,
            )

            const handleWidgetCollapsed = (e) => {
                const containerKey = e.detail.containerKey
                if (!this.collapsedWidgets[containerKey]) {
                    this.collapsedWidgets[containerKey] = {}
                }
                this.collapsedWidgets[containerKey][e.detail.id] =
                    !!e.detail.isCollapsed
            }

            window.addEventListener(
                'widget-collapsed-register',
                handleWidgetCollapsed,
            )
            window.addEventListener(
                'widget-collapsed-changed',
                handleWidgetCollapsed,
            )
        },

        selectAllRecords: async function (containerKey, widgetIndex) {
            this.isLoading = true

            this.selectedRecords[containerKey][widgetIndex] =
                await this.$wire.selectAllAssets(containerKey, widgetIndex)

            this.isLoading = false
        },

        deselectAllRecords: function (containerKey, widgetIndex) {
            this.selectedRecords[containerKey][widgetIndex] = []
        },

        collapseAll: function () {
            this.collapseAllComponents(true)
        },

        expandAll: function () {
            this.collapseAllComponents(false)
        },

        collapseAllComponents: function (isCollapsed) {
            this.collapseAllWidgets(isCollapsed)
            this.collapseAllContainers(isCollapsed)
        },

        collapseAllContainerWidgets: function (containerKey, isCollapsed) {
            if (!isCollapsed) {
                this.collapseContainer(containerKey, isCollapsed)
            }
            this.$dispatch('collapse-widget', {
                containerKey: containerKey,
                isCollapsed: isCollapsed,
            })
        },

        collapseContainer: function (containerKey, isCollapsed) {
            this.$dispatch('collapse-container', {
                id: containerKey,
                isCollapsed: isCollapsed,
            })
        },

        collapseAllWidgets: function (isCollapsed) {
            this.$dispatch('collapse-widget', { isCollapsed: isCollapsed })
        },

        collapseAllContainers: function (isCollapsed) {
            this.$dispatch('collapse-container', { isCollapsed: isCollapsed })
        },

        updateIsAllContainersCollapsed: function () {
            const values = Array.from(this.collapsedContainers.values())
            if (values.length === 0) {
                this.isContainersAllCollapsed = null
            } else if (values.every((v) => v === true)) {
                this.isContainersAllCollapsed = true
            } else if (values.every((v) => v === false)) {
                this.isContainersAllCollapsed = false
            } else {
                this.isContainersAllCollapsed = null
            }
        },

        isAllWidgetsCollapsed: function (containerKey) {
            if (!this.collapsedWidgets[containerKey]) {
                return null
            }
            const values = Object.values(this.collapsedWidgets[containerKey])
            if (values.length === 0) {
                return null
            } else if (values.every((v) => v === true)) {
                return true
            } else if (values.every((v) => v === false)) {
                return false
            } else {
                return null
            }
        },

        toggleReordering: function () {
            this.isReordering = !this.isReordering

            if (this.isReordering) {
                this.collapseAllWidgets(true)
            }
        },

        toggleReorderingResources: function (containerKey, widgetIndex) {
            this.deselectAllRecords(containerKey, widgetIndex)

            if (!this.isReorderingResources[containerKey]) {
                this.isReorderingResources[containerKey] = []

                this.isReorderingResources[containerKey][widgetIndex] = true

                return this.isReorderingResources[containerKey][widgetIndex]
            }

            this.isReorderingResources[containerKey][widgetIndex] =
                !this.isReorderingResources[containerKey][widgetIndex]

            return this.isReorderingResources[containerKey][widgetIndex]
        },

        isWidgetReorderingResources: function (containerKey, widgetIndex) {
            return this.isReorderingResources[containerKey]
                ? this.isReorderingResources[containerKey][widgetIndex]
                : false
        },
    }
}
