<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Elements\Tables;

use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\LayoutBuilder\Models\Element;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ElementSelectionTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        ElementsTable::configure($table);

        return $table->query(function (): Builder {
            /* @var class-string<\Capell\Core\Models\Widget> $model */
            $model = Element::class;

            return $model::query();
        });
    }
}
