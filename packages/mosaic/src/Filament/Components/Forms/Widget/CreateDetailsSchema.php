<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\NameInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CreateDetailsSchema
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
                    TypeSelect::make('type_id')
                        ->live()
                        ->withRelation()
                        ->when(
                            $schema->isCreating(),
                            fn (TypeSelect $component): TypeSelect => $component->withCreateForm(),
                            fn (TypeSelect $component): TypeSelect => $component->withEditForm(),
                        ),
                ]),
        ];
    }
}
