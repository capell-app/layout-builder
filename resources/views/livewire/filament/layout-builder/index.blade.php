<div>
    <div>
        <div
            class="mb-4 flex flex-wrap justify-between gap-4 pr-4 pl-1 sm:flex-nowrap lg:justify-end"
        >
            <div class="grow">
                <div class="text-lg font-semibold">
                    {{ __('capell-layout-builder::heading.layout_record', ['name' => $this->layout->name]) }}
                </div>
            </div>
        </div>

        @if ($this->layoutIsSharedWithOtherPages || ($this->page === null && $this->layoutIsUsedByPages))
            <x-filament::callout
                :icon="$this->layoutIsSharedWithOtherPages ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-information-circle'"
                :color="$this->layoutIsSharedWithOtherPages ? 'warning' : 'info'"
                class="mb-5"
            >
                <x-slot name="heading">
                    @if ($this->layoutIsSharedWithOtherPages)
                        {{
                            trans_choice(
                                'capell-layout-builder::message.layout_shared_with_other_pages_heading',
                                $this->otherPagesUsingLayoutCount,
                                ['count' => $this->otherPagesUsingLayoutCount],
                            )
                        }}
                    @else
                        {{
                            trans_choice(
                                'capell-layout-builder::message.layout_used_by_pages_heading',
                                $this->layoutPagesCount,
                                ['count' => $this->layoutPagesCount],
                            )
                        }}
                    @endif

                    <x-filament::link
                        href="{{ $this->getPagesUsingLayoutUrl() }}"
                        class="text-primary-700 hover:text-primary-600 decoration-primary-500/40 dark:text-primary-300 dark:hover:text-primary-200 inline-flex items-center gap-1 font-medium underline underline-offset-4"
                    >
                        {{ __('capell-layout-builder::button.view_pages_using_layout') }}
                    </x-filament::link>
                </x-slot>

                <x-slot name="description">
                    @if ($this->layoutIsSharedWithOtherPages)
                        {{
                            trans_choice(
                                'capell-layout-builder::message.layout_shared_with_other_pages_body',
                                $this->otherPagesUsingLayoutCount,
                            )
                        }}
                    @else
                        {{
                            trans_choice(
                                'capell-layout-builder::message.layout_used_by_pages_body',
                                $this->layoutPagesCount,
                            )
                        }}
                    @endif
                </x-slot>

                @if ($this->layoutIsSharedWithOtherPages)
                    <x-slot name="controls">
                        {{ $this->cloneLayoutForPageAction }}
                    </x-slot>
                @endif
            </x-filament::callout>
        @endif

        @php
            $starterLayoutPresets = $this->starterLayoutPresets;
            $canBrowseStarterLayouts = $this->canEditLayout() && $starterLayoutPresets !== [];
        @endphp

        @include ('capell-layout-builder::livewire.filament.layout-builder.visual-editor', [
            'canBrowseStarterLayouts' => $canBrowseStarterLayouts,
            'starterLayoutPresets' => $starterLayoutPresets,
        ])

        @if ($canBrowseStarterLayouts)
            <x-filament::modal
                id="capell-layout-builder-starter-layouts"
                :heading="__('capell-layout-builder::heading.starter_layouts')"
                :description="__('capell-layout-builder::message.starter_layouts_description')"
                width="2xl"
                slide-over
            >
                <div
                    x-data="{ starterLayoutsSearch: '' }"
                    class="flex flex-col gap-4"
                >
                    <label class="relative">
                        <span class="sr-only">
                            {{ __('capell-layout-builder::form.search_starter_layouts') }}
                        </span>
                        <span
                            class="pointer-events-none absolute inset-y-0 left-3 inline-flex items-center text-gray-400"
                            aria-hidden="true"
                        >
                            @svg ('heroicon-o-magnifying-glass', 'h-4 w-4')
                        </span>
                        <input
                            type="search"
                            x-model.debounce.120ms="starterLayoutsSearch"
                            placeholder="{{ __('capell-layout-builder::form.search_starter_layouts') }}"
                            class="focus:border-primary-500 focus:ring-primary-500 w-full rounded-lg border border-gray-200 bg-white py-2 pr-3 pl-9 text-sm shadow-sm focus:ring-1 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        />
                    </label>

                    <ul
                        role="list"
                        class="divide-y divide-gray-100 dark:divide-gray-800"
                    >
                        @foreach ($starterLayoutPresets as $preset)
                            <li
                                wire:key="starter-layout-preset-{{ $preset->key }}"
                                x-show="
                                    starterLayoutsSearch === '' ||
                                    $el.dataset.starterTerm
                                        .toLowerCase()
                                        .includes(
                                            starterLayoutsSearch.toLowerCase(),
                                        )
                                "
                                data-starter-term="{{ $preset->label . ' ' . $preset->description }}"
                                class="flex items-start justify-between gap-4 py-3"
                            >
                                <div class="min-w-0 flex-1">
                                    <h3
                                        class="text-sm font-semibold text-gray-950 dark:text-white"
                                    >
                                        {{ $preset->label }}
                                    </h3>
                                    <p
                                        class="mt-0.5 text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        {{ $preset->description }}
                                    </p>
                                    <p
                                        class="mt-1 text-xs text-gray-500 dark:text-gray-500"
                                    >
                                        {{ trans_choice('capell-layout-builder::message.starter_layout_container_count', count($preset->containers), ['count' => count($preset->containers)]) }}
                                        <span
                                            class="mx-1 opacity-50"
                                            aria-hidden="true"
                                        >
                                            ·
                                        </span>
                                        {{ trans_choice('capell-layout-builder::message.starter_layout_section_count', count($preset->sections), ['count' => count($preset->sections)]) }}
                                    </p>
                                </div>

                                <x-filament::button
                                    icon="heroicon-o-sparkles"
                                    size="sm"
                                    x-on:click="
                                        $dispatch('close-modal', {
                                            id: 'capell-layout-builder-starter-layouts',
                                        })
                                    "
                                    wire:click="applyStarterLayoutPreset(@js($preset->key))"
                                    wire:loading.attr="disabled"
                                    wire:target="applyStarterLayoutPreset"
                                >
                                    {{ __('capell-layout-builder::button.apply_starter_layout') }}
                                </x-filament::button>
                            </li>
                        @endforeach
                    </ul>

                    <p
                        x-show="
                            starterLayoutsSearch !== '' &&
                            ![
                                ...$root.querySelectorAll(
                                    '[data-starter-term]',
                                ),
                            ].some((row) =>
                                row.dataset.starterTerm
                                    .toLowerCase()
                                    .includes(
                                        starterLayoutsSearch.toLowerCase(),
                                    ),
                            )
                        "
                        x-cloak
                        class="py-6 text-center text-sm text-gray-500 dark:text-gray-400"
                    >
                        {{ __('capell-layout-builder::message.starter_layouts_search_empty') }}
                    </p>
                </div>
            </x-filament::modal>
        @endif
    </div>

    <x-filament-actions::modals />
</div>
