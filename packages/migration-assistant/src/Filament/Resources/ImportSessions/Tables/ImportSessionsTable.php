<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Filament\Resources\ImportSessions\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\MigrationAssistant\Enums\ImportSessionKind;
use Capell\MigrationAssistant\Enums\ImportSessionStatus;
use Capell\MigrationAssistant\Models\ImportSession;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Foundation\Auth\User;

class ImportSessionsTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns(static::getTableColumns())
            ->filters([
                SelectFilter::make('kind')
                    ->label(__('capell-admin::exchanger.kind'))
                    ->options(ImportSessionKind::class),
                SelectFilter::make('status')
                    ->label(__('capell-admin::exchanger.status'))
                    ->options(ImportSessionStatus::class),
                SelectFilter::make('user_id')
                    ->label(__('capell-admin::exchanger.user'))
                    ->options(fn (): array => User::query()
                        ->whereIn('id', ImportSession::query()->whereNotNull('user_id')->distinct()->pluck('user_id'))
                        ->pluck('name', 'id')
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading(__('capell-admin::generic.no_import_sessions'))
            ->emptyStateDescription(__('capell-admin::generic.no_import_sessions_description'))
            ->emptyStateIcon('heroicon-o-arrow-down-tray');
    }

    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            TextColumn::make('uuid')
                ->label(__('capell-admin::exchanger.uuid'))
                ->limit(8)
                ->searchable()
                ->toggleable(),
            TextColumn::make('kind')
                ->label(__('capell-admin::exchanger.kind'))
                ->badge()
                ->sortable(),
            TextColumn::make('status')
                ->label(__('capell-admin::exchanger.status'))
                ->badge()
                ->sortable()
                ->color(fn (ImportSessionStatus $state): string => match ($state) {
                    ImportSessionStatus::Completed => 'success',
                    ImportSessionStatus::Failed => 'danger',
                    ImportSessionStatus::Running, ImportSessionStatus::Queued => 'warning',
                    default => 'gray',
                }),
            TextColumn::make('user.name')
                ->label(__('capell-admin::exchanger.user'))
                ->placeholder('—')
                ->toggleable(),
            TextColumn::make('source_filename')
                ->label(__('capell-admin::exchanger.source_filename'))
                ->searchable()
                ->toggleable(),
            TextColumn::make('result_summary')
                ->label(__('capell-admin::exchanger.result_summary_counts'))
                ->formatStateUsing(static function (mixed $state): string {
                    if (! is_array($state) || $state === []) {
                        return '—';
                    }

                    $pages = (int) ($state['pages_imported'] ?? $state['pages_created'] ?? 0);
                    $relations = (int) ($state['relations_resolved'] ?? 0);
                    $media = (int) ($state['media_ingested'] ?? $state['media_reassigned'] ?? 0);
                    $sites = count(is_array($state['created_site_ids'] ?? null) ? $state['created_site_ids'] : []);
                    $domains = count(is_array($state['created_site_domain_ids'] ?? null) ? $state['created_site_domain_ids'] : []);

                    return sprintf('P:%d · R:%d · M:%d · S:%d · D:%d', $pages, $relations, $media, $sites, $domains);
                })
                ->toggleable(),
            DateColumn::make('executed_at')
                ->label(__('capell-admin::exchanger.executed_at'))
                ->toggleable(),
            DateColumn::make('created_at')
                ->toggleable(),
        ];
    }
}
