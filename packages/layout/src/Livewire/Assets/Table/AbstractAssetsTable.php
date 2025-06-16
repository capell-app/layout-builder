<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Assets\Table;

use Capell\Admin\Filament\Actions\BulkSelectAction;
use Capell\Layout\Livewire\LayoutBuilder;
use Closure;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

abstract class AbstractAssetsTable extends Component implements Forms\Contracts\HasForms, Tables\Contracts\HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    public string $actionId;

    public string $containerKey;

    public ?array $existingRecords = [];

    public ?bool $hasPageAssets = null;

    public ?int $pageId = null;

    public ?int $siteId = null;

    public string $type;

    public int $widgetIndex;

    abstract protected function getTableColumns(): array;

    abstract protected function getTableQuery(): Builder;

    public function getTableRecordKey(Model $record): string
    {
        return $record->uuid instanceof \Ramsey\Uuid\UuidInterface
            ? $record->uuid->toString()
            : (string) $record->uuid;
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

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                $this->getTableQuery()
                    ->when(
                        $this->existingRecords,
                        fn (Builder $query) => $query->whereNotIn('uuid', $this->existingRecords)
                    )
            )
            ->columns($this->getTableColumns())
            ->description(
                fn (self $livewire): string => $livewire->hasPageAssets === true
                    ? __('capell-admin::generic.select_page_widget_resource_description', ['type' => $this->type])
                    : __('capell-admin::generic.select_widget_resource_description', ['type' => $this->type])
            )
            ->filters($this->getTableFilters())
            ->filtersFormWidth('4xl')
            ->filtersFormColumns([
                'sm' => 2,
                'lg' => 3,
            ])
            ->bulkActions($this->getTableBulkActions());
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkSelectAction::make('selectRecords')
                ->label(__('capell-admin::button.add_widget_resource'))
                ->color('primary')
                ->action($this->syncAssets(...)),
        ];
    }

    protected function getTableFilters(): array
    {
        return [];
    }

    protected function getTableRecordClassesUsing(): ?Closure
    {
        return fn (): string => 'hover:bg-primary-500/5 cursor-pointer';
    }

    protected function shouldPersistTableFiltersInSession(): bool
    {
        return true;
    }

    protected function syncAssets(BulkSelectAction $action, $livewire): void
    {
        $this->dispatch(
            'sync-selected-assets',
            containerKey: $this->containerKey,
            widgetIndex: $this->widgetIndex,
            type: $this->type,
            hasPageAssets: $this->pageId !== null,
            assets: $livewire->selectedTableRecords,
        )
            ->to(LayoutBuilder::class);

        $this->dispatch('close-modal', id: $this->actionId);

        $action->success();
    }
}
