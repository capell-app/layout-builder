<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Filament\Resources\BlockLibrary\Tables;

use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\BlockLibrary\Models\ContentBlock;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContentBlockSelectionTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        BlockLibraryTable::configure($table);

        $table
            ->query(function (): Builder {
                /* @var class-string<\Capell\BlockLibrary\Models\ContentBlock> $model */
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
