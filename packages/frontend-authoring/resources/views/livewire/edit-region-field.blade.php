<div class="capell-authoring-editor">
    <form wire:submit="save">
        {{ $this->form }}

        <div class="capell-authoring-editor__actions">
            <x-filament::button type="submit">
                {{ __('capell-frontend-authoring::authoring.save') }}
            </x-filament::button>
        </div>
    </form>
</div>
