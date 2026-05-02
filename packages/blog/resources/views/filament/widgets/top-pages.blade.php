<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Top pages</x-slot>

        @if ($data->pages->isEmpty())
            <p class="text-sm text-gray-500">No page views recorded yet.</p>
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($data->pages as $page)
                    <div class="flex items-center justify-between py-2 text-sm">
                        <span
                            class="truncate font-medium text-gray-700 dark:text-gray-300"
                        >
                            {{ $page->path }}
                        </span>
                        <span
                            class="ml-4 shrink-0 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400"
                        >
                            {{ number_format($page->views) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
