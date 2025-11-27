<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Layout;

use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Filament\Resources\Widgets\Tables\WidgetsTable;
use Capell\Layout\Forms\WidgetsContainerForm;
use Capell\Layout\Livewire\ModalTableSelect;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;

class WidgetTableSelect extends ModalTableSelect
{
    #[Locked]
    public string $tableConfiguration = WidgetsTable::class;

    public ?Collection $containers = null;

    public ?string $containerKey = null;

    public function form(Schema $schema): Schema
    {
        return WidgetsContainerForm::configure(
            $schema->statePath('data'),
            $this,
        );
    }

    #[\Override]
    public function getSelectRecordsLabel(): string
    {
        return __('capell-layout::button.add_widgets_container');
    }

    public function selectRecords(): void
    {
        $this->dispatch(
            'add-widgets-to-container',
            containerKey: $this->data['container'] ?? null,
            widgets: $this->selectedRecords,
        );

        $this->resetPage();

        $this->dispatch('close-modal', id: $this->actionModalId);
    }

    protected function getTableQuery(): Builder
    {
        /* @var class-string<\Capell\Layout\Models\Widget> $model */
        $model = CapellCore::getModel(ModelEnum::Widget->name);

        return $model::with([
            'translations.language',
            'type',
        ]);
    }
}
