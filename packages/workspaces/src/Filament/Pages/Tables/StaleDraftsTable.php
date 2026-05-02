<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Pages\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Workspaces\Actions\Reports\BuildStaleDraftsQueryAction;
use Capell\Workspaces\Filament\Resources\Pages\Actions\DiscardDraftsBulkAction;
use Capell\Workspaces\Filament\Resources\Pages\Actions\RequestReviewBulkAction;
use Capell\Workspaces\Models\Workspace;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StaleDraftsTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => BuildStaleDraftsQueryAction::run())
            ->columns([
                TextColumn::make('name')
                    ->label(__('capell-admin::table.name'))
                    ->size('sm')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('capell-admin::table.status'))
                    ->badge()
                    ->size('sm'),
                DateColumn::make('updated_at')
                    ->label(__('capell-admin::table.last_updated'))
                    ->size('sm')
                    ->sortable(),
                TextColumn::make('days_stale')
                    ->label(__('capell-admin::table.days_stale'))
                    ->alignEnd()
                    ->size('sm')
                    ->state(fn (Workspace $record): int => (int) $record->updated_at?->diffInDays(now()))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy(
                        'updated_at',
                        $direction === 'asc' ? 'desc' : 'asc',
                    )),
            ])
            ->filters([
                Filter::make('days_threshold')
                    ->label(__('capell-admin::filter.days_threshold'))
                    ->schema([
                        TextInput::make('days')
                            ->numeric()
                            ->default(14)
                            ->minValue(1)
                            ->maxValue(365),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $days = (int) ($data['days'] ?? 14);

                        return $query->where('updated_at', '<', now()->subDays($days));
                    }),
            ])
            ->toolbarActions([
                RequestReviewBulkAction::make(),
                DiscardDraftsBulkAction::make(),
            ])
            ->defaultSort('updated_at', 'asc');
    }
}
