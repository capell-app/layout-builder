<?php

declare(strict_types=1);

namespace Capell\SeoTools\Assistant\Support\Admin;

use Capell\Admin\Contracts\Extenders\PageHeaderActionExtender;
use Capell\SeoTools\Assistant\Filament\Actions\AiCreatorAction;
use Filament\Actions\Action;

class AiCreatorPageExtender implements PageHeaderActionExtender
{
    /** @return array<int, Action> */
    public function actions(): array
    {
        return [AiCreatorAction::make()];
    }
}
