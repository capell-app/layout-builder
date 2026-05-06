<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\FormBuilder\Concerns\InteractsWithFormBuilder;
use Filament\FormBuilder\Contracts\HasFormBuilder;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use LogicException;

class ModalTableSelect extends Component implements HasActions, HasFormBuilder, HasTable
{
    use InteractsWithActions;
    use InteractsWithFormBuilder;
    use InteractsWithTable;
    use WithoutUrlPagination;

    #[Locked]
    public ?string $actionModalId = null;

    #[Locked]
    public bool $isDisabled = false;

    #[Locked]
    public ?int $maxSelectableRecords = null;

    #[Locked]
    public string $tableConfiguration;

    #[Locked]
    public array $tableArguments = [];

    public ?array $data = [];

    #[Locked]
    public Builder|Closure $tableQuery;

    protected string $view = 'capell-layout-builder::livewire.filament.layout-builder.widgets-table-select';

    public function mount(): void
    {
        throw_if(
            ! Filament::auth()->check(),
            AuthenticationException::class,
        );
    }

    public function table(Table $table): Table
    {
        $tableConfiguration = $this->tableConfiguration;

        throw_unless(
            class_exists($tableConfiguration),
            LogicException::class,
            sprintf('Table configuration class [%s] does not exist.', $tableConfiguration),
        );

        throw_unless(
            method_exists($tableConfiguration, 'configure'),
            LogicException::class,
            sprintf(
                'Table configuration class [%s] does not have a [configure(Table $table): Table] method.',
                $tableConfiguration,
            ),
        );

        $tableConfiguration::configure($table);

        $table
            ->query($this->getTableQuery())
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([])
            ->emptyStateActions([])
            ->selectable()
            ->trackDeselectedRecords(false)
            ->filtersFormWidth('4xl')
            ->filtersFormColumns([
                'sm' => 2,
                'lg' => 3,
            ])
            ->deferFilters(false)
            ->currentSelectionLivewireProperty('selectedTableRecords')
            ->maxSelectableRecords($this->maxSelectableRecords)
            ->deselectAllRecordsWhenFiltered(false)
            ->disabledSelection($this->isDisabled)
            ->arguments($this->getTableArguments());

        return $table;
    }

    public function form(Schema $configurator): Schema
    {
        return $configurator
            ->statePath('data');
    }

    /**
     * @return array<mixed>
     */
    public function getTableArguments(): array
    {
        return $this->tableArguments;
    }

    /**
     * Default action handler. Subclasses may override.
     */
    public function selectRecords(): void {}

    public function getSelectRecordsLabel(): string
    {
        return __('capell-layout-builder::button.select_records');
    }

    public function selectRecordsAction(): Action
    {
        return Action::make('selectRecords')
            ->label($this->getSelectRecordsLabel())
            ->button()
            ->color('primary')
            ->action(fn () => $this->selectRecords());
    }

    public function render(): View
    {
        return view($this->view);
    }

    /**
     * Provide a default query resolution using the configurable $tableQuery.
     */
    protected function getTableQuery(): Builder
    {
        if ($this->tableQuery instanceof Builder) {
            return $this->tableQuery;
        }

        if (is_callable($this->tableQuery)) {
            $builder = ($this->tableQuery)();

            throw_unless($builder instanceof Builder, LogicException::class, 'Configured tableQuery callable must return an instance of Illuminate\\Database\\Eloquent\\Builder.');

            return $builder;
        }

        throw new LogicException('No table query configured. Set $tableQuery to a Builder or a callable returning a Builder, or override getTableQuery().');
    }
}
