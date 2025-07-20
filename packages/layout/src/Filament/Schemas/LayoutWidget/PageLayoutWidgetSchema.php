<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\LayoutWidget;

use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;

class PageLayoutWidgetSchema extends DefaultLayoutWidgetSchema
{
    public static function make(Schema $schema): array
    {
        return [
            Checkbox::make('hide_title')
                ->label(__('capell-admin::form.hide_title')),
        ];
    }
}
