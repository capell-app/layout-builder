<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Filament\Components\Forms\Content;

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
                ->withRelation(),
        ];
    }
}
