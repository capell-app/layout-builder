<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\NameInput;
use Capell\Admin\Filament\Components\Forms\SyncNameWithTitle;
use Filament\Schemas\Components\Group;

class ContentDetailsSchema
{
    public static function make(): array
    {
        return [
            Group::make()
                ->extraAttributes(['class' => 'filament-form-compact'])
                ->schema([
                    NameInput::make('name')
                        ->withTitleUpdater(),

                    SyncNameWithTitle::make('sync_name_title'),
                ]),

            ContentTypeSelect::make('type_id')
                ->live()
                ->withRelation()
                ->withCreateForm()
                ->withEditForm(),
        ];
    }
}
