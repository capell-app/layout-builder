<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Layouts\Blocks;

use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;
use Override;

class PageLayoutBlockConfigurator extends DefaultLayoutBlockConfigurator
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
