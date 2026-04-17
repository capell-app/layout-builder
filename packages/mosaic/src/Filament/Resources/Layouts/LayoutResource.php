<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Layouts;

use Capell\Mosaic\Filament\Resources\Layouts\Tables\LayoutsTable;

class LayoutResource extends \Capell\Admin\Filament\Resources\Layouts\LayoutResource
{
    protected static string $tableConfigurator = LayoutsTable::class;
}
