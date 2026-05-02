<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Sections\Tables;

use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Mosaic\Models\Section;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SectionSelectionTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        SectionsTable::configure($table);

        $table
            ->query(function (): Builder {
                /* @var class-string<\Capell\Mosaic\Models\Section> $model */
                $model = Section::class;

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
