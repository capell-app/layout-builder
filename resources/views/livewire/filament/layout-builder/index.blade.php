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
        @endphp

        @if ($this->canEditLayout() && $starterLayoutPresets !== [])
            <section
                class="mb-5 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                aria-labelledby="capell-layout-builder-starter-layouts-heading"
            >
                <div
                    class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <h2
                            id="capell-layout-builder-starter-layouts-heading"
                            class="text-sm font-semibold text-gray-950 dark:text-white"
                        >
                            {{ __('capell-layout-builder::heading.starter_layouts') }}
                        </h2>

                        <p
                            class="mt-1 max-w-3xl text-sm text-gray-600 dark:text-gray-400"
                        >
                            {{ __('capell-layout-builder::message.starter_layouts_description') }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($starterLayoutPresets as $preset)
                        <article
                            wire:key="starter-layout-preset-{{ $preset->key }}"
                            class="flex min-h-36 flex-col justify-between rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-950"
                        >
                            <div>
                                <h3
                                    class="text-sm font-medium text-gray-950 dark:text-white"
                                >
                                    {{ $preset->label }}
                                </h3>

                                <p
                                    class="mt-1 text-sm text-gray-600 dark:text-gray-400"
                                >
                                    {{ $preset->description }}
                                </p>
                            </div>

                            <div
                                class="mt-4 flex flex-wrap items-center justify-between gap-3"
                            >
                                <div class="flex flex-wrap gap-1.5">
                                    <span
                                        class="rounded-md bg-white px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700"
                                    >
                                        {{ trans_choice('capell-layout-builder::message.starter_layout_container_count', count($preset->containers), ['count' => count($preset->containers)]) }}
                                    </span>

                                    <span
                                        class="rounded-md bg-white px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700"
                                    >
                                        {{ trans_choice('capell-layout-builder::message.starter_layout_section_count', count($preset->sections), ['count' => count($preset->sections)]) }}
                                    </span>
                                </div>

                                <x-filament::button
                                    icon="heroicon-o-sparkles"
                                    size="xs"
                                    wire:click="applyStarterLayoutPreset(@js($preset->key))"
                                    wire:loading.attr="disabled"
                                    wire:target="applyStarterLayoutPreset"
                                >
                                    {{ __('capell-layout-builder::button.apply_starter_layout') }}
                                </x-filament::button>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @include('capell-layout-builder::livewire.filament.layout-builder.visual-editor')
    </div>

    <x-filament-actions::modals />
</div>
