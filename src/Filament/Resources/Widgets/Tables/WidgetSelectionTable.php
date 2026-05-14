<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Widgets\Tables;

use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Core\Models\Widget;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WidgetSelectionTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        WidgetsTable::configure($table);

        return $table->query(function (): Builder {
            /* @var class-string<\Capell\Core\Models\Widget> $model */
            $model = Widget::class;

            return $model::query();
        });
    }
}
