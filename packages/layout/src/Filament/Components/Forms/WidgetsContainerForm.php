<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Capell\Layout\Livewire\Filament\LayoutBuilder\WidgetTableSelect;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class WidgetsContainerForm
{
    public static function configure(Schema $schema, WidgetTableSelect $component): Schema
    {
        return $schema->components([
            Select::make('container')
                ->label(__('capell-admin::form.container'))
                ->hiddenLabel()
                ->prefix(fn (Select $c): string => $c->getLabel() . ': ')
                ->required()
                ->options($component->containers),
        ]);
    }
}
