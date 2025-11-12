<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Actions;

use Capell\Admin\Filament\Actions\CreateModalAction;
use Capell\Layout\Actions\MutateContentDataBeforeFillAction;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Override;

class CreateContentModalAction extends CreateModalAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->slideOver()
            ->modalWidth(Width::SixExtraLarge);
    }

    #[Override]
    protected function mutateRecordBeforeSave(Model $record, array $data): array
    {
        if (! empty($data['is_published']) && $data['is_published'] === true) {
            $record->forceFill([
                'is_published' => true,
                'is_current' => true,
            ]);
        }

        return $data;
    }

    #[Override]
    protected function mutateFormData(array $data): array
    {
        return MutateContentDataBeforeFillAction::run($data);
    }
}
