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

        @if ($this->layoutModified)
            <x-filament::callout
                icon="heroicon-o-exclamation-triangle"
                color="warning"
                class="mb-5"
            >
                <x-slot name="heading">
                    {{ __('capell-layout-builder::message.layout_unsaved') }}
                </x-slot>

                @error('layoutDiagnostics')
                    <p
                        class="text-danger-600 dark:text-danger-400 mt-2 text-sm font-medium"
                    >
                        {{ $message }}
                    </p>
                @enderror

                @if ($this->layoutDiagnostics !== [])
                    <ul class="mt-2 list-disc space-y-1 ps-5 text-sm">
                        @foreach ($this->layoutDiagnostics as $diagnostic)
                            <li>
                                {{ $diagnostic['message'] ?? __('capell-admin::message.unknown_widget', ['widget' => __('capell-admin::generic.unknown')]) }}
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($this->saveLayoutAction->isVisible())
                    <x-slot name="controls">
                        {{ $this->saveLayoutAction }}
                    </x-slot>
                @endif
            </x-filament::callout>
        @endif

        @include('capell-layout-builder::livewire.filament.layout-builder.visual-editor')
    </div>

    <x-filament-actions::modals />
</div>
