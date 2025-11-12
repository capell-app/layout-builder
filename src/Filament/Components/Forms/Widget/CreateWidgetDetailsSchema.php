<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\NameInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CreateWidgetDetailsSchema
{
    public static function make(Schema $schema): Grid
    {
        return Grid::make()
            ->visibleOn(['create', 'createOption', 'replicate'])
            ->schema(self::getSchema($schema))
            ->columnSpanFull();
    }

    private static function getSchema(Schema $schema): array
    {
        return [
            Grid::make()
                ->columnSpanFull()
                ->schema([
                    NameInput::make('name')
                        ->withTitleUpdater(),
                    WidgetTypeSelect::make('type_id')
                        ->live()
                        ->withRelation()
                        ->when(
                            $schema->isCreating(),
                            fn (WidgetTypeSelect $component): WidgetTypeSelect => $component->withCreateForm(),
                            fn (WidgetTypeSelect $component): WidgetTypeSelect => $component->withEditForm(),
                        ),
                ]),
        ];
    }
}
