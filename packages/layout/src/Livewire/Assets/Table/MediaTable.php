<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Assets\Table;

use Capell\Admin\Filament\Components\Tables\Columns\CuratorColumn;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class MediaTable extends AbstractAssetsTable
{
    public string $type = 'media';

    protected function getTableColumns(): array
    {
        return [
            CuratorColumn::make('url')
                ->selectableRow(),
            Tables\Columns\TextColumn::make('name')
                ->label(__('curator::tables.columns.name'))
                ->toggleable(isToggledHiddenByDefault: true)
                ->selectableRow()
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('title')
                ->label(__('capell-admin::table.title'))
                ->selectableRow()
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('ext')
                ->label(__('curator::tables.columns.ext'))
                ->sortable(),
            DateColumn::make('updated_at')->toggleable(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        /* @var class-string<\Capell\Core\Models\Media> $model */
        $model = CapellCore::getModel(ModelEnum::Media);

        return $model::query();
    }
}
