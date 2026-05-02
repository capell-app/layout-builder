<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('capell-site-search::dashboard.search_overview') }}
        </x-slot>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('capell-site-search::dashboard.searches') }}
                </div>
                <div class="text-2xl font-semibold">
                    {{ number_format($totalSearches) }}
                </div>
            </div>

            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('capell-site-search::dashboard.query') }}
                </div>
                <div class="text-2xl font-semibold">
                    {{ number_format($uniqueQueries) }}
                </div>
            </div>

            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('capell-site-search::dashboard.results') }}
                </div>
                <div class="text-2xl font-semibold">
                    {{ number_format($totalResults) }}
                </div>
            </div>

            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('capell-site-search::dashboard.zero_result_rate') }}
                </div>
                <div class="text-2xl font-semibold">
                    {{ number_format($zeroResultRate, 1) }}%
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
