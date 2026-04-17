<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Layouts\Schemas\Types\Widgets;

use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;
use Override;

class PageLayoutWidgetSchema extends DefaultLayoutWidgetSchema
{
    #[Override]
    public function make(Schema $schema): array
    {
        return [
            Checkbox::make('show_page_title')
                ->label(__('capell-mosaic::form.show_page_title'))
                ->helperText(__('capell-admin::generic.show_page_title_info')),
        ];
    }
}
