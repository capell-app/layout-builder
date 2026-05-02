<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Filament\Resources\ContentBlocks\Tables;

use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\ContentBlocks\Models\ContentBlock;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContentBlockSelectionTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        ContentBlocksTable::configure($table);

        $table
            ->query(function (): Builder {
                /* @var class-string<\Capell\ContentBlocks\Models\ContentBlock> $model */
                $model = ContentBlock::class;

                return $model::query();
            })
            ->modifyQueryUsing(function (Builder $query, HasTable $livewire): Builder {
                $excludeIds = $livewire->getTableArguments()['excludeIds'] ?? [];

                return $query->when(
                    $excludeIds !== [],
                    fn (Builder $query) => $query->whereNotIn('id', $excludeIds),
                );
            });

        return $table;
    }
}
