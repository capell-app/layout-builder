<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Actions\Page;

use Capell\Admin\Filament\Actions\CreateModalAction;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Override;

class CreateWidgetAction extends CreateModalAction
{
    #[Override]
    protected function mutateFormData(array $data): array
    {
        $data['type_id'] = Type::query()->where('type', LayoutTypeEnum::Widget)->default()->value('id');

        $data['status'] = true;

        return $data;
    }
}
