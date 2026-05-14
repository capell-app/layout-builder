{{-- format-ignore-start --}}
@php
    use Filament\Support\Facades\FilamentAsset;

    $layoutBuilderConfiguration = 'Capell\\LayoutBuilder\\Support\\LayoutBuilderConfiguration';
    $matchFrontendContainerLayout = class_exists($layoutBuilderConfiguration) && method_exists($layoutBuilderConfiguration, 'matchFrontendContainerLayout')
        ? $layoutBuilderConfiguration::matchFrontendContainerLayout()
        : (bool) config(
            'capell-layout-builder.preview.match_frontend_container_layout',
            config('capell-admin.layout_builder.preview.match_frontend_container_layout', true),
        );
@endphp
{{-- format-ignore-end --}}
<div>
    <div
        x-load
        x-load-src="{{
            FilamentAsset::getAlpineComponentSrc(
                'layout-builder',
                'capell-layout-builder',
            )
        }}"
        x-data="layoutBuilderComponent"
        data-active-breakpoint="{{ $activeBreakpoint?->value }}"
        data-match-frontend-container-layout="{{ $matchFrontendContainerLayout ? 'true' : 'false' }}"
        x-bind:class="{
            'layout-builder-widget-actions-suppressed': isWidgetActionSuppressed,
        }"
        x-on:expand-all-containers.window="expandAll"
        x-on:collapse-all-containers.window="collapseAll"
        x-on:pointerup.window="releaseWidgetActions()"
        x-on:pointercancel.window="releaseWidgetActions()"
    >
        <div>
            <div
                class="mb-4 flex flex-wrap justify-between gap-4 pl-1 pr-4 sm:flex-nowrap lg:justify-end"
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

            @if ($editorMode === 'content_first')
                @include('capell-layout-builder::livewire.filament.layout-builder.content-first')
            @else
                <div
                    class="layout-canvas rounded-lg bg-gray-50 p-4 dark:bg-gray-950"
                >
                <div
                    class="layout-builder-workboard-toolbar mb-5 grid grid-cols-1 items-center gap-3 md:grid-cols-[1fr_minmax(16rem,28rem)_1fr]"
                >
                    <div
                        class="layout-builder-primary-actions flex flex-wrap items-center justify-start gap-2"
                    >
                        @if ($this->canEditContent())
                            <x-filament::button
                                color="gray"
                                icon="heroicon-o-arrow-left"
                                size="sm"
                                wire:click="showContentEditor"
                            >
                                {{ __('capell-layout-builder::button.return_to_content') }}
                            </x-filament::button>
                        @endif

                        {{ $this->addWidgetAction }}

                        {{ $this->addContainerAction }}

                        <button
                            type="button"
                            class="layout-builder-edit-mode-toggle"
                            x-on:click="setMode(mode === 'edit' ? 'view' : 'edit')"
                            x-bind:aria-pressed="(mode === 'edit').toString()"
                            x-bind:aria-label="
                                mode === 'edit'
                                    ? {{ Js::from(__('capell-layout-builder::button.exit_edit_mode')) }}
                                    : {{ Js::from(__('capell-layout-builder::button.edit_mode')) }}
                            "
                            x-bind:class="mode === 'edit' ? 'layout-builder-edit-mode-toggle-active' : ''"
                            x-tooltip.raw="{{ __('capell-layout-builder::button.edit_mode') }}"
                        >
                            @svg('heroicon-o-pencil-square', 'h-4 w-4')
                        </button>
                    </div>

                    @if ($containers)
                        <div
                            class="layout-builder-breakpoint-control inline-flex w-full max-w-full justify-self-center rounded-lg bg-white text-xs font-medium text-gray-600 shadow-sm ring-1 ring-gray-950/10 sm:w-auto dark:bg-white/5 dark:text-gray-300 dark:ring-white/10"
                            role="group"
                            aria-label="{{ __('capell-layout-builder::button.preview_breakpoint') }}"
                        >
                            @foreach ([['value' => null, 'label' => __('capell-layout-builder::button.desktop'), 'icon' => 'heroicon-o-computer-desktop'], ['value' => 'tablet', 'label' => __('capell-layout-builder::button.tablet'), 'icon' => 'heroicon-o-device-tablet'], ['value' => 'mobile', 'label' => __('capell-layout-builder::button.mobile'), 'icon' => 'heroicon-o-device-phone-mobile']] as $breakpointOption)
                                <button
                                    type="button"
                                    class="layout-builder-breakpoint-button"
                                    x-on:click="setActiveBreakpointPreview({{ Js::from($breakpointOption['value']) }})"
                                    x-bind:aria-pressed="isActiveBreakpoint({{ Js::from($breakpointOption['value']) }}).toString()"
                                    aria-pressed="{{ ($activeBreakpoint?->value ?? null) === $breakpointOption['value'] ? 'true' : 'false' }}"
                                    aria-label="{{ $breakpointOption['label'] }}"
                                    x-tooltip.raw="{{ $breakpointOption['label'] }}"
                                >
                                    @svg($breakpointOption['icon'], 'h-5 w-5 shrink-0')

                                    <span
                                        class="layout-builder-breakpoint-label"
                                    >
                                        {{ $breakpointOption['label'] }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div></div>
                    @endif

                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <div
                            class="layout-builder-history-actions flex items-center gap-2"
                        >
                            {{ $this->undoLayoutMutationAction }}

                            {{ $this->redoLayoutMutationAction }}
                        </div>

                        <div
                            class="layout-builder-density-actions inline-flex w-full sm:w-auto"
                        >
                            <button
                                type="button"
                                class="text-primary-700 hover:text-primary-600 focus-visible:text-primary-600 dark:text-primary-300 dark:hover:text-primary-200 dark:focus-visible:text-primary-200 inline-flex h-9 w-full items-center justify-center gap-1.5 px-1 text-xs font-medium underline-offset-4 transition hover:underline focus-visible:underline sm:w-auto"
                                x-on:click="toggleAllComponents()"
                                x-bind:aria-label="
                                    isContainersAllCollapsed === true
                                        ? '{{ __('capell-layout-builder::button.expand_all') }}'
                                        : '{{ __('capell-layout-builder::button.collapse_all') }}'
                                "
                            >
                                @svg('heroicon-m-plus', 'h-4 w-4', ['x-show' => 'isContainersAllCollapsed === true'])
                                @svg('heroicon-o-minus', 'h-4 w-4', ['x-show' => 'isContainersAllCollapsed !== true'])
                                <span
                                    x-show="isContainersAllCollapsed === true"
                                >
                                    {{ __('capell-layout-builder::button.expand_all') }}
                                </span>
                                <span
                                    x-show="isContainersAllCollapsed !== true"
                                    x-cloak
                                >
                                    {{ __('capell-layout-builder::button.collapse_all') }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="layout-builder-canvas-scroll">
                    <div
                        class="layout-builder-canvas-frame mx-auto transition-all duration-150"
                        x-bind:style="{
                            maxWidth: activeBreakpointMaxCanvasWidth(),
                            minWidth: activeBreakpointMinCanvasWidth(),
                        }"
                    >
                        <div class="space-y-10">
                            @if ($containers)
                                <div
                                    class="layout-containers mb-4 grid grid-cols-12 gap-4"
                                    x-sort="reorderContainer($item, $position)"
                                    x-sort:config="{
                                        animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches
                                            ? 0
                                            : 180,
                                        easing: 'cubic-bezier(0.2, 0, 0, 1)',
                                        forceFallback: true,
                                        fallbackClass: 'layout-sort-fallback',
                                        ghostClass: 'layout-sort-ghost',
                                        chosenClass: 'layout-sort-chosen',
                                        dragClass: 'layout-sort-drag',
                                    }"
                                >
                                    @php
                                        $occupiedContainerColumns = 0;
                                        $nextContainerStartsRow = true;
                                    @endphp

                                    @foreach ($containers as $containerKey => $container)
                                        @php
                                            $containerColspan = min(12, max(1, (int) data_get($container, 'meta.colspan', 12)));
                                            $startsContainerRow = $nextContainerStartsRow || ($occupiedContainerColumns + $containerColspan > 12);

                                            if ($startsContainerRow) {
                                                $occupiedContainerColumns = 0;
                                            }
                                        @endphp

                                        @if ($startsContainerRow)
                                            <div
                                                class="layout-container-insert-zone col-span-12 -my-1 flex items-center gap-3 px-2"
                                                x-show="shouldShowInsertTargets()"
                                                x-transition.opacity
                                                x-cloak
                                            >
                                                <span
                                                    class="border-primary-500/45 h-px flex-1 border-t border-dashed"
                                                ></span>

                                                <div
                                                    class="layout-container-insert-button border-primary-500/45 text-primary-600 hover:border-primary-500 dark:text-primary-400 rounded-full border bg-white text-xs font-medium shadow-sm transition"
                                                >
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center gap-1 px-2 py-0.5"
                                                        wire:click="insertContainerAtPosition({{ $loop->index }})"
                                                        aria-label="{{ __('capell-layout-builder::message.insert_container_here') }}"
                                                    >
                                                        @svg('heroicon-m-plus', 'h-3.5 w-3.5')
                                                        <span>
                                                            {{ __('capell-layout-builder::button.container') }}
                                                        </span>
                                                    </button>
                                                    <span class="sr-only">
                                                        {{ __('capell-layout-builder::message.insert_container_here') }}
                                                    </span>
                                                </div>

                                                <span
                                                    class="border-primary-500/45 h-px flex-1 border-t border-dashed"
                                                ></span>
                                            </div>
                                        @endif

                                        <x-capell-layout-builder::filament.layout-builder.container
                                            :$container
                                            :$containerKey
                                            :containerWidgets="$this->containerWidgets[$containerKey] ?? []"
                                        />

                                        @php
                                            $occupiedContainerColumns += $containerColspan;
                                            $nextContainerStartsRow = $occupiedContainerColumns >= 12;
                                        @endphp

                                        @if ($loop->last)
                                            <div
                                                class="layout-container-insert-zone col-span-12 -my-1 flex items-center gap-3 px-2"
                                                x-show="shouldShowInsertTargets()"
                                                x-transition.opacity
                                                x-cloak
                                            >
                                                <span
                                                    class="border-primary-500/45 h-px flex-1 border-t border-dashed"
                                                ></span>

                                                <div
                                                    class="layout-container-insert-button border-primary-500/45 text-primary-600 hover:border-primary-500 dark:text-primary-400 rounded-full border bg-white text-xs font-medium shadow-sm transition"
                                                >
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center gap-1 px-2 py-0.5"
                                                        wire:click="insertContainerAtPosition({{ $loop->iteration }})"
                                                        aria-label="{{ __('capell-layout-builder::message.insert_container_here') }}"
                                                    >
                                                        @svg('heroicon-m-plus', 'h-3.5 w-3.5')
                                                        <span>
                                                            {{ __('capell-layout-builder::button.container') }}
                                                        </span>
                                                    </button>
                                                    <span class="sr-only">
                                                        {{ __('capell-layout-builder::message.insert_container_here') }}
                                                    </span>
                                                </div>

                                                <span
                                                    class="border-primary-500/45 h-px flex-1 border-t border-dashed"
                                                ></span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <div
                                    class="layout-empty rounded-xl border border-gray-200 p-6 px-3 text-center text-base text-gray-600 dark:border-gray-700 dark:text-gray-100"
                                >
                                    {{ __('capell-layout-builder::message.layout_empty') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                </div>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
</div>
