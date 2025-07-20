<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\LayoutWidget;

use Capell\Layout\Filament\Components\Forms\HtmlClassInput;
use Capell\Layout\Filament\Schemas\AbstractLayoutWidgetSchema;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;

class DefaultLayoutWidgetSchema extends AbstractLayoutWidgetSchema
{
    public static function make(Schema $schema): array
    {
        return [
            Checkbox::make('hide_content')
                ->label(__('capell-admin::form.hide_content'))
                ->helperText(__('capell-admin::generic.hide_content_info')),
            HtmlClassInput::make('html_class'),
        ];
    }
}
