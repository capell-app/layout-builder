<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Filament\Pages\Tables;

use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\DeveloperTools\Actions\Reports\BuildQueueHealthQueryAction;
use Capell\DeveloperTools\Actions\Reports\SummarizeFailedJobExceptionAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QueueHealthTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => BuildQueueHealthQueryAction::run())
            ->columns([
                TextColumn::make('payload')
                    ->label('Job')
                    ->formatStateUsing(function (string $payload): string {
                        $decoded = json_decode($payload, true);

                        return $decoded['displayName'] ?? 'Unknown Job';
                    }),
                TextColumn::make('queue')
                    ->label('Queue')
                    ->sortable(),
                TextColumn::make('exception')
                    ->label('Exception')
                    ->formatStateUsing(fn (?string $state): string => SummarizeFailedJobExceptionAction::run($state))
                    ->limit(100),
                TextColumn::make('failed_at')
                    ->label('Failed At')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->striped()
            ->paginated();
    }
}
