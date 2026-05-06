<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Pages\Tables;

use Capell\Admin\Contracts\DashboardReports\ActivityTrailQueryProvider;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityTrailTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => resolve(ActivityTrailQueryProvider::class)->build())
            ->columns([
                TextColumn::make('subject_type')
                    ->label('Model')
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),
                TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('causer.name')
                    ->label('Actor')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->striped()
            ->paginated();
    }
}
