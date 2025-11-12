<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Layouts;

use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Layout\Filament\Resources\Layouts\Tables\LayoutsTable;

class LayoutResource extends \Capell\Admin\Filament\Resources\Layouts\LayoutResource
{
    /** @var class-string<TableConfigurator> */
    protected static string $tableConfigurator = LayoutsTable::class;
}
