<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms;

use Capell\Core\Enums\ContainerWidthEnum;
use Filament\Forms\Components\Select;

class ContainerWidthSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-mosaic::form.container_width'))
            ->helperText(__('capell-admin::generic.container_width_helper'))
            ->options(ContainerWidthEnum::class);
    }

    public static function getDefaultName(): ?string
    {
        return 'container';
    }
}
