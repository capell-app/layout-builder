<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Actions;

use Capell\Admin\Actions\BuildDefaultTranslationsAction;
use Capell\Admin\Filament\Actions\CreateAction;
use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Filament\Support\Enums\Width;
use Override;

class CreateElementAction extends CreateAction
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
        $data['blueprint_id'] = Blueprint::query()->where('type', LayoutTypeEnum::Element)->default()->value('id');

        $data['status'] = true;

        if (! isset($data['translations'])) {
            $data['translations'] = BuildDefaultTranslationsAction::run($data['site_id'] ?? null);
        }

        return $data;
    }
}
