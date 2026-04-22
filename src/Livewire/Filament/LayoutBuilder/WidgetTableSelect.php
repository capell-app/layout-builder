<?php

declare(strict_types=1);

namespace Capell\Mosaic\Livewire\Filament\LayoutBuilder;

use Capell\Core\Facades\CapellCore;
use Capell\Mosaic\Enums\ModelEnum;
use Capell\Mosaic\Filament\Components\Forms\WidgetsContainerForm;
use Capell\Mosaic\Filament\Resources\Widgets\Tables\WidgetsTable;
use Capell\Mosaic\Livewire\Filament\ModalTableSelect;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Override;

/**
 * @property Schema $form
 */
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

    #[Override]
    public function getSelectRecordsLabel(): string
    {
        return __('capell-mosaic::button.add_widgets_container');
    }

    public function selectRecords(): void
    {
        $selectedRecords = $this->selectedRecords;

        if (! $selectedRecords || count($selectedRecords) === 0) {
            Notification::make('no-widgets-selected')
                ->body(__('capell-mosaic::message.no_widgets_selected'))
                ->warning()
                ->send();

            return;
        }

        if ($this->containerKey) {
            $containerKey = $this->containerKey;
        } else {
            $formData = $this->form->getState();
            $containerKey = $formData['container'];
        }

        $this->dispatch(
            'add-widgets-to-container',
            containerKey: $containerKey,
            widgets: $selectedRecords,
            actionModalId: $this->actionModalId,
        );

        $this->resetPage();
    }

    protected function getTableQuery(): Builder
    {
        /* @var class-string<\Capell\Mosaic\Models\Widget> $model */
        $model = CapellCore::getModel(ModelEnum::Widget->name);

        return $model::with([
            'creator',
            'editor',
            'translations.language',
            'type',
        ]);
    }
}
