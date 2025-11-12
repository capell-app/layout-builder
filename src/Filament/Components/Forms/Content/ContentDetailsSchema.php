<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\NameInput;
use Filament\Schemas\Schema;

class ContentDetailsSchema
{
    public static function make(Schema $schema): array
    {
        return [
            NameInput::make('name')
                ->withTitleUpdater(),
            ContentTypeSelect::make('type_id')
                ->withRelation()
                ->when(
                    $schema->isCreating(),
                    fn (ContentTypeSelect $component): ContentTypeSelect => $component->withCreateForm(),
                    fn (ContentTypeSelect $component): ContentTypeSelect => $component->withEditForm(),
                ),
        ];
    }
}
