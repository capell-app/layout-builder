<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Actions\Page;

use Capell\Admin\Filament\Actions\CreateActionModal;
use Capell\Layout\Actions\MutateContentDataBeforeCreateAction;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Override;

class CreateContentAction extends CreateActionModal
{
    #[Override]
    protected function mutateFormData(array $data): array
    {
        return MutateContentDataBeforeCreateAction::run($data);
    }
}
