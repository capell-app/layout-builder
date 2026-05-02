<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Filament\Actions;

use Capell\Admin\Filament\Actions\CreateAction;
use Capell\ContentBlocks\Actions\MutateContentDataBeforeFillAction;
use Filament\Support\Enums\Width;
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
    protected function mutateFormData(array $data): array
    {
        return MutateContentDataBeforeFillAction::run($data);
    }
}
