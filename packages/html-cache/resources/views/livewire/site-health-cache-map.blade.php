<x-filament::section :heading="__('capell-html-cache::admin.cache_map')">
    @php
        $overview = $this->overview;
        $selectedResource = $this->selectedResource();
    @endphp

    @if ($overview->totalDependencies === 0)
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('capell-html-cache::admin.cache_map_empty_selected_site') }}
        </p>
    @else
        <div class="space-y-4">
            <div class="grid gap-3 md:grid-cols-3">
                <div
                    class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                >
                    <div
                        class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400"
                    >
                        {{ __('capell-html-cache::admin.cache_map_cached_urls') }}
                    </div>
                    <div
                        class="mt-2 text-3xl font-semibold tabular-nums text-gray-950 dark:text-white"
                    >
                        {{ $overview->totalUrls }}
                    </div>
                </div>

                <div
                    class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                >
                    <div
                        class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400"
                    >
                        {{ __('capell-html-cache::admin.cache_map_dependencies') }}
                    </div>
                    <div
                        class="mt-2 text-3xl font-semibold tabular-nums text-gray-950 dark:text-white"
                    >
                        {{ $overview->totalDependencies }}
                    </div>
                </div>

                <div
                    class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                >
                    <div
                        class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400"
                    >
                        {{ __('capell-html-cache::admin.cache_map_model_groups') }}
                    </div>
                    <div
                        class="mt-2 text-3xl font-semibold tabular-nums text-gray-950 dark:text-white"
                    >
                        {{ count($overview->modelSummaries) }}
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                    {{ __('capell-html-cache::admin.cache_map_top_resources') }}
                </h3>

                <button
                    type="button"
                    wire:click="openDetail"
                    class="text-primary-600 dark:text-primary-400 text-xs font-semibold hover:underline"
                >
                    {{ __('capell-html-cache::admin.cache_map_explore_all') }}
                </button>
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($overview->topResources as $resource)
                    <button
                        type="button"
                        wire:key="cache-map-top-resource-{{ $resource->key }}"
                        wire:click="openResourceDetail(@js($resource->modelType), @js($resource->key))"
                        class="hover:border-primary-300 dark:hover:border-primary-600 block rounded-lg border border-gray-200 bg-white p-3 text-left transition dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div
                                    class="text-sm font-medium text-gray-950 dark:text-white"
                                >
                                    {{ $resource->label }}
                                </div>
                                <div
                                    class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {{ $resource->modelLabel }}
                                    #{{ $resource->resourceId }}
                                </div>
                            </div>
                            <span
                                class="rounded-md bg-gray-100 px-2 py-1 text-xs font-semibold tabular-nums text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                            >
                                {{ $resource->urlCount }}
                            </span>
                        </div>
                    </button>
                @endforeach
            </div>

            <x-filament::modal
                id="html-cache-map-detail"
                width="7xl"
                slide-over
                sticky-header
                :heading="__('capell-html-cache::admin.cache_map_detail_heading')"
            >
                <div class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-3">
                        <label class="space-y-1">
                            <span
                                class="text-xs font-medium text-gray-600 dark:text-gray-300"
                            >
                                {{ __('capell-html-cache::admin.model') }}
                            </span>
                            <select
                                wire:model.live="selectedModelType"
                                class="focus:border-primary-500 focus:ring-primary-500 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                            >
                                <option value="">
                                    {{ __('capell-html-cache::admin.cache_map_all_models') }}
                                </option>
                                @foreach ($this->modelOptions as $modelType => $modelLabel)
                                    <option value="{{ $modelType }}">
                                        {{ $modelLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="space-y-1">
                            <span
                                class="text-xs font-medium text-gray-600 dark:text-gray-300"
                            >
                                {{ __('capell-html-cache::admin.cache_map_resource_search') }}
                            </span>
                            <input
                                type="search"
                                wire:model.live.debounce.300ms="resourceSearch"
                                @disabled(! filled($this->selectedModelType))
                                placeholder="{{ __('capell-html-cache::admin.cache_map_resource_search_placeholder') }}"
                                class="focus:border-primary-500 focus:ring-primary-500 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm disabled:bg-gray-50 disabled:text-gray-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:disabled:bg-gray-900"
                            />
                        </label>

                        <label class="space-y-1">
                            <span
                                class="text-xs font-medium text-gray-600 dark:text-gray-300"
                            >
                                {{ __('capell-html-cache::admin.resource') }}
                            </span>
                            <select
                                wire:model.live="selectedResourceKey"
                                @disabled(! filled($this->selectedModelType))
                                class="focus:border-primary-500 focus:ring-primary-500 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm disabled:bg-gray-50 disabled:text-gray-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:disabled:bg-gray-900"
                            >
                                <option value="">
                                    {{ __('capell-html-cache::admin.cache_map_top_five_resources') }}
                                </option>
                                @foreach ($this->resourceOptions as $resource)
                                    <option value="{{ $resource->key }}">
                                        {{ $resource->label }}
                                        ({{ trans_choice('capell-html-cache::admin.cache_map_urls_count', $resource->urlCount, ['count' => $resource->urlCount]) }})
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    @if (filled($this->selectedModelType))
                        <div>
                            <h3
                                class="text-sm font-semibold text-gray-950 dark:text-white"
                            >
                                @if ($selectedResource instanceof CacheMapResourceSummaryData)
                                    {{ __('capell-html-cache::admin.cache_map_urls_containing_resource', ['resource' => $selectedResource->label]) }}
                                @else
                                    {{ __('capell-html-cache::admin.cache_map_urls_containing_model', ['model' => $this->modelOptions[$this->selectedModelType] ?? class_basename($this->selectedModelType)]) }}
                                @endif
                            </h3>
                            <p
                                class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                            >
                                {{ trans_choice('capell-html-cache::admin.cache_map_urls_count', $this->detailUrlCount(), ['count' => $this->detailUrlCount()]) }}
                            </p>
                        </div>
                    @endif

                    {{ $this->table }}
                </div>
            </x-filament::modal>
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament::section>
