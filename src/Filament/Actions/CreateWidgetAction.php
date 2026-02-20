<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Actions;

use Capell\Admin\Actions\BuildDefaultTranslationsAction;
use Capell\Admin\Filament\Actions\CreateAction;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Filament\Support\Enums\Width;
use Override;

class CreateWidgetAction extends CreateAction
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
        $data['type_id'] = Type::query()->where('type', LayoutTypeEnum::Widget)->default()->value('id');

        $data['status'] = true;

        if (empty($data['translations'])) {
            $data['translations'] = BuildDefaultTranslationsAction::run($data['site_id'] ?? null);
        }

        return $data;
    }
}
