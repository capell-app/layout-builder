<?php

declare(strict_types=1);

namespace Capell\MediaLibrary\Filament\Pages\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\MediaLibrary\Actions\DashboardReports\BuildMediaHealthQueryAction;
use Capell\MediaLibrary\Models\CuratorMedia;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MediaHealthTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => BuildMediaHealthQueryAction::run())
            ->columns([
                TextColumn::make('name')
                    ->label(__('capell-admin::table.filename'))
                    ->size('sm')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('size')
                    ->label(__('capell-admin::table.size'))
                    ->size('sm')
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? round($state / 1024) . ' KB' : 'N/A')
                    ->sortable(),
                TextColumn::make('usage_count')
                    ->label(__('capell-admin::table.usage_count'))
                    ->size('sm')
                    ->state(fn (CuratorMedia $record): int => (int) ($record->getAttribute('usage_count') ?? 0))
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('capell-admin::table.media_type'))
                    ->size('sm')
                    ->badge()
                    ->sortable(),
                DateColumn::make('updated_at')
                    ->label(__('capell-admin::table.last_used'))
                    ->size('sm')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'asc');
    }
}
