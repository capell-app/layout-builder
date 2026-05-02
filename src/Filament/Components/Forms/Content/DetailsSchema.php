<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\NameInput;
use Filament\Schemas\Schema;

class DetailsSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            NameInput::make('name')
                ->withTitleUpdater(),
            TypeSelect::make('type_id')
                ->withRelation()
                ->when(
                    $configurator->isCreating(),
                    fn (TypeSelect $component): TypeSelect => $component->withCreateForm(),
                    fn (TypeSelect $component): TypeSelect => $component->withEditForm(),
                ),
        ];
    }
}
