<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\LayoutWidget;

use Filament\Forms;

class PageLayoutWidgetSchema extends DefaultLayoutWidgetSchema
{
    public static function make(Forms\Form $form): array
    {
        return [
            Forms\Components\Checkbox::make('hide_title')
                ->label(__('capell-admin::form.hide_title')),
        ];
    }
}
