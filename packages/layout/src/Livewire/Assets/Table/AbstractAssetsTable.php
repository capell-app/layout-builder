<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Assets\Table;

use Capell\Admin\Filament\Actions\BulkSelectAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;
use Livewire\Component;
use Ramsey\Uuid\UuidInterface;

abstract class AbstractAssetsTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public string $actionModalId;

    public array $arguments = [];

    public ?array $existingRecords = [];

    public string $type;

    public int $widgetIndex;

    #[Url(as: 'tab')]
    public ?string $activeTab = null;

    abstract protected function getTableQuery(): Builder;

    abstract public static function getResource(): string;

    public function getTableRecordKey(Model|array $record): string
    {
        return $record->id instanceof UuidInterface
            ? $record->id->toString()
            : (string) $record->id;
    }

    public function mount(): void
    {
        throw_if(
            ! Filament::auth()->check(),
            AuthenticationException::class
        );
    }

    public function render(): string
    {
        return <<<'blade'
            <div>
                {{ $this->table }}
            </div>
        blade;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->getTableQuery()
                    ->when(
                        $this->existingRecords,
                        fn (Builder $query) => $query->whereNotIn('id', $this->existingRecords)
                    )
            )
            ->filtersFormWidth('4xl')
            ->filtersFormColumns([
                'sm' => 2,
                'lg' => 3,
            ])
            ->toolbarActions($this->getTableBulkActions());
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkSelectAction::make('selectRecords')
                ->label(__('capell-admin::button.add_widget_asset'))
                ->color('primary')
                ->action($this->syncAssets(...)),
        ];
    }

    protected function shouldPersistTableFiltersInSession(): bool
    {
        return true;
    }

    protected function syncAssets(BulkSelectAction $action, self $livewire): void
    {
        $selectedRecords = $livewire->getSelectedTableRecordsQuery(shouldFetchSelectedRecords: false);

        $this->dispatch(
            'sync-selected-assets',
            arguments: $this->arguments,
            type: $this->type,
            assets: $selectedRecords->pluck('id')->toArray(),
        );

        $this->dispatch('close-modal', id: $this->actionModalId);

        $action->success();
    }
}
