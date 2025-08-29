<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Pages\Schemas\Types;

use Capell\Layout\Filament\Components\Forms\Page\Tab\PageLayoutTab;
use Filament\Schemas\Schema;

class ResultsPageSchema extends DefaultPageSchema
{
    protected static function getTabs(Schema $schema): array
    {
        return [
            PageLayoutTab::make(),
            ...parent::getTabs($schema),
        ];
    }
}
