<x-filament-widgets::widget>
    <x-filament::section heading="Workspace merge history">
        @php
            $data = $this->data;
        @endphp

        <div class="space-y-2">
            @if ($data->entries->count() === 0)
                <div class="text-sm text-gray-400 dark:text-gray-500">
                    No merged workspaces found.
                </div>
            @else
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($data->entries as $entry)
                        <div class="flex items-start gap-3 py-2">
                            <div class="min-w-0 flex-1">
                                <span
                                    class="block truncate text-sm font-medium text-gray-800 dark:text-gray-100"
                                >
                                    {{ $entry->name }}
                                </span>
                                <div
                                    class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {{ $entry->actorName }}
                                    &middot;
                                    {{ $entry->pageCount }}
                                    {{ Str::plural('page', $entry->pageCount) }}
                                    &middot; {{ $entry->durationOpenHours }}h
                                    open
                                </div>
                            </div>
                            <time
                                datetime="{{ $entry->publishedAt }}"
                                class="shrink-0 text-xs text-gray-400 dark:text-gray-500"
                            >
                                {{ Carbon::parse($entry->publishedAt)->diffForHumans() }}
                            </time>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
