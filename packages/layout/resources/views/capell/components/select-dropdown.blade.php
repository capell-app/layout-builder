<?php

declare(strict_types=1);

?>

@php
    $fieldWrapperView = $getFieldWrapperView();
    $statePath = $getStatePath();
    $state = $getState();
    $key = $getKey();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            isOpen: false,
            isSearching: false,
            search: '',
            results: [],
            highlightedIndex: -1,
            selectedLabel: @js(blank($state) ? null : $getOptionLabel()),
            optionsLimit: @js($getOptionsLimit()),
            searchDebounce: @js($getSearchDebounce()),
            searchTimer: null,

            async init() {
                if (this.state) {
                    await this.loadSelectedLabel()
                }
            },

            async loadSelectedLabel() {
                try {
                    const label = await $wire.callSchemaComponentMethod(
                        @js($key),
                        'getOptionLabel',
                    )
                    this.selectedLabel = label
                } catch (e) {}
            },

            open() {
                this.isOpen = ! this.isOpen
                if (this.isOpen) {
                    this.$nextTick(() => {
                        this.$refs.searchInput?.focus()
                        this.loadInitialOptions()
                    })
                }
            },

            close() {
                this.isOpen = false
                this.highlightedIndex = -1
            },

            handleKeyDown(event) {
                if (! this.isOpen) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault()
                        this.open()
                    }
                    return
                }
                if (event.defaultPrevented) return
                switch (event.key) {
                    case 'ArrowDown':
                        event.preventDefault()
                        this.highlightNext()
                        break
                    case 'ArrowUp':
                        event.preventDefault()
                        this.highlightPrevious()
                        break
                    case 'Enter':
                        event.preventDefault()
                        this.selectHighlighted()
                        break
                    case ' ':
                        if (event.target !== this.$refs.searchInput) {
                            event.preventDefault()
                            this.selectHighlighted()
                        }
                        break
                    case 'Escape':
                        event.preventDefault()
                        this.close()
                        break
                    case 'Home':
                        event.preventDefault()
                        this.highlightedIndex = 0
                        this.scrollToHighlighted()
                        break
                    case 'End':
                        event.preventDefault()
                        this.highlightedIndex = Math.min(
                            this.results.length - 1,
                            this.optionsLimit - 1,
                        )
                        this.scrollToHighlighted()
                        break
                    case 'Tab':
                        this.close()
                        break
                }
            },

            highlightNext() {
                const maxIndex = Math.min(
                    this.results.length - 1,
                    this.optionsLimit - 1,
                )
                if (this.highlightedIndex < maxIndex) {
                    this.highlightedIndex++
                    this.scrollToHighlighted()
                }
            },

            highlightPrevious() {
                if (this.highlightedIndex > 0) {
                    this.highlightedIndex--
                    this.scrollToHighlighted()
                }
            },

            scrollToHighlighted() {
                this.$nextTick(() => {
                    const list = this.$refs.optionList
                    const items = list?.querySelectorAll('.option-container')
                    const el = items?.[this.highlightedIndex]
                    if (el && list) {
                        const top = el.offsetTop
                        const bottom = top + el.offsetHeight
                        const listTop = list.scrollTop
                        const listBottom = listTop + list.clientHeight
                        if (top < listTop) {
                            list.scrollTop = top
                        } else if (bottom > listBottom) {
                            list.scrollTop = bottom - list.clientHeight
                        }
                    }
                })
            },

            async loadInitialOptions() {
                try {
                    this.isSearching = true
                    const res = await $wire.callSchemaComponentMethod(
                        @js($key),
                        'getOptionsForJs',
                    )
                    this.results = Array.isArray(res) ? res : []
                } finally {
                    this.isSearching = false
                }
            },

            searchOptions() {
                clearTimeout(this.searchTimer)
                this.searchTimer = setTimeout(async () => {
                    this.isSearching = true
                    try {
                        const res = await $wire.callSchemaComponentMethod(
                            @js($key),
                            'getSearchResultsForJs',
                            { search: this.search },
                        )
                        this.results = Array.isArray(res) ? res : []
                        this.highlightedIndex = -1
                    } finally {
                        this.isSearching = false
                    }
                }, this.searchDebounce || 300)
            },

            selectOption(value, label) {
                this.state = value
                this.selectedLabel = label
                this.close()
            },

            selectHighlighted() {
                if (
                    this.highlightedIndex >= 0 &&
                    this.highlightedIndex < this.results.length
                ) {
                    const opt = this.results[this.highlightedIndex]
                    this.selectOption(opt.value, opt.label)
                }
            },
        }"
        x-init="init()"
        {{ $getExtraAttributeBag() }}
    >
        <div class="fi-input-wrp fi-fo-select">
            <div class="fi-select-input relative">
                <div
                    x-show="isSearching"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                >
                    <div class="flex items-center">
                        <svg
                            class="-ml-1 mr-3 h-4 w-4 animate-spin text-gray-500"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        {{ $getSearchingMessage() }}
                    </div>
                </div>

                <button
                    x-show="!isSearching"
                    @click="open()"
                    @keydown="handleKeyDown($event)"
                    type="button"
                    class="fi-select-input-btn"
                    :aria-expanded="isOpen"
                    aria-haspopup="listbox"
                    role="combobox"
                    :aria-label="selectedLabel ? `Selected: ${selectedLabel}` : '{{ $getPlaceholder() }}'"
                >
                    <span
                        x-text="selectedLabel || '{{ $getPlaceholder() }}'"
                        class="truncate"
                    ></span>
                </button>

                <div
                    x-show="isOpen && !isSearching"
                    @click.away="close()"
                    x-transition:enter="transition duration-100 ease-out"
                    x-transition:enter-start="scale-95 transform opacity-0"
                    x-transition:enter-end="scale-100 transform opacity-100"
                    x-transition:leave="transition duration-75 ease-in"
                    x-transition:leave-start="scale-100 transform opacity-100"
                    x-transition:leave-end="scale-95 transform opacity-0"
                    class="fi-dropdown-panel max-h-100 mt-1 overflow-hidden"
                    role="listbox"
                    aria-label="Option selection"
                >
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <div class="fi-select-input-search-ctn">
                            <input
                                x-model="search"
                                x-ref="searchInput"
                                placeholder="{{ $getSearchPrompt() }}"
                                class="fi-input"
                                @keydown="handleKeyDown($event)"
                                @input="searchOptions()"
                                role="searchbox"
                                aria-label="{{ $getSearchPrompt() }}"
                                aria-autocomplete="list"
                                :aria-activedescendant="highlightedIndex >= 0 ? `option-${highlightedIndex}` : null"
                            />
                        </div>
                    </div>

                    <div
                        class="max-h-60 overflow-y-auto overflow-x-clip"
                        x-ref="optionList"
                    >
                        <template
                            x-for="(option, index) in results.slice(0, optionsLimit)"
                            :key="option.value + '-' + index"
                        >
                            <div class="option-container">
                                <button
                                    @click="selectOption(option.value, option.label)"
                                    @mouseenter="highlightedIndex = index"
                                    type="button"
                                    class="flex w-full items-center justify-between border-b border-gray-200 px-4 py-3 text-left text-sm text-gray-900 transition-colors last:border-b-0 hover:bg-gray-50 dark:border-gray-700/50 dark:text-gray-100 dark:hover:bg-gray-800"
                                    :class="{
                                        'bg-primary-50 dark:bg-primary-700/20': state === option.value,
                                        'bg-primary-100 dark:bg-primary-600/30': highlightedIndex === index && state !== option.value
                                    }"
                                    role="option"
                                    :aria-selected="state === option.value"
                                    :id="`option-${index}`"
                                >
                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <span
                                                x-text="option.label"
                                                class="truncate text-sm font-medium"
                                            ></span>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </template>

                        <div
                            x-show="search && results.length === 0"
                            class="px-4 py-8 text-center text-sm text-gray-500"
                        >
                            <div class="mb-2">
                                <svg
                                    class="mx-auto h-8 w-8 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    ></path>
                                </svg>
                            </div>
                            {{ $getNoSearchResultsMessage() }} "
                            <span
                                x-text="search"
                                class="font-medium"
                            ></span>
                            "
                        </div>

                        <div
                            x-show="results.length > optionsLimit"
                            class="border-t border-gray-200 px-4 py-2 text-center text-xs text-gray-400 dark:border-gray-700"
                        >
                            Showing first
                            <span x-text="optionsLimit"></span>
                            results. Use search to narrow down options.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>

<?php
