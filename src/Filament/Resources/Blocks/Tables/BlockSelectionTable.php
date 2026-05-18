<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Blocks\Tables;

use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\LayoutBuilder\Models\Block;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlockSelectionTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        BlocksTable::configure($table);

        return $table->query(function (): Builder {
            /* @var class-string<\Capell\LayoutBuilder\Models\Block> $model */
            $model = Block::class;

            return $model::query();
        });
    }
}
