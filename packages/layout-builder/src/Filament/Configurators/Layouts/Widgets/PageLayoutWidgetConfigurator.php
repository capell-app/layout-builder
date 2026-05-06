<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Layouts\Widgets;

use Filament\FormBuilder\Components\Checkbox;
use Filament\Schemas\Schema;
use Override;

class PageLayoutWidgetConfigurator extends DefaultLayoutWidgetConfigurator
{
    #[Override]
    public function make(Schema $configurator): array
    {
        return [
            Checkbox::make('show_page_title')
                ->label(__('capell-layout-builder::form.show_page_title'))
                ->helperText(__('capell-admin::generic.show_page_title_info')),
        ];
    }
}
