<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,28rem)]">
        <x-filament::section>
            {{ $this->form }}

            <div class="mt-6">
                <x-filament::button
                    icon="heroicon-o-sparkles"
                    wire:click="buildPrompt"
                >
                    {{ __('capell-mcp::admin.build_prompt') }}
                </x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                {{ __('capell-mcp::admin.prepared_prompt') }}
            </x-slot>

            <x-slot name="description">
                {{ __('capell-mcp::admin.prepared_prompt_description') }}
            </x-slot>

            <textarea
                class="block min-h-96 w-full resize-y rounded-lg border-gray-300 font-mono text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900"
                readonly
            >
{{ $preparedPrompt }}</textarea
            >
        </x-filament::section>
    </div>
</x-filament-panels::page>
