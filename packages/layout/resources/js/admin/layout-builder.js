import sort from '@alpinejs/sort'

Alpine.plugin(sort)

export default function layoutBuilderComponent() {
    return {
        isReordering: false,

        isReorderingResources: [],

        isLoading: false,

        selectedRecords: this.$wire.$entangle('selectedRecords'),

        init() {
            this.$wire.on('layout-builder-reset', () => {
                this.isReordering = false
                this.isReorderingResources = []
            })

            // on escape  key press
            window.addEventListener('keydown', (e) => {
                if (e.key !== 'Escape') {
                    return
                }
                this.isReordering = false
                this.isReorderingResources = []
            })
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

        collapseWidgets: function (toggle) {
            this.$dispatch('collapse-widget', { isCollapsed: toggle })
        },

        collapseContainers: function (toggle) {
            this.$dispatch('collapse-container', { isCollapsed: toggle })
        },

        collapseAllComponents: function (toggle) {
            this.collapseWidgets(toggle)
            this.collapseContainers(toggle)
        },

        toggleReordering: function () {
            this.isReordering = !this.isReordering

            if (this.isReordering) {
                this.collapseWidgets(true)
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
