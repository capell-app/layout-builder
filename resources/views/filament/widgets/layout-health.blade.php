<x-filament-widgets::widget>
    <x-filament::section
        :heading="__('capell-layout-builder::widgets.admin.layout_health.heading')"
    >
        <div class="space-y-6">
            {{-- Summary Cards --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                    <div
                        class="text-sm font-medium text-gray-600 dark:text-gray-400"
                    >
                        {{ __('capell-layout-builder::widgets.admin.layout_health.total_widgets') }}
                    </div>
                    <div
                        class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ $data->totalWidgets }}
                    </div>
                </div>
                <div class="rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                    <div
                        class="text-sm font-medium text-gray-600 dark:text-gray-400"
                    >
                        {{ __('capell-layout-builder::widgets.admin.layout_health.total_sections') }}
                    </div>
                    <div
                        class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ $data->totalSections }}
                    </div>
                </div>
                <div class="rounded-lg bg-amber-50 p-4 dark:bg-amber-900/20">
                    <div
                        class="text-sm font-medium text-gray-600 dark:text-gray-400"
                    >
                        {{ __('capell-layout-builder::widgets.admin.layout_health.published_sections') }}
                    </div>
                    <div
                        class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ $data->publishedSections }}
                    </div>
                </div>
                <div class="rounded-lg bg-purple-50 p-4 dark:bg-purple-900/20">
                    <div
                        class="text-sm font-medium text-gray-600 dark:text-gray-400"
                    >
                        {{ __('capell-layout-builder::widgets.admin.layout_health.pending_modifications') }}
                    </div>
                    <div
                        class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ $data->layoutsWithModifications }}
                    </div>
                </div>
            </div>

            {{-- Widget Groups --}}
            <div>
                <h3
                    class="mb-3 text-sm font-semibold text-gray-900 dark:text-white"
                >
                    {{ __('capell-layout-builder::widgets.admin.layout_health.widgets_by_group') }}
                </h3>
                <div class="space-y-2">
                    @forelse ($data->widgetsByGroup as $group)
                        <div
                            class="flex items-center justify-between rounded px-3 py-2 text-sm odd:bg-gray-50 dark:odd:bg-gray-800/50"
                        >
                            <span
                                class="font-medium text-gray-700 dark:text-gray-300"
                            >
                                {{ $group->group }}
                            </span>
                            <div class="flex items-center gap-3">
                                <span
                                    class="rounded bg-green-100 px-2 py-1 text-xs text-green-700 dark:bg-green-900/40 dark:text-green-300"
                                >
                                    {{ __('capell-layout-builder::widgets.admin.layout_health.published_count', ['count' => $group->published]) }}
                                </span>
                                <span
                                    class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    {{ __('capell-layout-builder::widgets.admin.layout_health.total_count', ['count' => $group->count]) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">
                            {{ __('capell-layout-builder::widgets.admin.layout_health.no_widgets_configured') }}
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- Least Used Widgets --}}
            @if ($data->leastUsedWidgets->count())
                <details class="group">
                    <summary
                        class="flex cursor-pointer items-center justify-between gap-2 py-2 text-sm font-medium text-gray-900 dark:text-white"
                    >
                        <span>
                            {{ __('capell-layout-builder::widgets.admin.layout_health.least_used_widgets') }}
                        </span>
                        <svg
                            class="h-4 w-4 transition-transform group-open:rotate-180"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </summary>
                    <div class="mt-2 space-y-1 pl-2">
                        @foreach ($data->leastUsedWidgets as $widget)
                            <div
                                class="flex items-center justify-between rounded px-2 py-1 text-xs odd:bg-gray-50 dark:odd:bg-gray-800/50"
                            >
                                <span class="text-gray-700 dark:text-gray-300">
                                    {{ $widget->name }}
                                </span>
                                <span
                                    class="rounded bg-amber-100 px-1.5 py-0.5 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300"
                                >
                                    {{ __('capell-layout-builder::widgets.admin.layout_health.layout_count', ['count' => $widget->layoutCount]) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif

            {{-- Unused Widgets --}}
            @if ($data->unusedWidgets->count())
                <details class="group">
                    <summary
                        class="flex cursor-pointer items-center justify-between gap-2 py-2 text-sm font-medium text-gray-900 dark:text-white"
                    >
                        <span>
                            {{ __('capell-layout-builder::widgets.admin.layout_health.unused_widget_types') }}
                        </span>
                        <span
                            class="rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700 dark:bg-red-900/40 dark:text-red-300"
                        >
                            {{ $data->unusedWidgets->count() }}
                        </span>
                        <svg
                            class="h-4 w-4 transition-transform group-open:rotate-180"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </summary>
                    <div class="mt-2 space-y-1 pl-2">
                        @foreach ($data->unusedWidgets as $widget)
                            <div
                                class="flex items-center justify-between rounded px-2 py-1 text-xs odd:bg-gray-50 dark:odd:bg-gray-800/50"
                            >
                                <span class="text-gray-700 dark:text-gray-300">
                                    {{ $widget->name }}
                                </span>
                                <span
                                    class="rounded bg-gray-100 px-1.5 py-0.5 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    {{ $widget->group }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
