<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('capell-layout-builder::widgets.admin.recent_activity.heading') }}
        </x-slot>

        @if ($data->items->isEmpty())
            <p class="text-sm text-gray-500">
                {{ __('capell-layout-builder::widgets.admin.recent_activity.empty') }}
            </p>
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($data->items as $item)
                    @php
                        $badgeClass = match ($item->status) {
                            'published' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                            'draft' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                            'scheduled' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                            default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                        };
                    @endphp

                    <div class="flex items-center justify-between py-2 text-sm">
                        <span
                            class="truncate font-medium text-gray-700 dark:text-gray-300"
                        >
                            {{ $item->title }}
                        </span>
                        <span
                            class="{{ $badgeClass }} ml-4 shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                        >
                            {{ $item->status }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
