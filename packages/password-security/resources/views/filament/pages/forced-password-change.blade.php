<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('capell-password-security::password_change.title') }}
        </x-slot>

        <x-slot name="description">
            {{ __('capell-password-security::password_change.description') }}
        </x-slot>

        <form wire:submit="updatePassword">
            {{ $this->form }}

            <div class="mt-6">
                <x-filament::button type="submit" icon="heroicon-o-key">
                    {{ __('capell-password-security::password_change.submit') }}
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
