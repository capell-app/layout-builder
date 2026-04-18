<?php

declare(strict_types=1);

namespace Capell\Assistant\Support\Admin;

use Capell\Admin\Contracts\Extenders\SiteHeaderActionExtender;
use Capell\Assistant\Filament\Actions\AiCreatorAction;
use Filament\Actions\Action;

class AiCreatorSiteExtender implements SiteHeaderActionExtender
{
    /** @return array<int, Action> */
    public function actions(): array
    {
        return [AiCreatorAction::make()];
    }
}
