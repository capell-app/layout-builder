<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('capell-starter-sites::page.section_heading') }}
        </x-slot>

        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('capell-starter-sites::page.section_description') }}
        </p>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
