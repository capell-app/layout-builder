<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Layouts;

use Capell\LayoutBuilder\Filament\Resources\Layouts\Tables\LayoutsTable;

class LayoutResource extends \Capell\Admin\Filament\Resources\Layouts\LayoutResource
{
    protected static ?string $slug = 'layout-builder/layouts';

    protected static bool $isGloballySearchable = true;

    protected static string $tableConfigurator = LayoutsTable::class;
}
