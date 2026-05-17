<div class="fi-elements-table-select-modal">
    <div class="px-4">
        {{ $this->table }}
    </div>

    <form
        wire:submit.prevent="selectRecords"
        class="fi-modal-footer fi-sticky fi-align-start -bottom-4 mb-0 mt-4"
    >
        <div class="flex flex-wrap items-end justify-between gap-x-8 gap-y-4">
            <div class="mb-0 mr-auto max-w-[50%] flex-grow">
                {{ $this->form }}
            </div>

            <div class="ml-auto">
                {{ $this->selectRecordsAction() }}
            </div>
        </div>
    </form>

    <x-filament-actions::modals />
</div>
