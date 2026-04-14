<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Actions;

use Capell\Admin\Filament\Actions\CreateAction;
use Capell\Layout\Actions\MutateContentDataBeforeFillAction;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Override;

class CreateContentAction extends CreateAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->slideOver()
            ->modalWidth(Width::ScreenLarge);
    }

    #[Override]
    protected function mutateRecordBeforeSave(Model $record, array $data): array
    {
        if (isset($data['is_published']) && $data['is_published'] === true) {
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
