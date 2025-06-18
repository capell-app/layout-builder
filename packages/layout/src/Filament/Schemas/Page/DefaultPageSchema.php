<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Page;

use Capell\Layout\Filament\Components\Forms\Page\Tab\PageLayoutTab;
use Filament\Forms;

class DefaultPageSchema extends \Capell\Admin\Filament\Schemas\Page\DefaultPageSchema
{
    protected static function getTabs(Forms\Form $form): array
    {
        return [
            PageLayoutTab::make(),
            ...parent::getTabs($form),
        ];
    }
}
