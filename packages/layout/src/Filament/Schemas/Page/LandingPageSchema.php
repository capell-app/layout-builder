<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Page;

use Capell\Layout\Filament\Components\Forms\Page\Tab\PageLayoutTab;
use Filament\Forms;

class LandingPageSchema extends \Capell\Admin\Filament\Schemas\Page\LandingPageSchema
{
    protected static function getTabs(Forms\Form $form): array
    {
        return [
            PageLayoutTab::make(),
            ...parent::getTabs($form),
        ];
    }
}
