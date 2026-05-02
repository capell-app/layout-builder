<x-filament-widgets::widget>
    <x-filament::section heading="Article health">
        <div class="space-y-6">
            {{-- Summary Cards --}}
            <div class="grid grid-cols-3 gap-4">
                <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                    <div
                        class="text-sm font-medium text-gray-600 dark:text-gray-400"
                    >
                        Total Articles
                    </div>
                    <div
                        class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ $data->totalArticles }}
                    </div>
                </div>
                <div class="rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                    <div
                        class="text-sm font-medium text-gray-600 dark:text-gray-400"
                    >
                        Total Tags
                    </div>
                    <div
                        class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ $data->totalTags }}
                    </div>
                </div>
                <div class="rounded-lg bg-purple-50 p-4 dark:bg-purple-900/20">
                    <div
                        class="text-sm font-medium text-gray-600 dark:text-gray-400"
                    >
                        Recent (7 days)
                    </div>
                    <div
                        class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ $data->recentlyCreatedCount + $data->recentlyUpdatedCount }}
                    </div>
                </div>
            </div>

            {{-- Status Breakdown --}}
            <div>
                <h3
                    class="mb-3 text-sm font-semibold text-gray-900 dark:text-white"
                >
                    Status Breakdown
                </h3>
                <div class="space-y-2">
                    <div
                        class="flex items-center justify-between rounded px-3 py-2 text-sm odd:bg-gray-50 dark:odd:bg-gray-800/50"
                    >
                        <span class="text-gray-700 dark:text-gray-300">
                            Published
                        </span>
                        <span
                            class="rounded bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300"
                        >
                            {{ $data->publishedCount }}
                        </span>
                    </div>
                    <div
                        class="flex items-center justify-between rounded px-3 py-2 text-sm even:bg-gray-50 dark:even:bg-gray-800/50"
                    >
                        <span class="text-gray-700 dark:text-gray-300">
                            Draft
                        </span>
                        <span
                            class="rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                        >
                            {{ $data->draftCount }}
                        </span>
                    </div>
                    <div
                        class="flex items-center justify-between rounded px-3 py-2 text-sm odd:bg-gray-50 dark:odd:bg-gray-800/50"
                    >
                        <span class="text-gray-700 dark:text-gray-300">
                            Scheduled (Future)
                        </span>
                        <span
                            class="rounded bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                        >
                            {{ $data->scheduledFutureCount }}
                        </span>
                    </div>
                    <div
                        class="flex items-center justify-between rounded px-3 py-2 text-sm even:bg-gray-50 dark:even:bg-gray-800/50"
                    >
                        <span class="text-gray-700 dark:text-gray-300">
                            Expired
                        </span>
                        <span
                            class="rounded bg-red-100 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-300"
                        >
                            {{ $data->expiredCount }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Top Tags --}}
            @if ($data->topTags->count())
                <details class="group">
                    <summary
                        class="flex cursor-pointer items-center justify-between gap-2 py-2 text-sm font-medium text-gray-900 dark:text-white"
                    >
                        <span>Top Tags</span>
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
                        @foreach ($data->topTags as $tag)
                            <div
                                class="flex items-center justify-between rounded px-2 py-1 text-xs odd:bg-gray-50 dark:odd:bg-gray-800/50"
                            >
                                <span class="text-gray-700 dark:text-gray-300">
                                    {{ $tag->name }}
                                </span>
                                <span
                                    class="rounded bg-blue-100 px-1.5 py-0.5 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                                >
                                    {{ $tag->articleCount }} articles
                                </span>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif

            {{-- Language Coverage --}}
            @if ($data->languageCoverage->count())
                <details class="group">
                    <summary
                        class="flex cursor-pointer items-center justify-between gap-2 py-2 text-sm font-medium text-gray-900 dark:text-white"
                    >
                        <span>Translation Coverage</span>
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
                        @foreach ($data->languageCoverage as $lang)
                            <div
                                class="space-y-1 rounded px-2 py-1 text-xs odd:bg-gray-50 dark:odd:bg-gray-800/50"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {{ $lang->language }}
                                    </span>
                                    <span
                                        class="text-gray-600 dark:text-gray-400"
                                    >
                                        {{ $lang->withTranslation }}/{{ $lang->total }}
                                    </span>
                                </div>
                                @if ($lang->total > 0)
                                    <div
                                        class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700"
                                    >
                                        <div
                                            class="h-full bg-green-500"
                                            style="
                                                width: {{ ($lang->withTranslation / $lang->total) * 100 }}%;
                                            "
                                        ></div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
