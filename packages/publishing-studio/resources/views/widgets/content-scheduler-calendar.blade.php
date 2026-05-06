<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('capell-publishing-studio::scheduler.calendar.heading') }}
        </x-slot>

        <div class="space-y-4">
            @forelse ($this->eventsByDate as $date => $events)
                <div
                    class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900"
                >
                    <div
                        class="mb-3 text-sm font-semibold text-gray-950 dark:text-white"
                    >
                        {{ CarbonImmutable::parse($date)->isoFormat('dddd D MMMM YYYY') }}
                    </div>

                    <div class="space-y-2">
                        @foreach ($events as $event)
                            <a
                                @if ($event->recordUrl !== null)
                                    href="{{ $event->recordUrl }}"
                                @endif
                                class="flex items-start justify-between gap-3 rounded-md border border-gray-100 px-3 py-2 text-sm transition hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5"
                            >
                                <span>
                                    <span
                                        class="font-medium text-gray-950 dark:text-white"
                                    >
                                        {{ $event->title }}
                                    </span>
                                    <span
                                        class="block text-gray-500 dark:text-gray-400"
                                    >
                                        {{ $event->description }}
                                    </span>
                                </span>
                                <span
                                    class="shrink-0 text-gray-500 dark:text-gray-400"
                                >
                                    {{ $event->scheduledFor->format('H:i') }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @empty
                <div
                    class="rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400"
                >
                    {{ __('capell-publishing-studio::scheduler.calendar.empty') }}
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
