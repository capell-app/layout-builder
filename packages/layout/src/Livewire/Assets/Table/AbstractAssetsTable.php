<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Assets\Table;

use Capell\Layout\Livewire\ModalTableSelect;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Ramsey\Uuid\UuidInterface;

abstract class AbstractAssetsTable extends ModalTableSelect
{
    #[Locked]
    public array $tableArguments = [];

    public array $existingRecords = [];

    #[Locked]
    public string $type;

    #[Locked]
    public int $widgetIndex;

    #[Url(as: 'tab')]
    public ?string $activeTab = null;

    abstract public static function getResource(): string;

    public function getTableRecordKey(Model|array $record): string
    {
        return $record->id instanceof UuidInterface
            ? $record->id->toString()
            : (string) $record->id;
    }

    public function selectRecords(): void
    {
        $this->dispatch(
            'sync-selected-assets',
            arguments: $this->tableArguments,
            type: $this->type,
            assets: $this->selectedRecords,
        );

        $this->resetPage();

        $this->dispatch('close-modal', id: $this->actionModalId);
    }

    protected function shouldPersistTableFiltersInSession(): bool
    {
        return true;
    }
}
