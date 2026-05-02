<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Pages\Tables;

use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Workspaces\Actions\Reports\BuildContentSchedulerEventsAction;
use Capell\Workspaces\Data\SchedulerEventData;
use Capell\Workspaces\Enums\SchedulerEventTypeEnum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ScheduledPublishingTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->records(fn (
                ?array $filters = null,
                ?string $search = null,
                ?string $sortColumn = null,
                ?string $sortDirection = null,
            ): Collection => self::records($filters ?? [], $search, $sortColumn, $sortDirection))
            ->columns([
                TextColumn::make('title')
                    ->label(__('capell-admin::table.name'))
                    ->size('sm')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('event_type_label')
                    ->label(__('capell-workspaces::scheduler.table.event_type'))
                    ->size('sm')
                    ->badge()
                    ->color(fn (array $record): string => $record['event_type_color'] ?? 'gray'),
                TextColumn::make('source_type')
                    ->label(__('capell-workspaces::scheduler.table.source'))
                    ->size('sm')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => (string) __('capell-workspaces::scheduler.sources.' . $state)),
                TextColumn::make('status')
                    ->label(__('capell-admin::table.status'))
                    ->size('sm')
                    ->badge(),
                TextColumn::make('scheduled_for')
                    ->label(__('capell-workspaces::scheduler.table.scheduled_for'))
                    ->size('sm')
                    ->dateTime(),
                TextColumn::make('description')
                    ->label(__('capell-workspaces::scheduler.table.description'))
                    ->size('sm')
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->label(__('capell-workspaces::scheduler.filters.event_type'))
                    ->options(SchedulerEventTypeEnum::class),
                SelectFilter::make('source_type')
                    ->label(__('capell-workspaces::scheduler.filters.source'))
                    ->options([
                        'workspace' => __('capell-workspaces::scheduler.sources.workspace'),
                        'page' => __('capell-workspaces::scheduler.sources.page'),
                    ]),
            ])
            ->recordUrl(fn (array $record): ?string => $record['record_url'] ?? null)
            ->defaultSort(column: 'scheduled_for', direction: 'asc')
            ->paginated([10, 25, 50]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private static function records(
        array $filters,
        ?string $search,
        ?string $sortColumn,
        ?string $sortDirection,
    ): Collection {
        $eventType = self::filterValue($filters, 'event_type');
        $sourceType = self::filterValue($filters, 'source_type');

        $records = BuildContentSchedulerEventsAction::run(
            eventType: $eventType !== null ? SchedulerEventTypeEnum::tryFrom($eventType) : null,
            sourceType: $sourceType,
        )->map(fn (SchedulerEventData $event): array => $event->toTableRecord());

        if (is_string($search) && $search !== '') {
            $needle = mb_strtolower($search);

            $records = $records->filter(
                fn (array $record): bool => str_contains(mb_strtolower((string) $record['title']), $needle)
                    || str_contains(mb_strtolower((string) $record['event_type_label']), $needle)
                    || str_contains(mb_strtolower((string) $record['source_type']), $needle)
                    || str_contains(mb_strtolower((string) $record['status']), $needle)
                    || str_contains(mb_strtolower((string) ($record['description'] ?? '')), $needle),
            );
        }

        $sortColumn ??= 'scheduled_for';
        $sortDirection = $sortDirection === 'desc' ? 'desc' : 'asc';

        $records = $records->sortBy(
            fn (array $record): mixed => self::sortValue($record, $sortColumn),
            descending: $sortDirection === 'desc',
        );

        return $records->values();
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private static function sortValue(array $record, string $sortColumn): mixed
    {
        return match ($sortColumn) {
            'title',
            'event_type_label',
            'source_type',
            'status',
            'description' => mb_strtolower((string) ($record[$sortColumn] ?? '')),
            'scheduled_for' => $record['scheduled_for'],
            default => $record['scheduled_for'],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private static function filterValue(array $filters, string $key): ?string
    {
        $value = $filters[$key]['value'] ?? $filters[$key] ?? null;

        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
