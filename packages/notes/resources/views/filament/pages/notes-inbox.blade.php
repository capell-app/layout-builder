<x-filament-panels::page>
    @php($counts = $this->counts())

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament::section>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('capell-notes::note.assigned_to_me') }}
            </div>
            <div class="mt-2 text-3xl font-semibold">
                {{ $counts->assigned }}
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('capell-notes::note.mentions') }}
            </div>
            <div class="mt-2 text-3xl font-semibold">
                {{ $counts->mentions }}
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('capell-notes::note.due_today') }}
            </div>
            <div class="mt-2 text-3xl font-semibold">
                {{ $counts->dueToday }}
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('capell-notes::note.overdue') }}
            </div>
            <div class="mt-2 text-3xl font-semibold">
                {{ $counts->overdue }}
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
