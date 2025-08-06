<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Actions\Page;

use Capell\Admin\Filament\Actions\CreateModalAction;
use Capell\Layout\Actions\MutateContentDataBeforeCreateAction;
use Override;

class CreateContentAction extends CreateModalAction
{
    #[Override]
    protected function mutateFormData(array $data): array
    {
        return MutateContentDataBeforeCreateAction::run($data);
    }
}
