<x-filament-widgets::widget>
    <x-filament::section heading="AI Usage">
        <div>
            <div class="text-2xl font-bold">
                {{ number_format($generationCount) }}
            </div>
            <div class="text-sm text-gray-500">Generations</div>
        </div>
        <div>
            <div class="text-2xl font-bold">
                {{ number_format($totalTokens) }}
            </div>
            <div class="text-sm text-gray-500">Total Tokens</div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
